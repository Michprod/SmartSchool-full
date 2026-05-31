# Guide des Relations Classe-Professeur-Élève

Ce document décrit la nouvelle architecture des relations entre classes, professeurs et élèves après la refonte de la base de données.

## Vue d'ensemble du modèle

```
┌─────────────┐         ┌─────────────────┐         ┌─────────────┐
│   Student   │         │   SchoolClass   │         │    User     │
│   (Élève)   │         │    (Classe)     │         │ (Professeur)│
└──────┬──────┘         └────────┬────────┘         └──────┬──────┘
       │                         │                         │
       │ class_id (FK)           │ teacher_id (FK)         │
       │ Many-to-One             │ Many-to-One             │
       │                         │ (Titulaire)             │
       │                         │                         │
       └───────────────┬─────────┴─────────────────────────┘
                         │
                ┌────────▼────────┐
                │  class_teacher  │  (Table pivot)
                │  (Many-to-Many) │
                └─────────────────┘
                       Attributes:
                       - subject (matière)
                       - academic_year
                       - schedule
                       - is_active
```

## Relations détaillées

### 1. Élève → Classe (Many-to-One)
Un élève appartient à **une seule** classe.
```php
$student = Student::find(1);
$class = $student->schoolClass;
```

### 2. Classe → Élèves (One-to-Many)
Une classe contient **plusieurs** élèves.
```php
$class = SchoolClass::find(1);
$students = $class->students;
```

### 3. Classe → Professeur Titulaire (Many-to-One)
Une classe a **un seul** professeur titulaire (principal).
```php
$class = SchoolClass::find(1);
$principalTeacher = $class->teacher;
```

### 4. Professeur → Classe Titulaire (One-to-One)
Un professeur peut être titulaire d'**une seule** classe.
```php
$teacher = User::find(1);
$principalClass = $teacher->principalClass;
```

### 5. Classe ↔ Professeurs Matières (Many-to-Many)
Une classe peut avoir **plusieurs** professeurs de matières différentes.
```php
$class = SchoolClass::find(1);

// Tous les professeurs de matières
$subjectTeachers = $class->subjectTeachers;

// Professeurs actifs uniquement
$activeTeachers = $class->activeSubjectTeachers;

// Professeurs groupés par matière
$bySubject = $class->teachersBySubject();
// Résultat: ['Mathématiques' => [...], 'Français' => [...]]
```

### 6. Professeur ↔ Classes (Many-to-Many)
Un professeur peut enseigner dans **plusieurs** classes.
```php
$teacher = User::find(1);

// Toutes les classes où il enseigne
$classes = $teacher->teachingClasses;

// Classes actives uniquement
$activeClasses = $teacher->activeTeachingClasses;

// Matières avec leurs classes
$subjects = $teacher->subjectsWithClasses();
// Résultat: ['Mathématiques' => [Classe1, Classe2], 'Physique' => [Classe1]]

// Vérifier s'il enseigne dans une classe
$teachesHere = $teacher->teachesInClass(5); // true/false
```

## Utilisation de la table pivot (class_teacher)

### Attacher un professeur à une classe
```php
$class = SchoolClass::find(1);
$teacher = User::find(5);

// Méthode 1: attach avec données pivot
$class->subjectTeachers()->attach($teacher->id, [
    'subject' => 'Mathématiques',
    'academic_year' => '2025-2026',
    'schedule' => json_encode([
        'monday' => ['08:00-10:00'],
        'wednesday' => ['10:00-12:00']
    ]),
    'is_active' => true
]);

// Méthode 2: sync (remplace toutes les relations)
$class->subjectTeachers()->sync([
    $teacher1->id => ['subject' => 'Mathématiques', 'academic_year' => '2025-2026'],
    $teacher2->id => ['subject' => 'Français', 'academic_year' => '2025-2026'],
]);

// Méthode 3: syncWithoutDetaching (ajoute sans supprimer)
$class->subjectTeachers()->syncWithoutDetaching([
    $teacher3->id => ['subject' => 'Histoire', 'academic_year' => '2025-2026'],
]);
```

### Détacher un professeur
```php
// Détacher un professeur spécifique
$class->subjectTeachers()->detach($teacher->id);

// Détacher tous les professeurs
$class->subjectTeachers()->detach();
```

### Mettre à jour une relation existante
```php
$class->subjectTeachers()->updateExistingPivot($teacher->id, [
    'subject' => 'Algèbre',
    'is_active' => false, // Désactiver temporairement
]);
```

### Récupérer avec données pivot
```php
$teacher = $class->subjectTeachers->first();
echo $teacher->pivot->subject;        // "Mathématiques"
echo $teacher->pivot->academic_year; // "2025-2026"
echo $teacher->pivot->is_active;      // true/false
```

