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
    <div class="form-group">
      <label class="col-md-4 control-label">{{Utilisateur/Téléphone}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Email ou numéro de téléphone avec lequel vous avez enregistrer votre compte WeBack}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="user"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Mot de passe}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Mot de passe du compte WeBack}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="password" value="" type="password" />
      </div>
    </div>
    <div class="form-group">
        <label class="col-md-4 control-label">{{Code pays}}
          <sup><i class="fas fa-question-circle tooltips" title="{{Code du pays (33 pour la France)}}"></i></sup>
        </label>
        <div class="col-md-4">
            <input class="configKey form-control" data-l1key="country" value="33" />
        </div>
    </div>
    <div class="form-group">
        <label class="col-md-4 control-label">{{Test}}</label>
        <div class="col-md-4">
            <a class="btn btn-default" id="btSearchDevice"><i class='fa fa-refresh'></i> {{Tester la connexion}}</a>
        </div>
    </div>
  </fieldset>
</form>

<script>
    $('#btSearchDevice').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/weback/core/ajax/weback.ajax.php", // url du fichier php
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
            $('#div_alert').showAlert({message: '{{Test de connexion au cloud WeBack réussie}}', level: 'success'});
          }
        });
    });
</script>
