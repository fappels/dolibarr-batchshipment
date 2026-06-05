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
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 *
 * $type, $text, $description, $line
 */

/**
 * @var Translate $langs
 */

// Protection to avoid direct call of template
if (empty($object) || ! is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}
print "<!-- BEGIN PHP TEMPLATE objectline_title.tpl.php -->\n";
// Title line
print "<thead>\n";

print '<tr class="liste_titre nodrag nodrop">';

// Adds a line numbering column
if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<td class="linecolnum center">&nbsp;</td>';
print '<td class="linecol maxwidth200">'.$langs->trans('Product').'</td>';
print '<td class="linecol minwidth100">'.$langs->trans('Order').'</td>';
//print '<td class="linecolqty right">'.$langs->trans('LineNo').'</td>';
print '<td class="linecol">'.$langs->trans('DueDate').'</td>';
print '<td class="linecolqty right">'.$langs->trans('Quantity').'</td>';
if ($object->status >= MasterShipment::STATUS_VALIDATED) print '<td class="linecolqty right">'.$langs->trans('PickedQuantity').'</td>';
if ($object->status >= MasterShipment::STATUS_VALIDATED) print '<td class="linecolqty right">'.$langs->trans('LoadedQuantity').'</td>';
if (!empty($conf->productbatch->enabled)) print '<td class="linecoldescription right">'.$langs->trans('ProductLotBatch').'</td>'; // TODO add eatby/sellby for receptionpackage
if ($object->status >= MasterShipment::STATUS_VALIDATED) print '<td class="linecolqty right">'.$langs->trans('QtyTake').'</td>';
print '<td class="linecol">'.$langs->trans('Source').'</td>';
if ($object->status >= MasterShipment::STATUS_SHIPMENTONPROCESS) print '<td class="linecol">'.$langs->trans('Shipment').'</td>';
//if ($object->status >= MasterShipment::STATUS_SHIPMENTONPROCESS && isModEnabled('shipmentpackage')) print '<td class="linecol">'.$langs->trans('ShipmentPackage').'</td>';
if ($object->status >= MasterShipment::STATUS_VALIDATED) print '<td class="linecoldescription right">'.$langs->trans('Comment').'</td>';
print '<td class="linecol">' . $langs->trans('Status') . '</td>';

$disabled = '';
foreach ($object->lines as $line) {
	if ($line->status == MasterShipmentLine::STATUS_LOADED && $object->status == MasterShipment::STATUS_PICKED) $disabled = 'disabled';
	if ($line->status == MasterShipmentLine::STATUS_CHECKED && $object->status == MasterShipment::STATUS_SHIPMENTONPROCESS) $disabled = 'disabled';
}
print '<td class="linecolcheckall center">';
print '<input type="checkbox" class="linecheckboxtoggle" '. $disabled .' />';
print '<script>$(document).ready(function() {$(".linecheckboxtoggle").click(function() {var checkBoxes = $(".linecheckbox");checkBoxes.prop("checked", this.checked);})});</script>';
print '</td>';

print '<td></td>';

print "</tr>\n";
print "</thead>\n";

print "<!-- END PHP TEMPLATE objectline_title.tpl.php -->\n";
