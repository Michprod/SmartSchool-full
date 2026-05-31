# Système de Gestion des Notes (Grade Management)

Documentation complète du module de gestion des notes, moyennes et bulletins.

## 📊 Architecture du Système

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    SYSTÈME DE NOTES - ARCHITECTURE                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐         │
│  │ Student  │────▶│ Subject  │◀────│  Class   │────▶│  User    │         │
│  │ (Élève)  │     │(Matière) │     │ (Classe) │     │(Professeur)        │
│  └────┬─────┘     └──────────┘     └──────────┘     └──────────┘         │
│       │                                                                     │
│       │                                                                     │
│       ▼                                                                     │
│  ┌────────────────────────────────────────────────────────────────┐     │
│  │                         ASSESSMENTS                               │     │
│  │                    (Table des évaluations)                         │     │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐            │     │
│  │  │ student_id   │  │ subject_id   │  │ teacher_id   │            │     │
│  │  │ score        │  │ max_score    │  │ coefficient  │            │     │
│  │  │ type         │  │ term         │  │ academic_year│            │     │
│  │  └──────────────┘  └──────────────┘  └──────────────┘            │     │
│  └────────────────────────────────────────────────────────────────┘     │
│                                    │                                       │
│                                    ▼                                       │
│  ┌────────────────────────────────────────────────────────────────┐     │
│  │                      STUDENT_AVERAGES                           │     │
│  │                  (Moyennes calculées)                           │     │
│  │  • Moyenne par matière                                           │     │
│  │  • Moyenne générale (toutes matières)                            │     │
│  │  • Rang dans la classe                                           │     │
│  └────────────────────────────────────────────────────────────────┘     │
│                                    │                                       │
│                                    ▼                                       │
│  ┌────────────────────────────────────────────────────────────────┐     │
│  │                       REPORT_CARDS                              │     │
│  │                    (Bulletins trimestriels)                       │     │
│  │  • Décision du conseil (passe/redouble)                          │     │
│  │  • Observations et recommandations                               │     │
│  │  • PDF généré                                                    │     │
│  └────────────────────────────────────────────────────────────────┘     │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## 📋 Tables de la Base de Données

### 1. `subjects` - Matières
```php
- id, name, code, description
- type: 'core' (fondamental) ou 'elective' (optionnel)
- is_active
```

**Exemples:**
- Mathématiques (MATH) - core
- Français (FRAN) - core
- Arts Plastiques (ARTS) - elective

### 2. `class_subject` - Association Classe-Matière
```php
- class_id, subject_id, teacher_id (professeur assigné)
- coefficient: 1, 2, 3, 4... (pour calcul pondéré)
- hours_per_week: Heures par semaine
- academic_year: '2025-2026'
- is_active
```

**Coefficients typiques:**
- Math/Français: 4
- Sciences: 2-3
- Langues: 2
- EPS/Arts: 1

### 3. `assessments` - Évaluations (Notes)
```php
- student_id, subject_id, teacher_id, class_id
- type: 'interrogation', 'devoir', 'composition', 'examen', 'projet'
- term: 'T1', 'T2', 'T3'
- academic_year: '2025-2026'
- score: Note obtenue (ex: 15.50)
- max_score: Note max (20, 10, etc.)
- coefficient: Poids du devoir
- title, comment, date
- is_published: Visible par l'élève?
```

### 4. `student_averages` - Moyennes Calculées
```php
- student_id, subject_id, class_id
- term, academic_year
- average_score: Moyenne sur 20
- total_coefficient: Somme des coeffs
- assessments_count: Nombre de devoirs
- general_average: Moyenne générale
- class_rank: Rang dans la classe
- appreciation: Appréciation automatique
```

### 5. `report_cards` - Bulletins
```php
- student_id, class_id
- term, academic_year
- general_average, class_rank, total_students
- decision: 'pass', 'conditional_pass', 'fail'
- class_council_observation, recommendations
- pdf_path, is_published, is_validated
- generated_by, validated_by
```

## 🧮 Méthode de Calcul des Moyennes

### Moyenne par matière (Points-based Averaging)

