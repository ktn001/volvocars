<?php
// vim: tabstop=4 autoindent

if (!isConnect()){
	throw new Exception('{{401 - Accès non autorisé}}');
}

$pluginName = init('m');

$eqLogics = eqLogic::byType($pluginName, true);
if (!$eqLogics) {
	throw new Exception('{{Aucun Véhicule trouvé. Pour en créer un, allez dans Plungins -> Objets connectés -> Volvo cars.}}');
}
include_file('desktop', 'panel', 'css', 'volvocars');
?>

<div class="row row-overflow" id="bckgd">

	<div id="div_display_eqLogicList" class="col-lg-2 col-md-3 col-sm-4">
		<div>
		<img class="volvo_logo_black" src="/plugins/volvocars/desktop/img/volvo_black.svg">
        <img class="volvo_logo_white" src="/plugins/volvocars/desktop/img/volvo_white.svg">
		</div>

		<span id="title"><i class="fas fa-car"></i> {{Mes véhicules}}</span>
		<div class="bs-sidebar">
			<ul id="ul_object" class="nav nav-list bs-sidenav">
				<?php
				$first = true;
				foreach ($eqLogics as $eqLogic) {
					if ($eqLogic->getIsVisible() != 1) {
						continue;
					}
					$active = '';
					if (init('eqLogic_id') == '' && $first == true) {
						$active = 'active';
						$first = false;
					} elseif ($eqLogic->getId() == init('eqLogic_id')) {
						$active = 'active';
					}
					?>
					<li class="cursor li_object <?=$active?>" data-eqLogic_id="<?=$eqLogic->getId()?>">
						<a class="nav-link" >
							<span class="img-container">
								<img src=<?=$eqLogic->getImage()?>>
							</span>
							<?=$eqLogic->getName()?>
						</a>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
	</div>

	<div id="div_display_eqLogic" class="col-lg-10 col-md-9 col-sm-8">
		<div id="eqLogic_widget_container" style="width: 100%;">
		</div>
	</div>
</div>

<?php
include_file('desktop', 'panel', 'js', 'volvocars');
?>
