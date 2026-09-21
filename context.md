you are assigned to develop a production-grade E-Commerce Web Application with a Dedicated Admin Dashboard using core **PHP** and MySQL.

Note: You are free to download and integrate modern, free **HTML**/**CSS** templates for both the storefront and the admin panel. Your primary focus will be backend architecture, clean business logic, robust database design, and end-to-end functionality. I will also share the templates with you on Microsoft Teams.

Project Deliverables & Key Features ## Storefront (Customer Facing)

Catalog & Navigation: Category-based product filtering and dynamic product listings.

Product Details: Single product view showing specifications, stock availability, and dynamic pricing.

Cart & Checkout: Persistent cart management and structured checkout pipeline.

Payment Gateways:

Cash on Delivery (**COD**)

Stripe test-mode integration

Customer Authentication: Secure sign-up, login, and session persistence.

## Admin Dashboard (Protected Access)

Role-Based Access Control: Pre-seeded admin account; all storefront signups default to customers.

Catalog Management (**CRUD**):

Full **CRUD** for Categories (with image upload/handling).

Full **CRUD** for Products (Category mapping, price, stock, description, image upload/handling).

User Management: Monitor registered users and toggle active/inactive status.

Order Tracking: Review incoming orders, update fulfillment statuses (Processing, Shipped, Delivered), and track payment records.

Sales Analytics & Reports: Dynamic reporting for Weekly, Monthly, and Yearly sales summaries.

## Engineering & Security Standards (Mandatory)

Database Driver: Use MySQLi (Object-Oriented approach). All dynamic queries must strictly use prepared statements ($mysqli->prepare(), bind_param(), and execute()) to prevent **SQL** injection.

Authentication: Passwords must be hashed using password_hash() and verified with password_verify().

Session Security: Implement session_regenerate_id(true) upon successful authentication and restrict admin pages using session role validation.

File Uploads: Server-side **MIME**-type checks, file size constraints, and sanitized unique naming conventions.

Architecture: Maintain a clean, modular folder separation between storefront views, admin controls, core utilities, and database configurations.

Timeline & Submission Submission Deadline: 17 Sep **2026**

Deliverables: A private GitHub repository link containing your complete source code, a clean schema.sql database dump with seeds, and a **README**.md with setup instructions.

### Recommended Folder Structure

Production mindset build karne ke liye admin aur storefront ka logic modular aur isolated hona chahiye:
Plaintext
 
 
ecommerce-project/
│
├── config/
│   ├── database.php          
│   └── stripe.php            # Stripe server configuration
│
├── core/
│   ├── Auth.php              # Login, registration, role checks
│   ├── Database.php          # Reusable query runner
│   ├── Session.php           # Session wrapper & flash messages
│   └── Validator.php         # Server-side validation rules
│
├── public/
│   ├── assets/               # **CSS**, JS, fonts, vendor files
│   ├── uploads/
│   │   ├── products/         # Uploaded product images
│   │   └── categories/       # Uploaded category banners/icons
│   ├── index.php             # Store homepage
│   ├── products.php          # Listing with filters
│   ├── product-detail.php    # Detail view
│   ├── cart.php              # Cart actions
│   ├── checkout.php          # Order checkout
│   ├── order-confirmation.php
│   ├── login.php
│   ├── register.php
│   └── logout.php
│
├── admin/
│   ├── assets/               # Admin template **CSS**/JS
│   ├── index.php             # Dashboard with sales metrics/charts
│   ├── categories/
│   │   ├── index.php         # List categories
│   │   ├── create.php
│   │   └── edit.php
│   ├── products/
│   │   ├── index.php         # List products with pagination
│   │   ├── create.php
│   │   └── edit.php
│   ├── users/
│   │   └── index.php         # Customer listing & toggle status
│   ├── orders/
│   │   ├── index.php         # Order history
│   │   └── detail.php        # Change status (Pending -> Delivered)
│   ├── reports.php           # Weekly, monthly, yearly exports/views
│   └── login.php             # Admin authentication entry
│
├── includes/
│   ├── header.php            # Storefront navbar & category bar
│   ├── footer.php
│   ├── admin-header.php      # Admin sidebar & top nav
│   └── admin-footer.php
│
└── .htaccess                 # Security: protect uploads & config
 
