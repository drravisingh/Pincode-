# 📁 FILE MANAGER INSTALLATION GUIDE - Complete Step by Step

## 🎯 cPanel/File Manager Se Website Kaise Install Karein

---

## 📊 **COMPLETE FOLDER STRUCTURE**

```
public_html/  (Ya www/ ya httpdocs/)
│
├── 📄 index.php                          ← Main entry point
├── 📄 install.php                        ← Run this first!
├── 📄 .htaccess                          ← Auto-created by installer
├── 📄 config.php                         ← Auto-created by installer
│
├── 📁 includes/                          ← Helper files (optional)
│
├── 📄 header.php                         ← Header template
├── 📄 footer.php                         ← Footer template
├── 📄 homepage_template.php              ← Homepage
├── 📄 template_pincode_page.php          ← PIN code pages
│
├── 📄 csv_importer.php                   ← Import script
├── 📄 post_generator.php                 ← Content generator
├── 📄 router_sitemap.php                 ← Routing system
│
├── 📁 templates/                         ← (Optional folder)
│   └── (Can move template files here)
│
├── 📁 legal/                            ← Legal pages
│   ├── 📄 privacy-policy.php
│   ├── 📄 terms-of-service.php
│   ├── 📄 disclaimer.php
│   ├── 📄 about.php
│   ├── 📄 contact.php
│   └── 📄 refund-policy.php
│
├── 📁 admin/                            ← Admin panel
│   └── 📄 admin_panel.html
│
├── 📁 assets/                           ← (Optional - for images/css)
│   ├── 📁 css/
│   ├── 📁 js/
│   └── 📁 images/
│
├── 📁 cache/                            ← Auto-created (for caching)
├── 📁 sitemaps/                         ← Auto-created (for sitemaps)
├── 📁 uploads/                          ← Auto-created (for CSV)
│
├── 📄 database_schema.sql               ← Database file
│
└── 📁 docs/                             ← Documentation (optional)
    ├── 📄 README.md
    ├── 📄 INSTALLATION_GUIDE.md
    └── (other .md files)
```

---

## 🚀 **STEP-BY-STEP INSTALLATION**

### **METHOD 1: Simple Method (Recommended)** ⭐

#### **Sabhi files ek saath public_html me daal do:**

```
public_html/
├── All .php files
├── All legal pages
├── admin_panel.html
├── database_schema.sql
└── Done!
```

**Advantage:** Simple, no complexity

---

### **METHOD 2: Organized Method** (Professional)

#### **Organized folders with structure:**

```
public_html/
├── index.php                    ← Root level
├── install.php                  ← Root level
├── header.php                   ← Root level
├── footer.php                   ← Root level
├── homepage_template.php        ← Root level
├── template_pincode_page.php    ← Root level
├── csv_importer.php            ← Root level
├── post_generator.php          ← Root level
├── router_sitemap.php          ← Root level
├── database_schema.sql         ← Root level
│
├── pages/                      ← Legal pages folder
│   ├── privacy-policy.php
│   ├── terms-of-service.php
│   ├── disclaimer.php
│   ├── about.php
│   ├── contact.php
│   └── refund-policy.php
│
├── admin/                      ← Admin folder
│   └── admin_panel.html
│
└── docs/                       ← Documentation folder
    └── (all .md files)
```

---

## 📝 **INSTALLATION STEPS**

### **STEP 1: LOGIN TO cPANEL**

```
1. Open browser
2. Go to: https://yoursite.com/cpanel
3. OR: https://yoursite.com:2083
4. Login with credentials
```

---

### **STEP 2: OPEN FILE MANAGER**

```
1. cPanel Dashboard dikhai dega
2. Find "File Manager" icon
3. Click on "File Manager"
4. New tab me File Manager khulega
```

**File Manager Location:**
```
cPanel > Files Section > File Manager
```

---

### **STEP 3: GO TO PUBLIC_HTML**

