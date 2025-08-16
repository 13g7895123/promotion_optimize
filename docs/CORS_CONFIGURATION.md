# CORS Configuration Documentation

## Overview
This document explains the CORS (Cross-Origin Resource Sharing) configuration implemented in the promotion platform backend to resolve cross-origin issues between the frontend and backend in Docker environment.

## Current Configuration

### 1. Backend CORS Settings (App.php)
The backend is configured with the most permissive CORS settings for development:

```php
public array $corsConfig = [
    'allowedOrigins' => ['*'], // Allow all origins
    'allowedMethods' => ['*'], // Allow all methods
    'allowedHeaders' => ['*'], // Allow all headers
    'exposedHeaders' => ['*'], // Expose all headers
    'maxAge' => 86400,
    'supportsCredentials' => false, // Set to false when using wildcard
];
```

### 2. CORS Filter (CORSFilter.php)
- Applied globally to all requests as the first filter
- Handles preflight OPTIONS requests automatically
- Sets appropriate CORS headers based on configuration
- Supports wildcard configurations

### 3. Apache .htaccess
Additional CORS headers set at the Apache level as a fallback:
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: *`
- `Access-Control-Allow-Headers: *`
- `Access-Control-Expose-Headers: *`

## Why This Solves CORS Issues

### Problem
In Docker environments, the frontend and backend run on different containers/ports, causing browser CORS restrictions when making API calls.

### Solution
1. **Wildcard Origins (`*`)**: Allows requests from any origin, including Docker container internal networks
2. **All Methods**: Supports GET, POST, PUT, DELETE, OPTIONS, PATCH, HEAD
3. **All Headers**: Accepts any request headers, including custom API headers
4. **Preflight Handling**: Properly responds to OPTIONS preflight requests
5. **Early Processing**: CORS filter runs first to handle requests before other filters

## Testing CORS Configuration

### Test Endpoints
Three test endpoints have been created to verify CORS functionality:

1. **GET Test**: `GET /api/cors-test`
2. **POST Test**: `POST /api/cors-test`
3. **OPTIONS Test**: `OPTIONS /api/cors-test`

### Manual Testing
You can test CORS using browser DevTools or tools like curl:

```bash
# Test basic GET request
curl -H "Origin: http://localhost:9117" \
     -H "Access-Control-Request-Method: GET" \
     -H "Access-Control-Request-Headers: Content-Type" \
     -X OPTIONS \
     http://localhost:9017/api/cors-test

# Test POST with JSON
curl -H "Origin: http://localhost:9117" \
     -H "Content-Type: application/json" \
     -X POST \
     -d '{"test": "data"}' \
     http://localhost:9017/api/cors-test
```

### Browser Console Testing
```javascript
// Test from browser console
fetch('http://localhost:9017/api/cors-test', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
    }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('CORS Error:', error));
```

## Security Considerations

### Development vs Production
Current configuration is optimized for development with maximum permissiveness. For production:

1. **Specific Origins**: Replace `'*'` with specific allowed origins
2. **Limited Methods**: Only include necessary HTTP methods
3. **Specific Headers**: Define only required headers
4. **Enable Credentials**: If authentication is needed across origins

### Production Configuration Example
```php
public array $corsConfig = [
    'allowedOrigins' => [
        'https://yourdomain.com',
        'https://www.yourdomain.com'
    ],
    'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowedHeaders' => [
        'Content-Type', 
        'Authorization', 
        'X-Requested-With'
    ],
    'exposedHeaders' => [],
    'maxAge' => 86400,
    'supportsCredentials' => true,
];
```

## Troubleshooting

### Common Issues
1. **Still getting CORS errors**: Check browser cache, restart Docker containers
2. **Preflight failures**: Ensure OPTIONS method is handled
3. **Credentials issues**: Cannot use credentials with wildcard origins

### Debug Steps
1. Check browser DevTools Network tab for preflight requests
2. Verify CORS headers are present in response
3. Test with curl to isolate browser-specific issues
4. Check backend logs for filter execution

### Container Communication
In Docker environment, ensure:
- Frontend can reach backend via internal network (`http://nginx/api`)
- External ports are properly mapped
- No network isolation between containers

## Files Modified
- `backend/app/Config/App.php` - Main CORS configuration
- `backend/app/Filters/CORSFilter.php` - CORS filter logic
- `backend/app/Config/Filters.php` - Filter registration
- `backend/public/.htaccess` - Apache-level CORS headers
- `backend/app/Config/Routes.php` - Test endpoints
- `backend/app/Controllers/API/CorsTestController.php` - Test controller