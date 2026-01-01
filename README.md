# Backender

⚡ A self-hosted backend runtime for creating custom API endpoints using PHP logic.

[![Version](https://img.shields.io/badge/version-0.1.1-orange.svg)](https://github.com/roelpaulo/backender/releases)
[![PHP Version](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

## Quick Start

```bash
docker build -t backender .
docker run -d -p 8080:80 \
  -v $(pwd)/storage:/app/storage \
  -e MAIL_DRIVER=smtp \
  -e MAIL_FROM_ADDRESS=admin@yourdomain.com \
  -e SMTP_HOST=smtp.gmail.com \
  -e SMTP_PORT=587 \
  -e SMTP_USERNAME=your-email@gmail.com \
  -e SMTP_PASSWORD=your-app-password \
  -e APP_URL=http://localhost:8080 \
  --name backender backender
```

Access the UI at **http://localhost:8080** and create your admin account.

## What is Backender?

Backender is a programmable API server that lets you create custom HTTP endpoints through a web interface. Each endpoint is a PHP function with full access to:

- **Request data** (query params, POST data, JSON, headers)
- **Response control** (JSON, text, custom status codes)
- **External APIs** (call any HTTP service)
- **Custom logic** (transformations, validations, processing)

**No framework overhead. No SaaS constraints. Just PHP.**

## Features

- ✅ **Email-based authentication** with verification
- ✅ **Password complexity requirements** (uppercase, lowercase, numbers, special chars)
- ✅ **Forgot password** / password reset functionality
- ✅ **API key authentication** for endpoint protection
- ✅ **Per-endpoint auth toggle** (public vs protected)
- ✅ Create API endpoints via web UI
- ✅ Monaco Editor (VS Code) for PHP code
- ✅ SQLite database with PDO access
- ✅ Dark theme interface (TailwindCSS + DaisyUI)
- ✅ Request/error logging
- ✅ Enable/disable endpoints
- ✅ Single Docker image deployment
- ✅ Persistent storage
- ✅ CORS support for JavaScript frontends

## Example Endpoint

```php
<?php
return function ($request) {
    $name = $request->query('name', 'World');
    
    return [
        'message' => "Hello, {$name}!",
        'timestamp' => time(),
        'method' => $request->method()
    ];
};
```

Test it:
```bash
curl "http://localhost:8080/api/hello?name=Developer"
```

## 🔐 Security

Backender includes enterprise-grade security features:

- **Email-based authentication** - No weak usernames, verified email addresses only
- **Email verification required** - Users must verify email before logging in
- **Strong password requirements** - Enforces uppercase, lowercase, numbers, special characters
- **Password reset flow** - Secure token-based password recovery
- **Bcrypt password hashing** - Industry-standard password storage
- **Session management** - Secure cookie-based sessions

For complete documentation, see **[AUTHENTICATION.md](docs/AUTHENTICATION.md)**.

## Documentation

- **[Quick Start Guide](docs/QUICKSTART.md)** - Get started in 5 minutes
- **[Authentication & Security](docs/AUTHENTICATION.md)** - Email verification, password reset
- **[API Key Authentication](docs/API_AUTHENTICATION.md)** - Protect endpoints with API keys
- **[HTTP Methods & Data Handling](docs/HTTP_METHODS.md)** - Complete guide to GET, POST, PUT, DELETE, PATCH
- **[API Examples](docs/EXAMPLES.md)** - Real-world endpoint examples
- **[Architecture](docs/ARCHITECTURE.md)** - System design and diagrams

## Deployment

### Docker Compose

```yaml
services:
  backender:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./storage:/app/storage
    environment:
      # Email configuration (for verification & password reset)
      - MAIL_DRIVER=smtp
      - MAIL_FROM_ADDRESS=admin@yourdomain.com
      - SMTP_HOST=smtp.gmail.com
      - SMTP_PORT=587
      - SMTP_USERNAME=your-email@gmail.com
      - SMTP_PASSWORD=your-app-password
      # App URL (used in email links)
      - APP_URL=http://localhost:8080
    restart: unless-stopped
```

### VPS / Cloud

```bash
docker build -t backender .
docker run -d -p 80:80 \
  -v /path/to/storage:/app/storage \
  -e MAIL_DRIVER=smtp \
  -e MAIL_FROM_ADDRESS=admin@yourdomain.com \
  -e SMTP_HOST=smtp.gmail.com \
  -e SMTP_PORT=587 \
  -e SMTP_USERNAME=your-email@gmail.com \
  -e SMTP_PASSWORD=your-app-password \
  -e APP_URL=https://your-domain.com \
  --name backender \
  backender
```

Works with: Dokploy, Coolify, Portainer, or any Docker host.

### Email Configuration

Backender uses **PHPMailer** for sending verification and password reset emails via SMTP.

**Required Environment Variables:**
- `MAIL_DRIVER=smtp` - Email driver (use "smtp")
- `MAIL_FROM_ADDRESS` - From email address
- `SMTP_HOST` - SMTP server hostname
- `SMTP_PORT` - SMTP port (587 for TLS, 465 for SSL)
- `SMTP_USERNAME` - SMTP authentication username
- `SMTP_PASSWORD` - SMTP authentication password
- `APP_URL` - Your application URL (for email links)

**Gmail Example:**
1. Enable 2-factor authentication on your Google account
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Use the app password in `SMTP_PASSWORD`

**Other SMTP Providers:**
- **SendGrid**: `smtp.sendgrid.net:587`
- **Mailgun**: `smtp.mailgun.org:587`
- **Amazon SES**: `email-smtp.us-east-1.amazonaws.com:587`
- **Custom SMTP**: Use your own mail server credentials

## Request Object API

```php
$request->method()                    // 'GET', 'POST', etc.
$request->path()                      // '/api/users'
$request->query('key', 'default')     // Query parameters
$request->input('key', 'default')     // POST/JSON data
$request->json()                      // Full JSON body
$request->headers()                   // All headers
$request->header('X-Custom')          // Specific header
```

## Response Types

**Array → JSON (200)**
```php
return ['status' => 'success', 'data' => [...]];
```

**String → Text (200)**
```php
return "Plain text response";
```

**Response Object → Full Control**
```php
return Response::json(['error' => 'Not found'], 404);
return Response::text('Created', 201);
return Response::redirect('/login');
```

## CORS (Cross-Origin Resource Sharing)

**CORS is enabled by default** for all endpoints, allowing JavaScript from any origin to call your APIs.

### Default Behavior

```php
<?php
return function ($request) {
    // Automatically includes CORS headers
    return ['message' => 'Hello from API'];
};
```

This allows requests from:
- Frontend apps (React, Vue, Angular)
- Different ports (localhost:3000 → localhost:8080)
- Different domains (myapp.com → api.myapp.com)

### Custom CORS Settings

```php
<?php
return function ($request) {
    return Response::json(['data' => 'value'])
        ->withCors('https://myapp.com', ['GET', 'POST']);
};
```

### Disable CORS (Restrict to Same Origin)

```php
<?php
return function ($request) {
    // Don't call withCors() - no CORS headers
    return Response::json(['data' => 'value'], 200);
};
```

### Testing CORS from JavaScript

```javascript
// From any frontend (React, Vue, vanilla JS, etc.)
fetch('http://localhost:8080/api/hello')
  .then(res => res.json())
  .then(data => console.log(data));

// With POST
fetch('http://localhost:8080/api/users', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ name: 'John' })
})
  .then(res => res.json())
  .then(data => console.log(data));
```

### Testing CORS Locally (Simulating Cross-Origin)

**Option 1: Create a simple HTML file**

Create `test-cors.html` anywhere on your computer:

```html
<!DOCTYPE html>
<html>
<head>
    <title>CORS Test</title>
</head>
<body>
    <h1>Testing Backender API</h1>
    <button onclick="testAPI()">Test GET (Public)</button>
    <button onclick="testProtectedAPI()">Test GET (Protected)</button>
    <button onclick="testPost()">Test POST with Data</button>
    <button onclick="testPut()">Test PUT with Data</button>
    <button onclick="testDelete()">Test DELETE</button>
    <pre id="result"></pre>

    <script>
        const API_KEY = 'bk_7eedcb6fcb8281005fa7dc50eddfeeffbc9004edf72bc51ffcc8ccfbe888dd01';

        async function testAPI() {
            try {
                // Call your Backender API from a different origin
                const response = await fetch('http://localhost:8080/api/hello?name=World');
                const data = await response.json();
                document.getElementById('result').textContent = 'GET (Public):\n' + JSON.stringify(data, null, 2);
            } catch (error) {
                document.getElementById('result').textContent = 'Error: ' + error.message;
            }
        }

        async function testProtectedAPI() {
            try {
                // Call protected endpoint with API key
                const response = await fetch('http://localhost:8080/api/protected', {
                    headers: {
                        'X-API-Key': API_KEY,
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();
                document.getElementById('result').textContent = 'GET (Protected):\n' + JSON.stringify(data, null, 2);
            } catch (error) {
                document.getElementById('result').textContent = 'Error: ' + error.message;
            }
        }

        async function testPost() {
            try {
                // POST request with JSON data and API key
                const response = await fetch('http://localhost:8080/api/users', {
                    method: 'POST',
                    headers: {
                        'X-API-Key': API_KEY,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name: 'John Doe',
                        email: 'john@example.com',
                        age: 30
                    })
                });
                const data = await response.json();
                document.getElementById('result').textContent = 'POST:\n' + JSON.stringify(data, null, 2);
            } catch (error) {
                document.getElementById('result').textContent = 'Error: ' + error.message;
            }
        }

        async function testPut() {
            try {
                // PUT request to update data
                const response = await fetch('http://localhost:8080/api/users/123', {
                    method: 'PUT',
                    headers: {
                        'X-API-Key': API_KEY,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name: 'Jane Doe',
                        email: 'jane@example.com',
                        age: 25
                    })
                });
                const data = await response.json();
                document.getElementById('result').textContent = 'PUT:\n' + JSON.stringify(data, null, 2);
            } catch (error) {
                document.getElementById('result').textContent = 'Error: ' + error.message;
            }
        }

        async function testDelete() {
            try {
                // DELETE request
                const response = await fetch('http://localhost:8080/api/users/123', {
                    method: 'DELETE',
                    headers: {
                        'X-API-Key': API_KEY
                    }
                });
                const data = await response.json();
                document.getElementById('result').textContent = 'DELETE:\n' + JSON.stringify(data, null, 2);
            } catch (error) {
                document.getElementById('result').textContent = 'Error: ' + error.message;
            }
        }
    </script>
</body>
</html>
```

Open this file directly in your browser (`file:///path/to/test-cors.html`) - this is a different origin than `localhost:8080`, so CORS will be tested!

**Option 2: Use a different port**

```bash
# Terminal 1: Backender running on port 8080
docker run -d -p 8080:80 backender

# Terminal 2: Simple Python HTTP server on port 3000
cd /path/to/your/frontend
python3 -m http.server 3000
```

Now `localhost:3000` calling `localhost:8080` tests CORS!

**Option 3: Use CodePen/JSFiddle**

Go to https://codepen.io and create a new pen:

```javascript
// JavaScript in CodePen
fetch('http://localhost:8080/api/hello')
  .then(res => res.json())
  .then(data => console.log(data))
  .catch(err => console.error(err));
```

CodePen's origin calling your localhost tests CORS!

### Production Example

**Backend (Backender):** `https://api.example.com`

**Frontend (Any domain):** `https://myapp.com`

The frontend just makes normal requests:

```javascript
// In your React/Vue/Angular app at myapp.com
const response = await fetch('https://api.example.com/api/users');
const users = await response.json();
```

**That's it!** No special configuration needed on the client side. Backender handles all CORS headers automatically.

### What the Client Needs

**Nothing!** The client (requester) just needs:

1. Your API URL: `https://api.example.com/api/endpoint`
2. Standard HTTP methods: GET, POST, PUT, DELETE, PATCH
3. Optional: Headers like `Content-Type: application/json` for POST/PUT

Example for a requester using your API:

```javascript
// GET request
const data = await fetch('https://api.example.com/api/users').then(r => r.json());

// POST request
const result = await fetch('https://api.example.com/api/users', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ name: 'John', email: 'john@example.com' })
}).then(r => r.json());
```

The browser automatically:
1. Sends an OPTIONS preflight request (Backender handles this)
2. Checks CORS headers (Backender sends these)
3. Allows the actual request to proceed ✅

## Connecting to External Databases

Endpoints can connect to **any external database** - your existing PostgreSQL, MySQL, MongoDB, etc. The internal SQLite is only for Backender's management.

### PostgreSQL

```php
<?php
return function ($request) {
    $pdo = new PDO(
        'pgsql:host=your-host;port=5432;dbname=your-db',
        'username',
        'password'
    );
    
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$request->query('id')]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
};
```

### MySQL

```php
<?php
return function ($request) {
    $pdo = new PDO(
        'mysql:host=your-host;dbname=your-db;charset=utf8mb4',
        'username',
        'password',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $pdo->query('SELECT * FROM products');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
};
```

### MongoDB (via HTTP API or Extension)

```php
<?php
return function ($request) {
    // Using MongoDB Atlas Data API
    $url = 'https://data.mongodb-api.com/app/your-app/endpoint/data/v1/action/find';
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n" .
                       "api-key: your-api-key\r\n",
            'content' => json_encode([
                'dataSource' => 'Cluster0',
                'database' => 'mydb',
                'collection' => 'users',
                'filter' => []
            ])
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return json_decode($result, true);
};
```

### REST APIs / Microservices

```php
<?php
return function ($request) {
    // Connect to any HTTP API
    $apiUrl = 'https://api.example.com/data';
    $ch = curl_init($apiUrl);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer your-token',
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
};
```

### Redis

```php
<?php
return function ($request) {
    $redis = new Redis();
    $redis->connect('your-redis-host', 6379);
    $redis->auth('your-password');
    
    $value = $redis->get('user:' . $request->query('id'));
    
    return ['data' => json_decode($value, true)];
};
```

### Environment Variables for Credentials

Store sensitive credentials in environment variables:

```php
<?php
return function ($request) {
    $pdo = new PDO(
        sprintf('pgsql:host=%s;dbname=%s', 
            getenv('DB_HOST'), 
            getenv('DB_NAME')
        ),
        getenv('DB_USER'),
        getenv('DB_PASS')
    );
    
    // Your query logic
};
```

Set environment variables in docker-compose:

```yaml
services:
  backender:
    build: .
    environment:
      - DB_HOST=postgres.example.com
      - DB_NAME=myapp
      - DB_USER=admin
      - DB_PASS=secret
    ports:
      - "8080:80"
    volumes:
      - ./storage:/app/storage
```

> **Note:** The internal SQLite (`/storage/database/backender.sqlite`) is only used by Backender for managing endpoints, users, and logs. Your API endpoints should connect to your own databases.

## Storage Structure

```
storage/
├── database/
│   └── backender.sqlite    # Application database
├── endpoints/
│   ├── 1.php              # Endpoint logic files
│   ├── 2.php
│   └── ...
└── logs/
    ├── php-error.log      # PHP errors
    └── php-fpm.log        # FPM logs
```

## Technology Stack

- **Backend**: PHP 8.4 (no framework)
- **Database**: SQLite 3
- **Web Server**: nginx
- **Runtime**: php-fpm
- **Container**: Alpine Linux 3.21
- **Frontend**: TailwindCSS + DaisyUI + Alpine.js
- **Editor**: Monaco Editor (VS Code)
- **Email**: PHPMailer 6.9 (SMTP support)
- **Dependencies**: Composer

## Philosophy

Backender is **NOT**:
- ❌ A CRUD generator
- ❌ A low-code platform
- ❌ A SaaS product
- ❌ A framework replacement

Backender **IS**:
- ✅ A programmable API server
- ✅ Full PHP control
- ✅ Self-hosted simplicity
- ✅ Developer freedom

**Perfect for:**
- Custom APIs and webhooks
- API proxies and transformations
- Data aggregation from multiple sources
- Serverless-like functions
- Backend prototyping
- Microservices

## Development

```bash
# Build
docker build -t backender .

# Run with live reload (mount code)
docker run -d -p 8080:80 \
  -v $(pwd)/storage:/app/storage \
  -v $(pwd)/app:/app/app \
  -v $(pwd)/public:/app/public \
  --name backender backender

# View logs
docker logs -f backender

# Shell access
docker exec -it backender sh
```

## Common Commands

```bash
# Rebuild and restart
docker build -t backender . && \
docker rm -f backender && \
docker run -d -p 8080:80 -v $(pwd)/storage:/app/storage --name backender backender

# View application logs
docker exec backender tail -f /app/storage/logs/php-error.log

# Backup storage
tar -czf backender-backup.tar.gz storage/
```

## Security Notes

Backender is designed for **self-hosted, developer-controlled environments**:

- ✅ Bcrypt password hashing
- ✅ Prepared SQL statements only
- ✅ Session-based authentication
- ✅ No eval() or dynamic includes
- ❌ Not designed for multi-tenant use
- ❌ Not a public-facing SaaS

**Use behind a reverse proxy with HTTPS in production.**

## Contributing

This is a personal/self-hosted tool. Feel free to fork and customize for your needs!

## License

MIT License - Use freely, modify as needed.

## Support

- 📖 [Documentation](docs/)
- 💬 [Issues](https://github.com/yourusername/backender/issues)
- ⭐ Star the repo if you find it useful!

---

## Demo

- 🌐 [https://backender-demo.roelsoft.dev/](https://backender-demo.roelsoft.dev/)

---

**Built for developers who want a programmable PHP backend without the overhead.**

🚀 Deploy once, code anywhere.
