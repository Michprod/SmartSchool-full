<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    
    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = ['all_permissions'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'is_active',
        'department',
        'has_professional_profile',
        'workload_hours',
        'job_grade',
        'job_title',
        'permissions',
        'last_login',
        'birth_date',
        'address',
        'city',
        'province',
        'province_id',
        'city_id',
        'commune_id',
        'quartier',
        'bio',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'has_professional_profile' => 'boolean',
            'workload_hours' => 'integer',
            'permissions' => 'array',
            'last_login' => 'datetime',
            'birth_date' => 'date',
        ];
    }

    public function rdcProvince()
    {
        return $this->belongsTo(RdcProvince::class, 'province_id');
    }

    public function rdcCity()
    {
        return $this->belongsTo(RdcCity::class, 'city_id');
    }

    public function rdcCommune()
    {
        return $this->belongsTo(RdcCommune::class, 'commune_id');
    }

    public function personnel()
    {
        return $this->hasOne(Personnel::class);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // System administrator always has all permissions
        if ($this->role === 'admin') {
            return true;
        }

        // If user has role-based permissions from the role
        $role = Role::where('slug', $this->role)->first();
        if ($role && !empty($role->permissions)) {
            $rolePermissions = $role->permissions;
            
            // Check for wildcard permission (admin)
            if (in_array('*', $rolePermissions)) {
                return true;
            }
            
            // Check exact permission match
            if (in_array($permission, $rolePermissions)) {
                return true;
            }
            
            // Check wildcard resource permission (e.g., 'students:*' matches 'students:read')
            $resource = explode(':', $permission)[0];
            if (in_array($resource . ':*', $rolePermissions)) {
                return true;
            }
        }
        
        // Check user-specific permissions (if any)
        if (!empty($this->permissions)) {
            if (in_array('*', $this->permissions)) {
                return true;
            }
            if (in_array($permission, $this->permissions)) {
                return true;
            }
            $resource = explode(':', $permission)[0];
            if (in_array($resource . ':*', $this->permissions)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the role model for this user
     */
    public function getRole()
    {
        return Role::where('slug', $this->role)->first();
    }

    /**
     * Get all permissions for this user (from role + user-specific)
     */
    public function getAllPermissions(): array
    {
        // System administrator always has all permissions
        if ($this->role === 'admin') {
            return ['*'];
        }

        $permissions = [];
        
        // Get permissions from role
        $role = $this->getRole();
        if ($role && !empty($role->permissions)) {
            $permissions = array_merge($permissions, $role->permissions);
        }
        
        // Merge with user-specific permissions
        if (!empty($this->permissions)) {
            $permissions = array_merge($permissions, $this->permissions);
        }
        
        return array_unique($permissions);
    }

    /**
     * Accessor for all_permissions attribute
     */
    public function getAllPermissionsAttribute(): array
    {
        return $this->getAllPermissions();
    }

    // ============================================================
    // RELATIONS CLASSE/PROFESSEUR
    // ============================================================

    /**
     * Classe dont ce professeur est le titulaire (principal)
     * Relation one-to-many: un professeur peut être titulaire d'une seule classe
     */
    public function principalClass()
    {
        return $this->hasOne(SchoolClass::class, 'teacher_id');
    }

    /**
     * Classes où ce professeur enseigne (via class_subject normalisé)
     */
    public function teachingClasses()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subject', 'teacher_id', 'class_id')
            ->withPivot('subject_id', 'coefficient', 'academic_year', 'is_active')
            ->withTimestamps();
    }

    /**
     * Classes actives où le professeur enseigne actuellement
     */
    public function activeTeachingClasses()
    {
        return $this->teachingClasses()->wherePivot('is_active', true);
    }

    /**
     * Obtenir les matières enseignées par ce professeur avec les classes associées
     */
    public function subjectsWithClasses()
    {
        return ClassSubject::where('teacher_id', $this->id)
            ->where('is_active', true)
            ->with(['subject', 'schoolClass'])
            ->get()
            ->groupBy(fn ($cs) => $cs->subject?->name ?? 'unknown');
    }

    /**
     * Vérifier si ce professeur enseigne dans une classe spécifique
     */
    public function teachesInClass(int $classId): bool
    {
        return ClassSubject::where('teacher_id', $this->id)
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * IDs des classes accessibles (matières enseignées + classe titulaire).
     *
     * @return array<int>
     */
    public function accessibleClassIds(?string $academicYear = null): array
    {
        return collect($this->myClassesAssignmentGroups($academicYear))
            ->map(fn (array $group) => $group['class']->id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Groupes classe + matières pour l'API my-classes (inclut titulaire sans matière).
     *
     * @return array<int, array{class: SchoolClass, is_principal: bool, subjects: \Illuminate\Support\Collection}>
     */
    public function myClassesAssignmentGroups(?string $academicYear = null): array
    {
        $classSubjects = ClassSubject::where('teacher_id', $this->id)
            ->where('is_active', true)
            ->when($academicYear, fn ($q) => $q->where('academic_year', $academicYear))
            ->with(['schoolClass', 'subject'])
            ->get()
            ->groupBy('class_id');

        $result = [];
        $seenClassIds = [];

        foreach ($classSubjects as $classId => $items) {
            $class = $items->first()->schoolClass;
            if (! $class) {
                continue;
            }
            $seenClassIds[] = (int) $classId;
            $result[] = [
                'class' => $class,
                'is_principal' => (int) $class->teacher_id === (int) $this->id,
                'subjects' => $items->map(fn (ClassSubject $item) => [
                    'subject' => $item->subject,
                    'coefficient' => $item->coefficient,
                    'hours_per_week' => $item->hours_per_week,
                    'academic_year' => $item->academic_year,
                ])->values(),
            ];
        }

        $principalQuery = SchoolClass::query()->where('teacher_id', $this->id);
        if ($academicYear) {
            $principalQuery->where('academic_year', $academicYear);
        }
        $principalClass = $principalQuery->first();

        if ($principalClass && ! in_array((int) $principalClass->id, $seenClassIds, true)) {
            $result[] = [
                'class' => $principalClass,
                'is_principal' => true,
                'subjects' => collect(),
            ];
        }

        return $result;
    }

    /**
     * Vérifier si un professeur peut noter une classe/matière spécifique
     */
    public function canGrade(int $classId, int $subjectId): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return ClassSubject::where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('teacher_id', $this->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Obtenir l'emploi du temps complet du professeur (classes titulaire + matières)
     */
    public function fullSchedule()
    {
        $schedule = [];

        // Classe dont il est titulaire
        if ($this->principalClass) {
            $schedule['principal_class'] = $this->principalClass;
        }

        // Classes où il enseigne des matières
        $schedule['teaching_classes'] = ClassSubject::where('teacher_id', $this->id)
            ->where('is_active', true)
            ->with(['schoolClass', 'subject'])
            ->get()
            ->map(function ($classSubject) {
                return [
                    'class' => $classSubject->schoolClass,
                    'subject' => $classSubject->subject?->name,
                    'academic_year' => $classSubject->academic_year,
                ];
            });

        return $schedule;
    }

    // ============================================================
    // RELATIONS SYSTÈME DE NOTES (PROFESSEUR)
    // ============================================================

    /**
     * Matières enseignées par ce professeur (via class_subject)
     */
    public function teachingSubjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'teacher_id', 'subject_id')
            ->withPivot('class_id', 'coefficient', 'academic_year', 'is_active')
            ->withTimestamps();
    }

    /**
     * Évaluations créées par ce professeur
     */
    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'teacher_id');
    }

    /**
     * Évaluations pour une classe et matière spécifiques
     */
    public function assessmentsForClassAndSubject(int $classId, int $subjectId)
    {
        return $this->assessments()
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId);
    }

    /**
     * Obtenir les élèves que ce professeur peut évaluer
     */
    public function studentsToGrade()
    {
        // Récupérer les IDs des classes où il enseigne
        $classIds = \App\Models\ClassSubject::where('teacher_id', $this->id)
            ->where('is_active', true)
            ->pluck('class_id')
            ->unique()
            ->toArray();

        return Student::whereIn('class_id', $classIds)->get();
    }

    /**
     * Moyennes calculées par ce professeur
     */
    public function generatedAverages()
    {
        return $this->hasMany(StudentAverage::class, 'calculated_by');
    }

    /**
     * Bulletins générés par ce professeur
     */
    public function generatedReportCards()
    {
        return $this->hasMany(ReportCard::class, 'generated_by');
    }
}
