# TaskFlow API

TaskFlow is a planned Laravel REST API for collaborative project management. Registered users will be able to create projects, manage project membership, create and assign tasks, organize tasks with reusable tags, and discuss work through comments.

The Laravel 13 application is scaffolded as an API-only backend. It uses SQLite for local development and Laravel Sanctum for token authentication. TaskFlow domain resources and CRUD endpoints are planned for a later phase.

## Documentation

- [Project proposal](PROJECT_PROPOSAL.md)
- [Entity-relationship diagram](docs/ERD.md)

## Technical direction

The API uses Laravel Sanctum for token authentication and will use Form Request classes for validation, policies for authorization, API resources for consistent JSON responses, and feature tests for authentication and CRUD behavior. The planned API namespace is `/api/v1`.

## Windows setup and local use

Install PHP 8.5, Composer, and the Laravel installer with Laravel's official Windows installer, then restart the terminal:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force
Invoke-Expression (Invoke-RestMethod 'https://php.new/install/windows/8.5')
```

From the repository root, prepare and run the API:

```powershell
composer install
Copy-Item .env.example .env
New-Item -ItemType File -Path database/database.sqlite -Force
php artisan key:generate
php artisan migrate
php artisan serve
```

The default local URL is `http://127.0.0.1:8000`. Run the test suite with:

```powershell
php artisan test
```

Node.js and frontend tooling are not required for the API workflow.
