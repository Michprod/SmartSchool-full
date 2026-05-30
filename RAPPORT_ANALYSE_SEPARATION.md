# Rapport d'analyse complet — SmartSchool-full

**Objectif :** Analyse approfondie du projet en vue de sa séparation en modules ou projets distincts.  
**Date :** 24 mai 2026  
**Projet :** SmartSchool-full — Gestion scolaire (contexte RDC)

---

## Table des matières

1. [Synthèse exécutive](#1-synthèse-exécutive)
2. [Nature et périmètre du projet](#2-nature-et-périmètre-du-projet)
3. [Stack technique](#3-stack-technique)
4. [Structure du dépôt](#4-structure-du-dépôt)
5. [Architecture actuelle](#5-architecture-actuelle)
6. [Cartographie des modules métier](#6-cartographie-des-modules-métier)
7. [Schéma de base de données et couplage](#7-schéma-de-base-de-données-et-couplage)
8. [Frontières API](#8-frontières-api)
9. [Frontend React / Inertia](#9-frontend-react--inertia)
10. [Authentification et permissions](#10-authentification-et-permissions)
11. [Code dupliqué et dette technique](#11-code-dupliqué-et-dette-technique)
12. [Déploiement et infrastructure](#12-déploiement-et-infrastructure)
13. [Tests et qualité](#13-tests-et-qualité)
14. [Risques et points critiques](#14-risques-et-points-critiques)
15. [Stratégie de séparation](#15-stratégie-de-séparation)
16. [Plan d'action par phases](#16-plan-daction-par-phases)
17. [Estimation d'effort](#17-estimation-deffort)
18. [Annexes](#18-annexes)

---

## 1. Synthèse exécutive

**SmartSchool-full** est une **application monolithique** de gestion scolaire, construite avec **Laravel 12** (backend) et **React 18 + Inertia.js** (frontend). Ce n'est **pas** un monorepo multi-projets : il s'agit d'**un seul dépôt Git**, **une seule base de données**, et **un seul déploiement** (Hostinger).

| Indicateur | Valeur |
|------------|--------|
| Fichiers source | ~250 |
| Modèles Eloquent | 15 |
| Migrations | 23 |
| Modules métier frontend | 11 |
| Contrôleurs API | 12 |
| Services métier dédiés | 1 (`GradeCalculationService`) |
| Tests PHPUnit | 11 fichiers |

**Conclusion principale :** La séparation est **faisable et déjà partiellement préparée** grâce à l'architecture API-first des données. La stratégie recommandée est **progressive** : d'abord Frontend/Backend, puis extraction des modules autonomes, et enfin microservices si nécessaire.

**Obstacle principal :** Le schéma relationnel unifié (clés étrangères croisées entre élèves, notes, finance et utilisateurs).

---

## 2. Nature et périmètre du projet

### 2.1 Description fonctionnelle

Application complète de gestion d'établissement scolaire couvrant :

- Gestion des élèves et des classes
- Système de notes, moyennes et bulletins
- Finance et paiements (CDF/USD, mobile money, espèces)
- Admissions (workflow candidature → acceptation/rejet)
- Communication (annonces)
- Calendrier d'événements
- Inventaire du matériel scolaire
- Gestion des utilisateurs et rôles (RBAC)
- Tableaux de bord et rapports statistiques
- Paramètres de l'établissement

### 2.2 Type architectural

| Caractéristique | Statut |
|-----------------|--------|
| Monorepo npm/composer | Non |
| Microservices | Non |
| Monolithe modulaire | **Oui** |
| API REST exposée | **Oui** (`/api/*`) |
| SPA découplée | Partiellement (Inertia lie navigation et backend) |

### 2.3 Contexte métier

- Adapté au contexte **République Démocratique du Congo (RDC)**
- Devises : CDF et USD
- Rôles : admin, director, teacher, accountant, secretary, parent
- Documentation métier : `GRADE_SYSTEM.md`, `CLASS_TEACHER_RELATIONSHIPS.md`

---

## 3. Stack technique

### 3.1 Backend

| Technologie | Version / détail |
|-------------|------------------|
| PHP | 8.2+ |
| Laravel | 12 |
| Laravel Sanctum | 4.x — auth API stateful (cookies session) |
| Laravel Breeze | Auth web (login, register, reset password) |
| Inertia Laravel | 2.x — rendu pages React |
| Ziggy | Routes Laravel côté JavaScript |
| Eloquent ORM | 15 modèles |
| PHPUnit | 12 |

### 3.2 Frontend

| Technologie | Version / détail |
|-------------|------------------|
| React | 18.2 |
| TypeScript | Features principales (`.tsx`) |
| JavaScript | Pages Breeze legacy (`.jsx`) |
| Inertia.js React | 2.x |
| Vite | 7.x |
| Axios | Appels REST `/api/*` |
| Tailwind CSS | 3.x/4.x + CSS modules par feature |
| Headless UI | Composants Breeze |

### 3.3 Base de données et infrastructure

| Composant | Développement | Production |
|-----------|---------------|------------|
| Base de données | SQLite | MySQL (Hostinger) |
| Cache | Driver `database` | Driver `database` |
| Sessions | Driver `database` | Driver `database` |
| Queue | Driver `database` (non utilisé métier) | Driver `database` |
| Mail | Driver `log` | Non documenté |
| Fichiers | `local` (photos, PDF bulletins) | `local` |
| Redis | Configuré, non utilisé par défaut | — |
| Docker | **Absent** | — |

### 3.4 CI/CD

| Élément | Détail |
|---------|--------|
| CI | GitHub Actions — tests PHP 8.2/8.3/8.4 (`tests.yml`) |
| Build frontend en CI | **Non** (tests PHP uniquement) |
| Déploiement | Webhook Hostinger + script `migrate_prod.sh` |
| Assets compilés | Versionnés dans `public/build/` |

---

## 4. Structure du dépôt

### 4.1 Arborescence principale

```
SmartSchool-full/
├── app/                          # Backend PHP
│   ├── Http/Controllers/
│   │   ├── Api/                  # 12 contrôleurs API métier
│   │   ├── Auth/                 # Breeze (login, register…)
│   │   └── ProfileController.php
│   ├── Http/Middleware/
│   │   └── CheckPermission.php
│   ├── Models/                   # 15 modèles Eloquent
│   └── Services/
│       └── GradeCalculationService.php
├── bootstrap/
├── config/                       # DB, auth, sanctum, queue, mail…
├── database/
│   ├── migrations/               # 23 migrations
│   ├── seeders/                  # 12 seeders
│   └── factories/
├── public/
│   ├── index.php
│   └── build/                    # Assets Vite compilés (versionnés)
├── resources/
│   ├── js/
│   │   ├── app.tsx               # Point d'entrée Inertia
│   │   ├── bootstrap.js          # Axios + CSRF
│   │   ├── Core/                 # Layout, Sidebar, Header, Pagination, Can
│   │   ├── Features/             # 11 modules métier
│   │   ├── Pages/                # Auth Breeze (legacy JSX)
│   │   └── Components/           # Composants Breeze legacy
│   ├── css/
│   └── views/app.blade.php
├── routes/
│   ├── web.php                   # Routes Inertia (pages)
│   ├── api.php                   # Routes REST JSON
│   └── auth.php                  # Routes Breeze
├── storage/
├── tests/                        # 11 fichiers PHPUnit
├── .github/workflows/
├── composer.json
├── package.json
├── vite.config.js
├── tsconfig.json
├── migrate_prod.sh
├── GRADE_SYSTEM.md
├── CLASS_TEACHER_RELATIONSHIPS.md
└── README.md
```

### 4.2 Fichiers sensibles ou problématiques

| Fichier | Problème |
|---------|----------|
| `tmp_env_prod` | Secrets production potentiellement en clair dans le dépôt |
| `routes/api.php` — `GET /api/fix-admin` | Endpoint sans auth qui reset le mot de passe admin |
| `public/build/` | Assets compilés versionnés (anti-pattern pour CI) |

### 4.3 Absences notables

- Pas de `docker-compose.yml`
- Pas de Kubernetes / Terraform
- Pas de workspace npm/yarn monorepo
- Pas de documentation OpenAPI/Swagger
- Pas de tests frontend (Jest, Vitest, Cypress)

---

## 5. Architecture actuelle

### 5.1 Schéma de flux

```
┌─────────────────────────────────────────────────────────────┐
│                         Navigateur                           │
│                    React (Features/*)                        │
└───────────────┬─────────────────────────┬───────────────────┘
                │                         │
        Navigation (pages)         Données CRUD
                │                         │
                ▼                         ▼
┌───────────────────────┐   ┌───────────────────────────────┐
│   routes/web.php      │   │      routes/api.php           │
│   Inertia::render()   │   │   auth:sanctum + permission   │
│   Blade app.blade.php │   │   Controllers Api/*           │
└───────────────────────┘   └───────────────┬───────────────┘
                                              │
                                              ▼
                              ┌───────────────────────────────┐
                              │     Eloquent Models           │
                              │     GradeCalculationService   │
                              └───────────────┬───────────────┘
                                              │
                                              ▼
                              ┌───────────────────────────────┐
                              │   MySQL / SQLite (unique)     │
                              └───────────────────────────────┘
```

### 5.2 Pattern hybride Inertia + API

| Responsabilité | Technologie | Fichiers clés |
|----------------|-------------|---------------|
| Chargement des pages | Inertia + routes web | `routes/web.php`, `resources/js/app.tsx` |
| Données métier (CRUD) | REST API + Axios | `routes/api.php`, `resources/js/bootstrap.js` |
| Auth | Session cookie + Sanctum stateful | `config/sanctum.php`, Breeze |
| Layout UI | React Core | `resources/js/Core/` |

**Atout pour la séparation :** Les modules consomment déjà l'API pour les données. Le travail principal du split Frontend/Backend porte sur la **navigation** (remplacer Inertia par React Router).

### 5.3 Point d'entrée frontend

Fichier : `resources/js/app.tsx`

- Résolution dynamique des pages via `import.meta.glob` sur `Features/**` et `Pages/**`
- Support `.tsx` et `.jsx`
- Pas de React Router — navigation gérée par Inertia

---

## 6. Cartographie des modules métier

### 6.1 Vue d'ensemble

| # | Module | Route web | Permission | API | Modèle(s) | Autonomie |
|---|--------|-----------|------------|-----|-----------|-----------|
| 1 | Dashboard | `/`, `/dashboard` | auth | `GET /api/reports/stats` | Agrégation | Faible |
| 2 | Students | `/students` | `students:read` | `/api/students`, `/api/classes` | Student, SchoolClass | Moyenne |
| 3 | Grades | `/grades` | `grades:read` | `/api/grades/*` (15+ routes) | Subject, ClassSubject, Assessment, StudentAverage, ReportCard | Faible |
| 4 | Finance | `/finance` | `finance:read` | `/api/payments` | Payment | Moyenne |
| 5 | Admissions | `/admissions` | `admissions:read` | `/api/admissions` | Admission | **Élevée** |
| 6 | Communication | `/communication` | `communication:read` | `/api/announcements` | Announcement | **Élevée** |
| 7 | Events | `/events` | `events:read` | `/api/events` | SchoolEvent | **Très élevée** |
| 8 | Inventory | `/inventory` | `inventory:read` | `/api/inventory` | InventoryItem | **Très élevée** |
| 9 | Users | `/users`, `/profile` | `users:read` | `/api/users`, `/api/roles` | User, Role | Transversal |
| 10 | Reports | `/reports` | `reports:read` | `GET /api/reports/stats` | Cross-module | Faible |
| 11 | Settings | `/settings` | `settings:read` | `/api/settings` | Setting | **Élevée** |

### 6.2 Détail par module

#### 6.2.1 Dashboard

- **Frontend :** `resources/js/Features/Dashboard/Pages/DashboardHome.tsx`
- **Backend :** Agrège via `ReportController::stats`
- **Dépendances :** Students, Users, Admissions, Payments
- **Séparation :** Traiter comme **BFF** (Backend For Frontend), pas comme microservice métier

#### 6.2.2 Students (Élèves & Classes)

- **Frontend :** `Features/Students/` — StudentManagement, StudentForm, StudentDetails, PhotoUpload
- **API :** `StudentController`, `SchoolClassController`
- **Modèles :** `Student`, `SchoolClass`
- **Relations :** `students.class_id` → `school_classes`, `school_classes.teacher_id` → `users`

#### 6.2.3 Grades (Notes & Bulletins) — Module le plus complexe

- **Frontend :** `Features/Grades/Pages/GradesPage.tsx`
- **API :** `GradeController` — 15+ endpoints sous `/api/grades/*`
- **Service :** `GradeCalculationService` (moyennes, rangs)
- **Modèles :** `Subject`, `ClassSubject`, `Assessment`, `StudentAverage`, `ReportCard`
- **Documentation :** `GRADE_SYSTEM.md`
- **Endpoints clés :**
  - CRUD évaluations (`/api/grades/`)
  - Saisie en masse (`/api/grades/bulk`)
  - Calcul moyennes (`/api/grades/students/{id}/calculate`)
  - Génération bulletins (`/api/grades/students/{id}/report-card`)

#### 6.2.4 Finance

- **Frontend :** `Features/Finance/` — FinancialDashboard, PaymentForm, PaymentReceipt
- **API :** `PaymentController`
- **Modèle :** `Payment` (montant, devise CDF/USD, mode mobile money/cash…)
- **FK :** `payments.student_id` → `students`

#### 6.2.5 Admissions

- **Frontend :** `Features/Admissions/` — AdmissionManagement, ApplicationForm
- **API :** `AdmissionController`
- **Modèle :** `Admission` (workflow : submitted → accepted/rejected)
- **FK :** `admissions.reviewed_by` → `users`
- **Note :** Classe référencée en string (pas de FK vers `school_classes`)

#### 6.2.6 Communication

- **Frontend :** `Features/Communication/Pages/CommunicationCenter.tsx`
- **API :** `AnnouncementController` (+ `POST announcements/{id}/read`)
- **Modèle :** `Announcement`
- **FK :** `announcements.created_by` → `users`

#### 6.2.7 Events

- **Frontend :** `Features/Events/Pages/EventsPage.tsx`
- **API :** `SchoolEventController`
- **Modèle :** `SchoolEvent`
- **FK :** Aucune — **module autonome**

#### 6.2.8 Inventory

- **Frontend :** `Features/Inventory/Pages/InventoryPage.tsx`
- **API :** `InventoryItemController` (Api)
- **Modèle :** `InventoryItem`
- **Note :** Stub vide `app/Http/Controllers/InventoryItemController.php` (non utilisé)
- **FK :** Aucune — **module autonome**

#### 6.2.9 Users & Roles

- **Frontend :** `Features/Users/` — UserManagement, ProfilePage, PhotoUpload
- **API :** `UserController`, `RoleController`
- **Modèles :** `User`, `Role`
- **Rôle :** Transversal — tous les modules en dépendent pour l'auth et les permissions

#### 6.2.10 Reports

- **Frontend :** `Features/Reports/Pages/ReportsPage.tsx`
- **API :** `ReportController::stats`
- **Couplage :** Agrège Student, User, Admission, Payment
- **Séparation :** Extraire en dernier (service BFF ou agrégateur)

#### 6.2.11 Settings

- **Frontend :** `Features/Settings/` — SettingsPage, ProfileManagement
- **API :** `SettingController` (clé JSON `school_settings`)
- **Modèle :** `Setting` (KV JSON)
- **FK :** Aucune — **module autonome**

### 6.3 Core UI partagé

Emplacement : `resources/js/Core/`

| Composant | Fichier | Usage |
|-----------|---------|-------|
| Layout principal | `Layouts/DashboardLayout.tsx` | Toutes les pages features |
| Navigation | `Components/Sidebar.tsx` | Menu + filtrage par permissions |
| En-tête | `Components/Header.tsx` | Barre supérieure |
| Pagination | `Components/Pagination.tsx` | Listes paginées |
| Contrôle d'accès UI | `Components/Can.tsx` | Affichage conditionnel RBAC |

---

## 7. Schéma de base de données et couplage

### 7.1 Tables métier

| Table | Module | Relations principales |
|-------|--------|----------------------|
| `roles` | Auth | JSON permissions, slug unique |
| `users` | Auth | `role` (slug, pas FK), relations multiples |
| `school_classes` | Académique | FK `teacher_id` → users |
| `students` | Académique | FK `class_id` → school_classes |
| `class_teacher` | Académique | Pivot legacy class ↔ teacher (matière en string) |
| `subjects` | Notes | Matières normalisées |
| `class_subject` | Notes | Pivot class ↔ subject ↔ teacher + coefficient |
| `assessments` | Notes | student, subject, teacher, class |
| `student_averages` | Notes | Moyennes calculées par trimestre |
| `report_cards` | Notes | Bulletins PDF, décisions conseil |
| `payments` | Finance | FK `student_id` → students |
| `admissions` | Admissions | FK `reviewed_by` → users |
| `announcements` | Communication | FK `created_by` → users |
| `school_events` | Événements | Autonome |
| `inventory_items` | Inventaire | Autonome |
| `settings` | Config | KV JSON (`school_settings`) |

### 7.2 Tables framework Laravel

- `sessions`, `cache`, `cache_locks`
- `jobs`, `job_batches`, `failed_jobs`
- `personal_access_tokens` (Sanctum)
- `password_reset_tokens`
- `migrations`

### 7.3 Graphe de dépendances (FK)

```
users
  ├── school_classes.teacher_id
  ├── class_teacher (pivot legacy)
  ├── class_subject.teacher_id
  ├── assessments.teacher_id
  ├── report_cards.generated_by / validated_by
  ├── announcements.created_by
  └── admissions.reviewed_by

school_classes
  ├── students.class_id
  ├── assessments, student_averages, report_cards
  └── class_subject

students
  ├── payments.student_id
  ├── assessments, student_averages, report_cards
  └── (cible principale du couplage)

subjects
  ├── class_subject
  ├── assessments
  └── student_averages
```

### 7.4 Matrice de couplage pour la séparation

| Module A | Dépend de | Type de lien | Difficulté extraction |
|----------|-----------|--------------|----------------------|
| Grades | Students, Classes, Users, Subjects | FK directes | Difficile |
| Finance | Students | FK directe | Moyenne |
| Dashboard/Reports | Tous | Agrégation applicative | Très difficile |
| Students/Classes | Users (professeur) | FK directe | Moyenne |
| Admissions | Users (reviewer) | FK faible | Facile |
| Communication | Users (auteur) | FK faible | Facile |
| Events | — | Aucun | Très facile |
| Inventory | — | Aucun | Très facile |
| Settings | — | Aucun | Très facile |

### 7.5 Problème de modèle en double (enseignants)

Deux pivots coexistent :

| Pivot | Table | Matière | Statut |
|-------|-------|---------|--------|
| Legacy | `class_teacher` | String libre | Ancien système |
| Normalisé | `class_subject` | FK → `subjects` | Système notes actuel |

**Action requise avant séparation Grades/Academic :** Unifier vers `class_subject` uniquement. Voir `CLASS_TEACHER_RELATIONSHIPS.md` et `GRADE_SYSTEM.md`.

### 7.6 Seeders

**Appelés par `DatabaseSeeder` :**
- UsersSeeder, SchoolClassesSeeder, StudentsSeeder
- PaymentsSeeder, AdmissionsSeeder, EventsSeeder
- AnnouncementsSeeder, InventorySeeder

**Existants mais NON appelés :**
- `RolesSeeder` — **permissions prod potentiellement incohérentes**
- `GradeSystemSeeder`
- `ClassTeacherRelationshipSeeder`

---

## 8. Frontières API

### 8.1 Routes web (Inertia) — `routes/web.php`

| Route | Page Inertia | Middleware |
|-------|--------------|------------|
| `/`, `/dashboard` | `Features/Dashboard/Pages/DashboardHome` | auth, verified |
| `/students` | `Features/Students/Pages/StudentManagement` | auth, permission:students:read |
| `/finance` | `Features/Finance/Pages/FinancialDashboard` | auth, permission:finance:read |
| `/communication` | `Features/Communication/Pages/CommunicationCenter` | auth, permission:communication:read |
| `/events` | `Features/Events/Pages/EventsPage` | auth, permission:events:read |
| `/inventory` | `Features/Inventory/Pages/InventoryPage` | auth, permission:inventory:read |
| `/users` | `Features/Users/Pages/UserManagement` | auth, permission:users:read |
| `/admissions` | `Features/Admissions/Pages/AdmissionManagement` | auth, permission:admissions:read |
| `/grades` | `Features/Grades/Pages/GradesPage` | auth, permission:grades:read |
| `/reports` | `Features/Reports/Pages/ReportsPage` | auth, permission:reports:read |
| `/settings` | `Features/Settings/Pages/SettingsPage` | auth, permission:settings:read |
| `/profile` | `Features/Users/Pages/ProfilePage` | auth |

### 8.2 Routes API — `routes/api.php`

**Groupe protégé :** `middleware('auth:sanctum')`

| Ressource | Méthodes | Permission |
|-----------|----------|------------|
| `GET /api/user` | Utilisateur courant | auth |
| `/api/students` | apiResource | students:read |
| `/api/classes` | apiResource | students:read |
| `/api/subjects` | apiResource | — |
| `/api/payments` | apiResource | finance:read |
| `/api/announcements` | apiResource + markRead | — |
| `/api/events` | apiResource | — |
| `/api/inventory` | apiResource | inventory:read |
| `/api/admissions` | apiResource | admissions:read |
| `/api/users` | apiResource | — |
| `/api/roles` | apiResource | users:read |
| `GET /api/reports/stats` | Statistiques agrégées | implicite |
| `GET/POST /api/settings` | Paramètres école | settings:read/write |
| `/api/grades/*` | 15+ endpoints | grades:* |

### 8.3 Endpoints Grades détaillés

| Méthode | Route | Action |
|---------|-------|--------|
| GET | `/api/grades/my-classes` | Classes du professeur |
| GET | `/api/grades/classes/{classId}/students` | Élèves d'une classe |
| GET/POST | `/api/grades/` | Liste / création évaluations |
| POST | `/api/grades/bulk` | Saisie en masse |
| GET/PUT/DELETE | `/api/grades/{id}` | CRUD évaluation |
| POST | `/api/grades/{id}/publish` | Publication |
| POST | `/api/grades/students/{studentId}/calculate` | Calcul moyennes élève |
| POST | `/api/grades/classes/{classId}/calculate` | Calcul moyennes classe |
| GET | `/api/grades/students/{studentId}/averages` | Moyennes élève |
| GET | `/api/grades/classes/{classId}/averages` | Moyennes classe |
| POST/GET | `/api/grades/students/{studentId}/report-card` | Génération / consultation bulletin |

### 8.4 Endpoint critique (sécurité)

```
GET /api/fix-admin
```

- **Sans authentification**
- Crée ou reset l'utilisateur `admin@example.com` avec mot de passe `password`
- **À supprimer immédiatement** avant toute mise en production ou séparation

### 8.5 Communication client

- Axios configuré dans `resources/js/bootstrap.js`
- `withCredentials: true`, `withXSRFToken: true`
- Pas de WebSocket, SSE, GraphQL
- Pas de jobs/queues métier implémentés

---

## 9. Frontend React / Inertia

### 9.1 Organisation des Features

```
resources/js/Features/
├── Admissions/
│   ├── Components/ApplicationForm.tsx
│   ├── Pages/AdmissionManagement.tsx
│   └── types.ts
├── Communication/
├── Dashboard/
├── Events/
├── Finance/
├── Grades/
├── Inventory/
├── Reports/
├── Settings/
├── Students/
└── Users/
```

Chaque feature contient typiquement :
- `Pages/` — page principale + CSS
- `Components/` — formulaires, détails
- `types.ts` — interfaces TypeScript locales

### 9.2 Imports et couplage frontend

- Toutes les features importent depuis `Core/` via chemins relatifs (`../../../Core/...`)
- Alias TypeScript `@/*` → `resources/js/*` configuré mais **peu utilisé**
- Pas de package npm interne (`@smartschool/ui`)
- Types TS **dupliqués par feature**, non mutualisés

### 9.3 Dualité JSX / TSX

| Zone | Format | Framework |
|------|--------|-----------|
| `Features/*` | TypeScript `.tsx` | App métier moderne |
| `Pages/Auth/*`, `Pages/Profile/*` | JavaScript `.jsx` | Laravel Breeze legacy |
| `Components/*` | JavaScript `.jsx` | Breeze (boutons, inputs) |

**Impact séparation :** Fusionner ou isoler Breeze dans le futur SPA.

---

## 10. Authentification et permissions

### 10.1 Flux d'authentification

1. Login via Breeze → cookie de session
2. Pages Inertia chargées avec `auth.user` partagé via `HandleInertiaRequests`
3. Appels API utilisent la même session Sanctum stateful (cookies + CSRF)

### 10.2 Rôles

| Rôle | Description |
|------|-------------|
| admin | Accès complet |
| director | Direction |
| teacher | Enseignant |
| accountant | Comptable |
| secretary | Secrétaire |
| parent | Parent d'élève |

### 10.3 Format des permissions

Format : `resource:action` (ex. `students:read`, `finance:*`, `grades:write`)

### 10.4 Implémentation RBAC (dupliquée)

| Couche | Fichier |
|--------|---------|
| Backend middleware | `app/Http/Middleware/CheckPermission.php` |
| Backend modèle | `app/Models/User.php` — `hasPermission()` |
| Frontend composant | `resources/js/Core/Components/Can.tsx` |
| Frontend navigation | `resources/js/Core/Components/Sidebar.tsx` |

**Recommandation :** Centraliser en librairie `@smartschool/permissions` avant le split.

---

## 11. Code dupliqué et dette technique

### 11.1 Duplications identifiées

| Élément | Emplacements | Action |
|---------|--------------|--------|
| `PhotoUpload.tsx` | `Features/Students/`, `Features/Users/` | Extraire en `@smartschool/ui` |
| `PhotoUpload.css` | 2 copies | Idem |
| Logique permissions | 4 fichiers (voir §10.4) | Package `@smartschool/permissions` |
| Layouts auth | Breeze JSX vs `DashboardLayout.tsx` | Fusionner dans le SPA |
| `InventoryItemController` | Stub web vide + API complète | Supprimer le stub |
| Pivots enseignant | `class_teacher` vs `class_subject` | Unifier le modèle |
| Types TypeScript | Par feature | Package `@smartschool/types` |

### 11.2 Services métier

Un seul service extrait : `app/Services/GradeCalculationService.php`

Les autres domaines ont la logique dans les contrôleurs — à extraire lors de la restructuration `app/Domains/`.

---

## 12. Déploiement et infrastructure

### 12.1 Environnement de production

| Élément | Détail |
|---------|--------|
| Hébergeur | Hostinger |
| Domaine | `plum-gerbil-537001.hostingersite.com` |
| Chemin serveur | `public_html/backend` (d'après `migrate_prod.sh`) |
| Base de données | MySQL locale au serveur |
| Build frontend | Assets pré-compilés dans `public/build/` |
| Migration | `migrate_prod.sh` + `db:seed --force` |
| Auto-deploy | Webhook Hostinger sur push Git |

### 12.2 Environnement de développement

```bash
composer dev
# Lance en parallèle : serve, queue, pail, vite
```

### 12.3 Manques infrastructure

- Pas de conteneurisation (Docker)
- Pas d'environnement staging documenté
- Pas de secrets manager
- Queue worker non documenté en production
- Pas de build npm dans la CI

---

## 13. Tests et qualité

### 13.1 Couverture actuelle

| Type | Fichiers | Couverture |
|------|----------|------------|
| PHPUnit Feature | Auth, permissions, exemple | Partielle |
| PHPUnit Unit | Minimal | Faible |
| Tests frontend | Aucun | 0 % |
| Tests contractuels API | Aucun | 0 % |

### 13.2 CI

- Workflow : `.github/workflows/tests.yml`
- PHP 8.2, 8.3, 8.4
- SQLite in-memory
- **Pas de build Vite ni tests JS**

### 13.3 Recommandations pour la séparation

- Tests d'intégration API par module avant extraction
- Tests contractuels (Pact ou équivalent) entre services
- Tests E2E sur le SPA découpé (Playwright/Cypress)

---

## 14. Risques et points critiques

| # | Risque | Sévérité | Mitigation |
|---|--------|----------|------------|
| 1 | Base de données unique avec FK croisées | Élevée | Events inter-services, IDs externes, BFF |
| 2 | Endpoint `/api/fix-admin` exposé | Critique | Supprimer immédiatement |
| 3 | `tmp_env_prod` avec secrets en clair | Critique | Retirer du repo, rotater credentials |
| 4 | Permissions dupliquées à 4 endroits | Moyenne | Package partagé avant split |
| 5 | `RolesSeeder` non appelé | Moyenne | Intégrer dans `DatabaseSeeder` |
| 6 | Modèle enseignant double (`class_teacher` / `class_subject`) | Moyenne | Unifier avant split Grades |
| 7 | `ReportController` cross-module | Moyenne | Extraire en BFF en dernier |
| 8 | Pas de Docker / orchestration | Moyenne | Ajouter pour microservices |
| 9 | Assets versionnés dans `public/build/` | Faible | CI build + deploy |
| 10 | Tests insuffisants | Moyenne | Renforcer avant chaque extraction |

---

## 15. Stratégie de séparation

### 15.1 Options envisageables

#### Option A — Split Frontend / Backend (recommandée en premier)

| Projet | Contenu |
|--------|---------|
| `smartschool-api` | Laravel sans Inertia, API REST pure, Sanctum |
| `smartschool-web` | SPA React (Vite), React Router, consomme `/api/*` |

**Avantages :** Faible risque, modules déjà API-first  
**Effort :** 2–4 semaines

#### Option B — Monolithe modulaire Laravel

Restructurer `app/` sans changer le déploiement :

```
app/Domains/
├── Auth/
├── Students/
├── Grades/
├── Finance/
├── Admissions/
├── Communication/
├── Events/
├── Inventory/
├── Reports/
└── Settings/
```

**Avantages :** Prépare les microservices, clarifie le code  
**Effort :** 1–2 semaines

#### Option C — Microservices par domaine (long terme)

```
                    ┌─────────────────────┐
                    │   API Gateway / BFF  │
                    └──────────┬──────────┘
         ┌──────────┼──────────┼──────────┼──────────┐
         ▼          ▼          ▼          ▼          ▼
    ┌────────┐ ┌─────────┐ ┌────────┐ ┌────────┐ ┌────────┐
    │  Auth  │ │Academic │ │ Grades │ │Finance │ │  Ops   │
    │  IAM   │ │Students │ │ Notes  │ │Payments│ │Events  │
    │  RBAC  │ │Classes  │ │Bulletins│        │ │Inventory│
    └────────┘ └─────────┘ └────────┘ └────────┘ │Comm    │
                                                  └────────┘
```

**Effort :** 3–6 mois

### 15.2 Ordre d'extraction des modules

| Priorité | Module | Raison |
|----------|--------|--------|
| 1 | Packages partagés (UI, permissions, types) | Réduit la duplication |
| 2 | Frontend SPA séparé | Débloque le découplage |
| 3 | Inventory | 0 FK, autonome |
| 4 | Events | 0 FK, autonome |
| 5 | Settings | 0 FK, autonome |
| 6 | Communication | FK users uniquement |
| 7 | Admissions | Workflow indépendant |
| 8 | Finance | FK students → event-driven |
| 9 | Academic (Students + Classes) | Cœur métier |
| 10 | Grades | Fort couplage avec Academic |
| 11 | Auth/IAM | Service central OAuth2/OIDC |
| 12 | Reporting/BFF | Agrégation cross-service |

### 15.3 Matrice de décision

| Question | Réponse SmartSchool |
|----------|---------------------|
| Monorepo ou multi-repo ? | Multi-repo après Phase 1 (api + web) |
| DB partagée ou par service ? | Partagée en Phase 1–2, par service ensuite |
| Auth centralisée ? | Oui — service IAM unique |
| Communication inter-services ? | Events (ex. admission acceptée → créer élève) |
| Reporting ? | BFF agrégateur, pas microservice métier |
| Frontend ? | SPA unique avec lazy-loading par feature |

---

## 16. Plan d'action par phases

### Phase 0 — Prérequis (1 semaine)

- [ ] Supprimer `GET /api/fix-admin`
- [ ] Retirer `tmp_env_prod` du dépôt et rotater les secrets
- [ ] Intégrer `RolesSeeder` dans `DatabaseSeeder`
- [ ] Unifier `class_teacher` → `class_subject`
- [ ] Extraire `PhotoUpload`, `Can`, `Pagination` en package interne
- [ ] Documenter l'API (OpenAPI/Swagger)

### Phase 1 — Split Frontend / Backend (2–4 semaines)

- [ ] Créer dépôt `smartschool-api` (Laravel sans Inertia)
- [ ] Créer dépôt `smartschool-web` (React + React Router + Vite)
- [ ] Migrer `resources/js/Features/` et `Core/` vers le frontend
- [ ] Configurer CORS + Sanctum SPA
- [ ] Remplacer routes Inertia par router client
- [ ] CI séparée : tests PHP + build Vite
- [ ] Ne plus versionner `public/build/` dans l'API

### Phase 2 — Monolithe modulaire (1–2 semaines)

- [ ] Restructurer `app/Domains/` par module
- [ ] Extraire logique métier des contrôleurs vers Services
- [ ] Policies Laravel par domaine
- [ ] Tests d'intégration par domaine

### Phase 3 — Extraction modules autonomes (itérative)

- [ ] Inventory → microservice ou module npm/backend isolé
- [ ] Events → idem
- [ ] Settings → idem
- [ ] Communication, Admissions
- [ ] Finance (découpler FK via `student_external_id` + events)
- [ ] Academic + Grades
- [ ] Auth/IAM centralisé
- [ ] BFF Reporting

### Phase 4 — Infrastructure microservices (si requis)

- [ ] Docker Compose pour dev local multi-services
- [ ] Message broker (RabbitMQ, Redis Streams)
- [ ] API Gateway
- [ ] Observabilité (logs centralisés, traces)
- [ ] Environnement staging

---

## 17. Estimation d'effort

| Scénario | Durée estimée | Risque | Valeur |
|----------|---------------|--------|--------|
| Phase 0 — Nettoyage | 1 semaine | Faible | Prérequis indispensable |
| Option A — Frontend/Backend | 2–4 semaines | Faible | **Haute** — débloque tout |
| Option B — Domains/ | 1–2 semaines | Très faible | Moyenne — clarifie le code |
| Extraction 3 modules autonomes | 3–5 semaines | Moyen | Proof of concept |
| Microservices complets | 3–6 mois | Élevé | Haute — scalabilité, équipes parallèles |

---

## 18. Annexes

### Annexe A — Liste des modèles Eloquent

1. `User`
2. `Role`
3. `Student`
4. `SchoolClass`
5. `Subject`
6. `ClassSubject`
7. `Assessment`
8. `StudentAverage`
9. `ReportCard`
10. `Payment`
11. `Admission`
12. `Announcement`
13. `SchoolEvent`
14. `InventoryItem`
15. `Setting`

### Annexe B — Liste des contrôleurs API

1. `StudentController`
2. `SchoolClassController`
3. `SubjectController`
4. `PaymentController`
5. `AnnouncementController`
6. `SchoolEventController`
7. `InventoryItemController`
8. `AdmissionController`
9. `UserController`
10. `RoleController`
11. `ReportController`
12. `GradeController`

### Annexe C — Permissions par ressource

| Ressource | Permissions typiques |
|-----------|---------------------|
| students | read, write, * |
| finance | read, write, * |
| grades | read, write, * |
| admissions | read, write, * |
| communication | read, write, * |
| events | read, write, * |
| inventory | read, write, * |
| users | read, write, * |
| reports | read |
| settings | read, write |

### Annexe D — Documentation projet existante

| Fichier | Sujet |
|---------|-------|
| `GRADE_SYSTEM.md` | Système de notes, calculs, bulletins |
| `CLASS_TEACHER_RELATIONSHIPS.md` | Relations enseignant-classe |
| `PROFILE_*.md` | Notes développement profils |
| `USER_PERMISSION_FIXES.md` | Corrections permissions |
| `CHANGELOG.md` | Changelog Laravel |

### Annexe E — Commandes utiles

```bash
# Développement local
composer dev

# Setup initial
composer setup

# Tests
composer test

# Build frontend
npm run build

# Migration production (Hostinger)
./migrate_prod.sh
```

### Annexe F — Structure cible après séparation (Phase 1)

```
smartschool-api/                    smartschool-web/
├── app/                            ├── src/
│   ├── Domains/                    │   ├── features/
│   │   ├── Students/               │   │   ├── students/
│   │   ├── Grades/                 │   │   ├── grades/
│   │   └── ...                     │   │   └── ...
│   └── Http/Controllers/Api/       │   ├── core/
├── routes/api.php                  │   │   ├── layouts/
├── database/                       │   │   └── components/
├── config/                         │   ├── router/
├── composer.json                   │   └── main.tsx
└── .env.example                    ├── package.json
                                    └── vite.config.ts
```

---

## Conclusion

SmartSchool-full est un **monolithe modulaire bien structuré** pour une première séparation Frontend/Backend. L'API REST existante et l'organisation en `Features/` constituent des fondations solides.

La séparation complète en microservices nécessitera en plus :
- Une **unification du modèle de données** (enseignants, permissions)
- Une **infrastructure conteneurisée**
- Un **service IAM central** et un **BFF** pour le reporting
- Une **stratégie de découplage des FK** (events, IDs externes)

**Prochaine étape recommandée :** Phase 0 (sécurité + nettoyage) puis Phase 1 (création des dépôts `smartschool-api` et `smartschool-web`).

---

*Rapport généré pour le projet SmartSchool-full — Analyse en vue de la séparation modulaire.*
