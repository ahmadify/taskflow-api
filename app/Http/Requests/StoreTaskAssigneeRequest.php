<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskAssigneeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageAssignments', $this->route('task'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->route('project');
        $task = $this->route('task');

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($project): void {
                    if ((int) $value !== $project->owner_id
                        && ! $project->members()->whereKey($value)->exists()) {
                        $fail('The selected user must belong to the project.');
                    }
                },
                Rule::unique('task_user', 'user_id')
                    ->where(fn (Builder $query) => $query->where('task_id', $task->id)),
            ],
        ];
    }
}
