# KitchenFlow — Setup Guide

---

## 1. Install Git

Download: https://git-scm.com/download/win

During install:
- Default editor → Visual Studio Code
- PATH → "Git from the command line and also from 3rd-party software"
- Line endings → "Checkout Windows-style, commit Unix-style"

---

## 2. Install WSL 2

Open PowerShell as Administrator:

```powershell
wsl --install
```

> **Restart your PC before moving on.** WSL 2 requires a full restart before Docker Desktop can use it.

---

## 3. Install Docker Desktop

Download: https://www.docker.com/products/docker-desktop/

- Enable WSL 2 integration when prompted
- Sign in with a Docker account
- **Keep Docker Desktop running** before proceeding

---

## 4. Configure Git Line Endings

```powershell
git config --global core.autocrlf input
```

---

## 5. Drop .env in the Directories

Take the .env file, frontend folder, backend folder and drop it in the root directory.

## 6. Run the Project

Open a new PowerShell window and run these commands in order:

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

Wait about a minute after the final `up -d`. If you see a connection error on first load, wait and refresh — it will resolve on its own.

---

## Access

| Service  | URL                   |
|----------|-----------------------|
| Frontend | http://localhost:3000 |
| Backend  | http://localhost:8000 |
| pgAdmin  | http://localhost:5050 |

**pgAdmin DB connection** — Host: `postgres` · Port: `5432` · DB: `kitchenflow_db` · User: `kitchenflow_user` · Password: `secret`

---

## Test Credentials

| Role     | Email                          | Password |
|----------|--------------------------------|----------|
| Admin    | sarah.ahmed@nexuscorp.com      | password |
| Staff    | priya.nair@nexuscorp.com       | password |
| Customer | tanvir.mahmud@nexuscorp.com    | password |