```
File Manager khulne ke baad:

1. Left sidebar me "public_html" dikhai dega
2. Click on "public_html"
3. Ye aapki website ka root folder hai
```

**Common Names:**
- public_html (Most common)
- www
- httpdocs
- public
- html

---

### **STEP 4: UPLOAD FILES**

#### **Option A: Upload All at Once** ⭐

```
1. Click "Upload" button (top menu)
2. Select all 24 files from your computer
3. Drag & drop OR click "Select File"
4. Wait for upload (2-3 minutes)
5. Done!
```

#### **Option B: Upload ZIP File** (Faster)

```
1. Create ZIP of all 24 files on computer
2. In File Manager, click "Upload"
3. Upload the ZIP file
4. After upload, right-click ZIP file
5. Select "Extract"
6. Extract to public_html
7. Delete ZIP file
8. Done!
```

---

### **STEP 5: CHECK FILES**

```
In File Manager, you should see:

public_html/
├── ✅ install.php
├── ✅ index.php
├── ✅ header.php
├── ✅ footer.php
├── ✅ homepage_template.php
├── ✅ template_pincode_page.php
├── ✅ csv_importer.php
├── ✅ post_generator.php
├── ✅ router_sitemap.php
├── ✅ database_schema.sql
├── ✅ admin_panel.html
├── ✅ All legal page files (.php)
└── ✅ All documentation files (.md)
```

**Verify Count:**
```
Total PHP files: 15
Total files: 24
```

---

### **STEP 6: SET PERMISSIONS**

#### **Important Files Need Correct Permissions:**

```
Right-click on file/folder → Permissions (or Change Permissions)

Recommended Permissions:
├── PHP files (.php)     → 644
├── HTML files (.html)   → 644
├── Folders              → 755
└── install.php          → 644 (delete after install)
```

**Quick Permission Guide:**
```
644 = Read & Write for owner, Read only for others
755 = Full access for owner, Read & Execute for others
```

---

### **STEP 7: CREATE FOLDERS** (Auto-created by installer, but check)

```
In File Manager:

1. Click "New Folder" button
2. Create these folders (if not exist):
   ├── cache/      (For caching)
   ├── sitemaps/   (For sitemaps)
   └── uploads/    (For CSV files)

3. Set permissions to 755 for all folders
```

---

### **STEP 8: RUN INSTALLER**

```
1. Open browser
2. Go to: http://yoursite.com/install.php
3. Follow the installation wizard
4. Installation will complete in 5 minutes
```

---

## 🎯 **DETAILED FOLDER STRUCTURE EXPLANATION**

### **Root Level Files (public_html):**

```
install.php                     ← START HERE! Installation wizard
index.php                       ← Main entry point, routes all requests
.htaccess                       ← Auto-created, handles URL rewriting
config.php                      ← Auto-created, stores DB credentials
```

### **Template Files (Root Level):**

```
header.php                      ← Master header (used everywhere)
footer.php                      ← Master footer (used everywhere)
homepage_template.php           ← Homepage template
template_pincode_page.php       ← PIN code page template
```

### **System Scripts (Root Level):**

```
csv_importer.php               ← Bulk CSV import script
post_generator.php             ← Dynamic content generator
router_sitemap.php             ← URL routing & sitemap generator
```

### **Legal Pages Folder (pages/ or root):**

```
pages/
├── privacy-policy.php         ← Privacy policy page
├── terms-of-service.php       ← Terms & conditions
├── disclaimer.php             ← Disclaimer page
├── about.php                  ← About us page
├── contact.php                ← Contact form
└── refund-policy.php          ← Refund policy
```

### **Admin Folder:**

```
admin/
└── admin_panel.html           ← Admin dashboard
```

### **Auto-Created Folders:**

```
cache/                         ← Page cache storage
├── (auto-generated files)

sitemaps/                      ← Generated sitemaps
├── sitemap_index.xml
├── sitemap-pincodes-1.xml
└── (more sitemap files)

uploads/                       ← CSV file uploads
└── your_csv_file.csv
```

