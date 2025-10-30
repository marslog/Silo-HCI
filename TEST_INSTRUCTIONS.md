# 🔧 How to Test File Browser - ทดสอบครับ

## ขั้นตอน 1: Hard Refresh
ไปที่ https://192.168.0.200/storage.php แล้ว:
- **Windows/Linux**: Ctrl+Shift+R
- **Mac**: Cmd+Shift+R

## ขั้นตอน 2: เปิด Console
กด F12 → เลือก **Console** tab

## ขั้นตอน 3: ไปหา "Browse Folder" button
- Scroll หาส่วน "Create New Storage"
- ดู Step 1
- คลิก **"Browse Folder"** button

## ขั้นตอน 4: ดู Console 
ลองดูว่าเขียน log อะไรบ้าง:

ถ้าใช้ได้ คุณควรเห็นประมาณนี้:
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
  ...
🌐 FETCHING API
  Node: silo1
  Full URL: https://192.168.0.200:8889/api/v1/nodes/silo1/browse-directory
  Path: /mnt
  → Sending fetch request...
✅ FETCH SUCCEEDED
  Status: 200 OK
  OK: true
📨 API RESPONSE
  Success: true
  Data is array: true
  Data length: 2
🎨 RENDERING TABLE
  → Rendering 2 rows
  [0] sdb
  [1] storage
✅ TABLE RENDERED
  Rows added: 2
```

## ขั้นตอน 5: ตรวจสอบ UI
ควรเห็น modal box ตรงกลางหน้า มี 2 folder:
- 📁 sdb
- 📁 storage

## ถ้าไม่ได้ครับ:
สกรีน console output ของคุณ + ถ่ายรูป screen (มี modal หรือไม่)

---

# 🧪 Test Alternative Page

หรือลองที่หน้า test นี้ก่อน:
https://192.168.0.200/test-file-browser.html

- Click "1. Test Modal Display" → ควรเห็น white box ตรงกลาง
- Click "2. Test API Fetch" → ควรเห็น log data ที่ดึงมาจาก API
- Click "3. Test Full Flow" → ควรเห็น modal มี table data ข้างใน

ถ้า test page นี้ใช้ได้ แสดงว่า HTML + API OK แต่ storage.php มี issue
