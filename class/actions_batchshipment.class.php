<?php
/* Copyright (C) 2023		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2026		Francis Appels				<francis.appels@z-application.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    batchshipment/class/actions_batchshipment.class.php
 * \ingroup batchshipment
 * \brief   Example hook overload.
 *
 * TODO: Write detailed description here.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';

/**
 * Class ActionsBatchShipment
 */
class ActionsBatchShipment extends CommonHookActions
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Errors
	 */
	public $errors = array();


	/**
	 * @var mixed[] Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var ?string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param	array<string,mixed>	$parameters	Array of parameters
	 * @param	CommonObject		$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string				$action		'add', 'update', 'view'
	 * @return	int								Return integer <0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *											>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overload the doActions function : replacing the parent's function with the one below
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param	CommonObject		$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	?string				$action			Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {	    // do something only for the context 'somecontext1' or 'somecontext2'
			// Do what you want here...
			// You can for example load and use call global vars like $fieldstosearchall to overwrite them, or update the database depending on $action and GETPOST values.

			if (!$error) {
				$this->results = array('myreturn' => 999);
				$this->resprints = 'A text to show';
				return 0; // or return 1 to replace standard code
			} else {
				$this->errors[] = 'Error message';
				return -1;
			}
		}

		return 0;
	}


	/**
	 * Overload the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param	CommonObject		$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	?string				$action			Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		$result = 0;

		$massAction = $parameters['massaction'];

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('orderlist', 'orderlistdetail'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			if (preg_match('/^add_to_mastershipment_([0-9]+)/', $massAction, $matches)) {
				$mastershipmentId = $matches[1];
				if ($mastershipmentId > 0) {
					if ($parameters['currentcontext'] == 'orderlistdetail') {
						$orderlinesSelected = true;
					} else {
						$orderlinesSelected = false;
					}
					$selected = array();
					if (count($parameters['toselect']) > 0) {
						$selected = $parameters['toselect'];
					}
					foreach ($selected as $objectid) {
						$order = new Commande($this->db);
						$orderLine = new OrderLine($this->db);
						if ($orderlinesSelected) {
							$orderLine->fetch($objectid);
							if ($orderLine->id > 0) {
								$order->fetch($orderLine->fk_commande);
							}
						} else {
							$order->fetch($objectid);
						}
						$addedToMastershipment = false;
						if ($order->id > 0 && ($order->status == Commande::STATUS_VALIDATED || $order->status == Commande::STATUS_SHIPMENTONPROCESS)) {
							dol_include_once('/batchshipment/class/mastershipment.class.php');
							$mastershipment = new MasterShipment($this->db);
							$mastershipment->fetch($mastershipmentId);
							$mastershipmentLine = new MasterShipmentLine($this->db);
							if (!$orderlinesSelected) {
								$mastershipmentLines = $mastershipmentLine->fetchAll('', '', 0, 0, 'fk_commande:=:' . $order->id);
							} else {
								$mastershipmentLines = $mastershipmentLine->fetchAll('', '', 0, 0, '(fk_commande:=:' . $order->id . ') AND (fk_commande_line:=:' . $orderLine->id . ')');
							}

							$order->loadExpeditions();
							foreach ($order->lines as $line) {
								if (!$orderlinesSelected || $line->id == $orderLine->id) {
									// check if order already in mastershipment and/or shipment
									$qtyInMaster = 0;
									$qtyInMasterLoaded = 0;
									if (is_array($mastershipmentLines) && count($mastershipmentLines) > 0) {
										foreach ($mastershipmentLines as $resultLine) {
											if ($resultLine->fk_commande_line == $line->id) {
												$qtyInMaster += $resultLine->qty;
												$qtyInMasterLoaded += $resultLine->qty_loaded;
											}
										}
									}
									if ($orderlinesSelected) {
										$toShipQty = $orderLine->qty - $qtyInMaster - ($order->expeditions[$line->id] + $qtyInMasterLoaded);
									} else {
										$toShipQty = $line->qty - $qtyInMaster - ($order->expeditions[$line->id] + $qtyInMasterLoaded);
									}
									if ($qtyInMaster >= $line->qty) {
										setEventMessages($langs->trans('AlreadyAddedToMasterShipment', ($line->product_ref ? $line->product_ref : $line->desc), $order->ref), null, 'warnings');
									} else {
										$result = $this->addMastershipmentLine($user, $mastershipment, $order, $line, $toShipQty);
										if ($result < 0) {
											$error++;
											break;
										}
										if ($result > 0) {
											$addedToMastershipment = true;
										}
									}
								}
							}
							if ($result < 0) break;
							if ($addedToMastershipment) {
								$order->array_options['options_batchshipment_mastershipment'] = $mastershipment->id;
								$order->insertExtraFields();
								setEventMessages($langs->trans('AddedToMasterShipment', $order->ref), null, 'mesgs');
							} else {
								setEventMessages($langs->trans('NotAddedToMasterShipment', $order->ref), null, 'warnings');
							}
							// sort mastershipment lines by product
							$mastershipment->sortLines($user, array(array('sortfield' => 'fk_product', 'sortorder' => 'ASC'), array('sortfield' => 'qty', 'sortorder' => 'DESC')));
						} else {
							$error++;
							$this->errors[] = 'You can only add to a master shipment from a validated order or partial delivered order.';
						}
					}
				} else {
					$error++;
					$this->errors[] = 'Error on master shipment id.';
				}
			} elseif ($massAction == 'create_mastershipment') {
				if ($parameters['currentcontext'] == 'orderlistdetail') {
					$orderlinesSelected = true;
				} else {
					$orderlinesSelected = false;
				}
				$selected = array();
				if (count($parameters['toselect']) > 0) {
					$selected = $parameters['toselect'];
				}
				if (count($selected) > 0) {
					dol_include_once('/batchshipment/class/mastershipment.class.php');
					$mastershipment = new MasterShipment($this->db);
					$result = $mastershipment->create($user);
					if ($result > 0) {
						foreach ($selected as $objectid) {
							$order = new Commande($this->db);
							$orderLine = new OrderLine($this->db);
							if ($orderlinesSelected) {
								$orderLine->fetch($objectid);
								if ($orderLine->id > 0) {
									$order->fetch($orderLine->fk_commande);
								}
							} else {
								$order->fetch($objectid);
							}
							$addedToMastershipment = false;
							if ($order->id > 0 && ($order->status == Commande::STATUS_VALIDATED || $order->status == Commande::STATUS_SHIPMENTONPROCESS)) {
								$mastershipmentLine = new MasterShipmentLine($this->db);
								if (!$orderlinesSelected) {
									$mastershipmentLines = $mastershipmentLine->fetchAll('', '', 0, 0, 'fk_commande:=:' . $order->id);
								} else {
									$mastershipmentLines = $mastershipmentLine->fetchAll('', '', 0, 0, '(fk_commande:=:' . $order->id . ') AND (fk_commande_line:=:' . $orderLine->id . ')');
								}

								$order->loadExpeditions();
								foreach ($order->lines as $line) {
									if (!$orderlinesSelected || $line->id == $orderLine->id) {
										// check if order already in mastershipment
										$qtyInMaster = 0;
										$qtyInMasterLoaded = 0;
										if (is_array($mastershipmentLines) && count($mastershipmentLines) > 0) {
											foreach ($mastershipmentLines as $resultLine) {
												if ($resultLine->fk_commande_line == $line->id) {
													$qtyInMaster += $resultLine->qty;
													$qtyInMasterLoaded += $resultLine->qty_loaded;
												}
											}
										}
										if ($orderlinesSelected) {
											$toShipQty = $orderLine->qty - $qtyInMaster- ($order->expeditions[$line->id] + $qtyInMasterLoaded);
										} else {
											$toShipQty = $line->qty - $qtyInMaster- ($order->expeditions[$line->id] + $qtyInMasterLoaded);
										}
										if ($qtyInMaster >= $line->qty) {
											setEventMessages($langs->trans('AlreadyAddedToMasterShipment', ($line->product_ref ? $line->product_ref : $line->desc), $order->ref), null, 'warnings');
										} else {
											$result = $this->addMastershipmentLine($user, $mastershipment, $order, $line, $toShipQty);
											if ($result < 0) {
												$error++;
												break;
											}
											if ($result > 0) {
												$addedToMastershipment = true;
											}
										}
									}
								}
								if ($result < 0) break;
								if ($addedToMastershipment) {
									$order->array_options['options_batchshipment_mastershipment'] = $mastershipment->id;
									$order->insertExtraFields();
									setEventMessages($langs->trans('AddedToMasterShipment', $order->ref), null, 'mesgs');
								} else {
									setEventMessages($langs->trans('NotAddedToMasterShipment', $order->ref), null, 'warnings');
								}
								// sort mastershipment lines by product
								$mastershipment->sortLines($user, array(array('sortfield' => 'fk_product', 'sortorder' => 'ASC'), array('sortfield' => 'qty', 'sortorder' => 'DESC')));
							} else {
								$error++;
								$this->errors[] = 'You can only add to a master shipment from a validated or or partial delivered order.';
							}
						}
					} else {
						$error++;
						$this->errors = $mastershipment->errors;
					}
				}
			}
		}

		if (in_array($parameters['currentcontext'], array('shipmentlist'))) {
			if ($massAction == 'delete_shipment') {
				foreach ($parameters['toselect'] as $objectid) {
					$shipment = new Expedition($this->db);
					$shipment->fetch($objectid);
					if ($shipment->status < Expedition::STATUS_CLOSED) {
						$shipment->id = $objectid;
						$result = $shipment->delete();
						if ($result < 0) {
							$error--;
							$this->errors[] = $shipment->error;
						}
					} else {
						$error--;
						$this->errors[] = "Send shipment ".$shipment->ref." can't be deleted.";
					}
				}
			}
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}

		return 0;
	}


	/**
	 * Overload the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param	array<string,mixed>	$parameters     Hook metadata (context, etc...)
	 * @param	CommonObject		$object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	?string	$action						Current action (if set). Generally create or edit or null
	 * @param	HookManager	$hookmanager			Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$disabled = 1;

		if (in_array($parameters['currentcontext'], array('orderlist', 'orderlistdetail'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
			dol_include_once('/batchshipment/class/mastershipment.class.php');
			dol_include_once('/product/stock/class/entrepot.class.php');
			if ($user->hasRight('batchshipment', 'mastershipment', 'write')) {
				$disabled = 0;
			} else {
				$disabled = 1;
			}
			$mastershipment = new MasterShipment($this->db);
			$drafts = $mastershipment->fetchAll('', '', 0, 0, 'status:=:' . MasterShipment::STATUS_DRAFT);
			$this->resprints = '';
			if (is_array($drafts) && count($drafts) > 0) {
				foreach ($drafts as $mastershipment) {
					if (!empty($mastershimpment->label)) {
						$label = $mastershipment->label;
					} else {
						$label = $mastershipment->ref;
					}
					if (!empty($mastershipment->fk_entrepot)) {
						$entrepot = new Entrepot($this->db);
						$entrepot->fetch($mastershipment->fk_entrepot);
						$label .= ' - '.$entrepot->label;
					}
					$label = '<span class="fa fa-ship paddingrightonly"></span>'.$langs->trans("AddToMasterShipment", $label);
					$this->resprints .= '<option value="add_to_mastershipment_' . $mastershipment->id . '"'.($disabled?' disabled="disabled"':'').' data-html="'.dol_escape_htmltag($label).'">'.$label.'</option>';
				}
			}
			$label = '<span class="fa fa-ship paddingrightonly"></span>'.$langs->trans("CreateAndAddToNewMasterShipment");
			$this->resprints .= '<option value="create_mastershipment"'.($disabled?' disabled="disabled"':'').' data-html="'.dol_escape_htmltag($label).'">'.$label.'</option>';
		}

		if (in_array($parameters['currentcontext'], array('shipmentlist'))) {
			if ($user->rights->expedition->supprimer) {
				$disabled = 0;
			} else {
				$disabled = 1;
			}
			$label = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
			$this->resprints = '<option value="delete_shipment"'.($disabled?' disabled="disabled"':'').' data-html="'.dol_escape_htmltag($label).'">'.$label.'</option>';
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}



	/**
	 * Execute action before PDF (document) creation
	 *
	 * @param	array<string,mixed>	$parameters	Array of parameters
	 * @param	CommonObject		$object		Object output on PDF
	 * @param	string				$action		'add', 'update', 'view'
	 * @return	int								Return integer <0 if KO,
	 *											=0 if OK but we want to process standard actions too,
	 *											>0 if OK and we want to replace standard actions.
	 */
	public function beforePDFCreation($parameters, &$object, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		// @phan-suppress-next-line PhanPluginEmptyStatementIf
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
			// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}

	/**
	 * Execute action after PDF (document) creation
	 *
	 * @param	array<string,mixed>	$parameters	Array of parameters
	 * @param	CommonDocGenerator	$pdfhandler	PDF builder handler
	 * @param	string				$action		'add', 'update', 'view'
	 * @return	int								Return integer <0 if KO,
	 * 											=0 if OK but we want to process standard actions too,
	 *											>0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		// @phan-suppress-next-line PhanPluginEmptyStatementIf
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
			// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}



	/**
	 * Overload the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param	?string				$action 		Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager    Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	{
		global $langs;

		$langs->load("batchshipment@batchshipment");

		$this->results = array();

		$head = array();
		$h = 0;

		if ($parameters['tabfamily'] == 'batchshipment') {
			$head[$h][0] = dol_buildpath('/module/index.php', 1);
			$head[$h][1] = $langs->trans("Home");
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans("BatchShipment");
			$this->results['picto'] = 'batchshipment@batchshipment';
		}

		$head[$h][0] = 'customreports.php?objecttype='.$parameters['objecttype'].(empty($parameters['tabfamily']) ? '' : '&tabfamily='.$parameters['tabfamily']);
		$head[$h][1] = $langs->trans("CustomReports");
		$head[$h][2] = 'customreports';

		$this->results['head'] = $head;

		$arrayoftypes = array();
		//$arrayoftypes['batchshipment_mastershipment'] = array('label' => 'MyObject', 'picto'=>'mastershipment@batchshipment', 'ObjectClassName' => 'MyObject', 'enabled' => isModEnabled('batchshipment'), 'ClassPath' => "/batchshipment/class/mastershipment.class.php", 'langs'=>'batchshipment@batchshipment')

		$this->results['arrayoftype'] = $arrayoftypes;

		return 0;
	}



	/**
	 * Overload the restrictedArea function : check permission on an object
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param   CommonObject    	$object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string				$action			Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int									Return integer <0 if KO,
	 *												=0 if OK but we want to process standard actions too,
	 *												>0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea($parameters, $object, &$action, $hookmanager)
	{
		global $user;

		if ($parameters['features'] == 'mastershipment') {
			if ($user->hasRight('batchshipment', 'mastershipment', 'read')) {
				$this->results['result'] = 1;
				return 1;
			} else {
				$this->results['result'] = 0;
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Execute action completeTabsHead
	 *
	 * @param	array<string,mixed>	$parameters		Array of parameters
	 * @param	CommonObject		$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string				$action			'add', 'update', 'view'
	 * @param	Hookmanager			$hookmanager	Hookmanager
	 * @return	int									Return integer <0 if KO,
	 *												=0 if OK but we want to process standard actions too,
	 *												>0 if OK and we want to replace standard actions.
	 */
	public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user;

		if (!isset($parameters['object']->element)) {
			return 0;
		}
		if ($parameters['mode'] == 'remove') {
			// used to make some tabs removed
			return 0;
		} elseif ($parameters['mode'] == 'add') {
			$langs->load('batchshipment@batchshipment');
			// used when we want to add some tabs
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;
			// verifier le type d'onglet comme member_stats où ça ne doit pas apparaitre
			// if (in_array($element, ['societe', 'member', 'contrat', 'fichinter', 'project', 'propal', 'commande', 'facture', 'order_supplier', 'invoice_supplier'])) {
			if (in_array($element, ['context1', 'context2'])) {
				$datacount = 0;

				$parameters['head'][$counter][0] = dol_buildpath('/batchshipment/batchshipment_tab.php', 1) . '?id=' . $id . '&amp;module='.$element;
				$parameters['head'][$counter][1] = $langs->trans('BatchShipmentTab');
				if ($datacount > 0) {
					$parameters['head'][$counter][1] .= '<span class="badge marginleftonlyshort">' . $datacount . '</span>';
				}
				$parameters['head'][$counter][2] = 'batchshipmentemails';
				$counter++;
			}
			if ($counter > 0 && (int) DOL_VERSION < 14) {  // @phpstan-ignore-line
				$this->results = $parameters['head'];
				// return 1 to replace standard code
				return 1;
			} else {
				// From V14 onwards, $parameters['head'] is modifiable by reference
				return 0;
			}
		} else {
			// Bad value for $parameters['mode']
			return -1;
		}
	}


	/**
	 * Overload the showLinkToObjectBlock function : add or replace array of object linkable
	 *
	 * @param	array<string,mixed>	$parameters		Hook metadata (context, etc...)
	 * @param	CommonObject		$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	?string				$action			Current action (if set). Generally create or edit or null
	 * @param	HookManager			$hookmanager	Hook manager propagated to allow calling another hook
	 * @return	int									Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function showLinkToObjectBlock($parameters, &$object, &$action, $hookmanager)
	{
		$mastershipment = new MyObject($object->db);
		$this->results = array('mastershipment@batchshipment' => array(
			'enabled' => isModEnabled('batchshipment'),
			'perms' => 1,
			'label' => 'LinkToMyObject',
			'sql' => "SELECT t.rowid, t.ref, t.ref as 'name' FROM " . $this->db->prefix() . $mastershipment->table_element. " as t "),);

		return 1;
	}
	/* Add other hook methods here... */

	/**
	 * Overloading the printFieldListSelect function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListSelect($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter

		$this->resprints = '';

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('orderlist'))) {
			$this->resprints .= ', (SELECT SUM(cd.qty) FROM '.MAIN_DB_PREFIX.'commandedet as cd WHERE c.rowid=cd.fk_commande) as order_qty';
			$this->resprints .= ', (SELECT SUM(msds.qty_pick) FROM '.MAIN_DB_PREFIX.'batchshipment_mastershipmentdet as msds WHERE c.rowid=msds.fk_commande) as picked_qty';
			$this->resprints .= ', (SELECT SUM(msds.qty_load) FROM '.MAIN_DB_PREFIX.'batchshipment_mastershipmentdet as msds WHERE c.rowid=msds.fk_commande) as loaded_qty';
			$this->resprints .= ', (SELECT SUM(msds.qty) FROM '.MAIN_DB_PREFIX.'batchshipment_mastershipmentdet as msds WHERE c.rowid=msds.fk_commande) as ms_qty';
			$this->resprints .= ', (SELECT GROUP_CONCAT(DISTINCT mss.rowid, ",") FROM '.MAIN_DB_PREFIX.'batchshipment_mastershipmentdet as msds INNER JOIN '.MAIN_DB_PREFIX.'batchshipment_mastershipment mss ON mss.rowid = msds.fk_mastershipment WHERE c.rowid=msds.fk_commande) as mastershipment_ids';
			$this->resprints .= ', (SELECT GROUP_CONCAT(DISTINCT mss.ref, ",") FROM '.MAIN_DB_PREFIX.'batchshipment_mastershipmentdet as msds INNER JOIN '.MAIN_DB_PREFIX.'batchshipment_mastershipment mss ON mss.rowid = msds.fk_mastershipment WHERE c.rowid=msds.fk_commande) as mastershipment_refs';
			$this->resprints .= ', (SELECT GROUP_CONCAT(DISTINCT mss.label, ",") FROM '.MAIN_DB_PREFIX.'batchshipment_mastershipmentdet as msds INNER JOIN '.MAIN_DB_PREFIX.'batchshipment_mastershipment mss ON mss.rowid = msds.fk_mastershipment WHERE c.rowid=msds.fk_commande) as mastershipment_labels';
			$this->resprints .= ', (SELECT GROUP_CONCAT(DISTINCT mss.status, ",") FROM '.MAIN_DB_PREFIX.'batchshipment_mastershipmentdet as msds INNER JOIN '.MAIN_DB_PREFIX.'batchshipment_mastershipment mss ON mss.rowid = msds.fk_mastershipment WHERE c.rowid=msds.fk_commande) as mastershipment_statuses';
		}
		if ($parameters['currentcontext'] == 'shipmentlist') {
			$this->resprints .= ', ms.rowid as mastershipment_id, ms.ref as mastershipment_ref, ms.label as mastershipment_label, ms.status as mastershipment_status';
		}
		if ($parameters['currentcontext'] == 'shipmentpackagelist') {
			$this->resprints .= ', ms.rowid as mastershipment_id, ms.ref as mastershipment_ref, ms.label as mastershipment_label, ms.status as mastershipment_status';
		}

		if (! $error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the printFieldListFrom function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListFrom($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter

		$this->resprints = '';

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if ($parameters['currentcontext'] == 'shipmentlist') {
			$this->resprints .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as eem ON eem.sourcetype = 'shipping' AND eem.targettype = 'batchshipment_mastershipment' AND eem.fk_source = e.rowid";
			$this->resprints .= " LEFT JOIN ".MAIN_DB_PREFIX."batchshipment_mastershipment as ms ON ms.rowid=eem.fk_target";
		}
		if ($parameters['currentcontext'] == 'shipmentpackagelist') {
			$this->resprints .= " LEFT JOIN ".MAIN_DB_PREFIX."batchshipment_mastershipmentdet as msd ON msd.fk_shipmentpackage=t.rowid";
			$this->resprints .= " LEFT JOIN ".MAIN_DB_PREFIX."batchshipment_mastershipment as ms ON ms.rowid=msd.fk_mastershipment";
		}

		if (! $error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the printFieldListWhere function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListWhere($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */

		$this->resprints = '';
		if (in_array($parameters['currentcontext'], array('orderlist'))) {
			$search_mastershipment = GETPOST('search_mastershipment', 'alpha');
			if ($search_mastershipment != '') {
				$this->resprints .= " AND (SELECT GROUP_CONCAT(mss.ref, ',') FROM ".MAIN_DB_PREFIX."batchshipment_mastershipmentdet as msds INNER JOIN ".MAIN_DB_PREFIX."batchshipment_mastershipment mss ON mss.rowid = msds.fk_mastershipment WHERE c.rowid=msds.fk_commande) LIKE '%".$search_mastershipment."%'";
			}
		}
		if (in_array($parameters['currentcontext'], array('shipmentlist', 'shipmentpackagelist'))) {
			$search_mastershipment = GETPOST('search_mastershipment', 'alpha');
			if ($search_mastershipment != '') {
				$this->resprints .= natural_search('ms.ref', $search_mastershipment, 0);
			}
		}

		if (! $error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the printFieldPreListTitle function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldPreListTitle($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */

		if (! $error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

		/**
	 * Overloading the printFieldPreListTitle function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListSearchParam($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter

		$this->resprints = '';
		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('shipmentlist', 'shipmentpackagelist', 'orderlist'))) {
			$param = '&search_product=' . GETPOST('search_product') .
				'&search_description=' . GETPOST('search_description') .
				'&search_mastershipment=' . GETPOST('search_mastershipment') .
				'&search_shipment=' . GETPOST('search_shipment');
			$this->resprints = $param;
		}

		if (! $error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Overloading the printFieldListOption function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListOption($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter

		$this->resprints = '';

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('orderlist'))) {
			$this->resprints .= '<td class="liste_titre right">';
			$this->resprints .= '</td>';
			$this->resprints .= '<td class="liste_titre right">';
			$this->resprints .= '</td>';
			$this->resprints .= '<td class="liste_titre right">';
			$this->resprints .= '</td>';
			$this->resprints .= '<td class="liste_titre right">';
			$this->resprints .= '</td>';
			$search_mastershipment = GETPOST('search_mastershipment', 'alpha');
			$this->resprints .= '<td class="liste_titre left">';
			$this->resprints .= '<input class="flat" type="text" size="20" name="search_mastershipment" value="'.$search_mastershipment.'">';
			$this->resprints .= '</td>';
		}
		if (in_array($parameters['currentcontext'], array('shipmentlist', 'shipmentpackagelist'))) {
			$search_mastershipment = GETPOST('search_mastershipment', 'alpha');
			$this->resprints .= '<td class="liste_titre left">';
			$this->resprints .= '<input class="flat" type="text" size="15" name="search_mastershipment" value="'.$search_mastershipment.'">';
			$this->resprints .= '</td>';
		}

		if (! $error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * Execute action printFieldListTitle
	 *
	 * @param   array           $parameters     Array of parameters
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         'add', 'update', 'view'
	 * @param   Hookmanager     $hookmanager    hookmanager
	 * @return  int                             <0 if KO,
	 *                                          =0 if OK but we want to process standard actions too,
	 *                                          >0 if OK and we want to replace standard actions.
	 */
	public function printFieldListTitle(&$parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		$error = 0; // Error counter
		$this->resprints = '';

		if (in_array($parameters['currentcontext'], array('orderlist'))) {
			$langs->load("batchshipment@batchshipment");
			$arrayfields = $parameters['arrayfields'];
			$sortfield = $parameters['sortfield'];
			$sortorder = $parameters['sortorder'];
			$param = $parameters['param'];
			$this->resprints .= getTitleFieldOfList($langs->trans('OrderQuantity'), 0, $_SERVER["PHP_SELF"], 'order_qty', '', $param, '', $sortfield, $sortorder, 'right ');
			$this->resprints .= getTitleFieldOfList($langs->trans('MasterShipmentQuantity'), 0, $_SERVER["PHP_SELF"], 'ms_qty', '', $param, '', $sortfield, $sortorder, 'right ');
			$this->resprints .= getTitleFieldOfList($langs->trans('PickedQuantity'), 0, $_SERVER["PHP_SELF"], 'picked_qty', '', $param, '', $sortfield, $sortorder, 'right ');
			$this->resprints .= getTitleFieldOfList($langs->trans('LoadedQuantity'), 0, $_SERVER["PHP_SELF"], 'loaded_qty', '', $param, '', $sortfield, $sortorder, 'right ');
			$this->resprints .= getTitleFieldOfList('Master Shipment', 0, $_SERVER["PHP_SELF"], 'mastershipment_refs', '', $param, '', $sortfield, $sortorder, 'left ');
		}
		if (in_array($parameters['currentcontext'], array('shipmentlist', 'shipmentpackagelist'))) {
			$arrayfields = $parameters['arrayfields'];
			$sortfield = $parameters['sortfield'];
			$sortorder = $parameters['sortorder'];
			$param = $parameters['param'];
			$this->resprints .= getTitleFieldOfList($langs->trans('MasterShipment'), 0, $_SERVER["PHP_SELF"], 'mastershipment_ref', '', $param, '', $sortfield, $sortorder, 'left ');
		}

		if (! $error) {
			return 0;
		} else {
			return -1;
		}
	}

	/**
	 * Execute action printFieldListValue
	 *
	 * @param   array           $parameters     Array of parameters
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         'add', 'update', 'view'
	 * @param   Hookmanager     $hookmanager    hookmanager
	 * @return  int                             <0 if KO,
	 *                                          =0 if OK but we want to process standard actions too,
	 *                                          >0 if OK and we want to replace standard actions.
	 */
	public function printFieldListValue(&$parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter
		$this->resprints = '';
		$link = '';

		if (in_array($parameters['currentcontext'], array('orderlist'))) {
			$obj = $parameters['obj'];
			$this->resprints .= '<td>'.price($obj->order_qty).'</td>';
			$this->resprints .= '<td>'.price($obj->ms_qty).'</td>';
			$linkpostfields = '&sortfield=' . GETPOST('sortfield', 'alpha') .
			'&sortorder=' . GETPOST('sortorder', 'alpha') .
			'&search_sale=' . GETPOST('search_sale') .
			'&search_user=' . GETPOST('search_user') .
			'&search_product_category=' . GETPOST('search_product_category', 'alpha') .
			'&search_ref=' . GETPOST('search_ref') .
			'&search_ref_customer=' . GETPOST('search_ref_customer') .
			'&search_request_author=' . GETPOST('search_request_author') .
			'&search_company=' . GETPOST('search_company') .
			'&search_zip=' . GETPOST('search_zip') .
			'&search_ordermonth=' . GETPOST('search_ordermonth') .
			'&search_orderyear=' . GETPOST('search_orderyear') .
			'&search_total_ht=' . GETPOST('search_total_ht') .
			'&search_backorder=' . GETPOST('search_backorder') .
			'&search_product=' . GETPOST('search_product') .
			'&search_description=' . GETPOST('search_description') .
			'&search_billed=' . GETPOST('search_billed') .
			'&page=' . GETPOST('page', 'int') .
			'&limit=' . GETPOST('limit', 'int');
			$linkpostfields .= '&search_mastershipment=' . GETPOST('search_mastershipment');
			$searchMasterShipmentArray = GETPOST('search_options_mastershipment', 'array');
			if (count($searchMasterShipmentArray) > 0) {
				foreach ($searchMasterShipmentArray as $searchMasterShipment) {
					$linkpostfields .= '&search_options_mastershipment[]='.$searchMasterShipment;
				}
			}
			$this->resprints .= '<td>'.price($obj->picked_qty).'</td>';
			$this->resprints .= '<td>'.price($obj->loaded_qty).'</td>';
			$mastershipment_ids = explode(",", $obj->mastershipment_ids);
			$mastershipment_refs = explode(",", $obj->mastershipment_refs);
			$mastershipment_labels = explode(",", $obj->mastershipment_labels);
			$mastershipment_statuses = explode(",", $obj->mastershipment_statuses);
			if (is_array($mastershipment_ids) && count($mastershipment_ids) > 0) {
				$link = '';
				$idsLinked = array();
				foreach ($mastershipment_ids as $key => $mastershipment_id) {
					if ($mastershipment_id > 0 && !in_array($mastershipment_id, $idsLinked)) {
						$mastershipmentStatic = new MasterShipment($this->db);
						$mastershipmentStatic->id = $mastershipment_id;
						$mastershipmentStatic->ref = $mastershipment_refs[$key];
						$mastershipmentStatic->label = $mastershipment_labels[$key];
						$mastershipmentStatic->status = $mastershipment_statuses[$key];
						$link .= $mastershipmentStatic->getNomUrl().' ';
						$idsLinked[] = $mastershipment_id;
					}
				}
				$this->resprints .= '<td>'.$link.'</td>';
			} else {
				$this->resprints .= '<td></td>';
			}
		}

		if (in_array($parameters['currentcontext'], array('shipmentlist', 'shipmentpackagelist'))) {
			$obj = $parameters['obj'];
			if ($obj->mastershipment_id > 0) {
				dol_include_once('/batchshipment/class/mastershipment.class.php');
				$mastershipmentStatic = new MasterShipment($this->db);
				$mastershipmentStatic->id = $obj->mastershipment_id;
				$mastershipmentStatic->ref = $obj->mastershipment_ref;
				$mastershipmentStatic->label = $obj->mastershipment_label;
				$mastershipmentStatic->status = $obj->mastershipment_status;
				$link .= $mastershipmentStatic->getNomUrl().' ';
				$this->resprints .= '<td>'.$link.'</td>';
			} else {
				$this->resprints .= '<td></td>';
			}
		}

		if (! $error) {
			return 0;
		} else {
			return -1;
		}
	}

	/**
	 * Overloading the printFieldListOption function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListFooter($parameters, &$object, &$action, $hookmanager)
	{
		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */

		if (! $error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * add master shipment line
	 *
	 * @param User					$user			User
	 * @param MasterShipment		$mastershipment	Container object
	 * @param Commande				$object			order object
	 * @param OrderLine				$objectLine		order line object
	 * @param float					$qty			Qty to add
	 * @return int NOK < 0 > OK, 0 = no add line
	 */
	private function addMasterShipmentLine($user, $mastershipment, $object, $objectLine, $qty)
	{
		global $langs;

		$result = 0;
		// check if mastershipment is valid and if we have a qty to add
		if ($mastershipment->id > 0 && $qty > 0) {
			$update = false;
			$mastershipment->fetch($mastershipment->id);
			if (empty($mastershipment->fk_shipping_method) && !empty($object->shipping_method_id)) {
				$mastershipment->fk_shipping_method = $object->shipping_method_id;
				$update = true;
			} elseif (!empty($mastershipment->fk_shipping_method) && !empty($object->shipping_method_id) && $mastershipment->fk_shipping_method != $object->shipping_method_id) {
				$mastershipment->fk_shipping_method = -1;
				$update = true;
				setEventMessages('MastershipmentHasDifferentShippingMethods', null);
			}
			if (empty($mastershipment->fk_soc) && !empty($object->socid)) {
				$mastershipment->fk_soc = $object->socid;
				$update = true;
			} elseif ($mastershipment->fk_soc > 0 && !empty($object->socid) && $mastershipment->fk_soc != $object->socid) {
				$mastershipment->fk_soc = -1;
				$update = true;
				setEventMessages('MastershipmentHasDifferentCustomers', null);
			}
			$objectShippingContactIds = $object->getIdContact('external', 'SHIPPING');
			if (empty($mastershipment->fk_contact) && $objectShippingContactIds[0] > 0) {
				dol_include_once('contact/class/contact.class.php');
				$contact = new Contact($this->db);
				$contact->fetch($objectShippingContactIds[0]);
				$mastershipment->fk_contact = $contact->id;
				$mastershipment->address = $contact->address;
				$mastershipment->zip = $contact->zip;
				$mastershipment->town = $contact->town;
				$mastershipment->fk_country = $contact->country_id;
				$update = true;
			}
			if (empty($mastershipment->fk_contact)) {
				// get company address
				dol_include_once('societe/class/societe.class.php');
				$customer = new Societe($this->db);
				$customer->fetch($mastershipment->fk_soc);
				$mastershipment->address = $customer->address;
				$mastershipment->zip = $customer->zip;
				$mastershipment->town = $customer->town;
				$mastershipment->fk_country = $customer->country_id;
				$update = true;
			}
			if ($result >= 0) {
				if ($update) {
					$result = $mastershipment->update($user);
				}
				if ($result >= 0) {
					$result = 0;
					if ($objectLine->element == 'commandedet') {
						$mastershipmentLine = new MasterShipmentLine($this->db);
						$product = new Product($this->db);
						$stockObject = null;
						$addLine = true;
						if ($objectLine->fk_product > 0) {
							$product->fetch($objectLine->fk_product);
							$mastershipmentLine->qty = $qty; // set before addLine to be able to use it in getBestWarehouse and getBestLot
							$mastershipmentLine->fk_product = $product->id;
							$stockObject = $mastershipmentLine->getBestWarehouse($product, $qty, $mastershipment->fk_entrepot);
							if ($stockObject) {
								if ($stockObject->real < $qty && $mastershipment->stock_mode == 2) {
									$addLine = false;
								} elseif ($stockObject->real <= 0 && $mastershipment->stock_mode == 1) {
									$addLine = false;
								}
							} elseif ($mastershipment->stock_mode > 0) {
								$addLine = false;
							}
						} elseif ($mastershipment->stock_mode > 0) {
							$addLine = false; // free line also have no stock
						}
						if ($addLine) {
							$result = $mastershipment->addLine(
								$user,
								$objectLine->fk_product,
								$qty,
								$object->id,
								$objectLine->id,
								$objectLine->comment
							);
							if ($result < 0) {
								$this->errors = $mastershipment->errors;
							} /*elseif ($objectLine->fk_product > 0) {
								$mastershipmentLine->fetch($result); // refetch to set stock defaults
								if ($stockObject) {
									$mastershipmentLine->fk_entrepot = $stockObject->fk_entrepot;
									if ($product->hasbatch()) {
										$batch = $mastershipmentLine->getBestLot($stockObject, $qty);
										$mastershipmentLine->fk_productbatch = $batch ? $batch->id : 0;
									}
									$mastershipmentLine->update($user);
								} elseif ($mastershipment->fk_entrepot > 0) {
									$mastershipmentLine->fk_entrepot = $mastershipment->fk_entrepot;
									$mastershipmentLine->update($user);
								}
							} */
						} else {
							setEventMessages($langs->trans('NotEnoughStockForThisLine', ($objectLine->product_ref ? $objectLine->product_ref : $objectLine->desc)), null, 'warnings');
						}
					} else {
						$result = -1;
						$mastershipment->error = 'Bad addline source object type';
					}
				}

				if ($result < 0) {
					$this->errors = $mastershipment->errors;
				}
			}
		} else {
			if (empty($mastershipment->id) || $mastershipment->id <= 0) {
				$result = -1;
				$this->errors[] = 'InvalidMasterShipment';
			}
			if ($qty <= 0) {
				setEventMessages($langs->trans('NoQtyToAddForThisLine', ($objectLine->product_ref ? $objectLine->product_ref : $objectLine->desc)), null, 'warnings');
			}
		}
		return $result;
	}
}
