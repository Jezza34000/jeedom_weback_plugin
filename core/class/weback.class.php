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
require_once __DIR__ . '/../../resources/vendor/aws-autoloader.php';

use Aws\Lambda\LambdaClient;

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
         if (weback::getToken() == true) {
               if (weback::getAWScredential() == true) {
                       if (weback::getDeviceList() == true) {
                         log::add('weback', 'debug', '### Recherche robot terminé avec succès!', true);
                         return null;
                       } else {
                         log::add('weback', 'debug', 'Recherche des robots KO > Echec GetDeviceList', true);
                         return "impossible de trouver un robot sur le compte.";
                       }
                 } else {
                   log::add('weback', 'debug', 'Recherche des robots KO > Echec AWS Credentials', true);
                   return "impossible de se connecter.";
                 }
           } else {
             log::add('weback', 'debug', 'Recherche des robots KO > Echec WeBack login', true);
             return "impossible de se connecter à WeBack.";
           }
     }


     public static function getToken() {
       log::add('weback', 'debug', 'Connexion à WeBack-login...');
       if (config::byKey('password', 'weback') != '' && config::byKey('user', 'weback') != '' && config::byKey('country', 'weback') != '') {
         $ch = curl_init();

         $data = array("App_Version" => "android_5.1.9", "Password" => md5(config::byKey('password', 'weback')), "User_Account" => "+".config::byKey('country', 'weback')."-".config::byKey('user', 'weback'));
         $data_string = json_encode($data);

         curl_setopt($ch, CURLOPT_URL, "https://www.weback-login.com/WeBack/WeBack_Login_Ats_V3");
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
          );
         $server_output = curl_exec($ch);
         $json = json_decode($server_output, true);
         log::add('weback', 'debug', 'WeBack answer = ' . print_r($json, true));

         if ($json['Request_Result'] == 'success') {
           //config::save("token", $json['LoginData']['ContextKey'], 'mitsubishi');
           log::add('weback', 'debug', 'Identifiant/mot de passe WeBack-Login OK');
           // Enregistrement des informations de connexion
           config::save("Identity_Pool_Id", $json['Identity_Pool_Id'], 'weback');
           config::save("Developer_Provider_Name", $json['Developer_Provider_Name'], 'weback');
           config::save("End_Point", $json['End_Point'], 'weback');
           config::save("Identity_Id", $json['Identity_Id'], 'weback');
           config::save("Token", $json['Token'], 'weback');
           config::save("Token_Duration", $json['Token_Duration'], 'weback');
           config::save("Region_Info", $json['Region_Info'], 'weback');
           config::save("Configuration_Page_URL", $json['Configuration_Page_URL'], 'weback');
           config::save("Discovery_Page_URL", $json['Discovery_Page_URL'], 'weback');
           config::save("Customer_Service_Card_URL", $json['Customer_Service_Card_URL'], 'weback');
           config::save("Thing_Register_URL", $json['Thing_Register_URL'], 'weback');
           config::save("Thing_Register_URL_Signature", $json['Thing_Register_URL_Signature'], 'weback');
           return true;
         } else {
           log::add('weback', 'debug', 'Erreur CURL = ' . curl_error($ch));
           log::add('weback', 'error', 'Echec de connexion à WeBack-Login : '.$json['Fail_Reason']);
           return false;
         }
         curl_close($ch);
       } else {
         log::add('weback', 'info', 'Information de connexion à WeBack manquantes');
         return false;
       }
     }

     public static function getAWScredential() {
       log::add('weback', 'debug', 'Connexion à AWS Cognito...');
         $ch = curl_init();
         $data = array("IdentityId" => config::byKey('Identity_Id', 'weback'), "Logins" => array("cognito-identity.amazonaws.com" => config::byKey('Token', 'weback')));
         $data_string = json_encode($data);

         log::add('weback', 'debug', 'JSON AWS to send = ' . print_r($data_string, true));

         curl_setopt($ch, CURLOPT_URL, "https://cognito-identity.eu-central-1.amazonaws.com");
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-amz-json-1.1',
            'X-Amz-Target: com.amazonaws.cognito.identity.model.AWSCognitoIdentityService.GetCredentialsForIdentity',
            'Content-Length: ' . strlen($data_string))
          );
         $server_output = curl_exec($ch);

         $json = json_decode($server_output, true);
         log::add('weback', 'debug', 'AWS Cognito answer = ' . print_r($json, true));

         if ($json['Credentials'] != NULL) {
           //config::save("token", $json['LoginData']['ContextKey'], 'mitsubishi');
           log::add('weback', 'debug', 'Information de connexion AWS Cognito OK');
           // Enregistrement des informations de connexion
           config::save("AccessKeyId", $json['Credentials']['AccessKeyId'], 'weback');
           config::save("Expiration", $json['Credentials']['Expiration'], 'weback');
           config::save("SecretKey", $json['Credentials']['SecretKey'], 'weback');
           config::save("SessionToken", $json['Credentials']['SessionToken'], 'weback');
           return true;
         } else {
           log::add('weback', 'debug', 'Erreur CURL = ' . curl_error($ch));
           log::add('weback', 'error', 'Echec d\'obtention des informations de connexion depuis AWS Cognito');
           return false;
         }
         curl_close($ch);
     }

     public static function getDeviceList() {
       log::add('weback', 'debug', 'Récupération des informations depuis AWS Lambda Device_Manager_V2...');
       $client = LambdaClient::factory([
           'version' => 'latest',
           'region'  => config::byKey('Region_Info', 'weback'),
           'credentials' => [
                'key'    => config::byKey('AccessKeyId', 'weback'),
                'secret' => config::byKey('SecretKey', 'weback'),
                'token' => config::byKey('SessionToken', 'weback'),]
       ]);

       $payload = array('Device_Manager_Request' => 'query',
            'Identity_Id' => config::byKey('Identity_Id', 'weback'),
            'Region_Info' => config::byKey('Region_Info', 'weback'));

       $result = $client->invoke(array(
           'FunctionName' => 'Device_Manager_V2',
           'InvocationType' => 'RequestResponse',
           'Payload' => json_encode($payload),
       ));

      log::add('weback', 'debug', 'Payload=' . print_r(array(
          'FunctionName' => 'Device_Manager_V2',
          'InvocationType' => 'RequestResponse',
          'Payload' => json_encode($payload),
      ), true));

      $return = (string)$result['Payload']->getContents();
      //var_dump((string)$result->get('Payload')); => OK!
      log::add('weback', 'debug', 'AWS Lambda answer : ' . $return);
      $json = json_decode($return, true);
      //var_dump($json);

       if ($json['Request_Result'] == 'success') {
           log::add('weback', 'info', 'Robot trouvé : ' .$json['Request_Cotent'][0]['Thing_Name']);
           weback::addNewRobot($json);
           return true;
       } else {
         event::add('jeedom::alert', array(
           'level' => 'alert',
           'page' => 'weback',
           'message' => __('Aucun robot trouvé', __FILE__)));
          log::add('weback', 'info', 'Aucun robot trouvé');
          return false;
       }
     }

    public static function addNewRobot($device) {
      $robot=weback::byLogicalId($device['Request_Cotent'][0]['Thing_Name'], 'weback');
      if (!is_object($robot)) {
        log::add('weback', 'info', $device['Request_Cotent'][0]['Thing_Nick_Name']. ' > Ce robot est inconnu, ajout dans les nouveaux objets');
        $robot = new weback();
        $robot->setEqType_name('weback');
        $robot->setLogicalId($device['Request_Cotent'][0]['Thing_Name']);
        $robot->setIsEnable(1);
        $robot->setIsVisible(1);
        $robot->setName($device['Request_Cotent'][0]['Thing_Nick_Name']." ".$device['Request_Cotent'][0]['Sub_type']);
        $robot->setConfiguration('Thing_Nick_Name', $device['Request_Cotent'][0]['Thing_Nick_Name']);
        $robot->setConfiguration('Sub_type', $device['Request_Cotent'][0]['Sub_type']);
        $robot->setConfiguration('Thing_Name', $device['Request_Cotent'][0]['Thing_Name']);
        $robot->setConfiguration('Mac_Adress', str_replace("-", ":", substr($device['Request_Cotent'][0]['Thing_Name'],-17)));
        $robot->save();
        $robot->loadCmdFromConf($device['Request_Cotent'][0]['Sub_type']);
      } else {
        log::add('weback', 'info', $device['Request_Cotent'][0]['Thing_Nick_Name']. ' > Ce robot est déjà enregistré dans les objets!');
      }
    }

    public static function getDeviceShadow($calledLogicalID){
      log::add('weback', 'debug', 'Mise à jour Shadow Device depuis IOT-Data...');
      log::add('weback', 'debug', 'End_Point='.config::byKey('End_Point', 'weback').' / Region_Info='.config::byKey('Region_Info', 'weback'));
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
      $result = $IoT->getThingShadow([
          'thingName' => $calledLogicalID,
      ]);
      $return = (string)$result['payload']->getContents();
      log::add('weback', 'debug', 'IOT Return : ' . $return);
      $shadowJson = json_decode($return, false);
      log::add('weback', 'debug', 'Mise à jours OK pour : '.$calledLogicalID);
      //$weback->checkAndUpdateCmd('working_status', $shadowJson->state->reported->working_status);
      $wback=weback::byLogicalId($calledLogicalID, 'weback');
      // Update INFO plugin
      if ($shadowJson->state->reported->undistrub_mode == 'on') {
        $undistrub = true;
      } else {
        $undistrub = false;
      }

      $wstatus = $shadowJson->state->reported->working_status;
      $wback->checkAndUpdateCmd('connected', $shadowJson->state->reported->connected);
      $wback->checkAndUpdateCmd('working_status', $wstatus);
      $wback->checkAndUpdateCmd('voice_switch', $shadowJson->state->reported->voice_switch);
      $wback->checkAndUpdateCmd('voice_volume', $shadowJson->state->reported->volume);
      $wback->checkAndUpdateCmd('carpet_pressurization', $shadowJson->state->reported->carpet_pressurization);
      $wback->checkAndUpdateCmd('undistrub_mode', $undistrub);
      $wback->checkAndUpdateCmd('fan_status', $shadowJson->state->reported->fan_status);
      $wback->checkAndUpdateCmd('water_level', $shadowJson->state->reported->water_level);
      $wback->checkAndUpdateCmd('error_info', $shadowJson->state->reported->error_info);
      $wback->checkAndUpdateCmd('battery_level', $shadowJson->state->reported->battery_level);
      $wback->checkAndUpdateCmd('continue_clean', $shadowJson->state->reported->continue_clean);
      $wback->checkAndUpdateCmd('clean_area', round($shadowJson->state->reported->clean_area, 1));
      $wback->checkAndUpdateCmd('clean_time', round(($shadowJson->state->reported->clean_time)/60,0));
      $wback->checkAndUpdateCmd('planning_rect_x', implode(",",$shadowJson->state->reported->planning_rect_x));
      $wback->checkAndUpdateCmd('planning_rect_y', implode(",",$shadowJson->state->reported->planning_rect_y));
      $wback->checkAndUpdateCmd('goto_point', implode(",",$shadowJson->state->reported->goto_point));
      //$wback->checkAndUpdateCmd('laser_goto_path_x', implode(",",$shadowJson->state->reported->laser_goto_path_x));
      //$wback->checkAndUpdateCmd('laser_goto_path_y', implode(",",$shadowJson->state->reported->laser_goto_path_y));

      $result = weback::DeterminateSimpleState($wstatus, $shadowJson->state->reported->error_info);
        if ($result == "docked") {
          $wback->checkAndUpdateCmd('isworking', 0);
          $wback->checkAndUpdateCmd('isdocked', 1);
        } elseif ($result == "working") {
          $wback->checkAndUpdateCmd('isdocked', 0);
          $wback->checkAndUpdateCmd('isworking', 1);
        } else {
          $wback->checkAndUpdateCmd('isdocked', 0);
          $wback->checkAndUpdateCmd('isworking', 0);
          log::add('weback', 'debug', 'Aucune equivalence Docked/Working trouvé pour l\'état : '.$wstatus);
        }
      }

    public static function IsRenewlRequired(){
      $date_utc = new DateTime("now", new DateTimeZone("UTC"));
      $tsnow = $date_utc->getTimestamp();
      $tsexpiration = config::byKey('Expiration', 'weback');
      log::add('weback', 'debug', 'Vérification validité TOKAN AWS ('.$tsexpiration.')');
      if ($tsexpiration < $tsnow) {
        log::add('weback', 'debug', '> Expired');
        return true;
      } else {
        log::add('weback', 'debug', '> OK, valid');
        return false;
      }
    }

    public static function updateStatusDevices($calledLogicalID){
      log::add('weback', 'debug', 'UpdateStatus de '.$calledLogicalID.' demandé');
      // Vérification si le TOKEN AWS IOT est toujours valable
      if (weback::IsRenewlRequired() == false){
        weback::getDeviceShadow($calledLogicalID);
      } else {
            log::add('weback', 'debug', 'Renouvellement du jeton requis...');
            // Renouvellement du TOKEN
            if (weback::getAWScredential()) {
              // TOKEN AWS OK
              log::add('weback', 'debug', 'Renouvellement OK poursuite de la MAJ');
              weback::getDeviceShadow($calledLogicalID);
            } else {
                  // Renouvellement de la connexion à WeBack
                  if (weback::getToken()) {
                    // Connexion WeBackOK
                    weback::getAWScredential();
                    weback::getDeviceShadow($calledLogicalID);
                  } else {
                    log::add('weback', 'debug', 'CRON > Impossible de mettre à jour connexion echouée à WeBack');
                  }
            }
      }
    }

    public static function SendAction($calledLogicalID, $action, $param) {
      log::add('weback', 'debug', 'Envoi d\'une commande au robot: '.$calledLogicalID.' Action demandé : '.$action. '/'.$param);
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
                       array(
                        $action => $param
                       ),
              )
          );
      $payload = json_encode($data);

      $result = $IoT->updateThingShadow([
          'payload' => $payload,
          'thingName' => $calledLogicalID,
      ]);
      $return = (string)$result['payload']->getContents();
      log::add('weback', 'debug', 'IOT Return : ' . $return);
      $shadowJson = json_decode($return, false);
      //log::add('weback', 'debug', 'OK> Mise à jours des INFO de ');
    }

    public static function DeterminateSimpleState($working_status, $error){
      /*
      ==================WORKING
      ROBOT_WORK_STATUS_STOP("Hibernating"),
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
      */

      $dockedStatus = array("Charging", "PileCharging", "DirCharging", "ChargeDone");
      $workingStatus = array("Relocation", "AutoClean", "SmartClean", "EdgeClean", "SpotClean", "RoomClean",
      "MopClean", "Standby", "PlanningLocation", "StrongClean", "PlanningRect", "ZmodeClean", "BackCharging");
      // Docked Status
      if (in_array($working_status, $dockedStatus)) {
          return "docked";
      }
      // Working Status
      if (in_array($working_status, $workingStatus)) {
          return "working";
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
            log::add('weback', 'debug', 'Process d\'acutalisation démarré pour : '.$webackrbt->getHumanName());
            weback::updateStatusDevices($webackrbt->getLogicalId());
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



    /*     * *********************Méthodes d'instance************************* */

 // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {
    }

 // Fonction exécutée automatiquement après la création de l'équipement
    public function postInsert() {

    }

    public function loadCmdFromConf($_type) {
      log::add('weback', 'debug', 'Chargement des commandes du robots depuis le fichiers JSON : '.$_type);
      if (!is_file(dirname(__FILE__) . '/../config/devices/' . $_type . '.json')) {
        log::add('weback', 'error', 'Fichier de configuration de robot introuvable !');
        return;
      }
      $content = file_get_contents(dirname(__FILE__) . '/../config/devices/' . $_type . '.json');
      log::add('weback', 'error', 'Content : '.$content);
      if (!is_json($content)) {
        log::add('weback', 'error', 'Format du fichier de configuration n\'est pas du JSON valide !');
        return;
      }
      $device = json_decode($content, true);
      if (!is_array($device) || !isset($device['commands'])) {
        log::add('weback', 'error', 'Pas de configuration valide trouvé dans le fichier');
        return true;
      }
      log::add('weback', 'debug', 'Nombre de commandes à ajouter : '.count($device['commands']));
      foreach ($device['commands'] as $command) {
        $cmd = null;
        foreach ($this->getCmd() as $liste_cmd) {
          if ((isset($command['logicalId']) && $liste_cmd->getLogicalId() == $command['logicalId'])
          || (isset($command['name']) && $liste_cmd->getName() == $command['name'])) {
            $cmd = $liste_cmd;
            break;
          }
        }
        log::add('weback', 'debug', 'Ajout de : '.$command['name']);
        if ($cmd == null || !is_object($cmd)) {
          $cmd = new webackCmd();
          $cmd->setEqLogic_id($this->getId());
          utils::a2o($cmd, $command);
          $cmd->save();
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

    /*
     * Non obligatoire : permet de modifier l'affichage du widget (également utilisable par les commandes)
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * Non obligatoire : permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire : permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class webackCmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*
      public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

  // Exécution d'une commande
     public function execute($_options = array()) {
      $eqLogic = $this->getEqLogic();
      $eqToSendAction = $eqLogic->getlogicalId();

      log::add('weback', 'debug', 'Execute '.$this->getLogicalId());

       switch ($this->getLogicalId()) {
          case 'refresh':
            log::add('weback', 'debug', 'Refresh (MANUEL) demandé sur : '.$eqToSendAction);
            weback::updateStatusDevices($eqToSendAction);
            break;
          case 'autoclean':
            weback::SendAction($eqToSendAction, "working_status", "AutoClean");
            break;
          case 'standby':
            weback::SendAction($eqToSendAction, "working_status", "Standby");
            break;
          case 'backcharging':
            weback::SendAction($eqToSendAction, "working_status","BackCharging");
            break;
          case 'setaspiration':
              log::add('weback', 'debug', 'SetAspiration='.$_options['select']);
              if ($_options['select'] == "1") {
                $action = "Quiet";
              } elseif ($_options['select'] == "2") {
                $action = "Normal";
              } elseif ($_options['select'] == "3") {
                $action = "Strong";
              } else {
                log::add('weback', 'debug', 'Impossible de déterminer l\'action demandé par la liste N° action:'.$_options['select']);
              }
              break;
          case 'setwaterlevel':
            log::add('weback', 'debug', 'SetWater='.$_options['select']);
            if ($_options['select'] == "1") {
              $action = "Low";
            } elseif ($_options['select'] == "2") {
              $action = "Default";
            } elseif ($_options['select'] == "3") {
              $action = "High";
            } else {
              log::add('weback', 'debug', 'Impossible de déterminer l\'action demandé par la liste N° action:'.$_options['select']);
            }
            break;
          case 'cleanspot':
            log::add('weback', 'debug', 'Spot info :'.$_options['message']);
            break;
          case 'cleanroom':
            log::add('weback', 'debug', 'Room info :'.$_options['message']);
            break;
        }

     }

    /*     * **********************Getteur Setteur*************************** */
}
