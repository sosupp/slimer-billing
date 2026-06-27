# Slimer Billing Package Documentation

**Version:** 1.0.0  
**Package:** sosupp/slimer-billing
**Laravel Version:** ^10.0|^11.0  

---

## 📚 Table of Contents

- [Introduction](#introduction)
- [Installation](#installation)
- [Core Concepts](#core-concepts)
- [Available Traits](#available-traits)
- [Core Usage Examples](#core-usage-examples)
- [Advanced Usage](#advanced-usage)
- [API Reference](#api-reference)
- [Troubleshooting](#troubleshooting)
- [Performance Tips](#performance-tips)
- [Contributing](#contributing)
- [License](#license)

---

## Introduction

The Slimer Billing Package is a comprehensive, polymorphic billing system for Laravel applications. It allows you to bill any entity (students, classes, departments, events, etc.) and accept payments through various methods. 

### Key Features

- ✅ **Polymorphic Relationships** - Bill any model (Student, Class, Department, Event, etc.)
- ✅ **Comprehensive Traits** - Reusable traits for billable, itemable, and payment methods
- ✅ **Full CRUD Operations** - Create, Read, Update, Delete billings with ease
- ✅ **Payment Processing** - Record and track payments with multiple payment methods
- ✅ **PDF Generation** - Generate professional PDF invoices
- ✅ **Status Management** - Track billing status (draft, published, sent, paid, overdue, cancelled, refunded)
- ✅ **Statistics & Reports** - Get detailed billing statistics for any entity
- ✅ **Multi-Currency Support** - Handle multiple currencies with exchange rates
- ✅ **Audit Trail** - Track who created and updated billings
- ✅ **Soft Deletes** - Safely delete and restore billings
- ✅ **Bulk Operations** - Create billings for multiple entities at once
- ✅ **Event System** - Listen to billing events for custom actions

### Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- MySQL 5.7+ / PostgreSQL 9.6+ / SQLite 3.8+

---

## Installation

### Step 1: Install via Composer

```bash
composer require your-vendor/school-fee-billing