-- SmartPick Database Schema for Dolibarr

CREATE TABLE IF NOT EXISTS llx_smartpick_queue (
    rowid INTEGER PRIMARY KEY AUTO_INCREMENT,
    fk_commande INTEGER NOT NULL,
    fk_commandedet INTEGER DEFAULT 0,
    fk_product INTEGER NOT NULL,
    product_ref VARCHAR(128) NOT NULL,
    barcode VARCHAR(128) DEFAULT NULL,
    label VARCHAR(255) NOT NULL,
    qty_to_pick DOUBLE(24,8) NOT NULL DEFAULT 1.0,
    qty_picked DOUBLE(24,8) NOT NULL DEFAULT 0.0,
    fk_warehouse INTEGER DEFAULT 0,
    loc_rack VARCHAR(64) DEFAULT '',
    loc_bin VARCHAR(64) DEFAULT '',
    status VARCHAR(32) DEFAULT 'pending', -- pending, picking, picked, partial, hold
    batch_id VARCHAR(64) DEFAULT NULL,
    fk_user_assigned INTEGER DEFAULT 0,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_smartpick_queue_fk_commande (fk_commande),
    INDEX idx_smartpick_queue_batch (batch_id),
    INDEX idx_smartpick_queue_status (status)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_smartpick_shipments (
    rowid INTEGER PRIMARY KEY AUTO_INCREMENT,
    fk_commande INTEGER NOT NULL,
    shipmondo_shipment_id VARCHAR(128) NOT NULL,
    carrier_code VARCHAR(64) DEFAULT NULL,
    pkg_no VARCHAR(128) DEFAULT NULL,
    tracking_url TEXT DEFAULT NULL,
    label_url TEXT DEFAULT NULL,
    status VARCHAR(32) DEFAULT 'created',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_smartpick_shipments_fk_commande (fk_commande)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_smartpick_user_logs (
    rowid INTEGER PRIMARY KEY AUTO_INCREMENT,
    fk_user INTEGER NOT NULL,
    fk_product INTEGER NOT NULL,
    qty_picked DOUBLE(24,8) NOT NULL DEFAULT 1.0,
    weight_lifted_kg DOUBLE(24,8) NOT NULL DEFAULT 0.0,
    duration_sec INTEGER DEFAULT 0,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_smartpick_user_logs_user (fk_user),
    INDEX idx_smartpick_user_logs_date (date_creation)
) ENGINE=innodb;
