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
require_once __DIR__ . '/../core/class/TuyaIOTService.class.php';
?>
<form class="form-horizontal">
    <fieldset>
    <div class="form-group">
            <label class="col-lg-4 control-label">{{Access ID/Client ID}}</label>
            <div class="col-lg-3">
                <input class="configKey form-control" data-l1key="accessKey" />
            </div>
        <div class="col-lg-5">Disponible dans {Project} > Overview > Access ID/Client ID</div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Access secret/Client secret}}</label>
            <div class="col-lg-3">
                <input class="configKey form-control" data-l1key="secretKey" value="" type="password" />
            </div>
            <div class="col-lg-5">Disponible dans {Project} > Overview > Access secret/Client secret</div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Account UID}}</label>
            <div class="col-lg-3">
                <input class="configKey form-control" data-l1key="uid" />
            </div>
            <div class="col-lg-5">Disponible dans {Project} > Devices > Link Tuya Account > UID</div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Url}}</label>
            <div class="col-lg-3">
                <select class="configKey form-control" data-l1key="baseUrl">
                    <?php
                    foreach (TuyaIOTService::getBaseUrlAvailable() as $url => $name) {
                        echo '<option value="' . $url . '">' . $name . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-lg-5">Visible dans {Project} > Overview > Data Center</div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Test}}</label>
            <div class="col-lg-3">
                <a class="btn btn-default" id="btSearchDevice"><i class='fa fa-refresh'></i> {{Tester la connexion}}</a>
            </div>
            <div class="col-lg-5">Sauvegarder les paramètres avant de faire le test</div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Activation automatique}}</label>
            <div class="col-lg-3">
                <select class="configKey form-control" data-l1key="autoenable">
                    <option value="1">Oui</option>
                    <option value="0">Non</option>
                </select>
            </div>
            <div class="col-lg-5">Active ou désactive les objets automatiquement lors de la découverte si ceux-ci sont online ou pas.<br>
                Si la valeur est à Non alors, à la 1ère découverte, aucun objet ne sera activé</div>
        </div>
        <?php
        echo "LogLevel: " . log::getLogLevel('TuyaIOT');
        ?>
  </fieldset>
</form>

<script>
    $('#btSearchDevice').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/TuyaIOT/core/ajax/TuyaIOT.ajax.php", // url du fichier php
            data: {
            	action: "checkConnection",
            },
            dataType: 'json',
            error: function (request, status, error) {
            	handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
            	$('#div_alert').showAlert({message: data.result, level: 'danger'});
            	return;
            }
            $('#div_alert').showAlert({message: '{{Test de connexion au cloud Tuya réussie}}', level: 'success'});
          }
        });
    });
</script>