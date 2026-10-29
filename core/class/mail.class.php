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
if(file_exists(dirname(__FILE__) . '/../../vendor/autoload.php')){
	require_once dirname(__FILE__) . '/../../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

#require_once dirname(__FILE__) . '/../../3rdparty/PHPMailer/src/Exception.php';
#require_once dirname(__FILE__) . '/../../3rdparty/PHPMailer/src/PHPMailer.php';
#require_once dirname(__FILE__) . '/../../3rdparty/PHPMailer/src/SMTP.php';


class mail extends eqLogic {
	/*     * ***********************Methode static*************************** */

	public static function cronDaily() {
		foreach (eqLogic::byType(__CLASS__) as $eqLogic) {
			$path = self::getAttachmentsPath($eqLogic->getId());
			if (!is_dir($path)) {
				continue;
			}
			$days = max(1, intval($eqLogic->getConfiguration('imap::attachments::retention', 7)));
			exec('find ' . escapeshellarg($path) . ' -type f -mtime +' . $days . ' -delete');
			exec('find ' . escapeshellarg($path) . ' -mindepth 1 -type d -empty -delete');
		}
	}

	public static function getDataPath() {
		$path = __DIR__ . '/../../data';
		if (!is_dir($path)) {
			mkdir($path, 0775, true);
		}
		if (!file_exists($path . '/.htaccess')) {
			// Received attachments must not be reachable from the web
			file_put_contents($path . '/.htaccess', "Order allow,deny\nDeny from all\n");
		}
		return realpath($path);
	}

	public static function getAttachmentsPath($_eqLogic_id) {
		return self::getDataPath() . '/attachments/' . $_eqLogic_id;
	}

	public static function getReceptionCommands() {
		// Ordered: values are updated in this order when a mail is received, subject last
		return array(
			'receive::date' => __('Date de réception', __FILE__),
			'receive::from' => __('Expéditeur', __FILE__),
			'receive::fromName' => __('Nom expéditeur', __FILE__),
			'receive::to' => __('Destinataire', __FILE__),
			'receive::html' => __('HTML', __FILE__),
			'receive::body' => __('Texte', __FILE__),
			'receive::attachments' => __('Pièces jointes', __FILE__),
			'receive::subject' => __('Sujet', __FILE__),
		);
	}

	public static function getListeners() {
		$return = array();
		foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {
			if ($eqLogic->getConfiguration('imap::enable', 0) == 1 && $eqLogic->getConfiguration('imap::server') != '') {
				$return[] = $eqLogic;
			}
		}
		return $return;
	}

	public static function deamon_info() {
		$return = array();
		$return['log'] = __CLASS__ . '_daemon';
		$return['launchable'] = 'ok';
		$return['state'] = self::isDaemonRunning() ? 'ok' : 'nok';
		if (count(self::getListeners()) == 0) {
			// The daemon is only needed for mail reception
			$return['state'] = 'ok';
			$return['launchable_message'] = __('Aucun équipement en réception', __FILE__);
		}
		return $return;
	}

	public static function isDaemonRunning() {
		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
		if (!file_exists($pid_file)) {
			return false;
		}
		if (@posix_getsid(intval(trim(file_get_contents($pid_file))))) {
			return true;
		}
		shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
		return false;
	}

	public static function deamon_start() {
		self::deamon_stop();
		if (count(self::getListeners()) == 0) {
			log::add(__CLASS__, 'info', __('Aucun équipement en réception, le démon n\'est pas lancé', __FILE__));
			return true;
		}
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$python = method_exists('system', 'getCmdPython3') ? trim(system::getCmdPython3(__CLASS__)) : 'python3';
		$cmd = $python . ' ' . realpath(__DIR__ . '/../../resources/maild') . '/maild.py';
		$cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
		$cmd .= ' --socketport ' . intval(config::byKey('socketport', __CLASS__, 55072));
		$cmd .= ' --callback ' . escapeshellarg(network::getNetworkAccess('internal', 'http:127.0.0.1:port:comp') . '/plugins/mail/core/php/jeeMail.php');
		$cmd .= ' --apikey ' . escapeshellarg(jeedom::getApiKey(__CLASS__));
		$cmd .= ' --pid ' . escapeshellarg(jeedom::getTmpFolder(__CLASS__) . '/deamon.pid');
		$cmd .= ' --state ' . escapeshellarg(self::getDataPath() . '/state.json');
		log::add(__CLASS__, 'info', __('Lancement du démon de réception', __FILE__));
		exec($cmd . ' >> ' . log::getPathToLog(__CLASS__ . '_daemon') . ' 2>&1 &');
		$i = 0;
		while ($i < 30) {
			if (self::isDaemonRunning() && self::sendSync()) {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 30) {
			log::add(__CLASS__, 'error', __('Impossible de lancer le démon de réception, vérifiez le log', __FILE__), 'unableStartDeamon');
			return false;
		}
		message::removeAll(__CLASS__, 'unableStartDeamon');
		return true;
	}

	public static function deamon_stop() {
		$pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
		if (file_exists($pid_file)) {
			system::kill(intval(trim(file_get_contents($pid_file))));
		}
		system::kill('maild.py');
		sleep(1);
	}

	public static function sendToDaemon($_params) {
		$_params['apikey'] = jeedom::getApiKey(__CLASS__);
		$socket = @stream_socket_client('tcp://127.0.0.1:' . intval(config::byKey('socketport', __CLASS__, 55072)), $errno, $errstr, 5);
		if ($socket === false) {
			return false;
		}
		fwrite($socket, json_encode($_params) . "\n");
		fclose($socket);
		return true;
	}

	public static function sendSync() {
		$accounts = array();
		foreach (self::getListeners() as $eqLogic) {
			$accounts[] = $eqLogic->getDaemonConfig();
		}
		return self::sendToDaemon(array('action' => 'sync', 'accounts' => $accounts));
	}

	public static function syncDaemon() {
		if (count(self::getListeners()) == 0) {
			if (self::isDaemonRunning()) {
				self::deamon_stop();
			}
			return;
		}
		if (!self::isDaemonRunning()) {
			if (config::byKey('deamonAutoMode', __CLASS__, 1) == 1) {
				self::deamon_start();
			}
			return;
		}
		if (!self::sendSync()) {
			log::add(__CLASS__, 'error', __('Impossible de transmettre la configuration au démon de réception', __FILE__));
		}
	}

	public static function receive($_mail) {
		$eqLogic = self::byId($_mail['eqLogic_id']);
		if (!is_object($eqLogic) || $eqLogic->getEqType_name() != __CLASS__) {
			return;
		}
		log::add(__CLASS__, 'info', $eqLogic->getHumanName() . ' ' . __('Mail reçu de', __FILE__) . ' ' . $_mail['from'] . ' : ' . $_mail['subject']);
		$_mail['date'] = date('Y-m-d H:i:s', isset($_mail['timestamp']) ? intval($_mail['timestamp']) : time());
		foreach (array_keys(self::getReceptionCommands()) as $logicalId) {
			$cmd = $eqLogic->getCmd('info', $logicalId);
			if (!is_object($cmd)) {
				continue;
			}
			$key = substr($logicalId, strlen('receive::'));
			$value = $_mail[$key] ?? '';
			if (is_array($value)) {
				$value = implode(',', $value);
			}
			$cmd->event($value);
		}
	}

	public static function receiveStatus($_status) {
		$eqLogic = self::byId($_status['eqLogic_id']);
		if (!is_object($eqLogic) || $eqLogic->getEqType_name() != __CLASS__) {
			return;
		}
		$previous = $eqLogic->getCache('imap::status');
		$eqLogic->setCache(array('imap::status' => $_status['state'], 'imap::message' => $_status['message']));
		if ($_status['state'] == 'error' && $previous != 'error') {
			log::add(__CLASS__, 'error', $eqLogic->getHumanName() . ' ' . __('Erreur de réception', __FILE__) . ' : ' . $_status['message']);
		} elseif ($_status['state'] == 'ok' && $previous != 'ok') {
			log::add(__CLASS__, 'info', $eqLogic->getHumanName() . ' ' . __('Connecté au serveur de réception', __FILE__));
		}
	}

	public static function getOAuthProvider($_provider) {
		switch ($_provider) {
			case 'google':
				$provider = array(
					'name' => 'Google',
					'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
					'token_url' => 'https://oauth2.googleapis.com/token',
					// Desktop application client: Google accepts any loopback redirect without registration
					'redirect_uri' => 'http://127.0.0.1:47823/',
					'scope' => 'openid email https://mail.google.com/',
					'client_id' => trim(config::byKey('oauth::google::client_id', __CLASS__)),
					'client_secret' => trim(config::byKey('oauth::google::client_secret', __CLASS__)),
				);
				break;
			case 'microsoft':
				$tenant = trim(config::byKey('oauth::microsoft::tenant', __CLASS__, 'common'));
				if ($tenant == '') {
					$tenant = 'common';
				}
				$provider = array(
					'name' => 'Microsoft',
					'device_url' => 'https://login.microsoftonline.com/' . rawurlencode($tenant) . '/oauth2/v2.0/devicecode',
					'token_url' => 'https://login.microsoftonline.com/' . rawurlencode($tenant) . '/oauth2/v2.0/token',
					'scope' => 'openid email offline_access https://outlook.office.com/IMAP.AccessAsUser.All https://outlook.office.com/SMTP.Send',
					'client_id' => trim(config::byKey('oauth::microsoft::client_id', __CLASS__)),
					'client_secret' => '',
				);
				break;
			default:
				throw new Exception(__('Fournisseur OAuth inconnu', __FILE__) . ' : ' . $_provider);
		}
		if ($provider['client_id'] == '') {
			throw new Exception(__('Veuillez renseigner l\'identifiant client OAuth', __FILE__) . ' ' . $provider['name'] . ' ' . __('dans la configuration du plugin', __FILE__));
		}
		return $provider;
	}

	private static function oauthRequest($_url, $_params) {
		$ch = curl_init($_url);
		curl_setopt_array($ch, array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query($_params),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTPHEADER => array('Accept: application/json'),
		));
		$response = curl_exec($ch);
		if ($response === false) {
			$error = curl_error($ch);
			curl_close($ch);
			throw new Exception(__('Erreur de connexion au serveur OAuth', __FILE__) . ' : ' . $error);
		}
		curl_close($ch);
		$data = json_decode($response, true);
		return is_array($data) ? $data : array();
	}

	private static function oauthError($_data) {
		if (isset($_data['error_description'])) {
			return $_data['error_description'];
		}
		return isset($_data['error']) ? $_data['error'] : __('réponse invalide', __FILE__);
	}

	private static function base64url($_data) {
		return rtrim(strtr(base64_encode($_data), '+/', '-_'), '=');
	}

	/*     * *********************Methode d'instance************************* */

	public function decrypt() {
		$this->setConfiguration('imap::password', utils::decrypt($this->getConfiguration('imap::password')));
		$this->setConfiguration('oauth::refresh_token', utils::decrypt($this->getConfiguration('oauth::refresh_token')));
	}

	public function encrypt() {
		$this->setConfiguration('imap::password', utils::encrypt($this->getConfiguration('imap::password')));
		$this->setConfiguration('oauth::refresh_token', utils::encrypt($this->getConfiguration('oauth::refresh_token')));
	}

	public function getOAuthUser() {
		if ($this->getConfiguration('oauth::account') != '') {
			return $this->getConfiguration('oauth::account');
		}
		if ($this->getConfiguration('imap::username') != '') {
			return $this->getConfiguration('imap::username');
		}
		return $this->getConfiguration('smtp::username');
	}

	/**
	 * Return a valid access token, refreshing it if needed.
	 * Shared by SMTP sending and by the reception daemon.
	 */
	public function getOAuthToken() {
		$provider = self::getOAuthProvider($this->getConfiguration('oauth::provider'));
		if ($this->getCache('oauth::expires', 0) > time() + 300) {
			$token = utils::decrypt($this->getCache('oauth::access_token'));
			if ($token != '') {
				return array('user' => $this->getOAuthUser(), 'access_token' => $token);
			}
		}
		if ($this->getConfiguration('oauth::refresh_token') == '') {
			throw new Exception(__('Le compte', __FILE__) . ' ' . $provider['name'] . ' ' . __('n\'est pas connecté', __FILE__));
		}
		$params = array(
			'client_id' => $provider['client_id'],
			'grant_type' => 'refresh_token',
			'refresh_token' => $this->getConfiguration('oauth::refresh_token'),
		);
		if ($this->getConfiguration('oauth::provider') == 'microsoft') {
			$params['scope'] = $provider['scope'];
		}
		if ($provider['client_secret'] != '') {
			$params['client_secret'] = $provider['client_secret'];
		}
		$data = self::oauthRequest($provider['token_url'], $params);
		if (!isset($data['access_token'])) {
			throw new Exception(__('Impossible de renouveler le jeton', __FILE__) . ' ' . $provider['name'] . ' : ' . self::oauthError($data));
		}
		$this->storeOAuthToken($data);
		if (isset($data['refresh_token']) && $data['refresh_token'] != $this->getConfiguration('oauth::refresh_token')) {
			// Microsoft rotates refresh tokens
			$this->setConfiguration('oauth::refresh_token', $data['refresh_token']);
			$this->save(true);
		}
		return array('user' => $this->getOAuthUser(), 'access_token' => $data['access_token']);
	}

	private function storeOAuthToken($_data) {
		$this->setCache(array(
			'oauth::access_token' => utils::encrypt($_data['access_token']),
			'oauth::expires' => time() + (isset($_data['expires_in']) ? intval($_data['expires_in']) : 3600),
		));
	}

	public function oauthStart($_provider) {
		$provider = self::getOAuthProvider($_provider);
		if ($_provider == 'google') {
			$verifier = self::base64url(random_bytes(32));
			$state = bin2hex(random_bytes(16));
			$this->setCache('oauth::pending', json_encode(array('provider' => $_provider, 'verifier' => $verifier, 'state' => $state, 'expires' => time() + 900)));
			$url = $provider['auth_url'] . '?' . http_build_query(array(
				'client_id' => $provider['client_id'],
				'redirect_uri' => $provider['redirect_uri'],
				'response_type' => 'code',
				'scope' => $provider['scope'],
				'access_type' => 'offline',
				'prompt' => 'consent',
				'state' => $state,
				'code_challenge' => self::base64url(hash('sha256', $verifier, true)),
				'code_challenge_method' => 'S256',
			));
			return array('type' => 'redirect', 'url' => $url);
		}
		$data = self::oauthRequest($provider['device_url'], array('client_id' => $provider['client_id'], 'scope' => $provider['scope']));
		if (!isset($data['device_code'])) {
			throw new Exception(__('Erreur OAuth', __FILE__) . ' ' . $provider['name'] . ' : ' . self::oauthError($data));
		}
		$interval = isset($data['interval']) ? intval($data['interval']) : 5;
		$this->setCache('oauth::pending', json_encode(array('provider' => $_provider, 'device_code' => $data['device_code'], 'interval' => $interval, 'expires' => time() + intval($data['expires_in']))));
		return array('type' => 'device', 'user_code' => $data['user_code'], 'verification_uri' => $data['verification_uri'], 'interval' => $interval);
	}

	private function getOAuthPending($_provider) {
		$pending = json_decode($this->getCache('oauth::pending', ''), true);
		if (!is_array($pending) || $pending['provider'] != $_provider || $pending['expires'] < time()) {
			throw new Exception(__('Aucune connexion en cours ou délai dépassé, veuillez recommencer', __FILE__));
		}
		return $pending;
	}

	/**
	 * Google: the user pastes the address of the page Google redirected to (http://127.0.0.1:47823/?code=...)
	 */
	public function oauthFinish($_url) {
		$pending = $this->getOAuthPending('google');
		$provider = self::getOAuthProvider('google');
		parse_str((string) parse_url(trim($_url), PHP_URL_QUERY), $query);
		if (isset($query['error'])) {
			throw new Exception(__('Autorisation refusée', __FILE__) . ' : ' . $query['error']);
		}
		if (!isset($query['code']) || !isset($query['state']) || !hash_equals($pending['state'], $query['state'])) {
			throw new Exception(__('Adresse invalide, copiez l\'adresse complète de la page affichée après l\'autorisation', __FILE__));
		}
		$data = self::oauthRequest($provider['token_url'], array(
			'client_id' => $provider['client_id'],
			'client_secret' => $provider['client_secret'],
			'grant_type' => 'authorization_code',
			'code' => $query['code'],
			'redirect_uri' => $provider['redirect_uri'],
			'code_verifier' => $pending['verifier'],
		));
		return $this->oauthConnected('google', $data);
	}

	/**
	 * Microsoft: called periodically while the user enters the code on microsoft.com/devicelogin
	 */
	public function oauthPoll() {
		$pending = $this->getOAuthPending('microsoft');
		$provider = self::getOAuthProvider('microsoft');
		$data = self::oauthRequest($provider['token_url'], array(
			'client_id' => $provider['client_id'],
			'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
			'device_code' => $pending['device_code'],
		));
		if (isset($data['error']) && in_array($data['error'], array('authorization_pending', 'slow_down'))) {
			return array('state' => 'pending');
		}
		return $this->oauthConnected('microsoft', $data);
	}

	private function oauthConnected($_provider, $_data) {
		$this->setCache('oauth::pending', '');
		if (!isset($_data['refresh_token'])) {
			throw new Exception(__('Erreur OAuth', __FILE__) . ' : ' . self::oauthError($_data));
		}
		$account = '';
		if (isset($_data['id_token'])) {
			$parts = explode('.', $_data['id_token']);
			$claims = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
			if (isset($claims['email'])) {
				$account = $claims['email'];
			} elseif (isset($claims['preferred_username'])) {
				$account = $claims['preferred_username'];
			}
		}
		$this->setConfiguration('oauth::provider', $_provider);
		$this->setConfiguration('oauth::refresh_token', $_data['refresh_token']);
		$this->setConfiguration('oauth::account', $account);
		// Changes the daemon configuration so that the reception reconnects with the new token
		$this->setConfiguration('oauth::connectedAt', time());
		$this->storeOAuthToken($_data);
		$this->save();
		log::add(__CLASS__, 'info', $this->getHumanName() . ' ' . __('Compte OAuth connecté', __FILE__) . ' : ' . $account);
		return array('state' => 'ok', 'account' => $account);
	}

	public function oauthDisconnect() {
		$this->setConfiguration('oauth::refresh_token', '');
		$this->setConfiguration('oauth::account', '');
		$this->setConfiguration('oauth::connectedAt', time());
		$this->setCache(array('oauth::access_token' => '', 'oauth::expires' => 0, 'oauth::pending' => ''));
		$this->save();
	}

	public function getDaemonConfig() {
		return array(
			'id' => $this->getId(),
			'name' => $this->getHumanName(),
			'server' => $this->getConfiguration('imap::server'),
			'port' => $this->getConfiguration('imap::port'),
			'security' => $this->getConfiguration('imap::security', 'ssl'),
			'username' => $this->getConfiguration('imap::username'),
			'password' => $this->getConfiguration('imap::password'),
			'oauth' => ($this->getConfiguration('oauth::provider', '') != ''),
			'oauthVersion' => $this->getConfiguration('oauth::connectedAt'),
			'dontcheckssl' => ($this->getConfiguration('imap::dontcheckssl', 0) == 1),
			'folder' => $this->getConfiguration('imap::folder', 'INBOX'),
			'markSeen' => ($this->getConfiguration('imap::markSeen', 0) == 1),
			'filterFrom' => $this->getConfiguration('imap::filter::from'),
			'filterSubject' => $this->getConfiguration('imap::filter::subject'),
			'attachments' => ($this->getConfiguration('imap::attachments', 0) == 1),
			'attachmentsPath' => self::getAttachmentsPath($this->getId()),
		);
	}

	public function postRemove() {
		$path = self::getAttachmentsPath($this->getId());
		if (is_dir($path)) {
			exec('rm -rf ' . escapeshellarg($path));
		}
		self::syncDaemon();
	}

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
		if ($this->getConfiguration('imap::enable', 0) == 1) {
			$visible = array('receive::date', 'receive::from', 'receive::subject');
			foreach (self::getReceptionCommands() as $logicalId => $name) {
				$cmd = $this->getCmd('info', $logicalId);
				if (is_object($cmd)) {
					continue;
				}
				$cmd = new mailCmd();
				$cmd->setEqLogic_id($this->getId());
				$cmd->setLogicalId($logicalId);
				$cmd->setName($name);
				$cmd->setType('info');
				$cmd->setSubType('string');
				$cmd->setIsVisible(in_array($logicalId, $visible) ? 1 : 0);
				// Two identical mails in a row must trigger scenarios twice
				$cmd->setConfiguration('repeatEventManagement', 'always');
				$cmd->save();
			}
		}
		self::syncDaemon();
	}
}

class mailCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function preSave() {
		if ($this->getType() == 'info') {
			return;
		}
		if ($this->getLogicalId() == 'send_to_custom_email'){
			$this->setConfiguration('recipient','');
			return;
		}
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
		if ($this->getType() == 'info') {
			return;
		}
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
				if ($eqLogic->getConfiguration('oauth::provider', '') != '') {
					$token = $eqLogic->getOAuthToken();
					$mail->SMTPAuth = true;
					$mail->AuthType = 'XOAUTH2';
					$mail->setOAuth(new class($token['user'], $token['access_token']) implements \PHPMailer\PHPMailer\OAuthTokenProvider {
						private $user;
						private $token;

						public function __construct($_user, $_token) {
							$this->user = $_user;
							$this->token = $_token;
						}

						public function getOauth64() {
							return base64_encode('user=' . $this->user . "\001auth=Bearer " . $this->token . "\001\001");
						}
					});
				} elseif ($eqLogic->getConfiguration('smtp::username') != '') {
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
		try {
			return $mail->send();
		} catch (\Throwable $th) {
			sleep(30);
		}
		return $mail->send();
		
	}

	/*     * **********************Getteur Setteur*************************** */
}
