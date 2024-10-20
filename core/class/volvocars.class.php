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

/* * ***************************Includes********************************* */
require_once __DIR__ . '/volvoException.class.php';
require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/volvoAccount.class.php';

class volvocars extends eqLogic {
	/*	 * *************************Attributs****************************** */

	const PYTHON_PATH = __DIR__ . '/../../resources/venv/bin/python3';

	/*
	 * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la
	 * fonction 'toHtml' par exemple)
	 * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	*/
	 public static $_widgetPossibility = array(
	 	'custom' => true
	);

	private static function pythonRequirementsInstalled(string $pythonPath, string $requirementsPath) {
		if (!file_exists($pythonPath) || !file_exists($requirementsPath)) {
			return false;
		}
		exec("{$pythonPath} -m pip --no-cache-dir  freeze", $packages_installed);
		$packages = join("||", $packages_installed);
		exec("cat {$requirementsPath}", $packages_needed);
		foreach ($packages_needed as $line) {
			if (preg_match('/([^\s]+)[\s]*([>=~]=)[\s]*([\d+\.?]+)$/', $line, $need) === 1) {
				if (preg_match('/' . $need[1] . '==([\d+\.?]+)/', $packages, $install) === 1) {
					if ($need[2] == '==' && $need[3] != $install[1]) {
						return false;
					} elseif (version_compare($need[3], $install[1], '>')) {
						return false;
					}
				} else {
					return false;
				}
			}
		}
		return true;
	}

	public static function dependancy_info() {
		$return = array();
		$return['log'] = log::getPathToLog(__CLASS__ . '_update');
		$return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependance';
		$return['state'] = 'ok';
		if (file_exists(jeedom::getTmpFolder(__CLASS__) . '/dependance')) {
			$return['state'] = 'in_progress';
		} elseif (!self::pythonRequirementsInstalled(self::PYTHON_PATH, __DIR__ . '/../../resources/requirements.txt')) {
			$return['state'] = 'nok';
		}
		return $return;
	}

	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		return array('script' => __DIR__ . '/../../resources/install_#stype#.sh', 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}

	public static function deamon_info() {
		return self::daemon_info();
	}
	public static function daemon_info() {
		$return = array();
		$return['log'] = __CLASS__;
		$return['launchable'] = 'ok';
		$return['state'] = 'nok';
		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
		if (file_exists($pid_file)) {
			if (@posix_getsid(trim(file_get_contents($pid_file)))) {
				$return['state'] = 'ok';
			} else {
				shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
			}
		}
		return $return;
	}

	public static function getPort() {
		$daemonState = self::daemon_info();
		$port = config::byKey('socketport',__CLASS__);
		if ($port) {
			if ($daemonState['state'] == 'ok') {
				return $port;
			}
			$connection = @fsockopen('localhost', $port);
			if (!is_resource($connection)) {
				return $port;
			}
		}
		$port = 8001;
		$max_port = 9000;
		while ($port <= $max_port) {
			$connection = @fsockopen('localhost', $port);
			if (!is_resource($connection)) {
				config::save('socketport',$port,__CLASS__);
				return $port;
			}
			fclose($connection);
			$port++;
		}
	}

	public static function deamon_start() {
		return self::daemon_start();
	}

	public static function daemon_start() {
		$logLevel = log::convertLogLevel(log::getLogLevel(__CLASS__));
		$path = realpath(__DIR__ . '/../../resources/bin');
		$cmd = self::PYTHON_PATH . " {$path}/volvocarsd.py";
		$cmd .= " -l " . $logLevel;
		$cmd .= " -p " . jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
		$cmd .= " -P " . self::getPort();
		$cmd .= " -a " . jeedom::getApiKey(__CLASS__);
		exec ($cmd . ' >> ' . log::getPathToLog(__CLASS__ . '_daemon') . ' 2>&1 &');
		$ok = false;
		for ($i=0; $i < 10; $i++) {
			$daemon_info = self::daemon_info();
			if ($daemon_info['state'] == 'ok') {
				$ok = true;
				break;
			}
			sleep (1);
		}
		if (!$ok) {
			log::add(__CLASS__,'error',__('Impossible de lancer le démon',__FILE__), 'unableStartDaemon');
			return false;
		}
		message::removeAll(__CLASS__, 'unableStartDaemon');
		return true;
	}

	public static function deamon_stop() {
		return self::daemon_stop();
	}

	public static function daemon_stop() {
		$pidFile = jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
		if (file_exists($pidFile)) {
			$pid = intval(trim(file_get_contents($pidFile)));
			system::kill($pid);
		}
		sleep(1);
	}

	public static function sendToDeamon($params) {
		$daemon_info = self::daemon_info();
		if ($daemon_info['state'] != 'ok') {
			throw new Exception(__("Le démon n'est pas démarreé",__FILE__));
		}
		$param['apikey'] = jeedom::getApiKey(__CLASS__);
		$payLoad = json_encode($params);
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		$socket_connect($socket, '127.0.0.1', self::getPort());
		$socket_write($socket, $payLoad, strlen($payLoad));
		$socket_close($socket);
	}
	/*	 * ***********************Methode static*************************** */

	/*
	 * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
	 * lors de la création semi-automatique d'un post sur le forum community
	 * public static function getConfigForCommunity() {
	 *	return "les infos essentiel de mon plugin";
	 * }
	 */

	/*
	 * Anonymisation d'un VIN
	 */
	public static function anonymizedVIN ($_vin) {
		return str_repeat("x",10) . substr($_vin,10);
	}
	/*
	 * Retourne tous les eqLogics de véhicule associés à un compte
	 */
	public static function byAccount_id($_account_id, $_onlyEnable = false) {
		$cars = array();
		foreach (self::byType(__CLASS__, $_onlyEnable) as $car) {
			if ($car->getAccount_id() == $_account_id) {
				$cars[] = $car;
			}
		}
		return $cars;
	}

	/*
	 * Cherche un véhicule via son VIN
	 */
	public static function byVin($_vin, $_onlyEnable = false) {
		return self::byLogicalId($_vin, __CLASS__);
	}

	/*
	 * Recherche un véhicule via son nom
	 */
	public static function byName($_name, $_onlyEnable = false) {
		$cars = array();
		foreach (self::byType(__CLASS__, $_onlyEnable) as $car) {
			if ($car->getName() == $_name) {
				$cars[] = $car;
			}
		}
		return $cars;
	}

	/*
	 * Le cron
	 */
	static public function cron() {
		foreach (volvocars::byType(__CLASS__, true) as $car) {
			if ($car->getIsEnable() == 1) {
				log::add("volvocars","debug","cron pour : " . $car->getName());
				$car->refresh(false);
			}
		}
	}

	static public function cronHourly() {
		foreach(volvoAccount::all() as $account) {
			$account->logStats();
		}
		foreach (volvocars::byType(__CLASS__, true) as $car) {
			$car->updateTooltips();
		}
	}

	/*
	 * Mise à jour des listener
	 */
	public static function setListeners() {
	log::add("volvocars","debug","setListeners");
		foreach (volvocars::byType('volvocars') as $car) {
			$car->setCarListeners();
		}
	}

