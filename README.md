# PharmaGuard: Pharmacy Stock and Expiry Alert System

HTML5/CSS3/JavaScript + PHP 8 + Java Servlets (Tomcat 9) + MySQL 8

## Layout
- `database/schema.sql`  tables, indexes and demo data
- `web/`                 PHP pages (login, dashboard, medicines, stock, prescriptions) + CSS/JS
- `servlet/`             Maven WAR: AuthFilter, AlertsServlet, DispenseServlet, ReceiveServlet

## Who does what
| Tier | Responsibility |
|---|---|
| PHP | login + sessions, medicine CRUD, prescription upload (type/size check, private storage), page rendering |
| Servlets | expiry/low-stock/reorder queries, FEFO dispensing in one locked transaction, batch receiving, token check |
| JavaScript | fetches `/api/alerts`, renders tables, CSV export, posts dispense/receive forms with no page reload |

PHP signs a cookie (`pg_token`, HMAC-SHA256). The Java `AuthFilter` verifies it, so one login covers both tiers.

## Setup
1. **Database:** `mysql -u root -p < database/schema.sql`
2. **PHP:** copy `web/` to Apache's web root as `pharmaguard/` (e.g. XAMPP `htdocs/pharmaguard`).
   Edit `config.php` (DB user/password). Open `http://localhost/pharmaguard/install.php` once, then delete `install.php`.
3. **Servlets:**
   - Put the MySQL connector JAR (`mysql-connector-j-8.x.jar`) in Tomcat's `lib/` folder.
   - Edit `servlet/src/main/webapp/META-INF/context.xml` (DB user/password).
   - `cd servlet && mvn package`, then copy `target/pharmaguard.war` to Tomcat's `webapps/` and start Tomcat 9.
4. **Secrets must match:** `TOKEN_SECRET` in `web/config.php` = `secret` in `servlet/.../WEB-INF/web.xml`. Change both from the default.
5. If Apache is not on port 80, set `allowedOrigin` in `web.xml` to your exact origin (e.g. `http://localhost:8081`).
6. Sign in at `http://localhost/pharmaguard/` with `admin / Admin@123` (can receive stock) or `pharma / Pharma@123`.

Tomcat 10+ uses `jakarta.*` instead of `javax.*`; use Tomcat 9 with this code.

## Demo script for the viva
1. Dashboard shows expiring, expired and low-stock rows (from the demo data).
2. Dispense 150 Paracetamol: the 12-day batch is drained first (FEFO), then the next batch.
3. Try to dispense more than is usable: rejected, nothing changes (rollback).
4. Log in as `pharma` and try to receive stock: HTTP 403.
5. Upload a prescription, view it, mark it dispensed.
6. Download the reorder CSV.

## Possible extensions
Email/SMS alerts for expiry, barcode scan on dispense, supplier purchase orders, write-off of expired batches, charts.
