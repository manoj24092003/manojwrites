# 📚 Manoj Writes

A modern **story / episodic writing platform** built with **PHP,
JavaScript, and Firebase**.

This project allows users to: - Read episodic stories - Search stories
instantly - Login with Google - Receive push notifications - Install the
website as a Progressive Web App (PWA)

------------------------------------------------------------------------

## 🌐 Live Website

🔗 https://manojwrites.xyz

------------------------------------------------------------------------

## ✨ Features

### 📖 Story Platform

-   Episodic story publishing system
-   JSON-based episode storage
-   Dynamic content loading

### 🔎 Search System

-   Fast story search functionality
-   Client-side filtering

### 👤 Authentication

-   Google OAuth login
-   Secure session management
-   Logout functionality

### 🔔 Push Notifications

-   Firebase Cloud Messaging
-   Device token storage
-   Real-time user notifications

### 📊 Visitor Analytics

-   Visitor logging
-   Visitor statistics display

### 📱 Progressive Web App

-   Installable web application
-   Offline support
-   Mobile-friendly interface

### 💬 Feedback System

-   User feedback submission
-   Backend processing

------------------------------------------------------------------------

## 🛠 Tech Stack

### Backend

-   PHP
-   MySQL

### Frontend

-   HTML5
-   CSS3
-   JavaScript

### Integrations

-   Firebase Cloud Messaging
-   Google OAuth

------------------------------------------------------------------------

## 📂 Project Structure

    manojwrites
    │
    ├── htdocs
    │   ├── index.php
    │   ├── search.php
    │   ├── google_login.php
    │   ├── google_callback.php
    │   ├── logout.php
    │   │
    │   ├── hero.js
    │   ├── hero.css
    │   ├── style.css
    │   │
    │   ├── episodes.json
    │   │
    │   ├── notify.php
    │   ├── save_token.php
    │   ├── firebase-messaging-sw.js
    │   │
    │   ├── feedback_submit.php
    │   ├── log_visitor.php
    │   ├── get_visitors.php
    │   │
    │   ├── manifest.json
    │   └── favicon.ico
    │
    └── config
        └── connection.php

------------------------------------------------------------------------

## ⚙️ Installation

### 1️⃣ Clone the repository

``` bash
git clone https://github.com/manoj24092003/manojwrites.git
```

### 2️⃣ Move to Web Server

Place the project inside:

    htdocs

or

    public_html

------------------------------------------------------------------------

### 3️⃣ Configure Database

Edit:

    config/connection.php

Example:

``` php
$con = mysqli_connect("localhost","username","password","database");
```

------------------------------------------------------------------------

### 4️⃣ Configure Google OAuth

Create credentials at:

https://console.cloud.google.com

Update credentials inside:

    google_login.php

------------------------------------------------------------------------

### 5️⃣ Configure Firebase

Create Firebase project:

https://console.firebase.google.com

Update configuration inside:

    firebase-messaging-sw.js
    notify.php

------------------------------------------------------------------------

## 📡 API Endpoints

  Endpoint              Purpose
  --------------------- --------------------------
  search.php            Search stories
  notify.php            Send notifications
  save_token.php        Save user device token
  feedback_submit.php   Submit feedback
  log_visitor.php       Log visitors
  get_visitors.php      Fetch visitor statistics

------------------------------------------------------------------------

## 🔒 Security Improvements (Future)

-   Use prepared SQL statements
-   Implement CSRF protection
-   Sanitize all user inputs
-   Secure API endpoints

------------------------------------------------------------------------

## 🚀 Future Improvements

-   Admin dashboard
-   Episode editor panel
-   Comment system
-   Dark mode
-   REST API
-   Pagination
-   User profiles

------------------------------------------------------------------------

## ⭐ Support

If you like this project, consider giving it a **⭐ star on GitHub**.

------------------------------------------------------------------------

## 👨‍💻 Author

**Manoj**

🌐 Website\
https://manojwrites.xyz

GitHub\
https://github.com/manoj24092003
