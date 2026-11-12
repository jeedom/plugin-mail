
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

$('.eqLogicAttr[data-l1key=configuration][data-l2key=sendMode]').on('change', function () {
    $('.sendMode').hide();
    $('.sendMode.' + $(this).value()).show();
});

$('.eqLogicAttr[data-l1key=configuration][data-l2key="imap::enable"]').on('change', function () {
    $('.imapConfig').toggle($(this).value() == 1);
});

$('.eqLogicAttr[data-l1key=configuration][data-l2key="imap::security"]').on('change', function () {
    $('.eqLogicAttr[data-l1key=configuration][data-l2key="imap::port"]').attr('placeholder', $(this).value() == 'ssl' ? '993' : '143');
});

var mailOAuthPresets = {
    google: {'smtp::server': 'smtp.gmail.com', 'smtp::port': '587', 'smtp::security': 'tls', 'imap::server': 'imap.gmail.com', 'imap::port': '993', 'imap::security': 'ssl'},
    microsoft: {'smtp::server': 'smtp.office365.com', 'smtp::port': '587', 'smtp::security': 'tls', 'imap::server': 'outlook.office365.com', 'imap::port': '993', 'imap::security': 'ssl'}
};
var mailOAuthPollTimer = null;

$('.eqLogicAttr[data-l1key=configuration][data-l2key="oauth::provider"]').on('change', function () {
    var provider = $(this).value();
    $('.oauthConfig').toggle(provider != '');
    $('.passwordAuth').toggle(provider == '');
    mailOAuthReset();
    if (mailOAuthPresets[provider]) {
        $.each(mailOAuthPresets[provider], function (key, value) {
            var field = $('.eqLogicAttr[data-l1key=configuration][data-l2key="' + key + '"]');
            if (field.value() == '') {
                field.value(value);
            }
        });
    }
});

function printEqLogic(_eqLogic) {
    mailOAuthSetAccount(_eqLogic.configuration ? _eqLogic.configuration['oauth::account'] : '');
}

function mailOAuthSetAccount(_account) {
    if (_account) {
        $('#span_oauthAccount').removeClass('label-default').addClass('label-success').text(_account);
        $('#bt_oauthDisconnect').show();
    } else {
        $('#span_oauthAccount').removeClass('label-success').addClass('label-default').text('{{Non connecté}}');
        $('#bt_oauthDisconnect').hide();
    }
}

function mailOAuthReset() {
    clearTimeout(mailOAuthPollTimer);
    $('#div_oauthGoogle, #div_oauthMicrosoft').hide();
    $('#in_oauthGoogleUrl').value('');
}

function mailAjax(_action, _data, _success) {
    _data.action = _action;
    _data.id = $('.eqLogicAttr[data-l1key=id]').value();
    $.ajax({
        type: 'POST',
        url: 'plugins/mail/core/ajax/mail.ajax.php',
        data: _data,
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (data.state != 'ok') {
                mailOAuthReset();
                jeedomUtils.showAlert({message: data.result, level: 'danger'});
                return;
            }
            _success(data.result);
        }
    });
}

function mailOAuthConnected(_result) {
    mailOAuthReset();
    mailOAuthSetAccount(_result.account);
    jeedomUtils.showAlert({message: '{{Compte connecté}} : ' + _result.account, level: 'success'});
}

function mailOAuthPoll(_interval) {
    mailOAuthPollTimer = setTimeout(function () {
        mailAjax('oauthPoll', {}, function (result) {
            if (result.state == 'pending') {
                mailOAuthPoll(_interval);
                return;
            }
            mailOAuthConnected(result);
        });
    }, _interval * 1000);
}

$('#bt_oauthConnect').on('click', function () {
    if ($('.eqLogicAttr[data-l1key=id]').value() == '') {
        jeedomUtils.showAlert({message: '{{Sauvegardez l\'équipement avant de connecter le compte}}', level: 'warning'});
        return;
    }
    mailOAuthReset();
    var provider = $('.eqLogicAttr[data-l1key=configuration][data-l2key="oauth::provider"]').value();
    mailAjax('oauthStart', {provider: provider}, function (result) {
        if (result.type == 'redirect') {
            window.open(result.url, '_blank');
            $('#div_oauthGoogle').show();
            return;
        }
        $('#a_oauthMicrosoftUri').attr('href', result.verification_uri).text(result.verification_uri);
        $('#span_oauthMicrosoftCode').text(result.user_code);
        $('#div_oauthMicrosoft').show();
        window.open(result.verification_uri, '_blank');
        mailOAuthPoll(result.interval);
    });
});

$('#bt_oauthGoogleFinish').on('click', function () {
    mailAjax('oauthFinish', {url: $('#in_oauthGoogleUrl').value()}, mailOAuthConnected);
});

$('#bt_oauthDisconnect').on('click', function () {
    mailAjax('oauthDisconnect', {}, function () {
        mailOAuthSetAccount('');
    });
});

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (init(_cmd.type) == 'info') {
        var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
        tr += '<td>';
        tr += '<input class="cmdAttr form-control" data-l1key="id" style="display : none;">';
        tr += '<input class="cmdAttr form-control" data-l1key="name"></td>';
        tr += '<td><span class="label label-info">{{Réception}}</span> ';
        tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label></td>';
        tr += '<td style="width:150px">';
        tr += '<input class="cmdAttr form-control" data-l1key="type" value="info" style="display : none;">';
        tr += '<input class="cmdAttr form-control" data-l1key="subType" value="string" style="display : none;">';
        tr += '<input class="cmdAttr form-control" data-l1key="logicalId" style="display : none;">';
        if (is_numeric(_cmd.id)) {
            tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
        }
        tr += '</td>';
        tr += '</tr>';
        $('#table_cmd tbody').append(tr);
        $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
        return;
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control" data-l1key="id" style="display : none;">';
    tr += '<input class="cmdAttr form-control" data-l1key="name"></td>';
    tr += '<td><input class="cmdAttr form-control" data-l1key="configuration" data-l2key="recipient"></td>';
    tr += '<td style="width:150px">';
    tr += '<input class="cmdAttr form-control" data-l1key="type" value="action" style="display : none;">';
    tr += '<input class="cmdAttr form-control" data-l1key="subType" value="message" style="display : none;">';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
}