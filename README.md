# 🏪 POS System - Badar Mart

A comprehensive and feature-rich Point of Sale (POS) system built with PHP, designed for retail businesses with support for both packaged and weight-based products.

![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.0-purple.svg)
![Security](https://img.shields.io/badge/Security-Hardened-green.svg)
![Live Demo](https://img.shields.io/badge/Demo-Online-brightgreen.svg)

## 🌐 Live Demo

**Experience the system live:**
- 🔗 [**Live POS System**](https://mystorebybadar.infinityfreeapp.com/pos_system/index.php)

**Demo Credentials:**
- **Username:** `admin` / **Password:** `password` (Check system for current credentials)
- **Role:** Administrator with full access

## 🎯 Features

- **👥 Multi-Role User Management**
  - Admin: Full system access and user management
  - Manager: Inventory, reports, and sales management
  - Cashier: Point of Sale and return processing

- **📦 Dual Product System**
  - Packaged Products: Fixed quantity items with barcodes
  - Open Products: Weight-based items (kg/grams) with dynamic pricing
  - Barcode generation and sticker printing
  - Inventory tracking with reorder points

- **💸 Advanced POS System**
  - Real-time shopping cart with tax calculation
  - Multiple payment methods (Cash, Card)
  - Discount system (amount or percentage)
  - Barcode scanner integration
  - Customer management

- **📊 Comprehensive Reporting**
  - Sales reports by date range
  - Cashier performance tracking
  - Transaction history
  - Inventory status and alerts

- **🔄 Return & Exchange Management**
  - Full or partial returns
  - Exchange with new products
  - Refund processing
  - Inventory reconciliation

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

### Installation

1. **Download and Extract**
   - Download the POS system files
   - Extract to your web server directory (e.g., `htdocs/pos_system`)

2. **Database Setup**
   ```sql
   CREATE DATABASE pos_system;
   USE pos_system;
   ```
   - Import the provided SQL schema file
   - Update database credentials in `includes/config.php`

3. **File Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/products/
   chmod 755 uploads/barcodes/
   chmod 755 logs/
   ```

4. **Configuration**
   - Edit `includes/config.php` with your database details
   - Set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - Configure `CSRF_TOKEN_SECRET` for security

5. **Access the System**
   - Navigate to `http://mystorebybadar.infinityfreeapp..com/pos_system`
   - **Or use our live demo:** [Badar Mart POS System](https://mystorebybadar.infinityfreeapp.com/pos_system/index.php)
   - Default admin login: Use credentials from database setup

## 👥 User Roles & Permissions

**Admin**
- Full system access
- User management
- System configuration
- All reports and analytics

**Manager**
- Inventory management
- Sales reports
- Product management
- Return processing

**Cashier**
- Point of Sale operations
- Customer transactions
- Product search
- Basic return processing

## 📦 Product Management

**Packaged Products**
- Fixed quantity items
- Barcode generation
- Stock level tracking
- Reorder point alerts

**Open Products (Weight-Based)**
- Weight in kg/grams
- Price per kg calculation
- Dynamic pricing at POS
- No barcode requirement

## 🎯 POS Operations

**Sales Process**
1. Search products by name, ID, or barcode
2. Add to cart (quantity for packaged, weight for open products)
3. Apply discounts and tax
4. Select payment method
5. Process sale and print receipt

**Barcode Integration**
- Automatic barcode generation
- Barcode scanner support
- Manual barcode entry
- Sticker printing for products

**Payment Methods**
- Cash (with change calculation)
- Card payments
- Receipt generation
- Transaction history

## 📊 Reporting & Analytics

**Sales Reports**
- Date range filtering
- Cashier performance
- Transaction details
- Sales summaries

**Inventory Reports**
- Stock levels
- Low stock alerts
- Product movement
- Category analysis

## 🔧 Technical Features

**Security**
- CSRF protection
- SQL injection prevention
- XSS protection
- Session security
- Role-based access control

**Performance**
- Optimized database queries
- Efficient image handling
- Responsive design
- Fast barcode generation

**Compatibility**
- Responsive design for all devices
- Cross-browser compatibility
- Mobile-friendly interface
- Printer-friendly receipts

## 🗂️ Project Structure

```bash
pos_system/
├── index.php                 # Login page
├── dashboard.php            # Main dashboard
├── pos.php                  # Point of Sale
├── inventory.php            # Inventory management
├── users.php                # User management
├── reports.php              # Sales reports
├── search.php               # Product search
├── return_exchange.php      # Return processing
├── receipt.php              # Receipt generation
├── sticker.php              # Barcode sticker printing
├── logout.php               # Session logout
├── update_admin_password.php # Password management
├── update_product.php       # Product updates
├── .htaccess               # Security configuration
├── includes/
│   ├── config.php          # Database configuration
│   ├── auth.php            # Authentication
│   ├── barcode.php         # Barcode generation
│   ├── sales.php           # Sales processing
│   ├── sidebar.php         # Navigation
│   └── footer.php          # Footer
├── css/
│   └── style.css           # Main stylesheet
├── js/
│   ├── pos.js              # POS functionality
│   ├── script.js           # General scripts
│   └── barcode.js          # Barcode scanning
└── uploads/
    ├── products/           # Product images
    └── barcodes/          # Generated barcodes
```

## 🛠️ Technology Stack

**Backend**
- PHP 7.4+ - Server-side scripting
- MySQL 5.7+ - Database management
- PDO - Database abstraction layer

**Frontend**
- HTML5 - Markup structure
- CSS3 - Styling and responsive design
- JavaScript - Client-side functionality
- Font Awesome - Icons

**Security**
- Prepared statements - SQL injection prevention
- CSRF tokens - Form submission protection
- Input sanitization - XSS prevention
- Session management - User authentication

## 🌐 Live Deployment

**Current Hosting:**
- **URL:** [Badar Mart POS System](https://mystorebybadar.infinityfreeapp.com/pos_system/index.php)
- **Provider:** InfinityFree
- **Status:** ✅ Active and Running

**Demo Access:**
- Test all features with demo account
- Explore different user roles
- Experience real-time POS operations
- View sample reports and analytics

## 🐛 Known Issues & Solutions

**Barcode Generation**
- Requires GD library enabled in PHP
- Check `php.ini` for `extension=gd`

**File Uploads**
- Ensure `uploads/` directory has write permissions
- Check PHP `upload_max_filesize` and `post_max_size`

**Session Issues**
- Verify session directory permissions
- Check browser cookie settings

**Printing**
- Configure browser print settings for receipts
- Use thermal printer for 80mm receipts

## 🤝 Support & Maintenance

**Regular Maintenance**
- Backup database regularly
- Monitor log files in `logs/` directory
- Update product images and barcodes as needed

**Troubleshooting**
- Check error logs in `logs/error.log`
- Verify file permissions
- Test database connectivity

## 📝 License

This POS system is proprietary software developed for Badar Mart. Unauthorized distribution or modification is prohibited.

## 👨‍💻 Developer Information

**System Requirements**
- PHP 7.4 or higher with GD library
- MySQL 5.7 or higher
- Web server with mod_rewrite enabled
- Modern web browser with JavaScript

**Customization**
- Modify `css/style.css` for branding
- Update store details in `receipt.php`
- Configure tax rates in POS settings
- Adjust barcode sizes in sticker printing

---

**🚀 Live Demo Available:** [Badar Mart](https://mystorebybadar.infinityfreeapp.com/pos_system/index.php)

**Built with ❤️ for Badar Mart**

For support and inquiries, contact the system administrator.

*Experience modern retail management! 🏪✨*