Database Schema (MySQL)
Foreign keys, constraints, aur data integrity enforce karne ke liye yeh 6 core tables required hain:
1. users
Tracks both customers and dashboard administrators.
**SQL**
 
 
**CREATE** **TABLE** users (
    id **INT** **UNSIGNED** AUTO_INCREMENT **PRIMARY** **KEY**,
    name **VARCHAR**(**100**) **NOT** **NULL**,
    email **VARCHAR**(**150**) **NOT** **NULL** **UNIQUE**,
    password **VARCHAR**(**255**) **NOT** **NULL**,
    role **ENUM**('admin', 'customer') **DEFAULT** 'customer',
    is_active **TINYINT**(1) **DEFAULT** 1,
    created_at **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP,
    updated_at **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP ON **UPDATE** CURRENT_TIMESTAMP) **ENGINE**=InnoDB;
 
2. categories
Handles navigation grouping and hierarchy.
**SQL**
 
 
**CREATE** **TABLE** categories (
    id **INT** **UNSIGNED** AUTO_INCREMENT **PRIMARY** **KEY**,
    name **VARCHAR**(**100**) **NOT** **NULL**,
    slug **VARCHAR**(**120**) **NOT** **NULL** **UNIQUE**,
    image **VARCHAR**(**255**) **NULL**,
    status **TINYINT**(1) **DEFAULT** 1,
    created_at **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP) **ENGINE**=InnoDB;
 
3. products
Product catalog with stock tracking.
**SQL**
 
 
**CREATE** **TABLE** products (
    id **INT** **UNSIGNED** AUTO_INCREMENT **PRIMARY** **KEY**,
    category_id **INT** **UNSIGNED** **NOT** **NULL**,
    name **VARCHAR**(**200**) **NOT** **NULL**,
    slug **VARCHAR**(**220**) **NOT** **NULL** **UNIQUE**,
    description **TEXT**,
    price **DECIMAL**(10, 2) **NOT** **NULL**,
    stock **INT** **UNSIGNED** **DEFAULT** 0,
    image **VARCHAR**(**255**) **NOT** **NULL**,
    status **TINYINT**(1) **DEFAULT** 1,
    created_at **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP,
    updated_at **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP ON **UPDATE** CURRENT_TIMESTAMP,
    **FOREIGN** **KEY** (category_id) **REFERENCES** categories(id) ON **DELETE** **CASCADE**
) **ENGINE**=InnoDB;
 
4. orders
Master order record with payment state.
**SQL**
 
 
**CREATE** **TABLE** orders (
    id **INT** **UNSIGNED** AUTO_INCREMENT **PRIMARY** **KEY**,
    user_id **INT** **UNSIGNED** **NOT** **NULL**,
    order_number **VARCHAR**(50) **NOT** **NULL** **UNIQUE**,
    total_amount **DECIMAL**(10, 2) **NOT** **NULL**,
    payment_method **ENUM**('cod', 'paypal', 'stripe') **NOT** **NULL**,
    payment_status **ENUM**('pending', 'completed', 'failed') **DEFAULT** 'pending',
    order_status **ENUM**('processing', 'shipped', 'delivered', 'cancelled') **DEFAULT** 'processing',
    transaction_id **VARCHAR**(**100**) **NULL**, -- Stores the verified payment provider transaction ID    shipping_address **TEXT** **NOT** **NULL**,
    created_at **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP,
    **FOREIGN** **KEY** (user_id) **REFERENCES** users(id) ON **DELETE** **CASCADE**
) **ENGINE**=InnoDB;
 
5. order_items
Individual line items to preserve historical purchase price.
**SQL**
 
 
**CREATE** **TABLE** order_items (
    id **INT** **UNSIGNED** AUTO_INCREMENT **PRIMARY** **KEY**,
    order_id **INT** **UNSIGNED** **NOT** **NULL**,
    product_id **INT** **UNSIGNED** **NOT** **NULL**,
    quantity **INT** **UNSIGNED** **NOT** **NULL**,
    unit_price **DECIMAL**(10, 2) **NOT** **NULL**,
    subtotal **DECIMAL**(10, 2) **NOT** **NULL**,
    **FOREIGN** **KEY** (order_id) **REFERENCES** orders(id) ON **DELETE** **CASCADE**,
    **FOREIGN** **KEY** (product_id) **REFERENCES** products(id) ON **DELETE** **RESTRICT**
) **ENGINE**=InnoDB;
 
