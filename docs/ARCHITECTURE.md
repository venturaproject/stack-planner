# Architecture

## Layers

`backend/src/` is split into three layers per bounded context (`Auth`, `Customer`,
`Product`, `Order`, `Notification`, `ApiClient`, `AuditLog`, `Dashboard`):

```
src/
├── Domain/{Context}/
│   ├── Entity/          # aggregates protect their own invariants
│   ├── ValueObject/
│   ├── Event/           # domain events, e.g. OrderPlaced, UserPasswordChanged
│   ├── Repository/      # write ports only — no Doctrine here
│   └── Exception/
├── Application/{Context}/
│   ├── Command/         # write use case + handler
│   ├── Query/           # read use case + read-model ports
│   ├── Security/        # application ports for authentication capabilities
│   ├── Shared/          # application ports: storage, mail, buses, transactions
│   └── DTO/
└── Infrastructure/
    ├── Persistence/Doctrine/     # write repositories, mappings, outbox listener
    ├── Outbox/                   # post-commit domain-event consumer
    ├── ApiPlatform/              # Resource/Provider/Processor for the external /api/v1
    ├── Http/Controller/          # plain controllers for the internal /api
    ├── Http/Security/            # authenticators, CSRF listener, JWT claims
    ├── Security/                 # Symfony Security, JWT and password adapters
    ├── Mail/                     # SymfonyMailer — the Mail port's only adapter
    └── AuditLog/                 # subscribes to Domain events, writes audit_logs
```

**Rule of thumb:** `Domain` never imports `Application`, `Infrastructure`, Symfony, or
Doctrine. Aggregates protect their own invariants; business rules belong to the Domain
layer and live in the domain object that naturally owns them: aggregate, value object,
domain service, policy, specification, or factory.

**Accepted exception:** `Role`, `User`, and `Order` type their to-many properties
(`permissions`, `roles`, `lines`) as `Doctrine\Common\Collections\Collection`, not a plain
array. This is a real constraint, not an oversight: these are native Doctrine associations
(`mapManyToMany`/`mapOneToMany`, with cascade/orphanRemoval for `Order.lines`) declared in
`Infrastructure/Persistence/Doctrine/Mapping/`, and Doctrine hydrates them by assigning a
`PersistentCollection` into the property — a typed-`array` property would throw a
`TypeError` the moment Doctrine tries. Decoupling this fully would mean these entities stop
being Doctrine's directly-managed association targets (repositories hand-rolling the
join-table reads/writes and cascade/orphan-removal diffing instead) — a materially bigger,
riskier change than this exception, so it's deliberately not made. No ORM mapping
*attributes* leak into these classes (mapping stays external, per above); this is the one
Doctrine type these entities keep, and only for the `Collection` interface, never behavior.

`Application` imports Domain types and application-owned ports, never concrete
Infrastructure classes or framework packages. `Infrastructure` is the only layer that
knows Doctrine, Symfony Messenger, Lexik JWT, or other third-party libraries. CQRS
handlers are registered from `config/services/application_handlers.php`; handler classes
carry no Symfony Messenger attributes. Handlers with special service arguments that are
explicitly redefined in context service files must keep their `messenger.message_handler`
tag there as well.

Application Command DTOs' input-shape validation (`NotBlank`, `Email`, `Length`, ...) is
already framework-free: constraints live as external YAML mapping under
`config/validator/`, wired via `framework.validation.mapping.paths`, instead of
`#[Assert\...]` attributes on the classes themselves. `#[MapRequestPayload]` in the
Infrastructure controllers still validates automatically against this same metadata —
only where the constraint is declared moved, not the runtime behavior.

## Pragmatic design

Add an abstraction only when it protects a boundary, a business rule, testability, or a
reasonable evolution path. Do not add one merely because a named pattern exists.

The architecture leaves room for domain services, specifications, policies, factories,
process managers, sagas, and richer read models. Introduce them only when the domain has
a concrete need: an invariant spanning objects, a reusable decision rule, or a workflow
that requires explicit coordination and compensation. Prefer the smallest correct design
until that need is real.

## CQRS

Every write goes through a **Command** dispatched on `command.bus`; every read through a
**Query** on `query.bus`. Symfony Messenger is an Infrastructure adapter behind
`Application/Shared/Bus/{Command,Query}BusInterface`; handler registration belongs to
Infrastructure service configuration, never Application metadata.

