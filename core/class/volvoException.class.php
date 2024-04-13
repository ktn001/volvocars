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
	protected $description = null;
	protected $detail = null;

	public function __construct ($endpoint, $httpCode, $message, $description = null, $detail = null, $previous = null) {
		parent::__construct($message, null, $previous);
		$this->httpCode = $httpCode;
		$this->description = $description;
		$this->detail = $detail;
		$this->endpoint = $endpoint;
	}

	final public function getEndpoint() {
		return $this->endpoint;
	}

	final public function getHttpCode() {
		return $this->httpCode;
	}

	final public function getDescription() {
		return $this->description;
	}

	final public function getDetail() {
		return $this->detail;
	}

	public function __toString() {
		$msg = '';
		$sep = '';
		if ($this->getEndpoint() != null) {
			$mesg = "Endpoint: " . $this->getEndpoint();
			$sep = '; ';
		}
		if ($this->getHttpCode() != null) {
			$mesg = $sep . "HttpCode: " . $this->getHttpCode();
			$sep = '; ';
		}
		if ($this->getDescription() != null) {
			$mesg = $sep . "Description: " . $this->getDescription();
			$sep = '; ';
		}
		if ($this->getDetail() != null) {
			$mesg = $sep . "Detail: " . $this->getDetail();
			$sep = '; ';
		}
	}
}
