DB Migration Plan — keep existing data

Goal
- Migrate the existing PHP-based MySQL/SQLite schema and queries to a Node.js (Knex) based API layer while keeping the same database and existing data intact.

High-level steps
1. Inventory DB usage
   - Scan the PHP codebase and identify all DB tables, columns, and queries used by the prioritized features: login, teams, players, attendance, events.
   - Create a mapping document with table names, column types, and constraints.

2. Create schema migrations (Knex)
   - For each table used by the app, create an explicit Knex migration that mirrors the existing schema (no destructive changes initially).
   - Add a `migrations/` folder and add baseline migration files that reflect the current schema as a starting point.

3. Add data validation and constraints
   - Where current schema is lax, add NOT NULL/unique constraints in non-destructive ways (backfilled via migration) after review.

4. Implement queries in API
   - Translate PHP queries to parameterized Knex queries.
   - Create repository layer for `auth`, `players`, `teams`, `attendance`, `events`.

5. Run migrations in a staging environment
   - Use a staging copy of the DB (full dump from production) to run migrations and validate zero data loss.

6. Migration strategy for production
   - Schedule a maintenance window (if schema changes are required).
   - Run migrations against production DB (Knex) and have a rollback plan (DB snapshot / dump ready).

7. Testing
   - Add integration tests for the API endpoints that validate schema, returned fields, and authentication flows.

Notes & considerations
- We'll avoid destructive schema changes until the API is fully validated.
- Keep original PHP project as read-only during initial migration for reference.
- Authentication: migrate to token-based JWT for mobile but keep a compatibility layer for existing web sessions if needed.

Deliverables
- `migrations/` folder with baseline migrations
- `api/src/repositories/` with DB access methods
- Integration tests and CI job to run migrations against a test DB
- Documentation describing table mappings and any schema normalizations
