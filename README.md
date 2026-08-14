# Online Salon Appointment Booking System

A demo full-stack salon appointment booking app — **React** frontend + **PHP REST** API + **MySQL**.

> ⚠️ Demo / college project only. No real payments are processed.

---

## Features
- Customer, Salon Admin accounts (designer accounts created by admins)
- Browse salons, services, designers
- Book **normal** appointments with backend availability check (no double-booking)
- Request **urgent** appointments with **price negotiation** (propose → counter → accept/reject)
- Status flow: `pending → accepted → completed` (or `rejected/cancelled`)
- Cancel / accept / reject / complete controls per role
- Responsive UI (desktop, tablet, mobile)
- Role-based route guards + session auth

## Tech Stack
- **Frontend:** React 18 + Vite, React Router, Axios, plain CSS
- **Backend:** PHP 8+, PDO (MySQL), sessions
- **Database:** MySQL 5.7+ / 8

## Folder Structure
```
Online_Salon/
├── backend/
│   ├── config/database.php
│   ├── controllers/
│   ├── middleware/auth.php, role.php
│   ├── models/User.php, Salon.php, Service.php,
│   │          Designer.php, DesignerAvailability.php,
│   │          Appointment.php, UrgentRequest.php
│   ├── routes/auth.php, salons.php, services.php,
│   │         designers.php, appointments.php, urgent.php
│   ├── utils/response.php
│   ├── index.php  (front controller + CORS)
│   └── .env.example
├── frontend/
│   └── src/
│       ├── components/  (Navbar, ProtectedRoute,
│       │                 AppointmentCard, UrgentRequestCard)
│       ├── pages/       (Home, Login, Register, Salons,
│       │                 SalonDetails, Booking, MyAppointments,
│       │                 SalonDashboard, DesignerDashboard)
│       ├── services/api.js
│       ├── context/AuthContext.jsx
│       ├── routes/AppRoutes.jsx
│       ├── styles.css
│       └── App.jsx, main.jsx
└── database/
    ├── schema.sql
    └── seed.sql
```

## Quick Start

### 1. Database
```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

### 2. Backend
Copy `.env.example` to `.env` and fill in DB credentials, then run either under Apache (`htdocs/salon-api`) or with the built-in PHP server:
```bash
cd backend
php -S localhost:8000
```

### 3. Frontend
```bash
cd frontend
cp .env.example .env
npm install
npm run dev   # http://localhost:5173
```

## API Endpoints
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/salons?action=index
GET    /api/salons?action=show&id={id}
GET    /api/salons?action=mine             (admin)
POST   /api/salons?action=store            (admin)
PUT    /api/salons?action=update&id={id}   (admin)
DELETE /api/salons?action=destroy&id={id}  (admin)

GET    /api/services?action=index&salon_id={id}
POST   /api/services?action=store          (admin)
PUT    /api/services?action=update&id={id} (admin)
DELETE /api/services?action=destroy&id={id}(admin)

GET    /api/designers?action=index
GET    /api/designers?action=show&id={id}
GET    /api/designers?action=by-salon&salon_id={id}
POST   /api/designers?action=store         (admin)
PUT    /api/designers?action=update&id={id}(admin)
DELETE /api/designers?action=destroy&id={id}(admin)
GET    /api/designers?action=mine          (designer)
PUT    /api/designers?action=availability  (designer)

GET    /api/appointments?action=check-availability
POST   /api/appointments?action=store
GET    /api/appointments?action=index
GET    /api/appointments?action=show&id={id}
PUT    /api/appointments?action=accept&id={id}
PUT    /api/appointments?action=reject&id={id}
PUT    /api/appointments?action=cancel&id={id}
PUT    /api/appointments?action=complete&id={id}

POST   /api/urgent-requests?action=store   (customer)
GET    /api/urgent-requests?action=index
PUT    /api/urgent-requests?action=counter-offer&id={id}
PUT    /api/urgent-requests?action=accept&id={id}
PUT    /api/urgent-requests?action=reject&id={id}
```

## Demo Credentials
| Role | Email | Password |
|---|---|---|
| Customer | `customer@example.com` | `password123` |
| Salon Admin | `admin@example.com` | `password123` |
| Designer (created by admin) | varies | `designer123` (default) |

## Normal vs Urgent Flow
- **Normal**: customer books → `pending` → admin/designer accepts → `accepted` → `completed`.
- **Urgent**: when slot is taken, customer proposes an extra amount; admin/designer can accept, reject, or counter-offer; on accept, appointment becomes `urgent_accepted`.

## Notes
- Designer role is **not** self-registered; admins create designers from the dashboard.
- All prices are computed **server-side** (never trust client values).
- CORS configured via `FRONTEND_URL` env var; default `http://localhost:5173`.
