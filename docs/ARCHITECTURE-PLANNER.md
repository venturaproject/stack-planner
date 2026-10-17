# Arquitectura del Planner

El backend sigue las convenciones detalladas en
[`ARCHITECTURE.md`](ARCHITECTURE.md), que son la guía vigente dentro de este repositorio.
Este documento concreta cómo aplicarlas al dominio Planner.

## Bounded contexts iniciales

- **Project**: proyectos, clave, miembros, configuración, tableros y campos configurables.
- **Issue**: tareas, tipo, estado, prioridad, asignaciones, comentarios, adjuntos, checklist,
  worklogs y relaciones entre issues.
- **Planning**: épicas, sprints, capacidad y ciclo de trabajo.
- **Auth**: usuarios, roles, permisos, sesiones y perfil.
- **Notification**: notificaciones, preferencias y lectura/eliminación.
- **Dashboard**: read models agregados para resumen, métricas y actividad reciente.

No crear un bounded context por pantalla o tabla. Mantener una regla/invariante en el
aggregate que la protege y separar contexts cuando haya reglas y lenguaje propios.

## Reglas backend

- `Domain/{Context}` mantiene entidades/agregados, value objects, eventos, excepciones y
  puertos de repositorio de escritura. No depende de Symfony, Doctrine ni Application.
- `Application/{Context}` contiene Commands/handlers para escrituras, Queries/handlers y
  read-model ports para lecturas, DTOs y puertos compartidos.
- `Infrastructure` implementa Doctrine, transporte, filesystem, seguridad y entrada HTTP.
- CQRS: todas las escrituras se ejecutan como Commands y todas las lecturas como Queries.
  Los handlers coordinan casos de uso; controllers, API Resources y processors son capas
  finas que validan/transforman el transporte, despachan y serializan DTOs.
- Mantener Doctrine mapping fuera de Domain. Persistir eventos en el outbox dentro de la
  transacción de escritura y procesarlos después del commit; los consumidores deben ser
  idempotentes.
- Separar API interna de administración (`/api/*`, sesión/JWT en cookies con CSRF) de la
  API pública versionada (`/api/v1/*`, API Platform y credenciales/scope propios) si se
  expone integración de terceros. Ambas deben compartir Commands/Queries, no lógica.
- Validaciones de payload y errores API deben seguir los patrones centralizados existentes
  en Symfony DDD, con respuestas literales y contratos estables.

## Contrato y frontend

- El frontend es una SPA; no importa ni usa `@inertiajs/react`, Blade, rutas Laravel o props
  compartidas de Inertia.
- Cada bounded context tiene servicios API (`services/{context}-api.ts`) tipados y rutas
  React propias. Los componentes llaman servicios; TanStack Query gestiona cache, carga e
  invalidación. Los componentes no construyen URLs HTTP ni acceden directamente a Axios.
- Mantener DTOs de transporte separados de las entidades del backend. Definir camelCase y
  paginación/error contracts explícitos en la API; no exponer agregados Doctrine.
- La autenticación web usa cookies httpOnly y protección CSRF double-submit, sin guardar
  tokens de sesión en `localStorage`. Las mutaciones envían `X-CSRF-Token` desde la cookie
  legible `csrf_token`.
- Permisos y capacidades recibidos desde API son datos de UI para ocultar/mostrar acciones;
  la autorización efectiva siempre se valida en backend.

## Adaptación del producto de origen

La aplicación React, su shell visual y los fixtures de `boilerplate-projects_management`
son la referencia funcional y de demo. Se portan a SPA/API manteniendo nombres y flujos de
pantalla. No trasladar modelos Eloquent, controllers, rutas Inertia ni contratos Laravel.
Las listas y filtros usan read models/API DTOs; las escrituras usan Commands. Los archivos
de demo viven en `frontend/public/demo` y se sirven con la API/NGINX, mientras las subidas
nuevas se abstraen mediante el puerto de almacenamiento de Application.

El fixture Planner refleja `ProjectSeeder` y `AcceptanceTestDataSeeder`: proyecto DEMO,
issues DEMO-1..14, cuatro épicas, cuatro sprints, checklists, comentarios, worklogs,
dependencias, actividad, usuarios de demo, avatares y el adjunto LISTROWS de DEMO-4.
