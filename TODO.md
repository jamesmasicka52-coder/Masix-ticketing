# TODO - Tickets page enhancements

## Step 1
- Add backend endpoint `list_tickets_filtered.php` supporting filtering by date range, search, priority, status, company, department, assigned_to.
- Enforce authorization scope using existing helpers.

## Step 2
- Add backend endpoint `download_tickets_csv.php` that exports filtered tickets to CSV.
- Reuse same filtering params as Step 1.

## Step 3
- Refactor `tickets.php` UI to be more professional.
- Implement filter UI (date from/to, search, priority, status, company/department/assigned_to) and apply/clear.

## Step 4
- In `tickets.php`, wire filtering to `list_tickets_filtered.php` and render table results.

## Step 5
- Add CSV Download and Print buttons.
- Print uses a print-friendly table section populated with current filtered results.

## Step 6
- Test manually: filtering, CSV export, print output.

---

# TODO - Masix-ticketing Azure deployment

## Step A (done)
- Update `db.php` to support Azure App Settings via environment variables:
  - DB_HOST
  - DB_USER
  - DB_PASSWORD
  - DB_NAME

## Step B
- Create Azure MySQL database instance and apply `schema.sql`.

## Step C
- Create Azure App Service (PHP) for this project.
- Configure Application Settings with the DB_* values.
- Verify PHP routing works (entry is `index.php`).

## Step D
- Deploy project to Azure App Service (ZIP deploy) including PHP/CSS/JS/SQL.

## Step E
- Smoke test: login, tickets CRUD, filtering JSON endpoints.

