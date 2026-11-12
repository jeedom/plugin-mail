# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

# Mail reception daemon (IMAP IDLE).
# Only uses the Python standard library so that the plugin keeps working without
# any pip dependency (python >= 3.9).

import argparse
import email
import email.policy
import email.utils
import hashlib
import html
import imaplib
import json
import logging
import os
import queue
import re
import select
import signal
import socket
import socketserver
import ssl
import sys
import threading
import time
import traceback
import urllib.request

IDLE_TIMEOUT = 25 * 60   # RFC 2177: re-issue IDLE before the 29 minutes server timeout
POLL_INTERVAL = 60       # used when the server does not support IDLE
SOCKET_TIMEOUT = 120
RECONNECT_MIN = 10
RECONNECT_MAX = 300
MAX_TEXT_LENGTH = 100000


def convert_log_level(level='error'):
    levels = {
        'debug': logging.DEBUG,
        'info': logging.INFO,
        'notice': logging.WARNING,
        'warning': logging.WARNING,
        'error': logging.ERROR,
        'critical': logging.CRITICAL,
        'none': logging.CRITICAL
    }
    return levels.get(level, logging.CRITICAL)


class JeedomCallback():
    """Send events to Jeedom, one after the other to keep their order."""

    def __init__(self, url, apikey, retry=3):
        self._url = url + ('&' if '?' in url else '?') + 'apikey=' + apikey
        self._retry = retry
        self._queue = queue.Queue()
        threading.Thread(target=self._loop, daemon=True).start()

    def test(self):
        try:
            with urllib.request.urlopen(self._url, timeout=10) as response:
                return response.status == 200
        except Exception as e:
            logging.error('Callback error: %s. Please check your network configuration page', e)
            return False

    def send(self, data):
        self._queue.put(data)

    def request(self, data):
        """Synchronous call, returns the JSON answered by Jeedom."""
        request = urllib.request.Request(self._url, data=json.dumps(data).encode('utf-8'), headers={'Content-Type': 'application/json'}, method='POST')
        with urllib.request.urlopen(request, timeout=60) as response:
            return json.loads(response.read().decode('utf-8'))

    def _loop(self):
        while True:
            data = self._queue.get()
            payload = json.dumps(data).encode('utf-8')
            for i in range(self._retry):
                try:
                    request = urllib.request.Request(self._url, data=payload, headers={'Content-Type': 'application/json'}, method='POST')
                    with urllib.request.urlopen(request, timeout=60) as response:
                        if response.status == 200:
                            break
                        logging.warning('Error on send request to jeedom, return code %s', response.status)
                except Exception as e:
                    logging.error('Error on send request to jeedom "%s" retry: %i/%i', e, i + 1, self._retry)
                time.sleep(1)


