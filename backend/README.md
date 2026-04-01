Backend (PHP) for myrajourney

Structure
- public/ (document root)
- src/ (code: config, controllers, models, middlewares, utils)
- scripts/migrations (SQL files)
- storage/uploads (file uploads)

Quick start
1) Copy .env.example to .env and set DB/JWT values
2) Import SQL from scripts/migrations in order
3) Point web server document root to backend/public/
4) Ensure PHP 8.1+, mysqli/PDO enabled

Endpoints (initial)
- POST /api/v1/auth/register
- POST /api/v1/auth/login
- GET  /api/v1/auth/me (Bearer token)

Notes
- JSON requests/responses; UTC timestamps; ISO8601
- Add more endpoints following existing pattern




















