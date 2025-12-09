# VIVO Attendance Import Guide

**Date**: December 9, 2025  
**Purpose**: Bulk import attendance records from CSV, JSON, or backup databases

---

## Quick Start

### Option 1: Web Upload (Easiest)
1. Go to **Bulk Attendance Import** page (link in admin menu)
2. Upload CSV or JSON file
3. Review preview
4. Confirm import

### Option 2: Backup Database Import
1. Go to **Bulk Attendance Import** page
2. Select backup database from dropdown
3. Choose duplicate handling option
4. Click **Import from Backup**

### Option 3: Manual Database Restore
Use the provided PHP script (see "Database Restore Script" section below)

---

## File Format Requirements

### CSV Format

**Required Columns**: `player_id`, `event_id`, `status`, `notes` (optional)

```csv
player_id,event_id,status,notes
183,31,present,Player arrived on time
199,37,absent,Not available
255,37,late,Arrived 15 minutes late
217,37,excused,Excused due to injury
278,37,present,
```

**Notes:**
- First row must contain column headers
- `notes` column is optional (can be empty)
- Status must be one of: `present`, `absent`, `late`, `excused`
- Player and event IDs must exist in your database

### JSON Format

**Structure**: Array of objects with required fields

```json
[
  {
    "player_id": 183,
    "event_id": 31,
    "status": "present",
    "notes": "Player arrived on time"
  },
  {
    "player_id": 199,
    "event_id": 37,
    "status": "absent",
    "notes": "Not available"
  }
]
```

**Notes:**
- Root element must be an array `[]`
- Each record is an object `{}`
- Fields are case-insensitive (will be converted to lowercase)
- Notes field is optional

---

## Status Values

| Status | Meaning | Usage |
|--------|---------|-------|
| `present` | Player attended | Default status |
| `absent` | Player did not attend | No show |
| `late` | Player arrived late | Delayed attendance |
| `excused` | Player was excused | Valid reason for absence |
| `not_recorded` | Not marked (optional) | No attendance record |

---

## Duplicate Handling Options

When importing, you can choose how to handle duplicate records (player + event combinations that already exist):

### Skip Duplicates (Recommended)
- **Option**: `skip`
- **Behavior**: Existing records are not changed
- **Use when**: You want to preserve current attendance records
- **Result**: Only new records are imported

### Update Duplicates
- **Option**: `update`
- **Behavior**: Existing records are overwritten with imported data
- **Use when**: You want to replace attendance records
- **Result**: All records are imported, existing ones are updated

---

## Column Mapping

The system automatically maps these column names (case-insensitive):

| System Name | Accepted Names | Required |
|------------|-----------------|----------|
| `player_id` | player_id, playerId, player, id | ✅ Yes |
| `event_id` | event_id, eventId, event, event_id | ✅ Yes |
| `status` | status, attendance_status | ✅ Yes |
| `notes` | notes, comments, note, comment | ❌ No |

---

## Data Validation

The import system automatically validates:

1. **Player Exists**: Player ID must exist in your database
2. **Event Exists**: Event ID must exist in your database
3. **Valid Status**: Status must be one of the allowed values
4. **Required Fields**: player_id and event_id are required

**Invalid records are:**
- Shown in the preview with error messages
- Not imported
- Listed in the summary report

---

## Backup Database Locations

The system automatically detects backup databases from these locations:

```
database.db.backup.*
vivo_football.db.backup
backups/database*.db
backups/vivo*.db
```

Currently available backups:
- `vivo_football.db.backup` (200 attendance records)
- `database.db.backup.20251208_221511` (1 record)
- And others in `backups/` folder

---

## Step-by-Step Import Process

### Step 1: Prepare Your Data
- Gather attendance data in CSV or JSON format
- Use template provided in templates/ folder
- Ensure player and event IDs match your database

### Step 2: Upload File
- Go to Bulk Attendance Import page
- Upload your file (CSV or JSON)
- System validates format

### Step 3: Review Preview
- Check the preview table
- Verify player IDs, event IDs, and statuses
- Note any error messages
- Review import statistics

### Step 4: Configure Options
- Choose duplicate handling (skip or update)
- Review the summary

### Step 5: Confirm Import
- Click "Confirm Import"
- System imports all valid records
- Invalid records are skipped

### Step 6: Review Results
- Success message shows:
  - Number of records imported
  - Number of records updated (if applicable)
  - Number of records skipped
  - Any errors encountered

---

## Templates Provided

### Location
```
templates/attendance_template.csv
templates/attendance_template.json
```

### CSV Template
```csv
player_id,event_id,status,notes
183,31,present,Player arrived on time
199,37,absent,Not available
255,37,late,Arrived 15 minutes late
217,37,excused,Excused due to injury
278,37,present,
```

### JSON Template
```json
[
  {
    "player_id": 183,
    "event_id": 31,
    "status": "present",
    "notes": "Player arrived on time"
  },
  {
    "player_id": 199,
    "event_id": 37,
    "status": "absent",
    "notes": "Not available"
  }
]
```

### Download Templates
- CSV: [Download Template](templates/attendance_template.csv)
- JSON: [Download Template](templates/attendance_template.json)
- Or use the **Download Template** button in the import page

