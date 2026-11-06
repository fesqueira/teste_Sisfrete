-- Consulta 1 com o índice (user_id, created_at)
EXPLAIN
SELECT u.id, u.name, COUNT(o.id) AS total_pedidos
FROM users u
LEFT JOIN orders o
    ON o.user_id = u.id
    AND o.created_at >= NOW() - INTERVAL 12 MONTH
GROUP BY u.id, u.name;

-- Consulta 1 sem o índice
EXPLAIN
SELECT u.id, u.name, COUNT(o.id) AS total_pedidos
FROM users u
LEFT JOIN orders o IGNORE INDEX (orders_user_id_created_at_index)
    ON o.user_id = u.id
    AND o.created_at >= NOW() - INTERVAL 12 MONTH
GROUP BY u.id, u.name;

-- Consulta 2
EXPLAIN
SELECT p.id, SUM(oi.quantity)
FROM order_items oi
JOIN orders o
    ON o.id = oi.order_id
    AND o.status IN ('paid', 'shipped', 'delivered')
JOIN products p ON p.id = oi.product_id
JOIN category_product cp ON cp.product_id = p.id
GROUP BY p.id;
