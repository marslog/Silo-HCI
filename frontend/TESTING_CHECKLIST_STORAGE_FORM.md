# Storage Form Testing Checklist

## Extensions Installed for Code Quality
✅ **ESLint** - Detects JavaScript errors and bad practices
✅ **Prettier** - Catches formatting and syntax issues
✅ **PHP Intelephense** - PHP error checking and code intelligence
✅ **Error Lens** - Shows errors inline in the editor
✅ **Code Spell Checker** - Catches typos in comments and strings

---

## Test Case 1: Modal Opens Without Errors
**Objective:** Verify the Add Storage modal opens cleanly

### Steps:
1. Open browser DevTools (F12)
2. Go to Console tab
3. Click "Add Storage" button on storage page
4. Observe modal appears

### Expected Results:
- ✅ Modal displays with no JavaScript errors
- ✅ Console shows: "Opening Add Storage modal..."
- ✅ NO error messages about null properties
- ✅ Node dropdown is empty (-- Select Node --)
- ✅ All 3 storage type cards visible
- ✅ Scan Disk button is DISABLED (grayed out)

### Actual Results:
- [ ] Pass / [ ] Fail
- Notes: ___________________

---

## Test Case 2: Node Selection
**Objective:** Verify node dropdown works and enables scan button

### Steps:
1. Modal is open (from Test 1)
2. Click Node dropdown
3. Select "silo1" (or any available node)

### Expected Results:
- ✅ Node is selected
- ✅ Storage type cards remain visible
- ✅ Scan button remains DISABLED until storage type is selected

### Actual Results:
- [ ] Pass / [ ] Fail
- Notes: ___________________

---

## Test Case 3: Storage Type Card Selection
**Objective:** Verify storage type cards work correctly

### Steps:
1. Modal is open with node selected (from Test 2)
2. Click on "Local Disk" card
3. Observe card highlight and button state
4. Click on "Virtual Storage" card
5. Observe change
6. Click on "Network Storage" card
7. Observe change

### Expected Results for Each Card:
- ✅ Selected card has BLUE border and gradient background
- ✅ Icon and text are highlighted
- ✅ Scan button becomes ENABLED (dark blue, clickable)
- ✅ Detected list is CLEARED when switching types
- ✅ Smooth transition animation

### Actual Results:
- [ ] Pass / [ ] Fail
- Notes: ___________________

---

## Test Case 4: Scan Disk Button - Local Disk
**Objective:** Verify scanning for local disk storage

### Steps:
1. Modal open, node selected, "Local Disk" card selected
2. Click "Scan Disk" button
3. Wait for scan to complete

### Expected Results:
- ✅ Button shows loading spinner: "⟳ Scanning..."
- ✅ After ~2-3 seconds: List of detected directories appears
- ✅ Each item shows: path name, size info, and "Available" badge
- ✅ Items are clickable (cursor changes to pointer)

### Actual Results:
- [ ] Pass / [ ] Fail
- Notes: ___________________

---

## Test Case 5: Scan Disk Button - Virtual Storage
**Objective:** Verify scanning for Ceph RBD storage

### Steps:
1. Modal open, node selected
2. Click "Virtual Storage" card
3. Click "Scan Disk" button
4. Wait for scan to complete

### Expected Results:
- ✅ Scan starts with loading animation
- ✅ Detected Ceph pools appear in list
- ✅ Each item shows: pool name, size, status
- ✅ Items are clickable

### Actual Results:
- [ ] Pass / [ ] Fail
- Notes: ___________________

---

## Test Case 6: Scan Disk Button - Network Storage
**Objective:** Verify scanning for NFS storage

### Steps:
1. Modal open, node selected
2. Click "Network Storage" card
3. Click "Scan Disk" button
4. Wait for scan to complete

### Expected Results:
- ✅ Scan starts with loading animation
- ✅ Detected NFS shares appear in list
- ✅ Each item shows: server, export path, size
- ✅ Items are clickable

### Actual Results:
- [ ] Pass / [ ] Fail
- Notes: ___________________

---

## Test Case 7: Select Detected Storage Item
**Objective:** Verify selecting detected storage auto-fills form

### Steps:
1. Have detected storage list visible (from Test 4, 5, or 6)
2. Click on one detected storage item

### Expected Results - Local Disk:
- ✅ Item highlights with blue border
- ✅ Badge changes to "Selected" (green/blue)
- ✅ Storage ID field auto-fills with suggested name (e.g., "local-silo1")
- ✅ Directory Path field auto-fills with detected path
- ✅ Form appears below (Step 2 becomes visible)

