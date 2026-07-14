<?php
/* Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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
 *    \file       mastershipment_card.php
 *    \ingroup    batchshipment
 *    \brief      Page to create/edit/view mastershipment
 */


// General defined Options
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');					// Force use of CSRF protection with tokens even for GET
//if (! defined('MAIN_AUTHENTICATION_MODE')) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined('MAIN_LANG_DEFAULT'))        define('MAIN_LANG_DEFAULT', 'auto');					// Force LANG (language) to a particular value
//if (! defined('MAIN_SECURITY_FORCECSP'))   define('MAIN_SECURITY_FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');					// Disable browser notification
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');						// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOLOGIN'))                  define('NOLOGIN', '1');						// Do not use login - if this page is public (can be called outside logged session). This includes the NOIPCHECK too.
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  		// Do not load ajax.lib.php library
//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');					// Do not create database handler $db
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');					// Do not load html.form.class.php
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');					// Do not load and show top and left menu
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');					// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');					// Do not load object $langs
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');					// Do not load object $user
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');			// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');			// Do not check injection attack on POST parameters
//if (! defined('NOSESSION'))                define('NOSESSION', '1');						// On CLI mode, no need to use web sessions
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');					// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');					// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
dol_include_once('/batchshipment/class/mastershipment.class.php');
dol_include_once('/batchshipment/lib/batchshipment_mastershipment.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 * @var int $hidedetails
 * @var int $hidedesc
 * @var int $hideref
 */

// Load translation files required by the page
$langs->loadLangs(array("batchshipment@batchshipment", "other", "sendings"));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$lineid   = GETPOSTINT('lineid');

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');					// if not set, a default page will be used
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');	// if not set, $backtopage will be used
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

// Initialize a technical objects
$object = new MasterShipment($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->batchshipment->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($object->element.'card', 'globalcard')); // Note that conf->hooks_modules contains array
$soc = null;

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);


