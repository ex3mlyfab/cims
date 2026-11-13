# Production deployment

The production container serves the app from `/var/www/html` (Sail-style image).
Because the container also runs the asset build, **dev dependencies must be
installed there** — Vite, `vite-plus`, the Babel/React plugins and
`laravel-vite-plugin` all live in `devDependencies`. The container needs
Node.js 22.12+ (`@rolldown/plugin-babel` engine requirement).

## Why the build failed

`.npmrc` sets `legacy-peer-deps=true`, so npm does not auto-install peer
dependencies. `@rolldown/plugin-babel` (used by `vite.config.ts` for the React
Compiler) lists `@babel/core` as a required peer, and it was not declared in
`package.json`. Locally it happened to exist in `node_modules`; a clean
`npm ci` on the server did not install it, so loading `vite.config.ts` failed
with `ERR_MODULE_NOT_FOUND: Cannot find package '@babel/core'`.

`@babel/core` is now an explicit `devDependency` and pinned in
`package-lock.json`, so `npm ci` installs it.

## Fresh server / container setup

```sh
cd /var/www/html

# 1. Application dependencies (runtime only)
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Front-end build — keep dev deps for the build
npm ci --include=dev
npm run build:prod          # removes a stale public/hot, then runs vp build

# 3. Environment
cp .env.production.example .env
php artisan key:generate --force

# 4. Storage link + permissions
php artisan storage:link
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# 5. Database
php artisan migrate --force

# 6. Framework caches
php artisan optimize       # config + events + routes + views
```

`npm ci` only ever exists to install from the lockfile — if the lockfile is
stale, regenerate it locally with `npm install` and commit it.

## Redeploy

```sh
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --include=dev
npm run build:prod
php artisan migrate --force
php artisan optimize
```

Restart long-running workers after every deploy:

```sh
php artisan queue:restart
```

## Scheduler and queue

Both use the `database` drivers, so they need runners outside the web process:

```cron
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

Run the queue worker under a supervisor:

```sh
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

## Production checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] `APP_URL` is the public HTTPS URL
- [ ] `APP_KEY` generated and stored outside the repo
- [ ] `SESSION_SECURE_COOKIE=true` (HTTPS only)
- [ ] `LOG_STACK=daily`, `LOG_LEVEL=warning`
- [ ] `public/hot` is absent (the `build:prod` script deletes it)
- [ ] `php artisan optimize` succeeds (config, events, routes and views cache)
- [ ] `php artisan about` shows `production` as the environment
- [ ] `storage/` and `bootstrap/cache/` writable by the web user
- [ ] Queue worker and scheduler running
- [ ] Database backups scheduled and a restore drill completed

## Local development stays the same

`npm run dev` / `vp dev` still writes `public/hot`; that file is gitignored and
must never be copied to or built on the server.
