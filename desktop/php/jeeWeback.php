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
    log::add('weback', 'debug', 'Reception d\'un message du deamon : '.print_r($result, true) );

    if (isset($result['mess'])) {
        if (isset($result['mess']['update'])) {
          weback::updateDeviceInfo($result['mess']['update']);
        }
        if (isset($result['mess']['credentials'])) {
          weback::getWebackToken();
        }
    } else {
        log::add('weback', 'error', 'Message du daemon inconnu');
    }
} catch (Exception $e) {
    log::add('weback', 'error', displayException($e));
}
?>