### **Documentation (Optional):**

```
docs/                          ← Keep for reference
├── README.md
├── INSTALLATION_GUIDE.md
├── QUICK_START.md
└── (other guides)
```

---

## 🎨 **FOLDER STRUCTURE OPTIONS**

### **OPTION 1: Flat Structure** (Easiest) ⭐

```
public_html/
├── All PHP files (15 files)
├── All HTML files (1 file)
├── Database file (1 file)
└── Documentation (7 files)

Total: 24 files in one folder

✅ Advantage: Simple, no confusion
✅ Best for: Beginners
```

---

### **OPTION 2: Organized Structure** (Professional)

```
public_html/
├── Core files (root)
│   ├── index.php
│   ├── install.php
│   ├── header.php
│   ├── footer.php
│   └── (other core files)
│
├── pages/
│   └── Legal pages
│
├── admin/
│   └── Admin panel
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── cache/
├── sitemaps/
└── uploads/

✅ Advantage: Professional, organized
✅ Best for: Advanced users
```

---

### **OPTION 3: Modular Structure** (Advanced)

```
public_html/
├── index.php (root)
├── install.php (root)
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── config.php
│   └── functions.php
│
├── templates/
│   ├── homepage.php
│   └── pincode.php
│
├── classes/
│   ├── Importer.php
│   ├── Generator.php
│   └── Router.php
│
├── pages/
│   └── Legal pages
│
└── (other folders)

✅ Advantage: Most organized
✅ Best for: Developers
```

---

## ⚠️ **IMPORTANT FILE LOCATIONS**

### **MUST BE IN ROOT (public_html):**

```
✅ index.php           ← Website entry point
✅ install.php         ← Installation wizard
✅ .htaccess          ← URL rewriting (auto-created)
✅ config.php         ← Configuration (auto-created)
```

### **CAN BE ANYWHERE (with path updates):**

```
⚡ header.php          ← Can be in includes/
⚡ footer.php          ← Can be in includes/
⚡ templates           ← Can be in templates/
⚡ legal pages         ← Can be in pages/
```

**Note:** Agar move karo to include paths update karne padenge!

---

## 🔧 **FILE MANAGER OPERATIONS**

### **Create New Folder:**

```
1. Click "+ Folder" button
2. Enter folder name
3. Click "Create New Folder"
4. Folder created!
```

### **Upload Files:**

```
1. Click "Upload" button
2. Drag files OR click "Select File"
3. Wait for upload
4. Close upload window
5. Files uploaded!
```

### **Extract ZIP:**

```
1. Upload ZIP file
2. Right-click on ZIP file
3. Select "Extract"
4. Choose destination (public_html)
5. Click "Extract File(s)"
6. Done!
```

### **Set Permissions:**

```
1. Right-click on file/folder
2. Select "Change Permissions"
3. Set permissions:
   - Read: 4
   - Write: 2
   - Execute: 1
   
   Common combinations:
   - 644 = 4+2+0, 4+0+0, 4+0+0
   - 755 = 4+2+1, 4+0+1, 4+0+1
   
4. Click "Change Permissions"
5. Done!
```

### **Delete Files:**

```
1. Select file/folder
2. Click "Delete" button
3. Confirm deletion
4. Done!
```

### **Rename Files:**

```
1. Right-click on file
2. Select "Rename"
3. Enter new name
4. Click "Rename File"
5. Done!
```

### **Move Files:**

```
1. Select file
2. Click "Move" button
3. Choose destination folder
4. Click "Move File(s)"
5. Done!
```

---

## ✅ **RECOMMENDED STRUCTURE FOR BEGINNERS**

