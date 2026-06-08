<?php

namespace App\Services;

use App\Models\ClassSubject;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TimetableService
{
    public const ALLOWED_DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    /**
     * @param  array<string, mixed>  $schedule
     * @return array<string, array<int, array{start: string, end: string, room?: string|null}>>
     */
    public function validateSchedule(array $schedule): array
    {
        $normalized = [];

        foreach ($schedule as $day => $daySlots) {
            if (! in_array($day, self::ALLOWED_DAYS, true)) {
                throw ValidationException::withMessages([
                    'schedule' => ["Jour invalide : {$day}."],
                ]);
            }

            if (! is_array($daySlots)) {
                throw ValidationException::withMessages([
                    'schedule' => ["Le jour {$day} doit être un tableau de créneaux."],
                ]);
            }

            $normalized[$day] = [];

            foreach ($daySlots as $index => $slot) {
                if (! is_array($slot)) {
                    throw ValidationException::withMessages([
                        'schedule' => ["Créneau invalide pour {$day} (#{$index})."],
                    ]);
                }

                $start = $slot['start'] ?? null;
                $end = $slot['end'] ?? null;

                if (! is_string($start) || ! preg_match('/^\d{2}:\d{2}$/', $start)) {
                    throw ValidationException::withMessages([
                        'schedule' => ["Heure de début invalide pour {$day} (#{$index})."],
                    ]);
                }

                if (! is_string($end) || ! preg_match('/^\d{2}:\d{2}$/', $end)) {
                    throw ValidationException::withMessages([
                        'schedule' => ["Heure de fin invalide pour {$day} (#{$index})."],
                    ]);
                }

                if ($this->toMinutes($start) >= $this->toMinutes($end)) {
                    throw ValidationException::withMessages([
                        'schedule' => ["L'heure de fin doit être après le début ({$day}, créneau #{$index})."],
                    ]);
                }

                $normalized[$day][] = [
                    'start' => $start,
                    'end' => $end,
                    'room' => isset($slot['room']) && $slot['room'] !== '' ? (string) $slot['room'] : null,
                ];
            }
        }

        return $normalized;
    }

    /**
     * @param  Collection<int, ClassSubject>  $assignments
     * @return array<int, array<string, mixed>>
     */
    public function flattenSlots(Collection $assignments): array
    {
        $slots = [];

        foreach ($assignments as $assignment) {
            if (! is_array($assignment->schedule)) {
                continue;
            }

            foreach ($assignment->schedule as $day => $daySlots) {
                if (! is_array($daySlots)) {
                    continue;
                }

                foreach ($daySlots as $slot) {
                    if (! is_array($slot)) {
                        continue;
                    }

                    $slots[] = [
                        'day' => $day,
                        'start' => $slot['start'] ?? null,
                        'end' => $slot['end'] ?? null,
                        'room' => $slot['room'] ?? null,
                        'class_id' => $assignment->class_id,
                        'class_name' => $assignment->schoolClass?->display_name ?? $assignment->schoolClass?->name,
                        'subject_name' => $assignment->subject?->name,
                        'subject_id' => $assignment->subject_id,
                        'teacher_id' => $assignment->teacher_id,
                        'teacher_name' => $assignment->teacher
                            ? trim("{$assignment->teacher->first_name} {$assignment->teacher->last_name}")
                            : null,
                        'assignment_id' => $assignment->id,
                    ];
                }
            }
        }

        usort($slots, function (array $a, array $b) {
            $dayOrder = array_flip(self::ALLOWED_DAYS);
            $dayCmp = ($dayOrder[$a['day']] ?? 99) <=> ($dayOrder[$b['day']] ?? 99);
            if ($dayCmp !== 0) {
                return $dayCmp;
            }

            return strcmp((string) $a['start'], (string) $b['start']);
        });

        return $slots;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function detectConflicts(?string $academicYear = null, ?int $excludeAssignmentId = null): array
    {
        $query = ClassSubject::query()
            ->where('is_active', true)
            ->with(['schoolClass', 'subject', 'teacher']);

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }

        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }

        $assignments = $query->get();
        $entries = [];

        foreach ($assignments as $assignment) {
            if (! is_array($assignment->schedule)) {
                continue;
            }

            foreach ($assignment->schedule as $day => $daySlots) {
                if (! is_array($daySlots)) {
                    continue;
                }

                foreach ($daySlots as $slot) {
                    if (! is_array($slot) || empty($slot['start']) || empty($slot['end'])) {
                        continue;
                    }

                    $entries[] = [
                        'assignment_id' => $assignment->id,
                        'teacher_id' => $assignment->teacher_id,
                        'class_id' => $assignment->class_id,
                        'day' => $day,
                        'start' => $slot['start'],
                        'end' => $slot['end'],
                        'room' => $slot['room'] ?? null,
                        'class_name' => $assignment->schoolClass?->display_name ?? $assignment->schoolClass?->name,
                        'subject_name' => $assignment->subject?->name,
                        'teacher_name' => $assignment->teacher
                            ? trim("{$assignment->teacher->first_name} {$assignment->teacher->last_name}")
                            : null,
                    ];
                }
            }
        }

        $conflicts = [];

        for ($i = 0; $i < count($entries); $i++) {
            for ($j = $i + 1; $j < count($entries); $j++) {
                $a = $entries[$i];
                $b = $entries[$j];

                if ($a['day'] !== $b['day'] || ! $this->timesOverlap($a['start'], $a['end'], $b['start'], $b['end'])) {
                    continue;
                }

                if ($a['teacher_id'] === $b['teacher_id']) {
                    $conflicts[] = $this->conflictPayload('teacher_overlap', $a, $b);
                }

                if (! empty($a['room']) && ! empty($b['room']) && $a['room'] === $b['room']) {
                    $conflicts[] = $this->conflictPayload('room_overlap', $a, $b);
                }

                if ($a['class_id'] === $b['class_id']) {
                    $conflicts[] = $this->conflictPayload('class_overlap', $a, $b);
                }
            }
        }

        return $conflicts;
    }

    /**
     * Conflits pour une affectation donnée après mise à jour du schedule.
     *
     * @param  array<string, mixed>  $schedule
     * @return array<int, array<string, mixed>>
     */
    public function conflictsForAssignment(ClassSubject $assignment, array $schedule): array
    {
        $normalized = $this->validateSchedule($schedule);
        $temp = $assignment->replicate();
        $temp->schedule = $normalized;
        $temp->id = $assignment->id;

        $others = ClassSubject::query()
            ->where('is_active', true)
            ->where('academic_year', $assignment->academic_year)
            ->where('id', '!=', $assignment->id)
            ->with(['schoolClass', 'subject', 'teacher'])
            ->get();

        $all = $others->push($temp);
        $entries = [];

        foreach ($all as $item) {
            if (! is_array($item->schedule)) {
                continue;
            }

            foreach ($item->schedule as $day => $daySlots) {
                if (! is_array($daySlots)) {
                    continue;
                }

                foreach ($daySlots as $slot) {
                    if (! is_array($slot) || empty($slot['start']) || empty($slot['end'])) {
                        continue;
                    }

                    $entries[] = [
                        'assignment_id' => $item->id,
                        'teacher_id' => $item->teacher_id,
                        'class_id' => $item->class_id,
                        'day' => $day,
                        'start' => $slot['start'],
                        'end' => $slot['end'],
                        'room' => $slot['room'] ?? null,
                        'class_name' => $item->schoolClass?->display_name ?? $item->schoolClass?->name,
                        'subject_name' => $item->subject?->name,
                        'teacher_name' => $item->teacher
                            ? trim("{$item->teacher->first_name} {$item->teacher->last_name}")
                            : null,
                    ];
                }
            }
        }

        $conflicts = [];
        $targetId = $assignment->id;

        for ($i = 0; $i < count($entries); $i++) {
            for ($j = $i + 1; $j < count($entries); $j++) {
                $a = $entries[$i];
                $b = $entries[$j];

                if ($a['assignment_id'] !== $targetId && $b['assignment_id'] !== $targetId) {
                    continue;
                }

                if ($a['day'] !== $b['day'] || ! $this->timesOverlap($a['start'], $a['end'], $b['start'], $b['end'])) {
                    continue;
                }

                if ($a['teacher_id'] === $b['teacher_id']) {
                    $conflicts[] = $this->conflictPayload('teacher_overlap', $a, $b);
                }

                if (! empty($a['room']) && ! empty($b['room']) && $a['room'] === $b['room']) {
                    $conflicts[] = $this->conflictPayload('room_overlap', $a, $b);
                }

                if ($a['class_id'] === $b['class_id']) {
                    $conflicts[] = $this->conflictPayload('class_overlap', $a, $b);
                }
            }
        }

        return $conflicts;
    }

    private function toMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return ($h * 60) + $m;
    }

    private function timesOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        $aStart = $this->toMinutes($startA);
        $aEnd = $this->toMinutes($endA);
        $bStart = $this->toMinutes($startB);
        $bEnd = $this->toMinutes($endB);

        return $aStart < $bEnd && $bStart < $aEnd;
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     * @return array<string, mixed>
     */
    private function conflictPayload(string $type, array $a, array $b): array
    {
        return [
            'type' => $type,
            'day' => $a['day'],
            'start' => max($a['start'], $b['start']),
            'end' => min($a['end'], $b['end']),
            'assignments' => [$a['assignment_id'], $b['assignment_id']],
            'details' => [$a, $b],
        ];
    }
}
