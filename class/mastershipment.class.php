<?php
/* Copyright (C) 2017       Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024  Frédéric France          <frederic.france@free.fr>
 * Copyright (C) 2026		Francis Appels					<francis.appels@z-application.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/mastershipment.class.php
 * \ingroup     batchshipment
 * \brief       This file is a CRUD class file for MasterShipment (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
dol_include_once('/commande/class/commande.class.php');
dol_include_once('/expedition/class/expedition.class.php');

/**
 * Class for MasterShipment
 */
class MasterShipment extends CommonObject
{
	/**
	 * @var string 		ID of module.
	 */
	public $module = 'batchshipment';

	/**
	 * @var string 		ID to identify managed object.
	 */
	public $element = 'mastershipment';

	/**
	 * @var string 		Name of table without prefix where object is stored. This is also the key used for extrafields management (so extrafields know the link to the parent table).
	 */
	public $table_element = 'batchshipment_mastershipment';

	/**
	 * @var string 		If permission must be checkec with hasRight('batchshipment', 'read') and not hasright('mymodyle', 'mastershipment', 'read'), you can uncomment this line
	 */
	//public $element_for_permission = 'batchshipment';

	/**
	 * @var string 		String with name of icon for mastershipment. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'mastershipment@batchshipment' if picto is file 'img/object_mastershipment.png'.
	 */
	public $picto = 'fa-file';

	/**
	 * @var int<0,1>	Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var int<0,1>|string|null  	Does this object support multicompany module ?
	 * 								0=No test on entity, 1=Test with field entity in local table, 'field@table'=Test entity into the field@table (example 'fk_soc@societe')
	 */
	public $ismultientitymanaged = 1;

	/** @var array */
	public $usedLotBatch = array();

	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_PICKED = 2;
	const STATUS_SHIPMENTONPROCESS = 3;
	const STATUS_CLOSED = 4;
	const STATUS_CANCELED = 9;

	/**
	 *  'type' field format:
	 *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *  	'select' (list of values are in 'options'. for integer list of values are in 'arrayofkeyval'),
	 *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:SortField]]]]]]',
	 *  	'chkbxlst:...',
	 *  	'varchar(x)',
	 *  	'text', 'text:none', 'html',
	 *   	'double(24,8)', 'real', 'price', 'stock',
	 *  	'date', 'datetime', 'timestamp', 'duration',
	 *  	'boolean', 'checkbox', 'radio', 'array',
	 *  	'email', 'phone', 'url', 'password', 'ip'
	 *		Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'length' the length of field. Example: 255, '24,8'
	 *  'label' the translation key.
	 *  'langfile' the key of the language file for translation.
	 *  'alias' the alias used into some old hard coded SQL requests
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form (not create). 5=Visible on list and view form (not create/not update). 6=visible on list and update/view form (not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'placeholder' to set the placeholder of a varchar field.
	 *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code like the constructor of the class.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if you need to validate the field with $this->validateField(). Need MAIN_ACTIVATE_VALIDATION_RESULT.
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	/**
	 * @inheritdoc
	 * Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		"rowid" => array("type" => "int", "label" => "TechnicalID", "enabled" => "1", 'position' => 10, 'notnull' => 1, "visible" => "0",),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>'1', 'visible'=>0, 'notnull'=> 1, 'default'=>1, 'index'=>1, 'position'=>15),
		"ref" => array("type" => "varchar(128)", "label" => "Ref", "enabled" => "1", 'position' => 20, 'notnull' => 1, 'default'=>'(PROV)', "visible" => "5", "csslist" => "tdoverflowmax150", "showoncombobox" => "1",),
		"label" => array("type" => "varchar(255)", "label" => "Label", "enabled" => "1", 'position' => 25, 'notnull' => 0, "visible" => "1", "alwayseditable" => "1", "css" => "minwidth300", "cssview" => "wordbreak", "csslist" => "tdoverflowmax150",),
		"value" => array("type" => "double", "label" => "Value", "enabled" => "1", 'position' => 30, 'notnull' => 0, "visible" => "-4",),
		"weight" => array("type" => "double", "label" => "Weight", "enabled" => "1", 'position' => 35, 'notnull' => 0, "visible" => "-4",),
		"estimated_weight" => array("type" => "double", "label" => "Estimatedweight", "enabled" => "0", 'position' => 40, 'notnull' => 0, "visible" => "-1",),
		"weight_units" => array("type" => "int", "label" => "Weightunits", "enabled" => "0", 'position' => 45, 'notnull' => 0, "visible" => "-1",),
		"picking_progress" => array("type" => "double", "label" => "Pickingprogress", "enabled" => "0", 'position' => 50, 'notnull' => 0, "visible" => "-4",),
		"loading_progress" => array("type" => "double", "label" => "Loadingprogress", "enabled" => "0", 'position' => 55, 'notnull' => 0, "visible" => "-4",),
		"proof_uploaded" => array("type" => "int", "label" => "Proofuploaded", "enabled" => "0", 'position' => 60, 'notnull' => 0, "visible" => "-4",),
		"fk_soc" => array("type" => "integer:Societe:societe/class/societe.class.php", "label" => "ThirdParty", "picto" => "company", "enabled" => "1", 'position' => 65, 'notnull' => 0, "visible" => "-1", "css" => "maxwidth500 widthcentpercentminusxx", "csslist" => "tdoverflowmax150",),
		"fk_project" => array("type" => "integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)", "label" => "Project", "picto" => "project", "enabled" => "1", 'position' => 70, 'notnull' => 0, "visible" => "-1", "css" => "maxwidth500 widthcentpercentminusxx", "csslist" => "tdoverflowmax150",),
		"description" => array("type" => "text", "label" => "Description", "enabled" => "1", 'position' => 75, 'notnull' => 0, "visible" => "-1",),
		"note_public" => array("type" => "text", "label" => "NotePrivate", "enabled" => "1", 'position' => 80, 'notnull' => 0, "visible" => "0", "cssview" => "wordbreak",),
		"note_private" => array("type" => "text", "label" => "NotePublic", "enabled" => "1", 'position' => 85, 'notnull' => 0, "visible" => "0", "cssview" => "wordbreak",),
		"date_creation" => array("type" => "datetime", "label" => "DateCreation", "enabled" => "1", 'position' => 90, 'notnull' => 1, "visible" => "-5",),
		"date_validation" => array("type" => "datetime", "label" => "DateValidation", "enabled" => "1", 'position' => 95, 'notnull' => 0, "visible" => "-5",),
		"date_pick" => array("type" => "datetime", "label" => "Datepick", "enabled" => "1", 'position' => 100, 'notnull' => 0, "visible" => "-5",),
		"date_load" => array("type" => "datetime", "label" => "Dateload", "enabled" => "getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING')", 'position' => 100, 'notnull' => 0, "visible" => "-5",),
		"date_ship" => array("type" => "datetime", "label" => "Dateship", "enabled" => "1", 'position' => 105, 'notnull' => 0, "visible" => "-5",),
		"tms" => array("type" => "timestamp", "label" => "DateModification", "enabled" => "1", 'position' => 110, 'notnull' => 0, "visible" => "0",),
		"fk_user_creat" => array("type" => "integer:User:user/class/user.class.php", "label" => "UserAuthor", "picto" => "user", "enabled" => "1", 'position' => 115, 'notnull' => 1, "visible" => "-5", "css" => "maxwidth500 widthcentpercentminusxx", "csslist" => "tdoverflowmax150",),
		"fk_user_modif" => array("type" => "integer:User:user/class/user.class.php", "label" => "UserModif", "picto" => "user", "enabled" => "1", 'position' => 120, 'notnull' => -1, "visible" => "-5", "css" => "maxwidth500 widthcentpercentminusxx", "csslist" => "tdoverflowmax150",),
		"fk_user_valid" => array("type" => "integer:User:user/class/user.class.php", "label" => "UserValidation", "picto" => "user", "enabled" => "1", 'position' => 125, 'notnull' => 0, "visible" => "-5", "css" => "maxwidth500 widthcentpercentminusxx", "csslist" => "tdoverflowmax150",),
		"fk_user_pick" => array("type" => "integer:User:user/class/user.class.php", "label" => "UserPick", "picto" => "user", "enabled" => "1", 'position' => 127, 'notnull' => 0, "visible" => "-5", "css" => "maxwidth500 widthcentpercentminusxx", "csslist" => "tdoverflowmax150",),
		"fk_user_load" => array("type" => "integer:User:user/class/user.class.php", "label" => "UserLoad", "picto" => "user", "enabled" => "getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING')", 'position' => 130, 'notnull' => 0, "visible" => "-5", "css" => "maxwidth500 widthcentpercentminusxx", "csslist" => "tdoverflowmax150",),
		"fk_user_ship" => array("type" => "integer:User:user/class/user.class.php", "label" => "UserShip", "picto" => "user", "enabled" => "1", 'position' => 135, 'notnull' => 0, "visible" => "-5", "css" => "maxwidth500 widthcentpercentminusxx", "csslist" => "tdoverflowmax150",),
		"last_main_doc" => array("type" => "varchar(255)", "label" => "Lastmaindoc", "enabled" => "1", 'position' => 140, 'notnull' => 0, "visible" => "0",),
		"import_key" => array("type" => "varchar(14)", "label" => "ImportId", "enabled" => "1", 'position' => 900, 'notnull' => 0, "visible" => "-2",),
		"model_pdf" => array("type" => "varchar(255)", "label" => "Modelpdf", "enabled" => "1", 'position' => 150, 'notnull' => 0, "visible" => "0",),
		"status" => array("type" => "int", "label" => "Status", "enabled" => "1", 'position' => 500, 'notnull' => 1, "visible" => "5", 'arrayofkeyval'=>array('0'=>'Draft', '1'=>'Validated', '2'=>'Picked', '3'=>'Loaded', '4'=>'Closed', '9'=>'Canceled')),
		"date_delivery" => array("type" => "datetime", "label" => "DeliveryDate", "enabled" => "1", 'position' => 160, 'notnull' => 0, "visible" => "-4",),
		"fk_shipping_method" => array("type" => "int", "label" => "Shippingmethod", "enabled" => "1", 'position' => 165, 'notnull' => 0, "visible" => "0", "css" => "maxwidth500 widthcentpercentminusxx",),
		"tracking_number" => array("type" => "varchar(50)", "label" => "Trackingnumber", "enabled" => "1", 'position' => 170, 'notnull' => 0, "visible" => "-4",),
		"fk_entrepot" => array("type" => "integer:Entrepot:product/stock/class/entrepot.class.php", "label" => "Warehouse", "enabled" => "1", 'position' => 175, 'notnull' => -1, "visible" => 1),
		"stock_mode" => array("type" => "int", "label" => "StockMode", "enabled" => "1", 'position' => 180, 'notnull' => 0, "visible" => "-1",'arrayofkeyval'=>array('0'=>'Off', '1'=>'PartualStock', '2'=>'FullStock',)),
	);

	/**
	 * Label of mastershipment
	 * @var string
	 */
	public $label;
	/**
	 * Value
	 * @var float
	 */
	public $value;
	/**
	 * Weight
	 * @var float
	 */
	public $weight;
	/**
	 * Estimated weight
	 * @var float
	 */
	public $estimated_weight;
	/**
	 * Weight units
	 * @var string
	 */
	public $weight_units;
	/**
	 * Picking progress
	 * @var float
	 */
	public $picking_progress;
	/**
	 * Loading progress
	 * @var float
	 */
	public $loading_progress;
	/**
	 * Proof uploaded
	 * @var float
	 */
	public $proof_uploaded;
	/**
	 * Third party used when all linked orders are from same third party. Otherwise, fk_soc is null
	 * @var int
	 */
	public $fk_soc;
	/**
	 * Description
	 * @var string
	 */
	public $description;
	/**
	 * Picking date
	 * @var int
	 */
	public $date_pick;
	/**
	 * Loading date
	 * @var int
	 */
	public $date_load;
	/**
	 * Shipping date
	 * @var int
	 */
	public $date_ship;
	/**
	 * User that validated the object
	 * @var int
	 */
	public $fk_user_valid;
	/**
	 * User that picked the object
	 * @var int
	 */
	public $fk_user_pick;
	/**
	 * User that loaded the object
	 * @var int
	 */
	public $fk_user_load;
	/**
	 * User that shipped the object
	 * @var int
	 */
	public $fk_user_ship;
	/**
	 * Delivery date
	 * @var int
	 */
	public $date_delivery;
	/**
	 * Shipping method
	 * @var int
	 */
	public $fk_shipping_method;
	/**
	 * Tracking number
	 * @var string
	 */
	public $tracking_number;
	/**
	 * Warehouse
	 * @var int
	 */
	public $fk_entrepot;
	/**
	 * Stock mode
	 * @var int
	 */
	public $stock_mode;

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'batchshipment_mastershipmentdet';

