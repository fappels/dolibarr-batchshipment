--
-- Script containing ddl updates
--

ALTER TABLE llx_batchshipment_mastershipment ADD COLUMN fk_entrepot int, ADD COLUMN stock_mode int;
