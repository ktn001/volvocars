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
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/volvoAccount.class.php';

class volvocars extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*
	 * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
	 * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	 * public static $_widgetPossibility = array();
	*/

	/*
	 * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
	 * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
	 * public static $_encryptConfigKey = array('param1', 'param2');
	*/

	/*     * ***********************Methode static*************************** */

	/*
	 * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
	 * lors de la création semi-automatique d'un post sur le forum community
	 * public static function getConfigForCommunity() {
	 *	return "les infos essentiel de mon plugin";
	 * }
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

	public static function byVin($_vin, $_onlyEnable = false) {
		return self::byLogicalId($_vin, __CLASS__);
	}

	public static function byName($_name, $_onlyEnable = false) {
		$cars = array();
		foreach (self::byType(__CLASS__, $_onlyEnable) as $car) {
			if ($car->getName() == $_name) {
				$cars[] = $car;
			}
		}
		return $cars;
	}

	private static function convertKeyword($keyword) {
		$value = $keyword;
		switch ($keyword){
			case 'CLOSED':
			case 'LOCKED':
				$value = array (
					'c' => 1,
					'o' => 0,
					's' => 0
				);
				break;
			case 'AJAR':
				$value = array (
					'c' => 0,
					'o' => 0,
					's' => 1
				);
				break;
			case 'OPEN':
			case 'UNLOCKED':
				$value = array (
					'c' => 0,
					'o' => 1,
					's' => 2
				);
				break;
			case 'HIGH_PRESSURE':
				$value = 1;
				break;
			case 'LOW_PRESSURE':
				$value = 2;
				break;
			case 'VERY_LOW_PRESSURE':
				$value = 3;
				break;
			case 'FAILURE':
				$value = 1;
				break;
			case 'NO_WARNING':
				$value = 0;
				break;
			case 'TOO_LOW':
				$value = 1;
				break;
			case 'UNSPECIFIED':
				$value = -1;
				break;
		}
		return $value;
	}

	/*     * *********************Méthodes d'instance************************* */

	// Fonction exécutée automatiquement avant la création de l'équipement
	public function preInsert() {
		if ($this->getVin() != '') {
			$car = self::byVin($this->getVin());
			if (is_object($car)){
				throw new Exception (__("Il y a déjà un véhicule avec ce vin!",__FILE__));
			}
		}
	}

	// Fonction exécutée automatiquement avant la mise à jour de l'équipement
	public function preUpdate() {
		if ($this->getVin() == '') {
			throw new Exception (__("Le vin n'est pas défini",__FILE__));
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

		if ($this->getConfiguration('heatEngine')){
			$limit = trim($this->getConfiguration('heatAautonomyLimit'));
			if ($limit == '') {
				$limit = 0;
			}
			if (! is_numeric($limit)) {
				throw new Exception (__("La limite d'autonomie doit être une valeur numérique",__FILE__));
			}
			$this->setConfiguration('heatAutonomyLimit', $limit);
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
		$this->setConfiguration('old_site1_name',$car->getConfiguration('site1_name'));
		$this->setConfiguration('old_site2_name',$car->getConfiguration('site2_name'));
	}

	public function postAjax() {
		foreach (['distance_site1','distance_site2'] as $logicalId){
			$cmd = $this->getCmd('info',$logicalId);
			if (is_object($cmd)) {
				$cmd->event($cmd->execute());
				$cmdName = $cmd->getName();
				switch ($logicalId) {
					case 'distance_site1':
						$siteName = $this->getConfiguration('site1_name');
						$oldSiteName = $this->getConfiguration('old_site1_name');
						$site = 'site1';
						break;
					case 'distance_site2':
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
		foreach (['presence_site1','presence_site2'] as $logicalId){
			$cmd = $this->getCmd('info',$logicalId);
			if (is_object($cmd)) {
				$cmdName = $cmd->getName();
				switch ($logicalId) {
					case 'presence_site1':
						$siteName = $this->getConfiguration('site1_name');
						$oldSiteName = $this->getConfiguration('old_site1_name');
						$site = 'site1';
						break;
					case 'presence_site2':
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
			}
		}
		foreach ([
			'al_electricAutonomy',
			'al_heatAutonomy'
		] as $logicalId) {
			$cmd = $this->getCmd('info',$logicalId);
			if (is_object($cmd)){
				$cmd->event($cmd->execute());
			}
		}
	}

	public function getImage() {
		$img = $this->getVin() .'.png';
		$imgPath = __DIR__ . '/../../data/' . $img;
		if (file_exists($imgPath)){
			return '/plugins/volvocars/data/' . $img;
		}
		$plugin = plugin::byId($this->getEqType_name());
		return $plugin->getPathImgIcon();
	}

	public function synchronize() {
		$this->updateDetails();
		$this->retrieveInfos(true);
		$this->createActionCmds();
	}

	public function updateDetails() {
		$changed = false;
		$account = $this->getAccount();
		$details = $account->getInfos('details',$this->getVin());
		log::add("volvocars","debug","DETAILS: " . json_encode($details));
		if (! isset($details['descriptions'])){
			log::add("volvocars","error",(__("Pas de key 'descriptions' dans les détails[data]",__FILE__)));
		} else {

			// Le modèle
			// ---------
			if (isset($details['descriptions']['model'])) {
				if ($details['descriptions']['model'] != $this->getConfiguration('model')) {
					log::add("volvocars","info",sprintf(__("Mise à jour du modèle pour le véhicule %s",__FILE__),$this->getVin()));
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
			log::add("volvocars","warning",sprintf(__("L'année de construction du le véhicule %s indéninie",__FILE__),$this->getVin()));
		} else {
			if ($details['modelYear'] != $this->getConfiguration('modelYear')){
				log::add("volvocars","info",sprintf(__("Mise à jour de l'année pour le véhicule %s",__FILE__),$this->getVin()));
				$this->setConfiguration('modelYear',$details['modelYear']);
				$changed = true;
			}
		}

		// Couleur
		// -------
		if (! isset($details['externalColour'])) {
			log::add("volvocars","warning",sprintf(__("la couleur du véhicule %s n'est pas définie",__FILE__),$this->getVin()));
		} else {
			if ($details['externalColour'] != $this->getConfiguration('externalColour')){
				log::add("volvocars","info",sprintf(__("Mise à jour de la couleur pour le véhicule %s",__FILE__),$this->getVin()));
				$this->setConfiguration('externalColour',$details['externalColour']);
				$changed = true;
			}
		}

		// Boîte à vitesse
		// ---------------
		if (! isset($details['gearbox'])) {
			log::add("volvocars","warning",sprintf(__("Le type de boîte à vitesse n'est pas défini pour le véhicule %s",__FILE__),$this->getVin()));
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
				log::add("volvocars","info",sprintf(__("Mise à jour du type de boîte à vitesse pour le véhicule %s",__FILE__),$this->getVin()));
				$this->setConfiguration('gearbox',$gearbox);
				$changed = true;
			}
		}

		// Carburant
		// ---------
		if (! isset($details['fuelType'])) {
			log::add("volvocars","warning",sprintf(__("Le carburant n'est pas défini pour le véicule %s",__FILE__),$this->getVin()));
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
				log::add("volvocars","info",sprintf(__("Mise à jour du carburant pour le véhicule %s",__FILE__),$this->getVin()));
				$this->setConfiguration('fuelType',$fuelType);
				$this->setConfiguration('heatEngine',$combustion);
				$this->setConfiguration('electricEngine',$electric);
				$changed = true;
			}
		}

		// Capacité batterie
		// -----------------
		if (isset($details['batteryCapacityKWH'])){
			if ($details['batteryCapacityKWH'] != $this->getConfiguration('batteryCapacityKWH')){
				log::add("volvocars","info",sprintf(__("Mise à jour da capacité de la batterie pour le véhicule %s",__FILE__),$this->getVin()));
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
				log::add("volvocars","debug","IMAGE: " . $url);
				$imgPath = __DIR__ . '/../../data';
				if (! is_dir($imgPath)){
					mkdir($imgPath);
				}
				$imgPath .= '/'.$this->getVin() . ".png";
				log::add("volvocars","debug",$imgPath);
				// file_put_contents($imgPath, file_get_contents($url));
				$session = curl_init($url);
				$image = fopen($imgPath, 'wb');
				curl_setopt($session,CURLOPT_FILE, $image);
				curl_setopt($session,CURLOPT_HEADER,0);
				curl_exec($session);
				$httpCode = curl_getinfo($session,CURLINFO_HTTP_CODE);
				if ($httpCode != 200) {
					log::add("volvocars","info",sprintf(__("Erreur lors du éléchargement de l'image. HTTPCODE: %s",__FILE__) . $httpCode));
				}
				curl_close($session);
				fclose($image);
			}
			if (isset($details['images']['internalImageUrl'])){
			}
		}

		if ($changed) {
			$this->save();
		}
	}

	public function createActionCmds() {
		$account = $this->getAccount();
		$commands = $account->getInfos('commands',$this->getVin());
		foreach ($commands as $command) {
			log::add("volvocars","debug",print_r($command,true));
			$logicalId = '';
			$name = '';
			switch($command['command']) {
				case 'LOCK_REDUCED_GUARD':
					$logicalId = 'lock_reduced';
					$name = __('Vérrouillage réduit',__FILE__);
					break;
				case 'LOCK':
					$logicalId = 'lock';
					$name = __('Vérrouillage',__FILE__);
					break;
				case 'UNLOCK':
					$logicalId = 'unlock';
					$name = __('Dévérrouillage',__FILE__);
					break;
				case 'CLIMATIZATION_START':
					$logicalId = 'clim_start';
					$name = __('Climatisation EN',__FILE__);
					break;
				case 'CLIMATIZATION_STOP':
					$logicalId = 'clim_stop';
					$name = __('Climatisation HORS',__FILE__);
					break;
				default:
					log::add("volvocars","warning",sprintf(__("Command %s inconnue! pas de créaction de command action",__FILE__),$command['command']));
					continue 2;
			}
			$cmd = $this->getCmd('action',$logicalId);
			if (!is_object($cmd)) {
				log::add("volvocars","debug",sprintf(__("Création de la commande %s",__FILE__),$logicalId));
				$cmd = new volvocarsCmd();
				$cmd->setEqLogic_id($this->getId());
				$cmd->setType('action');
				$cmd->setSubtype('other');
				$cmd->setName($name);
				$cmd->setLogicalid($logicalId);
				$cmd->save();
			}
		}
	}

	private function getInfosFromApi($endpoint, $createCmds=false, $updateValues=true){
		if ($this->getConfiguration('heatEngine') == 0){
			if ($endpoint == 'engine_diagnostics'){
				return;
			}
		}
		$account = $this->getAccount();
		$infos = $account->getInfos($endpoint,$this->getVin());
		foreach (array_keys($infos) as $key) {
			$logicalId = [];
			$updateValue = [];
			$name = [];
			$unit = null;
			$subType = array(
				'c' => 'numeric',
				'o' => 'numeric',
				's' => 'numeric',
			);
			switch ($endpoint.".".$key) {
				case 'brake_fluid.brakeFluidLevelWarning':
					$logicalId = 'al_brake_fluid';
					$name = __("niveau liquide de frein",__FILE__);
					$subType = "numeric";
					break;
				case 'diagnostics.engineCoolantLevelWarning':
					$logicalId = 'al_coolant';
					$name = __("niveau du liquide de refroidissement",__FILE__);
					$subType = "numeric";
					break;
				case 'diagnostics.oilLevelWarning':
					$logicalId = 'al_oil';
					$name = __("niveau d'huile",__FILE__);
					$subType = "numeric";
					break;
				case 'diagnostics.washerFluidLevelWarning':
					$logicalId = 'al_washer_fluid';
					$name = __('Lave-vitre',__FILE__);
					$subType = 'numeric';
					break;
				case 'doors.centralLock':
					$logicalId['c'] = 'lock_locked';
					$logicalId['o'] = 'lock_unlocked';
					$logicalId['s'] = 'lock_state';
					$name['c'] = __('vérouillé',__FILE__);
					$name['o'] = __('dévérouillé',__FILE__);
					$name['s'] = __('état verouillage',__FILE__);
					break;
				case 'doors.frontLeftDoor':
					$logicalId['c'] = 'door_fl_closed';
					$logicalId['o'] = 'door_fl_open';
					$logicalId['s'] = 'door_fl_state';
					$name['c'] = __('porte avant gauche fermée',__FILE__);
					$name['o'] = __('porte avant gauche ouverte',__FILE__);
					$name['s'] = __('état porte avant gauche',__FILE__);
					break;
				case 'doors.frontRightDoor':
					$logicalId['c'] = 'door_fr_closed';
					$logicalId['o'] = 'door_fr_open';
					$logicalId['s'] = 'door_fr_state';
					$name['c'] = __('porte avant droite fermée',__FILE__);
					$name['o'] = __('porte avant droite ouverte',__FILE__);
					$name['s'] = __('état porte avant droite',__FILE__);
					break;
				case 'doors.hood':
					$logicalId['c'] = 'hood_closed';
					$logicalId['o'] = 'hood_open';
					$logicalId['s'] = 'hood_state';
					$name['c'] = __('capot fermé',__FILE__);
					$name['o'] = __('capot ouvert',__FILE__);
					$name['s'] = __('état capot',__FILE__);
					break;
				case 'doors.rearLeftDoor':
					$logicalId['c'] = 'door_rl_closed';
					$logicalId['o'] = 'door_rl_open';
					$logicalId['s'] = 'door_rl_state';
					$name['c'] = __('porte arrière gauche fermée',__FILE__);
					$name['o'] = __('porte arrière gauche ouverte',__FILE__);
					$name['s'] = __('état porte arrière gauche',__FILE__);
					break;
				case 'doors.rearRightDoor':
					$logicalId['c'] = 'door_rr_closed';
					$logicalId['o'] = 'door_rr_open';
					$logicalId['s'] = 'door_rr_state';
					$name['c'] = __('porte arrière droite fermée',__FILE__);
					$name['o'] = __('porte arrière droite ouverte',__FILE__);
					$name['s'] = __('état porte arrière droite',__FILE__);
					break;
				case 'doors.tailgate':
					$logicalId['c'] = 'tail_closed';
					$logicalId['o'] = 'tail_open';
					$logicalId['s'] = 'tail_state';
					$name['c'] = __('hayon fermé',__FILE__);
					$name['o'] = __('hayon ouvert',__FILE__);
					$name['s'] = __('état hayon',__FILE__);
					break;
				case 'doors.tankLid':
					$logicalId['c'] = 'tank_closed';
					$logicalId['o'] = 'tank_open';
					$logicalId['s'] = 'tank_state';
					$name['c'] = __('trappe fermée',__FILE__);
					$name['o'] = __('trappe ouverte',__FILE__);
					$name['s'] = __('état trappe',__FILE__);
					break;
				case 'warnings.brakeLightCenterWarning':
					$logicalId['a'] = 'al_brakeLight_c';
					$logicalId['b'] = 'al_light';
					$name['a'] = __('feu frein centrale',__FILE);
					$name['b'] = __('alert lampes',__FILE);
					$subType['a'] = 'numeric';
					$subType['b'] = 'numeric';
					$updateValue['b'] = false;
					break;
				case 'warnings.brakeLightLeftWarning':
					$logicalId = 'al_brakeLight_l';
					$name = __('feu frein gauche',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.brakeLightRightWarning':
					$logicalId = 'al_brakeLight_r';
					$name = __('feu frein droite',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.daytimeRunningLightLeftWarning':
					$logicalId = 'al_daytimeRunningLight_l';
					$name = __('feu jour gauche',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.daytimeRunningLightRightWarning':
					$logicalId = 'al_daytimeRunningLight_r';
					$name = __('feu jour droite',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.fogLightFrontWarning':
					$logicalId = 'al_fogLight_f';
					$name = __('feux brouillard avant',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.fogLightRearWarning':
					$logicalId = 'al_fogLight_r';
					$name = __('feux brouillard arrière',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.hazardLightsWarning':
					$logicalId = 'al_hazardLights';
					$name = __('feux détresse',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.highBeamLeftWarning':
					$logicalId = 'al_highBeam_l';
					$name = __('feu route gauche',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.highBeamRightWarning':
					$logicalId = 'al_highBeam_r';
					$name = __('feu route droite',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.lowBeamLeftWarning':
					$logicalId = 'al_lowBeam_l';
					$name = __('feu croisement gauche',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.lowBeamRightWarning':
					$logicalId = 'al_lowBeam_r';
					$name = __('feu croisement droite',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.positionLightFrontLeftWarning':
					$logicalId = 'al_positionLight_fl';
					$name = __('feu position avant gauche',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.positionLightFrontRightWarning':
					$logicalId = 'al_positionLight_fr';
					$name = __('feu position avant droite',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.positionLightRearLeftWarning':
					$logicalId = 'al_positionLight_rl';
					$name = __('feu position arrière gauche',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.positionLightRearRightWarning':
					$logicalId = 'al_positionLight_rr';
					$name = __('feu position arrière droite',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.registrationPlateLightWarning':
					$logicalId = 'al_registrationPlateLight';
					$name = __('feu plaque',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.reverseLightsWarning':
					$logicalId = 'al_reverseLights';
					$name = __('ifeu recule',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.sideMarkLightsWarning':
					$logicalId = 'al_sideMarkLights';
					$name = __('feux latéraux',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.turnIndicationFrontLeftWarning':
					$logicalId = 'al_turnIndication_fl';
					$name = __('clignotant avant gauche',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.turnIndicationFrontRightWarning':
					$logicalId = 'al_turnIndication_fr';
					$name = __('clignotant avant droit',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.turnIndicationRearLeftWarning':
					$logicalId = 'al_turnIndication_rl';
					$name = __('clignotant arrière gauche',__FILE);
					$subType = 'numeric';
					break;
				case 'warnings.turnIndicationRearRightWarning':
					$logicalId = 'al_turnIndication_rr';
					$name = __('clignotant arrière droit',__FILE);
					$subType = 'numeric';
					break;
				case 'location.location':
					foreach (['site1', 'site2'] as $site) {
						$siteName = $this->getConfiguration($site . '_name');
						if ($siteName == '') {
							if ($site == 'site1') {
								$siteName = __('Domicile',__FILE__);
							} else {
								$siteName = __('Autre',__FILE__);
							}
						}
						$cmd = $this->getCmd('info','presence_' . $site);
						if (!is_object($cmd)) {
							log::add("volvocars","info",sprintf(__("Création de la commande %s",__FILE__),'presence_' . $site));
							$cmd = new volvocarsCmd();
							$cmd->setEqLogic_id($this->getId());
							$cmd->setLogicalId('presence_' . $site);
							$cmd->setName(__("présence" ,__FILE__)." ".$siteName);
							$cmd->setType('info');
							$cmd->setSubType('binary');
							$cmd->save();
						}
						$cmd = $this->getCmd('info','distance_' . $site);
						if (!is_object($cmd)) {
							log::add("volvocars","info",sprintf(__("Création de la commande %s",__FILE__),'distance_' . $site));
							$cmd = new volvocarsCmd();
							$cmd->setEqLogic_id($this->getId());
							$cmd->setLogicalId('distance_' . $site);
							$cmd->setName(__("distance",__FILE__)." ".$siteName);
							$cmd->setType('info');
							$cmd->setSubType('numeric');
							$cmd->save();
						}
					}
					$logicalId = 'position';
					$name = __('position',__FILE__);
					$subType = 'string';
					break;
				case 'statistics.distanceToEmptyBattery':
					$logicalId['a'] = 'electricAutonomy';
					$logicalId['b'] = 'al_electricAutonomy';
					$name['a'] = __('Autonomie électrique',__FILE__);
					$name['b'] = __('Autonomie électrique faible',__FILE__);
					$subType['a'] = 'numeric';
					$subType['b'] = 'numeric';
					$updateValue['b'] = false;
					break;
				case 'statistics.distanceToEmptyTank':
					$logicalId['a'] = 'heatAutonomy';
					$logicalId['b'] = 'al_heatAutonomy';
					$name['a'] = __('Autonomie thermique',__FILE__);
					$name['b'] = __('Autonomie thermique faible',__FILE__);
					$subType['a'] = 'numeric';
					$subType['b'] = 'numeric';
					$updateValue['b'] = false;
					break;
				case 'tyre.frontLeft':
					$logicalId = 'tyre_fl';
					$name = __('pneu avant gauche',__FILE__);
					$subType = 'numeric';
					break;
				case 'tyre.frontRight':
					$logicalId['a'] = 'tyre_fr';
					$logicalId['b'] = 'al_tyre';
					$name['a'] = __('pneu avant droit',__FILE__);
					$name['b'] = __('alerte pneus',__FILE__);
					$subType['a'] = 'numeric';
					$subType['b'] = 'numeric';
					$updateValue['b'] = false;
					break;
				case 'tyre.rearLeft':
					$logicalId = 'tyre_rl';
					$name = __('pneu arrière gauche',__FILE__);
					$subType = 'numeric';
					break;
				case 'tyre.rearRight':
					$logicalId = 'tyre_rr';
					$name = __('pneu arrière droit',__FILE__);
					$subType = 'numeric';
					break;
				case 'windows.frontLeftWindow':
					$logicalId['c'] = 'win_fl_closed';
					$logicalId['o'] = 'win_fl_open';
					$logicalId['s'] = 'win_fl_state';
					$name['c'] = __('fenêtre avant gauche fermée',__FILE__);
					$name['o'] = __('fenêtre avant gauche ouverte',__FILE__);
					$name['s'] = __('état fenêtre avant gauche',__FILE__);
					break;
				case 'windows.frontRightWindow':
					$logicalId['c'] = 'win_fr_closed';
					$logicalId['o'] = 'win_fr_open';
					$logicalId['s'] = 'win_fr_state';
					$name['c'] = __('fenêtre avant droite fermée',__FILE__);
					$name['o'] = __('fenêtre avant droite ouverte',__FILE__);
					$name['s'] = __('état fenêtre avant droite',__FILE__);
					break;
				case 'windows.rearLeftWindow':
					$logicalId['c'] = 'win_rl_closed';
					$logicalId['o'] = 'win_rl_open';
					$logicalId['s'] = 'win_rl_state';
					$name['c'] = __('fenêtre arrière gauche fermée',__FILE__);
					$name['o'] = __('fenêtre arrière gauche ouverte',__FILE__);
					$name['s'] = __('état fenêtre arrière gauche',__FILE__);
					break;
				case 'windows.rearRightWindow':
					$logicalId['c'] = 'win_rr_closed';
					$logicalId['o'] = 'win_rr_open';
					$logicalId['s'] = 'win_rr_state';
					$name['c'] = __('fenêtre arrière droite fermée',__FILE__);
					$name['o'] = __('fenêtre arrière droite ouverte',__FILE__);
					$name['s'] = __('état fenêtre arrière droite',__FILE__);
					break;
				case 'windows.sunroof':
					$logicalId['c'] = 'roof_closed';
					$logicalId['o'] = 'roof_open';
					$logicalId['s'] = 'roof_state';
					$name['c'] = __('toit fermé',__FILE__);
					$name['o'] = __('toit ouvert',__FILE__);
					$name['s'] = __('état toit',__FILE__);
					break;
				default:
					log::add('volvocars','warning',sprintf(__("%s.%s inconnu",__FILE__),$endpoint, $key));
			}
			if ($createCmds) {
				if (is_array($logicalId)) {
					foreach (array_keys($logicalId) as $i) {
						if ($i == 'c' and config::byKey('create_cmd_closed','volvocars') == '0') {
							continue;
						}
						if ($i == 'o' and config::byKey('create_cmd_open','volvocars') == '0') {
							continue;
						}
						if ($i == 's' and config::byKey('create_cmd_state','volvocars') == '0') {
							continue;
						}
						$cmd = $this->getCmd('info',$logicalId[$i]);
						if (! is_object($cmd)) {
							log::add("volvocars","info",sprintf(__("Création de la commande %s",__FILE__),$logicalId[$i]));
							$cmd = new volvocarsCmd();
							$cmd->setEqLogic_id($this->getId());
							$cmd->setLogicalId($logicalId[$i]);
							$cmd->setName($name[$i]);
							$cmd->setType('info');
							$cmd->setSubType($subType[$i]);
							$cmd->save();
						}
					}
				} else {
					$cmd = $this->getCmd('info',$logicalId);
					if (! is_object($cmd)) {
						log::add("volvocars","info",sprintf(__("Création de la commande %s",__FILE__),$logicalId));
						$cmd = new volvocarsCmd();
						$cmd->setEqLogic_id($this->getId());
						$cmd->setLogicalId($logicalId);
						$cmd->setName($name);
						$cmd->setType('info');
						$cmd->setSubType($subType);
						if ($unit !== null){
							$cmd->setUnite($unit);
						}
						$cmd->save();
					}
				}
			}
			if ($updateValues) {
				$time = date('Y-m-d H:i:s', strtotime($infos[$key]['timestamp']));
				$value = self::convertKeyword($infos[$key]['value']);
				if (is_array($logicalId)) {
					foreach (array_keys($logicalId) as $i) {
						if (isset($updateValue[$i]) && $updateValue[$i] == false){
							continue;
						}
						if (is_array($value)) {
							if (isset($value[$i])) {
								$this->checkAndUpdateCmd($logicalId[$i],$value[$i],$time);
							}
						} elseif ($value !== null) {
							$this->checkAndUpdateCmd($logicalId[$i],$value,$time);
						}
					}
				} else {
					switch ($key) {
						case 'location':
							$value = $infos[$key]['coordinates'][1] . ',' . $infos[$key]['coordinates'][0];
							break;
						default:
							$value = $infos[$key]['value'];
					}
					$this->checkAndUpdateCmd($logicalId,$value,$time);
				}
			}
		}
	}

	public function retrieveInfos($createCmds=false) {
		$this->getInfosFromApi('doors',$createCmds);
		$this->getInfosFromApi('location',$createCmds);
		$this->getInfosFromApi('windows',$createCmds);
		$this->getInfosFromApi('engine_diagnostics',$createCmds);
		$this->getInfosFromApi('brake_fluid',$createCmds);
		$this->getInfosFromApi('diagnostics',$createCmds);
		$this->getInfosFromApi('statistics',$createCmds);
		$this->getInfosFromApi('tyre',$createCmds);
		$this->getInfosFromApi('warnings',$createCmds);
	}

	/*
	* Permet de modifier l'affichage du widget (également utilisable par les commandes)
	*/
	public function toHtml($_version = 'dashboard') {

		// $this->emptyCacheWidget();

		$panel = false;
		if ($_version == 'panel') {
			$panel = true;
			$_version = 'dashboard';
		}

		$replace = $this->preToHtml($_version);
		log::add("volvocars","debug",$this->getId() . "  ". print_r($replace,true));
		if (!is_array($replace)){
			return $replace;
		}

		//---- IMAGE
		$replace['#vehicle_img#'] = $this->getImage();

		//---- SITES
		$replace['#site1_name'.$this->getId().'#'] = ucfirst($this->getConfiguration('site1_name'));
		$replace['#site2_name'.$this->getId().'#'] = ucfirst($this->getConfiguration('site2_name'));
		$replace['#site1_active'.$this->getId().'#'] = $this->getConfiguration('site1_active');
		$replace['#site2_active'.$this->getId().'#'] = $this->getConfiguration('site2_active');
		$replace['#site1_limit'.$this->getId().'#'] = $this->getConfiguration('site1_limit');
		$replace['#site2_limit'.$this->getId().'#'] = $this->getConfiguration('site2_limit');
		$cmd = $this->getCmd('info','presence_site1');
		if (is_object($cmd)) {
			$replace['#presence_site1_id#'] = $cmd->getId();
			$replace['#presence_site1#'] = $cmd->execCmd();
		}
		$cmd = $this->getCmd('info','presence_site2');
		if (is_object($cmd)) {
			$replace['#presence_site2_id#'] = $cmd->getId();
			$replace['#presence_site2#'] = $cmd->execCmd();
		}
		$cmd = $this->getCmd('info','distance_site1');
		if (is_object($cmd)) {
			$replace['#distance_site1_id#'] = $cmd->getId();
			$replace['#distance_site1#'] = $cmd->execCmd();
		}
		$cmd = $this->getCmd('info','distance_site2');
		if (is_object($cmd)) {
			$replace['#distance_site2_id#'] = $cmd->getId();
			$replace['#distance_site2#'] = $cmd->execCmd();
		}



		if ($panel == true) {
			$template = 'volvocars_panel';
		}
		return $this->postToHtml($_version, template_replace($replace, getTemplate('core',$_version,$template, 'volvocars')));
	}

	public function getPosition(){
		$position = array('lat' => '0.000000', 'long' => '0.000000');
		$cmd = $this->getCmd('info','position');
		if ( is_object($cmd)) {
			$coordinate = explode(',',$cmd->execCmd());
			$position['lat'] = $coordinate[0];
			$position['long'] = $coordinate[1];
		}
		return $position;
	}

	/*     * **********************Getteur Setteur*************************** */
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
	/*     * *************************Attributs****************************** */

	/*
	public static $_widgetPossibility = array();
	*/

	/*     * ***********************Methode static*************************** */


	/*     * *********************Methode d'instance************************* */

	/*
	* Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
	public function dontRemoveCmd() {
		return true;
	}
	*/

	public function preSave(){
		switch ($this->getLogicalId()) {
			case 'distance_site1':
			case 'distance_site2':
				$locationCmd = $this->getEqLogic()->getCmd('info','position');
				if (is_object($locationCmd)) {
					$this->setValue("#" . $locationCmd->getId() . '#');
				}
				break;
			case 'presence_site1':
				$distanceCmd = $this->getEqLogic()->getCmd('info','distance_site1');
				if (is_object($distanceCmd)) {
					$this->setValue("#" . $distanceCmd->getId() . '#');
				}
				break;
			case 'presence_site2':
				$distanceCmd = $this->getEqLogic()->getCmd('info','distance_site2');
				if (is_object($distanceCmd)) {
					$this->setValue("#" . $distanceCmd->getId() . '#');
				}
				break;
			case 'al_electricAutonomy':
				$autonomyCmd = $this->getEqLogic()->getCmd('info','electricAutonomy');
				if (is_object($autonomyCmd)) {
					$this->setValue("#" . $autonomyCmd->getId() . '#');
				}
				break;
			case 'al_heatAutonomy':
				$autonomyCmd = $this->getEqLogic()->getCmd('info','heatAutonomy');
				if (is_object($autonomyCmd)) {
					$this->setValue("#" . $autonomyCmd->getId() . '#');
				}
				break;
			case 'al_tyre':
				$values = '';
				foreach (['tyre_fl', 'tyre_fr', 'tyre_rl', 'tyre_rr'] as $tyre) {
					$tyreCmd = $this->getEqLogic()->getCmd('info', $tyre);
					if (is_object($tyreCmd)) {
						$values .= "#".$tyreCmd->getId()."#";
					}
				}
				$this->setValue($values);
				break;
			case 'al_light':
				$values = '';
				foreach ([
					'al_brakeLight_c',
					'al_brakeLight_l',
					'al_brakeLight_r',
					'al_daytimeRunningLight_l',
					'al_daytimeRunningLight_r',
					'al_fogLight_f',
					'al_fogLight_r',
					'al_hazardLights',
					'al_highBeam_l',
					'al_highBeam_r',
					'al_lowBeam_l',
					'al_lowBeam_r',
					'al_positionLight_fl',
					'al_positionLight_fr',
					'al_positionLight_rl',
					'al_positionLight_rr',
					'al_registrationPlateLight',
					'al_reverseLights',
					'al_sideMarkLights',
					'al_turnIndication_fl',
					'al_turnIndication_fr',
					'al_turnIndication_rl',
					'al_turnIndication_rr'
				] as $light) {
					$lightCmd = $this->getEqLogic()->getCmd('info', $light);
					if (is_object($lightCmd)) {
						$values .= "#".$lightCmd->getId()."#";
					}
				}
				$this->setValue($values);
				break;
		}
	}

	public function postInsert() {
		switch ($this->getLogicalId()) {
			case 'position':
				foreach (['distance_site1', 'distance_site2'] as $logicalId) {
					$cmd = $this->getEqLogic()->getCmd('info',$logicalId);
					if (is_object($cmd)) {
						$cmd->save();
					}
				}
				break;
			case 'distance_site1':
				$cmd = $this->getEqLogic()->getCmd('info','presence_site1');
				if (is_object($cmd)) {
					$cmd->save();
				}
				break;
			case 'distance_site2':
				$cmd = $this->getEqLogic()->getCmd('info','presence_site2');
				if (is_object($cmd)) {
					$cmd->save();
				}
				break;
			case 'electricAutonomy':
				$cmd = $this->getEqLogic()->getCmd('info','al_electricAutonomy');
				if (is_object($cmd)) {
					$cmd->save();
				}
				break;
			case 'heatAutonomy':
				$cmd = $this->getEqLogic()->getCmd('info','al_heatAutonomy');
				if (is_object($cmd)) {
					$cmd->save();
				}
				break;
			case 'tyre_fl':
			case 'tyre_fr':
			case 'tyre_rl':
			case 'tyre_rr':
				$cmd = $this->getEqLogic()->getCmd('info','al_tyre');
				if (is_object($cmd)) {
					$cmd->save();
				}
				break;
			case 'al_brakeLight_c':
			case 'al_brakeLight_l':
			case 'al_brakeLight_r':
			case 'al_daytimeRunningLight_l':
			case 'al_daytimeRunningLight_r':
			case 'al_fogLight_f':
			case 'al_fogLight_r':
			case 'al_hazardLights':
			case 'al_highBeam_l':
			case 'al_highBeam_r':
			case 'al_lowBeam_l':
			case 'al_lowBeam_r':
			case 'al_positionLight_fl':
			case 'al_positionLight_fr':
			case 'al_positionLight_rl':
			case 'al_positionLight_rr':
			case 'al_registrationPlateLight':
			case 'al_reverseLights':
			case 'al_sideMarkLights':
			case 'al_turnIndication_fl':
			case 'al_turnIndication_fr':
			case 'al_turnIndication_rl':
			case 'al_turnIndication_rr':
				$cmd = $this->getEqLogic()->getCmd('info','al_light');
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
		switch ($logicalId) {
			case 'distance_site1':
			case 'distance_site2':
				switch($logicalId) {
					case 'distance_site1':
						if ($car->getConfiguration('site1_active') != 1) {
							return '-1';
						}
						switch ($car->getConfiguration('site1_source')) {
							case 'jeedom':
								$siteLat = config::byKey('info::latitude','core',0);
								$siteLong = config::byKey('info::longitude','core',0);
								break;
							case 'manual':
							case 'vehicle':
								$siteLat = $car->getConfiguration('site1_lat');
								$siteLong = $car->getConfiguration('site1_long');
								break;
							default:
								$siteLat = 0;
								$siteLong = 0;
						}
						if ($siteLat == 0 && $siteLong == 0) {
							log::add("volvocars","warning",__("Les coordonées GPS du site 1 ne sont pas définies",__FILE__));
							return '-1';
						}
						break;
					case 'distance_site2':
						if ($car->getConfiguration('site2_active') != 1) {
							return '-1';
						}
						switch ($car->getConfiguration('site2_source')) {
							case 'jeedom':
								$siteLat = config::byKey('info::latitude','core',0);
								$siteLong = config::byKey('info::longitude','core',0);
								break;
							case 'manual':
							case 'vehicle':
								$siteLat = $car->getConfiguration('site2_lat');
								$siteLong = $car->getConfiguration('site2_long');
								break;
							default:
								$siteLat = 0;
								$siteLong = 0;
						}
						if ($siteLat == 0 && $siteLong == 0) {
							log::add("volvocars","warning",__("Les coordonées GPS du site 2 ne sont pas définies",__FILE__));
							return '-1';
						}
						break;
				}
				$position = $car->getPosition();
				if ($position['lat'] == 0 && $position['long'] == 0) {
					log::add("volvocars","warning",__("Les coordonées GPS de la position du véhicule ne sont pas définies",__FILE__));
					return '-1';
				}
				$earth_radius = 6371;
				log::add("volvocars","info","siteLat:  ". $siteLat);
				log::add("volvocars","info","siteLong: ". $siteLong);
				log::add("volvocars","info","PosLat:   ". $position['lat']);
				log::add("volvocars","info","PosLong:  ". $position['long']);

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
			case 'presence_site1':
				if ($car->getConfiguration('site1_active') != 1) {
					return '';
				}
				$distanceCmd = $car->getCmd('info','distance_site1');
				if (! is_object($distanceCmd)) {
					log::add("volvocars","error",__("La commande de distance pour le site 1 est introuvable",__FILE__));
					return '';
				}
				$distance = $distanceCmd->execCmd();
				if ($distance < 0) {
					log::add("volvovars","error",__("La distance du site 1 est indéterminée",__FILE___));
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
			case 'presence_site2':
				if ($car->getConfiguration('site2_active') != 1) {
					return '';
				}
				$distanceCmd = $car->getCmd('info','distance_site2');
				if (! is_object($distanceCmd)) {
					log::add("volvocars","error",__("La commande de distance pour le site 2 est introuvable",__FILE__));
					return '';
				}
				$distance = $distanceCmd->execCmd();
				if ($distance < 0) {
					log::add("volvovars","error",__("La distance du site 2 est indéterminée",__FILE___));
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
			case 'al_heatAutonomy':
				if ($car->getConfiguration('heatEngine') != 1) {
					return 0;
				}
				$limit = $car->getConfiguration('heatAutonomyLimit');
				$autonomyCmd = $car->getCmd('info','heatAutonomy');
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
				foreach (['tyre_fl', 'tyre_fr', 'tyre_rl', 'tyre_rr'] as $tyre) {
					$tyreCmd = $this->getEqLogic()->getCmd('info', $tyre);
					if (is_object($tyreCmd)) {
						$tyreValue = $tyreCmd->execCmd();
						$value = $tyreValue > $value ? $tyreValue : $value;
					}
				}
				return $value;
				break;
			case 'al_light':
				$value = 0;
				foreach ([
					'al_brakeLight_c',
					'al_brakeLight_l',
					'al_brakeLight_r',
					'al_daytimeRunningLight_l',
					'al_daytimeRunningLight_r',
					'al_fogLight_f',
					'al_fogLight_r',
					'al_hazardLights',
					'al_highBeam_l',
					'al_highBeam_r',
					'al_lowBeam_l',
					'al_lowBeam_r',
					'al_positionLight_fl',
					'al_positionLight_fr',
					'al_positionLight_rl',
					'al_positionLight_rr',
					'al_registrationPlateLight',
					'al_reverseLights',
					'al_sideMarkLights',
					'al_turnIndication_fl',
					'al_turnIndication_fr',
					'al_turnIndication_rl',
					'al_turnIndication_rr'
				] as $light) {
					$lightCmd = $this->getEqLogic()->getCmd('info', $light);
					if (is_object($lightCmd)) {
						if ($lightCmd->execCmd() == 1) {
							$value = 1;
							break;
						}
					}
				}
				return $value;
				break;
			case 'lock':
			case 'lock-reduced':
			case 'unlock':
			case 'clim_start':
			case 'clim_stop':
				$car->getAccount()->sendCommand($this->getLogicalId(),$car->getVin());
				break;
			default:
				log::add("volvocars","error",sprintf(__('Exécution de la commande "%s" non définie',__FILE__),$this->getLogicalId()));
				return false;
		}
	}

	/*     * **********************Getteur Setteur*************************** */

}
