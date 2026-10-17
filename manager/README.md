# VELORA Showroom Manager

A PHP and MySQL extension to the VELORA portfolio project, built by **Muhammad Ahsan Awan**. It adds a database-backed car collection, an administrator workspace, and an enquiry inbox.

**[Open live PHP + MySQL demo](https://velora-ahsan.great-site.net/manager/public/)**

Browse the public collection and test enquiries with fictional details. Admin access is private. The demo stores submissions; it does not send email or create bookings.

## What works

- Admin login with hashed passwords, session renewal, and a 30-minute inactivity timeout.
- Dashboard showing total cars, published entries, and new enquiries.
- Add, edit, publish, unpublish, and delete cars.
- Public collection with category filters and a car enquiry form.
- Save enquiries, change their status, and delete test or unwanted records.
- Server-side validation, prepared PDO statements, escaped output, and CSRF tokens on POST forms.
- Database-backed request limits: five login attempts or five valid enquiry submissions per client IP per 15 minutes.
- Mobile layout rules, keyboard-friendly controls, and a JavaScript-free workflow.

The five sample cars start as **drafts**. They are demonstration records, not real stock or offers. Photos are reused from the existing project, with their existing source/permission limitations.

## Important: where it runs

**GitHub Pages cannot run this PHP application or its MySQL database.** The original static showroom continues to run on Pages. This manager has its own public collection at `manager/public/index.php`; changes here do not change the static HTML/JavaScript showroom.

Use XAMPP locally, or a PHP/MySQL hosting service. No Composer, npm, or frontend build step is required.

## XAMPP setup — Roman Urdu

Requirements: PHP **8.2+**, PDO MySQL, aur MySQL 8 / MariaDB 10.4+. XAMPP ki PHP 8.2 installation is project ke liye munasib hai.

1. GitHub repository ko **Code → Download ZIP** se download karo aur extract karo. Folder ka naam `velora-car-showroom` rakho.
2. Poora folder `C:\xampp\htdocs\velora-car-showroom` mein rakho. `assets` folder bhi saath hona chahiye.
3. XAMPP Control Panel mein **Apache** aur **MySQL** start karo.
4. Browser mein `http://localhost/phpmyadmin` kholo. **New** se `velora_manager` naam ka naya database banao; collation `utf8mb4_unicode_ci` rakh sakte ho.
5. Isi khali database ko select karo. **Import** se `manager/database/schema.sql` import karo. Schema ko kisi purane ya production database par dobara import mat karo.
6. `manager/config/example.php` ki copy banao aur us ka naam `local.php` rakho, isi `config` folder mein. Default local XAMPP mein username `root` aur password khali hota hai. Agar tumne password lagaya hai to apna local password likho. Ye file GitHub par upload nahi karni.
7. XAMPP ka **Shell** kholo aur ye command chalao:

```bat
cd C:\xampp\htdocs\velora-car-showroom
C:\xampp\php\php.exe manager\bin\create-admin.php
```

8. Apna username aur 12–72 bytes ka password do. Password local terminal mein nazar aayega: screen share ya record karte hue ye step mat karo. Koi default admin password nahi hai. Pehla admin banne ke baad ye setup command lock ho jati hai.
9. Login kholo: `http://localhost/velora-car-showroom/manager/public/login.php`.
10. **Cars → Edit car → Publish → Save changes** karo. **View showroom** se published car dekho, fictional details se enquiry bhejo, phir **Enquiries** inbox mein check karo.

## Local PHP development server

After configuring MySQL, importing the schema, and creating the first admin, run from the repository root:

```bash
php -S 127.0.0.1:8000 -t manager/public
```

Open `http://127.0.0.1:8000`. Serve **only `manager/public`** with PHP's development server. It does not read Apache `.htaccess` rules. The allowlisted `photo.php` endpoint serves the existing project images without exposing configuration or SQL files.

## Structure

| Directory | Role |
| --- | --- |
| `app/` | Database connection, sessions, authorization, validation, and shared templates |
| `bin/` | CLI-only first-admin setup |
| `config/` | Example settings and ignored local credentials |
| `database/` | MySQL/MariaDB schema and draft seed records |
| `public/` | Public showroom, login, admin pages, and CSS |
| `tests/` | Dependency-free validation checks |

The `enquiries` table keeps a snapshot of the car name. Deleting a car clears its reference but preserves the enquiry. Request-limit buckets retain a SHA-256 hash of the client IP, not the raw IP; expired buckets are cleaned on subsequent limited requests. All timestamps are UTC.

## Checks

First-release checks passed on PHP 8.3 and MariaDB 10.11: PHP syntax, 20 validation/output checks, first-admin setup locking, and the HTTP smoke flow below. This verifies server behavior; cross-device visual QA and production deployment remain separate next steps.

```bash
php manager/tests/check.php
```

`tests/http-smoke.py` can exercise a running **isolated localhost installation** using Python 3. Set `VELORA_SMOKE_URL`, `VELORA_SMOKE_USER`, and `VELORA_SMOKE_PASSWORD` in your environment, then run `python manager/tests/http-smoke.py`. It creates and removes its own fictional car/enquiry and intentionally triggers the login rate limit at the end. Wait 15 minutes before more login tests. Never use a production database for this test.

Manual end-to-end check: log in, add a draft, publish it, send a fictional enquiry, update its status, unpublish the car, and confirm it disappears from the public collection. Delete the test car and confirm the enquiry still retains its name. Confirm protected admin pages redirect after logout. Missing/invalid CSRF tokens must return HTTP 403.

## Hosting and scope

Use HTTPS and a dedicated least-privilege database user; set `secure_cookies` to `true`. Keep the configuration, SQL, setup scripts, and tests outside the public document root. The whole repository must remain available to PHP so `photo.php` can read the existing images. Disable public error display, keep PHP patched, and arrange database backups before using real data.

This is a first portfolio release: one admin role, five allowlisted existing photos, and a stored enquiry workflow. There is no image upload, email delivery, password reset, booking, payment, or claim of production security certification. Deletion requires a confirmation page and is permanent in this app. Use fictional contact details for demos and delete test records afterwards.

Next learning steps: image uploads with validation, automated integration tests in CI, and a hosted PHP demo after deployment and privacy settings are reviewed.
