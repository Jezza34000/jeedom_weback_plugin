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
//require_once __DIR__ . '/../../resources/moduletest.php';
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
           config::save("Identity_Id", $json['Credentials']['Identity_Id'], 'weback');
           config::save("SessionToken", $json['Credentials']['SessionToken'], 'weback');
         } else {
           log::add('weback', 'debug', 'Erreur CURL = ' . curl_error($ch));
           log::add('weback', 'debug', 'Echec d\'obtention des informations de connexion depuis AWS Cognito');
         }
         curl_close($ch);
     }

     public static function getDeviceList() {
       $client = LambdaClient::factory([
           'version' => 'latest',
           'region'  => config::byKey('Region_Info', 'weback'),
           'credentials' => [
                'key'    => config::byKey('AccessKeyId', 'weback'),
                'secret' => config::byKey('SecretKey', 'weback'),
                'token' => config::byKey('SessionToken', 'weback'),]
       ]);

       $payload = array('Device_Manager_Request':'query',
            'Identity_Id': config::byKey('Identity_Id', 'weback'),
            'Region_Info': config::byKey('Region_Info', 'weback'));

      log::add('weback', 'debug', 'Payload='.$payload);

       $result = $client->invoke(array(
           'FunctionName' => 'Device_Manager_V2',
           'InvocationType' => 'RequestResponse',
           //'Payload' => $payload,
       ));


       var_dump($result->get('Payload'));
     }


  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */

    /*     * ***********************Methode static*************************** */

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron() {
      }
     */

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