```
public_html/
│
├── 📄 index.php
├── 📄 install.php
├── 📄 .htaccess (auto-created)
├── 📄 config.php (auto-created)
│
├── 📄 header.php
├── 📄 footer.php
├── 📄 homepage_template.php
├── 📄 template_pincode_page.php
│
├── 📄 csv_importer.php
├── 📄 post_generator.php
├── 📄 router_sitemap.php
├── 📄 database_schema.sql
│
├── 📄 privacy-policy.php
├── 📄 terms-of-service.php
├── 📄 disclaimer.php
├── 📄 about.php
├── 📄 contact.php
├── 📄 refund-policy.php
│
├── 📄 admin_panel.html
│
├── 📁 cache/ (create this)
├── 📁 sitemaps/ (create this)
├── 📁 uploads/ (create this)
│
└── 📁 docs/ (optional)
    └── All .md files
```

**Total Files in public_html: 24**

---

## 🎯 **QUICK INSTALLATION CHECKLIST**

### **Pre-Installation:**
- [ ] cPanel login credentials ready
- [ ] All 24 files downloaded
- [ ] Domain pointing to hosting

### **File Manager Steps:**
- [ ] Logged into cPanel
- [ ] Opened File Manager
- [ ] Navigated to public_html
- [ ] Uploaded all 24 files
- [ ] Verified all files present
- [ ] Set correct permissions (644 for files, 755 for folders)
- [ ] Created cache/, sitemaps/, uploads/ folders

### **Installation Steps:**
- [ ] Visited yoursite.com/install.php
- [ ] Followed installation wizard
- [ ] Entered database credentials
- [ ] Created admin account
- [ ] Installation completed
- [ ] Deleted install.php (security)

### **Post-Installation:**
- [ ] Homepage loads correctly
- [ ] Header/footer showing
- [ ] Legal pages accessible
- [ ] Admin panel accessible
- [ ] Ready to import CSV

---

## 📞 **COMMON ISSUES & SOLUTIONS**

### **Issue 1: 500 Internal Server Error**
```
Solution:
1. Check .htaccess file
2. Check file permissions
3. Check PHP version (need 7.4+)
4. Check error logs
```

### **Issue 2: Files Not Showing**
```
Solution:
1. Clear browser cache (Ctrl + F5)
2. Check file uploaded to correct folder
3. Check file permissions (644)
4. Wait 1-2 minutes for server
```

### **Issue 3: Cannot Create Folders**
```
Solution:
1. Check public_html permissions (should be 755)
2. Contact hosting support
3. Use FTP client (FileZilla)
```

### **Issue 4: Upload Failed**
```
Solution:
1. Check file size limit
2. Try ZIP method
3. Upload in batches
4. Use FTP client
```

---

## 💡 **PRO TIPS**

### **1. Use ZIP Upload:**
```
✅ Faster than individual files
✅ No file left behind
✅ Easy extraction
```

### **2. Backup Original Files:**
```
✅ Keep backup on computer
✅ Can re-upload if needed
✅ Safety measure
```

### **3. Organize Documentation:**
```
✅ Keep .md files in docs/ folder
✅ Easy reference
✅ Clean public_html
```

### **4. Check Permissions:**
```
✅ Files: 644
✅ Folders: 755
✅ Security important
```

### **5. Test After Upload:**
```
✅ Visit homepage
✅ Check if files loading
✅ Verify before proceeding
```

---

## 🎉 **SUMMARY**

### **Simple 5-Step Process:**

```
1. Login to cPanel → File Manager
2. Go to public_html folder
3. Upload all 24 files (drag & drop)
4. Visit yoursite.com/install.php
5. Follow wizard → Done! ✅
```

### **File Count Verification:**

```
PHP files: 15
HTML files: 1
SQL files: 1
Documentation: 7
Total: 24 files ✅
```

### **Folder Structure (Simple):**

```
public_html/
└── All 24 files (flat structure)
    ├── Core files
    ├── Legal pages
    ├── Admin panel
    └── Documentation
```

---

## ✅ **READY TO INSTALL!**

**Follow steps above aur 30 minutes me website ready! 🚀**

---

**Questions? Check INSTALLATION_GUIDE.md for more details!**

**Good luck! 💪**