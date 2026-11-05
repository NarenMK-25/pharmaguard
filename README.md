# PharmaGuard: Pharmacy Stock and Expiry Alert System

PHP 8 + MySQL 8 + HTML/CSS/JavaScript. The website and its JSON API run together, so Java/Tomcat is not required for the current app.

## Host publicly on Render (free)

This repo includes [render.yaml](./render.yaml) for a free Docker web service. Render will give it a public `onrender.com` URL. The app needs a separate MySQL-compatible database: Render's free Postgres is not compatible with this app's MySQL schema, and Render's free Postgres currently expires after 30 days.

1. Push this repository to GitHub and connect it to Render. In Render, choose **New + > Blueprint** and select the repo; accept the `pharmaguard` web service from `render.yaml`.
2. Create a free MySQL-compatible TiDB Cloud Starter instance (or another externally reachable MySQL-compatible database), enable its public endpoint, and allow connections from Render. Copy its host, port, database name, user and password. TiDB's current free Starter quota is 5 GiB storage and 50 million request units per month per eligible MySQL-compatible instance; quota limits can suspend new connections until reset.
3. Configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` in the Render service's environment. For TiDB, set `DB_PORT` to the port shown by its connection settings (usually 4000) and set `DB_SSL_CA` to `/etc/ssl/certs/ca-certificates.crt` so PHP verifies TLS with the container's trusted root certificates.
4. Select the target database in TiDB's SQL editor and run `database/schema.sql` from this repository to create tables and demo stock.
5. The hosted demo accounts are pre-created. Sign in with `admin / Admin@123` or `pharma / Pharma@123`. For any other deployment, provision users through a private setup process before making the site public; do not expose a user-creation endpoint.

**Free-tier behavior:** Render spins a free web service down after 15 minutes without traffic; the next visit can take about a minute to wake it. Free web services also have a 750-hour monthly allowance. Render free services have no persistent disk, so prescription files uploaded to this demo are lost on spin-down, restart, or deploy. The external database is separate and has its own quotas/availability. This can stay online as a hobby/demo for months while providers keep their free tiers available and within quota, but it is not guaranteed uptime or durable file storage. Do not store real patient data.

## Host on InfinityFree

1. Create a hosting account and website in InfinityFree, then create a MySQL database in its control panel. Note the database hostname, full database name, username, and password.
2. Open that database in phpMyAdmin, select it, and import `database/schema.sql`.
3. Edit `web/config.php` and replace `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` with the values from the InfinityFree control panel.
4. Upload the **contents** of `web/` (including `api/`, `assets/`, and `uploads/`) to the site's `htdocs` directory. Do not upload the Java `servlet/` folder.
5. Provision users through a private setup process before making the site public; this repository does not ship a public user-creation endpoint.
6. The hosted demo uses `admin / Admin@123` or `pharma / Pharma@123`; these credentials are not created automatically on other deployments.

The `.htaccess` files disable directory listings, block direct access to the database config and uploaded prescription files, and leave prescription viewing to the authenticated PHP page. Keep HTTPS enabled.

This sample application is not designed for storing real patient/medical records; do not use it with real patient data or public-facing pharmacy operations.

## Run locally

1. Create a MySQL database named `pharmaguard`, then import the schema with `mysql -u root -p pharmaguard < database/schema.sql`.
2. Set the database values in `web/config.php` to your local credentials.
3. Serve `web/` with PHP 8 and the PDO MySQL extension (for example, point Apache's document root at `web/`).
4. Provision a user privately with a `password_hash()` value and an `ADMIN` or `PHARMACIST` role before signing in. Do not expose a user-creation endpoint.

## Project layout

- `database/schema.sql` — tables and demo stock data.
- `web/` — PHP pages, same-origin API endpoints, CSS/JS and private prescription storage.
- `servlet/` — legacy Tomcat implementation; not needed by the PHP-hosted site.

## Demo script for the viva
1. Dashboard shows expiring, expired and low-stock rows (from the demo data).
2. Dispense 150 Paracetamol: the 12-day batch is drained first (FEFO), then the next batch.
3. Try to dispense more than is usable: rejected, nothing changes (rollback).
4. Log in as `pharma` and try to receive stock: HTTP 403.
5. Upload a prescription, view it, mark it dispensed.
6. Download the reorder CSV.

## Possible extensions
Email/SMS alerts for expiry, barcode scan on dispense, supplier purchase orders, write-off of expired batches, charts.
