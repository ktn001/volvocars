// vim: tabstop=2 autoindent expandtab
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

"use strict"

if (typeof volvocarsFrontEnd === "undefined") {
  var volvocarsFrontEnd = {
    mdId_editAccount : 'mod_editVolvocarsAccount',
    accountNeedReload : false,
    ajaxUrl : 'plugins/volvocars/core/ajax/volvocars.ajax.php',
  }

    /*
     * Initialisation après chergement de la page
     */
  volvocarsFrontEnd.init = function() {
    document.getElementById('div_pageContainer').addEventListener('click', function(event) {
      let _target = null

      if (_target = event.target.closest('.accountDisplayCard')) {
        volvocarsFrontEnd.editAccount(_target.getAttribute('data-account_id'))
        return
      }

      if (_target = event.target.closest('.accountAction[data-action=add]')) {
        volvocarsFrontEnd.createAccount()
        return
      }

      if (_target = event.target.closest('.accountAction[data-action=sync]')) {
        volvocarsFrontEnd.synchronizeAccount()
        return
      }

      if (_target = event.target.closest('.eqLogicAction[data-action=edit]')) {
        volvocarsFrontEnd.toggleEditVehicle(true)
        return
      }

      if (_target = event.target.closest('.eqLogicAction[data-action=protect]')) {
        volvocarsFrontEnd.toggleEditVehicle(false)
        return
      }

      if (_target = event.target.closest('.eqLogicAction[data-action=get_raw-datas]')) {
        volvocarsFrontEnd.showRawData()
        return
      }
    })

    document.getElementById('div_pageContainer').addEventListener('change', function(event) {
      let _target = null

      if (_target = event.target.closest('.eqLogicAttr[data-l2key=electricEngine]')) {
        console.log(_target)
        volvocarsFrontEnd.toggleElectricEngine(_target.checked)
        return
      }

      if (_target = event.target.closest('.eqLogicAttr[data-l2key=fuelEngine]')) {
        console.log(_target)
        volvocarsFrontEnd.toggleFuelEngine(_target.checked)
        return
      }
    })
  },

  /*
   * Création d'un account
   */
  volvocarsFrontEnd.createAccount = function () {
    jeeDialog.prompt({title: "{{Nom de l'account}}:"}, function(name) {
      if (name !== null) {
        domUtils.ajax({
          type: 'POST',
          async: false,
          global: false,
          url: volvocarsFrontEnd.ajaxUrl,
          data: {
            action: 'createAccount',
            name: name
          },
          dataType: 'json',
          success: function(data) {
            if (data.state != 'ok') {
              jeedomUtils.showAlert({message: data.result, level: 'danger'})
              return
            }
            let account = json_decode(data.result)
            volvocarsFrontEnd.editAccount(account.id)
          }
        })
      }
    })
  },

  /*
   * Création d'un account
   */
  volvocarsFrontEnd.createAccount = function () {
    jeeDialog.prompt({title: "{{Nom de l'account}}:"}, function(name) {
      if (name !== null) {
        domUtils.ajax({
          type: 'POST',
          async: false,
          global: false,
          url: volvocarsFrontEnd.ajaxUrl,
          data: {
            action: 'createAccount',
            name: name
          },
          dataType: 'json',
          success: function(data) {
            if (data.state != 'ok') {
              jeedomUtils.showAlert({message: data.result, level: 'danger'})
              return
            }
            let account = json_decode(data.result)
            volvocarsFrontEnd.editAccount(account.id)
          }
        })
      }
    })
  }

  /*
   * Edition d'un account
   */
  volvocarsFrontEnd.editAccount = function (id) {
    domUtils.ajax({
      type: 'POST',
      async: false,
      global: false,
      url: volvocarsFrontEnd.ajaxUrl,
      data: {
        action: 'getAccount',
        id: id
      },
      dataType: 'json',
      success: function(data) {
        if (data.state != 'ok') {
          jeedomUtils.showAlert({message: data.result, level: 'danger'})
          return
        }
        let account = json_decode(data.result)
        jeeDialog.dialog({
          id: volvocarsFrontEnd.mdId_editAccount,
          title: '{{Compte}}: ' + account.name,
          height: 260,
          width: 400,
          contentUrl: 'index.php?v=d&plugin=volvocars&modal=editAccount',
          buttons: {
            cancel: {
              callback: {
                click: function(event) {
                  let account = editVolvocarsAccount.getAccount()
                  let card = document.querySelector('.accountDisplayCard[data-account_id="' + account.id + '"]')
                  if (! card) {
                    jeedomUtils.loadPage(document.URL)
                  }
                  editVolvocarsAccount.close()
                }
              },
            },
            delete: {
              label: '<i class="fa fa-times"></i> {{Supprimer}}',
              className: 'danger',
              callback: {
                click: function(event) {
                  let account = editVolvocarsAccount.getAccount()
                  domUtils.ajax({
                    type: 'POST',
                    async: false,
                    global: false,
                    url: volvocarsFrontEnd.ajaxUrl,
                    data: {
                      action: 'removeAccount',
                      id: account.id
                    },
                    dataType: 'json',
                    success: function(data) {
                      if (data.state != 'ok') {
                        jeedomUtils.showAlert({message: data.result, level: 'danger'})
                        return
                      }
                      let card = document.querySelector('.accountDisplayCard[data-account_id="' + account.id + '"]')
                      if (card) {
                        card.remove()
                      }
                      let option = document.querySelector('#sel_account option[value="' + account.id + '"]')
                      if (option) {
                        option.remove()
                      }
                      editVolvocarsAccount.close()
                    }
                  })
                }
              },
            },
            confirm: {
              callback: {
                click: function(event) {
                  let account = editVolvocarsAccount.getAccount()
                  domUtils.ajax({
                    url: volvocarsFrontEnd.ajaxUrl,
                    data: {
                      action: 'saveAccount',
                      account: json_encode(account)
                    },
                    dataType: 'json',
                    success: function(data) {
                      if (data.state != 'ok') {
                        jeedomUtils.showAlert({message: data.result, level: 'danger'})
                        return
                      }
                      let card = document.querySelector('.accountDisplayCard[data-account_id="' + account.id + '"]')
                      let reload = false
                      if (card) {
                        card.getElementsByClassName('name')[0].innerText = account.name
                      } else {
                        reload = true
                      }
                      let option = document.querySelector('#sel_account option[value="' + account.id + '"]')
                      if (option) {
                        option.text = account.name
                      } else {
                        reload = true
                      }
                      if (reload) {
                        jeedomUtils.loadPage(document.URL)
                      }
                      editVolvocarsAccount.close()
                    }
                  })
                }
              }
            }
          },
          callback: function() {
            editVolvocarsAccount.init(account)
          },
        })
      },
    })
  }

  /*
   * Synchronisation d'un account
   */
  volvocarsFrontEnd.synchronizeAccount = function () {
    let options = []
    let accountCards = document.getElementsByClassName('accountDisplayCard')
    for (let i=0; i < accountCards.length; i++) {
      let option = {
        'value': accountCards[i].getAttribute('data-account_id'),
        'text': accountCards[i].getAttribute('data-account_name')
      }
      options.push(option)
    }
    jeeDialog.prompt({
      title: '{{Compte à synchronyser}}',
      message: "{{Compte}}:",
      inputType: 'select',
      inputOptions: options,
    }, function (accountId){
      if (!accountId) {
        return
      }
      domUtils.showLoading()
        setTimeout(function(){
        domUtils.ajax({
          type: 'POST',
          async: false,
          global: false,
          url: volvocarsFrontEnd.ajaxUrl,
          data: {
            action: 'synchronizeAccount',
            accountId: accountId
          },
          dataType: 'json',
          success: function(data) {
            if (data.state != 'ok') {
              jeedomUtils.showAlert({message: data.result, level: 'danger'})
              domUtils.hideLoading()
              return
            }
            jeedomUtils.loadPage(document.URL)
          }
        })
      })
    })
  }

  /*
   * togggle edit vehicle
   */
  volvocarsFrontEnd.toggleEditVehicle = function (edit) {
    if (edit) {
      jeeDialog.alert('{{Les données modifiées seront remises à jour lors de la prochaine synchronisation!}}')
      document.querySelector('.eqLogicAction[data-action=edit]').addClass('hide')
      document.querySelector('.eqLogicAction[data-action=protect]').removeClass('hide')
      document.querySelectorAll('.eqLogicAttr.sensible').removeClass('disabled')
      let checkboxes = document.querySelectorAll('input.eqLogicAttr.sensible[type=checkbox]')
      for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].closest('label').removeClass('disabled')
      }
    } else {
      document.querySelector('.eqLogicAction[data-action=edit]').removeClass('hide')
      document.querySelector('.eqLogicAction[data-action=protect]').addClass('hide')
      document.querySelectorAll('.eqLogicAttr.sensible').addClass('disabled')
      let checkboxes = document.querySelectorAll('input.eqLogicAttr.sensible[type=checkbox]')
      for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].closest('label').addClass('disabled')
      }
    }
  }
  
  /*
   * Action sur changement moteur électrique
   */
  volvocarsFrontEnd.toggleElectricEngine = function(checked) {
    if (checked) {
      document.getElementById('electricAutonomy').removeClass('hide')
      document.querySelector('.eqLogicAttr[data-l2key=batteryCapacityKWH]').closest('div.form-group').removeClass('hide')
    } else  {
      document.getElementById('electricAutonomy').addClass('hide')
      document.querySelector('.eqLogicAttr[data-l2key=batteryCapacityKWH]').closest('div.form-group').addClass('hide')
    }
  }
  
  /*
   * Action sur changement moteur thermique
   */
  volvocarsFrontEnd.toggleFuelEngine = function(checked) {
    if (checked) {
      document.getElementById('fuelAutonomy').removeClass('hide')
    } else  {
      document.getElementById('fuelAutonomy').addClass('hide')
    }
  }

 /*
  * Action du bouton Données brutes
  */
 volvocarsFrontEnd.showRawData = function() {
  let carId = document.querySelector('.eqLogicAttr[data-l1key=id]').value
  console.log(carId)
  jeeDialog.dialog({
    id: volvocarsFrontEnd.mdId_editAccount,
    title: '{{Compte}}: ',
    height: '90vh',
    width: '75vW',
    top: '5vh',
    contentUrl: 'index.php?v=d&plugin=volvocars&modal=rawData&eqLogicId=' + carId,
  })
 }

}

