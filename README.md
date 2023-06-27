# Plugin Jeedom pour utiliser les robots aspirateur : Neatsvor / Tesvor / Orfeld / Abir / ...

* Permet le contrôle des robots aspirateur de marque Neatsvor, Tesvor, Orfeld, etc... la liste des robots utilisant cette API est longue le détail complet est disponible ici : https://github.com/Jezza34000/weback/blob/master/robotcompatible

* Via les applications : WeBack/Tesvor
(L'API utilisée est non officielle, et basée sur le reverse engineering trouvé sur ce dépôt Python : https://github.com/opravdin/weback-unofficial)

> Important : Interruption du service WeBack et impact sur ce plugin Jeedom

_Chère communauté de Jeedom,_

_Je souhaite attirer votre attention sur une information importante concernant le service WeBack et son impact sur le plugin Jeedom que vous utilisez pour contrôler les aspirateurs robots via leur API non officielle._

_En raison de la décision de WeBack de cesser ses activités, il n'est plus possible d'enregistrer de nouveaux robots sur leur plateforme cloud. Cependant, je tiens à préciser que, pour une durée indéterminée, les robots précédemment enregistrés peuvent encore fonctionner en utilisant l'infrastructure cloud de WeBack. Il est donc crucial de ne pas modifier votre compte WeBack si vous souhaitez continuer à utiliser l'application WeBack avec vos robots existants._

_Compte tenu de ce changement, le plugin Jeedm spécialement conçu pour l'utilisation de WeBack n'a plus d'utilité pratique puisque l'enregistrement de nouveaux robots n'est plus possible. Par conséquent, la maintenance et le développement ultérieur de ce composant seront interrompus._

_De plus, selon les fabricants de robots et leur SAV, il existe des solutions alternatives pour modifier le firmware et migrer vers d'autres plateformes. Il est conseillé de contacter directement le SAV du fabricant pour explorer les options potentielles en fonction de leur orientation choisie. Par exemple, dans le cas du robot Neatsvor X600, une mise à jour est proposée pour passer de WeBack à Tuya._

_Merci de votre compréhension._


**ATTENTION** : 2 version HARDWARE sont présentent sur le marché. Ce plugin n'est pas compatible avec les robots de la marque qui utilisent les plateformes : Tuya/SmartLife

(Ne connaissant pas toutes les fonctionnalités/options disponible pour chaque modèle, il est possible que les options ne soient pas remontés dans Jeedom, n'hésitez pas à ouvrir une issue pour que fasse je les modifications nécéssaire)

Les modèles de robot présent sur le cloud WeBack mais non reconnu sont désormais ajouté en mode "générique" avec les fonctions basiques.


* Contrôle du robot disponible :

-Mode ménage possible ( Automatique / Spot / Bordure / Zmode / Renforcée )

-Réglage de la vitesse d'aspiration ( 4 modes en fonction des modèles )

-Réglage du débit d'eau (3 modes )

-Localiser le robot

-Effacer la carte

-Activer/Désactiver le SON 

-Activer/Desactiver le mode "Ne pas déranger"

-Régler le volume du robot



Certains paramètres sont remontés par le robot mais je n'ai pas compris leur usage (en fonction des modèles) :

-carpet_pressurization (true/false)

-continue_clean (true/false)

-cliff_detect (enable/disable)

-left_water (-1)

-optical_flow (on/off)

-final_edge (on/off)



* Fonctions partiellement supportées : 

-Nettoyage coin (spot/emplacement spécifique) :
Pour utiliser cette fonction, vous devez lancer le robot en nettoyage spot/emplacement spécifique depuis votre application, une fois que le robot démarre, récupérer les coordonnées qui apparaissent dans l'info "goto_point". Ensuite utiliser la fonction "Nettoyer spot" avec ces coordonnées là.

-Nettoyage pièce (rectangle/zone) :
Pour utiliser cette fonction, vous devez lancer le robot en nettoyage rectangle/zone depuis votre application, une fois que le robot démarre, récupérer les coordonnées qui apparaissent dans les informations "planning_rect_x" & "planning_rect_y". Ensuite utiliser la fonction "Nettoyer pièce" avec ces coordonnées là.

