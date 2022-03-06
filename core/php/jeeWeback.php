
<?php

try {
    require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

    if (!jeedom::apiAccess(init('apikey'), 'weback')) {
        echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
        die();
    }
    if (init('test') != '') {
        echo 'OK';
        die();
    }
    $result = json_decode(file_get_contents("php://input"), true);
    if (!is_array($result)) {
        die();
    }
    log::add('weback', 'debug', 'Reception d\'un message du deamon : '.print_r($result, true));

    if (isset($result['notify_info']) && $result['notify_info'] == 'thing_status_update') {
        log::add('weback', 'debug', 'Thing_Update receptionné depuis daemon  : '.print_r($result['thing_status'], true));
        weback::updateDeviceInfo($result['thing_name'], $result['thing_status']);

    } elseif (isset($result['action']) && $result['action'] == "getcredentials") {
        log::add('weback', 'debug', 'Le deamon demande les infos de connection...');
        if (weback::webackTokenValidity() == true) {
          weback::sendCredentialsToDaemon();
        } else {
          weback::getWebackToken();
        }
    } else {
        log::add('weback', 'error', 'Message du daemon inconnu');
    }
} catch (Exception $e) {
    log::add('weback', 'error', displayException($e));
}
?>