	/*
	 * Fonctions appelée par le listener en cas de changement de valeur d'une commande de type 'info'
	 */
	public static function updateMessages($_options) {
		$mapping = [
			'div_al_brake' => [
				'al_brakeFluid' => [
					'0' => __("Niveau liquide de frein OK",__FILE__),
				],
				'brakeFluidLevel' => [
					'TOO_LOW' => __("Niveau liquide de freins bas",__FILE__),
				],
			],
			'div_al_coolant' => [
				'al_coolant' => [
					'0' => __("Niveau liquide refroidissement OK",__FILE__),
				],
				'coolantLevel' => [
					'TOO_LOW' => __("Niveau liquide refroidissement bas",__FILE__),
				],
			],
			'div_al_oil' => [
				'al_oil' => [
					'0' => __("Niveau d'huile OK",__FILE__),
				],
				'oilLevel' => [
					'SERVICE_REQUIRED' => __("Service à effectuer",__FILE__),
					"TOO_LOW" => __("Niveau d'huile bas",__FILE__),
					"TOO_HIGH" => __("Niveau d'huile haut",__FILE__),
				],
			],
			'div_al_wash' => [
				'al_washerFluid' => [
					'0' => __("Niveau lave-vitres OK", __FILE__),
				],
				'washerFluidLevel' => [
					'TOO_LOW' => __("Niveau liquide lave-vitres bas",__FILE__),
				],
			],
			'div_al_fuelautonomy' => [
				'fuelAutonomy' => [
					$_options['value'] => __("Autonomie:",__FILE__) . " #value# #unite#",
				],
			],
			'div_al_electricautonomy' => [
				'electricAutonomy' => [
					$_options['value'] => __("Autonomie:",__FILE__) . " #value# #unite#",
				],
			],
			'div_al_tyre' => [
				'al_tyre' => [
					'0' => __("Pression pneus OK",__FILE__),
				],
				'tyreFl' => [
					"VERY_LOW_PRESSURE"	=> __("Pression pneu avant gauche très basse",__FILE__),
					"LOW_PRESSURE"		=> __("Pression pneu avant gauche basse",__FILE__),
					"HIGH_PRESSURE"		=> __("Pression pneu avant gauche élevée",__FILE__),
				],
				'tyreFr' => [
					"VERY_LOW_PRESSURE" => __("Pression pneu avant droit très basse",__FILE__),
					"LOW_PRESSURE"		=> __("Pression pneu avant droit basse",__FILE__),
					"HIGH_PRESSURE"		=> __("Pression pneu avant droit élevée",__FILE__),
				],
				'tyreRl' => [
					"VERY_LOW_PRESSURE"	=> __("Pression pneu arrière gauche très basse",__FILE__),
					"LOW_PRESSURE"		=> __("Pression pneu arrière gauche basse",__FILE__),
					"HIGH_PRESSURE"		=> __("Pression pneu arrière gauche élevée",__FILE__),
				],
				'tyreRr' => [
					"VERY_LOW_PRESSURE" => __("Pression pneu arrière droit très basse",__FILE__),
					"LOW_PRESSURE"		=> __("Pression pneu arrière droit basse",__FILE__),
					"HIGH_PRESSURE"		=> __("Pression pneu arrière droit élevée",__FILE__),
				],
			],
			'div_al_light' => [
				'al_light'				   => [ '0' => __("Feux OK",__FILE__) ],
				"al_brakeLightL"           => [ 'FAILURE' => __("Défaut feu frein gauche",__FILE__) ],
				"al_brakeLightR"           => [ 'FAILURE' => __("Défaut feu frein droite",__FILE__) ],
				"al_brakeLightC"           => [ 'FAILURE' => __("Défaut feu frein central",__FILE__) ],
				"al_daytimeRunningLightL"  => [ 'FAILURE' => __("Défaut feu jour gauche",__FILE__) ],
				"al_daytimeRunningLightR"  => [ 'FAILURE' => __("Défaut feu jour droit",__FILE__) ],
				"al_fogLightF"             => [ 'FAILURE' => __("Défaut feux brouillard avant",__FILE__) ],
				"al_fogLightR"             => [ 'FAILURE' => __("Défaut feux brouillard arrière",__FILE__) ],
				"al_hazardLights"          => [ 'FAILURE' => __("Défaut feux détresse",__FILE__) ],
				"al_highBeamL"             => [ 'FAILURE' => __("Défaut feu route gauche",__FILE__) ],
				"al_highBeamR"             => [ 'FAILURE' => __("Défaut feu route droite",__FILE__) ],
				"al_lowBeamL"              => [ 'FAILURE' => __("Défaut feu croisement gauche",__FILE__) ],
				"al_lowBeamR"              => [ 'FAILURE' => __("Défaut feu croisement droite",__FILE__) ],
				"al_positionLightFl"       => [ 'FAILURE' => __("Défaut feu position avant gauche",__FILE__) ],
				"al_positionLightFr"       => [ 'FAILURE' => __("Défaut feu position avant droite",__FILE__) ],
				"al_positionLightRl"       => [ 'FAILURE' => __("Défaut feu position arrière gauche",__FILE__) ],
				"al_positionLightRr"       => [ 'FAILURE' => __("Défaut feu position arrière droit",__FILE__) ],
				"al_registrationPlateLight"=> [ 'FAILURE' => __("Défaut feu plaque",__FILE__) ],
				"al_reverseLights"         => [ 'FAILURE' => __("Défaut feu recule",__FILE__) ],
				"al_sideMarkLights"        => [ 'FAILURE' => __("Défaut feux latéraux",__FILE__) ],
				"al_turnIndicationFl"      => [ 'FAILURE' => __("Défaut clignotant avant gauche",__FILE__) ],
				"al_turnIndicationFr"      => [ 'FAILURE' => __("Défaut clignotant avant droit",__FILE__) ],
				"al_turnIndicationRl"      => [ 'FAILURE' => __("Défaut clignotant arrière gauche",__FILE__) ],
				"al_turnIndicationRr"      => [ 'FAILURE' => __("Défaut clignotant arrière droit",__FILE__) ],
			],
		];
		$car = volvocars::byId($_options['carId']);
		if (!is_object($car)){
			log::add("volvocars","error","lh_diagnostics: " . sprintf(__("Véhicule %s introuvable",__FILE__),$_options['event_id']));
			return;
		}
		$cmd = volvocarsCmd::byId($_options['event_id']);
		if (!is_object($cmd)){
			log::add("volvocars","error","lh_diagnostics: " . sprintf(__("Commande %s introuvable",__FILE__),$_options['event_id']));
			return;
		}
		$logicalId = $cmd->getLogicalId();

		foreach (array_keys($mapping) as $cible) {
			if (isset($mapping[$cible][$logicalId])) {
				if (isset($mapping[$cible][$logicalId][$_options['value']])) {
					$txt = $mapping[$cible][$logicalId][$_options['value']];
					$txt = str_replace('#value#',$_options['value'],$txt);
					$txt = str_replace('#unite#',$cmd->getUnite(),$txt);
					$car->addWidgetMessage($cible,$_options['event_id'],$txt);
				} else {
					$car->rmWidgetMessage($cible,$_options['event_id']);
				}
			}
		}
	}

	/*
	 * Les handlers des listener
	 *   un handler par endpoint car il y a trop de commandes pour l'enregistrement d'un seul
	 *   handler pour toutes les commandes (contrainte de la DB)
	 */
	public static function lh_accessibility($_options)		{ self::updateMessages($_options); }
	public static function lh_brakes($_options)				{ self::updateMessages($_options); }
	public static function lh_diagnostics($_options)		{ self::updateMessages($_options); }
	public static function lh_doors($_options)				{ self::updateMessages($_options); }
	public static function lh_engine_diagnostics($_options)	{ self::updateMessages($_options); }
	public static function lh_engine_status($_options)		{ self::updateMessages($_options); }
	public static function lh_fuel($_options)				{ self::updateMessages($_options); }
	public static function lh_location($_options)			{ self::updateMessages($_options); }
	public static function lh_odometer($_options)			{ self::updateMessages($_options); }
	public static function lh_recharge_status($_options)	{ self::updateMessages($_options); }
	public static function lh_statistics($_options)			{ self::updateMessages($_options); }
	public static function lh_tyre($_options)				{ self::updateMessages($_options); }
	public static function lh_warnings($_options)			{ self::updateMessages($_options); }
	public static function lh_windows($_options)			{ self::updateMessages($_options); }
	public static function lh_plugin($_options)				{ self::updateMessages($_options); }


	/*
	 * Config de templatge de widgets
	 */
	public static function templateWidget() {
		$return = [
			'info' => [
				'binary' => [
					'DoorClosed' => [
						'template' => 'tmplimg',
						'replace' => [
							'#_img_light_on_#' => '<img src="/plugins/volvocars/desktop/img/door_closed.png"/>',
							'#_img_dark_on_#' => '<img src="/plugins/volvocars/desktop/img/door_closed.png"/>',
							'#_img_light_off_#' => '<img src="/plugins/volvocars/desktop/img/door_open.png"/>',
							'#_img_dark_off_#' => '<img src="/plugins/volvocars/desktop/img/door_open.png"/>',
							'#_desktop_width_#' => 30,
						]
					],
					'DoorOpen' => [
						'template' => 'tmplimg',
						'replace' => [
							'#_img_light_on_#' => '<img src="/plugins/volvocars/desktop/img/door_open.png"/>',
							'#_img_dark_on_#' => '<img src="/plugins/volvocars/desktop/img/door_open.png"/>',
							'#_img_light_off_#' => '<img src="/plugins/volvocars/desktop/img/door_closed.png"/>',
							'#_img_dark_off_#' => '<img src="/plugins/volvocars/desktop/img/door_closed.png"/>',
							'#_desktop_width_#' => 30,
						]
					]
				]
			]
		];
		return $return;
	}

	/*	 * *********************Méthodes d'instance************************* */

	public function getCmdsConfig() {
		if (isset($this->_cmdsConfig)) {
			return $this->_cmdsConfig;
		}
		$cmdsFile = realpath(__DIR__ . '/../config/cmds.json');
		$cmds = json_decode(translate::exec(file_get_contents($cmdsFile),$cmdsFile),true);
		foreach ($cmds as $cmd) {
			$cmd['name'] = str_replace('#site1#',$this->getConfiguration('site1_name'),$cmd['name']);
			$cmd['name'] = str_replace('#site2#',$this->getConfiguration('site2_name'),$cmd['name']);
		}
		$this->_cmdsConfig = $cmds;
		return $cmds;
	}

