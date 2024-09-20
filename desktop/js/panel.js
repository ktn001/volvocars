// vim: tabstop=2 autoindent expandtab

"use strict";

if (typeof volvocarsPanel === "undefined") {
  var volvocarsPanel = {};

  /*
   * Initialisation
   */
  volvocarsPanel.init = function () {
    document
      .getElementById("div_pageContainer")
      .addEventListener("click", function (event) {
        let _target = null;

        if ((_target = event.target.closest("#div_display_eqLogicList a"))) {
          if (!_target.closest("li").matches(".active")) {
            let entries = _target.closest("ul").querySelectorAll("li.active");
            for (let i = 0; i < entries.length; i++) {
              entries[i].removeClass("active");
            }
            _target.closest("li").addClass("active");
          }
          volvocarsPanel.displaySelectedEqLogic();
        }
      });
    jeedomUI.setHistoryModalHandler();
  };

  /*
   * Affichage du véhicule sélectionné
   */
  volvocarsPanel.displaySelectedEqLogic = function () {
    domUtils.showLoading();
    let el = document.querySelector("#div_display_eqLogicList .active[data-eqLogic_id]")
    if (! el) {
      return
    }
    let id = el.getAttribute("data-eqLogic_id");
    domUtils.ajax({
      type: "POST",
      url: "plugins/volvocars/core/ajax/volvocars.ajax.php",
      async: false,
      data: {
        action: "panelWidget",
        id: id,
      },
      dataType: "json",
      global: false,
      success: function (data) {
        if (data.state != "ok") {
          jeedomUtils.showAlert({ message: data.result, level: "danger" });
          return;
        }
        setTimeout(function () {
          document
            .getElementById("div_display_eqLogic")
            .empty()
            .html(data.result);
          domUtils.hideLoading();
        }, 50);
      },
    });
  };
}
volvocarsPanel.init();
setTimeout (volvocarsPanel.displaySelectedEqLogic, 500)
