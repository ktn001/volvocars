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
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/command-accessibility",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 1,
			"cmds" => [
				"availabilityStatus" => ["availability", "unavailableReason" ],
			],
		],
		"battery_level" => [
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status/battery-charge-level",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
			"type" => "info",
			"refreshDelai" => 5,
		],
		"brakes" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/brakes",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 30,
			"cmds" => [
				"brakeFluidLevelWarning" => "brake_fluid_level",
			],
		],
		"charging_connection_status" => [
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status/charging-connection-status",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
			"type" => "info",
			"refreshDelai" => 2,
		],
		"charging_system_status" => [
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status/charging-system-status",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
			"type" => "info",
			"refreshDelai" => 2,
		],
		"charge_time" => [
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status/estimated-charging-time",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
			"type" => "info",
			"refreshDelai" => 1,
		],
		"commands" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 0,
		],
		"details" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 0,
		],
		"diagnostics" => [
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
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/doors",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 2,
			"cmds" => [
				"centralLock"     => "locked",
            	"frontLeftDoor"   => "door_fl_state",
            	"frontRightDoor"  => "door_fr_state",
            	"rearLeftDoor"    => "door_rl_state",
            	"rearRightDoor"   => "door_rr_state",
            	"hood"            => "hood_state",
            	"tailgate"        => "tail_state",
            	"tankLid"         => "tank_state",
			],
		],
		"electric_range" => [
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status/electric-range",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
			"type" => "info",
			"refreshDelai" => 5,
		],
		"engine_diagnostics" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/engine",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 15,
			"cmds" => [
				"engineCoolantLevelWarning"  => "coolant_level",
        	    "oilLevelWarning"            => "oil_level",
			],
		],
		"engine_status" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/engine-status",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 1,
			"cmds" => [
				"engineStatus" => "engineON",
			],
		],
		"fuel" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/fuel",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 30,
			"cmds" => [
				"fuelAmount" => "fuel_amount",
			],
		],
		"location" => [
			"url" => "https://api.volvocars.com/location/v1/vehicles/%s/location",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 1,
			"cmds" => [
				"geometry" => "position",
			],
		],
		"odometer" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/odometer",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 15,
			"cmds" => [
				"odometer" => "odometer",
			],
		],
		"recharge_status" => [
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
		"resources" => [
			"url" => "https://api.volvocars.com/extended-vehicle/v1/vehicles/%s/resources",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 0,
		],
		"statistics" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/statistics",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 10,
			"cmds" => [
				"averageEnergyConsumption"        => "conso_electric",
            	"averageFuelConsumption"          => "conso_fuel",
            	"averageFuelConsumptionAutomatic" => "conso_fuel_trip",
            	"distanceToEmptyBattery"          => "electricAutonomy",
            	"distanceToEmptyTank"             => "fuelAutonomy",
			],
		],
		"tyre" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/tyres",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 30,
			"cmds" => [
				"frontLeft"  => "tyre_fl",
            	"frontRight" => "tyre_fr",
            	"rearLeft"   => "tyre_rl",
            	"rearRight"  => "tyre_rr",
			],
		],
		"vehicles" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles",
			"accept" => "application/json",
			"type" => "account_info",
			"refreshDelai" => 0,
		],
		"warnings" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/warnings",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 30,
			"cmds" => [
				"brakeLightCenterWarning"          => "al_brakeLight_c",
            	"brakeLightLeftWarning"            => "al_brakeLight_l",
            	"brakeLightRightWarning"           => "al_brakeLight_r",
            	"daytimeRunningLightLeftWarning"   => "al_daytimeRunningLight_l",
            	"daytimeRunningLightRightWarning"  => "al_daytimeRunningLight_r",
            	"fogLightFrontWarning"             => "al_fogLight_f",
            	"fogLightRearWarning"              => "al_fogLight_r",
            	"hazardLightsWarning"              => "al_hazardLights",
            	"highBeamLeftWarning"              => "al_highBeam_l",
            	"highBeamRightWarning"             => "al_highBeam_r",
            	"lowBeamLeftWarning"               => "al_lowBeam_l",
            	"lowBeamRightWarning"              => "al_lowBeam_r",
            	"positionLightFrontLeftWarning"    => "al_positionLight_fl",
            	"positionLightFrontRightWarning"   => "al_positionLight_fr",
            	"positionLightRearLeftWarning"     => "al_positionLight_rl",
            	"positionLightRearRightWarning"    => "al_positionLight_rr",
            	"registrationPlateLightWarning"    => "al_registrationPlateLight",
            	"reverseLightsWarning"             => "al_reverseLights",
            	"sideMarkLightsWarning"            => "al_sideMarkLights",
            	"turnIndicationFrontLeftWarning"   => "al_turnIndication_fl",
            	"turnIndicationFrontRightWarning"  => "al_turnIndication_fr",
            	"turnIndicationRearLeftWarning"    => "al_turnIndication_rl",
            	"turnIndicationRearRightWarning"   => "al_turnIndication_rr",
			],
		],
		"windows" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/windows",
			"accept" => "application/json",
			"type" => "info",
			"refreshDelai" => 2,
			"cmds" => [
				"frontLeftWindow"   => "win_fl_state",
            	"frontRightWindow"  => "win_fr_state",
            	"rearLeftWindow"    => "win_rl_state",
            	"rearRightWindow"   => "win_rr_state",
            	"sunroof"           => "roof_state",
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
}

