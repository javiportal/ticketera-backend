# Ticketera

Sistema de gestión de eventos y venta de entradas construido con Laravel 13. Permite a organizadores crear eventos con diferentes tipos de entrada, a clientes comprar tickets y a operadores validar entradas en puerta mediante check-in.

## Stack Tecnológico

- **PHP** 8.3+
- **Laravel** 13
- **Base de datos** SQLite (configurable a MySQL/PostgreSQL)
- **Autenticación API** Laravel Sanctum (tokens de acceso personal)
- **Frontend** Vite + Tailwind CSS v4
- **Testing** Pest

## Requisitos Previos

- PHP >= 8.3
- Composer
- Node.js y npm

## Instalación

```bash
# Clonar el repositorio
git clone <url-del-repositorio>
cd ticketera

# Instalar dependencias de PHP
composer install

# Instalar dependencias de Node
npm install

# Configurar el entorno
cp .env.example .env
php artisan key:generate

# Crear la base de datos y poblarla con datos de ejemplo
php artisan migrate --seed

# Iniciar el servidor de desarrollo
composer dev
```

El comando `composer dev` levanta en paralelo el servidor PHP, el worker de colas, el visor de logs (Pail) y el servidor de Vite.

## Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php        # Registro, login, logout
│   │       ├── EventController.php       # Listado público de eventos
│   │       ├── TicketController.php       # Compra de entradas
│   │       ├── AttendanceController.php   # Validación en puerta
│   │       └── Admin/
│   │           ├── EventController.php    # CRUD de eventos (admin/organizador)
│   │           └── UserController.php     # Gestión de usuarios (admin)
│   ├── Requests/                          # Form Requests con validación
│   └── Resources/                         # API Resources (transformación de respuestas)
├── Models/                                # User, Role, Permission, Event, TicketType, Ticket, Attendance
├── Policies/                              # EventPolicy, TicketPolicy
└── Providers/                             # Gates de autorización
```

## Modelo de Datos

| Modelo | Descripción |
|--------|-------------|
| **User** | Usuarios con campo `is_active` y relación con roles |
| **Role** | Roles del sistema (admin, organizador, cliente) |
| **Permission** | Permisos granulares asignados a roles |
| **Event** | Eventos con título, descripción, ubicación, fecha y organizador |
| **TicketType** | Tipos de entrada por evento (nombre, precio, cantidad disponible) |
| **Ticket** | Entrada comprada con código UUID único y estado (`valid`, `used`, `cancelled`) |
| **Attendance** | Registro de check-in con timestamp |

## API Endpoints

### Públicos

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/register` | Registro de usuario |
| `POST` | `/api/login` | Inicio de sesión (devuelve token) |
| `GET` | `/api/events` | Listar eventos activos |
| `GET` | `/api/events/{event}` | Ver detalle de un evento |

### Autenticados (Bearer Token)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/logout` | Cerrar sesión |
| `GET` | `/api/tickets` | Mis entradas |
| `POST` | `/api/tickets` | Comprar entrada |
| `POST` | `/api/tickets/{code}/validate` | Validar entrada en puerta |

### Administración (Bearer Token + permisos)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/admin/events` | Listar eventos (admin: todos, organizador: los suyos) |
| `POST` | `/api/admin/events` | Crear evento |
| `GET` | `/api/admin/events/{event}` | Ver evento |
| `PUT` | `/api/admin/events/{event}` | Actualizar evento |
| `DELETE` | `/api/admin/events/{event}` | Eliminar evento (solo admin) |
| `GET` | `/api/admin/events/{event}/attendees` | Listado de asistentes |
| `GET` | `/api/admin/users` | Listar usuarios |
| `GET` | `/api/admin/users/{user}` | Ver usuario |
| `PUT` | `/api/admin/users/{user}` | Actualizar usuario |
| `DELETE` | `/api/admin/users/{user}` | Eliminar usuario |

## Roles y Permisos

El sistema implementa RBAC (Role-Based Access Control):

- **Admin** — Gestión completa de eventos, usuarios y reportes.
- **Organizador** — Crea y administra sus propios eventos.
- **Cliente** — Compra entradas y consulta sus tickets.

Permisos disponibles: `manage-users`, `manage-events`, `validate-tickets`, `view-reports`.

## Reglas de Negocio

- Solo se pueden comprar entradas de eventos activos.
- Stock limitado por tipo de entrada.
- Un usuario solo puede comprar una entrada por tipo de ticket.
- La validación en puerta marca el ticket como `used` y registra la asistencia.
- Usuarios con `is_active = false` no pueden iniciar sesión.

## Testing

```bash
php artisan test
```

El proyecto utiliza [Pest](https://pestphp.com/) como framework de testing.

## Usuarios de Prueba

Al ejecutar el seeder se crean los siguientes usuarios:

| Rol | Email |
|-----|-------|
| Admin | `admin@ticketera.com` |
| Organizador | _(definido en el seeder)_ |
| Cliente | _(definido en el seeder)_ |

> Consulta `database/seeders/DatabaseSeeder.php` para ver las credenciales completas.

## Licencia

Este proyecto utiliza el framework Laravel, licenciado bajo la [MIT License](https://opensource.org/licenses/MIT).
