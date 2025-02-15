# System Patterns: Personal Finance Management System

## Architecture Overview

### System Architecture

```
[Client Layer]
    │
    ├── Web Interface (HTML/CSS/JS)
    │   ├── Dashboard Components
    │   ├── Forms & Inputs
    │   └── Interactive Charts
    │
[Application Layer]
    │
    ├── PHP Backend
    │   ├── Authentication System
    │   ├── Business Logic
    │   └── API Endpoints
    │
[Data Layer]
    │
    ├── MySQL Database
    │   ├── Core Tables
    │   ├── Transaction History
    │   └── User Data
    │
[Integration Layer]
    │
    └── External Services
        ├── Exchange Rate API
        └── Email Service
```

## Design Patterns

### 1. Authentication & Security

- Enhanced Session Management
  - Secure cookie settings (httpOnly, secure, strict)
  - Session lifetime control
  - IP and user agent validation
  - Session regeneration on login

- Brute Force Protection
  - Failed login attempt tracking
  - Account lockout mechanism
  - Cooldown period implementation
  - IP-based rate limiting

- Input Security
  - CSRF token implementation
  - Input sanitization and validation
  - Password complexity requirements
  - XSS prevention

- Persistent Authentication
  - Remember me functionality
  - Secure token generation
  - Token expiration management
  - Cookie security settings

- Security Logging
  - Activity tracking
  - Security event logging
  - IP address monitoring
  - User agent tracking

### 2. Database Design

- Normalized schema design (3NF)
- Foreign key constraints for referential integrity
- JSON columns for flexible data storage
- Indexed queries for performance
- Activity logging for audit trails

### 3. API Structure

- RESTful endpoints organization
- Resource-based URL routing
- Standardized response formats
- Error handling and status codes
- Rate limiting and caching

### 4. Frontend Organization

- Component-based structure
- Event-driven interactions
- Asynchronous data loading
- Responsive design patterns
- Theme switching capability

## Key Technical Decisions

### Database Schema

- Users and authentication
- Financial transactions (income/expenses)
- Categories and tags
- Bills and reminders
- Savings goals
- Activity logging
- Exchange rates

### Security Implementation

```php
// Session Security
initSecureSession()
validateSession()
regenerateSession()

// Authentication
validateLogin()
checkRateLimit()
handleFailedAttempts()

// Token Management
generateSecureToken()
validateToken()
handleTokenExpiration()

// Input Protection
sanitizeInput()
validateInput()
checkCsrfToken()

// Security Logging
logSecurityEvent()
trackUserActivity()
monitorFailedAttempts()
```

### Database Schema Updates

```sql
-- Remember Me Tokens
CREATE TABLE remember_me_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_expiry (expires_at)
);

-- Security Event Logging
ALTER TABLE activity_log
ADD COLUMN ip_address VARCHAR(45),
ADD COLUMN user_agent TEXT;

-- User Security Fields
ALTER TABLE users
ADD COLUMN failed_login_attempts INT DEFAULT 0,
ADD COLUMN last_failed_login TIMESTAMP NULL;
```

### API Response Format

```json
{
  "status": "success|error",
  "data": {},
  "message": "Response message",
  "code": 200
}
```

## Component Relationships

### 1. Authentication Flow

```
Login/Register → Session Management → Access Control
```

### 2. Transaction Processing

```
Input Validation → Currency Conversion → Database Storage → Activity Log
```

### 3. Reporting System

```
Data Aggregation → Analysis → Visualization → Export
```

## Architectural Patterns

### 1. MVC Pattern

- Models: Database interactions
- Views: PHP templates and JS rendering
- Controllers: Business logic and routing

### 2. Repository Pattern

- Separation of data access logic
- Consistent interface for data operations
- Centralized data manipulation

### 3. Service Layer

- Business logic encapsulation
- Transaction management
- External service integration

## Error Handling

### 1. Exception Hierarchy

- Database errors
- Validation errors
- Authentication errors
- Integration errors

### 2. Error Response Format

```json
{
  "status": "error",
  "code": "ERROR_CODE",
  "message": "User friendly message",
  "details": "Technical details"
}
```

## Performance Optimizations

### 1. Caching Strategy

- API response caching
- Exchange rate caching
- Session data caching
- Database query caching

### 2. Query Optimization

- Proper indexing
- Query planning
- Batch operations
- Connection pooling

## Integration Patterns

### 1. External APIs

- RESTful integration
- Webhook handling
- Rate limit management
- Error handling

### 2. Internal Services

- Modular design
- Service discovery
- Load balancing
- Circuit breaking

## Development Patterns

### 1. Code Organization

```
/api          - API endpoints
/assets       - Static resources
/includes     - Core functionality
/cache        - Cached data
```

### 2. Naming Conventions

- camelCase for JavaScript
- snake_case for PHP/MySQL
- PascalCase for classes
- kebab-case for assets

## Maintenance Patterns

### 1. Logging

- Error logging
- Activity logging
- Performance monitoring
- Security auditing

### 2. Backup Strategy

- Database backups
- Configuration backups
- User data protection
- Recovery procedures
