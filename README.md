# Card Analytics System (Group 25)

## Project Overview
It is a WordPress-based application designed to manage trading card collections, estimate values, view market trends, and set price alerts.

## Requirements
- WordPress 6.0 or higher
- PHP 7.4 or higher
- **Plugins Required:**
  - Advanced Custom Fields (ACF)
  - Astra Theme (Recommended)
  - Custom Post Type UI (CPT UI)
  - Visualizer: Tables and Charts for WordPress

## Installation Guide

### 1. Setup WordPress
Ensure you have a standard WordPress installation running on your localhost (via XAMPP). You can look at: https://youtu.be/usoJ6ckzUz8?si=Jy-Xi3zWP-vW3P6r

### 2. Install the Plugin
1. Download `card-analytics.php` from this repository.
2. Navigate to your WordPress `wp-content/plugins/` directory.
3. Create a folder named `card-analytics`.
4. Place `card-analytics.php` inside that folder.
5. Go to WordPress Dashboard > Plugins > Activate **Card Analytics**.

### 3. Import Data (Crucial)
To see the demo data (Pikachu, Charizard, etc.):
1. Go to Tools > Import > WordPress.
2. Install the importer if prompted.
3. Upload the `sample-data.xml` file provided in this repository.
4. Assign the posts to an existing user.

### 4. Setup Pages
Create the following pages and insert the corresponding shortcodes:

- **My Card Collection:** `[interactive_dashboard]`
- **Price Alerts:** `[price_alert_system]`
- **Market Trends:** `[market_overview]·

## Features Implemented
- **Manage Collection:** View cards with real-time Profit/Loss calculation.
- **Interactive Dashboard:** Dynamic filtering by rarity and total ROI calculation.
- **Market Trends:** Visual line charts using Chart.js for price history.
- **Price Alerts:** Set target prices and receive visual alerts when market price drops.
- **Login Protection:** Auto-redirect to collection page upon login.

## Credits
Software Engineering Assignment 2 - Group 25
