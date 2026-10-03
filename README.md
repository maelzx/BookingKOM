# BookingKOM

**Rooms. Vehicles. Equipment. People. One booking system.**

BookingKOM is an open-source resource booking and scheduling system for small and medium
organisations. If it can be booked, it is a **resource** — meeting rooms, company vehicles,
projectors, training rooms, shared facilities, or a colleague's time. A single booking can combine
several resources at once, and the system never allows double-booking.

> **AssetKOM = What do we own? BookingKOM = When can we use it?**
>
> BookingKOM is a sibling application to [AssetKOM](https://github.com/maelzx/AssetKOM) and shares
> its foundation, conventions and visual language. An AssetKOM asset can become a bookable
> BookingKOM resource.

## Why BookingKOM?

- ✓ One booking can reserve **multiple resources** (room + projector + vehicle + person)
- ✓ **Server-side conflict detection** — overlaps, buffers, blocked periods and working hours
- ✓ **Day / week / month calendar** with filters by resource, type and status
- ✓ **Configurable approvals** per resource (none, admin, owner, or explicit acceptance)
- ✓ **Recurring bookings** (daily / weekly / monthly)
- ✓ **QR codes** on resources, opening a mobile availability page
- ✓ **Resource attachments** (private documents/images, authorised downloads)
- ✓ **Notifications** for creation, approval, decisions, cancellation and reminders
- ✓ **Reports & CSV export** — utilisation, most booked, cancellations, no-shows, by department
- ✓ **Role-based access** (Admin / Resource Manager / User)
- ✓ Audit trail via `spatie/laravel-activitylog`

## Screenshots

| Dashboard | Calendar |
|---|---|
| _coming soon_ | _coming soon_ |

| Resource detail + QR | New booking with conflict check |
|---|---|
| _coming soon_ | _coming soon_ |

## Core concepts

| Concept | Description |
|---|---|
| **Resource** | Anything bookable. Has a type, location, owner/manager, booking rules and an approval mode. |
| **Resource type** | Configurable grouping (Meeting Room, Vehicle, Equipment, Person…). |
| **Booking** | A time window, one organiser, one or more resources and optional attendees. |
| **Occurrence** | One booking in a recurring series. |
| **Blocked period** | Unavailable / maintenance / holiday time that overrides booking. |

### Booking statuses

`Pending → Confirmed → Completed`, plus `Rejected`, `Cancelled` and `No-show`.

Bookings with no approval requirement are **Confirmed** instantly. When any selected resource
requires approval the booking starts **Pending**, and becomes **Confirmed** once every resource
line is approved. Approvals are per resource line: each line is decided by that resource's owner
(or an administrator), and a single rejection rejects the whole booking.

> The `approved` status exists in the enum for manual/forward-compatible use; the standard
> approval flow moves straight from `Pending` to `Confirmed` when all lines are approved.

## Roles

| Capability | Admin | Resource Manager | User |
|---|---|---|---|
| Manage users / settings | ✅ | — | — |
| Manage resource types | ✅ | ✅ | — |
| Create resources | ✅ | ✅ | — |
| Edit resources | ✅ (all) | ✅ (assigned) | — |
| Delete resources | ✅ | — | — |
| Create bookings | ✅ | ✅ | ✅ |
| Approve bookings | ✅ (all) | ✅ (own resources) | — |
| Reports & exports | ✅ | ✅ | — |

### Visibility model

- **Calendar & booking detail** — any signed-in user can see every booking. Availability is
  shared, so the calendar never shows an entry it then refuses to open.
- **Bookings list** — defaults to *My bookings* for regular users and *Upcoming* for resource
  managers / admins. Everyone can switch the scope to *All* or *Past* to browse.
- **Writing** — only the organiser (or an admin) can edit or cancel a booking; only an admin,
  the resource owner, or an authorised resource manager can approve one.

## Tech stack

Laravel 13 · PHP 8.4 · Livewire 3 + Volt · Tailwind CSS 4 + daisyUI · Vite · SQLite (default) /
MySQL & MariaDB (production) · PHPUnit · endroid/qr-code · league/csv · spatie/laravel-activitylog.

## Try it locally

**Requirements:** PHP 8.4 (with `pdo_sqlite`, `mbstring`, `gd`), Composer, Node 20+ / npm.

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
php artisan serve          # http://localhost:9091 (port via APP_PORT)
```

Or run everything at once (server + queue + logs + Vite): `composer run dev`.

### Demo accounts (password `password`)

Seeded only in `local` / `testing`:

| Email | Role |
|---|---|
| `admin@bookingkom.test` | Administrator |
| `facilities@bookingkom.test` | Resource Manager |
| `fleet@bookingkom.test` | Resource Manager |
| `aisyah@bookingkom.test` | User |

The demo dataset includes meeting rooms, vehicles, equipment, a training room, a hall and a
bookable person, plus sample bookings in various states.

## Docker

```sh
cp .env.example .env
php artisan key:generate --show        # copy the value into .env as APP_KEY
docker compose up --build -d
docker compose exec app php artisan db:seed
```

The app is served at <http://localhost:9091>. SQLite data and uploads are kept in named volumes.
Set `BOOKINGKOM_RUN_SEEDER=true` to seed on first boot. A `queue` service runs the queue worker
for notifications and reminders.

## Configuration

Organisation settings live in the **Settings** screen and the `settings` table (cached):

- organisation name and timezone
- working days and working hours
- default / minimum / maximum booking duration
- advance-booking limit and minimum notice
- cancellation notice
- resource-code and booking-reference prefixes
- notification and QR toggles, reminder lead time

Resource-level rules (buffer time, per-resource duration limits, availability window and days)
override the organisation defaults.

## Testing

```sh
php artisan test
```

The suite covers conflict detection (overlap, buffer, blocked periods, working hours, recurrence,
multi-resource), the approval workflow, permissions/policies, the booking form component and full
page smoke tests.

## Background jobs

```sh
php artisan schedule:work     # sends upcoming-booking reminders every 15 minutes
php artisan queue:work        # processes notification jobs
```

## Going live

1. Point a domain with HTTPS at `public/` and set `APP_URL`.
2. Set `APP_ENV=production`, `APP_DEBUG=false` and a strong `APP_KEY`.
3. Use MySQL/MariaDB by setting the `DB_*` variables; migrations are engine-portable.
4. Run `composer install --no-dev --optimize-autoloader && npm ci && npm run build`.
5. Run `php artisan migrate --force` and `php artisan storage:link`.
6. Run a queue worker and the scheduler under a supervisor (systemd/supervisor).
7. Create the first administrator: `php artisan admin:create you@example.org`.

> **Cache store:** booking creation uses atomic cache locks (`cache.lock`) to prevent
> double-booking under concurrency. Keep a store that supports atomic locks — the
> default `database` store (uses the `cache_locks` table) or Redis. Do not use the
> `file` or `array` store in production.

## Project layout

```
app/
  Enums/            Role, ResourceStatus, BookingStatus, ApprovalMode, BlockType
  Models/           Resource, ResourceType, Booking, BookingAttendee, ResourceBlockedPeriod…
  Services/         BookingService, BookingAvailability, RecurrenceService, status transitions
  Policies/         ResourcePolicy, BookingPolicy, ResourceTypePolicy, AttachmentPolicy
  Notifications/    BookingCreated, BookingApprovalRequired, BookingDecision, BookingCancelled…
  Support/          QR + reference/code generators
database/
  migrations/       resources, bookings, booking_resource, blocked periods, attachments…
  seeders/          demo organisation, resources and bookings
resources/views/livewire/   dashboard, calendar, resources, bookings, approvals, reports…
tests/              conflict, approval, permission and smoke tests
```

## Related projects

- [AssetKOM](https://github.com/maelzx/AssetKOM) — open-source asset management (the sibling app this project shares its foundation with).

## Contact

Questions, feature requests, customisation, deployment help or collaboration — get in touch:

- **Request / collaboration form:** https://borang.digital/maelzx/bookingkom-contact
- **Issues:** https://github.com/maelzx/BookingKOM/issues

## License

MIT — see [LICENSE](LICENSE).
