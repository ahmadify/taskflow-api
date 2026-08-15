<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachTaskTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageTags', $this->route('task'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->route('project');
        $task = $this->route('task');

        return [
            'tag_id' => [
                'required',
                'integer',
                Rule::exists('tags', 'id')
                    ->where(fn (Builder $query) => $query->where('project_id', $project->id)),
                Rule::unique('task_tag', 'tag_id')
                    ->where(fn (Builder $query) => $query->where('task_id', $task->id)),
            ],
        ];
    }
}
