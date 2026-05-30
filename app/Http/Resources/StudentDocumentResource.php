<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StudentDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'type' => $this->type,
            'file_path' => $this->file_path,
            'url' => Storage::disk('public')->url($this->file_path),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'uploaded_by' => $this->uploaded_by,
            'uploaded_by_user' => $this->whenLoaded('uploader', function () {
                return [
                    'id' => $this->uploader?->id,
                    'first_name' => $this->uploader?->first_name,
                    'last_name' => $this->uploader?->last_name,
                    'email' => $this->uploader?->email,
                ];
            }),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
