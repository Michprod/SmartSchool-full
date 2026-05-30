<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentAttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'attendance_date' => optional($this->attendance_date)->toDateString(),
            'status' => $this->status,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'recorded_by' => $this->recorded_by,
            'recorded_by_user' => $this->whenLoaded('recorder', function () {
                return [
                    'id' => $this->recorder?->id,
                    'first_name' => $this->recorder?->first_name,
                    'last_name' => $this->recorder?->last_name,
                    'email' => $this->recorder?->email,
                ];
            }),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
