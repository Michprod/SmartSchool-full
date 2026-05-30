# SmartSchool — Plan de Restructuration & Séparation

**Version :** 1.0  
**Date :** 24 mai 2026  
**Horizon :** 8 à 10 semaines  
**Documents de référence :**
- [`RAPPORT_ANALYSE_SEPARATION.md`](./RAPPORT_ANALYSE_SEPARATION.md) — diagnostic
- [`GUIDE_ARCHITECTURE_RESTRUCTURATION.md`](./GUIDE_ARCHITECTURE_RESTRUCTURATION.md) — spécifications normatives
- [`GUIDE_SEPARATION_FRONTEND_BACKEND.md`](./GUIDE_SEPARATION_FRONTEND_BACKEND.md) — détail technique du split

---

## 1. Objectif final

Transformer **SmartSchool-full** (monolithe Laravel + Inertia) en :

```
smartschool-api          smartschool-web
(Laravel 12, API JSON)   (React 18, SPA)
         │                        │
         └──────── MySQL ─────────┘
              (base unique en Phase 1–3)
```

**Livrable final :** Deux dépôts Git indépendants, déployables séparément, avec auth Sanctum cross-origin fonctionnelle.

---

## 2. Principes directeurs

| # | Principe |
|---|----------|
| 1 | **Une phase à la fois** — ne pas sauter la sécurisation |
| 2 | **Ne pas casser la prod** — chaque phase laisse l'app utilisable |
| 3 | **API d'abord stable, UI ensuite** — l'API existe déjà à 70 % |
| 4 | **Un module = une PR** — revue facile, rollback possible |
| 5 | **Tester avant d'extraire** — auth + permissions à chaque étape |

---

## 3. Vue d'ensemble des phases

```
Semaine 1        Semaines 2–3       Semaines 4–6       Semaines 7–8       Semaine 9+
─────────        ────────────       ────────────       ────────────       ─────────
 PHASE 0          PHASE 1            PHASE 2            PHASE 3            PHASE 4
 Sécurité         RBAC               Split              Modules            Production
 & Stabilisation  normalisé          Frontend/Backend   autonomes          & CI/CD
```

| Phase | Nom | Durée | Dépend de |
|-------|-----|-------|-----------|
| **0** | Sécurité & stabilisation | 1 sem | — |
| **1** | RBAC normalisé | 1–2 sem | Phase 0 |
| **2** | Split Frontend / Backend | 2–3 sem | Phase 0 (Phase 1 en parallèle possible) |
| **3** | Restructuration backend + reporting | 1–2 sem | Phase 2 |
| **4** | Extraction modules autonomes | itératif | Phase 3 |
| **5** | Production & CI/CD | 1 sem | Phase 2 minimum |

> **Chemin critique :** Phase 0 → Phase 2 → Phase 5  
> **Chemin parallèle :** Phase 1 peut démarrer pendant Phase 2

---

## 4. PHASE 0 — Sécurité & stabilisation

**Durée :** 1 semaine  
**Objectif :** Éliminer les failles et stabiliser les fondations avant tout split.

### Tâches

| # | Tâche | Fichier(s) | Priorité |
|---|-------|------------|----------|
| 0.1 | Supprimer l'endpoint backdoor admin | `routes/api.php` — `GET /api/fix-admin` | P0 |
| 0.2 | Retirer `tmp_env_prod` du repo + rotater secrets | `.gitignore`, Hostinger panel | P0 |
| 0.3 | Intégrer `RolesSeeder` dans `DatabaseSeeder` | `database/seeders/DatabaseSeeder.php` | P1 |
| 0.4 | Intégrer `GradeSystemSeeder` dans `DatabaseSeeder` | idem | P1 |
| 0.5 | Migrer données `class_teacher` → `class_subject` | Migration + seeder | P1 |
| 0.6 | Supprimer table `class_teacher` (après migration validée) | Migration | P1 |
| 0.7 | Tests auth + permissions passent | `tests/Feature/` | P1 |

### Critères de sortie (Definition of Done)

- [ ] Aucun endpoint non authentifié dangereux
- [ ] Aucun secret en clair dans Git
- [ ] `php artisan db:seed` crée un environnement cohérent (rôles + notes)
- [ ] Un seul pivot enseignant-classe : `class_subject`
- [ ] Tests PHPUnit verts

### Risques

| Risque | Parade |
|--------|--------|
| Migration `class_teacher` perd des données | Backup DB + seeder de rollback |
| Prod avec permissions incohérentes | Exécuter `RolesSeeder` manuellement sur prod avant deploy |

---

