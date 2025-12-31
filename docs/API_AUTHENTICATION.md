# API Key Authentication

Backender includes a complete API key authentication system to protect your custom endpoints from unauthorized access.

## 🔑 Features

- **API Key Generation** - Create secure, random API keys with custom labels
- **Per-Endpoint Authentication** - Choose which endpoints require authentication
- **Automatic Validation** - Keys are checked before endpoint logic executes
- **Usage Tracking** - See when each API key was last used
- **Key Revocation** - Delete keys instantly to revoke access
- **Multiple Authentication Methods** - Supports both `X-API-Key` header and `Authorization: Bearer` format

## Quick Start

### 1. Generate an API Key

1. Log in to the admin UI at `http://localhost:8080`
2. Click **API Keys** in the navigation
3. Click **Generate API Key**
4. Enter a label (e.g., "Mobile App", "Frontend", "Third Party")
5. Copy the generated key - **it won't be shown again!**

### 2. Enable Authentication on an Endpoint

1. Go to **Dashboard**
2. Edit an existing endpoint or create a new one
3. Check **"Require API Key Authentication"**
4. Save the endpoint

### 3. Make Authenticated Requests

```bash
# Using X-API-Key header
curl http://localhost:8080/api/your-endpoint \
  -H "X-API-Key: bk_abc123..."

# Using Authorization Bearer header
curl http://localhost:8080/api/your-endpoint \
  -H "Authorization: Bearer bk_abc123..."
```

## API Key Format

API keys use the format: `bk_` + 64 hexadecimal characters

Example: `bk_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2`

- **Prefix:** `bk_` identifies it as a Backender API key
- **Token:** 64 chars (32 bytes of random data, hex-encoded)
- **Cryptographically secure:** Generated using PHP's `random_bytes()`

## Authentication Behavior

### Public Endpoints (Default)
```php
<?php
// No authentication required
return function ($request) {
    return ['message' => 'Public endpoint'];
};
```

Anyone can access these endpoints without an API key.

### Protected Endpoints
When you enable **"Require API Key Authentication"** on an endpoint:

✅ **With Valid API Key:**
```bash
curl -H "X-API-Key: bk_valid_key..." http://localhost:8080/api/protected
# → 200 OK, endpoint executes normally
```

❌ **Without API Key or Invalid Key:**
```bash
curl http://localhost:8080/api/protected
# → 401 Unauthorized
# {
#   "error": "Unauthorized",
#   "message": "Valid API key required. Include \"X-API-Key\" header."
# }
```

## Usage Examples

### cURL

```bash
# GET request
curl http://localhost:8080/api/users \
  -H "X-API-Key: bk_your_api_key_here"

# POST request with JSON
curl -X POST http://localhost:8080/api/users \
  -H "X-API-Key: bk_your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{"name": "John Doe", "email": "john@example.com"}'

# Using Bearer token format
curl http://localhost:8080/api/users \
  -H "Authorization: Bearer bk_your_api_key_here"
```

### JavaScript (fetch)

```javascript
// GET request
const response = await fetch('http://localhost:8080/api/users', {
  headers: {
    'X-API-Key': 'bk_your_api_key_here'
  }
});
const data = await response.json();

// POST request
const response = await fetch('http://localhost:8080/api/users', {
  method: 'POST',
  headers: {
    'X-API-Key': 'bk_your_api_key_here',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'John Doe',
    email: 'john@example.com'
  })
});
```

### Python (requests)

```python
import requests

# GET request
headers = {'X-API-Key': 'bk_your_api_key_here'}
response = requests.get('http://localhost:8080/api/users', headers=headers)
data = response.json()

# POST request
payload = {'name': 'John Doe', 'email': 'john@example.com'}
response = requests.post(
    'http://localhost:8080/api/users',
    headers=headers,
    json=payload
)
```

### PHP (Guzzle)

```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'http://localhost:8080',
    'headers' => [
        'X-API-Key' => 'bk_your_api_key_here'
    ]
]);

// GET request
$response = $client->get('/api/users');
$data = json_decode($response->getBody(), true);

// POST request
$response = $client->post('/api/users', [
    'json' => [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]
]);
```

## Managing API Keys

### View All Keys

Navigate to **API Keys** in the admin panel to see:
- Key label
- Masked key (first 8 and last 8 characters)
- Last used timestamp
- Creation date

### Create New Key

