# Implementation Plan: 遊戲伺服器推廣平台 - 完整系統

**Branch**: `002-view-spekkit` | **Date**: 2025-10-08 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/002-view-spekkit/spec.md`

**Note**: This is a baseline documentation plan capturing the existing system architecture. Unlike typical implementation plans, this documents what has already been built rather than planning new development.

## Summary

This plan documents the complete architecture of the existing game server promotion platform, a multi-tenant SaaS system that enables game server owners to run promotional campaigns with automated reward distribution. The platform integrates user management, role-based access control, server registration/approval workflows, promotion tracking, and reward automation across a modern web stack (Nuxt.js 3 frontend + CodeIgniter 4 backend).

**Key Achievement**: Successfully implemented a production-ready platform with 98 functional requirements across 10 major feature areas, supporting 5-tier role hierarchy and automated promotion-to-reward workflows.

## Technical Context

**Language/Version**:
- Frontend: JavaScript/TypeScript with Node.js 18+ (Nuxt.js 3.x)
- Backend: PHP 8.1+ (CodeIgniter 4.x framework)

**Primary Dependencies**:
- Frontend: Nuxt.js 3, Vue 3, Pinia (state management), Tailwind CSS
- Backend: CodeIgniter 4, JWT-PHP (authentication), PHPMailer (notifications)
- Database: MySQL 8.0+ with MySQLi/PDO drivers
- Cache (planned): Redis for session storage and caching

**Storage**:
- Primary: MySQL 8.0+ relational database (13 core tables)
- File Storage: Local filesystem for uploads (logos, banners, backgrounds)
- Future: CDN integration for static assets

**Testing**:
- Backend: PHPUnit (planned, minimal coverage currently)
- Frontend: Vitest/Vue Test Utils (planned)
- Integration: Manual testing via API endpoints
- Contract: OpenAPI/Swagger documentation (to be formalized)

**Target Platform**:
- Deployment: Docker Compose (development + production)
- Web Server: Apache/Nginx with PHP-FPM
- Database Server: MySQL 8.0+ standalone or containerized
- Client: Modern web browsers (Chrome, Firefox, Safari, Edge - last 2 versions)
- Mobile: Responsive design supporting minimum 320px width

**Project Type**: Web application (frontend + backend separation)

**Performance Goals**:
- API Response: 90% of requests < 200ms
- Page Load: < 2 seconds for admin dashboard
- Concurrent Users: Support 1000 concurrent connections
- Database: Single query < 1 second execution time
- Click Tracking: Handle 1000 concurrent promotion clicks/sec
- Reward Processing: < 5 minutes from conversion to distribution (auto mode)

**Constraints**:
- Session Token: 1 hour validity (Access Token), 7 days (Refresh Token)
- File Upload: 5MB maximum per image
- API Rate Limit: 60 requests/minute per IP
- Data Retention: Promotion clicks 1 year, rewards永久保留
- Promotion Code: 32 characters unique code
- Database Encoding: UTF-8 for multilingual support
- Minimum Screen Width: 320px (mobile responsive)

**Scale/Scope**:
- Supported Servers: 100+ game servers
- User Base: Designed for 10,000+ active users
- Promotion Links: Unlimited active promotions
- Reward Records: Permanent storage with archival strategy
- API Endpoints: 80+ RESTful endpoints
- Database Tables: 13 core tables with foreign key relationships
- Frontend Pages: 20+ routes (public + admin)
- Frontend Components: 50+ Vue components

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**NOTE**: This project does not currently have a formalized constitution document. The constitution template at `.specify/memory/constitution.md` is unpopulated.

For baseline documentation purposes, we identify the **implicit architectural principles** observed in the codebase:

### Implicit Principles Detected

1. **API-First Architecture**
   - ✅ PASS: Clean separation between frontend (Nuxt.js) and backend (CodeIgniter 4) via RESTful APIs
   - ✅ PASS: All business logic encapsulated in backend controllers/models
   - ✅ PASS: Frontend consumes APIs exclusively, no direct database access

2. **Security by Default**
   - ✅ PASS: Multiple authentication layers (Session + JWT support)
   - ✅ PASS: Role-Based Access Control (RBAC) implemented
   - ✅ PASS: Input validation filters (InputValidationFilter.php)
   - ✅ PASS: SQL injection protection via prepared statements
   - ⚠️ PARTIAL: CORS control implemented but needs production configuration
   - ⚠️ PARTIAL: Rate limiting implemented but not fully enabled

3. **Data Integrity**
   - ✅ PASS: Soft delete implementation for critical entities (users, servers, promotions)
   - ✅ PASS: Foreign key constraints across all major tables
   - ✅ PASS: Audit trails (created_at, updated_at, deleted_at timestamps)
   - ✅ PASS: Transaction support for reward distribution

4. **Scalability Preparation**
   - ⚠️ PARTIAL: Redis caching architecture planned but not implemented
   - ✅ PASS: Database indexing on foreign keys and query-heavy columns
   - ✅ PASS: Pagination implemented on all list endpoints
   - ⚠️ PARTIAL: Background job processing needed for reward distribution

5. **Developer Experience**
   - ⚠️ PARTIAL: Git commit standards defined in CLAUDE.md but not enforced
   - ❌ NEEDS WORK: Automated testing coverage minimal
   - ❌ NEEDS WORK: API documentation incomplete (no OpenAPI spec)
   - ❌ NEEDS WORK: Development workflow documentation missing

### Constitution Compliance Status

**Overall: BASELINE ACCEPTABLE** - System demonstrates solid architectural foundations with clear areas for improvement.

**Critical Gaps Identified**:
1. Formalize constitution document with explicit principles
2. Implement comprehensive automated testing
3. Generate OpenAPI specifications for all API endpoints
4. Complete Redis caching layer
5. Document development workflows and coding standards

**Recommendation**: Create project constitution based on observed patterns before adding new features.

## Project Structure

### Documentation (this feature)

```
specs/002-view-spekkit/
├── spec.md              # Complete system specification (COMPLETED)
├── plan.md              # This file - implementation plan (IN PROGRESS)
├── research.md          # Technical decisions & rationale (Phase 0)
├── data-model.md        # Database schema documentation (Phase 1)
├── quickstart.md        # Development setup guide (Phase 1)
├── contracts/           # API contract specifications (Phase 1)
│   ├── auth.yaml        # Authentication endpoints
│   ├── users.yaml       # User management endpoints
│   ├── servers.yaml     # Server management endpoints
│   ├── promotions.yaml  # Promotion system endpoints
│   ├── rewards.yaml     # Reward system endpoints
│   └── statistics.yaml  # Statistics & analytics endpoints
└── checklists/
    └── requirements.md  # Specification quality checklist (COMPLETED)