## 5. PHASE 1 — RBAC normalisé

**Durée :** 1–2 semaines  
**Objectif :** Remplacer les permissions JSON par des tables relationnelles.

### Tâches

| # | Tâche | Livrable |
|---|-------|----------|
| 1.1 | Créer migrations `permissions`, `permission_role`, `role_user` | 3 migrations |
| 1.2 | Créer modèles `Permission` + relations sur `User` et `Role` | 2 modèles |
| 1.3 | Seeder `MigrateJsonPermissionsSeeder` (JSON → relations) | Seeder idempotent |
| 1.4 | Refactor `User::hasPermission()` (wildcards conservés) | `app/Models/User.php` |
| 1.5 | Refactor `CheckPermission` middleware (JSON only, plus de redirect) | Middleware |
| 1.6 | Créer `UserResource` pour `GET /api/user` | API Resource |
| 1.7 | Adapter `Can.tsx` et `Sidebar.tsx` (consommer `all_permissions`) | Frontend |
| 1.8 | Supprimer colonnes JSON obsolètes (`roles.permissions`, `users.role`) | Migration |
| 1.9 | Tests permissions par rôle (admin, teacher, accountant…) | Tests Feature |

### Critères de sortie

- [ ] Permissions stockées en tables pivot, pas en JSON
- [ ] `GET /api/user` retourne `all_permissions` via Resource
- [ ] Wildcards `students:*` et `*` fonctionnent
- [ ] Frontend affiche le menu selon permissions réelles
- [ ] Tests permissions verts

### Peut démarrer en parallèle de Phase 2 ?

**Oui**, si Phase 0 est terminée. Les deux phases touchent des fichiers différents (RBAC = models/seeders, Split = routes/frontend).

---

## 6. PHASE 2 — Split Frontend / Backend ⭐ (priorité principale)

**Durée :** 2–3 semaines  
**Objectif :** Deux dépôts autonomes — API Laravel pure + SPA React.

### Semaine 1 — Préparer l'API

| # | Tâche | Détail |
|---|-------|--------|
| 2.1 | Publier et configurer CORS | `config/cors.php`, `FRONTEND_URL` |
| 2.2 | Configurer Sanctum stateful domains | `config/sanctum.php`, `.env` |
| 2.3 | Adapter login/logout → JSON | `AuthenticatedSessionController` |
| 2.4 | Adapter `CheckPermission` → JSON only | Middleware |
| 2.5 | Supprimer routes web métier | `routes/web.php` pages Inertia |
| 2.6 | Tests API auth (login, logout, /api/user) | Tests Feature |

### Semaine 2 — Créer le SPA

| # | Tâche | Détail |
|---|-------|--------|
| 2.7 | Initialiser repo `smartschool-web` (Vite + React TS) | Nouveau dépôt |
| 2.8 | Copier `Features/` → `src/features/`, `Core/` → `src/core/` | Migration fichiers |
| 2.9 | Créer `AuthProvider`, client Axios, `ProtectedRoute` | Core auth |
| 2.10 | Créer routeur SPA (miroir de `web.php`) | `src/router/` |
| 2.11 | Adapter `Sidebar`, `Header`, `Can` (react-router) | Core components |
| 2.12 | Migrer page Login depuis Breeze JSX | `features/auth/` |

### Semaine 3 — Migrer les features

