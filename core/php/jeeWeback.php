
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
    /*
    > WSS receiving message =
    {"notify_info": "thing_status_update", "thing_status": {"connected": "true", "working_status": "ChargeDone", "voice_switch": "on", "volume": 80, "voice_pack": "default", "carpet_pressurization": false, "undisturb_mode": "on", "fan_status": "Quiet", "water_level": "None", "error_info": "NoError", "yugong_debug_version": "0.3.3.1", "yugong_software_version": "0.3.3", "vendor_software_version": "2.2.3", "vendor_firmware_version": "2.2.0", "vendor_system_version": "2.0.1", "vendor_vupdate_version": "2.1.0", "current_status_percentage": 0, "Voicebox_Source": "null", "battery_level": 100, "continue_clean": false, "offset_hours": 8, "offset_minutes": 0, "clean_area": 0, "clean_time": 0, "save_map": "off", "upgrade_logic": "1.0", "hardware_platform": "5002-1004", "extend_function_flag": 3}, "thing_name": "neatsvor-x600-20-4e-f6-9e-f2-a1"}
    */
    if (isset($result['notify_info']) && $result['notify_info'] == 'thing_status_update') {
        log::add('weback', 'debug', 'Thing_Update receptionné depuis daemon  : '.print_r($result['thing_status'], true));
        weback::updateDeviceInfo($result['thing_name'], $result['thing_status']);
    } elseif (isset($result['notify_info']) && $result['notify_info'] == 'map_data') {
        log::add('weback', 'debug', 'Map_data receptionné depuis daemon  : '.print_r($result['map_data'], true));
        // not used
    } elseif (isset($result['action']) && $result['action'] == "getcredentials") {
        log::add('weback', 'debug', 'Le deamon demande les infos de connection...');
        if (weback::webackTokenValidity() == true) {
          weback::sendCredentialsToDaemon();
        } else {
          weback::getWebackToken();
        }
    } else {
        log::add('weback', 'error', 'Message du daemon inconnu : ' .print_r($result, true));
    }
} catch (Exception $e) {
    log::add('weback', 'error', displayException($e));
}
?>
