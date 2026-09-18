# Cotizador App

Sistema de cotizaciones, portal de clientes y panel administrativo.

## Stack
- Laravel 13 + PHP 8.3
- Inertia.js + React + TypeScript
- MySQL
- Redis (colas)
- Vite

## Requisitos previos
- PHP 8.3+
- Composer
- Node.js 20+
- MySQL 8+
- Redis
- Git

## Instalación en una PC nueva

```bash
git clone https://github.com/TU-USUARIO/cotizador-app.git
cd cotizador-app
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Edita `.env` con tus credenciales locales:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cotizador_db
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Crea la base de datos en MySQL:
```sql
CREATE DATABASE cotizador_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Migra y arranca:
```bash
php artisan migrate
composer run dev
```

App en http://localhost:8000

## Flujo de trabajo Git

- `main` → rama estable, protegida. **NO** push directo.
- Ramas: `feature/nombre-tarea`, `fix/descripcion`
- Todo cambio va por **Pull Request** con al menos 1 aprobación.

### Ciclo diario
```bash
git checkout main
git pull origin main
git checkout -b feature/mi-tarea
# ...programar...
git add .
git commit -m "feat: descripción"
git push origin feature/mi-tarea
# Abrir PR en GitHub
```
