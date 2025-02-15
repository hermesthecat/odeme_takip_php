-- Add is_default and display_name to categories table
ALTER TABLE categories 
ADD COLUMN is_default BOOLEAN DEFAULT FALSE,
ADD COLUMN display_name VARCHAR(50) NOT NULL AFTER name;

-- Add is_default to bill_categories table
ALTER TABLE bill_categories 
ADD COLUMN is_default BOOLEAN DEFAULT FALSE,
ADD COLUMN display_name VARCHAR(50) NOT NULL AFTER name;

-- Update existing categories to have display_name match name
UPDATE categories SET display_name = name WHERE display_name IS NULL;
UPDATE bill_categories SET display_name = name WHERE display_name IS NULL;

-- Insert default bill categories
INSERT INTO bill_categories (user_id, name, display_name, icon, color, is_default) VALUES
(1, 'utilities', 'Faturalar', 'file-text', '#F44336', TRUE),
(1, 'rent', 'Kira', 'home', '#2196F3', TRUE),
(1, 'insurance', 'Sigorta', 'shield', '#4CAF50', TRUE),
(1, 'subscription', 'Abonelikler', 'repeat', '#FFC107', TRUE),
(1, 'other', 'Diğer', 'more-horizontal', '#9E9E9E', TRUE);

-- Update existing income categories to be default
UPDATE categories 
SET is_default = TRUE, 
    name = CASE 
        WHEN name = 'Maaş' THEN 'salary'
        WHEN name = 'Serbest Çalışma' THEN 'freelance'
        WHEN name = 'Yatırım' THEN 'investment'
        WHEN name = 'Diğer Gelir' THEN 'other'
    END,
    display_name = name
WHERE type = 'income';

-- Update existing expense categories to be default
UPDATE categories 
SET is_default = TRUE,
    name = CASE 
        WHEN name = 'Faturalar' THEN 'bills'
        WHEN name = 'Kira' THEN 'rent'
        WHEN name = 'Gıda' THEN 'food'
        WHEN name = 'Ulaşım' THEN 'transportation'
        WHEN name = 'Alışveriş' THEN 'shopping'
        WHEN name = 'Sağlık' THEN 'health'
        WHEN name = 'Eğitim' THEN 'education'
        WHEN name = 'Eğlence' THEN 'entertainment'
        WHEN name = 'Diğer Gider' THEN 'other'
    END,
    display_name = name
WHERE type = 'expense';
