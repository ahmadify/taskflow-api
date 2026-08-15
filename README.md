# TaskFlow API

TaskFlow is a Laravel REST API for collaborative project management. Registered users can create projects, manage project membership, create and assign tasks, organize tasks with reusable tags, and discuss work through comments.

The Laravel 13 application is an API-only backend using SQLite for local development and Laravel Sanctum for token authentication. Authentication, project membership, task management, assignments, tags, and comments are implemented.

## Documentation

- [Project proposal](PROJECT_PROPOSAL.md)
- [Entity-relationship diagram](docs/ERD.md)

## Technical direction

The API uses Laravel Sanctum for token authentication, Form Request classes for validation, policies for authorization, API resources for consistent JSON responses, and feature tests for authentication and CRUD behavior.

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

Global tag updates and deletion are deferred until a broader tag lifecycle is defined. The current API limits global tag operations to authorized listing and project-owner creation; task-level attachment and detachment remain project-scoped.
