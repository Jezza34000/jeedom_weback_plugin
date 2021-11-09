# Plugin Jeedom pour utiliser les robots aspirateur : Neatsvor / Tesvor / Orfeld / Abir / ...

* Permet le contrôle des robots aspirateur de marque Neatsvor, Tesvor, Orfeld, etc... la liste des robots utilisant cette API est longue le détail complet est disponible ici : https://github.com/Jezza34000/weback/blob/master/robotcompatible

Via les applications : WeBack/Tesvor
(L'API utilisée est non officielle, et basée sur le reverse engineering trouvé sur ce dépôt Python : https://github.com/opravdin/weback-unofficial)

ATTENTION : 2 version HARDWARE sont présentent sur le marché. Ce plugin n'est pas compatible avec les robots de la marque qui utilisent les plateformes : Tuya/SmartLife

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

