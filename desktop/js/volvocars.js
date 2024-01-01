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

volvocars = {
	accountNeedReload : false
}

/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmd").sortable({
	axis: "y",
	cursor: "move",
	items: ".cmd",
	placeholder: "ui-state-highlight",
	tolerance: "intersect",
	forcePlaceholderSize: true
})

/*
 * Edition d'un account
 */
function editAccount(id) {
	if ($('#modContainer_editAccount').length == 0) {
		$('body').append('<div id="modContainer_editAccount"></dev>')
		jQuery.ajaxSetup({async: false})
		$('#modContainer_editAccount').load('index.php?v=d&plugin=volvocars&modal=editAccount')
		jQuery.ajaxSetup({async: true})
		$('#modContainer_editAccount').dialog({
			closeText: '',
			autoOpen: false,
			modal: true,
			height: 260,
			width: 400
		})
	}
	$.ajax({
		type: 'POST',
		url: '/plugins/volvocars/core/ajax/volvocars.ajax.php',
		data: {
			action: 'getAccount',
			id: id
		},
		dataType: 'json',
		global: false,
		error: function(request, status, error) {
			handleAjaxError(request, status, error)
		},
		success: function(data) {
			if (data.state != 'ok') {
				$.fn.showAlert({message: data.result, level: 'danger'})
			}
			$('#modContainer_editAccount').setValues(json_decode(data.result),'.accountAttr')
			$('#modContainer_editAccount').dialog({
				title: '{{Compte}}: ' + name,
				close: function() {
					if (volvocars.accountNeedReload){
						location.reload()
					}
				}
			})
			$('#modContainer_editAccount').dialog('option', 'buttons', [{
				text: "{{Annuler}}",
				click: function() {
					$(this).dialog("close")
				}
			},
			{
				text: "{{Supprimer}}",
				class: "btn-delete",
				click: function() {
					$.ajax({
						type: 'POST',
						url: 'plugins/volvocars/core/ajax/volvocars.ajax.php',
						data: {
							action: 'removeAccount',
							id: id
						},
						dataType: 'json',
						global: 'false',
						error: function(request, status, error) {
							handleAjaxError(request, status, error)
						},
						success: function(data) {
							if (data.state != 'ok') {
								$.fn.showAlert({message: data.result, level: 'danger'})
								return
							}
							$('.accountDisplayCard[data-account_id=' + id + ']').remove()
							$('#sel_account option[value=' + id + ']').remove()
						}
					})
					$(this).dialog("close")
				}
			},
			{
				text: "{{Valider}}",
				click: function() {
					account = $('#modContainer_editAccount').getValues('.accountAttr')[0]
					$.ajax({
						type: 'POST',
						url: 'plugins/volvocars/core/ajax/volvocars.ajax.php',
						data: {
							action: 'saveAccount',
							account: json_encode(account)
						},
						dataType: 'json',
						global: false,
						error: function(request, status, error){
							handleAjaxError(request, status, error)
						},
						success: function(data){
							if (data.state != 'ok'){
								$.fn.showAlert({message: data.result, level: 'danger'})
								return
							}
						}
					})
					$(this).dialog("close")
				}
			}])
			$('#modContainer_editAccount').dialog('open')
		}
	})
}

/*
 * Action du bouton d'ajout d'un compte
 */
$('.accountAction[data-action=add]').off('click').on('click',function() {
	bootbox.prompt('{{Nom du compte}}', function(result) {
		if (result !== null) {
			$.ajax({
				type: 'POST',
				url: 'plugins/volvocars/core/ajax/volvocars.ajax.php',
				data: {
					action: 'createAccount',
					name: result
				},
				dataType: 'json',
				global: false,
				error: function (request, status, error) {
					handleAjaxError(request, status, error)
				},
				success: function (data) {
					if (data.state != 'ok') {
						$.fn.showAlert({message: data.result, level:'danger'})
						return
					}
					volvocars.accountNeedReload = true
					editAccount(json_decode(data.result).id)
				}
			})
		}
	})
});

/*
 * Action du bouton Synchronisation
 */
