# Technical Research & Decisions: 遊戲伺服器推廣平台

**Project**: Game Server Promotion Platform
**Date**: 2025-10-08
**Status**: Baseline Documentation (Retroactive)
**Purpose**: Document technical decisions made during initial development

---

## Overview

This document captures the key technical decisions made during the development of the game server promotion platform. Unlike typical research documents that guide future implementation, this retroactively documents choices already embedded in the existing codebase.

## Decision Summary Table

| Decision Area | Choice Made | Status | Rationale |
|--------------|-------------|--------|-----------|
| Frontend Framework | Nuxt.js 3 | ✅ Implemented | SSR/SPA flexibility, Vue 3 ecosystem, file-based routing |
| Backend Framework | CodeIgniter 4 | ✅ Implemented | Lightweight PHP, excellent MySQL support, rapid development |
| Database | MySQL 8.0+ | ✅ Implemented | Relational data, ACID compliance, proven at scale |
| Authentication | Session + JWT Dual | ✅ Implemented | Migration flexibility, multi-client support |
| File Storage | Local Filesystem | ✅ Implemented | Simple deployment, CDN-ready architecture |
| Caching Layer | Redis | ⏳ Planned | Session storage, query caching, rate limiting |
| State Management | Pinia | ✅ Implemented | Official Vue 3 store, TypeScript support |
| CSS Framework | Tailwind CSS | ✅ Implemented | Utility-first, responsive design, customization |
| Testing Strategy | Manual + Planned Auto | ⚠️ Partial | Rapid delivery prioritized, automation planned for Phase 2 |

---

## 1. Frontend Framework Selection

### Decision: Nuxt.js 3 (Vue 3 Composition API)

**Rationale**:
- **Universal Rendering**: SSR for SEO on public pages, SPA for admin dashboard performance
- **Developer Experience**: File-based routing eliminates router configuration
- **Modern Stack**: Vue 3 Composition API provides better TypeScript support and code organization
- **Ecosystem**: Rich plugin ecosystem (Pinia for state, Tailwind for styling)
- **Community**: Active community, regular updates, excellent documentation

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **Next.js (React)** | Team expertise in Vue, React learning curve would delay delivery |
| **SvelteKit** | Smaller ecosystem, fewer third-party integrations for enterprise features |
| **Plain Vue 3 SPA** | Missing SSR for SEO, no built-in API route handling |
| **Laravel Blade (PHP)** | Lacks reactive UI capabilities, poor mobile experience |

**Implementation Details**:
- Nuxt 3.x with TypeScript support
- Composition API for complex components
- Auto-imports for components and composables
- Layouts system for public vs. admin areas
- Middleware for authentication guards

**Trade-offs Accepted**:
- **Pro**: Faster development, better UX, SEO-friendly
- **Con**: Node.js deployment adds infrastructure complexity
- **Mitigation**: Docker Compose handles multi-service orchestration

---

## 2. Backend Framework Selection

### Decision: CodeIgniter 4 (PHP 8.1+)

**Rationale**:
- **Lightweight & Fast**: Minimal overhead, excellent for API-only backend
- **MySQL-First**: Built-in query builder optimized for MySQL
- **Learning Curve**: Simple MVC pattern, easy for junior developers
- **Flexibility**: No strict conventions, adaptable to project needs
- **PHP Ecosystem**: Access to Composer packages (JWT, PHPMailer, etc.)

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **Laravel** | Too opinionated, excessive features for API-only use case, heavier footprint |
| **Symfony** | Steep learning curve, over-engineered for this project scale |
| **Slim Framework** | Too minimal, would require building too many utilities from scratch |
| **Express.js (Node)** | Adds second language to stack, team lacks Node.js expertise |
| **FastAPI (Python)** | Python environment unfamiliar to team, MySQL tooling less mature |

**Implementation Details**:
- RESTful API design pattern
- Filters for cross-cutting concerns (auth, CORS, validation)
- Models with CodeIgniter ORM (Query Builder)
- Libraries for business logic encapsulation
- Environment-based configuration

