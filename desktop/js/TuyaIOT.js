
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


/**
 * Discover objects button
 */
$('.discover').on('click', function () {
    $('#div_alert').showAlert({
        message: '{{Détection en cours}}',
        level: 'warning',
        ttl: 10000, // 10s
    });
    $.ajax({
        type: "POST",
        url: "plugins/TuyaIOT/core/ajax/TuyaIOT.ajax.php",
        data: {
            action: "discover",
            mode: $(this).attr('data-action'),
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#div_alert').showAlert({message: '{{Opération réalisée avec succès : Rafraichir la page F5}}', level: 'success'});
        }
    });
});
$('#bt_generatecommand').on('click', function () {
    bootbox.confirm('{{Etes-vous sûr de vouloir (re)générer toutes les commandes ?<br> Cela ne va pas supprimer les commandes existantes mais ajouter celles manquantes}}', function (result) {
        if (result) {
            $.ajax({
                type: "POST",
                url: "plugins/TuyaIOT/core/ajax/TuyaIOT.ajax.php",
                data: {
                    action: "generateCommand",
                    id: $('.eqLogicAttr[data-l1key=id]').value()
                },
                dataType: 'json',
                global: false,
                error: function (request, status, error) {
                    handleAjaxError(request, status, error);
                },
                success: function (data) {
                    if (data.state != 'ok') {
                        $('#div_alert').showAlert({message: data.result, level: 'danger'});
                        return;
                    }
                    $('#div_alert').showAlert({message: '{{Opération réalisée avec succès}}', level: 'success'});
                    $('.eqLogicDisplayCard[data-eqLogic_id=' + $('.eqLogicAttr[data-l1key=id]').value() + ']').click();
                }
            });
        }
    });

});