volvocarsFrontEnd.init()
volvocarsFrontEnd.toggleEditVehicle(false)

/*
 * function appelée lors du chargement d'un eqLogic
 */
function printEqLogic(data) {
  let img = document.querySelector('.eqLogicDisplayCard[data-eqLogic_id="' + data.id + '"] img').getAttribute('src')
  document.querySelector('#img_car').setAttribute('src',img)
}

/*
 * Activation / désactivation d'un site
 */
$('[data-l1key=configuration][data-l2key^=site][data-l2key$=_active]').off('change').on('change', function(){
  let site = $(this).data('site')
  if ($(this).value() == 1) {
    $('div.'+site+' *').removeClass('hidden')
  } else {
    $('div.'+site+' *').addClass('hidden')
    let cmds = []
    $('#table_cmd span[data-l1key=configuration][data-l2key=onlyFor]').filter(function(){
      return $(this).text() == site
    }).each(function(){
      name = $(this).closest('tr').find('.cmdAttr[data-l1key=name]').val()
      let logicalId = $(this).closest('tr').find('.cmdAttr[data-l1key=logicalId]').val()
      let id = $(this).closest('tr').find('.cmdAttr[data-l1key=id]').text()
      cmds.push({id:id, name:name, logicalId,logicalId})
    })
    if (cmds.length) {
      let message = "{{Les commandes suivantes seront supprimées lors de la sauvegarde}}" +":<br>"
      message += '<ul>'
      cmds.forEach(function(cmd){
        message += "<li>" + cmd.name + " (logicalId: " + cmd.logicalId + ")</li>"
      })
      message += '</ul>'
      bootbox.alert({
        message: message,
      })
    }
  }
})

