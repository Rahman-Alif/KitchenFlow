# KitchenFlow

> A B2B SaaS catering and inventory management platform for organizations that operate an internal canteen or food service.

---

## Overview

KitchenFlow is a subscription-based web platform that streamlines canteen operations — from menu management and order processing to inventory tracking and cash transactions. It serves three distinct roles within an organization: administrators, kitchen staff, and regular users (customers).

Each subscribing organization is fully isolated as a **tenant**. If a tenant's subscription lapses, all users in that organization lose access regardless of role.

---

## Features

### Admin
- Dashboard with live order stats, revenue charts, and low stock alerts
- Full user management — create, edit, activate, deactivate, bulk import via CSV
- Menu and category management with image uploads
- Inventory restocking and availability control

### Kitchen Staff
- Live order queue with real-time polling
- Order status progression — pending → preparing → ready → served
- Availability toggling and restock requests
- Cash transaction recording at point of serving

### Customer
- Browse available menu items by category
- Place orders and view order history

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | Next.js 14 (App Router), TypeScript, Tailwind CSS |
| Backend | Laravel 11, PHP 8.2, REST API |
| Database | PostgreSQL 16 |
| Auth | Laravel Sanctum (Bearer tokens) |
| Web Server | Nginx (Alpine) |
| Containerization | Docker Compose |

---

## Project Structure

```
KitchenFlow/
├── backend/                  # Laravel 11 REST API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/  # Admin, KitchenStaff, User, Auth
│   │   │   ├── Middleware/   # CheckTenantSubscription, EnsureRole
│   │   │   ├── Requests/     # Form Requests per endpoint
│   │   │   └── Resources/    # API response shaping
│   │   └── Models/
│   └── database/
│       ├── migrations/
│       └── seeders/
├── frontend/                 # Next.js 14
│   └── src/
│       ├── app/              # App Router pages by role
│       ├── components/       # UI components split by role
│       ├── lib/              # API calls, utilities
│       └── types/            # TypeScript interfaces
└── docker/
    ├── nginx/
    └── php/
```

---

## Setup Guide

### Prerequisites

**1. Install Git**

Download: https://git-scm.com/download/win

During install:
- Default editor → Visual Studio Code
- PATH → "Git from the command line and also from 3rd-party software"
- Line endings → "Checkout Windows-style, commit Unix-style"

**2. Install WSL 2**

Open PowerShell as Administrator:
```powershell
wsl --install
```
> **Restart your PC before proceeding.** WSL 2 requires a full restart before Docker Desktop can use it.

**3. Install Docker Desktop**

Download: https://www.docker.com/products/docker-desktop/
- Enable WSL 2 integration when prompted
- Sign in with a Docker account
- **Keep Docker Desktop running** at all times while working on the project

**4. Configure Git line endings**
```powershell
git config --global core.autocrlf input
```

---

### Installation

Open a new PowerShell window and run these in order:

```powershell
# Clone and enter the project
git clone https://github.com/Rahman-Alif/KitchenFlow
cd KitchenFlow

# Build and start containers
docker-compose build
docker-compose up -d

# Install PHP dependencies
cd backend
composer install

# Set up the backend
docker exec -it kitchenflow_backend php artisan key:generate
docker exec -it kitchenflow_backend php artisan migrate:fresh
docker exec -it kitchenflow_backend php artisan db:seed
docker exec -it kitchenflow_backend php artisan storage:link
docker exec -u root kitchenflow_backend chmod -R 775 storage bootstrap/cache
docker exec -u root kitchenflow_backend chown -R www-data:www-data storage bootstrap/cache

# Restart cleanly
docker-compose down
docker-compose up -d
```

Wait about a minute after the final `up -d`. If you see a connection error on first load, wait and refresh.

---

### Access

| Service | URL |
|---|---|
| Frontend | http://localhost:3000 |
| Backend API | http://localhost:8000 |
| pgAdmin | http://localhost:5050 |

**pgAdmin DB connection**
- Host: `postgres`
- Port: `5432`
- Database: `kitchenflow_db`
- Username: `kitchenflow_user`
- Password: `secret`

---

### Test Credentials

| Role | Email | Password |
|---|---|---|
| Admin | sarah.ahmed@nexuscorp.com | password |
| Kitchen Staff | priya.nair@nexuscorp.com | password |
| Customer | tanvir.mahmud@nexuscorp.com | password |

---

### Daily Workflow

**Start of day:**
```powershell
cd KitchenFlow
docker-compose up -d
```

**End of day:**
```powershell
docker-compose down
```

**After pulling changes that include new migrations:**
```powershell
docker exec -it kitchenflow_backend php artisan migrate
```

---

## API

The backend exposes a REST API consumed exclusively by the frontend. All endpoints require a Sanctum Bearer token except login and password reset.

