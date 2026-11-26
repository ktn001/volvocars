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

class endpoint {

    //=====================================================================
    //============================= ATTRIBUTS =============================
    //=====================================================================
    private static $endpointsConfig = null;

    /*
     * Met en cache les données de configuration des endpoint si pas déjàs
     * fait. Puis retourne les données d'un endpoint ou de tous
     */
    private static function getEndpointConfig ($endpointId = null) {
        if (self::$endpointsConfig == null) {
            $endpointsFileName = __DIR__ . "/../config/endpoints.json";
            self::$endpointsConfig = json_decode(file_get_contents($endpointsFileName),true);
        }
        if ($endpointId) {
            return self::$endpointsConfig[$endpointId] ?? null;
        }
        return self::$endpointsConfig;
    }

    /*
     * Retourne la liste des ids des endpoints d'un certain type
     */
    public static function byType ($type) {
        $endpointIds = [];
        foreach (self::getEndpointConfig() as $id => $config) {
            if ($type === $config['type'] ?? '') {
                $endpointIds[] = $id;
            }
        }
        return $endpointIds;
    }

    /*
     * Retourne la liste des logicalId des commandes dépendantes d'un endpoint
     * et éventuellement d'une seule des infos de l'endpoint
     */
    public static function getLogicalIds($endpointId, $info = null) {
        $endpointConfig = self::getEndpointConfig($endpointId);
        if ($info) {
            $logicalIds = $endpointConfig['cmds'][$info] ?? [];
            if (is_array($logicalIds)) {
                return $logicalIds;
            }
            return [$logicalIds];
        }
        $logicalIds = [];
        foreach ($endpointConfig['cmds'] ?? [] as $logicalId) {
            if (is_array($logicalId)) {
                $logicalIds = array_merge($logicalIds,$logicalId);
            } else {
                if ($logicalId) {
                    $logicalIds[] = $logicalId;
                }
            }
        }
        return array_unique($logicalIds);
    }

    /*
     * Retourne l'url d'un endpoint
     */
    public static function getUrl($endpointId, $vin = null) {
        $endpointConfig = self::getEndpointConfig($endpointId);
        $url = $endpointConfig['url'] ?? null;
        if (!$url) {
            return null;
        }
        if ($vin) {
            $url = str_replace('#VIN#', $vin, $url);
        }
        return $url;
    }

    /*
     * Retourne le délai d'attente pour un endpoint
     */
    public static function getRefreshDelay($endpointId) {
        $endpointConfig = self::getEndpointConfig($endpointId);
        return $endpointConfig['refreshDelay'] ?? null;
    }

    /*
     * Retourne les valeurs par défaut
     */
    public static function getDefaults($endpointId) {
        $endpointConfig = self::getEndpointConfig($endpointId);
        return $endpointConfig['defaults'] ?? [];
    }

    /*
     * Retourne l'API d'un endpoint
     */
    public static function getApi($endpointId) {
        $endpointConfig = self::getEndpointConfig($endpointId);
        return $endpointConfig['API'] ?? null;
    }

    /*
     * Retourne le format de retour accepté
     */
    public static function getAccept($endpointId) {
        $endpointConfig = self::getEndpointConfig($endpointId);
        return $endpointConfig['accept'] ?? null;
    }

    /*
     * Retourne la liste des scopes
     */
    public static function getScopes($endpointId = null) {
        $endpointConfig = self::getEndpointConfig($endpointId);
        if ($endpointId) {
            return $endpointConfig['scope'] ?? [];
        }
        $scopes = [];
        foreach ($endpointConfig as $config) {
            if (is_array($config['scopes'] ?? [])) {
                $scopes = array_merge($scopes,$config['scopes'] ?? []);
            } else {
                $scopes[] = $config['scopes'];
            }
        }
        return array_unique($scopes);
    }
}
