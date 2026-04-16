-- Asset Management Normalization
-- Migration: support up to 10 Windows entries per PC/Laptop asset
-- Date: 2026-04-16

-- 1) (Recommended) create DB backup first.
--    Example:
--    mysqldump -u root -p asset_management_normalization > backup_before_asset_windows.sql

-- 2) Create child table for Windows rows (max 10 per asset).
CREATE TABLE IF NOT EXISTS asset_windows (
    window_id INT NOT NULL AUTO_INCREMENT,
    asset_id INT NOT NULL,
    window_index TINYINT UNSIGNED NOT NULL,
    window__os VARCHAR(100) NOT NULL,
    windows_serial VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (window_id),
    UNIQUE KEY uq_asset_windows_slot (asset_id, window_index),
    KEY idx_asset_windows_asset (asset_id),
    CONSTRAINT chk_asset_windows_index CHECK (window_index BETWEEN 1 AND 10),
    CONSTRAINT fk_asset_windows_asset
        FOREIGN KEY (asset_id) REFERENCES assets(asset_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) One-time backfill old single Windows value into slot 1.
--    This preserves existing data from assets.windows_key.
INSERT INTO asset_windows (asset_id, window_index, window__os, windows_serial)
SELECT
    a.asset_id,
    1 AS window_index,
    a.windows_key AS window__os,
    '' AS windows_serial
FROM assets a
LEFT JOIN asset_windows aw
    ON aw.asset_id = a.asset_id
    AND aw.window_index = 1
WHERE a.asset_type IN ('Desktop', 'Laptop')
  AND COALESCE(TRIM(a.windows_key), '') <> ''
  AND aw.window_id IS NULL;

-- 4) Optional checks.
-- SELECT * FROM asset_windows ORDER BY asset_id, window_index;
-- SELECT asset_id, COUNT(*) AS windows_rows FROM asset_windows GROUP BY asset_id HAVING COUNT(*) > 10;
