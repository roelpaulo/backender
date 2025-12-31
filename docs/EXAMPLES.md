# Backender API Examples

Real-world examples of API endpoints you can build with Backender.

## Table of Contents

- [User Management](#user-management)
- [Blog/Posts API](#blogposts-api)
- [Webhooks](#webhooks)
- [Authentication](#authentication)
- [File Upload Metadata](#file-upload-metadata)
- [Search & Filtering](#search--filtering)
- [Analytics & Tracking](#analytics--tracking)

---

## User Management

### Create Users Table

First, create a users table via a setup endpoint:

**POST /setup/users**
```php
<?php
return function ($request, $db) {
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    return ['message' => 'Users table created'];
};
```

### List Users with Pagination

**GET /api/users**
```php
<?php
return function ($request, $db) {
    $page = $request->query('page', 1);
    $limit = $request->query('limit', 10);
    $offset = ($page - 1) * $limit;
    
    $stmt = $db->prepare('SELECT * FROM users LIMIT ? OFFSET ?');
    $stmt->execute([$limit, $offset]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    
    return [
        'data' => $users,
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'pages' => ceil($total / $limit)
    ];
};
```

**Usage:**
```bash
curl "http://localhost:8080/api/users?page=1&limit=20"
```

---

## Blog/Posts API

### Create Posts Table

**POST /setup/posts**
```php
<?php
return function ($request, $db) {
    $db->exec('CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT,
        author TEXT,
        published INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    return ['message' => 'Posts table created'];
};
```

### Create Post

**POST /api/posts**
```php
<?php
return function ($request, $db) {
    $title = $request->input('title');
    $content = $request->input('content');
    $author = $request->input('author', 'Anonymous');
    
    if (!$title) {
        return Response::json(['error' => 'Title is required'], 400);
    }
    
    $stmt = $db->prepare(
        'INSERT INTO posts (title, content, author) VALUES (?, ?, ?)'
    );
    $stmt->execute([$title, $content, $author]);
    
    return Response::json([
        'id' => $db->lastInsertId(),
        'title' => $title
    ], 201);
};
```

### Get Published Posts

**GET /api/posts**
```php
<?php
return function ($request, $db) {
    $stmt = $db->query('SELECT * FROM posts WHERE published = 1 ORDER BY created_at DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
};
```

### Get Single Post

**GET /api/posts/single**
```php
<?php
return function ($request, $db) {
    $id = $request->query('id');
    
    if (!$id) {
        return Response::json(['error' => 'ID is required'], 400);
    }
    
    $stmt = $db->prepare('SELECT * FROM posts WHERE id = ? AND published = 1');
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        return Response::json(['error' => 'Post not found'], 404);
    }
    
    return $post;
};
```

### Publish/Unpublish Post

**PATCH /api/posts/publish**
```php
<?php
return function ($request, $db) {
    $id = $request->query('id');
    $published = $request->input('published', 1);
    
    $stmt = $db->prepare('UPDATE posts SET published = ? WHERE id = ?');
    $stmt->execute([$published, $id]);
    
    return ['message' => 'Post status updated'];
};
```

---

## Webhooks

### GitHub Webhook Handler

**POST /webhooks/github**
```php
<?php
return function ($request, $db) {
    // Get the webhook payload
    $payload = $request->json();
    $event = $request->header('X-GitHub-Event');
    
    // Create webhooks log table if needed
    $db->exec('CREATE TABLE IF NOT EXISTS webhook_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        source TEXT,
        event TEXT,
        payload TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    // Log the webhook
    $stmt = $db->prepare(
        'INSERT INTO webhook_logs (source, event, payload) VALUES (?, ?, ?)'
    );
    $stmt->execute(['github', $event, json_encode($payload)]);
    
    // Handle specific events
    if ($event === 'push') {
        $commits = count($payload['commits'] ?? []);
        $branch = $payload['ref'] ?? 'unknown';
        
        return [
            'message' => 'Push event processed',
            'commits' => $commits,
            'branch' => $branch
        ];
    }
    
    return ['status' => 'received', 'event' => $event];
};
```

### Generic Webhook Logger

**POST /webhooks/log**
```php
<?php
return function ($request, $db) {
    $source = $request->input('source', 'unknown');
    $data = $request->json();
    
    $db->exec('CREATE TABLE IF NOT EXISTS webhooks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        source TEXT,
        data TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    $stmt = $db->prepare('INSERT INTO webhooks (source, data) VALUES (?, ?)');
    $stmt->execute([$source, json_encode($data)]);
    
    return ['id' => $db->lastInsertId()];
};
```

---

## Authentication

### Simple API Key Authentication

**GET /api/protected**
```php
<?php
return function ($request, $db) {
    $apiKey = $request->header('X-API-Key');
    
    if (!$apiKey) {
        return Response::json(['error' => 'API key required'], 401);
    }
    
    // Check API key in database
    $stmt = $db->prepare('SELECT * FROM api_keys WHERE key = ? AND active = 1');
    $stmt->execute([$apiKey]);
    $validKey = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$validKey) {
        return Response::json(['error' => 'Invalid API key'], 401);
    }
    
    // Return protected data
    return ['message' => 'Access granted', 'data' => 'secret stuff'];
};
```

**Usage:**
```bash
curl http://localhost:8080/api/protected \
  -H "X-API-Key: your-secret-key"
```

---

## File Upload Metadata

Track file upload metadata (store actual files elsewhere, track in DB):

**POST /api/uploads**
```php
<?php
return function ($request, $db) {
    $db->exec('CREATE TABLE IF NOT EXISTS uploads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        filename TEXT,
        size INTEGER,
        mime_type TEXT,
        url TEXT,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    $filename = $request->input('filename');
    $size = $request->input('size');
    $mimeType = $request->input('mime_type');
    $url = $request->input('url');
    
    $stmt = $db->prepare(
        'INSERT INTO uploads (filename, size, mime_type, url) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$filename, $size, $mimeType, $url]);
    
    return Response::json([
        'id' => $db->lastInsertId(),
        'filename' => $filename
    ], 201);
};
```

---

## Search & Filtering

### Search Posts

**GET /api/search**
```php
<?php
return function ($request, $db) {
    $query = $request->query('q', '');
    $category = $request->query('category');
    
    if (empty($query)) {
        return Response::json(['error' => 'Search query required'], 400);
    }
    
    $sql = 'SELECT * FROM posts WHERE published = 1 AND (title LIKE ? OR content LIKE ?)';
    $params = ["%{$query}%", "%{$query}%"];
    
    if ($category) {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return [
        'query' => $query,
        'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
};
```

**Usage:**
```bash
curl "http://localhost:8080/api/search?q=hello&category=tech"
```

---

## Analytics & Tracking

### Page View Counter

**POST /api/track**
```php
<?php
return function ($request, $db) {
    $db->exec('CREATE TABLE IF NOT EXISTS page_views (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        page TEXT,
        ip TEXT,
        user_agent TEXT,
        viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    $page = $request->input('page');
    $ip = $request->header('X-Forwarded-For', 'unknown');
    $userAgent = $request->header('User-Agent', 'unknown');
    
    $stmt = $db->prepare('INSERT INTO page_views (page, ip, user_agent) VALUES (?, ?, ?)');
    $stmt->execute([$page, $ip, $userAgent]);
    
    // Get total views for this page
    $stmt = $db->prepare('SELECT COUNT(*) FROM page_views WHERE page = ?');
    $stmt->execute([$page]);
    $totalViews = $stmt->fetchColumn();
    
    return [
        'page' => $page,
        'total_views' => $totalViews
    ];
};
```

### Get Analytics

**GET /api/analytics**
```php
<?php
return function ($request, $db) {
    $page = $request->query('page');
    
    if ($page) {
        // Get stats for specific page
        $stmt = $db->prepare('SELECT COUNT(*) as views FROM page_views WHERE page = ?');
        $stmt->execute([$page]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'page' => $page,
            'views' => $result['views']
        ];
    }
    
    // Get overall stats
    $totalViews = $db->query('SELECT COUNT(*) FROM page_views')->fetchColumn();
    $stmt = $db->query('SELECT page, COUNT(*) as views FROM page_views GROUP BY page ORDER BY views DESC LIMIT 10');
    $topPages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'total_views' => $totalViews,
        'top_pages' => $topPages
    ];
};
```

---

## External API Integration

### Weather Proxy

**GET /api/weather**
```php
<?php
return function ($request, $db) {
    $city = $request->query('city', 'London');
    
    // Call external API
    $apiKey = 'your-api-key';
    $url = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}";
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    // Transform and return
    return [
        'city' => $city,
        'temperature' => $data['main']['temp'] ?? null,
        'condition' => $data['weather'][0]['description'] ?? null
    ];
};
```

---

## Tips for Building APIs

1. **Start with database setup endpoints** - Create tables first
2. **Use proper HTTP methods** - GET for reading, POST for creating, etc.
3. **Return meaningful status codes** - 201 for created, 404 for not found
4. **Validate all input** - Never trust user data
5. **Log important actions** - Use the built-in logging or create custom tables
6. **Handle errors gracefully** - Return JSON errors with appropriate codes
7. **Use prepared statements** - Always use `?` placeholders in SQL

---

For HTTP method details, see [HTTP Methods documentation](HTTP_METHODS.md).
