<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_taskflow_tables_and_columns_exist(): void
    {
        $expectedSchema = [
            'projects' => ['id', 'owner_id', 'name', 'description', 'start_date', 'due_date', 'created_at', 'updated_at'],
            'project_user' => ['project_id', 'user_id', 'role', 'joined_at', 'created_at', 'updated_at'],
            'tasks' => ['id', 'project_id', 'created_by', 'title', 'description', 'status', 'priority', 'due_date', 'completed_at', 'created_at', 'updated_at'],
            'task_user' => ['task_id', 'user_id', 'assigned_at', 'created_at', 'updated_at'],
            'tags' => ['id', 'project_id', 'name', 'color', 'created_at', 'updated_at'],
            'task_tag' => ['task_id', 'tag_id', 'created_at', 'updated_at'],
            'comments' => ['id', 'task_id', 'user_id', 'body', 'created_at', 'updated_at'],
        ];

        foreach ($expectedSchema as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
            $this->assertTrue(Schema::hasColumns($table, $columns), "Missing columns on table: {$table}");
        }
    }
}
