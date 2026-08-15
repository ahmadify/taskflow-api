# TaskFlow Project Proposal

## Problem

Small teams often coordinate projects across disconnected messages, notes, and spreadsheets. This makes ownership unclear, hides progress, and makes it difficult to find the discussion or context behind a task. TaskFlow will provide one REST API for organizing project membership, task assignments, tags, and comments.

## Objective and users

The objective is to build a secure Laravel REST API that gives registered users a consistent way to manage collaborative project work. Its intended users are project owners, project members, and client applications that need structured project-management data.

Project owners will create projects and manage membership. Project members will view the projects they belong to, create and update tasks when authorized, assign work, apply tags, and discuss tasks through comments.

## Core functionality

TaskFlow will support:

- User registration, login, logout, and retrieval of the authenticated user.
- Project creation, viewing, updating, and deletion.
- Project membership management with an `owner` or `member` role and a recorded join date.
- Task creation within a project, including status, priority, due date, and creator details.
- Assignment of one or more project members to a task.
- Project-level tags that can be attached to multiple tasks.
- Task comments written by authenticated project members.
- Authorization rules that restrict data and actions to appropriate project members or owners.

## API scope

Phase 1 defines the domain, relationships, API surface, and implementation standards. The internship implementation exposes authentication endpoints under `/api`. CRUD operations for projects, memberships, tasks, assignments, tags, and comments remain planned for future development. A web interface, notifications, file attachments, billing, reporting dashboards, and real-time collaboration are outside the scope.

Responses will use a consistent JSON structure. Successful responses will contain a `data` field and may include `message` and `meta` fields. Errors will contain a clear `message` and, for validation failures, an `errors` object keyed by input field. Appropriate HTTP status codes will distinguish successful creation, validation failure, authentication failure, authorization failure, missing resources, and deletion.

## Authentication and authorization

Laravel Sanctum will provide token authentication. Users will register or log in to receive a personal access token, send it in the `Authorization: Bearer <token>` header, and revoke the current token on logout.

Policies and project membership checks will protect resources. Project owners will control project details and membership. Project members will only access projects and related resources when they have membership. Assignment targets must belong to the same project as the task, and tags used by a task must belong to that task's project. Comment actions will be limited to authorized project members, with update and deletion restricted to the comment author unless a defined owner privilege applies.

## Validation

Laravel Form Request classes will validate all write operations. Validation will cover required fields, string lengths, enum-like values, dates, uniqueness where needed, resource existence, and cross-resource rules. Examples include ensuring a due date is valid, a project role is supported, an assignee belongs to the project, and a tag belongs to the same project as the task. Controllers will remain concise by delegating validation, authorization, and domain-specific logic to the appropriate framework classes and services.

## Database design

The relational database will contain `users`, `projects`, `tasks`, `tags`, and `comments`, together with three many-to-many pivot tables.

- `projects.owner_id` identifies the user responsible for a project.
- `project_user` connects users and projects and stores each member's role and `joined_at` date.
- `tasks.project_id` scopes every task to one project, while `created_by` records its creator.
- `task_user` allows a task to have multiple assignees and a user to have multiple assignments.
- `tags.project_id` scopes reusable tags to a project.
- `task_tag` connects tasks and tags.
- `comments` belong to both a task and their authoring user.

Foreign keys and unique composite constraints on pivot pairs will preserve referential integrity and prevent duplicate memberships, assignments, and tag attachments. Indexes will support common filters such as project, status, priority, due date, assignee, and task comments. Timestamps will support auditing and ordering; soft deletion can be evaluated during implementation for records where recovery is useful.

See [docs/ERD.md](docs/ERD.md) for the proposed entity-relationship diagram.

## Endpoint plan

The authentication endpoints are implemented beneath `/api`; all domain CRUD endpoints in this table remain planned beneath the same prefix.

| Area | Method and path | Purpose |
| --- | --- | --- |
| Authentication | `POST /register` | Register a user and issue a token |
| Authentication | `POST /login` | Authenticate a user and issue a token |
| Authentication | `POST /logout` | Revoke the current token |
| Authentication | `GET /user` | Return the authenticated user |
| Projects | `GET /projects` | List projects available to the user |
| Projects | `POST /projects` | Create a project |
| Projects | `GET /projects/{project}` | View a project |
| Projects | `PATCH /projects/{project}` | Update a project |
| Projects | `DELETE /projects/{project}` | Delete a project |
| Members | `GET /projects/{project}/members` | List project members |
| Members | `POST /projects/{project}/members` | Add a project member |
| Members | `PATCH /projects/{project}/members/{user}` | Change a member role |
| Members | `DELETE /projects/{project}/members/{user}` | Remove a project member |
| Tasks | `GET /projects/{project}/tasks` | List and filter project tasks |
| Tasks | `POST /projects/{project}/tasks` | Create a task |
| Tasks | `GET /tasks/{task}` | View a task |
| Tasks | `PATCH /tasks/{task}` | Update a task |
| Tasks | `DELETE /tasks/{task}` | Delete a task |
| Assignments | `POST /tasks/{task}/assignees` | Assign one or more project members |
| Assignments | `DELETE /tasks/{task}/assignees/{user}` | Remove an assignee |
| Tags | `GET /projects/{project}/tags` | List project tags |
| Tags | `POST /projects/{project}/tags` | Create a tag |
| Tags | `PATCH /tags/{tag}` | Update a tag |
| Tags | `DELETE /tags/{tag}` | Delete a tag |
| Task tags | `POST /tasks/{task}/tags` | Attach one or more project tags |
| Task tags | `DELETE /tasks/{task}/tags/{tag}` | Detach a tag |
| Comments | `GET /tasks/{task}/comments` | List task comments |
| Comments | `POST /tasks/{task}/comments` | Create a comment |
| Comments | `PATCH /comments/{comment}` | Update a comment |
| Comments | `DELETE /comments/{comment}` | Delete a comment |

List endpoints will use pagination and may support relevant filters, sorting, and relationship inclusion without exposing data outside the authenticated user's projects.

## Expected deliverables

The internship delivery includes:

- A Laravel application configured as a REST API.
- Database migrations, Eloquent models, relationships, and factories for the documented schema.
- Laravel Sanctum registration, login, authenticated-user, and logout flows.
- Form Requests, a concise authentication controller, an API resource, and consistent JSON responses.
- Feature tests covering authentication, validation, the database schema, constraints, and model relationships.
- API documentation and a Postman collection that clearly distinguish implemented authentication from planned CRUD functionality.