/*
 * Action bouton de récupération position véhicule
 */
$('.eqLogicAction[data-action=get_pos]').off('click').on('click', function() {
  let site = $(this).data('site')
  let source = $(this).data('src')
  let id = ''
  if (source == 'car') {
    id = $('.eqLogicAttr[data-l1key=id]').value()
  }
  if (source == 'jeedom') {
    id = 'jeedom'
  }
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
 * Suppression d'une liste de commandes
 */
function removeCmds (ids) {
  let id = $('.eqLogicAttr[data-l1key=id]').value()
  $.ajax({
    type: 'POST',
    url: '/plugins/volvocars/core/ajax/volvocars.ajax.php',
    data: {
      action: 'getCmdsUse',
      ids: ids,
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
      uses = json_decode(data.result)
      let text ='{{Êtes-vous sûr de vouloir supprimer les commandes}}' + "?<br>"
      for (id in uses) {
        let name = $('#table_cmd tr[data-cmd_id=' + id + ']').find('.cmdAttr[data-l1key=name]').val()
        text += '- <b>' + name + '</b><br>'
        let usageText = ''
        for (composant in uses[id]) {
          switch (composant) {
            case 'cmd':         display_composant = '{{Commande}}';    break
            case 'eqLogic':     display_composant = '{{Equipement}}';  break
            case 'interactDef': display_composant = '{{Intéraction}}'; break
            case 'object':      display_composant = '{{Objet}}';       break
            case 'plan':        display_composant = '{{Plan}}';        break
            case 'plan3d':      display_composant = '{{Plan3d}}';      break
            case 'plugin':      display_composant = '{{Plugin}}';      break
            case 'scenario':    display_composant = '{{Scénario}}';    break
            case 'view':        display_composant = '{{Vue}}';         break
            default:
              display_composant = composant
          }
          for (entry in uses[id][composant]) {
            usageText += '&nbsp;&nbsp;&nbsp;&nbsp;• ' + display_composant + ' :  <b>' + uses[id][composant][entry].name + '</b><br>'
          }
        }
        if (usageText.length > 0) {
          text += "&nbsp;&nbsp;&nbsp;&nbsp;" + '{{Utilisé par:}}' + '<br>' + usageText
        }
      }
      bootbox.confirm(text, function(result) {
        if (result) {
          modifyWithoutSave = true
          ids.forEach(function(id) {
            $('#table_cmd tr.cmd[data-cmd_id=' + id + ']').remove()
          })
        }
      })
    }
  })
}

/*
 * Action sur bouton de suppression des commandes OPEN
 */
$('.cmdAction[data-action=removeOpen]').off('click').on('click', function() {
  removeCmds(
    $('#table_cmd .cmdAttr[data-l1key=logicalId]')
    .filter(function() {
      return this.value.endsWith('_open')
    })
    .closest('tr')
    .map(function(){
      return $(this).attr('data-cmd_id')
    })
    .get()
  )
})

/*
 * Action sur bouton de suppression des commandes CLOSED
 */
$('.cmdAction[data-action=removeClosed]').off('click').on('click', function() {
  removeCmds(
    $('#table_cmd .cmdAttr[data-l1key=logicalId]')
    .filter(function() {
      return this.value.endsWith('_closed')
    })
    .closest('tr')
    .map(function(){
      return $(this).attr('data-cmd_id')
    })
    .get()
  )
})

/*
 * Action sur bouton de recréation des commandes manquantes
 */
$('.cmdAction[data-action=recreate]').off('click').on('click',function() {
  if (modifyWithoutSave) {
    bootbox.alert("{{Vous devez sauvegarder vos modifications en cours avant de lancer cette opération!}}")
    return
  }
  id = $('.eqLogicAttr[data-l1key=id]').value()
  $.showLoading()
  $.ajax({
    type: 'POST',
    url: 'plugins/volvocars/core/ajax/volvocars.ajax.php',
    data: {
      action: 'recreateCmds',
      id: id
    },
    dataType: 'json',
    global: false,
    error: function (request, status, error) {
      $.hideLoading()
      handleAjaxError(request, status, error)
    },
    success: function (data) {
      if (data.state != 'ok') {
        $.hideLoading()
        $.fn.showAlert({message: data.result, level:'danger'})
        return
      }
      location.reload()
    }
  })
})

/*
 * Action sur le bouton de tri des commandes
 */
$('.cmdAction[data-action=sort]').off('click').on('click',function() {
  if (modifyWithoutSave) {
    bootbox.alert("{{Vous devez sauvegarder vos modifications en cours avant de lancer cette opération!}}")
    return
  }
  id = $('.eqLogicAttr[data-l1key=id]').value()
  $.showLoading()
  $.ajax({
    type: 'POST',
    url: 'plugins/volvocars/core/ajax/volvocars.ajax.php',
    data: {
      action: 'sortCmds',
      id: id
    },
    dataType: 'json',
    global: false,
    error: function (request, status, error) {
      $.hideLoading()
      handleAjaxError(request, status, error)
    },
    success: function (data) {
      if (data.state != 'ok') {
        $.hideLoading()
        $.fn.showAlert({message: data.result, level:'danger'})
        return
      }
      location.reload()
    }
  })
})

/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    let _cmd = { configuration: {} }
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {}
  }
  if (isset(_cmd.logicalId)) {
    if (_cmd.logicalId.endsWith("_open")) {
      $('.cmdAction[data-action=removeOpen]').removeClass('hidden')
    }
    if (_cmd.logicalId.endsWith("_closed")) {
      $('.cmdAction[data-action=removeClosed]').removeClass('hidden')
    }
  }
  let tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
  tr += '<td class="hidden-xs">'
  tr += '<span class="cmdAttr" data-l1key="id"></span>'
  tr += '<span class="cmdAttr hidden" data-l1key="configuration" data-l2key="onlyFor"></span>'
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
  tr = $('#table_cmd tbody tr').last()
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
