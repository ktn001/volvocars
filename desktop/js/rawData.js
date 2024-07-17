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

if (typeof volvocarsRawData === "undefined") {
  var volvocarsRawData ={}

  volvocarsRawData.init = function() {
    console.log(volvocarsFrontEnd.mdId_rawDatas)
    document.getElementById(volvocarsFrontEnd.mdId_rawDatas).addEventListener('click', function(event) {
      let _target = null

      if (_target = event.target.closest('.endpointEntry')) {
        if (_target.getElementsByClassName('show_values')[0].isHidden()) {
          _target.getElementsByClassName('show_values')[0].seen()
          _target.getElementsByClassName('hidde_values')[0].unseen()
          _target.getElementsByClassName('endpointValue')[0].unseen()
        } else {
          _target.getElementsByClassName('show_values')[0].unseen()
          _target.getElementsByClassName('hidde_values')[0].seen()
          _target.getElementsByClassName('endpointValue')[0].seen()
        }
        return
      }

      if (_target = event.target.closest('.rawDataAction[data-action=plie]')) {
        console.log("PLIE")
        document.querySelectorAll('.endpointHeader .show_values').forEach(function(node) {
          node.unseen()
          node.click()
        })
      }
      if (_target = event.target.closest('.rawDataAction[data-action=deplie]')) {
        console.log("DEPLIE")
        document.querySelectorAll('.endpointHeader .show_values').forEach(function(node) {
          node.seen()
          node.click()
        })
      }

    })

    document.querySelectorAll('.endpointHeader .show_values').forEach(function(node) {
       node.unseen()
       node.click()
    })
  }

}
volvocarsRawData.init()
