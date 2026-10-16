-- PostgreSQL
-- Je n'ai pas voulu complexifier l'exercice mais ... en mode réél, j'aurais ajouté des colones un peu "tech"
-- des created_at, updated_at, deleted_at, etc. car ça facilite le debug et la traçabilité de la donnée
-- J'ai aussi ignoré certaines données métier comme la TVA par exemple
CREATE TABLE products (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    price NUMERIC(10, 2) NOT NULL,
    description TEXT NOT NULL,
    category TEXT NOT NULL,
    image TEXT NOT NULL
);

CREATE TABLE customers (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE orders (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    customer_id INTEGER NOT NULL REFERENCES customers(id),
    ordered_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders_products (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders(id),
    product_id INTEGER NOT NULL REFERENCES products(id),
    quantity INTEGER NOT NULL,
    unit_price NUMERIC(10, 2) NOT NULL
);

-- Données de test

-- J'ai créé des articles/clients en me basant sur Rick et Morty
INSERT INTO products (title, price, description, category, image) VALUES
    ('Pistolet à portails', 199.90, 'Un gadget pour explorer les dimensions avec Rick.', 'Gadgets', 'https://example.com/images/portal-gun.png'),
    ('Boîte à Meeseeks', 49.90, 'Une boîte pour appeler un Meeseeks à la rescousse.', 'Gadgets', 'https://example.com/images/meeseeks-box.png'),
    ('Peluche Pickle Rick', 19.90, 'Une peluche de Rick transformé en cornichon.', 'Peluches', 'https://example.com/images/pickle-rick.png'),
    ('T-shirt Rick et Morty', 24.90, 'Un t-shirt illustré avec Rick et Morty.', 'Vêtements', 'https://example.com/images/rick-and-morty-tshirt.png');

INSERT INTO customers (first_name, last_name, email) VALUES
    ('Rick', 'Sanchez', 'rick.sanchez@example.com'),
    ('Morty', 'Smith', 'morty.smith@example.com'),
    ('Summer', 'Smith', 'summer.smith@example.com'),
    ('Beth', 'Smith', 'beth.smith@example.com');

INSERT INTO orders (customer_id, ordered_at) VALUES
    (1, CURRENT_TIMESTAMP - INTERVAL '1 day'),
    (1, CURRENT_TIMESTAMP - INTERVAL '3 days'),
    (2, CURRENT_TIMESTAMP - INTERVAL '1 day'),
    (3, CURRENT_TIMESTAMP - INTERVAL '3 days'),
    (2, CURRENT_TIMESTAMP - INTERVAL '10 days');

INSERT INTO orders_products (order_id, product_id, quantity, unit_price) VALUES
    -- Commande 1 : Rick achète le même produit sur deux lignes distinctes.
    (1, 1, 1, 199.90),
    (1, 1, 2, 199.90),
    (1, 2, 2, 49.90),
    -- Commande 2 : le prix historique du pistolet diffère de son prix actuel.
    (2, 1, 1, 179.90),
    -- Commandes 3 et 4 : Morty et Summer achètent des boîtes.
    (3, 2, 1, 49.90),
    (4, 2, 5, 49.90),
    -- Commande 5 : les achats de Morty datent de plus de sept jours.
    (5, 1, 3, 169.90),
    (5, 3, 4, 19.90);

-- Requêtes demandées

-- 1. Prénom et nom des clients ayant commandé le produit 1.
SELECT c.first_name, c.last_name
FROM customers AS c
WHERE EXISTS (
    SELECT 1
    FROM orders AS o
    JOIN orders_products AS op ON op.order_id = o.id
    WHERE o.customer_id = c.id
      AND op.product_id = 1
)
ORDER BY c.last_name, c.first_name, c.id;

-- 2. Produits vendus au cours des sept derniers jours, par quantité décroissante.
SELECT p.title, SUM(op.quantity) AS total_quantity
FROM products AS p
JOIN orders_products AS op ON op.product_id = p.id
JOIN orders AS o ON o.id = op.order_id
WHERE o.ordered_at >= CURRENT_TIMESTAMP - INTERVAL '7 days'
  AND o.ordered_at <= CURRENT_TIMESTAMP
GROUP BY p.id, p.title
ORDER BY total_quantity DESC, p.id;
