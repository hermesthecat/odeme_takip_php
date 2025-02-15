# Technical Context: Personal Finance Management System

## Technology Stack

### Backend Technologies

- **PHP 7.4+**
  - Unified configuration system
  - Centralized includes structure
  - PDO database integration
  - Security implementation
  - API endpoints
  - Helper functions

- **MySQL 5.7+**
  - Complete schema implementation
  - Views for reporting
  - Triggers for automation
  - Foreign key relationships
  - Transaction support
  - JSON column support

### Frontend Technologies

- **JavaScript (ES6+)**
  - Async/await operations
  - Form handling
  - Data validation
  - API integration
  - Error management
  - Real-time updates

- **HTML5/CSS3**
  - Semantic markup
  - Form validation
  - Responsive design
  - Custom styling
  - Interactive elements

### Supporting Technologies

- **Chart.js**
  - Financial visualizations
  - Data representation
  - Interactive graphs
  - Real-time updates

- **Frontend Libraries**
  - SweetAlert for notifications
  - FontAwesome for icons
  - Date handling utilities
  - Currency formatting

## File Structure

### Core Files

```php
/includes/
  ├── config.php      // Central configuration
  ├── db.php         // Database connection
  ├── functions.php  // Helper functions
  ├── security.php   // Security functions
  └── mail.php       // Email functionality

/api/
  ├── auth.php       // Authentication endpoints
  ├── bills.php     // Bill management
  ├── expense.php   // Expense handling
  ├── income.php    // Income management
  └── reports.php   // Report generation
```

### Configuration System

```php
// Unified Configuration (includes/config.php)
// Application Settings
define('APP_NAME', 'Kişisel Finans Yönetimi');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://butce.local');

// Database Settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'odeme_takip');
define('DB_USER', 'user');
define('DB_PASS', 'password');

// Security Settings
define('ALLOWED_ORIGIN', 'https://butce.local');
define('API_RATE_LIMIT', 100);
define('API_CACHE_TIME', 300);

// Feature Settings
define('SUPPORTED_CURRENCIES', [...]);
define('REPORT_TYPES', [...]);
define('DATE_RANGES', [...]);
```

## Development Setup

### Server Requirements

```php
// Core Requirements
PHP >= 7.4
MySQL >= 5.7
Apache/Nginx with mod_rewrite

// PHP Extensions
mysqli
json
curl
mbstring
xml
```

### API Implementation

```php
// Standard API Structure
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Request handling
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':    // Read
    case 'POST':   // Create
    case 'PUT':    // Update
    case 'DELETE': // Delete
}
```

## Technical Constraints

### Security Requirements

1. Authentication
   - Session-based auth
   - CSRF protection
   - Input validation
   - XSS prevention

2. Data Protection
   - Prepared statements
   - Transaction safety
   - Error handling
   - Activity logging

3. Configuration
   - Centralized settings
   - Environment separation
   - Secure credentials
   - Feature flags

### Performance Requirements

1. Response Times
   - Database: < 100ms
   - API: < 200ms
   - Frontend: < 2s
   - Total load: < 3s

2. Data Limits
   - Query results: 1000 rows
   - API payload: 1MB
   - File uploads: 5MB
   - Cache size: 100MB

## Integration Features

### API Structure

```php
/api/
  ├── Authentication
  │   ├── Login
  │   ├── Register
  │   └── Password Reset
  │
  ├── Financial Management
  │   ├── Income
  │   ├── Expenses
  │   └── Bills
  │
  ├── Analysis
  │   ├── Reports
  │   └── Statistics
  │
  └── System
      ├── Configuration
      └── User Settings
```

### Data Flow

1. Request Flow
   ```
   Client -> API -> Config -> Database -> Response
   ```

2. Data Processing
   ```
   Input -> Validation -> Processing -> Response
   ```

3. Error Handling
   ```
   Try -> Catch -> Log -> Response
   ```

## Development Tools

### Version Control

- Git for source control
- Feature branch workflow
- Pull request reviews
- Version tagging

### Code Quality

- PHP_CodeSniffer
- ESLint for JavaScript
- MySQL optimizer
- Security scanning

### Testing

- PHPUnit for PHP
- Jest for JavaScript
- API testing tools
- Load testing suite

## Deployment

### Environment Setup

1. Development
   - Debug enabled
   - Error display
   - No caching
   - Sample data

2. Production
   - Error logging
   - Caching enabled
   - Optimized settings
   - Real data

### Deployment Steps

1. Version Control
   - Feature branches
   - Code review
   - Testing phase
   - Version tagging

2. Server Update
   - Backup creation
   - Code deployment
   - Config update
   - Cache clear

## Monitoring

### System Metrics

- Server resources
- Database performance
- API response times
- Error rates

### Performance

- Query execution time
- API response time
- Frontend load time
- Cache hit rates

## Documentation

### API Documentation

- Endpoint descriptions
- Request/response formats
- Authentication details
- Error codes

### Configuration Guide

- System requirements
- Installation steps
- Configuration options
- Environment setup

### Code Standards

- PHP PSR standards
- JavaScript style guide
- SQL formatting
- Documentation format
