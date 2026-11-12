<?php
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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
	include_file('desktop', '404', 'php');
	die();
}
?>
<form class="form-horizontal">
	<fieldset>
		<legend><i class="fas fa-inbox"></i> {{Démon de réception}}</legend>
		<div class="form-group">
			<label class="col-md-4 control-label">{{Port socket interne}}
				<sup><i class="fas fa-question-circle tooltips" title="{{Port local utilisé par le démon de réception des mails (55072 par défaut)}}"></i></sup>
			</label>
			<div class="col-md-4">
				<input class="configKey form-control" data-l1key="socketport" placeholder="55072" />
			</div>
		</div>
		<legend><i class="fab fa-google"></i> {{Application OAuth2 Google}}</legend>
		<div class="form-group">
			<label class="col-md-4 control-label">{{ID client}}
				<sup><i class="fas fa-question-circle tooltips" title="{{Identifiant d'un client OAuth de type Application de bureau, créé dans la console Google Cloud}}"></i></sup>
			</label>
			<div class="col-md-4">
				<input class="configKey form-control" data-l1key="oauth::google::client_id" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-4 control-label">{{Code secret du client}}</label>
			<div class="col-md-4">
				<input type="password" class="configKey form-control" data-l1key="oauth::google::client_secret" autocomplete="new-password" />
			</div>
		</div>
		<legend><i class="fab fa-microsoft"></i> {{Application OAuth2 Microsoft}}</legend>
		<div class="form-group">
			<label class="col-md-4 control-label">{{ID d'application (client)}}
				<sup><i class="fas fa-question-circle tooltips" title="{{Application enregistrée dans Microsoft Entra ID avec les flux de client public autorisés}}"></i></sup>
			</label>
			<div class="col-md-4">
				<input class="configKey form-control" data-l1key="oauth::microsoft::client_id" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-4 control-label">{{Locataire (tenant)}}
				<sup><i class="fas fa-question-circle tooltips" title="{{common par défaut. Indiquez l'ID du locataire si l'application n'accepte que les comptes de votre organisation}}"></i></sup>
			</label>
			<div class="col-md-4">
				<input class="configKey form-control" data-l1key="oauth::microsoft::tenant" placeholder="common" />
			</div>
		</div>
	</fieldset>
</form>
