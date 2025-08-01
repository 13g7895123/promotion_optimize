# Game Server Promotion Platform - Backend API

## Overview

This is a comprehensive backend API for managing game server promotions, built with CodeIgniter 4.x framework. The platform provides a robust foundation for server owners to promote their games, manage users, and track promotional activities.

## Architecture

### Technology Stack
- **Framework**: CodeIgniter 4.x
- **Database**: MySQL 8.0+
- **Cache**: Redis
- **Authentication**: JWT (JSON Web Tokens)
- **API Standard**: RESTful API with OpenAPI 3.0 documentation

### Core Features
- JWT-based authentication with refresh tokens
- Role-based access control (RBAC) with 5 permission levels
- Comprehensive input validation and security measures
- Rate limiting and API security headers
- Database migrations and seeders
- Session management with device tracking
- Server management and approval workflow

## Permission System

The platform implements a 5-level hierarchical permission system:

1. **Super Admin** (Level 1) - Full system access
2. **Admin** (Level 2) - User and server management
3. **Server Owner** (Level 3) - Own server management
4. **Reviewer** (Level 4) - Server approval/rejection
5. **User** (Level 5) - Basic access and participation

## Security Features

### Authentication
- JWT tokens with configurable expiration
- Refresh token mechanism
- Session tracking with device information
- Failed login attempt protection
- Account locking mechanism

### Input Security
- SQL injection prevention patterns
- XSS protection filters
- CSRF protection for stateful operations
- File upload security validation
- Rate limiting per endpoint and user

### API Security
- CORS configuration
- Security headers (X-Frame-Options, X-XSS-Protection, etc.)
- Input sanitization and validation
- Request size limits

## Database Schema

### Core Tables
- `users` - User accounts and profiles
- `roles` - Permission roles
- `permissions` - Individual permissions
- `user_roles` - User-role assignments
- `role_permissions` - Role-permission mappings
- `servers` - Game server registrations
- `server_settings` - Server configuration
- `user_sessions` - Active user sessions

## API Documentation

### OpenAPI Specification
The complete API documentation is available in OpenAPI 3.0 format at:
- File: `docs/api/openapi.yaml`
- Swagger UI: Access via `/docs` endpoint (when implemented)

### Authentication Flow
1. **Registration**: `POST /api/auth/register`
2. **Login**: `POST /api/auth/login`
3. **Token Refresh**: `POST /api/auth/refresh`
4. **Logout**: `POST /api/auth/logout`

### Key Endpoints

#### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `POST /api/auth/refresh` - Refresh JWT token
- `POST /api/auth/logout` - User logout
- `GET /api/auth/profile` - Get user profile
- `PUT /api/auth/profile` - Update user profile
- `POST /api/auth/change-password` - Change password

#### User Management (Admin only)
- `GET /api/users` - List users with pagination
- `GET /api/users/{id}` - Get user details
- `POST /api/users` - Create new user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user
- `POST /api/users/{id}/roles` - Assign role to user
- `DELETE /api/users/{id}/roles/{roleId}` - Remove role from user

#### Server Management
- `GET /api/servers` - List servers with filters
- `GET /api/servers/{id}` - Get server details
- `POST /api/servers` - Register new server
- `PUT /api/servers/{id}` - Update server
- `DELETE /api/servers/{id}` - Delete server
- `POST /api/servers/{id}/approve` - Approve server (Reviewer+)
- `POST /api/servers/{id}/reject` - Reject server (Reviewer+)

#### System (Admin only)
- `GET /api/health` - System health check
- `GET /api/stats` - System statistics

## Configuration

### Environment Variables
```env
DB_HOST=localhost
DB_USER=promotion_user
DB_PASS=promotion_pass
DB_NAME=promotion_platform
DB_PORT=3306

REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0
```

### JWT Configuration
```php
'jwtConfig' => [
    'secretKey' => 'your-256-bit-secret-key-here-change-in-production',
    'algorithm' => 'HS256',
    'expiration' => 3600, // 1 hour
    'refreshExpiration' => 604800, // 1 week
],
```

### Rate Limiting
```php
'rateLimiting' => [
    'enabled' => true,
    'requests' => 100,
    'window' => 3600, // 1 hour
],
```

## Deployment

### Development Setup
1. Install dependencies: `composer install`
2. Configure database in `.env`
3. Run migrations: `php spark migrate`
4. Start development server: `php spark serve`

### Production Considerations
- Use HTTPS in production
- Configure proper JWT secret key
- Set up Redis for caching and sessions
- Configure proper CORS origins
- Enable production logging
- Set up monitoring and health checks

## Models and Relationships

### UserModel
- User account management
- Role and permission queries
- Login attempt tracking
- Password management

### RoleModel
- Role hierarchy management
- Permission assignment
- User role queries

### ServerModel
- Server registration and management
- Approval workflow
- Owner verification

### UserSessionModel
- Session tracking
- Device information
- Security monitoring

## Filters and Middleware

### JWTAuthFilter
- Token validation
- User payload injection
- Session verification

### RBACFilter
- Permission checking
- Role hierarchy enforcement
- Resource ownership validation

### RateLimitFilter
- Request rate limiting
- IP and user-based limits
- Endpoint-specific limits

### InputValidationFilter
- SQL injection detection
- XSS prevention
- File upload validation

### CORSFilter
- Cross-origin request handling
- Preflight request support

## Testing

### Unit Tests
Run unit tests with: `php spark test`

### API Testing
Use the OpenAPI specification with tools like:
- Postman
- Insomnia
- Swagger UI
- curl commands

## Monitoring and Logging

### Health Checks
- Database connectivity
- Cache system status
- File system permissions
- Memory usage monitoring

### Statistics
- User activity metrics
- Server registration stats
- Session analytics
- Performance metrics

## Contributing

1. Follow CodeIgniter 4 coding standards
2. Use SOLID principles
3. Write comprehensive tests
4. Update API documentation
5. Follow security best practices

## License

MIT License - see LICENSE file for details.

## Support

For technical support or questions:
- Documentation: This README and OpenAPI spec
- Issues: Create GitHub issue
- Security: Report via private communication

---

**Note**: This is the Phase 1 implementation focusing on core authentication, user management, and server registration. Additional features like promotion campaigns, reward systems, and activity tracking will be implemented in subsequent phases.