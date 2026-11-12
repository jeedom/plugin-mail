<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('mail');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br />
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br />
				<span>{{Configuration}}</span>
			</div>
		</div>
		<legend><i class="fas fa-envelope"></i> {{Mes mails}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
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
	</div>

	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}
				</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#receivetab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-inbox"></i> {{Réception}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<form class="form-horizontal">
					<fieldset>
						<div class="col-sm-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}" />
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
								<div class="col-sm-8">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label"></label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
								</div>
							</div>

							<legend><i class="fas fa-cogs"></i> {{Paramètres spécifiques}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Mode d'envoi}}</label>
								<div class="col-sm-6">
									<select class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='sendMode'>
										<option value='smtp'>SMTP</option>
										<option value='sendmail'>Sendmail</option>
										<option value='qmail'>Qmail</option>
										<option value='mail'>Mail() [PHP fonction]</option>
										<option value='jeedomCloud'>Jeedom cloud</option>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Authentification}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Utilisée pour l'envoi SMTP et pour la réception IMAP. Google et Microsoft 365 imposent OAuth2}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="oauth::provider">
										<option value="">{{Mot de passe}}</option>
										<option value="google">Google (OAuth2)</option>
										<option value="microsoft">Microsoft 365 / Outlook (OAuth2)</option>
									</select>
								</div>
							</div>
							<div class="form-group oauthConfig" style="display:none;">
								<label class="col-sm-4 control-label">{{Compte}}</label>
								<div class="col-sm-6">
									<span id="span_oauthAccount" class="label label-default"></span>
									<a class="btn btn-sm btn-success" id="bt_oauthConnect"><i class="fas fa-link"></i> {{Connecter le compte}}</a>
									<a class="btn btn-sm btn-danger" id="bt_oauthDisconnect"><i class="fas fa-unlink"></i> {{Déconnecter}}</a>
								</div>
							</div>
							<div class="form-group" id="div_oauthGoogle" style="display:none;">
								<div class="col-sm-offset-4 col-sm-6">
									<div class="alert alert-info">{{Autorisez l'accès dans la fenêtre Google qui vient de s'ouvrir. Votre navigateur affichera ensuite une page en erreur : copiez l'adresse complète de cette page (elle commence par http://127.0.0.1:47823/) et collez-la ci-dessous.}}</div>
									<div class="input-group">
										<input type="text" class="form-control roundedLeft" id="in_oauthGoogleUrl" placeholder="http://127.0.0.1:47823/?state=...&code=..." />
										<span class="input-group-btn">
											<a class="btn btn-success roundedRight" id="bt_oauthGoogleFinish"><i class="fas fa-check"></i> {{Valider}}</a>
										</span>
									</div>
								</div>
							</div>
							<div class="form-group" id="div_oauthMicrosoft" style="display:none;">
								<div class="col-sm-offset-4 col-sm-6">
									<div class="alert alert-info">
										{{Ouvrez}} <a id="a_oauthMicrosoftUri" target="_blank" rel="noopener"></a> {{et saisissez le code}} <strong id="span_oauthMicrosoftCode" style="font-size:1.3em;"></strong>
										<br /><i class="fas fa-spinner fa-spin"></i> {{En attente de l'autorisation...}}
									</div>
								</div>
							</div>
							<div class="form-group sendMode mail qmail sendmail smtp">
								<label class="col-sm-4 control-label">{{Nom expéditeur}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='fromName' />
								</div>
							</div>
							<div class="form-group sendMode mail qmail sendmail smtp">
								<label class="col-sm-4 control-label">{{Mail expéditeur}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='fromMail' />
								</div>
							</div>
						</div>

						<div class="col-sm-6">
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
							<div class='sendMode jeedomCloud' style="display: none;">
								<div class="alert alert-danger">{{Attention il y a une limite de 1 mails par jour (cette limite est succeptible de varier à l'avenir)}}</div>
							</div>
							<div class='sendMode sendmail' style="display: none;">
								<div class="alert alert-danger">{{Attention cette option nécessite d'avoir correctement configuré le système (OS).}}</div>
							</div>
							<div class='sendMode mail' style="display: none;">
								<div class="alert alert-danger">{{Attention cette option nécessite d'avoir correctement configuré le système (OS).}}</div>
							</div>
							<div class='sendMode qmail' style="display: none;">
								<div class="alert alert-danger">{{Attention cette option nécessite d'avoir correctement configuré le système (OS).}}</div>
							</div>
							<div class='sendMode smtp' style="display: none;">
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Serveur SMTP}}</label>
									<div class="col-sm-8">
										<input type="text" class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='smtp::server' />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Port SMTP}}</label>
									<div class="col-sm-8">
										<input type="text" class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='smtp::port' />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Sécurité SMTP}}</label>
									<div class="col-sm-8">
										<select class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='smtp::security'>
											<option value=''>{{Aucune}}</option>
											<option value='tls'>TLS</option>
											<option value='ssl'>SSL</option>
										</select>
									</div>
								</div>
								<div class="form-group passwordAuth">
									<label class="col-sm-2 control-label">{{Utilisateur SMTP}}</label>
									<div class="col-sm-8">
										<input type="text" class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='smtp::username' />
									</div>
								</div>
								<div class="form-group passwordAuth">
									<label class="col-sm-2 control-label">{{Mot de passe SMTP}}</label>
									<div class="col-sm-8">
										<input type="password" class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='smtp::password' />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label"></label>
									<div class="col-sm-6">
										<label class="control-label"><input type="checkbox" class="eqLogicAttr" data-l1key='configuration' data-l2key='smtp::dontcheckssl' />{{Ne pas vérifier le certificat SSL}}</label>
									</div>
								</div>
							</div>

						</div>
					</fieldset>
				</form>
			</div>
			<div role="tabpanel" class="tab-pane" id="receivetab">
				<form class="form-horizontal">
					<fieldset>
						<div class="col-sm-6">
							<legend><i class="fas fa-inbox"></i> {{Réception des mails (IMAP)}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Activer la réception}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Surveille une boîte mail et met à jour les commandes info de l'équipement à chaque nouveau mail}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="imap::enable" />
								</div>
							</div>
							<div class="imapConfig">
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Serveur IMAP}}</label>
									<div class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="imap::server" placeholder="imap.exemple.com" />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Sécurité IMAP}}</label>
									<div class="col-sm-6">
										<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="imap::security">
											<option value="ssl">SSL/TLS</option>
											<option value="starttls">STARTTLS</option>
											<option value="none">{{Aucune}}</option>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Port IMAP}}</label>
									<div class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="imap::port" placeholder="993" />
									</div>
								</div>
								<div class="form-group passwordAuth">
									<label class="col-sm-4 control-label">{{Utilisateur IMAP}}</label>
									<div class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="imap::username" autocomplete="off" />
									</div>
								</div>
								<div class="form-group passwordAuth">
									<label class="col-sm-4 control-label">{{Mot de passe IMAP}}</label>
									<div class="col-sm-6">
										<input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="imap::password" autocomplete="new-password" />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label"></label>
									<div class="col-sm-6">
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="imap::dontcheckssl" />{{Ne pas vérifier le certificat SSL}}</label>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Dossier surveillé}}</label>
									<div class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="imap::folder" placeholder="INBOX" />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Marquer comme lu}}
										<sup><i class="fas fa-question-circle tooltips" title="{{Marque les mails traités comme lus sur le serveur}}"></i></sup>
									</label>
									<div class="col-sm-6">
										<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="imap::markSeen" />
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-6 imapConfig">
							<legend><i class="fas fa-filter"></i> {{Filtres}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Expéditeurs}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Ne traiter que les mails dont l'adresse de l'expéditeur contient l'une de ces valeurs (séparées par des virgules). Vide pour tout accepter}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="imap::filter::from" placeholder="alarme@exemple.com, @domaine.fr" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Sujet contient}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Ne traiter que les mails dont le sujet contient ce texte. Vide pour tout accepter}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="imap::filter::subject" />
								</div>
							</div>
							<legend><i class="fas fa-paperclip"></i> {{Pièces jointes}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Enregistrer les pièces jointes}}</label>
								<div class="col-sm-6">
									<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="imap::attachments" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Conservation (jours)}}</label>
								<div class="col-sm-6">
									<input type="number" min="1" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="imap::attachments::retention" placeholder="7" />
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande mail}}</a>
				<br /><br />
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th style="min-width:200px;width:350px;">{{Nom}}</th>
							<th>{{Email}}</th>
							<th style="width:130px;">{{Actions}}</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<?php
include_file('desktop', 'mail', 'js', 'mail');
include_file('core', 'plugin.template', 'js');
?>