1. Click **"Generate API Key"**
2. Enter a descriptive label
3. Copy the full key immediately (won't be shown again!)
4. Store it securely (password manager, environment variables, etc.)

### Delete Key

Click the delete button (🗑️) next to a key. This immediately revokes access - any requests using this key will be rejected with 401 Unauthorized.

## Security Best Practices

### ✅ Do's

- **Store keys securely** - Use environment variables, not hardcoded in code
- **Use descriptive labels** - "Mobile App v2.1", "Partner XYZ API", etc.
- **Rotate keys regularly** - Generate new keys and delete old ones periodically
- **One key per client** - Don't share the same key across multiple apps
- **Delete unused keys** - Revoke keys for decommissioned apps immediately
- **Use HTTPS in production** - Encrypt API keys in transit

### ❌ Don'ts

- **Don't commit keys to Git** - Add them to `.gitignore`
- **Don't expose keys in URLs** - Always use headers, never query params
- **Don't log keys** - Sanitize logs to prevent key leaks
- **Don't share keys publicly** - Treat them like passwords
- **Don't reuse deleted keys** - Each key is unique and disposable

## Environment Variables

For production applications, store API keys in environment variables:

### Node.js
```javascript
const apiKey = process.env.BACKENDER_API_KEY;

fetch('https://api.example.com/endpoint', {
  headers: { 'X-API-Key': apiKey }
});
```

### Python
```python
import os
api_key = os.getenv('BACKENDER_API_KEY')

requests.get('https://api.example.com/endpoint', 
             headers={'X-API-Key': api_key})
```

### PHP
```php
$apiKey = getenv('BACKENDER_API_KEY');

$client->request('GET', '/endpoint', [
    'headers' => ['X-API-Key' => $apiKey]
]);
```

### Docker
```bash
docker run -d \
  -e BACKENDER_API_KEY=bk_your_key_here \
  your-app:latest
```

## Database Schema

```sql
CREATE TABLE api_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT UNIQUE NOT NULL,
    label TEXT NOT NULL,
    last_used DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Endpoints table includes require_auth flag
CREATE TABLE endpoints (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    method TEXT NOT NULL,
    path TEXT NOT NULL,
    enabled INTEGER DEFAULT 1,
    require_auth INTEGER DEFAULT 0,  -- NEW: 0=public, 1=requires API key
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## Advanced Use Cases

### Mixed Authentication

Some endpoints can be public while others are protected:

- `/api/health` - Public, no auth required
- `/api/users` - Protected, requires API key
- `/api/admin` - Protected, requires API key

### Rate Limiting (Future Feature)

Track `last_used` timestamp to implement rate limiting:
- Allow X requests per minute per API key
- Block keys that exceed limits
- Alert on suspicious usage patterns

### Scoped Keys (Future Feature)

Create keys with specific permissions:
- Read-only keys (GET only)
- Write keys (POST/PUT/DELETE)
- Endpoint-specific keys

## Troubleshooting

### "Unauthorized" Error

**Problem:** Getting 401 response even with API key

**Solutions:**
1. Verify key is correct (no extra spaces)
2. Check header name is exactly `X-API-Key` (case-sensitive)
3. Confirm endpoint has "Require API Key Authentication" enabled
4. Test key hasn't been deleted in admin panel

### Key Not Working After Creation

**Problem:** New key returns 401

**Possible causes:**
1. Endpoint authentication not enabled - check the endpoint settings
2. Wrong header format - use `X-API-Key: bk_...` not `X-Api-Key` or `x-api-key`
3. Database issue - check container logs: `docker logs backender`

### Can't Find Generated Key

**Problem:** Forgot to copy key after generation

**Solution:** 
- Keys are only shown once for security
- Generate a new key and delete the old one
- Or check database directly:
```bash
docker exec -it backender sqlite3 /app/storage/database/backender.sqlite \
  "SELECT key FROM api_keys WHERE label = 'Your Label'"
```

## Testing Authentication

### Test Without Key (Should Fail)
```bash
curl http://localhost:8080/api/protected
# Expected: 401 Unauthorized
```

### Test With Valid Key (Should Succeed)
```bash
curl -H "X-API-Key: bk_your_key" http://localhost:8080/api/protected
# Expected: 200 OK + endpoint response
```

### Test With Invalid Key (Should Fail)
```bash
curl -H "X-API-Key: invalid_key" http://localhost:8080/api/protected
# Expected: 401 Unauthorized
```

## CORS with Authentication

API key authentication works seamlessly with CORS. The authentication check happens after CORS preflight:

1. Browser sends OPTIONS preflight → Backender responds with CORS headers
2. Browser sends actual request with `X-API-Key` → Backender validates key
3. If valid → Endpoint executes and returns data with CORS headers

```javascript
// Works from any frontend domain
fetch('http://localhost:8080/api/protected', {
  headers: {
    'X-API-Key': 'bk_your_key_here'
  }
})
  .then(res => res.json())
  .then(data => console.log(data));
```

## Migration Guide

### Existing Endpoints

After upgrading to this version:
- **All existing endpoints remain public** (backward compatible)
- **No breaking changes** - authentication is opt-in
- Enable authentication per-endpoint as needed

### Enabling Auth on Existing Endpoints

1. Go to Dashboard
2. Edit each endpoint you want to protect
3. Check "Require API Key Authentication"
4. Save changes
5. Generate API keys for your clients
6. Update client code to include API keys
