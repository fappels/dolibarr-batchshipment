-- Copyright (C) 2021      Francis Appels <francis.appels@z-application.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

ALTER TABLE llx_batchshipment_mastershipmentdet ADD INDEX idx_batchshipment_mastershipmentdet_fk_mastershipment (fk_mastershipment);
ALTER TABLE llx_batchshipment_mastershipmentdet ADD CONSTRAINT fk_batchshipment_mastershipmentdet_fk_mastershipment FOREIGN KEY (fk_mastershipment) REFERENCES llx_batchshipment_mastershipment (rowid);
ALTER TABLE llx_batchshipment_mastershipmentdet ADD INDEX idx_batchshipment_mastershipmentdet_fk_commande (fk_commande);
ALTER TABLE llx_batchshipment_mastershipmentdet ADD INDEX idx_batchshipment_mastershipmentdet_fk_commande_line (fk_commande_line);
ALTER TABLE llx_batchshipment_mastershipmentdet ADD INDEX idx_batchshipment_mastershipmentdet_fk_expedition (fk_expedition);
ALTER TABLE llx_batchshipment_mastershipmentdet ADD INDEX idx_batchshipment_mastershipmentdet_fk_expedition_line (fk_expedition_line);
ALTER TABLE llx_batchshipment_mastershipmentdet ADD INDEX idx_batchshipment_mastershipmentdet_fk_shipmentpackage (fk_shipmentpackage);
ALTER TABLE llx_batchshipment_mastershipmentdet ADD UNIQUE INDEX idx_batchshipment_mastershipmentdet_fk_product_fk_product_batch (fk_product, fk_product_batch);
