# Backender Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        Docker Container                      │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                      nginx :80                       │   │
│  │  ┌────────────────────────────────────────────────┐ │   │
│  │  │              Static Files / Routing             │ │   │
│  │  └────────────────────┬───────────────────────────┘ │   │
│  └───────────────────────┼─────────────────────────────┘   │
│                          │                                   │
│  ┌───────────────────────▼─────────────────────────────┐   │
│  │                php-fpm (127.0.0.1:9000)             │   │
│  │  ┌────────────────────────────────────────────────┐ │   │
│  │  │             public/index.php                    │ │   │
│  │  │                                                  │ │   │
│  │  │  ┌──────────────────────────────────────────┐  │ │   │
│  │  │  │         Application Bootstrap            │  │ │   │
│  │  │  │  - Autoloader                            │  │ │   │
│  │  │  │  - Initialize App                        │  │ │   │
│  │  │  │  - Create Request                        │  │ │   │
│  │  │  │  - Route Request                         │  │ │   │
│  │  │  └──────────────────────────────────────────┘  │ │   │
│  │  └────────────────────────────────────────────────┘ │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                  Application Layer                   │   │
│  │                                                       │   │
│  │  Core/                   Controllers/                │   │
│  │  ├─ App.php              ├─ AdminController.php      │   │
│  │  ├─ Router.php           └─ EndpointController.php   │   │
│  │  └─ EndpointRunner.php                               │   │
│  │                                                       │   │
│  │  Http/                   Services/                   │   │
│  │  ├─ Request.php          └─ Auth.php                 │   │
│  │  └─ Response.php                                     │   │
│  │                                                       │   │
│  │  Views/                                               │   │
│  │  ├─ layout.php                                        │   │
│  │  ├─ setup.php                                         │   │
│  │  ├─ login.php                                         │   │
│  │  ├─ dashboard.php                                     │   │
│  │  ├─ edit.php                                          │   │
│  │  └─ logs.php                                          │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                  Storage (Persistent)                │   │
│  │                                                       │   │
│  │  /app/storage/                                        │   │
│  │  ├─ database/                                         │   │
│  │  │  └─ backender.sqlite                              │   │
│  │  ├─ endpoints/                                        │   │
│  │  │  ├─ 1.php                                          │   │
│  │  │  ├─ 2.php                                          │   │
│  │  │  └─ *.php                                          │   │
│  │  └─ logs/                                             │   │
│  │     ├─ php-error.log                                  │   │
│  │     └─ php-fpm.log                                    │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
         │                                          ▲
         │ Volume Mount                             │
         ▼                                          │
  ./storage (Host)                          Port 8080:80
```

## Request Flow

### Management UI Request

```
User Browser
    │
    ├─→ GET / (Dashboard)
    │       │
    │       ├─→ nginx
    │       │       │
    │       │       └─→ php-fpm → index.php
    │       │               │
    │       │               ├─→ AdminController::dashboard()
    │       │               │       │
    │       │               │       ├─→ Auth::requireAuth()
    │       │               │       │       │
    │       │               │       │       └─→ Check session
    │       │               │       │
    │       │               │       └─→ Query endpoints from DB
    │       │               │               │
    │       │               │               └─→ PDO → SQLite
    │       │               │
    │       │               └─→ Render dashboard.php view
    │       │                       │
    │       │                       └─→ Return HTML Response
    │       │
    │       └─→ Send to browser
    │
    └─→ Response: Endpoint list UI
```

### Custom API Endpoint Request

```
HTTP Client
    │
    ├─→ GET /api/hello?name=World
    │       │
    │       ├─→ nginx
    │       │       │
    │       │       └─→ php-fpm → index.php
    │       │               │
    │       │               ├─→ Create Request object
    │       │               │       │
    │       │               │       └─→ Parse method, path, query
    │       │               │
    │       │               ├─→ Router::match()
    │       │               │       │
    │       │               │       ├─→ Query enabled endpoints
    │       │               │       │       │
    │       │               │       │       └─→ PDO → SQLite
    │       │               │       │
    │       │               │       └─→ Return endpoint {id: 1, ...}
    │       │               │
    │       │               ├─→ EndpointRunner::execute()
    │       │               │       │
    │       │               │       ├─→ Load /storage/endpoints/1.php
    │       │               │       │       │
    │       │               │       │       └─→ require 'return function...'
    │       │               │       │
    │       │               │       ├─→ Call handler($request, $db)
    │       │               │       │       │
    │       │               │       │       └─→ Execute user logic
    │       │               │       │
    │       │               │       └─→ Normalize response
    │       │               │               │
    │       │               │               ├─→ Array → JSON
    │       │               │               ├─→ String → Text
    │       │               │               └─→ Response → Raw
    │       │               │
    │       │               └─→ Log request
    │       │                       │
    │       │                       └─→ INSERT INTO logs
    │       │
    │       └─→ Send JSON/Text response
    │
    └─→ Response: {"message": "Hello, World!", ...}
