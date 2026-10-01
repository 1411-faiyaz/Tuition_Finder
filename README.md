# 🎓 Tuition Finder

A tuition/tutor marketplace web app — guardians post tuition requirements, tutors browse and apply, and an admin moderates the platform. Includes a **Tutor Salary Prediction Assistant**: a guided chatbot (tutors only) backed by a trained ML model.

**Stack:** PHP + MySQL (XAMPP) on the web side, a small Python/FastAPI service for the ML-powered chatbot.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Prerequisites](#prerequisites)
- [Setup](#setup)
- [Running the App](#running-the-app)
- [Demo Accounts](#demo-accounts)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing-teammates)
- [Notes](#notes)

---

## Features

- **Three roles:** Guardian, Tutor, Admin — session-based auth, role guards on every protected page
- **Guardian:** post/edit/delete tuition requirements, browse tutors, review & accept/reject applications
- **Tutor:** build a profile, browse the tuition board, apply to posts, track application status
- **Admin:** approve/reject tutor accounts, moderate tuition posts, manage all users
- **Messaging:** guardians and tutors can message each other
- **🤖 Tutor Salary Prediction Assistant:** a guided chat UI, tutor-only, that walks through area / class / curriculum / subject / schedule and returns a real ML-predicted monthly salary estimate (via a CatBoost model served by a separate Python microservice)
- Client-side (JS) + server-side (PHP) validation everywhere; passwords hashed with `password_hash()`; all queries use prepared statements

---

## Tech Stack

| Layer | Tech |
|---|---|
| Frontend | HTML5, custom CSS, vanilla JavaScript |
| Backend | PHP 8 (`mysqli`, prepared statements) |
| Database | MySQL / MariaDB |
| ML service | Python, FastAPI, CatBoost |
| Local environment | XAMPP (Apache + MySQL) |

---

## Project Structure

```
tuition-finder/
├── config/
│   ├── db.php                 # Database connection settings
│   └── backend.php            # URL + shared key for the Python ML service
├── includes/
│   ├── functions.php          # Session, auth guards, helpers, validation
│   ├── header.php             # Shared header/nav (role-aware)
│   └── footer.php             # Shared footer
├── assets/
│   ├── css/style.css          # Site-wide styling
│   ├── css/chatbot.css        # Salary Prediction Assistant styling
│   └── js/validation.js       # Client-side form validation
├── database/
│   └── schema.sql             # Full DB schema + seed/demo data
├── index.php / signup.php / login.php / logout.php
├── backend/                   # Python ML microservice
│   ├── main.py                 # FastAPI app serving the salary model
│   ├── requirements.txt
│   ├── train_model.py          # Retrains the CatBoost model (optional)
│   ├── data/                   # Training dataset
│   └── model/                  # Trained model file (salary_model.pkl)
├── tutor/
│   ├── dashboard.php / profile.php
│   ├── browse-tuitions.php / apply.php / my-applications.php
│   ├── salary-chatbot.php     # Salary Prediction Assistant UI
│   └── predict-salary.php     # AJAX endpoint -> proxies to backend/main.py
├── guardian/
│   ├── dashboard.php / post-tuition.php / tuitions.php
│   ├── edit-tuition.php / delete-tuition.php
│   ├── applications.php / browse-tutors.php
├── messages/
│   └── inbox.php
└── admin/
    ├── dashboard.php / manage-users.php / manage-tuitions.php
```

---

## Prerequisites

- [XAMPP](https://www.apachefriends.org/) (PHP 8.0+ and MySQL/MariaDB included)
- [Python 3.9+](https://www.python.org/downloads/) and `pip`
- Any modern browser
- Git

---

## Setup

### 1. Clone the repo
```bash
git clone <your-repo-url> tuition-finder
```
Copy (or symlink) the cloned `tuition-finder` folder into your XAMPP `htdocs` directory, e.g.:
- Windows: `C:\xampp\htdocs\tuition-finder`
- macOS: `/Applications/XAMPP/htdocs/tuition-finder`

### 2. Start Apache & MySQL
Open the **XAMPP Control Panel** → **Start** next to both **Apache** and **MySQL**.

### 3. Create the database
1. Go to `http://localhost/phpmyadmin`.
2. Click **Import** → **Choose File** → select `database/schema.sql` → **Go**.
3. You should now see a `tuition_finder` database with 5 tables (`users`, `tutor_profiles`, `tuition_posts`, `applications`, `messages`) and sample data.

> If your MySQL root user has a password (rare on a default XAMPP install), edit `config/db.php` and update `$DB_PASS`.

### 4. Install the Python service dependencies
```bash
cd backend
pip install -r requirements.txt
```

---

## Running the App

**Every time you work on this project, two things need to be running:**

**1. The Python ML service** (powers the Salary Prediction Assistant):
```bash
cd backend
python -m uvicorn main:app --reload --port 8000
```
Wait for `Salary model loaded from ...` and `Uvicorn running on http://127.0.0.1:8000`.
Check it's alive at [http://127.0.0.1:8000/health](http://127.0.0.1:8000/health) → should return `{"status":"ok"}`.

> If plain `uvicorn ...` says "not recognized", use `python -m uvicorn ...` instead (or `py -m uvicorn ...` on Windows) — this is just a PATH issue, not a bug.

**2. Apache + MySQL** via the XAMPP Control Panel (both showing "Running").

Then open:
```
http://localhost/tuition-finder/
```

If the Python service isn't running, the rest of the site works completely normally — the chatbot just shows a friendly "couldn't generate the salary prediction right now" message instead of a result.

---

## Demo Accounts

All demo accounts use the password **`admin123`**:

| Role      | Phone         | Notes                                |
|-----------|---------------|---------------------------------------|
| Admin     | 01700000000   | Full admin dashboard access           |
| Guardian  | 01710000001   | Already has 2 sample tuition posts    |
| Tutor     | 01710000002   | Already approved                      |
| Tutor     | 01710000003   | Pending admin approval (test this!)   |

Log in as either tutor account → **Salary Predictor** in the nav (or the dashboard shortcut) to try the chatbot. Guardian/admin accounts don't see this link and are redirected if they visit the URL directly.

You can also sign up new accounts from **Sign Up**. New tutor accounts start **pending** and need admin approval (Manage Users → Tutors); guardian accounts are approved automatically.

---

## Configuration

- `config/db.php` — MySQL connection settings.
- `config/backend.php` — URL and shared API key for the Python ML service. This key must match `SALARY_API_KEY` in `backend/main.py` (or its `SALARY_API_KEY` environment variable). **Change this key before deploying anywhere beyond localhost** — it's the only thing stopping someone who finds the Python service's port from getting predictions without going through the PHP app's tutor-only check.

---

## Troubleshooting

| Symptom | Likely cause / fix |
|---|---|
| `uvicorn` : term not recognized | Use `python -m uvicorn main:app --reload --port 8000` instead |
| `/health` doesn't load | Port 8000 already in use, or the service crashed on startup — check the terminal output |
| Chatbot always shows "couldn't generate a prediction" | The Python service (`uvicorn`) isn't running, or the key in `config/backend.php` doesn't match `backend/main.py` |
| "Database connection failed" | MySQL isn't running in XAMPP, or `tuition_finder` wasn't imported yet |
| Blank page / PHP error | Confirm PHP's `curl` extension is enabled (default in XAMPP) — `tutor/predict-salary.php` needs it |

---

## Contributing (teammates)

- Create a branch per feature/fix (`git checkout -b feature/your-thing`) rather than committing straight to `main`.
- Don't commit real credentials — `config/db.php` and `config/backend.php` are meant to be edited locally; if we move to real secrets later, we should switch these to environment variables and add them to `.gitignore`.
- Before pushing, re-import `database/schema.sql` on a clean database and click through your changed pages once — this project has no automated test suite yet.
- If you touch `backend/main.py` or the model, make sure `tutor/predict-salary.php`'s allow-lists (place/version/subject) still match — they're duplicated in three places on purpose (PHP, Python, and the chat UI's JS) and need to stay in sync; see the comments in each file.

---

## Notes

- Passwords are hashed with PHP's `password_hash()` (bcrypt) — never stored in plain text.
- All DB queries use prepared statements (`mysqli::prepare` + `bind_param`) — SQL-injection safe.
- All user-supplied output is escaped with `htmlspecialchars()` (the `h()` helper) — XSS safe.
- To reset the database, re-import `database/schema.sql` (drop the `tuition_finder` database first for a clean re-import, since the script doesn't drop existing tables).
- No CSRF tokens exist anywhere in the app yet (including the messaging and salary-prediction forms) — worth adding if this ever goes beyond a local/demo deployment.
