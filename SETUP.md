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

## 5. Put .env files in the Root Directory

Download the .env files from this link: https://drive.google.com/drive/folders/1NCtTjq_UGczrf2Nax4L0WCaJRcmdbdli?usp=sharing
Unzip the downloaded file and take the .env file, frontend folder, backend folder from the unzipped folder and drop them in the root directory.

## 6. Run the Project

Open a new PowerShell window and run this entire command block:

```powershell
git clone https://github.com/Rahman-Alif/KitchenFlow
cd KitchenFlow
docker-compose build
docker-compose up -d
cd backend
composer install
docker exec -it kitchenflow_backend php artisan key:generate
docker exec -it kitchenflow_backend php artisan migrate:fresh
docker exec -it kitchenflow_backend php artisan db:seed
docker exec -it kitchenflow_backend php artisan storage:link
docker exec -u root kitchenflow_backend chmod -R 775 storage bootstrap/cache
docker exec -u root kitchenflow_backend chown -R www-data:www-data storage bootstrap/cache
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