## Exemples pratiques

### 1. Obtenir l'emploi du temps complet d'un professeur
```php
$teacher = User::find(1);
$schedule = $teacher->fullSchedule();

/*
[
    'principal_class' => SchoolClass {...},
    'teaching_classes' => Collection [
        [
            'class' => SchoolClass {...},
            'subject' => 'Mathématiques',
            'academic_year' => '2025-2026',
            'schedule' => [...]
        ],
        [...]
    ]
]
*/
```

### 2. Obtenir tous les professeurs d'un élève
```php
$student = Student::find(1);
$allTeachers = $student->allClassTeachers();

/*
Collection [
    ['teacher' => User {...}, 'role' => 'titulaire', 'subject' => null],
    ['teacher' => User {...}, 'role' => 'matière', 'subject' => 'Mathématiques'],
    ['teacher' => User {...}, 'role' => 'matière', 'subject' => 'Français'],
]
*/
```

### 3. Lister les classes avec leurs professeurs
```php
$classes = SchoolClass::with(['teacher', 'subjectTeachers'])->get();

foreach ($classes as $class) {
    echo "Classe: {$class->name}\n";
    echo "Titulaire: {$class->teacher?->first_name} {$class->teacher?->last_name}\n";
    
    echo "Professeurs de matières:\n";
    foreach ($class->teachersBySubject() as $subject => $teachers) {
        echo "  - {$subject}: ";
        echo $teachers->pluck('full_name')->join(', ');
        echo "\n";
    }
}
```

### 4. Requêtes avec filtres
```php
// Professeurs enseignant les mathématiques cette année
$teachers = User::whereHas('teachingClasses', function ($query) {
    $query->wherePivot('subject', 'Mathématiques')
          ->wherePivot('academic_year', '2025-2026')
          ->wherePivot('is_active', true);
})->get();

// Classes ayant plus de 3 professeurs
$classes = SchoolClass::has('subjectTeachers', '>', 3)->get();

// Classes sans professeur titulaire
$classesWithoutTeacher = SchoolClass::whereNull('teacher_id')->get();
```

### 5. Scopes utiles
```php
// Dans SchoolClass model, ajouter ces scopes

public function scopeWithActiveTeachers($query)
{
    return $query->with(['subjectTeachers' => function ($q) {
        $q->wherePivot('is_active', true);
    }]);
}

public function scopeForAcademicYear($query, $year)
{
    return $query->with(['subjectTeachers' => function ($q) use ($year) {
        $q->wherePivot('academic_year', $year);
    }]);
}
```

## Migration

Exécuter la migration pour créer la table pivot :
```bash
php artisan migrate
```

Cela créera la table `class_teacher` avec :
- `class_id` → FK vers `school_classes`
- `teacher_id` → FK vers `users`
- `subject` → Matière enseignée
- `academic_year` → Année académique
- `schedule` → Emploi du temps JSON
- `is_active` → Statut actif/inactif
- Contrainte d'unicité sur (class_id, teacher_id, subject, academic_year)

## Notes importantes

1. **Professeur Titulaire vs Matière** : Le professeur titulaire est stocké dans `school_classes.teacher_id`, tandis que les professeurs de matières sont dans la table pivot `class_teacher`.

2. **Unicité** : Un professeur ne peut pas enseigner la même matière dans la même classe la même année (contrainte d'unicité).

3. **Soft Delete** : Si nécessaire, ajouter `SoftDeletes` aux modèles et à la table pivot.

4. **Performance** : Utiliser `with()` pour eager loading et éviter le N+1 :
   ```php
   SchoolClass::with(['teacher', 'classSubjects.subject', 'classSubjects.teacher', 'students'])->get();
   ```

## Module Enseignant (2026)

### Table `class_subject` (remplace `class_teacher`)

| Champ | Description |
|-------|-------------|
| `class_id`, `subject_id`, `teacher_id` | Affectation matière/classe/prof |
| `coefficient`, `hours_per_week` | Poids pédagogique et charge |
| `schedule` | Emploi du temps JSON par jour |
| `academic_year`, `is_active` | Année et statut |

### API affectations

- `GET /api/classes/{class}/subjects` — matrice matières
- `POST /api/classes/{class}/subjects` — créer affectation
- `PUT /api/class-subjects/{id}` — modifier
- `PUT /api/class-subjects/{id}/schedule` — emploi du temps
- `DELETE /api/class-subjects/{id}` — désactiver si notes existent

### API profil enseignant

- `GET /api/teachers` — liste (admin)
- `GET /api/teachers/{user}` — fiche complète
- `GET /api/me/teaching-profile` — profil connecté
- `GET /api/me/timetable` — EDT agrégé
- `GET /api/teachers/workload-summary` — charge globale