	public function getCmdByConfiguration($_configuration) {
		$values = array(
			'eqLogic_id' => $this->getId(),
			'configuration' => '%' . $_configuration . '%',
		);
		$sql = 'SELECT ' . DB::buildField(__CLASS__ . 'Cmd') . '
		FROM cmd
		WHERE eqLogic_id = :eqLogic_id AND configuration LIKE :configuration';
		return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__ . 'Cmd');
	}

	/*
	 * Fonction appelée automatiquement avant la sauvegarde
	 */
	public function preSave() {
		$this_wasEnable = $this->getIsEnable();
		$this->_oldSiteState = array(
			"site1" => $this->getConfiguration('site1_active',0),
			"site2" => $this->getConfiguration('site2_active',0),
		);
	}
	/*
	 * Fonction exécutée automatiquement avant la création de l'équipement
	 */
	public function preInsert() {
		if ($this->getVin() != '') {
			$car = self::byVin($this->getVin());
			if (is_object($car)){
				throw new Exception (__("Il y a déjà un véhicule avec ce vin!",__FILE__));
			}
		}
		$this->setDisplay('width','832px');
		$this->setDisplay('height','1292px');
	}

	/*
	 * Fonction exécutée automatiquement avant la mise à jour de l'équipement
	 */
	public function preUpdate() {
		if ($this->getVin() == '') {
			throw new Exception (__("Le vin n'est pas défini",__FILE__));
		}
		$car = self::byVin($this->getVin());
		if (is_object($car) and ($car->getId() != $this->getId())){
			throw new Exception (__("Il y a un autre véhicule avec ce vin!",__FILE__));
		}
		if ($this->getConfiguration('site1_active') == 1 && $this->getConfiguration('site1_name') == '') {
			$this->setConfiguration('site1_name',__('Domicile',__FILE__));
		}
		if ($this->getConfiguration('site2_active') == 1 && $this->getConfiguration('site2_name') == '') {
			$this->setConfiguration('site2_name',__('Autre',__FILE__));
		}
		if (is_object($car)) {
			$this->setConfiguration('old_site1_name',$car->getConfiguration('site1_name'));
			$this->setConfiguration('old_site2_name',$car->getConfiguration('site2_name'));
		}

		if ($this->getConfiguration('electricEngine')){
			$limit = trim($this->getConfiguration('electricAutonomyLimit'));
			if ($limit == '') {
				$limit = 0;
			}
			if (! is_numeric($limit)) {
				throw new Exception (__("La limite d'autonomie doit être une valeur numérique",__FILE__));
			}
			$this->setConfiguration('electricAutonomyLimit', $limit);
		}

		if ($this->getConfiguration('fuelEngine')){
			$limit = trim($this->getConfiguration('fuelAutonomyLimit'));
			if ($limit == '') {
				$limit = 0;
			}
			if (! is_numeric($limit)) {
				throw new Exception (__("La limite d'autonomie doit être une valeur numérique",__FILE__));
			}
			$this->setConfiguration('fuelAutonomyLimit', $limit);
		}

		$car = self::byId($this->getId());
	}

	/*
	 * Fonction appelée automatiquement après la création de l'équipement
	 */
	public function postInsert() {
		$this->createCmd("refresh");
		$this->createCmd("msg2widget");
		$this->createCmd("lastAnswer");
	}

	/*
	 * Fonction appelée après la sauvegarde de l'eqLogic et des commandes via l'interface WEB
	 */
	public function postAjax() {
		if ($this->getIsEnable()) {
			foreach (['site1','site2'] as $site){
				if ($this->getConfiguration($site . '_active') == 1) {
					if ($this->_oldSiteState[$site] != 1) {
						if ($cmdsConfig == null) {
							$cmdsConfig = $this->getCmdsConfig();
						}
						foreach ($cmdsConfig as $cmdConfig) {
							if (isset($cmdConfig['configuration']['onlyFor']) and $cmdConfig['configuration']['onlyFor'] == $site) {
								$this->createCmd($cmdConfig['type'],$cmdConfig['logicalId']);
							}
						}
					}
				} else {
					$cmds = $this->getCmdByConfiguration('"onlyFor":"' . $site .'"');
					foreach ($cmds as $cmd) {
						$cmd->remove();
					}
				}
			}

			foreach (['distanceSite1','distanceSite2'] as $logicalId){
				$cmd = $this->getCmd('info',$logicalId);
				if (is_object($cmd)) {
					$cmd->event($cmd->execute());
					$cmdName = $cmd->getName();
					switch ($logicalId) {
						case 'distanceSite1':
							$siteName = $this->getConfiguration('site1_name');
							$oldSiteName = $this->getConfiguration('old_site1_name');
							$site = 'site1';
							break;
						case 'distanceSite2':
							$siteName = $this->getConfiguration('site2_name');
							$oldSiteName = $this->getConfiguration('old_site2_name');
							$site = 'site2';
							break;
					}
					$distance = __('distance',__FILE__);
					if (($cmdName != $oldSiteName) && (($cmdName == $distance . ' ' . $site) || $cmdName == ($distance . ' ' . $oldSiteName))) {
						$cmd->setName($distance . ' ' . $siteName);
						$cmd->save();
					}
				}
			}
			foreach (['presenceSite1','presenceSite2'] as $logicalId){
				$cmd = $this->getCmd('info',$logicalId);
				if (is_object($cmd)) {
					$cmdName = $cmd->getName();
					switch ($logicalId) {
						case 'presenceSite1':
							$siteName = $this->getConfiguration('site1_name');
							$oldSiteName = $this->getConfiguration('old_site1_name');
							$site = 'site1';
							break;
						case 'presenceSite2':
							$siteName = $this->getConfiguration('site2_name');
							$oldSiteName = $this->getConfiguration('old_site2_name');
							$site = 'site2';
							break;
					}
					$presence = __('présence',__FILE__);
					if (($cmdName != $oldSiteName) && (($cmdName == $presence . ' ' . $site) || $cmdName == ($presence . ' ' . $oldSiteName))) {
						$cmd->setName($presence . ' ' . $siteName);
						$cmd->save();
					}
					$cmd->event($cmd->execute());
				}
			}
			foreach ([
				'al_electricAutonomy',
				'al_fuelAutonomy'
			] as $logicalId) {
				$cmd = $this->getCmd('info',$logicalId);
				if (is_object($cmd)){
					$cmd->event($cmd->execute());
				}
			}
			$this->setCarListeners();
			$this->cleanWidgetMessages();
		}
	}

	/*
	 * Fonctions pour la gestion des listeners du véhicule
	 * ***************************************************
	 */

	/*
	 * Retourne le liste des listeners
	 */
	private function getCarListeners() {
		$listeners = array();
		foreach(listener::byClass(__CLASS__) as $listener) {
			if ($listener->getOption('carId') == $this->getid()) {
				$listeners[$listener->getFunction()] = $listener;
			}
		}
		return ($listeners);
	}

	/*
	 * Suppression de tous les listeners du véhicule
	 */
	private function removeCarListeners() {
		log::add("volvocars","info",$this->getname() . ": " . __("Suppression des listeners",__FILE__));
		foreach ($this->getCarListeners() as $listener) {
			$listener->remove();
		}
	}

	/*
	 * Configuration des Listeners du vhéhicule
	 */
	private function setCarListeners() {
		if ($this->getIsEnable() == 0) {
			$this->removeCarListeners();
			return;
		}

		log::add("volvocars","info",$this->getName() . ": " . __("mise à jour des listeners",__FILE__));
		$cmd2listeners = [];
	 	foreach ($this->getCmdsConfig() as $command) {
			if (!isset($command['configuration']['listener'])){
				continue;
			}
			$cmd2listeners[$command['logicalId']] = 1;
		}
		$listeners = $this->getCarListeners();
		foreach (endpoint::all('info') as $endpoint) {
			$logicalIds = $endpoint->getLogicalIds();
			if (count($logicalIds) == 0) {
				continue;
			}
			$function = "lh_" . $endpoint->getId();
			if (! method_exists($this, $function)) {
				log::add("volvocars","error",sprintf(__("handler pour le listener du endpoint %s introuvable",__FILE__),$endpoint->getId()));
				continue;
			}
			$listener = listener::byClassAndFunction(__CLASS__,$function,array('carId'=>$this->getId()));
			if (!is_object($listener)) {
				$listener = new listener();
				$listener->setClass(__CLASS__);
				$listener->setFunction($function);
				$listener->setOption('carId',$this->getId());
			}
			$listener->emptyEvent();
			foreach ($logicalIds as $logicalId) {
				$cmd = $this->getCmd('info',$logicalId);
				if (!is_object($cmd)) {
					continue;
				}
				log::add("volvocars","debug", sprintf(__("Ajout de la commande '%s' au listener",__FILE__),$logicalId));
				$listener->addEvent($cmd->getId());
				unset ($cmd2listeners[$logicalId]);
			}
			$listener->save();
		}
		$function = "lh_plugin";
		$listener = listener::byClassAndFunction(__CLASS__,$function,array('carId'=>$this->getId()));
		if (!is_object($listener)) {
			$listener = new listener();
			$listener->setClass(__CLASS__);
			$listener->setFunction($function);
			$listener->setOption('carId',$this->getId());
		}
		$listener->emptyEvent();
		foreach (array_keys($cmd2listeners) as $logicalId) {
			$cmd = $this->getCmd('info',$logicalId);
			if (!is_object($cmd)) {
				continue;
			}
			log::add("volvocars","debug", sprintf(__("Ajout de la commande '%s' au listener",__FILE__),$logicalId));
			$listener->addEvent($cmd->getId());
		}
		$listener->save();
	}

	/*
	 * Vérifie si une image du véhicule est disponible
	 */
	public function imageOK() {
		$img = $this->getVin() .'.png';
		$imgPath = __DIR__ . '/../../data/' . $img;
		if (file_exists($imgPath)){
			$fileInfo = getimagesize($imgPath);
			if ($fileInfo !== false) {
				return '1';
			}
		}
		$plugin = plugin::byId($this->getEqType_name());
		return '0';
	}

	/*
	 * Retourne l'image du véhicule
	 */
	public function getImage() {
		if ($this->imageOK()) {
			return '/plugins/volvocars/data/' . $this->getVin() . '.png';
		}
		$plugin = plugin::byId(__CLASS__);
		return $plugin->getPathImgIcon();
	}

	/*
	 * Retourne l'URL pour obtenir une image du véhicule
	 */
	public function getImageUrl() {
		$account = $this->getAccount();
		$details = $account->getInfos('details',$this->getVin(), true);

		if (!isset($details['data'])) {
			return "";
		}
		if (!isset($details['data']['images'])) {
			return "";
		}
		if (!isset($details['data']['images']['exteriorImageUrl'])) {
			return "";
		}
		$url = $details['data']['images']['exteriorImageUrl'];
		$parsedURL = parse_url($url);
		parse_str($parsedURL['query'],$params);
		$params['bg'] = 'ffffff00';
		$params['w'] = '1200';
		$parsedURL['query'] = http_build_query($params);
		$url = ((isset($parsedURL['scheme'])) ? $parsedURL['scheme'] . '://' : '')
			  .((isset($parsedURL['user'])) ? $parsedURL['user'] . ((isset($parsedURL['pass'])) ? ':' . $parsedURL['pass'] : '') .'@' : '')
			  .((isset($parsedURL['host'])) ? $parsedURL['host'] : '')
			  .((isset($parsedURL['port'])) ? ':' . $parsedURL['port'] : '')
			  .((isset($parsedURL['path'])) ? $parsedURL['path'] : '')
			  .((isset($parsedURL['query'])) ? '?' . $parsedURL['query'] : '')
			  .((isset($parsedURL['fragment'])) ? '#' . $parsedURL['fragment'] : '');
		log::add("volvocars","debug","IMAGE: " . $url);
		return $url;
	}

	/*
	 * Mise à jour des détails du véhicule et des valeurs des commandes info
	 * à partir des infos fournies par les API Volvo
	 */
	public function synchronize() {
		$this->updateDetails();
		$this->setActions();
	}

	public function setActions() {
		$created = false;
		$account = $this->getAccount();
		$payload = $account->getInfos('commands',$this->getVin(), true);
		if (!isset($payload['status']) || $payload['status'] !== 'ok') {
			$httpCode = isset($payload['httpCode']) ? $payload['httpCode'] : '';
			$message = isset($payload['message']) ? $payload['message'] : null;
			$description = isset($payload['description']) ? $payload['description'] : null;
			$detail = isset($payload['detail']) ? $payload['detail'] : null;
			log::add("volvocars","error","└" . sprintf(__("Echec de la synchonisation de l'account %s",__FILE__),$this->getName()));
			throw new volvoApiException('details',$httpCode,$message,$description,$detail);
		}
		if (!isset($payload['data'])) {
			log::add("volvocars","error","└" . __("Le payload %s n'a pas de 'data'", __FILE__));
			throw new Exception("no data");
		}
		$commands = $payload['data'];

		$cmdsConfig = $this->getCmdsConfig();
		foreach ($commands as $command) {
			$cmdOk = false;
			foreach($cmdsConfig as $cmdConfig){
				if (!isset($cmdConfig['_volvoName']) || $cmdConfig['_volvoName'] !== $command['command']) {
					continue;
				}
				$cmd = $this->getCmd(null,$cmdConfig['logicalId']);
				if (!is_object($cmd)) {
					$this->createCmd($cmdConfig['logicalId']);
					$cmd = $this->getCmd(null,$cmdConfig['logicalId']);
					$cmd->setConfiguration('href',$command['href']);
					$cmd->save();
				}
				$cmdOk = true;
				break;
			}
			if (! $cmdOk) {
				log::add("volvocars","warning",sprintf(__("Commande Jeedom pour '%s' introuvable",__FILE__),$command['command']));
			}
		}
	}

	/*
	 * Mise à jour de détails du véhicule à partir des infos fournies par
	 * les API de Volvo
	 */
	public function updateDetails() {
		$changed = false;
		$account = $this->getAccount();
		$payload = $account->getInfos('details',$this->getVin(), true);
		if (!isset($payload['status']) || $payload['status'] !== 'ok') {
			$httpCode = isset($payload['httpCode']) ? $payload['httpCode'] : '';
			$message = isset($payload['message']) ? $payload['message'] : null;
			$description = isset($payload['description']) ? $payload['description'] : null;
			$detail = isset($payload['detail']) ? $payload['detail'] : null;
			log::add("volvocars","error","└" . sprintf(__("Echec de la synchonisation de l'account %s",__FILE__),$this->getName()));
			throw new volvoApiException('details',$httpCode,$message,$description,$detail);
		}
		if (!isset($payload['data'])) {
			log::add("volvocars","error","└" . __("Le payload %s n'a pas de 'data'", __FILE__));
			throw new Exception("no data");
		}
		$details = $payload['data'];

		log::add("volvocars","debug","│ " . "DETAILS: " . json_encode($details));
		if (! isset($details['descriptions'])){
			log::add("volvocars","error","│ " . (__("Pas de key 'descriptions' dans les détails[data]",__FILE__)));
		} else {

			// Le modèle
			// ---------
			if (isset($details['descriptions']['model'])) {
				if ($details['descriptions']['model'] != $this->getConfiguration('model')) {
					log::add("volvocars","info","│ " . sprintf(__("Mise à jour du modèle pour le véhicule %s",__FILE__),$this->getVin()));
					$this->setConfiguration('model',$details['descriptions']['model']);
					$changed = true;
					if ($this->getName() == $this->getVin()) {
						if (count(self::byName($details['descriptions']['model'])) == 0) {
							$this->setName($details['descriptions']['model']);
						} else {
							$i = 1;
							$name = $details['descriptions']['model'] . ' (' . $i . ')';
							while (count(self::byName($name)) > 0) {
								$i += 1;
								$name = $details['descriptions']['model'] . ' (' . $i . ')';
							}
							$this->setName($name);
						}
					}
				}
			}
		}

		// L'année
		// -------
		if (! isset($details['modelYear'])) {
			log::add("volvocars","warning","│ " . sprintf(__("L'année de construction du le véhicule %s indéninie",__FILE__),$this->getVin()));
		} else {
			if ($details['modelYear'] != $this->getConfiguration('modelYear')){
				log::add("volvocars","info","│ " . sprintf(__("Mise à jour de l'année pour le véhicule %s",__FILE__),$this->getVin()));
				$this->setConfiguration('modelYear',$details['modelYear']);
				$changed = true;
			}
		}

		// Couleur
		// -------
		if (! isset($details['externalColour'])) {
			log::add("volvocars","warning","│ " . sprintf(__("la couleur du véhicule %s n'est pas définie",__FILE__),$this->getVin()));
		} else {
			if ($details['externalColour'] != $this->getConfiguration('externalColour')){
				log::add("volvocars","info","│ " . sprintf(__("Mise à jour de la couleur pour le véhicule %s",__FILE__),$this->getVin()));
				$this->setConfiguration('externalColour',$details['externalColour']);
				$changed = true;
			}
		}

		// Boîte à vitesse
		// ---------------
		if (! isset($details['gearbox'])) {
			log::add("volvocars","warning","│ " . sprintf(__("Le type de boîte à vitesse n'est pas défini pour le véhicule %s",__FILE__),$this->getVin()));
		} else {
			switch ($details['gearbox']) {
				case 'AUTOMATIC':
					$gearbox = __('Automatique',__FILE__);
					break;
				case 'MANUAL':
					$gearbox = __('Manuelle',__FILE__);
					break;
				default:
					$gearbox = $details['gearbox'];
			}
			if ($gearbox != $this->getConfiguration('gearbox')){
				log::add("volvocars","info","│ " . sprintf(__("Mise à jour du type de boîte à vitesse pour le véhicule %s",__FILE__),$this->getVin()));
				$this->setConfiguration('gearbox',$gearbox);
				$changed = true;
			}
		}

		// Carburant
		// ---------
		if (! isset($details['fuelType'])) {
			log::add("volvocars","warning","│ " . sprintf(__("Le carburant n'est pas défini pour le véicule %s",__FILE__),$this->getVin()));
		} else {
			switch ($details['fuelType']) {
				case 'DIESEL':
					$fuelType = __('Diesel',__FILE__);
					$electric = 0;
					$combustion = 1;
					break;
				case 'PETROL':
					$fuelType = __('Essence',__FILE__);
					$electric = 0;
					$combustion = 1;
					break;
				case 'PETROL/ELECTRIC':
					$fuelType = __('Hybride',__FILE__);
					$electric = 1;
					$combustion = 1;
					break;
				case 'ELECTRIC':
					$fuelType = __('Electricité',__FILE__);
					$electric = 1;
					$combustion = 0;
					break;
				case 'NONE':
					$fuelType = __('Aucun',__FILE__);
					$electric = 0;
					$combustion = 0;
					break;
				default:
					$fuelType = $details['fuelType'];
			}
			if ($fuelType != $this->getConfiguration('fuelType')){
				log::add("volvocars","info","│ " . sprintf(__("Mise à jour du carburant pour le véhicule %s",__FILE__),$this->getVin()));
				$this->setConfiguration('fuelType',$fuelType);
				$this->setConfiguration('fuelEngine',$combustion);
				$this->setConfiguration('electricEngine',$electric);
				$changed = true;
			}
		}

		// Capacité batterie
		// -----------------
		if (isset($details['batteryCapacityKWH'])){
			if ($details['batteryCapacityKWH'] != $this->getConfiguration('batteryCapacityKWH')){
				log::add("volvocars","info","│ " . sprintf(__("Mise à jour da capacité de la batterie pour le véhicule %s",__FILE__),$this->getVin()));
				$this->setConfiguration('batteryCapacityKWH',$details['batteryCapacityKWH']);
				$changed = true;
			}
		}

		// Les images
		// ----------
		if (isset($details['images'])){
			if (isset($details['images']['exteriorImageUrl'])){
				$url = $details['images']['exteriorImageUrl'];
				$parsedURL = parse_url($url);
				parse_str($parsedURL['query'],$params);
				$params['bg'] = 'ffffff00';
				$params['w'] = '1200';
				$parsedURL['query'] = http_build_query($params);
				$url = ((isset($parsedURL['scheme'])) ? $parsedURL['scheme'] . '://' : '')
					  .((isset($parsedURL['user'])) ? $parsedURL['user'] . ((isset($parsedURL['pass'])) ? ':' . $parsedURL['pass'] : '') .'@' : '')
					  .((isset($parsedURL['host'])) ? $parsedURL['host'] : '')
					  .((isset($parsedURL['port'])) ? ':' . $parsedURL['port'] : '')
					  .((isset($parsedURL['path'])) ? $parsedURL['path'] : '')
					  .((isset($parsedURL['query'])) ? '?' . $parsedURL['query'] : '')
					  .((isset($parsedURL['fragment'])) ? '#' . $parsedURL['fragment'] : '');
				log::add("volvocars","debug","│ IMAGE: " . $url);
				$imgPath = __DIR__ . '/../../data';
				if (! is_dir($imgPath)){
					mkdir($imgPath);
				}
				$imgPath .= '/'.$this->getVin() . ".png";
				log::add("volvocars","debug","│ " . $imgPath);
				log::add("volvocars","debug","│ " . print_r(get_headers($url,1),true));
				$session = curl_init();
				curl_setopt($session, CURLOPT_URL, $url);
				curl_setopt($session, CURLOPT_CUSTOMREQUEST, 'GET');
				curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($session, CURLOPT_HTTPHEADER, [
					"Accept: image/avif,image/webp,image/png,image/svg+xml,*/*;q=0.8",
			  		"Accept-Language: fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3",
					"Connection: keep-alive",
				  	"Access-Control-Allow-Origin: *",
				  	"Pragma: no-cache",
					"Host: cas.volvocars.com",
					"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0",
				]);

				$data = curl_exec($session);
				$httpCode = curl_getinfo($session,CURLINFO_HTTP_CODE);
				log::add("volvocars","debug","│ httpCode: " . $httpCode);
				if ($httpCode != 200) {
					log::add("volvocars","error","│ " . sprintf(__("Erreur lors du téléchargement de l'image. HTTPCODE: %s",__FILE__), $httpCode));
				}
				curl_close($session);
				$image = fopen($imgPath, 'wb');
				fwrite($image,$data);
				fclose($image);
			}
			if (isset($details['images']['internalImageUrl'])){
			}
		}

		if ($changed) {
			$this->save();
		}
	}

	/*
	 * Création d'une commande
	 */
	public function createCmd($_logicalId, $sort=true) {
		$created = false;
		$cmdsConfig = $this->getCmdsConfig();
		foreach ($cmdsConfig as $cmdConfig) {
			if ($cmdConfig['logicalId'] !== $_logicalId) {
				continue;
			}
			if (isset($cmdConfig['configuration']) && isset($cmdConfig['configuration']['onlyFor'])) {
				switch ($cmdConfig['configuration']['onlyFor']) {
					case 'fuelEngine' :
						if ($this->getConfiguration('fuelEngine',0) == 0) {
							return;
						}
						break;
					case 'electricEngine' :
						if ($this->getConfiguration('electricEngine',0) == 0) {
							return;
						}
						break;
					case 'site1' :
						if ($this->getConfiguration('site1_active',0) == 0) {
							return;
						}
						break;
					case 'site2' :
						if ($this->getConfiguration('site2_active',0) == 0) {
							return;
						}
						break;
				}
			}
			if (substr_compare($_logicalId,'Open',-4) == 0) {
				if (config::byKey("create_cmd_open","volvocars", "0") == 0) {
					return;
				}
			}
			if (substr_compare($_logicalId,'Closed',-6) == 0) {
				if (config::byKey("create_cmd_closed","volvocars", "0") == 0) {
					return;
				}
			}
			if (isset($cmdConfig['configuration']) && isset($cmdConfig['configuration']['dependTo'])) {
				$depends = $cmdConfig['configuration']['dependTo'];
				if (!is_array($depends)) {
					$depends = array($depends);
				}
				foreach ($depends as $depend) {
					$dependCmd = $this->getCmd(null,$depend);
					if (is_object($dependCmd)) {
						break;
					}
					return;
				}
			}

			$cmd = $this->getCmd($cmdConfig['type'],$cmdConfig['logicalId']);
			if (! is_object($cmd)) {
				log::add("volvocars","debug",sprintf(__("Création de la commande %s pour le véhicule %s",__FILE__),$cmdConfig['logicalId'],$this->getName()));
				$cmd = new volvocarsCmd();
				$cmd->setEqLogic_id($this->getId());
				utils::a2o($cmd,$cmdConfig);
				$cmd->save();
				$created = true;
			}
			if (isset($cmdConfig['configuration']) && isset($cmdConfig['configuration']['dependencies'])) {
				$dependencies = $cmdConfig['configuration']['dependencies'];
				if (! is_array($dependencies)) {
					$dependencies = array($dependencies);
				}
				foreach ($dependencies as $dependency) {
					$this->createCmd($dependency, false);
				}
			}
			if ($created && $sort) {
				$this->sortCmds();
			}
			return;
		}
	}

	public function createMissingCmds() {
		$commands = $this->getCmdsConfig();
		foreach ($commands as $command) {
			if (!isset($command['configuration'])) {
				continue;
			}
			if (!isset($command['configuration']['removable'])) {
				continue;
			}
			if ($command['configuration']['removable'] != 1) {
				continue;
			}
			$this->createCmd($command['logicalId'], false);
			$this->sortCmds();
		}
	}

	/*
	 * Tri des commandes sur la base du fichier de configuration
	 */
	public function sortCmds() {
		$pos = 1;
		$pattern = '/^\s*"logicalId"\s*:\s*"(.*?)"/';
		$cmdsFile = realpath(__DIR__ . '/../config/cmds.json');
		$fh = fopen($cmdsFile, "r");
		while (($line = fgets($fh)) !== false) {
			if (preg_match($pattern,$line,$matches)) {
				$logicalId = $matches[1];
				$cmd = $this->getCmd(null,$logicalId);
				if (is_object($cmd)) {
					if ($cmd->getOrder() != $pos) {
						$cmd->setOrder($pos);
						$cmd->save();
					}
					$pos++;
				}
			}
		}
		fclose($handle);
	}

	/*
	 * Récupération des infos brutes
	 */
	public function getRawDatas() {
		$data = $this->getAccount()->getRawDatas($this->getVin());
		$fileName = __DIR__ . '/../../data/' . $this->getVin() . '.data.json';
		$file = fopen($fileName, 'w');
		foreach ($data as $endpoint => $content) {
			fwrite($file, "====== " . $endpoint . " ======\n");
			fwrite($file, $content . "\n");
		}
		fclose($file);
		chmod($fileName, 0775);
		return $data;
	}

	/*
	 * Mise à jour des tooltips
	 */
	public function updateTooltips(){
		foreach ($this->getCmd('info') as $cmd) {
			if ($cmd->getConfiguration('listener') != 1) {
				continue;
			}
			$options = [
				'carId' => $this->getId(),
				'event_id' => $cmd->getId(),
				'value' => $cmd->execCmd(),
			];
			self::updateMessages($options);
		}
	}

	/*
	 * Mise à jour des infos retournée par un endpoint des API Volvo
	 */
	public function getInfosFromApi($endpoint_id, $force = false){
		log::add("volvocars","info",sprintf("┌Getting infos '%s'...",$endpoint_id));
		$account = $this->getAccount();
		$payload = $account->getInfos($endpoint_id,$this->getVin(), $force);
		if ($payload === false) {
			log::add("volvocars","debug","└end");
			return;
		}
		if (!isset($payload['status']) || $payload['status'] !== 'ok') {
			$httpCode = isset($payload['httpCode']) ? $payload['httpCode'] : '';
			$message = isset($payload['message']) ? $payload['message'] : null;
			$description = isset($payload['description']) ? $payload['description'] : null;
			$detail = isset($payload['detail']) ? $payload['detail'] : null;
			log::add("volvocars","error","└" . sprintf(__("Echec lors de l'appel du endpoint '%s' pour le véhicule %s",__FILE__),$endpoint_id,$this->getName()));
			return;
		}
		if (!isset($payload['data'])) {
			log::add("volvocars","error","└" . __("Le payload %s n'a pas de 'data'", __FILE__));
			throw new Exception("no data");
		}

		$infos = $payload['data'];
		foreach (array_keys($infos) as $key) {
			log::add("volvocars","debug",sprintf("├─key: %s",$key));
			foreach (endpoint::byId($endpoint_id)->getLogicalIds($key) as $logicalId) {
				$cmd = $this->getCmd('info',$logicalId);
				if (!is_object($cmd)) {
					$this->createCmd($logicalId);
				}
				$cmd = $this->getCmd('info',$logicalId);
				if (!is_object($cmd)) {
					continue;
				}
				switch ($logicalId) {
					case 'position':
						$value = $infos[$key]['coordinates'][1] . ',' . $infos[$key]['coordinates'][0];
						break;
					case 'unavailableReason':
						if (isset($infos[$key]['unavailableReason'])) {
							$value = $infos[$key]['unavailableReason'];
						} else {
							$value = "";
						}
						break;
					case 'engineON':
						$value = $infos[$key]['value'] == 'RUNNING' ? '1' : '0';
						break;
					default:
						$value = $infos[$key]['value'];
				}
				log::add("volvocars","info",sprintf("│ %s: %s",$logicalId,$value));
				$this->checkAndUpdateCmd($cmd,$value);
			}
		}
		log::add("volvocars","info","└OK");
	}

	/*
	 * Interrogation de tous les endpoints de l'API pour remonter les infos
	 */
	public function refresh($force = false) {
		$cmdsConfig = volvocars::getCmdsConfig();
		foreach (endpoint::all('info',true) as $endpoint) {
			try {
				$relevant = false;
				foreach ($endpoint->getLogicalIds() as $logicalId) {
					$cmd = $this->getCmd('info',$logicalId);
					if (is_object($cmd)) {
						$relevant = true;
						break;
					}
					foreach ($cmdsConfig as $cmd) {
						if ($cmd['logicalId'] === $logicalId) {
							if (!isset ($cmd['configuration'])) {
								$relevant = true;
								break 2;
							}
							if (!isset ($cmd['configuration']['onlyFor'])) {
								$relevant = true;
								break 2;
							}
							if (!isset ($cmd['configuration']['onlyFor'])) {
								$relevant = true;
								break 2;
							}
							if ($this->getConfiguration($cmd['configuration']['onlyFor']) == 1) {
								$relevant = true;
								break 2;
							}
						}
					}
				}
				if ($relevant) {
					$this->getInfosFromApi($endpoint->getId(), $force);
				}
			} catch (volvoApiException $e) {
				if ($e->getHttpCode() == '403') {
					if (strpos($e->getMessage(),'Out of call volume quota.') !== false) {
						$cmd = $this->getCmd('info','availability');
						if (is_object($cmd)){
							log::add('volvocars','info',sprintf('│ %s: %s','availability','QUOTA_OUT'));
							$this->checkAndUpdateCmd($cmd,'QUOTA_OUT');
						}
						$cmd = $this->getCmd('info','unavailableReason');
						if (is_object($cmd)) {
							preg_match('/[\d:]+/',$e->getMessage(), $matches);
							$delai = $matches[0];
							$value = sprintf(__('Réinitialisation dans %s',__FILE__), $delai);
							log::add('volvocars','info',sprintf('│ %s: %s','unavailableReason',$value));
							$this->checkAndUpdateCmd($cmd,$value);
						}
						log::add("volvocars","info","└KO");
						return;
					}
				}
				throw $e;
			}
		}
		if ($force) {
			$this->updateTooltips();
		}
	}

	/*
	 * Retourne un filedescriptor utiliser pour la gestion de lock
	 */
	private function getFileDescriptorLock() {
		if (!isset($this->_lockFd) || $this->_lockFd === null) {
			$this->_lockFd = fopen(jeedom::getTmpFolder() . '/volvocars_' . $this->getId() . ".lock", 'w');
		}
		return $this->_lockFd;
	}

	/*
	 * Prise d'un lock exclusif
	 */
	private function getLock() {
		$return = flock($this->getFileDescriptorLock(), LOCK_EX);
		return $return;
	}

	/*
	 * Libération du lock
	 */
	private function releaseLock() {
		$return = flock($this->getFileDescriptorLock(), LOCK_UN);
		return $return;
	}

	/*
	 * Ajout d'un message d'aides du panel
	 * Un lock permet de s'assurer qu'un seul process lit, modifie puis enregistre
	 * les messages
	 */
	public function addWidgetMessage ($cible, $id, $message) {
		$cmd = $this->getCmd("info","msg2widget");
		if (! is_object($cmd)) {
			log::add("volvocars","warning",sprintf(__("la commande %s est introuvable",__FILE__),'msg2widget'));
			return;
		}
		$this->getLock();
		$messages = json_decode($cmd->execCmd(),true);
		if (!is_array($messages)) {
			$messages = array();
		}
		if (!isset($messages[$cible])){
			$messages[$cible] = array();
		}
		$messages[$cible][$id] = $message;
		$this->checkAndUpdateCmd('msg2widget',json_encode($messages));
		$this->releaseLock();
		return;
	}

	/*
	 * Retrait d'un message d'aides du panel
	 * Un lock permet de s'assurer qu'un seul process lit, modifie pour enregistre
	 * les messages
	 */
	public function rmWidgetMessage ($cible, $id) {
		$cmd = $this->getCmd("info","msg2widget");
		if (! is_object($cmd)) {
			log::add("volvocars","warning",sprintf(__("la commande %s est introuvable",__FILE__),'msg2widget'));
			return;
		}
		$this->getLock();
		$messages = json_decode($cmd->execCmd(),true);
		if (!is_array($messages)) {
			$this->releaseLock();
			return;
		}
		if (!isset($messages[$cible])) {
			$this->releaseLock();
			return;
		}
		if (isset($messages[$cible][$id])) {
			unset($messages[$cible][$id]);
			$this->checkAndUpdateCmd('msg2widget',json_encode($messages));
		}
		$this->releaseLock();
		return;
	}

	/*
	 * Suppression de messages pour les commandes inexistantes
	 */
	public function cleanWidgetMessages() {
		$cmd = $this->getCmd("info","msg2widget");
		if (! is_object($cmd)) {
			log::add("volvocars","warning",sprintf(__("la commande %s est introuvable",__FILE__),'msg2widget'));
			return;
		}
		$this->getLock();
		$messages = json_decode($cmd->execCmd(),true);
		if (!is_array($messages)) {
			$this->releaseLock();
			return;
		}
		foreach (array_keys($messages) as $cible){
			if (is_array($messages[$cible])) {
				foreach(array_keys($messages[$cible]) as $id) {
					$cmd = cmd::byId($id);
					if (!is_object($cmd)){
						unset ($messages[$cible][$id]);
					}
				}
				if (count($messages[$cible]) == 0) {
					unset ($messages[$cible]);
				}
			}
		}
		$this->checkAndUpdateCmd('msg2widget',json_encode($messages));
		$this->releaseLock();
	}

	/*
	* Widget pour le panel
	*/
	public function toHtml($_version = 'dashboard') {

		// $this->emptyCacheWidget();

		if ($_version !== 'panel') {
			return parent::toHtml($_version);
		}

		$replace = $this->preToHtml($_version);
		if (!is_array($replace)){
			return $replace;
		}

		$carId = $this->getId();
		$replace['#id#'] = $carId;

		//---- IMAGE
		$replace['#vehicle_img'.$carId.'#'] = $this->getImage();

		//---- SITES
		$replace['#site1_name'.$this->getId().'#'] = ucfirst($this->getConfiguration('site1_name'));
		$replace['#site2_name'.$this->getId().'#'] = ucfirst($this->getConfiguration('site2_name'));
		$replace['#site1_active'.$this->getId().'#'] = $this->getConfiguration('site1_active',0);
		$replace['#site2_active'.$this->getId().'#'] = $this->getConfiguration('site2_active',0);
		$replace['#site1_limit'.$this->getId().'#'] = $this->getConfiguration('site1_limit',0);
		$replace['#site2_limit'.$this->getId().'#'] = $this->getConfiguration('site2_limit',0);

		//---- COMMANDES ID et VALUE
		$logicalIds = array(
			'availability',
			'unavailableReason',
			'al_brakeFluid',
			'al_coolant',
			'al_electricautonomy',
			'al_fuelautonomy',
			'al_light',
			'al_oil',
			'al_tyre',
			'al_washerFluid',
			'batteryLevel',
			'chargingEndTime',
			'chargingStatus',
			'climStart',
			'climStop',
			'honk',
			'flash',
			'honk_flash',
			'connectorStatus',
			'consoElectric',
			'consoFuel',
			'consoFuelTrip',
			'distanceToService',
			'distanceSite1',
			'distanceSite2',
			'doorFlState',
			'doorFrState',
			'doorRlState',
			'doorRrState',
			'electricAutonomy',
			'engineHoursToService',
			'engineON',
			'fuelAmount',
			'fuelAutonomy',
			'hoodState',
			'lastAnswer',
			'locked',
			'lock',
			'odometer',
			'position',
			'presenceSite1',
			'presenceSite2',
			'refresh',
			'roofState',
			'service',
			'serviceTrigger',
			'tailState',
			'timeToService',
			'tyreFl',
			'tyreFr',
			'tyreRl',
			'tyreRr',
			'unlock',
			'winFlState',
			'winFrState',
			'winRlState',
			'winRrState',
		);
		foreach ($logicalIds as $logicalId) {
			$cmd = $this->getCmd(null,$logicalId);
			if (is_object($cmd)) {
				if ($cmd->getType() == 'info') {
					$value = $cmd->execCmd();
					$unit=$cmd->getUnite();
					$display_value = $cmd->getDisplayValue($value);
					if ($cmd->getSubType() == 'numeric') {
						$valueInfo = volvocarsCmd::autoValueArray($value, $cmd->getConfiguration('historizeRound', 99), $cmd->getUnite());
						$display_value = $valueInfo[0];
						$unit = $valueInfo[1];
					}
					$replace['#' . $logicalId . '_id' . $carId . '#'] = $cmd->getId();
					$replace['#' . $logicalId . $carId . '#'] = $value;
					$replace['#' . $logicalId . '_display_value' . $carId . '#'] = $display_value;
					$replace['#' . $logicalId . '_unit' . $carId . '#'] = $unit;
					$replace['#' . $logicalId . '_history' . $carId . '#'] = $cmd->getIsHistorized() ? 'history cursor' : '';
				}
				$replace['#' . $logicalId . '_id' . $carId . '#'] = $cmd->getId();
				$replace['#' . $logicalId . '_hidden' . $carId . '#'] = '';
			} else {
				$replace['#' . $logicalId . '_hidden' . $carId . '#'] = 'hidden';
			}
		}

		//---- WIDGETS UNIQUEMENT POUR MOTEUR THERMIQUE
		if ($this->getConfiguration('fuelEngine') == 0) {
			$replace['#fuelEngineOnly'.$carId.'#'] = 'hidden';
		} else {
			$replace['#fuelEngineOnly'.$carId.'#'] = '';
		}

		//---- WIDGETS UNIQUEMENT POUR MOTEUR ELECTRIQUE
		if ($this->getConfiguration('electricEngine') == 0) {
			$replace['#electricEngineOnly'.$carId.'#'] = 'hidden';
		} else {
			$replace['#electricEngineOnly'.$carId.'#'] = '';
		}

		$cmd = $this->getCmd('info','msg2widget');
		if (is_object($cmd)) {
			$replace['#msg2widget_id' . $carId . '#'] = $cmd->getId();
			$replace['#msg2widget' . $carId . '#'] = addslashes($cmd->execCmd());
		}

		if ($_version === 'panel') {
			$widgetFile = realpath( __DIR__ . '/../template/dashboard/volvocars_panel.html');
		}
		$html = template_replace($replace, file_get_contents($widgetFile));
		return translate::exec($html,$widgetFile);
		return $this->postToHtml($_version, template_replace($replace, getTemplate('core',$_version,$template, 'volvocars')));
	}

	/*
	 * Retourne la position actuelle du véhicule
	 */
	public function getPosition(){
		$position = array('lat' => '0.000000', 'long' => '0.000000');
		$cmd = $this->getCmd('info','position');
		if ( is_object($cmd)) {
			$coordinate = explode(',',$cmd->execCmd());
			if (count($coordinate) == 2) {
				$position['lat'] = $coordinate[0];
				$position['long'] = $coordinate[1];
			}
		}
		return $position;
	}

	/*	 * **********************Getteur Setteur*************************** */

	public function setVin($_vin){
		$this->setLogicalId($_vin);
		return $this;
	}

	public function getVin(){
		return $this->getLogicalId();
	}

	public function setAccount_id($_account_id){
		$this->setConfiguration('account_id',$_account_id);
		return $this;
	}

	public function getAccount_id(){
		return $this->getConfiguration('account_id');
	}

	public function getAccount(){
		$account = volvoAccount::byId($this->getAccount_id());
		if (!is_object($account)) {
			throw new Exception (sprintf(__("L'account '%s' est introuvable",__FILE__),$this->getAccount_id()));
		}
		return $account;
	}

}