**Trade-offs Accepted**:
- **Pro**: Simple, fast, MySQL-optimized, team expertise
- **Con**: Smaller community than Laravel, fewer built-in features
- **Mitigation**: Composer packages fill gaps (JWT auth, testing)

---

## 3. Database Architecture

### Decision: MySQL 8.0+ with Normalized Relational Schema

**Rationale**:
- **Data Integrity**: Foreign keys enforce referential integrity across entities
- **ACID Transactions**: Critical for reward distribution (no duplicate rewards)
- **Query Optimization**: Excellent support for complex joins and indexes
- **Maturity**: Decades of production use, known performance characteristics
- **Tooling**: Rich ecosystem (phpMyAdmin, MySQL Workbench, migration tools)

**Schema Design Principles**:
1. **Normalized to 3NF**: Minimize redundancy while maintaining query performance
2. **Soft Deletes**: `deleted_at` column preserves audit trail
3. **Timestamps**: `created_at`, `updated_at` for all tables
4. **Foreign Keys**: Enforce relationships (user → server, promotion → server, reward → promotion)
5. **Indexes**: On all foreign keys and frequently queried columns

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **PostgreSQL** | Team lacks Postgres expertise, MySQL simpler for this use case |
| **MongoDB (NoSQL)** | Relational data structure (users ← roles → permissions) fits poorly in document model |
| **SQLite** | No concurrent write support, unsuitable for multi-user platform |
| **MariaDB** | Considered equivalent to MySQL, chosen for better documentation |

**Implementation Details**:
- 13 core tables with clear domain separation
- Many-to-many junctions (`user_roles`, `role_permissions`)
- JSON columns for flexible data (`server_settings.notification_settings`)
- Composite indexes for common query patterns

**Trade-offs Accepted**:
- **Pro**: Data safety, query power, transaction support
- **Con**: Scaling requires read replicas or sharding
- **Mitigation**: Redis caching layer (Phase 2) will reduce DB load

---

## 4. Authentication Strategy

### Decision: Dual Authentication (Session-Based + JWT)

**Rationale**:
- **Session Tokens**: Simpler for web frontend, automatic CSRF protection
- **JWT Tokens**: Stateless for mobile apps, API key usage, microservices
- **Migration Path**: Can gradually shift web app to JWT without breaking changes

**Authentication Flow**:

```
User Login → Verify Password → Generate Session Token + JWT Token → Store in user_sessions table
    ↓
Web Frontend: Uses Session Token (cookie-based)
    ↓
Mobile/API: Uses JWT Token (Authorization: Bearer header)
    ↓
Both Validated via SessionAuthFilter / JWTAuthFilter
```

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **Session-Only** | Limits mobile app integration, requires sticky sessions in load balancers |
| **JWT-Only** | Frontend needs significant refactor, CSRF protection more complex |
| **OAuth 2.0** | Over-engineered for first-party apps, external provider dependency |
| **Magic Links** | Poor UX for frequent logins, email delivery reliability issues |

**Implementation Details**:
- Session Token: 1 hour validity, stored in `user_sessions` table
- Refresh Token: 7 days validity, allows silent re-authentication
- JWT: HS256 algorithm, contains user_id and role information
- Filters: `SessionAuthFilter.php` and `JWTAuthFilter.php` handle validation
- Rotation: Tokens refreshed on activity to maintain sessions

**Trade-offs Accepted**:
- **Pro**: Maximum flexibility, supports web + mobile + API
- **Con**: Dual implementation complexity, database lookups for sessions
- **Mitigation**: Redis migration will make session lookups O(1)

---

## 5. Role-Based Access Control (RBAC)

### Decision: 5-Tier Role Hierarchy with Resource-Action Permissions

**Rationale**:
- **Multi-Tenancy**: Server owners can't access each other's data
- **Least Privilege**: Users only get permissions they need
- **Auditability**: Who did what tracked via assigned_by fields
- **Flexibility**: New roles can be added without code changes

