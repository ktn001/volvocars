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

if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
}
?>
<div id="EditAccount">
    <h4>{{Compte VolvoId}} :</h4>
    <form class="form-horizontal">
        <fieldset>
            <span class="accountAttr hidden" data-l1key='id'></span>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Nom}}:</label>
                <input type="text" class="accountAttr form-control col-sm-7" data-l1key="name"/>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">vcc-api-key</label>
                <div class="input-group col-sm-7" style="padding-right:0px !important; padding-left:0px !important">
                    <input class="accountAttr form-control inputPassword roudedLeft" type="text" data-l1key="vccApiKey">
                    <span class="input-group-btn">
                        <a class="btn btn-default form-control bt_showPass roundedRight"><i class="fas fa-eye"></i></a>
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">Client ID</label>
                <input type="text" class="accountAttr form-control col-sm-7" data-l1key="clientId"/>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">Client secret</label>
                <div class="input-group col-sm-7" style="padding-right:0px !important; padding-left:0px !important">
                    <input class="accountAttr form-control inputPassword roudedLeft" type="text" data-l1key="clientSecret">
                    <span class="input-group-btn">
                        <a class="btn btn-default form-control bt_showPass roundedRight"><i class="fas fa-eye"></i></a>
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Statut}}</label>
                <div class="col-sm-2">
                    <span class="label" id='authStatus'></span>
                    <a class="btn btn-default btn-xs" id="bt_refreshAuthenticationInfo" title="{{Actualiser le statut}}">
                        <i class="fas fa-sync-alt"></i> {{Actualiser}}
                    </a>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label"></label>
                <div class="col-sm-9">
                    <a class="btn btn-primary btn-xs" id="bt_resetOAuthLink"><i class="fas fa-link"></i> {{Lier Jeedom et Volvocars}}</a>
                </div>
            </div>
        </fieldset>
        <div id="error_message" class="danger"></div>
    </form>
</div>
<?php
    include_file('desktop', 'editAccount', 'js', 'volvocars');
?>
