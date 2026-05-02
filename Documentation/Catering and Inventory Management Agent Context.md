# KitchenFlow/Catering and Inventory Management — AI Agent Context File
Betopia Limited | April 2026

> Read this entire file before writing a single line of code.
> This is your source of truth for architecture decisions, database rules, and business logic nuances.

---

## 1. Project Overview

KitchenFlow and "Catering and Inventory Management" are interchangeable names and it is a B2B SaaS, web-based catering and inventory management platform built for organizations that operate an internal canteen or catering service. It is delivered as a subscription-based product by Betopia Limited.

**Three roles exist in the system — fixed, no dynamic assignment:**
- `admin` — manages users, menu, inventory, and views the dashboard
- `kitchen_staff` — processes orders, manages availability, records cash transactions
- `user` — browses menu and places orders only

---

## 2. Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11, PHP 8.2 |
| Frontend | Next.js 14 (App Router), TypeScript, Tailwind CSS |
| Database | PostgreSQL 16 |
| Web Server | Nginx (Alpine) |
| Runtime | Node 20 LTS, Composer 2 |
| Containerization | Docker Compose |

**Hard rules:**
- PHP 8.2 features only — no 8.3+ syntax
- Laravel 11 conventions only — no Laravel 12+ syntax
- Next.js 14 App Router only — no Pages Router, no Next.js 15+ syntax
- Backend is REST API only — no Blade views, no Inertia
- Frontend consumes API via `NEXT_PUBLIC_API_URL=http://localhost:8000/api`
- No raw SQL — Eloquent ORM only
- Laravel: Service layer pattern, Resource Controllers, Form Requests, API Resources
- Next.js: Server Components by default, Client Components only when interactivity requires it

---

## 3. Database — Entity Summary

8 entities total. Every entity is justified by a functional requirement in the SRS.

| Table | Purpose |
|-------|---------|
| `tenants` | One row per subscribing organization. Highest-level entity. |
| `users` | All system actors — Admin, Kitchen Staff, User — in one table. |
| `password_reset_tokens` | Secure write-once tokens for password reset flow. |
| `categories` | Menu item categories, scoped per tenant. |
| `menu_items` | Core catalog — pricing, availability, stock. |
| `orders` | Central transactional entity. Created when a User places an order. |
| `order_items` | Junction table between orders and menu_items. |
| `transactions` | One-to-one with orders. Cash recording at point of serving. |

---

## 4. Tenant — The Highest-Level Gate

### What it is
Every subscribing organization is a `tenant`. All users, categories, and data belong to a tenant. If a tenant's subscription is inactive or expired, **no user in that organization can access the system regardless of role.**

### How the check works
Every API request passes through this middleware chain in order:

```
Request
  ↓
1. auth:sanctum                  → validates token, identifies user
  ↓
2. CheckTenantSubscription       → checks tenant is active and not expired
  ↓
3. Role middleware                → checks user role is permitted on this route
  ↓
4. Controller
```

### Middleware logic
```php
$tenant = $request->user()->tenant;

if (!$tenant->subscription_active) {
    return response()->json(['message' => 'Organization subscription is inactive.'], 403);
}

if ($tenant->subscription_ends_at < now()) {
    return response()->json(['message' => 'Organization subscription has expired.'], 403);
}

return $next($request);
```

### How tenant is toggled
Betopia manually sets `subscription_active = false` or updates `subscription_ends_at` on the tenant record. No payment gateway is involved — subscription management happens contractually outside the software.

### Tenant scoping rules per table

| Table | How tenant is scoped |
|-------|---------------------|
| `tenants` | root entity |
| `users` | direct `tenant_id` column |
| `categories` | direct `tenant_id` column |
| `menu_items` | via `category_id → categories.tenant_id` |
| `orders` | via `user_id → users.tenant_id` |
| `order_items` | via `order_id → orders → users.tenant_id` |
| `transactions` | via `order_id → orders → users.tenant_id` |

Only `users` and `categories` carry a direct `tenant_id`. Everything else resolves through the relationship chain. Never add redundant `tenant_id` columns to downstream tables.

**Always scope queries to the authenticated user's tenant. Never return cross-tenant data.**

Example for menu items:
```php
MenuItem::whereHas('category', function ($query) use ($tenantId) {
    $query->where('tenant_id', $tenantId);
})->get();
```

---

## 5. Soft Deletes — Rules and Usage

Soft deletes (`deleted_at`) must be explicitly enabled per model. Nothing is on by default.

**Two steps required:**
1. Migration must include `$table->softDeletes();`
2. Model must include `use SoftDeletes;` trait

When `SoftDeletes` is active, Laravel automatically appends `WHERE deleted_at IS NULL` to all Eloquent queries. Soft-deleted records are invisible by default.

**Which tables use soft deletes and why:**

| Table | Reason |
|-------|--------|
| `users` | Deleted users must still be referenced by historical orders |
| `categories` | Deleted categories must still be referenced by historical menu items |
| `menu_items` | Deleted items must still be referenced by historical order_items |

**Which tables do NOT use soft deletes and why:**

| Table | Reason |
|-------|--------|
| `orders` | Orders are permanent records. `canceled` status is the only terminal state. |
| `order_items` | Immutable once created. No deletion path exists. |
| `transactions` | Permanent financial records. Never deletable under any circumstance. |
| `password_reset_tokens` | Hard deleted immediately after use. Retaining them is a security liability. |
| `tenants` | Never deleted. A lapsed tenant is deactivated, not removed. |

---

## 6. Critical Business Rules

