Vivo API (Node + TypeScript)

Quickstart

1. Copy `.env.example` to `.env` and set DB connection and `JWT_SECRET`.
2. Install dependencies: `pnpm install` (from repo root)
3. Run migrations: `pnpm --filter api migrate`
4. Seed an admin user: `pnpm --filter api run seed:admin`
5. Start dev server: `pnpm --filter api dev`

Notes
- Migrations are intentionally non-destructive and mirror the current SQLite schema used by the PHP app.
- Use `DB_CLIENT` and connection env variables to point to your production MySQL or a local DB during development.
