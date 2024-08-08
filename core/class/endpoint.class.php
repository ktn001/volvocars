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

class endpoint {
	private static $_endpoints = [
		"accessibility" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/command-accessibility",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 5,
			"cmds" => [
				"availabilityStatus" => ["availability", "unavailableReason" ],
			],
		],
		"brakes" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/brakes",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 60,
			"cmds" => [
				"brakeFluidLevelWarning" => "brakeFluidLevel",
			],
		],
		"commands" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 0,
		],
		"details" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 0,
		],
		"diagnostics" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/diagnostics",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 10,
			"cmds" => [
				"serviceWarning"          => "service",
				"serviceTrigger"          => "serviceTrigger",
				"engineHoursToService"    => "engineHoursToService",
				"distanceToService"       => "distanceToService",
				"timeToService"           => "timeToService",
				"washerFluidLevelWarning" => "washerFluidLevel",
			],
			"defaults" => [
				"serviceTrigger" => "&nbsp;",
			],
			"options" => [
				"timeToService" => "convertToDays",
			]
		],
		"doors" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/doors",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 2,
			"cmds" => [
				"centralLock"     => "locked",
				"frontLeftDoor"   => "doorFlState",
				"frontRightDoor"  => "doorFrState",
				"rearLeftDoor"    => "doorRlState",
				"rearRightDoor"   => "doorRrState",
				"hood"            => "hoodState",
				"tailgate"        => "tailState",
				"tankLid"         => "tankState",
			],
		],
		"engine_diagnostics" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/engine",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 15,
			"cmds" => [
				"engineCoolantLevelWarning"  => "coolantLevel",
				"oilLevelWarning"            => "oilLevel",
			],
		],
		"engine_status" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/engine-status",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 5,
			"cmds" => [
				"engineStatus" => "engineON",
			],
		],
		"fuel" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/fuel",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 30,
			"cmds" => [
				"fuelAmount" => "fuelAmount",
			],
		],
		"location" => [
			"API" => "Location",
			"url" => "https://api.volvocars.com/location/v1/vehicles/%s/location",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 1,
			"cmds" => [
				"location" => "position",
			],
		],
		"odometer" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/odometer",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 15,
			"cmds" => [
				"odometer" => "odometer",
			],
		],
		"recharge_status" => [
			"API" => "Energy",
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
			"type" => "info",
			"refreshDelai" => 5,
			"cmds" => [
				"batteryChargeLevel"        => "batteryLevel",
				"chargingSystemStatus"      => "chargingStatus",
				"estimatedChargingTime"     => "chargingRemainingTime",
				"chargingConnectionStatus"  => "connectorStatus",
			],
		],
		"statistics" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/statistics",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 10,
			"cmds" => [
				"averageEnergyConsumption"        => "consoElectric",
				"averageFuelConsumption"          => "consoFuel",
				"averageFuelConsumptionAutomatic" => "consoFuelTrip",
				"distanceToEmptyBattery"          => "electricAutonomy",
				"distanceToEmptyTank"             => "fuelAutonomy",
			],
		],
		"tyre" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/tyres",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 30,
			"cmds" => [
				"frontLeft"  => "tyreFl",
				"frontRight" => "tyreFr",
				"rearLeft"   => "tyreRl",
				"rearRight"  => "tyreRr",
			],
		],
		"vehicles" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles",
			"accept" => "application/json",
			"type" => "account_info",
			"refreshDelai" => 0,
		],
		"warnings" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/warnings",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 30,
			"cmds" => [
				"brakeLightCenterWarning"          => "al_brakeLightC",
				"brakeLightLeftWarning"            => "al_brakeLightL",
				"brakeLightRightWarning"           => "al_brakeLightR",
				"daytimeRunningLightLeftWarning"   => "al_daytimeRunningLightL",
				"daytimeRunningLightRightWarning"  => "al_daytimeRunningLightR",
				"fogLightFrontWarning"             => "al_fogLightF",
				"fogLightRearWarning"              => "al_fogLightR",
				"hazardLightsWarning"              => "al_hazardLights",
				"highBeamLeftWarning"              => "al_highBeamL",
				"highBeamRightWarning"             => "al_highBeamR",
				"lowBeamLeftWarning"               => "al_lowBeamL",
				"lowBeamRightWarning"              => "al_lowBeamR",
				"positionLightFrontLeftWarning"    => "al_positionLightFl",
				"positionLightFrontRightWarning"   => "al_positionLightFr",
				"positionLightRearLeftWarning"     => "al_positionLightRl",
				"positionLightRearRightWarning"    => "al_positionLightRr",
				"registrationPlateLightWarning"    => "al_registrationPlateLight",
				"reverseLightsWarning"             => "al_reverseLights",
				"sideMarkLightsWarning"            => "al_sideMarkLights",
				"turnIndicationFrontLeftWarning"   => "al_turnIndicationFl",
				"turnIndicationFrontRightWarning"  => "al_turnIndicationFr",
				"turnIndicationRearLeftWarning"    => "al_turnIndicationRl",
				"turnIndicationRearRightWarning"   => "al_turnIndicationRr",
			],
		],
		"windows" => [
			"API" => "Connected-vehicle",
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/windows",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 2,
			"cmds" => [
				"frontLeftWindow"   => "winFlState",
				"frontRightWindow"  => "winFrState",
				"rearLeftWindow"    => "winRlState",
				"rearRightWindow"   => "winRrState",
				"sunroof"           => "roofState",
			],
		],
	];
	private $endpoint_id;

	static function byId($_endpoint_id) {
		if (!array_key_exists($_endpoint_id,self::$_endpoints)) {
			return null;
		}
		return new endpoint($_endpoint_id);
	}

	static function getEndpoint_ids() {
		return array_keys(self::$_endpoints);
	}

	static function all($_type = null, $toRefresh = false) {
		$return = array();
		foreach (self::$_endpoints as $endpoint_id => $value) {
			if (!is_array($value)) {
				continue;
			}
			if (!isset($value['type'])) {
				continue;
			}
			if ($_type !== null and $value['type'] != $_type) {
				continue;
			}
			if ($toRefresh) {
				if (!isset($value['cmds'])) {
					continue;
				}
				if (!isset($value['refreshDelai'])) {
					continue;
				}
				if ($value['refreshDelai'] == 0) {
					continue;
				}
			}
			$return[] = self::byId($endpoint_id);
		}
		return $return;
	}

	function __construct ($endpoint_id) {
		if (array_key_exists($endpoint_id,self::$_endpoints)) {
			$this->endpoint_id = $endpoint_id;
		} else {
			$this->endpoint_id = null;
		}
	}

	function getLogicalIds($_info = null) {
		if ($this->endpoint_id === null) {
			return array();
		}
		if (!array_key_exists('cmds',self::$_endpoints[$this->endpoint_id])) {
			return array();
		}
		$infos = self::$_endpoints[$this->endpoint_id]['cmds'];
		if (count($infos) == 0) {
			return array();
		}
		$logicalIds = array();
		foreach ($infos as $info => $value) {
			if ($_info == null or $_info == $info) {
				if (is_array($value)) {
					$logicalIds = array_merge($logicalIds,$value);
				} else {
					$logicalIds[] = $value;
				}
			}
		}
		return array_unique($logicalIds);
	}

	function getRefreshDelai() {
		if ($this->endpoint_id === null) {
			return null;
		}
		if (!array_key_exists('refreshDelai',self::$_endpoints[$this->endpoint_id])) {
			return null;
		}
		return self::$_endpoints[$this->endpoint_id]['refreshDelai'];
	}

	function getId() {
		return $this->endpoint_id;
	}

	function getUrl() {
		if ($this->endpoint_id === null) {
			return null;
		}
		if (!array_key_exists('url',self::$_endpoints[$this->endpoint_id])) {
			return null;
		}
	 	return self::$_endpoints[$this->endpoint_id]['url'];
	}

	function getAccept() {
		return self::$_endpoints[$this->endpoint_id]['accept'];
	}

	function getDefaults() {
		if ($this->endpoint_id === null) {
			return array();
		}
		if (!array_key_exists('defaults',self::$_endpoints[$this->endpoint_id])) {
			return array();
		}
		return self::$_endpoints[$this->endpoint_id]['defaults'];
	}

	function getOptions() {
		if ($this->endpoint_id === null) {
			return array();
		}
		if (!array_key_exists('options',self::$_endpoints[$this->endpoint_id])) {
			return array();
		}
		return self::$_endpoints[$this->endpoint_id]['options'];
	}

	function getApi() {
		return self::$_endpoints[$this->endpoint_id]['API'];
	}

}

