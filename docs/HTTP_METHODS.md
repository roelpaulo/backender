# HTTP Methods & Data Handling

Complete guide to working with different HTTP methods and handling data in Backender endpoints.

## Table of Contents

- [GET Requests](#get-requests)
- [POST Requests](#post-requests)
- [PUT Requests](#put-requests)
- [DELETE Requests](#delete-requests)
- [PATCH Requests](#patch-requests)
- [Request Object API](#request-object-api)
- [Response Types](#response-types)

---

## GET Requests

GET requests typically retrieve data and use query parameters.

### Basic GET Endpoint (No Database)

```php
<?php
return function ($request, $db) {
    // Note: $db is always passed but you don't have to use it!
    
    // Get single query parameter with default
    $id = $request->query('id', 1);
    
    // Get all query parameters
    $allParams = $request->query();
    
    return [
        'id' => $id,
        'all_params' => $allParams
    ];
};
```

### Simple Calculator Endpoint (No Database)

```php
<?php
return function ($request, $db) {
    // Process data without touching the database
    $a = (float) $request->query('a', 0);
    $b = (float) $request->query('b', 0);
    $op = $request->query('op', 'add');
    
    $result = match($op) {
        'add' => $a + $b,
        'subtract' => $a - $b,
        'multiply' => $a * $b,
        'divide' => $b != 0 ? $a / $b : 'Error: Division by zero',
        default => 'Invalid operation'
    };
    
    return [
        'operation' => $op,
        'a' => $a,
        'b' => $b,
        'result' => $result
    ];
};
```

**Usage:**
```bash
curl "http://localhost:8080/api/calc?a=10&b=5&op=multiply"
# Returns: {"operation":"multiply","a":10,"b":5,"result":50}
```

### Example Usage

```bash
# Simple query parameter
curl http://localhost:8080/api/user?id=123

# Multiple query parameters
curl "http://localhost:8080/api/search?q=hello&page=2&limit=10"
```

### GET with Database Query

```php
<?php
return function ($request, $db) {
    $id = $request->query('id');
    
    if (!$id) {
        return Response::json(['error' => 'ID is required'], 400);
    }
    
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return Response::json(['error' => 'User not found'], 404);
    }
    
    return $user;
};
```

---

## POST Requests

POST requests typically create new resources and accept data in the request body.

### JSON POST Endpoint

```php
<?php
return function ($request, $db) {
    // Get individual fields
    $name = $request->input('name');
    $email = $request->input('email');
    $age = $request->input('age', 0);
    
    // Or get all input at once
    $data = $request->input();
    
    return [
        'received' => [
            'name' => $name,
            'email' => $email,
            'age' => $age
        ]
    ];
};
```

### Example Usage

```bash
# JSON POST
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "age": 30
  }'

# Form POST
curl -X POST http://localhost:8080/api/users \
  -d "name=John Doe" \
  -d "email=john@example.com" \
  -d "age=30"
```

### POST with Database Insert

```php
<?php
return function ($request, $db) {
    // Validate input
    $name = $request->input('name');
    $email = $request->input('email');
    
    if (!$name || !$email) {
        return Response::json([
            'error' => 'Name and email are required'
        ], 400);
    }
    
    // Insert into database
    $stmt = $db->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
    $stmt->execute([$name, $email]);
    
    return Response::json([
        'id' => $db->lastInsertId(),
        'name' => $name,
        'email' => $email
    ], 201);
};
```

### Nested JSON Data

```php
<?php
return function ($request, $db) {
    // Access nested JSON data
    $user = $request->input('user');
    $address = $request->input('user.address');
    
    // Or get the full JSON structure
    $fullData = $request->json();
    
    return [
        'user_name' => $user['name'] ?? null,
        'user_address' => $address ?? null,
        'full_data' => $fullData
    ];
};
```

**Example:**
```bash
curl -X POST http://localhost:8080/api/complex \
  -H "Content-Type: application/json" \
  -d '{
    "user": {
      "name": "John",
      "address": {
        "city": "New York",
        "zip": "10001"
      }
    }
  }'
```

---

## PUT Requests

PUT requests typically update existing resources completely.

### Basic PUT Endpoint

```php
<?php
return function ($request, $db) {
    $id = $request->query('id');
    $name = $request->input('name');
    $email = $request->input('email');
    
    if (!$id) {
        return Response::json(['error' => 'ID is required'], 400);
    }
    
    // Update the entire resource
    $stmt = $db->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
    $stmt->execute([$name, $email, $id]);
    
    if ($stmt->rowCount() === 0) {
        return Response::json(['error' => 'User not found'], 404);
    }
    
    return Response::json([
        'message' => 'User updated',
        'id' => $id
    ]);
};
```

### Example Usage

```bash
curl -X PUT "http://localhost:8080/api/user?id=123" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Updated",
    "email": "john.updated@example.com"
  }'
```

---

## DELETE Requests

DELETE requests remove resources.

### Basic DELETE Endpoint

```php
<?php
return function ($request, $db) {
    $id = $request->query('id');
    
    if (!$id) {
        return Response::json(['error' => 'ID is required'], 400);
    }
    
    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        return Response::json(['error' => 'User not found'], 404);
    }
    
    return Response::json([
        'message' => 'User deleted',
        'id' => $id
    ], 200);
};
```

### Example Usage

```bash
curl -X DELETE "http://localhost:8080/api/user?id=123"
```

### DELETE with Soft Delete

```php
<?php
return function ($request, $db) {
    $id = $request->query('id');
    
    // Soft delete - mark as deleted instead of removing
    $stmt = $db->prepare('UPDATE users SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([$id]);
    
    return Response::json(['message' => 'User soft deleted']);
};
```

---

## PATCH Requests

PATCH requests partially update resources (only specified fields).

### Basic PATCH Endpoint

```php
<?php
return function ($request, $db) {
    $id = $request->query('id');
    $updates = [];
    $params = [];
    
    // Build dynamic UPDATE query
    if ($request->input('name') !== null) {
        $updates[] = 'name = ?';
        $params[] = $request->input('name');
    }
    
    if ($request->input('email') !== null) {
        $updates[] = 'email = ?';
        $params[] = $request->input('email');
    }
    
    if (empty($updates)) {
        return Response::json(['error' => 'No fields to update'], 400);
    }
    
    $params[] = $id;
    $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return Response::json(['message' => 'User partially updated']);
};
```

### Example Usage

```bash
# Update only name
curl -X PATCH "http://localhost:8080/api/user?id=123" \
  -H "Content-Type: application/json" \
  -d '{"name": "John Patched"}'

# Update only email
curl -X PATCH "http://localhost:8080/api/user?id=123" \
  -H "Content-Type: application/json" \
  -d '{"email": "patched@example.com"}'
```

---

## Request Object API

Complete reference for the `$request` object available in all endpoints.

### Methods

```php
// HTTP Method
$request->method()  // Returns: 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'

// Path
$request->path()    // Returns: '/api/users'

// Query Parameters (URL parameters)
$request->query()              // Get all query params as array
$request->query('key')         // Get specific param
$request->query('key', 'default')  // Get param with default

// Input Data (POST/PUT/PATCH body)
$request->input()              // Get all input as array
$request->input('key')         // Get specific field
$request->input('key', 'default')  // Get field with default

// JSON Body
$request->json()               // Get parsed JSON as array

// Headers
$request->headers()            // Get all headers
$request->header('Content-Type')        // Get specific header
$request->header('X-Custom', 'default') // Get header with default
```

### Examples

```php
<?php
return function ($request, $db) {
    $method = $request->method();        // 'POST'
    $path = $request->path();            // '/api/users'
    $id = $request->query('id', 0);      // From ?id=123
    $name = $request->input('name');     // From POST body
    $allData = $request->input();        // All POST data
    $json = $request->json();            // Raw JSON array
    $contentType = $request->header('Content-Type');
    
    return [
        'method' => $method,
        'path' => $path,
        'query_id' => $id,
        'posted_name' => $name,
        'content_type' => $contentType
    ];
};
```

---

## Response Types

### JSON Response (Array)

```php
return [
    'status' => 'success',
    'data' => ['id' => 1, 'name' => 'John']
];
// Returns: HTTP 200 with application/json
```

### Text Response (String)

```php
return "Plain text response";
// Returns: HTTP 200 with text/plain
```

### Response Object (Full Control)

```php
// Success response
return Response::json(['data' => 'value'], 200);

// Error responses
return Response::json(['error' => 'Not found'], 404);
return Response::json(['error' => 'Unauthorized'], 401);
return Response::json(['error' => 'Bad request'], 400);

// Text response
return Response::text('Created successfully', 201);

// HTML response
return Response::html('<h1>Hello</h1>', 200);

// Redirect
return Response::redirect('/login', 302);
```

### Common HTTP Status Codes

```php
200  // OK - Success
201  // Created - Resource created
204  // No Content - Success but no response body
400  // Bad Request - Invalid input
401  // Unauthorized - Authentication required
403  // Forbidden - Not allowed
404  // Not Found - Resource doesn't exist
500  // Internal Server Error - Server error
```

---

## Complete Examples

### REST API for Users

**GET /api/users** - List all users
```php
<?php
return function ($request, $db) {
    $stmt = $db->query('SELECT * FROM users');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
};
```

**GET /api/users** - Get specific user
```php
<?php
return function ($request, $db) {
    $id = $request->query('id');
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: Response::json(['error' => 'Not found'], 404);
};
```

**POST /api/users** - Create user
```php
<?php
return function ($request, $db) {
    $name = $request->input('name');
    $email = $request->input('email');
    
    $stmt = $db->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
    $stmt->execute([$name, $email]);
    
    return Response::json(['id' => $db->lastInsertId()], 201);
};
```

**PUT /api/users** - Update user
```php
<?php
return function ($request, $db) {
    $id = $request->query('id');
    $stmt = $db->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
    $stmt->execute([$request->input('name'), $request->input('email'), $id]);
    
    return Response::json(['message' => 'Updated']);
};
```

**DELETE /api/users** - Delete user
```php
<?php
return function ($request, $db) {
    $id = $request->query('id');
    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    
    return Response::json(['message' => 'Deleted']);
};
```

---

## Tips & Best Practices

1. **Always validate input** - Check for required fields and data types
2. **Use prepared statements** - Never concatenate user input into SQL
3. **Return appropriate status codes** - 200 for success, 201 for created, 404 for not found, etc.
4. **Handle errors gracefully** - Return meaningful error messages
5. **Use query params for GET** - Use `$request->query()` for filtering/pagination
6. **Use body data for POST/PUT/PATCH** - Use `$request->input()` for data modifications
7. **Validate content type** - Check headers when expecting specific formats

---

For more examples, see the [Examples documentation](EXAMPLES.md).