```
Moyenne = (Σ(note × coefficient)) / (Σ(max_note × coefficient)) × 20
```

**Exemple:**
```
Devoir 1: 15/20 (coeff 2)
Devoir 2: 12/20 (coeff 2)
Composition: 14/20 (coeff 3)

Moyenne = (15×2 + 12×2 + 14×3) / (20×2 + 20×2 + 20×3) × 20
        = (30 + 24 + 42) / (40 + 40 + 60) × 20
        = 96 / 140 × 20
        = 13.71/20
```

### Moyenne Générale

```
Moyenne Générale = (Σ(moyenne_matière × coeff_matière)) / Σ(coeff_matières)
```

**Appréciations automatiques:**
- ≥ 16: "Très Bien - Excellent travail"
- ≥ 14: "Bien - Bon travail, continuez ainsi"
- ≥ 12: "Assez Bien - Travail satisfaisant"
- ≥ 10: "Passable - Efforts à poursuivre"
- ≥ 8: "Insuffisant - Besoin d'un soutien accru"
- < 8: "Faible - Accompagnement nécessaire"

## 🎯 API Endpoints

### Authentification requise: `auth:sanctum`
### Permission requise: `grades:*`

### Évaluations (Notes)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/grades` | Liste des évaluations du professeur |
| POST | `/api/grades` | Créer une évaluation |
| POST | `/api/grades/bulk` | Créer plusieurs évaluations |
| GET | `/api/grades/{id}` | Voir une évaluation |
| PUT | `/api/grades/{id}` | Modifier une évaluation |
| DELETE | `/api/grades/{id}` | Supprimer une évaluation |
| POST | `/api/grades/{id}/publish` | Publier l'évaluation |

### Classes et Élèves

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/grades/my-classes` | Classes où le professeur enseigne |
| GET | `/api/grades/classes/{id}/students` | Élèves d'une classe |

### Calcul des Moyennes

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/grades/students/{id}/calculate` | Calculer moyennes d'un élève |
| POST | `/api/grades/classes/{id}/calculate` | Calculer moyennes d'une classe |
| GET | `/api/grades/students/{id}/averages` | Voir moyennes d'un élève |
| GET | `/api/grades/classes/{id}/averages` | Voir moyennes de toute la classe |

### Bulletins

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/grades/students/{id}/report-card` | Générer bulletin |
| GET | `/api/grades/students/{id}/report-card` | Voir bulletin |

## 💻 Exemples d'Utilisation

### 1. Créer une évaluation

```php
POST /api/grades
{
    "student_id": 1,
    "subject_id": 2,
    "class_id": 1,
    "type": "devoir",
    "term": "T1",
    "academic_year": "2025-2026",
    "score": 15.5,
    "max_score": 20,
    "coefficient": 2,
    "title": "Devoir #3 - Équations",
    "comment": "Bon travail, mais attention aux erreurs de calcul",
    "date": "2025-10-15"
}
```

### 2. Créer des évaluations en masse

```php
POST /api/grades/bulk
{
    "class_id": 1,
    "subject_id": 2,
    "type": "devoir",
    "term": "T1",
    "academic_year": "2025-2026",
    "max_score": 20,
    "coefficient": 2,
    "title": "Devoir commun - Mathématiques",
    "date": "2025-10-15",
    "grades": [
        {"student_id": 1, "score": 15.5, "comment": "Bien"},
        {"student_id": 2, "score": 12.0, "comment": "Peut mieux faire"},
        {"student_id": 3, "score": 18.0, "comment": "Excellent"},
        // ... autres élèves
    ]
}
```

### 3. Calculer les moyennes

```php
// Pour un élève
POST /api/grades/students/1/calculate
{
    "term": "T1",
    "academic_year": "2025-2026"
}

// Pour toute une classe
POST /api/grades/classes/1/calculate
{
    "term": "T1",
    "academic_year": "2025-2026"
}
```

### 4. Voir les moyennes d'un élève

```php
GET /api/grades/students/1/averages?term=T1&academic_year=2025-2026