class volvocarsCmd extends cmd {
	/*	 * *************************Attributs****************************** */

	/*
	public static $_widgetPossibility = array();
	*/

	/*	 * ***********************Methode static*************************** */


	/*	 * *********************Methode d'instance************************* */

	/*
	* Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
	*/
	public function dontRemoveCmd() {
		if ($this->getConfiguration('removable',0) == 1) {
			return false;
		}
		return true;
	}

	public function preSave(){
		$logicalIds = $this->getConfiguration('dependTo');
		if (!is_array($logicalIds)) {
			$logicalIds = array ($logicalIds);
		}
		$value = '';
		foreach ($logicalIds as $logicalId) {
			$cmd = $this->getEqLogic()->getCmd('info',$logicalId);
			if (is_object($cmd)) {
				$value .= '#' . $cmd->getId() . '#';
			}
		}
		$this->setValue($value);

		switch ($this->getLogicalId()) {
			case 'distanceSite1':
			case 'distanceSite2':
				$locationCmd = $this->getEqLogic()->getCmd('info','position');
				if (is_object($locationCmd)) {
					$this->setValue("#" . $locationCmd->getId() . '#');
				}
				break;
			case 'presenceSite1':
				$distanceCmd = $this->getEqLogic()->getCmd('info','distanceSite1');
				if (is_object($distanceCmd)) {
					$this->setValue("#" . $distanceCmd->getId() . '#');
				}
				break;
			case 'presenceSite2':
				$distanceCmd = $this->getEqLogic()->getCmd('info','distanceSite2');
				if (is_object($distanceCmd)) {
					$this->setValue("#" . $distanceCmd->getId() . '#');
				}
				break;
		}
	}

