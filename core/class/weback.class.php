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
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class weback extends eqLogic {
    /*     * *************************Attributs****************************** */
    /**
     * Recherche les équipements sur clique du bouton
     *
     * return Array of Device
     */

     public static function discoverRobot()
     {
         log::add('weback', 'debug', 'Démarrage de la recherche des robots...', true);
         if (weback::getWebackToken() == true) {
               if (weback::getAWScredential() == true) {
                       if (weback::getDeviceList() == true) {
                         log::add('weback', 'debug', '### Recherche robot terminée avec succès!', true);
                         return null;
                       } else {
                         log::add('weback', 'error', 'Recherche des robots KO > Echec GetDeviceList', true);
                         return "impossible de trouver un robot sur le compte.";
                       }
                 } else {
                   log::add('weback', 'error', 'Recherche des robots KO > Echec AWS Credentials', true);
                   return "impossible de se connecter.";
                 }
           } else {
             log::add('weback', 'error', 'Recherche des robots KO > Echec WeBack login', true);
             return "impossible de se connecter à WeBack.";
           }
     }


     public static function getWebackToken() {
       log::add('weback', 'debug', 'Connexion à grit-cloud...');
       if (config::byKey('password', 'weback') != '' && config::byKey('user', 'weback') != '' && config::byKey('country', 'weback') != '') {
         $ch = curl_init();
         /*{"payload":{
                "opt":"login",
                "pwd":"66f7a4d78be2accaf520079d0445f5b3"
            },
            "header":{
                "language":"fr",
                "app_name":"WeBack",
                "calling_code":"0033",
                "api_version":"1.0",
                "account":"tekv3frm@gmail.com",
                "client_id":"yugong_app"}
            }*/
        $data = array("payload" => array("opt" => "login",
                                        "pwd" => md5(config::byKey('password', 'weback'))),
                      "header" => array("language" => "fr",
                                        "app_name" => "WeBack",
                                        "calling_code" => "00".config::byKey('country', 'weback'),
                                        "api_version" => "1.0",
                                        "account" => config::byKey('user', 'weback'),
                                        "client_id" => "yugong_app")
                      );
         $data_string = json_encode($data);

         curl_setopt($ch, CURLOPT_URL, "https://user.grit-cloud.com/oauth");
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
          );
         $server_output = curl_exec($ch);
         $json = json_decode($server_output, true);
         log::add('weback', 'debug', 'grit-cloud answer = ' . print_r($json, true));

         if ($json['msg'] == 'success') {
           log::add('weback', 'debug', 'Identifiant/mot de passe grit-cloud OK');
           // Enregistrement des informations de connexion
           config::save("jwt_token", $json['data']['jwt_token'], 'weback');
           config::save("region_name", $json['region_name'], 'weback');
           config::save("api_url", $json['api_url'], 'weback');
           config::save("wss_url", $json['wss_url'], 'weback');
           $date_utc = new DateTime("now", new DateTimeZone("UTC"));
           $wbtsexpiration = $json['expired_time']+$date_utc->getTimestamp();
           config::save("token_expiration", $wbtsexpiration, 'weback');
           return true;
         } else {
           log::add('weback', 'debug', 'Erreur CURL = ' . curl_error($ch));
           log::add('weback', 'error', 'Echec de connexion à grit-cloud : '.$json['msg']);
           return false;
         }
         curl_close($ch);
       } else {
         log::add('weback', 'info', 'Informations de connexion à WeBack manquantes');
         return false;
       }
     }

     public static function getDeviceList() {
        log::add('weback', 'debug', 'Récupération des informations depuis grit-cloud API...');
        $ch = curl_init();
        $data = array("opt" => "user_thing_list_get");
         $data_string = json_encode($data);

         curl_setopt($ch, CURLOPT_URL, config::byKey("api_url", 'weback'));
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Token:'.config::byKey("jwt_token", 'weback'),
            'Region:'.config::byKey("region_name", 'weback'),
            'Content-Length: ' . strlen($data_string))
          );
         $server_output = curl_exec($ch);
         $json = json_decode($server_output, true);
         log::add('weback', 'debug', 'grit-cloud answer = ' . print_r($json, true));

         if ($json['msg'] == 'success') {
           log::add('weback', 'info', 'Robot trouvé : ' .$json['data']['thing_list'][0]['thing_name']);
           weback::addNewRobot($json);
           return true;
         } else {
           log::add('weback', 'debug', 'Erreur CURL = ' . curl_error($ch));
           log::add('weback', 'error', 'Echec de connexion à grit-cloud : '.$json['msg']);
           event::add('jeedom::alert', array(
             'level' => 'alert',
             'page' => 'weback',
             'message' => __('Aucun robot trouvé', __FILE__)));
            log::add('weback', 'info', 'Aucun robot trouvé');
           return false;
         }
         curl_close($ch);
     }

    public static function addNewRobot($device) {
      $robot=weback::byLogicalId($device['data']['thing_list'][0]['thing_name'], 'weback');
      if (!is_object($robot)) {
        log::add('weback', 'info', $device['data']['thing_list'][0]['thing_nickname']. ' > Ce robot est inconnu, ajout dans les nouveaux objets');
        $robot = new weback();
        $robot->setEqType_name('weback');
        $robot->setLogicalId($device['data']['thing_list'][0]['thing_name']);
        $robot->setIsEnable(1);
        $robot->setIsVisible(1);
        $robot->setName($device['data']['thing_list'][0]['thing_nickname']." ".$device['data']['thing_list'][0]['sub_type']);
        $robot->setConfiguration('Mac_Adress', str_replace("-", ":", substr($device['data']['thing_list'][0]['thing_name'],-17)));
        $robot->save();
        $robot->loadCmdFromConf($device['Request_Cotent'][0]['Sub_type'], $device['Request_Cotent'][0]['Thing_Name']);
      } else {
        log::add('weback', 'info', $device['Request_Cotent'][0]['Thing_Nick_Name']. ' > Ce robot est déjà enregistré dans les objets!');
        $robot->loadCmdFromConf($device['data']['thing_list'][0]['sub_type'], $device['data']['thing_list'][0]['thing_name']);
      }
    }

    public static function getDeviceShadow($calledLogicalID){
      //$endpointSRV = config::byKey('End_Point', 'weback');
      log::add('weback', 'debug', 'Mise à jour GetThingShadow depuis IOT-Data (end-point: '.$endpointSRV.')...');
      /*try {
        $IoT = new Aws\IotDataPlane\IotDataPlaneClient([
            'endpointAddress' => 'https://'.$endpointSRV,
            'endpointType' => 'iot:Data-ATS',
            'http'    => [
              'verify' => false
              ],
            'version' => 'latest',
            'region'  => config::byKey('Region_Info', 'weback'),
            'credentials' => [
                 'key'    => config::byKey('AccessKeyId', 'weback'),
                 'secret' => config::byKey('SecretKey', 'weback'),
                 'token' => config::byKey('SessionToken', 'weback'),]
        ]);
        $result = $IoT->getThingShadow([
            'thingName' => $calledLogicalID,
        ]);
      } catch (Exception $e) {
          log::add('weback', 'error', 'Erreur sur la fonction AWS GetThingShadow, détail : '. $e->getMessage());
          return false;
      }*/

      // Status code
      /*$statuscode = (string)$result['@metadata']['statusCode'];
      //log::add('weback', 'debug', 'HTTP Status code : ' . $statuscode);
      if ($statuscode == 200) {

        // Data
        $return = (string)$result['payload']->getContents();*/
        log::add('weback', 'debug', 'IOT Return : ' . $return);
        $shadowJson = json_decode($return, false);
        log::add('weback', 'debug', 'Mise à jours OK pour : '.$calledLogicalID);

        $wback=weback::byLogicalId($calledLogicalID, 'weback');

        $wstatus = $shadowJson->state->reported->working_status;
        $errnfo = $shadowJson->state->reported->error_info;

        if ($errnfo == "NoError" || $errnfo == NULL) {
          $wback->checkAndUpdateCmd('haserror', 0);
        } else {
          $wback->checkAndUpdateCmd('haserror', 1);
        }

        if ($shadowJson->state->reported->connected == "true") {
          $wback->checkAndUpdateCmd('connected', true);
          $wback->checkAndUpdateCmd('working_status', $wstatus);
          $wback->checkAndUpdateCmd('voice_switch', $shadowJson->state->reported->voice_switch);
          $wback->checkAndUpdateCmd('voice_volume', $shadowJson->state->reported->volume);
          $wback->checkAndUpdateCmd('undistrub_mode', $shadowJson->state->reported->undisturb_mode);
          $wback->checkAndUpdateCmd('fan_status', $shadowJson->state->reported->fan_status);
          $wback->checkAndUpdateCmd('water_level', $shadowJson->state->reported->water_level);
          $wback->checkAndUpdateCmd('error_info', $errnfo);
          $wback->checkAndUpdateCmd('battery_level', $shadowJson->state->reported->battery_level);
          $wback->checkAndUpdateCmd('clean_area', round($shadowJson->state->reported->clean_area, 1));
          $wback->checkAndUpdateCmd('clean_time', round(($shadowJson->state->reported->clean_time)/60,0));
          $wback->checkAndUpdateCmd('planning_rect_x', implode(",",$shadowJson->state->reported->planning_rect_x));
          $wback->checkAndUpdateCmd('planning_rect_y', implode(",",$shadowJson->state->reported->planning_rect_y));
          $wback->checkAndUpdateCmd('goto_point', implode(",",$shadowJson->state->reported->goto_point));
          $wback->checkAndUpdateCmd('optical_flow', $shadowJson->state->reported->optical_flow);
          $wback->checkAndUpdateCmd('left_water', $shadowJson->state->reported->left_water);
          $wback->checkAndUpdateCmd('cliff_detect', $shadowJson->state->reported->cliff_detect);
          $wback->checkAndUpdateCmd('final_edge', $shadowJson->state->reported->final_edge);
          $wback->checkAndUpdateCmd('uv_lamp', $shadowJson->state->reported->uv_lamp);
          $wback->checkAndUpdateCmd('laser_wall_line_point_num', ($shadowJson->state->reported->laser_wall_line_point_num)/2);
          //$wback->checkAndUpdateCmd('laser_goto_path_x', implode(",",$shadowJson->state->reported->laser_goto_path_x));
          //$wback->checkAndUpdateCmd('laser_goto_path_y', implode(",",$shadowJson->state->reported->laser_goto_path_y));
          // BOOLEAN
          if ($shadowJson->state->reported->continue_clean) {
            $wback->checkAndUpdateCmd('continue_clean', 1);
          } else {
            $wback->checkAndUpdateCmd('continue_clean', 0);
          }
          if ($shadowJson->state->reported->carpet_pressurization) {
            $wback->checkAndUpdateCmd('carpet_pressurization', 1);
          } else {
            $wback->checkAndUpdateCmd('carpet_pressurization', 0);
          }
        } else {
          $wback->checkAndUpdateCmd('connected', false);
          $wback->checkAndUpdateCmd('working_status', '');
          $wback->checkAndUpdateCmd('voice_switch', '');
          $wback->checkAndUpdateCmd('voice_volume', '');
          $wback->checkAndUpdateCmd('undistrub_mode', '');
          $wback->checkAndUpdateCmd('fan_status', '');
          $wback->checkAndUpdateCmd('water_level', '');
          $wback->checkAndUpdateCmd('error_info', '');
          $wback->checkAndUpdateCmd('battery_level', 0);
          $wback->checkAndUpdateCmd('clean_area', 0);
          $wback->checkAndUpdateCmd('clean_time', 0);
          $wback->checkAndUpdateCmd('planning_rect_x', '');
          $wback->checkAndUpdateCmd('planning_rect_y', '');
          $wback->checkAndUpdateCmd('goto_point', '');
          $wback->checkAndUpdateCmd('optical_flow', '');
          $wback->checkAndUpdateCmd('left_water', '');
          $wback->checkAndUpdateCmd('cliff_detect', '');
          $wback->checkAndUpdateCmd('final_edge', '');
          $wback->checkAndUpdateCmd('uv_lamp', '');
          $wback->checkAndUpdateCmd('laser_wall_line_point_num', '');
          $wback->checkAndUpdateCmd('carpet_pressurization', 0);
          $wback->checkAndUpdateCmd('continue_clean', 0);
        }

        $result = weback::DeterminateSimpleState($wstatus);
          if ($result == "docked") {
            $wback->checkAndUpdateCmd('isworking', 0);
            $wback->checkAndUpdateCmd('isdocked', 1);
          } elseif ($result == "working") {
            $wback->checkAndUpdateCmd('isdocked', 0);
            $wback->checkAndUpdateCmd('isworking', 1);
          } elseif ($result == "hibernating") {
            /*if ($wback->isworking->getValue() == 1) {
              $wback->checkAndUpdateCmd('isdocked', 0);
              $wback->checkAndUpdateCmd('isworking', 0);
            }
            if ($wback->isdocked->getValue() == 1) {
              $wback->checkAndUpdateCmd('isdocked', 1);
              $wback->checkAndUpdateCmd('isworking', 0);
            }*/
          } else {
            $wback->checkAndUpdateCmd('isdocked', 0);
            $wback->checkAndUpdateCmd('isworking', 0);
            log::add('weback', 'debug', 'Aucune équivalence Docked/Working trouvée pour l\'état : '.$wstatus);
          }

          // Check for unreported item
          $awaitingOrder = count($shadowJson->state->delta);
          $wback->checkAndUpdateCmd('awaiting_order', $awaitingOrder);
          if ($awaitingOrder > 0 ) {
            log::add('weback', 'warning', 'Attention présence d\'ordre transmis au serveur, mais en en attentes d\'execution par le robot x'.$awaitingOrder);
          }
        return true;
      /*
      } else {
        // HTTP Error code
        log::add('weback', 'warning', 'Erreur HTTP code : ' . $statuscode);
        return false;
      }*/


      }


    public static function webackTokenValidity(){
      $date_utc = new DateTime("now", new DateTimeZone("UTC"));
      $tsnow = $date_utc->getTimestamp();
      $tsexpiration =  (config::byKey('token_expiration', 'weback')) -30;

      if ($tsexpiration < $tsnow) {
        log::add('weback', 'warning', 'Token WeBack (ts '.$tsexpiration.') => Expiré !');
        return true;
      } else {
        log::add('weback', 'debug', 'Token WeBack (ts '.$tsexpiration.') => OK Valide');
        return false;
      }
    }

    public static function updateStatusDevices($calledLogicalID){
      log::add('weback', 'debug', 'UpdateStatus de '.$calledLogicalID.' demandé');
      // Vérification si le TOKEN AWS IOT est toujours valable

      if (weback::webackTokenValidity() == true) {
        if (weback::getWebackToken() == true) {
          log::add('weback', 'debug', 'CRON > Mise à jour WeBack token OK ');
        } else {
          log::add('weback', 'error', 'CRON > Echec de mise à jour WeBack token');
        }
      }
      if (weback::awsTokenValidity() == true) {
        if (weback::getAWScredential() == true) {
          log::add('weback', 'debug', 'CRON > Mise à jour AWS token OK ');
        } else {
          log::add('weback', 'error', 'CRON > Echec de mise à jour AWS token');
        }
      }

      if (weback::getDeviceShadow($calledLogicalID) == false) {
        log::add('weback', 'error', 'CRON > Echec de mise à jour ');
      }
    }

    public static function SendAction($calledLogicalID, $action) {
      log::add('weback', 'debug', 'Envoi d\'une action au robot: '.$calledLogicalID.' Action demandée : '.$action);

      /*try {
        $IoT = new Aws\IotDataPlane\IotDataPlaneClient([
            'endpointAddress' => 'https://'.config::byKey('End_Point', 'weback'),
            'endpointType' => 'iot:Data-ATS',
            'http'    => [
              'verify' => false
              ],
            'version' => 'latest',
            'region'  => config::byKey('Region_Info', 'weback'),
            'credentials' => [
                 'key'    => config::byKey('AccessKeyId', 'weback'),
                 'secret' => config::byKey('SecretKey', 'weback'),
                 'token' => config::byKey('SessionToken', 'weback'),]
        ]);
        // Formatage du Payload
        $data = array (
            "state" => array (
                "desired" =>
                      $action,
              )
            );
        $payload = json_encode($data);

        $result = $IoT->updateThingShadow([
            'payload' => $payload,
            'thingName' => $calledLogicalID,
        ]);
      } catch (Exception $e) {
          log::add('weback', 'error', 'Erreur sur la fonction updateThingShadow, détail : '. $e->getMessage());
          return false;
      }

      // Status code
      $statuscode = (string)$result['@metadata']['statusCode'];
      log::add('weback', 'debug', 'HTTP Status code : ' . $statuscode);

      if ($statuscode == 200) {
        $return = (string)$result['payload']->getContents();
        log::add('weback', 'debug', 'IOT Return : ' . $return);
        return true;
      } else {
        // HTTP Error code
        log::add('weback', 'warning', 'Erreur HTTP code : ' . $statuscode);
        return false;
      }*/
    }

    public static function DeterminateSimpleState($working_status){
      /*
      ==================ROBOT_WORK_STATUS_CHARGING_3
      ROBOT_WORK_STATUS_STANDBY("Standby"),
      ROBOT_WORK_STATUS_CTRL("DirectionControl"),
      ROBOT_WORK_STATUS_ERROR("Malfunction"),
      ROBOT_WORK_STATUS_LOWPOWER("Lowpower"),
      ROBOT_WORK_STATUS_WORKING("Cleaning"),
      ROBOT_WORK_STATUS_WORK_OVER("Cleandone"),
      ROBOT_WORK_STATUS_GO_CHARGE("Backcharging"),
      ==================DOCKED
      ROBOT_WORK_STATUS_CHARGING_3("Charging"),
      ROBOT_WORK_STATUS_CHARGING("Pilecharging"),
      ROBOT_WORK_STATUS_CHARGE_OVER("Chargedone"),
      ROBOT_WORK_STATUS_CHARGING2("DirCharging"),
      ==================NOT WORKING and MAY NOT DOCKED
      ROBOT_WORK_STATUS_STOP("Hibernating"),
      */

      $dockedStatus = array("Charging", "PileCharging", "DirCharging", "ChargeDone");
      $workingStatus = array("Relocation", "AutoClean", "SmartClean", "EdgeClean", "SpotClean", "RoomClean",
      "MopClean", "Standby", "PlanningLocation", "StrongClean", "PlanningRect", "ZmodeClean", "BackCharging", "VacuumClean");
      // Docked Status
      if (in_array($working_status, $dockedStatus)) {
          return "docked";
      }
      // Working Status
      if (in_array($working_status, $workingStatus)) {
          return "working";
      }
      // Working Status
      if ($working_status == "Hibernating") {
          return "hibernating";
      }
      return null;
    }

  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */

    /*     * ***********************Methode static*************************** */


     //Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron($_eqlogic_id = null) {
        $eqLogics = ($_eqlogic_id !== null) ? array(eqLogic::byId($_eqlogic_id)) : eqLogic::byType('weback', true);
        if (count($eqLogics) > 0) {
          log::add('weback', 'debug', 'Refresh (CRON) démarré pour actualiser : '.count($eqLogics).' robot(s)');
          foreach ($eqLogics as $webackrbt) {
            //log::add('weback', 'debug', 'Process d\'actualisation démarré pour : '.$webackrbt->getHumanName());
            //weback::updateStatusDevices($webackrbt->getLogicalId());
          }
        } else {
          log::add('weback', 'debug', 'Refresh (CRON) n\'a pas de robot à actualiser.');
        }
      }

    /*
     * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
      public static function cron5() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
      public static function cron10() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
      public static function cron15() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
      public static function cron30() {
      }
     */

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {
      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {
      }
     */

     public static function deamon_info() {
        $return = array();
        $return['log'] = __CLASS__;
        $return['state'] = 'nok';
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file)) {
            if (@posix_getsid(trim(file_get_contents($pid_file)))) {
                $return['state'] = 'ok';
            } else {
                shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
            }
        }
        $return['launchable'] = 'ok';
        /*$user = config::byKey('user', __CLASS__); // exemple si votre démon à besoin de la config user,
        $pswd = config::byKey('password', __CLASS__); // password,
        $clientId = config::byKey('clientId', __CLASS__); // et clientId
        if ($user == '') {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('Le nom d\'utilisateur n\'est pas configuré', __FILE__);
        } elseif ($pswd == '') {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('Le mot de passe n\'est pas configuré', __FILE__);
        } elseif ($clientId == '') {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('La clé d\'application n\'est pas configurée', __FILE__);
        }*/
        return $return;
    }

    public static function deamon_start() {
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }

        $path = realpath(dirname(__FILE__) . '/../../resources/demond');
        $cmd = 'python3 ' . $path . '/webackd.py';
        $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
        $cmd .= ' --socketport ' . config::byKey('socketport', __CLASS__, '33009');
        $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/template/core/php/jeeWeback.php';
        $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
        $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        log::add(__CLASS__, 'info', 'Lancement démon');
        $result = exec($cmd . ' >> ' . log::getPathToLog('weback_daemon') . ' 2>&1 &');
        $i = 0;
        while ($i < 20) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__), 'unableStartDeamon');
            return false;
        }
        message::removeAll(__CLASS__, 'unableStartDeamon');
        return true;
    }


    public static function deamon_stop() {
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file)) {
            $pid = intval(trim(file_get_contents($pid_file)));
            system::kill($pid);
        }
        system::kill('webackd.py');
        sleep(1);
    }

    public static function sendToDaemon($params) {
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] != 'ok') {
            throw new Exception("Le démon n'est pas démarré");
        }
        $params['apikey'] = jeedom::getApiKey(__CLASS__);
        $payLoad = json_encode($params);
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        socket_connect($socket, '127.0.0.1', config::byKey('socketport', __CLASS__, '33009'));
        socket_write($socket, $payLoad, strlen($payLoad));
        socket_close($socket);
    }

    /*     * *********************Méthodes d'instance************************* */

 // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {
    }

 // Fonction exécutée automatiquement après la création de l'équipement
    public function postInsert() {


    }

    public function loadCmdFromConf($_type, $roboteqId) {
      log::add('weback', 'debug', 'Chargement des commandes du robots depuis le fichiers JSON : '.$_type);
      if (!is_file(dirname(__FILE__) . '/../config/devices/' . $_type . '.json')) {
        log::add('weback', 'warning', 'Fichier de configuration du robot introuvable! Utilisation du type "générique" seules les commandes basiques seront disponible.');
        $_type = "generic";
      }
      $content = file_get_contents(dirname(__FILE__) . '/../config/devices/' . $_type . '.json');

      if (!is_json($content)) {
        log::add('weback', 'error', 'Format du fichier de configuration n\'est pas du JSON valide !');
        return;
      }
      $device = json_decode($content, true);
      if (!is_array($device) || !isset($device['commands'])) {
        log::add('weback', 'error', 'Pas de configuration valide trouvé dans le fichier');
        return true;
      }
      log::add('weback', 'info', 'Nombre de commandes à ajouter : '.count($device['commands']));
      $cmd_order = 0;
      foreach ($device['commands'] as $command) {
        $cmd = null;
        foreach ($this->getCmd() as $liste_cmd) {
          if ((isset($command['logicalId']) && $liste_cmd->getLogicalId() == $command['logicalId'])
          || (isset($command['name']) && $liste_cmd->getName() == $command['name'])) {
            $cmd = $liste_cmd;
            break;
          }
        }
        if ($cmd == null || !is_object($cmd)) {
          log::add('weback', 'info', '+ Ajout de : '.$command['name']);
          $cmd = new webackCmd();
          $cmd->setOrder($cmd_order);
          $cmd->setEqLogic_id($this->getId());
          utils::a2o($cmd, $command);
          $cmd->save();
          if ($cmd->getConfiguration('valueFrom') != "") {
            $valueLink = $cmd->getConfiguration('valueFrom');
            $robot=weback::byLogicalId($roboteqId, 'weback');
            $cmdlogic = webackCmd::byEqLogicIdAndLogicalId($robot->getId(), $valueLink);
            if (is_object($cmdlogic)) {
        			$cmd->setValue($cmdlogic->getId());
              $cmd->save();
              log::add('weback', 'debug', '-> Valeur lier depuis : '.$valueLink." (".$cmdlogic->getId().")");
        		} else {
              log::add('weback', 'warning', '-> Liaison impossible objet introuvable : '.$valueLink);
            }
          }
          $cmd_order++;
        } else {
          log::add('weback', 'debug', 'Commande déjà présente : '.$command['name']);
        }
      }

    }

 // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {

    }

 // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {

    }

 // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
    public function preSave() {

    }

 // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
    public function postSave() {

    }

 // Fonction exécutée automatiquement avant la suppression de l'équipement
    public function preRemove() {

    }

 // Fonction exécutée automatiquement après la suppression de l'équipement
    public function postRemove() {

    }
}

