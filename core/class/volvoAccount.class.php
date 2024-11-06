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

require_once __DIR__ . '/volvoException.class.php';
require_once __DIR__ . '/endpoint.class.php';
class volvoAccount {

	const OAUTH_URL = "https://volvoid.eu.volvocars.com/as/token.oauth2";
	const OAUTH_AUTH_URL = "https://volvoid.eu.volvocars.com/as/authorization.oauth2";
	const USER_AGENT = "vca-android/5.46.0";


	const CAR_LOCK_URL = "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands/lock";
	const CAR_LOCK_REDUCED_URL = "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands/lock-reduced-guard";
	const CAR_UNLOCK_URL = "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands/unlock";
	const CLIMATE_START_URL = "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands/climatization-start";
	const CLIMATE_STOP_URL = "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands/climatization-stop";

	private $id = -1;
	private $name = '';
	private $login = '';
	private $password = '';
	private $_token = null;
	private $_fd = null;

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

	public static function tokenFile () {
		return __DIR__ . "/../../data/tokens.json";
	}

	public static function getTokens() {
		$tokenFileName = self::tokenFile();
		if (file_exists($tokenFileName)) {
			$tokens = json_decode(file_get_contents($tokenFileName),true);
			if ($tokens === false) {
				throw new Exception(sprintf(__("Erreur lors de la lecture du fichier %s",__FILE__),$tokenFileName));
			}
		}  else {
			$tokens = array();
		}
		return $tokens;
	}

	public static function saveToken ($token, $account_id) {
		$token['expires_at'] = time() + $token['expires_in'];
		$tokens = self::getTokens();
		$tokens[$account_id] = $token;
		$tokenFileName = self::tokenFile();
		if (!file_exists(dirname($tokenFileName))) {
			mkdir (dirname($tokenFileName), 0777, true);
			chmod (dirname($tokenFileName), 0775);
		}
		file_put_contents($tokenFileName,json_encode($tokens,JSON_PRETTY_PRINT));
	}

	/* *************************************************** */
	/* *************** Méthodes d'instance *************** */
	/* *************************************************** */

	/*
	 * save
	 */
	public function save() {
		if ($this->getName() == '') {
			throw new Exception(__("Le nom du compte ne peut pas être vide!",__FILE__));
		}
		$acc = self::byName($this->getName());
		if (is_object($acc) and $acc->getId() != $this->getId()) {
			throw new Exception (sprintf(__('Un compte nommé %s existe déjà',__FILE__),$this->getName()));
		}
		$value = utils::o2a($this);
		$value['password'] = utils::encrypt($value['password']);
		$value = json_encode($value);
		$key = 'account::' . $this->id;
		config::save($key, $value, 'volvocars');
		cache::delete($this->cacheKey());
	}

	/*
	 * remove
	 */
	public function remove() {
		$cars = volvocars::byAccount_id($this->getId(), false);
		if (count($cars) > 0) {
			throw new Exception (sprintf(__("Le compte %s est utilisé pour le véhicule %s",__FILE__), $this->name, $cars[0]->getName()));
		}
		cache::delete($this->cacheKey());
		$key = 'account::' . $this->id;
		return config::remove($key,'volvocars');
	}

	private function cacheKey(){
		return 'volvoAccountToken'. $this->getId();
	}

