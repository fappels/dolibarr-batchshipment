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
 * $seller, $buyer
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */
dol_include_once('/commande/class/commande.class.php');

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

/**
 * @var Translate $langs
 * @var int $lineid
 * @var int $forceall
 * @var int $i
 */


global $forceall, $lineid;

if (empty($forceall)) $forceall = 0;


// Define colspan for the button 'Add'
$colspan = 3; // Columns: total ht + col edit + col delete

// Get order line to display some order line info like rang and delivery date
$line = new MasterShipmentLine($object->db);
$line->fetch($lineid);
$orderLine = new OrderLine($object->db);
$orderLine->fetch($line->fk_commande_line);
$orderLine->fetch_optionals();

print "<!-- BEGIN PHP TEMPLATE objectline_edit.tpl.php -->\n";

$coldisplay=0;
print '<tr class="oddeven tredited">';
// Adds a line numbering column
if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
	print '<td class="linecolnum center">'.($i+1).'</td>';
	$coldisplay++;
	print '<td><div id="line_'.$line->id.'"></div></td>';
}
print '<input type="hidden" name="lineid" value="'.$line->id.'">';
$coldisplay++;
print '<td class="bordertop nobottom linecol">';
$statustoshow = 1;
if ($line->fk_product > 0) {
	print $line->showOutputField($line->fields['fk_product'], 'fk_product', $line->fk_product);
} else {
	if ($orderLine->id > 0) {
		print $orderLine->desc;
	}
}

print '</td>';
$coldisplay++;
print '<td class="linecol">';
print $line->showOutputField($line->fields['fk_commande'], 'fk_commande', $line->fk_commande);
print '</td>';
$coldisplay++;
print '<td class="linecolqty right">' . ($orderLine->rang ? $orderLine->rang : 1);
$coldisplay++;
print '<td class="linecol">' . ($order->delivery_date ? dol_print_date($order->delivery_date, 'day') : '');
print '</td>';
$coldisplay++;
print '<td class="linecolqty minwidth200imp">' . $line->qty;
print '</td>';

$coldisplay++;
print '<td class="bordertop nobottom linecolqty"><input type="text" size="2" name="qty_pick" id="qty_pick" class="flat right" value="'.$line->qty_pick.'">';
print '</td>';

$coldisplay++;
print '<td class="linecolqty right">' . $line->qty_load;
print '</td>';

if (!empty($conf->productbatch->enabled)) {
	$coldisplay++;
	print '<td class="bordertop nobottom linecolqty">';
	print $formproduct->selectLotStock($line->fk_productbatch, 'fk_productbatch', '', 0, 0, $line->fk_product);
	print $line->showInputField(null, 'fk_productlot', $line->fk_productlot);
	print '</td>';
}

$coldisplay++;
print '<td class="linecol">';
print $line->showOutputField($line->fields['fk_entrepot'], 'fk_entrepot', $line->fk_entrepot);
print '</td>';
$coldisplay++;
print '<td class="bordertop nobottom linecoldescription minwidth200imp">';
print '<input type="text" size="20" name="comment" id="line_comment" class="flat right" value="'.$line->comment.'">';
print '</td>';

$coldisplay+=$colspan;
print '<td class="nobottom linecoledit center valignmiddle" colspan="'.$colspan.'">';
$coldisplay+=$colspan;
print '<input type="submit" class="button" id="savelinebutton" name="save" value="'.$langs->trans("Save").'">';
print '<br>';
print '<input type="submit" class="button" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'">';
print '</td>';
print '</tr>';


print "<!-- END PHP TEMPLATE objectline_edit.tpl.php -->\n";
