<p align="center">
  <img src="docs/demo_logo.png" alt="Boilerplate Planner — gestión de proyectos para equipos de desarrollo" width="100%">
</p>

# Boilerplate Planner

Planner con SPA React independiente y backend Symfony DDD/CQRS. La aplicación no usa Laravel,
Blade ni Inertia.

## Stack

- Symfony 7.4 LTS / PHP 8.4, Doctrine ORM y PostgreSQL 16.
- API interna `/api/*` con JWT en cookies httpOnly y CSRF double-submit.
- API externa `/api/v1/*` con API Platform 5, tokens Bearer y scopes por recurso.
- React 19, TypeScript, Vite, React Router, TanStack Query y shadcn/ui.
- Docker Compose con backend, worker, frontend, PostgreSQL, nginx y Mailpit.

## Arranque local

Requisitos: Docker con el plugin Compose v2 y `make`. Docker está configurado con puertos propios
para convivir con otros proyectos:

```bash
cp .env.example .env
cp backend/.env.example backend/.env
make up
make ps
```

El backend instala Composer, genera las claves JWT, aplica migraciones y carga los datos iniciales
al arrancar por primera vez. Espera a que `make ps` muestre los servicios saludables antes de
usar la aplicación. Si necesitas volver a cargar los datos iniciales, ejecuta `make seed`.

- App: http://localhost:8093
- API docs: http://localhost:8093/api/v1/docs
- Mailpit: http://localhost:8025
- Demo: `admin@admin.com` / `admin123`

`make seed-force` reconstruye el proyecto DEMO y sus issues, épicas, sprints, comentarios,
checklist, worklogs y actividad. `make down` detiene los servicios y conserva la base de datos.
El entrypoint del backend aplica las migraciones al arrancar; para ejecutarlas manualmente usa
`make migrate`.

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

## API externa de Planner

La API externa usa API Platform 5 y acepta clientes API creados desde **Usuarios → Clientes API**.
Al crear un cliente, selecciona los permisos necesarios; el secreto se muestra una sola vez.
Los scopes disponibles para Planner son:

- `projects:read`: listar y buscar todos los proyectos.
- `tasks:read`: listar, buscar y consultar tareas de todos los proyectos.
- `tasks:write`: crear tareas, reemplazarlas y cambiar su estado.
- `*`: acceso completo a todos los recursos y operaciones disponibles.

Los proyectos se exponen en lectura. La creación de proyectos sigue siendo una operación interna,
porque requiere asignar un propietario de usuario. Los recursos externos de tareas corresponden al
contexto interno `Issue`.

Canjea el `clientId` y `clientSecret` por un token:

```bash
curl --request POST http://localhost:8093/api/v1/auth/token \
  --header 'Content-Type: application/ld+json' \
  --header 'Accept: application/ld+json' \
  --data '{"clientId":"<CLIENT_ID>","clientSecret":"<CLIENT_SECRET>"}'
```

Envía el `accessToken` devuelto como `Authorization: Bearer <ACCESS_TOKEN>`. El access token dura
una hora; se renueva con `POST /api/v1/auth/refresh` y el `refreshToken`. Solicitar un token nuevo
con las credenciales del cliente invalida el token anterior de ese cliente.

| Método | Ruta | Permiso |
| --- | --- | --- |
| `GET` | `/api/v1/projects` | `projects:read` |
| `QUERY` | `/api/v1/projects/search` | `projects:read` |
| `GET` | `/api/v1/tasks` | `tasks:read` |
| `GET` | `/api/v1/tasks/{id}` | `tasks:read` |
| `QUERY` | `/api/v1/tasks/search` | `tasks:read` |
| `POST` | `/api/v1/tasks` | `tasks:write` |
| `PUT` | `/api/v1/tasks/{id}` | `tasks:write` |
| `PATCH` | `/api/v1/tasks/{id}/status` | `tasks:write` |

Las operaciones `QUERY` siguen el método de API Platform 5 y reciben sus criterios en JSON en el
cuerpo de la petición, por ejemplo `{"projectId":1,"status":"in_progress","search":"login"}`.
La documentación OpenAPI interactiva está en [`/api/v1/docs`](http://localhost:8093/api/v1/docs).

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
