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

try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	require_once __DIR__ . '/../php/volvocars.inc.php';
	include_file('core', 'authentification', 'php');

	if (!isConnect('admin')) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

  /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
	En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
	En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s) dans un tableau en argument
  */
	ajax::init();
	$action = init('action');
	log::add("volvocars","debug","Action AJAX: " . $action);

	if ($action == 'createAccount') {
		try {
			$name = init('name');
			if ($name === 'null' or $name === '') {
				throw new Exception(__("Le nom du compte n'est pas défini",__FILE__));
			}
			log::add("volvocars", "debug", sprintf (__('Création du compte %s',__FILE__),$name));
			$account = volvoAccount::create($name);
			ajax::success(json_encode(utils::o2a($account)));
		} catch (Exception $e) {
			ajax::error(displayException($e), $e->getCode());
		}
	}

	if ($action == 'saveAccount') {
		$data = init('account');
		if ($data == ''){
			throw new Exception (__("Pas de données pour la sauvegarde du compte",__FILE__));
		}
		$data = json_decode($data,true);
		if ($data['name'] == ''){
			throw new Exception(__("Le nom de l'account à sauvegarder est indéfini",__FILE__));
		}

		log::add("volvocars","info",__("Envoi de username/password au serveur volvocars via le daemon",__FILE__));
		$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
		if ($socket === false) {
			throw new Exception ("Erreur de socket: " . socket_strerror(socket_last_error()));
		}
		$port = volvocars::getPort();
		$connection = socket_connect($socket,'127.0.0.1',$port);
		if ($connection === false) {
			throw new Exception ("Erreur de socket: " . socket_strerror(socket_last_error($socket)));
		}
		$message_to_daemon = array (
			'apikey' => jeedom::getapiKey('volvocars'),
			'action' => 'login',
            'account_id' => $data['id'],
			'login' => $data['login'],
			'password' => $data['password'],
		);
		$message_to_daemon = json_encode($message_to_daemon) . "\n";
		socket_write($socket,$message_to_daemon,strlen($message_to_daemon));

        log::add("volvocars","info",__("Lecture du résultat de l'envoi de username/password",__FILE__));
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 15, "usec" => 0));
		$message_from_daemon = chop(socket_read($socket, 8192));
        if ($message_from_daemon == false) {
            $error_code = socket_last_error($socket);
            log::add("volvocars","error","socket error_code" . $error_code);
            if ($error_code == SOCKET_EAGAIN || $error_code == SOCKET_EWOULDBLOCK) {
                $message_from_daemon = [
                    "type"    => "message",
                    "message" => __("Timeout lors de l'envoi du login/password",__FILE__)
                ];
            } else {
                $message_from_daemon = [
                    "type"    => "message",
                    "message" => __("Erreur de socket: ",__FILE__) . socket_strerror($error_code)
                ];
            }
        }
		$message_from_daemon = is_json($message_from_daemon,$message_from_daemon);
		if (isset($message_from_daemon['type']) && $message_from_daemon['type'] == 'message') {
			$level = isset($message_from_daemon['level']) ? $message_from_daemon['level'] : "error";
			$code = isset($message_from_daemon['code']) ? $message_from_daemon['code'] : "unknow";
			$msg = isset($message_from_daemon['message']) ? $message_from_daemon['message'] : "undefined message";
			log::add("volvocars",$level,$message_from_daemon['message']);
			$data = array (
				'level' => $level,
				'msg' => $msg,
			);
			ajax::error($data,$code);
		}

		$account = volvoAccount::byId($data['id']);
		if (!is_object($account)){
			throw new Exception(sprintf(__("L'account %s à sauvegarder est introuvable",__FILE__),$data['id']));
		}
		utils::a2o($account,$data);
		$account->save(true);
		ajax::success($message_from_daemon);
	}

	if ($action == 'sendOTP') {
		$account_id = init('account_id');
		$otp = init('otp');
		$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
		if ($socket === false) {
			throw new Exception ("Erreur de socket: " . socket_strerror(socket_last_error()));
		}
		$port = volvocars::getPort();
		$connection = socket_connect($socket,'127.0.0.1',$port);
		if ($connection === false) {
			throw new Exception ("Erreur de socket: " . socket_strerror(socket_last_error($socket)));
		}
		$message_to_daemon = array(
			'apikey' => jeedom::getapiKey('volvocars'),
			'action' => 'sendOTP',
			'account_id' => $account_id,
			'otp' => $otp,
		);
		$message_to_daemon = json_encode($message_to_daemon) . "\n";
		socket_write($socket,$message_to_daemon,strlen($message_to_daemon));
		$message_from_daemon = chop(socket_read($socket, 8192));
		log::add("volvocars","debug","tokens: " . print_r($message_from_daemon,true));
		$message_from_daemon=is_json($message_from_daemon,$message_from_daemon);
		if (isset($message_from_daemon['type']) && $message_from_daemon['type'] == 'message') {
			$level = isset($message_from_daemon['level']) ? $message_from_daemon['level'] : "error";
			$code = isset($message_from_daemon['code']) ? $message_from_daemon['code'] : "unknow";
			$msg = isset($message_from_daemon['message']) ? $message_from_daemon['message'] : "undefined message";
			log::add("volvocars",$level,$message_from_daemon['message']);
			$data = array (
				'level' => $level,
				'msg' => $msg,
			);
			ajax::error($data,$code);
		}
		if (isset($message_from_daemon['type']) and $message_from_daemon['type'] === 'token') {
		    volvoAccount::saveToken($message_from_daemon['token_data'], $account_id);
		}
		ajax::success();
	}

	if ($action == 'getAccount') {
		$id = init('id');
		if ($id == '') {
			throw new Exception(__("L'id du compte n'est pas défini",__FILE__));
		}
		if ($id == 0) {
			$account=[];
		} else {
			$account = volvoAccount::byId($id);
		}
		if (!is_object($account)) {
			throw new Exception(sprintf(__("Le compte %s est introuvable",__FILE__),$id));
		}
		ajax::success(json_encode(utils::o2a($account)));
	}

	if ($action == 'resendOTP') {
		$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
		if ($socket === false) {
			throw new Exception ("Erreur de socket: " . socket_strerror(socket_last_error()));
		}
		$port = volvocars::getPort();
		$connection = socket_connect($socket,'127.0.0.1',$port);
		if ($connection === false) {
			throw new Exception ("Erreur de socket: " . socket_strerror(socket_last_error($socket)));
		}
		$message = array (
			'apikey' => jeedom::getapiKey('volvocars'),
			'action' => 'resendOTP',
			'url' => init('url'),
		);
		$message = json_encode($message) . "\n";
		socket_write($socket,$message,strlen($message));
		$response = chop(socket_read($socket, 8192));
		$response=is_json($response,$response);
		log::add("volvocars","debug",print_r($response,true));
		if (isset($response['error'])) {
			if (isset($response['HttpCode'])) {
				log::add("volvocars","error","HttpCode: " . $response['HttpCode'] . " Content: " . $response['content']);
				ajax::error(sprintf(__("Erreur HTTP %s lors de la demande de renvoi du OTP",__FILE__),$response['HttpCode']));
			}
			if (isset($response['state'])) {
				log::add("volvocars","error","State: " . $response['state'] . " Content: " . $response['content']);
				ajax::error(sprintf(__("Erreur dans le flux de traitement de validation du login/password (state: %s)",__FILE__),$response['state']));
			}
			ajax::error(__("Errreur indéterminée lors de la validation du login/password",__FILE__));
		}
		ajax::success($response);
	}

	if ($action == 'getPosition') {
		$id = init('id');
		if ($id == ''){
			throw new Exception(__("l'id du véhicule n'est pas défini",__FILE__));
		}
		if (is_numeric($id)) {
			$car = volvocars::byid($id);
			if (! is_object($car)){
				throw new Exception (sprintf(__("Le véhicule %s est introuvable",__FILE__),$id));
			}
			ajax::success($car->getPosition());
		}
		if ($id == 'jeedom') {
			$position = array(
				'lat' => config::byKey('info::latitude','core','0'),
				'long' => config::byKey('info::longitude','core','0'),
			);
			ajax::success($position);
		}
	}

	if ($action == 'panelWidget'){
		$id = init('id');
		if ($id == ''){
			throw new Exception(__("l'id du véhicule n'est pas défini",__FILE__));
		}
		$car = volvocars::byid($id);
		if (! is_object($car)){
			throw new Exception (sprintf(__("Le véhicule %s est introuvable",__FILE__),$id));
		}
		$widget = $car->toHtml('panel');
		ajax::success($widget);
	}

	if ($action == 'recreateCmds'){
		$car_id = init('id');
		if ($car_id == '') {
			throw new Exception(__("L'id du véhicule n'est pas défini",__FILE__));
		}
		$car = volvocars::byId($car_id);
		if (!is_object($car)){
			throw new Exception(sprintf(__("Le véhicule %s est introuvable",__FILE__),$car_id));
		}
		$car->createMissingCmds();
		ajax::success();
	}

	if ($action == 'removeAccount') {
		$id = init('id');
		if ($id == '') {
			throw new Exception(__("l'id du compte à supprimer n'est pas défini",__FILE__));
		}
		$account = volvoAccount::byId($id);
		if (!is_object($account)) {
			throw new Exception(sprintf(__("Le compte %s est introuvable",__FILE__),$id));
		}
		if ($account->remove()) {
			ajax::success();
		}
		ajax::error(sprintf(__("La suppression de compte %s n'a pas fonctionné correctement",__FILE__),$id));
	}

	if ($action == 'getCmdsUse'){
		$return = array();
		foreach (json_decode(init('ids'),true) as $id) {
			$cmd = cmd::byId($id);
			if (is_object($cmd)){
				$return[$id] = array();
				$usages = $cmd->getUsedBy();
				foreach ( $usages as $composant => $usage ) {
					if (count($usage) == 0) {
						continue;
					}
					$return[$id][$composant] = array();
					foreach ($usage as $user) {
						$entry = [];
						$entry['id'] = $user->getId();
						if (method_exists($user, 'getHumanName')) {
							$entry['name'] = $user->getHumanName();
						} else if (method_exists($user, 'getName')) {
							$entry['name'] = $user->getName();
						}
						$return[$id][$composant][] = $entry;
					}
				}
			}
		}
		ajax::success(json_encode($return));
	}

	if ($action == 'sortCmds'){
		$car_id = init('id');
		if ($car_id == '') {
			throw new Exception(__("L'id du véhicule n'est pas défini",__FILE__));
		}
		$car = volvocars::byId($car_id);
		if (!is_object($car)){
			throw new Exception(sprintf(__("Le véhicule %s est introuvable",__FILE__),$car_id));
		}
		$car->sortCmds(true);
		ajax::success();
	}

	if ($action == 'synchronizeAccount'){
		$accountId = init('accountId');
		if ($accountId == '') {
			throw new Exception(__("l'id du compte à synchroniser n'est pas défini",__FILE__));
		}
		$account = volvoAccount::byId($accountId);
		if (!is_object($account)) {
			throw new Exception(sprintf(__("Le compte %s est introuvable",__FILE__),$accountId));
		}
		$account->synchronize();
		ajax::success();
	}

	if ($action == 'getImageUrl'){
		$car_id = init('id');
		if ($car_id == '') {
			throw new Exception(__("L'id du véhicule n'est pas défini",__FILE__));
		}
		$car = volvocars::byId($car_id);
		if (!is_object($car)){
			throw new Exception(sprintf(__("Le véhicule %s est introuvable",__FILE__),$car_id));
		}
		$url = $car->getImageUrl();
		ajax::success($url);
	}

	if ($action == 'saveImage'){
		$car_id = init('id');
		if ($car_id == '') {
			throw new Exception(__("L'id du véhicule n'est pas défini",__FILE__));
		}
		$car = volvocars::byId($car_id);
		$vin = $car->getVin();
		if (!is_object($car)){
			throw new Exception(sprintf(__("Le véhicule %s est introuvable",__FILE__),$car_id));
		}
		$image = str_replace(' ','+',init('image'));
		$content = explode(',',$image);
		$imageData = base64_decode(array_pop($content));
		$imagePath = __DIR__ . './../../data/' . $vin . '.png';
		file_put_contents($imagePath, $imageData);
		ajax::success($car->getImage());
	}

	throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
	/*     * *********Catch exeption*************** */
}
catch (Exception $e) {
	ajax::error(displayException($e), $e->getCode());
}
