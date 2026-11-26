"use strict"
if (typeof editAccount === "undefined") {
  var editAccount = {
    modal : null,
    unsavedChanges : false,

    init: function(accountId) {
      editAccount.modal = document.getElementById(volvocarsFrontEnd.mdId_editAccount)

      editAccount.modal.addEventListener('click', function(event) {
        let _target = null

        if (_target = event.target.closest('#bt_resetOAuthLink')) {
          editAccount.resetOAuthLink(accountId)
          return
        }
      })

      editAccount.modal.addEventListener('change', function(event) {
        editAccount.unsavedChanges = true
      })

      editAccount.modal.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
          event.preventDefault()
          jeeDialog.get(editAccount.modal).close()
        }
      })

      editAccount.load(accountId)
      let dialogInstance = jeeDialog.get('#md_monPlugin')
      let dialogEl = document.getElementById('md_monPlugin')
    },

    load(accountId) {
      if (accountId) {
        domUtils.ajax({
          url : volvocarsFrontEnd.ajaxUrl,
          data : {
            action: "getAccount",
            id: accountId
          },
          error: function(error) {
            jeedomUtils.showAlert({ message: error.result, level: 'danger'})
          },
          success: function(data) {
            if (data.state != 'ok') {
              jeedomUtils.showAlert({ message: data.result, level: 'danger' })
              return
            }
            let account = json_decode(data.result)
            editAccount.modal.setJeeValues(account,'.accountAttr')
            editAccount.unsavedChanges = false
          }
        })
      }
    },

    resetOAuthLink : function(accountId) {
      if (editAccount.unsavedChanges) {
        alert("{{Il y a des modification non sauvegardées, veuillez d'abord sauvegarder.}}")
        return
      }
      let texte = '{{Veuillez utiliser vos identifiants VolvoId dans la fenêtre qui va s\'ouvrir pour approuver le lien entre Volvocars et votre Jeedom.}}'
      texte += '<br>{{Sélectionnez la case}}<b> "{{Se souvenir de moi}}"</b> {{pour que le lien avec Jeedom soit permanent.}}'
      texte += '<br/><br/>'
      texte += '{{Vous pourrez ensuite cliquer sur le bouton Rafraichir ci-dessous.}}'
      texte += '<br/><br/>'
      texte += '{{Êtes-vous sur de vouloir continuer? vous ne devriez faire cela que si le statut est NOK}}'
      let color
      if (document.body.getAttribute('data-theme') == 'core2019_Dark') {
        color = 'rgb(55,55,55)'
      }
      jeeDialog.confirm({
        message: texte,
        className: 'confirmOAuth',
        callback: function(result) {
          if (result) {
            domUtils.ajax({
              url : volvocarsFrontEnd.ajaxUrl,
              data : {
                action: "resetOAuthLink",
                accountId: accountId,
              },
              error: function(error) {
                jeedomUtils.showAlert({ message: error.result, level: 'danger'})
                resolve(false)
              },
              success: function(data) {
                if (data.state != 'ok') {
                  jeedomUtils.showAlert({ message: data.result, level: 'danger'})
                  return
                }
                if (!isset(data.result)) {
                  jeedomUtils.showAlert({ message: "{{URL d'authentification non définie}}", level: 'danger'});
                  return
                }
                window.open(data.result).focus()
              },
            })
          }
        }
      })
      let dialogBox = document.querySelector('.jeeDialogConfirm')
      if (dialogBox && color) {
        dialogBox.style.backgroundColor = color
      }
    },

    cancel: function() {
      editAccount.close()
    },

    delete: async function() {
      let accountId = editAccount.modal.querySelector('.accountAttr[data-l1key=id]').jeeValue()
      let remove = new Promise((resolve) => {
        domUtils.ajax({
          url : volvocarsFrontEnd.ajaxUrl,
          data : {
            action: "removeAccount",
            id: accountId,
          },
          error: function(error) {
            jeedomUtils.showAlert({ message: error.result, level: 'danger'})
            resolve(false)
          },
          success: function(data) {
            if (data.state != 'ok') {
              jeedomUtils.showAlert({ message: data.result, level: 'danger'})
              resolve(false)
              return
            }
            resolve(true)
          }
        })
      })
      let isRemoved = await remove
      if (isRemoved) {
        volvocarsFrontEnd.removeAccountCard(accountId)
        editAccount.close()
      }
    },

    save: function() {
      return new Promise((resolve) => {
        let account = editAccount.modal.getJeeValues('.accountAttr');
        account = JSON.stringify(account[0])
        domUtils.ajax({
          url : volvocarsFrontEnd.ajaxUrl,
          data : {
            action: "saveAccount",
            account: account,
          },
          error: function(error) {
            jeedomUtils.showAlert({ message: error.result, level: 'danger'})
            resolve(false)
          },
          success: function(data) {
            if (data.state != 'ok') {
              jeedomUtils.showAlert({ message: data.result, level: 'danger'})
              resolve(false)
              return
            }
            editAccount.modal.setJeeValues(data.result,'.accountAttr')
            volvocarsFrontEnd.updateOrAddAccountCard(data.result.id, data.result.name)
            editAccount.unsavedChanges = false
            resolve(true)
          }
        })
      })
    },

    saveAndClose: async function() {
      let isSaved = await editAccount.save()
      if (isSaved) {
        editAccount.close()
      }
    },

    close: function() {
      jeeDialog.get(editAccount.modal).close()
    },

    beforeClose: function() {
      return false
    },

    onClose: function() {
      jeeDialog.get(editAccount.modal).destroy()
    },

    onShown: function() {
    },
  }
}
