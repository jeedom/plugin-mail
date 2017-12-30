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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('3rdparty', 'PHPMailer/PHPMailerAutoload', 'php','mail');

class mail extends eqLogic {

}

class mailCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function preSave() {
		$this->setType('action');
		$this->setSubType('message');
		if ($this->getConfiguration('recipient') == '') {
			throw new Exception(__('L\'adresse mail ne peut etre vide', __FILE__));
		}
		$bValid = true;
		foreach(explode(',', $this->getConfiguration('recipient')) AS $sEmailAddress){
			$bValid = ($bValid && filter_var(trim($sEmailAddress), FILTER_VALIDATE_EMAIL));
		}
		if ($bValid == false) {
			throw new Exception(__('L\'adresse mail n\'est pas valide', __FILE__));
		}
	}

	public function execute($_options = null) {
		$eqLogic = $this->getEqLogic();
		if ($_options === null) {
			throw new Exception(__('[Mail] Les options de la fonction ne peuvent etre null', __FILE__));
		}

		if ($_options['message'] == '' && $_options['title'] == '') {
			throw new Exception(__('[Mail] Le message et le sujet ne peuvent être vide', __FILE__));
			return false;
		}

		if ($_options['title'] == '') {
			$_options['title'] = __('[Jeedom] - Notification', __FILE__);
		}

		$mail = new PHPMailer(true); //PHPMailer instance with exceptions enabled
		$mail->CharSet = 'utf-8';
		$mail->SMTPDebug = 0;
		switch ($eqLogic->getConfiguration('sendMode', 'mail')) {
			case 'smtp':
				$mail->isSMTP();
				$mail->Host = $eqLogic->getConfiguration('smtp::server');
				$mail->Port = (integer) $eqLogic->getConfiguration('smtp::port');
				if ($eqLogic->getConfiguration('smtp::security', '') != '' && $eqLogic->getConfiguration('smtp::security', '') != 'none') {
					$mail->SMTPSecure = $eqLogic->getConfiguration('smtp::security', '');
				}
				if ($eqLogic->getConfiguration('smtp::username') != '') {
					$mail->SMTPAuth = true;
					$mail->Username = $eqLogic->getConfiguration('smtp::username'); // SMTP account username
					$mail->Password = $eqLogic->getConfiguration('smtp::password'); // SMTP account password
				}
				if ($eqLogic->getConfiguration('smtp::dontcheckssl', 0) == 1) {
					$mail->SMTPOptions = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true,
						),
					);
				}
				break;
			case 'mail':
				$mail->isMail();
				break;
			case 'sendmail':
				$mail->isSendmail();
				break;
			case 'qmail':
				$mail->isQmail();
				break;
			default:
				throw new Exception(__('Mode d\'envoi non reconnu', __FILE__));
		}
		if ($eqLogic->getConfiguration('fromName') != '') {
			$mail->addReplyTo($eqLogic->getConfiguration('fromMail'), $eqLogic->getConfiguration('fromName'));
			$mail->FromName = $eqLogic->getConfiguration('fromName');
		} else {
			$mail->addReplyTo($eqLogic->getConfiguration('fromMail'));
			$mail->FromName = $eqLogic->getConfiguration('fromMail');
		}
		$mail->From = $eqLogic->getConfiguration('fromMail');
		$mail->isHTML(true);
		foreach(explode(',', $this->getConfiguration('recipient')) AS $sEmailAddress){
			$mail->AddAddress(trim($sEmailAddress));
		}
		$mail->Subject = $_options['title'];
		$mail->Body = nl2br($_options['message']);
		$mail->AltBody = nl2br($_options['message']);
		if (isset($_options['files']))
			if (is_array($_options['files'])) {
				foreach ($_options['files'] as $file) {
					$mail->addAttachment($file);
				}
			} else {
				$mail->addAttachment($_options['files']);
			}
		}
		return $mail->send();
	}

	/*     * **********************Getteur Setteur*************************** */
}
