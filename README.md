# 🛋️ Urban Ladder - E-Commerce Administration Console

Urban Ladder Admin Panel is a secure, responsive, full-stack backoffice management system built natively with PHP and MySQLi. Designed for premium furniture retail workflows, this platform allows administrators to seamlessly control layout hierarchies, manage inventory catalogs, track customer registration matrices, and monitor live metrics.

---

## ✨ Features

### 🖥️ Command & Analytics Dashboard
* **Dynamic Analytics Aggregation:** Compiles database states into real-time metric tracking cards (Total Categories, Subcategories, Live Products, and Total Customers).
* **Interactive Top Navigation Bar:** Features a clean brand control title, quick system notifications alert matrix, and a premium administrative account dropdown block.

### 📦 Catalog & Hierarchy Management
* **Relational Node Mapping:** Multi-folder structure managing inventory lifecycles seamlessly across Categories, Subcategories, and Products.
* **AJAX Dependent Dropdowns:** Implements live JavaScript Fetch workflows on product creation to pull child subcategories dynamically based on parent selections.
* **Automated Asset Cleanup:** Built-in server file system integration that uses automated `unlink` routines to destroy abandoned image files when records are replaced or deleted.

### 👥 Customer Base Ledger
* **Schema-Aligned Directory:** A real-time data grid tracking consumer account histories, verification timestamps, and unique profile identifiers mapped perfectly to the database structure.

---

## 🛠️ Tech Stack

* **Backend Engine:** PHP 8.x (Session state machines, object validation layers)
* **Database Layer:** MySQL / MariaDB (Relational indexes, primary keys tracking)
* **Frontend UI:** HTML5, CSS3 (Modern variables mapping, flexible grid arrays), Google Material Icons Engine

---

## 📂 Repository File Structure

```text
urbanladder/
├── admin/
│   ├── categories/
│   │   ├── categories_view.php
│   │   └── edit.php
│   ├── subcategories/
│   │   ├── subcategories_view.php
│   │   └── subcategories_edit.php
│   ├── products/
│   │   ├── index.php
│   │   ├── add.php
│   │   └── edit.php
│   ├── admin-style.css
│   ├── admin_login.php
│   ├── customers_view.php
│   └── index.php
├── assets/
│   └── images/
│       ├── screenshots/      <-- Store your workspace PNG/JPG images here!
│       └── uploads/          <-- Local multipart form file upload directory
├── config/
│   └── db.php
└── logout.php
