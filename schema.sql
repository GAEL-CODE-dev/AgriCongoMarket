CREATE DATABASE IF NOT EXISTS agricongo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agricongo;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('buyer', 'seller', 'admin') NOT NULL DEFAULT 'buyer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS producers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    farm_name VARCHAR(150) NOT NULL,
    region VARCHAR(120) NULL,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_producers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL
);

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producer_id INT UNSIGNED NULL,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    price_value DECIMAL(10,2) NOT NULL,
    price_label VARCHAR(80) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    bg_gradient VARCHAR(120) NOT NULL DEFAULT 'from-gray-50 to-white',
    stock_quantity INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_producer FOREIGN KEY (producer_id) REFERENCES producers(id) ON DELETE SET NULL,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    profile_type VARCHAR(50) NULL,
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    status ENUM('pending', 'paid', 'processing', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    product_name VARCHAR(150) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    line_total DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

INSERT INTO categories (slug, name) VALUES
('fruits-legumes', 'Fruits et legumes'),
('produits-laitiers', 'Produits frais et oeufs'),
('viandes', 'Viandes et volailles'),
('epicerie', 'Epicerie et miel')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (id, full_name, email, phone, password_hash, role) VALUES
(1, 'Merveille Ndziessi', 'vendeur@example.com', '+242 06 123 4567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller'),
(2, 'Patrick Mavoungou', 'acheteur@example.com', '+242 05 987 6543', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    email = VALUES(email),
    phone = VALUES(phone),
    role = VALUES(role);

INSERT INTO producers (id, user_id, farm_name, region, description) VALUES
(1, 1, 'Cooperative Maraichere du Djoue', 'Brazzaville', 'Production maraichere locale pour les marches urbains.'),
(2, NULL, 'Verger du Pool', 'Pool', 'Fruits et bananes plantains issus de petites exploitations familiales.'),
(3, NULL, 'Ferme Avicole de Kombe', 'Pointe-Noire', 'Elevage fermier et oeufs frais en circuit court.'),
(4, NULL, 'Rucher des Plateaux', 'Plateaux', 'Miel congolais et produits transformes artisanaux.'),
(5, NULL, 'Elevage de la Lekoumou', 'Lekoumou', 'Volailles et viande locale pour la restauration et les familles.'),
(6, NULL, 'Transformation de Nkayi', 'Bouenza', 'Transformation artisanale d huile et de produits agricoles locaux.')
ON DUPLICATE KEY UPDATE
    farm_name = VALUES(farm_name),
    region = VALUES(region),
    description = VALUES(description);

INSERT INTO products (id, producer_id, category_id, name, description, price_value, price_label, unit, image_path, bg_gradient, stock_quantity, is_active) VALUES
(1, 1, 1, 'Tomates de Brazzaville', 'Tomates fraiches recoltees au petit matin.', 1500, '1 500 FCFA / kg', 'kg', 'img/tomates-saison-scaled.webp', 'from-red-50 to-orange-50', 120, 1),
(2, 2, 1, 'Bananes plantains', 'Bananes plantains locales, fermes et savoureuses.', 2200, '2 200 FCFA / regime', 'regime', 'img/bananes.webp', 'from-green-50 to-emerald-50', 90, 1),
(3, 3, 2, 'Oeufs fermiers x6', 'Oeufs frais issus d elevages familiaux locaux.', 1800, '1 800 FCFA / boite', 'boite', 'img/oeufs.png', 'from-stone-50 to-gray-50', 70, 1),
(4, 4, 4, 'Miel des Plateaux', 'Miel artisanal congolais au parfum floral.', 3500, '3 500 FCFA / pot', 'pot', 'img/miel-nitrub.jpg', 'from-yellow-50 to-amber-50', 45, 1),
(5, 5, 3, 'Poulet fermier', 'Poulet fermier prepare le jour meme.', 6500, '6 500 FCFA / piece', 'piece', 'img/viandes.jpg', 'from-rose-50 to-red-50', 24, 1),
(6, 6, 4, 'Huile de palme rouge', 'Huile locale non raffinee, ideale pour la cuisine.', 2800, '2 800 FCFA / litre', 'litre', 'img/huile.png', 'from-orange-100 to-yellow-50', 50, 1)
ON DUPLICATE KEY UPDATE
    producer_id = VALUES(producer_id),
    category_id = VALUES(category_id),
    name = VALUES(name),
    description = VALUES(description),
    price_value = VALUES(price_value),
    price_label = VALUES(price_label),
    unit = VALUES(unit),
    image_path = VALUES(image_path),
    bg_gradient = VALUES(bg_gradient),
    stock_quantity = VALUES(stock_quantity),
    is_active = VALUES(is_active);

INSERT INTO orders (id, user_id, status, total_amount) VALUES
(1, 2, 'paid', 6100)
ON DUPLICATE KEY UPDATE
    status = VALUES(status),
    total_amount = VALUES(total_amount);

INSERT INTO order_items (id, order_id, product_id, product_name, unit_price, quantity, line_total) VALUES
(1, 1, 1, 'Tomates de Brazzaville', 1500, 1, 1500),
(2, 1, 3, 'Oeufs fermiers x6', 1800, 1, 1800),
(3, 1, 6, 'Huile de palme rouge', 2800, 1, 2800)
ON DUPLICATE KEY UPDATE
    product_name = VALUES(product_name),
    unit_price = VALUES(unit_price),
    quantity = VALUES(quantity),
    line_total = VALUES(line_total);
