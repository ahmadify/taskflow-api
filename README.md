# TaskFlow API

TaskFlow is a Laravel 13 REST API for a project-management domain. The current internship deliverable implements user registration and Laravel Sanctum token authentication. The database schema, Eloquent models, relationships, and tests support future project, task, assignment, tag, and comment features, but their CRUD endpoints are intentionally not implemented.

## Documentation

- [Project proposal](PROJECT_PROPOSAL.md)
- [Entity-relationship diagram](docs/ERD.md)
- [API plan](docs/API_PLAN.md)

## Requirements

- Windows 10 or later
- PHP 8.5 with SQLite support
- Composer
- Git

Node.js, npm, frontend tooling, Docker, and a separate database server are not required.

## Windows installation

Install PHP 8.5, Composer, and the Laravel installer with Laravel's official Windows installation command, then restart the terminal:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force
Invoke-Expression (Invoke-RestMethod 'https://php.new/install/windows/8.5')
```

From the repository root, install the PHP dependencies and prepare the environment:

```powershell
composer install
Copy-Item .env.example .env
New-Item -ItemType File -Path database/database.sqlite -Force
php artisan key:generate
php artisan migrate
```

The supplied `.env.example` uses SQLite through `DB_CONNECTION=sqlite`.

## Run and test

Start the local API server:

```powershell
php artisan serve
```

The default URL is `http://127.0.0.1:8000`. Run database migrations and tests with:

```powershell
php artisan migrate:fresh
php artisan test
php vendor/bin/pint --test
```

## Implemented endpoints

| Method | Endpoint | Authentication | Purpose |
| --- | --- | --- | --- |
| `POST` | `/api/register` | Public | Register a user and issue a Sanctum token |
| `POST` | `/api/login` | Public | Authenticate a user and issue a Sanctum token |
| `GET` | `/api/user` | Bearer token | Return the authenticated user |
| `POST` | `/api/logout` | Bearer token | Revoke the current request token |

Project, membership, task, assignment, tag, and comment routes are planned only. See the [API plan](docs/API_PLAN.md) for the complete distinction between implemented and planned functionality.

## Postman

Import both files into Postman:

1. `postman/TaskFlow_API.postman_collection.json`
2. `postman/TaskFlow_Local.postman_environment.json`

Select the **TaskFlow Local** environment. The collection generates temporary registration values at runtime and saves a successful registration or login token to the environment. Planned requests are documentation examples and will return `404` until their backend endpoints are implemented outside the current internship scope.
