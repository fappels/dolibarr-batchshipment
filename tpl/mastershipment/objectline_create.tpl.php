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
//$colspan++;
//print '<td class="linecol"></td>';
$colspan++;
print '<td class="linecol"></td>';
$colspan++;
print '<td class="linecolqty right"></td>';
$colspan++;
if ($object->status >= MasterShipment::STATUS_VALIDATED) {
	print '<td class="linecolqty right"></td>';
	$colspan++;
	if (getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING')) {
		print '<td class="linecolqty right"></td>';
		$colspan++;
	}
}
if (!empty($conf->productbatch->enabled)) {
	print '<td class="linecoldescription right"></td>';
	$colspan++;
}
$beforeSubmitButtonColspan = $colspan;
print '<td class="linecolqty right"></td>';
$colspan++;
print '<td class="linecol"></td>';
$colspan++;
if ($object->status == MasterShipment::STATUS_VALIDATED || $object->status == MasterShipment::STATUS_PICKED) {
	print '<td class="linecol"></td>';
	$colspan++;
}
if ($object->status >= MasterShipment::STATUS_SHIPMENTONPROCESS || (!getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING') && $object->status >= MasterShipment::STATUS_PICKED)) {
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
if ($object->status == MasterShipment::STATUS_VALIDATED || ($object->status == MasterShipment::STATUS_PICKED && getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING'))) {
	print '<td class="linecol"></td>';
	$colspan++;
}
print '</tr>';
print '<tr>';
if (!$nolinesbefore) {
	print '<td class="bordertop nobottom linecoledit right valignmiddle" colspan="'.$beforeSubmitButtonColspan.'"></td>';
	if ($this->status == MasterShipment::STATUS_SHIPMENTONPROCESS || ($this->status == MasterShipment::STATUS_PICKED && !getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING'))) {
		print '<td class="bordertop nobottom linecoledit right valignmiddle">';
		print '<input type="submit" class="button" value="'.$langs->trans('CheckLoad').'" name="check" id="checkbutton">';
		print '</td>';
		$colspan -= $beforeSubmitButtonColspan;
		$colspan -= 1; // nbr of button
	} elseif ($this->status == MasterShipment::STATUS_PICKED && getDolGlobalInt('BATCHSHIPMENT_TWO_STAGE_PICKING')) {
		print '<td class="bordertop nobottom linecoledit right valignmiddle">';
		print '<input type="submit" class="button" value="'.$langs->trans('Load').'" name="load" id="loadbutton">';
		print '</td>';
		$colspan -= $beforeSubmitButtonColspan;
		$colspan -= 1; // nbr of button
	} elseif ($this->status == MasterShipment::STATUS_VALIDATED) {
		print '<td class="bordertop nobottom linecoledit right valignmiddle">';
		print '<input type="submit" class="button" value="'.$langs->trans('Pick').'" name="pick" id="pickbutton">';
		print '</td>';
		$colspan -= $beforeSubmitButtonColspan;
		$colspan -= 1; // nbr of button
	} elseif ($this->status == MasterShipment::STATUS_DRAFT) {
		print '<td class="bordertop nobottom linecoledit right valignmiddle">';
		print '<input type="submit" class="button" value="'.$langs->trans('Split').'" name="split" id="splitbutton">';
		print '</td>';
		print '<td class="bordertop nobottom linecoledit right valignmiddle">';
		print '<input type="submit" class="button" value="'.$langs->trans('MergeLines').'" name="merge" id="mergebutton">';
		print '</td>';
		print '<td class="bordertop nobottom linecoledit right valignmiddle">';
		print '<input type="submit" class="button" value="'.$langs->trans('SetLines').'" name="group" id="groupbutton">';
		print '</td>';
		$colspan -= $beforeSubmitButtonColspan;
		$colspan -= 3; // nbr of button
	}
	print '<td class="bordertop nobottom linecoledit right valignmiddle" colspan="'.$colspan.'"></td>';
}
print '</tr>';


?>

<script>

/* JQuery stuff */
jQuery(document).ready(function() {
	// scroll back to the line whose select field triggered the page reload
	var scrollToLineId = sessionStorage.getItem('mastershipment_scrolltoline');
	if (scrollToLineId) {
		sessionStorage.removeItem('mastershipment_scrolltoline');
		var $scrollToRow = $('tr[data-id="' + scrollToLineId + '"]');
		if ($scrollToRow.length) {
			$('html, body').animate({ scrollTop: $scrollToRow.offset().top - 100 }, 300);
		}
	}

	$(".change-warehouse").change(function() {
		var entrepotid = $(this).val();
		var line = $(this).closest('tr').data('id');
		console.log("We have changed the warehouse " + entrepotid + " on line " + line + " - Reload page");
		// reload page
		sessionStorage.setItem('mastershipment_scrolltoline', line);
		$("input[name=changedline]").val(line);
		$("input[name=changedwarehouse]").val(entrepotid);
		if (<?php echo ($object->status == MasterShipment::STATUS_DRAFT ? 'true' : 'false'); ?>) {
			$("form[name=draft]").submit();
		} else {
			$("form[name=pick]").submit();
		}

	});
	$(".change-batch").change(function() {
		var batchid = $(this).val();
		var entrepotid = $(this).closest('tr').data('warehouse_id');
		var line = $(this).closest('tr').data('id');
		console.log("We have changed the batch " + batchid + " on line " + line + " - Reload page");
		// reload page
		sessionStorage.setItem('mastershipment_scrolltoline', line);
		$("input[name=changedline]").val(line);
		$("input[name=changedwarehouse]").val(entrepotid);
		$("input[name=changedbatch]").val(batchid);
		if (<?php echo ($object->status == MasterShipment::STATUS_DRAFT ? 'true' : 'false'); ?>) {
			$("form[name=draft]").submit();
		} else {
			$("form[name=pick]").submit();
		}
	});
});
</script>

<!-- END PHP TEMPLATE objectline_create.tpl.php -->