### Expected Results - Virtual Storage:
- ✅ Item highlights
- ✅ Badge shows "Selected"
- ✅ Storage ID auto-fills (e.g., "ceph-rbd")
- ✅ Monitor Hosts field auto-fills
- ✅ Pool field auto-fills

### Expected Results - Network Storage:
- ✅ Item highlights
- ✅ Badge shows "Selected"
- ✅ Storage ID auto-fills (e.g., "nfs-storage")
- ✅ NFS Server field auto-fills
- ✅ Export Path field auto-fills

### Actual Results:
- [ ] Pass / [ ] Fail
- Notes: ___________________

---

## Test Case 8: Content Types Selection
**Objective:** Verify multi-select dropdown for content types

### Steps:
1. Storage fields are visible (after selecting detected storage)
2. Scroll down to "Content Types" section
3. Click on the multi-select dropdown
4. Hold CTRL (or CMD on Mac)
5. Click on "Disk Images (VM/CT disks)" - should stay selected
6. Click on "ISO Images (Installation media)"
7. Click on "Backups"
8. Release CTRL

### Expected Results:
- ✅ Multiple items can be selected simultaneously
- ✅ Selected items are highlighted in blue
- ✅ List shows all 6 content types available
- ✅ Selection persists while dropdown is open

### Actual Results:
- [ ] Pass / [ ] Fail
- Notes: ___________________

---

## Test Case 9: Complete Form Submission
**Objective:** Verify form can be submitted with all data

### Steps:
1. Complete form is visible with all fields filled:
   - Node: selected
   - Storage Type: selected (card highlighted)
   - Detected storage: selected
   - Storage ID: filled with suggested name
   - Type-specific fields: filled from detection
   - Content Types: multiple items selected
2. Leave "Shared storage" and "Enable storage" at defaults
3. Click "Add Storage" button
4. Observe response

### Expected Results:
- ✅ Form validates and sends POST request
- ✅ Loading indicator or success message appears
- ✅ Either:
   - Success message with green notification
   - OR appropriate error message if API fails
- ✅ Modal closes on success

### Actual Results:
- [ ] Pass / [ ] Fail
- Notes: ___________________

---

## Test Case 10: Modal Reset After Close
**Objective:** Verify modal resets for next use

### Steps:
1. After Test 9, click "Cancel" or close modal
2. Click "Add Storage" button again

### Expected Results:
- ✅ Modal opens with all fields cleared
- ✅ Node dropdown shows "-- Select Node --"
- ✅ Storage type cards are unchecked (no blue border)
- ✅ Scan button is DISABLED
- ✅ Detected list is hidden
- ✅ Form fields (Step 2) are hidden
- ✅ NO JavaScript errors in console

### Actual Results:
- [ ] Pass / [ ] Fail
- Notes: ___________________

---

## Browser Console Error Check
**Objective:** Verify no JavaScript errors occur during testing

### During All Tests:
- Check DevTools Console for ANY error messages
- Filter for ❌ (errors only)

### Expected Results:
- ✅ NO "Cannot read properties of null" errors
- ✅ NO "Uncaught TypeError" errors
- ✅ NO "Uncaught ReferenceError" errors
- ✅ Only informational logs should appear (info messages prefixed with ℹ️)

### Actual Results:
- [ ] Pass / [ ] Fail
- Errors found: ___________________

---

## Summary

| Test Case | Result | Notes |
|-----------|--------|-------|
| 1. Modal Opens | [ ] | |
| 2. Node Selection | [ ] | |
| 3. Storage Type Cards | [ ] | |
| 4. Scan - Local Disk | [ ] | |
| 5. Scan - Virtual Storage | [ ] | |
| 6. Scan - Network Storage | [ ] | |
| 7. Select Detected Storage | [ ] | |
| 8. Content Types Selection | [ ] | |
| 9. Form Submission | [ ] | |
| 10. Modal Reset | [ ] | |

**Overall Status:** [ ] ALL PASS ✅ / [ ] NEEDS FIXES ⚠️

---

## Code Quality Checks

### JavaScript Errors (ESLint)
- [ ] No unused variables
- [ ] No missing semicolons
- [ ] No undefined functions
- [ ] Proper error handling with try-catch blocks

### PHP Errors (Intelephense)
- [ ] No PHP syntax errors
- [ ] No undefined variables
- [ ] Proper null checks before accessing properties

### Formatting (Prettier)
- [ ] Consistent indentation
- [ ] Consistent quote usage
- [ ] Proper spacing around operators
