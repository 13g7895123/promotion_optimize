# Tasks: 遊戲伺服器推廣平台 - 完整系統基線文檔

**Feature Branch**: `002-view-spekkit`
**Created**: 2025-10-08
**Status**: Baseline Documentation Project
**Type**: Documenting EXISTING Implementation

**Input**: Design documents from `/home/jarvis/project/idea/promotion_optimize/specs/002-view-spekkit/`
- spec.md (10 user stories with P1/P2 priorities)
- plan.md (tech stack and architecture)
- data-model.md (13 database tables)
- contracts/*.yaml (7 API contract files)
- research.md (technical decisions)

---

## CRITICAL: This is a DOCUMENTATION Project

**PURPOSE**: Document the EXISTING game server promotion platform implementation, NOT build from scratch.

All tasks should be phrased as:
- ✅ "Document existing X implementation"
- ✅ "Verify X functionality works as documented"
- ✅ "Add tests for existing X feature"
- ❌ NOT "Implement X" (it's already implemented!)

---

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- All paths are ABSOLUTE paths from repository root

## Path Conventions (Web Application)

- **Backend**: `/home/jarvis/project/idea/promotion_optimize/backend/`
- **Frontend**: `/home/jarvis/project/idea/promotion_optimize/frontend/`
- **Specs**: `/home/jarvis/project/idea/promotion_optimize/specs/002-view-spekkit/`
- **Docs**: `/home/jarvis/project/idea/promotion_optimize/docs/`

---

## Phase 1: Setup (Documentation Infrastructure)

**Purpose**: Initialize documentation structure and baseline verification

- [ ] T001 [P] Verify project structure matches plan.md architecture diagram
- [ ] T002 [P] Document environment setup in specs/002-view-spekkit/quickstart.md
- [ ] T003 [P] Verify Docker Compose configuration is functional (docker-compose.yml)
- [ ] T004 [P] Document database migration process in quickstart.md
- [ ] T005 Verify all 13 database tables exist and match data-model.md schema

**Checkpoint**: ✅ Development environment documented and reproducible

---

## Phase 2: Foundational (Core Infrastructure Documentation)

**Purpose**: Document the EXISTING core infrastructure that all features depend on

**⚠️ CRITICAL**: This documents the foundation that was built FIRST - authentication, database, and basic API structure

### Database Schema Documentation

- [ ] T006 [P] [FOUNDATION] Document users table structure and verify against backend/app/Database/Migrations/
- [ ] T007 [P] [FOUNDATION] Document roles table and 5-tier role hierarchy (super_admin, admin, server_owner, reviewer, user)
- [ ] T008 [P] [FOUNDATION] Document permissions table and resource-action model
- [ ] T009 [P] [FOUNDATION] Document user_roles junction table and role assignment logic
- [ ] T010 [P] [FOUNDATION] Document role_permissions junction table and permission inheritance

### Authentication Framework Documentation

- [ ] T011 [P] [FOUNDATION] Document Session-based authentication in backend/app/Filters/SessionAuthFilter.php
- [ ] T012 [P] [FOUNDATION] Document JWT authentication in backend/app/Filters/JWTAuthFilter.php and backend/app/Libraries/JWTAuth.php
- [ ] T013 [P] [FOUNDATION] Document user_sessions table and token management (access_token, refresh_token)
- [ ] T014 [P] [FOUNDATION] Verify authentication endpoints match contracts/auth.yaml
- [ ] T015 [FOUNDATION] Document login flow: password validation → token generation → session storage

### Authorization Framework Documentation

- [ ] T016 [P] [FOUNDATION] Document RBACFilter.php permission checking logic
- [ ] T017 [P] [FOUNDATION] Document permission caching strategy (planned Redis integration)
- [ ] T018 [P] [FOUNDATION] Verify role-based access control in backend/app/Controllers/API/
- [ ] T019 [FOUNDATION] Create permission matrix: roles vs. resources (users, servers, promotions, rewards)

### Base API Structure Documentation

- [ ] T020 [P] [FOUNDATION] Document API routing configuration in backend/app/Config/Routes.php
- [ ] T021 [P] [FOUNDATION] Document CORS configuration in backend/app/Filters/CORSFilter.php
- [ ] T022 [P] [FOUNDATION] Document input validation in backend/app/Filters/InputValidationFilter.php
- [ ] T023 [P] [FOUNDATION] Document error handling in backend/app/Libraries/ErrorHandler.php
- [ ] T024 [P] [FOUNDATION] Document rate limiting architecture in backend/app/Filters/RateLimitFilter.php (planned)

### Base Frontend Structure Documentation

- [ ] T025 [P] [FOUNDATION] Document Nuxt.js configuration in frontend/nuxt.config.ts
- [ ] T026 [P] [FOUNDATION] Document Pinia stores structure: frontend/stores/auth.js, server.ts, promotion.ts
- [ ] T027 [P] [FOUNDATION] Document authentication middleware in frontend/middleware/auth.ts
- [ ] T028 [P] [FOUNDATION] Document layout system: frontend/layouts/default.vue and frontend/layouts/admin.vue
- [ ] T029 [P] [FOUNDATION] Document Tailwind CSS configuration in frontend/tailwind.config.js

**Checkpoint**: ✅ Core infrastructure fully documented - ready for user story documentation

---

## Phase 3: User Story 1 - 使用者註冊與身份管理 (Priority: P1) 🎯 MVP

**Goal**: 使用者能夠自主註冊帳號、登入系統、管理個人資料，系統根據角色提供相應權限

**Independent Test**: 註冊新帳號 → 登入 → 查看個人資料 → 更新資料 → 登出 → 驗證帳號鎖定機制

### Backend Models Documentation (US1)

- [ ] T030 [P] [US1] Document UserModel.php: fields, validation rules, password hashing (bcrypt)
- [ ] T031 [P] [US1] Document UserSessionModel.php: token lifecycle, expiry handling
- [ ] T032 [P] [US1] Verify soft delete implementation (deleted_at column) in users table

### Backend Services Documentation (US1)

- [ ] T033 [US1] Document user registration logic in backend/app/Controllers/API/AuthController.php::register()
- [ ] T034 [US1] Document login validation and session creation in AuthController.php::login()
- [ ] T035 [US1] Document token refresh mechanism in AuthController.php::refresh()
- [ ] T036 [US1] Document logout and session cleanup in AuthController.php::logout()
- [ ] T037 [US1] Document account locking logic (5 failed attempts → 30 min lock) in UserModel.php

### API Endpoints Documentation (US1)

- [ ] T038 [P] [US1] Verify POST /api/auth/register matches contracts/auth.yaml
- [ ] T039 [P] [US1] Verify POST /api/auth/login matches contracts/auth.yaml
- [ ] T040 [P] [US1] Verify POST /api/auth/refresh matches contracts/auth.yaml
- [ ] T041 [P] [US1] Verify POST /api/auth/logout matches contracts/auth.yaml
- [ ] T042 [P] [US1] Verify GET /api/users/profile matches contracts/users.yaml
- [ ] T043 [P] [US1] Verify PUT /api/users/profile matches contracts/users.yaml

### Frontend Components Documentation (US1)

- [ ] T044 [P] [US1] Document auth/register.vue registration form and validation
- [ ] T045 [P] [US1] Document auth/login.vue login form and error handling
- [ ] T046 [P] [US1] Document user profile page implementation (location TBD)
- [ ] T047 [US1] Document auth store (frontend/stores/auth.js): login/logout/session management

### Integration Testing (US1)

- [ ] T048 [US1] Create manual test checklist for US1 acceptance scenarios (5 scenarios from spec.md)
- [ ] T049 [US1] Verify registration → login → profile view → logout flow works end-to-end
- [ ] T050 [US1] Verify account locking after 5 failed login attempts

**Checkpoint**: ✅ US1 fully documented and independently testable

---

## Phase 4: User Story 2 - 五級角色權限體系 (Priority: P1) 🎯 MVP

**Goal**: 支援五種角色（超級管理員、管理員、服主、審核員、一般使用者），每個角色擁有明確權限範圍

**Independent Test**: 創建不同角色測試帳號 → 驗證各角色看到的功能選單不同 → 測試權限邊界（如一般使用者無法審核）

### Backend Models Documentation (US2)

- [ ] T051 [P] [US2] Document RoleModel.php: 5 predefined roles with level hierarchy (1-5)
- [ ] T052 [P] [US2] Document PermissionModel.php: resource-action permission model
- [ ] T053 [P] [US2] Document UserRoleModel.php: user-role assignment with validity period
- [ ] T054 [P] [US2] Document RolePermissionModel.php: role-permission mapping

### Backend Services Documentation (US2)

- [ ] T055 [US2] Document role assignment logic in backend/app/Controllers/API/RoleController.php
- [ ] T056 [US2] Document permission checking in RBACFilter.php: hasPermission() method
- [ ] T057 [US2] Document permission inheritance: multi-role users get union of all permissions
- [ ] T058 [US2] Document role seeding: default roles and permissions in backend/app/Database/Seeds/

### API Endpoints Documentation (US2)

- [ ] T059 [P] [US2] Verify GET /api/roles matches contracts/roles.yaml
- [ ] T060 [P] [US2] Verify GET /api/permissions matches contracts/roles.yaml
- [ ] T061 [P] [US2] Verify POST /api/users/:id/roles matches contracts/users.yaml
- [ ] T062 [P] [US2] Verify DELETE /api/users/:id/roles/:roleId matches contracts/users.yaml
- [ ] T063 [P] [US2] Verify GET /api/users/:id/permissions (returns effective permissions)

### Frontend Components Documentation (US2)

- [ ] T064 [P] [US2] Document role-based menu rendering in frontend/components/AppSidebar.vue
- [ ] T065 [P] [US2] Document permission checks in navigation guards (frontend/middleware/)
- [ ] T066 [P] [US2] Document admin role management UI (if exists in frontend/pages/admin/)
- [ ] T067 [US2] Document how frontend stores user permissions (likely in auth store)

### Integration Testing (US2)

- [ ] T068 [US2] Create manual test checklist for US2 acceptance scenarios (5 scenarios from spec.md)
- [ ] T069 [US2] Verify super_admin sees all menu items, regular user sees limited items
- [ ] T070 [US2] Verify permission boundaries: user without `servers.approve` cannot approve servers

**Checkpoint**: ✅ US2 fully documented, role system verified independently

---

## Phase 5: User Story 3 - 伺服器註冊與審核流程 (Priority: P1) 🎯 MVP

**Goal**: 服主註冊遊戲伺服器到平台，審核員查看待審核申請並決定核准或拒絕

**Independent Test**: 服主提交新伺服器申請 → 審核員審核並核准/拒絕 → 驗證伺服器狀態變更 → 通知送達

### Database Models Documentation (US3)

- [ ] T071 [P] [US3] Document servers table: 17 fields including status, owner_id, approval tracking
- [ ] T072 [P] [US3] Verify server status state machine: pending → approved/rejected → suspended/inactive
- [ ] T073 [P] [US3] Verify foreign keys: owner_id → users.id, approved_by → users.id

### Backend Models Documentation (US3)

- [ ] T074 [P] [US3] Document ServerModel.php: validation rules, status transitions, soft delete
- [ ] T075 [P] [US3] Document server_code generation logic (unique code for each server)

### Backend Services Documentation (US3)

- [ ] T076 [US3] Document server registration in backend/app/Controllers/API/ServerController.php::create()
- [ ] T077 [US3] Document approval workflow in ServerController.php::approve()
- [ ] T078 [US3] Document rejection workflow in ServerController.php::reject()
- [ ] T079 [US3] Document pending server list query (filtered by status='pending')
- [ ] T080 [US3] Document notification service integration (Email/LINE) in server approval

### API Endpoints Documentation (US3)

- [ ] T081 [P] [US3] Verify POST /api/servers matches contracts/servers.yaml
- [ ] T082 [P] [US3] Verify GET /api/servers (list with filters) matches contracts/servers.yaml
- [ ] T083 [P] [US3] Verify GET /api/servers/:id matches contracts/servers.yaml
- [ ] T084 [P] [US3] Verify POST /api/servers/:id/approve matches contracts/servers.yaml
- [ ] T085 [P] [US3] Verify POST /api/servers/:id/reject matches contracts/servers.yaml
- [ ] T086 [P] [US3] Verify PUT /api/servers/:id matches contracts/servers.yaml

### Frontend Components Documentation (US3)

- [ ] T087 [P] [US3] Document server registration form (likely in frontend/pages/servers/)
- [ ] T088 [P] [US3] Document pending servers list for reviewers (admin area)
- [ ] T089 [P] [US3] Document server approval/rejection UI with reason input
- [ ] T090 [P] [US3] Document server list page (frontend/pages/servers/index.vue)
- [ ] T091 [US3] Document server detail page (frontend/pages/servers/[server].vue)

### Integration Testing (US3)

- [ ] T092 [US3] Create manual test checklist for US3 acceptance scenarios (5 scenarios from spec.md)
- [ ] T093 [US3] Verify full approval flow: submit → pending → approve → status change → notification
- [ ] T094 [US3] Verify rejection flow: reject with reason → status change → owner receives reason

**Checkpoint**: ✅ US3 fully documented, server lifecycle verified

---

## Phase 6: User Story 4 - 伺服器資料與設定管理 (Priority: P2)

**Goal**: 服主管理伺服器完整資訊（基本設定、視覺素材、社群連結、資料庫連線）

**Independent Test**: 服主登入 → 編輯伺服器資訊 → 上傳圖片 → 設定資料庫連線 → 測試連線成功

### Database Models Documentation (US4)

- [ ] T095 [P] [US4] Document server_settings table: db connection, reward mapping, notifications
- [ ] T096 [P] [US4] Verify one-to-one relationship: servers ← server_settings
- [ ] T097 [P] [US4] Document encrypted password storage in db_password_encrypted field

### Backend Models Documentation (US4)

- [ ] T098 [P] [US4] Document ServerSettingsModel.php: encryption/decryption methods
- [ ] T099 [P] [US4] Document reward field mapping JSON schema validation

### Backend Services Documentation (US4)

- [ ] T100 [US4] Document file upload service in backend/app/Libraries/FileUploadService.php
- [ ] T101 [US4] Document database connection testing in backend/app/Libraries/DatabaseTestService.php
- [ ] T102 [US4] Document server update logic in ServerController.php::update()
- [ ] T103 [US4] Document image upload endpoints (logo, banner, background)
- [ ] T104 [US4] Document server settings CRUD in ServerController.php or ServerSettingsController.php

### API Endpoints Documentation (US4)

- [ ] T105 [P] [US4] Verify POST /api/servers/:id/upload-logo matches contracts/servers.yaml
- [ ] T106 [P] [US4] Verify POST /api/servers/:id/upload-banner matches contracts/servers.yaml
- [ ] T107 [P] [US4] Verify POST /api/servers/:id/upload-background matches contracts/servers.yaml
- [ ] T108 [P] [US4] Verify PUT /api/servers/:id/settings matches contracts/servers.yaml
- [ ] T109 [P] [US4] Verify POST /api/servers/:id/test-connection matches contracts/servers.yaml

### Frontend Components Documentation (US4)

- [ ] T110 [P] [US4] Document server edit form with all fields (info, images, links, tags)
- [ ] T111 [P] [US4] Document file upload components (drag-and-drop, progress bar)
- [ ] T112 [P] [US4] Document database settings form with connection test button
- [ ] T113 [P] [US4] Document reward mapping configuration UI
- [ ] T114 [US4] Document notification settings form (Email/LINE)

### Integration Testing (US4)

- [ ] T115 [US4] Create manual test checklist for US4 acceptance scenarios (5 scenarios from spec.md)
- [ ] T116 [US4] Verify image upload: select file → upload → display in server page
- [ ] T117 [US4] Verify database connection test: input credentials → test → success/error message

**Checkpoint**: ✅ US4 fully documented, server management verified

---

## Phase 7: User Story 5 - 推廣連結生成與分享 (Priority: P1) 🎯 MVP

**Goal**: 使用者為伺服器生成專屬推廣連結（唯一代碼、完整URL、短連結、QR Code）並分享至社群平台

**Independent Test**: 選擇伺服器 → 生成推廣連結 → 複製連結 → 下載QR Code → 分享至社群 → 驗證連結可訪問

### Database Models Documentation (US5)

- [ ] T118 [P] [US5] Document promotions table: 12 fields including promotion_code, URLs, statistics
- [ ] T119 [P] [US5] Verify unique constraint on promotion_code (32 characters)
- [ ] T120 [P] [US5] Verify unique constraint on (user_id, server_id, status='active')

### Backend Models Documentation (US5)

- [ ] T121 [P] [US5] Document PromotionModel.php: code generation, URL building, status management

### Backend Services Documentation (US5)

- [ ] T122 [US5] Document promotion link generator in backend/app/Libraries/PromotionLinkGenerator.php
- [ ] T123 [US5] Document QR code generation logic (library and configuration)
- [ ] T124 [US5] Document short URL generation (if implemented)
- [ ] T125 [US5] Document promotion creation in backend/app/Controllers/API/PromotionController.php::create()
- [ ] T126 [US5] Document duplicate promotion check (one active per user-server pair)

### API Endpoints Documentation (US5)

- [ ] T127 [P] [US5] Verify POST /api/promotions matches contracts/promotions.yaml
- [ ] T128 [P] [US5] Verify GET /api/promotions (user's promotions) matches contracts/promotions.yaml
- [ ] T129 [P] [US5] Verify GET /api/promotions/:id matches contracts/promotions.yaml
- [ ] T130 [P] [US5] Verify PUT /api/promotions/:id/pause matches contracts/promotions.yaml
- [ ] T131 [P] [US5] Verify PUT /api/promotions/:id/resume matches contracts/promotions.yaml

### Frontend Components Documentation (US5)

- [ ] T132 [P] [US5] Document promotion creation UI (select server, generate button)
- [ ] T133 [P] [US5] Document promotion link display with copy button
- [ ] T134 [P] [US5] Document QR code display and download functionality
- [ ] T135 [P] [US5] Document social media sharing buttons (Facebook, Twitter, Discord, LINE)
- [ ] T136 [US5] Document promotion list page (frontend/pages/promotions/index.vue)

### Integration Testing (US5)

- [ ] T137 [US5] Create manual test checklist for US5 acceptance scenarios (5 scenarios from spec.md)
- [ ] T138 [US5] Verify promotion creation: select server → generate → display unique link + QR
- [ ] T139 [US5] Verify duplicate prevention: try creating second promotion for same server → error

**Checkpoint**: ✅ US5 fully documented, promotion generation verified

---

## Phase 8: User Story 6 - 推廣效果追蹤與分析 (Priority: P2)

**Goal**: 使用者查看推廣連結效果（總點擊數、唯一訪客數、轉換數、轉換率、來源分析、時間分佈）

**Independent Test**: 分享推廣連結 → 模擬點擊 → 查看分析頁面 → 驗證數據正確 → 匯出CSV

### Database Models Documentation (US6)

- [ ] T140 [P] [US6] Document promotion_clicks table: IP, user agent, referrer, fingerprint, device info
- [ ] T141 [P] [US6] Document promotion_stats table: daily aggregations, hourly distribution, source breakdown
- [ ] T142 [P] [US6] Verify fingerprint-based unique visitor detection logic

### Backend Models Documentation (US6)

- [ ] T143 [P] [US6] Document PromotionClickModel.php: click recording, geolocation parsing
- [ ] T144 [P] [US6] Document PromotionStatsModel.php: aggregation queries, time-series data

### Backend Services Documentation (US6)

- [ ] T145 [US6] Document promotion tracker in backend/app/Libraries/PromotionTracker.php
- [ ] T146 [US6] Document fingerprint generation algorithm (IP + User Agent + screen size)
- [ ] T147 [US6] Document unique visitor deduplication (24-hour window)
- [ ] T148 [US6] Document click recording filter in backend/app/Filters/PromotionTrackingFilter.php
- [ ] T149 [US6] Document statistics calculation and caching logic

### API Endpoints Documentation (US6)

- [ ] T150 [P] [US6] Verify GET /api/promotions/:id/analytics matches contracts/promotions.yaml
- [ ] T151 [P] [US6] Verify GET /api/promotions/:id/clicks matches contracts/promotions.yaml
- [ ] T152 [P] [US6] Verify GET /api/promotions/:id/stats (time-series) matches contracts/promotions.yaml
- [ ] T153 [P] [US6] Verify POST /api/promotions/track (click tracking endpoint)
- [ ] T154 [P] [US6] Verify GET /api/promotions/:id/export (CSV/JSON export)

### Frontend Components Documentation (US6)

- [ ] T155 [P] [US6] Document promotion analytics page with charts (clicks, conversions, trends)
- [ ] T156 [P] [US6] Document time range selector (7 days / 30 days / all time)
- [ ] T157 [P] [US6] Document referrer breakdown chart (pie chart or bar chart)
- [ ] T158 [P] [US6] Document device breakdown chart (desktop/mobile/tablet)
- [ ] T159 [P] [US6] Document CSV export button and download functionality
- [ ] T160 [US6] Document click tracking implementation (tracking pixel or API call)

### Integration Testing (US6)

- [ ] T161 [US6] Create manual test checklist for US6 acceptance scenarios (5 scenarios from spec.md)
- [ ] T162 [US6] Verify click tracking: click promotion link → check analytics page → see click count +1
- [ ] T163 [US6] Verify unique visitor logic: same browser clicks twice → only 1 unique click

**Checkpoint**: ✅ US6 fully documented, tracking system verified

---

## Phase 9: User Story 7 - 推廣獎勵自動化處理 (Priority: P1) 🎯 MVP

**Goal**: 推廣轉換時系統自動創建獎勵記錄，根據設定自動審核並發放至遊戲資料庫

**Independent Test**: 模擬推廣轉換 → 驗證獎勵記錄自動創建 → 檢查狀態流轉 → 驗證發放至遊戲DB

### Database Models Documentation (US7)

- [ ] T164 [P] [US7] Document rewards table: 14 fields including status, priority, retry tracking
- [ ] T165 [P] [US7] Verify reward status state machine: pending → approved → distributed/failed/cancelled
- [ ] T166 [P] [US7] Verify foreign keys to users, servers, promotions, reward_settings

### Backend Models Documentation (US7)

- [ ] T167 [P] [US7] Document RewardModel.php: state transitions, priority handling, soft delete

### Backend Services Documentation (US7)

- [ ] T168 [US7] Document reward creation logic in PromotionController.php or RewardController.php
- [ ] T169 [US7] Document auto-approval logic based on server_settings.auto_approve_rewards
- [ ] T170 [US7] Document reward distribution in backend/app/Libraries/RewardCalculator.php
- [ ] T171 [US7] Document database direct write method (INSERT into game DB)
- [ ] T172 [US7] Document retry mechanism (max 3 attempts, exponential backoff)
- [ ] T173 [US7] Document error handling and failed reward tracking

### API Endpoints Documentation (US7)

- [ ] T174 [P] [US7] Verify GET /api/rewards (user's rewards) matches contracts/rewards.yaml
- [ ] T175 [P] [US7] Verify GET /api/rewards/:id matches contracts/rewards.yaml
- [ ] T176 [P] [US7] Verify POST /api/rewards/:id/approve matches contracts/rewards.yaml
- [ ] T177 [P] [US7] Verify POST /api/rewards/:id/reject matches contracts/rewards.yaml
- [ ] T178 [P] [US7] Verify POST /api/rewards/:id/distribute matches contracts/rewards.yaml
- [ ] T179 [P] [US7] Verify GET /api/rewards/pending (for reviewers)

### Frontend Components Documentation (US7)

- [ ] T180 [P] [US7] Document pending rewards list for reviewers (admin/rewards page)
- [ ] T181 [P] [US7] Document batch approval UI (select multiple rewards, approve all)
- [ ] T182 [P] [US7] Document user reward history page
- [ ] T183 [P] [US7] Document reward status indicators (pending/approved/distributed/failed)
- [ ] T184 [US7] Document error message display for failed rewards

### Integration Testing (US7)

- [ ] T185 [US7] Create manual test checklist for US7 acceptance scenarios (5 scenarios from spec.md)
- [ ] T186 [US7] Verify auto-approval: enable auto_approve → conversion → reward instantly approved
- [ ] T187 [US7] Verify manual approval: disable auto_approve → conversion → pending → reviewer approves
- [ ] T188 [US7] Verify distribution: approved reward → system writes to game DB → status = distributed

**Checkpoint**: ✅ US7 fully documented, reward automation verified

---

## Phase 10: User Story 8 - 獎勵設定與規則管理 (Priority: P2)

**Goal**: 服主設定獎勵規則（觸發條件、獎勵內容、數量限制、有效期限）

**Independent Test**: 服主創建獎勵規則 → 設定觸發條件和限制 → 驗證規則生效 → 測試數量限制

### Database Models Documentation (US8)

- [ ] T189 [P] [US8] Document reward_settings table: 15 fields including trigger_conditions, reward_config, limits_config
- [ ] T190 [P] [US8] Verify JSON schema for trigger_conditions field
- [ ] T191 [P] [US8] Verify JSON schema for reward_config field
- [ ] T192 [P] [US8] Verify JSON schema for limits_config field (daily/weekly/monthly)

### Backend Models Documentation (US8)

- [ ] T193 [P] [US8] Document RewardSettingsModel.php: validation, priority handling, active status

### Backend Services Documentation (US8)

- [ ] T194 [US8] Document reward settings CRUD in RewardController.php or RewardSettingsController.php
- [ ] T195 [US8] Document rule matching logic (trigger condition evaluation)
- [ ] T196 [US8] Document limit enforcement (daily/weekly/monthly counters)
- [ ] T197 [US8] Document rule priority handling (highest priority rule wins)
- [ ] T198 [US8] Document validity period checking (valid_from, valid_until)

### API Endpoints Documentation (US8)

- [ ] T199 [P] [US8] Verify GET /api/servers/:id/reward-settings matches contracts/rewards.yaml
- [ ] T200 [P] [US8] Verify POST /api/servers/:id/reward-settings matches contracts/rewards.yaml
- [ ] T201 [P] [US8] Verify PUT /api/reward-settings/:id matches contracts/rewards.yaml
- [ ] T202 [P] [US8] Verify DELETE /api/reward-settings/:id matches contracts/rewards.yaml
- [ ] T203 [P] [US8] Verify GET /api/reward-settings/:id/stats (usage statistics)

### Frontend Components Documentation (US8)

- [ ] T204 [P] [US8] Document reward settings list page (server owner view)
- [ ] T205 [P] [US8] Document reward rule creation form with JSON editor for conditions
- [ ] T206 [P] [US8] Document reward configuration UI (items, quantities, message)
- [ ] T207 [P] [US8] Document limits configuration UI (daily/weekly/monthly)
- [ ] T208 [P] [US8] Document validity period date pickers
- [ ] T209 [US8] Document rule statistics display (usage_count, success_count, failed_count)

### Integration Testing (US8)

- [ ] T210 [US8] Create manual test checklist for US8 acceptance scenarios (5 scenarios from spec.md)
- [ ] T211 [US8] Verify rule creation: create rule → conversion → reward matches rule config
- [ ] T212 [US8] Verify daily limit: set limit=3 → 4th conversion → no reward created

**Checkpoint**: ✅ US8 fully documented, reward rules verified

---

## Phase 11: User Story 9 - 統計分析與儀表板 (Priority: P2)

**Goal**: 不同角色查看對應統計資料（管理員看全平台、服主看自己伺服器、使用者看自己推廣）

**Independent Test**: 不同角色登入 → 查看儀表板 → 驗證顯示資料範圍正確 → 測試圖表和匯出

### Backend Services Documentation (US9)

- [ ] T213 [US9] Document statistics aggregation in backend/app/Controllers/API/StatisticsController.php
- [ ] T214 [US9] Document admin dashboard stats (total users, servers, promotions, rewards)
- [ ] T215 [US9] Document server owner stats (own servers, top promoters, reward distribution)
- [ ] T216 [US9] Document user stats (own promotions, clicks, conversions, rewards)
- [ ] T217 [US9] Document leaderboard calculation (top promoters by conversions)

### API Endpoints Documentation (US9)

- [ ] T218 [P] [US9] Verify GET /api/statistics/dashboard matches contracts/statistics.yaml
- [ ] T219 [P] [US9] Verify GET /api/statistics/servers/:id matches contracts/statistics.yaml
- [ ] T220 [P] [US9] Verify GET /api/statistics/users/:id matches contracts/statistics.yaml
- [ ] T221 [P] [US9] Verify GET /api/statistics/leaderboard matches contracts/statistics.yaml
- [ ] T222 [P] [US9] Verify GET /api/statistics/trends (time-series data)

### Frontend Components Documentation (US9)

- [ ] T223 [P] [US9] Document admin dashboard (frontend/pages/admin/dashboard.vue)
- [ ] T224 [P] [US9] Document server owner dashboard components
- [ ] T225 [P] [US9] Document user dashboard components
- [ ] T226 [P] [US9] Document chart components (frontend/components/charts/)
- [ ] T227 [P] [US9] Document time range selector (7/30/90 days)
- [ ] T228 [P] [US9] Document leaderboard display component
- [ ] T229 [US9] Document statistics export functionality (CSV/JSON)

### Integration Testing (US9)

- [ ] T230 [US9] Create manual test checklist for US9 acceptance scenarios (5 scenarios from spec.md)
- [ ] T231 [US9] Verify admin sees all platform stats (total counts across all servers)
- [ ] T232 [US9] Verify server owner only sees own server stats (data isolation)
- [ ] T233 [US9] Verify regular user only sees own promotion stats

**Checkpoint**: ✅ US9 fully documented, analytics verified

---

## Phase 12: User Story 10 - 前端管理介面 (Priority: P2)

**Goal**: 提供現代化管理後台（側邊欄導航、深色模式、響應式設計）

**Independent Test**: 管理員登入後台 → 測試所有管理功能 → 切換深色模式 → 測試手機訪問

### Frontend Layout Documentation (US10)

- [ ] T234 [P] [US10] Document admin layout (frontend/layouts/admin.vue) with sidebar
- [ ] T235 [P] [US10] Document sidebar navigation (frontend/components/AppSidebar.vue)
- [ ] T236 [P] [US10] Document sidebar menu items and role-based visibility
- [ ] T237 [P] [US10] Document sidebar collapse/expand functionality

### Frontend UI Components Documentation (US10)

- [ ] T238 [P] [US10] Document dark mode implementation (frontend/stores/theme.js)
- [ ] T239 [P] [US10] Document theme toggle button and persistence (localStorage)
- [ ] T240 [P] [US10] Document responsive design breakpoints in Tailwind config
- [ ] T241 [P] [US10] Document mobile menu hamburger implementation
- [ ] T242 [P] [US10] Document file upload components (drag-drop in frontend/components/)
- [ ] T243 [P] [US10] Document search/filter components for list pages
- [ ] T244 [P] [US10] Document pagination components

### Admin Pages Documentation (US10)

- [ ] T245 [P] [US10] Document user management page (frontend/pages/admin/)
- [ ] T246 [P] [US10] Document server management page (frontend/pages/admin/)
- [ ] T247 [P] [US10] Document promotion management page (frontend/pages/admin/promotions.vue)
- [ ] T248 [P] [US10] Document reward management page (frontend/pages/admin/)
- [ ] T249 [P] [US10] Document settings pages (frontend/pages/settings/)

### Integration Testing (US10)

- [ ] T250 [US10] Create manual test checklist for US10 acceptance scenarios (5 scenarios from spec.md)
- [ ] T251 [US10] Verify admin can access all menu items via sidebar
- [ ] T252 [US10] Verify dark mode toggle switches entire UI theme
- [ ] T253 [US10] Verify mobile responsive layout works on 375px width screen

**Checkpoint**: ✅ US10 fully documented, admin UI verified

---

## Phase 13: Polish & Cross-Cutting Concerns

**Purpose**: System-wide documentation and validation

### API Contract Validation

- [ ] T254 [P] [POLISH] Generate OpenAPI 3.0 spec from contracts/*.yaml files
- [ ] T255 [P] [POLISH] Validate all backend endpoints match OpenAPI spec
- [ ] T256 [P] [POLISH] Create Postman collection from OpenAPI spec for testing

### Security Documentation

- [ ] T257 [P] [POLISH] Document CORS configuration and allowed origins
- [ ] T258 [P] [POLISH] Document rate limiting configuration (60 req/min per IP)
- [ ] T259 [P] [POLISH] Document input validation rules in InputValidationFilter.php
- [ ] T260 [P] [POLISH] Document SQL injection prevention (prepared statements)
- [ ] T261 [P] [POLISH] Document XSS prevention (output escaping)
- [ ] T262 [P] [POLISH] Document CSRF protection strategy

### Performance Documentation

- [ ] T263 [P] [POLISH] Document database indexes and query optimization strategy
- [ ] T264 [P] [POLISH] Document Redis caching architecture (planned implementation)
- [ ] T265 [P] [POLISH] Document pagination implementation (all list endpoints)
- [ ] T266 [P] [POLISH] Document file upload size limits and validation

### Deployment Documentation

- [ ] T267 [POLISH] Document Docker Compose setup and configuration
- [ ] T268 [POLISH] Document environment variables (.env.example)
- [ ] T269 [POLISH] Document database migration workflow
- [ ] T270 [POLISH] Document production deployment checklist
- [ ] T271 [POLISH] Document backup and recovery procedures

### Developer Onboarding

- [ ] T272 [POLISH] Complete quickstart.md with step-by-step setup instructions
- [ ] T273 [POLISH] Create API usage examples for common operations
- [ ] T274 [POLISH] Document testing procedures (manual test checklists)
- [ ] T275 [POLISH] Document Git workflow and commit conventions (no Claude footer!)
- [ ] T276 [POLISH] Validate quickstart.md by running through setup process

### Code Quality Review

- [ ] T277 [P] [POLISH] Review and document coding standards (backend)
- [ ] T278 [P] [POLISH] Review and document coding standards (frontend)
- [ ] T279 [POLISH] Identify and document technical debt areas
- [ ] T280 [POLISH] Create roadmap for Phase 2 improvements (Redis, testing, CDN)

**Checkpoint**: ✅ Complete baseline documentation ready for reference

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 1 (Setup)
    ↓
Phase 2 (Foundational) ← CRITICAL: Blocks all user stories
    ↓
Phase 3-12 (User Stories) ← Can proceed in parallel or priority order
    ↓
Phase 13 (Polish) ← Depends on all desired user stories
```

### User Story Dependencies

**Foundational Phase (Phase 2) MUST complete before ANY user story can start**

Once Foundational is complete, user stories can proceed:

- **US1 (Phase 3)**: Can start after Foundational ✅ No dependencies on other stories
- **US2 (Phase 4)**: Can start after Foundational ✅ Independent (role system)
- **US3 (Phase 5)**: Can start after Foundational ✅ Independent (server registration)
- **US4 (Phase 6)**: Depends on US3 (need servers to manage)
- **US5 (Phase 7)**: Depends on US1 + US3 (need users and servers)
- **US6 (Phase 8)**: Depends on US5 (need promotions to track)
- **US7 (Phase 9)**: Depends on US5 + US3 (promotions + servers)
- **US8 (Phase 10)**: Depends on US7 (reward rules for reward system)
- **US9 (Phase 11)**: Depends on US5 + US6 + US7 (needs data to analyze)
- **US10 (Phase 12)**: Can start anytime (UI enhancements)

### Recommended Implementation Order (Priority-Based)

**MVP Priority (P1 User Stories)**:
1. Phase 1 (Setup)
2. Phase 2 (Foundational) ← CRITICAL CHECKPOINT
3. Phase 3 (US1 - User Registration) ← MVP CORE
4. Phase 4 (US2 - Role System) ← MVP CORE
5. Phase 5 (US3 - Server Registration) ← MVP CORE
6. Phase 7 (US5 - Promotion Links) ← MVP CORE
7. Phase 9 (US7 - Reward Automation) ← MVP CORE

**Post-MVP Priority (P2 User Stories)**:
8. Phase 6 (US4 - Server Settings)
9. Phase 8 (US6 - Analytics)
10. Phase 10 (US8 - Reward Rules)
11. Phase 11 (US9 - Statistics)
12. Phase 12 (US10 - Admin UI)

**Final Phase**:
13. Phase 13 (Polish)

### Within Each User Story Phase

1. **Database Models** (can run in parallel) → Document tables, verify schema
2. **Backend Models** (can run in parallel) → Document ORM models
3. **Backend Services** (sequential) → Document business logic
4. **API Endpoints** (can run in parallel) → Verify contracts
5. **Frontend Components** (can run in parallel) → Document UI
6. **Integration Testing** (sequential) → Verify end-to-end flows

### Parallel Opportunities

**Within Foundational Phase (Phase 2)**:
- All database schema docs (T006-T010) can run in parallel [P]
- All authentication framework docs (T011-T015) can run in parallel [P]
- All authorization framework docs (T016-T019) can run in parallel [P]
- All base API structure docs (T020-T024) can run in parallel [P]
- All base frontend structure docs (T025-T029) can run in parallel [P]

**Across User Stories** (once Foundational completes):
- US1, US2, US3 can all start in parallel (if team capacity allows)
- US4 can start once US3 completes
- US5 can start once US1 + US3 complete
- US10 can start anytime (independent UI work)

**Within Each User Story**:
- All model documentation tasks marked [P] can run in parallel
- All API endpoint verification tasks marked [P] can run in parallel
- All frontend component documentation tasks marked [P] can run in parallel

---

## Parallel Example: Phase 2 (Foundational)

All foundational documentation can be divided among team members:

```bash
# Team Member A - Database Schema
T006, T007, T008, T009, T010

# Team Member B - Authentication
T011, T012, T013, T014, T015

# Team Member C - Authorization
T016, T017, T018, T019

# Team Member D - API Structure
T020, T021, T022, T023, T024

# Team Member E - Frontend Structure
T025, T026, T027, T028, T029
```

All complete in parallel → Foundational phase checkpoint reached

---

## Parallel Example: User Story 3

```bash
# Launch database documentation together:
Task: "Document servers table: 17 fields..." (T071)
Task: "Verify server status state machine..." (T072)
Task: "Verify foreign keys..." (T073)

# Launch backend models together:
Task: "Document ServerModel.php..." (T074)
Task: "Document server_code generation..." (T075)

# Launch API endpoint verification together:
Task: "Verify POST /api/servers..." (T081)
Task: "Verify GET /api/servers..." (T082)
Task: "Verify GET /api/servers/:id..." (T083)
Task: "Verify POST /api/servers/:id/approve..." (T084)
Task: "Verify POST /api/servers/:id/reject..." (T085)
Task: "Verify PUT /api/servers/:id..." (T086)

# Launch frontend components together:
Task: "Document server registration form..." (T087)
Task: "Document pending servers list..." (T088)
Task: "Document approval/rejection UI..." (T089)
Task: "Document server list page..." (T090)
```

---

## Implementation Strategy

### MVP First (P1 User Stories Only)

**Goal**: Document the working MVP system

1. ✅ Complete Phase 1: Setup (environment docs)
2. ✅ Complete Phase 2: Foundational (CRITICAL - blocks everything)
3. ✅ Complete Phase 3: US1 (User Registration)
4. ✅ Complete Phase 4: US2 (Role System)
5. ✅ Complete Phase 5: US3 (Server Registration)
6. ✅ Complete Phase 7: US5 (Promotion Links)
7. ✅ Complete Phase 9: US7 (Reward Automation)
8. **VALIDATE**: MVP documentation complete, all P1 features documented

### Incremental Documentation

**Best approach for large documentation projects**:

1. Phase 1-2 → Foundation documented
2. Phase 3 (US1) → Test independently, validate docs
3. Phase 4 (US2) → Test independently, validate docs
4. Phase 5 (US3) → Test independently, validate docs
5. Continue through all user stories
6. Phase 13 → Polish and validation

Each phase adds documentation without breaking previous phases.

### Parallel Team Strategy

With multiple team members documenting simultaneously:

**Week 1**: All team members complete Phase 1-2 together (foundation)

**Week 2-4** (once foundation complete):
- Member A: Document US1 + US2
- Member B: Document US3 + US4
- Member C: Document US5 + US6
- Member D: Document US7 + US8
- Member E: Document US9 + US10

**Week 5**: All team members work on Phase 13 (Polish) together

---

## Summary

**Total Tasks**: 280 tasks across 13 phases
**User Stories**: 10 stories (5 × P1 priority, 5 × P2 priority)
**Database Tables**: 13 tables documented
**API Endpoints**: 80+ endpoints verified
**Frontend Components**: 50+ components documented

**Estimated Timeline**:
- **Setup**: 1 day (5 tasks)
- **Foundational**: 3-5 days (24 tasks) ← CRITICAL PATH
- **Each User Story**: 2-4 days (15-30 tasks per story)
- **Polish**: 3-5 days (27 tasks)
- **Total**: 4-8 weeks (depending on team size and parallel execution)

**MVP Milestone**: After completing Phase 1-2 + US1, US2, US3, US5, US7 (P1 priorities), you have a documented working MVP system.

**Key Success Metrics**:
- ✅ All 13 database tables documented and verified
- ✅ All API endpoints match OpenAPI contracts
- ✅ All 10 user stories independently testable
- ✅ quickstart.md allows new developer to set up in < 1 hour
- ✅ Manual test checklists pass for all user stories

---

## Notes

- **[P]** = Parallelizable tasks (different files, no dependencies)
- **[Story]** = User story label for traceability (US1-US10, FOUNDATION, POLISH)
- **This is DOCUMENTATION** = All tasks document EXISTING code, not new implementation
- **Independent Testing** = Each user story should be testable without others
- **Commit Frequently** = Commit after each logical group of tasks
- **Stop at Checkpoints** = Validate documentation accuracy at each checkpoint
- **No Claude Footer** = Follow CLAUDE.md: NO "Generated with Claude Code" in commits

**Next Step**: Run `/speckit.implement` to begin executing these tasks with AI assistance, or manually work through task list in priority order.

---

**Document End** | Total Tasks: 280 | User Stories: 10 | Estimated Duration: 4-8 weeks
