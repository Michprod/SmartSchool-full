# Architecture API + SPA (séparation complète)

## Deux projets indépendants

```
irmak project/
├── SmartSchool-full/     ← API Laravel JSON uniquement (:8000)
└── smartschool-web/      ← SPA React (:5173)
```

| Projet | Rôle | URL dev |
|--------|------|---------|
| **SmartSchool-full** | API REST + auth session Sanctum | `http://127.0.0.1:8000` |
| **smartschool-web** | Interface utilisateur React | `http://127.0.0.1:5173` |

**Plus de monolithe Inertia** : `:8000/dashboard` renvoie 404. Toute l'UI est sur le SPA.

---

## Communication cross-origin

```
┌─────────────────────┐         CORS + cookies          ┌─────────────────────┐
│  smartschool-web    │  ─────────────────────────────► │  SmartSchool-full   │
│  :5173              │   GET http://127.0.0.1:8000/    │  :8000              │
│                     │       api/announcements         │                     │
└─────────────────────┘                                 └─────────────────────┘
```

Le frontend appelle l'API via **URL absolue** (`VITE_API_URL`), pas de proxy Vite.

```env
# smartschool-web/.env
VITE_API_URL=http://127.0.0.1:8000
```

```env
# SmartSchool-full/.env
APP_URL=http://127.0.0.1:8000
FRONTEND_URL=http://127.0.0.1:5173
SANCTUM_STATEFUL_DOMAINS=127.0.0.1,127.0.0.1:5173,localhost,localhost:5173
```

Dans DevTools Network, les requêtes API apparaissent bien sur **`:8000`**.

---

## Auth Sanctum (SPA stateful)

1. SPA → `GET http://127.0.0.1:8000/sanctum/csrf-cookie`
2. SPA → `POST http://127.0.0.1:8000/login` (JSON)
3. SPA → `GET http://127.0.0.1:8000/api/user` (cookie session)

Les routes `GET /login`, `/register`, etc. sur l'API **redirigent** vers le SPA (`FRONTEND_URL`).

---

## Démarrage

```powershell
# Terminal 1 — API
cd SmartSchool-full
php artisan serve

# Terminal 2 — SPA
cd smartschool-web
npm run dev
```

- API info : http://127.0.0.1:8000/ → JSON `{ service: "SmartSchool API", ... }`
- Application : http://127.0.0.1:5173
- Login : `admin@smartschool.cd` / `password`

---

## Production (cible)

| Service | URL |
|---------|-----|
| Frontend | `https://app.smartschool.cd` |
| API | `https://api.smartschool.cd` |

```env
# smartschool-web
VITE_API_URL=https://api.smartschool.cd

# SmartSchool-full
FRONTEND_URL=https://app.smartschool.cd
```

Voir : [`PLAN_RESTRUCTURATION.md`](PLAN_RESTRUCTURATION.md)