---

## Common Use Cases

### Case 1: Restore from Backup
**Goal**: Recover attendance data from a backup

**Steps**:
1. Go to Bulk Attendance Import
2. Select "Import from Backup Database"
3. Choose backup from dropdown: `vivo_football.db.backup`
4. Select "Skip duplicates" (to keep existing data)
5. Click "Import from Backup"

**Result**: Old attendance records restored without overwriting new ones

### Case 2: Bulk Import from Excel
**Goal**: Import attendance from Excel file

**Steps**:
1. Prepare data in Excel with columns: player_id, event_id, status, notes
2. Save as CSV (File → Save As → Format: CSV)
3. Go to Bulk Attendance Import
4. Upload CSV file
5. Review preview
6. Confirm import

**Result**: All Excel data imported into attendance table

### Case 3: Update Existing Records
**Goal**: Update attendance data for a past event

**Steps**:
1. Prepare corrected data in CSV with player_id, event_id, status
2. Go to Bulk Attendance Import
3. Upload CSV file
4. Select "Update duplicates" (to overwrite existing records)
5. Confirm import

**Result**: Records updated with new data

### Case 4: One-Time Data Migration
**Goal**: Move attendance from one database to another

**Steps**:
1. Export data from source database as CSV
2. Go to Bulk Attendance Import
3. Upload CSV
4. Skip duplicates
5. Confirm

**Result**: Data migrated to current database

---

## Database Restore Script

For command-line restoration without web interface:

```bash
#!/bin/bash

# Restore attendance from backup to current database
SOURCE_DB="vivo_football.db.backup"
TARGET_DB="database.db"

# Extract attendance data
sqlite3 "$SOURCE_DB" "SELECT player_id, event_id, status, notes FROM attendance;" > attendance_backup.txt

# Insert into target database
while IFS='|' read -r player_id event_id status notes; do
  sqlite3 "$TARGET_DB" "INSERT OR IGNORE INTO attendance (player_id, event_id, status, notes) VALUES ($player_id, $event_id, '$status', '$notes');"
done < attendance_backup.txt

echo "Import complete!"
```

---

## Error Messages & Solutions

### "File upload error"
**Cause**: File didn't upload properly  
**Solution**: Try uploading again, check file size

### "Unsupported file type"
**Cause**: File is not CSV or JSON  
**Solution**: Save file as CSV or JSON format

### "Invalid JSON format"
**Cause**: JSON structure is invalid  
**Solution**: Validate JSON (use https://jsonlint.com)

### "Player not found"
**Cause**: Player ID doesn't exist in database  
**Solution**: Check player ID, add player if missing

### "Event not found"
**Cause**: Event ID doesn't exist in database  
**Solution**: Check event ID, create event if needed

### "Invalid status"
**Cause**: Status is not recognized  
**Solution**: Use only: present, absent, late, excused

### "Database connection error"
**Cause**: Backup database path is invalid  
**Solution**: Check backup file exists in correct location

---

## Performance Notes

**Import Speed**:
- CSV with 100 records: ~1 second
- JSON with 100 records: ~1 second
- Database import: ~2-5 seconds depending on record count

**File Size Limits**:
- CSV/JSON: No strict limit (limited by server)
- Typical: 10MB+ files work fine

**Recommendations**:
- For large imports (>1000 records): Use database import
- For occasional imports: Use CSV/JSON file upload
- Always backup before importing large datasets

---

## Security & Best Practices

✅ **Always Do**:
- Validate data before importing
- Review preview before confirming
- Backup database before large imports
- Use "Skip duplicates" by default
- Keep templates in secure location

❌ **Never Do**:
- Import without reviewing preview
- Use "Update duplicates" unless intentional
- Share backup files publicly
- Import untrusted sources
- Skip validation checks

---

## Troubleshooting

### Import seems stuck
**Solution**: Check browser console for errors, refresh page, try again

### Records not appearing after import
**Solution**: Refresh page, check for error messages in review, verify player/event IDs exist

### Wrong data imported
**Solution**: Use "Skip duplicates" next time, don't use "Update duplicates" without verification

### Need to undo import
**Solution**: Restore from backup database, or delete records manually

---

## API Usage (Advanced)

You can also use PHP script directly to import:

```php
<?php
require_once 'bulk_attendance_import.php';

// Parse CSV
$records = parseAttendanceCSV('path/to/file.csv', $db);

// Parse JSON
$records = parseAttendanceJSON('path/to/file.json', $db);

// Insert records
foreach ($records as $record) {
    $stmt = $db->prepare("INSERT INTO attendance (player_id, event_id, status, notes) VALUES (?, ?, ?, ?)");
    $stmt->execute([$record['player_id'], $record['event_id'], $record['status'], $record['notes']]);
}
?>
```

---

## Support & Questions

**For more help:**
- Check this guide again
- Review template files
- Check error messages carefully
- Contact admin

**Features:**
✅ CSV import  
✅ JSON import  
✅ Backup database import  
✅ Preview before import  
✅ Duplicate handling  
✅ Validation  
✅ Error reporting  
✅ Transaction support  

---

**Last Updated**: December 9, 2025  
**Version**: 1.0  
**Status**: Production Ready
