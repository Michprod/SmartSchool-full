# SmartSchool-full — Guide d'Architecture & de Restructuration Global

**Type :** Prompt-Master & Spécifications Techniques pour l'IA d'Ingénierie Logicielle  
**Version :** 1.1  
**Date :** 24 mai 2026  
**Statut :** Normatif — référentiel de travail pour agents IA et équipe technique  
**Document complémentaire :** [`RAPPORT_ANALYSE_SEPARATION.md`](./RAPPORT_ANALYSE_SEPARATION.md)

---

## Comment utiliser ce document (agents IA)

Ce fichier est conçu pour être **injecté tel quel** dans le contexte d'un LLM, Copilot Agent ou orchestrateur de développement. Il décrit :

1. L'**état actuel** du monolithe (faits vérifiables dans le code)
2. La **vision cible** (découplage Frontend/Backend, RBAC normalisé, interconnexion logique)
3. Les **directives impératives** (sécurité, ordre d'exécution, interdictions)
4. Les **artefacts de code** attendus (middleware, migrations, flux auth, événements)

### Règles impératives pour l'IA

| Priorité | Règle |
|----------|-------|
| P0 | Ne jamais laisser `GET /api/fix-admin` en production |
| P0 | Ne jamais committer de secrets (`.env`, `tmp_env_prod`) |
| P1 | Traiter la Phase 0 (sécurité + unification) **avant** tout split de dépôt |
| P1 | Conserver la compatibilité des permissions `resource:action` et wildcards (`students:*`, `*`) |
| P2 | Minimiser le scope de chaque PR — une phase, un objectif |
| P2 | Suivre les conventions existantes Laravel 12 + React Features/ |
| P3 | Documenter chaque migration de schéma avec rollback testé |

### Interdictions explicites

- Ne pas introduire de microservices avant le split Frontend/Backend validé
- Ne pas supprimer `class_teacher` tant que les relations `class_subject` ne sont pas migrées et testées
- Ne pas remplacer Sanctum par JWT sans décision explicite de l'équipe
- Ne pas dupliquer la logique RBAC — une seule source de vérité backend (`User::hasPermission` → relations normalisées)

---

## Table des matières

1. [Fiche d'identité actuelle (monolithe)](#1-fiche-didentité-actuelle-monolithe)
2. [Actions immédiates de sécurisation](#2-actions-immédiates-de-sécurisation)
3. [Vision cible : découplage Frontend / Backend](#3-vision-cible--découplage-frontend--backend)
4. [RBAC normalisé (remplacement du modèle JSON)](#4-rbac-normalisé-remplacement-du-modèle-json)
5. [Interconnexion inter-modules par identifiants logiques](#5-interconnexion-inter-modules-par-identifiants-logiques)
6. [Reporting découplé (CQRS + BFF)](#6-reporting-découplé-cqrs--bff)
7. [Prérequis de rentrée académique et nettoyage](#7-prérequis-de-rentrée-académique-et-nettoyage)
8. [Plan d'exécution par phases](#8-plan-dexécution-par-phases)
9. [Arborescence cible des dépôts](#9-arborescence-cible-des-dépôts)
10. [Contrats API et formats de réponse](#10-contrats-api-et-formats-de-réponse)
11. [Catalogue d'événements métier](#11-catalogue-dévénements-métier)
12. [Annexes techniques](#12-annexes-techniques)

---

## 1. Fiche d'identité actuelle (monolithe)

### 1.1 Description

SmartSchool-full est un **monolithe hybride** couvrant l'intégralité d'un système de gestion scolaire adapté au contexte **République Démocratique du Congo (RDC)**.

| Couche | Technologie | Détail |
|--------|-------------|--------|
| Backend | Laravel 12, PHP 8.2+ | Eloquent ORM, Sanctum, Breeze |
| Frontend métier | React 18.2, TypeScript | `resources/js/Features/*` |
| Frontend auth legacy | React, JavaScript | `resources/js/Pages/Auth/*` (Breeze) |
| Pont navigation | Inertia.js 2.x | `routes/web.php` → pages React |
| Données async | Axios | `GET/POST /api/*` depuis le client |
| Base de données | SQLite (dev) / MySQL (prod) | Une seule base, ~20 tables métier |
| Hébergement | Hostinger | Webhook deploy, `migrate_prod.sh` |

### 1.2 Architecture actuelle (schéma)

```
Navigateur
    │
    ├── Inertia (web.php) ──► Shell React (navigation)
    │
    └── Axios (/api/*) ──► Sanctum + CheckPermission ──► Controllers Api/* eslint► MySQL
```

**Constat clé :** Les modules consomment déjà l'API REST pour les données CRUD. Inertia ne sert qu'au **routing de pages**. Le découplage SPA est donc **partiellement accompli**.

### 1.3 État actuel du RBAC (à remplacer)

| Élément | Implémentation actuelle | Problème |
|---------|-------------------------|----------|
| Rôle utilisateur | Colonne `users.role` (string slug) | Pas de FK, un seul rôle par user |
| Permissions rôle | JSON dans `roles.permissions` | Non normalisé, pas de jointures |
| Permissions user | JSON dans `users.permissions` | Duplication, difficile à auditer |
| Vérification | `User::hasPermission()` + `CheckPermission` middleware | Logique dupliquée côté frontend (`Can.tsx`, `Sidebar.tsx`) |
| Seeders | `RolesSeeder` existe mais **non appelé** par `DatabaseSeeder` | Incohérence prod/dev |

**Fichiers concernés :**
- `app/Models/User.php` — lignes 92–136 (`hasPermission`)
- `app/Models/Role.php` — cast JSON `permissions`
- `app/Http/Middleware/CheckPermission.php`
- `resources/js/Core/Components/Can.tsx`

### 1.4 État actuel des relations enseignant (à unifier)

| Pivot | Table | Usage | Statut |
|-------|-------|-------|--------|
| Legacy | `class_teacher` | Matière en string, relation `User::teachingClasses()` | **Obsolète** |
| Normalisé | `class_subject` | FK `subject_id`, `teacher_id`, coefficient | **Cible** |

---

## 2. Actions immédiates de sécurisation

> **CRITIQUE — P0 :** L'IA doit traiter ces points **avant toute refonte de code**.

### 2.1 Suppression de la backdoor d'administration

**Fichier :** `routes/api.php` (lignes 4–17)

```php
// À SUPPRIMER INTÉGRALEMENT
Route::get('/fix-admin', function () { ... });
```

**Action :**
1. Supprimer la route
2. Vérifier qu'aucune variante n'existe ailleurs (`grep fix-admin`)
3. Rotater le mot de passe de tout compte `admin@example.com` en production

### 2.2 Purge des secrets

**Fichier :** `tmp_env_prod` (présent à la racine)

**Action :**
1. Retirer du suivi Git : `git rm --cached tmp_env_prod`
2. Ajouter à `.gitignore` : `tmp_env_prod`, `*.env.prod`
3. Invalider et regénérer : `APP_KEY`, mots de passe DB, tokens Sanctum
4. Utiliser exclusivement les variables d'environnement Hostinger (panel ou CI secrets)

### 2.3 Durcissement Sanctum (préparation SPA)

**Fichier :** `config/sanctum.php`

```env
# .env (API)
SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:3000,app.smartschool.cd
SESSION_DOMAIN=.smartschool.cd
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

**Fichier :** `config/cors.php` (à publier si absent)

```php
'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
'supports_credentials' => true,
```

---

## 3. Vision cible : découplage Frontend / Backend

Le projet doit être scindé en **deux dépôts autonomes**. Le couplage Inertia doit être **intégralement démonté**.

### 3.1 Backend — `smartschool-api` (API REST stateful)

| Directive | Détail |
|-----------|--------|
| Rôle | Serveur Laravel **API pure** — aucun rendu HTML/React |
| Auth | Sanctum **Stateful Browser Authentication** (cookies session + CSRF) |
| Retrait | `inertiajs/inertia-laravel`, `HandleInertiaRequests`, `routes/web.php` (pages métier), Blade `app.blade.php` |
| Conservation | `routes/api.php`, Breeze auth routes (`login`, `logout`, `register`) via `routes/auth.php` adaptées JSON |
| Réponses | JSON strict via **API Resources** Laravel |
| CORS | Origine frontend explicitement autorisée |

**Routes auth minimales à conserver/exposer :**

| Méthode | Route | Rôle |
|---------|-------|------|
| GET | `/sanctum/csrf-cookie` | Initialisation CSRF |
| POST | `/login` | Authentification |
| POST | `/logout` | Déconnexion |
| GET | `/api/user` | Hydratation user + permissions |

**Middleware `bootstrap/app.php` cible :**

```php
$middleware->api(prepend: [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
]);

$middleware->alias([
    'permission' => \App\Http\Middleware\CheckPermission::class,
]);

// SUPPRIMER du groupe web :
// HandleInertiaRequests, AddLinkHeadersForPreloadedAssets
```

### 3.2 Frontend — `smartschool-web` (SPA React / Vite)

| Directive | Détail |
|-----------|--------|
| Build | Vite 7.x, point d'entrée `src/main.tsx` |
| Routing | `react-router-dom` v6+ — remplace Inertia |
| HTTP | Axios avec `withCredentials: true`, `withXSRFToken: true` |
| Auth | Context React `AuthProvider` — hydratation via `GET /api/user` |
| Structure | Migrer `resources/js/Features/` → `src/features/`, `Core/` → `src/core/` |
| Env | `VITE_API_URL=https://api.smartschool.cd` |

### 3.3 Cycle d'authentification SPA (normatif)

```
┌─────────────┐     GET /sanctum/csrf-cookie      ┌─────────────┐
│  Axios SPA  │ ─────────────────────────────────► │  API Laravel │
└─────────────┘                                     └─────────────┘
       │
       │     POST /login { email, password }
       ▼
┌─────────────┐     Set-Cookie: session + XSRF    ┌─────────────┐
│  Axios SPA  │ ◄───────────────────────────────── │  API Laravel │
└─────────────┘                                     └─────────────┘
       │
       │     GET /api/user  (permissions incluses)
       ▼
┌─────────────┐
│ AuthProvider│ ──► Routes protégées + composant <Can permission="...">
└─────────────┘
```

**Code client normatif (`src/core/api/client.ts`) :**

```typescript
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
});

export async function initSession(): Promise<void> {
  await api.get('/sanctum/csrf-cookie');
}

export async function login(email: string, password: string) {
  await initSession();
  await api.post('/login', { email, password });
  return api.get('/api/user');
}

export async function logout() {
  await api.post('/logout');
}

export default api;
```

**Routeur SPA normatif (`src/router/index.tsx`) :**

```typescript
// Routes miroir de l'actuel web.php
/                    → DashboardHome        (auth)
/students            → StudentManagement    (permission: students:read)
/finance             → FinancialDashboard   (permission: finance:read)
/grades              → GradesPage           (permission: grades:read)
/admissions          → AdmissionManagement  (permission: admissions:read)
/communication       → CommunicationCenter  (permission: communication:read)
/events              → EventsPage           (permission: events:read)
/inventory           → InventoryPage        (permission: inventory:read)
/users               → UserManagement       (permission: users:read)
/reports             → ReportsPage          (permission: reports:read)
/settings            → SettingsPage         (permission: settings:read)
/profile             → ProfilePage          (auth)
/login               → LoginPage            (guest)
```

---

## 4. RBAC normalisé (remplacement du modèle JSON)

### 4.1 Objectif

Remplacer le stockage JSON des permissions et la colonne `users.role` (string) par un **RBAC relationnel normalisé**, conforme aux bonnes pratiques ANSI/ISO (modèle NIST RBAC : Users ↔ Roles ↔ Permissions).

> **Note de migration :** Le projet utilise actuellement des `id` auto-incrémentés (`bigint`). La migration vers **UUID** est recommandée à moyen terme pour l'isolation inter-services, mais peut être **phase 2**. La Phase 1 peut conserver `bigint` pour les tables RBAC.

### 4.2 Modèle physique cible (MPD)

#### Phase 1 — RBAC normalisé (bigint, migration immédiate)

| Table | Clé | Champs critiques | Contraintes |
|-------|-----|------------------|-------------|
| `users` | `id` bigint PK | `first_name`, `last_name`, `email` unique, `is_active` | InnoDB |
| `roles` | `id` bigint PK | `name`, `slug` unique, `description` | — |
| `permissions` | `id` bigint PK | `name` unique (`resource:action`) | Index sur `name` |
| `permission_role` | `(role_id, permission_id)` PK | FK → roles, permissions | ON DELETE CASCADE |
| `role_user` | `(user_id, role_id)` PK | FK → users, roles | ON DELETE CASCADE |

**Migration SQL conceptuelle :**

```php
// database/migrations/xxxx_create_rbac_normalized_tables.php

Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique(); // ex: students:read, finance:*
    $table->string('resource');       // ex: students
    $table->string('action');         // ex: read, *, write
    $table->timestamps();
});

Schema::create('permission_role', function (Blueprint $table) {
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
    $table->primary(['role_id', 'permission_id']);
});

Schema::create('role_user', function (Blueprint $table) {
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->primary(['user_id', 'role_id']);
});
```

#### Phase 2 — UUID (optionnelle, préparation microservices)

| Table | Clé | Note |
|-------|-----|------|
| `users` | `uuid` PK | Colonne `id` bigint conservée temporairement pour compatibilité |
| Toutes entités exposées inter-services | `uuid` unique | Colonne `{entity}_external_id` dans les modules consommateurs |

### 4.3 Migration des données existantes

**Script de migration des permissions JSON → table `permissions` :**

```php
// database/seeders/MigrateJsonPermissionsSeeder.php

foreach (Role::all() as $role) {
    foreach ($role->permissions ?? [] as $permName) {
        [$resource, $action] = array_pad(explode(':', $permName, 2), 2, '*');
        $permission = Permission::firstOrCreate(
            ['name' => $permName],
            ['resource' => $resource, 'action' => $action]
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }
}

// Migrer users.role (string) → role_user
foreach (User::all() as $user) {
    $role = Role::where('slug', $user->role)->first();
    if ($role) {
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
```

**Après validation :**
- Supprimer colonne `roles.permissions` (JSON)
- Supprimer colonne `users.permissions` (JSON) — ou conserver pour overrides explicites (permissions directes user)
- Supprimer colonne `users.role` (string) — après migration `role_user`

### 4.4 Modèle Eloquent cible — `User.php`

```php
public function roles()
{
    return $this->belongsToMany(Role::class);
}

public function hasPermission(string $permission): bool
{
    // Super-admin outrepasse tout
    if ($this->roles()->where('slug', 'admin')->exists()) {
        return true;
    }

    // Permission exacte
    if ($this->roles()->whereHas('permissions', fn ($q) => $q->where('name', $permission))->exists()) {
        return true;
    }

    // Wildcard resource (students:* couvre students:read)
    [$resource] = explode(':', $permission, 2);
    if ($this->roles()->whereHas('permissions', fn ($q) => $q->where('name', "{$resource}:*"))->exists()) {
        return true;
    }

    // Permissions directes user (override optionnel)
    return $this->directPermissions()->where('name', $permission)->exists();
}

public function getAllPermissionsAttribute(): array
{
    if ($this->roles()->where('slug', 'admin')->exists()) {
        return ['*'];
    }
    return $this->roles()
        ->with('permissions')
        ->get()
        ->flatMap(fn ($role) => $role->permissions->pluck('name'))
        ->unique()
        ->values()
        ->all();
}
```

### 4.5 Middleware de sécurité cible — `CheckPermission.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is deactivated.'], 403);
        }

        // Super-administrateur outrepasse les barrières
        if ($user->roles()->where('slug', 'admin')->exists()) {
            return $next($request);
        }

        if (!$user->hasPermission($permission)) {
            return response()->json([
                'error' => 'Access Denied',
                'message' => "Droits insuffisants. Requiert : {$permission}",
            ], 403);
        }

        return $next($request);
    }
}
```

> **Amélioration vs proposition initiale :** Conservation de la vérification `is_active`, support des wildcards via `User::hasPermission()`, réponses JSON uniformes (plus de redirect Inertia).

### 4.6 Synchronisation frontend

| Composant | Action |
|-----------|--------|
| `Can.tsx` | Consommer `auth.user.all_permissions` depuis `GET /api/user` — **ne pas** recalculer les permissions côté client |
| `Sidebar.tsx` | Filtrer les liens via `all_permissions` uniquement |
| `@smartschool/permissions` (package futur) | Hook `usePermission(name)` partagé |

---

## 5. Interconnexion inter-modules par identifiants logiques

### 5.1 Principe

Les modules futurs **ne doivent pas** dépendre de FK SQL cross-service. En Phase 1 (monolithe), les FK restent acceptables. En Phase 3+ (microservices), utiliser des **identifiants logiques** (`external_id` UUID).

### 5.2 Convention de nommage

| Pattern | Exemple | Usage |
|---------|---------|-------|
| `{entity}_external_id` | `teacher_external_id` | Référence vers un user du service IAM |
| `{entity}_external_id` | `student_external_id` | Référence vers un élève du service Academic |
| Préfixe logique (optionnel) | `usr_teach_77100` | Lisibilité debug — **UUID v4 recommandé en prod** |

### 5.3 Exemple : chaîne Enseignant → Classe → Élève

#### État actuel (FK directes)

```
users.id ←── school_classes.teacher_id
users.id ←── class_subject.teacher_id
students.class_id → school_classes.id
payments.student_id → students.id
```

#### État cible (découplé)

```
┌─────────────────┐         ┌──────────────────────┐         ┌─────────────────┐
│  Module IAM     │         │  Module Académique   │         │  Module Finance │
│  users (uuid)   │         │  class_subject       │         │  payments       │
│                 │         │  teacher_external_id │         │  student_ext_id │
└─────────────────┘         │  (pas de FK users)   │         │  (pas de FK)    │
                            └──────────────────────┘         └─────────────────┘
```

**Table pivot normalisée cible — `class_subject` :**

| Colonne | Type | Note |
|---------|------|------|
| `id` | bigint PK | — |
| `class_id` | FK locale | Module Academic |
| `subject_id` | FK locale | Module Academic |
| `teacher_external_id` | UUID string, indexé | Référence IAM — **sans FK SQL** |
| `coefficient` | decimal | — |
| `academic_year` | string | — |
| `is_active` | boolean | — |

**Règle pour l'IA :** Lors de l'extraction d'un module, remplacer les FK cross-domaine par `{entity}_external_id` + validation applicative (appel API ou cache local).

### 5.4 Stratégie de résolution des références

| Contexte | Mécanisme |
|----------|-----------|
| Monolithe (Phase 1–2) | FK SQL + jointures Eloquent — acceptable |
| Split API/SPA (Phase 1) | Inchangé côté backend |
| Microservices (Phase 3+) | API sync + cache Redis, ou events (`TeacherAssigned`) |

---

## 6. Reporting découplé (CQRS + BFF)

### 6.1 Problème actuel

`ReportController::stats` agrège directement `Student`, `User`, `Admission`, `Payment` — couplage transversal, JOINs lourds, impossible à extraire tel quel.

**Fichier :** `app/Http/Controllers/Api/ReportController.php`

### 6.2 Pattern cible : CQRS asynchrone

```
┌──────────────┐    Event     ┌──────────────┐    Write    ┌─────────────────────┐
│ Module       │ ──────────► │   Listener   │ ──────────► │ Table dénormalisée  │
│ Finance/     │ PAYMENT_    │ ReportProject│             │ report_snapshots    │
│ Grades/...   │ RECEIVED    │ orListener   │             │ (lecture seule)     │
└──────────────┘             └──────────────┘             └─────────────────────┘
                                                                    │
                                                                    ▼
                                                          ┌─────────────────────┐
                                                          │ BFF ReportController│
                                                          │ GET /api/reports/*  │
                                                          └─────────────────────┘
```

### 6.3 Table de lecture dénormalisée (exemple)

```php
Schema::create('report_snapshots', function (Blueprint $table) {
    $table->id();
    $table->string('metric_key');        // ex: total_students, payments_month
    $table->string('module');            // ex: finance, academic
    $table->json('value');               // { "count": 150, "amount_cdf": 500000 }
    $table->date('snapshot_date');
    $table->timestamps();
    $table->index(['metric_key', 'snapshot_date']);
});
```

### 6.4 Implémentation Laravel (Phase 2+)

```php
// app/Events/PaymentReceived.php
class PaymentReceived
{
    public function __construct(public Payment $payment) {}
}

// app/Listeners/ProjectPaymentToReport.php
class ProjectPaymentToReport
{
    public function handle(PaymentReceived $event): void
    {
        ReportSnapshot::updateOrCreate(
            ['metric_key' => 'payments_total_cdf', 'snapshot_date' => now()->toDateString()],
            ['module' => 'finance', 'value' => ['increment' => $event->payment->amount]]
        );
    }
}
```

**BFF Controller :**

```php
public function stats(Request $request)
{
    return response()->json([
        'students' => ReportSnapshot::where('metric_key', 'total_students')->latest()->first()?->value,
        'payments' => ReportSnapshot::where('metric_key', 'payments_total_cdf')->latest()->first()?->value,
        // ...
    ]);
}
```

> **Note :** En Phase 1 (monolithe), un **job planifié** (`php artisan schedule:run`) peut alimenter `report_snapshots` sans infrastructure d'events distribués.

---

## 7. Prérequis de rentrée académique et nettoyage

L'IA doit **valider ces étapes** avant d'engager l'extraction de modules (Inventory, Events, Finance).

### 7.1 Convergence des modèles enseignants

| Étape | Action | Fichiers |
|-------|--------|----------|
| 1 | Migrer données `class_teacher` → `class_subject` | Migration + Seeder |
| 2 | Mettre à jour `User::teachingClasses()` pour utiliser `class_subject` | `app/Models/User.php` |
| 3 | Supprimer relations pivot legacy | `User.php` lignes 210–234 |
| 4 | Drop table `class_teacher` | Migration |
| 5 | Tests GradeController + permissions teacher | `tests/Feature/` |

### 7.2 Filiation des seeders

**Fichier cible — `database/seeders/DatabaseSeeder.php` :**

```php
public function run(): void
{
    $this->call([
        RolesSeeder::class,              // AJOUT — permissions normalisées
        MigrateJsonPermissionsSeeder::class, // AJOUT — si migration RBAC
        GradeSystemSeeder::class,        // AJOUT — matières, coefficients
        ClassTeacherRelationshipSeeder::class, // TEMPORAIRE — puis supprimer après convergence
        UsersSeeder::class,
        SchoolClassesSeeder::class,
        StudentsSeeder::class,
        PaymentsSeeder::class,
        AdmissionsSeeder::class,
        EventsSeeder::class,
        AnnouncementsSeeder::class,
        InventorySeeder::class,
    ]);
}
```

### 7.3 Extraction composants UI dupliqués

| Composant | Source | Cible |
|-----------|--------|-------|
| `PhotoUpload.tsx` | Students + Users | `src/core/components/PhotoUpload.tsx` |
| `Can.tsx` | Core | Package `@smartschool/ui` ou `src/core/` |
| Types TS | Par feature | `src/core/types/` |

---

## 8. Plan d'exécution par phases

### Phase 0 — Sécurité & stabilisation (Semaine 1)

- [ ] Supprimer `/api/fix-admin`
- [ ] Purger `tmp_env_prod`, rotater secrets
- [ ] Intégrer `RolesSeeder` + `GradeSystemSeeder` dans `DatabaseSeeder`
- [ ] Unifier `class_teacher` → `class_subject`
- [ ] Tests de non-régression auth + permissions

### Phase 1 — RBAC normalisé (Semaine 2)

- [ ] Migrations `permissions`, `permission_role`, `role_user`
- [ ] Seeder migration JSON → relations
- [ ] Refactor `User::hasPermission()` + `CheckPermission`
- [ ] Mettre à jour `GET /api/user` (Resource avec `all_permissions`)
- [ ] Adapter `Can.tsx` et `Sidebar.tsx`

### Phase 2 — Split Frontend/Backend (Semaines 3–5)

- [ ] Créer dépôt `smartschool-api` (retirer Inertia)
- [ ] Créer dépôt `smartschool-web` (React Router + AuthProvider)
- [ ] Configurer CORS + Sanctum stateful
- [ ] Migrer `Features/` et `Core/`
- [ ] CI séparée (PHPUnit API + build Vite)
- [ ] Ne plus versionner `public/build/` dans l'API

### Phase 3 — Domains + Reporting CQRS (Semaines 6–8)

- [ ] Restructurer `app/Domains/`
- [ ] Implémenter events + `report_snapshots`
- [ ] Refactor `ReportController` → lecture dénormalisée
- [ ] API Resources uniformes

### Phase 4 — Extraction modules autonomes (Itératif)

Ordre recommandé :

1. Inventory (0 FK)
2. Events (0 FK)
3. Settings (0 FK)
4. Communication (FK users → external_id)
5. Admissions
6. Finance (FK students → external_id)
7. Academic + Grades
8. IAM centralisé
9. BFF Reporting

---

## 9. Arborescence cible des dépôts

### 9.1 `smartschool-api`

```
smartschool-api/
├── app/
│   ├── Domains/
│   │   ├── Auth/
│   │   ├── Students/
│   │   ├── Grades/
│   │   ├── Finance/
│   │   └── ...
│   ├── Events/
│   ├── Listeners/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   ├── Middleware/CheckPermission.php
│   │   └── Resources/
│   └── Models/
├── routes/
│   ├── api.php
│   └── auth.php          # login/logout JSON
├── database/migrations/
├── config/
│   ├── sanctum.php
│   └── cors.php
├── composer.json
└── .env.example
```

### 9.2 `smartschool-web`

```
smartschool-web/
├── src/
│   ├── main.tsx
│   ├── router/
│   │   ├── index.tsx
│   │   └── ProtectedRoute.tsx
│   ├── core/
│   │   ├── api/client.ts
│   │   ├── auth/AuthProvider.tsx
│   │   ├── layouts/DashboardLayout.tsx
│   │   └── components/
│   │       ├── Sidebar.tsx
│   │       ├── Can.tsx
│   │       └── Pagination.tsx
│   └── features/
│       ├── students/
│       ├── grades/
│       ├── finance/
│       └── ...
├── package.json
├── vite.config.ts
└── .env.example          # VITE_API_URL
```

---

## 10. Contrats API et formats de réponse

### 10.1 Enveloppe JSON standard

**Succès (collection) :**

```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72
  }
}
```

**Succès (ressource unique) :**

```json
{
  "data": {
    "id": 1,
    "first_name": "Jean",
    "email": "jean@example.com"
  }
}
```

**Erreur :**

```json
{
  "error": "Access Denied",
  "message": "Droits insuffisants. Requiert : students:write"
}
```

### 10.2 User Resource (auth hydration)

```json
{
  "data": {
    "id": 1,
    "first_name": "Admin",
    "last_name": "User",
    "email": "admin@example.com",
    "roles": ["admin"],
    "all_permissions": ["*"],
    "is_active": true
  }
}
```

### 10.3 Headers requis (SPA)

```
Accept: application/json
Content-Type: application/json
X-XSRF-TOKEN: {token}
Cookie: laravel_session=...; XSRF-TOKEN=...
```

---

## 11. Catalogue d'événements métier

| Événement | Module source | Payload | Listener cible |
|-----------|---------------|---------|--------------|
| `StudentEnrolled` | Academic | `{ student_id, class_id }` | ReportProjector |
| `StudentTransferred` | Academic | `{ student_id, from_class, to_class }` | ReportProjector |
| `PaymentReceived` | Finance | `{ payment_id, student_id, amount, currency }` | ReportProjector |
| `PaymentCancelled` | Finance | `{ payment_id }` | ReportProjector |
| `GradePublished` | Grades | `{ assessment_id, class_id, subject_id }` | ReportProjector |
| `ReportCardGenerated` | Grades | `{ student_id, term }` | ReportProjector |
| `AdmissionAccepted` | Admissions | `{ admission_id }` | StudentEnrolled (cascade) |
| `AdmissionRejected` | Admissions | `{ admission_id }` | ReportProjector |
| `AnnouncementCreated` | Communication | `{ announcement_id }` | — (pas de reporting) |
| `UserRoleChanged` | Auth | `{ user_id, roles[] }` | CacheInvalidation |

---

## 12. Annexes techniques

### Annexe A — Mapping fichiers actuels → cibles

| Actuel (monolithe) | Cible API | Cible Web |
|--------------------|-----------|-----------|
| `routes/api.php` | `routes/api.php` | — |
| `routes/web.php` (pages métier) | **Supprimé** | `src/router/index.tsx` |
| `resources/js/Features/*` | — | `src/features/*` |
| `resources/js/Core/*` | — | `src/core/*` |
| `resources/js/Pages/Auth/*` | `routes/auth.php` (JSON) | `src/features/auth/*` |
| `app/Http/Controllers/Api/*` | `app/Domains/*/Controllers/` | — |
| `public/build/` | **Non versionné** | `dist/` (deploy CDN/static) |

### Annexe B — Variables d'environnement

**API (`smartschool-api/.env`) :**

```env
APP_URL=https://api.smartschool.cd
FRONTEND_URL=https://app.smartschool.cd
SANCTUM_STATEFUL_DOMAINS=app.smartschool.cd,localhost:5173
SESSION_DOMAIN=.smartschool.cd
DB_CONNECTION=mysql
```

**Web (`smartschool-web/.env`) :**

```env
VITE_API_URL=https://api.smartschool.cd
VITE_APP_NAME=SmartSchool
```

### Annexe C — Checklist validation IA avant merge

- [ ] Aucun import Inertia restant dans le frontend
- [ ] Aucune route web Inertia métier dans l'API
- [ ] `CheckPermission` retourne JSON 401/403 (pas de redirect)
- [ ] `GET /api/user` inclut `all_permissions`
- [ ] CORS + Sanctum testés avec credentials cross-origin
- [ ] Seeders RBAC exécutés et idempotents
- [ ] Tests auth + permissions passent
- [ ] `class_teacher` supprimé ou migration documentée

### Annexe D — Références documentation projet

| Fichier | Contenu |
|---------|---------|
| `RAPPORT_ANALYSE_SEPARATION.md` | Analyse complète, matrice couplage, estimations |
| `GRADE_SYSTEM.md` | Système de notes |
| `CLASS_TEACHER_RELATIONSHIPS.md` | Relations enseignant-classe |
| `USER_PERMISSION_FIXES.md` | Historique corrections RBAC |

---

## Conclusion normative

Ce guide définit la trajectoire de transformation de SmartSchool-full :

1. **Sécuriser** (P0)
2. **Normaliser le RBAC** (relations, pas JSON)
3. **Découpler Frontend/Backend** (SPA + API pure)
4. **Unifier le modèle enseignant** (`class_subject` unique)
5. **Découpler le reporting** (CQRS + snapshots)
6. **Extraire les modules** par identifiants logiques

Toute IA ou développeur travaillant sur ce projet doit **respecter l'ordre des phases** et **ne pas sauter la Phase 0–1** sous peine de reproduire la dette technique actuelle (permissions JSON, pivot double, secrets exposés).

---

*SmartSchool-full — Spécification d'Architecture v1.1 — Document normatif de restructuration.*
