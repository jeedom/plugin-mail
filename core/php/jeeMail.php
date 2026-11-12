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

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

if (!jeedom::apiAccess(init('apikey'), 'mail')) {
	echo __('Vous n\'êtes pas autorisé à effectuer cette action', __FILE__);
	die();
}

$result = json_decode(file_get_contents('php://input'), true);
if (!is_array($result)) {
	die();
}

if (isset($result['token'])) {
	// Synchronous request from the daemon: answer with an OAuth access token
	$eqLogic = mail::byId($result['token']['eqLogic_id']);
	if (!is_object($eqLogic) || $eqLogic->getEqType_name() != 'mail') {
		echo json_encode(array('error' => 'Unknown equipment'));
		die();
	}
	try {
		echo json_encode($eqLogic->getOAuthToken());
	} catch (Exception $e) {
		echo json_encode(array('error' => $e->getMessage()));
	}
	die();
}
if (isset($result['status'])) {
	mail::receiveStatus($result['status']);
}
if (isset($result['mail'])) {
	mail::receive($result['mail']);
}
