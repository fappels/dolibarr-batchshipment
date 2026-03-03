-- Copyright (C) 2026      Francis Appels <francis.appels@z-application.com>
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
create table llx_batchshipment_mastershipmentdet
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY, 
  fk_mastershipment         integer NOT NULL, 
  fk_product                integer,            -- product id 
  fk_entrepot               integer,            -- warehouse id 
  fk_productbatch           integer,            -- product lot stock id 
  fk_productlot             integer,            -- product lot id 
  qty                       real,               -- ordered Quantity 
  qty_pick                  real,               -- picked Quantity 
  qty_load                  real,               -- loaded Quantity 
  fk_commande               integer,            -- Corresponds with the order 
  fk_commande_line          integer,            -- Corresponds with the order line 
  fk_expedition             integer,            -- Corresponds with the shipment 
  fk_expedition_line        integer,            -- Corresponds with the shipment line 
  fk_shipmentpackage        integer,            -- Corresponds with the destination object (shipment package) 
  fk_shipmentpackage_line   integer,            -- shipmentpackage line id for update 
  comment                   varchar(255),       -- shipping comment 
  status                    smallint DEFAULT 0, -- 0 = not shipped, 1 = shipped 
  position                  integer  DEFAULT 0
)ENGINE=innodb;
