I would like to create a new project.
the project is for managing inventory in parts department
the parts department will have parts to keep track of.
the departments will purchase the parts from vendors and manufacturer.
some parts come with core charge from the manufacturer and when returned, will rebate a fraction of money. the return will vary by product and manufacturer
parts will have their own manufacturer part number. and the part number will change over time as they advance the parts. so the inventory should have to have a field to store old part numbers
same type of part are available in the aftermarket for lower price and they will have different part number, again according to manufacturer.
we will need to include a anchor point for all of these so that it will be easier to find them in the inventory
also add fast search (fulltext/index) so any oem/aftermarket/historical number and manufacturer will find the correct part.
the products have price that needs to be tracked while buying
they need to be able to sort products by sellers/manufacturers
url can help the workers find the parts easily if they need to buy the same stuff again.
work order number field will help the workers track the parts. work order is a different project that tracks worker to vehicle
product notes/description and stock notes are helpful


xxxxxxx


we should be able to track the parts to a technician. when we checkout the part to a user, it should be reflected.
some parts are going to be returned and they should also be recorded
parts with core charge being returned should be recorded accordingly
snapshot price/core at the time of movement so reports stay consistent even if supplier prices change.


xxxxx


we might not need to include the part size as I think the part number for different sized same items are different


xxxxx


in the inventory location should be identified by aisle, shelf and bay to pinpoint where it is stored
use transactions and row locks when changing stock to prevent race conditions and avoid negative stock (unless a flag allows it).
require an idempotency key for stock-changing requests to avoid double posts.
xxxxx
add a scanner feature that will have a camera interface and when scanned a part qr, it will show detail about the part like if given out to a technician, location, available units, manufacturer and so on
this should also have the feature to add to stock and remove from stock with full functionality
use signed qr payloads (not guessable ids), enforce csrf on forms, and rate limit stock-changing endpoints.


xxxxx


I would like the following technologies used for building this project
PHP (custom and proper MVC)
MySQL
HTML
CSS (custom + locally installed tailwind)
JS
PDF (possibly with LaTex)
Fontawesome
also keep soft delete flags on master data (parts/suppliers/locations) and a unique anchor per part. add fulltext over part numbers/manufacturer.
server compatibility: must work on both apache (with .htaccess) and iis (with web.config) for url rewriting and security headers.


security & standards:
- follow php-fig psr-4 autoloading and namespacing; composer-managed.
- enforce csrf protection (tokens, samesite=strict cookies) and rate limits on stock-changing endpoints.
- signed qr payloads; avoid exposing raw ids; validate inputs server-side.
- secrets via environment; no secrets in git; structured json logs with correlation ids.

xxxxx


delivery phases should be planned: phase 1 foundation (mvc skeleton, mysql schema incl. audit log and core liability fields, .env config, basic CRUD + fulltext search). phase 2 workflows (receiving with transactions/locks, checkout with work orders, returns/core returns with rebate logging, stock/location updates with idempotency keys). phase 3 QR/scanner (signed qr payloads, camera scan, show part details, add/remove stock with validation + audit). phase 4 reporting/alerts (stock on hand, aging, core liabilities, pending rebates, low-stock/core deadlines, optional daily snapshots). phase 5 polishing (pdf outputs via html-to-pdf, performance, security hardening, ux with tailwind/custom css).
environment steps: development uses local php server + mysql with `.env` for credentials/app url/feature flags and a committed `.env.example`. staging mirrors prod schema, uses anonymized data, enables full logging for qa, and loads `.env` from secure store. production uses hardened php runtime, managed mysql with backups, secrets via environment, minimal pii logging, and migrations before deploy.
enforce csrf/rate limits on stock endpoints and require idempotency keys; structured json logs with correlation ids.


xxxxx


authentication/roles: users authenticated via ldap are treated as "user" role. accounts stored in the database are "admin" and have elevated access.
