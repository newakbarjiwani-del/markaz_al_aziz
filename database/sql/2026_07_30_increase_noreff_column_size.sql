-- Increase NOREFF column size from CHAR(20) to CHAR(40) on live production
-- Applies to both finance and cashless ledgers.
-- NOTE: The original migration files (2026_06_30_000002 & 2026_07_06_190000)
-- already use CHAR(40) now, so fresh migrate/seeds will get the new size.
-- Run this ONLY if you have existing tables from a previous migration:

ALTER TABLE sccttran MODIFY NOREFF CHAR(40) NULL;
ALTER TABLE sccttran_cashless MODIFY NOREFF CHAR(40) NULL;
