<?php
// vi: tabstop=4 autoindent

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

class volvoAccount {

	const ENDPOINT = [
		"availability" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/command-accessibility",
			"accept" => "application/json",
		],
		"brakes" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/brakes",
			"accept" => "application/json",
		],
		"details" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s",
			"accept" => "application/json",
		],
		"diagnostics" =>[
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/diagnostics",
			"accept" => "application/json",
		],
		"doors" =>[
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/doors",
			"accept" => "application/json",
		],
		"engine_diagnostics" =>[
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/engine",
			"accept" => "application/json",
		],
		"fuel" =>[
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/fuel",
			"accept" => "application/json",
		],
		"location" =>[
			"url" => "https://api.volvocars.com/location/v1/vehicles/%s/location",
			"accept" => "application/json",
		],
		"odometer" =>[
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/odometer",
			"accept" => "application/json",
		],
		"recharge_status" =>[
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
		],
		"statistics" =>[
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/statistics",
			"accept" => "application/json",
		],
		"tyre" =>[
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/tyres",
			"accept" => "application/json",
		],
		"vehicles" =>[
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles",
			"accept" => "application/json",
		],
		"warnings" =>[
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/warnings",
			"accept" => "application/json",
		],
		"windows" =>[
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/windows",
			"accept" => "application/json",
		],
	];

	const OAUTH_URL = "https://volvoid.eu.volvocars.com/as/token.oauth2";

	const COMMANDS_URL = "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands";


	const CAR_LOCK_URL = "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands/lock";
	const CAR_LOCK_REDUCED_URL = "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands/lock-reduced-guard";
	const CAR_UNLOCK_URL = "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands/unlock";
	const CLIMATE_START_URL = "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands/climatization-start";
	const CLIMATE_STOP_URL = "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands/climatization-stop";

	const ENGINE_STATE_URL = "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/engine-status";
	const API_BACKEND_STATUS = "https://oip-dev-bff.euwest1.production.volvo.care/api/v1/backend-status";

	private $id = -1;
	private $name = '';
	private $login = '';
	private $password = '';
	private $_token = null;

	/* *********************************************** */
	/* *************** Méthodes Static *************** */
	/* *********************************************** */

	/*
	 * Détermination d'un nouvel ID
	 */
	public static function nextId() {
		$nextId = config::byKey('nextAccountId','volvocars',1);
		config::save('nextAccountId',$nextId+1,'volvocars');
		return $nextId;
	}

	/*
	 * Création d'un account
	 */
	public static function create($name) {
		$id = self::nextId();
		$key = 'account::' . $id;
		$config = config::byKey($key, 'volvocars');
		if ($config != '') {
			throw new Exception (sprintf(__('Un compte nommé %s existe déjà',__FILE__),$name));
		}
		$account = new self();
		$account->setId($id);
		$account->setName($name);
		$account->save();
		return $account;
	}

	/*
	 * Tous les accounts
	 */
	public static function all($_onlyEnable = false) {
		$configs = config::searchKey('account::%', 'volvocars');
		$accounts = [];
		foreach ($configs as $config) {
			if ($_onlyEnable) {
				if (!isset($config['value']['isEnable']) || $config['value']['isEnable'] == 0 || $config['value']['isEnable'] == '') {
					continue;
				}
			}
			if (isset($config['value']['password'])) {
				$config['value']['password'] = utils::decrypt($config['value']['password']);
			} else {
				$config['value']['password'] = '';
			}
			$account = new self();
			utils::a2o($account,$config['value']);
			$accounts[] = $account;
		}
		return $accounts;
	}

	/*
	 * byId
	 */
	public static function byId($id) {
		$key = 'account::' . $id;
		$value = config::byKey($key, 'volvocars');
		if ($value == '') {
			return null;
		}
		$value = is_json($value,$value);
		if (isset($value['password'])) {
			$value['password'] = utils::decrypt($value['password']);
		} else {
			$value['password'] = '';
		}
		$account = new self();
		utils::a2o($account,$value);
		return $account;
	}

	/*
	 * byName
	 */
	public static function byName($name) {
		log::add("volvocars","debug","Recherche de " . $name);
		$configs = config::searchKey('account::%', 'volvocars');
		foreach ($configs as $config) {
			$value = $config['value'];
			if ($value['name'] == $name) {
				if (isset($value['password'])) {
					$value['password'] = utils::decrypt($value['password']);
				} else {
					$value['password'] = '';
				}
				$account = new self();
				utils::a2o($account,$value);
				return $account;
			}
		}
		return null;
	}

	/* *************************************************** */
	/* *************** Méthodes d'instance *************** */
	/* *************************************************** */

	/*
	 * save
	 */
	public function save() {
		$acc = self::byName($this->getName());
		if (is_object($acc) and $acc->getId() != $this->getId()) {
			throw new Exception (sprintf(__('Un compte nommé %s existe déjà',__FILE__),$this->getName()));
		}
		$value = utils::o2a($this);
		$value['password'] = utils::encrypt($value['password']);
		$value = json_encode($value);
		$key = 'account::' . $this->id;
		config::save($key, $value, 'volvocars');
	}

	/*
	 * remove
	 */
	public function remove() {
		$cars = volvocars::byAccount_id($this->getId(), false);
		if (count($cars) > 0) {
			throw new Exception (sprintf(__("Le compte %s est utilisé pour le véhicule %s",__FILE__), $this->name, $cars[0]->getName()));
		}
		$key = 'account::' . $this->id;
		return config::remove($key,'volvocars');
	}

	private function cacheKey(){
		return 'volvoAccountToken'. $this->getId();
	}

	private function login() {
		log::add("volvocars","debug",sprintf(__("Login du compte %s",__FILE__). "...", $this->getName()));
		$session = curl_init(self::OAUTH_URL);
		curl_setopt($session, CURLOPT_HTTPHEADER, [
			"authorization: Basic aDRZZjBiOlU4WWtTYlZsNnh3c2c1WVFxWmZyZ1ZtSWFEcGhPc3kxUENhVXNpY1F0bzNUUjVrd2FKc2U0QVpkZ2ZJZmNMeXc=",
			"content-type: application/x-www-form-urlencoded",
			"accept: application/json"
		]);
		$data = http_build_query([
			"username" => $this->getLogin(),
			"password" => $this->getPassword(),
			"grant_type" => "password",
			"scope" => " openid email profile care_by_volvo:financial_information:invoice:read care_by_volvo:financial_information:payment_method"
					 . " care_by_volvo:subscription:read customer:attributes customer:attributes:write order:attributes vehicle:attributes"
					 . " tsp_customer_api:all conve:brake_status conve:climatization_start_stop conve:command_accessibility conve:commands"
					 . " conve:diagnostics_engine_status conve:diagnostics_workshop conve:doors_status conve:engine_status conve:environment"
					 . " conve:fuel_status conve:honk_flash conve:lock conve:lock_status conve:navigation conve:odometer_status conve:trip_statistics"
					 . " conve:tyre_status conve:unlock conve:vehicle_relation conve:warnings conve:windows_status energy:battery_charge_level"
					 . " energy:charging_connection_status energy:charging_system_status energy:electric_range energy:estimated_charging_time"
					 . " energy:recharge_status vehicle:attributes"
		]);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_POSTFIELDS, $data);
		$content = curl_exec($session);
		$httpCode = curl_getinfo($session,CURLINFO_HTTP_CODE);
		if ( $httpCode != 200) {
			throw new Exception (sprintf(__("Erreur lors de l'authentification du compte %s. (httpcode: %s)",__FILE__), $this->getName(), $httpCode));
		}
		$this->_token = is_json($content,$content);
		$this->_token['expires_at'] = time() + $this->_token['expires_in'] - 90;
		cache::set($this->cacheKey(),json_encode($this->_token), $this->_token['expires_in'] - 10);
		log::add("volvocars","info",sprintf(__("Compte %s logué!",__FILE__),$this->getName()));
	}

	private function refreshToken() {
		log::add("volvocars","debug",sprintf(__("Rafraîchissement du token du compte %s",__FILE__). "...", $this->getName()));
		$session = curl_init(self::OAUTH_URL);
		curl_setopt($session, CURLOPT_HTTPHEADER, [
			"authorization: Basic aDRZZjBiOlU4WWtTYlZsNnh3c2c1WVFxWmZyZ1ZtSWFEcGhPc3kxUENhVXNpY1F0bzNUUjVrd2FKc2U0QVpkZ2ZJZmNMeXc=",
			"content-type: application/x-www-form-urlencoded",
			"accept: application/json"
		]);
		$data = http_build_query([
			"grant_type" => "refesh_token",
			"refresh_token" => $this->_token["refresh_token"]
		]);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_POSTFIELDS, $data);
		$content = curl_exec($session);
		$httpCode = curl_getinfo($session,CURLINFO_HTTP_CODE);
		if ( $httpCode != 200) {
			throw new Exception (sprintf(__("Erreur lors du refraîchissement du token pour le compte %s. (httpcode: %s)",__FILE__), $this->getName(), $httpCode));
		}
		$this->_token = is_json($content,$content);
		$this->_token['expires_at'] = time() + $this->_token['expires_in'] - 90;
		cache::set($this->cacheKey(),json_encode($this->_token), $this->_token['expires_in'] - 10);
		log::add("volvocars","info",sprintf(__("Token du compte %s raffraîchi!",__FILE__),$this->getName()));
	}

	public function session($url, $endpoint = null) {
		if ($this->_token == null or $this->_token['expires_at'] <= time()) {
			// Il n'y a pas de token ou il a expiré
			$this->_token = null;

			if (! cache::exist($this->cacheKey())) {
				// Il n'y a pas/plus de token en cache. Il faut se loguer pour en obtenir un
				$this->login();
			} else {
				// Il y a un token en cache, on le récupère
				$token = cache::byKey($this->cacheKey())->getValue();
				$this->_token = is_json($token,$token);
			}

			if ($this->_token['expires_at'] <= time()) {
				// Le token a expiré. Il faut le rafraîchir
				// ----------------------------------------
				try {
					$this->refreshToken();
				} catch (Exception $e) {
					log::add("volvocars","warning",$e->getMessage());
					$this->_token = $this->login();
				}
			}
		}
		$session = curl_init($url);
		if ($endpoint === null) {
			$accept = "application/json";
		} else {
			$accept = self::ENDPOINT[$endpoint]['accept'];
		}
		curl_setopt($session, CURLOPT_HTTPHEADER, [
			"authorization: Bearer " . $this->_token['access_token'],
			"vcc-api-key: f3eeea40752040b88125725896290bad",
			"accept: " . $accept,
			"content-type: application/json"
		]);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		return $session;
	}

	public function synchronize() {
		$cars_infos = $this->getInfos('vehicles');
		foreach ($cars_infos as $car_infos) {
			$vin = $car_infos['vin'];
			$car = volvocars::byVin($vin);
			if (! is_object($car)) {
				log::add("volvocars","info",sprintf(__("Créaion du véhicule '%s'",__FILE__),$vin));
				$car = new volvocars();
				$car->setEqType_name('volvocars');
				$car->setName($vin);
				$car->setAccount_id($this->getId());
				$car->setVin($vin);
				$car->save();
			}
			$car->synchronize();
		}
	}

	public function getInfos($endpoint, $vin=null) {
		if (!isset (SELF::ENDPOINT[$endpoint])) {
			log::add("volvocars","error",sprintf(__("URL pour le endpoint %s non définie",__FILE__),$endpoint));
		}
		$url = sprintf(self::ENDPOINT[$endpoint]['url'],$vin);
		log::add("volvocars","debug","│ URL: " .$url);
		$session = $this->session($url, $endpoint);
		$content = curl_exec($session);
		log::add("volvocars","debug","│ ".$content);
		$content = is_json($content,$content);
		$httpCode = curl_getinfo($session,CURLINFO_HTTP_CODE);
		if ( $httpCode != 200) {
			$lignes = array();
			$lignes[] = 'Error getting infos "' . $endpoint . '" for vin: ' . $vin;
			$lignes[] = "httpCode: " . $httpCode;
			if (isset($content['message'])) {
				$lignes[] = $content['message'];
			}
			if (isset($content['error']['message'])) {
				$lignes[] = $content['error']['message'];
			}
			if (isset($content['error']['description'])) {
				$lignes[] =  $content['error']['description'];
			}
			if (isset($content['exveErrorMsg'])) {
				$lignes[] = $content['exveErrorMsg'];
			}
			if (isset($content['exveNote'])) {
				$lignes[] = $content['exveNote'];
			}
			$prefix = '┌';
			for ($i = 0; $i < count($lignes)-1; $i++) {
				log::add("volvocars","error",$prefix . $lignes[$i]);
				$prefix = '│';
			}
			log::add("volvocars","error",'└' . end($lignes));
			throw new Exception (sprintf(__("Erreur de la récupération d'infos pour le  véhicule '%s' (http_code: %s)",__FILE__), $vin, $httpCode));
		}
		if (isset($content['data'])) {
			$content = $content['data'];
		}
		if ($endpoint == 'location'){
			if(isset($content['type'])){
				unset ($content['type']);
			}
			if(isset($content['properties'])){
				unset ($content['properties']);
			}
			if(isset($content['geometry'])){
				$content['location'] = $content['geometry'];
				unset ($content['geometry']);
			}
		}
		return $content;
	}

	public function sendCommand($command, $vin) {
		switch ($command) {
			case 'lock':
				$url = sprintf(self::CAR_LOCK_URL,$vin);
				break;
			case 'lock-reduced':
				$url = sprintf(self::CAR_LOCK_REDUCED_URL,$vin);
				break;
			case 'unlock':
				$url = sprintf(self::CAR_UNLOCK_URL,$vin);
				break;
			case 'clim_start':
				$url = sprintf(self::CLIMATE_START_URL,$vin);
				break;
			case 'clim_stop':
				$url = sprintf(self::CLIMATE_STOP_URL,$vin);
				break;
		}
		log::add("volvocars","debug",sprintf(__('Envoi de la commande %s (%s)',__FILE__),$command,$url));
		$session = $this->session($url);
		curl_setopt($session,CURLOPT_POST,1);
		$content = curl_exec($session);
		$httpCode = curl_getinfo($session,CURLINFO_HTTP_CODE);
		if ( $httpCode != 200) {
			throw new Exception (sprintf(__("Erreur de l'envoi d'une commande pour le  véhicule '%s' (http_code: %s)",__FILE__), $vin, $httpCode));
		}
	}

	/* *********************************************** */
	/* *************** Getters setters *************** */
	/* *********************************************** */

	/*
	 * id
	 */
	public function setId($_id) {
		$this->id = $_id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}

	/*
	 * name
	 */
	public function setName($_name) {
		$this->name = $_name;
		return $this;
	}
	public function getName() {
		return $this->name;
	}

	/*
	 * login
	 */
	public function setLogin($_login) {
		$this->login = $_login;
		return $this;
	}
	public function getLogin() {
		return $this->login;
	}

	/*
	 * password
	 */
	public function setPassword($_password) {
		$this->password = $_password;
		return $this;
	}
	public function getPassword() {
		return $this->password;
	}

}
