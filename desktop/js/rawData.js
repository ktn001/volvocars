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

"use strict";

if (typeof volvocarsRawData === "undefined") {
  var volvocarsRawData = {

    init: function () {
      document
        .getElementById(volvocarsFrontEnd.mdId_rawDatas)
        .addEventListener("click", function (event) {
          let _target = null;
  
          if (_target = event.target.closest(".endpointEntry")) {
            volvocarsRawData.toggle(_target)
            return;
          }
  
          if (_target = event.target.closest(".rawDataAction[data-action=plie]")) {
            document.querySelectorAll(".endpointEntry").forEach(function (entry) {
              volvocarsRawData.hidde(entry)
            });
          }
          if (_target = event.target.closest(".rawDataAction[data-action=deplie]")) {
            document.querySelectorAll(".endpointEntry").forEach(function (entry) {
              volvocarsRawData.show(entry)
            })
          }
        });
  
      document
        .getElementById(volvocarsFrontEnd.mdId_rawDatas)
        .querySelector('.rawDataAction[data-action=plie')
        .click()
    },

    toggle: function (entry) {
      if (entry.querySelector('.show_values').isHidden()) {
        volvocarsRawData.hidde(entry)
      } else {
        volvocarsRawData.show(entry)
      }
    },

    show: function (entry) {
      entry.querySelector('.show_values').unseen()
      entry.querySelector('.hidde_values').seen()
      entry.querySelector('.endpointValue').seen()
    },

    hidde: function (entry) {
      entry.querySelector('.show_values').seen()
      entry.querySelector('.hidde_values').unseen()
      entry.querySelector('.endpointValue').unseen()
    },
  }
}
volvocarsRawData.init();
