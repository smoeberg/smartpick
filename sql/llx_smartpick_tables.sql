// Placeholder for llx_smartpick_tables.sql
CREATE TABLE llx_smartpick_queue (
    rowid INTEGER PRIMARY KEY AUTOINCREMENT,
    fk_commande INTEGER,
    fk_product INTEGER,
    product_ref TEXT,
    qty REAL,
    status TEXT DEFAULT "pending",
    tms DATETIME DEFAULT CURRENT_TIMESTAMP,
    fk_user_assigned INTEGER,
    fk_warehouse INTEGER,
    batch TEXT
);