$('.accountAction[data-action=sync]').off('click').on('click',function() {
	options = []
	$('.accountDisplayCard').each(function() {
		option = {
			'value': $(this).data('account_id'),
			'text': $(this).data('account_name')
		}
		options.push(option)
	})
	bootbox.prompt({
		'title': '{{Compte à synchroniser}}',
		'inputType': 'select',
		'inputOptions': options,
		'value': options[0]['value'],
		'callback': function(accountId){
			if (accountId == null) {
				return
			}
			$.showLoading()
			$.ajax({
				type: 'POST',
				url: 'plugins/volvocars/core/ajax/volvocars.ajax.php',
				data: {
					action: 'synchronizeAccount',
					accountId: accountId
				},
				dataType: 'json',
				global: false,
				error: function (request, status, error) {
					$.hideLoading()
					handleAjaxError(request, status, error)
				},
				success: function(data) {
					if (data.state != 'ok') {
						$.hideLoading()
						$.fn.showAlert({message: data.result, level:'danger'})
						return
					}
					location.reload()
				}
			})
		}
	})
})

/*
 * Action sur AccountDisplayCard
 */
$('.eqLogicThumbnailContainer[data-type=account]').off().on('click','.accountDisplayCard', function() {
	editAccount($(this).data('account_id'))
})

/*
 * Sur modification de "moteur électrique"
 */
$('.eqLogicAttr[data-l2key=electricEngine]').off('change').on('change',function(){
	if ($(this).value() == 1){
		$('.eqLogicAttr[data-l2key=batteryCapacityKWH]').closest('div.form-group').removeClass('hidden')
	} else {
		$('.eqLogicAttr[data-l2key=batteryCapacityKWH]').closest('div.form-group').addClass('hidden')
	}
})

/*
 * Action du bouton Editer
 */
$('.eqLogicAction[data-action=edit]').off('click').on('click',function() {
	bootbox.alert({
		message: '{{Les données modifiées seront remises à jour lors de la prochaine synchronisation!}}',
		callback : function(){
			$('.eqLogicAction[data-action=edit]').addClass('hidden')
			$('.eqLogicAction[data-action=protect]').removeClass('hidden')
			$('.eqLogicAttr.sensible').removeClass('disabled')
			$('label.checkbox-inline:has(.eqLogicAttr.sensible)').removeClass('disabled')
		}
	})
})

/*
 * Action du bouton Protéger
 */
$('.eqLogicAction[data-action=protect]').off('click').on('click',function() {
	$(this).addClass('hidden')
	$('.eqLogicAction[data-action=edit]').removeClass('hidden')
	$('.eqLogicAttr.sensible').addClass('disabled')
	$('label.checkbox-inline:has(.eqLogicAttr.sensible)').addClass('disabled')
})
$('.eqLogicAction[data-action=protect]').trigger('click')

/*
 * Action suri changement moteur électrique
 */
$('[data-l2key=electricEngine]').off('change').on('change', function() {
	if ($(this).value() == 1) {
		$('#electricAutonomy').removeClass('hidden')
	} else {
		$('#electricAutonomy').addClass('hidden')
	}
})

/*
 * Action suri changement moteur thermique
 */
$('[data-l2key=heatEngine]').off('change').on('change', function() {
	if ($(this).value() == 1) {
		$('#heatAutonomy').removeClass('hidden')
	} else {
		$('#heatAutonomy').addClass('hidden')
	}
})

/*
 * function appelée lors du chargement d'un eqLogic
 */
function printEqLogic(data) {
	img = $('.eqLogicDisplayCard[data-eqLogic_id=' + data.id + '] img').attr('src')
	$('#img_car').attr('src',img)
}

/*
 * Activation / désactivation d'un site
 */
$('[data-l1key=configuration][data-l2key$=_active]').off('change').on('change', function(){
	site = $(this).data('site')
	if ($(this).value() == 1) {
		$('div.'+site+' *').removeClass('hidden')
		$('div.'+site+' select').trigger('change')
	} else {
		$('div.'+site+' *').addClass('hidden')
	}

})
$('[data-l1key=configuration][data-l2key$=_active]').trigger('change')

/*
 * Action bouton de récupération position véhicule
 */
