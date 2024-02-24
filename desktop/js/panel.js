// vim: tabstop=4 autoindent

if(jeedomUtils){jeedomUtils.positionEqLogic();}else{positionEqLogic();}

//----- Theme colors

//$('body').on('changeThemeEvent', function (event,theme) {
//	timedSetTheme(0);
//});

//function timedSetTheme(occurence = 0){
//
//	if ( $('body')[0].hasAttribute('data-theme') != true )  {
//		occurence++;
//		if (occurence > 40){
//			return;
//		}
//		setTimeout( () => { timedSetTheme(occurence); }, 500 );
//		return;
//	}
//
//	var bckgd_color;
//	var font_color;
//
//	if ($('body').attr('data-theme') == 'core2019_Dark') {
//	bckgd_color = 'black';
//	font_color = 'white';
//	}
//	else if ($('body').attr('data-theme') == 'core2019_Light') {
//		bckgd_color = 'white';
//		font_color = 'black';
//	}
//	var bckgd = document.getElementById("bckgd");
//	var title = document.getElementById("title");
//	bckgd.style.backgroundColor = bckgd_color;
//	title.style.backgroundColor = bckgd_color;
//	title.style.color = font_color;
//}
//
//timedSetTheme(0);

//----- Affichage du véhicule slectionné
function displaySelectedEqLogic () {
	$.showLoading()
	id = $('#div_display_eqLogicList .active[data-eqLogic_id]').attr('data-eqLogic_id')
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
