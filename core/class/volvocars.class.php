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

	private static function convertKeyword($keyword, $ignoreUnspecified = true) {
		$value = [];
		switch ($keyword){
			case 'CLOSED':
			case 'LOCKED':
				$value['c'] = 1;
				$value['o'] = 0;
				$value['s'] = 0;
				break;
			case 'AJAR':
				$value['c'] = 0;
				$value['o'] = 0;
				$value['s'] = 1;
				break;
			case 'OPEN':
			case 'UNLOCKED':
				$value['c'] = 0;
				$value['o'] = 1;
				$value['s'] = 2;
				break;
			case 'UNSPECIFIED':
				if (! $ignoreUnspecified) {
					$value['c'] = 0;
					$value['o'] = 0;
					$value['s'] = -1;
				}
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
		$car = self::byVin($this->getVin());
		if (is_object($car) and ($car->getId() != $this->getId())){
			throw new Exception (__("Il y a un autre véhicule avec ce vin!",__FILE__));
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
				$params['w'] = '384';
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

	public function retrieveInfos($createCmds=false) {
		$this->getInfosFromApi('doors',$createCmds);
		$this->getInfosFromApi('windows',$createCmds);
	}

	private function getInfosFromApi($endpoint, $createCmds){
		$account = $this->getAccount();
		$infos = $account->getInfos($endpoint,$this->getVin());
		foreach (array_keys($infos) as $key) {
			$logicalId = [];
			$name = [];
			$subType = array(
				'c' => 'binary',
				'o' => 'binary',
				's' => 'numeric',
			);
			switch ($key) {
				case 'frontLeftWindow':
					$logicalId['c'] = 'win_fl_closed';
					$logicalId['o'] = 'win_fl_open';
					$logicalId['s'] = 'win_fl_state';
					$name['c'] = __('fenêtre avant gauche fermée',__FILE__);
					$name['o'] = __('fenêtre avant gauche ouverte',__FILE__);
					$name['s'] = __('état fenêtre avant gauche',__FILE__);
					break;
				case 'frontRightWindow':
					$logicalId['c'] = 'win_fr_closed';
					$logicalId['o'] = 'win_fr_open';
					$logicalId['s'] = 'win_fr_state';
					$name['c'] = __('fenêtre avant droite fermée',__FILE__);
					$name['o'] = __('fenêtre avant droite ouverte',__FILE__);
					$name['s'] = __('état fenêtre avant droite',__FILE__);
					break;
				case 'rearLeftWindow':
					$logicalId['c'] = 'win_rl_closed';
					$logicalId['o'] = 'win_rl_open';
					$logicalId['s'] = 'win_rl_state';
					$name['c'] = __('fenêtre arrière gauche fermée',__FILE__);
					$name['o'] = __('fenêtre arrière gauche ouverte',__FILE__);
					$name['s'] = __('état fenêtre arrière gauche',__FILE__);
					break;
				case 'rearRightWindow':
					$logicalId['c'] = 'win_rr_closed';
					$logicalId['o'] = 'win_rr_open';
					$logicalId['s'] = 'win_rr_state';
					$name['c'] = __('fenêtre arrière droite fermée',__FILE__);
					$name['o'] = __('fenêtre arrière droite ouverte',__FILE__);
					$name['s'] = __('état fenêtre arrière droite',__FILE__);
					break;
				case 'sunroof':
					$logicalId['c'] = 'roof_closed';
					$logicalId['o'] = 'roof_open';
					$logicalId['s'] = 'roof_state';
					$name['c'] = __('toit fermé',__FILE__);
					$name['o'] = __('toit ouvert',__FILE__);
					$name['s'] = __('état toit',__FILE__);
					break;
				case 'centralLock':
					$logicalId['c'] = 'lock_locked';
					$logicalId['o'] = 'lock_unlocked';
					$logicalId['s'] = 'lock_state';
					$name['c'] = __('vérouillé',__FILE__);
					$name['o'] = __('dévérouillé',__FILE__);
					$name['s'] = __('état verouillage',__FILE__);
					break;
				case 'frontLeftDoor':
					$logicalId['c'] = 'door_fl_closed';
					$logicalId['o'] = 'door_fl_open';
					$logicalId['s'] = 'door_fl_state';
					$name['c'] = __('porte avant gauche fermée',__FILE__);
					$name['o'] = __('porte avant gauche ouverte',__FILE__);
					$name['s'] = __('état porte avant gauche',__FILE__);
					break;
				case 'frontRightDoor':
					$logicalId['c'] = 'door_fr_closed';
					$logicalId['o'] = 'door_fr_open';
					$logicalId['s'] = 'door_fr_state';
					$name['c'] = __('porte avant droite fermée',__FILE__);
					$name['o'] = __('porte avant droite ouverte',__FILE__);
					$name['s'] = __('état porte avant droite',__FILE__);
					break;
				case 'rearLeftDoor':
					$logicalId['c'] = 'door_rl_closed';
					$logicalId['o'] = 'door_rl_open';
					$logicalId['s'] = 'door_rl_state';
					$name['c'] = __('porte arrière gauche fermée',__FILE__);
					$name['o'] = __('porte arrière gauche ouverte',__FILE__);
					$name['s'] = __('état porte arrière gauche',__FILE__);
					break;
				case 'rearRightDoor':
					$logicalId['c'] = 'door_rr_closed';
					$logicalId['o'] = 'door_rr_open';
					$logicalId['s'] = 'door_rr_state';
					$name['c'] = __('porte arrière droite fermée',__FILE__);
					$name['o'] = __('porte arrière droite ouverte',__FILE__);
					$name['s'] = __('état porte arrière droite',__FILE__);
					break;
				case 'tailgate':
					$logicalId['c'] = 'tail_closed';
					$logicalId['o'] = 'tail_open';
					$logicalId['s'] = 'tail_state';
					$name['c'] = __('hayon fermé',__FILE__);
					$name['o'] = __('hayon ouvert',__FILE__);
					$name['s'] = __('état hayon',__FILE__);
					break;
				case 'hood':
					$logicalId['c'] = 'hood_closed';
					$logicalId['o'] = 'hood_open';
					$logicalId['s'] = 'hood_state';
					$name['c'] = __('capot fermé',__FILE__);
					$name['o'] = __('capot ouvert',__FILE__);
					$name['s'] = __('état capot',__FILE__);
					break;
				case 'tankLid':
					$logicalId['c'] = 'tank_closed';
					$logicalId['o'] = 'tank_open';
					$logicalId['s'] = 'tank_state';
					$name['c'] = __('trappe fermée',__FILE__);
					$name['o'] = __('trappe ouverte',__FILE__);
					$name['s'] = __('état trappe',__FILE__);
					break;
				default:
					log::add('volvocars','error',sprintf(__("%s.%s inconnu",__FILE__),$category, $key));
			}
			if ($createCmds) {
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
						$cmd->setSubType($subType{$i});
						$cmd->save();
					}
				}
			}
			$value = [];
			$value = self::convertKeyword($infos[$key]['value']);
			$time = date('Y-m-d H:i:s', strtotime($infos[$key]['timestamp']));
			foreach (array_keys($logicalId) as $i) {
				if (isset($value[$i]) and isset($logicalId[$i])) {
					$this->checkAndUpdateCmd($logicalId[$i],$value[$i],$time);
				}
			}
		}
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

		$replace['#vehicle_img#'] = $this->getImage();
		if ($panel == true) {
			$template = 'volvocars_panel';
		}
		return $this->postToHtml($_version, template_replace($replace, getTemplate('core',$_version,$template, 'volvocars')));
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

	// Exécution d'une commande
	public function execute($_options = array()) {
		$car = $this->getEqLogic();
		switch ($this->getLogicalId()) {
			case ('lock'):
			case ('lock-reduced'):
			case ('unlock'):
			case ('clim_start'):
			case ('clim_stop'):
				$car->getAccount()->sendCommand($this->getLogicalId(),$car->getVin());
				break;
			default:
				log::add("volvocars","error",sprintf(__('Exécution de la commande "%s" non définie',__FILE__),$this->getLogicalId()));
				return false;
		}
	}

	/*     * **********************Getteur Setteur*************************** */

}