```php
// Application/Auth/Command/CreateUserCommand.php — plain data
final readonly class CreateUserCommand { public function __construct(... ) {} }

// Application/Auth/Command/CreateUserHandler.php
final class CreateUserHandler {
    public function handle(CreateUserCommand $cmd): UserResponse { ... }
}
```

Controllers/Resources/Processors are thin: deserialize the request into a Command/Query,
dispatch it, map the result to a response DTO. No business logic there.

## Domain events

Two ways an event reaches the outbox — pick the one matching where the event comes from,
don't mix them for the same event:

- **Aggregate-raised** (the default). Aggregates use `RecordsDomainEvents` and call
  `$this->raise(new SomeEvent(...))` from a semantic method, never from a handler.
  Doctrine's `DoctrineOutboxListener` serializes pending events to `outbox_events` using
  the same database transaction as the aggregate — no event listener is called during
  `flush`, and it's atomic with the aggregate's own write for free.
- **Explicitly published**, via `DomainEventPublisherInterface` (`OutboxPublisher`) — for
  the rare case where a handler needs to record something happened without an aggregate
  producing it (`LoginHandler`/`ImpersonateUserHandler`: a login isn't a change to the
  `User` aggregate). Because this is a second write, separate from whatever else the
  handler persisted, `command.bus` runs `doctrine_transaction` middleware so the whole
  `handle()` call — every repository save *and* every `publish()` — commits or rolls back
  together.

A third, unrelated thing that also happens to use Symfony Messenger: `MailerInterface`
queues outgoing email as `SendEmailMessage` on the `async` transport. That's mail
delivery, not a domain event — it never touches `outbox_events` and has no bearing on the
two paths above.

After commit, `app:outbox:consume` claims rows with `FOR UPDATE SKIP LOCKED`, restores the
captured audit context, dispatches the typed event, and marks it processed. Failed rows
are retried with exponential backoff, up to `OutboxConsumer::MAX_ATTEMPTS`; past that the
row is marked `failed_at` instead of retried forever — `app:outbox:failed` /
`app:outbox:requeue` inspect and reprocess those. The worker runs this consumer under
Supervisor. This gives at-least-once delivery: a redelivered event must not be able to
duplicate whatever it does. `AuditLogRecorder` and `CreateNotificationHandler` dedupe by
the outbox row's id for exactly this reason — any new consumer that has an external side
effect (not just "compute a response") needs the same treatment, not just the audit ones.

`Infrastructure/AuditLog/AuditLogSubscriber` listens to most domain events and decides
what is safe to persist to `audit_logs`, hash-chained for tamper-evidence — writes to that
chain are serialized with a Postgres advisory lock (`AuditLogRepositoryInterface::
withHashChainLock()`) since `app:outbox:consume` supports multiple concurrent workers.
Domain events must never carry plaintext passwords, access tokens, or other credentials.
New-user activation uses a signed, expiring, single-use link; password changes only
notify the user.

## Ports & adapters (beyond the CQRS buses)

Two more examples of the same pattern, worth copying for anything else that talks to a
third-party library:

- `Application/Shared/Mail/MailerInterface` → `Infrastructure/Mail/SymfonyMailer`. Event
  handlers call `$mailer->send(to, subject, template, context, attachments)` and know
  nothing about Symfony Mailer, Twig, `TemplatedEmail`, or `MAILER_FROM_ADDRESS`.
- `Application/Shared/Bus/{Command,Query}BusInterface` → `Infrastructure/Bus/Messenger*Bus`.
- `Application/Auth/Security/{AccessTokenIssuer,PasswordHasher}Interface` → Lexik JWT and
  Symfony password-hasher adapters under `Infrastructure/Security/`.
- `Application/Shared/Storage/FileStorageInterface` →
  `Infrastructure/Storage/LocalFileStorage`.
- `Application/Shared/Outbox/{AuditContext,EmailIdempotencyGuard}Interface` →
  `Infrastructure/Outbox/{AuditContext,EmailIdempotencyGuard}`. The Application-side
  handlers that react to a redelivered outbox event (`OnUserCreatedHandler`,
  `SendWelcomeEmailOnUserCreated`, ...) only need `outboxEventId()` / `alreadySent()` /
  `markSent()` — they depend on these two narrow ports, never on the concrete classes.

