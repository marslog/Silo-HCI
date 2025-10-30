# File Browser Modal Debugging Guide

## Overview
This document provides step-by-step instructions to debug the file browser modal that appears when clicking the "Browse Folder" button in the Storage page.

## Issue Description
The file browser modal should display a list of directories from `/mnt/` when the user clicks the "Browse Folder" button, but it currently shows "no content" or doesn't appear.

## How to Test

### Step 1: Hard Refresh the Browser
1. Open https://192.168.0.200/storage.php
2. **Hard refresh to bypass cache:**
   - Windows/Linux: Press `Ctrl + Shift + R`
   - Mac: Press `Cmd + Shift + R`

### Step 2: Open Developer Console
1. Press `F12` (or right-click → Inspect)
2. Click on the **Console** tab
3. You should see green logs saying "Configured API_URL: ..." and "Actual API_URL used: ..."

**If you don't see these logs:**
- Hard refresh again (Ctrl+Shift+R)
- Close and re-open DevTools

### Step 3: Click "Browse Folder" Button
1. In the Storage page, scroll to the **Create New Storage** section (Step 1)
2. Click the **"Browse Folder"** button
3. **Keep the Console visible** as you do this

### Step 4: Check Console Output

You should see a series of colored logs appearing in the console. Here's what to look for:

#### Expected Console Output (in order):

```
🔍 openFileBrowser STARTED
   Requested path: /mnt
✓ Looking for modal element with id="fileBrowserModal"
   Result: FOUND ✅
➕ Adding .active class
🎨 Setting inline styles
✓ Checking computed styles:
   - display: flex
   - visibility: visible
   - opacity: 1
   - z-index: 9999
   - position: fixed
   - width: 100%
   - height: 100%
📂 Calling loadDirectoryContents("/mnt")

📂 loadDirectoryContents STARTED
   Path: /mnt
   API_URL: https://192.168.0.200:8889/api/v1
🌐 FETCHING API
   Node: silo1
   Full URL: https://192.168.0.200:8889/api/v1/nodes/silo1/browse-directory
   Path: /mnt
   → Sending fetch request...
✅ FETCH SUCCEEDED
   Status: 200 OK
   OK: true
   Headers: {content-type: application/json, content-length: ...}
✅ JSON PARSED
📨 API RESPONSE
   Full response: {success: true, data: [...]}
   Success: true
   Data type: object
   Data is array: true
   Data length: 2
📋 PROCESSING ITEMS
   Count: 2
   Items: [...]
🎨 RENDERING TABLE
   → Rendering 2 rows
   [0] sdb
   [1] storage
✅ TABLE RENDERED
   Rows added: 2
```

### Step 5: Visual Check

**Expected behavior after clicking "Browse Folder":**
1. A dark semi-transparent overlay appears (behind the modal)
2. A white modal box appears in the center of the screen
3. The modal contains a table with folder listings (e.g., "sdb", "storage")
4. The breadcrumb shows "/mnt" at the top

---

## Troubleshooting Guide

### Issue: Console logs don't appear at all

**Possible causes:**
1. Browser cache not cleared
2. Page not reloaded
3. Console closed/minimized

**Solutions:**
- Hard refresh with Ctrl+Shift+R
- Close and reopen DevTools
- Try a different browser (Chrome, Firefox)
- Check if JavaScript is enabled in browser settings

---

### Issue: Modal not visible on screen

**Check the console output:**
- Does it say `Result: FOUND ✅` for the modal element?
  - If **YES**: CSS display property not working. Check:
    ```
    - display: flex (should NOT be 'none')
    - visibility: visible (should NOT be 'hidden')
    - opacity: 1 (should NOT be 0)
    - z-index: 9999 (should be high number)
    - position: fixed (should NOT be 'static')
    ```
  - If **NO**: HTML element `fileBrowserModal` not found in page

**Quick test in console:**
```javascript
document.getElementById('fileBrowserModal')
```
- If result is `null`: Element doesn't exist
- If result shows an element: Element exists but CSS not working

---

### Issue: Modal visible but table is empty (shows "Loading...")

**Check the console for these sections:**

