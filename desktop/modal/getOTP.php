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

<h4>{{Authentification par courriel}}</h4>
<div id="volvocarsOTP_T1">
	{{Pour votre sécurité, nous avons envoyé un code de vérification à six chiffres à votre adresse courriel : }}
</div>
<div id="email"></div>
<div id="volvocarsOTP_T2">{{Veuillez le saisir ci-dessous pour vérifier votre identité}}</div>
<div id="volvocarsOTP_code"><input type="text" class="form-control"/></div>
<a id="resendOTP" >{{Envoyer un nouveau code}}</a>
<script>
"use strict"

if (typeof volvocarsOTP === "undefined") {
    var volvocarsOTP = {
    	init: function (auth){
			volvocarsOTP.auth = auth
			document.querySelector('#' + volvocarsFrontEnd.mdId_getOTP + ' #email').innerText = auth.devices[0].target
		},
		close: function(){
			document.getElementById(volvocarsFrontEnd.mdId_getOTP)._jeeDialog.close()
		},
		getOTP: function(){
			return document.querySelector('#volvocarsOTP_code input').value
		},
		getAuth: function(){
			return volvocarsOTP.auth
		},
		resendOTP: function(){
			domUtils.ajax({
				type: "POST",
				async: true,
				global:false,
				url: volvocarsFrontEnd.ajaxUrl,
				data: {
					action: "resendOTP",
					url: volvocarsOTP.renewLink,
				},
				dataType: "json",
				success: function(data) {
					if (data.state != 'ok') {
						jeedomUtils.showAlert({message: data.result, level: "danger"})
						return
					}
				},
			})
		}
    }
}
document.getElementById('resendOTP').onclick = volvocarsOTP.resendOTP
</script>

