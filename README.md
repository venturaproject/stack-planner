# Boilerplate Planner

Planner con SPA React independiente y backend Symfony DDD/CQRS, construido sobre la
estructura e infraestructura de [`boilerplate-symfony-ddd`](../boilerplate-symfony-ddd).
La aplicación no usa Laravel, Blade ni Inertia.

## Stack

- Symfony 8.1 / PHP 8.4, Doctrine ORM y PostgreSQL 16.
- API interna `/api/*` con JWT en cookies httpOnly y CSRF double-submit.
- API externa opcional `/api/v1/*` con API Platform.
- React 19, TypeScript, Vite, React Router, TanStack Query y shadcn/ui.
- Docker Compose con backend, worker, frontend, PostgreSQL, nginx y Mailpit.

## Arranque local

Docker está configurado con puertos propios para convivir con otros proyectos:

```bash
cp .env.example .env
make up
make ps
make seed
```

- App: http://localhost:8093
- API docs: http://localhost:8093/api/v1/docs
- Mailpit: http://localhost:8025
- Demo: `admin@admin.com` / `admin123`

`make seed` crea los datos iniciales; `make seed-force` reconstruye el proyecto DEMO y sus
issues, épicas, sprints, comentarios, checklist, worklogs y actividad. `make down` detiene los
servicios y conserva la base de datos. Para inicializar el esquema manualmente: `make migrate`.

## Dominio Planner

Bounded contexts: Project, Issue, Planning (épicas/sprints), Auth, Notification y Dashboard.
La demo siembra el conjunto `DEMO` del proyecto de origen, incluyendo 14 issues, 4 épicas,
4 sprints y su historial de comentarios, checklist, enlaces, actividad y worklogs. Hay
también usuarios de demo `Laura Gómez`, `Marcos Ruiz` y `Elena Torres` (`demo123`).
Las reglas de capas, contratos frontend/API y guía de adaptación están en
[`docs/ARCHITECTURE-PLANNER.md`](docs/ARCHITECTURE-PLANNER.md). La arquitectura general del
backend está documentada en [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

## Documentación

- [Arquitectura del Planner y sus bounded contexts](docs/ARCHITECTURE-PLANNER.md)
- [Arquitectura general del backend DDD/CQRS](docs/ARCHITECTURE.md)

## Estructura del repositorio

- `backend/`: aplicación Symfony, organizada por capas y bounded contexts.
- `frontend/`: SPA React/Vite, incluyendo `src/`, `public/` y su configuración Node/pnpm.
- `infrastructure/`: Dockerfiles y configuración de nginx/runtime.
- `docs/`: decisiones y guías de arquitectura.
- `compose*.yml`, `Makefile` y `.env.example`: orquestación y operaciones del proyecto.

La configuración de Node, pnpm, Vite, TypeScript, Vitest, PostCSS, Oxlint y Prettier vive en
`frontend/`. El build de Docker y CI usan esa carpeta como raíz de trabajo del frontend.

La SPA conserva el shell visual y los módulos Planner de `boilerplate-projects_management`
(dashboard, proyectos, tablero, backlog, cronograma, épicas, sprints, informes, issues y
mi trabajo), navegando con React Router y consumiendo la API mediante servicios tipados y
TanStack Query. No se usa Inertia ni se copian modelos Eloquent/controladores; Symfony usa
Commands/Queries y read models en sus bounded contexts.

Consulta los comandos adicionales con `make help`. Los servicios pueden inspeccionarse con
`docker compose -f compose.dev.yml ps`.
