# Koda Projects API

A REST API for managing client projects, built with Laravel 13 and PHP 8.4.

- Token authentication with Laravel Sanctum
- Project CRUD with validation, search, filtering, sorting and pagination
- Soft deletes
- Swagger UI generated from the code
- Docker setup with MySQL

## Getting started

### Docker

Requires Docker with Compose v2.

```bash
docker compose up --build
```

On start the container creates `.env`, generates an app key, runs the migrations and seeds the database.

| | |
|---|---|
| API | http://localhost:8000/api/v1 |
| Swagger UI | http://localhost:8000/docs/api |
| MySQL | `localhost:3307`, database `koda`, user `koda`, password `secret` |

Set `APP_PORT` or `FORWARD_DB_PORT` to use other ports. Run the tests inside the container with:

```bash
docker compose exec app vendor/bin/pest --parallel
```

The tests always use an in-memory SQLite database (forced in `phpunit.xml`), so they never touch the container's MySQL data.

### Without Docker

Requires PHP 8.4 and Composer. The default `.env.example` uses SQLite.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

## Seeded data

| | |
|---|---|
| User | `test@example.com` / `password` |
| Projects | 12 sample projects (ids 1–12) |

Seeders can be run repeatedly: they reset the test user and the sample projects instead of duplicating them.

## Authentication

Log in (or register) to get a token, then send it as a bearer token on every project request.

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email": "test@example.com", "password": "password"}'
```

```bash
curl http://localhost:8000/api/v1/projects \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer <token>'
```

Login allows 5 attempts per minute for each email and IP address.

## Endpoints

| Method | Path | Auth | Description |
|---|---|---|---|
| POST | `/api/v1/auth/register` | – | Register with `name`, `email`, `password` |
| POST | `/api/v1/auth/login` | – | Log in with `email`, `password` |
| GET | `/api/v1/projects` | Bearer | List projects |
| POST | `/api/v1/projects` | Bearer | Create a project |
| GET | `/api/v1/projects/{id}` | Bearer | Show a project |
| PUT | `/api/v1/projects/{id}` | Bearer | Update a project |
| DELETE | `/api/v1/projects/{id}` | Bearer | Delete a project (soft delete) |
| GET | `/api/v1/health` | – | Health check |

### Project body

```json
{
  "clientName": "Acme Corporation",
  "projectName": "Corporate Website Redesign",
  "description": "Redesign and modernize the company's corporate website.",
  "status": "In Progress",
  "priority": "High",
  "startDate": "2026-06-01",
  "dueDate": "2026-07-15"
}
```

| Field | Rules |
|---|---|
| `clientName` | Required, up to 255 characters |
| `projectName` | Required, up to 255 characters; a client cannot have two projects with the same name, including deleted ones |
| `description` | Optional text |
| `status` | `Planning`, `In Progress`, `On Hold` or `Completed`; defaults to `Planning` |
| `priority` | `Low`, `Medium` or `High`; defaults to `Medium` |
| `startDate`, `dueDate` | Optional dates (`YYYY-MM-DD`); `dueDate` cannot be earlier than `startDate` |

`PUT` replaces the project. A `status` or `priority` left out of the request keeps its current value.

### Listing projects

| Parameter | Example | Description |
|---|---|---|
| `search` | `?search=portal` | Case-insensitive match on client name, project name or description |
| `filters[...]` | `?filters[status]=On Hold&filters[priority]=High` | Exact match on `clientName`, `projectName`, `status` or `priority` |
| `sortBy` | `?sortBy=dueDate` | `id`, `clientName`, `projectName`, `status`, `priority`, `startDate`, `dueDate`, `createdAt` or `updatedAt`; defaults to `id` |
| `sortOrder` | `?sortOrder=asc` | `asc` or `desc`; defaults to `desc` (newest first) |
| `page` | `?page=2` | 15 projects per page |

Parameters can be combined, and the pagination links keep them. Encode spaces in URLs, for example `filters[status]=On%20Hold`.

## Responses

Successful responses share one envelope:

```json
{
  "message": "Project created successfully",
  "data": { "id": 13, "clientName": "Acme Corporation", "...": "..." }
}
```

Lists add `meta` (`currentPage`, `lastPage`, `perPage`, `total`) and `links` (`first`, `last`, `prev`, `next`). Register and login return `data.user` and `data.token`.

| Status | When | Body |
|---|---|---|
| 401 | Missing or invalid token | `{"message": "Unauthenticated."}` |
| 404 | Project does not exist or was deleted | `{"message": "Project not found."}` |
| 422 | Validation failed | `{"message": "...", "errors": {"clientName": ["The client name field is required."]}}` |
| 429 | Too many login attempts | `{"message": "Too Many Attempts."}` |

## Architecture

Code is grouped by domain under `src/` (namespace `Domain\`). Each domain owns its HTTP layer, business operations and data access.

```
src/
├── Authentications/   Register, login, token issuing, login rate limit
├── Projects/
│   ├── Actions/        Business operations (service layer)
│   ├── DTO/            Typed input passed from controllers to actions
│   ├── Database/       Factory and seeder
│   ├── Enums/          Status and priority
│   ├── Http/           Controller, form requests, API resource
│   ├── Models/         Eloquent model with filter and search scopes
│   └── Repositories/   Data access
└── Users/              User model, repository and resource
```

A request flows through: route → form request (validation) → controller → DTO → action → repository → model. Responses are built from API resources and wrapped by the `response()->success()`, `created()`, `updated()`, `deleted()` and `paginated()` macros in `app/Providers/AppServiceProvider.php`.

## Development

```bash
vendor/bin/pest --parallel    # Pest feature and unit tests
vendor/bin/pint               # Code style
vendor/bin/phpstan analyse    # Static analysis (Larastan)
```
