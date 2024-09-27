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

class volvoApiException extends Exception {

	protected $endpoint = null;
	protected $httpCode = null;
	protected $msg = null;
	protected $description = null;
	protected $detail = null;

	public function __construct ($endpoint, $httpCode, $msg, $description = null, $detail = null, $previous = null) {
	 	$message = "Endpoint: " . $endpoint . "\n";
	 	$message .= 'httpCode: ' . $httpCode . "\n";
	 	$message .= $msg;
	 	if ($description) {
	 		$message .= "\n\n" . $description;
	 	}
	 	if ($detail) {
	 		$message .= "\n\n" . $detail;
	 	}
		parent::__construct($message, null, $previous);
		$this->endpoint = $endpoint;
		$this->httpCode = $httpCode;
		$this->msg = $msg;
		$this->description = $description;
		$this->detail = $detail;
	}

	function getEndpoint () {
		return $this->endpoint;
	}

	function getHttpCode () {
		return $this->httpCode;
	}

	function getMsg () {
		return $this->msg;
	}

	function getDescription () {
		return $this->description;
	}

	function getDetail () {
		return $this->detail;
	}
}
