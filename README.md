# 🍽️ Savoré Restaurant Management System

[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql)](https://www.mysql.com/)

A comprehensive full-stack restaurant management system built with PHP, MySQL and vanilla JavaScript. Savoré offers a complete solution for managing restaurant operations including online ordering, table reservations, employee management and administrative controls.

---

## 📋 Table of Contents

- [About the Project](#about-the-project)
- [Key Features](#key-features)
- [Technology Stack](#technology-stack)
- [System Architecture](#system-architecture)
- [Installation & Setup](#installation--setup)
- [How to Use](#how-to-use)
- [Project Structure](#project-structure)
- [Database Configuration](#database-configuration)
- [API Documentation](#api-documentation)
- [Security Features](#security-features)
- [Contributing](#contributing)
- [License](#license)
- [Credits](#credits)
- [Contact](#contact)

---

## 🎯 About the Project

**Savoré Restaurant Management System** is a modern, feature-rich web application designed to streamline restaurant operations. The system provides three distinct user interfaces:

- **Customer Portal**: Browse menu, place orders, reserve tables, and leave reviews
- **Employee Dashboard**: Manage orders, handle table reservations, and process bills
- **Admin Panel**: Complete control over menu items, employees, customers, and analytics

The project emphasizes user experience with responsive design, real-time updates, and secure authentication mechanisms including OTP-based email verification and automatic login security notifications that alert users of sign-in activity with device details.

---

## ✨ Key Features

### 👥 Customer Features

- **User Authentication**: Secure login/registration with email OTP verification
- **Login Security Notifications**: Automatic email alerts on each sign-in with device IP, browser details, and timestamp
- **Password Recovery**: Forgot password functionality with email-based token reset
- **Menu Browsing**: Filter and search through categorized menu items
- **Shopping Cart**: Add items to cart with quantity management
- **Online Ordering**: Place orders with email confirmations
- **Table Reservations**: Custom date-time picker for booking tables
- **Reviews & Ratings**: Submit feedback with star ratings
- **Order History**: View and manage past orders
- **Profile Management**: Update account information and change passwords

### 👨‍💼 Employee Features

- **Dashboard Analytics**: Visual charts showing orders, menu distribution and customers
- **Order Management**: View, process and update order status
- **Table Management**: Monitor and manage table reservations
- **Bill Generation**: Create and print invoices for customers
- **Email Notifications**: Automatic order confirmation emails to customers
- **Real-time Updates**: Dynamic order list with status tracking

### 🔧 Admin Features

- **Comprehensive Dashboard**: Statistics on employees, customers, orders and menu items
- **Employee Management**: Add, edit and remove employee accounts
- **Customer Management**: View and manage customer database
- **Menu Management**: CRUD operations for menu items with image uploads
- **Order Overview**: Complete order tracking and status management
- **Reviews Moderation**: View all customer reviews and ratings
- **Analytics & Charts**: Visual data representation using Chart.js

---

## 🛠️ Technology Stack

### Backend

- **PHP 7.4+**: Server-side scripting
- **MySQL 8.0+**: Relational database management
- **PHPMailer**: Email functionality for OTP, security notifications, and order confirmations
- **Composer**: Dependency management

### Frontend

- **HTML5**: Semantic markup
- **CSS3**: Modern styling with custom properties
- **JavaScript (ES6+)**: Dynamic client-side functionality
- **Font Awesome**: Icon library
- **Chart.js**: Data visualization

### Development Tools

- **XAMPP**: Local development environment
- **Git**: Version control
- **VS Code**: Recommended IDE

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Client Browser                       │
│         (HTML/CSS/JavaScript - Responsive UI)           │
└──────────────────────┬──────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────┐
│                    PHP Backend                          │
│  ┌─────────────┬──────────────┬──────────────────┐      │
│  │  Customer   │   Employee   │     Admin        │      │
│  │   Portal    │   Dashboard  │     Panel        │      │
│  └─────────────┴──────────────┴──────────────────┘      │
│              │             │             │              │
│              └─────────────┴─────────────┘              │
│                            ↓                            │
│                   ┌──────────────┐                      │
│                   │   REST APIs  │                      │
│                   └──────────────┘                      │
└────────────────────────┬──────────────────────────────--┘
                         │
                         ↓
               ┌──────────────────┐
               │   MySQL Database │
               │   (savoredb)     │
               └──────────────────┘
```

---

## 📦 Installation & Setup

### Prerequisites

Before you begin, ensure you have the following installed:

- **XAMPP** (Apache + MySQL + PHP) - [Download here](https://www.apachefriends.org/)
- **Composer** - [Download here](https://getcomposer.org/)
- **Git** (optional) - [Download here](https://git-scm.com/)

### Step-by-Step Installation

#### 1. Clone or Download the Repository

```bash
# Option 1: Clone with Git
git clone https://github.com/tuRjoX/savore-restaurant.git

# Option 2: Download ZIP and extract to XAMPP htdocs folder
```

Move the project to your XAMPP htdocs directory:

```
C:\xampp\htdocs\savore-restaurant
```

#### 2. Install PHP Dependencies

Open terminal/command prompt in the project directories and install dependencies:

```bash
# Install customer dependencies
cd c:\xampp\htdocs\savore-restaurant\customer\config
composer install

# Install employee dependencies
cd c:\xampp\htdocs\savore-restaurant\employee
composer install
```

#### 3. Configure Database

1. **Start XAMPP**: Launch XAMPP Control Panel and start Apache and MySQL

2. **Create Database**:

   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create a new database named `savoredb`
   - Set collation to `utf8mb4_general_ci`

3. **Create Database Tables**: Execute the following SQL commands:

```sql
-- Customers Table
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `mobile` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employees Table
CREATE TABLE `employee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `mobile` varchar(15) NOT NULL,
  `designation` varchar(50) NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menu Table
CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders Table
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `items` text NOT NULL,
  `quantities` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `statuss` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tables (Reservations) Table
CREATE TABLE `tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `datee` date NOT NULL,
  `times` time NOT NULL,
  `guests` int(11) NOT NULL,
  `tableno` varchar(10) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews Table
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `stars` int(1) NOT NULL CHECK (`stars` >= 1 AND `stars` <= 5),
  `message` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4. Configure Email Settings (Required for Full Functionality)

Email configuration is essential for:

- **OTP verification** during customer registration
- **Security notifications** on each customer sign-in
- **Order confirmations** for customers
- **Password reset** functionality

Edit email configuration files:

**For Customer Portal**: `customer/config/email_config.php`
**For Employee Portal**: `employee/config/email_service.php`

```php
// Update with your SMTP credentials
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
```

> **Note**: For Gmail, you need to generate an [App Password](https://support.google.com/accounts/answer/185833). Without email configuration, registration, login security notifications and password recovery features will not work.

#### 5. Update Database Configuration

Verify database connection settings in:

- `admin/config/database.php`
- `customer/config/config.php`
- `employee/config/database.php`

Default settings:

```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "savoredb";
```

#### 6. Start the Application

**Option 1**: Using the included batch file (Windows):

```bash
# Double-click runner.bat in the project root
# Or run from command line:
runner.bat
```

**Option 2**: Manual start:

1. Ensure Apache is running in XAMPP
2. Open browser and navigate to:
   - Customer Portal: `http://localhost/savore-restaurant/customer/index.php`
   - Employee Dashboard: `http://localhost/savore-restaurant/employee/index.php`
   - Admin Panel: `http://localhost/savore-restaurant/admin/index.php`

---

## 🚀 How to Use

### For Customers

1. **Registration**:

   - Click "Sign Up" on the homepage
   - Fill in your details (name, email, mobile, password)
   - Verify your email with the OTP sent to your inbox
   - Login with your credentials

2. **Secure Sign-In**:

   - Upon successful login, receive an instant security notification email
   - Email includes: IP address, browser/device info and sign-in timestamp
   - Monitor unauthorized access attempts to your account

3. **Browse & Order**:

   - Navigate to the Menu section
   - Filter by category or search for items
   - Add items to cart with desired quantity
   - Review cart and proceed to checkout
   - Receive order confirmation via email

4. **Reserve a Table**:

   - Click "Reserve Table" (requires login)
   - Select date, time and number of guests
   - Choose table number
   - Submit reservation

5. **Leave a Review**:
   - Scroll to Reviews section
   - Rate with stars (1-5)
   - Write your feedback
   - Submit review

### For Employees

1. **Login**: Access employee dashboard at `/employee/index.php`
2. **Dashboard**: View analytics and recent activities
3. **Manage Orders**:
   - View all orders in Orders List
   - Update order status (Pending → Processing → Completed)
   - Send confirmation emails
4. **Create Orders**: Use Make Orders page for walk-in customers
5. **Table Management**: View and manage table reservations
6. **Generate Bills**: Create printable invoices for orders

### For Administrators

1. **Login**: Access admin panel at `/admin/index.php`
2. **Dashboard**: Monitor overall statistics with visual charts
3. **Manage Menu**:
   - Add new menu items with images
   - Edit existing items
   - Delete items
   - Organize by categories
4. **Employee Management**: Add, edit, or remove employee accounts
5. **Customer Management**: View customer database
6. **Review Orders**: Complete order history and status
7. **View Reviews**: Monitor customer feedback

---

## 📁 Project Structure

```
savore-restaurant/
│
├── admin/                     # Admin panel
│   ├── index.php              # Admin dashboard
│   ├── config/
│   │   └── database.php       # Database connection
│   ├── html/                  # Admin pages
│   │   ├── customer.php       # Customer management
│   │   ├── employee.php       # Employee management
│   │   ├── menu.php           # Menu management
│   │   ├── orders-list.php    # Orders overview
│   │   └── reviews.php        # Reviews management
│   ├── scripts/               # JavaScript files
│   └── styles/                # CSS files
│
├── customer/                  # Customer portal
│   ├── index.php              # Main customer page
│   ├── api/                   # RESTful API endpoints
│   │   ├── get_menu.php
│   │   ├── submit_review.php
│   │   ├── reserve_table.php
│   │   └── ...
│   ├── auth/                  # Authentication
│   │   ├── login.php
│   │   ├── send_otp.php
│   │   ├── verify_otp.php
│   │   ├── forgot_password.php
│   │   └── ...
│   ├── assets/
│   │   ├── style.css
│   │   ├── scripts/
│   │   │   └── main.js        # Main JavaScript
│   │   └── image/
│   │       └── menu/          # Menu item images
│   ├── config/
│   │   ├── config.php         # Database config
│   │   ├── email_config.php   # Email settings
│   │   └── composer.json
│   └── vendor/                # Composer dependencies
│
├── employee/                  # Employee dashboard
│   ├── index.php              # Employee dashboard
│   ├── api/
│   │   ├── get_order_details.php
│   │   └── get_tables.php
│   ├── config/
│   │   ├── database.php
│   │   ├── email_service.php
│   │   └── tables.json
│   ├── html/                  # Employee pages
│   │   ├── make-orders.php
│   │   ├── orders-list.php
│   │   ├── tables.php
│   │   └── bill.php
│   ├── css/                   # Stylesheets
│   ├── scripts/               # JavaScript files
│   └── vendor/                # Composer dependencies
│
├── LICENSE                    # Apache 2.0 License
├── README.md                  # This file
└── runner.bat                 # Quick start script (Windows)
```

---

## 🗄️ Database Configuration

### Database Name: `savoredb`

### Tables:

1. **customers** - Customer accounts and profiles
2. **employee** - Employee information and credentials
3. **menu** - Restaurant menu items with details
4. **orders** - Customer orders and status
5. **tables** - Table reservation records
6. **reviews** - Customer reviews and ratings

### Relationships:

- Orders are linked to customers via email
- Table reservations are linked to customers via email
- Reviews can be submitted by any user (registered or guest)

---

## 📡 API Documentation

### Customer API Endpoints

| Endpoint                        | Method | Description              |
| ------------------------------- | ------ | ------------------------ |
| `/api/get_menu.php`             | GET    | Fetch all menu items     |
| `/api/get_menu_types.php`       | GET    | Get menu categories      |
| `/api/submit_review.php`        | POST   | Submit a review          |
| `/api/reserve_table.php`        | POST   | Reserve a table          |
| `/api/get_reservations.php`     | GET    | Get user reservations    |
| `/api/get_user_orders.php`      | GET    | Fetch user order history |
| `/api/cancel_order.php`         | POST   | Cancel an order          |
| `/api/send_contact_message.php` | POST   | Send contact message     |

### Authentication Endpoints

| Endpoint                           | Method | Description                                   |
| ---------------------------------- | ------ | --------------------------------------------- |
| `/auth/login.php`                  | POST   | User login (with security notification email) |
| `/auth/send_otp.php`               | POST   | Send OTP for registration                     |
| `/auth/verify_otp.php`             | POST   | Verify OTP code                               |
| `/auth/forgot_password.php`        | POST   | Request password reset                        |
| `/auth/verify_reset_token.php`     | GET    | Verify reset token                            |
| `/auth/process_password_reset.php` | POST   | Reset password                                |

---

## 🔒 Security Features

Savoré Restaurant Management System implements multiple security layers:

### Login Security Notifications

Every time a customer signs in, an automated security notification is sent to their registered email address containing:

- **IP Address**: The device's IP address used for login
- **Timestamp**: Exact date and time of the sign-in
- **Browser & Device Info**: Detailed information about the browser and operating system
- **Full User Agent String**: Complete technical details for verification

**Supported Detection**:

- Browser: Chrome, Firefox, Safari, Edge, Opera
- Operating Systems: Windows 10/8/7, macOS, iOS, Android, Linux
- IP Address extraction from various proxy headers

This feature helps users:

- Monitor account activity in real-time
- Detect unauthorized access attempts
- Verify legitimate sign-ins from multiple devices
- Take immediate action if suspicious activity is detected

**Example Email Content**:

```
Hi [Customer Name],

We noticed a sign-in to your Savoré account. If this was you,
there's nothing to do. If you don't recognize this activity,
please secure your account immediately.

IP address:       192.168.1.100
Time:             2025-10-12 14:30:45
Device / Browser: Chrome on Windows 10

If you need help, contact our support at savore.2006@gmail.com
```

### Additional Security Measures

- Password hashing with PHP's `password_hash()` function
- Session management with secure cookies
- Email verification via OTP before account activation
- SQL injection protection using prepared statements
- XSS protection with input sanitization
- HTTPS ready for production deployment

---

## 🤝 Contributing

Contributions are welcome! If you'd like to improve Savoré Restaurant Management System:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Guidelines:

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic
- Test thoroughly before submitting PR
- Update documentation as needed

---

## 📄 License

This project is licensed under the **Apache License 2.0** - see the [LICENSE](LICENSE) file for details.

```
Copyright 2025 Savoré Restaurant Management System

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
```

---

## 🙏 Credits

### Developed By

- **tuRjoX** - [GitHub Profile](https://github.com/tuRjoX)
- **ivyfaraezi** - [GitHub Profile](https://github.com/ivyfaraezi)

---

## 📞 Contact

For questions, suggestions, or support:

- **GitHub Issues**: [Report a bug or request a feature](https://github.com/tuRjoX/savore-restaurant/issues)
- **GitHub Repository**: [https://github.com/tuRjoX/savore-restaurant](https://github.com/tuRjoX/savore-restaurant)

---

## 🎓 Learning Resources

If you're new to the technologies used in this project:

- **PHP**: [Official Documentation](https://www.php.net/docs.php)
- **MySQL**: [MySQL Tutorial](https://dev.mysql.com/doc/)
- **JavaScript**: [MDN Web Docs](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
- **XAMPP**: [Getting Started Guide](https://www.apachefriends.org/documentation.html)
- **Composer**: [Composer Documentation](https://getcomposer.org/doc/)

---

## 🚧 Future Enhancements

Planned features for future releases:

- [ ] Payment gateway integration (Stripe, PayPal)
- [ ] Real-time order tracking
- [ ] Mobile app (iOS & Android)
- [ ] Multi-language support
- [ ] Advanced analytics dashboard
- [ ] Loyalty program integration
- [ ] SMS notifications for orders and reservations
- [ ] Two-factor authentication (2FA)
- [ ] Geolocation-based security alerts
- [ ] Dark mode theme
- [ ] Delivery tracking system
- [ ] Customer feedback analysis with AI
- [ ] Integration with third-party delivery services
- [ ] Session management dashboard for users
- [ ] Login history and device management

---

## 📊 Project Stats

- **Total Lines of Code**: ~15,000+
- **Languages**: PHP, JavaScript, HTML, CSS
- **Database Tables**: 6
- **API Endpoints**: 20+
- **User Roles**: 3 (Customer, Employee, Admin)

---

<div align="center">

### ⭐ If you find this project useful, please consider giving it a star!

</div>
