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

require_once __DIR__ . '/../php/volvocars.inc.php';

class volvoAccount {

    const TOKEN_URL = "https://volvoid.eu.volvocars.com/as/token.oauth2";
    const OAUTH_AUTH_URL = "https://volvoid.eu.volvocars.com/as/authorization.oauth2";

    private $id = -1;
    private $name = '';
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
        if ($this->getName() == '') {
            throw new Exception(__("Le nom du compte ne peut pas être vide!",__FILE__));
        }
        $acc = self::byName($this->getName());
        if (is_object($acc) and $acc->getId() != $this->getId()) {
            throw new Exception (sprintf(__('Un compte nommé %s existe déjà',__FILE__),$this->getName()));
        }
        if (!$this->getId()) {
            $this->setId(self::nextId());
        }
        $value = utils::o2a($this);
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
            throw new Exception (
                sprintf(
                    __("Le compte %s est utilisé pour le véhicule %s",__FILE__),
                    $this->name,
                    $cars[0]->getName()
                )
            );
        }
        $token = $this->getToken();
        if ($token) {
            $token->remove();
        }
        $key = 'account::' . $this->id;
        return config::remove($key,'volvocars');
    }

    /*
     * L'url de rediction pour l'authorisation OAuth
     */
    public function getRedirectUri() {
        $redirectUri = config::byKey('externalProtocol','core','https://');
        $redirectAddr = config::byKey('externalAddr');
        if ($redirectAddr == '') {
            throw new Exception(__("L'adresse externe doit être configurée dans Jeedom.",__FILE__));
        }
        $redirectUri .= $redirectAddr; # . ':' . config::byKey('externalPort','core','443');
        $redirectComplement = config::byKey('externalComplement');
        if ($redirectComplement) {
            $redirectUri .= '/' . $redirectComplement;
        }
        $redirectUri .= '/plugins/volvocars/core/php/oauth.php';
        return $redirectUri;
    }

    /*
     * L'url pour la demande OAuth
     */
    public function getAuthorizationUrl() {
        log::add("volvocars","debug",sprintf(__("Récupération de l'URL d'autorisation pour le compte '%s'",__FILE__),$this->getName()));

        $redirectUri = $this->getRedirectUri();
        log::add("volvocars","debug","redirect URL: " . $redirectUri);

        $scopes = endpoint::getScopes();
        sort ($scopes);
        $scopes = implode(' ', $scopes);
        log::add("volvocars","debug","scopes: " . $scopes);

        $codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        $state = bin2hex(random_bytes(16));
        $data = [
            'codeVerifier' => $codeVerifier,
            'accountId'    => $this->getId()
        ];
        cache::set('volvocars::' . $state, $data, 604800);

        $authUrl = "https://auth.volvocars.com/realms/volvocars/protocol/openid-connect/auth?";
        $authUrl .= http_build_query([
            'response_type'         => 'code',
            'client_id'             => config::byKey('clientId','volvocars'),
            'redirect_uri'          => $redirectUri,
            'scope'                 => $scopes,
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256'
        ]);
        #], '', '&', PHP_QUERY_RFC3986);

        return $authUrl;
    }

    /*
     * Retourne l'objet qui contient les tokens
     */
    private function getToken() {
        if (!$this->_token) {
            $this->_token = volvoToken::byId($this->getId(), false);
        }
        return $this->_token;
    }

    /*
     * retourne l'accessToken
     */
    private function accessToken() {
        $token = $this->getToken();
        if ($token) {
            return $token->accessToken();
        }
        return null;
    }

    public function session($url, $endpointId = null) {
        $session = curl_init($url);
        if ($endpointId === null) {
            $accept = "application/json";
        } else {
            $accept = endpoint::getAccept($endpointId);
        }
        curl_setopt($session, CURLOPT_HTTPHEADER, [
            "authorization: Bearer " . $this->accessToken(),
            "vcc-api-key: " . config::byKey("vcc-api-key"),
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
        foreach (endpoint::byType('info') as $endpointId) {
            $return[$endpointId] = $this->getInfos($endpointId, $vin, true)['rawData'];
        }
        return $return;
    }

    public function getInfos($endpointId, $vin=null, $force = false) {

        try {
            if (!$force and !$this->shouldRequest($endpointId, $vin)) {
                log::add("volvocars","debug","│ " . __("Pas nécessaire",__FILE__));
                return false;
            }

            $url = endpoint::getUrl($endpointId);
            if ($url === null) {
                log::add("volvocars","error",sprintf(__("URL pour le endpoint %s non définie",__FILE__),$endpoint));
                return false;
            }
            log::add("volvocars","debug","│ URL: " .$url);
            $url = str_replace('#VIN#',$vin,$url);

            $session = $this->session($url, $endpointId);

            $this->incrementEndpointCounter($endpointId, $vin);

            $response   = curl_exec($session);
            $httpCode  = curl_getinfo($session, CURLINFO_HTTP_CODE);
            $curlError = curl_error($session);

            if ($curlError) {
                throw new Exception (__("Erreur cURL : ",__FILE__) . $curlError);
            }

            if ($httpCode !== 200) {
                $response = is_json($response,$response);
                $txt = $response;
                if (is_array($response)) {
                    $txt = ($response['error_description'] ?? '') ?: print_r($response,true);
                }
                throw new Exception (sprintf(__("Erreur API Volvo (HTTP %s) %s",__FILE__), $httpCode, $txt));
            }

            $rawData = str_replace($vin,'{VIN}',$response);
            if ($endpointId == 'location') {
                $rawData = preg_replace("/\d+\.\d{4,}/","#.#####",$rawData);
            }
            
            $return = array(
                'httpCode' => curl_getinfo($session,CURLINFO_HTTP_CODE),
                'rawData' => $rawData,
            );
            $response = is_json($response,$response);
            if ( $return['httpCode'] == 200) {
                $return['status'] = 'ok';
                if (isset($response['data'])) {
                    $response = $response['data'];
                }
                if ($endpointId == 'location'){
                    if(isset($response['type'])){
                        unset ($response['type']);
                    }
                    if(isset($response['properties'])){
                        unset ($response['properties']);
                    }
                    if(isset($response['geometry'])){
                        $response['location'] = $response['geometry'];
                        unset ($response['geometry']);
                    }
                }
                if ($endpointId == 'diagnostics') {
                    if ($response['timeToService']['unit'] == 'months') {
                        $response['timeToService']['value'] *= 30;
                        $response['timeToService']['unit'] = 'days';
                    }
                }
                foreach(endpoint::getDefaults($endpointId) as $info => $defaultValue) {
                    if (!isset($response[$info])) {
                        $response[$info] = array(
                            "value" => $defaultValue,
                        );
                    }
                }
                $return['data'] = $response;
            } else {
                $return['status'] = 'ko';
                if (isset($response['error'])) {
                    if (isset($response['error']['messages'])) {
                        $return['message'] = $response['error']['message'];
                    }
                    if (isset($response['error']['description'])) {
                        $return['description'] = $response['error']['description'];
                    }
                } else {
                    if (isset($response['messages'])) {
                        $return['message'] = $response['message'];
                    }
                    if (isset($response['description'])) {
                        $return['description'] = $response['description'];
                    }
                }
            }

            return $return;
        }
        catch (Exception $e) {
            log::add('volvocars','error','[getInfos] ' . $e->getMessage());
        }
    }

    public function sendCommand($cmd, $payload = null) {
        log::add("volvocars","info",print_r($payload,true));
        if ($payload) {
            log::add('volvocars','info',sprintf(
                __("Envoi de la commande %s (%s) pour le véhicule %s (data: %s)",__FILE__),
                $cmd->getName(),
                $cmd->getLogicalId(),
                $cmd->getEqLogic()->getName(),
                $payload
            ));
        } else {
            log::add('volvocars','info',sprintf(
                __("Envoi de la commande %s (%s) pour le véhicule %s",__FILE__),
                $cmd->getName(),
                $cmd->getLogicalId(),
                $cmd->getEqLogic()->getName()
            ));
        }
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
        if ($payload) {
            curl_setopt($session,CURLOPT_POSTFIELDS, $payload);
        }
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

    public function shouldRequest($endpointId, $vin) {
        $lastAccess = $this->getCache('lastEndpointAccess');
        if (!is_array($lastAccess)) {
            $lastAccess = array();
        }
        if (!isset($lastAccess[$endpointId]) or !is_array($lastAccess[$endpointId])) {
            $lastAccess[$endpointId] = array();
        }
        if (!isset($lastAccess[$endpointId][$vin])) {
            $lastAccess[$endpointId][$vin] = 0;
        }
        $lastAccessTime = intval($lastAccess[$endpointId][$vin]);
        $refreshDelay = endpoint::getRefreshDelay($endpointId);
        if ($refreshDelay === null) {
            return false;
        }
        if ((intval(date('U')) - $lastAccessTime) <= (($refreshDelay * 60)-20)) {
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

    public function incrementEndpointCounter($endpointId, $vin) {
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

            if (!isset($cache['endpointAccessCount'][$endpointId])) {
                $cache['endpointAccessCount'][$endpointId] = array();
            }

            if (!isset($cache['endpointAccessCount'][$endpointId][$vin])) {
                $cache['endpointAccessCount'][$endpointId][$vin] = 1;
            } else {
                $cache['endpointAccessCount'][$endpointId][$vin]++;
            }

            if (!isset($cache['lastEndpointAccess']) or !is_array($cache['lastEndpointAccess'])) {
                $cache['lastEndpointAccess'] = array();
            }

            if (!isset($cache['lastEndpointAccess'][$endpointId]) or !is_array($cache['lastEndpointAccess'][$endpointId])) {
                $cache['lastEndpointAccess'][$endpointId] = array();
            }

            $cache['lastEndpointAccess'][$endpointId][$vin] = date('U');

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

        $endpointIds = array();
        foreach ($counter as $endpointId => $values) {
            if ($endpointId == 'countFrom') continue;
            $api = endpoint::getApi($endpointId);

            foreach ($values as $vin => $count) {
                if (!isset ($carStats[$vin])) $carStats[$vin] = array('total' => 0);
                if (!isset ($carStats[$vin][$api])) $carStats[$vin][$api] = array('total' => 0);
                $carStats['total'] += $count;
                $carStats[$vin]['total'] += $count;
                $carStats[$vin][$api]['total'] += $count;
                $carStats[$vin][$api][$endpointId] = $count;
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
    public function setId($id) {
        $this->id = $id;
        return $this;
    }
    public function getId() {
        return $this->id;
    }

    /*
     * name
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }
    public function getName() {
        return $this->name;
    }

}