Core Implementation Checklist for Trainees
Security & Auth:
password_hash($password, PASSWORD_BCRYPT) and password_verify() usage.
Role checks ($_SESSION['user_role'] === 'admin') on every admin page with automated redirection if unauthorized.
session_regenerate_id(true) upon successful authentication to guard against session fixation.
Payment Flow:
**COD**: Insert directly into orders with payment_status = 'pending'.
Stripe: Create a hosted Checkout Session in test mode, verify the paid session server-side, and insert the verified PaymentIntent ID into the order.
Reports Logic:
Weekly: **SELECT** **DATE**(created_at) as day, **SUM**(total_amount) as sales **FROM** orders **WHERE** created_at >= **NOW**() - **INTERVAL** 7 **DAY** **GROUP** BY day
Monthly: **SELECT** **MONTHNAME**(created_at) as month, **SUM**(total_amount) as sales **FROM** orders **WHERE** **YEAR**(created_at) = **YEAR**(**CURDATE**()) **GROUP** BY **MONTH**(created_at)
Yearly: **SELECT** **YEAR**(created_at) as year, **SUM**(total_amount) as sales **FROM** orders **GROUP** BY year
File Uploads:
Validate **MIME** types via mime_content_type(), enforce a file size limit (e.g., **2MB** max), and generate unique filenames using bin2hex(random_bytes(10)) to prevent directory traversal or file replacement.

Database creation query :

**DROP** **DATABASE** IF **EXISTS** `ecommerce_db`; **CREATE** **DATABASE** `ecommerce_db` **CHARACTER** **SET** utf8mb4 **COLLATE** utf8mb4_unicode_ci; **USE** `ecommerce_db`;

**CREATE** **TABLE** `users` (
    `id` **INT** **UNSIGNED** AUTO_INCREMENT **PRIMARY** **KEY**,
    `name` **VARCHAR**(**100**) **NOT** **NULL**,
    `email` **VARCHAR**(**150**) **NOT** **NULL** **UNIQUE**,
    `password` **VARCHAR**(**255**) **NOT** **NULL**,
    `role` **ENUM**('admin', 'customer') **DEFAULT** 'customer',
    `is_active` **TINYINT**(1) **DEFAULT** 1,
    `created_at` **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP,
    `updated_at` **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP ON **UPDATE** CURRENT_TIMESTAMP
) **ENGINE**=InnoDB;

**CREATE** **TABLE** `categories` (
    `id` **INT** **UNSIGNED** AUTO_INCREMENT **PRIMARY** **KEY**,
    `name` **VARCHAR**(**100**) **NOT** **NULL**,
    `slug` **VARCHAR**(**120**) **NOT** **NULL** **UNIQUE**,
    `image` **VARCHAR**(**255**) **NULL**,
    `status` **TINYINT**(1) **DEFAULT** 1,
    `created_at` **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP
) **ENGINE**=InnoDB;

**CREATE** **TABLE** `products` (
    `id` **INT** **UNSIGNED** AUTO_INCREMENT **PRIMARY** **KEY**,
    `category_id` **INT** **UNSIGNED** **NOT** **NULL**,
    `name` **VARCHAR**(**200**) **NOT** **NULL**,
    `slug` **VARCHAR**(**220**) **NOT** **NULL** **UNIQUE**,
    `description` **TEXT**,
    `price` **DECIMAL**(10, 2) **NOT** **NULL**,
    `stock` **INT** **UNSIGNED** **DEFAULT** 0,
    `image` **VARCHAR**(**255**) **NOT** **NULL**,
    `status` **TINYINT**(1) **DEFAULT** 1,
    `created_at` **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP,
    `updated_at` **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP ON **UPDATE** CURRENT_TIMESTAMP,
    **CONSTRAINT** `fk_products_category` **FOREIGN** **KEY** (`category_id`) 
    **REFERENCES** `categories` (`id`) ON **DELETE** **CASCADE**
) **ENGINE**=InnoDB;

