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

class volvocars extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*
	* Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
	* Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
	*/

	/*
	* Permet de crypter/décrypter automatiquement des champs de configuration du plugin
	* Exemple : "param1" & "param2" seront cryptés mais pas "param3"
	public static $_encryptConfigKey = array('param1', 'param2');
	*/

	/*     * ***********************Methode static*************************** */

	/*
	 * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
	 * lors de la création semi-automatique d'un post sur le forum community
	 public static function getConfigForCommunity() {
		return "les infos essentiel de mon plugin";
	 }
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

	public function synchronize() {
		$this->updateDetails();
		$this->retrieveInfos(true);
	}

	public function updateDetails() {
		$changed = false;
		$account = $this->getAccount();
		$details = $account->carDetails($this->getVin());
		if (! isset($details['data'])){
			throw new Exception (__("Pas de key 'data' dans les détails",__FILE__));
		}
		$details = $details['data'];
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
					break;
				case 'PETROL':
					$fuelType = __('Essence',__FILE__);
					break;
				case 'PETROL/ELECTRIC':
					$fuelType = __('Hybride',__FILE__);
					break;
				case 'ELECTRIC':
					$fuelType = __('Electricité',__FILE__);
					break;
				case 'NONE':
					$fuelType = __('Aucun',__FILE__);
					break;
				default:
					$fuelType = $details['fuelType'];
			}
			if ($fuelType != $this->getConfiguration('fuelType')){
				log::add("volvocars","info",sprintf(__("Mise à jour du carburant pour le véhicule %s",__FILE__),$this->getVin()));
				$this->setConfiguration('fuelType',$fuelType);
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

		if ($changed) {
			$this->save();
		}
	}

	public function retrieveInfos($createCmds=false) {
		$this->retrieveWindowsInfos($createCmds);
	}

	public function retrieveWindowsInfos($createCmds=false) {
		$account = $this->getAccount();
		$windowsState = $account->windowsState($this->getVin());
		if (! isset($windowsState['data'])){
			throw new Exception (__("Pas de key 'data' dans les infos des fenêtres",__FILE__));
		}
		$windowsState = $windowsState['data'];
		foreach (array_keys($windowsState) as $key) {
			$logicalId = [];
			$name = [];
			switch ($key) {
				case 'frontLeftWindow':
					$logicalId['c'] = 'win_fl_closed';
					$logicalId['s'] = 'win_fl_state';
					$name['c'] = __('fenêtre avant gauche fermée',__FILE__);
					$name['s'] = __('état fenêtre avant gauche',__FILE__);
					break;
				case 'frontRightWindow':
					$logicalId['c'] = 'win_fr_closed';
					$logicalId['s'] = 'win_fr_state';
					$name['c'] = __('fenêtre avant droite fermée',__FILE__);
					$name['s'] = __('état fenêtre avant droite',__FILE__);
					break;
				case 'rearLeftWindow':
					$logicalId['c'] = 'win_rl_closed';
					$logicalId['s'] = 'win_rl_state';
					$name['c'] = __('fenêtre arrière gauche fermée',__FILE__);
					$name['s'] = __('état fenêtre arrière gauche',__FILE__);
					break;
				case 'rearRightWindow':
					$logicalId['c'] = 'win_rr_closed';
					$logicalId['s'] = 'win_rr_state';
					$name['c'] = __('fenêtre arrière droite fermée',__FILE__);
					$name['s'] = __('état fenêtre arrière droite',__FILE__);
					break;
				case 'sunroof':
					$logicalId['c'] = 'roof_closed';
					$logicalId['s'] = 'roof_state';
					$name['c'] = __('toit fermé',__FILE__);
					$name['s'] = __('état toit',__FILE__);
					break;
			}
			foreach (['c', 's'] as $i) {
				switch ($i) {
					case 'c':
						$subType = 'binary';
						break;
					case 's':
						$subType = 'numeric';
						break;
				}
				if ($createCmds) {
					$cmd = $this->getCmd('info',$logicalId[$i]);
					if (! is_object($cmd)) {
						log::add("volvocars","info",sprintf(__("Création de la commande %s",__FILE__),$logicalId[$i]));
						$cmd = new volvocarsCmd();
						$cmd->setEqLogic_id($this->getId());
						$cmd->setLogicalId($logicalId[$i]);
						$cmd->setName($name[$i]);
						$cmd->setType('info');
						$cmd->setSubType($subType);
						$cmd->save();
					}
				}
			}
			log::add("volvocars","debug","DDD " . print_r($windowsState[$key],true));
			$value = [];
			switch ($windowsState[$key]['value']){
				case 'UNSPECIFIED':
					$value['c'] = 0;
					$value['s'] = -1;
					break;
				case 'CLOSED':
					$value['c'] = 1;
					$value['s'] = 0;
					break;
				case 'AJAR':
					$value['c'] = 0;
					$value['s'] = 1;
					break;
				case 'OPEN':
					$value['c'] = 0;
					$value['s'] = 2;
					break;
			}
			$time = date('Y-m-d H:i:s', strtotime($windowsState[$key]['timestamp']));
		}
	}

	/*
	* Permet de modifier l'affichage du widget (également utilisable par les commandes)
	public function toHtml($_version = 'dashboard') {}
	*/

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
	}

	/*     * **********************Getteur Setteur*************************** */

}
