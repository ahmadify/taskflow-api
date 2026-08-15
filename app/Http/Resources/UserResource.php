<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'membership' => $this->whenPivotLoaded('project_user', fn () => [
                'role' => $this->pivot->role,
                'joined_at' => $this->pivot->joined_at,
            ]),
            'assignment' => $this->whenPivotLoaded('task_user', fn () => [
                'assigned_at' => $this->pivot->assigned_at,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
