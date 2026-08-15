# TaskFlow API Plan

This document separates the authentication endpoints delivered for the internship from the domain CRUD endpoints retained as future plans. Only the four endpoints in the implemented section are registered by the application.

## Implemented authentication API

| Method | Endpoint | Authentication | Successful response | Other responses |
| --- | --- | --- | --- | --- |
| `POST` | `/api/register` | Public | `201 Created` | `422 Unprocessable Content` for validation errors |
| `POST` | `/api/login` | Public | `200 OK` | `401 Unauthorized` for incorrect credentials; `422 Unprocessable Content` for invalid input |
| `GET` | `/api/user` | Sanctum bearer token | `200 OK` | `401 Unauthorized` without a valid token |
| `POST` | `/api/logout` | Sanctum bearer token | `200 OK` | `401 Unauthorized` without a valid token |

Protected requests use this header:

```http
Authorization: Bearer <token>
Accept: application/json
```

Registration and login return the token in `data.token` with `data.token_type` set to `Bearer`. The current-user response returns `data.user`. Logout returns a success message with `data` set to `null`. Validation errors use Laravel's JSON error format with a `message` and an `errors` object keyed by field. Incorrect login credentials return a generic message and `data: null`.

## Implemented request fields

| Endpoint | Field | Rules |
| --- | --- | --- |
| `POST /api/register` | `name` | Required string, maximum 255 characters |
| `POST /api/register` | `email` | Required valid email, maximum 255 characters, unique among users; normalized to lowercase |
| `POST /api/register` | `password` | Required string, minimum 8 characters, must match `password_confirmation` |
| `POST /api/register` | `password_confirmation` | Required string matching `password` |
| `POST /api/login` | `email` | Required valid email string; normalized to lowercase |
| `POST /api/login` | `password` | Required string |

`GET /api/user` and `POST /api/logout` do not accept request-body fields.

## Planned CRUD API

The following endpoints are documentation plans based on the existing ERD. They are not registered or implemented in the current internship scope.

| Area | Method | Planned endpoint | Purpose |
| --- | --- | --- | --- |
| Projects | `GET` | `/api/projects` | List accessible projects |
| Projects | `POST` | `/api/projects` | Create a project |
| Projects | `GET` | `/api/projects/{project}` | View a project |
| Projects | `PATCH` | `/api/projects/{project}` | Update a project |
| Projects | `DELETE` | `/api/projects/{project}` | Delete a project |
| Project membership | `POST` | `/api/projects/{project}/members` | Add a user with a membership role |
| Project membership | `DELETE` | `/api/projects/{project}/members/{user}` | Remove a project member |
| Tasks | `GET` | `/api/projects/{project}/tasks` | List project tasks |
| Tasks | `POST` | `/api/projects/{project}/tasks` | Create a project task |
| Tasks | `GET` | `/api/projects/{project}/tasks/{task}` | View a project task |
| Tasks | `PATCH` | `/api/projects/{project}/tasks/{task}` | Update a project task |
| Tasks | `DELETE` | `/api/projects/{project}/tasks/{task}` | Delete a project task |
| Task assignments | `POST` | `/api/projects/{project}/tasks/{task}/assignees` | Assign a project user to a task |
| Task assignments | `DELETE` | `/api/projects/{project}/tasks/{task}/assignees/{user}` | Remove a task assignee |
| Tags | `GET` | `/api/tags` | List accessible tags |
| Tags | `POST` | `/api/tags` | Create a project tag |
| Tags | `POST` | `/api/projects/{project}/tasks/{task}/tags` | Attach a tag to a task |
| Tags | `DELETE` | `/api/projects/{project}/tasks/{task}/tags/{tag}` | Detach a tag from a task |
| Comments | `GET` | `/api/projects/{project}/tasks/{task}/comments` | List task comments |
| Comments | `POST` | `/api/projects/{project}/tasks/{task}/comments` | Add a task comment |
| Comments | `PATCH` | `/api/projects/{project}/tasks/{task}/comments/{comment}` | Update an authored comment |
| Comments | `DELETE` | `/api/projects/{project}/tasks/{task}/comments/{comment}` | Delete an authored or moderated comment |

All planned endpoints would require Sanctum authentication and project-scoped authorization. Their example payloads follow the columns and relationships documented in the [ERD](ERD.md). Their presence in the Postman collection is for planning and review only, not evidence of backend implementation.