**Role Hierarchy**:

```
1. Super Admin  (Level 1) → Full system access
2. Admin        (Level 2) → User & server management
3. Server Owner (Level 3) → Own servers only
4. Reviewer     (Level 4) → Approve/reject only
5. User         (Level 5) → Basic promotion participation
```

**Permission Model**:

```
Resource.Action pattern:
- users.create, users.read, users.update, users.delete
- servers.approve, servers.reject, servers.manage
- promotions.participate, promotions.review
- rewards.approve, rewards.distribute
```

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **Attribute-Based Access Control (ABAC)** | Too complex for current needs, over-engineered |
| **Simple Admin Flag** | Insufficient granularity, can't separate reviewer from server owner |
| **Group-Based Permissions** | Less flexible than roles, harder to audit |

**Implementation Details**:
- Many-to-many: Users ← UserRoles → Roles ← RolePermissions → Permissions
- `RBACFilter.php` checks permissions on every API request
- Permissions cached per user session to reduce DB queries
- Role assignment tracked with `assigned_by` for audit trail

**Trade-offs Accepted**:
- **Pro**: Granular control, audit trail, multi-tenancy support
- **Con**: Complex permission checks on every request
- **Mitigation**: Permission caching, efficient index queries

---

## 6. Promotion Tracking System

### Decision: Fingerprinting + IP-Based Deduplication

**Rationale**:
- **Fraud Prevention**: Prevent click inflation from repeated clicks
- **Accurate Metrics**: Unique visitor count reflects real reach
- **Privacy Balance**: No cookies required, GDPR-friendly
- **Performance**: Fast lookups via indexed queries

**Deduplication Strategy**:

```
Unique Visitor Fingerprint = MD5(IP + User Agent + Screen Resolution)
    ↓
Check if fingerprint exists in last 24 hours
    ↓
If NEW → Increment unique_click_count
If REPEAT → Increment total_click_count only
```

**Tracking Data Captured**:
- IP Address (for fraud detection)
- User Agent (device identification)
- Referrer URL (traffic source analysis)
- Timestamp (time-series analytics)
- Promotion Code (link attribution)

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **Cookie-Based Tracking** | Requires user consent (GDPR), easily cleared |
| **Browser LocalStorage** | Doesn't work across browsers/devices |
| **Third-Party Analytics (GA)** | Privacy concerns, data ownership issues |
| **Device Fingerprinting Libraries** | Too complex, browser blocking concerns |

**Implementation Details**:
- `promotion_clicks` table stores all click events
- Indexed on `(promotion_id, fingerprint, clicked_at)` for fast lookups
- Tracking pixel: 1x1 transparent GIF for immediate logging
- `PromotionTracker.php` service handles all tracking logic

**Trade-offs Accepted**:
- **Pro**: Accurate, privacy-friendly, performant
- **Con**: VPN/Tor users may be under-counted
- **Mitigation**: Fingerprint algorithm can be enhanced in future

---

## 7. Reward Distribution Architecture

### Decision: Multi-Method Distribution (Auto/Manual/API/Database)

**Rationale**:
- **Flexibility**: Different game servers have different capabilities
- **Reliability**: Fallback to manual if automation fails
- **Safety**: Approval workflow prevents fraudulent rewards
- **Scalability**: Direct DB writes fastest for high-volume

**Distribution Methods**:

```
1. AUTO (Database Direct)
   → Read server_settings.reward_table_mapping
   → Execute INSERT into game DB
   → Fastest, requires DB access

2. MANUAL (Admin Confirmation)
   → Admin marks reward as distributed
   → Safest, slowest, requires human verification

3. API (Webhook Call)
   → POST to game server API
   → Flexible, requires game server API support

4. HYBRID (Auto Approve + Manual Distribute)
   → Auto-approve based on rules
   → Admin manually marks distributed
   → Balance of automation and safety
```

