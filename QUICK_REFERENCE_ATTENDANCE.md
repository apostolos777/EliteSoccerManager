# Attendance Import - Quick Reference Card

## 🚀 Get Started in 60 Seconds

### Option A: Web Interface (Easiest)
1. Go to `/bulk_attendance_import.php`
2. Upload CSV or JSON file
3. Click Import
4. Done!

### Option B: Command Line (Fastest)
```bash
cd /path/to/vivo
php restore_attendance_from_backup.php vivo_football.db.backup skip false
```

### Option C: Direct Entry
1. Go to Attendance page
2. Add records one by one
3. Takes longer but simple

---

## 📊 What You Have

| Item | Count/Location |
|------|-----------------|
| Recoverable Records | 200 (in vivo_football.db.backup) |
| Current Records | 5 |
| New Records After Import | 205 |
| Import Time | ~1 second |

---

## 📁 Files to Know

```
bulk_attendance_import.php          ← Use this (web)
restore_attendance_from_backup.php  ← Use this (CLI)
templates/attendance_template.csv   ← Reference/example
templates/attendance_template.json  ← Reference/example
ATTENDANCE_IMPORT_GUIDE.md         ← Full docs
ATTENDANCE_RESTORE_SUMMARY.md      ← This overview
```

---

## 🛠 Common Commands

```bash
# Preview what will import (dry run)
php restore_attendance_from_backup.php vivo_football.db.backup skip true

# Actually import the 200 records
php restore_attendance_from_backup.php vivo_football.db.backup skip false

# Use different backup file
php restore_attendance_from_backup.php database.db.backup.20251208_221511 skip false

# Update instead of skip duplicates
php restore_attendance_from_backup.php vivo_football.db.backup update false
```

---

## 📋 CSV Format

```csv
player_id,event_id,status,notes
16,10,present,
17,10,present,Played full match
18,11,absent,Injury
```

**Statuses**: `present`, `absent`, `late`, `excused`

---

## 🔄 Process Flow

```
Preview (dry run) 
    ↓
Review numbers
    ↓
Actual import (false)
    ↓
Check results
    ↓
Done!
```

---

## ⚙️ Duplicate Handling

| Mode | Behavior | When to Use |
|------|----------|------------|
| skip | Keep current data, add new | Default, safe |
| update | Replace with backup data | When backup is more accurate |

---

## ✅ Success Indicators

- [ ] Command runs without errors
- [ ] Summary shows correct counts
- [ ] Attendance page shows all records
- [ ] Players and events list correctly
- [ ] Status values are accurate

---

## 🚨 Troubleshooting

| Problem | Solution |
|---------|----------|
| File not found | Check path: `vivo_football.db.backup` |
| Permission denied | Run with correct user/path |
| No records imported | Check dry run output first |
| Missing players | Ensure player IDs match database |
| Stuck on import | Large files take 5+ seconds, be patient |

---

## 🔗 Links

- **Web Interface**: `/bulk_attendance_import.php`
- **Full Guide**: `/ATTENDANCE_IMPORT_GUIDE.md`
- **Summary**: `/ATTENDANCE_RESTORE_SUMMARY.md`
- **Navigation Setup**: `/NAVIGATION_INTEGRATION.md`

---

## 📞 Need Help?

1. Check dry run output first
2. Read ATTENDANCE_IMPORT_GUIDE.md for details
3. Verify backup file exists
4. Check player IDs in your database match backup data

---

**Status**: ✅ Ready to use  
**Records available**: 200  
**Your current records**: 5  
**System**: Production ready
