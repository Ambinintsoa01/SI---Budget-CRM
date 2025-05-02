SELECT
    b.*,
    d.name as department_name
FROM
    budgets b
    JOIN departments d ON b.department_id = d.id
WHERE
    b.period_id = 1
ORDER BY
    b.department_id


SELECT
    b.*,
    d.name as department_name
FROM
    budgets b
    JOIN departments d ON b.department_id = d.id
WHERE
    b.department_id = 1
    AND b.period_id = ?


SELECT b.*, d.name as department_name 
                FROM budgets b
                JOIN departments d ON b.department_id = d.id
                WHERE b.department_id = 1 AND b.year = YEAR(CURRENT_DATE)
                ORDER BY b.created_at

SELECT b.department_id, b.period_id, b.initial_balance, bf.category, bf.amount
FROM budgets b
JOIN budget_forecasts bf
ON b.id = bf.budget_id

SELECT 
    p.id as period_id,
    p.name as period_name,
    d.id as department_id,
    d.name as department_name,
    b.id as budget_id,
    b.initial_balance,
    b.status,
    bf.id as forecast_id,
    bf.category as forecast_category,
    bf.amount as forecast_amount,
    bf.description as forecast_description,
    br.id as realization_id,
    br.category as realization_category,
    br.amount as realization_amount,
    br.date as realization_date,
    br.description as realization_description
FROM periods p
LEFT JOIN budgets b ON p.id = b.period_id
LEFT JOIN departments d ON b.department_id = d.id
LEFT JOIN budget_forecasts bf ON b.id = bf.budget_id AND bf.period_id = p.id
LEFT JOIN budget_realizations br ON b.id = br.budget_id AND br.period_id = p.id
    AND br.category = bf.category
 WHERE b.department_id = 2
 ORDER BY p.start_date, d.name, bf.category;


SELECT 
    p.id as period_id,
    p.name as period_name,
    d.id as department_id,
    d.name as department_name,
    b.id as budget_id,
    b.initial_balance,
    b.status,
    bf.id as forecast_id,
    bf.category as forecast_category,
    bf.amount as forecast_amount,
    bf.description as forecast_description,
    br.id as realization_id,
    br.category as realization_category,
    br.amount as realization_amount,
    br.date as realization_date,
    br.description as realization_description
FROM periods p
LEFT JOIN budgets b ON p.id = b.period_id
LEFT JOIN departments d ON b.department_id = d.id
LEFT JOIN budget_forecasts bf ON b.id = bf.budget_id AND bf.period_id = p.id
LEFT JOIN budget_realizations br ON b.id = br.budget_id AND br.period_id = p.id
    AND br.category = bf.category
 ORDER BY p.start_date, d.name, bf.category;


SELECT 
    p.id as period_id,
    p.name as period_name,
    d.id as department_id,
    d.name as department_name,
    b.id as budget_id,
    b.initial_balance,
    b.status,
    bf.id as forecast_id,
    bf.category as forecast_category,
    bf.type as forecast_type,
    bf.amount as forecast_amount,
    bf.description as forecast_description,
    br.id as realization_id,
    br.category as realization_category,
    br.type as realization_type,
    br.amount as realization_amount,
    br.date as realization_date,
    br.description as realization_description
FROM budgets b
INNER JOIN departments d ON b.department_id = d.id
INNER JOIN periods p ON b.period_id = p.id
LEFT JOIN budget_forecasts bf ON b.id = bf.budget_id AND bf.period_id = p.id
LEFT JOIN budget_realizations br ON b.id = br.budget_id AND br.period_id = p.id 
    AND br.type = bf.type
WHERE (bf.category IS NOT NULL OR br.category IS NOT NULL) AND b.status = 1;


SELECT DISTINCT bf.type, b.id as budget_id
FROM budget_forecasts bf
JOIN budgets b ON bf.budget_id = b.id
WHERE b.department_id = 1 AND bf.category = 'Dépense'
UNION
SELECT DISTINCT br.type, b.id as budget_id
FROM budget_realizations br
JOIN budgets b ON br.budget_id = b.id
WHERE b.department_id = 1 AND br.category = 'Dépense'
ORDER BY type;

SELECT bf.type, bf.budget_id
FROM budget_forecasts bf
JOIN budgets b ON bf.budget_id = b.id
WHERE bf.category = 'Depense' AND bf.period_id = 1 AND b.department_id = 1


SELECT 
    bf.budget_id,
    bf.category,
    bf.type,
    bf.amount AS forecast_amount,
    br.amount AS realization_amount,
    bf.period_id
FROM 
    budget_forecasts bf
