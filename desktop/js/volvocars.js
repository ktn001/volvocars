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
    mdId_rawDatas: 'mod_rawDatas',
    accountNeedReload : false,
    ajaxUrl : 'plugins/volvocars/core/ajax/volvocars.ajax.php',
  }

    /*
     * Initialisation après chargement de la page
     */
  volvocarsFrontEnd.init = function() {
    /*
     * Click
     */
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

      if (_target = event.target.closest('.eqLogicAction[data-action=get_pos]')) {
        volvocarsFrontEnd.setSitePosition(_target)
        return
      }

      if (_target = event.target.closest('.cmdAction[data-action=removeOpen]')){
        volvocarsFrontEnd.removeOpenOrClosedCmds('open')
        return
      }

      if (_target = event.target.closest('.cmdAction[data-action=removeClosed]')){
        volvocarsFrontEnd.removeOpenOrClosedCmds('closed')
        return
      }

      if (_target = event.target.closest('.cmdAction[data-action=recreate]')) {
        volvocarsFrontEnd.recreateMissingsCmds()
        return
      }

      if (_target = event.target.closest('.cmdAction[data-action=sort]')) {
        volvocarsFrontEnd.sortCmds()
        return
      }

    })

    /*
     * Change
     */
    document.getElementById('div_pageContainer').addEventListener('change', function(event) {
      let _target = null

      if (_target = event.target.closest('.eqLogicAttr[data-l2key=electricEngine]')) {
        volvocarsFrontEnd.toggleElectricEngine(_target.checked)
        return
      }

      if (_target = event.target.closest('.eqLogicAttr[data-l2key=fuelEngine]')) {
        volvocarsFrontEnd.toggleFuelEngine(_target.checked)
        return
      }

      if (_target = event.target.closest('[data-l1key=configuration][data-l2key^=site][data-l2key$=_active]')) {
        volvocarsFrontEnd.siteActivation(_target)
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
    jeeDialog.dialog({
      id: volvocarsFrontEnd.mdId_rawDatas,
      title: '{{RawData}}: ',
      height: '90vh',
      width: '75vW',
      top: '5vh',
      contentUrl: 'index.php?v=d&plugin=volvocars&modal=rawData&eqLogicId=' + carId,
      buttons: {
        'cancel': {
          className: 'hidden'
        },
        'confirm': {
          callback: { 
            click: function() {
              document.getElementById('mod_rawDatas')._jeeDialog.close()
            },
          },
        },
      },
    })
  }

  /*
   * Action sur (dés)activation d'un site
   */
  volvocarsFrontEnd.siteActivation = function(_checkbox) {
    let site = _checkbox.getAttribute('data-site')
    if (_checkbox.checked) {
      document.querySelectorAll('div.' + site + ' *').seen()
    } else {
      document.querySelectorAll('div.' + site + ' *').unseen()
      let cmds = []
      document.querySelectorAll('#table_cmd span[data-l1key=configuration][data-l2key=onlyFor]').forEach(function(node){
        if (node.textContent != site) {
          return
        }
        let name = node.closest('tr').querySelector('.cmdAttr[data-l1key=name]').value
        let logicalId = node.closest('tr').querySelector('.cmdAttr[data-l1key=logicalId]').value
        cmds.push({name:name, logicalId: logicalId})
      })
      if (cmds.length) {
        let message = "{{Les commandes suivantes seront supprimées lors de la sauvegarde}}" +":<br>"
        message += '<ul>'
        cmds.forEach(function(cmd){
          message += "<li>" + cmd.name + " (logicalId: " + cmd.logicalId + ")</li>"
        })
        message += '</ul>'
        jeeDialog.alert(message)
      }
    }
  }

  /*
   * Action bouton de récupération position pour un site
   */
  volvocarsFrontEnd.setSitePosition = function(button) {
    let site = button.getAttribute('data-site')
    let source = button.getAttribute('data-src')
    let id = ''
    if (source == 'car') {
      id = document.querySelector('.eqLogicAttr[data-l1key=id]').value
    }
    if (source == 'jeedom') {
      id = 'jeedom'
    }
    domUtils.showLoading()
    domUtils.ajax({
      type: 'POST',
      async: false,
      global: false,
      url: volvocarsFrontEnd.ajaxUrl,
      data: {
        action: 'getPosition',
        id: id
      },
      dataType: 'json',
      success: function(data) {
        if (data.state != 'ok') {
          jeedomUtils.showAlert({message: data.result, level: 'danger'})
          domUtils.hideLoading()
          return
        }
        document.querySelector('[data-l2key='+site+'_lat').value = data.result.lat
        document.querySelector('[data-l2key='+site+'_long').value = data.result.long
        domUtils.hideLoading()
      }
    })
  }

  /*
   * Suppression d'une liste de commandes
   */
  volvocarsFrontEnd.removeCmds = function(cmdIds) {
    domUtils.ajax({
      type: 'POST',
      async: false,
      global: false,
      url: volvocarsFrontEnd.ajaxUrl,
      data: {
        action: 'getCmdsUse',
        ids: JSON.stringify(cmdIds),
      },
      dataType: 'json',
      success: function(data) {
        if (data.state != 'ok') {
          jeedomUtils.showAlert({message: data.result, level: 'danger'})
          return
        }
        let uses = json_decode(data.result)
        let text ='{{Êtes-vous sûr de vouloir supprimer les commandes}}' + "?<br>"
        for (let id in uses) {
          let name = document.querySelector('#table_cmd tr[data-cmd_id="' + id + '"]' + ' .cmdAttr[data-l1key=name]').value
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
        jeeDialog.confirm(text, function(response) {
          if (response) {
            jeeFrontEnd.modifyWithoutSave = true
            cmdIds.forEach(function(id) {
              document.querySelector('#table_cmd tr.cmd[data-cmd_id="' + id + '"]').remove()
            })
          }
        })
      }
    })
  }

  /*
   * Supression des commandes *_open on *_closed
   */
  volvocarsFrontEnd.removeOpenOrClosedCmds = function(state) {
    let ids = []
    document.querySelectorAll('#table_cmd .cmdAttr[data-l1key=logicalId]').forEach(function(logicalIdEl) {
      if (logicalIdEl.value.endsWith('_' + state)) {
        let id = logicalIdEl.closest('tr').querySelector('.cmdAttr[data-l1key=id]').textContent
        ids.push(id)
      }
    })
    volvocarsFrontEnd.removeCmds(ids)
  }

  /*
   * Recréation des commandes manquantes
   */
  volvocarsFrontEnd.recreateMissingsCmds = function() {
    if (jeeFrontEnd.modifyWithoutSave) {
      jeeDialog.alert("{{Vous devez sauvegarder vos modifications en cours avant de lancer cette opération!}}")
      return
    }
    domUtils.showLoading()
    let carId = document.querySelector('.eqLogicAttr[data-l1key=id]').value
    domUtils.ajax({
      type: 'POST',
      async: false,
      global: false,
      url: volvocarsFrontEnd.ajaxUrl,
      data: {
        action: 'recreateCmds',
        id: carId,
      },
      dataType: 'json',
      success: function(data) {
        if (data.state != 'ok') {
          jeedomUtils.showAlert({message: data.result, level: 'danger'})
          return
        }
        jeedomUtils.loadPage(document.URL)
      }
    })
  }

  volvocarsFrontEnd.sortCmds = function() {
    if (jeeFrontEnd.modifyWithoutSave) {
      jeeDialog.alert("{{Vous devez sauvegarder vos modifications en cours avant de lancer cette opération!}}")
      return
    }
    domUtils.showLoading()
    let carId = document.querySelector('.eqLogicAttr[data-l1key=id]').value
    domUtils.ajax({
      type: 'POST',
      async: false,
      global: false,
      url: volvocarsFrontEnd.ajaxUrl,
      data: {
        action: 'sortCmds',
        id: carId,
      },
      dataType: 'json',
      success: function(data) {
        if (data.state != 'ok') {
          jeedomUtils.showAlert({message: data.result, level: 'danger'})
          return
        }
        jeedomUtils.loadPage(document.URL)
      }
    })
  }

  volvocarsFrontEnd.addCmdToTable = function(_cmd) {
    if (!isset(_cmd)) {
      let _cmd = { configuration: {} }
    }
    if (!isset(_cmd.configuration)) {
      _cmd.configuration = {}
    }
    if (isset(_cmd.logicalId)) {
      if (_cmd.logicalId.endsWith("_open")) {
        document.querySelector('.cmdAction[data-action=removeOpen]').removeClass('hidden')
      }
      if (_cmd.logicalId.endsWith("_closed")) {
        document.querySelector('.cmdAction[data-action=removeClosed]').removeClass('hidden')
      }
    }
    let tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
    // ID
    tr += '<td class="hidden-xs">'
    tr += '<span class="cmdAttr" data-l1key="id"></span>'
    tr += '<span class="cmdAttr hidden" data-l1key="configuration" data-l2key="onlyFor"></span>'
    tr += '</td>'
    // Nom
    tr += '<td>'
    tr += '<div class="input-group">'
    tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
    tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
    tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
    tr += '</div>'
    tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
    tr += '<option value="">{{Aucune}}</option>'
    tr += '</select>'
    // Type
    tr += '</td>'
    tr += '<td>'
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
    tr += '</td>'
    // LogicalId
    tr += '<td>'
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="logicalId" placeholder="LogicalId">'
    tr += '</td>'
    // Paramètres
    tr += '<td>'
    tr += '</td>'
    // Options
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
    // Etat
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
    tr += '</td>';
    // Actions
    tr += '<td>'
    if (is_numeric(_cmd.id)) {
      tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
      tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>'
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i>'
    tr += '</td>'
    tr += '</tr>'

    let newRow = document.createElement('tr')
    newRow.innerHTML = tr
    newRow.addClass('cmd')
    newRow.setAttribute('data-cmd_id', init(_cmd.id))
    document.getElementById('table_cmd').querySelector('tbody').appendChild(newRow)
    jeedom.eqLogic.buildSelectCmd({
      id: document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue(),
      filter: { type: 'info' },
      error: function(error) {
        jeedomUtils.showAlert({ message: error.message, level: 'danger' })
      },
      success: function(result) {
        newRow.querySelector('.cmdAttr[data-l1key="value"]').insertAdjacentHTML('beforeend', result)
        newRow.setJeeValues(_cmd, '.cmdAttr')
        jeedom.cmd.changeType(newRow, init(_cmd.subType))
      }
    })

  }

  volvocarsFrontEnd.toggleEditVehicle(false)
}

volvocarsFrontEnd.init()

/*
 * function appelée lors du chargement d'un eqLogic
 */
function printEqLogic(data) {
  let img = document.querySelector('.eqLogicDisplayCard[data-eqLogic_id="' + data.id + '"] img').getAttribute('src')
  document.querySelector('#img_car').setAttribute('src',img)
}

/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
  volvocarsFrontEnd.addCmdToTable(_cmd)
}
