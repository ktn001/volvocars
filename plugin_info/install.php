<?php
// vim: tabstop=4 autoindent
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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function volvocars_goto_1() {
	$hrefs = array (
		'lock' => '/v2/vehicles/#VIN#/commands/lock',
		'unlock' => '/v2/vehicles/#VIN#/commands/unlock',
		'lockReduced' => '/v2/vehicles/#VIN#/commands/lock-reduced-guards',
		'climStart' => '/v2/vehicles/#VIN#/commands/climatization-start',
		'climStop' => '/v2/vehicles/#VIN#/commands/climatization-stop'
	);
	$cars = volvocars::byType('volvocars');
	foreach ($cars as $car){
		$cmdsConfig = array();
		foreach ($car->getCmdsConfig() as $config) {
			if ($config['type'] !== 'action') {
				continue;
			}
			if (! isset($config['_volvoName'])) {
				continue;
			}
			if (! isset($config['configuration'])) {
				continue;
			}
			if (! isset($config['configuration']['volvoApi'])) {
				continue;
			}
			$cmdsConfig[$config['logicalId']] = $config;
		}
		foreach ($car->getCmd('action') as $cmd) {
			if (! isset ($cmdsConfig[$cmd->getLogicalId()])) {
				continue;
			}
			if (isset($hrefs[$cmd->getLogicalId()])) {
				if ($cmd->getConfiguration('href') === '') {
					$href = str_replace('#VIN#',$car->getLogicalId(),$hrefs[$cmd->getLogicalId()]);
					$cmd->setConfiguration('href',$href);
					$cmd->save();
				}
				if (isset($cmdsConfig[$cmd->getLogicalId()])) {
					$cmdConfig = $cmdsConfig[$cmd->getLogicalId()];
					if (isset($cmdConfig['configuration'])) {
						if (isset($cmdConfig['configuration']['volvoApi'])) {
							$cmd->setConfiguration('volvoApi',$cmdConfig['configuration']['volvoApi']);
							$cmd->save();
						}
					}
				}
			}
		}
	}
}

function volvocars_upgrade() {

	$lastLevel = 1;

	$pluginLevel = config::byKey('pluginLevel','volvocars',0);
	log::add("volvocars","info","pluginLevel: " . $pluginLevel . " => " . $lastLevel);
	for ($level = 1; $level <= $lastLevel; $level++) {
		if ($pluginLevel < $level) {
			$function = 'volvocars_goto_' . $level;
			if (function_exists($function)) {
				log::add("volvocars","info","Execution de " . $function . "()");
				$function();
				config::save('pluginLevel',$level,'volvocars');
				$pluginLevel = $level;
				log::add("volvocars","info","pluginLevel: " . $pluginLevel);
			}
		}
	}
}

// Fonction exécutée automatiquement après l'installation du plugin
function volvocars_install() {
	log::add("volvocars","info","Lancement de 'volvocars_install()'");
	volvocars_upgrade();
	volvocars::setListeners();
}

// Fonction exécutée automatiquement après la mise à jour du plugin
function volvocars_update() {
	log::add("volvocars","info","Lancement de 'volvocars_update'");
	volvocars_upgrade();
	volvocars::setListeners();
}

// Fonction exécutée automatiquement après la suppression du plugin
function volvocars_remove() {
}
