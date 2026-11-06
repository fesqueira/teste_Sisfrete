-- Consulta 1: total de pedidos e receita por cliente nos últimos 12 meses
SELECT
    u.id,
    u.name,
    COUNT(o.id) AS total_pedidos,
    COALESCE(SUM(CASE WHEN o.status IN ('paid', 'shipped', 'delivered') THEN o.total END), 0) AS receita
FROM users u
LEFT JOIN orders o
    ON o.user_id = u.id
    AND o.created_at >= NOW() - INTERVAL 12 MONTH
GROUP BY u.id, u.name
ORDER BY receita DESC;

-- Consulta 2: os 3 produtos mais vendidos de cada categoria
WITH vendas AS (
    SELECT
        c.id AS categoria_id,
        c.name AS categoria,
        p.id AS produto_id,
        p.name AS produto,
        SUM(oi.quantity) AS quantidade_vendida,
        SUM(oi.quantity * oi.price) AS receita
    FROM order_items oi
    JOIN orders o
        ON o.id = oi.order_id
        AND o.status IN ('paid', 'shipped', 'delivered')
    JOIN products p ON p.id = oi.product_id
    JOIN category_product cp ON cp.product_id = p.id
    JOIN categories c ON c.id = cp.category_id
    GROUP BY c.id, c.name, p.id, p.name
),
ranking AS (
    SELECT
        vendas.*,
        ROW_NUMBER() OVER (
            PARTITION BY categoria_id
            ORDER BY quantidade_vendida DESC, receita DESC
        ) AS posicao
    FROM vendas
)
SELECT categoria, posicao, produto, quantidade_vendida, receita
FROM ranking
WHERE posicao <= 3
ORDER BY categoria, posicao;