```

## Database Schema

```sql
┌──────────────────────────────────────────────┐
│                   users                       │
├──────────────────────────────────────────────┤
│ id         INTEGER PRIMARY KEY AUTOINCREMENT │
│ username   TEXT UNIQUE NOT NULL              │
│ password   TEXT NOT NULL (bcrypt)            │
│ created_at DATETIME DEFAULT CURRENT_TIMESTAMP│
└──────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│                 endpoints                     │
├──────────────────────────────────────────────┤
│ id         INTEGER PRIMARY KEY AUTOINCREMENT │
│ name       TEXT NOT NULL                     │
│ method     TEXT NOT NULL (GET/POST/...)      │
│ path       TEXT NOT NULL                     │
│ enabled    INTEGER DEFAULT 1 (0 or 1)        │
│ created_at DATETIME DEFAULT CURRENT_TIMESTAMP│
│ updated_at DATETIME DEFAULT CURRENT_TIMESTAMP│
├──────────────────────────────────────────────┤
│ UNIQUE INDEX: (method, path)                 │
└──────────────────────────────────────────────┘
                    │
                    │ FK endpoint_id
                    ▼
┌──────────────────────────────────────────────┐
│                    logs                       │
├──────────────────────────────────────────────┤
│ id          INTEGER PRIMARY KEY AUTOINCREMENT│
│ type        TEXT NOT NULL (error/request)    │
│ message     TEXT NOT NULL                    │
│ endpoint_id INTEGER NULL                     │
│ created_at  DATETIME DEFAULT CURRENT_TIMESTAMP│
└──────────────────────────────────────────────┘
```

## Endpoint File Structure

```
storage/endpoints/{id}.php

┌─────────────────────────────────────────────┐
│ <?php                                        │
│ return function ($request, $db) {            │
│     // User-defined logic                   │
│     $name = $request->query('name');        │
│                                              │
│     $stmt = $db->prepare('SELECT ...');     │
│     $stmt->execute([...]);                  │
│                                              │
│     return [                                 │
│         'result' => 'data'                   │
│     ];                                       │
│ };                                           │
└─────────────────────────────────────────────┘
         │
         │ Loaded via require
         │
         ▼
    Callable Handler
         │
         │ Executed with ($request, $db)
         │
         ▼
    Return Value
         │
         ├─→ Array → Response::json()
         ├─→ String → Response::text()
         └─→ Response → Direct output
```

## Security Model

```
┌─────────────────────────────────────────────┐
│            Self-Hosted Security              │
├─────────────────────────────────────────────┤
│                                              │
│  ✓ Bcrypt password hashing                  │
│  ✓ PHP session-based auth                   │
│  ✓ Prepared SQL statements only             │
│  ✓ No eval() or dynamic includes            │
│  ✓ Endpoint files restricted to /storage    │
│  ✓ No access to /app directory              │
│                                              │
│  ✗ Not multi-tenant                         │
│  ✗ Not public SaaS                          │
│  ✗ Assumes trusted developers               │
│                                              │
└─────────────────────────────────────────────┘
```

## Deployment Scenarios

### Local Development
```
Developer Machine
    │
    └─→ docker-compose up
            │
            └─→ localhost:8080
```

### VPS Deployment
```
VPS (Ubuntu/Debian)
    │
    ├─→ Docker Engine
    │       │
    │       └─→ docker run -p 80:80 backender
    │               │
    │               └─→ Volume: /var/backender/storage
    │
    └─→ Public IP :80
```

### Dokploy / Coolify
```
Platform
    │
    ├─→ Git Repository
    │       │
    │       └─→ Dockerfile
    │
    ├─→ Build & Deploy
    │       │
    │       └─→ Container Registry
    │
    └─→ Running Instance
            │
            ├─→ Persistent Volume: /app/storage
            └─→ Public URL
```

## Technology Stack

```
┌──────────────────────────────────────────┐
│              Frontend (UI)                │
├──────────────────────────────────────────┤
│  HTML5                                    │
│  TailwindCSS (CDN)                        │
│  DaisyUI (CDN)                            │
│  Alpine.js (CDN)                          │
│  → Dark theme default                     │
│  → No build tools required                │
└──────────────────────────────────────────┘

┌──────────────────────────────────────────┐
│             Backend (Runtime)             │
├──────────────────────────────────────────┤
│  PHP 8.3+ (php-fpm)                       │
│  → No framework                           │
│  → PSR-style autoloading                  │
│  → Object-oriented                        │
└──────────────────────────────────────────┘

┌──────────────────────────────────────────┐
│              Web Server                   │
├──────────────────────────────────────────┤
│  nginx (Alpine Linux)                     │
│  → FastCGI to php-fpm                     │
│  → Static file serving                    │
└──────────────────────────────────────────┘

┌──────────────────────────────────────────┐
│               Database                    │
├──────────────────────────────────────────┤
│  SQLite 3                                 │
│  → Single file                            │
│  → PDO access                             │
│  → ACID compliant                         │
└──────────────────────────────────────────┘

┌──────────────────────────────────────────┐
│            Container Platform             │
├──────────────────────────────────────────┤
│  Docker (Alpine base)                     │
│  → Single image                           │
│  → Non-root user                          │
│  → ~50MB image size                       │
└──────────────────────────────────────────┘
```

## Design Principles

1. **No Magic**: Everything is explicit PHP code
2. **No Framework**: Just classes and functions
3. **File-Based**: Endpoints are PHP files, not database blobs
4. **Self-Contained**: Single Docker image with everything
5. **Developer-First**: Built for programmers, not marketers
6. **Simple Deploy**: One image, one volume, done

---

**Backender: A programmable backend runtime for developers who code.**