**CREATE** **TABLE** `orders` (
    `id` **INT** **UNSIGNED** AUTO_INCREMENT **PRIMARY** **KEY**,
    `user_id` **INT** **UNSIGNED** **NOT** **NULL**,
    `order_number` **VARCHAR**(50) **NOT** **NULL** **UNIQUE**,
    `total_amount` **DECIMAL**(10, 2) **NOT** **NULL**,
    `payment_method` **ENUM**('cod', 'paypal', 'stripe') **NOT** **NULL**,
    `payment_status` **ENUM**('pending', 'completed', 'failed') **DEFAULT** 'pending',
    `order_status` **ENUM**('processing', 'shipped', 'delivered', 'cancelled') **DEFAULT** 'processing',
    `transaction_id` **VARCHAR**(**100**) **NULL**,
    `shipping_address` **TEXT** **NOT** **NULL**,
    `created_at` **TIMESTAMP** **DEFAULT** CURRENT_TIMESTAMP,
    **CONSTRAINT** `fk_orders_user` **FOREIGN** **KEY** (`user_id`) 
    **REFERENCES** `users` (`id`) ON **DELETE** **CASCADE**
) **ENGINE**=InnoDB;

**CREATE** **TABLE** `order_items` (
    `id` **INT** **UNSIGNED** AUTO_INCREMENT **PRIMARY** **KEY**,
    `order_id` **INT** **UNSIGNED** **NOT** **NULL**,
    `product_id` **INT** **UNSIGNED** **NOT** **NULL**,
    `quantity` **INT** **UNSIGNED** **NOT** **NULL**,
    `unit_price` **DECIMAL**(10, 2) **NOT** **NULL**,
    `subtotal` **DECIMAL**(10, 2) **NOT** **NULL**,
    **CONSTRAINT** `fk_order_items_order` **FOREIGN** **KEY** (`order_id`) 
    **REFERENCES** `orders` (`id`) ON **DELETE** **CASCADE**,
    **CONSTRAINT** `fk_order_items_product` **FOREIGN** **KEY** (`product_id`) 
    **REFERENCES** `products` (`id`) ON **DELETE** **RESTRICT**
) **ENGINE**=InnoDB;

**INSERT** **INTO** `users` (`name`, `email`, `password`, `role`, `is_active`) **VALUES** ('System Administrator', '[admin@ecommerce.local](mailto:admin@ecommerce.local)', '$2y$10$w0/5oWbE7qf97k4X2sL.EOPj.xO6Z4g9R9E5bA3KsmTfK9xTqPqSm', 'admin', 1);

**INSERT** **INTO** `categories` (`name`, `slug`, `image`, `status`) **VALUES** ('Computers & Laptops', 'computers-laptops', '1.png', 1), ('Digital Cameras', 'digital-cameras', '2.png', 1), ('Smart Phones', 'smart-phones', '3.png', 1), ('Televisions', 'televisions', '4.png', 1), ('Audio', 'audio', '5.png', 1);

**INSERT** **INTO** `products` (`category_id`, `name`, `slug`, `description`, `price`, `stock`, `image`, `status`) **VALUES** (1, 'MacBook Pro 13" Display, i5', 'macbook-pro-13-i5', 'Apple MacBook Pro with Retina Display, **8GB** **RAM**, **256GB** **SSD**.', **1199**.99, 15, 'product-1.jpg', 1), (5, 'Bose SoundLink Bluetooth Speaker', 'bose-soundlink-speaker', 'High performance wireless audio speaker with water-resistant body.', 79.99, 25, 'product-2.jpg', 1), (3, 'Apple 11" iPad Pro Wi-Fi **256GB**', 'apple-ipad-pro-11', 'Liquid Retina display with ProMotion and **A12X** Bionic chip.', **899**.99, 10, 'product-3.jpg', 1), (3, 'Google Pixel 3 XL **128GB**', 'google-pixel-3-xl', 'Crisp **OLED** display, incredible low-light camera, long battery life.', 41.67, 30, 'product-4.jpg', 1);