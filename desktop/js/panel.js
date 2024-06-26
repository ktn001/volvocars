// vim: tabstop=4 autoindent

"use strict"

if(jeedomUtils){jeedomUtils.positionEqLogic();}else{positionEqLogic();}

//----- Affichage du véhicule slectionné
function displaySelectedEqLogic () {
	$.showLoading()
	let id = $('#div_display_eqLogicList .active[data-eqLogic_id]').attr('data-eqLogic_id')
	$.ajax({
		type: 'POST',
		url: 'plugins/volvocars/core/ajax/volvocars.ajax.php',
		data: {
			action: 'panelWidget',
			id: id
		},
		dataType: 'json',
		global: false,
		error: function(request, status, error) {
			$.hideLoading()
            handleAjaxError(request, status, error)
        },
        success: function(data) {
            if (data.state != 'ok') {
                $.fn.showAlert({message: data.result, level: 'danger'})
				$.hideLoading()
                return
            }
			console.debug(data.result)
			$('#div_display_eqLogic').empty().html(data.result)
			//$('#eqLogic_widget_container').empty().html(data.result)
			$.hideLoading()
		}
	})

}
//----- Sélection d'un véhicule

$('#div_display_eqLogicList a').off('click').on('click', function() {
	if (! $(this).closest('li').hasClass('active')) {
		$('#div_display_eqLogicList .active').removeClass('active')
		$(this).closest('li').addClass('active')
	}
	displaySelectedEqLogic()
})

displaySelectedEqLogic()