$('.eqLogicAction[data-action=get_pos]').off('click').on('click', function() {
	site = $(this).data('site')
	id = $('.eqLogicAttr[data-l1key=id]').value()
	$.ajax({
		type: 'POST',
		url: 'plugins/volvocars/core/ajax/volvocars.ajax.php',
		data: {
			action: 'getPosition',
			id: id
		},
		dataType: 'json',
		global: false,
		error: function (request, status, error) {
			handleAjaxError(request, status, error)
		},
		success: function (data) {
			if (data.state != 'ok') {
				$.fn.showAlert({message: data.result, level:'danger'})
				return
			}
			$('[data-l2key='+site+'_lat').value(data.result.lat)
			$('[data-l2key='+site+'_long').value(data.result.long)
		}
	})
})

/*
 * Changement de source d'un site
 */
$('.eqLogicAttr[data-l2key$=_source]').off('change').on('focus', function() {
	site = $(this).data('site')
	switch ($(this).value()) {
		case 'manual':
			$(this).data('old_manual_lat', $('[data-l2key='+site+'_lat]').value())
			$(this).data('old_manual_long', $('[data-l2key='+site+'_long]').value())
			break
		case 'manual':
			$(this).data('old_vehicle_lat', $('[data-l2key='+site+'_lat]').value())
			$(this).data('old_vehicle_long', $('[data-l2key='+site+'_long]').value())
			break
	}
}).on( 'change', function() {
	site = $(this).data('site')
	switch ($(this).value()){
		case 'jeedom':
			$('input[data-l2key='+site+'_lat], input[data-l2key='+site+'_long]').addClass('hidden')
			$('.'+site+' .btn[data-action=get_pos]').addClass('hidden')
			break
		case 'vehicle':
			$('[data-l2key='+site+'_lat').value($(this).data('old_vehicle_lat'))
			$('[data-l2key='+site+'_long').value($(this).data('old_vehicle_long'))
			$('input[data-l2key='+site+'_lat], input[data-l2key='+site+'_long]').removeClass('hidden')
			$('input[data-l2key='+site+'_lat], input[data-l2key='+site+'_long]').addClass('disabled')
			$('.'+site+' .btn[data-action=get_pos]').removeClass('hidden')
			// getPosition(site)
			break
		case 'manual':
			$('[data-l2key='+site+'_lat').value($(this).data('old_manual_lat'))
			$('[data-l2key='+site+'_long').value($(this).data('old_manual_long'))
			$('input[data-l2key='+site+'_lat], input[data-l2key='+site+'_long]').removeClass('hidden')
			$('input[data-l2key='+site+'_lat], input[data-l2key='+site+'_long]').removeClass('disabled')
			$('.'+site+' .btn[data-action=get_pos]').addClass('hidden')
			break
		default:
			$('input[data-l2key='+site+'_lat], input[data-l2key='+site+'_long]').addClass('hidden')
			$('.'+site+' .btn[data-action=get_pos]').addClass('hidden')
	}
})

/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = { configuration: {} }
	}
	if (!isset(_cmd.configuration)) {
		_cmd.configuration = {}
	}
	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
	tr += '<td class="hidden-xs">'
	tr += '<span class="cmdAttr" data-l1key="id"></span>'
	tr += '</td>'
	tr += '<td>'
	tr += '<div class="input-group">'
	tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
	tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
	tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
	tr += '</div>'
	tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
	tr += '<option value="">{{Aucune}}</option>'
	tr += '</select>'
	tr += '</td>'
	tr += '<td>'
	tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
	tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
	tr += '</td>'
	tr += '<td>'
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="logicalId" placeholder="LogicalId">'
	tr += '</td>'
	tr += '<td>'
	tr += '</td>'
	tr += '<td>'
	tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
	tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
	tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
	tr += '<div style="margin-top:7px;">'
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
	tr += '</div>'
	tr += '</td>'
	tr += '<td>';
	tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
	tr += '</td>';
	tr += '<td>'
	if (is_numeric(_cmd.id)) {
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>'
	}
	tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i></td>'
	tr += '</tr>'
	$('#table_cmd tbody').append(tr)
	var tr = $('#table_cmd tbody tr').last()
	jeedom.eqLogic.buildSelectCmd({
		id: $('.eqLogicAttr[data-l1key=id]').value(),
		filter: { type: 'info' },
		error: function (error) {
			$('#div_alert').showAlert({ message: error.message, level: 'danger' })
		},
		success: function (result) {
			tr.find('.cmdAttr[data-l1key=value]').append(result)
			tr.setValues(_cmd, '.cmdAttr')
			jeedom.cmd.changeType(tr, init(_cmd.subType))
		}
	})
}