class IMAPBufferMixin():
    """Replace imaplib buffered file by our own buffer.

    imaplib reads through sock.makefile() whose internal buffer can't be inspected
    without blocking, which makes it impossible to wait for IDLE notifications with
    select(). Reading the socket ourselves lets us know when data is pending.
    """

    def open(self, host='', port=None, timeout=None):
        self._rbuf = bytearray()
        super().open(host, port, timeout)

    def _fill(self):
        data = self.sock.recv(65536)
        if not data:
            raise imaplib.IMAP4.abort('socket error: EOF')
        self._rbuf += data

    def read(self, size):
        while len(self._rbuf) < size:
            self._fill()
        data = bytes(self._rbuf[:size])
        del self._rbuf[:size]
        return data

    def readline(self):
        while True:
            pos = self._rbuf.find(b'\n')
            if pos != -1:
                break
            if len(self._rbuf) > imaplib._MAXLINE:
                raise self.error('got more than %d bytes' % imaplib._MAXLINE)
            self._fill()
        data = bytes(self._rbuf[:pos + 1])
        del self._rbuf[:pos + 1]
        return data

    def has_pending_data(self):
        if len(self._rbuf) > 0:
            return True
        return isinstance(self.sock, ssl.SSLSocket) and self.sock.pending() > 0

    def idle(self, stop_event, timeout):
        """Run IDLE until the mailbox changes, timeout expires or stop is requested."""
        tag = self._new_tag()
        self.send(tag + b' IDLE\r\n')
        changed = False
        while True:
            # Untagged responses may arrive before the continuation
            line = self.readline()
            if line.startswith(b'+'):
                break
            if line.startswith(tag):
                raise self.error('IDLE refused: %r' % line)
            if re.match(rb'^\* \d+ (EXISTS|RECENT)', line):
                changed = True
        end = time.monotonic() + timeout
        while not changed and not stop_event.is_set():
            remaining = end - time.monotonic()
            if remaining <= 0:
                break
            if not self.has_pending_data():
                readable, _, _ = select.select([self.sock], [], [], min(remaining, 5))
                if not readable:
                    continue
            line = self.readline()
            logging.debug('IDLE: %r', line)
            if line.startswith(b'* BYE'):
                raise self.abort('server closed connection: %r' % line)
            if re.match(rb'^\* \d+ (EXISTS|RECENT)', line):
                changed = True
                break
        self.send(b'DONE\r\n')
        while True:
            line = self.readline()
            if line.startswith(tag):
                if not line.startswith(tag + b' OK'):
                    raise self.error('IDLE failed: %r' % line)
                break
            if re.match(rb'^\* \d+ (EXISTS|RECENT)', line):
                changed = True
        return changed


class IMAP4(IMAPBufferMixin, imaplib.IMAP4):
    pass


class IMAP4_SSL(IMAPBufferMixin, imaplib.IMAP4_SSL):
    pass


def quote_mailbox(name):
    return '"' + name.replace('\\', '\\\\').replace('"', '\\"') + '"'


def safe_filename(name):
    name = os.path.basename(name or '').strip()
    name = re.sub(r'[^\w.\- ]', '_', name)
    name = name.lstrip('.')
    return name[:150] or 'attachment'


def error_message(e):
    if e.args and isinstance(e.args[0], bytes):
        return e.args[0].decode('utf-8', errors='replace')
    return str(e)


def part_content(part):
    try:
        return part.get_content()
    except Exception:
        payload = part.get_payload(decode=True) or b''
        try:
            return payload.decode(part.get_content_charset() or 'utf-8', errors='replace')
        except LookupError:
            return payload.decode('utf-8', errors='replace')


def html_to_text(content):
    content = re.sub(r'(?is)<(script|style).*?</\1>', '', content)
    content = re.sub(r'(?i)<br\s*/?>|</p>|</div>|</tr>', '\n', content)
    content = re.sub(r'<[^>]+>', '', content)
    content = html.unescape(content).replace('\xa0', ' ')
    content = re.sub(r'[ \t]+', ' ', content)
    return re.sub(r'\n\s*\n+', '\n\n', content).strip()


