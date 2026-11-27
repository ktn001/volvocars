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

class volvoToken {

    /* ********************************************** */
    /* ***************** Attributes ***************** */
    /* ********************************************** */

    const REFRESH_URL = 'https://auth.volvocars.com/realms/volvocars/protocol/openid-connect/token';
    private $id = 0;
    private $accessToken = '';
    private $refreshToken = '';
    private $lifeTime = 0;
    private $expiresAt = 0;
    private $refreshExpiresAt = 0;

    /* *********************************************** */
    /* *************** Méthodes Static *************** */
    /* *********************************************** */
    
    private static function cacheKey($id) {
        return ("volvoToken::" . $id);
    }

    public static function byId($id, $create = true) {
        if (!self::exist($id) and !$create) {
            return null;
        }
        $cache = cache::byKey(self::cacheKey($id));
        $token = new volvoToken($id);
        utils::a2o($token, $cache->getValue());
        return $token;
    }

    public static function exist($id) {
        return cache::exist(self::cacheKey($id));
    }

    public static function remove($id) {
        if (cache::exist(self::cacheKey($id))) {
            cache::delete(self::cacheKey($id));
        }
    }

    public static function refreshAll() {
        foreach (volvoAccount::all() as $account) {
            if (self::exist($account->getId())) {
                $tokens = self::byId($account->getId());
                try {
                    if ($tokens->refresh_needed()) {
                        $tokens->refresh();
                    }
                }
                catch (Exception $e) {
                    log::add('volvocars','error',"[account: {$account->getName()}]" . $e->getMessage()); 
                }
            }
        }
    }

    /* *********************************************** */
    /* ************* Méthodes d'instance ************* */
    /* *********************************************** */

    public function __construct($id) {
        $this->id = $id;
    }

    public function set($access, $refresh, $lifeTime, $refreshLifeTime) {
        $this->setAccessToken($access);
        $this->setRefreshToken($refresh);
        $this->setLifeTime($lifeTime);
        $this->setExpiresAt(time() + $lifeTime);
        $this->setRefreshExpiresAt(time() + $refreshLifeTime);
    }

    public function accessToken() {
        if ($this->refresh_needed()) {
            $this->refresh();
        }
        return $this->accessToken;
    }

    public function state() {
        $state = false;
    }

    public function refresh_needed() {
        if (($this->expiresAt - time()) > 75) {
            return false;
        }
        if (($this->refreshExpiresAt - time()) < 10) {
            throw new Exception (
                sprintf(
                    __("Le token de renouvellement %s a expiré. Veuillez renouveler l'autorisation volvocars",__FILE__),
                    $this->getId()
                )
            );
        }
        return true;
    }

    public function refresh() {
        log::add('volvocars','info',sprintf(__("Renouvellement du token %s",__FILE__),$this->getId()));
        $account = volvoAccount::byId($this->getId());
        if (!is_object($account)) {
            self::remove($this->getId());
            throw new Exception(sprintf(__("Account %s introuvable! Suppression du token %s",__FILE__,$this-getId(), $this->getId())));
        }
        $basicAuth = base64_encode(config::byKey('clientId','volvocars') . ':' . config::byKey('clientSecret','volvocars'));
        $headers = [
            'content-type: application/x-www-form-urlencoded',
            'authorization: Basic ' . $basicAuth,
        ];
        $data = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->refreshToken,
        ];
        $ch = curl_init(self::REFRESH_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        try {
            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

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
            $tokens = json_decode($response,true);
            log::add('volvocars','debug',print_r($tokens,true));

            if (!isset($tokens['access_token'])) {
                throw new Exception (__("access_token introuvable dans la réponse"));
            }
            $accessToken      = $tokens['access_token'];
            $refreshToken     = $tokens['refresh_token'] ?? '';
            $expiresIn        = $tokens['expires_in'] ?? 0;
            $refreshExpiresIn = $tokens['refresh_expires_in'] ?? 0;

            $token = volvoToken::byId($account->getId());
            $token->set($accessToken, $refreshToken, $expiresIn, $refreshExpiresIn);
            $token->save();
        }
        catch (Exception $e) {
           log::add('volvocars','error','[refreshToken] ' . $e->getMessage());
        }
    }

    public function save() {
        $tokenArray = utils::o2a($this);
        cache::set(self::cacheKey($this->id), $tokenArray);
    }


    /* *********************************************** */
    /* *************** getter / setter *************** */
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
     * accessToken
     */
    public function setAccessToken($accessToken) {
        $this->accessToken = $accessToken;
        return $this;
    }
    public function getAccessToken() {
        return $this->accessToken;
    }

    /*
     * refreshToken
     */
    public function setRefreshToken($refreshToken) {
        $this->refreshToken = $refreshToken;
        return $this;
    }
    public function getRefreshToken() {
        return $this->refreshToken;
    }

    /*
     * lifeTime
     */
    public function setLifeTime($lifeTime) {
        $this->lifeTime = $lifeTime;
        return $this;
    }
    public function getLifeTime() {
        return $this->lifeTime;
    }

    /*
     * expiresAt
     */
    public function setExpiresAt($expiresAt) {
        $this->expiresAt = $expiresAt;
        return $this;
    }
    public function getExpiresAt() {
        return $this->expiresAt;
    }

    /*
     * refreshExpiresAt
     */
    public function setRefreshExpiresAt($refreshExpiresAt) {
        $this->refreshExpiresAt = $refreshExpiresAt;
        return $this;
    }
    public function getRefreshExpiresAt() {
        return $this->refreshExpiresAt;
    }

}

