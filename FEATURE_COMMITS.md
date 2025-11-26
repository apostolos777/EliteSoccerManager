# Example Commit Messages for Player Profile Feature

## Database Schema Commits

```bash
git add database/migrations/
git commit -m "feat(db): create player_teams join table for multi-team support

- Add player_teams table with player_id and team_id foreign keys
- Enable players to belong to multiple teams
- Includes cascade delete for data integrity
"

git commit -m "feat(db): add position columns to players table

- Add primary_position, secondary_position, third_position columns
- Support storing up to 3 football positions per player
- Reference official FIFA position abbreviations (GK, DF, MD, FW, etc.)
"

git commit -m "feat(db): create player_documents table for document storage

- Add player_documents table for ID, passport, birth certificate storage
- Include columns: player_id, document_type, file_path, file_size, uploaded_at
- Enable tracking of document metadata (type, size, upload timestamp)
"
```

## Backend Model Commits

```bash
git add app/Models/Player.php
git commit -m "feat(models): add team relationships to Player model

- Add hasMany relationship for player_teams join table
- Add belongsToMany relationship for multiple teams
- Add helper methods: syncTeams(), getTeamsArray(), removeTeam()
- Enable easy team management: \$player->teams()->sync([1, 2, 3])
"

git add app/Models/PlayerDocument.php
git commit -m "feat(models): create PlayerDocument model for document handling

- New model with belongs-to relationship to Player
- Include methods: getTypeLabel(), deleteFile(), formatFileSize()
- Support document metadata retrieval and file lifecycle management
- Document types: id, passport, birth_certificate
"
```

## Backend Controller Commits

```bash
git add app/Http/Controllers/PlayerController.php
git commit -m "feat(controllers): enhance PlayerController with profile management

- Add editProfile() to display player edit form with all fields
- Add updateProfile() to persist multi-team, position, contact data
- Add uploadProfilePicture() with atomic file replacement (old → backup, new → active)
- Include validation: max 5MB, JPEG/PNG only, auto-delete previous file

- Add uploadDocument() for ID/passport/birth certificate storage
- Support PDF and image formats, max 10MB per document
- Store metadata: type, file_size, upload timestamp

- Add deleteDocument() and downloadDocument() for document lifecycle
- Add getDocuments() to list all player documents with URLs
- Add private helpers: validatePositions(), getCountriesWithFlags(), getPositionConstants()
"
```

## Routes Commits

```bash
git add routes/player.php
git commit -m "feat(routes): add routes for enhanced player profile management

- POST   /player/{player}/update-profile     → update player data and teams
- POST   /player/{player}/upload-profile-picture → upload/replace profile photo
- POST   /player/{player}/upload-document     → upload ID/passport/birth cert
- DELETE /player/{player}/document/{doc}      → delete player document
- GET    /player/{player}/document/{doc}      → download player document
- GET    /player/{player}/documents           → get documents JSON list
- GET    /player/{player}/edit                → show edit profile form

All routes middleware: [auth, can:edit-player-profile]
"
```

## Frontend Blade Template Commits

```bash
git add resources/views/player/edit_profile.blade.php
git commit -m "feat(views): create comprehensive edit_profile blade template

- Profile picture section with preview and atomic upload
- Multi-select dropdown for teams using Select2 plugin
- Three position dropdowns (primary, secondary, tertiary) with all 16 FIFA positions
- Football info: jersey number, height, weight
- Personal details: email, phone, emergency contact, medical notes
- Parent/guardian information section
- Document upload section: ID, passport, birth certificate
- Document list with download and delete actions
- Nationality field with flag emoji display
- Helper text under key fields (Emergency Contact Example, Medical Notes Example)
- Form validation feedback and AJAX submission without page reload
- Success/error alerts for all operations (uploads, deletes, profile save)
"
```

## Validation Commits

```bash
git add app/Http/Requests/UpdatePlayerProfileRequest.php
git commit -m "feat(validation): create UpdatePlayerProfileRequest form validation

- Centralized validation rules for player profile updates
- Validate required fields: name, teams (min 1)
- Validate positions: all optional, secondary/tertiary must differ from primary
- Validate team selections: array, min 1, exists in teams table
- Validate file uploads: profile picture (max 5MB), documents (max 10MB)
- Validate contact info: email (unique), phone formats
- Validate profile data: date_of_birth before today, jersey_number 1-99
- Custom error messages for all validation failures
- Position standardization in prepareForValidation() hook
"
```

## Integration Test Commits

```bash
git add tests/Feature/PlayerProfileTest.php
git commit -m "test(feature): add comprehensive tests for player profile feature

- Test editProfile() renders form with correct data
- Test updateProfile() persists teams via sync relationship
- Test uploadProfilePicture() replaces old file and stores path
- Test uploadDocument() creates database record and stores file
- Test deleteDocument() removes file and database record
- Test getDocuments() returns JSON list with download URLs
- Test validation: reject invalid positions, require teams, validate file types
- Test authorization: user can edit own profile, admin can edit any profile
- Test file cleanup: old profile pictures and deleted documents removed from storage
"
```

## Full Feature Commit (Alternative)

```bash
git add database/migrations/ app/Models/ app/Http/Controllers/PlayerController.php routes/player.php resources/views/player/edit_profile.blade.php app/Http/Requests/UpdatePlayerProfileRequest.php
git commit -m "feat: implement enhanced player profile management system

Features:
- Multi-team support: players can belong to multiple teams
- Football positions: primary, secondary, tertiary position tracking
- Profile picture management: upload, preview, replace with atomic operations
- Document storage: ID, passport, birth certificate file uploads
- Extended profile fields: nationality, height, weight, emergency contact, medical notes
- Parent/guardian information: name, email, phone
- Responsive UI: Blade template with Select2, file upload preview, document list

Changes:
- Create player_teams, player_documents database tables
- Add position columns to players table
- Enhance Player model with team relationships and methods
- Create PlayerDocument model with file lifecycle methods
- Extend PlayerController with profile management CRUD
- Create UpdatePlayerProfileRequest validation
- Add player profile routes with auth middleware
- Create edit_profile Blade template with all form fields

Tests:
- Profile updates with multi-team persistence
- File uploads with replacement and cleanup
- Document management (upload, download, delete)
- Validation for all input fields
- Authorization checks for user roles

Closes #123 (player profile enhancement epic)
"
```

## Rollback Instructions

```bash
# If any migration needs to be rolled back:
php artisan migrate:rollback --step=3

# Or specific migration:
php artisan migrate:rollback --target=2025_11_26_create_player_teams_table

# Restore from backup:
git revert HEAD~5  # Revert 5 commits (adjust as needed)
```

## Deployment Commands

```bash
# Fresh deployment to production:
git pull origin main
php artisan migrate --force
php artisan storage:link  # Link storage for file uploads

# Cache clearing after feature deployment:
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Backup players table before production migration:
mysqldump -u user -p database_name players > backups/players_backup_2025_11_26.sql
```
