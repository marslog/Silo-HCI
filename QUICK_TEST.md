# Quick Test Commands for File Browser Modal

Run these commands one at a time in the browser console (F12 → Console tab):

## Step 1: Verify page loaded
```javascript
console.log('API_URL:', API_URL);
console.log('PROXMOX_NODE:', PROXMOX_NODE);
```
Expected: Should show the API URL and node name

## Step 2: Test table update directly
```javascript
testTablePopulation()
```
Expected: Should see test rows appear in the modal, proves table CAN be updated

## Step 3: Test API call directly
```javascript
fetch('https://192.168.0.200:8889/api/v1/nodes/silo1/browse-directory', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ path: '/mnt' })
}).then(r => r.json()).then(d => {
    console.log('API Response:', d);
    console.log('Items count:', d.data?.length);
    d.data?.forEach(item => console.log('-', item.name));
});
```
Expected: Should show /mnt directory contents (sdb, storage, etc.)

## Step 4: Manually populate table with API data
```javascript
fetch('https://192.168.0.200:8889/api/v1/nodes/silo1/browse-directory', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ path: '/mnt' })
}).then(r => r.json()).then(d => {
    const tbody = document.getElementById('fileListBody');
    tbody.innerHTML = d.data.map(item => `
        <tr>
            <td>${item.is_dir ? '📁' : '📄'} ${item.name}</td>
            <td>${item.is_dir ? 'Folder' : 'File'}</td>
            <td>${item.is_dir ? '<button>Delete</button>' : ''}</td>
        </tr>
    `).join('');
    console.log('✅ Table populated with', d.data.length, 'items');
});
```
Expected: Should show folder list in modal table

## Step 5: Open file browser and check console
```javascript
openFileBrowser('/mnt')
```
Then look at console for all the debug messages

---

## Troubleshooting

### If Step 1 fails (no API_URL)
- Hard refresh: Ctrl+Shift+R
- Check browser console errors

### If Step 2 fails (test rows don't show)
- Modal HTML might be missing
- CSS display might be broken
- Run: `console.log(document.getElementById('fileBrowserModal'))`

### If Step 3 fails (API error)
- Backend might be down
- Run terminal: `docker logs silo-backend`
- Test: `curl -s -k -X POST https://192.168.0.200:8889/api/v1/nodes/silo1/browse-directory -H "Content-Type: application/json" -d '{"path": "/mnt"}'`

### If Step 4 works but Step 5 fails
- The loadDirectoryContents() function has a bug
- Check console output from Step 5 carefully

---

## Console Output Interpretation

✅ **Good signs:**
- `✅ openFileBrowser STARTED` appears
- `Result: FOUND ✅` for modal element
- `✅ FETCH SUCCEEDED` 
- `✅ TABLE RENDERED`
- Modal shows up visually

❌ **Bad signs:**
- No console logs appear at all
- `Result: NOT FOUND ❌` for modal
- `❌ Fetch error:`
- Modal shows but stays at "Loading..."
- Empty content appears (meaning tbody.innerHTML was cleared but not repopulated)

---

## Report Back With:
1. Output of Step 1 (API_URL, PROXMOX_NODE)
2. Result of Step 2 (did test table populate?)
3. Result of Step 3 (does API work?)
4. Result of Step 4 (can we manually populate?)
5. Result of Step 5 (what console messages appear?)
