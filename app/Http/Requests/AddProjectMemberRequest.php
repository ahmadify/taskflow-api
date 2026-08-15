<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageMembers', $this->route('project'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::notIn([$project->owner_id]),
                Rule::unique('project_user', 'user_id')
                    ->where(fn (Builder $query) => $query->where('project_id', $project->id)),
            ],
            'role' => ['sometimes', 'string', Rule::in(['member'])],
        ];
    }
}
