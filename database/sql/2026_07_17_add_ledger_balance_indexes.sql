-- Composite indexes for balance aggregation queries on finance/cashless ledgers.
-- Safe to re-run on MySQL/MariaDB: skips indexes that already exist.
--
-- Production runbook:
--   1. Pre-flight: SHOW INDEX, check table size, EXPLAIN baseline GROUP BY query
--   2. Run during low-traffic window
--   3. mysql -u USER -p DATABASE < database/sql/2026_07_17_add_ledger_balance_indexes.sql
--   4. Post-check: SHOW INDEX + EXPLAIN
-- Rollback:
--   ALTER TABLE sccttran DROP INDEX sccttran_custid_trxdate_index;
--   ALTER TABLE sccttran_cashless DROP INDEX sccttran_cashless_custid_trxdate_index;
--   ALTER TABLE sccttran_cashless DROP INDEX sccttran_cashless_custid_wallet_index;

-- sccttran (CUSTID, TRXDATE)
SET @exists := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'sccttran'
      AND index_name = 'sccttran_custid_trxdate_index'
);
SET @sql := IF(@exists = 0,
    'ALTER TABLE sccttran ADD INDEX sccttran_custid_trxdate_index (CUSTID, TRXDATE)',
    'SELECT ''sccttran_custid_trxdate_index already exists'' AS note'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- sccttran_cashless (CUSTID, TRXDATE)
SET @exists := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'sccttran_cashless'
      AND index_name = 'sccttran_cashless_custid_trxdate_index'
);
SET @sql := IF(@exists = 0,
    'ALTER TABLE sccttran_cashless ADD INDEX sccttran_cashless_custid_trxdate_index (CUSTID, TRXDATE)',
    'SELECT ''sccttran_cashless_custid_trxdate_index already exists'' AS note'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- sccttran_cashless (CUSTID, wallet)
SET @exists := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'sccttran_cashless'
      AND index_name = 'sccttran_cashless_custid_wallet_index'
);
SET @sql := IF(@exists = 0,
    'ALTER TABLE sccttran_cashless ADD INDEX sccttran_cashless_custid_wallet_index (CUSTID, wallet)',
    'SELECT ''sccttran_cashless_custid_wallet_index already exists'' AS note'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