**Approval Workflow**:

```
Promotion Converts → Create Reward (status: pending)
    ↓
If auto_approve=true → status: approved
If auto_approve=false → Wait for reviewer
    ↓
If distribution_method=auto → Execute DB write → status: distributed
If distribution_method=manual → Wait for admin → status: distributed
    ↓
If failed → status: failed, retry_count++, max 3 retries
```

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **Queue System (Laravel Queue)** | Adds infrastructure complexity (Redis/Beanstalkd), overkill for current scale |
| **Always Manual** | Defeats automation purpose, poor UX for high-volume servers |
| **Always Auto** | Safety risk, no human oversight for suspicious rewards |
| **Email Notification Only** | Doesn't actually distribute, just informs (not solving the problem) |

**Implementation Details**:
- `RewardModel.php` state machine: pending → approved → distributed / failed
- `RewardCalculator.php` library handles distribution logic
- Retry mechanism: 3 attempts with exponential backoff
- Error logging: Full stack trace captured for debugging

**Trade-offs Accepted**:
- **Pro**: Maximum flexibility, safety with automation
- **Con**: Complex state management, multiple code paths
- **Mitigation**: Comprehensive logging, clear state transitions

---

## 8. File Upload Strategy

### Decision: Local Filesystem with CDN-Ready Architecture

**Rationale**:
- **Simplicity**: Zero external dependencies for initial deployment
- **Cost**: No S3/CDN costs during MVP phase
- **Performance**: Local reads are fast enough for 100 servers
- **Migration Path**: File paths abstracted for easy CDN migration

**File Organization**:

```
backend/public/uploads/
├── logos/           # Server logos (max 5MB)
├── backgrounds/     # Background images (max 5MB)
└── banners/         # Banner images (max 5MB each)
```

**Upload Flow**:

```
User uploads file via frontend
    ↓
POST /api/servers/:id/upload-logo
    ↓
FileUploadService validates: size, type, dimensions
    ↓
Save to /uploads/{type}/{server_id}_{timestamp}.{ext}
    ↓
Update server record with file path
    ↓
Return public URL: /uploads/logos/server_123_1234567890.jpg
```

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **AWS S3** | Adds AWS dependency, configuration complexity, monthly costs |
| **Cloudinary** | Third-party service lock-in, API rate limits |
| **Database BLOBs** | Poor performance, bloats database backups |
| **Separate File Server** | Over-engineered for current scale |

**Implementation Details**:
- `FileUploadService.php` handles validation and storage
- Allowed types: jpg, png, gif
- Max size: 5MB per file
- File naming: `{type}_{server_id}_{timestamp}.{ext}` prevents collisions
- Validation: Image dimensions, MIME type verification

**CDN Migration Path** (Future):
1. Implement `StorageInterface` abstraction
2. Add `S3StorageAdapter` and `LocalStorageAdapter`
3. Switch via environment variable
4. Migrate existing files via batch script

**Trade-offs Accepted**:
- **Pro**: Simple, fast, no external dependencies
- **Con**: Not scalable to thousands of servers, no global CDN
- **Mitigation**: CDN migration path planned, architecture supports it

---

## 9. Caching Strategy

### Decision: Redis for Session Storage and Query Caching (Planned)

**Current Status**: ⏳ Architecture planned but not yet implemented

**Rationale**:
- **Session Storage**: Move from DB to Redis for O(1) lookups
- **Query Caching**: Cache expensive aggregation queries (statistics, leaderboards)
- **Rate Limiting**: Store API request counts per IP
- **Performance**: Sub-millisecond cache lookups vs. 10-50ms DB queries

**Planned Cache Layers**:

