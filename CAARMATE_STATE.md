# CAARMATE_STATE.md

> **AI Context Document** — Pass this file as context to avoid full-codebase reads.
> Last updated: 2026-03-03 (Phase 1 — Roles & CPTs registered)

---

## Project

| Key | Value |
|---|---|
| Name | CaarMate |
| Version | V1 MVP |
| Stack | WordPress 6.x · PHP 8.1 · FSE Block Theme |
| Plugin Namespace | `CaarMate\Core` |
| Theme Name | CaarMate Canvas |

---

## Current Build Phase

**Phase 1 — Scaffolding**

---

## Global Rules

1. **No business logic in the theme.** All CPTs, roles, meta, and API logic live in `caarmate-plugin`.
2. **Strict PSR-12** coding standards across all PHP files. `declare(strict_types=1)` required.
3. **Update this document** after every major feature merge.
4. **Commit convention:** `type: description` (e.g. `feat:`, `fix:`, `chore:`).

---

## Database Schema

### Custom Roles

| Role Slug | Display Name | Capabilities |
|---|---|---|
| `cm_driver` | Driver | `read`, `upload_files`, `edit_posts`, `edit_published_posts`, `publish_posts`, `delete_posts`, `delete_published_posts`, `edit_cm_rides`, `publish_cm_rides`, `delete_cm_rides` |
| `cm_passenger` | Passenger | `read`, `create_cm_bookings`, `read_cm_bookings` |

### Custom Post Types (CPTs)

| CPT Slug | Singular | Plural | Public | REST | Supports |
|---|---|---|---|---|---|
| `cm_ride` | Ride | Rides | Yes | `/wp-json/wp/v2/rides` | `title`, `author`, `custom-fields` |
| `cm_booking` | Booking | Bookings | No | `/wp-json/wp/v2/bookings` | `title`, `author`, `custom-fields` |

### Post Meta — `cm_ride`

| Meta Key | Type | REST | Description |
|---|---|---|---|
| `_cm_departure` | `string` | ✅ | Departure city / address |
| `_cm_destination` | `string` | ✅ | Destination city / address |
| `_cm_datetime` | `string` | ✅ | Ride date & time (ISO `datetime-local`) |
| `_cm_total_seats` | `integer` | ✅ | Total seats (1–8) |
| `_cm_available_seats` | `integer` | ✅ | Remaining seats (auto-defaults to total) |
| `_cm_price` | `number` | ✅ | Price per seat |

### Post Meta — `cm_booking`

| Meta Key | Type | REST | Description |
|---|---|---|---|
| `_cm_ride_id` | `integer` | ✅ | Associated `cm_ride` post ID |
| `_cm_passenger_id` | `integer` | ✅ | Booking user ID |
| `_cm_seats_booked` | `integer` | ✅ | Number of seats reserved |
| `_cm_status` | `string` | ✅ | `pending` · `confirmed` · `cancelled` |

---

## Theme Architecture

| Layer | Strategy |
|---|---|
| Rendering | Block-first Full-Site Editing (FSE) |
| CSS | Tailwind utility classes (CDN for MVP, build pipeline later) |
| Templates | `/templates/` — FSE page templates |
| Parts | `/parts/` — Reusable block template parts (header, footer) |
| `functions.php` | Enqueue only. Zero logic. |

---

## File Map

```
CaarMate/
├── CAARMATE_STATE.md          ← You are here
├── .gitignore
└── wp-content/
    ├── plugins/caarmate-plugin/
    │   ├── caarmate-plugin.php    (bootstrap, namespace CaarMate\Core)
    │   ├── inc/
    │   │   ├── Bootstrap.php      (wires subsystems into WP hooks)
    │   │   ├── Meta.php           (schema, meta boxes, save logic)
    │   │   ├── Roles.php          (cm_driver, cm_passenger)
    │   │   └── PostTypes.php      (cm_ride, cm_booking)
    │   └── assets/                (css/, js/)
    └── themes/caarmate-theme/
        ├── style.css              (theme header)
        ├── functions.php          (enqueue wrapper)
        ├── index.php              (silent fallback)
        ├── parts/                 (block template parts)
        └── templates/             (FSE templates)
```

---

## Task Tracker

### ✅ Completed

- [x] Initialize WordPress directory structure
- [x] Scaffold `caarmate-plugin` with headers & namespace
- [x] Scaffold `caarmate-theme` (CaarMate Canvas)
- [x] Create `.gitignore`
- [x] Push to GitHub (`AdilAhmedAhir/CaarMate`)
- [x] Register custom roles (`cm_driver`, `cm_passenger`) — `Roles.php`
- [x] Register CPTs (`cm_ride`, `cm_booking`) — `PostTypes.php`
- [x] Create OOP Bootstrap architecture — `Bootstrap.php`
- [x] Register post meta fields + admin meta boxes — `Meta.php`

### 🔲 Pending — Phase 1

- [ ] Add basic FSE templates (index, single-ride, archive-rides)

### 🔲 Backlog — Phase 2+

- [ ] Ride search & filtering
- [ ] Booking flow (frontend form → `cm_booking` creation)
- [ ] Driver dashboard
- [ ] Passenger dashboard
- [ ] REST API endpoints
- [ ] Tailwind build pipeline (PostCSS)