class webackCmd extends cmd {
  // Exécution d'une commande
     public function execute($_options = array()) {
      $retry = 1;
      $eqLogic = $this->getEqLogic();
      $eqToSendAction = $eqLogic->getlogicalId();
      log::add('weback', 'debug', '-> Execute : '.$this->getLogicalId());

       switch ($this->getLogicalId()) {
          case 'refresh':
            log::add('weback', 'debug', 'Refresh (MANUEL) demandé sur : '.$eqToSendAction);
            weback::updateStatusDevices($eqToSendAction);
            break;
          case 'cleanspot':
            log::add('weback', 'debug', 'Spot info :'.$_options['message']);
            $coordinates = explode(",", $_options['message']);
            $actionToSend = array("working_status" => "PlanningLocation");
            $actionToSend["goto_point"] = "[".$coordinates[0].",".$coordinates[1]."]";
            $actionToSend["laser_goto_path_x"] = "[".$coordinates[0]."]";
            $actionToSend["laser_goto_path_y"] = "[".$coordinates[1]."]";
            weback::SendAction($eqToSendAction, $actionToSend);
            break;
          case 'cleanroom':
            log::add('weback', 'debug', 'Room info X:'.$_options['message']." Y:".$_options['title']);
            $actionToSend = array("working_status" => "PlanningRect");
            $actionToSend["planning_rect_x"] = "[".$_options['title']."]";
            $actionToSend["planning_rect_y"] = "[".$_options['message']."]";
            weback::SendAction($eqToSendAction, $actionToSend);
            break;
          default:
            $actRequest = $this->getConfiguration('actionrequest');
            log::add('weback', 'debug', '>ActionRequest : '.$actRequest);
            if ($this->getSubType() == 'other') {
              $stateRequest = $this->getLogicalId();
              log::add('weback', 'debug', '>Value (from logicalID) : '.$stateRequest);
            } elseif ($this->getSubType() == 'select') {
              $stateRequest = $_options['select'];
              log::add('weback', 'debug', '>Value (from select) : '.$stateRequest);
            } elseif ($this->getSubType() == 'slider') {
              $stateRequest = $_options['slider'];
              log::add('weback', 'debug', '>Value (from slider) : '.$stateRequest);
            } else {
              $stateRequest = $_options['message'];
              log::add('weback', 'debug', '>Value (from message) : '.$stateRequest);
            }

            // Vérification du jeton avant envoie de la commande
            if (weback::awsTokenValidity() == true) {
              // JETON expiré renouvellement requis
              if (weback::getAWScredential()) {
                // Token AWS à jour
                log::add('weback', 'debug', 'Renouvellement OK poursuite de l\'envoie de la commande');
              } else {
                log::add('weback', 'error', 'Envoi commande NOK (Jeton AWS expiré, renouvellement echoué)');
                break;
              }
            }

            while ($retry < 4) {
                if (weback::SendAction($eqToSendAction, array($actRequest => $stateRequest)) == true) {
                  break;
                }
                if ($retry > 1) {
                  log::add('weback', 'warning', 'Echec d\'envoi de la commande au robot. Nouvel essai... ('.$retry.'x)');
                }
                $retry++;
            }
            if ($retry == 4) {
                log::add('weback', 'error', 'Echec d\'envoi de la commande au robot. (4x)');
            }
        }
     }
    /*     * **********************Getteur Setteur*************************** */
}
