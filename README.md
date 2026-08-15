# TaskFlow API

TaskFlow is a planned Laravel REST API for collaborative project management. Registered users will be able to create projects, manage project membership, create and assign tasks, organize tasks with reusable tags, and discuss work through comments.

This repository is currently in Phase 1: proposal and data-model documentation. Laravel has not been scaffolded and no application dependencies have been installed.

## Documentation

- [Project proposal](PROJECT_PROPOSAL.md)
- [Entity-relationship diagram](docs/ERD.md)

## Planned technical direction

The API will use Laravel Sanctum for token authentication, Form Request classes for validation, policies for authorization, API resources for consistent JSON responses, and feature tests for authentication and CRUD behavior. The planned API namespace is `/api/v1`.

Implementation and local setup instructions will be added in a later phase after the Laravel application is scaffolded.
