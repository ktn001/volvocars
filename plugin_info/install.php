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

function volvocars_goto_8() {
	$cars = volvocars::byType('volvocars');
	$countFuelEngines = 0;
	foreach ($cars as $car) {
		#if ($car->getConfiguration('fuelEngine') == 1) {
		if ($car->getConfiguration('FuelEngine') == 1) {
			$countFuelEngines++;
		}
	}
	if ($countFuelEngines > 0) {
		message::add("Volvo", __("Veuillez relancer une Synchronisation des comptes",__FILE__));
	}
}

function volvocars_goto_7() {
	$cars = volvocars::byType('volvocars');
	foreach ($cars as $car){
		$roofStateCmd = $car->getCmd('info','roofState');
		if (! is_object($roofStateCmd)) {
			continue;
		}
		$roofState = $roofStateCmd->execCmd();
		if (! in_array($roofState,['OPEN','CLOSED','AJAR'])) {
			$roofStateCmd->remove();
		}
	}
}

function volvocars_goto_6() {
	config::save('use_widget_volvocars',1,'volvocars');
}

function volvocars_goto_5() {
	$cars = volvocars::byType('volvocars');
	foreach ($cars as $car){
		log::add("volvocars","info",sprintf(__("Traitement du véhicule %s",__FILE__),$car->getName()));
		$cmdsConfig = $car->getCmdsConfig();
		foreach ($cmdsConfig as $cmdConfig) {
			if (in_array($cmdConfig['logicalId'], ['doorFlState','doorFrState','doorRlState','doorRrState','hoodState','tailState','winFlState','winFrState','winRlState','winRrState','roofState'])) {
				$cmd = $car->getCmd(null,$cmdConfig['logicalId']);
				if (!is_object($cmd)) {
					continue;
				}
				$cmd->setConfiguration('dependencies',$cmdConfig['configuration']['dependencies']);
				$cmd->save();
			}
			if (in_array($cmdConfig['logicalId'], ['allDoorsClosed','allWinsClosed'])){
				log::add("volvocars","info","Création de " . $cmdConfig['logicalId']);
				$car->createCmd($cmdConfig['logicalId']);
			}
			if (in_array($cmdConfig['logicalId'], ['presenceSite1','presenceSite2'])) {
				$cmd = $car->getCmd(null,$cmdConfig['logicalId']);
				if (!is_object($cmd)) {
					continue;
				}
				$cmd->setConfiguration('dependTo',$cmdConfig['configuration']['dependTo']);
				$cmd->save();
			}
			if (in_array($cmdConfig['logicalId'], ['position','distanceSite1','distanceSite2'])) {
				$cmd = $car->getCmd(null,$cmdConfig['logicalId']);
				if (!is_object($cmd)) {
					continue;
				}
				$cmd->setConfiguration('dependencies',$cmdConfig['configuration']['dependencies']);
				$cmd->save();
			}
		}
	}
}

function volvocars_goto_4() {
	$cars = volvocars::byType('volvocars');
	foreach ($cars as $car){
		$car->setConfiguration('visibleOnPanel',1);
		$car->save();
	}
}

function volvocars_goto_3() {
	$cars = volvocars::byType('volvocars');
	foreach ($cars as $car){
		log::add("volvocars","info",sprintf(__("Traitement du véhicule %s",__FILE__),$car->getName()));
		$commandMapping = array (
			"HONK" => "honk",
			"FLASH" => "flash",
			"HONK_AND_FLASH" => "honk_flash",
		);
		$account = $car->getAccount();
		$payload = $account->getInfos('commands',$car->getVin(), true);
		if (!isset($payload['status']) || $payload['status'] !== 'ok') {
			$httpCode = isset($payload['httpCode']) ? $payload['httpCode'] : '';
			$message = isset($payload['message']) ? $payload['message'] : null;
			$description = isset($payload['description']) ? $payload['description'] : null;
			$detail = isset($payload['detail']) ? $payload['detail'] : null;
			log::add("volvocars","error","└" . sprintf(__("Echec de la synchonisation de l'account %s",__FILE__),$car->getName()));
			throw new volvoApiException('details',$httpCode,$message,$description,$detail);
		}
		if (!isset($payload['data'])) {
			log::add("volvocars","error","└" . __("Le payload %s n'a pas de 'data'", __FILE__));
			throw new Exception("no data");
		}
		$commands = $payload['data'];
		$cmdsConfig = $car->getCmdsConfig();
		foreach ($commands as $command) {
			if (!isset($commandMapping[$command['command']])) {
				continue;
			}
			log::add("volvocars","info",sprintf(__("Création de la commande %s",__FILE__),$commandMapping[$command['command']]));
			$car->createCmd($commandMapping[$command['command']]);
			$cmd=$car->getCmd(null,$commandMapping[$command['command']]);
			$cmd->setConfiguration('href',$command['href']);
			$cmd->save();
		}
	}
}

function volvocars_goto_2() {
	$cars = volvocars::byType('volvocars');
	foreach ($cars as $car){
		$car->createCmd('lastAnswer');
		$car->sortCmds();
		foreach ($car->getCmdsConfig() as $config) {
			if (!isset($config['configuration'])) {
				continue;
			}
			if (!isset($config['configuration']['linkedEndpoint'])) {
				continue;
			}
			$cmd = $car->getCmd(null,$config['logicalId']);
			if (is_object($cmd)) {
				$cmd->setConfiguration('linkedEndpoint',$config['configuration']['linkedEndpoint']);
				$cmd->save();
			}
		}
	}
}

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

	$lastLevel = 8;

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