```
Layer 1: Session Storage
- user_sessions migrated to Redis
- TTL matches token expiry (1 hour access, 7 days refresh)

Layer 2: Query Results
- Dashboard statistics (10-minute TTL)
- Leaderboards (1-hour TTL)
- Server lists (5-minute TTL)

Layer 3: Rate Limiting
- API request counts per IP (1-minute sliding window)
- Promotion click deduplication (24-hour TTL)

Layer 4: Application State
- Permission cache per user (session lifetime)
- Server settings cache (30-minute TTL)
```

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **Memcached** | No data persistence, lacks advanced data structures (sets, sorted sets) |
| **In-Memory PHP Arrays** | Lost on process restart, not shared across requests |
| **Database Query Cache** | MySQL query cache deprecated in 8.0, less flexible |
| **APCu (PHP Cache)** | Not shared across PHP-FPM processes, limited to single server |

**Implementation Plan** (Phase 2):
1. Add Redis container to docker-compose.yml
2. Install `phpredis` extension
3. Create `CacheService.php` abstraction layer
4. Migrate session storage: `SessionAuthFilter.php` updates
5. Add query caching: `StatisticsController.php` updates
6. Implement rate limiting: `RateLimitFilter.php` updates

**Trade-offs Accepted**:
- **Pro**: Massive performance improvement (10-50ms → <1ms)
- **Con**: Adds infrastructure complexity, cache invalidation complexity
- **Mitigation**: Well-tested library (phpredis), fallback to DB if Redis unavailable

---

## 10. State Management (Frontend)

### Decision: Pinia for Centralized State

**Rationale**:
- **Official Solution**: Recommended by Vue team, first-class support
- **TypeScript**: Full type safety with auto-completion
- **Composition API**: Aligns with Vue 3 Composition API patterns
- **DevTools**: Excellent debugging with Vue DevTools integration
- **Lightweight**: Minimal overhead compared to Vuex

**Store Organization**:

```
stores/
├── auth.js          # User authentication state (login, logout, session)
├── server.ts        # Server list, current server, server filters
├── promotion.ts     # Promotion list, active promotion, analytics
├── notifications.js # Toast messages, system alerts
├── settings.js      # UI preferences, theme, language
├── sidebar.js       # Sidebar collapse state, active menu
└── theme.js         # Dark mode, color customization
```

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **Vuex 4** | More verbose, mutation boilerplate, Pinia is successor |
| **No State Management (Composables Only)** | State duplication across components, hard to debug |
| **Component Props/Emit** | Prop drilling hell for deeply nested components |
| **Browser LocalStorage** | Not reactive, manual sync required |

**Implementation Details**:
- Composition API syntax (`setup()` pattern)
- Persisted state for auth tokens (localStorage)
- Hydration on page load for SSR compatibility
- Actions for async API calls
- Getters for computed derived state

**Trade-offs Accepted**:
- **Pro**: Clean API, TypeScript support, official recommendation
- **Con**: Learning curve for developers familiar with Vuex
- **Mitigation**: Excellent documentation, simpler than Vuex overall

---

## 11. Testing Strategy

### Decision: Manual Testing with Planned Automation (Phase 2)

**Current Status**: ⚠️ Partial - Manual testing only, automation planned

**Rationale**:
- **Time Constraints**: Automated testing would delay MVP launch
- **Complex Setup**: Frontend + Backend + Database + Redis requires complex test environment
- **Manual Coverage**: Critical paths tested manually before deployment
- **Planned Investment**: Automation planned for Phase 2 after product-market fit

**Current Testing Approach**:

```
Manual Testing:
✅ API endpoint testing via Postman collections
✅ Frontend flow testing (register → login → create promotion → view analytics)
✅ Permission testing (different role logins)
✅ Database integrity checks (foreign keys, soft deletes)
✅ File upload testing (size limits, type validation)

Planned Automation (Phase 2):
⏳ Backend unit tests (PHPUnit)
⏳ API integration tests (REST contract tests)
⏳ Frontend component tests (Vitest + Vue Test Utils)
⏳ E2E tests (Cypress or Playwright)
```

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **TDD from Day 1** | Would delay feature delivery, premature for MVP validation |
| **Only E2E Tests** | Slow, brittle, doesn't cover edge cases well |
| **No Testing at All** | Unacceptable risk for production, manual testing is minimum |