Base URL: `http://localhost:8000/api`

### Auth
| Method | Endpoint | Description |
|---|---|---|
| POST | `/auth/login` | Login and receive token |
| POST | `/auth/logout` | Invalidate token |
| POST | `/auth/password-reset/request` | Request reset email |
| POST | `/auth/password-reset/confirm` | Confirm new password |

### Profile (all authenticated roles)
| Method | Endpoint | Description |
|---|---|---|
| GET | `/profile` | View own profile |
| PATCH | `/profile` | Update own profile |
| PUT | `/profile/password` | Change own password |

### Admin
| Method | Endpoint | Description |
|---|---|---|
| GET | `/admin/dashboard` | Order and revenue summary |
| GET | `/admin/dashboard/revenue-week` | 7-day revenue chart data |
| GET | `/admin/tenant` | Tenant subscription details |
| GET | `/admin/roles` | List all roles |
| GET | `/admin/roles/{id}` | Single role detail |
| GET | `/admin/users/with-roles` | Users with role details |
| GET | `/admin/stock` | All stock records |
| GET | `/admin/menu-items/{id}/stock-history` | Stock movement history for an item |
| POST | `/admin/menu-items/{id}/stock/restock` | Restock item |
| POST | `/admin/menu-items/{id}/stock/record-sale` | Manually record a sale |
| GET | `/admin/audit/menu-items/{id}` | Audit trail for a menu item |
| GET | `/admin/audit/categories/{id}` | Audit trail for a category |
| GET | `/admin/audit/recent-changes` | Recent changes across all entities |
| POST | `/admin/users` | Create user |
| GET/PUT/DELETE | `/admin/users/{id}` | View / edit / delete user |
| PATCH | `/admin/users/{id}/activate` | Activate user account |
| PATCH | `/admin/users/{id}/deactivate` | Deactivate user account |
| POST | `/admin/users/bulk` | Bulk create users via CSV |
| GET/POST | `/admin/categories` | List / create categories |
| DELETE | `/admin/categories/{id}` | Soft delete category |
| GET/POST | `/admin/menu-items` | List / create menu items |
| GET/PUT/DELETE | `/admin/menu-items/{id}` | View / edit / delete item |
| PATCH | `/admin/menu-items/{id}/restock` | Restock item quantity |
| PATCH | `/admin/menu-items/{id}/availability` | Toggle availability |
| GET | `/admin/menu-items/low-stock` | Items at or below threshold |
| GET | `/admin/orders` | All orders |
| GET | `/admin/orders/{id}` | Single order detail |

### Admin & Kitchen Staff (shared)
| Method | Endpoint | Description |
|---|---|---|
| GET | `/admin/users` | List all users |
| GET | `/admin/messages` | Inbox |
| POST | `/admin/messages` | Send message |
| PATCH | `/admin/messages/{id}/read` | Mark message as read |
| DELETE | `/admin/messages/{id}` | Delete message |

### Kitchen Staff
| Method | Endpoint | Description |
|---|---|---|
| GET | `/kitchen/orders` | Live order queue |
| GET | `/kitchen/orders/{id}` | Order detail |
| PATCH | `/kitchen/orders/{id}/status` | Advance order status |
| POST | `/kitchen/orders/{id}/transaction` | Record cash transaction |
| GET | `/kitchen/menu` | Menu with availability controls |
| PATCH | `/kitchen/menu/{id}/availability` | Toggle item availability |
| POST | `/kitchen/menu/{id}/request-restock` | Submit restock request |

### Customer
| Method | Endpoint | Description |
|---|---|---|
| GET | `/user/menu` | Browse available items |
| POST | `/user/orders` | Place an order |
| GET | `/user/orders` | Order history |
| GET | `/user/orders/{id}` | Order detail |
| PATCH | `/user/orders/{id}/cancel` | Cancel a pending order |

---

## Business Rules

- **No self-registration** — admins create all accounts
- **Order status is strictly linear** — `pending → preparing → ready → served` or `pending → canceled`. No backward transitions
- **Pricing is snapshotted** at order placement — price changes never affect existing orders
- **Stock is atomic** — decremented inside a database transaction; never goes below zero
- **Availability auto-disables** when stock hits zero; re-enabling always requires a manual action
- **Transactions are immutable** — one per order, permanently recorded, never deletable
- **Tenant subscription gates everything** — inactive or expired subscription locks out the entire organization

---

## Out of Scope

The following will not be built in this version:

- Digital wallet or any online payment method
- Multi-branch or multi-location support
- Native mobile application
- Third-party payment gateway or banking integration
- External system integrations (HR, ERP, accounting)
- Dynamic role or permission assignment
- Self-registration

---

## License

Private project — Betopia Limited. Not open for public use or redistribution.
