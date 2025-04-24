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