```

### Source Code (repository root)

**Actual Structure** (Web application with frontend + backend):

```
promotion_optimize/
│
├── frontend/                    # Nuxt.js 3 Application
│   ├── app.vue                  # Root app component
│   ├── nuxt.config.ts           # Nuxt configuration
│   ├── tailwind.config.js       # Tailwind CSS config
│   │
│   ├── pages/                   # Route pages (auto-routing)
│   │   ├── index.vue            # Home page
│   │   ├── auth/
│   │   │   ├── login.vue        # User login
│   │   │   └── register.vue     # User registration
│   │   ├── admin/               # Admin area
│   │   │   ├── login.vue        # Admin login
│   │   │   ├── register.vue     # Admin registration
│   │   │   ├── dashboard.vue    # Admin dashboard
│   │   │   ├── promotions.vue   # Promotion management
│   │   │   └── tools.vue        # Admin tools
│   │   ├── servers/
│   │   │   ├── index.vue        # Server list
│   │   │   └── [server].vue     # Server detail (dynamic)
│   │   ├── promotions/
│   │   │   └── index.vue        # Promotion list
│   │   ├── dashboard/
│   │   │   └── analytics.vue    # Analytics dashboard
│   │   ├── settings/            # Settings pages
│   │   │   ├── index.vue        # Settings home
│   │   │   ├── users.vue        # User settings
│   │   │   ├── permissions.vue  # Permission settings
│   │   │   ├── theme.vue        # Theme settings
│   │   │   └── ui.vue           # UI settings
│   │   └── help/
│   │       └── index.vue        # Help center
│   │
│   ├── components/              # Vue components
│   │   ├── AppNavbar.vue        # Top navigation
│   │   ├── AppSidebar.vue       # Sidebar navigation
│   │   ├── AppFootbar.vue       # Footer
│   │   ├── SidebarMenuItem.vue  # Sidebar menu item
│   │   ├── AdminDashboard.vue   # Admin dashboard component
│   │   ├── charts/              # Chart components
│   │   ├── common/              # Common/shared components
│   │   ├── dashboard/           # Dashboard-specific components
│   │   ├── effects/             # Visual effects
│   │   ├── form/                # Form components
│   │   ├── guide/               # Guide/tutorial components
│   │   ├── home/                # Home page components
│   │   ├── layout/              # Layout components
│   │   ├── promotion/           # Promotion components
│   │   └── promotions/          # Promotion management components
│   │
│   ├── layouts/                 # Layout templates
│   │   ├── default.vue          # Default layout
│   │   └── admin.vue            # Admin layout
│   │
│   ├── stores/                  # Pinia state stores
│   │   ├── auth.js/ts           # Authentication state
│   │   ├── notifications.js     # Notification system
│   │   ├── settings.js          # UI settings & preferences
│   │   ├── sidebar.js           # Sidebar state
│   │   ├── theme.js             # Theme customization
│   │   ├── server.ts            # Server state
│   │   └── promotion.ts         # Promotion state
│   │
│   ├── middleware/              # Route middleware
│   │   └── auth.ts              # Authentication guard
│   │
│   ├── composables/             # Vue composables (planned)
│   ├── utils/                   # Utility functions (planned)
│   ├── assets/                  # Static assets
│   │   ├── css/                 # CSS files
│   │   └── images/              # Image assets
│   │
│   └── public/                  # Public static files
│
├── backend/                     # CodeIgniter 4 Application
│   ├── app/
│   │   ├── Config/              # Configuration files
│   │   │   ├── Routes.php       # API routes definition
│   │   │   ├── Database.php     # Database config
│   │   │   ├── Filters.php      # Filter configuration
│   │   │   └── App.php          # Application config
│   │   │
│   │   ├── Controllers/         # API Controllers
│   │   │   ├── API/
│   │   │   │   ├── AuthController.php          # Authentication endpoints
│   │   │   │   ├── UserController.php          # User management
│   │   │   │   ├── RoleController.php          # Role management
│   │   │   │   ├── PermissionController.php    # Permission management
│   │   │   │   ├── ServerController.php        # Server management
│   │   │   │   ├── PromotionController.php     # Promotion management
│   │   │   │   ├── RewardController.php        # Reward management
│   │   │   │   ├── StatisticsController.php    # Statistics & analytics
│   │   │   │   └── SystemController.php        # System utilities
│   │   │   └── BaseController.php              # Base controller
│   │   │
│   │   ├── Models/              # Database Models (ORM)
│   │   │   ├── UserModel.php                   # User entity
│   │   │   ├── RoleModel.php                   # Role entity
│   │   │   ├── PermissionModel.php             # Permission entity
│   │   │   ├── UserRoleModel.php               # User-Role junction
│   │   │   ├── RolePermissionModel.php         # Role-Permission junction
│   │   │   ├── ServerModel.php                 # Server entity
│   │   │   ├── ServerSettingsModel.php         # Server settings
│   │   │   ├── PromotionModel.php              # Promotion entity
│   │   │   ├── PromotionStatsModel.php         # Promotion statistics
│   │   │   ├── PromotionClickModel.php         # Click tracking
│   │   │   ├── RewardModel.php                 # Reward entity
│   │   │   ├── RewardSettingsModel.php         # Reward settings
│   │   │   └── UserSessionModel.php            # Session management
│   │   │
│   │   ├── Filters/             # Request/Response Filters
│   │   │   ├── JWTAuthFilter.php               # JWT authentication
│   │   │   ├── SessionAuthFilter.php           # Session authentication
│   │   │   ├── CORSFilter.php                  # CORS control
│   │   │   ├── RateLimitFilter.php             # Rate limiting
│   │   │   ├── InputValidationFilter.php       # Input sanitization
│   │   │   ├── RBACFilter.php                  # Permission check
│   │   │   ├── PromotionSecurityFilter.php     # Promotion security
│   │   │   └── PromotionTrackingFilter.php     # Click tracking
│   │   │
│   │   ├── Libraries/           # Service Classes
│   │   │   ├── JWTAuth.php                     # JWT token handling
│   │   │   ├── PromotionLinkGenerator.php      # Link generation
│   │   │   ├── PromotionTracker.php            # Tracking service
│   │   │   ├── PromotionCacheService.php       # Caching service
│   │   │   ├── RewardCalculator.php            # Reward calculation
│   │   │   ├── FileUploadService.php           # File handling
│   │   │   ├── DatabaseTestService.php         # DB testing
│   │   │   ├── SystemMonitorService.php        # Monitoring
│   │   │   ├── SecurityService.php             # Security utilities
│   │   │   └── ErrorHandler.php                # Error handling
│   │   │
│   │   ├── Database/            # Database Migrations & Seeds
│   │   │   ├── Migrations/      # Schema migrations (13 tables)
│   │   │   └── Seeds/           # Seed data
│   │   │
│   │   ├── Validation/          # Custom validation rules (planned)
│   │   └── Helpers/             # Helper functions (planned)
│   │
│   ├── public/                  # Web root
│   │   ├── index.php            # Entry point
│   │   ├── uploads/             # Uploaded files
│   │   │   ├── logos/           # Server logos
│   │   │   ├── backgrounds/     # Background images
│   │   │   └── banners/         # Banner images
│   │   ├── test-auth-flow.php   # Auth testing script
│   │   └── debug.php            # Debug utilities
│   │
│   ├── writable/                # Writable directory
│   │   ├── logs/                # Application logs
│   │   ├── cache/               # Cache files
│   │   ├── session/             # Session files
│   │   └── uploads/             # Temp uploads
│   │
│   └── storage/                 # Storage directory (custom)
│
├── docker-compose.yml           # Docker services config
├── .env.example                 # Environment template
├── .env                         # Environment variables (gitignored)
│
├── specs/                       # SpecKit documentation
│   └── 002-view-spekkit/        # This baseline spec
│
├── .specify/                    # SpecKit configuration
│   ├── templates/               # SpecKit templates
│   ├── scripts/                 # SpecKit scripts
│   └── memory/                  # SpecKit memory
│
├── .claude/                     # Claude Code configuration
│   ├── settings.local.json      # Local settings
│   └── commands/                # Custom slash commands
│
├── CLAUDE.md                    # Project instructions for Claude
├── README.md                    # Project README
└── docs/                        # Additional documentation
    └── prompts.md               # Prompt templates
