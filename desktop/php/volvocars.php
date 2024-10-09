<?php
// vim: tabstop=4 autoindent

require_once __DIR__ . '/../../core/php/volvocars.inc.php';
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// Déclaration des variables obligatoires
include_file('desktop', 'volvocars', 'css', 'volvocars');
$plugin = plugin::byId('volvocars');
sendVarToJS([
	'eqType' => $plugin->getId(),
]);
$eqLogics = eqLogic::byType($plugin->getId());
$accounts = volvoAccount::all();
?>

<div class="row row-overflow">
	<!-- ======================== -->
	<!-- Page d'accueil du plugin -->
	<!-- ======================== -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">

		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<!-- Boutons de gestion du plugin -->
		<!-- ============================= -->
		<div class="eqLogicThumbnailContainer">
			<div class="cursor accountAction logoPrimary" data-action="add">
				<i class="fas fa-user-plus"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<?php
			if (count($accounts) > 0) {
				?>
				<div class="cursor accountAction" data-action="sync">
					<i class="fas fa-sync-alt logoPrimary"></i>
					<br>
					<span>{{Synchronisation}}</span>
				</div>
				<?php
			}
			?>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div> <!-- Boutons de gestion du plugin -->

		<!-- Les comptes -->
		<!-- =========== -->
		<legend><i class="fas fa-table"></i> {{Mes comptes volvoId}}</legend>
		<?php
		if (count($accounts) == 0) {
			?>
			<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun compte VolvoId trouvé, cliquer sur "Ajouter" pour commencer}}</div>
			<?php
		} else {
			?>
			<!-- Champ de recherche -->
			<div class="input-group" style="margin:5px;">
				<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchAccount">
				<div class="input-group-btn">
					<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>
					<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>
				</div>
			</div>
			<!-- liste das comptes -->
			<div class="eqLogicThumbnailContainer" data-type='account'>
				<?php
				foreach ($accounts as $account) {
					echo "<div class='accountDisplayCard cursor' data-account_id='{$account->getId()}' data-account_name='{$account->getName()}'>";
					echo "<img src='/plugins/volvocars/desktop/img/account.png'/>";
					echo "<br>";
					echo "<span class='name'>{$account->getName()}</span>";
					echo "</div>";
				}
				?>
			</div>
			<?php
		}
		?>

		<!-- Les véhicules -->
		<!-- ============= -->
		<legend><i class="fas fa-table"></i> {{Mes véhicules}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			if (count($accounts) == 0) {
				echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Vous devez créer un compte volvoId avant de pouvoir créer un véhicule}}</div>';
			} else {
				echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun véhicule trouvé, cliquer sur "Synchronisation" pour commencer}}</div>';
			}
		} else {
			// Champ de recherche
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Liste des équipements du plugin
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $eqLogic->getImage() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hiddenAsCard displayTableRight hidden">';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div> <!-- /.eqLogicThumbnailDisplay -->

	<!-- Page de présentation de l'équipement -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<!-- barre de gestion de l'équipement -->
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<!-- Onglets -->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content">
			<!-- Onglet de configuration de l'équipement -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<!-- Partie gauche de l'onglet "Equipements" -->
				<!-- Paramètres généraux et spécifiques de l'équipement -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">

							<!-- PARAMETRES GENERAUX -->

							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom du véhicule}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="visibleOnPanel" checked>{{Visible sur le panel}}</label>
								</div>
							</div>

							<!-- PARAMETRES VEHICULE -->

							<legend><i class="fas fa-cogs"></i> {{Paramètres du véhicule}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">vin
									<sup><i class="fas fa-question-circle tooltips" title="{{vin du véhicule}}"></i></sup>
								</label>
								<div class="col-sm-6 blur">
									<input type="text" class="eqLogicAttr form-control sensible" data-l1key="logicalId" placeholder="{{vin}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Compte VolvoId}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Compte VolvoId pour la gestion du véhicule}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<select id="sel_account" class="eqLogicAttr form-control sensible" data-l1key="configuration" data-l2key="account_id">
									<?php
									foreach ($accounts as $account) {
										echo "<option value='{$account->getId()}'>{$account->getName()}</option>";
									}
									?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Modèle}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Modèle du véhicule}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control sensible" data-l1key="configuration" data-l2key="model" placeholder="{{Modèle}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Couleur}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control sensible" data-l1key="configuration" data-l2key="externalColour" placeholder="{{Couleur}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Année}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control sensible" data-l1key="configuration" data-l2key="modelYear" placeholder="{{Année}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Carburant}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control sensible" data-l1key="configuration" data-l2key="fuelType" placeholder="{{Carburant}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Capacité de la batterie (KWH)}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control sensible" data-l1key="configuration" data-l2key="batteryCapacityKWH" placeholder="KWH">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Boîte à vitesse}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control sensible" data-l1key="configuration" data-l2key="gearbox" placeholder="{{Boîte à vitesse}}">
								</div>
							</div>
							<div class="form-group">
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Moteur}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input class="eqLogicAttr sensible" type="checkbox" data-l1key="configuration" data-l2key="electricEngine">{{Electrique}}</label>
									<label class="checkbox-inline"><input class="eqLogicAttr sensible" type="checkbox" data-l1key="configuration" data-l2key="fuelEngine">{{Thermique}}</label>
								</div>
							</div>
							<div class="form-group">
								<div class="col-sm-4">
								</div>
								<div class="col-sm-6">
									<a class="btn btn-sm btn-danger eqLogicAction pull-right" data-action="edit"><i class="fas fa-edit"></i> {{Editer}}</a>
									<a class="btn btn-sm btn-success eqLogicAction pull-right" data-action="protect"><i class="fas fa-ban"></i> {{Protéger}}</a>
								</div>
							</div>

							<!-- PARAMETRES ALERTES -->

							<legend><i class="fas fa-exclamation-triangle"></i> {{Paramètres d'alertes}}</legend>
							<div id="electricAutonomy" class="form-group">
								<label class="col-sm-4 control-label">{{Autonomie électrique (Km)}}</label>
								<div class="col-sm-6">
									<input class="eqLogicAttr form-control" type="text" data-l1key="configuration" data-l2key="electricAutonomyLimit"></input>
								</div>
							</div>
							<div id="fuelAutonomy" class="form-group">
								<label class="col-sm-4 control-label">{{Autonomie thermique (Km)}}</label>
								<div class="col-sm-6">
									<input class="eqLogicAttr form-control" type="text" data-l1key="configuration" data-l2key="fuelAutonomyLimit">
								</div>
							</div>

							<!-- PARAMETRES LOCALISATION -->

							<legend><i class="fas fa-location-arrow"></i> {{Paramètres de localisation}}</legend>
							<div class="form-group">
								<div class="col-sm-offset-4 col-sm-2">
									{{Site 1}}:
								</div>
								<div class="col-sm-2">
									<label class="checkbox-inline pull-right"><input class="eqLogicAttr" type="checkbox" checked data-site="site1" data-l1key="configuration" data-l2key="site1_active">{{Activer}}</label>
								</div>
								<div class="col-sm-2">
									{{Site 2}}:
								</div>
								<div class="col-sm-2">
									<label class="checkbox-inline pull-right"><input class="eqLogicAttr" type="checkbox" checked data-site="site2" data-l1key="configuration" data-l2key="site2_active">{{Activer}}</label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom}}</label>
								<div class="col-sm-4 site1">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="site1_name" placeholder="{{Domicile}}">
								</div>
								<div class="col-sm-4 site2">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="site2_name" placeholder="{{Autre}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label help" data-help="{{Coordonnées GPS au format xx.xxxxxx  et pas xx°xx'xx.x''N}}">{{Coordonnées GPS}}</label>
								<div class="col-sm-4 site1" style="padding-left: 0px !important; padding-right: 0px !important">
									<div class="col-sm-6" style="padding-right: 1px !important">
										<input type="text" class="eqLogicAttr form-control blur" data-l1key="configuration" data-l2key="site1_lat" placeholder="{{Lat. 1}}"/>
									</div>
									<div class="col-sm-6" style="padding-left: 1px !important">
										<input type="text" class="eqLogicAttr form-control blur" data-l1key="configuration" data-l2key="site1_long" placeholder="{{Long. 1}}"/>
									</div>
								</div>
								<div class="col-sm-4 site2" style="padding-left: 0px !important; padding-right: 0px !important">
									<div class="col-sm-6" style="padding-right: 1px !important">
										<input type="text" class="eqLogicAttr form-control blur" data-l1key="configuration" data-l2key="site2_lat" placeholder="{{Lat. 2}}"/>
									</div>
									<div class="col-sm-6" style="padding-left: 1px !important">
										<input type="text" class="eqLogicAttr form-control blur" data-l1key="configuration" data-l2key="site2_long" placeholder="{{Long. 2}}"/>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Distance max (en m)}}</label>
								<div class="col-sm-4 site1">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="site1_limit">
								</div>
								<div class="col-sm-4 site2">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="site2_limit">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label help">{{Récupérer coordonnées GPS}}</label>
								<div class="col-sm-4 site1">
									<a class="btn btn-sm btn-primary eqLogicAction pull-right" data-site="site1" data-action="get_pos" data-src="car"><i class="fas fa-location-arrow"></i> {{Véhicule}}</a>
									<a class="btn btn-sm btn-primary eqLogicAction pull-right" data-site="site1" data-action="get_pos" data-src="jeedom"><i class="fas fa-location-arrow"></i> {{Jeedom}}</a>
								</div>
								<div class="col-sm-4 site2">
									<a class="btn btn-sm btn-primary eqLogicAction pull-right" data-site="site2" data-action="get_pos" data-src="car"><i class="fas fa-location-arrow"></i> {{Véhicule}}</a>
									<a class="btn btn-sm btn-primary eqLogicAction pull-right" data-site="site2" data-action="get_pos" data-src="jeedom"><i class="fas fa-location-arrow"></i> {{Jeedom}}</a>
								</div>
							</div>
						</div>

						<!-- Partie droite de l'onglet "Équipement" -->
						<div class="col-lg-6">
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Description}}</label>
								<div class="col-sm-7">
									<textarea class="form-control eqLogicAttr autogrow" data-l1key="comment"></textarea>
								</div>
							</div>
							<div class='col-sm-12'>
								<img id="img_car" class="img-responsive" style="margin-left:auto;margin-right:auto;margin-top:30px">
							</div>
							<div id="div-get_image" class="col-sm-12 hidden" style="text-align:center;margin-bottom:5px">
								<a id="btn-get_image" class="btn btn-sm btn-primary"><i class="fas fa-image"></i> {{Récupérer une image du véhicule}}</a>
							</div>
							<div class="col-sm-12" style="text-align:center;">
								<a class="btn btn-sm btn-primary eqLogicAction" data-action="get_raw-datas"><i class="fas fa-file-alt"></i> {{Données brutes}}</a>
							</div>
							<div id="drop-area" class="col-sm-12 hidden" contenteditable="true">
								{{Coller l'image ici}}
							</div>
						</div>
					</fieldset>
				</form>
			</div><!-- /.tabpanel #eqlogictab-->

			<!-- Onglet des commandes de l'équipement -->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<div class="input-group pull-right" style="display:inline-flex;">
					<span class="input-group-btn">
						<a class="btn btn-default btn-sm cmdAction hidden" data-action="removeOpen" style="margin-top:5px;"><i class="fas fa-minus-circle"></i> {{Supprimer *open}}</a>
						<a class="btn btn-default btn-sm cmdAction hidden" data-action="removeClosed" style="margin-top:5px;"><i class="fas fa-minus-circle"></i> {{Supprimer *closed}}</a>
						<a class="btn btn-success btn-sm cmdAction" data-action="sort" style="margin-top:5px;"><i class="fas fa-sort-amount-down"></i> {{Trier}}</a>
						<a class="btn btn-success btn-sm cmdAction" data-action="recreate" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Créer commandes manquantes}}</a>
					</span>
				</div>
				<br><br>
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
								<th style="min-width:200px;width:350px;">{{Nom}}</th>
								<th style="width:130px">{{Type}}</th>
								<th style="width:130px">LogicalId</th>
								<th>{{paramètres}}</th>
								<th style="width:270px;">{{Options}}</th>
								<th style="width:150px">{{Etat}}</th>
								<th style="min-width:80px;width:200px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->

		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->
</div><!-- /.row row-overflow -->

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'volvocars', 'js', 'volvocars'); ?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js'); ?>
