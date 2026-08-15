<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Tag;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = Project::find($this->integer('project_id'));

        return $project === null || $this->user()->can('create', [Tag::class, $project]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'name')
                    ->where(fn (Builder $query) => $query->where('project_id', $this->integer('project_id'))),
            ],
            'color' => ['nullable', 'string', 'max:255'],
        ];
    }
}