Application event handlers that react to a domain event (`Application/{Context}/
EventHandler/`) are plain classes — no `Symfony\Component\EventDispatcher\
EventSubscriberInterface`, no `getSubscribedEvents()`. Wiring a handler's method to the
event it reacts to is Infrastructure's job, done with an explicit
`->tag('kernel.event_listener', ['event' => SomeEvent::class, 'method' => 'handle'])` in
`config/services/{context}.php` — see `config/services/outbox.php` and `auth.php`. This
mirrors the target shape described above for CQRS handler registration: the framework
mechanism lives in Infrastructure config, not as an interface or attribute the Application
class itself carries.

## Two APIs, one CQRS core

Both the internal admin API (`/api/*`, plain Symfony controllers, JWT-in-cookie auth)
and the external API (`/api/v1/*`, API Platform Resources, client-credentials Bearer
tokens) dispatch the *same* Commands/Queries — they're just two different
Infrastructure-layer entry points into the same Application layer. This is why adding a
field to a command's validation, for instance, automatically applies to both.

Domain side effects triggered by domain events flow through the transactional Outbox and
are handled only after commit. Explicit asynchronous application jobs may be Commands
dispatched through `command.bus` and routed by Messenger to an async transport. Keep
these mechanisms separate: do not dispatch commands from domain-event handlers unless a
deliberately designed process manager with compensation is required.

The `worker` runs both `app:outbox:consume` and `messenger:consume async` under
Supervisor. Mailer messages are routed to the `async` transport (`messenger_messages`).

## Adding a new bounded context

Using an existing context (`Product` is a good, small template) as a reference:

1. `Domain/{Context}/Entity/`, `ValueObject/`, `Event/`, `Repository/{X}RepositoryInterface`.
2. `Infrastructure/Persistence/Doctrine/Mapping/App.Domain.{Context}.Entity.{X}.php`
   (PHP-array Doctrine mapping — see any existing one for the format) +
   `Doctrine{X}Repository implements {X}RepositoryInterface`.
3. `Application/{Context}/Command/` + `Query/` for each use case, each with its own
   plain-PHP handler. Register handlers on `command.bus`/`query.bus` from Infrastructure
   service configuration; do not add Symfony attributes to Application classes.
4. `config/services/{context}.php` — alias the repository interface to its Doctrine
   implementation (`$services->alias(XRepositoryInterface::class, DoctrineXRepository::class)`).
   Picked up automatically: `config/services.php` imports `services/*.php`.
5. A migration: `make migration NAME=Create{X}` then `make diff` (or hand-write it) +
   `make migrate`.
6. Expose it: either a plain controller under `Infrastructure/Http/Controller/` (internal
   API, mirrors existing ones like `ProductController`) and/or an API Platform
   `Resource`/`Provider`/`Processor` under `Infrastructure/ApiPlatform/` (external
   `/api/v1`, only if it should be reachable by machine clients).
7. Frontend: `frontend/src/services/{context}-api.ts`, `pages/{context}/`,
   `routes/{context}-route.tsx`, register the route in `app-routes.tsx` and
   `lib/route-modules.ts` (the latter is what makes sidebar hover-prefetch work).

## Testing

- `tests/Unit/` — pure PHP, no container, no DB. Mock everything.
- `tests/Integration/` — real DB via `App\Tests\Support\DatabaseKernelTestCase`.
- `tests/Functional/` — real HTTP requests via `App\Tests\Support\DatabaseWebTestCase`
  (a `KernelBrowser`).

Test isolation is handled by `dama/doctrine-test-bundle` (wired via `phpunit.dist.xml`'s
`<extensions>` block) — it wraps each test in a savepoint that's always rolled back, even
across the EntityManager resets Doctrine triggers on certain exceptions, which a naive
`$em->beginTransaction()`/`rollback()` in the base test classes does *not* survive. Don't
remove it or hand-roll transaction handling in a new base test class.

**If you copy `compose.dev.yml`'s pattern for a new environment, don't hardcode
`DATABASE_URL`/`MAILER_DSN`/etc. as a container `environment:` value** — a real process
env var always wins over `backend/.env.*`, silently defeating Symfony's own
`APP_ENV`-based config cascade. This bit us once already (every test run was silently
hitting the dev database instead of `boilerplate_ddd_test`); let `backend/.env`,
`.env.test`, `.env.dev` do their job instead.

Frontend: `frontend/src/**/*.test.ts(x)` via Vitest. Favor testing extracted pure
functions (see `pages/users/user-payload.ts`) over mounting full pages — it's what
caught two real bugs (payload casing, camelCase/snake_case mismatches) this boilerplate
shipped with early on.
