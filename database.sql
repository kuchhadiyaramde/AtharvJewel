-- ============================================================
-- Atharv Jewel — Database Schema & Seed Data
-- Run this script once, then run setup.php to create admin user.
-- ============================================================

CREATE DATABASE IF NOT EXISTS atharv_jewel
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE atharv_jewel;

-- -------------------------------------------------------
-- Categories
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)     NOT NULL,
    slug        VARCHAR(100)     NOT NULL UNIQUE,
    image       VARCHAR(255)     NOT NULL DEFAULT '',
    sort_order  INT              NOT NULL DEFAULT 0,
    created_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -------------------------------------------------------
-- Products
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id             INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(255)   NOT NULL,
    category_id    INT UNSIGNED   NOT NULL,
    current_price  VARCHAR(50)    NOT NULL,
    original_price VARCHAR(50)    NOT NULL,
    rating         DECIMAL(3,1)   NOT NULL DEFAULT 0.0,
    description    TEXT           NOT NULL DEFAULT '',
    image          VARCHAR(255)   NOT NULL DEFAULT '',
    is_new         TINYINT(1)     NOT NULL DEFAULT 0,
    is_active      TINYINT(1)     NOT NULL DEFAULT 1,
    created_at     TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- -------------------------------------------------------
-- Admin users
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id             INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(100)   NOT NULL UNIQUE,
    password_hash  VARCHAR(255)   NOT NULL,
    last_login     TIMESTAMP      NULL,
    created_at     TIMESTAMP      DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------
-- Seed: categories
-- -------------------------------------------------------
INSERT IGNORE INTO categories (name, slug, image, sort_order) VALUES
('Earrings',  'earrings',  'photos/01.jpg', 1),
('Bangles',   'bangles',   'photos/03.jpg', 2),
('Necklaces', 'necklaces', 'photos/10.jpg', 3),
('Rings',     'rings',     'photos/12.jpg', 4);

-- -------------------------------------------------------
-- Seed: products
-- -------------------------------------------------------
INSERT IGNORE INTO products
    (id, name, category_id, current_price, original_price, rating, description, image, is_new)
VALUES
(1,  'The In Between',     1, '₹65,455',   '₹78,154',   4.5, 'Women the in-between solitaire casual earrings with diamond.',            'photos/01.jpg', 1),
(2,  'Just Like Heaven',   1, '₹83,420',   '₹99,639',   4.0, 'Women just like heaven solitaire stud earrings.',                         'photos/02.jpg', 1),
(3,  'Starlight Earrings', 1, '₹55,120',   '₹68,450',   4.7, 'Women starlight stud earrings with premium finish.',                      'photos/05.jpg', 0),
(4,  'Golden Drop',        1, '₹45,890',   '₹57,200',   3.5, 'Women golden drop earrings with gemstone accent.',                        'photos/08.jpg', 0),
(5,  'The Royal Treatment',2, '₹89,650',   '₹1,07,068', 5.0, 'Women the royal treatment solitaire bangles in 22K gold.',                'photos/03.jpg', 0),
(6,  'Radiant Bloom',      2, '₹70,900',   '₹89,800',   4.2, 'Women radiant bloom casual bangles with floral design.',                  'photos/04.jpg', 1),
(7,  'Diamond Bangle',     2, '₹1,25,000', '₹1,52,000', 4.8, 'Premium diamond bangle crafted in 18K gold.',                             'photos/09.jpg', 1),
(8,  'Crystal Cascade',    3, '₹1,45,000', '₹1,76,000', 4.6, 'Crystal cascade necklace with sapphire pendant.',                        'photos/10.jpg', 1),
(9,  'Eternal Glow',       3, '₹98,500',   '₹1,19,200', 4.3, 'Eternal glow statement necklace with multi-stone setting.',               'photos/11.jpg', 0),
(10, 'Solitaire Ring',     4, '₹2,10,000', '₹2,58,000', 5.0, 'Classic solitaire engagement ring set in 22K gold with GIA diamond.',    'photos/12.jpg', 1),
(11, 'Floral Band',        4, '₹85,000',   '₹1,02,000', 4.1, 'Floral band ring with micro-pave diamonds and rose gold finish.',         'photos/13.jpg', 0);
