# Support Desk Lite

RESTful API for support ticket management, built with Laravel 12 and PHP 8.4.

## 📋 Prerequisites

- PHP 8.4 or higher
- Composer
- SQLite (included with PHP, no additional setup needed)

## 🚀 Quick Setup

```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Run migrations
php artisan migrate

# 4. Seed database (optional - creates sample tags and user)
php artisan db:seed

# 5. Start server
php artisan serve
```

API will be available at `http://localhost:8000`

**Note**: The `.env.example` file is already configured for SQLite. No database server setup required!

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific tests
php artisan test --filter=TicketCreation
```

**Coverage**: 32 tests, 135 assertions

## 📡 Main Endpoints

### Tickets
- `GET /api/tickets` - List tickets (filters, search, pagination)
- `POST /api/tickets` - Create ticket
- `GET /api/tickets/{id}` - Get ticket details
- `PATCH /api/tickets/{id}` - Update ticket

### Ticket Updates
- `POST /api/tickets/{id}/updates` - Add comment/internal note

### Tags
- `PUT /api/tickets/{id}/tags/{tag}` - Attach tag
- `DELETE /api/tickets/{id}/tags/{tag}` - Detach tag

### Customers
- `POST /api/customers` - Create customer

## 🏗 Architecture

- **Service Layer**: Business logic in `TicketService`
- **Form Requests**: Validation and authorization
- **API Resources**: Data transformation
- **Policies**: Access control
- **Enums**: Type safety for status, priority, and types

## 📋 Business Rules

1. **Resolved Status**: When a ticket changes to `resolved`, `resolved_at` is automatically set
2. **Reopening**: Comments on `resolved` or `closed` tickets reopen to `open`
3. **Internal Notes**: Only visible to authenticated users (internal agents)

## 🛠 Tech Stack

- Laravel 12
- PHP 8.4
- SQLite (development)
- Pest (testing)

## 📁 Project Structure

```
app/
├── Enums/          # PHP Enums
├── Exceptions/     # Custom exceptions
├── Http/
│   ├── Controllers/ # API controllers
│   ├── Requests/    # Form Requests
│   └── Resources/   # API Resources
├── Models/          # Eloquent models
├── Policies/        # Authorization policies
└── Services/        # Service layer
```

## 🏛 Architecture Decisions

### Service Layer Pattern
Business logic is centralized in `TicketService` to keep controllers thin and improve testability. All business rules (resolved_at, ticket reopening) are implemented in one place.

### Form Requests for Validation & Authorization
All endpoints use Form Requests that handle both validation and authorization via Policies. This ensures separation of concerns and reusability.

### API Resources for Data Transformation
Resources standardize JSON responses and enable conditional filtering (e.g., internal notes visibility based on user permissions).

### Policy-Based Authorization
Policies (`TicketPolicy`, `TicketUpdatePolicy`) manage access control. The `viewInternalNote` method enforces the business rule that internal notes are only visible to authenticated internal users.

### Enum Usage
PHP 8.1+ Enums provide type safety for status, priority, and update types, preventing invalid values at compile time.

## ⚖️ Tradeoffs Made

### Authentication Simplification
**Tradeoff**: Assumed authentication rather than implementing full auth system.

**Reasoning**: Focus on core business logic and architecture. Authentication can be added later (Sanctum, Passport). Tests use `actingAs()` to simulate authenticated users.

**Impact**: ✅ Faster development, cleaner codebase | ⚠️ Requires auth middleware for production

### SQLite for Development
**Tradeoff**: Used SQLite instead of requiring MySQL/PostgreSQL setup.

**Reasoning**: Easier setup for evaluators (no database server needed). All required features supported (FKs, constraints, indexes). Can easily switch to MySQL/PostgreSQL via `.env`.

**Impact**: ✅ Zero-configuration setup, faster tests | ⚠️ Some edge cases might differ from production

### RESTful Tags API
**Tradeoff**: Implemented `PUT/DELETE /api/tickets/{id}/tags/{tag}` instead of `POST /api/tickets/{id}/tags` with action field.

**Reasoning**: More RESTful and semantic. PUT for attach, DELETE for detach follows HTTP standards better.

**Impact**: ✅ Better API design, clearer semantics | ⚠️ Slightly different from requirement (but functionally equivalent)

### No Frontend
**Tradeoff**: API-only implementation, no UI.

**Reasoning**: Focus on backend architecture and business logic. API can be consumed by any frontend. More time for testing and code quality.

**Impact**: ✅ More time for backend quality | ⚠️ No visual demonstration

## 📮 Postman Collection

A Postman collection is included for easy API testing:

**File**: `Support_Desk_Lite.postman_collection.json`

### How to Use

1. Import the collection into Postman
2. Set the `base_url` variable (default: `http://localhost:8000`)
3. Start the Laravel server: `php artisan serve`
4. **Important**: Run `php artisan db:seed` to create sample tags (or create tags manually)
5. Update variables (`ticket_id`, `customer_id`, `tag_id`) with actual IDs from responses
6. Run requests in order (create customer → create ticket → add updates → attach tags)

The collection includes:
- All API endpoints
- Example request bodies
- Query parameters for filtering/searching
- Variable placeholders for easy testing

**Note**: Tags are created by the seeder. If you haven't run the seeder, create tags first using Laravel Tinker or the seeder.

## 🔮 Next Steps

With more time, I would prioritize improvements in this order:

### High Priority (Security & Performance)

1. **Rate Limiting** - Implement throttling on ticket creation and update endpoints to prevent abuse
2. **Full Authentication** - Integrate Laravel Sanctum/Passport for token-based authentication
3. **Caching Strategy** - Cache frequently accessed data (tags list, user lists) to reduce database load
4. **Query Optimization** - Add database indexes for common filter combinations and review query performance

### Medium Priority (Features & UX)

5. **Soft Deletes** - Implement soft deletes for Tickets and Customers to preserve data history
6. **User Roles & Permissions** - Add role-based access control (agent/admin/customer) with granular permissions
7. **Activity Logging** - Audit trail for all ticket changes (who, what, when) for compliance

### Nice to Have (Enhancements)

8. **API Documentation** - Generate OpenAPI/Swagger documentation for better developer experience
9. **Queue Notifications** - Email/SMS notifications for ticket updates using Laravel queues
10. **File Attachments** - Support for file uploads on tickets and updates
11. **Ticket Templates** - Pre-defined ticket templates for common issues
12. **Advanced Search** - Full-text search
13. **Analytics Dashboard** - Metrics and reports (response times, resolution rates, agent performance)
