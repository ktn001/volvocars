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

require_once __DIR__  . '/volvocars.inc.php';

log::add('volvocars_oauth','debug',"Code reçus : " . print_r($_GET,true));

try {
    $state = init('state');
    if (!$state) {
        throw new Exception(sprintf(__("requête sans '%s'.",__FILE__),'state'));
    }

    $issuer = init('iss');
    if ($issuer !== 'https://auth.volvocars.com/realms/volvocars') {
        throw new Exception(sprintf(__("L'émetteur (%s) n'est pas l'émetteur attendu.",__FILE__),$issuer));
    }

    $errorDescription = init('error_description');
    if ($errorDescription) {
        throw new Exception($errorDescription);
    }

    $code = init('code');
    if (!$code) {
        throw new Exception(__("Aucun de code reçu.",__FILE__));
    }

    $cacheKey = 'volvocars::' . $state;
    if (!cache::exist($cacheKey)) {
        throw new Exception(
            sprintf(
               __("Aucune information disponible pour la demande d'authentification %s.",__FILE__),
               $state
            )
        );
    }
    $fromCache = cache::byKey($cacheKey)->getValue();
    $codeVerifier = $fromCache['codeVerifier'];
    $account = volvoAccount::byId($fromCache['accountId']);
    if (!is_object($account)) {
        throw new Exception(sprintf(__("account %s introuvable.",__FILE__),$fromCache['accountId']));
    }
    $clientId = $account->getClientId();
    $clientSecret = $account->getClientSecret();

    log::add("volvocars_oauth","debug",__("Envoi de la requête pour obtenir le token",__FILE__));
    $tokenUrl = 'https://auth.volvocars.com/realms/volvocars/protocol/openid-connect/token';
    $basicAuth = base64_encode($clientId . ':' . $clientSecret);
    $headers = [
        'content-type: application/x-www-form-urlencoded',
        'authorization: Basic ' . $basicAuth,
        'accept: application/json'
    ];
    $postData = [
        'grant_type'    => 'authorization_code',
        'code'          => $code,
        'redirect_uri'  => $account->getRedirectUri(),
        'code_verifier' => $codeVerifier,
    ];
    log::add("volvocars_oauth","debug","DATA: " . print_r($postData,true));

    $ch = curl_init($tokenUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

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
            $txt = ($response['error_description'] || '') ?: print_r($response,true);
        }
        throw new Exception (sprintf(__("Erreur API Volvo (HTTP %s) %s",__FILE__), $httpCode, $txt));
    }
        
    $tokens = json_decode($response, true);
    log::add('volvocars_oauth','debug',print_r($tokens,true));
    
    if (!isset($tokens['access_token'])) {
        throw new Exception (__("access_token introuvable dans la réponse"));
    }
    $accessToken      = $tokens['access_token'];
    $refreshToken     = $tokens['refresh_token'] ?? null;
    $expiresIn        = $tokens['expires_in'] ?? 0;
    $refreshExpiresIn = $tokens['refresh_expires_in'] ?? 0;

    $token = new volvoToken($account->getId());
    $token->set($accessToken, $refreshToken, $expiresIn, $refreshExpiresIn);
    $token->save();
    ?>
    <script>
    window.close()
    </script>
    <?php
}
catch (Exception $e) {
    log::add('volvocars_oauth','error','OAuth: ' . $e->getMessage());

}
