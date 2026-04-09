# Ticketera

API REST para gestión de eventos, venta de entradas y validación de acceso construida con Laravel 13. El sistema permite a organizadores crear eventos con distintos tipos de entrada, a clientes comprar tickets y a personal autorizado validar el ingreso mediante check-in.

## Integrantes

- Javier Andres Chavez Portal
- Erick Daniel Pineda Baires
- Sebastian Alberto Dimas Rodriguez

## Descripción General

`Ticketera` resuelve el flujo completo de administración y consumo de eventos:

- gestión de usuarios con roles y permisos
- autenticación por token usando Sanctum
- publicación y consulta de eventos activos
- compra de entradas con reglas de negocio
- control de acceso en puerta mediante validación de tickets
- documentación OpenAPI con Swagger UI
- pruebas automatizadas con Pest

## Objetivos Del Proyecto

- aplicar principios RESTful en el diseño de endpoints
- implementar autenticación y autorización por roles
- modelar reglas de negocio reales sobre disponibilidad de tickets
- documentar la API de forma clara y usable
- asegurar calidad mediante pruebas unitarias y funcionales

## Stack Tecnológico

- **PHP** 8.4
- **Laravel** 13
- **Base de datos** SQLite por defecto, adaptable a MySQL/PostgreSQL
- **Autenticación API** Laravel Sanctum
- **Documentación** OpenAPI + Swagger UI
- **Frontend de apoyo** Vite + Tailwind CSS v4
- **Testing** Pest

## Funcionalidades Principales

- registro y login de usuarios
- control de acceso basado en roles y permisos
- listado público de eventos activos
- CRUD de eventos para administradores y organizadores
- compra de tickets por clientes
- prevención de compra duplicada por tipo de ticket
- control de stock por tipo de entrada
- validación de tickets en puerta
- reporte de asistentes por evento

## Requisitos Previos

- PHP >= 8.4
- Composer
- Node.js y npm
- SQLite o un motor de base de datos compatible

## Instalación

```bash
git clone <url-del-repositorio>
cd ticketera
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
composer dev
```

El comando `composer dev` levanta el servidor PHP, el worker de colas, el visor de logs con Pail y el servidor de Vite.

## Ejecución Rápida

Una vez iniciado el proyecto:

- aplicación local: `http://127.0.0.1:8000`
- Swagger UI: `http://127.0.0.1:8000/swagger`
- JSON OpenAPI: `http://127.0.0.1:8000/api/docs/json`

## Estructura Del Proyecto

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── EventController.php
│   │       ├── TicketController.php
│   │       ├── AttendanceController.php
│   │       └── Admin/
│   │           ├── EventController.php
│   │           └── UserController.php
│   ├── Requests/
│   └── Resources/
├── Models/
├── Policies/
├── Providers/
database/
├── factories/
├── migrations/
└── seeders/
routes/
├── api.php
└── web.php
tests/
├── Feature/
└── Unit/
```

## Arquitectura De La API

- `Controllers`: coordinan cada flujo HTTP.
- `Form Requests`: encapsulan validaciones de entrada.
- `API Resources`: normalizan la salida JSON.
- `Policies` y `Gates`: controlan autorización por rol o permiso.
- `Models`: representan entidades del dominio y sus relaciones.
- `Seeders`: generan datos iniciales para demo y pruebas.

## Modelo De Datos

| Modelo | Descripción |
|--------|-------------|
| **User** | Usuario del sistema con estado `is_active` |
| **Role** | Rol asignable a usuarios |
| **Permission** | Permiso granular asociado a roles |
| **Event** | Evento con organizador, fecha y ubicación |
| **TicketType** | Tipo de entrada por evento con precio y cantidad |
| **Ticket** | Entrada comprada con UUID y estado |
| **Attendance** | Registro de ingreso de un ticket validado |

## Roles Y Permisos

El sistema usa RBAC (`Role-Based Access Control`):

- **Admin**: administra usuarios, eventos y reportes
- **Organizador**: crea y administra sus propios eventos
- **Cliente**: compra entradas y consulta sus tickets

Permisos disponibles:

- `manage-users`
- `manage-events`
- `sell-tickets`
- `validate-tickets`
- `view-reports`

## Reglas De Negocio

- solo se pueden comprar entradas de eventos activos
- el stock depende de la cantidad configurada por tipo de ticket
- un cliente no puede comprar dos veces el mismo tipo de entrada para el mismo evento
- un ticket validado cambia a estado `used`
- un ticket cancelado o usado no puede volver a validarse
- usuarios con `is_active = false` no pueden iniciar sesión
- cada evento debe crearse con exactamente tres tipos de entrada: `General`, `VIP` y `Premium`

## Documentación De Endpoints

### Públicos

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/register` | Registro de usuario |
| `POST` | `/api/login` | Inicio de sesión y emisión de token |
| `GET` | `/api/events` | Listado de eventos activos |
| `GET` | `/api/events/{event}` | Detalle de evento activo |