class Account(threading.Thread):

    def __init__(self, config, callback, state):
        super().__init__(daemon=True)
        self.config = config
        self.id = str(config['id'])
        self.name = config.get('name', self.id)
        self.callback = callback
        self.state = state
        self.stop_event = threading.Event()
        self.imap = None

    def stop(self):
        self.stop_event.set()
        imap = self.imap
        if imap is not None:
            try:
                imap.sock.shutdown(socket.SHUT_RDWR)
            except Exception:
                pass

    def status(self, state, message=''):
        self.callback.send({'status': {'eqLogic_id': self.id, 'state': state, 'message': message}})

    def run(self):
        delay = RECONNECT_MIN
        while not self.stop_event.is_set():
            try:
                self.connect()
                delay = RECONNECT_MIN
                self.status('ok')
                self.listen()
            except Exception as e:
                if self.stop_event.is_set():
                    break
                message = error_message(e)
                logging.error('[%s] %s', self.name, message)
                logging.debug(traceback.format_exc())
                self.status('error', message)
            finally:
                self.disconnect()
            if self.stop_event.wait(delay):
                break
            delay = min(delay * 2, RECONNECT_MAX)
        logging.info('[%s] Stopped', self.name)

    def connect(self):
        config = self.config
        context = ssl.create_default_context()
        if config.get('dontcheckssl'):
            context.check_hostname = False
            context.verify_mode = ssl.CERT_NONE
        host = config['server']
        security = config.get('security', 'ssl')
        port = int(config.get('port') or (993 if security == 'ssl' else 143))
        logging.info('[%s] Connecting to %s:%s (%s)', self.name, host, port, security)
        if security == 'ssl':
            self.imap = IMAP4_SSL(host, port, ssl_context=context, timeout=SOCKET_TIMEOUT)
        else:
            self.imap = IMAP4(host, port, timeout=SOCKET_TIMEOUT)
            if security == 'starttls':
                self.imap.starttls(ssl_context=context)
        self.imap.sock.setsockopt(socket.SOL_SOCKET, socket.SO_KEEPALIVE, 1)
        if config.get('oauth'):
            self.login_oauth()
        else:
            self.imap.login(config['username'], config['password'])
        typ, data = self.imap.select(quote_mailbox(config.get('folder') or 'INBOX'))
        if typ != 'OK':
            raise Exception('Unable to open folder %s: %s' % (config.get('folder'), data))
        logging.info('[%s] Connected, capabilities: %s', self.name, ' '.join(self.imap.capabilities))

    def login_oauth(self):
        token = self.callback.request({'token': {'eqLogic_id': self.id}})
        if 'error' in token:
            raise Exception(token['error'])
        auth_string = 'user=%s\x01auth=Bearer %s\x01\x01' % (token['user'], token['access_token'])
        sent = []

        def authobject(challenge):
            # On failure the server sends an error challenge, answer it with an empty line
            if sent:
                return b''
            sent.append(True)
            return auth_string.encode('utf-8')
        self.imap.authenticate('XOAUTH2', authobject)

    def disconnect(self):
        imap, self.imap = self.imap, None
        if imap is None:
            return
        try:
            imap.logout()
        except Exception:
            pass

    def response_value(self, name):
        typ, data = self.imap.response(name)
        if data and data[0] is not None:
            return int(data[0])
        return None

    def listen(self):
        uidvalidity = self.response_value('UIDVALIDITY')
        uidnext = self.response_value('UIDNEXT')
        saved = self.state.get(self.id)
        if saved is None or saved.get('uidvalidity') != uidvalidity:
            # First start (or mailbox recreated): don't flood Jeedom with old mails
            if uidnext is None:
                typ, data = self.imap.uid('SEARCH', None, 'ALL')
                uids = [int(u) for u in data[0].split()] if typ == 'OK' and data[0] else []
                uidnext = max(uids) + 1 if uids else 1
            self.state.set(self.id, {'uidvalidity': uidvalidity, 'last_uid': uidnext - 1})
        use_idle = 'IDLE' in self.imap.capabilities
        if not use_idle:
            logging.warning('[%s] Server does not support IDLE, polling every %ss', self.name, POLL_INTERVAL)
        while not self.stop_event.is_set():
            self.fetch_new(uidvalidity)
            if use_idle:
                self.imap.idle(self.stop_event, IDLE_TIMEOUT)
            else:
                if self.stop_event.wait(POLL_INTERVAL):
                    break
                self.imap.noop()

    def fetch_new(self, uidvalidity):
        last_uid = self.state.get(self.id)['last_uid']
        typ, data = self.imap.uid('SEARCH', None, 'UID %d:*' % (last_uid + 1))
        if typ != 'OK' or not data[0]:
            return
        # "n:*" always returns the last message, even if its uid is lower than n
        uids = sorted(int(u) for u in data[0].split() if int(u) > last_uid)
        for uid in uids:
            if self.stop_event.is_set():
                return
            typ, data = self.imap.uid('FETCH', str(uid), '(BODY.PEEK[])')
            raw = None
            if typ == 'OK':
                for part in data:
                    if isinstance(part, tuple):
                        raw = part[1]
                        break
            if raw is None:
                logging.warning('[%s] Unable to fetch mail uid %s', self.name, uid)
            else:
                try:
                    self.process(uid, raw)
                except Exception as e:
                    logging.error('[%s] Error processing mail uid %s: %s', self.name, uid, e)
                    logging.debug(traceback.format_exc())
            self.state.set(self.id, {'uidvalidity': uidvalidity, 'last_uid': uid})

    def match_filters(self, from_addr, subject):
        filter_from = [f.strip().lower() for f in (self.config.get('filterFrom') or '').split(',') if f.strip()]
        if filter_from and not any(f in from_addr.lower() for f in filter_from):
            return False
        filter_subject = (self.config.get('filterSubject') or '').strip().lower()
        if filter_subject and filter_subject not in subject.lower():
            return False
        return True

    def process(self, uid, raw):
        msg = email.message_from_bytes(raw, policy=email.policy.default)
        from_name, from_addr = email.utils.parseaddr(str(msg.get('From', '')))
        subject = str(msg.get('Subject', '') or '').strip()
        if not self.match_filters(from_addr, subject):
            logging.info('[%s] Mail uid %s from %s ignored by filters', self.name, uid, from_addr)
            return
        logging.info('[%s] New mail uid %s from %s: %s', self.name, uid, from_addr, subject)

        body = ''
        html_body = ''
        part = msg.get_body(preferencelist=('plain',))
        if part is not None:
            body = part_content(part)
        part = msg.get_body(preferencelist=('html',))
        if part is not None:
            html_body = part_content(part)
        if body == '' and html_body != '':
            body = html_to_text(html_body)

        # Sent as a timestamp: Jeedom formats it with its own timezone
        try:
            timestamp = int(email.utils.parsedate_to_datetime(str(msg.get('Date'))).timestamp())
        except Exception:
            timestamp = int(time.time())

        attachments = []
        if self.config.get('attachments') and self.config.get('attachmentsPath'):
            attachments = self.save_attachments(msg, uid)

        self.callback.send({'mail': {
            'eqLogic_id': self.id,
            'uid': uid,
            'from': from_addr,
            'fromName': from_name,
            'to': ', '.join(addr for _, addr in email.utils.getaddresses([str(v) for v in msg.get_all('To', [])])),
            'subject': subject,
            'body': body.strip()[:MAX_TEXT_LENGTH],
            'html': html_body[:MAX_TEXT_LENGTH],
            'timestamp': timestamp,
            'attachments': attachments
        }})

        if self.config.get('markSeen'):
            self.imap.uid('STORE', str(uid), '+FLAGS', '(\\Seen)')

    def save_attachments(self, msg, uid):
        folder = os.path.join(self.config['attachmentsPath'], str(uid))
        files = []
        for part in msg.iter_attachments():
            content = part.get_payload(decode=True)
            if content is None:
                continue
            os.makedirs(folder, exist_ok=True)
            name = safe_filename(part.get_filename())
            path = os.path.join(folder, name)
            index = 1
            while path in files or os.path.exists(path):
                base, ext = os.path.splitext(name)
                path = os.path.join(folder, '%s_%d%s' % (base, index, ext))
                index += 1
            with open(path, 'wb') as f:
                f.write(content)
            files.append(path)
            logging.debug('[%s] Attachment saved: %s', self.name, path)
        return files


