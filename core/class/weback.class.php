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

    public static function dependancy_info() {
        log::add("weback", 'debug', "Vérification des dépendances...", $_logicalId);
        $return = array();
        $return['log'] = log::getPathToLog(__CLASS__ . '_update');
        $return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependency';
        if (file_exists(jeedom::getTmpFolder(__CLASS__) . '/dependency')) {
            $return['state'] = 'in_progress';
        } else {
            if (exec(system::getCmdSudo() . system::get('cmd_check') . '-Ec "python3\-requests|python3\-boto3"') < 2) { // adaptez la liste des paquets et le total
                $return['state'] = 'nok';
            } elseif (exec(system::getCmdSudo() . 'pip3 list | grep -Ewc "weback-unofficial"') < 1) { // adaptez la liste des paquets et le total
                $return['state'] = 'nok';
            } else {
                $return['state'] = 'ok';
                log::add("weback", 'debug', "Dépendances OK", $_logicalId);
            }
        }
        return $return;
    }

    public static function dependancy_install() {
        log::remove(__CLASS__ . '_update');
        log::add("weback", 'debug', "Installation des dépendances...", $_logicalId);
        return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependency', 'log' => log::getPathToLog(__CLASS__ . '_update'));
    }


    /**
     * Recherche les équipements sur clique du bouton
     *
     * return Array of Device
     */

     public static function discoverRobot()
     {
         log::add('weback', 'debug', 'Démarrage de la recherche des robots...', true);
         weback::getToken();
         weback::getAWScredential();
         weback::getDeviceList();
         return null;
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
         curl_close($ch);
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
         } else {
           log::add('weback', 'debug', 'Echec de connexion à WeBack-Login :'.$json['Fail_Reason']);
         }
       }
     }

     public static function getAWScredential() {
       log::add('weback', 'debug', 'Récupération des informations de connexion de AWS Cognito...');
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
           config::save("IdentityId", $json['IdentityId'], 'weback');
           config::save("SessionToken", $json['Credentials']['SessionToken'], 'weback');
         } else {
           log::add('weback', 'debug', 'Erreur CURL = ' . curl_error($ch));
           log::add('weback', 'debug', 'Echec d\'obtention des informations de connexion depuis AWS Cognito');
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
         /*event::add('jeedom::alert', array(
           'level' => 'success',
           'page' => 'weback',
           'message' => __('Robot trouvé : '.$json['Request_Cotent'][0]['Thing_Name'], __FILE__)));*/
           log::add('weback', 'debug', 'Robot trouvé : ' .$json['Request_Cotent'][0]['Thing_Name']);
           // sauvegarde des informations
           config::save("Thing_Name", $json['Request_Cotent'][0]['Thing_Name'], 'weback');
           config::save("Thing_Nick_Name", $json['Request_Cotent'][0]['Thing_Nick_Name'], 'weback');
           config::save("Sub_type", $json['Request_Cotent'][0]['Sub_type'], 'weback');
           config::save("Image_Url", $json['Request_Cotent'][0]['Image_Url'], 'weback');

           $robot=weback::byLogicalId($json['Request_Cotent'][0]['Thing_Nick_Name'].$json['Request_Cotent'][0]['Thing_Name'], 'weback');
           if (!is_object($robot)) {
             log::add('weback', 'debug', $json['Request_Cotent'][0]['Thing_Nick_Name']. ' > Ce robot est inconnu, ajout dans les nouveaux objets');
             $robot = new weback();
             $robot->setEqType_name('weback');
             $robot->setLogicalId($json['Request_Cotent'][0]['Thing_Nick_Name'].$json['Request_Cotent'][0]['Thing_Name']);
             $robot->setIsEnable(1);
             $robot->setIsVisible(1);
             $robot->setName($json['Request_Cotent'][0]['Thing_Nick_Name']." ".$json['Request_Cotent'][0]['Sub_type']);
             $robot->setConfiguration('Thing_Nick_Name', $json['Request_Cotent'][0]['Thing_Nick_Name']);
             $robot->setConfiguration('Sub_type', $json['Request_Cotent'][0]['Sub_type']);
             $robot->setConfiguration('Thing_Name', $json['Request_Cotent'][0]['Thing_Name']);
             $robot->setConfiguration('Mac_Adress', str_replace("-", ":", substr($json['Request_Cotent'][0]['Thing_Name'],-17)));
             $robot->save();
           } else {
             log::add('weback', 'debug', $json['Request_Cotent'][0]['Thing_Nick_Name']. ' > Ce robot est déjà enregistré dans les objets!');
           }
       } else {
         event::add('jeedom::alert', array(
           'level' => 'alert',
           'page' => 'weback',
           'message' => __('Aucun robot trouvé', __FILE__)));
          log::add('weback', 'debug', 'Aucun robot trouvé');
       }

       /*log::add('weback', 'debug', '==== Amazon Lambda ====');
       log::add('weback', 'debug', 'Status = '.$result->get('StatusCode'));
       log::add('weback', 'debug', 'FunctionError  = '.$result->get('FunctionError'));
       log::add('weback', 'debug', 'LogResult   = '.$result->get('LogResult'));
       log::add('weback', 'debug', 'Payload   = '.$result->get('Payload'));*/
     }

    public static function addNewRobot($device) {

      $robot=weback::byLogicalId($device['BuildingID'] . $device['DeviceID'], 'mitsubishi');
      if (!is_object($robot)) {
        $robot = new weback();
        $robot->setEqType_name('mitsubishi');
        $robot->setLogicalId($device['BuildingID'] . $device['DeviceID']);
        $robot->setIsEnable(1);
        $robot->setIsVisible(1);
        $robot->setName($device['DeviceName']);
        $robot->setConfiguration('DeviceID', $device['DeviceID']);
        $robot->setConfiguration('BuildingID', $device['BuildingID']);
        $robot->setConfiguration('DeviceType', $device['Device']['DeviceType']);//0 air/air, 1 air/water
        $robot->setConfiguration('SubType', 'air');
        $robot->save();
      }
    }

    public static function getDeviceShadow(){
      weback::IsRenewlRequired();
      log::add('weback', 'debug', 'Mise à jour Shadow Device depuis Iot-Data...');
      log::add('weback', 'debug', 'ThingName ='.config::byKey('Thing_Name', 'weback'));
      log::add('weback', 'debug', 'Region_Info ='.config::byKey('Region_Info', 'weback'));
      log::add('weback', 'debug', 'End_Point ='.config::byKey('End_Point', 'weback'));
      $IoT = new Aws\IotDataPlane\IotDataPlaneClient([
          //'endpoint' => 'https://'.config::byKey('End_Point', 'weback'),
          'endpointAddress' => 'https://'.config::byKey('End_Point', 'weback'),
          'endpointType' => 'iot:Data-ATS',
          'scheme'  => 'https',
          'version' => 'latest',
          'region'  => config::byKey('Region_Info', 'weback'),
          'credentials' => [
               'key'    => config::byKey('AccessKeyId', 'weback'),
               'secret' => config::byKey('SecretKey', 'weback'),
               'token' => config::byKey('SessionToken', 'weback'),]
      ]);
      $result = $IoT->getThingShadow([
          'thingName' => config::byKey('Thing_Name', 'weback'),
      ]);
      var_dump((string)$result->get('payload'));
    }

    public static function IsRenewlRequired(){
      log::add('weback', 'debug', 'TS validity Checking...');
      $tsnow = new DateTime();
      $tsexpiration = config::byKey('AccessKeyId', 'weback');
      if ($tsexpiration < $tsnow) {
        log::add('weback', 'debug', '=> OK VALID');
        return true;
      } else {
        log::add('weback', 'debug', '=> EXPIRED');
        return false;
      }
    }

    public static function updateStatusDevices(){
      log::add('weback', 'debug', 'CRON > UpdateStatusDevices');
    }


    public function loadCmdFromConf($_type) {
      if (!is_file(dirname(__FILE__) . '/../config/devices/' . $_type . '.json')) {
        return;
      }
      $content = file_get_contents(dirname(__FILE__) . '/../config/devices/' . $_type . '.json');
      if (!is_json($content)) {
        return;
      }
      $device = json_decode($content, true);
      if (!is_array($device) || !isset($device['commands'])) {
        return true;
      }
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
          $cmd = new webackCmd();
          $cmd->setEqLogic_id($this->getId());
          utils::a2o($cmd, $command);
          $cmd->save();
        }
      }
    }

  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */

    /*     * ***********************Methode static*************************** */


     //Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron() {
        weback::updateStatusDevices()
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

      $domogeekCmd = new webackCmd();
      $domogeekCmd->setName(__('Rafraichir', __FILE__));
      $domogeekCmd->setEqLogic_id($this->id);
      $domogeekCmd->setType('action');
      $domogeekCmd->setSubType('other');
      $domogeekCmd->setLogicalId('refresh');
      $domogeekCmd->save();

      $domogeekCmd = new webackCmd();
      $domogeekCmd->setName(__('Etat', __FILE__));
      $domogeekCmd->setEqLogic_id($this->id);
      $domogeekCmd->setConfiguration('data', 'etatrobot');
      $domogeekCmd->setUnite('');
      $domogeekCmd->setType('info');
      $domogeekCmd->setEventOnly(1);
      $domogeekCmd->setSubType('string');
      $domogeekCmd->setIsHistorized(0);
      $domogeekCmd->setLogicalId('etatrobot');
      $domogeekCmd->save();

      $domogeekCmd = new webackCmd();
      $domogeekCmd->setName(__('Etat détaillé', __FILE__));
      $domogeekCmd->setEqLogic_id($this->id);
      $domogeekCmd->setConfiguration('data', 'etatdetaillerobot');
      $domogeekCmd->setUnite('');
      $domogeekCmd->setType('info');
      $domogeekCmd->setEventOnly(1);
      $domogeekCmd->setSubType('string');
      $domogeekCmd->setIsHistorized(0);
      $domogeekCmd->setLogicalId('etatdetaillerobot');
      $domogeekCmd->save();

      $domogeekCmd = new webackCmd();
      $domogeekCmd->setName(__('Nettoyage auto', __FILE__));
      $domogeekCmd->setEqLogic_id($this->id);
      $domogeekCmd->setType('action');
      $domogeekCmd->setSubType('other');
      $domogeekCmd->setLogicalId('smartcleanrobot');
      $domogeekCmd->save();

      $domogeekCmd = new webackCmd();
      $domogeekCmd->setName(__('Pause', __FILE__));
      $domogeekCmd->setEqLogic_id($this->id);
      $domogeekCmd->setType('action');
      $domogeekCmd->setSubType('other');
      $domogeekCmd->setLogicalId('pauserobot');
      $domogeekCmd->save();

      $domogeekCmd = new webackCmd();
      $domogeekCmd->setName(__('Retour à la base', __FILE__));
      $domogeekCmd->setEqLogic_id($this->id);
      $domogeekCmd->setType('action');
      $domogeekCmd->setSubType('other');
      $domogeekCmd->setLogicalId('returntohomerobot');
      $domogeekCmd->save();

      $domogeekCmd = new webackCmd();
      $domogeekCmd->setName(__('Batterie', __FILE__));
      $domogeekCmd->setEqLogic_id($this->id);
      $domogeekCmd->setConfiguration('data', 'batterierobot');
      $domogeekCmd->setUnite('');
      $domogeekCmd->setType('info');
      $domogeekCmd->setEventOnly(1);
      $domogeekCmd->setSubType('string');
      $domogeekCmd->setIsHistorized(0);
      $domogeekCmd->setLogicalId('batterierobot');
      $domogeekCmd->save();

      $domogeekCmd = new webackCmd();
      $domogeekCmd->setName(__('Puissance aspiration', __FILE__));
      $domogeekCmd->setEqLogic_id($this->id);
      $domogeekCmd->setConfiguration('data', 'puissanceaspirationrobot');
      $domogeekCmd->setUnite('');
      $domogeekCmd->setType('info');
      $domogeekCmd->setEventOnly(1);
      $domogeekCmd->setSubType('string');
      $domogeekCmd->setIsHistorized(0);
      $domogeekCmd->setLogicalId('puissanceaspirationrobot');
      $domogeekCmd->save();

      $domogeekCmd = new webackCmd();
      $domogeekCmd->setName(__('Durée ménage', __FILE__));
      $domogeekCmd->setEqLogic_id($this->id);
      $domogeekCmd->setConfiguration('data', 'dureemenage');
      $domogeekCmd->setUnite('');
      $domogeekCmd->setType('info');
      $domogeekCmd->setEventOnly(1);
      $domogeekCmd->setSubType('string');
      $domogeekCmd->setIsHistorized(0);
      $domogeekCmd->setLogicalId('dureemenage');
      $domogeekCmd->save();

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

     }

    /*     * **********************Getteur Setteur*************************** */
}
