# แผนการย้าย Menu ไปด้านบน (Top Navigation)

## วัตถุประสงค์
ปรับ layout จาก sidebar navigation เป็น top navigation พร้อม left panel สำหรับ control menu

---

## โครงสร้างใหม่

```
┌─────────────────────────────────────────────────────┐
│  Logo  │ Dashboard │ Nodes │ VMs │ Containers │ ... │  ← Top Menu
├────────┴──────────────────────────────────────────────┤
│        │                                              │
│ Control│          Main Content Area                   │
│ Panel  │                                              │
│        │                                              │
│  - CPU │                                              │
│  - RAM │                                              │
│  - Disk│                                              │
│  - Net │                                              │
│        │                                              │
└────────┴──────────────────────────────────────────────┘
```

---

## ไฟล์ที่ต้องแก้ไข

### 1. `/frontend/public/components/header.php`
- เพิ่ม top navigation bar
- ย้าย menu items จาก sidebar

### 2. `/frontend/public/components/sidebar.php`
- เปลี่ยนเป็น control panel
- แสดงข้อมูล realtime (CPU, RAM, Disk, Network)
- เพิ่ม quick actions

### 3. `/frontend/public/assets/css/theme.css`
- ปรับ layout grid
- เพิ่ม top-nav styles
- ปรับ sidebar width และ content

### 4. Main Pages
- `dashboard.php`
- `nodes.php`
- `vms.php`
- `containers.php`
- `storage.php`
- `network.php`

---

## ข้อดีของ Layout ใหม่

✅ **ประหยัดพื้นที่แนวตั้ง**: เหมาะกับจอ ultrawide  
✅ **เข้าถึง menu ได้ง่าย**: menu อยู่ด้านบนตลอด  
✅ **Control panel**: แสดงข้อมูล realtime ด้านซ้าย  
✅ **Modern UX**: ตาม trend ของ cloud platform  

---

## Timeline การทำงาน

### Phase 1: Design & Planning (1-2 ชั่วโมง)
- [ ] วาด mockup layout ใหม่
- [ ] กำหนด menu structure
- [ ] กำหนด control panel content

### Phase 2: Implementation (4-6 ชั่วโมง)
- [ ] สร้าง top navigation component
- [ ] ปรับ sidebar เป็น control panel
- [ ] อัพเดท CSS/styling
- [ ] ทดสอบ responsive design

### Phase 3: Testing (1-2 ชั่วโมง)
- [ ] ทดสอบทุกหน้า
- [ ] ทดสอบ responsive (mobile, tablet, desktop)
- [ ] แก้ไข bugs

---

## คำแนะนำ

**ควรทำเป็น branch ใหม่**:
```bash
cd /opt/silo-hci
git checkout -b feature/top-navigation
```

**ทดสอบก่อน merge**:
- ทดสอบทุก feature
- ตรวจสอบ responsive
- รับ feedback จาก users

---

## ตัวอย่าง Top Navigation Structure

```html
<nav class="top-nav">
    <div class="nav-brand">
        <img src="/assets/images/logo.svg" alt="Silo HCI">
        <span>Silo HCI</span>
    </div>
    <ul class="nav-menu">
        <li><a href="/dashboard">Dashboard</a></li>
        <li><a href="/nodes">Nodes</a></li>
        <li><a href="/vms">Virtual Machines</a></li>
        <li><a href="/containers">Containers</a></li>
        <li><a href="/storage">Storage</a></li>
        <li><a href="/network">Network</a></li>
    </ul>
    <div class="nav-user">
        <span>Admin</span>
        <img src="/assets/images/user.png" class="avatar">
    </div>
</nav>
```

---

**หมายเหตุ**: การเปลี่ยน layout ครั้งใหญ่แบบนี้ควรทำในเวลาที่เหมาะสม เพื่อไม่กระทบการใช้งาน
