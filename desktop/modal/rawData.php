<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

include_file('desktop', 'rawData', 'css', 'volvocars');

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

if (!isset($_GET['eqLogicId'])) {
	throw new Exception (__("ID du véhicule indéfini",__FILE__));
}
$car = eqLogic::byId($_GET['eqLogicId']);
if (!is_object($car)) {
	throw new Exception(sprintf(__("Véhicule %s introuvable",__FILE__),$_GET['eqLogicId']));
}

log::add("volvocars","debug","┌ " . __("Début de la récupération des données bruts",__FILE__));
$vin = $car->getVin();
$data = $car->getRawDatas();
log::add("volvocars","debug","└ " . __("Fin de la récupération des données bruts",__FILE__));
?>
<div class="input-group pull-right" style="display:inline-flex">
	<a class="btn btn-sm btn-default rawDataAction roundleft" data-action="plie">
		<i class="fas fa-caret-right"></i> <span>{{Plie tout}}</span>
	</a>
	<a class="btn btn-sm btn-default rawDataAction roundleft" data-action="deplie">
		<i class="fas fa-caret-down"></i> <span>{{Déplie tout}}</span>
	</a>
</div>
<div id="rawData">
<?php
foreach ($data as $endpoint => $values) {
	?>
	<div class="endpointEntry">
		<div class="endpointHeader">
			<span class="show_values"> <i class="fas fa-caret-right"></i> </span>
			<span class="hidde_values"> <i class="fas fa-caret-down"></i> </span>
			Endpoint: <span class="endpointName"><?=$endpoint?>
		</div>
		<div class="endpointValue">
			<pre><?= $values ?></pre>
		</div>
	</div>
	<?php
}
?>
</div>
<?php include_file('desktop', 'rawData', 'js', 'volvocars'); ?>