	private function refreshToken() {
		log::add("volvocars","info",sprintf(__("Rafraîchissement du token du compte %s",__FILE__). "...", $this->getName()));

		$tokens = self::getTokens();
		if (! isset($tokens[$this->getId()])){
			throw new Exception (sprintf(__("Token pour le compte %s introuvable. Veuillez refaire une sauvegarde de ce compte",__FILE__),$this->getName()));
		}
		$token = $tokens[$this->getId()];
		
		$session = curl_init(self::OAUTH_URL);
		log::add("volvocars","debug","renewToken: " . $token["refresh_token"]);
		curl_setopt($session, CURLOPT_HTTPHEADER, [
			"authorization: Basic aDRZZjBiOlU4WWtTYlZsNnh3c2c1WVFxWmZyZ1ZtSWFEcGhPc3kxUENhVXNpY1F0bzNUUjVrd2FKc2U0QVpkZ2ZJZmNMeXc=",
			"content-type: application/x-www-form-urlencoded",
			"accept: application/json"
		]);
		$data = "grant_type" . "=" .  "refresh_token"
			  . "&refresh_token" . "=" . $token["refresh_token"];
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_POSTFIELDS, $data);
		$content = curl_exec($session);
		log::add("volvocars","debug","content: " . $content);
		$httpCode = curl_getinfo($session,CURLINFO_HTTP_CODE);
		if ( $httpCode != 200) {
			$detail = $content;
			$content = is_json($content,$content);
			$message = null;
			if (isset($content['error'])) {
				$message = $content['error'];
			}
			$description = null;
			if (isset($content['error_description'])) {
				$description = $content['error_description'];
			}
			throw new volvoApiException (self::OAUTH_URL, $httpCode, $message, $description, $detail);
		}
		$token = is_json($content,$content);
		self::saveToken($token, $this->getId());
	}

	private function getToken() {
		$tokens = self::getTokens();
		if (! isset($tokens[$this->getId()])){
			throw new Exception (sprintf(__("Token pour le compte %s introuvable. Veuillez refaire une sauvegarde de ce compte",__FILE__),$this->getName()));
		}
		$token = $tokens[$this->getId()];
		if (($token['expires_at'] - ($token['expires_in']/2)) < time()) {
			$this->refreshToken();
			return $this->getToken();
		}
		return $token;
	}

	public function getAccessToken() {
		$token = $this->getToken();
		return $token['access_token'];
	}

	public function session($url, $endpoint = null) {
		$accessToken = $this->getAccessToken();
		$session = curl_init($url);
		if ($endpoint === null) {
			$accept = "application/json";
		} else {
			$accept = $endpoint->getAccept();
		}
		$vccApiKey = config::byKey('vcc-api-key','volvocars');
		curl_setopt($session, CURLOPT_HTTPHEADER, [
			"authorization: Bearer " . $accessToken,
			"vcc-api-key: " . $vccApiKey,
			"accept: " . $accept,
			"content-type: application/json"
		]);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		return $session;
	}

	public function synchronize() {
		log::add("volvocars","info","┌" . sprintf(__("Début de la synchonisation de l'account %s",__FILE__),$this->getName()));
		log::add("volvocars","debug","├─" . __("Récupération de la liste des véhicules",__FILE__));
		$payload = $this->getInfos('vehicles');
		if (!isset($payload['status']) || $payload['status'] !== 'ok') {
			$httpCode = isset($payload['httpCode']) ? $payload['httpCode'] : '';
			$message = isset($payload['message']) ? $payload['message'] : null;
			$description = isset($payload['description']) ? $payload['description'] : null;
			$detail = isset($payload['detail']) ? $payload['detail'] : null;
			log::add("volvocars","error","└" . sprintf(__("Echec de la synchonisation de l'account %s",__FILE__),$this->getName()));
			throw new volvoApiException('vehicles',$httpCode,$message,$description,$detail);
		}
		if (!isset($payload['data'])) {
			log::add("volvocars","error","└" . __("Le payload %s n'a pas de 'data'", __FILE__));
			throw new Exception("no data");
		}
		if (!is_array($payload['data'])) {
			log::add("volvocars","warning","└" . __("Pas de véhicule trouvé",__FILE__));
			throw new Exception (__("Pas de véhicule trouvé",__FILE__));
		}
		foreach ($payload['data'] as $car_info) {
			$vin = $car_info['vin'];
			$car = volvocars::byVin($vin);
			if (! is_object($car)) {
				log::add("volvocars","info","├─" . sprintf(__("Création du véhicule '%s'",__FILE__),$vin));
				$car = new volvocars();
				$car->setEqType_name('volvocars');
				$car->setName(volvocars::anonymizedVIN($vin));
				$car->setAccount_id($this->getId());
				$car->setVin($vin);
				$car->save();
			}
			log::add("volvocars","info","├─" . sprintf(__("Début de l'actualisation du véhicule '%s'",__FILE__),$vin));
			$car->synchronize();
			log::add("volvocars","info","├─" . sprintf(__("Fin de l'actualisation du véhicule '%s'",__FILE__),$vin));
		}
		log::add("volvocars","info", "└" . sprintf(__("Fin de la synchonisation de l'account %s",__FILE__),$this->getName()));
	}

	public function getRawDatas($vin) {
		$return = array();
		foreach (endpoint::all('info') as $endpoint) {
			$return[$endpoint->getId()] = $this->getInfos($endpoint->getId(), $vin, true)['rawData'];
		}
		return $return;
	}

	public function getInfos($_endpoint_id, $vin=null, $_force = false) {
		$endpoint = endpoint::byId($_endpoint_id);
		if ($endpoint === null) {
			log::add("volvocars","error",sprintf(__("URL pour le endpoint %s non définie",__FILE__),$endpoint));
			return false;
		}
		if (!$_force and !$this->shouldRequest($_endpoint_id, $vin)) {
			log::add("volvocars","debug","│ " . __("Pas nécessaire",__FILE__));
			return false;
		}
		$url = sprintf($endpoint->getUrl(),$vin);
		log::add("volvocars","debug","│ URL: " .$url);
		try {
			$session = $this->session($url, $endpoint);
		} catch (volvocarApiException $e){
			log::add("volvocars","error",$e->getMessage());
			return array();
		}
		$this->incrementEndpointCounter($_endpoint_id, $vin);
		$content = curl_exec($session);
		$rawData = str_replace($vin,'{VIN}',$content);
		if ($_endpoint_id == 'location') {
			$rawData = preg_replace("/\d+\.\d{4,}/","#.#####",$rawData);
		}
		log::add("volvocars","debug","│ ".$content);
		$return = array(
			'httpCode' => curl_getinfo($session,CURLINFO_HTTP_CODE),
			'rawData' => $rawData,
		);
		$content = is_json($content,$content);
		if ( $return['httpCode'] == 200) {
			$return['status'] = 'ok';
			if (isset($content['data'])) {
				$content = $content['data'];
			}
			if ($_endpoint_id == 'location'){
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
			if ($_endpoint_id == 'diagnostics') {
				if ($content['timeToService']['unit'] == 'months') {
					$content['timeToService']['value'] *= 30;
					$content['timeToService']['unit'] = 'days';
				}
			}
			foreach($endpoint->getDefaults() as $info => $defaultValue) {
				if (!isset($content[$info])) {
					$content[$info] = array(
						"value" => $defaultValue,
					);
				}
			}
			$return['data'] = $content;
		} else {
			$return['status'] = 'ko';
			if (isset($content['error'])) {
				if (isset($content['error']['messages'])) {
					$return['message'] = $content['error']['message'];
				}
				if (isset($content['error']['description'])) {
					$return['description'] = $content['error']['description'];
				}
			} else {
				if (isset($content['messages'])) {
					$return['message'] = $content['message'];
				}
				if (isset($content['description'])) {
					$return['description'] = $content['description'];
				}
			}
		}

		return $return;
	}

	public function sendCommand($cmd) {
		log::add('volvocars','info',sprintf(
			__("Envoi de la commande %s (%s) pour le véhicule %s",__FILE__),
			$cmd->getName(),
			$cmd->getLogicalId(),
			$cmd->getEqLogic()->getName()
		));
		$srv = array (
			'connectedVehicle' => 'https://api.volvocars.com/connected-vehicle',
		);
		$href = $cmd->getConfiguration('href');
		if ($href === '') {
			throw new Exception (sprintf(
				__("href inconnu pour la commande '%s' (%s)",__FILE__),
				$cmd->getName(),
				$cmd->getLogicalId()
			));
		}
		$volvoApi = $cmd->getConfiguration('volvoApi');
		if ($volvoApi === '') {
			throw new Exception (sprintf(
				__("VolvoApi inconnu pour la commande '%s' (%s)",__FILE__),
				$cmd->getName(),
				$cmd->getLogicalId()
			));
			return;
		}
		$url = $srv[$volvoApi] . $href;
		$session = $this->session($url);
		curl_setopt($session,CURLOPT_POST,1);
		$content = curl_exec($session);
		log::add('volvocars','debug',$content);
		$content = is_json($content,$content);
		$httpCode = curl_getinfo($session,CURLINFO_HTTP_CODE);
		if (isset ($content['data'])) {
			$data = $content['data'];
		} elseif (isset ($content['error'])) {
			$data = $content['error'];
		} else {
			$data = $content;
		}
		$lastAnswer = array(
			 'cmd' => $cmd->getName(),
			 'date' => date('Y-m-d H:i:s')
		);
		
		foreach (['invokeStatus','message','readyToUnlock','readyToUnlockUntil','description'] as $key) {
			if (isset($data[$key])) $lastAnswer[$key] = $data[$key];
		}
		switch ($httpCode) {
			case 200:
				switch ($data['invokeStatus']) {
					case "COMPLETED":
					case "WAITING":
					case "RUNNING":
					case "REJECTED":
					case "UNKNOWN":
					case "TIMEOUT":
					case "CONNECTION_FAILURE":
					case "VEHICLE_IN_SLEEP":
					case "UNLOCK_TIME_FRAME_PASSED":
					case "UNABLE_TO_LOCK_DOOR_OPEN":
					case "EXPIRED":
					case "SENT":
					case "NOT_SUPPORTED":
					case "CAR_IN_SLEEP_MODE":
					case "DELIVERED":
					case "DELIVERY_TIMEOUT":
					case "SUCCESS":
					case "CAR_TIMEOUT":
					case "CAR_ERROR":
					case "NOT_ALLOWED_PRIVACY_ENABLED":
					case "NOT_ALLOWED_WRONG_USAGE_MODE":
					case "INVOCATION_SPECIFIC_ERROR":
						break;
					default:
						throw new Exception (sprintf(__("invokeStatus '%s' inconnu",__FILE__),$data['invokeStatus']));
				}
				break;
			case 400:
			case 401:
			case 403:
			case 404:
			case 405:
			case 409:
			case 415:
			case 422:
			case 500:
			case 503:
			case 504:
				$lastAnswer['httpCode'] = $httpCode;
			default:
				$cmd->getEqLogic()->checkAndUpdateCmd('lastAnswer',json_encode($lastAnswer));
				throw new Exception (sprintf(
					__("Erreur de l'envoi d'une commande pour le véhicule '%s' (http_code: %s): %s",__FILE__),
					$vin,
					$httpCode,
					$lastAnswer['message']
				));
		}
		$cmd->getEqLogic()->checkAndUpdateCmd('lastAnswer',json_encode($lastAnswer));
		return;
	}

	public function shouldRequest($_endpoint_id, $_vin) {
		$lastAccess = $this->getCache('lastEndpointAccess');
		if (!is_array($lastAccess)) {
			$lastAccess = array();
		}
		if (!isset($lastAccess[$_endpoint_id]) or !is_array($lastAccess[$_endpoint_id])) {
			$lastAccess[$_endpoint_id] = array();
		}
		if (!isset($lastAccess[$_endpoint_id][$_vin])) {
			$lastAccess[$_endpoint_id][$_vin] = 0;
		}
		$lastAccessTime = intval($lastAccess[$_endpoint_id][$_vin]);
		$endpoint = new endpoint($_endpoint_id);
		$refreshDelai = $endpoint->getRefreshDelai();
		if ($refreshDelai === null) {
			return false;
		}
		if ((intval(date('U')) - $lastAccessTime) <= (($refreshDelai * 60)-20)) {
			return false;
		}
		return true;
	}

	public function getFileDescriptorLock() {
		if ($this->_fd === null) {
			$fileName = jeedom::getTmpFolder() . '/volvocars_cache_account_' . $this->getId() . '.lock';
			@chmod($fileName, 0777);
			$this->_fd = fopen($fileName, 'w');
		}
		return $this->_fd;
	}

	public function incrementEndpointCounter($_endpoint_id, $_vin) {
		$fd = $this->getFileDescriptorLock();
		$waitIfLocked = true;
		if (@flock($fd, LOCK_EX, $waitIfLocked)) {
			$cache = $this->getCache();
			if (!is_array($cache)) {
				$cache = [];
			}

			if (!isset($cache['endpointAccessCount'])) {
				$cache['endpointAccessCount'] = array();
			}

			if (!isset($cache['endpointAccessCount']['countFrom']) or (gmdate('d',$cache['endpointAccessCount']['countFrom']) != gmdate('d'))) {
				$this->logStats();
				$cache['endpointAccessCount'] = array();
				$cache['endpointAccessCount']['countFrom'] = date('U');
			}

			if (!isset($cache['endpointAccessCount'][$_endpoint_id])) {
				$cache['endpointAccessCount'][$_endpoint_id] = array();
			}

			if (!isset($cache['endpointAccessCount'][$_endpoint_id][$_vin])) {
				$cache['endpointAccessCount'][$_endpoint_id][$_vin] = 1;
			} else {
				$cache['endpointAccessCount'][$_endpoint_id][$_vin]++;
			}

			if (!isset($cache['lastEndpointAccess']) or !is_array($cache['lastEndpointAccess'])) {
				$cache['lastEndpointAccess'] = array();
			}

			if (!isset($cache['lastEndpointAccess'][$_endpoint_id]) or !is_array($cache['lastEndpointAccess'][$_endpoint_id])) {
				$cache['lastEndpointAccess'][$_endpoint_id] = array();
			}

			$cache['lastEndpointAccess'][$_endpoint_id][$_vin] = date('U');

			$this->setCache($cache);
			@flock($fd, LOCK_UN);
		} else {
			log::add('volvocars','warn',__('Erreur de prise de lock',__FILE__));
		}
	}

	public function logStats() {
		$counter = $this->getCache('endpointAccessCount');
		if (!is_array($counter)) return;

		$carStats = array('total' => 0);
		$endpointStats = array();
		$apiStats = array();

		$endpoints = array();
		foreach ($counter as $endpoint_id => $values) {
			if ($endpoint_id == 'countFrom') continue;

			if (!isset ($endpoints[$endpoint_id])) $endpoints[$endpoint_id] = new endpoint($endpoint_id);
			if (!is_object ($endpoints[$endpoint_id])) continue;
			$api = $endpoints[$endpoint_id]->getApi();

			foreach ($values as $vin => $count) {
				if (!isset ($carStats[$vin])) $carStats[$vin] = array('total' => 0);
				if (!isset ($carStats[$vin][$api])) $carStats[$vin][$api] = array('total' => 0);
				$carStats['total'] += $count;
				$carStats[$vin]['total'] += $count;
				$carStats[$vin][$api]['total'] += $count;
				$carStats[$vin][$api][$endpoint_id] = $count;
			}
		}

		log::add('volvocars.stats','info',"╔════════════════ ".__("statistiques",__FILE__)." ═════════════════");
		log::add('volvocars.stats','info',"╟─".sprintf(__("Appels API depuis: %s",__FILE__),date('d-m-Y H:i:s',$counter['countFrom'])));
		log::add('volvocars.stats','info',sprintf("║  %-42s %5d", __("Account",__FILE__) . ": " .$this->getName(), $carStats['total']));
		foreach (array_keys($carStats) as $vin) {
			if ($vin == 'total') continue;
			$car = volvocars::byVin($vin);
			if (is_object($car)) {
				$carName = $car->getName();
			} else {
				$carName = $vin;
			}
			log::add('volvocars.stats','info',sprintf("║    %-37s %5d", __("Véhicule",__FILE__).": " . $carName , $carStats[$vin]['total']));
			foreach (array_keys($carStats[$vin]) as $api) {
				if ($api == 'total') continue;
				log::add('volvocars.stats','info',sprintf("║      %-30s %5d", "API: " . $api, $carStats[$vin][$api]['total']));
				foreach (array_keys($carStats[$vin][$api]) as $endpoint) {
					if ($endpoint == 'total') continue;
					log::add('volvocars.stats','info',sprintf("║        %-24s %5d", "Endpoint: " . $endpoint, $carStats[$vin][$api][$endpoint]));
				}
			}


		}
		log::add('volvocars.stats','info',"╚══════════════════════════════════════════════");
	}

	public function getCache($_key = '', $_default = '') {
		$cache = cache::byKey(__CLASS__ . $this->getId())->getValue();
		return utils::getJsonAttr($cache, $_key, $_default);
	}

	public function setCache($_key, $_value = null) {
        cache::set(__CLASS__ . $this->getId(), utils::setJsonAttr(cache::byKey(__CLASS__ . $this->getId())->getValue(), $_key, $_value));
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