class State():
    """Last processed uid of each account, kept on disk to catch up after a restart."""

    def __init__(self, path):
        self._path = path
        self._lock = threading.Lock()
        self._data = {}
        try:
            with open(path) as f:
                self._data = json.load(f)
        except Exception:
            pass

    def get(self, key):
        with self._lock:
            return self._data.get(key)

    def set(self, key, value):
        with self._lock:
            self._data[key] = value
            tmp = self._path + '.tmp'
            with open(tmp, 'w') as f:
                json.dump(self._data, f)
            os.replace(tmp, self._path)


class Daemon():

    def __init__(self, apikey, callback, state):
        self.apikey = apikey
        self.callback = callback
        self.state = state
        self.accounts = {}
        self.hashes = {}
        self.lock = threading.Lock()

    def handle(self, message):
        if message.get('apikey') != self.apikey:
            logging.error('Invalid apikey from socket')
            return
        if message.get('action') == 'sync':
            self.sync(message.get('accounts', []))

    def sync(self, configs):
        with self.lock:
            wanted = {}
            for config in configs:
                key = str(config['id'])
                wanted[key] = config
            for key in list(self.accounts):
                new_hash = self.config_hash(wanted[key]) if key in wanted else None
                if new_hash != self.hashes[key]:
                    logging.info('[%s] Configuration removed or changed, stopping', self.accounts[key].name)
                    self.accounts[key].stop()
                    self.accounts[key].join(10)
                    del self.accounts[key]
                    del self.hashes[key]
            for key, config in wanted.items():
                if key in self.accounts:
                    continue
                account = Account(config, self.callback, self.state)
                self.accounts[key] = account
                self.hashes[key] = self.config_hash(config)
                account.start()
            logging.info('%d account(s) listened', len(self.accounts))

    def stop(self):
        with self.lock:
            for account in self.accounts.values():
                account.stop()
            for account in self.accounts.values():
                account.join(5)

    @staticmethod
    def config_hash(config):
        return hashlib.sha256(json.dumps(config, sort_keys=True).encode('utf-8')).hexdigest()


