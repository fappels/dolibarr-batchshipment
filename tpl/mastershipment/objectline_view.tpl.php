<?php
/* Copyright (C) 2022      Francis Appels <francis.appels@z-application.com>
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
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $object_rights->creer initialized from = $object->getRights()
 * $disableedit, $disablemove, $disableremove
 *
 * $type, $text, $description, $line
 */

dol_include_once('/commande/class/commande.class.php');

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

/**
 * @var mixed $forceall
 * @var mixed $permissiontoadd
 * @var FormProduct $formproduct
 * @var int $i
 * @var Translate $langs
 */

global $forceall, $permissiontoadd, $formproduct, $stockObjects;

if (empty($dateSelector)) $dateSelector = 0;
if (empty($forceall)) $forceall = 0;

$disablemove = 1; // TODO debug line move

$stockObject = $stockObjects[$line->id];
if ($line->fk_product > 0) {
	// inputs to store changed warehouse
	print '<input type="hidden" name="changedline" value="">';
	print '<input type="hidden" name="changedwarehouse" value="">';
}
if (GETPOST('fk_entrepot', 'array')) {
	$fk_entrepotArray = GETPOST('fk_entrepot', 'array');
	$line->fk_entrepot = $fk_entrepotArray[$i + 1];
} elseif ($line->fk_entrepot) {
	$line->fk_entrepot = $line->fk_entrepot;
} elseif ($line->fk_product) {
	$line->fk_entrepot = $stockObject ? $stockObject->fk_entrepot : 0;
} elseif ($object->fk_entrepot) {
	$line->fk_entrepot = $object->fk_entrepot;
} else {
	$line->fk_entrepot = 0;
}

// add html5 elements
$domData  = ' data-element="'.$line->element.'"';
$domData .= ' data-id="'.$line->id.'"';
$domData .= ' data-warehouse_id="'.$line->fk_entrepot.'"';

// Get order line to display some order line info like rang and delivery date
$objectline = new MasterShipmentLine($object->db);
$orderLine = new OrderLine($object->db);
$orderLine->fetch($line->fk_commande_line);

$coldisplay = 0;
print "<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->\n";
print '<tr id="row-'.$line->id.'" class="drag drop oddeven" '.$domData.' >';
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
	print '<td class="linecolnum center">'.($i + 1).'</td>';
	$coldisplay++;
}

