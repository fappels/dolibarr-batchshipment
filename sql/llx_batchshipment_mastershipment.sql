-- Copyright (C) 2026		Francis Appels					<francis.appels@z-application.com>
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


CREATE TABLE llx_batchshipment_mastershipment(
	-- BEGIN MODULEBUILDER FIELDS
	rowid int AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	entity integer DEFAULT 1 NOT NULL, 
	ref varchar(128) NOT NULL, 
	label varchar(255), 
	value double, 
	weight double, 
	estimated_weight double, 
	weight_units int, 
	picking_progress double, 
	loading_progress double, 
	proof_uploaded int, 
	fk_soc integer, 
	fk_project integer, 
	description text, 
	note_public text, 
	note_private text, 
	date_creation datetime NOT NULL, 
	date_validation datetime, 
	date_pick datetime, 
	date_load datetime, 
	date_ship datetime, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	fk_user_valid integer, 
	fk_user_pick integer, 
	fk_user_load integer, 
	fk_user_ship integer, 
	last_main_doc varchar(255), 
	import_key varchar(14), 
	model_pdf varchar(255), 
	status int NOT NULL, 
	date_delivery datetime, 
	fk_shipping_method int, 
	tracking_number varchar(50),
	fk_entrepot int,
	stock_mode int,
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