### Protegidos Con Bearer Token

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/logout` | Cierre de sesión |
| `GET` | `/api/tickets` | Tickets del usuario autenticado |
| `POST` | `/api/tickets` | Compra de ticket |
| `POST` | `/api/tickets/{code}/validate` | Validación en puerta |

### Administración

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/admin/events` | Lista eventos según rol |
| `POST` | `/api/admin/events` | Crear evento |
| `GET` | `/api/admin/events/{event}` | Ver evento |
| `PUT` | `/api/admin/events/{event}` | Actualizar evento |
| `PATCH` | `/api/admin/events/{event}` | Actualizar parcialmente evento |
| `DELETE` | `/api/admin/events/{event}` | Eliminar evento |
| `GET` | `/api/admin/events/{event}/attendees` | Reporte de asistentes |
| `GET` | `/api/admin/users` | Listar usuarios |
| `GET` | `/api/admin/users/{user}` | Ver usuario |
| `PUT` | `/api/admin/users/{user}` | Actualizar usuario |
| `PATCH` | `/api/admin/users/{user}` | Actualizar parcialmente usuario |
| `DELETE` | `/api/admin/users/{user}` | Eliminar usuario |

## Ejemplos De Uso

### Login

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "client@ticketera.com",
    "password": "password"
  }'
```

### Compra De Ticket

```bash
curl -X POST http://127.0.0.1:8000/api/tickets \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN" \
  -d '{
    "event_id": 1,
    "ticket_type_id": 1
  }'
```

## Testing

El proyecto utiliza [Pest](https://pestphp.com/) para pruebas unitarias y funcionales.

Ejecutar toda la suite:

```bash
php artisan test --compact
```

Ejecutar una prueba específica:

```bash
php artisan test --compact tests/Feature/AuthTest.php
```

Cobertura actual validada durante la revisión:

- autenticación
- autorización `401` y `403`
- validaciones `422`
- flujos de eventos
- compra de tickets
- reglas de negocio
- validación en puerta
- documentación Swagger crítica

## Usuarios De Prueba

Al ejecutar `php artisan migrate:fresh --seed` se crean estos usuarios:

| Rol | Email | Password |
|-----|-------|----------|
| Admin | `admin@ticketera.com` | `password` |
| Organizador | `organizer@ticketera.com` | `password` |
| Cliente | `client@ticketera.com` | `password` |

## Colección Postman

Para la defensa se recomienda preparar una colección que cubra:

- registro o login
- acceso no autenticado `401`
- acceso no autorizado `403`
- creación de evento por organizador
- consulta pública del evento
- compra de ticket
- intento duplicado `422`
- validación en puerta
- reporte de asistentes

## Defensa En Vivo

Durante la demo conviene seguir este orden:

1. mostrar `README.md` y estructura del proyecto
2. abrir Swagger en `/swagger`
3. ejecutar un flujo público
4. autenticar un usuario
5. demostrar seguridad con `401` y `403`
6. crear un evento
7. comprar un ticket
8. validar ticket en puerta
9. ejecutar `php artisan test --compact`

## Licencia

Este proyecto utiliza el framework Laravel, licenciado bajo la [MIT License](https://opensource.org/licenses/MIT).
