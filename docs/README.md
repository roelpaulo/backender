# Backender Documentation

A self-hosted backend runtime for creating custom API endpoints using PHP logic.

## 📚 Documentation Index

- **[QUICKSTART.md](QUICKSTART.md)** - Get started in 5 minutes
- **[AUTHENTICATION.md](AUTHENTICATION.md)** - Email verification, password reset, security features
- **[API_AUTHENTICATION.md](API_AUTHENTICATION.md)** - API key authentication for endpoint protection
- **[HTTP_METHODS.md](HTTP_METHODS.md)** - Complete guide to GET, POST, PUT, DELETE, PATCH
- **[EXAMPLES.md](EXAMPLES.md)** - Real-world API examples (users, blog, webhooks)
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - How Backender works internally

## Quick Start

```bash
# Build the Docker image
docker build -t backender .

# Run the container
docker run -d -p 8080:80 -v $(pwd)/storage:/app/storage --name backender backender

# Access the management UI
open http://localhost:8080
```

## First Run

1. Navigate to `http://localhost:8080`
2. **Create your admin account** (email + secure password)
3. **Verify your email** (check `storage/logs/emails.log` for link in development)
4. **Log in** and start building custom API endpoints

## Creating Endpoints

Endpoints are custom PHP functions that handle HTTP requests:

```php
return function ($request) {
    $name = $request->query('name', 'World');
    
    return [
        'message' => "Hello, {$name}!",
        'timestamp' => time()
    ];
};
```

## Request Object

- `$request->method()` - HTTP method (GET, POST, etc.)
- `$request->path()` - Request path
- `$request->query($key, $default)` - Query parameter
- `$request->input($key, $default)` - POST/JSON input
- `$request->json()` - Full JSON body as array
- `$request->headers()` - All headers

## Response Types

- **Array**: Returns JSON with 200 status
- **String**: Returns text with 200 status
- **Response object**: Full control over status and headers

## Database Access

SQLite database is available via `$db` PDO instance:

```php
return function ($request, $db) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$request->query('id')]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
};
```

## Storage

Data persists in the `/storage` directory:
- `/storage/database/backender.sqlite` - Application database
- `/storage/endpoints/` - Endpoint PHP files
- `/storage/logs/` - Error and request logs

## Deployment

### Docker Compose

```yaml
services:
  backender:
    image: backender
    ports:
      - "8080:80"
    volumes:
      - ./storage:/app/storage
    restart: unless-stopped
```

### VPS / Dokploy

Deploy the single container image with persistent storage mounted.

## Philosophy

Backender is not a CRUD generator or low-code platform. It's a programmable API server that gives you:

- Full PHP control over request handling
- Direct database access via PDO
- No framework overhead
- Simple, explicit behavior

Perfect for developers who want backend flexibility without SaaS constraints.
