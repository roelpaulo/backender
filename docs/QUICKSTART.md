# Backender Quick Start Guide

## What You Just Built

Backender is a self-hosted PHP backend runtime that lets you create custom API endpoints through a web UI. No frameworks, no complexity - just PHP functions that handle HTTP requests.

## Project Structure

```
backender/
├── app/
│   ├── Core/
│   │   ├── App.php              # Application initialization
│   │   ├── Router.php           # Route matching
│   │   └── EndpointRunner.php   # Endpoint execution
│   ├── Http/
│   │   ├── Request.php          # Request wrapper
│   │   └── Response.php         # Response builder
│   ├── Controllers/
│   │   ├── AdminController.php  # UI controllers
│   │   └── EndpointController.php
│   ├── Services/
│   │   └── Auth.php             # Authentication
│   └── Views/
│       ├── layout.php           # Base template
│       ├── setup.php            # First-run setup
│       ├── login.php            # Login page
│       ├── dashboard.php        # Endpoint list
│       ├── edit.php             # Endpoint editor
│       └── logs.php             # Log viewer
├── public/
│   └── index.php                # Application bootstrap
├── storage/
│   ├── database/                # SQLite database
│   ├── endpoints/               # Endpoint logic files
│   └── logs/                    # Error logs
├── docker/
│   ├── nginx.conf               # nginx configuration
│   └── php-fpm.conf             # PHP-FPM configuration
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## Getting Started

### 1. Configure Email (Optional but Recommended)

For email verification and password reset to work, configure SMTP in `docker-compose.yml`:

```yaml
environment:
  - MAIL_DRIVER=smtp
  - MAIL_FROM_ADDRESS=admin@yourdomain.com
  - SMTP_HOST=smtp.gmail.com
  - SMTP_PORT=587
  - SMTP_USERNAME=your-email@gmail.com
  - SMTP_PASSWORD=your-app-password
  - APP_URL=http://localhost:8080
