# Koda Projects API

A REST API for managing client projects, built with Laravel 13 and PHP 8.4.

## Features implemented

**Core requirements**

- Project CRUD: list, show, create, update and delete projects
- Validation: client name and project name are required, status and priority must be valid, and the due date cannot be earlier than the start date, each with a clear error message
- Consistent JSON errors for invalid input (422), missing or invalid tokens (401), unknown projects (404) and too many login attempts (429)
- Database: MySQL in Docker or SQLite locally, with migrations and seed data matching `test_data.json`
- Layered architecture: controllers, form requests, DTOs, actions (services) and repositories

**Bonus**

- Pagination: 15 projects per page, with `meta` and `links`
- Search across client name, project name and description
- Filtering by status, priority, client name or project name, plus sorting
- Authentication: register or log in to get a Sanctum bearer token; login is rate limited
- Feature and unit tests (Pest), run in GitHub Actions together with Pint and Larastan
- Docker setup with FrankenPHP and MySQL
- API documentation: Swagger UI at `/docs/api`

**Extras**

- Soft deletes
- Duplicate protection: a client cannot have two projects with the same name

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

| Layer | Responsibility |
|---|---|
| Controllers | HTTP only: read the validated request, call an action and return the response |
| Actions (services) | One business operation per class, such as creating a project or checking login credentials |
| Repositories | All reads and writes to the database for projects and users |

For project CRUD the actions are thin today, because validation lives in form requests and queries live in the repository. They are the place for business rules as the domain grows, such as status transitions or notifications, without touching controllers or queries.

A few standard Laravel features reach the database outside a repository, on purpose:

- **Route model binding** loads the project for show, update and delete, and returns 404 when it does not exist.
- **Uniqueness validation** (`Rule::unique`) checks for duplicate emails and project names in the form requests.
- **Sanctum's `createToken()`** is called on the user model when a token is issued.

## Assumptions

- **Field names and values follow `test_data.json`:** camelCase keys and labels such as `In Progress` and `High`.
- **Status and priority are optional when creating a project** and default to `Planning` and `Medium`.
- **Description, start date and due date are optional.** The due date is only compared when a start date is given, and a due date on the start date is allowed.
- **Dates use the `YYYY-MM-DD` format.**
- **Every project endpoint requires authentication.** Any authenticated user can manage every project, because the project model has no owner.
- **A client cannot have two projects with the same name.** Deleted projects still count, so a name is never reused silently.
- **Deleting is a soft delete.** Deleted projects are hidden from the list and return 404.
- **`PUT` replaces the project.** Optional fields left out become empty, except `status` and `priority`, which keep their current values.
- **Endpoints are versioned under `/api/v1`,** so `GET /projects` is served at `GET /api/v1/projects`.
- **"Get all projects" is paginated** to keep responses small. Search, filters and sorting are query parameters on that endpoint rather than separate endpoints.
- **Registration is open** to anyone.

## Technical decisions

- **Code grouped by domain in `src/`.** Each domain keeps its controller, requests, actions, repository and model together, so a feature can be read and changed in one place.
- **Controller → DTO → action → repository.** Controllers only handle HTTP, actions hold the business operations and repositories own the queries, so each layer can change and be tested on its own.
- **Laravel Sanctum** provides simple bearer tokens without the overhead of OAuth.
- **Form requests** hold all validation, with custom messages that list the valid values.
- **Status and priority are stored as the labels the API shows,** so no mapping layer is needed. PHP enums keep the allowed values in one place.
- **Duplicate projects are blocked by a database constraint as well as validation,** because validation alone cannot stop two identical requests arriving at the same time.
- **Every successful response uses one `{message, data}` format,** so clients handle all endpoints the same way.
- **Sorting only accepts known fields,** because column names cannot be passed safely to the database as query parameters.
- **The Swagger documentation is generated from the code** (Scramble), so it stays in sync with the validation rules and responses.

## AI disclosure

This project was built with **Claude Code** (Anthropic), an AI coding assistant, throughout development.

**How AI was used**

- **Implementation:** the AI wrote most of the code, tests, Docker setup and this README, working from my instructions one step at a time.
- **Checks:** after each change it ran the test suite, Pint and Larastan, and any failures were fixed before moving on.

**What I decided and directed**

- **Scope:** how to interpret the requirements, and which bonus features to build.
- **My coding style:** I asked the AI to follow the conventions from my existing Laravel projects, and it studied one of them to match how I work. That includes:
  - code grouped by domain in `src/`
  - actions with DTOs, and repositories
  - form requests
  - response macros for a consistent `{message, data}` format
  - scopes like `applyFilter`
  - Scramble for API docs
  - tests organised by domain
- **API behaviour:**
  - camelCase fields and label values matching `test_data.json`
  - `search` as its own query parameter
  - soft deletes
  - blocking duplicate client and project names
- **Tools:** MySQL for Docker.

**Review**

I reviewed the changes and tested the API locally. Where the behaviour wasn't what I wanted, I had it changed. For example, I moved `search` out of `filters`, and stopped the duplicate check from rejecting a project saved under its own name.

## Development

```bash
vendor/bin/pest --parallel    # Pest feature and unit tests
vendor/bin/pint               # Code style
vendor/bin/phpstan analyse    # Static analysis (Larastan)
```