$coldisplay++;
print '<td class="linecol maxwidth200">';
if ($line->fk_product > 0) {
	print $objectline->showOutputField($objectline->fields['fk_product'], 'fk_product', $line->fk_product);
	// label
	$product = new Product($object->db);
	$product->fetch($line->fk_product);
	print ' - '.$product->label;
} else {
	if ($orderLine->id > 0) {
		print $orderLine->desc;
	}
}
print '</td>';
$linesChecked = GETPOST('line_checkbox', 'array');
$comments = GETPOST('comment', 'array');
$qtys = GETPOST('qty_group', 'array');
$qtysLoaded = GETPOST('qty_load', 'array');
$qtysPicked = GETPOST('qty_pick', 'array');
$lotLoaded = GETPOST('fk_productbatch', 'array');
if ($object->status >= MasterShipment::STATUS_PICKED) {
	$coldisplay++;
	print '<td class="linecol">';
	print $objectline->showOutputField($objectline->fields['fk_commande'], 'fk_commande', $line->fk_commande);
	// ref customer
	$order = new Commande($object->db);
	$order->fetch($line->fk_commande);
	if ($order->ref_client) print ' - '.$order->ref_client;
	print '</td>';
//	$coldisplay++;
//	print '<td class="linecolqty right">' . ($orderLine->rang ? $orderLine->rang : 1);
	print '</td>';
	$coldisplay++;
	print '<td class="linecol">' . ($order->delivery_date ? dol_print_date($order->delivery_date, 'day') : '');
	print '</td>';
	$coldisplay++;
	print '<td class="linecolqty right">' . $line->qty;
	print '</td>';
	$coldisplay++;
	print '<td class="linecolqty right"><span class="expectedqty_load">' . $line->qty_pick . '</span>';
	print '</td>';
	if (getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING')) {
		$coldisplay++;
		print '<td class="linecolqty right">' . $line->qty_load;
		print '</td>';
	}
	if (!empty($conf->productbatch->enabled)) {
		$coldisplay++;
		print '<td class="linecoldescription right">';
		if (empty($line->fk_productbatch)) {
			print $langs->trans('NA');
		} else {
			print $objectline->showOutputField($objectline->fields['fk_productlot'], 'fk_productlot', $line->fk_productlot);
			print '<input type="hidden" name="fk_productbatch['.($i + 1).']" value="'.$line->fk_productbatch.'">';
		}
		print '</td>';
	}
	if ($object->status == MasterShipment::STATUS_PICKED && getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING')) {
		$disabled = 0;
		if ($line->status == MasterShipmentLine::STATUS_LOADED) {
			$disabled = 1;
		}
		print '<td class="linecolqty right">';
		print '<input type="text" size="5" name="qty_load['.($i + 1).']" id="qty_load['.($i + 1).']" class="flat right qty_load_input" value="'.(isset($qtysLoaded[$i+1]) ? $qtysLoaded[$i+1] : $line->qty_load).'" ' . ($disabled ? 'disabled' : '') . '>';
		print '</td>';
		$coldisplay++;
	}
	print '<td class="linecol">';
	if (GETPOST('fk_entrepot', 'array')) {
		$fk_entrepotArray = GETPOST('fk_entrepot', 'array');
		$fk_entrepot = $fk_entrepotArray[$i + 1];
	} elseif ($line->fk_entrepot) {
		$fk_entrepot = $line->fk_entrepot;
	}
	print $objectline->showOutputField($objectline->fields['fk_entrepot'], 'fk_entrepot', $fk_entrepot) . (!empty($stockObject->real) ? ' (stock:' . $stockObject->real . ')' : '');
	print '<input type="hidden" name="fk_entrepot['.($i + 1).']" value="'.$fk_entrepot.'">';
	print '</td>';
	$coldisplay++;
	if ($object->status >= MasterShipment::STATUS_SHIPMENTONPROCESS || !getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING')) {
		print '<td class="linecol">';
		print $objectline->showOutputField($objectline->fields['fk_expedition'], 'fk_expedition', $line->fk_expedition);
		print '</td>';
	}
	//if ($object->status >= MasterShipment::STATUS_SHIPMENTONPROCESS && isModEnabled('shipmentpackage')) {
	//	print '<td class="linecol">';
	//	print $objectline->showOutputField($objectline->fields['fk_shipmentpackage'], 'fk_shipmentpackage', $line->fk_shipmentpackage);
	//	print '</td>';
	//	$coldisplay++;
	//}
	print '<td class="linecoldescription right">';
	print '<input type="text" name="comment['.($i + 1).']" id="line_comment['.($i + 1).']" class="flat right" value="'.(isset($comments[$i+1]) ? $comments[$i+1] : $line->comment).'">';
	print '</td>';
	$coldisplay++;
	print '<td class="linecol">' . $line->getLabelStatus(3) . '</td>';
	// tick
	$disabled = 1;
	if ($line->status == MasterShipmentLine::STATUS_LOADED && $object->status == MasterShipment::STATUS_SHIPMENTONPROCESS) {
		$disabled = 0;
	}
	if ($line->status == MasterShipmentLine::STATUS_PICKED && $object->status == MasterShipment::STATUS_PICKED) {
		$disabled = 0;
	}
	print '<td class="linecolcheck center">';
	if (isset($linesChecked[$i+1]) || ($line->status == MasterShipmentLine::STATUS_LOADED && $object->status == MasterShipment::STATUS_SHIPMENTONPROCESS) || $line->status == MasterShipmentLine::STATUS_CHECKED) {
		print '<input type="checkbox" class="linecheckbox" name="line_checkbox['.($i + 1).']" value="'.$line->id.'" checked ' . ($disabled ? 'disabled' : '') . ' >';
	} else {
		print '<input type="checkbox" class="linecheckbox" name="line_checkbox['.($i + 1).']" value="'.$line->id.'" ' . ($disabled ? 'disabled' : '') . ' >';
	}
	print '</td>';
	print '<td>';
	if ($line->status == MasterShipmentLine::STATUS_CHECKED && $object->status != MasterShipment::STATUS_CLOSED) {
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=undocheck&amp;token=' . newToken() . '&amp;lineid='.$line->id.'">'. img_left('Undo', 0, 'style="max-width: 20px"') .'</a>';
	} elseif ($line->status == MasterShipmentLine::STATUS_LOADED && $object->status == MasterShipment::STATUS_PICKED) {
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=undoline&amp;token=' . newToken() . '&amp;lineid='.$line->id.'">'. img_left('Undo', 0, 'style="max-width: 20px"') .'</a>';
	}
	print '</td>';
	$coldisplay = $coldisplay + 1;
} else {
	$coldisplay++;
	print '<td class="linecol">';
	print $objectline->showOutputField($objectline->fields['fk_commande'], 'fk_commande', $line->fk_commande);
	// ref customer
	$order = new Commande($object->db);
	$order->fetch($line->fk_commande);
	if ($order->ref_client) print ' - '.$order->ref_client;
	print '</td>';
//	$coldisplay++;
//	print '<td class="linecolqty right">' . ($orderLine->rang ? $orderLine->rang : 1);
	print '</td>';
	$coldisplay++;
	print '<td class="linecol">' . ($order->delivery_date ? dol_print_date($order->delivery_date, 'day') : '');
	print '</td>';
	$coldisplay++;
	print '<td class="linecolqty right">';
	if ($object->status == MasterShipment::STATUS_DRAFT) {
		print '<input type="text" size="5" name="qty_group['.($i + 1).']" id="qty_group['.($i + 1).']" class="flat right" value="'.(isset($qtys[$i+1]) ? $qtys[$i+1] : $line->qty).'">';
	} else {
		print '<span class="expectedqty_pick">' . $line->qty . '</span>';
	}
	print '</td>';
	if ($object->status >= MasterShipment::STATUS_VALIDATED) {
		$coldisplay++;
		print '<td class="linecolqty right">' . $line->qty_pick;
		print '</td>';
		if (getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING')) {
			$coldisplay++;
			print '<td class="linecolqty right">' . $line->qty_load;
			print '</td>';
		}
	}
	if (!empty($conf->productbatch->enabled)) {
		$coldisplay++;
		print '<td class="linecoldescription right">';
		if ($line->fk_product > 0 && $product->hasbatch() && $line->status == MasterShipmentLine::STATUS_DRAFT) {
			print '<input type="hidden" name="changedbatch" value="">';
			if ($line->fk_productbatch) {
				$selectedBatch = $line->fk_productbatch;
			} else {
				$selectedBatch = 0;
			}
			if ($selectedBatch < 0) {
				$showEmptyBatch = 1;
			} else {
				$showEmptyBatch = 0;
			}

			print $formproduct->selectLotStock((isset($lotLoaded[$i+1]) ? $lotLoaded[$i+1] : $selectedBatch), 'fk_productbatch['.($i + 1).']', '', $showEmptyBatch, 0, $line->fk_product, $line->fk_entrepot, array(), '', 0, array(), 'minwidth200 change-batch');
		} else if (empty($line->fk_productbatch)) {
			print $langs->trans('NA');
		} else {
			print $objectline->showOutputField($objectline->fields['fk_productlot'], 'fk_productlot', $line->fk_productlot);
			print '<input type="hidden" name="fk_productbatch['.($i + 1).']" value="'.$line->fk_productbatch.'">';
		}
		print '</td>';
	}

	$coldisplay++;
	$disabled = 0;
	if ($line->status == MasterShipmentLine::STATUS_PICKED) {
		$disabled = 1;
	}
	if ($object->status == MasterShipment::STATUS_VALIDATED) {
		print '<td class="linecolqty right">';
		print '<input type="text" size="5" name="qty_pick['.($i + 1).']" id="qty_pick['.($i + 1).']" class="flat right qty_pick_input" value="'.(isset($qtysPicked[$i+1]) ? $qtysPicked[$i+1] : $line->qty_pick).'" ' . ($disabled ? 'disabled' : '') . '>';
		$coldisplay++;
	}
	print '<td class="linecol">';

	if ($line->fk_product > 0) {
		if ($line->status == MasterShipmentLine::STATUS_DRAFT) {
			print $formproduct->selectWarehouses($line->fk_entrepot, 'fk_entrepot['.($i + 1).']', '', 0, 0, $line->fk_product, '', 1, 0, array(), 'minwidth200 change-warehouse');
		} else {
			print $objectline->showOutputField($objectline->fields['fk_entrepot'], 'fk_entrepot', $line->fk_entrepot) . (!empty($stockObject->real) ? ' (stock:' . $stockObject->real . ')' : '');
			print '<input type="hidden" name="fk_entrepot['.($i + 1).']" value="'.$line->fk_entrepot.'">';
		}
	} else {
		print $langs->trans('NA');
	}

	print '</td>';

	$disabled = 0;
	if (($line->status == MasterShipmentLine::STATUS_GROUPED && $object->status == MasterShipment::STATUS_DRAFT) || $line->status == MasterShipmentLine::STATUS_PICKED) {
		$disabled = 1;
	}
	if ($object->status >= MasterShipment::STATUS_VALIDATED) {
		print '<td class="linecoldescription right">';
		print '<input type="text" name="comment['.($i + 1).']" id="line_comment['.($i + 1).']" class="flat right" value="'.(isset($comments[$i+1]) ? $comments[$i+1] : $line->comment).'" ' . ($disabled ? 'disabled' : '') . '>';
		print '</td>';
		$coldisplay++;
	}
	print '<td class="linecol right">' . $line->getLabelStatus(3) . '</td>';
	// tick to pick or create shipments
	$coldisplay++;
	print '<td class="linecolcheck center">';
	if (isset($linesChecked[$i+1]) || ($line->status == MasterShipmentLine::STATUS_GROUPED && $object->status == MasterShipment::STATUS_DRAFT) || $line->status == MasterShipmentLine::STATUS_PICKED) {
		print '<input type="checkbox" class="linecheckbox" name="line_checkbox['.($i + 1).']" value="'.$line->id.'" checked ' . ($disabled ? 'disabled' : '') . '>';
	} else {
		print '<input type="checkbox" class="linecheckbox" name="line_checkbox['.($i + 1).']" value="'.$line->id.'" ' . ($disabled ? 'disabled' : '') . '>';
	}
	print '</td>';
	$coldisplay++;
	print '<td>';
	if (($line->status == MasterShipmentLine::STATUS_GROUPED && $object->status == MasterShipment::STATUS_DRAFT) || $line->status == MasterShipmentLine::STATUS_PICKED) {
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=undoline&amp;token=' . newToken() . '&amp;lineid='.$line->id.'">'. img_left('Undo', 0, 'style="max-width: 20px"') .'</a>';
	} elseif ($line->status == MasterShipmentLine::STATUS_DRAFT) {
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=deleteline&amp;token=' . newToken() . '&amp;lineid='.$line->id.'">'. img_delete('Delete', 0, 'style="max-width: 20px"') .'</a>';
	}
	//print;
	print '</td>';
}

print '</tr>';

print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";