**Future Test Coverage Goals**:

```
Phase 2 Target: 70% code coverage

Backend (PHPUnit):
- Model tests (validation, relationships)
- Service tests (PromotionTracker, RewardCalculator)
- Controller tests (API responses, error handling)

Frontend (Vitest):
- Component tests (props, events, slots)
- Composable tests (business logic)
- Store tests (Pinia actions, getters)

Integration (Postman/Newman):
- API contract tests (request/response schemas)
- Authentication flows
- Permission boundaries

E2E (Cypress):
- Critical user journeys
- Cross-browser compatibility
```

**Trade-offs Accepted**:
- **Pro**: Faster MVP delivery, focus on user feedback
- **Con**: Higher risk of regressions, manual testing bottleneck
- **Mitigation**: Comprehensive logging, error monitoring (Sentry planned), manual QA checklist

---

## 12. Development Workflow

### Decision: Git Flow with Feature Branches

**Current Status**: ✅ Implemented (partially documented)

**Branch Strategy**:

```
master       → Production-ready code
develop      → Integration branch (currently unused)
feature/*    → Feature development branches
hotfix/*     → Emergency production fixes
```

**Commit Convention** (From CLAUDE.md):

```
Format: <type>: <description>

Types:
- feat: New feature
- fix: Bug fix
- refactor: Code refactoring
- docs: Documentation changes
- style: Code style changes (formatting)
- test: Test additions/changes

Example:
feat: 新增推廣連結 QR Code 生成功能
fix: 修正獎勵重複發放問題
docs: 更新 API 文檔
```

**Deployment Workflow**:

```
Development:
1. Work on feature/* branch
2. Commit following convention (NO "Generated with Claude Code" footer)
3. Local testing via Docker Compose
4. Merge to master when ready

Production:
1. Pull latest master
2. docker-compose up -d --build
3. Run database migrations
4. Test deployment
```

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **Trunk-Based Development** | Risk of breaking main branch, team lacks CI/CD maturity |
| **GitHub Flow (main only)** | Too simple, no staging environment distinction |
| **GitLab Flow (environment branches)** | Over-engineered for single deployment target |

**Trade-offs Accepted**:
- **Pro**: Clear separation of features, safe experimentation
- **Con**: Merge overhead, stale branches if not cleaned up
- **Mitigation**: Regular branch cleanup, short-lived feature branches

---

## 13. API Design Principles

### Decision: RESTful API with JSON Responses

**Rationale**:
- **Industry Standard**: REST is widely understood and tooled
- **Stateless**: Each request contains all needed information
- **HTTP Semantics**: Proper use of GET/POST/PUT/DELETE verbs
- **JSON**: Universal format, easy to parse in any language

**API Conventions**:

```
Resource Naming: Plural nouns
✅ /api/servers
✅ /api/servers/:id/promotions
❌ /api/getServers (RPC-style)

HTTP Methods:
GET    → Read resource(s)
POST   → Create resource
PUT    → Update resource (full replacement)
PATCH  → Update resource (partial)
DELETE → Delete resource (soft delete)

Response Format:
{
  "success": true,
  "data": {...},
  "message": "Success message"
}

Error Format:
{
  "success": false,
  "error": "Error message",
  "code": "ERROR_CODE"
}
```

**Pagination**:

```
GET /api/servers?page=1&limit=20

Response:
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 95,
    "items_per_page": 20
  }
}
```

**Alternatives Considered**:

| Alternative | Why Rejected |
|------------|--------------|
| **GraphQL** | Over-engineered for current needs, frontend doesn't need flexible queries |
| **RPC (JSON-RPC/gRPC)** | Less intuitive than REST, smaller ecosystem |
| **SOAP** | Outdated, XML overhead, poor developer experience |