Réponse:
{
    "student": {...},
    "term": "T1",
    "academic_year": "2025-2026",
    "general_average": 14.22,
    "class_rank": 5,
    "total_students": 25,
    "subject_averages": [
        {
            "subject": {"name": "Mathématiques"},
            "average_score": 15.50,
            "appreciation": "Bien - Bon travail, continuez ainsi"
        },
        // ... autres matières
    ]
}
```

### 5. Générer un bulletin

```php
POST /api/grades/students/1/report-card
{
    "term": "T1",
    "academic_year": "2025-2026",
    "class_council_observation": "Élève sérieux, participation active",
    "work_recommendations": "Approfondir la révision",
    "behavior_recommendations": "Continuer sur cette voie"
}

Réponse:
{
    "message": "Report card generated successfully",
    "data": {
        "student_id": 1,
        "term": "T1",
        "general_average": 14.22,
        "class_rank": 5,
        "decision": "pass",
        "is_published": false
    }
}
```

## 🔐 Permissions et Sécurité

### Rôles autorisés

| Rôle | Permissions |
|------|-------------|
| **Professeur** | Créer/modifier ses évaluations, voir ses classes, calculer moyennes de ses élèves |
| **Titulaire** | + Générer les bulletins de sa classe |
| **Admin** | Toutes les permissions |
| **Directeur** | Voir toutes les moyennes et bulletins |

### Vérifications automatiques

1. **CanGrade:** Vérifie que le professeur enseigne dans la classe/matière
2. **Student in Class:** Vérifie que l'élève appartient bien à la classe
3. **Ownership:** Vérifie que le professeur est propriétaire de l'évaluation

## 📈 Workflows typiques

### Workflow 1: Professeur - Saisie des notes

```
1. GET /api/grades/my-classes → Liste des classes
2. GET /api/grades/classes/{id}/students → Liste des élèves
3. POST /api/grades/bulk → Saisie des notes en masse
4. POST /api/grades/{id}/publish → Publication aux élèves
```

### Workflow 2: Titulaire - Calcul et bulletins

```
1. POST /api/grades/classes/{id}/calculate → Calcul moyennes classe
2. GET /api/grades/classes/{id}/averages → Vérification
3. POST /api/grades/students/{id}/report-card → Génération bulletins
4. (Future: Publication aux parents)
```

## 🚀 Mise en place

### 1. Exécuter les migrations

```bash
php artisan migrate
```

### 2. Remplir les données de test (optionnel)

```bash
# Si ce n'est pas déjà fait
php artisan db:seed --class=ClassTeacherRelationshipSeeder

# Données du système de notes
php artisan db:seed --class=GradeSystemSeeder
```

### 3. Vérifier les permissions

Dans `RolesSeeder.php`, le rôle 'teacher' doit avoir:
```php
'permissions' => ['students:read', 'students:write', 'classes:*', 'grades:*']
```

### 4. Tester avec Postman ou cURL

```bash
# Authentification (obtenir token)
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"jean.martin@school.com","password":"password"}'

# Créer une évaluation
curl -X POST http://localhost:8000/api/grades \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{...}'
```

## 📝 Notes Importantes

1. **Publication des notes:** Les évaluations ne sont visibles par les élèves que si `is_published = true`
2. **Calcul des moyennes:** Seules les évaluations publiées sont prises en compte
3. **Rangs:** Calculés automatiquement avec gestion des ex-aequo
4. **Décisions:** Déterminées automatiquement basé sur la moyenne générale et les matières en échec

## Sessions d'évaluation (2026)

### Table `evaluation_sessions`

Organise interros, devoirs et examens séparément. Chaque session a un barème, une date et un type.

- `POST /api/grades/evaluation-sessions` — créer session
- `GET /api/grades/evaluation-sessions` — lister (filtres class/subject/term)
- `POST /api/grades/evaluation-sessions/{id}/grades` — saisie bulk liée
- `POST /api/grades/evaluation-sessions/{id}/publish` — publier
- `GET /api/grades/grid` — grille élèves avec notes existantes

### Conduite (bulletin)

Table `conduct_grades` : appréciation saisie par le titulaire, injectée dans `behavior_recommendations` du bulletin.

- `GET /api/classes/{class}/conduct`
- `POST /api/classes/{class}/conduct/bulk`
