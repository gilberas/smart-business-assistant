# Business WhatsApp AI Assistant — Architecture Blueprint

> **Author:** Lead Software Architect
> **Status:** Approved
> **Last Updated:** 2026-07-22
> **Intended Audience:** Senior Engineers, Tech Leads, Stakeholders

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Functional Requirements](#2-functional-requirements)
3. [Non-functional Requirements](#3-non-functional-requirements)
4. [High-Level Architecture](#4-high-level-architecture)
5. [Architectural Principles](#5-architectural-principles)
6. [Multi-Tenant Strategy](#6-multi-tenant-strategy)
7. [Coding Standards](#7-coding-standards)
8. [Backend Folder Structure](#8-backend-folder-structure)
9. [Frontend Folder Structure](#9-frontend-folder-structure)
10. [Database Strategy](#10-database-strategy)
11. [Security Architecture](#11-security-architecture)
12. [API Design Standards](#12-api-design-standards)
13. [UI/UX Standards](#13-uiux-standards)
14. [Development Rules](#14-development-rules)

---

## 1. Executive Summary

### What the Platform Is

The Business WhatsApp AI Assistant is a production-ready, multi-tenant SaaS platform that enables businesses to connect their Meta WhatsApp Business account and automate customer communication through artificial intelligence. It is not a simple chatbot. It is a comprehensive AI-powered Business Communication Platform that understands business context, retrieves knowledge, manages conversations, and hands over to humans when necessary.

### Business Goals

- Enable businesses of all sizes to provide 24/7 instant customer support via WhatsApp
- Reduce operational cost by automating repetitive inquiries
- Increase customer satisfaction through instant, accurate responses
- Provide actionable analytics on customer communication patterns
- Create a scalable multi-tenant revenue-generating SaaS product

### Primary Users

| Role | Description |
|------|-------------|
| Business Owner | Configures the platform, manages subscriptions, views analytics |
| Agent | Handles conversations handed over by AI, manages customers |
| Customer | End-user interacting with the business via WhatsApp |
| Super Admin | Manages the entire SaaS platform, tenants, billing |

### Expected Benefits

- 80%+ automation rate for common customer inquiries
- Centralized multi-business management from a single dashboard
- Pluggable AI architecture preventing vendor lock-in
- Full conversation history with human handover audit trail
- Data-driven insights into customer behaviour and team performance

---

## 2. Functional Requirements

### 2.1 Authentication

**Purpose:** Secure user access with email/password and social login. Session management via Laravel Sanctum tokens.

**Responsibilities:**
- Register, login, logout, password reset, email verification
- Two-factor authentication (TOTP)
- Session management (list active sessions, revoke)
- OAuth for Meta WhatsApp Business account linking
- Social login (Google, Microsoft) for dashboard users

**Future Extensibility:**
- SAML / SSO for enterprise tenants
- Hardware security key (WebAuthn) support
- API token management for programmatic access

### 2.2 Business Management

**Purpose:** Allow multi-tenant business onboarding and profile configuration.

**Responsibilities:**
- Create and manage business profiles (name, logo, address, timezone, currency)
- Configure business hours and holiday schedules
- WhatsApp Business Account (WABA) connection via Meta OAuth flow
- Phone number registration and verification
- Business feature toggles

**Future Extensibility:**
- Multiple phone numbers per business
- Branch / location management
- White-label branding options

### 2.3 Team Management

**Purpose:** Allow business owners to invite team members with granular role-based access.

**Responsibilities:**
- Invite team members via email
- Role assignment (Admin, Agent, Viewer)
- Permission management using Spatie Permission
- Team member status (active, suspended, invited)
- Activity overview per team member

**Future Extensibility:**
- Custom role creation
- Department-based grouping
- Shift scheduling and availability management

### 2.4 Customers

**Purpose:** Central repository of all customer profiles across conversations.

**Responsibilities:**
- Auto-create customer profiles from WhatsApp interactions
- Store customer attributes (name, phone, email, tags, notes)
- Customer segmentation and filtering
- Conversation history per customer
- Merge duplicate customer profiles

**Future Extensibility:**
- Import/export customers (CSV, API)
- Custom fields per business
- Customer portal / self-service

### 2.5 Products

**Purpose:** Manage product/service catalog for retrieval-augmented Q&A.

**Responsibilities:**
- CRUD for products (name, description, price, images, category, variants)
- Product search and filtering
- Link products to knowledge base articles
- Stock/availability status

**Future Extensibility:**
- Inventory management integration
- E-commerce platform sync (Shopify, WooCommerce)
- Dynamic pricing and discounts

### 2.6 Knowledge Base

**Purpose:** The business knowledge that powers AI responses.

**Responsibilities:**
- Article CRUD with rich text and attachments
- Categorization and tagging
- Version history for articles
- Auto-suggest articles when training AI
- Bulk import (PDF, DOCX, website scraping via webhook)
- Article publish/draft/archived states

**Future Extensibility:**
- Multi-language knowledge base
- AI-assisted article generation
- Public knowledge base widget

### 2.7 AI Engine

**Purpose:** Abstracted AI layer that powers all intelligent responses.

**Responsibilities:**
- Provider-agnostic interface for LLM interaction
- Support OpenAI, Anthropic Claude, Google Gemini
- System prompt construction per business
- Context injection (business info, products, knowledge base)
- Response generation with structured output
- Token usage tracking and cost attribution per tenant
- Fallback provider logic if primary fails
- Model configuration per business (model, temperature, max tokens)
- Context window management and conversation trimming

**Future Extensibility:**
- Custom fine-tuned model support
- Local/on-premise LLM support (Llama, Mistral)
- Embedding model abstraction for vector search
- RAG pipeline optimization with reranking

**Module Integration Rules (Chatbot, Knowledge Base RAG, Conversation Intelligence):**
All modules that require AI capabilities must depend only on `AIServiceInterface` (`App\Contracts\AI\AIServiceInterface`) — never directly on adapters, providers, or the factory. This guarantees provider agnosticism, automatic fallback, usage tracking, request logging, and event dispatching without any module needing to know which provider serves its requests.

| Module | Contract Method to Use |
|--------|----------------------|
| Chatbot (conversation response generation) | `generateText()` or `streamResponse()` |
| Knowledge Base RAG (semantic search, embeddings) | `generateStructuredOutput()` with schema constraints |
| Conversation Intelligence (sentiment, summarization, intent) | `generateStructuredOutput()` with typed schemas |

All modules must pass `business_id` and relevant context via `AIRequestDTO`. Token usage, cost tracking, and audit logging are handled automatically by `AIService`.

### 2.8 WhatsApp Integration

**Purpose:** Connect and communicate through Meta WhatsApp Cloud API.

**Responsibilities:**
- Webhook receiver for incoming messages
- Message status callbacks (sent, delivered, read, failed)
- Template message management
- Media upload and download (images, documents, audio)
- Rate-limit-aware message dispatch queue
- QR code / deep link for WhatsApp number linking
- Handle message encryption and security

**Future Extensibility:**
- Interactive message buttons and lists
- Flows (Meta's form experience)
- Catalog integration for product display

### 2.9 Conversation Inbox

**Purpose:** Real-time unified inbox for all WhatsApp conversations.

**Responsibilities:**
- Real-time conversation list with unread count
- Message thread view (sent/received)
- AI-powered suggested replies
- Assign conversations to team members
- Conversation status (open, pending, closed, spam)
- Search messages and conversations
- Message timestamps and delivery status indicators
- Customer profile sidebar

**Future Extensibility:**
- Internal notes and @mentions
- Canned responses / saved replies
- Conversation tags and priority levels

### 2.10 Human Handover

**Purpose:** Seamlessly transfer conversation control from AI to a human agent.

**Responsibilities:**
- Detect escalation triggers (sentiment, keyword, repetition, explicit request)
- Assign handover to available agent or round-robin
- Notify assigned agent via in-app notification
- Maintain full conversation context during handover
- Allow agent to resume AI assistance or disable it per conversation
- Handover audit log (who, when, why)

**Future Extensibility:**
- Skill-based routing to specific agents
- Queue position for customers waiting for human
- Automatic re-engagement of AI after agent inactivity

### 2.11 Broadcast Campaigns

**Purpose:** Send targeted WhatsApp messages to customer segments.

**Responsibilities:**
- Campaign creation (name, audience segment, message template)
- Schedule immediate or future send
- Rate-limited, batched dispatch via queue
- Campaign analytics (sent, delivered, read, replied)
- Opt-out tracking and compliance
- Template message approval flow via Meta

**Future Extensibility:**
- A/B testing message variants
- Drip campaigns (multi-step automated sequences)
- Personalization variables per customer

### 2.12 Analytics

**Purpose:** Provide actionable data on conversations, AI performance, and team productivity.

**Responsibilities:**
- Conversation volume over time (line charts)
- AI vs human handled ratio
- Average response time (AI and human)
- Customer satisfaction scoring (after-conversation survey)
- Top customer inquiries (intent/ topic clustering)
- Agent performance metrics
- Token usage and cost per AI provider
- Export reports (PDF, CSV)

**Future Extensibility:**
- Custom report builder
- Scheduled email reports
- Real-time dashboard with WebSocket updates
- Third-party BI tool integration (Metabase, PowerBI)

### 2.13 Notifications

**Purpose:** Keep users informed of important events in real time.

**Responsibilities:**
- In-app notification center (bell icon, dropdown list)
- Notification types: handover assigned, new customer, campaign completed, subscription expiry
- Read/unread state
- Push notifications (desktop)
- Email notification digest

**Future Extensibility:**
- SMS notifications
- Slack/Teams webhook integration
- Custom notification rules per business

### 2.14 Audit Logs

**Purpose:** Immutable record of all security and data-modifying events.

**Responsibilities:**
- Log all CRUD operations on sensitive entities
- Log authentication events (login, logout, failed login, 2FA)
- Log permission changes
- Log handover events
- Log AI configuration changes
- Searchable and filterable log viewer
- Retention policy enforcement

**Future Extensibility:**
- Real-time audit stream for SIEM integration
- Export to compliance tools

### 2.15 Settings

**Purpose:** Central configuration panel for business-level and user-level settings.

**Responsibilities:**
- Business profile settings
- AI provider selection and model configuration
- Notification preferences
- Business hours and holiday calendar
- WhatsApp template management
- Language and regional preferences
- User profile and password management

**Future Extensibility:**
- Feature flag management
- Integration settings (webhooks, third-party APIs)

### 2.16 Subscriptions

**Purpose:** Manage tenant billing and plan entitlements.

**Responsibilities:**
- Plan definitions (tiers with feature limits: conversations, team members, AI providers)
- Trial period management
- Payment method management via payment gateway
- Invoice generation and payment history
- Usage-based overage billing
- Plan upgrade/downgrade/cancel
- Grace period for payment failures

**Future Extensibility:**
- Usage-based pricing (per conversation, per token)
- Coupon and discount management
- Multi-currency support

---

## 3. Non-functional Requirements

### 3.1 Performance

- API responses must complete within 200ms for 95th percentile (excluding AI generation)
- AI generation responses must stream via SSE for real-time user experience
- Conversation list must load within 500ms for up to 10,000 conversations per business
- Broadcast campaigns must process at least 100 messages per second via queue workers
- Database queries must be indexed and never scan more than 10,000 rows per query
- Page load time < 2s on standard broadband connection

### 3.2 Scalability

- Horizontal scaling for API servers (stateless, behind load balancer)
- Queue workers scaled independently based on queue depth
- Read replicas for MySQL for analytics queries
- Redis cluster for cache, sessions, queues
- Frontend assets served via CDN
- Database sharding strategy prepared for >100M conversations

### 3.3 Availability

- Target 99.9% uptime (8.76 hours downtime/year max)
- Graceful degradation when AI provider is unavailable (fallback provider or queue for retry)
- Maintenance mode with queued requests
- Zero-downtime deployments

### 3.4 Reliability

- All external API calls (WhatsApp, AI providers) must have retry with exponential backoff
- Messages must never be lost — store before processing (inbox pattern)
- Webhook idempotency via unique message IDs
- Failed message retry queue with dead-letter after max attempts
- Transactional integrity for billing operations

### 3.5 Maintainability

- Strict adherence to SOLID principles
- Feature-based modular organization
- Comprehensive test coverage (>80%)
- PHPStan at max level
- TypeScript strict mode
- Auto-generated API documentation (Scribe)
- Swagger/OpenAPI specification

### 3.6 Security

- OWASP Top 10 compliance
- All data encrypted at rest (AES-256) and in transit (TLS 1.3)
- Tenant data strict isolation at database query level
- Input validation on all endpoints via Form Requests
- Rate limiting per user, per IP, per endpoint
- CORS restricted to known origins
- Secrets never in code — always via environment variables or vault

### 3.7 Accessibility

- WCAG 2.2 AA compliance target
- Semantic HTML
- ARIA labels where necessary
- Keyboard navigable
- Screen reader friendly
- Colour contrast ratios meeting WCAG standards in both themes

### 3.8 Internationalization

- Backend error messages in English only (simplicity)
- Frontend UI fully localizable via i18n library (react-i18next)
- Date, time, number, currency formatting per locale
- Right-to-left (RTL) layout support prepared

### 3.9 Logging

- Structured JSON logging for all backend services
- Log levels: debug, info, notice, warning, error, critical, alert, emergency
- Centralized log aggregation (e.g., Graylog, ELK, or CloudWatch)
- Request ID tracing across services
- No sensitive data ever logged (PII masked)

### 3.10 Monitoring

- Health check endpoints (`/api/v1/health`, `/api/v1/health/db`, `/api/v1/health/redis`)
- Application performance monitoring (Laravel Pulse or similar)
- Queue metrics (length, processing time, failure rate)
- AI provider latency and error rate dashboards
- Scheduled heartbeat checks for WhatsApp webhook connectivity
- Alerting on P1/P2 thresholds (PagerDuty or equivalent)

### 3.11 Backup Strategy

| Asset | Frequency | Retention | Method |
|-------|-----------|-----------|--------|
| MySQL database | Daily full + hourly incremental | 30 days | mysqldump / snapshot |
| Redis | Persistent RDB snapshots | 7 days | Redis BGSAVE |
| User uploaded files (local) | Daily | 30 days | S3 sync |
| S3 (production) | Cross-region replication | N/A | AWS S3 Replication |
| Environment config | On change | Git history | Encrypted in deployment pipeline |

- Recovery Time Objective (RTO): 4 hours
- Recovery Point Objective (RPO): 1 hour

---

## 4. High-Level Architecture

### 4.1 Architecture Diagram (Textual)

```
┌─────────────────────────────────────────────────────────────┐
│                        Frontend (React + Vite)              │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────┐  │
│  │ Dashboard │ │ Inbox    │ │ Analytics│ │ Settings     │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────────┘  │
│                         │ HTTP/SSE                          │
├─────────────────────────┴───────────────────────────────────┤
│              CDN / Load Balancer                            │
├─────────────────────────┬───────────────────────────────────┤
│                  Laravel 13 Application                     │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌────────┐ ┌──────────────┐  │
│  │ API  │ │Queue │ │Event │ │Console │ │ Scheduled    │  │
│  │Controllers│Workers│Listeners│Commands│ │ Tasks       │  │
│  └──┬───┘ └──┬───┘ └──┬───┘ └───┬────┘ └──────────────┘  │
│     │        │        │         │                           │
│  ┌──┴────────┴────────┴─────────┴────────────────────┐     │
│  │              Service Layer                          │     │
│  │  ┌────────┐ ┌──────────┐ ┌─────────┐ ┌──────────┐ │     │
│  │  │Business│ │Conversation│ │ AI    │ │WhatsApp  │ │     │
│  │  │Service │ │Service    │ │Service │ │Service   │ │     │
│  │  └───┬────┘ └────┬─────┘ └────┬────┘ └────┬─────┘ │     │
│  │      │            │           │           │          │     │
│  │  ┌───┴────────────┴───────────┴───────────┴──────┐  │     │
│  │  │          Repository Interfaces                │  │     │
│  │  └───────────────────────────┬──────────────────┘  │     │
│  └─────────────┬──────────────┬─┴──────────┬──────────┘     │
│                │              │            │                 │
├────────────────┼──────────────┼────────────┼─────────────────┤
│         ┌──────┴──────┐ ┌────┴────┐ ┌────┴─────┐           │
│         │   MySQL     │ │  Redis  │ │  S3      │           │
│         │  (Primary)  │ │(Cache,  │ │(Files)   │           │
│         │  (Read Rep) │ │Queues,  │ │          │           │
│         │             │ │Sessions)│ │          │           │
│         └─────────────┘ └─────────┘ └──────────┘           │
├─────────────────────────────────────────────────────────────┤
│                    External Services                        │
│  ┌──────────┐ ┌─────────┐ ┌────────┐ ┌────────────────┐   │
│  │ WhatsApp │ │ OpenAI  │ │Claude  │ │ Gemini         │   │
│  │ Cloud API│ │         │ │        │ │                │   │
│  └──────────┘ └─────────┘ └────────┘ └────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### 4.2 Component Descriptions

**Frontend (React + TypeScript + Vite)**
- SPA with client-side routing (React Router v7)
- State management via Zustand (lightweight, TypeScript-first)
- API calls via Axios with interceptors for auth token injection and 401 handling
- Real-time updates via Laravel Echo (Pusher or WebSocket)
- SSE for AI response streaming
- Fully themed (dark/light) via Tailwind CSS + shadcn/ui

**Backend (Laravel 13)**
- Stateless API-only application (no Blade/Breeze/Jetstream)
- Sanctum token-based authentication
- Feature-based directory structure
- Service layer encapsulating all business logic
- Repository pattern for data access abstraction
- Form Requests for validation
- API Resources for response transformation

**Database (MySQL)**
- Primary instance for all write operations
- Read replica(s) for analytics, reporting, dashboard queries
- Strictly indexed for performance
- All tables use InnoDB with UTF-8 MB4

**Redis**
- Cache: application cache, rate limiter, token storage
- Queue: Horizon-driven job queues (conversations, broadcasts, AI)
- Session: Sanctum token sessions
- Pub/Sub: real-time event broadcasting

**Queue Workers (Horizon)**
- Multiple queue pipelines with dedicated workers
- `high` — AI responses, message sending (critical path)
- `default` — broadcast campaigns, webhook processing
- `low` — analytics aggregation, log cleanup, exports

**Storage**
- Local for development (disk driver)
- S3-compatible for production (with CDN fronting public assets)
- Private files served via signed URLs

**AI Providers**
- Provider interface defines contract (`AiProviderContract`)
- Each provider implemented as a separate class
- Provider selection per business via settings
- Automatic fallback on failure

**WhatsApp**
- Outbound: queue-based dispatch with rate limiting
- Inbound: webhook receiver → validation → queue → process
- Media: download via Meta, store in application storage

### 4.3 Request Flow (Typical Customer Inquiry)

```
1. Customer sends WhatsApp message
2. Meta sends webhook POST → /api/v1/webhooks/whatsapp
3. Webhook controller validates signature
4. Dispatches ProcessIncomingMessage job to 'high' queue
5. Worker processes:
   a. Store message in database
   b. Create/get customer profile
   c. Create/get conversation
   d. Check business hours / holiday
   e. Check if conversation is in human handover mode
   f. If AI mode → call AIService.generateReply()
   g. AIService → resolves provider via AIContract
   h. Builds prompt: system instructions + context (products, knowledge base)
   i. Calls LLM API (OpenAI/Claude/Gemini)
   j. Parse and validate response
   k. Store AI reply in database
   l. Dispatch SendWhatsAppMessage job
   m. SendWhatsAppMessage worker calls Meta API
   n. Update message status in database
6. Real-time event broadcast to dashboard via WebSocket
```

---

## 5. Architectural Principles

### 5.1 Service Layer

**Why:** Encapsulates all business logic away from controllers. Controllers become thin — they only handle HTTP concerns (request parsing, response formatting). Services are framework-agnostic PHP classes that can be tested in isolation.

**Enforcement:**
- Controller methods call exactly one service method
- Services never directly return HTTP responses
- Services are stateless (all dependencies injected)
- Services throw domain-specific exceptions

### 5.2 Repository Pattern

**Why:** Abstracts data access logic behind interfaces. Controllers and services never write Eloquent queries directly. This enables:
- Switching data sources without changing business logic
- Unit testing services with mocked repositories
- Centralized query optimization
- Consistent pagination, filtering, sorting

**Enforcement:**
- Repositories receive Model via DI
- Repository interfaces in `Contracts/Repositories`
- Repository implementations in `Repositories`
- Services type-hint interfaces, not implementations

### 5.3 Dependency Injection

**Why:** Laravel's container automatically resolves dependencies. This promotes loose coupling, testability, and swap-ability of implementations.

**Enforcement:**
- All dependencies injected via constructor
- No `app()` or `resolve()` facades in business logic
- No static facades in services/repositories
- Binding interfaces to implementations in ServiceProvider

### 5.4 Form Requests

**Why:** Separates validation logic from controllers. Each endpoint has a dedicated Form Request class containing rules, authorization logic, and custom validation messages.

**Enforcement:**
- Every store/update endpoint has a Form Request
- Form Request contains `authorize()` and `rules()`
- Complex validation rules as dedicated Rule classes
- Form Requests are the only place validation logic lives

### 5.5 API Resources

**Why:** Transforms Eloquent models into JSON responses consistently. Resources live in a dedicated layer, keeping response formatting out of controllers and models.

**Enforcement:**
- Every model has at least one Resource class
- Collections use `ResourceCollection`
- Conditional attributes via `when()` and `whenLoaded()`
- Resources never contain business logic

### 5.6 DTOs (Data Transfer Objects)

**Recommended:** Yes, for internal data passing between layers.

**Why:** DTOs provide type safety and documentation for data passed between controllers, services, repositories, and jobs. They prevent array-shaped-data bugs and make refactoring safer.

**When to use:**
- Service method parameters with >2 fields
- Job constructor data
- Return data from repository methods (instead of returning arrays)
- Event data

**When not to use:**
- Simple parameters (single ID, string)

### 5.7 Events

**Why:** Decouple side effects from primary operations. When a conversation is created, multiple listeners can react independently (notify, log, update analytics) without the creating code knowing about them.

**Enforcement:**
- Events dispatched from services after successful operations
- Event naming: past-tense verb + entity noun (e.g., `ConversationCreated`, `MessageReceived`)
- Each event has a dedicated DTO or uses Eloquent model (use `ShouldBroadcast` when needed)

### 5.8 Listeners

**Why:** Handle side effects in response to events. Listeners are queued for non-critical paths.

**Enforcement:**
- One listener per logical side effect
- Queue listeners that perform I/O (API calls, email, logging)
- Synchronous listeners only for in-process requirements

### 5.9 Jobs

**Why:** Offload long-running or unreliable operations to queue workers. This keeps API response times low and provides automatic retry on failure.

**Enforcement:**
- Job naming: verb + noun (e.g., `SendWhatsAppMessage`, `ProcessIncomingMessage`)
- Jobs are self-contained (receive all data via constructor DTOs)
- Jobs use `ShouldBeUnique` where duplicate execution is dangerous
- `Middleware` for rate limiting on external API jobs
- Failed jobs go to `failed_jobs` table with notification

### 5.10 Caching

**Strategy:**
- Cache read-heavy, write-slow data (knowledge base articles, product catalog)
- Cache invalidation via cache tags (Redis) or explicit `forget()` calls
- Never cache tenant-specific data outside tenant-scoped keys
- Cache key format: `{tenant_id}:{entity}:{identifier}`

**Enforcement:**
- Cache logic in Repository layer, not Service layer
- `Cache::remember()` with sensible TTLs
- Bust cache on `created`, `updated`, `deleted` events via listeners

### 5.11 Repository Interfaces

**Why:** Depend on abstractions, not concretions. Each repository has an interface in `Contracts/Repositories`. Services depend on the interface.

**Enforcement:**
- Interface defines all public methods
- Implementation in concrete class
- Bound in `RepositoryServiceProvider`
- Methods return Model, Collection, LengthAwarePaginator, or DTOs — never Builder instances

### 5.12 Dependency Inversion

**Why:** High-level modules (services) should not depend on low-level modules (repositories, external APIs). Both should depend on abstractions.

**Enforcement:**
- Services depend on repository interfaces
- AI service depends on `AiProviderContract` interface
- WhatsApp service depends on `MessagingProviderContract`
- Concrete implementations bound in service providers

---

## 6. Multi-Tenant Strategy

### 6.1 Recommended Model: Single Database with Row-Level Isolation (Scoped)

**Decision:** Single MySQL database with a `business_id` column on all tenant-scoped tables. Global scopes automatically filter by the current authenticated user's business.

### 6.2 Why This Approach

| Factor | Single DB | Separate DB | Separate Schema |
|--------|-----------|-------------|-----------------|
| Complexity | Low | High | Medium |
| Cross-tenant reporting | Easy | Hard | Medium |
| Migration cost | Low | High | Medium |
| Tenant data isolation | Moderate | Strong | Strong |
| Hardware cost | Low | High | Medium |
| Backup/restore | Simple | Complex | Moderate |
| Maximum tenants | 10,000+ | 100s | 1,000s |

For a SaaS product starting with hundreds to thousands of tenants, single-database row-level isolation provides the best balance of operational simplicity, cost, and performance — provided tenant scoping is strictly enforced.

### 6.3 Tenant Isolation Enforcement

- Every tenant-scoped table has an indexed `business_id` column
- Laravel Global Scope (`BusinessScope`) automatically applies `where business_id = ?` to all queries on scoped models
- All Eloquent queries on scoped models go through the scope — raw SQL is forbidden for scoped models
- Repository methods accept `$businessId` or derive it from authenticated user
- Cross-tenant data access blocked at the application layer (no SQL-level mechanism to bypass)
- Super admin can access all tenants via explicit `withoutGlobalScope()` calls (logged in audit)

### 6.4 Data Ownership

- **Business** owns all data under its `business_id`
- **User** belongs to a business via `business_user` pivot (team membership)
- **Customer** profiles are per-business (a phone number appearing under two businesses creates two customer records)
- **Conversations, Messages, Products, Knowledge Base** all scoped to a business
- **Global entities** (AI providers, system settings) are business-agnostic and managed by super admin

### 6.5 Future Scaling Strategy

When single database reaches performance limits:

1. **Read Replicas** — Route dashboard/analytics queries to read replicas first
2. **Sharding** — Shard by business_id using application-level routing (e.g., Vitess or custom proxy)
3. **Separate Database per High-Value Tenant** — Allow enterprise tenants to opt into dedicated databases
4. **Archival** — Move closed conversations (older than X months) to archival tables or cold storage

---

## 7. Coding Standards

### 7.1 Folder Naming

| Context | Convention | Example |
|---------|-----------|---------|
| PHP directories | `PascalCase` | `Services`, `DTOs`, `Http/Controllers` |
| PHP files | `PascalCase` | `SendMessageJob.php` |
| Config files | `snake_case` | `whatsapp.php` |
| Migration files | `snake_case` | `2026_07_22_000001_create_conversations_table.php` |
| Test files | `PascalCase` | `ConversationServiceTest.php` |
| React directories | `kebab-case` | `conversation-inbox/` |
| React component files | `PascalCase` | `ConversationThread.tsx` |
| React hook files | `camelCase` | `useConversations.ts` |
| React utility files | `camelCase` | `formatDate.ts` |
| CSS/SCSS files | `kebab-case` | `inbox-styles.css` |

### 7.2 Namespaces (PHP)

```
App\Contracts\Repositories
App\DTOs\Conversation
App\Enums
App\Events
App\Exceptions
App\Http\Controllers\Api\V1
App\Http\Requests\Api\V1\Conversation
App\Http\Resources\Api\V1
App\Jobs
App\Listeners
App\Models
App\Providers
App\Repositories
App\Rules
App\Services
App\Support\AiProviders
```

### 7.3 Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Controllers | `{Entity}Controller` | `ConversationController` |
| Services | `{Entity}Service` | `ConversationService` |
| Repositories | `{Entity}Repository` | `ConversationRepository` |
| Repository Interfaces | `{Entity}RepositoryInterface` | `ConversationRepositoryInterface` |
| Form Requests | `{Action}{Entity}Request` | `StoreConversationRequest` |
| Resources | `{Entity}Resource` | `ConversationResource` |
| Resource Collections | `{Entity}Collection` | `ConversationCollection` |
| Jobs | `{Action}{Entity}Job` | `SendWhatsAppMessageJob` |
| Events | `{Entity}{Action}Event` | `ConversationCreatedEvent` |
| Listeners | `{Action}{Notification}` | `SendHandoverNotification` |
| DTOs | `{Entity}{Action}DTO` | `ConversationCreateDTO` |
| Enums | `{Entity}Status` | `ConversationStatus` |
| Rules | `{Validation}Rule` | `ValidPhoneNumberRule` |
| Exceptions | `{Entity}{Exception}Exception` | `ConversationNotFoundException` |
| Traits | `{Capability}` | `HasBusinessScope` |
| Policies | `{Entity}Policy` | `ConversationPolicy` |

### 7.4 Controllers

- Single responsibility: one controller per entity/resource
- Only 5 standard methods: `index`, `show`, `store`, `update`, `destroy`
- Additional methods only for truly separate concerns (e.g., `ConversationController@handover`)
- Controller method signature: `public function store(StoreConversationRequest $request): ConversationResource`
- No business logic in controllers — delegate to services
- No direct Eloquent queries in controllers
- Return type-hinted with Resource or JsonResponse

### 7.5 Services

- One service class per domain entity
- Public methods named as actions: `createConversation()`, `assignAgent()`, `handoverToHuman()`
- Services receive dependencies via constructor injection
- Services throw `DomainException` or specific subclasses
- Services return DTOs, Collections, or Models — never Response objects
- Private methods for internal logic decomposition

### 7.6 Repositories

- Interface defines the contract
- Implementation handles Eloquent queries
- Methods: `findById()`, `findAll()`, `create()`, `update()`, `delete()`, `paginate()`
- Additional query methods named descriptively: `findActiveByBusiness()`, `findWithUnreadMessages()`
- Repositories return Models, Collections, LengthAwarePaginator
- No business logic in repositories
- Query scopes and filters as method parameters, not hardcoded

### 7.7 Interfaces

- Prefix with entity name, suffix with `Interface` or `Contract`
- `AiProviderContract` for AI providers (not `AiProviderInterface`)
- `ConversationRepositoryInterface` for repositories
- Define only public methods
- Document with PHPDoc blocks

### 7.8 Requests

- One Form Request per API action
- `authorize()` method calls Gate or Policy
- `rules()` returns validation rules array
- Custom validation in Rule classes
- Messages via `messages()` method when defaults are insufficient
- After-validation hooks for complex preparation

### 7.9 Resources

- One Resource per representation (e.g., `ConversationResource` for detail, `ConversationListResource` for index)
- `toArray($request)` returns structured array
- Use `$this->merge()` and `$this->when()` for conditional data
- Nested relationships via `whenLoaded()`

### 7.10 React Components

- PascalCase file names for components
- One component per file
- Default export for main component, named exports for subcomponents
- Props interface defined in same file or co-located `types.ts`
- Functional components with hooks (no class components)
- Custom hooks for reusable stateful logic (`useConversations`, `useTheme`)

### 7.11 Hooks

- File name: `use{Feature}.ts`
- One hook per file
- Custom hooks encapsulate both state and side effects
- Use SWR or TanStack Query for server state (if adopted)
- Use Zustand for client state

### 7.12 Stores (Zustand)

- One store per domain slice: `useConversationStore`, `useAuthStore`
- Store file: `{domain}-store.ts`
- Actions defined within store, separate from components
- Selectors for derived state

### 7.13 API Conventions

- All routes prefixed with `/api/v1/`
- Resourceful routes: `POST /api/v1/conversations`
- Nested resources: `GET /api/v1/conversations/{id}/messages`
- Actions: `POST /api/v1/conversations/{id}/handover`
- Parameters in camelCase
- Response envelope: `{ data: ..., meta: ..., message: "..." }`

### 7.14 Git Commit Conventions

Follow Conventional Commits:

```
feat: add conversation handover endpoint
fix: prevent duplicate webhook processing
chore: update dependencies
docs: add API authentication guide
refactor: extract AiProviderContract
test: add ConversationService unit tests
style: format with Pint
```

Format: `type(scope): description`

- Scope is optional but recommended: `feat(api)`, `fix(inbox)`
- Description is lowercase, imperative mood, no period

### 7.15 Branch Strategy (Trunk-Based Development)

```
main              Production-ready, deployed
├── feat/*        Feature branches (short-lived, < 2 days)
├── fix/*         Bug fixes
├── chore/*       Tooling, config, dependencies
└── release/*     Release stabilization branches
```

- Feature branches merge into `main` via squash-merge
- `main` must always be deployable
- No long-lived branches
- Every merge triggers CI/CD pipeline

---

## 8. Backend Folder Structure

```
├── app/
│   ├── Contracts/
│   │   ├── Repositories/
│   │   │   ├── BusinessRepositoryInterface.php
│   │   │   ├── ConversationRepositoryInterface.php
│   │   │   ├── CustomerRepositoryInterface.php
│   │   │   ├── KnowledgeBaseArticleRepositoryInterface.php
│   │   │   ├── MessageRepositoryInterface.php
│   │   │   ├── ProductRepositoryInterface.php
│   │   │   ├── TeamMemberRepositoryInterface.php
│   │   │   └── UserRepositoryInterface.php
│   │   ├── AiProviderContract.php
│   │   └── MessagingProviderContract.php
│   │
│   ├── DTOs/
│   │   ├── Ai/
│   │   │   ├── AiCompletionDTO.php
│   │   │   └── AiCompletionResultDTO.php
│   │   ├── Broadcast/
│   │   │   └── BroadcastCampaignDTO.php
│   │   ├── Conversation/
│   │   │   ├── CreateConversationDTO.php
│   │   │   ├── HandoverDTO.php
│   │   │   └── MessageDTO.php
│   │   ├── Customer/
│   │   │   └── CreateCustomerDTO.php
│   │   └── WhatsApp/
│   │       ├── IncomingMessageDTO.php
│   │       └── OutgoingMessageDTO.php
│   │
│   ├── Enums/
│   │   ├── ConversationStatus.php
│   │   ├── MessageDirection.php
│   │   ├── MessageStatus.php
│   │   ├── HandoverReason.php
│   │   ├── BroadcastStatus.php
│   │   ├── Permission.php
│   │   ├── Role.php
│   │   └── SubscriptionPlan.php
│   │
│   ├── Events/
│   │   ├── Conversation/
│   │   │   ├── ConversationCreated.php
│   │   │   ├── ConversationAssigned.php
│   │   │   ├── ConversationHandoverInitiated.php
│   │   │   └── ConversationClosed.php
│   │   ├── Message/
│   │   │   ├── MessageReceived.php
│   │   │   └── MessageSent.php
│   │   ├── Ai/
│   │   │   └── AiResponseGenerated.php
│   │   ├── Customer/
│   │   │   └── CustomerCreated.php
│   │   └── Auth/
│   │       ├── UserLoggedIn.php
│   │       └── UserFailedLogin.php
│   │
│   ├── Exceptions/
│   │   ├── AiProviderException.php
│   │   ├── BusinessNotFoundException.php
│   │   ├── ConversationNotFoundException.php
│   │   ├── HandoverFailedException.php
│   │   ├── TenantMismatchException.php
│   │   ├── WhatsAppApiException.php
│   │   └── Handler.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           ├── AuthController.php
│   │   │           ├── BusinessController.php
│   │   │           ├── TeamMemberController.php
│   │   │           ├── CustomerController.php
│   │   │           ├── ProductController.php
│   │   │           ├── KnowledgeBaseController.php
│   │   │           ├── ConversationController.php
│   │   │           ├── MessageController.php
│   │   │           ├── HandoverController.php
│   │   │           ├── BroadcastController.php
│   │   │           ├── AnalyticsController.php
│   │   │           ├── NotificationController.php
│   │   │           ├── SettingController.php
│   │   │           ├── SubscriptionController.php
│   │   │           ├── WebhookController.php
│   │   │           └── HealthController.php
│   │   │
│   │   ├── Middleware/
│   │   │   ├── BusinessScope.php
│   │   │   ├── VerifyWebhookSignature.php
│   │   │   ├── TenantPermission.php
│   │   │   └── ForceJsonResponse.php
│   │   │
│   │   ├── Requests/
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           ├── Auth/
│   │   │           │   ├── LoginRequest.php
│   │   │           │   ├── RegisterRequest.php
│   │   │           │   └── ResetPasswordRequest.php
│   │   │           ├── Business/
│   │   │           │   ├── StoreBusinessRequest.php
│   │   │           │   └── UpdateBusinessRequest.php
│   │   │           ├── Conversation/
│   │   │           │   ├── StoreConversationRequest.php
│   │   │           │   ├── UpdateConversationRequest.php
│   │   │           │   └── HandoverRequest.php
│   │   │           ├── Customer/
│   │   │           │   └── StoreCustomerRequest.php
│   │   │           ├── KnowledgeBase/
│   │   │           │   └── StoreArticleRequest.php
│   │   │           ├── Product/
│   │   │           │   └── StoreProductRequest.php
│   │   │           └── Broadcast/
│   │   │               └── StoreCampaignRequest.php
│   │   │
│   │   └── Resources/
│   │       └── Api/
│   │           └── V1/
│   │               ├── AuthResource.php
│   │               ├── BusinessResource.php
│   │               ├── ConversationResource.php
│   │               ├── ConversationCollection.php
│   │               ├── MessageResource.php
│   │               ├── CustomerResource.php
│   │               ├── ProductResource.php
│   │               ├── KnowledgeBaseArticleResource.php
│   │               ├── TeamMemberResource.php
│   │               ├── BroadcastResource.php
│   │               ├── NotificationResource.php
│   │               └── SubscriptionResource.php
│   │
│   ├── Jobs/
│   │   ├── ProcessIncomingMessage.php
│   │   ├── SendWhatsAppMessage.php
│   │   ├── GenerateAiReply.php
│   │   ├── ProcessBroadcastCampaign.php
│   │   ├── SendBroadcastMessage.php
│   │   ├── AggregateDailyAnalytics.php
│   │   ├── CleanupExpiredSessions.php
│   │   └── SyncWhatsAppTemplates.php
│   │
│   ├── Listeners/
│   │   ├── DispatchAiReply.php
│   │   ├── LogHandoverEvent.php
│   │   ├── SendHandoverNotification.php
│   │   ├── UpdateCustomerActivity.php
│   │   ├── IncrementAnalyticsCounter.php
│   │   └── SendEmailNotification.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Business.php
│   │   ├── Customer.php
│   │   ├── Conversation.php
│   │   ├── Message.php
│   │   ├── Product.php
│   │   ├── ProductCategory.php
│   │   ├── KnowledgeBaseArticle.php
│   │   ├── KnowledgeBaseCategory.php
│   │   ├── TeamMember.php
│   │   ├── BroadcastCampaign.php
│   │   ├── BroadcastRecipient.php
│   │   ├── Notification.php
│   │   ├── AiProviderConfig.php
│   │   ├── Subscription.php
│   │   ├── SubscriptionInvoice.php
│   │   ├── AuditLog.php
│   │   ├── BusinessHour.php
│   │   └── Holiday.php
│   │
│   ├── Observers/
│   │   ├── ConversationObserver.php
│   │   └── MessageObserver.php
│   │
│   ├── Policies/
│   │   ├── ConversationPolicy.php
│   │   ├── CustomerPolicy.php
│   │   ├── ProductPolicy.php
│   │   ├── KnowledgeBaseArticlePolicy.php
│   │   └── BroadcastPolicy.php
│   │
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   ├── HorizonServiceProvider.php
│   │   ├── RepositoryServiceProvider.php
│   │   └── AiProviderServiceProvider.php
│   │
│   ├── Repositories/
│   │   ├── BusinessRepository.php
│   │   ├── ConversationRepository.php
│   │   ├── CustomerRepository.php
│   │   ├── KnowledgeBaseArticleRepository.php
│   │   ├── MessageRepository.php
│   │   ├── ProductRepository.php
│   │   ├── TeamMemberRepository.php
│   │   └── UserRepository.php
│   │
│   ├── Rules/
│   │   ├── ValidPhoneNumber.php
│   │   ├── ValidWhatsAppTemplate.php
│   │   ├── BusinessHourRule.php
│   │   └── UniquePerBusiness.php
│   │
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── BusinessService.php
│   │   ├── ConversationService.php
│   │   ├── CustomerService.php
│   │   ├── KnowledgeBaseService.php
│   │   ├── ProductService.php
│   │   ├── TeamService.php
│   │   ├── AIService.php
│   │   ├── WhatsAppService.php
│   │   ├── BroadcastService.php
│   │   ├── AnalyticsService.php
│   │   ├── NotificationService.php
│   │   ├── SubscriptionService.php
│   │   └── AuditService.php
│   │
│   ├── Support/
│   │   ├── AiProviders/
│   │   │   ├── OpenAIProvider.php
│   │   │   ├── AnthropicProvider.php
│   │   │   └── GeminiProvider.php
│   │   ├── Messaging/
│   │   │   └── WhatsAppCloudApi.php
│   │   ├── Helpers/
│   │   │   ├── TokenUsageHelper.php
│   │   │   └── MessageTruncater.php
│   │   └── Traits/
│   │       ├── HasBusinessScope.php
│   │       └── HandlesPagination.php
│   │
│   └── Console/
│       └── Commands/
│           ├── SetupTenant.php
│           ├── SyncWhatsAppTemplates.php
│           ├── AggregateAnalytics.php
│           ├── CleanupOldLogs.php
│           └── TestAiProviderConnection.php
│
├── bootstrap/
├── config/
│   ├── whatsapp.php
│   ├── ai-providers.php
│   ├── broadcast.php
│   ├── permissions.php
│   └── tenant.php
│
├── database/
│   ├── migrations/
│   ├── seeders/
│   │   ├── RoleSeeder.php
│   │   ├── PermissionSeeder.php
│   │   └── PlanSeeder.php
│   └── factories/
│
├── routes/
│   ├── api.php              # All API routes
│   ├── web.php              # Webhook routes (no CSRF)
│   └── channels.php         # WebSocket channels
│
├── storage/
│   ├── app/
│   │   ├── public/          # Public assets
│   │   └── private/         # Private files (media, exports)
│   └── logs/
│
├── tests/
│   ├── Unit/
│   │   ├── Services/
│   │   └── DTOs/
│   ├── Feature/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       └── Api/
│   │   │           └── V1/
│   │   ├── Jobs/
│   │   └── Events/
│   └── TestCase.php
│
├── .env.example
├── .phpstan.neon
├── phpunit.xml
└── pint.json
```

### Directory Explanations

| Directory | Purpose |
|-----------|---------|
| `Contracts/` | Interfaces and abstract contracts that define the architecture's dependency inversion boundaries |
| `Contracts/Repositories/` | Repository interfaces that services depend on (not concrete implementations) |
| `DTOs/` | Data Transfer Objects for type-safe data passing between layers |
| `Enums/` | PHP enums for all fixed-value constants (statuses, types, roles) |
| `Events/` | Domain events dispatched when notable things happen |
| `Exceptions/` | Custom exception classes organised by domain |
| `Http/Controllers/Api/V1` | Thin controllers that handle HTTP concerns only |
| `Http/Middleware/` | Custom middleware for cross-cutting concerns |
| `Http/Requests/Api/V1` | Form Request validation classes |
| `Http/Resources/Api/V1` | API Resource transformers |
| `Jobs/` | Queueable jobs for async processing |
| `Listeners/` | Event listeners for side effects |
| `Models/` | Eloquent models with scopes, relations, and accessors only |
| `Observers/` | Model lifecycle hooks |
| `Policies/` | Authorization policies per entity |
| `Providers/` | Service providers for binding and bootstrapping |
| `Repositories/` | Data access implementations |
| `Rules/` | Custom validation rule classes |
| `Services/` | Business logic layer |
| `Support/AiProviders/` | Provider-specific AI implementations (OpenAI, Claude, Gemini) |
| `Support/Helpers/` | Stateless utility functions |
| `Support/Traits/` | Reusable behaviour traits |
| `Console/Commands/` | Artisan commands for maintenance and operations |

---

## 9. Frontend Folder Structure

```
src/
├── app/
│   ├── App.tsx                    # Root component with providers
│   ├── main.tsx                   # Entry point
│   └── router.tsx                 # Route definitions
│
├── assets/
│   ├── images/
│   ├── icons/                     # SVG icons (if not using icon library)
│   └── fonts/
│
├── components/
│   ├── ui/                        # shadcn/ui primitives (generated)
│   │   ├── button.tsx
│   │   ├── input.tsx
│   │   ├── dialog.tsx
│   │   ├── dropdown-menu.tsx
│   │   ├── table.tsx
│   │   ├── card.tsx
│   │   ├── avatar.tsx
│   │   ├── badge.tsx
│   │   ├── separator.tsx
│   │   ├── sheet.tsx
│   │   ├── toast.tsx
│   │   └── ...
│   │
│   ├── layout/                    # Layout components
│   │   ├── AppLayout.tsx          # Main authenticated layout
│   │   ├── AuthLayout.tsx         # Login/register layout
│   │   ├── Sidebar.tsx
│   │   ├── Topbar.tsx
│   │   ├── MobileNav.tsx
│   │   └── Breadcrumbs.tsx
│   │
│   ├── shared/                    # Shared reusable components
│   │   ├── DataTable.tsx
│   │   ├── Pagination.tsx
│   │   ├── SearchInput.tsx
│   │   ├── FilterBar.tsx
│   │   ├── EmptyState.tsx
│   │   ├── ErrorState.tsx
│   │   ├── LoadingState.tsx
│   │   ├── LoadingSpinner.tsx
│   │   ├── ConfirmDialog.tsx
│   │   ├── EmptyState.tsx
│   │   ├── PageHeader.tsx
│   │   ├── StatusBadge.tsx
│   │   ├── ThemeToggle.tsx
│   │   ├── AvatarWithFallback.tsx
│   │   └── NotificationBell.tsx
│   │
│   └── features/                  # Feature-specific components
│       ├── auth/
│       │   ├── LoginForm.tsx
│       │   ├── RegisterForm.tsx
│       │   ├── ForgotPasswordForm.tsx
│       │   └── ResetPasswordForm.tsx
│       ├── business/
│       │   ├── BusinessProfile.tsx
│       │   ├── BusinessSettings.tsx
│       │   ├── WhatsAppConnection.tsx
│       │   └── BusinessHoursEditor.tsx
│       ├── team/
│       │   ├── TeamMemberList.tsx
│       │   ├── InviteMemberDialog.tsx
│       │   └── RoleSelector.tsx
│       ├── customers/
│       │   ├── CustomerTable.tsx
│       │   ├── CustomerProfile.tsx
│       │   └── CustomerMergeDialog.tsx
│       ├── products/
│       │   ├── ProductTable.tsx
│       │   ├── ProductForm.tsx
│       │   └── ProductCategoryTree.tsx
│       ├── knowledge-base/
│       │   ├── ArticleList.tsx
│       │   ├── ArticleEditor.tsx
│       │   ├── ArticleVersionHistory.tsx
│       │   └── CategoryManager.tsx
│       ├── inbox/
│       │   ├── ConversationList.tsx
│       │   ├── ConversationThread.tsx
│       │   ├── MessageBubble.tsx
│       │   ├── MessageInput.tsx
│       │   ├── CustomerSidebar.tsx
│       │   ├── HandoverBar.tsx
│       │   ├── SuggestedReplies.tsx
│       │   └── InboxFilters.tsx
│       ├── broadcast/
│       │   ├── CampaignList.tsx
│       │   ├── CampaignForm.tsx
│       │   └── CampaignAnalytics.tsx
│       ├── analytics/
│       │   ├── AnalyticsOverview.tsx
│       │   ├── ConversationChart.tsx
│       │   ├── AiPerformanceCard.tsx
│       │   ├── AgentPerformanceTable.tsx
│       │   ├── TokenUsageChart.tsx
│       │   └── ExportReportButton.tsx
│       ├── notifications/
│       │   ├── NotificationCenter.tsx
│       │   └── NotificationItem.tsx
│       └── settings/
│           ├── ProfileSettings.tsx
│           ├── AiProviderSettings.tsx
│           ├── NotificationPreferences.tsx
│           └── SubscriptionPlan.tsx
│
├── hooks/                         # Custom React hooks
│   ├── useAuth.ts
│   ├── useBusiness.ts
│   ├── useConversations.ts
│   ├── useCustomers.ts
│   ├── useProducts.ts
│   ├── useKnowledgeBase.ts
│   ├── useBroadcasts.ts
│   ├── useNotifications.ts
│   ├── useTheme.ts
│   ├── useDebounce.ts
│   ├── useIntersectionObserver.ts
│   └── useMediaQuery.ts
│
├── lib/                           # Utility functions and API client
│   ├── api/
│   │   ├── client.ts              # Axios instance with interceptors
│   │   ├── auth.ts
│   │   ├── business.ts
│   │   ├── conversations.ts
│   │   ├── customers.ts
│   │   ├── products.ts
│   │   ├── knowledge-base.ts
│   │   ├── broadcasts.ts
│   │   ├── analytics.ts
│   │   ├── notifications.ts
│   │   ├── settings.ts
│   │   └── subscriptions.ts
│   ├── utils/
│   │   ├── cn.ts                  # class-variance-authority + tailwind-merge
│   │   ├── formatDate.ts
│   │   ├── formatCurrency.ts
│   │   ├── formatNumber.ts
│   │   ├── formatPhone.ts
│   │   ├── truncate.ts
│   │   └── validators.ts
│   └── constants.ts
│
├── stores/                        # Zustand state stores
│   ├── auth-store.ts
│   ├── business-store.ts
│   ├── conversation-store.ts
│   ├── notification-store.ts
│   ├── theme-store.ts
│   └── ui-store.ts                # Sidebar state, modals, etc.
│
├── types/                         # TypeScript type definitions
│   ├── api.ts                     # API response types
│   ├── auth.ts
│   ├── business.ts
│   ├── conversation.ts
│   ├── message.ts
│   ├── customer.ts
│   ├── product.ts
│   ├── knowledge-base.ts
│   ├── broadcast.ts
│   ├── analytics.ts
│   ├── notification.ts
│   └── common.ts                  # Pagination, filters, common types
│
├── providers/                     # React context providers
│   ├── AuthProvider.tsx
│   ├── ThemeProvider.tsx
│   └── QueryProvider.tsx          # TanStack Query provider
│
├── pages/                         # Page-level components (one per route)
│   ├── auth/
│   │   ├── LoginPage.tsx
│   │   ├── RegisterPage.tsx
│   │   ├── ForgotPasswordPage.tsx
│   │   └── ResetPasswordPage.tsx
│   ├── dashboard/
│   │   └── DashboardPage.tsx
│   ├── inbox/
│   │   └── InboxPage.tsx
│   ├── customers/
│   │   ├── CustomerListPage.tsx
│   │   └── CustomerDetailPage.tsx
│   ├── products/
│   │   ├── ProductListPage.tsx
│   │   └── ProductFormPage.tsx
│   ├── knowledge-base/
│   │   ├── ArticleListPage.tsx
│   │   └── ArticleEditorPage.tsx
│   ├── broadcast/
│   │   ├── CampaignListPage.tsx
│   │   └── CampaignFormPage.tsx
│   ├── analytics/
│   │   └── AnalyticsPage.tsx
│   ├── team/
│   │   └── TeamPage.tsx
│   ├── settings/
│   │   ├── ProfilePage.tsx
│   │   ├── BusinessSettingsPage.tsx
│   │   ├── AiSettingsPage.tsx
│   │   ├── NotificationSettingsPage.tsx
│   │   └── SubscriptionPage.tsx
│   └── 404.tsx
│
├── styles/
│   ├── globals.css                # Tailwind directives + base styles
│   └── themes.css                 # CSS custom properties for theming
│
└── tests/
    ├── components/
    ├── hooks/
    ├── stores/
    └── utils/
```

### Directory Explanations

| Directory | Purpose |
|-----------|---------|
| `app/` | App-wide setup: root component, entry point, routing |
| `components/ui/` | shadcn/ui primitive components (auto-generated, do not edit) |
| `components/layout/` | Layout structure components (sidebar, topbar, shells) |
| `components/shared/` | Shared reusable components used across features |
| `components/features/` | Feature-specific components, grouped by domain |
| `hooks/` | Custom React hooks for reusable stateful logic |
| `lib/api/` | API client modules — one per domain, thin wrappers around Axios |
| `lib/utils/` | Pure utility functions |
| `stores/` | Zustand stores for client-side state management |
| `types/` | TypeScript type definitions for the entire domain model |
| `providers/` | React context providers wrapping the app tree |
| `pages/` | Page-level components assembled from feature components |
| `styles/` | Global styles, theme tokens |

---

## 10. Database Strategy

### 10.1 Entity Groups

The database is organised into logical entity groups. No migrations are created in this phase.

**Authentication & Identity**
- `users` — dashboard users (business owners, agents, super admins)
- `personal_access_tokens` — Sanctum token storage
- `password_reset_tokens` — Password reset flow
- `sessions` — Session information (optional, Sanctum may use)

**Multi-Tenancy**
- `businesses` — tenant (business) profiles
- `business_user` — pivot table linking users to businesses with role

**Team & Permissions**
- `permissions` — Spatie Permission: all permission definitions
- `roles` — Spatie Permission: role definitions
- `model_has_roles` — Spatie Permission: role assignment pivot
- `model_has_permissions` — Spatie Permission: direct permission assignment pivot

**Customers**
- `customers` — customer profiles per business

**Products**
- `product_categories` — product category tree per business
- `products` — product/service catalog per business

**Knowledge Base**
- `knowledge_base_categories` — article categories per business
- `knowledge_base_articles` — knowledge content per business
- `knowledge_base_article_versions` — version history for articles

**Conversations & Messages**
- `conversations` — conversation threads per business per customer
- `messages` — individual messages within conversations
- `message_status_updates` — delivery/read receipt timestamps

**AI & Automation**
- `ai_provider_configs` — per-business AI provider configuration
- `ai_response_logs` — token usage, latency, cost per AI call

**Broadcast**
- `broadcast_campaigns` — campaign definitions
- `broadcast_recipients` — individual message records per campaign

**Human Handover**
- `handover_logs` — audit of handover events (initiated by, reason, assigned to, resolved at)

**Analytics**
- `analytics_daily` — daily aggregated metrics per business
- `analytics_event_logs` — raw event stream for custom queries

**Notifications**
- `notifications` — in-app notifications (Laravel's notifiable system)

**Subscriptions & Billing**
- `subscription_plans` — plan definitions (global, not tenant-scoped)
- `subscriptions` — active/past subscriptions per business
- `subscription_invoices` — invoice records

**Audit**
- `audit_logs` — immutable audit trail

**WhatsApp Integration**
- `whatsapp_phone_numbers` — registered phone numbers per business
- `whatsapp_templates` — synced template messages
- `whatsapp_webhook_events` — raw incoming webhook payloads (for debugging)

**System**
- `job_batches` — Horizon job batch tracking
- `failed_jobs` — failed queue job records
- `cache` — cache store table (if using database cache)
- `sessions` — session storage (if using database sessions)
- `media` — if using Spatie Media Library for file attachments

### 10.2 Relationships

```
User ──< BusinessUser >── Business
Business ──< Customer
Business ──< ProductCategory ──< Product
Business ──< KnowledgeBaseCategory ──< KnowledgeBaseArticle
Business ──< AiProviderConfig
Business ──< BroadcastCampaign ──< BroadcastRecipient
Business ──< Subscription
Business ──< HandoverLog
Business ──< AnalyticsDaily
Business ──< WhatsAppPhoneNumber
Business ──< WhatsAppTemplate

Customer ──< Conversation ──< Message
Conversation ──< HandoverLog
Conversation >── User (assigned agent)
Message >── MessageStatusUpdate

User ──< Notification
```

### 10.3 Future Database Organization

- All tenant-scoped tables use `business_id` as the sharding/partitioning key
- The `conversations` and `messages` tables will be the largest — partition by `business_id` from the start
- Analytics tables are append-only with scheduled aggregation into summary tables
- Audit logs are append-only with a scheduled archival policy (move to archive table after 90 days)
- Index strategy: indexes on `business_id` + commonly filtered/joined columns
- Full-text indexes on `messages.body`, `knowledge_base_articles.content`, `products.name`

### 10.4 Migration Strategy

- Each migration file is atomic (one logical change)
- Migrations are reversible (`down()` method) only for the current sprint — revert support is dropped after the next deployment
- Naming: `YYYY_MM_DD_HHMMSS_description.php`
- All foreign keys are defined in separate migrations, after the referenced tables exist
- Indexes are created in the same migration as the table
- Never modify a published migration — create a new one
- Seeders are for reference data only (roles, permissions, plans) — never for test data
- Factories handle test/development data generation

---

## 11. Security Architecture

### 11.1 Authentication

- Laravel Sanctum token-based authentication for SPA
- Tokens issued with specific abilities (`conversations:read`, `conversations:write`, etc.)
- Token expiration configurable per ability (short-lived for sensitive operations)
- Refresh token rotation: issuing a new token invalidates the previous one
- Failed login rate limiting: 5 attempts per minute per IP/email
- Account lockout after 10 failed attempts (30-minute cooldown)
- Email verification required before accessing dashboard features
- Two-factor authentication optional but enforced for certain roles (Admin, Super Admin)

### 11.2 Authorization

- Spatie Permission package for role-based access control
- Two permission levels:
  - System-level: `super-admin` (full access to all tenants)
  - Business-level: `business-admin`, `agent`, `viewer`
- Business-level roles scoped to a single business via the `business_user` pivot
- Laravel Policies for entity-level authorization (e.g., "can this agent view this conversation?")
- `authorize()` calls in Form Requests and Controllers
- Permission check middleware for route-level gate: `$this->middleware('permission:conversations.create')`

### 11.3 Tenant Isolation

- Global scope (`BusinessScope`) applied to all tenant-scoped models
- `business_id` automatically set on create via authenticated user's business
- Middleware extracts business from authenticated user on every request
- Cross-tenant data access is a security incident — logged and alerted
- Super admin can bypass scope only via explicit `withoutGlobalScope()` with audit log entry

### 11.4 API Security

- All API routes require authentication (except auth endpoints and webhooks)
- CORS restricted to the dashboard's origin(s)
- CSRF protection on web routes (though API uses tokens)
- `X-Content-Type-Options: nosniff` header
- `X-Frame-Options: DENY` header
- `Strict-Transport-Security` header in production
- `Content-Security-Policy` header for frontend
- Referrer-Policy: `strict-origin-when-cross-origin`

### 11.5 Rate Limiting

| Endpoint Group | Limit | Window |
|---------------|-------|--------|
| Auth (login, register, password) | 5 requests | 1 minute |
| General API | 60 requests | 1 minute |
| WhatsApp webhook | 200 requests | 1 minute |
| AI generation | 20 requests | 1 minute |
| Broadcast API | 10 requests | 1 minute |

Rate limit headers returned on every response: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`.

### 11.6 Encryption

| Data | Encryption | Mechanism |
|------|-----------|-----------|
| Passwords | Bcrypt | Laravel Hash facade |
| Database at rest | AES-256 | MySQL TDE / disk-level encryption |
| Secrets in DB (API keys) | AES-256 | Laravel `encrypt()` / `Crypt` facade |
| WhatsApp webhook secret | AES-256 | Laravel `encrypt()` |
| Files at rest | AES-256 | Filesystem-level or S3 server-side |
| Transit | TLS 1.3 | Nginx / Cloudflare |

### 11.7 Secret Management

- Never store secrets in code, config files, or database in plaintext
- All secrets in `.env` file (not committed to git)
- Production secrets via environment variables set in deployment pipeline
- AI provider API keys encrypted at rest in the `ai_provider_configs` table
- WhatsApp access tokens encrypted at rest
- Secrets rotated on a scheduled basis (quarterly for API keys, on incident for compromised keys)
- `.env.example` contains placeholder values only, never real secrets

### 11.8 Input Validation

- Every input is validated via Form Request rules
- Strict type validation: `integer`, `boolean`, `string`, `array`, `email`, `url`, etc.
- SQL injection prevented by Eloquent parameter binding (raw queries forbidden)
- XSS prevented by output escaping in API Resources
- File uploads validated by mime type, size, and virus scanning
- HTML/script tags stripped from text fields (or rejected with validation error)

### 11.9 Output Escaping

- API responses: HTML entities escaped in all text fields via `e()` helper or Resource transformation
- JSON responses are safe by default (JSON encoder handles escaping)
- Frontend: React's JSX escaping handles XSS automatically
- Never output user-generated content without escaping

### 11.10 Audit Logging

All of the following events are logged to the `audit_logs` table:

- Authentication: login, logout, failed login, password change, 2FA toggle
- User management: user invite, role change, account suspend/reactivate
- Business settings: AI provider change, business profile change
- Conversations: handover initiated, handover completed, assignment change
- Customer: profile merge, data export
- Subscription: plan change, payment method change, cancellation
- Permissions: any role/permission modification

Audit log entries include: timestamp, actor (user ID), action, entity type, entity ID, old values, new values, IP address, user agent.

---

## 12. API Design Standards

### 12.1 API Versioning

- Version prefix in URL path: `/api/v1/`
- Breaking changes create a new version (`/api/v2/`)
- Non-breaking changes add to current version
- Deprecated versions documented with sunset header: `Sunset: Sat, 22 Jul 2027 00:00:00 GMT`
- Minimum version support: current + 1 previous version

### 12.2 Response Format

**Success Response:**
```json
{
  "data": { ... },
  "message": "Conversation created successfully."
}
```

**Collection Response:**
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 145,
    "from": 1,
    "to": 15
  }
}
```

### 12.3 Error Format

```json
{
  "message": "Validation failed.",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email must be a valid email address."]
  },
  "code": 422
}
```

**Detailed error fields:**

| Field | Description | Required |
|-------|-------------|----------|
| `message` | Human-readable error summary | Always |
| `errors` | Field-level error messages | For 422 only |
| `code` | HTTP status code | Always |
| `trace_id` | Unique identifier for the request (for debugging) | Recommended |

### 12.4 Pagination

- Query parameters: `?page=1&per_page=15`
- Default `per_page`: 15
- Maximum `per_page`: 100
- Response meta includes: `current_page`, `last_page`, `per_page`, `total`, `from`, `to`
- Link header for prev/next navigation (optional)

### 12.5 Filtering

- Query parameter format: `?filter[status]=active&filter[assigned_to]=42`
- Multiple values: `?filter[status][]=open&filter[status][]=pending`
- Date range: `?filter[created_at][from]=2026-01-01&filter[created_at][to]=2026-07-22`
- Boolean: `?filter[has_unread]=true`
- Null: `?filter[assigned_to]=null`

### 12.6 Sorting

- Query parameter: `?sort=created_at` (ascending) or `?sort=-created_at` (descending with minus prefix)
- Multi-field: `?sort=-status,created_at`
- Allowed sort columns defined per endpoint (invalid columns return 400)

### 12.7 Searching

- Query parameter: `?q=search+term`
- Searches across relevant columns (configurable per endpoint)
- Full-text search on supported columns
- Minimum search term length: 2 characters

### 12.8 HTTP Status Codes

| Code | Usage |
|------|-------|
| 200 | Successful GET, PUT, PATCH |
| 201 | Successful POST (resource created) |
| 204 | Successful DELETE (no content) |
| 400 | Bad request (invalid input) |
| 401 | Unauthenticated (missing/invalid token) |
| 403 | Forbidden (authenticated but not authorized) |
| 404 | Resource not found |
| 409 | Conflict (duplicate, state conflict) |
| 422 | Validation error |
| 429 | Rate limit exceeded |
| 500 | Internal server error |

---

## 13. UI/UX Standards

### 13.1 Navigation Philosophy

- Persistent left sidebar with primary navigation items
- Top bar for context-aware actions, breadcrumbs, and user menu
- Sidebar collapses to icon-only on smaller screens
- Navigation items grouped logically: Inbox, Customers, Products, Knowledge Base, Broadcast, Analytics, Settings
- Active route highlighted with accent colour
- Keyboard shortcuts for power users: `g i` → go to inbox, `g d` → go to dashboard, `?` → keyboard shortcut help

### 13.2 Layout System

- 3-tier layout: AppShell → Page → Content Card
- AppShell: sidebar + topbar + main content area
- Page: consistent padding, max-width container
- Content Card: white/dark surface for each logical section
- Responsive breakpoints: sm (640px), md (768px), lg (1024px), xl (1280px), 2xl (1536px)
- Main content: flexible, sidebar: fixed width (280px expanded, 64px collapsed)

### 13.3 Spacing

- 4px base unit (4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80, 96)
- Page padding: p-6 (24px)
- Card padding: p-6 (24px)
- Component gap: gap-4 (16px)
- Section spacing: space-y-8 (32px)
- List spacing: space-y-2 (8px)

### 13.4 Typography

| Element | Size | Weight | Line Height |
|---------|------|--------|-------------|
| Page title | text-3xl (30px) | font-bold (700) | leading-tight |
| Section heading | text-xl (20px) | font-semibold (600) | leading-snug |
| Card title | text-base (16px) | font-semibold (600) | leading-normal |
| Body text | text-sm (14px) | font-normal (400) | leading-relaxed |
| Small/meta | text-xs (12px) | font-normal (400) | leading-normal |
| Code/mono | text-sm (14px) | font-normal (400) | font-mono |

Font family: Inter (sans-serif) for UI, JetBrains Mono (monospace) for code.

### 13.5 Colors

**Light Mode:**
- Background: white (#ffffff), soft gray (#f9fafb)
- Surface: white (#ffffff), subtle gray (#f3f4f6)
- Text: gray-900, gray-700, gray-500
- Accent: blue-600 (primary), blue-50 (light accent bg)
- Destructive: red-600
- Success: green-600
- Warning: amber-600

**Dark Mode:**
- Background: gray-950 (#0a0a0a), gray-900 (#111827)
- Surface: gray-900, gray-800
- Text: gray-50, gray-300, gray-500
- Accent: blue-400 (primary), blue-950 (light accent bg)
- Destructive: red-400
- Success: green-400
- Warning: amber-400

All colours accessible with WCAG AA contrast ratios.

### 13.6 Component Strategy

- shadcn/ui for all primitive components (button, input, dialog, table, etc.)
- Components are composable, not monolithic
- Each component accepts `className` for override via `cn()` utility
- Components are server-safe (no direct DOM access in render)
- Custom components built on top of shadcn/ui primitives
- Component props follow HTML attribute conventions where possible

### 13.7 Icons

- Lucide React as the icon library (consistent with shadcn/ui)
- SVG-based, no icon fonts
- Icon size: 16px (inline), 20px (button), 24px (empty states)
- Decorative icons marked with `aria-hidden="true"`
- Informational icons have `aria-label` for screen readers

### 13.8 Forms

- Label above input, left-aligned
- Inline validation error below input
- Submit button right-aligned
- Loading state on submit button (spinner + disabled)
- Optional fields marked with `(optional)` label
- Required fields implied (no asterisk unless explicitly requested)
- Autofocus first field on dialog open
- Tab order follows visual order

### 13.9 Tables

- Alternating row colours (subtle) for readability
- Column sorting indicators (arrows in header)
- Selectable rows via checkbox column
- Action column with dropdown menu (edit, delete, etc.)
- Empty state with illustration when no data
- Sticky header on scroll
- Row hover state with highlight

### 13.10 Modals

- Dialog sheet (from shadcn/ui)
- Close on Escape key and backdrop click
- Focus trap within modal
- Scroll lock on body when open
- Title + description + content + action buttons
- Loading state on primary action button

### 13.11 Responsive Behavior

- Sidebar: visible on lg+, overlay drawer on smaller screens
- Inbox: two-column layout on lg+ (list + thread), single column with back button on smaller screens
- Tables: horizontal scroll on smaller screens (hide non-essential columns)
- Forms: single column on mobile, multi-column on desktop
- Analytics charts: full width on mobile, grid layout on desktop
- Modals: full-screen sheet on mobile, centered dialog on desktop

### 13.12 Loading States

- Skeleton loading for list/card content
- Spinner for button/submit actions
- Progress bar for page transitions (if using router)
- Shimmer animation for skeleton placeholders
- Skeleton matches the final layout shape

### 13.13 Empty States

- Illustration or icon
- Title: "No conversations yet"
- Description: "When customers send messages, they'll appear here."
- Action button if applicable: "Learn more about setting up WhatsApp"
- No empty states show raw table headers (unless loading)

### 13.14 Error States

- Inline error for form fields
- Toast notification for API errors (auto-dismiss after 5s)
- Error boundary for React component crash (fallback UI, not white screen)
- Full-page error state for critical failures (network down, 500)
- "Try again" button for retryable errors

---

## 14. Development Rules

These rules are **permanent** and **must be followed** in every implementation phase.

### Code Organization

1. **Feature-based modular structure**: Group code by business domain (conversations, products, etc.), not by technical layer (controllers, models, etc.).
2. **No circular dependencies**: Services must never depend on other services that depend back on them. Use events for decoupled side effects.
3. **One class, one file**: Every class gets its own file. No inline class definitions.
4. **No God classes**: Any class exceeding 400 lines of code must be refactored. Service classes exceeding 20 public methods must be split.
5. **No static methods in application logic**: Static methods are only permitted in helpers and enums — never in Services, Repositories, or Controllers.

### Architecture Enforcement

6. **Services are the only business logic layer**: Controllers, Jobs, Commands, and Event Listeners must delegate to Services. Duplication of business logic is forbidden.
7. **Repositories are the only data access layer**: No Eloquent queries in Controllers, Services, Jobs, or Listeners.
8. **Form Requests are the only validation layer**: No `$request->validate()` in controllers. No validation in services or models.
9. **API Resources are the only response transformation layer**: No `toArray()`, `toJson()`, or manual array building in controllers or models for API responses.
10. **Contracts/Interfaces for all boundaries**: Every service external dependency (repositories, AI providers, messaging providers) must be behind an interface.

### Multi-Tenant Rules

11. **Every tenant-scoped query must pass through BusinessScope**: No manual `->where('business_id', ...)` — always use the global scope.
12. **No cross-tenant data access**: Code must never access data belonging to a different business unless the request is from a super admin and explicitly logged.
13. **Tenant ID must never be user-supplied**: The `business_id` is derived from the authenticated user, never from request input.

### Database Rules

14. **All migrations must be reversible for the current sprint**: After the sprint is deployed, reversal may be removed.
15. **No raw SQL in application code**: Raw SQL is only permitted in database migrations. All queries use Eloquent or the Query Builder.
16. **N+1 queries must be eliminated**: Always eager-load relationships. Monitor query count in every PR.
17. **Foreign keys must be indexed**: Every foreign key column needs an explicit index.

### Security Rules

18. **Secrets never in code**: API keys, tokens, passwords never appear in any committed file. `.env` only.
19. **Input validation on every endpoint**: No endpoint accepts data without a Form Request.
20. **Authorization check on every mutating endpoint**: Every `store`, `update`, `delete` action must verify the user's permission.
21. **Escape all output**: User-generated content must never be output raw.
22. **Audit log all sensitive operations**: Every create, update, delete on user, business, role, permission, conversation, customer, subscription, and AI config.

### Testing Rules

23. **Tests are mandatory for every feature**: No feature branch merges without tests.
24. **Service layer must have unit tests**: Every public method of a Service must have at least one positive and one negative test.
25. **API endpoints must have feature tests**: Every endpoint tests: success response, validation failure, authorization failure, authentication failure, not-found response.
26. **Test coverage minimum 80%**: Measured by PHPUnit/coverage tool. CI fails below threshold.

### API Rules

27. **Version all APIs**: Every endpoint is under `/api/v{n}/`.
28. **Return consistent response format**: Follow the response format defined in this document. No deviations.
29. **Pagination on all list endpoints**: No list endpoint returns an unbounded collection. Always paginate.
30. **Rate limiting on all endpoints**: Every route group has a rate limit definition in `RouteServiceProvider` or middleware.

### Frontend Rules

31. **No class components**: All React components must be functional components with hooks.
32. **No inline styles**: All styling via Tailwind utility classes or CSS modules. Inline `style` props are forbidden.
33. **One component per file**: No exceptions.
34. **Custom hooks for reusable logic**: Any stateful logic used in more than one component must be extracted to a custom hook.
35. **API calls only through `lib/api/` modules**: Components never call Axios or fetch directly.

### Git & Workflow Rules

36. **Conventional commits**: Every commit message follows `type(scope): description`.
37. **Feature branches short-lived**: Maximum 2 days. If a feature takes longer, split it.
38. **Main is always deployable**: No work-in-progress code on main.
39. **PRs must be reviewed**: Every pull request requires at least one approval from a senior engineer.
40. **CI must pass before merge**: All tests, linting, and static analysis must pass in CI before merging.

### Documentation Rules

41. **PHPDoc on all public methods**: Every public method in every class must have a PHPDoc block describing parameters and return type.
42. **Readme updated with each feature**: When adding a new feature module, update the relevant README section.
43. **Architecture decisions recorded**: Any significant deviation from this blueprint must be documented in an ADR (Architecture Decision Record) stored at `docs/adr/`.
44. **No code comments for "what"**: Comments explain "why", never "what" — code should be self-documenting for "what".

### Monorepo Management

45. **No orphan files**: Every file in the repository must belong to a feature module or a well-known utility directory.
46. **No dead code**: Unused imports, variables, functions, and components must be removed before commit.
47. **Dependencies explicitly declared**: Every package used must be in `composer.json` or `package.json`. No transitive dependency usage.

---

*End of Architecture Blueprint — This document becomes the single source of truth for all future implementation phases.*
