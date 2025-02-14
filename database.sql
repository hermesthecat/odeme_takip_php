-- Veritabanı oluştur
CREATE DATABASE IF NOT EXISTS odeme_takip CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE odeme_takip;

-- Kullanıcılar tablosu
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    preferences JSON,
    last_login DATETIME,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Gelirler tablosu
CREATE TABLE incomes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT,
    income_date DATE NOT NULL,
    category VARCHAR(50) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'TRY',
    tags JSON,
    recurring_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recurring_id) REFERENCES recurring_transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Giderler tablosu
CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    payment_date DATE,
    category VARCHAR(50) NOT NULL,
    status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    currency VARCHAR(3) NOT NULL DEFAULT 'TRY',
    tags JSON,
    recurring_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recurring_id) REFERENCES recurring_transactions(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Birikim hedefleri tablosu
CREATE TABLE savings_goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    target_amount DECIMAL(15, 2) NOT NULL,
    current_amount DECIMAL(15, 2) NOT NULL DEFAULT 0,
    description TEXT,
    target_date DATE NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'TRY',
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Fatura hatırlatıcıları tablosu
CREATE TABLE bill_reminders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    due_date DATE NOT NULL,
    repeat_interval VARCHAR(20) NOT NULL DEFAULT 'monthly',
    description TEXT,
    category VARCHAR(50),
    currency VARCHAR(3) NOT NULL DEFAULT 'TRY',
    status ENUM('active', 'inactive') DEFAULT 'active',
    notification_days INT DEFAULT 3,
    last_notification_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tekrarlanan işlemler tablosu
CREATE TABLE recurring_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'TRY',
    interval_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    interval_count INT NOT NULL DEFAULT 1,
    start_date DATE NOT NULL,
    end_date DATE,
    last_execution_date DATE,
    next_execution_date DATE,
    status ENUM('active', 'paused', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Kategoriler tablosu
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    icon VARCHAR(50),
    color VARCHAR(7),
    parent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_category (user_id, name, type)
) ENGINE=InnoDB;

-- Etiketler tablosu
CREATE TABLE tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tag (user_id, name)
) ENGINE=InnoDB;

-- Bütçeler tablosu
CREATE TABLE budgets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'TRY',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    repeat_interval VARCHAR(20) DEFAULT 'monthly',
    notification_threshold INT DEFAULT 80,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Aktivite günlüğü tablosu
CREATE TABLE activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Bildirimler tablosu
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Döviz kurları tablosu
CREATE TABLE exchange_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    base_currency VARCHAR(3) NOT NULL,
    target_currency VARCHAR(3) NOT NULL,
    rate DECIMAL(15, 6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_pair (base_currency, target_currency, created_at)
) ENGINE=InnoDB;

-- İndeksler
CREATE INDEX idx_incomes_user_date ON incomes(user_id, income_date);
CREATE INDEX idx_expenses_user_date ON expenses(user_id, due_date);
CREATE INDEX idx_savings_user_date ON savings_goals(user_id, target_date);
CREATE INDEX idx_bills_user_date ON bill_reminders(user_id, due_date);
CREATE INDEX idx_recurring_next_exec ON recurring_transactions(next_execution_date);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_activity_user_date ON activity_log(user_id, created_at);
CREATE INDEX idx_exchange_rates_date ON exchange_rates(created_at);

-- Varsayılan kategorileri ekle
INSERT INTO categories (user_id, name, type, icon, color) VALUES
(1, 'Maaş', 'income', 'wallet', '#4CAF50'),
(1, 'Serbest Çalışma', 'income', 'briefcase', '#2196F3'),
(1, 'Yatırım', 'income', 'trending-up', '#9C27B0'),
(1, 'Diğer Gelir', 'income', 'plus-circle', '#607D8B'),
(1, 'Faturalar', 'expense', 'file-text', '#F44336'),
(1, 'Kira', 'expense', 'home', '#FF5722'),
(1, 'Gıda', 'expense', 'shopping-cart', '#FFC107'),
(1, 'Ulaşım', 'expense', 'map', '#795548'),
(1, 'Alışveriş', 'expense', 'shopping-bag', '#E91E63'),
(1, 'Sağlık', 'expense', 'heart', '#8BC34A'),
(1, 'Eğitim', 'expense', 'book', '#00BCD4'),
(1, 'Eğlence', 'expense', 'music', '#673AB7'),
(1, 'Diğer Gider', 'expense', 'more-horizontal', '#9E9E9E');

-- Tetikleyiciler
DELIMITER //

-- Gider ödemesi yapıldığında durumu güncelle
CREATE TRIGGER after_expense_payment
AFTER UPDATE ON expenses
FOR EACH ROW
BEGIN
    IF NEW.payment_date IS NOT NULL AND OLD.payment_date IS NULL THEN
        UPDATE expenses SET status = 'paid' WHERE id = NEW.id;
    END IF;
END //

-- Fatura vadesi geçtiğinde gider oluştur
CREATE TRIGGER after_bill_due
AFTER UPDATE ON bill_reminders
FOR EACH ROW
BEGIN
    IF NEW.due_date < CURDATE() AND OLD.due_date >= CURDATE() THEN
        INSERT INTO expenses (
            user_id, amount, description, due_date, category,
            currency, status
        ) VALUES (
            NEW.user_id, NEW.amount, NEW.title, NEW.due_date,
            'Faturalar', NEW.currency, 'overdue'
        );
    END IF;
END //

-- Birikim hedefi tamamlandığında durumu güncelle
CREATE TRIGGER after_savings_update
AFTER UPDATE ON savings_goals
FOR EACH ROW
BEGIN
    IF NEW.current_amount >= NEW.target_amount AND OLD.current_amount < OLD.target_amount THEN
        UPDATE savings_goals SET status = 'completed' WHERE id = NEW.id;
    END IF;
END //

DELIMITER ;

-- Görünümler
-- Aylık özet görünümü
CREATE VIEW monthly_summary AS
SELECT 
    u.id as user_id,
    DATE_FORMAT(COALESCE(i.income_date, e.due_date), '%Y-%m') as month,
    COALESCE(SUM(i.amount), 0) as total_income,
    COALESCE(SUM(e.amount), 0) as total_expense,
    COALESCE(SUM(i.amount), 0) - COALESCE(SUM(e.amount), 0) as net_balance,
    COUNT(DISTINCT i.id) as income_count,
    COUNT(DISTINCT e.id) as expense_count
FROM users u
LEFT JOIN incomes i ON u.id = i.user_id
LEFT JOIN expenses e ON u.id = e.user_id
GROUP BY u.id, month;

-- Kategori bazlı harcama görünümü
CREATE VIEW category_expenses AS
SELECT 
    u.id as user_id,
    e.category,
    COUNT(*) as transaction_count,
    SUM(e.amount) as total_amount,
    AVG(e.amount) as average_amount,
    MIN(e.amount) as min_amount,
    MAX(e.amount) as max_amount
FROM users u
JOIN expenses e ON u.id = e.user_id
GROUP BY u.id, e.category;

-- Birikim hedefleri ilerleme görünümü
CREATE VIEW savings_progress AS
SELECT
    user_id,
    COUNT(*) as total_goals,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_goals,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_goals,
    SUM(target_amount) as total_target,
    SUM(current_amount) as total_saved,
    (SUM(current_amount) / SUM(target_amount) * 100) as overall_progress
FROM savings_goals
GROUP BY user_id;