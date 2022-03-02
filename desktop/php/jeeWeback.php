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

    if (isset($result['mess'])) {
        // do something
    } else {
        log::add('weback', 'error', 'unknown message received from daemon');
    }
} catch (Exception $e) {
    log::add('weback', 'error', displayException($e));
}
?>
