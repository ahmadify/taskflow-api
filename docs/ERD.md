# TaskFlow Entity-Relationship Diagram

The proposed schema keeps membership, assignment, and tagging relationships explicit through pivot tables. Project ownership is separate from project membership; an owner should also receive an `owner` membership row when a project is created.

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        timestamp created_at
        timestamp updated_at
    }

    projects {
        bigint id PK
        bigint owner_id FK
        string name
        text description
        date start_date
        date due_date
        timestamp created_at
        timestamp updated_at
    }

    project_user {
        bigint project_id PK, FK
        bigint user_id PK, FK
        string role
        timestamp joined_at
        timestamp created_at
        timestamp updated_at
    }

    tasks {
        bigint id PK
        bigint project_id FK
        bigint created_by FK
        string title
        text description
        string status
        string priority
        date due_date
        timestamp completed_at
        timestamp created_at
        timestamp updated_at
    }

    task_user {
        bigint task_id PK, FK
        bigint user_id PK, FK
        timestamp assigned_at
        timestamp created_at
        timestamp updated_at
    }

    tags {
        bigint id PK
        bigint project_id FK
        string name
        string color
        timestamp created_at
        timestamp updated_at
    }

    task_tag {
        bigint task_id PK, FK
        bigint tag_id PK, FK
        timestamp created_at
        timestamp updated_at
    }

    comments {
        bigint id PK
        bigint task_id FK
        bigint user_id FK
        text body
        timestamp created_at
        timestamp updated_at
    }

    users ||--o{ projects : owns
    users ||--o{ project_user : has_memberships
    projects ||--o{ project_user : has_members
    projects ||--o{ tasks : contains
    users ||--o{ tasks : creates
    tasks ||--o{ task_user : has_assignments
    users ||--o{ task_user : receives_assignments
    projects ||--o{ tags : defines
    tasks ||--o{ task_tag : has_tags
    tags ||--o{ task_tag : labels_tasks
    tasks ||--o{ comments : has
    users ||--o{ comments : writes
```

## Constraints and cardinalities

- A user can own zero or many projects; each project has exactly one owner through `projects.owner_id`.
- Users and projects have a many-to-many membership relationship through `project_user`. Each pivot row belongs to exactly one user and one project and is unique by `(project_id, user_id)`.
- A project can contain zero or many tasks; each task belongs to exactly one project.
- A user can create zero or many tasks; each task records exactly one creator through `tasks.created_by`.
- Users and tasks have a many-to-many assignment relationship through `task_user`, unique by `(task_id, user_id)`.
- A project can define zero or many tags; each tag belongs to exactly one project. Tag names should be unique within a project.
- Tasks and tags have a many-to-many relationship through `task_tag`, unique by `(task_id, tag_id)`. Application validation must ensure both belong to the same project.
- A task can have zero or many comments; each comment belongs to exactly one task and one authoring user.
