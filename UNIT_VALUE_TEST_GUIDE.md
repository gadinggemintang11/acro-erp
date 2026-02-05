# Unit Value Implementation - Quick Test Guide

## Testing Objectives
Verify that unit pricing (nilai satuan) works correctly in both MIR and Warehouse_in modules with proper role-based access control.

---

## Pre-Test Setup

### Test Users Required
1. **Admin User** - Role containing "admin"
2. **Non-Admin User** - Role NOT containing "admin" (e.g., warehouse staff, supervisor)

### Test Data Required
- At least one active Item in system
- At least one Item Category

---

## Test Scenario 1: MIR Module - Unit Price Entry

### Step 1.1: Create MIR Header
- Go to MIR module
- Create new MIR document
- Fill in required fields (supplier, warehouse, etc.)
- **Status:** Draft

### Step 1.2: Add Item Manually (All Users)
- Click "Add Item" button
- Select an item from dropdown
- Enter:
  - Qty Request: 100
  - Allowance: 5
  - **Nilai Satuan (IDR): 50,000.00** ← New field
- Click "Save Item"

**Expected Result:**
- Item appears in table
- Nilai Satuan column shows: 50,000.00
- Total Harga column shows: (100 + 5) × 50,000.00 = 5,250,000.00
- Format: 5,250,000.00 (with comma separator)

### Step 1.3: Edit Item (All Users)
- Click "Edit" button on item row
- Change Qty Request to 150
- **Expected:** Total Harga auto-updates to (150 + 5) × 50,000.00 = 7,750,000.00
- Change Nilai Satuan to 60,000.00
- **Expected:** Total Harga auto-updates to (150 + 5) × 60,000.00 = 9,300,000.00
- Save changes

**Expected Result:**
- Item updates immediately
- Nilai Satuan and Total Harga reflect new values
- All users can perform this action (no restriction in MIR)

### Step 1.4: Approve MIR
- Submit/Approve MIR document
- Status changes to "Approved" or similar

---

## Test Scenario 2: Warehouse_in Module - Role-Based Access (Admin User)

### Step 2.1: Create GRN Header
- Go to Warehouse_in (Penerimaan) module
- Create new GRN document
- Set "Base On" to the MIR created in Test 1.4
- Fill in required fields

### Step 2.2: Auto-Load MIR Items (Admin User)
- Click "Auto-Load Items dari MIR" or equivalent button
- **Expected:** Modal appears asking to confirm MIR selection
- Click "Yes" or "Load"

**Expected Result:**
- GRN table populates with items from MIR
- Each item shows:
  - Qty: Copied from MIR
  - Nilai Satuan: Copied from MIR (should be 50,000.00 from Step 1.2)
  - Total Harga: Auto-calculated
  - **Edit Button:** Visible ✅
  - **Delete Button:** Visible ✅

### Step 2.3: Edit MIR Item (Admin User)
- As **ADMIN USER**, click "Edit" button on MIR item
- Modal opens: "Edit Item MIR"
- Fields visible:
  - Item Name (read-only)
  - Quantity Diterima (editable) ← Admin only
  - Nilai Satuan (IDR) (editable)

**Edit and verify:**
- Change Quantity Diterima: 120
- Change Nilai Satuan: 55,000.00
- Click "Simpan"

**Expected Result:**
- Item updates in table
- Alert: "Item berhasil diperbarui. Silakan refresh halaman."
- Page reloads
- Item shows new values
- Total Harga recalculates

### Step 2.4: Delete MIR Item (Admin User)
- As **ADMIN USER**, click "Delete" button on MIR item
- Confirm deletion in browser prompt
- **Expected:** Item removed from GRN table

---

## Test Scenario 3: Warehouse_in Module - Role-Based Access (Non-Admin User)

### Step 3.1: Login as Non-Admin User
- Logout current user
- Login as non-admin user (warehouse staff, supervisor, etc.)
- Open same GRN document created in Test 2

### Step 3.2: Verify Button Visibility (Non-Admin)
- Look at MIR items table

**Expected Result:**
- **Edit Button:** NOT VISIBLE - shows "-" instead ❌
- **Delete Button:** NOT VISIBLE - shows "-" instead ❌
- Item data is readable (qty, unit_value, total_harga visible)
- No interaction possible on MIR items

**DO NOT SEE:**
```
[Edit] [Delete]
```

**DO SEE:**
```
-
```

### Step 3.3: Attempt Direct Edit (Non-Admin)
If non-admin tries to directly access modal (via browser console), it should still be restricted at controller level.

**Optional test:** Open browser developer tools, check network requests. Non-admin should not be able to call `warehouse_in/update_mir_item` endpoint.

---

## Test Scenario 4: Verify Total Price Calculation

### Step 4.1: In MIR Module
- Open MIR detail that has items with unit_value
- Verify for each item:
  - Total Harga = (Qty Request + Allowance) × Nilai Satuan

Example:
```
Item: ABC-001
Qty Request: 100
Allowance: 5
Nilai Satuan: 50,000.00
Expected Total Harga: 105 × 50,000.00 = 5,250,000.00
```

### Step 4.2: In GRN Module
- Open GRN with auto-loaded MIR items
- Verify each item shows:
  - Total Harga (calculated by system)
  - Matches MIR total_harga value

**Number Format Verification:**
- Should display: 5,250,000.00 (comma as thousands separator, 2 decimals)
- NOT: 5250000 or 5.250.000 (period separator)

