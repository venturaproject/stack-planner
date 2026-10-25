# Backend Architecture Audit

**Date:** 2026-10-25
**Scope:** `backend/src/` and the conventions documented in [`ARCHITECTURE.md`](ARCHITECTURE.md).
**Requested reference:** `ARQUITECTURA_SOLID_Y_DTOS.md` could not be found in the available paths during the review. This report therefore uses the repository's current architecture guide as its baseline and can be cross-checked against the original reference if it becomes available.

## Executive summary

The backend largely follows DDD and Clean Architecture: the Domain layer does not import Symfony, Doctrine, or Infrastructure; Application depends on ports and Domain types; and concrete adapters are concentrated in Infrastructure. No critical dependency inversion was identified.

The initial review found repository lookups in two controllers, generic output contracts, and Messenger attributes in Application. **All three findings have been addressed**: reads now go through Queries/Handlers, Issue/Project array responses have precise PHPStan shapes, and Messenger handlers are registered in Infrastructure. One localized repository lookup remains in a console command; its impact is lower because the command is an Infrastructure entry point.

## Findings

| ID | Severity | Finding | Assessment |
|---|---|---|---|
| A-01 | Resolved | HTTP controllers queried repositories directly | Reads were moved to Application queries/handlers. |
| A-02 | Resolved | Application responses used generic arrays | Issue/Project responses now declare precise shapes; arrays remain a pragmatic HTTP contract. |
| A-03 | Resolved | Symfony `AsMessageHandler` attributes in Application | Attributes were removed and handlers are registered through Infrastructure configuration. |
| A-04 | Low | Console command queries a Domain repository directly | Localized Infrastructure shortcut; role selection could go through Application if the flow is unified. |
| A-05 | Accepted exception | Doctrine Collections in three entities | Explicitly justified in `ARCHITECTURE.md`; no other framework/ORM imports were found in Domain. |

### A-01 — Repository access from controllers

The initial review found these direct lookups:

- [`ProjectMemberController.php`](../backend/src/Infrastructure/Http/Controller/Project/ProjectMemberController.php#L29) injected `ProjectRepositoryInterface` and checked for the project before dispatching `ListProjectMembersQuery` (lines 37–45). Access checking also takes place at the entry point through an Infrastructure policy.
- [`IssueAttachmentController.php`](../backend/src/Infrastructure/Http/Controller/Issue/IssueAttachmentController.php#L30) queried `IssueAttachmentRepositoryInterface` in `download()` and performed attachment ownership/MIME checks in the HTTP adapter (lines 71–84).

**Fix applied:** project existence is now checked by `ListProjectMembersHandler`. Attachment metadata is retrieved through `GetIssueAttachmentQuery` and `IssueAttachmentDownloadResponse`. The controller is left with HTTP protocol concerns, entry-point authorization, and file content retrieval through the storage port.

### A-02 — Array-based output contracts

Response DTOs are used in several contexts, but not uniformly. For example, [`GetIssueDetailHandler.php`](../backend/src/Application/Issue/Query/GetIssueDetailHandler.php#L17) initially returned `array<string, mixed>` and built nested structures manually; [`CreateIssueHandler.php`](../backend/src/Application/Issue/Command/CreateIssueHandler.php#L23) also returned a generic array. Other Issue/Project/Planning handlers documented their array shapes, but still used PHP arrays.

**Assessment:** this is not inherently a DDD violation—arrays can be suitable for CQRS read models—but `mixed` and associative structures make field renames or type changes harder to detect and leave part of the contract implicit. `QueryBusInterface::ask()` and `CommandBusInterface::dispatch()` also return `mixed`, as a consequence of the generic bus rather than an isolated defect.

**Fix applied:** the identified `array<string, mixed>` responses and generic nested shapes were replaced with PHPDoc array shapes that explicitly declare fields and types. Dedicated DTO classes were not introduced for every read projection: precise arrays preserve the current JSON shape and are verifiable by PHPStan.

### A-03 — Symfony attributes in Application

The initial review found `Symfony\Component\Messenger\Attribute\AsMessageHandler` on Application handlers. These attributes have since been removed. `config/services/application_handlers.php` registers handlers by convention, while context service files explicitly retain Messenger tags for handlers whose definitions are overridden for special configuration. No Symfony/Doctrine/ApiPlatform/Infrastructure imports remain in Application.

**Fix applied:** handler registration is documented in `ARCHITECTURE.md`. Both buses were verified with `debug:messenger` and the complete test suite.

### A-04 — Repository access from a console command

[`CreateUserCommand.php`](../backend/src/Infrastructure/Console/Auth/CreateUserCommand.php#L35) injects `RoleRepositoryInterface` and resolves role names before dispatching the create/activate commands (lines 59–77). Since it resides in Infrastructure, it does not violate the inward dependency rule, but the adapter coordinates a read outside the bus.

**Recommendation:** if this command is to remain a supported interface, add a Query/Handler to resolve roles, or accept IDs and delegate the full coordination to Application. Given that it is oriented toward testing/seeding and reuses the create/activate use cases, it can remain a pragmatic exception as long as it does not duplicate invariants.

### A-05 — Doctrine Collections dependency in Domain

The only external dependency identified in Domain is `Doctrine\Common\Collections` in `Auth/Entity/Role.php`, `Auth/Entity/User.php`, and `Order/Entity/Order.php`. This matches the exception described in [`ARCHITECTURE.md`](ARCHITECTURE.md): Doctrine hydrates these associations with `PersistentCollection`; no ORM mapping attributes were found on the entities.

**Assessment:** this is a deliberate, contained exception justified by the current mapping. No change is recommended as part of this audit.

## Practices that are working well

- No imports of Application, Infrastructure, Symfony, ApiPlatform, or Doctrine ORM/DBAL were found in Domain. The three Doctrine Collections dependencies above are the documented exception.
- Application does not access `EntityManager`, DBAL, or concrete Infrastructure classes directly; use cases primarily depend on repositories/read models and ports defined in inner layers.
- Concrete persistence and Doctrine usage are in Infrastructure, along with Symfony controllers, authentication, adapters, and operational commands.
- Input DTO validation is described in the architecture guide as external YAML metadata, avoiding Symfony validation attributes on Application classes.
- Commands/Queries, read/write ports, and response DTOs are already established across several contexts. The recommendations focus on consistency and contracts rather than adding layers indiscriminately.

## Recommended follow-up

1. Keep HTTP reads within Application use cases (A-01).
2. Keep array shapes precise; promote a response to a dedicated DTO if an Issue/Project projection becomes an independently shared contract (A-02).
3. Keep Messenger tags in Infrastructure when adding or reconfiguring handlers (A-03).
4. Revisit the console command's direct repository lookup only if its scope grows or it begins to duplicate logic (A-04).

## Review limitations

This was a static architecture review covering namespaces, dependencies, entry points, and selected contracts. It does not certify every aggregate business rule and does not replace functional tests. No application code was changed as part of the original audit.
