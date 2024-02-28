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

class endpoints {
	private static $_endpoints = [
		"engine_diagnostics" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/engine",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 10,
		],
		"diagnostics" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/diagnostics",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 10,
		],
		"brakes" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/brakes",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 10,
		],
		"windows" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/windows",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 1,
		],
		"doors" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/doors",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 1,
		],
		"engine_status" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/engine-status",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 1,
		],
		"fuel" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/fuel",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 5,
		],
		"odometer" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/odometer",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 10,
		],
		"statistics" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/statistics",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 10,
		],
		"tyre" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/tyres",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 5,
		],
		"details" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 0,
		],
		"warnings" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/warnings",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 10,
		],
		"battery_level" => [
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status/battery-charge-level",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
			"type" => "info",
			"refreshTime" => 5,
		],
		"charging_connection_status" => [
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status/charging-connection-status",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
			"type" => "info",
			"refreshTime" => 1,
		],
		"charging_system_status" => [
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status/charging-system-status",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
			"type" => "info",
			"refreshTime" => 1,
		],
		"electric_range" => [
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status/electric-range",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
			"type" => "info",
			"refreshTime" => 5,
		],
		"charge_time" => [
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status/estimated-charging-time",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
			"type" => "info",
			"refreshTime" => 1,
		],
		"recharge_status" => [
			"url" => "https://api.volvocars.com/energy/v1/vehicles/%s/recharge-status",
			"accept" => "application/vnd.volvocars.api.energy.vehicledata.v1+json",
			"type" => "info",
			"refreshTime" => 5,
		],
		"resources" => [
			"url" => "https://api.volvocars.com/extended-vehicle/v1/vehicles/%s/resources",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 0,
		],
		"location" => [
			"url" => "https://api.volvocars.com/location/v1/vehicles/%s/location",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 1,
		],
		"accessibility" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/command-accessibility",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 1,
		],
		"commands" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles/%s/commands",
			"accept" => "application/json",
			"type" => "info",
			"refreshTime" => 0,
		],
		"vehicles" => [
			"url" => "https://api.volvocars.com/connected-vehicle/v2/vehicles",
			"accept" => "application/json",
			"type" => "account_info",
			"refreshTime" => 0,
		],
	];

	static function getEndpoint($_endpoint) {
		if (!isset (self::$_endpoints[$_endpoint])) {
			return null;
		}
		return self::$_endpoints[$_endpoint];
	}

	static function getEndpoints($_type = null) {
		$return = array();
		foreach (self::$_endpoints as $property => $value) {
			if (is_array($value) and isset($value['type'])){
				if ($_type === null or $value['type'] == $_type) {
					$return[$property] = $value;
				}
			}
		}
		return $return;
	}
}
