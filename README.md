# Plugin pour utiliser les robots Neatsvor / Tesvor / Orfeld sur Jeedom

* Permet le contrôle des robots aspirateur de marque Neatsvor / Tesvor / Orfeld
Via l'application WeBack/Tesvor. 
L'API utilisée est non officielle, et basée sur le reverse engineering trouvé sur ce dépôt : https://github.com/opravdin/weback-unofficial

Fonctionnel avec les modèles suivant : 
* Neatsvor X600
* Neatsvor X500
* Neatsvor X520
* Orfeld X503
* Tesvor M1
* Tesvor S6
* Tesvor T8
* Tesvor V300

(non testé avec les autres)

* Contrôle du robot disponible :

-Nettoyage Auto / Pause / Retour à la base

-Réglage de la vitesse d'aspiration 

-Réglage du débit d'eau

Certains paramètres sont remontés par le robot mais je n'ai pas compris leur usage (en fonction des modèles) :

-carpet_pressurization (true/false)

-continue_clean (true/false)

-cliff_detect (enable/disable)

-left_water (-1)

-optical_flow (on/off)

-final_edge (on/off)


* Sur la branche beta (mais non fonctionnel complètement)

-Nettoyage coin (spot)

-Nettoyage pièce (rectangle)