```

**Structure Decision**:

This is a **Web Application (Option 2)** architecture with clean frontend/backend separation:

1. **Frontend (Nuxt.js 3)**:
   - Universal SSR/SPA capabilities
   - File-based routing in `/pages`
   - Component-based UI in `/components`
   - Centralized state management via Pinia stores
   - Responsive layouts for public and admin areas

2. **Backend (CodeIgniter 4)**:
   - RESTful API architecture
   - MVC pattern (Models, Controllers)
   - Service layer (Libraries) for business logic
   - Filter layer for cross-cutting concerns (auth, validation, security)
   - Database migrations for schema versioning

3. **Integration**:
   - Frontend consumes backend APIs exclusively
   - No direct database access from frontend
   - Authentication via Session tokens + JWT support
   - CORS configured for cross-origin requests

4. **Infrastructure**:
   - Docker Compose for local development
   - MySQL 8.0+ for data persistence
   - Local filesystem for file uploads (CDN-ready architecture)
   - Redis planned for caching and session storage

## Complexity Tracking

*Fill ONLY if Constitution Check has violations that must be justified*

**NOTE**: Since this is baseline documentation and no formal constitution exists yet, we identify architectural decisions that **would require justification** if a constitution were in place:

| Potential Violation | Why This Approach Was Used | Simpler Alternative Rejected Because |
|---------------------|---------------------------|-------------------------------------|
| Dual Authentication (Session + JWT) | Flexibility for different client types (web vs. mobile), gradual migration strategy | Pure JWT would break existing session-based frontend code; pure sessions limit future API integrations |
| Manual Reward Distribution Queue | Immediate functionality without queue infrastructure | Background job systems (Laravel Queue, Beanstalkd) add deployment complexity; manual marking suffices for current scale |
| Local File Storage | Zero external dependencies, simple deployment | CDN/S3 integration adds configuration complexity; local storage acceptable for 100 servers with 5MB limit |
| Missing Automated Tests | Rapid prototype-to-production timeline | TDD would have delayed feature delivery; manual testing covered critical paths; tests planned for Phase 2 |
| Soft Delete Everywhere | Data protection and audit trail requirements | Hard deletes irreversible; regulatory compliance unknown; soft delete safer default |
| Multi-Table Promotion Stats | Optimized query performance for analytics | Single table with JSON would make time-series queries slow; denormalization improves dashboard response time |

**Justification Summary**:

These architectural decisions prioritize **deployment simplicity** and **feature delivery speed** while maintaining **data safety** and **future extensibility**. All "violations" represent pragmatic trade-offs suitable for a platform at this maturity stage (70% feature completeness, production-ready MVP).

**Technical Debt Identified**:
1. Implement automated testing before adding complex features
2. Formalize API contracts with OpenAPI specifications
3. Complete Redis caching layer for production scale
4. Add background job processing for reward distribution
5. Implement proper logging and monitoring infrastructure

---

## Phase 0: Research & Technical Decisions

**Status**: ✅ **COMPLETED** (implicit - system already built)

Since this is baseline documentation, research.md will document **decisions already made** during development rather than conducting new research.

**Research Areas to Document**:
1. Framework Selection (Nuxt.js 3 + CodeIgniter 4)
2. Authentication Strategy (Session + JWT dual approach)
3. Database Schema Design (13-table normalized design)
4. File Upload Strategy (local filesystem vs CDN)
5. Caching Strategy (Redis architecture)
6. Promotion Tracking Implementation (fingerprinting + deduplication)
7. Reward Distribution Methods (auto/manual/api/database)
8. Role-Based Access Control Design (5-tier hierarchy)

See `research.md` for detailed documentation of all technical decisions.

## Phase 1: Design Artifacts

**Status**: PENDING (to be generated by this plan execution)

### Deliverables:

1. **data-model.md**: Complete database schema documentation
   - All 13 tables with field definitions
   - Foreign key relationships
   - Indexes and constraints
   - State machine diagrams for promotion/reward workflows

2. **contracts/** directory: OpenAPI 3.0 specifications
   - `auth.yaml`: Authentication endpoints
   - `users.yaml`: User management CRUD
   - `roles.yaml`: Role & permission management
   - `servers.yaml`: Server lifecycle & settings
   - `promotions.yaml`: Promotion creation & tracking
   - `rewards.yaml`: Reward processing & statistics
   - `statistics.yaml`: Analytics & reporting

3. **quickstart.md**: Developer onboarding guide
   - Local environment setup (Docker Compose)
   - Database migration and seeding
   - Frontend dev server startup
   - Backend API configuration
   - Common development tasks

4. **Agent Context Update**: Add project tech stack to Claude context

## Next Steps After This Plan

This plan execution will **STOP after Phase 1** as per SpecKit workflow. To continue:

1. **Review generated artifacts**: Check research.md, data-model.md, contracts/, quickstart.md
2. **Run `/speckit.tasks`**: Generate detailed implementation task breakdown
3. **Run `/speckit.implement`**: Execute tasks with AI assistance
4. **Iterate**: Use `/speckit.clarify` if requirements need refinement

**Current Plan Status**: Phase 0 and Phase 1 artifacts generation in progress...
