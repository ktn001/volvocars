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
    <div class="form-group">
      <label class="col-md-4 col-sm-6 control-label">{{Commandes à créer pour les ouvrants}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Commandes à créer lors des synchronisations}}"></i></sup>
      </label>
      <div class="col-md-4 col-sm-6">
	<label>{{Ouvert}}
          <sup><i class="fas fa-question-circle tooltips" title="{{Prend la valeur 1 (true) lorsque l'ouvrant est ouvert}}"></i></sup>
	  <input type="checkbox" class="configKey" data-l1key="create_cmd_open" checked></input>
	</label>
	<label>{{Fermé}}
          <sup><i class="fas fa-question-circle tooltips" title="{{Prend la valeur 1 (true) lorsque l'ouvrant est fermé}}"></i></sup>
	  <input type="checkbox" class="configKey" data-l1key="create_cmd_closed" checked></input>
	</label>
      </div>
    </div>
  </fieldset>
</form>
