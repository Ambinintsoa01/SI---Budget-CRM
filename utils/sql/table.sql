DROP DATABASE IF EXISTS gestion_budget;
CREATE DATABASE IF NOT EXISTS gestion_budget;
USE gestion_budget;

-- Table des départements
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    password VARCHAR(255)
);

-- Ajout de la table des périodes
CREATE TABLE periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    year INT NOT NULL
);

-- Table des budgets
CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    initial_balance DECIMAL(15,2) NOT NULL,
    year INT NOT NULL,
    period_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status INT DEFAULT 0, -- 0: En attente, 1: Validé, 2: Rejeté
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (period_id) REFERENCES periods(id)
);

-- Table des prévisions budgétaires
CREATE TABLE budget_forecasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    type VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    period_id INT NOT NULL,
    description VARCHAR(255),
    FOREIGN KEY (budget_id) REFERENCES budgets(id),
    FOREIGN KEY (period_id) REFERENCES periods(id)
);

-- Table des réalisations budgétaires
CREATE TABLE budget_realizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    type VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    period_id INT NOT NULL,
    description VARCHAR(255),
    date DATE NOT NULL,
    FOREIGN KEY (budget_id) REFERENCES budgets(id),
    FOREIGN KEY (period_id) REFERENCES periods(id)
);

--Table des categories clients (mere)
CREATE TABLE customer_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

--Table des clients (fille)
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    category_id INT,
    FOREIGN KEY (category_id) REFERENCES customer_categories(id)
);

CREATE TABLE product_category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100)
);

CREATE TABLE products (
    id INt AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    price INT NOT NULL,
    category_id INT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES product_category(id)
);

--Table des actions
CREATE TABLE customer_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    p_category_id INT,
    product_id INT,
    customer_id INT NOT NULL,
    phase INT NOT NULL, -- 0: avant; 1: pendant; 2: apres
    description TEXT NOT NULL,
    date_action DATE,
    FOREIGN KEY (p_category_id) REFERENCES product_category(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE comm_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    p_category_id INT,
    product_id INT,
    department_id INT NOT NULL,
    phase INT NOT NULL, -- 0: avant; 1: pendant; 2: apres
    description TEXT NOT NULL,
    date_reaction DATE,
    FOREIGN KEY (p_category_id) REFERENCES product_category(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (department_id) REFERENCES customers(id)
);

CREATE TABLE CRM (
    id INT AUTO_INCREMENT PRIMARY KEY,
    c_action_id INT NOT NULL,
    c_reaction_id INT NOT NULL,
    FOREIGN KEY (c_action_id) REFERENCES customer_actions(id),
    FOREIGN KEY (c_reaction_id) REFERENCES comm_reactions(id)
);

CREATE TABLE vente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    customer_id INT NOT NULL,
    nb_vente  INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nb_produit INT NOT NULL,
    product_id INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id)
);