vivoapp-android

Monorepo scaffold for the Vivo Android app.

Structure:
- api/ — Node.js + TypeScript Express API (migrating from PHP)
- mobile/ — React Native (TypeScript) app
- migrations/ — DB migration scripts and plan

Quick start (dev):
- Install dependencies (pnpm recommended)
- Start API: cd api && pnpm run dev
- Start mobile (local emulator/dev): cd mobile && pnpm run android

See `DB_MIGRATION_PLAN.md` for details on migrating the existing DB and keeping existing data.