	/**
	 * @var string    Field name with ID of parent key if this object has a parent, Or Field name of in child tables to link to this record.
	 */
	public $fk_element = 'fk_mastershipment';

	/**
	 * @var string    Name of subtable class that manage subtable lines
	 */
	public $class_element_line = 'MasterShipmentline';

	/**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array();

	/**
	 * @var array    List of child tables. To know object to delete on cascade.
	 *               If name matches '@ClassName:FilePathClass:ParentFkFieldName' (the recommended mode) it will
	 *               call method ClassName->deleteByParentField(parentId, 'ParentFkFieldName') to fetch and delete child object.
	 *               Using an array like childtables should not be implemented because a child may have other child, so we must only use the method that call deleteByParentField().
	 */
	protected $childtablesoncascade = array('batchshipment_mastershipmentdet');

	/**
	 * @var MasterShipmentLine[]     Array of subtable lines
	 */
	public $lines = array();



	/**
	 * Constructor
	 *
	 * @param	DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;

		if (isModEnabled('shipmentpackage')) {
			dol_include_once('/shipmentpackage/class/shipmentpackage.class.php');
		}

		if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->hasRight('batchshipment', 'mastershipment', 'read')) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param	User		$user		User that creates
	 * @param	int<0,1> 	$notrigger	0=launch triggers after, 1=disable triggers
	 * @return	int<-1,max>				Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		$result = $this->createCommon($user, $notrigger);

		// uncomment lines below if you want to validate object after creation
		// if ($result > 0) {
		// $this->fetch($this->id); // needed to retrieve some fields (ie date_creation for masked ref)
		// $resultupdate = $this->validate($user, $notrigger);
		// if ($resultupdate < 0) { return $resultupdate; }
		// }

		return $result;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param	User 	$user		User that creates
	 * @param	int 	$fromid		Id of object to clone
	 * @return	self|int<-1,-1>		New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && !empty($object->table_element_line)) {
			$object->fetchLines();
		}

		// get lines so they will be clone
		//foreach($this->lines as $line)
		//	$line->fetch_optionals();

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		if (property_exists($object, 'ref')) {
			$object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_".$object->ref : $this->fields['ref']['default'];
		}
		if (property_exists($object, 'label')) {
			$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
		}
		if (property_exists($object, 'status')) {
			$object->status = self::STATUS_DRAFT;
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'date_modification')) {
			$object->date_modification = null;
		}
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
					//var_dump($key);
					//var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->setErrorsFromObject($object);
		}

		if (!$error) {
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0) {
				$error++;
			}
		}

		if (!$error) {
			// copy external contacts if same company
			if (!empty($object->socid) && property_exists($this, 'fk_soc') && $this->fk_soc == $object->socid) {
				if ($this->copy_linked_contact($object, 'external') < 0) {
					$error++;
				}
			}
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

		/**
	 * add line to mastershipment
	 *
	 * @param user		$user				User that do the action
	 * @param int		$fk_product			product id
	 * @param float		$qty				$qty of product id in container
	 * @param int		$fk_commande		order id
	 * @param int		$fk_commande_line	order line id
	 * @param string	$comment			line comment
	 *
	 * @return	int NOK < 0 > lineid
	 */
	public function addLine($user, $fk_product, $qty, $fk_commande, $fk_commande_line, $comment = '')
	{
		$result = 0;
		$error = 0;

		$line = new MasterShipmentLine($this->db);
		$line->fk_mastershipment = $this->id;
		$line->fk_product = $fk_product;
		$line->qty = $qty;
		$line->fk_commande = $fk_commande;
		$line->fk_commande_line = $fk_commande_line;
		if (!empty($comment)) $line->comment = $comment;


		$result = $line->create($user);
		if ($result < 0) $error++;

		if (!$error) {
			$this->update($user, true); // recalc weight and value
			return $result;
		} else {
			$this->error = $line->error;
			return $result;
		}
	}

