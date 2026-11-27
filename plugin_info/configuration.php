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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
<form class="form-horizontal">
    <fieldset>
        <div class="col-sm-6">
            <div class="form-group">
                <legend><i class="fas fa-comments"></i> {{API}}:</legend>
                <label class="col-sm-3 control-label">{{Clé VCC API}}
                    <sup><i class="fas fa-question-circle tooltips"  title="{{voir la documentation pour la création d'une application sur https://developer.volvocars.com}}"></i></sup>
                </label>
                <div class="input-group col-sm-9" style="padding-right:0px !important; padding-left:0px !important">
                    <input class="configKey form-control inputPassword roundedleft" type="text" data-l1key="vcc-api-key"></input>
                    <span class="input-group-btn">
                        <a class="btn btn-default form-control bt_showPass roundedRight"><i class="fas fa-eye"></i></a>
                    </span>
                </div>
                <label class="col-sm-3 control-label">Client ID
                    <sup><i class="fas fa-question-circle tooltips"  title="{{voir la documentation pour la publication d'une application sur https://developer.volvocars.com}}"></i></sup>
                </label>
                <input class="configKey form-control col-sm-9" type="text" data-l1key="clientId"></input>
                <label class="col-sm-3 control-label">Client Secret
                    <sup><i class="fas fa-question-circle tooltips"  title="{{voir la documentation pour la publication d'une application sur https://developer.volvocars.com}}"></i></sup>
                </label>
                <div class="input-group col-sm-9" style="padding-right:0px !important; padding-left:0px !important">
                    <input class="configKey form-control inputPassword roundedleft" type="text" data-l1key="clientSecret"></input>
                    <span class="input-group-btn">
                        <a class="btn btn-default form-control bt_showPass roundedRight"><i class="fas fa-eye"></i></a>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-sm-6">
            <div class="form-group">
                <legend><i class="fas fa-tachometer-alt"></i> {{Dashboard}}</legend>
                <div class="row">
                  <label class="col-sm-6 control-label">{{Utiliser le widget du plugin}}</label>
                  <input type="checkbox" class="configKey" data-l1key="use_widget_volvocars" checked></input>
                </div>
            </div>
        </div>

        <div class="col-sm-6">
            <legend><i class="fas fa-list"></i> {{Commandes}}</legend>
            <label class="col-sm-11 col-sm-offset-1">{{Les ouvrants}}:
                <sup><i class="fas fa-question-circle tooltips" title="{{Commandes à créer lors des synchronisations}}"></i></sup>
            </label>
            <div class="col-sm-10 col-sm-offset-2">
                <div class="form-group">
                    <label class="col-sm-3">{{Ouvert}}: 
                        <sup><i class="fas fa-question-circle tooltips" title="{{Prend la valeur 1 (true) lorsque l'ouvrant est ouvert}}"></i></sup>
                    </label>
                    <input type="checkbox" class="configKey" data-l1key="create_cmd_open" checked></input>
                </div>
                <div class="form-group">
                    <label class="col-sm-3">{{Fermé}}
                        <sup><i class="fas fa-question-circle tooltips" title="{{Prend la valeur 1 (true) lorsque l'ouvrant est fermé}}"></i></sup>
                    </label>
                    <input type="checkbox" class="configKey" data-l1key="create_cmd_closed" checked></input>
                </div>
            </div>
        </div>
    </fieldset>
</form>
