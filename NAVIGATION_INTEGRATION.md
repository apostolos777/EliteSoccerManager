# Admin Navigation Integration

Add these menu items to your admin navigation to access the bulk attendance import:

## Option 1: Quick Add to Header/Sidebar

Add to your navigation menu (likely in `header.php` or `sidebar.php`):

```html
<!-- Bulk Attendance Import -->
<a href="bulk_attendance_import.php" class="nav-link">
    <i class="icon-upload"></i> Bulk Attendance Import
</a>
```

## Option 2: With Admin Check

For safety, add only for admin users:

```php
<?php if ($isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <li>
        <a href="bulk_attendance_import.php">
            <span>📥 Bulk Attendance Import</span>
        </a>
    </li>
<?php endif; ?>
```

## Option 3: Full Implementation

Complete navigation item with icon and styling:

```php
<?php 
// Check if admin
$isAdmin = isset($_SESSION['user_id']) && checkUserIsAdmin($_SESSION['user_id']);

if ($isAdmin): 
?>
    <div class="nav-item admin-tool">
        <a href="bulk_attendance_import.php" class="nav-link" title="Import attendance records from CSV or JSON">
            <span class="nav-icon">📥</span>
            <span class="nav-label">Bulk Import</span>
        </a>
        <span class="badge">Import</span>
    </div>
<?php endif; ?>
```

## Option 4: Dropdown Menu

Add to admin tools dropdown:

```html
<li class="nav-dropdown">
    <a href="#" class="nav-link dropdown-toggle">Tools</a>
    <ul class="dropdown-menu">
        <li><a href="bulk_attendance_import.php">Bulk Attendance Import</a></li>
        <li><a href="bulk_add_players.php">Bulk Player Import</a></li>
        <li><a href="database_backup.php">Backup Database</a></li>
    </ul>
</li>
```

## CSS Styling (Optional)

Add to your CSS for nice styling:

```css
.nav-item.admin-tool {
    background-color: #f0f0f0;
    border-left: 3px solid #007bff;
    padding-left: 12px;
    margin: 5px 0;
}

.nav-item.admin-tool a:hover {
    background-color: #e8e8e8;
    color: #0056b3;
}

.nav-icon {
    margin-right: 8px;
    font-size: 18px;
}

.badge {
    display: inline-block;
    background-color: #28a745;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    margin-left: 8px;
}
```

## Testing

After adding the menu item:
1. Log in as admin
2. Look for the menu item
3. Click it to open the bulk import page
4. Verify it loads without errors

## Mobile Responsive

The menu items will automatically be responsive if your navigation uses:
- Flexbox/Grid layout
- Mobile menu toggle
- Standard Bootstrap navigation

No additional CSS changes needed for most setups.

## Location Reference

- **Page File**: `/bulk_attendance_import.php`
- **Templates**: `/templates/attendance_template.{csv,json}`
- **CLI Script**: `/restore_attendance_from_backup.php`
- **Full Guide**: `/ATTENDANCE_IMPORT_GUIDE.md`
- **Summary**: `/ATTENDANCE_RESTORE_SUMMARY.md`

---

That's it! Your admin users can now easily access the bulk attendance import feature.
