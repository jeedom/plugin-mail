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
require_once dirname(__FILE__) . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

#require_once dirname(__FILE__) . '/../../3rdparty/PHPMailer/src/Exception.php';
#require_once dirname(__FILE__) . '/../../3rdparty/PHPMailer/src/PHPMailer.php';
#require_once dirname(__FILE__) . '/../../3rdparty/PHPMailer/src/SMTP.php';


class mail extends eqLogic {

	public function postSave() {
		$customEmail = $this->getCmd(null, 'send_to_custom_email');
		if (!is_object($customEmail)) {
			$customEmail = new mailCmd();
			$customEmail->setEqLogic_id($this->getId());
			$customEmail->setLogicalId('send_to_custom_email');
			$customEmail->setIsVisible(0);
			$customEmail->setName(__('Envoyer email à', __FILE__));
			$customEmail->setType('action');
			$customEmail->setSubType('message');
			$customEmail->setDisplay('cmd_with_recipient', '1');
			$customEmail->save();
		}
	}
}

class mailCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function preSave() {
		if ($this->getLogicalId() == 'send_to_custom_email') return;

		$this->setType('action');
		$this->setSubType('message');
		if ($this->getConfiguration('recipient') == '') {
			throw new Exception(__('L\'adresse mail ne peut être vide', __FILE__));
		}
		$bValid = true;
		foreach (explode(',', $this->getConfiguration('recipient')) as $sEmailAddress) {
			$bValid = ($bValid && filter_var(trim($sEmailAddress), FILTER_VALIDATE_EMAIL));
		}
		if ($bValid == false) {
			throw new Exception(__('L\'adresse mail n\'est pas valide', __FILE__));
		}
	}

	public function getWidgetTemplateCode($_version = 'dashboard', $_clean = true, $_widgetName = '') {

		if ($_version != 'scenario') return parent::getWidgetTemplateCode($_version, $_clean, $_widgetName);

		if ($this->getDisplay('cmd_with_recipient', '') != 1) return parent::getWidgetTemplateCode($_version, $_clean, $_widgetName);

		$template = getTemplate('core', $_version, 'cmd.action.message_with_recipient', 'mail');

		if (!empty($template)) {
			if (version_compare(jeedom::version(), '4.2.0', '>=')) {
				if (!is_array($template)) return array('template' => $template, 'isCoreWidget' => false);
			} else {
				return $template;
			}
		}
		return parent::getWidgetTemplateCode($_version, $_clean, $_widgetName);
	}

	public function execute($_options = null) {
		$eqLogic = $this->getEqLogic();
		if ($_options === null) {
			throw new Exception(__('[Mail] Les options de la fonction ne peuvent être null', __FILE__));
		}

		if ($_options['message'] == '' && $_options['title'] == '') {
			throw new Exception(__('[Mail] Le message et le sujet ne peuvent être vides', __FILE__));
			return false;
		}

		if ($_options['title'] == '') {
			$_options['title'] = __('[Jeedom] - Notification', __FILE__);
		}
		if ($eqLogic->getConfiguration('sendMode', 'mail') == 'jeedomCloud') {
			$data = array(
				'to' => $_options['recipient'] ?? $this->getConfiguration('recipient'),
				'subject' => $_options['title'],
				'text' => $_options['message'],
				'html' => $_options['message']
			);
			if (isset($_options['files']) && is_array($_options['files'])) {
				$data['attachments'] = array();
				foreach ($_options['files'] as $file) {
					$data['attachments'][] = array(
						'filename' => basename($file),
						'content' => base64_encode(file_get_contents($file))
					);
				}
			}
			$url = config::byKey('service::cloud::url') . '/service/mail';
			$request_http = new com_http($url);
			$request_http->setHeader(array('Content-Type: application/json', 'Autorization: ' . sha512(mb_strtolower(config::byKey('market::username')) . ':' . config::byKey('market::password'))));
			$request_http->setPost(json_encode($data));
			$datas = json_decode($request_http->exec(30, 1), true);
			if ($datas['state'] != 'ok') {
				throw new \Exception(__('Erreur sur l\'envoi du mail : ', __FILE__) . json_encode($datas));
			}
			return;
		}

		$mail = new PHPMailer(true); //PHPMailer instance with exceptions enabled
		$mail->CharSet = 'utf-8';
		$mail->SMTPDebug = 0;
		switch ($eqLogic->getConfiguration('sendMode', 'mail')) {
			case 'smtp':
				$mail->isSMTP();
				$mail->Host = $eqLogic->getConfiguration('smtp::server');
				$mail->Port = (int) $eqLogic->getConfiguration('smtp::port');
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
		$recipientsArr =  $_options['recipient'] ?? $this->getConfiguration('recipient');
		foreach (explode(',', $recipientsArr) as $sEmailAddress) {
			$mail->AddAddress(trim($sEmailAddress));
		}
		$mail->Subject = $_options['title'];
		$mail->Body = nl2br($_options['message']);
		$mail->AltBody = nl2br($_options['message']);
		if (isset($_options['files']) && is_array($_options['files'])) {
			foreach ($_options['files'] as $file) {
				$mail->addAttachment($file);
			}
		}
		return $mail->send();
	}

	/*     * **********************Getteur Setteur*************************** */
}
