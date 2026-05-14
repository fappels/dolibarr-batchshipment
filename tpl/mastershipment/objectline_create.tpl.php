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
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error: this template page cannot be called directly as an URL";
	exit;
}

/**
 * @var Translate $langs
 * @var int $forceall
 * @var int $forcetoshowtitlelines
 */

global $forceall, $forcetoshowtitlelines;

if (empty($forceall)) $forceall = 0;


// Define colspan for the button 'Add'
$colspan = 0; // Columns: total ht + col edit + col delete
//print $object->element;

$objectline = new MasterShipmentLine($this->db);

print "<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php -->\n";

$nolinesbefore = (count($this->lines) == 0 || $forcetoshowtitlelines);

print '<tr class="liste_titre nodrag nodrop">';
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
	print '<td class="linecolnum center"></td>';
	$colspan++;
}
print '<td class="linecol maxwidth200"></td>';
$colspan++;
print '<td class="linecol"></td>';
$colspan++;
print '<td class="linecol"></td>';
$colspan++;
print '<td class="linecolqty right"></td>';
$colspan++;
print '<td class="linecolqty right"></td>';
$colspan++;
print '<td class="linecolqty right"></td>';
$colspan++;
if (!empty($conf->productbatch->enabled)) {
	print '<td class="linecoldescription right"></td>';
	$colspan++;
}
$beforeSubmitButtonColspan = $colspan;
print '<td class="linecolqty right"></td>';
$colspan++;
print '<td class="linecol"></td>';
$colspan++;
if ($object->status >= MasterShipment::STATUS_VALIDATED) {
	print '<td class="linecol"></td>';
	$colspan++;
}
if ($object->status >= MasterShipment::STATUS_SHIPMENTONPROCESS && isModEnabled('shipmentpackage')) {
	print '<td class="linecol"></td>';
	$colspan++;
}
$colspan++;
print '<td class="linecoldescription right"></td>';
$colspan++;
print '<td class="linecol"></td>';
$colspan++;

print '<td class="linecolcheckall center"></td>';
$colspan++;

print '<td class="linecol"></td>';
$colspan++;
print '</tr>';
print '<tr>';
if (!$nolinesbefore) {
	print '<td class="bordertop nobottom linecoledit right valignmiddle" colspan="'.$beforeSubmitButtonColspan.'"></td>';
	if ($this->status == MasterShipment::STATUS_SHIPMENTONPROCESS) {
		print '<td class="bordertop nobottom linecoledit right valignmiddle">';
		print '<input type="submit" class="button" value="'.$langs->trans('CheckLoad').'" name="check" id="checkbutton">';
		print '</td>';
		$colspan -= $beforeSubmitButtonColspan;
		$colspan -= 1; // nbr of button
	} elseif ($this->status == MasterShipment::STATUS_PICKED) {
		print '<td class="bordertop nobottom linecoledit right valignmiddle">';
		print '<input type="submit" class="button" value="'.$langs->trans('Load').'" name="load" id="loadbutton">';
		print '</td>';
		print '<td></td>';
		print '<td class="bordertop nobottom linecoledit right valignmiddle">';
		print '<input type="submit" class="button" value="'.$langs->trans('UndoAllLoad').'" name="undo_load" id="undoloadbutton">';
		print '</td>';
		$colspan -= $beforeSubmitButtonColspan;
		$colspan -= 2; // nbr of button
	} elseif ($this->status == MasterShipment::STATUS_VALIDATED) {
		print '<td class="bordertop nobottom linecoledit right valignmiddle">';
		print '<input type="submit" class="button" value="'.$langs->trans('Pick').'" name="pick" id="pickbutton">';
		print '</td>';
		$colspan -= $beforeSubmitButtonColspan;
		$colspan -= 1; // nbr of button
	} elseif ($this->status == MasterShipment::STATUS_DRAFT) {
		print '<td class="bordertop nobottom linecoledit right valignmiddle">';
		print '<input type="submit" class="button" value="'.$langs->trans('Group').'" name="group" id="groupbutton">';
		print '</td>';
		$colspan -= $beforeSubmitButtonColspan;
		$colspan -= 1; // nbr of button
	}
	print '<td class="bordertop nobottom linecoledit right valignmiddle" colspan="'.$colspan.'"></td>';
}
print '</tr>';


?>

<script>

/* JQuery stuff */
jQuery(document).ready(function() {

});

</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->
