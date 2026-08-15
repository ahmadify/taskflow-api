# TaskFlow API

## About

TaskFlow is a Laravel REST API for project management. Its database structure supports users, projects, project members, tasks, assignments, tags, and comments. The application uses SQLite for local development and provides data through JSON API requests.

This repository is an internship project focused on building a clear backend foundation. It includes database migrations, Eloquent models, relationships, validation, automated tests, and API documentation.

## Current features

User registration and token authentication are implemented with Laravel Sanctum. A user can register, log in, view their authenticated account, and log out by revoking the token used for the request.

The implemented API routes are:

- `POST /api/register`
- `POST /api/login`
- `GET /api/user`
- `POST /api/logout`

CRUD features for projects, project membership, tasks, task assignments, tags, and comments are planned for future development. Their database structure and documentation are included, but their API routes are not implemented.

## Basic setup

PHP 8.3 or later and Composer are required. Install the project dependencies:

```text
composer install
```

Copy `.env.example` to `.env` and create an empty `database/database.sqlite` file. Then prepare and run the application:

```text
php artisan key:generate
php artisan migrate
php artisan serve
```

The local API is available at `http://127.0.0.1:8000` by default.

## Documentation

- [Project proposal](PROJECT_PROPOSAL.md)
- [Entity-relationship diagram](docs/ERD.md)
- [API plan](docs/API_PLAN.md)
- [Postman collection](postman/TaskFlow_API.postman_collection.json)
- [Postman local environment](postman/TaskFlow_Local.postman_environment.json)

The API plan and Postman collection clearly identify which requests are implemented and which are planned. Import both Postman files and select the TaskFlow Local environment to test the authentication routes.