---

## Test Scenario 5: Data Persistence

### Step 5.1: Edit and Reload
- Edit an item's unit_value in GRN
- Save changes
- Refresh page manually (F5)
- **Expected:** New unit_value persists on page reload

### Step 5.2: Submit GRN
- Complete GRN form and submit
- Verify GRN status changes
- Navigate away and back to same GRN
- **Expected:** unit_value and total_harga still present and correct

### Step 5.3: Verify Database
- (Optional for database admin)
- Query: `SELECT id, receive_qty, unit_value FROM grn_details WHERE grn_header_id = [grnId]`
- **Expected:** unit_value column has numeric values (not NULL, not 0 for MIR items)

---

## Test Scenario 6: Edge Cases

### Step 6.1: Zero Unit Value
- Add MIR item with Nilai Satuan = 0
- **Expected:** Total Harga = 0
- GRN auto-loads correctly

### Step 6.2: Decimal Unit Value
- Add MIR item with Nilai Satuan = 25,500.50
- Add Qty: 10, Allowance: 2
- **Expected:** Total Harga = 12 × 25,500.50 = 306,006.00
- Display shows correct 2 decimals

### Step 6.3: Large Unit Value
- Add MIR item with Nilai Satuan = 999,999,999.99
- **Expected:** System handles without overflow
- Displays correctly with formatting

### Step 6.4: Very Small Quantity + Large Unit Value
- Qty: 1, Allowance: 0, Unit Value: 1,000,000.00
- **Expected:** Total Harga = 1,000,000.00
- Displays correctly

---

## Troubleshooting Common Issues

### Issue: "Nilai Satuan" field not visible in MIR
- **Check:** MIR status is Draft (not Approved/Completed)
- **Check:** Browser cache cleared (Ctrl+Shift+Delete)
- **Check:** PHP error log in `/Applications/XAMPP/xamppfiles/htdocs/mingbao_erp/application/logs/`

### Issue: Admin can see button, but click does nothing
- **Check:** Modal `#editMirItemModal` exists in HTML
- **Check:** Browser console for JavaScript errors (F12 → Console tab)
- **Check:** AutoNumeric library loaded correctly
- **Check:** AJAX endpoint `warehouse_in/update_mir_item` exists

### Issue: Non-admin sees button instead of "-"
- **Check:** User role is correctly assigned (not containing "admin")
- **Check:** File `/warehouse_in/detail.php` was modified correctly
- **Check:** Clear browser cache and reload
- **Check:** Verify role name in database: `SELECT role_name FROM roles WHERE id = [role_id]`

### Issue: Total Harga not calculating
- **Check:** JavaScript console for errors (F12)
- **Check:** updateTotalValue() function exists in page source
- **Check:** unit_value input has correct class/ID
- **Check:** Change event handler is attached

### Issue: Auto-load copies wrong unit_value
- **Check:** MIR item unit_value is saved correctly in database
- **Check:** Query: `SELECT unit_value FROM mir_details WHERE id = [itemId]`
- **Check:** Warehouse_in_model auto_load_mir_items() method

---

## Success Criteria Checklist

### Database ✅
- [ ] mir_details table has unit_value column
- [ ] Value persists after insert/update
- [ ] Default value is 0.00

### MIR Module ✅
- [ ] Unit value input visible on item form
- [ ] Total harga auto-calculates
- [ ] All users can edit (no role restriction)
- [ ] Values persist after save

### Warehouse_in Module - Admin ✅
- [ ] Edit button visible for MIR items
- [ ] Delete button visible for MIR items
- [ ] Modal opens on edit button click
- [ ] Can modify both qty and unit_value
- [ ] Changes persist after save
- [ ] Can delete items

### Warehouse_in Module - Non-Admin ✅
- [ ] Edit button NOT visible (shows "-")
- [ ] Delete button NOT visible (shows "-")
- [ ] Can view item data (qty, unit_value, total)
- [ ] Cannot interact with items

### Data Integrity ✅
- [ ] Auto-loaded unit_value matches MIR value
- [ ] Total harga calculation is correct
- [ ] Currency formatting is consistent
- [ ] No data loss on page reload

---

## Sign-Off Template

After testing, please fill in:

```
Test Date: ___________
Tester Name: ___________
Test Scenarios Completed:
  [ ] Scenario 1: MIR Unit Price Entry
  [ ] Scenario 2: Warehouse_in Admin Access
  [ ] Scenario 3: Warehouse_in Non-Admin Access
  [ ] Scenario 4: Price Calculation
  [ ] Scenario 5: Data Persistence
  [ ] Scenario 6: Edge Cases

Issues Found:
___________________________________________
___________________________________________

Status: [ ] PASSED [ ] FAILED [ ] NEEDS REVISION

Comments:
___________________________________________
___________________________________________
```

---

## Files to Monitor During Testing

Log files to check for errors:
```
/application/logs/log-YYYY-MM-DD.php
/error_log (in root)
```

View files to check:
```
application/views/mir/detail.php
application/views/warehouse_in/detail.php
```

Controller files to check:
```
application/controllers/Mir.php
application/controllers/Warehouse_in.php
```

---

## Support Contacts

For issues:
1. Check error logs
2. Review JavaScript console (F12)
3. Verify database query: `SELECT * FROM mir_details LIMIT 1` to confirm schema
4. Check role assignment: `SELECT u.username, r.role_name FROM users u JOIN roles r WHERE u.role_id = r.id`

