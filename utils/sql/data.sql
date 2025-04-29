
INSERT INTO departments (name, password) VALUES 
('Directeur general', 'dg123'),
('Compta & Finance', 'compta123'),
('Marketing & Communication', 'comm123'),
('Service Commercial', 'srcomm123');

INSERT INTO periods (name, start_date, end_date, year) VALUES 
('P1', '2023-01-01', '2023-02-28', 2023),
('P2', '2023-03-01', '2023-04-30', 2023),
('P3', '2023-05-01', '2023-06-30', 2023),
('P4', '2023-07-01', '2023-08-31', 2023),
('P5', '2023-9-01', '2023-10-31', 2023),
('P6', '2023-11-01', '2023-12-31', 2023);

INSERT INTO customer_categories (name) VALUES
('Prospect'),
('Nouveau client'),
('Client fidèle'),
('Client inactif'),
('Client VIP'),
('Entreprise');

INSERT INTO customers (name, email, phone, category_id) VALUES
('Alice Dupont', 'alice.dupont@example.com', '0600000001', 1), -- Prospect
('Bob Martin', 'bob.martin@example.com', '0600000002', 2),      -- Nouveau client
('Claire Morel', 'claire.morel@example.com', '0600000003', 3),  -- Client fidèle
('David Bernard', 'david.bernard@example.com', '0600000004', 3),
('Emma Lefevre', 'emma.lefevre@example.com', '0600000005', 4),  -- Client inactif
('Fabrice Laurent', 'fabrice.laurent@example.com', '0600000006', 5), -- VIP
('Giselle Marchand', 'giselle.marchand@example.com', '0600000007', 2),
('Henri Petit', 'henri.petit@example.com', '0600000008', 1),
('Isabelle Fabre', 'isabelle.fabre@example.com', '0600000009', 4),
('Julien Renault', 'julien.renault@example.com', '0600000010', 5),
('Kenza Bensalem', 'kenza.bensalem@example.com', '0600000011', 6), -- Entreprise
('Léo Garnier', 'leo.garnier@example.com', '0600000012', 6);

INSERT INTO product_category (name) VALUES
('Pizza'),
('Boisson'),
('Pain'),
('Dessert'),
('Entrée');

INSERT INTO products (name, price, category_id) VALUES
-- Pizzas (category_id = 1)
('Pizza 4 Fromages', 12, 1),
('Pizza Reine', 11, 1),
('Pizza Margherita', 9, 1),
('Pizza Pepperoni', 13, 1),
('Pizza Végétarienne', 10, 1),

-- Boissons (category_id = 2)
('Coca-Cola 33cl', 2, 2),
('Orangina 33cl', 2, 2),
('Eau minérale 50cl', 1, 2),
('Ice Tea Pêche', 2, 2),
('Sprite 33cl', 2, 2),

-- Pains (category_id = 3)
('Panini Jambon Fromage', 6, 3),
('Panini Poulet Curry', 6, 3),
('Panini Végétarien', 5, 3),
('Pain à l’ail', 3, 3),
('Pain nature', 2, 3),

-- Desserts (category_id = 4)
('Tiramisu', 4, 4),
('Fondant au chocolat', 4, 4),
('Mousse au chocolat', 3, 4),
('Panna Cotta', 4, 4),
('Cookie maison', 2, 4),

-- Entrées (category_id = 5)
('Salade verte', 3, 5),
('Salade de chèvre chaud', 5, 5),
('Mozzarella sticks', 4, 5),
('Onion rings', 3, 5),
('Mini bruschettas', 4, 5);

INSERT INTO customer_actions (p_category_id, product_id, customer_id, phase, description, date_action) VALUES
(NULL, 1, 1, 0, 'Le client n’a jamais entendu parler de la Pizza 4 Fromages.', '2025-04-10'),
(NULL, 1, 2, 0, 'Peu de clients commandent la Pizza 4 Fromages après leur première visite.', '2025-04-11'),

(NULL, 6, 3, 0, 'Le Coca-Cola est souvent ignoré au moment de commander.', '2025-04-12'),
(NULL, 6, 1, 0, 'Le client ne savait pas qu’on proposait du Coca-Cola.', '2025-04-09'),

(NULL, 2, 4, 0, 'La Pizza Reine n’est pas très populaire ces temps-ci.', '2025-04-13'),
(NULL, 2, 5, 0, 'Beaucoup hésitent à prendre la Pizza Reine.', '2025-04-14'),

(NULL, 3, 1, 0, 'Personne ne parle vraiment de la Margherita.', '2025-04-08'),
(NULL, 3, 2, 0, 'Le client a dit que la Margherita semblait trop simple.', '2025-04-10'),

(NULL, 4, 3, 0, 'Le Pepperoni ne semble pas attirer les clients aujourd’hui.', '2025-04-12'),
(NULL, 4, 4, 0, 'Le client ne savait pas que le Pepperoni était au menu.', '2025-04-09'),

(NULL, 7, 2, 0, 'Le client a dit qu’il ne boit jamais d’Orangina.', '2025-04-10'),
(NULL, 7, 3, 0, 'Pas de retour notable sur l’Orangina.', '2025-04-11'),

(NULL, 8, 1, 0, 'L’eau minérale est rarement ajoutée à la commande.', '2025-04-12'),
(NULL, 8, 2, 0, 'Le client n’a pas remarqué qu’il y avait de l’eau.', '2025-04-09'),

(NULL, 9, 3, 0, 'L’Ice Tea est bien là mais peu de clients le prennent.', '2025-04-13'),
(NULL, 9, 4, 0, 'Le client n’a pas vu l’Ice Tea sur la carte.', '2025-04-14'),

(NULL, 10, 5, 0, 'Le Sprite est très rarement demandé.', '2025-04-10'),
(NULL, 10, 1, 0, 'Le client ne savait pas que Sprite était proposé.', '2025-04-12'),

(NULL, 11, 2, 0, 'Le Panini Jambon Fromage passe inaperçu.', '2025-04-08'),
(NULL, 11, 3, 0, 'Très peu de commandes de Panini Jambon Fromage.', '2025-04-11');

INSERT INTO customer_actions (p_category_id, product_id, customer_id, phase, description, date_action) VALUES
(1, NULL, 4, 0, 'Le client ignorait que l’on proposait des pizzas.', '2025-04-10'),
(1, NULL, 5, 0, 'La catégorie Pizza est parfois jugée trop classique.', '2025-04-12'),

(2, NULL, 2, 0, 'Les boissons sont souvent négligées pendant la commande.', '2025-04-11'),
(2, NULL, 3, 0, 'Certains clients ne regardent même pas la partie Boisson.', '2025-04-09'),

(3, NULL, 1, 0, 'Les pains sont rarement mis en avant.', '2025-04-08'),
(3, NULL, 2, 0, 'Le client ne savait pas qu’on proposait des paninis.', '2025-04-13');

INSERT INTO stock (nb_produit, product_id) VALUES
(4, 1),
(7, 2),
(5, 3),
(6, 4),
(4, 5),
(8, 6),
(3, 7),
(5, 8),
(4, 9),
(7, 10);

INSERT INTO vente (product_id, customer_id, nb_vente) VALUES
(1, 1, 6),

(2, 3, 3),

(3, 5, 5),

(4, 2, 4),

(5, 4, 6),

(6, 1, 2),

(7, 3, 7),

(8, 5, 5),

(9, 2, 6),

(10, 4, 3);