1. **Check FETCH phase:**
   ```
   ✅ FETCH SUCCEEDED
      Status: 200 OK
   ```
   - If status is NOT 200: API endpoint failed. Check error details.
   - If fetch error appears: Network/HTTPS certificate issue

2. **Check JSON parsing:**
   ```
   ✅ JSON PARSED
   ```
   - If JSON parse error: Response not valid JSON. Check browser Network tab.

3. **Check API response:**
   ```
   📨 API RESPONSE
      Success: true
   ```
   - If `Success: false`: API returned error. Check `Error:` message in logs.

4. **Check table rendering:**
   ```
   🎨 RENDERING TABLE
      → Rendering 2 rows
   ```
   - If this section missing: Error occurred before rendering

---

### Issue: API error or "Network error"

**Check console for:**
```
❌ Fetch error:
   Type: [error name]
   Message: [error message]
```

**Common errors:**
- `TypeError: Failed to fetch` → HTTPS/CORS issue
- `SyntaxError: Unexpected token` → Response not valid JSON
- `404 Not Found` → API endpoint doesn't exist

**Next steps:**
1. Test API directly in another tab:
   ```
   https://192.168.0.200:8889/api/v1/nodes/silo1/browse-directory
   ```
   Send POST request with body: `{"path": "/mnt"}`

2. Check browser Network tab:
   - Right-click button → Click "Browse Folder"
   - Go to Network tab
   - Look for request to `browse-directory`
   - Check Response tab for error details

---

## Additional Debugging Steps

### View Browser Network Activity
1. Open DevTools (F12)
2. Click **Network** tab
3. Click "Browse Folder" button
4. Look for `browse-directory` request
5. Click on it and check:
   - **Status**: Should be `200`
   - **Response**: Should show JSON with file list

### Check HTML Structure
In console, run:
```javascript
console.log(document.getElementById('fileBrowserModal'));
console.log(document.getElementById('fileListBody'));
```

Both should return element objects (not null).

### Test Modal Visibility Directly
In console, run:
```javascript
const m = document.getElementById('fileBrowserModal');
m.classList.add('active');
m.style.display = 'flex';
console.log(window.getComputedStyle(m).display);
```

Result should show `flex` (not `none`).

---

## Common Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| `❌ fileBrowserModal NOT FOUND` | HTML element missing | Check if modal HTML exists in page source |
| `❌ JSON PARSE ERROR` | Invalid JSON response | Check API returns valid JSON |
| `❌ API RETURNED ERROR: ...` | Backend endpoint failed | Check API logs: `docker logs silo-backend` |
| `❌ Fetch error: TypeError: Failed to fetch` | HTTPS/network issue | Test API in new tab, check CORS |
| `Error: APIUrl not configured` | API_URL undefined | Reload page, check PHP config |

---

## Questions to Answer for Support

If you need to report this issue, provide:

1. **Screenshot of console output** (paste entire sequence)
2. **Answer to: Does the modal appear on screen?** (Yes/No)
3. **Answer to: Are any table rows visible?** (Yes/No/Shows "Loading...")
4. **Browser and version** (Chrome 120, Firefox 121, etc.)
5. **Network tab screenshot** showing the browse-directory request and response

---

## Manual API Test

To verify the API works independently:

```bash
# From terminal on the server:
curl -s -k -X POST \
  https://192.168.0.200:8889/api/v1/nodes/silo1/browse-directory \
  -H "Content-Type: application/json" \
  -d '{"path": "/mnt"}' | jq .
```

Expected output:
```json
{
  "success": true,
  "data": [
    {
      "name": "sdb",
      "path": "/mnt/sdb",
      "is_dir": true,
      "type": "directory"
    },
    {
      "name": "storage",
      "path": "/mnt/storage",
      "is_dir": true,
      "type": "directory"
    }
  ]
}
```

If this works but the browser request fails, it's a front-end/browser issue.
If this fails, it's a backend API issue.

---

## Next Steps After Debugging

1. **Collect console output** (Ctrl+A, Ctrl+C to copy)
2. **Take screenshot** of:
   - Console output
   - Modal if visible
   - Network tab request/response
3. **Report specific error** with exact error messages
4. **System info**: Browser type/version, OS

This will help identify the exact point of failure and provide targeted fix.
