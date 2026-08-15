# TaskFlow Project Proposal

## Overview and problem

Small teams often manage their work through separate messages, notes, and spreadsheets. This makes it difficult to know who is responsible for each task, which work is finished, and where important discussions took place. Information can be repeated, lost, or become outdated.

TaskFlow is a Laravel REST API designed to keep project information in one structured system. It provides the foundation for managing projects, members, tasks, assignments, tags, and comments. Client applications can use the API to access this information in a consistent JSON format.

## Objective and intended users

The objective is to create a secure and organized backend for collaborative project management. The main users are registered team members, project owners, and developers building applications that use TaskFlow data.

Project owners will be responsible for their projects and membership. Project members will be able to take part in shared work, receive task assignments, use tags, and discuss tasks through comments when the related features are implemented.

## Main functionality

The current internship version focuses on user authentication. A user can register, log in, view their authenticated account, and log out. Registration and login return a personal access token that can be used for protected requests.

The wider TaskFlow design also includes project creation, project membership, tasks, task assignments, tags, and comments. These features are represented in the database structure and Eloquent model relationships. Their CRUD endpoints are planned for future development and are not implemented in the current version.

## Database design

TaskFlow uses SQLite for local development. Its main database entities are `users`, `projects`, `tasks`, `tags`, and `comments`.

A project belongs to an owner. The `project_user` pivot table connects projects and their members while recording each member's role and join date. Each task belongs to a project and records the user who created it. The `task_user` pivot table supports assigning several users to a task.

Tags belong to projects and can be connected to tasks through the `task_tag` pivot table. Comments belong to both a task and the user who wrote them. Foreign keys protect these relationships, while unique constraints prevent duplicate memberships, assignments, and tag attachments. The complete relationships and cardinalities are shown in [docs/ERD.md](docs/ERD.md).

## Authentication and implemented endpoints

TaskFlow uses Laravel Sanctum for token authentication. Passwords are securely hashed before storage. Registration validates the user's name, email address, password, and password confirmation. Login checks the submitted credentials without exposing sensitive account information.

The implemented endpoints are:

- `POST /api/register` to create a user and issue a token.
- `POST /api/login` to authenticate a user and issue a token.
- `GET /api/user` to return the authenticated user.
- `POST /api/logout` to revoke the token used for the current request.

The current-user and logout endpoints require a valid bearer token. Automated tests cover successful requests, validation errors, incorrect credentials, protected access, and token revocation.

## Planned CRUD features

Future development may add CRUD endpoints for projects and tasks, together with project membership management. It may also support assigning project members to tasks, creating and attaching project tags, and adding or managing task comments. Access rules should ensure that users only work with projects they own or belong to.

These planned endpoints are documented separately in [docs/API_PLAN.md](docs/API_PLAN.md) and included as clearly marked planning examples in the Postman collection. They are outside the current internship implementation.

## Expected outcome

The delivered project provides a working Laravel API, a tested authentication flow, a complete relational database foundation, setup documentation, and Postman examples. It can serve as a clear starting point for future project-management features without presenting planned functionality as completed work.