```

For Gmail, generate an App Password: https://myaccount.google.com/apppasswords

### 2. Build and Run

```bash
docker-compose up --build -d
```

### 3. Access the UI

Open http://localhost:8080 in your browser.

### 4. First Run Setup

You'll be prompted to create an admin account. You'll need to verify your email before logging in.

### 4. Create Your First Endpoint

1. Click "New Endpoint"
2. Fill in:
   - **Name**: Hello World
   - **Method**: GET
   - **Path**: /api/hello

3. Edit the endpoint logic:

```php
<?php
return function ($request) {
    $db = new PDO('sqlite:' . __DIR__ . '/../database/backender.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $name = $request->query('name', 'World');
    
    return [
        'message' => "Hello, {$name}!",
        'timestamp' => time()
    ];
};
```

4. Test it: http://localhost:8080/api/hello?name=Developer

## Creating Endpoints

Every endpoint is a PHP file that returns a function:

```php
<?php
return function ($request) {
    // Create DB connection inside the handler:
    $db = new PDO('sqlite:' . __DIR__ . '/../database/backender.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Your logic here
    return ['result' => 'data'];
};
```

### Request Object API

```php
$request->method()              // GET, POST, etc.
$request->path()                // /api/users
$request->query('key', 'default') // Query parameters
$request->input('key', 'default') // POST/JSON data
$request->json()                // Full JSON body
$request->headers()             // All headers
```

### Response Types

**Return an array** → JSON response (200)
```php
return ['status' => 'success', 'data' => [...]];
```

**Return a string** → Text response (200)
```php
return "Plain text response";
```

**Return a Response object** → Full control
```php
return Response::json(['error' => 'Not found'], 404);
return Response::text('Created', 201);
return Response::redirect('/somewhere');
```

### Database Access

Create a PDO connection inside your handler when you need DB access:

```php
return function ($request) {
    $db = new PDO('sqlite:' . __DIR__ . '/../database/backender.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create a table
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL
    )');
    
    // Insert data
    $stmt = $db->prepare('INSERT INTO users (name) VALUES (?)');
    $stmt->execute([$request->input('name')]);
    
    // Query data
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$request->query('id')]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
};
```

## Example Endpoints

### REST API

```php
// GET /api/users - List users
return function ($request) {
    $db = new PDO('sqlite:' . __DIR__ . '/../database/backender.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->query('SELECT * FROM users');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
};

// POST /api/users - Create user
return function ($request) {
    $db = new PDO('sqlite:' . __DIR__ . '/../database/backender.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $name = $request->input('name');
    $email = $request->input('email');
    
    $stmt = $db->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
    $stmt->execute([$name, $email]);
    
    return Response::json([
        'id' => $db->lastInsertId(),
        'name' => $name,
        'email' => $email
    ], 201);
};
```

### Webhook Handler

```php
// POST /webhooks/github
return function ($request) {
    $db = new PDO('sqlite:' . __DIR__ . '/../database/backender.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $payload = $request->json();
    
    // Log the webhook
    $stmt = $db->prepare('INSERT INTO webhook_logs (source, data) VALUES (?, ?)');
    $stmt->execute(['github', json_encode($payload)]);
    
    // Process the webhook
    if ($payload['action'] === 'opened') {
        // Do something
    }
    
    return ['status' => 'received'];
};
```

### Proxy/Transform

```php
// GET /api/weather
return function ($request) {
    $city = $request->query('city', 'London');
    
    // Call external API
    $data = file_get_contents("https://api.weather.example.com?city={$city}");
    $weather = json_decode($data, true);
    
    // Transform and return
    return [
        'city' => $city,
        'temperature' => $weather['main']['temp'],
        'condition' => $weather['weather'][0]['description']
    ];
};
```

## Deployment

### Local Development

```bash
docker-compose up -d
```

### VPS Deployment

1. Clone the repository on your VPS
2. Build and run:
   ```bash
   docker build -t backender .
   docker run -d -p 80:80 -v /path/to/storage:/app/storage backender
   ```

### Dokploy / Coolify

Deploy as a single Docker image with persistent volume for `/app/storage`.

## Data Persistence

All data is stored in `/storage`:
- **Database**: `/storage/database/backender.sqlite`
- **Endpoints**: `/storage/endpoints/{id}.php`
- **Logs**: `/storage/logs/`

To backup, just copy the storage directory.

## Security Notes

Backender is designed for self-hosted, developer-controlled environments:

- ✅ Bcrypt password hashing
- ✅ Prepared SQL statements only
- ✅ Session-based authentication
- ✅ Endpoint files restricted to storage directory
- ❌ Not designed for multi-tenant use
- ❌ Not a public-facing SaaS

## Architecture

### Request Lifecycle

1. **HTTP Request** → nginx → php-fpm
2. **Router** → Match method + path
3. **Check** → Is endpoint enabled?
4. **Load** → Require endpoint PHP file
5. **Execute** → Call handler function with ($request, $db)
6. **Normalize** → Convert return value to Response
7. **Send** → Output HTTP response

### No Eval, No Dynamic Includes

Endpoints are regular PHP files loaded via `require`. No eval(), no security holes.

## Troubleshooting

### View Logs

In the UI: Navigate to "Logs" in the top menu

Or via Docker:
```bash
docker exec -it backender tail -f /app/storage/logs/php-error.log
```

### Reset Everything

```bash
docker-compose down -v
rm -rf storage/database storage/endpoints
docker-compose up -d
```

### Check Endpoint Logic

Endpoint files are in `storage/endpoints/{id}.php`. You can edit them directly if needed.

## Philosophy

Backender is NOT:
- A CRUD generator
- A low-code platform
- A framework replacement
- A SaaS product

Backender IS:
- A programmable API server
- Full PHP control
- Self-hosted simplicity
- Developer freedom

**You write the logic. Backender runs it.**

## Support

This is self-hosted software. You have the code, you have the control.

Enjoy building! 🚀
