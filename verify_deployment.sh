#!/bin/bash
# Attendance Import System - Deployment Verification Script
# Run this script to verify all components are ready for deployment

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  Attendance Import System - Deployment Verification Script    ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

PASS=0
FAIL=0

# Function to check file exists
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✅${NC} File exists: $1"
        ((PASS++))
        return 0
    else
        echo -e "${RED}❌${NC} File missing: $1"
        ((FAIL++))
        return 1
    fi
}

# Function to check command
check_command() {
    if command -v "$1" &> /dev/null; then
        echo -e "${GREEN}✅${NC} Command available: $1"
        ((PASS++))
        return 0
    else
        echo -e "${RED}❌${NC} Command not found: $1"
        ((FAIL++))
        return 1
    fi
}

echo "1️⃣  CHECKING REQUIRED FILES..."
echo "───────────────────────────────────────────────────────────────"

check_file "bulk_attendance_import.php"
check_file "restore_attendance_from_backup.php"
check_file "templates/attendance_template.csv"
check_file "templates/attendance_template.json"
check_file "database.db"

echo ""
echo "2️⃣  CHECKING DOCUMENTATION..."
echo "───────────────────────────────────────────────────────────────"

check_file "00_START_HERE.md"
check_file "INDEX.md"
check_file "QUICK_REFERENCE_ATTENDANCE.md"
check_file "VISUAL_GUIDE.md"
check_file "ATTENDANCE_IMPORT_GUIDE.md"
check_file "ATTENDANCE_RESTORE_SUMMARY.md"
check_file "COMPLETION_SUMMARY.md"
check_file "NAVIGATION_INTEGRATION.md"

echo ""
echo "3️⃣  CHECKING SYSTEM REQUIREMENTS..."
echo "───────────────────────────────────────────────────────────────"

check_command "php"
check_command "sqlite3"

echo ""
echo "4️⃣  CHECKING DATABASE..."
echo "───────────────────────────────────────────────────────────────"

# Check database connectivity
if sqlite3 database.db "SELECT COUNT(*) FROM attendance;" &> /dev/null; then
    RECORD_COUNT=$(sqlite3 database.db "SELECT COUNT(*) FROM attendance;")
    echo -e "${GREEN}✅${NC} Database accessible"
    echo "   Records in attendance table: $RECORD_COUNT"
    ((PASS++))
else
    echo -e "${RED}❌${NC} Cannot access database"
    ((FAIL++))
fi

# Check table exists
if sqlite3 database.db ".tables" | grep -q "attendance"; then
    echo -e "${GREEN}✅${NC} Attendance table exists"
    ((PASS++))
else
    echo -e "${RED}❌${NC} Attendance table not found"
    ((FAIL++))
fi

echo ""
echo "5️⃣  CHECKING PHP SYNTAX..."
echo "───────────────────────────────────────────────────────────────"

if php -l bulk_attendance_import.php &> /dev/null; then
    echo -e "${GREEN}✅${NC} bulk_attendance_import.php - Valid syntax"
    ((PASS++))
else
    echo -e "${RED}❌${NC} bulk_attendance_import.php - Syntax error"
    ((FAIL++))
fi

if php -l restore_attendance_from_backup.php &> /dev/null; then
    echo -e "${GREEN}✅${NC} restore_attendance_from_backup.php - Valid syntax"
    ((PASS++))
else
    echo -e "${RED}❌${NC} restore_attendance_from_backup.php - Syntax error"
    ((FAIL++))
fi

echo ""
echo "6️⃣  CHECKING FILE PERMISSIONS..."
echo "───────────────────────────────────────────────────────────────"

if [ -r "bulk_attendance_import.php" ]; then
    echo -e "${GREEN}✅${NC} bulk_attendance_import.php readable"
    ((PASS++))
else
    echo -e "${RED}❌${NC} bulk_attendance_import.php not readable"
    ((FAIL++))
fi

if [ -w "database.db" ]; then
    echo -e "${GREEN}✅${NC} database.db writable"
    ((PASS++))
else
    echo -e "${YELLOW}⚠️${NC}  database.db not writable (may need chmod)"
fi

echo ""
echo "7️⃣  CHECKING TEMPLATES..."
echo "───────────────────────────────────────────────────────────────"

if grep -q "player_id,event_id,status" templates/attendance_template.csv 2>/dev/null; then
    echo -e "${GREEN}✅${NC} CSV template valid (contains headers)"
    ((PASS++))
else
    echo -e "${RED}❌${NC} CSV template invalid"
    ((FAIL++))
fi

if grep -q '"player_id"' templates/attendance_template.json 2>/dev/null; then
    echo -e "${GREEN}✅${NC} JSON template valid (contains fields)"
    ((PASS++))
else
    echo -e "${RED}❌${NC} JSON template invalid"
    ((FAIL++))
fi

echo ""
echo "═════════════════════════════════════════════════════════════════"
echo "📊 VERIFICATION RESULTS"
echo "═════════════════════════════════════════════════════════════════"

TOTAL=$((PASS + FAIL))
echo ""
echo "Checks Passed: ${GREEN}$PASS${NC}"
echo "Checks Failed: ${RED}$FAIL${NC}"
echo "Total Checks:  $TOTAL"
echo ""

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║            ✅ ALL CHECKS PASSED - READY TO DEPLOY ✅           ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
    exit 0
else
    echo -e "${RED}╔════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${RED}║         ❌ SOME CHECKS FAILED - REVIEW ABOVE ❌                ║${NC}"
    echo -e "${RED}╚════════════════════════════════════════════════════════════════╝${NC}"
    exit 1
fi