class SocketHandler(socketserver.StreamRequestHandler):

    def handle(self):
        line = self.rfile.readline().strip()
        if not line:
            return
        try:
            message = json.loads(line.decode('utf-8'))
        except Exception as e:
            logging.error('Invalid message from socket: %s', e)
            return
        self.server.daemon_instance.handle(message)


def shutdown(signum=None, frame=None):
    logging.info('Shutdown')
    try:
        daemon.stop()
    except Exception:
        pass
    try:
        os.remove(args.pid)
    except Exception:
        pass
    sys.stdout.flush()
    os._exit(0)


parser = argparse.ArgumentParser(description='Mail reception daemon for Jeedom')
parser.add_argument('--loglevel', type=str, default='error')
parser.add_argument('--socketport', type=int, default=55072)
parser.add_argument('--callback', type=str, required=True)
parser.add_argument('--apikey', type=str, required=True)
parser.add_argument('--pid', type=str, required=True)
parser.add_argument('--state', type=str, required=True)
args = parser.parse_args()

logging.basicConfig(level=convert_log_level(args.loglevel), format='[%(asctime)-15s][%(levelname)s] : %(message)s', datefmt='%Y-%m-%d %H:%M:%S')
logging.info('Start maild, log level: %s, socket port: %s', args.loglevel, args.socketport)

signal.signal(signal.SIGINT, shutdown)
signal.signal(signal.SIGTERM, shutdown)

try:
    with open(args.pid, 'w') as f:
        f.write(str(os.getpid()))
    callback = JeedomCallback(args.callback, args.apikey)
    if not callback.test():
        logging.error('Network communication issues. Please fix your Jeedom network configuration.')
        shutdown()
    daemon = Daemon(args.apikey, callback, State(args.state))
    socketserver.TCPServer.allow_reuse_address = True
    server = socketserver.TCPServer(('127.0.0.1', args.socketport), SocketHandler)
    server.daemon_instance = daemon
    server.serve_forever()
except Exception as e:
    logging.error('Fatal error: %s', e)
    logging.info(traceback.format_exc())
    shutdown()