	public function postInsert() {
		$logicalIds = $this->getConfiguration('dependencies');
		if ($logicalIds !== '') {
			if (!is_array($logicalIds)) {
				$logicalIds = array($logicalIds);
			}
			foreach ($logicalIds as $logicalId) {
				$cmd = $this->getEqLogic()->getCmd('info',$logicalId);
				if (is_object($cmd)) {
					$cmd->save();
				}
			}
		}
		switch ($this->getLogicalId()) {
			case 'position':
				foreach (['distanceSite1', 'distanceSite2'] as $logicalId) {
					$cmd = $this->getEqLogic()->getCmd('info',$logicalId);
					if (is_object($cmd)) {
						$cmd->save();
					}
				}
				break;
			case 'distanceSite1':
				$cmd = $this->getEqLogic()->getCmd('info','presenceSite1');
				if (is_object($cmd)) {
					$cmd->save();
				}
				break;
			case 'distanceSite2':
				$cmd = $this->getEqLogic()->getCmd('info','presenceSite2');
				if (is_object($cmd)) {
					$cmd->save();
				}
				break;
		}
	}

	// Exécution d'une commande
	public function execute($_options = array()) {
		$car = $this->getEqLogic();
		$logicalId = $this->getLogicalId();
		if ($this->getType() == 'action') {
			log::add("volvocars","info",sprintf(__("Execution de la commande %s pour le véhicule %s",__FILE__),$this->getName(),$car->getName()));
			if ($this->getConfiguration('volvoApi') !== '') {
				$car->getAccount()->sendCommand($this);
				$endpoint = $this->getConfiguration('linkedEndpoint');
				if ($endpoint !== '') {
					$car->getInfosFromApi($endpoint, true);
				}
				return;
			}
			if ($logicalId === 'refresh') {
				$car->refresh(true);
				return;
			}
			log::add("volvocars","error",sprintf(__('Exécution de la commande action "%s" non définie',__FILE__),$this->getLogicalId()));
			return false;
		}
		if ($this->getType() == 'info') {
			switch ($logicalId) {
				case 'distanceSite1':
				case 'distanceSite2':
					switch($logicalId) {
						case 'distanceSite1':
							if ($car->getConfiguration('site1_active') != 1) {
								return '-1';
							}
							$siteLat = $car->getConfiguration('site1_lat');
							$siteLong = $car->getConfiguration('site1_long');
							break;
						case 'distanceSite2':
							if ($car->getConfiguration('site2_active') != 1) {
								return '-1';
							}
							$siteLat = $car->getConfiguration('site2_lat');
							$siteLong = $car->getConfiguration('site2_long');
							break;
					}
					$position = $car->getPosition();
					if ($position['lat'] == 0 && $position['long'] == 0) {
						log::add("volvocars","warning",__("Les coordonées GPS de la position du véhicule ne sont pas définies",__FILE__));
						return '-1';
					}
					$earth_radius = 6371;

					$rla1 = deg2rad( floatval($siteLat) );
					$rlo1 = deg2rad( floatval($siteLong) );
					$rla2 = deg2rad( floatval($position['lat']) );
					$rlo2 = deg2rad( floatval($position['long']) );
					$dlo = ($rlo2 - $rlo1) / 2;
					$dla = ($rla2 - $rla1) / 2;
					$a = (sin($dla) * sin($dla)) + cos($rla1) * cos($rla2) * (sin($dlo) * sin($dlo));
					$d = 2 * atan2(sqrt($a), sqrt(1 - $a));
					return round(($earth_radius * $d * 1000), 1);
					break;
				case 'presenceSite1':
					if ($car->getConfiguration('site1_active') != 1) {
						return '';
					}
					$distanceCmd = $car->getCmd('info','distanceSite1');
					if (! is_object($distanceCmd)) {
						log::add("volvocars","error",__("La commande de distance pour le site 1 est introuvable",__FILE__));
						return '';
					}
					$distance = $distanceCmd->execCmd();
					if ($distance < 0) {
						log::add("volvocars","error",__("La distance du site 1 est indéterminée",__FILE__));
						return '';
					}
					$limite = $car->getConfiguration('site1_limit', '');
					if (! is_numeric($limite)) {
						log::add("volvocars","error",__("La distance limite pour le site 1 est indéterminée",__FILE__));
						return '';
					}
					if ($distance <= $limite) {
						return 1;
					} else {
						return 0;
					}
					break;
				case 'presenceSite2':
					if ($car->getConfiguration('site2_active') != 1) {
						return '';
					}
					$distanceCmd = $car->getCmd('info','distanceSite2');
					if (! is_object($distanceCmd)) {
						log::add("volvocars","error",__("La commande de distance pour le site 2 est introuvable",__FILE__));
						return '';
					}
					$distance = $distanceCmd->execCmd();
					if ($distance < 0) {
						log::add("volvocars","error",__("La distance du site 2 est indéterminée",__FILE__));
						return '';
					}
					$limite = $car->getConfiguration('site2_limit', '');
					if (! is_numeric($limite)) {
						log::add("volvocars","error",__("La distance limite pour le site 2 est indéterminée",__FILE__));
						return '';
					}
					if ($distance <= $limite) {
						return 1;
					} else {
						return 0;
					}
					break;
				case 'al_brakeFluid':
					$cmd = $this->getEqLogic()->getCmd('info','brakeFluidLevel');
					if (is_object($cmd)) {
						switch ($cmd->execCmd()) {
							case 'TOO_LOW':
								return 1;
						}
					}
					return 0;
					break;
				case 'al_coolant':
					$cmd = $this->getEqLogic()->getCmd('info','coolantLevel');
					if (is_object($cmd)) {
						switch ($cmd->execCmd()) {
							case 'TOO_LOW':
								return 1;
						}
					}
					return 0;
					break;
				case 'al_oil':
					$cmd = $this->getEqLogic()->getCmd('info','oilLevel');
					if (is_object($cmd)) {
						switch ($cmd->execCmd()) {
							case 'SERVICE_REQUIRED':
							case 'TOO_LOW':
							case 'TOO_HIGH':
								return 1;
						}
					}
					return 0;
					break;
				case 'al_washerFluid':
					$cmd = $this->getEqLogic()->getCmd('info','washerFluidLevel');
					if (is_object($cmd)) {
						switch ($cmd->execCmd()) {
							case 'TOO_LOW':
								return 1;
						}
					}
					return 0;
					break;
				case 'al_electricAutonomy':
					if ($car->getConfiguration('electricEngine') != 1) {
						return 0;
					}
					$limit = $car->getConfiguration('electricAutonomyLimit');
					$autonomyCmd = $car->getCmd('info','electricAutonomy');
					if (! is_object($autonomyCmd)) {
						return 0;
					}
					if ($autonomyCmd->execCmd() < $limit) {
						return 1;
					}
					return 0;
					break;
				case 'al_fuelAutonomy':
					if ($car->getConfiguration('fuelEngine') != 1) {
						return 0;
					}
					$limit = $car->getConfiguration('fuelAutonomyLimit');
					$autonomyCmd = $car->getCmd('info','fuelAutonomy');
					if (! is_object($autonomyCmd)) {
						return 0;
					}
					if ($autonomyCmd->execCmd() < $limit) {
						return 1;
					}
					return 0;
					break;
				case 'al_tyre':
					$value = 0;
					foreach (['tyreFl', 'tyreFr', 'tyreRl', 'tyreRr'] as $tyre) {
						$tyreCmd = $this->getEqLogic()->getCmd('info', $tyre);
						if (is_object($tyreCmd)) {
							switch ($tyreCmd->execCmd()) {
								case 'VERY_LOW_PRESSURE':
								case 'LOW_PRESSURE':
								case 'HIGH_PRESSURE':
									$value = 1;
							}
						}
						if ($value == 1) {
							break;
						}
					}
					return $value;
					break;
				case 'al_light':
					$value = 0;
					foreach ([
						'al_brakeLightC',
						'al_brakeLightL',
						'al_brakeLightR',
						'al_daytimeRunningLightL',
						'al_daytimeRunningLightR',
						'al_fogLightF',
						'al_fogLightR',
						'al_hazardLights',
						'al_highBeamL',
						'al_highBeamR',
						'al_lowBeamL',
						'al_lowBeamR',
						'al_positionLightFl',
						'al_positionLightFr',
						'al_positionLightRl',
						'al_positionLightRr',
						'al_registrationPlateLight',
						'al_reverseLights',
						'al_sideMarkLights',
						'al_turnIndicationFl',
						'al_turnIndicationFr',
						'al_turnIndicationRl',
						'al_turnIndicationRr'
					] as $light) {
						$lightCmd = $this->getEqLogic()->getCmd('info', $light);
						if (is_object($lightCmd)) {
							if ($lightCmd->execCmd() == 'FAILURE') {
								$value = 1;
								break;
							}
						}
					}
					return $value;
					break;
				case 'doorFlOpen':
				case 'doorFrOpen':
				case 'doorRlOpen':
				case 'doorRrOpen':
				case 'winFlOpen':
				case 'winFrOpen':
				case 'winRlOpen':
				case 'winRrOpen':
				case 'hoodOpen':
				case 'tailOpen':
				case 'tankOpen':
				case 'roofOpen':
					$stateCmd = $car->getCmd('info',str_replace('open','state',$logicalId));
					if (is_object($stateCmd)) {
						switch ($stateCmd->execCmd()) {
							case 'OPEN':
								return 1;
							case 'AJAR':
							case 'CLOSED':
								return 0;
						}
						return $this->execCmd();
					}
				case 'doorFlClosed':
				case 'doorFrClosed':
				case 'doorRlClosed':
				case 'doorRrClosed':
				case 'winFlClosed':
				case 'winFrClosed':
				case 'winRlClosed':
				case 'winRrClosed':
				case 'hoodClosed':
				case 'tailClosed':
				case 'tankClosed':
				case 'roofClosed':
					$stateCmd = $car->getCmd('info',str_replace('closed','state',$logicalId));
					if (is_object($stateCmd)) {
						switch ($stateCmd->execCmd()) {
							case 'OPEN':
								return 0;
							case 'AJAR':
							case 'CLOSED':
								return 1;
						}
						return $this->execCmd();
					}
				case 'chargingEndTime':
					$cmd = $car->getCmd('info','chargingRemainingTime');
					if (! is_object($cmd)){
						return "";
					}
					$remaining = $cmd->execCmd();
					if ($remaining == 0) {
						return "";
					}
					return date_fr(date('D H:i', strtotime($cmd->getValueDate() . "+" . $remaining . "minutes")));
					break;
				default:
					log::add("volvocars","error",sprintf(__('Exécution de la commande "%s" non définie',__FILE__),$this->getLogicalId()));
					return false;
			}
		}
	}

