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
    <div class="col-md-6 col-sm-12">
      <legend><i class="fas fa-comments"></i> {{API}}:</legend>
      <div class="row">
        <label class="col-sm-3 col-md-5 col-lg-4 control-label">{{Clé VCC API}}
          <sup><i class="fas fa-question-circle tooltips"  title="{{voir la documentation pour la création d'une clé sur https://developer.volvocars.com}}"></i></sup>
        </label>
        <input class="col-md-4 col-sm-5 configKey blur" data-l1key="vcc-api-key"></input>
      </div>
      <legend><i class="fas fa-tachometer-alt"></i> {{Dashboard}}:</legend>
      <div class="row">
        <label class="col-sm-3 col-md-5 col-lg-4 control-label">{{Utiliser le widget du plugin}}</label>
        <input type="checkbox" class="configKey" data-l1key="use_widget_volvocars" checked></input>
      </div>
    </div>
    <div class="col-md-6 col-sm-12">
      <legend><i class="fas fa-list"></i> {{Commandes}}:</legend>
      <label class="col-sm-11 col-sm-offset-1">{{Les ouvrants}}:
        <sup><i class="fas fa-question-circle tooltips" title="{{Commandes à créer lors des synchronisations}}"></i></sup>
      </label>
      <fieldset class="col-sm-10 col-sm-offset-2">
        <label class="col-sm-3">{{Ouvert}}: 
          <sup><i class="fas fa-question-circle tooltips" title="{{Prend la valeur 1 (true) lorsque l'ouvrant est ouvert}}"></i></sup>
        </label>
	<span>
          <input type="checkbox" class="configKey" data-l1key="create_cmd_open" checked></input>
        </span>
      </fieldset>
      <fieldset class="col-sm-10 col-sm-offset-2">
        <label class="col-sm-3">{{Fermé}}
          <sup><i class="fas fa-question-circle tooltips" title="{{Prend la valeur 1 (true) lorsque l'ouvrant est fermé}}"></i></sup>
        </label>
        <input type="checkbox" class="configKey" data-l1key="create_cmd_closed" checked></input>
      </fieldset>
    </div>
  </fieldset>
</form>
