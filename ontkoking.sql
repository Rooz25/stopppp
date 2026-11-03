-- =========================================================
-- Stop de ontkoking - database
-- Import in phpMyAdmin: select the database > tab "SQL" (or "Import")
-- Test logins:
--   admin@ontkoking.nl  / admin1234  (role: admin)
--   sanne@ontkoking.nl  / test1234   (role: user)
--   jayden@ontkoking.nl / test1234   (role: user)
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS recipe_categories;
DROP TABLE IF EXISTS recipe_ingredients;
DROP TABLE IF EXISTS steps;
DROP TABLE IF EXISTS recipe_photos;
DROP TABLE IF EXISTS recipes;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS ingredients;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- Tables
-- ---------------------------------------------------------

CREATE TABLE users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username        VARCHAR(50)  NOT NULL,
    email           VARCHAR(255) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255) NULL,
    bio             TEXT         NULL,
    role            ENUM('user','admin')      NOT NULL DEFAULT 'user',
    status          ENUM('active','blocked')  NOT NULL DEFAULT 'active',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recipes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(150) NOT NULL,
    description TEXT         NULL,
    prep_time   INT UNSIGNED NOT NULL COMMENT 'minutes',
    servings    INT UNSIGNED NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_recipes_user (user_id),
    KEY idx_recipes_prep_time (prep_time),
    CONSTRAINT fk_recipes_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recipe_photos (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    recipe_id  INT UNSIGNED NOT NULL,
    file_path  VARCHAR(255) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = cover photo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_photos_recipe (recipe_id),
    CONSTRAINT fk_photos_recipe FOREIGN KEY (recipe_id)
        REFERENCES recipes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE steps (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    recipe_id   INT UNSIGNED NOT NULL,
    step_number INT UNSIGNED NOT NULL,
    instruction TEXT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_steps_recipe_number (recipe_id, step_number),
    CONSTRAINT fk_steps_recipe FOREIGN KEY (recipe_id)
        REFERENCES recipes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ingredients (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ingredients_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recipe_ingredients (
    recipe_id     INT UNSIGNED NOT NULL,
    ingredient_id INT UNSIGNED NOT NULL,
    amount        DECIMAL(8,2) NULL COMMENT 'NULL = naar smaak',
    unit          VARCHAR(20)  NULL COMMENT 'g, ml, stuks, el, tl, ...',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (recipe_id, ingredient_id),
    KEY idx_ri_ingredient (ingredient_id),
    CONSTRAINT fk_ri_recipe FOREIGN KEY (recipe_id)
        REFERENCES recipes (id) ON DELETE CASCADE,
    CONSTRAINT fk_ri_ingredient FOREIGN KEY (ingredient_id)
        REFERENCES ingredients (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recipe_categories (
    recipe_id   INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (recipe_id, category_id),
    KEY idx_rc_category (category_id),
    CONSTRAINT fk_rc_recipe FOREIGN KEY (recipe_id)
        REFERENCES recipes (id) ON DELETE CASCADE,
    CONSTRAINT fk_rc_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favorites (
    user_id    INT UNSIGNED NOT NULL,
    recipe_id  INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, recipe_id),
    KEY idx_fav_recipe (recipe_id),
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_recipe FOREIGN KEY (recipe_id)
        REFERENCES recipes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Categories
-- ---------------------------------------------------------

INSERT INTO categories (id, name) VALUES
-- Maaltijdmoment
(1,  'Ontbijt'),
(2,  'Lunch'),
(3,  'Diner'),
(4,  'Snack'),
-- Gang
(5,  'Voorgerecht'),
(6,  'Hoofdgerecht'),
(7,  'Nagerecht'),
(8,  'Bijgerecht'),
-- Dieet
(9,  'Vegetarisch'),
(10, 'Vegan'),
(11, 'Glutenvrij'),
(12, 'Lactosevrij'),
(13, 'Halal'),
(14, 'Eiwitrijk'),
(15, 'Koolhydraatarm'),
-- Soort gerecht
(16, 'Soep'),
(17, 'Salade'),
(18, 'Pasta'),
(19, 'Rijst & noedels'),
(20, 'Bakken'),
(21, 'Budget');

-- ---------------------------------------------------------
-- Test users (passwords hashed with PHP password_hash)
-- ---------------------------------------------------------

INSERT INTO users (id, username, email, password_hash, bio, role) VALUES
(1, 'admin',  'admin@ontkoking.nl',  '$2y$12$n5Oj/GxeOWw4mOxxekYQQOG2kluVho12i9quo89Gijc15sdS6Odsy', 'Beheerder van het platform.', 'admin'),
(2, 'sanne',  'sanne@ontkoking.nl',  '$2y$12$shK8mrUhOCg6A/k5opHl7.ioUJ6QYBXy7BMykOVh1iZH2/GSjslj2', 'Student, kookt graag snel en goedkoop.', 'user'),
(3, 'jayden', 'jayden@ontkoking.nl', '$2y$12$shK8mrUhOCg6A/k5opHl7.ioUJ6QYBXy7BMykOVh1iZH2/GSjslj2', 'Plantbased sinds 2024.', 'user');

-- ---------------------------------------------------------
-- Ingredients
-- ---------------------------------------------------------

INSERT INTO ingredients (id, name) VALUES
(1,  'ei'),
(2,  'tomatenblokjes'),
(3,  'ui'),
(4,  'knoflook'),
(5,  'paprika'),
(6,  'komijn'),
(7,  'olijfolie'),
(8,  'zout'),
(9,  'peper'),
(10, 'spaghetti'),
(11, 'rode peper'),
(12, 'peterselie'),
(13, 'havermout'),
(14, 'havermelk'),
(15, 'banaan'),
(16, 'blauwe bessen'),
(17, 'kidneybonen'),
(18, 'mais'),
(19, 'rijst'),
(20, 'paprikapoeder'),
(21, 'groentebouillon');

-- ---------------------------------------------------------
-- Test recipes
-- ---------------------------------------------------------

INSERT INTO recipes (id, user_id, title, description, prep_time, servings) VALUES
(1, 2, 'Shakshuka', 'Eieren gepocheerd in een pittige tomatensaus. Perfect voor een lazy zondag.', 20, 2),
(2, 2, 'Spaghetti aglio e olio', 'Vijf ingredienten, tien minuten, altijd goed.', 10, 2),
(3, 3, 'Overnight oats met blauwe bessen', 'Avond ervoor maken, ochtend meteen eten.', 5, 1),
(4, 3, 'Chili sin carne', 'Vullende vegan chili voor de hele week.', 35, 4);

INSERT INTO recipe_photos (recipe_id, file_path, sort_order) VALUES
(1, 'uploads/recipes/shakshuka-1.jpg', 0),
(1, 'uploads/recipes/shakshuka-2.jpg', 1),
(2, 'uploads/recipes/aglio-e-olio.jpg', 0),
(3, 'uploads/recipes/overnight-oats.jpg', 0),
(4, 'uploads/recipes/chili-sin-carne.jpg', 0);

INSERT INTO recipe_ingredients (recipe_id, ingredient_id, amount, unit) VALUES
-- Shakshuka
(1, 1, 4, 'stuks'),
(1, 2, 400, 'g'),
(1, 3, 1, 'stuks'),
(1, 4, 2, 'teentjes'),
(1, 5, 1, 'stuks'),
(1, 6, 1, 'tl'),
(1, 7, 2, 'el'),
(1, 8, NULL, NULL),
-- Aglio e olio
(2, 10, 200, 'g'),
(2, 4, 4, 'teentjes'),
(2, 7, 4, 'el'),
(2, 11, 1, 'stuks'),
(2, 12, 1, 'handje'),
(2, 8, NULL, NULL),
-- Overnight oats
(3, 13, 50, 'g'),
(3, 14, 150, 'ml'),
(3, 15, 0.5, 'stuks'),
(3, 16, 50, 'g'),
-- Chili sin carne
(4, 17, 400, 'g'),
(4, 2, 800, 'g'),
(4, 3, 1, 'stuks'),
(4, 4, 3, 'teentjes'),
(4, 5, 2, 'stuks'),
(4, 18, 150, 'g'),
(4, 6, 2, 'tl'),
(4, 20, 2, 'tl'),
(4, 21, 200, 'ml'),
(4, 19, 300, 'g'),
(4, 9, NULL, NULL);

INSERT INTO steps (recipe_id, step_number, instruction) VALUES
(1, 1, 'Snijd de ui en paprika in blokjes en de knoflook fijn.'),
(1, 2, 'Fruit de ui 3 minuten in olijfolie tot hij glazig is. Voeg paprika, knoflook en komijn toe.'),
(1, 3, 'Doe de tomatenblokjes erbij en laat 8 minuten zachtjes pruttelen. Breng op smaak met zout.'),
(1, 4, 'Maak 4 kuiltjes in de saus, breek in elk kuiltje een ei en doe de deksel op de pan tot het eiwit gestold is.'),
(2, 1, 'Kook de spaghetti in ruim water met zout.'),
(2, 2, 'Snijd de knoflook in plakjes en de rode peper in ringetjes.'),
(2, 3, 'Verwarm de olie op laag vuur en bak knoflook en peper 2 minuten tot ze goudgeel zijn.'),
(2, 4, 'Schep de spaghetti met een scheut kookwater door de olie en bestrooi met peterselie.'),
(3, 1, 'Prak de halve banaan in een pot of bakje.'),
(3, 2, 'Roer de havermout en havermelk erdoor.'),
(3, 3, 'Zet een nacht in de koelkast en top met blauwe bessen.'),
(4, 1, 'Kook de rijst volgens de verpakking.'),
(4, 2, 'Fruit ui, knoflook en paprika 5 minuten in een grote pan.'),
(4, 3, 'Voeg komijn en paprikapoeder toe en bak 1 minuut mee.'),
(4, 4, 'Doe tomatenblokjes, bouillon, uitgelekte kidneybonen en mais erbij en laat 20 minuten pruttelen.'),
(4, 5, 'Breng op smaak met peper en serveer met de rijst.');

INSERT INTO recipe_categories (recipe_id, category_id) VALUES
-- Shakshuka: ontbijt, lunch, hoofdgerecht, vegetarisch, glutenvrij, budget
(1, 1), (1, 2), (1, 6), (1, 9), (1, 11), (1, 21),
-- Aglio e olio: diner, hoofdgerecht, vegan, pasta, budget
(2, 3), (2, 6), (2, 9), (2, 10), (2, 18), (2, 21),
-- Overnight oats: ontbijt, snack, vegetarisch, vegan, lactosevrij
(3, 1), (3, 4), (3, 9), (3, 10), (3, 12),
-- Chili sin carne: diner, hoofdgerecht, vegan, glutenvrij, lactosevrij, eiwitrijk, rijst, budget
(4, 3), (4, 6), (4, 9), (4, 10), (4, 11), (4, 12), (4, 14), (4, 19), (4, 21);

INSERT INTO favorites (user_id, recipe_id) VALUES
(2, 4),
(3, 1),
(3, 2);