**Checklist test manuel SPA** (http://127.0.0.1:5173) :

- [ ] Login / Logout
- [ ] Dashboard (stats)
- [ ] Inventory (CRUD)
- [ ] Events (CRUD)
- [ ] Students (liste + formulaire)
- [ ] Finance (paiements)
- [ ] Grades (notes)
- [ ] Admissions
- [ ] Communication
- [ ] Users + Profile
- [ ] Reports
- [ ] Settings

Migrer **une feature par jour** dans cet ordre :

| Jour | Feature | Appels API | Complexité |
|------|---------|------------|------------|
| J1 | Dashboard | 1 | Faible |
| J2 | Inventory | 4 CRUD | Faible |
| J3 | Events | 4 CRUD | Faible |
| J4 | Settings | 2 + roles | Moyenne |
| J5 | Communication | 2 | Faible |
| J6 | Admissions | 4 CRUD | Moyenne |
| J7 | Reports | 1 | Faible |
| J8 | Finance | 5 | Moyenne |
| J9 | Students | 3 + navigation | Moyenne |
| J10 | Users + Profile | 5 + upload | Moyenne |
| J11 | Grades | 6+ | Élevée |
| J12 | Tests intégration + corrections | — | — |

**Par feature, checklist :**
- [ ] Supprimer imports `@inertiajs/react`
- [ ] Remplacer `usePage()` → `useAuth()`
- [ ] Remplacer `router.visit()` → `useNavigate()`
- [ ] Remplacer `axios.get('/api/...')` → client API centralisé
- [ ] Tester CRUD complet

### Semaine 4 (buffer) — Nettoyage

| # | Tâche |
|---|-------|
| 2.13 | Retirer Inertia de `composer.json` |
| 2.14 | Supprimer `HandleInertiaRequests`, `app.blade.php`, `resources/js/` |
| 2.15 | Renommer repo monolithe → `smartschool-api` |
| 2.16 | Ne plus versionner `public/build/` |

### Critères de sortie

- [ ] Deux repos Git distincts et fonctionnels
- [ ] Login SPA → dashboard → logout OK
- [ ] Les 11 modules CRUD testés
- [ ] Aucun import Inertia restant
- [ ] API ne sert plus de HTML

### Architecture cible à la fin de Phase 2

```
app.smartschool.cd (SPA)  ──►  api.smartschool.cd (JSON)
     smartschool-web              smartschool-api
```

---

## 7. PHASE 3 — Restructuration backend & reporting

**Durée :** 1–2 semaines  
**Objectif :** Organiser le backend en domaines et découpler le reporting.

### Tâches

| # | Tâche | Détail |
|---|-------|--------|
| 3.1 | Restructurer `app/Domains/` par module | Auth, Students, Grades, Finance… |
| 3.2 | Extraire logique métier des contrôleurs → Services | Par domaine |
| 3.3 | API Resources uniformes (enveloppe JSON standard) | Tous les endpoints |
| 3.4 | Créer table `report_snapshots` (lecture dénormalisée) | Migration |
| 3.5 | Implémenter events métier (PaymentReceived, GradePublished…) | Events + Listeners |
| 3.6 | Refactor `ReportController` → lit `report_snapshots` | BFF pattern |
| 3.7 | Job planifié pour agrégation (fallback sans events) | `app/Console/Kernel` |
| 3.8 | Documenter API (OpenAPI/Swagger) | `docs/api.yaml` |

### Critères de sortie

- [ ] Code backend organisé par domaine
- [ ] Dashboard/Reports ne font plus de JOIN cross-module
- [ ] Documentation API publiée

---

## 8. PHASE 4 — Extraction modules autonomes (optionnel, long terme)

**Durée :** itératif (2–4 semaines par module)  
**Objectif :** Préparer la scalabilité future sans obligation immédiate.

### Ordre d'extraction

```
Priorité 1 (facile)          Priorité 2 (moyen)         Priorité 3 (difficile)
────────────────────         ──────────────────         ──────────────────────
Inventory                    Communication              Academic (Students)
Events                       Admissions                 Grades
Settings                     Finance                    Auth/IAM centralisé
                                                        BFF Reporting
```

### Par module extrait

| Étape | Action |
|-------|--------|
| 1 | Remplacer FK cross-module par `{entity}_external_id` (UUID) |
| 2 | Isoler migrations + modèles dans le domaine |
| 3 | Tests contractuels API |
| 4 | (Futur) Service déployable séparément + message broker |

> **Note :** Cette phase n'est **pas nécessaire** pour avoir un système fonctionnel en deux dépôts. Elle prépare l'avenir.

---

## 9. PHASE 5 — Production & CI/CD

**Durée :** 1 semaine  
**Objectif :** Déployer les deux projets en production de manière fiable.

### Tâches

| # | Tâche | Détail |
|---|-------|--------|
| 5.1 | Configurer sous-domaines Hostinger | `api.smartschool.cd` + `app.smartschool.cd` |
| 5.2 | CI API — tests PHPUnit sur push | `.github/workflows/api-tests.yml` |
| 5.3 | CI Web — build Vite sur push | `.github/workflows/web-build.yml` |
| 5.4 | Deploy API — composer + migrate | Script ou webhook |
| 5.5 | Deploy Web — `npm run build` → static | Copier `dist/` |
| 5.6 | Configurer cookies cross-domain prod | `SESSION_DOMAIN=.smartschool.cd` |
| 5.7 | Test end-to-end staging | Login → CRUD → logout |
| 5.8 | Monitoring basique (logs, uptime) | Optionnel |

### Critères de sortie

- [ ] Push sur `main` → deploy automatique API + Web
- [ ] Auth cross-origin fonctionne en HTTPS
- [ ] Rollback documenté

---

## 10. Calendrier récapitulatif

```
Semaine │ Phase │ Focus principal                    │ Jalon
────────┼───────┼────────────────────────────────────┼──────────────────────
   1    │   0   │ Sécurité, seeders, pivot enseignant│ ✅ Fondations saines
   2    │  1+2  │ RBAC + CORS/Sanctum + scaffold SPA │ ✅ API prête pour SPA
   3    │   2   │ Migration features (6 modules)     │ 🔄 SPA partiellement OK
   4    │   2   │ Migration features (5 modules)     │ ✅ Split complet
   5    │   3   │ Domains/ + reporting CQRS          │ ✅ Backend structuré
   6    │   5   │ CI/CD + deploy staging             │ ✅ Staging live
   7    │   5   │ Deploy production + tests E2E      │ ✅ Production live
  8–10  │   4   │ Extraction modules (optionnel)     │ 🔮 Scalabilité future
```

---

## 11. Équipe & responsabilités suggérées

| Rôle | Responsabilités | Phases |
|------|-----------------|--------|
| **Backend dev** | API, RBAC, migrations, CORS/Sanctum | 0, 1, 2-S1, 3 |
| **Frontend dev** | SPA, AuthProvider, migration features | 2-S2 à S4 |
| **DevOps** | CI/CD, deploy Hostinger, DNS | 5 |
| **QA** | Tests manuels par module, E2E | 2, 5 |

> Avec **1 seul développeur full-stack**, compter **8 semaines** en suivant l'ordre Phase 0 → 2 → 5, avec Phase 1 et 3 en parallèle quand possible.

---

## 12. Métriques de succès

| Métrique | Avant | Cible Phase 2 | Cible Phase 5 |
|----------|-------|---------------|---------------|
| Dépôts Git | 1 | 2 | 2 |
| Couplage Inertia | 100 % | 0 % | 0 % |
| Endpoints API documentés | 0 % | 50 % | 100 % |
| Tests auth/permissions | partiels | complets | complets |
| Deploy indépendant API/Web | non | non | oui |
| Temps deploy | manuel | manuel | automatique |
| Secrets en clair dans Git | oui | non | non |

---

## 13. Décisions à prendre avant de démarrer

| # | Question | Options | Recommandation |
|---|----------|---------|----------------|
| D1 | UUID maintenant ou plus tard ? | Phase 1 vs Phase 4 | **Plus tard** (bigint suffit pour le split) |
| D2 | JWT ou Sanctum cookies ? | JWT vs Sanctum SPA | **Sanctum** (déjà en place) |
| D3 | Monorepo temporaire ou 2 repos dès le début ? | 1 repo 2 dossiers vs 2 repos | **2 repos dès Phase 2-S2** |
| D4 | Proxy Vite ou CORS en dev ? | Proxy vs CORS | **Proxy Vite** (plus simple) |
| D5 | Phase 4 (microservices) nécessaire ? | Oui vs Non | **Non** pour l'instant |
| D6 | Domaine production ? | Sous-domaines vs chemins | **`api.` + `app.`** |

---

## 14. Prochaine action immédiate

```
┌─────────────────────────────────────────────────────────┐
│  COMMENCER PAR PHASE 0 — Semaine 1                      │
│                                                          │
│  Jour 1 : Supprimer /api/fix-admin + purge tmp_env_prod  │
│  Jour 2 : Intégrer RolesSeeder + GradeSystemSeeder       │
│  Jour 3 : Migration class_teacher → class_subject        │
│  Jour 4 : Tests + validation                             │
│  Jour 5 : Revue + go/no-go Phase 2                       │
└─────────────────────────────────────────────────────────┘
```

---

## 15. Suivi d'avancement (template)

Copier ce tableau et le mettre à jour chaque semaine :

| Phase | Statut | Début | Fin | Bloqueur | Notes |
|-------|--------|-------|-----|----------|-------|
| 0 — Sécurité | ✅ Terminé | 24/05/2026 | 24/05/2026 | — | fix-admin, tmp_env_prod, seeders, class_subject |
| 1 — RBAC | ⬜ À faire | | | | |
| 2 — Split FE/BE | 🔄 En cours | 24/05/2026 | | — | API CORS/Sanctum, smartschool-web créé |
| 3 — Domains/CQRS | ⬜ À faire | | | | |
| 4 — Modules | ⬜ Optionnel | | | | |
| 5 — Prod/CI | ⬜ À faire | | | | |

**Légende :** ⬜ À faire · 🔄 En cours · ✅ Terminé · ⏸️ En pause · ❌ Bloqué

---

*SmartSchool — Plan de Restructuration v1.0*
