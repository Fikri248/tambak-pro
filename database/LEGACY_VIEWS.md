# Legacy database views

Before Laravel migrations became the schema source of truth, the local
`tambak_management_db` database contained the following manually created
views. They were documented before the local database was rebuilt and are
intentionally not recreated by migrations. Equivalent reporting can be built
with Eloquent queries when it is needed.

## `vw_current_pond_stock`

Returned positive pond-stock rows with location, batch, commodity, optional
vendor, purchase cost, and an estimated stock value.

```sql
SELECT
    ps.id AS stock_id,
    l.id AS location_id,
    l.code AS location_code,
    l.name AS location_name,
    cb.id AS batch_id,
    cb.batch_code,
    c.id AS commodity_id,
    c.code AS commodity_code,
    c.name AS commodity_name,
    v.id AS vendor_id,
    v.name AS vendor_name,
    ps.quantity,
    c.unit,
    cb.purchase_date,
    cb.unit_cost,
    ps.quantity * cb.unit_cost AS estimated_stock_value,
    ps.updated_at
FROM pond_stocks AS ps
JOIN locations AS l ON ps.location_id = l.id
JOIN commodity_batches AS cb ON ps.batch_id = cb.id
JOIN commodities AS c ON cb.commodity_id = c.id
LEFT JOIN vendors AS v ON cb.vendor_id = v.id
WHERE ps.quantity > 0;
```

## `vw_pond_commodity_summary`

Aggregated positive pond stock by location and commodity.

```sql
SELECT
    l.id AS location_id,
    l.name AS location_name,
    c.id AS commodity_id,
    c.name AS commodity_name,
    SUM(ps.quantity) AS total_quantity,
    c.unit
FROM pond_stocks AS ps
JOIN locations AS l ON ps.location_id = l.id
JOIN commodity_batches AS cb ON ps.batch_id = cb.id
JOIN commodities AS c ON cb.commodity_id = c.id
WHERE ps.quantity > 0
GROUP BY l.id, l.name, c.id, c.name, c.unit;
```