		/**
	 * update line mastershipment
	 *
	 * @param user		$user				User that do the action
	 * @param int		$lineid				line id
	 * @param int		$status				status of line
	 * @param int		$fk_product			product id
	 * @param float		$qty				qty of product to pick in mastershipment
	 * @param float		$qty_pick			picked qty of product
	 * @param int		$fk_commande		order id
	 * @param int		$fk_entrepot		warehouse id
	 * @param float		$qty_load			loaded qty of product
	 * @param string	$comment			receive comment
	 * @param int		$fk_productbatch	product lot
	 * @return	int NOK < 0 > lineid
	 */
	public function updateLine($user, $lineid, $status, $fk_product, $qty, $qty_pick, $fk_commande, $fk_entrepot = null, $qty_load = 0, $comment = '', $fk_productbatch = null)
	{
		$result = 0;

		$line = new MasterShipmentLine($this->db);
		$result = $line->fetch($lineid);

		if ($result > 0) {
			$line->fk_product = $fk_product;
			$line->fk_entrepot = $fk_entrepot;
			$line->qty = $qty;
			$line->qty_pick = $qty_pick;
			$line->fk_commande = $fk_commande;
			$line->status = $status;
			$line->fk_productbatch = $fk_productbatch;
			if (isset($qty_load)) $line->qty_load = $qty_load;
			if (isset($comment)) $line->comment = $comment;

			$lineid = $line->update($user);
		}

		if ($result > 0 && $lineid > 0) {
			$this->update($user, true); // recalc weight and value
			return $lineid;
		} else {
			$this->error = $line->error;
			return $result || $lineid;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param	int    		$id   			Id object
	 * @param	string 		$ref  			Ref
	 * @param	int<0,1>	$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @param	int<0,1>	$nolines		0=Default to load lines, 1=No lines
	 * @return	int<-1,1>					Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $noextrafields = 0, $nolines = 0)
	{
		$result = $this->fetchCommon($id, $ref, '', $noextrafields);
		if ($result > 0 && !empty($this->table_element_line) && empty($nolines)) {
			$this->fetchLines($noextrafields);
		}
		// Tracking url
		$this->getUrlTrackingStatus($this->tracking_number);
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @param	int<0,1>	$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @return 	int<-1,1>					Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines($noextrafields = 0)
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon('', $noextrafields);
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 * Using a fetchAll() with limit = 0 is a very bad practice. Instead try to forge yourself an optimized SQL request with
	 * your own loop with start and stop pagination.
	 *
	 * @param	string		$sortorder	Sort Order
	 * @param	string		$sortfield	Sort field
	 * @param	int<0,max>	$limit		Limit the number of lines returned
	 * @param	int<0,max>	$offset		Offset
	 * @param	string		$filter		Filter as an Universal Search string.
	 *                                  Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
	 * @param	string		$filtermode	No longer used
	 * @return	array<int,self>|int<-1,-1>	 <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 1000, $offset = 0, string $filter = '', $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		if (!empty($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
			$sql .= " LEFT JOIN ".$this->db->prefix().$this->table_element."_extrafields as te ON te.fk_object = t.rowid";
		}
		if (!empty($this->ismultientitymanaged) && (int) $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->element).")";
		} elseif (preg_match('/^\w+@\w+$/', (string) $this->ismultientitymanaged)) {
			$tmparray = explode('@', (string) $this->ismultientitymanaged);
			$sql .= " LEFT JOIN ".$this->db->prefix().$tmparray[1]." as pt ON t.".$this->db->sanitize($tmparray[0])." = pt.rowid";
			$sql .= " WHERE pt.entity IN (".getEntity($this->element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				if (!empty($record->isextrafieldmanaged)) {
					$record->fetch_optionals();
				}

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param	User		$user		User that modifies
	 * @param	int<0,1>	$notrigger	0=launch triggers after, 1=disable triggers
	 * @return	int<-1,1>				Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $conf;

		$this->fetchLines();
		//$weightArray = $this->getTotalWeightVolume();
		$this->value = $this->getTotalValue();
		$result = $this->updateCommon($user, $notrigger);
		if ($result > 0) {
			foreach ($this->lines as $line) {
				// also update shipping method, tracking number and ship date in Shipments
				if ($line->fk_expedition > 0) {
					$shipment = new Expedition($this->db);
					$shipment->fetch($line->fk_expedition);
					$shipment->shipping_method_id = $this->fk_shipping_method;
					$shipment->tracking_number = $this->tracking_number;
					$shipment->date_delivery = $this->date_delivery;
					$result = $shipment->update($user);
					if ($result < 0) {
						$this->errors[] = $shipment->error;
					}
				}
			}
			// sort mastershipment lines by product
			if ($result > 0) {
				$this->sortLines($user, array(array('sortfield' => 'fk_product', 'sortorder' => 'ASC'), array('sortfield' => 'qty', 'sortorder' => 'DESC')));
			}
		}
		return $result;
	}

	/**
	 * Delete object in database
	 *
	 * @param	User		$user		User that deletes
	 * @param	int<0,1> 	$notrigger	0=launch triggers, 1=disable triggers
	 * @return	int<-1,1>				Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Delete a line of object in database
	 *
	 *	@param	User		$user		User that delete
	 *  @param	int			$idline		Id of line to delete
	 *	@param	int			$fk_commande	lines linked to order to delete
	 *  @param	int<0,1>	$notrigger	0=launch triggers after, 1=disable triggers
	 *  @return	int<-2,1>				>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline = null, $fk_commande = null, $notrigger = 0)
	{
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}
		$res = 0;
		$line = new MasterShipmentLine($this->db);
		if (!$idline) {
			if ($fk_commande) {
				$result = $line->fetchAll('', '', 0, 0, 'fk_commande:=:' . $fk_commande);
				if (is_array($result)) {
					foreach ($result as $line) {
						$idline = $line->id;
						$res = $this->deleteLineCommon($user, $idline, $notrigger);
						if ($res < 0) return $res;
					}
				}
			}
		} else {
			$res = $this->deleteLineCommon($user, $idline, $notrigger);
		}

		if ($res > 0) {
			$this->update($user, true); // recalc weight and value
		}

		return $res;
	}


	/**
	 *	Validate object
	 *
	 *	@param	User		$user		User making status change
	 *  @param	int<0,1>	$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return	int<-1,1>				Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate($user, $notrigger = 0)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::validate action abandoned: already validated", LOG_WARNING);
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment', 'mastershipment', 'write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment', 'mastershipment_advance', 'validate')))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef();
		} else {
			$num = $this->ref;
		}
		$this->newref = $num;

		if (!empty($num)) {
			// Validate
			$sql = "UPDATE ".$this->db->prefix().$this->table_element;
			$sql .= " SET ";
			if (!empty($this->fields['ref'])) {
				$sql .= " ref = '".$this->db->escape($num)."',";
			}
			$sql .= " status = ".self::STATUS_VALIDATED;
			if (!empty($this->fields['date_validation'])) {
				$sql .= ", date_validation = '".$this->db->idate($now)."'";
			}
			if (!empty($this->fields['fk_user_valid'])) {
				$sql .= ", fk_user_valid = ".((int) $user->id);
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('MASTERSHIPMENT_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE '.$this->db->prefix()."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'mastershipment/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'mastershipment/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.$this->db->prefix()."ecm_files set filepath = 'mastershipment/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'mastershipment/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->batchshipment->dir_output.'/mastershipment/'.$oldref;
				$dirdest = $conf->batchshipment->dir_output.'/mastershipment/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->batchshipment->dir_output.'/mastershipment/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
							$dirsource = $fileentry['path'].'/'.$dirsource;
							$dirdest = $fileentry['path'].'/'.$dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->ref = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Validate picked
	 *
	 *	@param	User		$user		User making status change
	 *  @param	int<0,1>	$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return	int<-1,1>				Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validatePicked($user, $notrigger = 0)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_PICKED) {
			dol_syslog(get_class($this)."::validatePicked action abandoned: already picked", LOG_WARNING);
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment', 'mastershipment', 'write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment', 'mastershipment_advance', 'validate')))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();


		// Validate picked
		$sql = "UPDATE ".$this->db->prefix().$this->table_element;
		$sql .= " SET ";
		$sql .= " status = ".self::STATUS_PICKED;
		if (!empty($this->fields['fk_user_pick'])) {
			$sql .= ", fk_user_pick = ".((int) $user->id);
		}
		if (!empty($this->fields['date_pick'])) {
			$sql .= ", date_pick = '".$this->db->idate($now)."'";
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::validatePicked()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('MASTERSHIPMENT_VALIDATE_PICKED', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// when two stage picking is disabled, there is no separate loading step, so create shipments now
		if (!$error && !$twoStagePicking) {
			$result = $this->createShipments($user, $notrigger, 'qty_pick');
			if ($result < 0) {
				$error++;
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = $newStatus;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

		/**
	 *	validate object loaded
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validateLoaded($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_SHIPMENTONPROCESS) {
			dol_syslog(get_class($this)."::ship action abandonned: already shipment on process", LOG_WARNING);
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment', 'mastershipment', 'write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment', 'mastershipment_advance', 'validate')))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_SHIPMENTONPROCESS;
		if (!empty($this->fields['date_load'])) {
			$sql .= ", date_load = '".$this->db->idate($now)."'";
		}
		if (!empty($this->fields['fk_user_load'])) {
			$sql .= ", fk_user_load = ".((int) $user->id);
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('MASTERSHIPMENT_SHIPMENTONPROCESS', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// create shipments (skipped when two stage picking is disabled: shipments are already created on picking)
		if (!$error && getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING')) {
			$result = $this->createShipments($user, $notrigger);
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			$this->status = self::STATUS_SHIPMENTONPROCESS;
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Create (and validate) a shipment per order from the master shipment lines, and link
	 *	each master shipment line to its resulting shipment line.
	 *	Requires $this->lines to be loaded.
	 *
	 *	@param		User	$user     		User creating the shipments
	 *  @param		int		$notrigger		1=Does not execute triggers, 0=execute triggers
	 *  @param		string	$qtyfield		Line property to use as shipped qty: 'qty_load' (default) or 'qty_pick'
	 *	@return  	int						<0 if KO, >0 if OK
	 */
	public function createShipments($user, $notrigger = 0, $qtyfield = 'qty_load')
	{
		global $conf;

		$error = 0;

		// get packages
		$shipment = array();
		$order = array();
		$shipmentLineIds = array();
		// create shipments
		foreach ($this->lines as $line) {
			/** @var MasterShipmentLine $line */
			$toWarehouse = $line->fk_entrepot;
			$qtyToShip = $qtyfield == 'qty_pick' ? $line->qty_pick : $line->qty_load;

			if (empty($shipment[$line->fk_commande])) {
				$order[$line->fk_commande] = new Commande($this->db);
				$order[$line->fk_commande]->fetch($line->fk_commande);
				$shipment[$line->fk_commande] = new Expedition($this->db);
				$shipment[$line->fk_commande]->origin_id = $line->fk_commande;
				$shipment[$line->fk_commande]->origin_type = 'commande';
				$shipment[$line->fk_commande]->fk_project = $this->fk_project;
				$shipment[$line->fk_commande]->socid = $order[$line->fk_commande]->socid;
				$shipment[$line->fk_commande]->ref_customer = $order[$line->fk_commande]->ref_client;
				$shipment[$line->fk_commande]->date_delivery = $this->date_delivery;
				$shipment[$line->fk_commande]->shipping_method_id = $this->fk_shipping_method;
				$shipment[$line->fk_commande]->tracking_number = $this->tracking_number;
				$shipment[$line->fk_commande]->note_private = $order[$line->fk_commande]->note_private;
				$shipment[$line->fk_commande]->note_public = $order[$line->fk_commande]->note_public;
				$shipment[$line->fk_commande]->fk_incoterms = $order[$line->fk_commande]->fk_incoterms;
				$shipment[$line->fk_commande]->location_incoterms = $order[$line->fk_commande]->location_incoterms;
				$result = $shipment[$line->fk_commande]->create($user);
				if ($result <= 0) {
					if ($result == 0) {
						$this->error = 'Shipment not created';
					} else {
						$this->error = $shipment[$line->fk_commande]->error;
					}
					$error++;
				}
			}
			if (!$error) {
				$shipmentLineId = $shipment[$line->fk_commande]->create_line($toWarehouse, $line->fk_commande_line,  $qtyToShip);
				if ($shipmentLineId <= 0) {
					if ($shipmentLineId == 0) {
						$this->error = 'Shipment line not created';
					} else {
						$this->error = $shipment[$line->fk_commande]->error;
					}
					$error++;
				} else {
					$shipmentLineIds[$line->id] = $shipmentLineId;
				}
				if (!empty($conf->productbatch->enabled) && $line->fk_productbatch > 0) {
					dol_include_once('/expedition/class/expeditionlinebatch.class.php');
					dol_include_once('/product/stock/class/productbatch.class.php');
					dol_include_once('/product/stock/class/productlot.class.php');
					$productBatch = new Productbatch($this->db);
					$productBatch->fetch($line->fk_productbatch);
					$productLot = new Productlot($this->db);
					$productLot->fetch(null, $line->fk_product, $productBatch->batch);
					$expeditionLineBatch = new ExpeditionLineBatch($this->db);
					$expeditionLineBatch->sellby = $productLot->sellby;
					$expeditionLineBatch->eatby = $productLot->eatby;
					$expeditionLineBatch->batch = $productBatch->batch;
					$expeditionLineBatch->qty = $qtyToShip;
					$expeditionLineBatch->fk_origin_stock = $productBatch->fk_product_stock;
					$expeditionLineBatch->create($shipmentLineId);
				}
			}
		}

		if (!$error) {
			// validate shipments
			foreach ($this->lines as $line) {
				/** @var MasterShipmentLine $line */
				if ($shipment[$line->fk_commande]->status == 0) {
					// validate shipment
					if (!empty($line->fk_commande)) {
						$result = $shipment[$line->fk_commande]->valid($user);

						if ($result < 0) {
							$error++;
							$this->errors = $shipment[$line->fk_commande]->errors;
							break;
						}
						// link shipment to mastershipment
						if ($this->add_object_linked('shipping', $shipment[$line->fk_commande]->id, $user) < 0) {
							$error++;
							break;
						}
					}
				}
				$line->fk_expedition = $shipment[$line->fk_commande]->id;
				$line->fk_expedition_line = $shipmentLineIds[$line->id];
				$result = $line->update($user, $notrigger);
				if ($result < 0) {
					$error++;
					$this->errors = $line->errors;
					break;
				}
			}
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}


	/**
	 *	Set draft status
	 *
	 *	@param	User		$user		Object user that modify
	 *  @param	int<0,1>	$notrigger	1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<0,1>				Return integer <0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment','batchshipment_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'BATCHSHIPMENT_MASTERSHIPMENT_UNVALIDATE');
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User		$user		Object user that modify
	 *  @param	int<0,1>	$notrigger	1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<-1,1>				Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment','batchshipment_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'BATCHSHIPMENT_MASTERSHIPMENT_CANCEL');
	}

		/**
	 *	close object loaded
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function close($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_CLOSED) {
			dol_syslog(get_class($this)."::ship action abandonned: already shipment on process", LOG_WARNING);
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment','batchshipment_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET status = ".self::STATUS_CLOSED;
		if (!empty($this->fields['date_ship'])) {
			$sql .= ", date_ship = '".$this->db->idate($now)."'";
		}
		if (!empty($this->fields['fk_user_ship'])) {
			$sql .= ", fk_user_ship = ".((int) $user->id);
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::close()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		foreach ($this->lines as $mastershipmentLine) {
			// close all shipments
			$shipment = array();
			if ($mastershipmentLine->fk_expedition > 0) {
				$shipment[$mastershipmentLine->fk_commande] = new Expedition($this->db);
				$shipment[$mastershipmentLine->fk_commande]->fetch($mastershipmentLine->fk_expedition);
				if ($shipment[$mastershipmentLine->fk_commande]->status != Expedition::STATUS_CLOSED) {
					$result = $shipment[$mastershipmentLine->fk_commande]->setClosed();
					if ($result < 0) {
						$this->errors = $shipment[$mastershipmentLine->fk_commande]->errors;
						break;
					}
				}
			}
			// close all shipmentspackage
			$shipmentPackage = array();
			if ($mastershipmentLine->fk_shipmentpackage > 0) {
				dol_include_once('shipmentpackage/class/shipmentpackage.class.php');
				$shipmentPackage[$mastershipmentLine->fk_shipmentpackage] = new ShipmentPackage($this->db);
				$shipmentPackage[$mastershipmentLine->fk_shipmentpackage]->fetch($mastershipmentLine->fk_shipmentpackage);
				$result = $shipmentPackage[$mastershipmentLine->fk_shipmentpackage]->close($user);
				if ($result < 0) {
					$this->errors = $shipmentPackage[$mastershipmentLine->fk_commande]->errors;
					break;
				}
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('MASTERSHIPMENT_CLOSE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Set new ref and current status
		if (!$error) {
			$this->status = self::STATUS_CLOSED;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Set back to validated status
	 *
	 *	@param	User		$user			Object user that modify
	 *  @param	int<0,1>	$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<-1,1>					Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('batchshipment','batchshipment_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'MASTERSHIPMENT_REOPEN');
	}

	/**
	 *	Set back to validated status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setValidated($user, $notrigger = 0)
	{
		$error = 0;

		// Protection
		if ($this->status <= self::STATUS_VALIDATED) {
			return 0;
		}

		$result = $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'MASTERSHIPMENT_UNPICKED');

		return $result;
	}

	/**
	 *	Set back to picked status
	 *
	 *	@param User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setPicked($user, $notrigger = 0)
	{
		$error = 0;

		// Protection
		if ($this->status <= self::STATUS_PICKED) {
			return 0;
		}

		$result = $this->setStatusCommon($user, self::STATUS_PICKED, $notrigger, 'MASTERSHIPMENT_UNSHIPMENTONPROCESS');


		// delete shipments
		$shipment = new Expedition($this->db);
		foreach ($this->lines as $line) {
			if ($line->fk_expedition > 0) {
				$result = $shipment->fetch($line->fk_expedition);
				if ($result > 0) {
					$result = $shipment->delete();
					if ($result < 0) {
						$error++;
						$this->error = $shipment->error;
						break;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * pick from lines marked
	 *
	 * if ok mark lines with package id and update status of line
	 *
	 * @param	User	$user			Object user that modify
	 * @param	array	$linesChecked	lines to group
	 * @param	array	$qtysToGroup	qty's to group
	 * @param	array	$warehouses		warehouses for group
	 * @param	array	$productBatches	product batch id's for group
	 * @return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function group($user, $linesChecked, $qtysToGroup, $warehouses, $productBatches)
	{
		$error = 0;

		// Protection
		if ($this->status != self::STATUS_DRAFT) {
			return 0;
		}

		if (is_array($linesChecked) && count($linesChecked) > 0) {
			// update qty, warehouse and product batch fields for master shipment
			foreach ($linesChecked as $key=>$lineId) {
				$line = new MasterShipmentLine($this->db);
				$result = $line->fetch($lineId);
				if ($result > 0) {
					$result = $this->updateLine($user,
						$line->id,
						MasterShipmentLine::STATUS_GROUPED,
						$line->fk_product,
						price2num($qtysToGroup[$key]),
						0,
						$line->fk_commande,
						$warehouses[$key],
						0,
						'',
						isset($productBatches[$key]) ? $productBatches[$key] : 0
					);
					if ($result < 0) {
						$error++;
						break;
					}
				} else {
					$error++;
					$this->errors[] = $line->error;
					break;
				}
			}
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * merge lines
	 *
	 * @param User $user Object user that merge line
	 * @param array $linesChecked lines to merge
	 * @param array $qtysToGroup qty's to merge
	 * @param array $warehouses warehouses for merge
	 * @param	array	$productBatches	product batch id's for merge
	 * @return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function mergeLines($user, $linesChecked, $qtysToGroup, $warehouses, $productBatches)
	{
		$error = 0;

		// Protection
		if ($this->status != self::STATUS_DRAFT) {
			return 0;
		}

		if (is_array($linesChecked) && count($linesChecked) > 0) {
			// merge lines in database
			$orderLineId = 0;
			$lineToKeepId = 0;
			$warehouseToKeepId = 0;
			$batchToKeepId = 0;
			$totalQty = 0;
			foreach ($linesChecked as $key=>$lineId) {
				$line = new MasterShipmentLine($this->db);
				$result = $line->fetch($lineId);
				if ($result > 0) {
					$totalQty += $qtysToGroup[$key];
					if (empty($lineToKeepId)) {
						$lineToKeepId = $lineId;
						$warehouseToKeepId = $warehouses[$key];
						$batchToKeepId = isset($productBatches[$key]) ? $productBatches[$key] : 0;
					}
					if (!empty($orderLineId) && $line->fk_commande_line != $orderLineId) {
						$error++;
						$this->errors[] = 'Lines must be from same order line to be merged';
						break;
					} elseif ($line->id != $lineToKeepId) {
						$result = $line->delete($user);
						if ($result < 0) {
							$error++;
							$this->errors[] = $line->error;
							break;
						}
					} else {
						$orderLineId = $line->fk_commande_line;
					}
				} else {
					$error++;
					$this->errors[] = $line->error;
					break;
				}
			}
			if ($lineToKeepId > 0 && !$error) {
				$line = new MasterShipmentLine($this->db);
				$result = $line->fetch($lineToKeepId);
				if ($result > 0) {
					$result = $this->updateLine($user,
						$lineToKeepId,
						MasterShipmentLine::STATUS_DRAFT,
						$line->fk_product,
						$totalQty,
						0,
						$line->fk_commande,
						$warehouseToKeepId,
						0,
						'',
						$batchToKeepId
					);
					if ($result < 0) {
						$error++;
						$this->errors[] = $line->error;
					}
				}
				if ($result < 0) {
					$error++;
					$this->errors[] = $line->error;
				}
			}
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * split lines in database
	 *
	 * @param User $user Object user that split line
	 * @param array $linesChecked lines to split
	 * @param array $qtysToSplit qty to split for each line
	 * @param array $warehouses warehouse to split for each line
	 * @param	array	$productBatches	product batch id's for split
	 * @return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function splitLines($user, $linesChecked, $qtysToSplit, $warehouses, $productBatches)
	{
		$error = 0;

		// Protection
		if ($this->status != self::STATUS_DRAFT) {
			return 0;
		}

		if (is_array($linesChecked) && count($linesChecked) > 0) {
			foreach ($linesChecked as $key=>$lineId) {
				$line = new MasterShipmentLine($this->db);
				$result = $line->fetch($lineId);
				if ($result > 0) {
					$result = $line->split($user, $this, price2num($qtysToSplit[$key]), $warehouses[$key], isset($productBatches[$key]) ? $productBatches[$key] : 0);
					if ($result < 0) {
						$error++;
						$this->errors[] = $line->error;
						break;
					}
				} else {
					$error++;
					$this->errors[] = $line->error;
					break;
				}
			}
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * pick from lines marked
	 *
	 * if ok mark lines with package id and update status of line
	 *
	 * @param	User	$user			Object user that modify
	 * @param	array	$linesChecked	lines to pick
	 * @param	array	$qtysToPick		qty's to pick
	 * @param	array	$comments		comments for pick
	 * @param	array	$productbatchs	product batch id's picked
	 * @param	array	$warehouses		warehouses to pick
	 * @return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function pick($user, $linesChecked, $qtysToPick, $comments, $productbatchs, $warehouses)
	{
		$error = 0;

		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		if (is_array($linesChecked) && count($linesChecked) > 0) {
			// update picked qty fields for master shipment
			foreach ($linesChecked as $key=>$lineId) {
				$line = new MasterShipmentLine($this->db);
				$result = $line->fetch($lineId);
				if ($result > 0) {
					$result = $this->updateLine($user,
						$line->id,
						MasterShipmentLine::STATUS_PICKED,
						$line->fk_product,
						$line->qty,
						price2num($qtysToPick[$key]),
						$line->fk_commande,
						$warehouses[$key],
						0,
						$comments[$key],
						$productbatchs[$key]
					);
					if ($result < 0) {
						$error++;
						break;
					}
				} else {
					$error++;
					$this->errors[] = $line->error;
					break;
				}
			}
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * load lines marked to load
	 * create shipment object for each order and if reception packet id
	 * create shipment packages from reception packages used and link to orders
	 * if ok mark lines loaded.
	 * @param	User	$user			Object user that modify
	 * @param	array	$linesChecked	lines to load
	 * @param	array	$qtysToLoad		qty's to load
	 * @param	array	$comments		comments  for load
	 * @param	array	$productbatchs	product batch id's load
	 * @param	array	$warehouses		warehouses to pick
	 * @return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function load($user, $linesChecked, $qtysToLoad, $comments, $productbatchs, $warehouses)
	{
		$error = 0;
		// Protection
		if ($this->status != self::STATUS_PICKED) {
			return 0;
		}

		if (is_array($linesChecked) && count($linesChecked) > 0) {
			foreach ($linesChecked as $checkKey=>$lineId) {
				$line = new MasterShipmentLine($this->db);
				$result = $line->fetch($lineId);
				if ($result > 0) {
					$result = $this->updateLine($user,
						$line->id,
						MasterShipmentLine::STATUS_LOADED,
						$line->fk_product,
						$line->qty,
						$line->qty_pick,
						$line->fk_commande,
						$warehouses[$checkKey],
						$qtysToLoad[$checkKey],
						$comments[$checkKey],
						$productbatchs[$checkKey]
					);
					if ($result < 0) {
						$error++;
						break;
					}
				} else {
					$error++;
					$this->errors[] = $line->error;
					break;
				}
			}
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * undo load (delete all shipment package if used)
	 *
	 * @param	User	$user			Object user that modify
	 * @return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function undoLoad($user)
	{
		$error = 0;
		$deleted = 0;
		$result = 0;

		if (empty($this->lines)) {
			$this->getLinesArray();
		}

		foreach ($this->lines as $line) {
			if ($line->fk_shipmentpackage > 0) {
				// delete shipment packages
				$package = new ShipmentPackage($this->db);
				$result = $package->fetch($line->fk_shipmentpackage);
				if ($result > 0) {
					$result = $package->delete($user);
					if ($result < 0) {
						$error++;
						$this->errors = $package->errors;
						break;
					} else {
						$deleted++;
					}
				}
			} else {
				$masterShipmentLine = new MasterShipmentLine($this->db);
				$masterShipmentLine->fetch($line->id);
				$masterShipmentLine->qty_load = 0;
				$masterShipmentLine->fk_shipmentpackage = null;
				$masterShipmentLine->fk_shipmentpackage_line = null;
				$masterShipmentLine->status = MasterShipmentLine::STATUS_PICKED;
				$masterShipmentLine->update($user);
			}
		}

		if ($error) {
			return $result;
		} else {
			$this->nbr_packages = 0;
			$this->weight = 0;
			$this->update($user);
			return $deleted;
		}
	}

	/**
	 * Check lines
	 *
	 * if ok mark lines loaded.
	 * @param	User	$user			Object user that modify
	 * @param	array	$linesChecked	lines to check
	 * @param	array	$comments		comments  for check
	 * @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 * @return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function check($user, $linesChecked, $comments, $notrigger = 0)
	{
		global $langs, $conf;

		$error = 0;
		// Protection
		if (($this->status != self::STATUS_SHIPMENTONPROCESS && getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING')) || $this->status != self::STATUS_PICKED) {
			return 0;
		}

		foreach ($linesChecked as $key=>$lineId) {
			$line = new MasterShipmentLine($this->db);
			$result = $line->fetch($lineId);
			if (!$error && $result > 0) {
				// check if lines has a shipment before you can close
				if (empty($line->fk_expedition)) {
					$error++;
					$this->errors[] = "You can't close a master shipment with missing shipments.";
					break;
				}
				$line->comment = $comments[$key];
				$line->status = MasterShipmentLine::STATUS_CHECKED;
				$result = $line->update($user, $notrigger);
				if ($result < 0) {
					$error++;
					$this->errors = $line->errors;
					break;
				}
			}
		}

		if (!$error) {
			// get nbr of packages and total weight
			$shipmentPackage = array();
			$shipments = array();
			$shipmentTotalWeight = 0;
			foreach ($this->lines as $line) {
				$shipmentPackage[$line->fk_shipmentpackage] = $line->fk_shipmentpackage;
				$shipments[$line->fk_expedition] = $line->fk_expedition;
			}
			$this->nbr_packages = count($shipmentPackage);
			foreach ($shipments as $shipmentid) {
				$shipment = new Expedition($this->db);
				$shipment->fetch($shipmentid);
				$totalWeightVolume = $shipment->getTotalWeightVolume();
				$shipmentTotalWeight += $totalWeightVolume['weight'];
			}
			$this->weight_units = 99;
			$this->weight = $shipmentTotalWeight / 0.45359237;
			if (!empty($conf->global->SHIPMENTPACKAGE_EMPTY_WEIGHT)) $this->weight += ($this->nbr_packages * $conf->global->SHIPMENTPACKAGE_EMPTY_WEIGHT);
			$result = $this->update($user);
			if ($result < 0) $error++;
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 * override of commenobject for getting estimated master shipment weight. Return into unit=0, the calculated total of weight and volume of all lines * qty
	 * Calculate by adding weight and volume of each product line, so properties ->volume/volume_units/weight/weight_units must be loaded on line.
	 *
	 * @return  array                           array('weight'=>...,'volume'=>...)
	 */
	public function getTotalWeightVolume()
	{
		$totalWeight = 0;
		$totalVolume = 0;
		$totalOrdered = '';
		$totalToShip = '';

		foreach ($this->lines as $line) {
			if (isset($line->qty)) {
				if (empty($totalOrdered)) {
					$totalOrdered = 0; // Avoid warning because $totalOrdered is ''
				}
				$totalOrdered += $line->qty; // defined for shipment only
			}
			if (isset($line->qty_load)) {
				if (empty($totalToShip)) {
					$totalToShip = 0; // Avoid warning because $totalToShip is ''
				}
				$totalToShip += $line->qty_load; // defined for shipment only
			}

			// we get ordered qty for estimated weight
			$qty = $line->qty ? $line->qty : 0;

			$product = new Product($this->db);
			$product->fetch($line->fk_product);

			$weight = $product->weight;
			$volume = $product->volume;
			($volume == 0 && !empty($line->product->volume)) ? $volume = $line->product->volume : 0;

			$weight_units = $product->weight_units;
			$volume_units = $product->volume_units;

			$weightUnit = 0;
			$volumeUnit = 0;
			if (!empty($weight_units)) {
				$weightUnit = $weight_units;
			}
			if (!empty($volume_units)) {
				$volumeUnit = $volume_units;
			}

			if (empty($totalWeight)) {
				$totalWeight = 0; // Avoid warning because $totalWeight is ''
			}
			if (empty($totalVolume)) {
				$totalVolume = 0; // Avoid warning because $totalVolume is ''
			}

			//var_dump($line->volume_units);
			if ($weight_units < 50) {   // < 50 means a standard unit (power of 10 of official unit), > 50 means an exotic unit (like inch)
				$trueWeightUnit = pow(10, $weightUnit);
				$totalWeight += $weight * $qty * $trueWeightUnit;
			} else {
				if ($weight_units == 99) {
					// conversion 1 Pound = 0.45359237 KG
					$trueWeightUnit = 0.45359237;
					$totalWeight += $weight * $qty * $trueWeightUnit;
				} elseif ($weight_units == 98) {
					// conversion 1 Ounce = 0.0283495 KG
					$trueWeightUnit = 0.0283495;
					$totalWeight += $weight * $qty * $trueWeightUnit;
				} else {
					$totalWeight += $weight * $qty; // This may be wrong if we mix different units
				}
			}
			if ($volume_units < 50) {   // >50 means a standard unit (power of 10 of official unit), > 50 means an exotic unit (like inch)
				//print $line->volume."x".$line->volume_units."x".($line->volume_units < 50)."x".$volumeUnit;
				$trueVolumeUnit = pow(10, $volumeUnit);
				//print $line->volume;
				$totalVolume += $volume * $qty * $trueVolumeUnit;
			} else {
				$totalVolume += $volume * $qty; // This may be wrong if we mix different units
			}
		}

		return array('weight'=>$totalWeight, 'volume'=>$totalVolume, 'ordered'=>$totalOrdered, 'toship'=>$totalToShip);
	}

	/**
	 * getting total value of loaded qty master shipment.
	 *
	 * @return  float                           total value
	 */
	public function getTotalValue()
	{
		$totalValue = 0;

		foreach ($this->lines as $line) {
			/** @var MasterShipmentLine $line */
			if (!empty($line->fk_commande_line)) {
				$orderLine = new OrderLine($this->db);
				$orderLine->fetch($line->fk_commande_line);
				if ($this->status == MasterShipment::STATUS_CLOSED && isset($line->qty_load)) {
					$totalValue += ($line->qty_load * $orderLine->subprice);
				} elseif ($this->status == MasterShipment::STATUS_SHIPMENTONPROCESS && isset($line->qty)) {
					$totalValue += ($line->qty * $orderLine->subprice);
				} elseif ($this->status == MasterShipment::STATUS_VALIDATED && isset($line->qty)) {
					$totalValue += ($line->qty * $orderLine->subprice);
				} elseif ($this->status == MasterShipment::STATUS_DRAFT && isset($line->qty)) {
					$totalValue += ($line->qty * $orderLine->subprice);
				}
			}
		}

		return $totalValue;
	}

	/**
	 * set tracking url
	 *
	 * @param	string	$value		Value
	 * @return	void
	 */
	public function getUrlTrackingStatus($value = '')
	{
		if (!empty($this->fk_shipping_method)) {
			$sql = "SELECT em.code, em.tracking";
			$sql .= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as em";
			$sql .= " WHERE em.rowid = ".((int) $this->fk_shipping_method);

			$resql = $this->db->query($sql);
			if ($resql) {
				if ($obj = $this->db->fetch_object($resql)) {
					$tracking = $obj->tracking;
				}
			}
		}

		if (!empty($tracking) && !empty($value)) {
			$url = str_replace('{TRACKID}', $value, $tracking);
			$this->tracking_url = sprintf('<a target="_blank" href="%s">'.($value ? $value : 'url').'</a>', $url, $url);
		} else {
			$this->tracking_url = empty($value) ? '' : $value;
		}
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param	array<string,string> 	$params 	Params to construct tooltip data
	 * @since 	v18
	 * @return	array{optimize?:string,picto?:string,ref?:string}
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$datas = [];

		if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("ShowMasterShipment")];
		}
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("MasterShipment").'</u>';
		if (isset($this->status)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}
		if (property_exists($this, 'ref')) {
			$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}
		if (property_exists($this, 'label')) {
			$datas['label'] = '<br>'.$langs->trans('Label').':</b> '.$this->label;
		}

		return $datas;
	}

	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 *  @param	int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param	string  $option                     On what the link point to ('nolink', ...)
	 *  @param	int     $notooltip                  1=Disable tooltip
	 *  @param	string  $morecss                    Add more css on link
	 *  @param	int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$params = [
			'id' => (string) $this->id,
			'objecttype' => $this->element.($this->module ? '@'.$this->module : ''),
			'option' => $option,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = dol_buildpath('/batchshipment/mastershipment_card.php', 1).'?id='.$this->id;

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($url && $add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowMasterShipment");
				$linkclose .= ' alt="'.dolPrintHTMLForAttribute($label).'"';
			}
			$linkclose .= ($label ? ' title="'.dolPrintHTMLForAttribute($label).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink' || empty($url)) {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (!getDolGlobalString(strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS')) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		return $result;
	}

	/**
	 *	Return a thumb for kanban views
	 *
	 *	@param	string	    			$option		Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param	?array<string,mixed>	$arraydata	Array of data
	 *  @return	string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $conf, $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'label')) {
			$return .= ' <div class="inline-block opacitymedium valignmiddle tdoverflowmax100">'.$this->label.'</div>';
		}
		if (property_exists($this, 'thirdparty') && is_object($this->thirdparty)) {
			$return .= '<br><div class="info-box-ref tdoverflowmax150">'.$this->thirdparty->getNomUrl(1).'</div>';
		}
		if (property_exists($this, 'amount')) {
			$return .= '<br>';
			$return .= '<span class="info-box-label amount">'.price($this->amount, 0, $langs, 1, -1, -1, $conf->currency).'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param	int<0,6>	$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param	int<0,6>	$mode	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string				Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given status
	 *
	 *  @param	int			$status		Id status
	 *  @param	int<0,6>	$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string					Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (is_null($status)) {
			return '';
		}

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("batchshipment@batchshipment");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
			$this->labelStatus[self::STATUS_PICKED] = $langs->transnoentitiesnoconv('Picked');
			$this->labelStatus[self::STATUS_SHIPMENTONPROCESS] = $langs->transnoentitiesnoconv('Loaded');
			$this->labelStatus[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('Shipped');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Canceled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
			$this->labelStatusShort[self::STATUS_PICKED] = $langs->transnoentitiesnoconv('Picked');
			$this->labelStatusShort[self::STATUS_SHIPMENTONPROCESS] = $langs->transnoentitiesnoconv('Loaded');
			$this->labelStatusShort[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('Shipped');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Canceled');
		}

		$statusType = 'status'.$status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param	int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = "SELECT rowid,";
		$sql .= " date_creation as datec, tms as datem";
		if (!empty($this->fields['date_validation'])) {
			$sql .= ", date_validation as datev";
		}
		if (!empty($this->fields['fk_user_creat'])) {
			$sql .= ", fk_user_creat";
		}
		if (!empty($this->fields['fk_user_modif'])) {
			$sql .= ", fk_user_modif";
		}
		if (!empty($this->fields['fk_user_valid'])) {
			$sql .= ", fk_user_valid";
		}
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if (!empty($this->fields['fk_user_creat'])) {
					$this->user_creation_id = $obj->fk_user_creat;
				}
				if (!empty($this->fields['fk_user_modif'])) {
					$this->user_modification_id = $obj->fk_user_modif;
				}
				if (!empty($this->fields['fk_user_valid'])) {
					$this->user_validation_id = $obj->fk_user_valid;
				}
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
				if (!empty($obj->datev)) {
					$this->date_validation   = empty($obj->datev) ? '' : $this->db->jdate($obj->datev);
				}
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialize object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return	int
	 */
	public function initAsSpecimen()
	{
		// Set here init that are not commonf fields
		// $this->property1 = ...
		// $this->property2 = ...

		return $this->initAsSpecimenCommon();
	}

	/**
	 * 	Create an array of lines
	 *
	 * 	@return	CommonObjectLine[]|int		array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new MasterShipmentLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, '(fk_mastershipment:=:'.((int) $this->id).')');
		$stockUsedForBatch = array();
		foreach ($result as $line) {
			if (!empty($line->fk_productbatch)) {
				$batch = new ProductBatch($this->db);
				$batch->fetch($line->fk_productbatch);

				if ($batch->qty < $line->qty) {
					!empty($stockUsedForBatch[$batch->id]) ? $stockUsedForBatch[$batch->id] += $batch->qty : $stockUsedForBatch[$batch->id] = $batch->qty;
				} else {
					!empty($stockUsedForBatch[$batch->id]) ? $stockUsedForBatch[$batch->id] += $line->qty : $stockUsedForBatch[$batch->id] = $line->qty;
				}
				if ($stockUsedForBatch[$batch->id] >= $batch->qty) {
					$this->usedLotBatch[$batch->id] = $batch->id;
				}
			}
		}

		if (is_numeric($result)) {
			$this->setErrorsFromObject($objectline);
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/** Sort lines
	 * this function is used to sort lines depending on status of MasterShipment
	 * It will set the position field of lines to be able to sort them in the right order on card and list.
	 *
	 * @param User $user Object user that sort lines
	 * @param array $sorters array of sort properties, each element of array is an array with keys 'field' and 'direction' (ASC or DESC)
	 * @return void
	 */
	public function sortLines($user,$sorters = array()) {
		if (empty($this->lines)) {
			$this->getLinesArray();
		}

		$data = array();
		// convert object array to array of associative array
		foreach ($this->lines as $line) {
			$data[] = (array) $line;
		}
		// create args for php array_multisort
		$multisortArgs = array();
		foreach ($sorters as $sort) {
			if (!empty($sort['sortfield']) && !empty($sort['sortorder'])) {
				$tmp = array();
				foreach ($data as $key => $values) {
					$tmp[$key] = $values[$sort['sortfield']];
				}
				$multisortArgs[] = $tmp;
				if ($sort['sortorder'] == 'DESC') {
					$multisortArgs[] = SORT_DESC;
				} else {
					$multisortArgs[] = SORT_ASC;
				}
			}
		}
		$multisortArgs[] = &$data;
		// call php array_multisort and get sorted data
		call_user_func_array('array_multisort', $multisortArgs);
		$data = array_pop($multisortArgs);
		// convert array of associative array to object array
		$position = 1;
		foreach ($data as $value) {
			// update position in database
			$masterShipmentLine = new MasterShipmentLine($this->db);
			$masterShipmentLine->fetch($value['id']);
			$masterShipmentLine->position = $position;
			$position++;
			$masterShipmentLine->update($user);
		}
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 *  @return	string      		Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("batchshipment@batchshipment");

		if (!getDolGlobalString('BATCHSHIPMENT_MASTERSHIPMENT_ADDON')) {
			$conf->global->BATCHSHIPMENT_MASTERSHIPMENT_ADDON = 'mod_mastershipment_standard';
		}

		if (getDolGlobalString('BATCHSHIPMENT_MASTERSHIPMENT_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('BATCHSHIPMENT_MASTERSHIPMENT_ADDON').".php";
			$classname = getDolGlobalString('BATCHSHIPMENT_MASTERSHIPMENT_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/batchshipment/");

				// Load file with numbering class (if found)
				$mybool = $mybool || @include_once $dir.$file;
			}

			if (!$mybool) {
				dol_print_error(null, "Failed to include file ".$file);
				return '';
			}

			if (class_exists($classname)) {
				$obj = new $classname();
				'@phan-var-force ModeleNumRefMasterShipment $obj';
				$numref = $obj->getNextValue($this);

				if ($numref != '' && $numref != '-1') {
					return $numref;
				} else {
					$this->error = $obj->error;
					//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
					return "";
				}
			} else {
				print $langs->trans("Error")." ".$langs->trans("ClassNotFound").' '.$classname;
				return "";
			}
		} else {
			print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
			return "";
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	string		$modele			Force template to use ('' to not force)
	 *  @param	Translate	$outputlangs	object lang a utiliser pour traduction
	 *  @param	int<0,1>	$hidedetails    Hide details of lines
	 *  @param	int<0,1>	$hidedesc       Hide description
	 *  @param	int<0,1>	$hideref        Hide ref
	 *  @param	?array<string,string>  $moreparams     Array to provide more information
	 *  @return	int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $langs;

		$result = 0;
		$includedocgeneration = 1;

		$langs->load("batchshipment@batchshipment");

		if (!dol_strlen($modele)) {
			$modele = 'standard_mastershipment';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('MASTERSHIPMENT_ADDON_PDF')) {
				$modele = getDolGlobalString('MASTERSHIPMENT_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/batchshipment/doc/";

		if ($includedocgeneration && !empty($modele)) {
			$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}

		return $result;
	}

	/**
	 * Return validation test result for a field.
	 * Need MAIN_ACTIVATE_VALIDATION_RESULT to be called.
	 *
	 * @param   array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,5>|string,noteditable?:int<0,1>,default?:int<0,1>|string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,comment?:string,validate?:int<0,1>}>  $fields Array of properties of field to show
	 * @param	string  $fieldKey            Key of attribute
	 * @param	string  $fieldValue          value of attribute
	 * @return	bool 						Return false if fail, true on success, set $this->error for error message
	 */
	public function validateField($fields, $fieldKey, $fieldValue)
	{
		// Add your own validation rules here.
		// ...

		return parent::validateField($fields, $fieldKey, $fieldValue);
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		//global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlogfile.log';

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__." start", LOG_INFO);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		dol_syslog(__METHOD__." end", LOG_INFO);

		return $error;
	}
}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class MasterShipmentLine. You can also remove this and generate a CRUD class for lines objects.
 */
class MasterShipmentLine extends CommonObjectLine
{
		/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'notnull'=>1, 'visible'=>-1, 'noteditable'=>'1', 'index'=>1, 'comment'=>"Id"),
		'fk_mastershipment' => array('type'=>'integer:MasterShipment:batchshipment/class/mastershipment.class.php', 'label'=>'MasterShipment', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'index'=>1,),
		'fk_product' => array('type'=>'integer:Product:product/class/product.class.php', 'label'=>'Product', 'enabled'=>'1', 'notnull'=>-1, 'visible'=>1),
		'fk_entrepot' => array('type'=>'integer:Entrepot:product/stock/class/entrepot.class.php', 'label'=>'Warehouse', 'enabled'=>'1', 'notnull'=>-1, 'visible'=>1),
		'fk_productbatch' => array('type'=>'integer', 'label'=>'ProductBatch', 'enabled'=>'1', 'notnull'=>-1, 'visible'=>-1),
		'fk_productlot' => array('type'=>'integer:ProductLot:product/stock/class/productlot.class.php', 'label'=>'ProductLot', 'enabled'=>'1', 'notnull'=>-1, 'visible'=>1),
		'qty' => array('type'=>'real', 'label'=>'Quantity', 'enabled'=>'1', 'notnull'=>1, 'visible'=>1),
		'qty_pick' => array('type'=>'real', 'label'=>'PickedQuantity', 'enabled'=>'1', 'notnull'=>1, 'visible'=>1),
		'qty_load' => array('type'=>'real', 'label'=>'LoadedQuantity', 'enabled'=>'1', 'notnull'=>1, 'visible'=>1),
		'fk_commande' => array('type'=>'integer:Commande:commande/class/commande.class.php', 'label'=>'Order', 'enabled'=>'1', 'notnull'=>-1, 'visible'=>1),
		'fk_commande_line' => array('type'=>'integer', 'label'=>'OrderLine', 'enabled'=>'1', 'notnull'=>-1, 'visible'=>-1),
		'fk_expedition' => array('type'=>'integer:Expedition:expedition/class/expedition.class.php', 'label'=>'Shipment', 'enabled'=>'1', 'notnull'=>-1, 'visible'=>1),
		'fk_expedition_line' => array('type'=>'integer', 'label'=>'ShipmentLine', 'enabled'=>'1', 'notnull'=>-1, 'visible'=>-1),
		'fk_shipmentpackage' => array('type'=>'integer:ShipmentPackage:shipmentpackage/class/shipmentpackage.class.php', 'label'=>'ShipmentPackage', 'enabled'=>'0', 'notnull'=>-1, 'visible'=>1),
		'fk_shipmentpackage_line' => array('type'=>'integer', 'label'=>'ShipmentPackageLine', 'enabled'=>'0', 'notnull'=>-1, 'visible'=>-1),
		'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>200, 'notnull'=>1, 'visible'=>5, 'index'=>1, 'default'=>0, 'arrayofkeyval'=>array('0'=>'Draft', '1' => 'Set', '2'=>'Picked', '3'=>'Loaded', '4'=>'Checked'),),
		'comment' => array('type'=>'varchar(255)', 'label'=>'Comment', 'enabled'=>'1', 'notnull'=>-1, 'visible'=>1),
		'position' => array('type'=>'integer', 'label'=>'Rang', 'enabled'=>'1', 'notnull'=>-1, 'visible'=>0)
	);
	/**
	 * ID of parent object
	 * @var int
	 */
	public $fk_mastershipment;

	/**
	 * ID of linked warehouse
	 * @var int
	 */
	public $fk_entrepot;
	/**
	 * ID of linked product batch
	 * @var int
	 */
	public $fk_productbatch;
	/**
	 * ID of linked product lot
	 * @var int
	 */
	public $fk_productlot;

	/**
	 * Picked quantity
	 * @var float
	 */
	public $qty_pick;
	/**
	 * Loaded quantity
	 * @var float
	 */
	public $qty_load;
	/**
	 * ID of linked order
	 * @var int
	 */
	public $fk_commande;
	/**
	 * ID of linked order line
	 * @var int
	 */
	public $fk_commande_line;
	/**
	 * ID of linked shipment
	 * @var int
	 */
	public $fk_expedition;
	/**
	 * ID of linked shipment line
	 * @var int
	 */
	public $fk_expedition_line;
	/**
	 * ID of linked shipment package
	 * @var int
	 */
	public $fk_shipmentpackage;
	/**
	 * ID of linked shipment package line
	 * @var int
	 */
	public $fk_shipmentpackage_line;
	/**
	 * Status
	 * @var int
	 */
	public $status;
	/**
	 * Comment
	 * @var string
	 */
	public $comment;
	/**
	 * Position
	 * @var int
	 */
	public $position;

	/**
	 * To overload
	 * @see CommonObjectLine
	 */
	public $parent_element = 'mastershipment';		// Example: '' or 'mastershipment'

	/**
	 * To overload
	 * @see CommonObjectLine
	 */
	public $fk_parent_attribute = 'fk_mastershipment';	// Example: '' or 'fk_mastershipment'

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'mastershipmentline';

	/**
	 * @var int    Name of subtable line
	 */
	public $table_element = 'batchshipment_mastershipmentdet';

	/**
	 * @var int<0,1>	Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var int<0,1>|string|null  	Does this object support multicompany module ?
	 * 								0=No test on entity, 1=Test with field entity in local table, 'field@table'=Test entity into the field@table (example 'fk_soc@societe')
	 */
	public $ismultientitymanaged = 0;

	const STATUS_DRAFT = 0;
	const STATUS_GROUPED = 1;
	const STATUS_PICKED = 2;
	const STATUS_LOADED = 3;
	const STATUS_CHECKED = 4;

	/**
	 * Constructor
	 *
	 * @param	DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		$result = $this->createCommon($user, $notrigger);
		if ($result > 0 && $this->fk_entrepot > 0) {
			// check if we need to set 'grouped' status
			$this->update($user, $notrigger);
		}
		return $result;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		return $result;
	}

	/**
	 * Load list of objects in memory from the database.
	 * Using a fetchAll() with limit = 0 is a very bad practice. Instead try to forge yourself an optimized SQL request with
	 * your own loop with start and stop pagination.
	 *
	 * @param	string		$sortorder	Sort Order
	 * @param	string		$sortfield	Sort field
	 * @param	int<0,max>	$limit		Limit the number of lines returned
	 * @param	int<0,max>	$offset		Offset
	 * @param	string		$filter		Filter as an Universal Search string.
	 *                                  Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
	 * @param	string		$filtermode	No longer used
	 * @return	array<int,self>|int<-1,-1>	 <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		if (isset($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
			$sql .= " LEFT JOIN ".$this->db->prefix().$this->table_element."_extrafields as te ON te.fk_object = t.rowid";
		}
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				if (!empty($record->isextrafieldmanaged)) {
					$record->fetch_optionals();
				}

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		if ($this->status == self::STATUS_DRAFT && $this->fk_product > 0 && $this->fk_entrepot > 0) {
			$product = new Product($this->db);
			$product->fetch($this->fk_product);
			if (!empty($product) && $product->status_batch > 0) {
				if ($this->fk_productbatch > 0) {
					dol_include_once('/product/stock/class/productbatch.class.php');
					dol_include_once('/product/stock/class/productlot.class.php');
					$productBatch = new Productbatch($this->db);
					$productBatch->fetch($this->fk_productbatch);
					$this->fk_productbatch = $productBatch->id;
					$productLot = new Productlot($this->db);
					$productLot->fetch(null, $this->fk_product, $productBatch->batch);
					$this->fk_productlot = $productLot->id;
					$this->status = self::STATUS_GROUPED;
				}
			} else {
				$this->status = self::STATUS_GROUPED;
			}
		}
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 * Split line in database for multi warehouse and/or multi lot/batch picking/loading
	 * @param User $user Object user that split line
	 * @param MasterShipment $masterShipment Object master shipment to which line belongs
	 * @param float $qtyToSplit Quantity to split on line (must be >0 and < current line quantity)
	 * @param int $warehouse Id of warehouse to split line (can be null if we
	 * @param int $lotbatch Id of lot/batch to split line (can be null if we
	 * @return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function split($user, $masterShipment, $qtyToSplit, $warehouse, $lotbatch)
	{
		$error = 0;

		$newLine = clone $this;
		$newLine->id = 0;
		$this->fk_entrepot = $warehouse;
		$this->fk_productbatch = $lotbatch;
		$newLine->fk_entrepot = $warehouse;
		$newLine->fk_productbatch = $lotbatch;
		// get best split qty depending on stock of warehouse and lot/batch
		$stockObject = null;
		$product = new Product($this->db);
		if (!empty($warehouse) && $this->fk_product) {
			$product->fetch($this->fk_product);
			$stockObject = $this->getBestWarehouse($product, $qtyToSplit, $warehouse);
		}
		if (empty($stockObject)) {
			$this->qty = $qtyToSplit / 2;
			$newLine->qty = $qtyToSplit / 2;
		} elseif (!empty($lotbatch) && $product->status_batch == 2) {
			// serial numeber case, we must split line by serial number
			$this->qty = 1;
			$this->fk_productbatch = $lotbatch;
			$newLine->qty = 1;
			$qtyToSplit = $qtyToSplit - 1;
			$masterShipment->usedLotBatch[$lotbatch] = $lotbatch;
			 // find best remainder batch/lot to split remaining quantity
			$productBatch2 = $this->getBestLot($stockObject, $qtyToSplit, $masterShipment->usedLotBatch);
			if (!empty($productBatch2) && $productBatch2->qty > 0) {
				$newLine->fk_productbatch = $productBatch2->id;
			} else {
				$newLine->fk_productbatch = -1;
				$newLine->qty = $qtyToSplit;
			}
			if ($newLine->fk_productbatch > 0 && $qtyToSplit > 1) {
				// split all remaining quantity
				$result = $newLine->split($user, $masterShipment, $qtyToSplit, $warehouse, $newLine->fk_productbatch);
				if ($result < 0) {
					$error++;
					$this->errors = array_merge($this->errors, $newLine->errors);
				}
			}
		} elseif (!empty($lotbatch)) {
			$productBatch = new ProductBatch($this->db);
			$productBatch->fetch($lotbatch);
			if (!empty($productBatch) && $productBatch->qty > 0 && $productBatch->qty < $qtyToSplit) {
				$this->qty = $productBatch->qty;
				$this->fk_productbatch = $productBatch->id;
				$masterShipment->usedLotBatch[$lotbatch] = $lotbatch;
				// find best remainder batch/lot to split remaining quantity
				$productBatch2 = $this->getBestLot($stockObject, $qtyToSplit - $productBatch->qty, $masterShipment->usedLotBatch);
				if (!empty($productBatch2) && $productBatch2->qty >= $qtyToSplit - $productBatch->qty) {
					$newLine->qty = $qtyToSplit - $productBatch->qty;
					$newLine->fk_productbatch = $productBatch2->id;
				} elseif (!empty($productBatch2) && $productBatch2->qty > 0) {
					$newLine->qty = $qtyToSplit - $productBatch->qty;
					$newLine->fk_productbatch = $productBatch2->id;
				} else {
					$newLine->qty = $qtyToSplit - $productBatch->qty;
					$newLine->fk_productbatch = -1;
				}
			} elseif (!empty($productBatch) && $productBatch->qty >= $qtyToSplit) {
				$this->qty = $qtyToSplit / 2;
				$this->fk_productbatch = $productBatch->id;
				$newLine->qty = $qtyToSplit / 2;
				$newLine->fk_productbatch = $productBatch->id;
			}
		} elseif (!empty($stockObject)) {
			if ($stockObject->real > 0 && $stockObject->real < $qtyToSplit) {
				$this->qty = $stockObject->real;
				// find best remainder warehouse to split remaining quantity
				$stockObject2 = $this->getBestWarehouse($product, $qtyToSplit - $stockObject->real, null, array($stockObject->fk_entrepot));
				if (!empty($stockObject2) && $stockObject2->real > 0) {
					$newLine->qty = $qtyToSplit - $stockObject->real;
					$newLine->fk_entrepot = $stockObject2->fk_entrepot;
				} else {
					$newLine->qty = $qtyToSplit - $stockObject->real;
					$newLine->fk_entrepot = $stockObject->fk_entrepot;
				}
			} elseif ($stockObject->real >= $qtyToSplit) {
				$this->qty = $qtyToSplit;
				$this->fk_entrepot = $stockObject->fk_entrepot;
				$newLine->qty = 0;
				$newLine->fk_entrepot = $stockObject->fk_entrepot;
			} elseif ($stockObject->real <= 0) {
				$split = 2;
				$this->qty = $qtyToSplit - ($qtyToSplit / $split);
				$newLine->qty = $qtyToSplit / $split;
			}
		}
		if (!$error) {
			$result = $newLine->create($user);
			if ($result < 0) {
				$error++;
				$this->errors = array_merge($this->errors, $newLine->errors);
			} else {
				$result = $this->update($user);
				if ($result < 0) {
					$error++;
					$this->errors = array_merge($this->errors, $newLine->errors);
				}
			}
		}
		if ($error) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 *  Return the best warehouse to pick or load depending on the quantity to pick/load and the stock of warehouses.
	 *  @param  Product $product   Product object
	 *  @param  float $neededQty    Quantity to pick/load
	 *  @param  int|null $fk_entrepot   Id of warehouse to force to pick/load from (can be null to not force any warehouse)
	 *
	 *  @return stdClass             best warehouse stock object with properties 'id' and 'real' (real stock) or null if no warehouse found
	 */
	public function getBestWarehouse($product, $neededQty = 0, $fk_entrepot = null, $warehousestoExclude = array())
	{
		// TODO also check quantities reserved in other master shipment lines not yet shipped to check real available stock to pick/load on warehouse
		$product->load_stock('novirtual');
		$warehouse = new Entrepot($this->db);
		if (!empty($product->stock_warehouse)) {
			// If a warehouse is forced and has stock to pick/load
			if (!empty($fk_entrepot)) {
				// if warehouse has child warehouse
				$warehouse->fetch($fk_entrepot);
				$childWarehouses = array();
				$childWarehouses = $warehouse->get_children_warehouses($fk_entrepot, $childWarehouses);
				if (is_array($childWarehouses) && count($childWarehouses) > 0) {
					// we add the forced warehouse in first position of array to check it first
					array_unshift($childWarehouses, $fk_entrepot);
					//we check if one has matching stock to pick/load whole quantity
					foreach ($childWarehouses as $childWarehouse) {
						if (isset($product->stock_warehouse[$childWarehouse]) && !empty($product->stock_warehouse[$childWarehouse]->real) && $product->stock_warehouse[$childWarehouse]->real >= $neededQty) {
							$product->stock_warehouse[$childWarehouse]->fk_entrepot = $childWarehouse;
							return $product->stock_warehouse[$childWarehouse];
						}
					}
					//we check if one has stock to pick/load whole quantity
					foreach ($childWarehouses as $childWarehouse) {
						if (isset($product->stock_warehouse[$childWarehouse]) && !empty($product->stock_warehouse[$childWarehouse]->real) && $product->stock_warehouse[$childWarehouse]->real > 0) {
							$product->stock_warehouse[$childWarehouse]->fk_entrepot = $childWarehouse;
							return $product->stock_warehouse[$childWarehouse];
						}
					}
				} elseif (isset($product->stock_warehouse[$fk_entrepot])) {
					$product->stock_warehouse[$fk_entrepot]->fk_entrepot = $fk_entrepot;
					return $product->stock_warehouse[$fk_entrepot];
				}
			} elseif (empty($fk_entrepot)) {
				// Try to find a warehouse with enough stock to pick/load whole quantity
				foreach ($product->stock_warehouse as $warehouse => $stock) {
					if (!empty($stock->real) && $stock->real >= $neededQty && !in_array($warehouse, $warehousestoExclude)) {
						$stock->fk_entrepot = $warehouse;
						return $stock;
					}
				}
				// If not found, try to find a warehouse with at least some stock to pick/load partially quantity
				foreach ($product->stock_warehouse as $warehouse => $stock) {
					if (!empty($stock->real) && $stock->real > 0 && !in_array($warehouse, $warehousestoExclude)) {
						$stock->fk_entrepot = $warehouse;
						return $stock;
					}
				}
			}
		}

		return null;
	}

	/**
	 *  Return the best lot/batch to pick or load the line depending on the quantity to pick/load and the stock of lots/batches.
	 *  @param  stdClass $stockObject   Stock object of the warehouse to pick/load (object with properties 'id' and 'real')
	 *  @param  float $neededQty        Quantity needed to pick/load
	 *  @param  array $lotbatchtoExclude Array of lot/batch id to exclude from search (for example because they are already used for other lines of the same pick/load)
	 *  @param  string $mode            'fifo' or 'bestfit'
	 *
	 *  @return ProductBatch|null                 Best lot/batch or null if no lot/batch found
	 */
	public function getBestLot($stockObject, $neededQty = 0, $lotbatchtoExclude = array(), $mode = 'fifo')
	{
		// TODO also check Lots reserved in other master shipment lines not yet shipped to check real available stock to pick/load on warehouse
		global $conf;

		$productbatch = new ProductBatch($this->db);
		$conf->global->SHIPPING_DISPLAY_STOCK_ENTRY_DATE = 1; // We want to sort by entry date to pick/load first the oldest lot/batch
		$result = $productbatch->findAll($this->db, $stockObject->id, 1, $this->fk_product);
		$batchFound = null;
		if (is_array($result) && count($result) > 0) {
			foreach ($result as $batch) {
				$stock_entry_date = $batch->context['stock_entry_date'];
				if (in_array($batch->id, $lotbatchtoExclude)) {
					continue; // we skip this lot/batch
				} else {
					$batchFound = $batch;
				}
				if ($mode == 'fifo') break; // we take the first lot/batch with stock to pick/load whole quantity
				if (!empty($batch->qty) && $batch->qty >= $neededQty) {
					break; // best fit lot/batch found with enough stock to pick/load whole quantity
				}
			}
		}
		return $batchFound;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_GROUPED] = $langs->transnoentitiesnoconv('Grouped');
			$this->labelStatus[self::STATUS_PICKED] = $langs->transnoentitiesnoconv('Picked');
			$this->labelStatus[self::STATUS_LOADED] = $langs->transnoentitiesnoconv('Loaded');
			$this->labelStatus[self::STATUS_CHECKED] = $langs->transnoentitiesnoconv('Checked');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_GROUPED] = $langs->transnoentitiesnoconv('Grouped');
			$this->labelStatusShort[self::STATUS_PICKED] = $langs->transnoentitiesnoconv('Picked');
			$this->labelStatusShort[self::STATUS_LOADED] = $langs->transnoentitiesnoconv('Loaded');
			$this->labelStatusShort[self::STATUS_CHECKED] = $langs->transnoentitiesnoconv('Checked');
		}

		$statusType = 'status'.$status;

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}
}
