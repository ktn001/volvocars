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

"use strict"

$('.show_values').off('click').on('click', function() {
	$(this).addClass('hidden')
	$(this).closest('.rawData').find('.hidde_values').removeClass('hidden')
	$(this).closest('.rawData').find('.rawData-body').removeClass('hidden')
})

$('.hidde_values').off('click').on('click', function() {
	$(this).addClass('hidden')
	$(this).closest('.rawData').find('.show_values').removeClass('hidden')
	$(this).closest('.rawData').find('.rawData-body').addClass('hidden')
})

$('.hidde_values').trigger('click')