JOIN 
    budget_realizations br ON bf.budget_id = br.budget_id
    AND bf.category = br.category
    AND bf.type = br.type
    AND bf.period_id = br.period_id
JOIN
    budgets b ON bf.budget_id = b.id
WHERE 
    b.department_id = 1
ORDER BY 
    bf.budget_id, bf.category, bf.type, bf.period_id;


SELECT 
    p.id as period_id,
    p.name as period_name,
    d.id as department_id,
    d.name as department_name,
    b.id as budget_id,
    b.initial_balance,
    b.status,
    bf.id as forecast_id,
    bf.category,
    bf.type,
    bf.amount as forecast_amount,
    br.id as realization_id,
    br.amount as realization_amount
FROM budgets b
INNER JOIN departments d ON b.department_id = d.id
INNER JOIN periods p ON b.period_id = p.id
LEFT JOIN budget_forecasts bf ON b.id = bf.budget_id AND bf.period_id = p.id
LEFT JOIN budget_realizations br ON b.id = br.budget_id 
    AND br.period_id = p.id 
    AND br.category = bf.category 
    AND br.type = bf.type
WHERE (bf.category IS NOT NULL OR br.category IS NOT NULL) 
    AND b.status = 1 AND d.id = 3
    ORDER BY d.name, p.start_date, bf.category DESC, bf.type;

DROP TABLE CRM;
DROP TABLE comm_reactions;
DROP TABLE customer_actions;

SELECT 
    p.id AS product_id,
    p.name AS product_name,
    pc.name AS category_name,
    ca.description AS action_description,
    COALESCE(SUM(v.nb_vente), 0) AS total_ventes
FROM products p
JOIN product_category pc ON p.category_id = pc.id
LEFT JOIN customer_actions ca ON ca.product_id = p.id
LEFT JOIN vente v ON v.product_id = p.id
GROUP BY p.id, p.name, pc.name, ca.description
ORDER BY p.id;

SELECT ca.id, c.name, ca.description
FROM customer_actions ca
JOIN customers c
ON c.id = ca.customer_id;

SELECT p_category_id, product_id, description, date_action
FROM customer_actions 
WHERE id = 1;

SELECT 
    p.id AS product_id,
    p.name AS product_name,
    pc.name AS category_name,
    GROUP_CONCAT(DISTINCT CONCAT(c.name, ': ', ca.description) SEPARATOR '\n') AS action_descriptions,
    v.nb_vente AS total_ventes,
    s.nb_produit AS total_stock
FROM products p
JOIN product_category pc ON p.category_id = pc.id
LEFT JOIN customer_actions ca ON ca.product_id = p.id
LEFT JOIN customers c ON ca.customer_id = c.id
LEFT JOIN vente v ON v.product_id = p.id
LEFT JOIN stock s ON s.product_id = p.id
GROUP BY p.id, p.name, pc.name
ORDER BY p.id;

INSERT INTO comm_reactions 
(p_category_id, product_id, department_id, phase, description, date_reaction) 
VALUES (NULL, 1, 3, 3, 'Pub pizza 4 fromage', '2025-01-02');

SELECT p_category_id, product_id, description, date_action
FROM customer_actions 
WHERE id = 1;

SELECT ca.id, c.name, ca.description
FROM customer_actions ca
JOIN customers c
ON c.id = ca.customer_id
WHERE ca.p_category_id IS NULL
ORDER BY ca.id;

SELECT *
FROM customer_actions
WHERE id=1;

SELECT 
    b.id AS budget_id,
    bf.id AS forecast_id,
    bf.category,
    bf.amount - COALESCE(SUM(br.amount), 0) AS remaining_amount
FROM budgets b
INNER JOIN budget_forecasts bf ON b.id = bf.budget_id
LEFT JOIN budget_realizations br ON b.id = br.budget_id 
    AND bf.category = br.category 
    AND bf.type = br.type
WHERE 
    b.period_id = 1 
    AND b.department_id = 3 
    AND bf.category != 'Recette'
    AND b.status = 1 -- Budget validé
GROUP BY 
    b.id, 
    bf.id, 
    bf.category, 
    bf.amount
HAVING 
    remaining_amount > 0

SELECT 
    COALESCE(SUM(br.amount), 0) - bf.amount AS remaining_amount
FROM budget_forecasts bf
LEFT JOIN budget_realizations br ON bf.budget_id = br.budget_id 
    AND bf.category = br.category 
    AND bf.type = br.type
WHERE bf.budget_id = 1 AND bf.category = 'Dépense' AND bf.type = 'Pub pizza 4 fromage'
GROUP BY bf.amount;

SELECT *
FROM customer_actions 
WHERE id = 1;