$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = getDolGlobalInt('BATCHSHIPMENT_ENABLE_PERMISSION_CHECK');
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('batchshipment', 'mastershipment', 'read');
	$permissiontoadd = $user->hasRight('batchshipment', 'mastershipment', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->hasRight('batchshipment', 'mastershipment', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('batchshipment', 'mastershipment', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->hasRight('batchshipment', 'mastershipment', 'write'); // Used by the include of actions_dellink.inc.php
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

$upload_dir = $conf->batchshipment->multidir_output[isset($object->entity) ? $object->entity : 1].'/mastershipment';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled($object->module)) {
	accessforbidden("Module ".$object->module." not enabled");
}
if (!$permissiontoread) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = dol_buildpath('/batchshipment/mastershipment_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/batchshipment/mastershipment_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'BATCHSHIPMENT_MASTERSHIPMENT_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';
	if (($action == 'confirm_group' || ($action == 'confirm_pick') && getDolGlobalInt('BATCHSHIPMENT_ALLOW_PICKING_NOT_GROUPED')) && $confirm == 'yes' && $permissiontoadd) {
		// we change warehgouse and or lot/serial number
		$changedWarehouse = GETPOST('changedwarehouse', 'int');
		$changedBatch = GETPOST('changedbatch', 'int');
		$changedLine = GETPOST('changedline', 'int');
		if ($changedLine > 0) {
			if ($changedWarehouse > 0) {
				$line = new MasterShipmentLine($db);
				$result = $line->fetch($changedLine);
				if ($result > 0) {
					$line->fk_entrepot = $changedWarehouse;
					$line->update($user);
					if (empty($changedBatch)) {
						$product = new Product($db);
						$product->fetch($line->fk_product);
						$stockObject = $line->getBestWarehouse($product, $line->qty, $changedWarehouse);
						if ($stockObject) {
							$object->getLinesArray(); // to get used lot number
							$batch = $line->getBestLot($stockObject, $line->qty, $object->usedLotBatch);
							if (!empty($batch->id)) {
								$line->fk_productbatch = $batch->id;
							}
						} else {
							$line->fk_productbatch = -1;
						}
					}
				}
			}
			if ($changedBatch > 0) {
				$line = new MasterShipmentLine($db);
				$result = $line->fetch($changedLine);
				if ($result > 0) {
					$line->fk_productbatch = $changedBatch;
					$line->update($user);
				}
			}
		}
	}
	if ($action == 'confirm_group' && $confirm == 'yes' && $permissiontoadd) {
		$result = 0;
		$linesChecked = GETPOST('line_checkbox', 'array');
		$productBatchToGroup = GETPOST('fk_productbatch', 'array');
		$qtysToGroup = GETPOST('qty_group', 'array');
		$warehouses = GETPOST('fk_entrepot', 'array');

		if (GETPOST('group')) {
			$result = $object->group($user, $linesChecked, $qtysToGroup, $warehouses, $productBatchToGroup);
		} elseif (GETPOST('split')) {
			$object->getLinesArray(); // to get used lot number
			$result = $object->splitLines($user, $linesChecked, $qtysToGroup, $warehouses, $productBatchToGroup);
		} elseif (GETPOST('merge')) {
			$result = $object->mergeLines($user, $linesChecked, $qtysToGroup, $warehouses, $productBatchToGroup);
		}
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			unset($_POST['line_checkbox']);
			unset($_POST['qty_group']);
			unset($_POST['fk_entrepot']);
			unset($_POST['fk_productbatch']);
		}
		$action = '';
	}

	if ($action == 'confirm_pick' && $confirm == 'yes' && $permissiontoadd) {
		$result = 0;
		$linesChecked = GETPOST('line_checkbox', 'array');
		$qtysToPick = GETPOST('qty_pick', 'array');
		$comments = GETPOST('comment', 'array');
		$productbatchs = GETPOST('fk_productbatch', 'array');
		$warehouses = GETPOST('fk_entrepot', 'array');
		if (GETPOST('pick')) {
			$result = $object->pick($user, $linesChecked, $qtysToPick, $comments, $productbatchs, $warehouses);
		}
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			unset($_POST['line_checkbox']);
			unset($_POST['qty_pick']);
			unset($_POST['comment']);
			unset($_POST['fk_productbatch']);
			unset($_POST['fk_entrepot']);
		}
		$action = '';
	}

	if ($action == 'confirm_load' && $confirm == 'yes' && $permissiontoadd) {
		if (!GETPOST('undo_load')) {
			$linesChecked = GETPOST('line_checkbox', 'array');
			$qtysToLoad = GETPOST('qty_load', 'array');
			$comments = GETPOST('comment', 'array');
			$productbatchs = GETPOST('fk_productbatch', 'array');
			$warehouses = GETPOST('fk_entrepot', 'array');
			$result = $object->load($user, $linesChecked, $qtysToLoad, $comments, $productbatchs, $warehouses);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				unset($_POST['line_checkbox']);
				unset($_POST['comment']);
				unset($_POST['qty_load']);
				unset($_POST['fk_productbatch']);
				unset($_POST['fk_entrepot']);
			}
			$action = '';
		}
	}

	if ($action == 'confirm_check' && $confirm == 'yes' && $permissiontoadd) {
		$linesChecked = GETPOST('line_checkbox', 'array');
		$comments = GETPOST('comment', 'array');
		$result = $object->check($user, $linesChecked, $comments);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			unset($_POST['comment']);
			unset($_POST['line_checkbox']);
		}
		$action = '';
	}

	// Action close object
	if ($action == 'confirm_setclosed' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->close($user);
		if ($result >= 0) {
			// Define output language
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				if (method_exists($object, 'generateDocument')) {
					$outputlangs = $langs;
					$newlang = '';
					if (getDolGlobalInt('MAIN_MULTILANGS') /* && empty($newlang) */ && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
						$newlang = $object->thirdparty->default_lang;
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}
					$model = $object->model_pdf;
					$ret = $object->fetch($id); // Reload to get new records

					$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
			}
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}

	if ($action == 'undoline') {
		$mastershipment_line_id = GETPOST('lineid', 'int');

		$mastershipmentLine = new MasterShipmentLine($db);
		$mastershipmentLine->fetch($mastershipment_line_id);
		if ($mastershipmentLine->status == MasterShipmentLine::STATUS_GROUPED) {
			$mastershipmentLine->status = MasterShipmentLine::STATUS_DRAFT;
			$mastershipmentLine->fk_productbatch = null;
			$mastershipmentLine->fk_entrepot = null;
		} elseif ($mastershipmentLine->status == MasterShipmentLine::STATUS_PICKED) {
			$mastershipmentLine->status = MasterShipmentLine::STATUS_GROUPED;
			$mastershipmentLine->qty_pick = 0;
		}

		$mastershipmentLine->status = MasterShipmentLine::STATUS_DRAFT;
		$mastershipmentLine->update($user);
		$action = '';
	}

	if ($action == 'undocheck') {
		$mastershipment_line_id = GETPOST('lineid', 'int');

		$mastershipmentLine = new MasterShipmentLine($db);
		$mastershipmentLine->fetch($mastershipment_line_id);
		$mastershipmentLine->status = MasterShipmentLine::STATUS_LOADED;
		$mastershipmentLine->update($user);
		$action = '';
	}

	if ($action == 'confirm_undoall' && $confirm == 'yes') {
		foreach ($object->lines as $mastershipmentLine) {
			if ($object->status >= MasterShipment::STATUS_PICKED) {
				if ($mastershipmentLine->status == MasterShipmentLine::STATUS_CHECKED && $object->status != MasterShipment::STATUS_CLOSED) {
					$mastershipmentLine->status = MasterShipmentLine::STATUS_LOADED;
					$mastershipmentLine->update($user);
				}
			} else {
				if (($mastershipmentLine->status == MasterShipmentLine::STATUS_GROUPED && $object->status == MasterShipment::STATUS_DRAFT) || $mastershipmentLine->status == MasterShipmentLine::STATUS_PICKED) {
					if ($mastershipmentLine->status == MasterShipmentLine::STATUS_GROUPED) {
						$mastershipmentLine->fk_productbatch = null;
						$mastershipmentLine->fk_entrepot = null;
					} elseif ($mastershipmentLine->status == MasterShipmentLine::STATUS_PICKED) {
						$mastershipmentLine->qty_pick = 0;
					}
					$mastershipmentLine->status = MasterShipmentLine::STATUS_DRAFT;
					$mastershipmentLine->update($user);
				}
			}
		}
		$object->fetch($id);
		$action = '';
	}

	if ($action == 'confirm_undo_load') {
		$result = $object->undoLoad($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}

	// Action validate picked
	if ($action == 'confirm_picked' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->validatePicked($user);
		if ($result >= 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				if (method_exists($object, 'generateDocument')) {
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
						$newlang = $object->thirdparty->default_lang;
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$ret = $object->fetch($id); // Reload to get new records
					// TODO pack list model
					$model = $object->model_pdf;

					$retgen = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($retgen < 0) {
						setEventMessages($object->error, $object->errors, 'warnings');
					}
				}
			}
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}

	// Action validate loading
	if ($action == 'confirm_shipmentonprocess' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->validateLoaded($user);
		if ($result >= 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				if (method_exists($object, 'generateDocument')) {
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
						$newlang = $object->thirdparty->default_lang;
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$ret = $object->fetch($id); // Reload to get new records
					// TODO pack list model
					$model = $object->model_pdf;

					$retgen = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($retgen < 0) {
						setEventMessages($object->error, $object->errors, 'warnings');
					}
				}
			}
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}

	// Action back to validated object
	if ($action == 'confirm_setvalidated' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->setValidated($user);
		if ($result >= 0) {
			// Nothing else done
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}

	// Action back to picked object
	if ($action == 'confirm_setpicked' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->setPicked($user);
		if ($result >= 0) {
			// Nothing else done
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';
	}

	// shipping method
	if ($action == 'setshippingmethod' && $permissiontoadd) {
		$result = $object->setShippingMethod(GETPOSTINT('fk_shipping_method'), 0, $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} elseif (GETPOSTINT('fk_shipping_method') > 0) {
			$object->fk_shipping_method = GETPOSTINT('fk_shipping_method');
		}
	}

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', null, 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOSTINT('projectid'));
	}

	// Actions to send emails
	$triggersendname = 'BATCHSHIPMENT_MASTERSHIPMENT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_MASTERSHIPMENT_TO';
	$trackid = 'mastershipment'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$formproduct = new FormProduct($db);

$title = $langs->trans("MasterShipment")." - ".$langs->trans('Card');
//$title = $object->ref." - ".$langs->trans('Card');
if ($action == 'create') {
	$title = $langs->trans("NewObject", $langs->transnoentitiesnoconv("MasterShipment"));
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-batchshipment page-card');

// Example : Adding jquery code
// print '<script type="text/javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';


// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden('NotEnoughPermissions', 0, 1);
	}

	print load_fiche_titre($title, '', $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}
	if ($dol_openinpopup) {
		print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';
	}

	print dol_get_fiche_head(array(), '');


	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("MasterShipment"), '', $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$head = mastershipmentPrepareHead($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("MasterShipment"), -1, $object->picto, 0, '', '', 0, '', 1);

	$formconfirm = '';

	// Confirmation to delete (using preloaded confirm popup)
	if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteMasterShipment'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 'action-delete');
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Clone confirmation
	//if ($action == 'clone') {
	//	// Create an array for form
	//	$formquestion = array();
	//	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	//}

	// Close confirmation
	if ($action == 'close') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClose'), $langs->trans('ConfirmCloseAsk', $object->ref), 'confirm_setclosed', $formquestion, 'yes', 1);
	}

	// Confirmation of action setdraft
	if ($action == 'setdraft') {
		$formquestion = array();
		$langs->load("stocks");
		$text = ''; //$langs->trans("ConfirmSetDraftMasterShipmentForStock");
		//$formquestion = array(
		//	array('type' => 'other', 'name' => 'idwarehouse', 'label' => $label, 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse') ?GETPOST('idwarehouse') : 'ifone', 'idwarehouse', 'warehouseopen', 1, 0, 0, $langs->trans("NoStockAction"), 0, $forcecombo))
		//);
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('SetDraftMasterShipment'), $text, 'confirm_setdraft', $formquestion, 0, 1);
	}

	// Confirmation of action setvalidate
	if ($action == 'setvalidated') {
		$formquestion = array();
		$langs->load("stocks");
		$text = ''; //$langs->trans("ConfirmSetValidatedMasterShipmentForStock");
		//if (!empty($conf->global->MASTERSHIPMENT_DEFAULT_PICKING_LOCATION)) {
		//	$warehouseid = $conf->global->MASTERSHIPMENT_DEFAULT_PICKING_LOCATION;
		//} else {
		//	$warehouseid = 'ifone';
		//}
		//$formquestion = array(
		//	array('type' => 'other', 'name' => 'idwarehouse', 'label' => $label, 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse') ?GETPOST('idwarehouse') : $warehouseid, 'idwarehouse', '', 1, 0, 0, $langs->trans("NoStockAction"), 0, $forcecombo))
		//);
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('SetValidatedMasterShipment'), $text, 'confirm_setvalidated', $formquestion, 0, 1);
	}

	// Confirmation of action setvalidate
	if ($action == 'setpicked') {
		$formquestion = array();
		$langs->load("stocks");
		$text = ''; //$langs->trans("ConfirmSetValidatedMasterShipmentForStock");
		//if (!empty($conf->global->MASTERSHIPMENT_DEFAULT_PICKING_LOCATION)) {
		//	$warehouseid = $conf->global->MASTERSHIPMENT_DEFAULT_PICKING_LOCATION;
		//} else {
		//	$warehouseid = 'ifone';
		//}
		//$formquestion = array(
		//	array('type' => 'other', 'name' => 'idwarehouse', 'label' => $label, 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse') ?GETPOST('idwarehouse') : $warehouseid, 'idwarehouse', '', 1, 0, 0, $langs->trans("NoStockAction"), 0, $forcecombo))
		//);
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('SetPickedMasterShipment'), $text, 'confirm_setpicked', $formquestion, 0, 1);
	}

	// Confirmation of undo load, will delete all shipmentpackage and shipment made
	if ($action == 'confirm_load' && GETPOST('undo_load')) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('UndoAllLoad'), $langs->trans('ConfirmUndoAllLoading'), 'confirm_undo_load', '', 0, 1);
	}

	// Confirmation of undo all lines
	if ($action == 'undoall') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('UndoAll'), $langs->trans('ConfirmUndoAll'), 'confirm_undoall', '', 0, 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/batchshipment/mastershipment_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string'.(getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE') ? ':'.getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
		if (!getDolGlobalInt('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
		}
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			$morehtmlref .= '<br>';
			if ($permissiontoadd) {
				$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
				}
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= $proj->getNomUrl(1);
					if ($proj->title) {
						$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
					}
				}
			}
		}
	*/
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Shipping Method
	print '<tr><td>';
	print $form->editfieldkey("SendingMethod", 'shippingmethod', '', $object, (int) $permissiontoadd);
	print '</td><td class="valuefield">';
	if ($action == 'editshippingmethod') {
		$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, (string) $object->fk_shipping_method, 'fk_shipping_method', 1);
	} else {
		$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, (string) $object->fk_shipping_method, 'none');
	}
	print '</td>';
	print '</tr>';

	// Tracking URL
	if ($object->tracking_url) {
		print '<tr><td class="titlefield">'.$langs->trans("TrackingUrl").'</td><td colspan="3">';
		print $object->tracking_url;
		print '</td></tr>';
	}

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line)) {
		// Show object lines
		$result = $object->getLinesArray();

		if ($object->status == MasterShipment::STATUS_SHIPMENTONPROCESS || (!getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING') && $object->status == MasterShipment::STATUS_PICKED)) {
			print '	<form name="checking" id="check" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">
			<input type="hidden" name="token" value="' . newToken().'">
			<input type="hidden" name="action" value="confirm_check">
			<input type="hidden" name="confirm" value="yes">
			<input type="hidden" name="mode" value="">
			<input type="hidden" name="page_y" value="">
			<input type="hidden" name="id" value="' . $object->id.'">
			';
		} elseif ($object->status == MasterShipment::STATUS_PICKED) {
			print '	<form name="loading" id="load" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">
			<input type="hidden" name="token" value="' . newToken().'">
			<input type="hidden" name="action" value="confirm_load">
			<input type="hidden" name="confirm" value="yes">
			<input type="hidden" name="mode" value="">
			<input type="hidden" name="page_y" value="">
			<input type="hidden" name="id" value="' . $object->id.'">
			';
		} elseif ($object->status == MasterShipment::STATUS_VALIDATED) {
			print '	<form name="pick" id="pick" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">
			<input type="hidden" name="token" value="' . newToken().'">
			<input type="hidden" name="action" value="confirm_pick">
			<input type="hidden" name="confirm" value="yes">
			<input type="hidden" name="mode" value="">
			<input type="hidden" name="page_y" value="">
			<input type="hidden" name="id" value="' . $object->id.'">
			';
		} else {
			print '	<form name="draft" id="draft" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">
			<input type="hidden" name="token" value="' . newToken().'">
			<input type="hidden" name="action" value="confirm_group">
			<input type="hidden" name="confirm" value="yes">
			<input type="hidden" name="mode" value="">
			<input type="hidden" name="page_y" value="">
			<input type="hidden" name="id" value="' . $object->id.'">
			';
		}

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		// Link to autofill qty_pick or qty_load fields with the line quantity
		if (!empty($conf->use_javascript_ajax) && $permissiontoadd) {
			if ($object->status == MasterShipment::STATUS_VALIDATED) {
				print '<center>';
				print '<a id="fillqtypick" class="marginrightonly paddingright marginleftonly paddingleft" href="#">'.img_picto('', 'autofill', 'class="paddingrightonly"').$langs->trans('AutofillQtyToPick').'</a>';
				print '<script>';
				print '$(document).ready(function() {';
				print '	$("#fillqtypick").on("click", function(){
							$(".qty_pick_input").each(function(){
								var expectedqty = $(this).closest("tr").find(".expectedqty_pick").text();
								$(this).val(expectedqty);
							});
							return false;
						});';
				print '});';
				print '</script>';
				print '<br><br></center>';
			} elseif ($object->status == MasterShipment::STATUS_PICKED && getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING')) {
				print '<center>';
				print '<a id="fillqtyload" class="marginrightonly paddingright marginleftonly paddingleft" href="#">'.img_picto('', 'autofill', 'class="paddingrightonly"').$langs->trans('AutofillQtyToLoad').'</a>';
				print '<script>';
				print '$(document).ready(function() {';
				print '	$("#fillqtyload").on("click", function(){
							$(".qty_load_input").each(function(){
								var expectedqty = $(this).closest("tr").find(".expectedqty_load").text();
								$(this).val(expectedqty);
							});
							return false;
						});';
				print '});';
				print '</script>';
				print '<br><br></center>';
			}
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		$defaulttpldir = '/custom/batchshipment/tpl/mastershipment';
		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, null, GETPOSTINT('lineid'), 1, $defaulttpldir);
		}

		// Buttons for picking and loading actions which are defined in the objectline create template
		if ($permissiontoadd && $action != 'selectlines') {
			if ($action != 'editline') {
				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}
				if (empty($reshook)) {
					$object->formAddObjectLine(1, $mysoc, $soc, $defaulttpldir);
				}
			}
		}

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	}


	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// checks
			$disableBackToDraftWarning = '';
			$disableBackToValidateWarning = '';
			$disableCloseWarning = '';
			$disableValidateWarning = '';
			$disableValidateLoadingWarning = '';
			$disableBackToPickedWarning = '';
			$disableValidatePickingWarning = '';
			$rightfordraft = $permissiontoadd;
			foreach ($object->lines as $line) {
				if ($line->status != MasterShipmentLine::STATUS_GROUPED && $line->status != MasterShipmentLine::STATUS_DRAFT) {
					$rightfordraft = false;
					$disableBackToDraftWarning = $langs->trans('NotAllLinesStatusDraft');
					break;
				}
			}
			$rightforbacktovalidate = $permissiontoadd;
			$rightforbacktopicked = $permissiontoadd;
			$allowClosing = $permissiontoadd;
			$allowValidate = $permissiontoadd;
			$allowValidatePicking = $permissiontoadd;
			$allowValidateLoading = $permissiontoadd;
			if (!getDolGlobalInt('BATCHSHIPMENT_ALLOW_PICKING_NOT_GROUPED')) {
				foreach ($object->lines as $line) {
					if ($line->status != MasterShipmentLine::STATUS_GROUPED) {
						$allowValidate = false;
						$disableValidateWarning =  $langs->trans('NotAllLinesStatusGrouped');
						break;
					}
				}
			}
			foreach ($object->lines as $line) {
				if ($line->status != MasterShipmentLine::STATUS_PICKED) {
					$allowValidatePicking = false;
					$rightforbacktovalidate = false;
					$disableValidatePickingWarning = $langs->trans('NotAllLinesPicked');
					$disableBackToValidateWarning = $langs->trans('NotAllLinesPicked');
					break;
				}
			}
			foreach ($object->lines as $line) {
				if ($line->status != MasterShipmentLine::STATUS_LOADED) {
					$allowValidateLoading = false;
					$disableValidateLoadingWarning =  $langs->trans('NotAllLinesStatusLoaded');
					break;
				}
			}
			foreach ($object->lines as $line) {
				if ($line->fk_expedition > 0) {
					$rightforbacktopicked = false;
					$disableBackToPickedWarning = $langs->trans('shipmentsCreated');
					break;
				}
			}
			foreach ($object->lines as $line) {
				if ($line->status != MasterShipmentLine::STATUS_CHECKED) {
					$allowClosing = false;
					$disableCloseWarning = $langs->trans('NotAllLinesChecked');
				}
			}
			if ($object->fk_shipping_method > 0 && $object->fk_soc > 0 && empty($object->tracking_number)) {
				// not allow to close for a specific customer mastershipment if there is a shipping method and no tracking number set
				$allowClosing = false;
				$disableCloseWarning = $langs->trans('TrackingMissing');
			}
			// Send
			if (empty($user->socid)) {
				print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
			}

			// Modify
			print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				$buttonParams = array();
				if (!$rightfordraft) $buttonParams['attr']['title'] = $disableBackToDraftWarning;
				print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $rightfordraft, $buttonParams);
			}

			// Back to validated
			if ($object->status == $object::STATUS_PICKED) {
				$buttonParams = array();
				if (!$rightforbacktovalidate) $buttonParams['attr']['title'] = $disableBackToValidateWarning;
				print dolGetButtonAction($langs->trans('SetToValidated'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setvalidated&confirm=no&token='.newToken(), '', $rightforbacktovalidate, $buttonParams);
			}

			// Back to picked
			if ($object->status == $object::STATUS_SHIPMENTONPROCESS) {
				$buttonParams = array();
				if (!$rightforbacktopicked) $buttonParams['attr']['title'] = $disableBackToPickedWarning;
				print dolGetButtonAction($langs->trans('SetToPicked'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setpicked&confirm=no&token='.newToken(), '', $rightforbacktopicked, $buttonParams);
			}

			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					$buttonParams = array();
					if (!$allowValidate) $buttonParams['attr']['title'] = $disableValidateWarning;
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $allowValidate, $buttonParams);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Validate picking
			if ($object->status == $object::STATUS_VALIDATED) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					$buttonParams = array();
					if (!$allowValidatePicking) $buttonParams['attr']['title'] = $disableValidatePickingWarning;
					print dolGetButtonAction($langs->trans('ValidatePicking'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_picked&confirm=yes&token='.newToken(), '', $allowValidatePicking, $buttonParams);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}

			// Validate Loading
			if ($object->status == $object::STATUS_PICKED && getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING')) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					$buttonParams = array();
					if (!$allowValidateLoading) $buttonParams['attr']['title'] = $disableValidateLoadingWarning;
					print dolGetButtonAction($langs->trans('ValidateLoading'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_shipmentonprocess&confirm=yes&token='.newToken(), '', $allowValidateLoading, $buttonParams);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("ValidateLoading"), 'default', '#', '', 0);
				}
			}

			// close
			if ($object->status == $object::STATUS_SHIPMENTONPROCESS || ($object->status == $object::STATUS_PICKED && !getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING'))) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					$buttonParams = array();
					if (!$allowClosing) $buttonParams['attr']['title'] = $disableCloseWarning;
					print dolGetButtonAction($langs->trans('Close'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&confirm=no&token='.newToken(), '', $allowClosing, $buttonParams);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Close"), 'default', '#', '', 0);
				}
			}

			if ($object->status == $object::STATUS_DRAFT) {
				// Delete (with preloaded confirm popup)
				$deleteUrl = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken();
				$buttonId = 'action-delete-no-ajax';
				if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)) {	// We can use preloaded confirm if not jmobile
					$deleteUrl = '';
					$buttonId = 'action-delete';
				}
				$params = array();
				print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $deleteUrl, $buttonId, $permissiontodelete, $params);
			}
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->batchshipment->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('batchshipment:MasterShipment', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		$tmparray = $form->showLinkToObjectBlock($object, array(), array('mastershipment'), 1);
		if (is_array($tmparray)) {
			$linktoelem = $tmparray['linktoelem'];
			$htmltoenteralink = $tmparray['htmltoenteralink'];
			print $htmltoenteralink;
			$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
		} else {
			// backward compatibility
			$somethingshown = $form->showLinkedObjectBlock($object, $tmparray);
		}

		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/batchshipment/mastershipment_agenda.php', 1).'?id='.$object->id);

		$includeeventlist = 0;

		// List of actions on element
		if ($includeeventlist) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);
		}

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'mastershipment';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->batchshipment->dir_output;
	$trackid = 'mastershipment'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