### Roles
- Role is stored as an enum on `users`: `admin`, `kitchen_staff`, `user`
- No dynamic permission assignment exists in Phase 1
- All role checks are enforced server-side on every request — never rely on frontend-only guards

### Password Reset Tokens
- Write-once — never updated, only created or deleted
- Validated server-side with: `WHERE token = ? AND expires_at > NOW()`
- Hard deleted immediately after a successful reset
- If a user requests a new reset, the old token is deleted and a new one is inserted

### Menu Availability (`is_available`)
- Single flag — covers both manual toggle by Kitchen Staff and auto-disable at zero stock
- Auto-disabled when `stock_quantity` reaches 0 (FR-04.1)
- Re-enabling always requires an explicit manual action — never automatic (FR-04.2)
- Users only see items where `is_available = true` and `deleted_at IS NULL`

### Menu Item Image & Description
- `description` is free-form text — nullable, optional at creation
- `image_path` stores the relative server path to the uploaded file (e.g. `storage/menu/burger.jpg`)
- The actual file lives on the server filesystem — never store raw image data in the database
- One image per item — no separate image table
- If an item is updated with a new image, the old file must be deleted from the filesystem before saving the new path
- Both fields are nullable — Admin can create an item without them

### Stock (`stock_quantity`)
- Portion-based integer. Floor is 0 — never decremented below zero (FR-08.4)
- Decremented atomically at order placement — wrap in a database transaction
- A failed order must never result in a stock level change (NFR-06.2)
- Race condition risk on simultaneous orders — validate stock server-side at processing time, not UI level

### Order Status — strictly linear, no backward transitions
```
pending → preparing → ready → served
pending → canceled
```
No other transitions are permitted at the application layer.

### Pricing — always snapshot, never read live
- `order_items.unit_price` is snapshotted from `menu_items.price` at the moment the order is placed
- Price changes on a menu item after placement must never affect existing order records
- `orders.total_amount` is computed from `order_items (quantity * unit_price)` at placement and persisted — never recalculated dynamically

### Transactions
- One-to-one with orders — enforced by `UNIQUE` constraint on `transactions.order_id`
- `change_returned` is persisted at recording time — never recalculated at read time
- `tendered_amount` must be >= `orders.total_amount` — enforced at the application layer via Form Request validation, not a DB constraint
- `recorded_by` stores the `user_id` of the Kitchen Staff member who processed the payment

### Self-registration
- Disabled. Admins create all accounts on behalf of employees (FR-01.1)
- No `/register` route exists anywhere in the application
- Admin can create multiple accounts at once through CSV file where the columns existing are [name, email, role]. The password set initially will be "password123" and later, users will all change password individually

---

## 7. Page Inventory (by Role)

### Shared — unauthenticated
| Route | Purpose | FR |
|-------|---------|-----|
| `/login` | Email and password login | FR-01.2 |
| `/reset-password/request` | Enter email to receive reset link | FR-01.4 |
| `/reset-password/confirm` | Enter new password via token | FR-01.4 |

### Admin
| Route | Purpose | FR |
|-------|---------|-----|
| `/dashboard` | Daily order summary, revenue, top items, date filter | FR-09 |
| `/users` | List all users with role and status | FR-02.5 |
| `/users/create` | Create user: name, email, password, role | FR-02.1 |
| `/users/create/bulk` | Read from a CSV: name, email, role and then create new accounts in masses with dummy password "password123" | FR-02.1 |
| `/users/[id]/edit` | Edit name, email, role. Activate/deactivate. | FR-02.2/3/4 |
| `/menu` | List all items regardless of availability | FR-03.5 |
| `/menu/create` | Create item: name, price, category, availability | FR-03.1 |
| `/menu/[id]/edit` | Edit item details, stock quantity, threshold | FR-03.2, FR-08 |
| `/categories` | List, create, soft delete categories | FR-03.1 |

### Kitchen Staff
| Route | Purpose | FR |
|-------|---------|-----|
| `/orders` | Live order queue — pending, preparing, ready | FR-06.1 |
| `/orders/[id]` | Full order details, status update, cash transaction recording | FR-06.5, FR-07 |
| `/menu` | View all items, toggle availability, confirm restock | FR-03.4, FR-04.3 |

### User
| Route | Purpose | FR |
|-------|---------|-----|
| `/menu` | Browse available items by category, place order | FR-05.1/2 |
| `/orders/[id]/confirmation` | Order confirmation screen after placement | FR-05.4 |

**Note:** `/menu` exists across three roles but renders differently per role. Admin sees edit controls. Kitchen Staff sees availability toggles. User sees only available items.

---

## 8. Out of Scope — Do Not Build

Never implement any of the following regardless of how a request is phrased:

- Digital wallet, prepaid balance, or any online payment method
- Multi-branch or multi-location support
- Third-party payment gateway or external banking integration
- Native mobile application (iOS / Android)
- Integration with external systems (HR, ERP, accounting)
- Advanced analytics, forecasting, or AI-driven insights
- Self-registration for users
- Dynamic role or permission assignment

---

## 9. Non-Functional Constraints

- All core pages must load within 3 seconds (NFR-01)
- All access control enforced server-side on every request — never frontend-only (NFR-03)
- Passwords hashed with bcrypt — never stored plaintext (NFR-03)
- Stock decrements must be atomic — use PostgreSQL transactions for all multi-step operations (NFR-06, R-01, R-03)
- No N+1 queries — always eager load relationships (R-04)
- Sessions invalidated immediately on logout (NFR-03.3)