	public function getDisplayValue($value) {
		$textes = [
			"CHARGING_SYSTEM_CHARGING"		=> __("en charge",__FILE__),
			"CHARGING_SYSTEM_IDLE"			=> __("en pause",__FILE__),
			"CHARGING_SYSTEM_DONE"			=> __("terminée",__FILE__),
			"CHARGING_SYSTEM_FAULT"			=> __("en erreur",__FILE__),
			"CHARGING_SYSTEM_SCHEDULED"		=> __("programmée",__FILE__),
			"CHARGING_SYSTEM_UNSPECIFIED"	=> __("état inconnu",__FILE__),

			"CONNECTION_STATUS_CONNECTED_AC" => __("branchée (AC)",__FILE__),
			"CONNECTION_STATUS_CONNECTED_DC" => __("branchée (DC)",__FILE__),
			"CONNECTION_STATUS_DISCONNECTED" => __("débranchée",__FILE__),
			"CONNECTION_STATUS_FAULT"		 => __("en erreur",__FILE__),
			"CONNECTION_STATUS_UNSPECIFIED"	 => __("état inconnu",__FILE__),

			"AVAILABLE"	   => __("accessible",__FILE__),
			"UNAVAILABLE"  => __("indisponnible",__FILE__),
			"NO_INTERNET"  => __("pas d'accès Internet",__FILE__),
			"POWER_SAWING" => __("en veille",__FILE__),
			"CAR_IN_USE"   => __("en court d'utilisation",__FILE__),
			"QUOTA_OUT"    => __("quota API dépassé",__FILE__),

			"LOCKED"	=> __("verrouillé",__FILE__),
			"UNLOCKED"	=> __("déverrouillé",__FILE__),

			"OPEN"		=> __("ouvert",__FILE__),
			"AJAR"		=> __("entre-ouvert",__FILE__),
			"CLOSED"	=> __("fermé",__FILE__),

			"UNKNOWN_WARNING"								=> __("Service pour raison indterminée",__FILE__),
			"REGULAR_MAINTENANCE_ALMOST_TIME_FOR_SERVICE"	=> __("Service régulier à prévoire",__FILE__),
			"REGULAR_MAINTENANCE_TIME_FOR_SERVICE"			=> __("Service régulier à faire",__FILE__),
			"REGULAR_MAINTENANCE_OVERDUE_FOR_SERVICE"		=> __("service régulier en retard",__FILE__),
			"ENGINE_HOURS_ALMOST_TIME_FOR_SERVICE"			=> __("Service moteur à prévoire",__FILE__),
			"ENGINE_HOURS_TIME_FOR_SERVICE"					=> __("Service moteur à faire",__FILE__),
			"ENGINE_HOURS_OVERDUE_FOR_SERVICE"				=> __("Service moteur en retard",__FILE__),
			"DISTANCE_DRIVEN_ALMOST_TIME_FOR_SERVICE"		=> __("Service suite kilométrage à prévoire",__FILE__),
			"DISTANCE_DRIVEN_TIME_FOR_SERVICE"				=> __("Service suite kilométrage à faire",__FILE__),
			"DISTANCE_DRIVEN_OVERDUE_FOR_SERVICE"			=> __("Service suite kilométrage en retard",__FILE__),

			"CALENDAR_TIME"	=> __("Temps depuis dernier service",__FILE__),
			"DISTANCE"		=> __("Kilomètres parcourus",__FILE__),
			"ENGINE_HOURS"	=> __("Temps de fonctionnement du moteur",__FILE__),

			"UNSPECIFIED"	=> __("Indéfini",__FILE__),
			"UNKNOWN"		=> __("Inconnu",__FILE__),
			"NO_WARNING"	=> __("OK",__FILE__),
		];

		if ($this->getSubType() == 'string') {
			if (isset($textes[$value])) {
				return $textes[$value];
			}
			return $value;
		}
		if ($this->getSubType() == 'binary' && $this->getDisplay('invertBinary') == 1) {
			return ($value == 1) ? 0 : 1;
		}

		if ($this->getSubType() == 'numeric') {
			if (trim($value) === '') {
				$value = 0;
			}
			if ($this->getLogicalId() == 'chargingRemainingTime') {
				return sprintf("%d:%02d", floor($value / 60), $value % 60);
			}
			return $value;
		}
		if ($this->getSubType() == 'binary' && trim($value) === '') {
			return 0;
		}
		return $value;
	}

	public function formatValueWidget($value) {
		return $this->getDisplayValue($value);
	}

	/*	 * **********************Getteur Setteur*************************** */

}
