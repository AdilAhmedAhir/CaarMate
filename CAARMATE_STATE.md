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

**Phase 4 — Booking Mutation**

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
| `_cm_commission_cut` | `number` | ✅ | Platform commission (10% of price) |

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
    │   │   ├── PostTypes.php      (cm_ride, cm_booking)
    │   │   ├── Shortcodes.php     (cm_ride_meta, cm_book_cta)
    │   │   └── BookingEngine.php  (transaction logic, seat inventory)
    │   └── assets/                (css/, js/)
    └── themes/caarmate-theme/
        ├── style.css              (theme header)
        ├── functions.php          (enqueue wrapper)
        ├── index.php              (silent fallback)
        ├── theme.json             (design tokens — colors, typography, layout)
        ├── assets/images/         (hero-bg.png)
        ├── parts/                 (block template parts)
        │   └── home/hero.html     (cinematic hero section)
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
- [x] Establish global design tokens — `theme.json` (Phase 2)
- [x] FSE templates (index, archive-cm_ride, single-cm_ride) — `templates/`
- [x] Shortcode API + data binding in single template — `Shortcodes.php` (Phase 3)
- [x] Booking mutation engine with transaction gates — `BookingEngine.php` (Phase 4)
- [x] Homepage Hero section assembled — `parts/home/hero.html` (Phase 6)
- [x] Homepage How It Works section assembled — `parts/home/how-it-works.html` (Phase 6)
- [x] Homepage Trust & Safety section assembled — `parts/home/trust.html` (Phase 6)
- [x] Homepage Featured Rides section assembled — `parts/home/featured.html` (Phase 6)
- [x] Premium CSS pipeline established — `assets/css/main.css` via `functions.php` enqueue
- [x] Hero rebuilt to Tier-1 functional UI — search widget, Uber-style layout, stark CSS
- [x] How It Works upgraded to Tier-1 interactive grid — feature cards, icon badges, hover-lift
- [x] Trust section upgraded to Tier-1 editorial layout — Z-pattern, check list, elevated media
- [x] Recent Rides upgraded to Tier-1 ticket stub UI — boarding pass cards, dashed divider, slide-arrow
- [x] Search Filter Logic (pre_get_posts) implemented — Filter.php, departure/destination/date
- [x] Ride Board upgraded to Tier-1 Sidebar + Ticket UI — archive page, sticky filter, inherited query

### 🔲 Pending — Phase 2

### 🔲 Backlog — Phase 2+

- [ ] Ride search & filtering
- [ ] Booking flow (frontend form → `cm_booking` creation)
- [ ] Driver dashboard
- [ ] Passenger dashboard
- [ ] REST API endpoints
- [ ] Tailwind build pipeline (PostCSS)
