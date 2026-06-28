# Laravel HRMS REST API

A production-ready Human Resource Management System built as a REST API with Laravel 13. Covers the full employee lifecycle — onboarding, attendance, leave management, and payroll — with role-based access control enforced at the middleware level.

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Tests](https://img.shields.io/badge/Tests-31%20passing-4CAF50?logo=pestphp)
![License](https://img.shields.io/badge/License-MIT-blue)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4 |
| Framework | Laravel 13 |
| Authentication | Laravel Sanctum 4 |
| Database | MySQL (via Docker) |
| Testing | Pest PHP 4 |
| Container | Docker / Laradock |
| Code Style | Laravel Pint |

---

## Features

- **JWT-less token auth** via Laravel Sanctum (Bearer tokens, rotated on login)
- **Role-based access control** via custom `RoleMiddleware` supporting `admin`, `hr_manager`, and `employee` roles
- **Employee onboarding** — atomic DB transaction creates both `User` and `Employee` records; rolls back on failure
- **Attendance tracking** — daily check-in / check-out with duplicate prevention via composite unique constraint
- **Leave management** — employees apply; HR/Admin approve or reject with status transitions
- **Payroll generation** — auto-calculates `present_days` from Attendance table; enforces one payroll per employee per month; pro-rates salary; enforces draft → approved → paid state machine
- **API versioning** — all routes under `/api/v1/`
- **Structured JSON errors** — validation failures, 401s, and 422s all return consistent JSON

---

## Roles and Permissions

| Action | Admin | HR Manager | Employee |
|---|:---:|:---:|:---:|
| Register / Login / Logout | ✅ | ✅ | ✅ |
| Manage Departments (CRUD) | ✅ | ❌ | ❌ |
| Manage Designations (CRUD) | ✅ | ❌ | ❌ |
| Create / Update / Delete Employees | ✅ | ✅ | ❌ |
| View All Employees | ✅ | ✅ | ❌ |
| Apply for Leave | ✅ | ✅ | ✅ |
| View Own Leaves | ✅ | ✅ | ✅ |
| Approve / Reject Leave | ✅ | ✅ | ❌ |
| Check In / Check Out | ✅ | ✅ | ✅ |
| View All Attendance | ✅ | ✅ | Own only |
| Generate Payroll | ✅ | ✅ | ❌ |
| Approve / Mark Paid Payroll | ✅ | ✅ | ❌ |
| View All Payroll | ✅ | ✅ | Own only |

---

## Installation

### Prerequisites

- Docker + Docker Compose
- [Laradock](https://laradock.io/) cloned alongside this repo
- Git

### 1. Clone the repository

```bash
git clone https://github.com/devshoaib06-gkw/laravel-hrms-api.git
cd laravel-hrms-api
```

### 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` if needed — defaults are already configured for Laradock:

```
DB_HOST=mysql
DB_DATABASE=hrms
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Start Docker containers

```bash
# From your Laradock directory
docker-compose up -d nginx mysql workspace
```

### 4. Install dependencies and set up the application

```bash
# Enter the workspace container
docker-compose exec workspace bash

# Inside container:
composer install
php artisan key:generate
php artisan migrate --seed
```

### 5. Verify the API is running

```bash
curl http://localhost/api/v1/auth/login
# Expects 422 (validation error — confirms routing works)
```

---

## API Reference

Base URL: `http://localhost/api/v1`

All authenticated endpoints require: `Authorization: Bearer {token}`

### Auth

| Method | Endpoint | Auth | Roles |
|---|---|:---:|---|
| POST | `/auth/register` | No | — |
| POST | `/auth/login` | No | — |
| POST | `/auth/logout` | Yes | Any |

### Departments

| Method | Endpoint | Auth | Roles |
|---|---|:---:|---|
| GET | `/departments` | Yes | Admin |
| POST | `/departments` | Yes | Admin |
| GET | `/departments/{id}` | Yes | Admin |
| PUT | `/departments/{id}` | Yes | Admin |
| DELETE | `/departments/{id}` | Yes | Admin |

### Designations

| Method | Endpoint | Auth | Roles |
|---|---|:---:|---|
| GET | `/designations` | Yes | Admin |
| POST | `/designations` | Yes | Admin |
| GET | `/designations/{id}` | Yes | Admin |
| PUT | `/designations/{id}` | Yes | Admin |
| DELETE | `/designations/{id}` | Yes | Admin |

### Employees

| Method | Endpoint | Auth | Roles |
|---|---|:---:|---|
| GET | `/employees` | Yes | Admin, HR Manager |
| POST | `/employees` | Yes | Admin, HR Manager |
| GET | `/employees/{id}` | Yes | Admin, HR Manager |
| PUT | `/employees/{id}` | Yes | Admin, HR Manager |
| DELETE | `/employees/{id}` | Yes | Admin, HR Manager |

### Leaves

| Method | Endpoint | Auth | Roles |
|---|---|:---:|---|
| GET | `/leaves` | Yes | All |
| POST | `/leaves` | Yes | All |
| GET | `/leaves/{id}` | Yes | All |
| PUT | `/leaves/{id}` | Yes | Admin, HR Manager |

### Attendance

| Method | Endpoint | Auth | Roles |
|---|---|:---:|---|
| GET | `/attendance` | Yes | All (employees see own) |
| POST | `/attendance/check-in` | Yes | All |
| POST | `/attendance/check-out` | Yes | All |

### Payroll

| Method | Endpoint | Auth | Roles |
|---|---|:---:|---|
| GET | `/payroll` | Yes | Admin, HR Manager (employees see own) |
| POST | `/payroll` | Yes | Admin, HR Manager |
| GET | `/payroll/{id}` | Yes | Admin, HR Manager |
| PUT | `/payroll/{id}` | Yes | Admin, HR Manager |
| GET | `/my-payroll` | Yes | Employee |

---

## Request / Response Examples

### POST /api/v1/auth/login

**Request**
```json
{
  "email": "admin@hrms.com",
  "password": "password"
}
```

**Response 200**
```json
{
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@hrms.com",
    "role": "admin",
    "created_at": "2026-06-09T18:00:00.000000Z"
  },
  "token": "1|abc123xyz..."
}
```

**Response 401**
```json
{
  "message": "Invalid credentials."
}
```

---

### POST /api/v1/employees

**Request**
```json
{
  "name": "Jane Smith",
  "email": "jane@hrms.com",
  "password": "password",
  "phone": "+1-555-0100",
  "department_id": 1,
  "designation_id": 2,
  "joining_date": "2026-07-01",
  "salary": 5000
}
```

**Response 201**
```json
{
  "id": 3,
  "user_id": 5,
  "department_id": 1,
  "designation_id": 2,
  "joining_date": "2026-07-01",
  "phone": "+1-555-0100",
  "salary": "5000.00",
  "status": "active",
  "user": {
    "id": 5,
    "name": "Jane Smith",
    "email": "jane@hrms.com",
    "role": "employee"
  },
  "department": { "id": 1, "name": "Engineering" },
  "designation": { "id": 2, "name": "Software Engineer" }
}
```

---

### POST /api/v1/attendance/check-in

**Request** *(no body required)*
```
POST /api/v1/attendance/check-in
Authorization: Bearer {token}
```

**Response 201**
```json
{
  "id": 12,
  "employee_id": 3,
  "date": "2026-06-28",
  "clock_in": "2026-06-28T09:02:45.000000Z",
  "clock_out": null,
  "status": "present"
}
```

**Response 422** *(already checked in)*
```json
{
  "message": "Already checked in today."
}
```

---

### POST /api/v1/payroll

**Request**
```json
{
  "employee_id": 3,
  "month": 6,
  "year": 2026,
  "working_days": 22,
  "deductions": 200
}
```

**Response 201**
```json
{
  "id": 7,
  "employee_id": 3,
  "month": 6,
  "year": 2026,
  "basic_salary": "5000.00",
  "working_days": 22,
  "present_days": 18,
  "deductions": "200.00",
  "net_salary": "3890.91",
  "status": "draft",
  "approved_by": null,
  "employee": {
    "id": 3,
    "user": { "name": "Jane Smith", "email": "jane@hrms.com" }
  }
}
```

---

## Testing

Run the full test suite:

```bash
php artisan test --compact
```

Run a specific test file:

```bash
php artisan test --compact --filter=AuthTest
php artisan test --compact --filter=AttendanceTest
php artisan test --compact --filter=LeaveTest
```

The suite covers 31 tests across 5 files:

| File | Coverage |
|---|---|
| `AuthTest` | Register, login, logout, validation |
| `RoleMiddlewareTest` | Role enforcement per endpoint |
| `DepartmentTest` | Full CRUD + authorization |
| `LeaveTest` | Apply, view, approve/reject |
| `AttendanceTest` | Check-in, check-out, duplicate prevention |

---

## Key Design Decisions

### Separation of User and Employee tables

`User` stores authentication credentials and role. `Employee` stores HR data (salary, department, joining date). This separates auth concerns from HR concerns and lets the same `users` table serve all roles without nullable HR columns polluting the auth record.

### DB Transactions on Employee creation

`EmployeeController::store()` wraps the `User::create()` + `Employee::create()` pair in a `DB::beginTransaction()` / `DB::commit()`. If the Employee insert fails after the User is created, the transaction rolls back both, preventing orphaned user records.

### Composite unique constraints on Attendance and Payroll

`attendances` has a unique index on `(employee_id, date)` — enforced at both the DB and application level — preventing duplicate check-ins. `payrolls` has a unique index on `(employee_id, month, year)` preventing double payroll generation.

### Payroll auto-calculates present_days from Attendance

`PayrollController::store()` queries `Attendance` directly to count `status = 'present'` rows for the given employee/month/year. This makes payroll figures auditable — the source of truth is always the attendance record, not a manually entered number.

### Middleware in bootstrap/app.php

Laravel 13 removed `Kernel.php`. `RoleMiddleware` is registered as a named alias `role` in `bootstrap/app.php` and applied directly to route groups: `Route::middleware('role:admin,hr_manager')`.

---

## Project Structure

```
laravel-hrms-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── AuthController.php
│   │   │   ├── AttendanceController.php
│   │   │   ├── DepartmentController.php
│   │   │   ├── DesignationController.php
│   │   │   ├── EmployeeController.php
│   │   │   ├── LeaveController.php
│   │   │   └── PayrollController.php
│   │   └── Middleware/
│   │       └── RoleMiddleware.php
│   └── Models/
│       ├── Attendance.php
│       ├── Department.php
│       ├── Designation.php
│       ├── Employee.php
│       ├── Leave.php
│       ├── Payroll.php
│       └── User.php
├── bootstrap/
│   └── app.php              # Middleware registration
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php              # All /api/v1/ routes
├── tests/
│   └── Feature/
│       ├── AttendanceTest.php
│       ├── AuthTest.php
│       ├── DepartmentTest.php
│       ├── LeaveTest.php
│       └── RoleMiddlewareTest.php
├── .env.example
├── HRMS-API.postman_collection.json
└── README.md
```

---

## License

MIT
