<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\User;

class BulletinAccessService
{
    public function __construct(
        protected AcademicPeriodService $periods
    ) {}

    public function isParentOfStudent(User $user, Student $student): bool
    {
        if ($user->role !== 'parent') {
            return false;
        }

        $parentIds = $student->parent_ids ?? [];

        return in_array($user->id, array_map('intval', $parentIds), true);
    }

    /**
     * Frais scolaires impayés bloquants (configurable).
     */
    public function hasBlockingUnpaidTuition(Student $student): bool
    {
        if (! $this->isBulletinBlockedForUnpaid()) {
            return false;
        }

        return Payment::query()
            ->where('student_id', $student->id)
            ->where('type', 'tuition')
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('due_date')
                    ->orWhere('due_date', '<', now()->toDateString());
            })
            ->exists();
    }

    public function isBulletinBlockedForUnpaid(): bool
    {
        $settings = \App\Models\Setting::query()->where('key', 'school_settings')->first();
        $value = $settings?->value;

        if (is_array($value) && array_key_exists('block_bulletin_unpaid', $value)) {
            return (bool) $value['block_bulletin_unpaid'];
        }

        return true;
    }

    /**
     * @return array{allowed: bool, reason: ?string}
     */
    public function canViewStudentBulletin(User $user, Student $student, ?ReportCard $reportCard = null): array
    {
        if ($user->role === 'admin' || $user->hasPermission('grades:*')) {
            return ['allowed' => true, 'reason' => null];
        }

        if ($user->hasPermission('grades:read') || $user->hasRole('teacher')) {
            if ($user->canGrade($student->class_id, 0) || $student->schoolClass?->teacher_id === $user->id) {
                return ['allowed' => true, 'reason' => null];
            }
        }

        if ($user->role === 'parent' || $user->hasPermission('bulletins:read_own')) {
            if (! $this->isParentOfStudent($user, $student)) {
                return ['allowed' => false, 'reason' => 'Vous ne pouvez consulter que les bulletins de vos enfants.'];
            }

            if ($this->hasBlockingUnpaidTuition($student)) {
                return [
                    'allowed' => false,
                    'reason' => 'Accès au bulletin suspendu : frais scolaires impayés. Veuillez régulariser votre situation à la comptabilité.',
                ];
            }

            if ($reportCard && ! $reportCard->is_published) {
                return ['allowed' => false, 'reason' => 'Le bulletin n\'est pas encore publié.'];
            }

            return ['allowed' => true, 'reason' => null];
        }

        return ['allowed' => false, 'reason' => 'Droits insuffisants pour consulter ce bulletin.'];
    }

    /**
     * @return array{allowed: bool, reason: ?string}
     */
    public function canViewStudentAcademicData(User $user, Student $student): array
    {
        $bulletinCheck = $this->canViewStudentBulletin($user, $student);

        if ($user->role === 'parent') {
            return $bulletinCheck;
        }

        if ($user->role === 'admin' || $user->hasPermission('grades:*') || $user->hasPermission('grades:read')) {
            return ['allowed' => true, 'reason' => null];
        }

        if ($user->hasRole('teacher') && ($user->canGrade($student->class_id, 0) || $student->schoolClass?->teacher_id === $user->id)) {
            return ['allowed' => true, 'reason' => null];
        }

        return ['allowed' => false, 'reason' => 'Accès non autorisé aux résultats scolaires.'];
    }
}