**Implementation Details**:
- All endpoints return JSON (never HTML)
- Consistent error handling via `ErrorHandler.php`
- HTTP status codes: 200 (success), 400 (bad request), 401 (unauthorized), 404 (not found), 500 (server error)
- CORS headers for cross-origin frontend access

**Trade-offs Accepted**:
- **Pro**: Simple, standard, great tooling (Postman, Swagger)
- **Con**: Over-fetching/under-fetching (no field selection like GraphQL)
- **Mitigation**: Tailored endpoints for specific use cases (e.g., `/servers/:id/analytics`)

---

## Decision Log

| Date | Decision | Impact | Status |
|------|----------|--------|--------|
| 2024-01 | Nuxt.js 3 frontend | High | ✅ Implemented |
| 2024-01 | CodeIgniter 4 backend | High | ✅ Implemented |
| 2024-01 | MySQL 8.0 database | High | ✅ Implemented |
| 2024-02 | Session + JWT auth | Medium | ✅ Implemented |
| 2024-02 | 5-tier RBAC | High | ✅ Implemented |
| 2024-03 | Local file storage | Low | ✅ Implemented |
| 2024-03 | Fingerprint-based tracking | Medium | ✅ Implemented |
| 2024-04 | Multi-method reward distribution | High | ✅ Implemented |
| 2024-Q2 | Redis caching | Medium | ⏳ Planned |
| 2024-Q3 | Automated testing | Medium | ⏳ Planned |
| 2024-Q4 | OpenAPI documentation | Low | ⏳ Planned |

---

## Technical Debt Backlog

### High Priority
1. **Automated Testing**: Add PHPUnit (backend) + Vitest (frontend) + E2E tests
2. **Redis Caching**: Implement session storage and query caching
3. **OpenAPI Specs**: Formalize API contracts for all endpoints
4. **Error Monitoring**: Integrate Sentry or similar error tracking

### Medium Priority
5. **Background Jobs**: Queue system for reward distribution (Laravel Queue or Beanstalkd)
6. **CDN Integration**: Migrate file uploads to S3 + CloudFront
7. **Rate Limiting**: Enable and tune RateLimitFilter.php
8. **Logging Infrastructure**: Centralized logging (ELK stack or CloudWatch)

### Low Priority
9. **Websockets**: Real-time notifications for promotion conversions
10. **Microservices**: Extract reward distribution into separate service (if scaling needed)
11. **GraphQL**: Explore if frontend needs more flexible data fetching
12. **i18n**: Full multilingual support (currently partial)

---

## Lessons Learned

### What Worked Well
1. **Nuxt.js + CodeIgniter separation**: Clean boundaries, independent scaling
2. **Soft deletes everywhere**: Saved multiple times from accidental data loss
3. **Dual authentication**: Proved valuable when adding mobile API support
4. **Normalized database**: Complex queries easier than expected with proper indexes

### What Could Be Improved
1. **Delayed automated testing**: Manual testing is bottleneck, regressions harder to catch
2. **No API versioning**: Breaking changes require careful coordination
3. **Missing documentation**: Onboarding new developers is slow
4. **Over-reliance on JSON columns**: Some `server_settings` queries are inefficient

### Recommendations for Future Projects
1. Start with OpenAPI spec before writing code
2. Set up CI/CD pipeline on day 1
3. Write tests for complex business logic (RewardCalculator, PromotionTracker) early
4. Document architecture decisions in real-time (not retroactively like this)

---

## References

- [Nuxt.js 3 Documentation](https://nuxt.com/docs)
- [CodeIgniter 4 User Guide](https://codeigniter.com/user_guide/)
- [MySQL 8.0 Reference Manual](https://dev.mysql.com/doc/refman/8.0/en/)
- [Pinia Documentation](https://pinia.vuejs.org/)
- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [JWT Introduction](https://jwt.io/introduction)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)

---

**Document Status**: ✅ Complete
**Next Steps**: Proceed to Phase 1 design artifacts (data-model.md, contracts/, quickstart.md)
