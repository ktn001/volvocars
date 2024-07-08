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

if (!isConnect('admin')) {
        throw new Exception('{{401 - Accès non autorisé}}');
}
?>
<h4>{{Compte VolvoId}} :</h4>
<form class="form-horizontal">
	<fieldset>
		<span class="accountAttr xhidden" data-l1key='id'></span>
		<div class="form-group">
			<label class="col-sm-3 control-label">{{Nom}}:</label>
			<input type="text" class="accountAttr form-control col-sm-7" data-l1key="name"/>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label">{{Compte}}:</label>
			<input type="text" class="accountAttr form-control col-sm-7" data-l1key="login"/>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label">{{Password}}:</label>
			<div class="input-group col-sm-7" style="padding-right:0px !important; padding-left:0px !important">
				<input class="accountAttr form-control inputPassword rouddedLeft" type="text" data-l1key="password">
				<span class="input-group-btn">
					<a class="btn btn-default form-control bt_showPass roundedRight"><i class="fas fa-eye"></i></a>
				</span>
			</div>
		</div>
	</fieldset>
</form>

<script>
"use strict"

if (typeof editvolvocarsAccount === "undefined") {
	var editVolvocarsAccount = {}
	editVolvocarsAccount.init = function(account) {
		let modal = document.getElementById(volvocarsFrontEnd.mdId_editAccount)
		modal.setJeeValues(account,'.accountAttr')
	},
	editVolvocarsAccount.getAccount = function(mdId) {
		let modal = document.getElementById(volvocarsFrontEnd.mdId_editAccount)
		let account=modal.getJeeValues('.accountAttr')
		return account[0]
	},
	editVolvocarsAccount.close = function() {
		document.getElementById(volvocarsFrontEnd.mdId_editAccount)._jeeDialog.close()
	}
}
</script>

