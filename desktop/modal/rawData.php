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

$vin = $car->getVin();
$data = $car->getRawDatas();
echo "<span class='hidden full_json'>" , json_encode($data,JSON_PRETTY_PRINT) , "</span>";
foreach ($data as $endpoint => $values) {
	?>
	<div class="rawData">
		<div class="rawData-heading">
			<span class="show_values"> <i class="fas fa-caret-right"></i> </span>
			<span class="hidde_values"> <i class="fas fa-caret-down"></i> </span>
			Endpoint: <span class="endpointName"><?=$endpoint?></span>
		</div>
		<div class="rawData-body">
			<pre><?= str_replace($vin, "{VIN}", json_encode($values,JSON_PRETTY_PRINT)) ?></pre>
		</div>
	</div>
	<?php
}
?>
<?php include_file('desktop', 'rawData', 'js', 'volvocars'); ?>
