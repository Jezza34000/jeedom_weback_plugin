# Plugin pour utiliser les robots Neatsvor & Tesvor sur Jeedom

* Permet le contrôle des robots aspirateur de marque Neatsvor & Tesvor
Via l'application WeBack/Tesvor. 
L'API utilisée est non officielle, et basée sur le reverse engineering trouvé sur ce dépôt : https://github.com/opravdin/weback-unofficial

* Fonctionnel avec les modèles : 
* Neatsvor X600 
* Neatsvor X520
* Orfeld X503
(non testé avec les autres)

* Contrôle du robot disponible :
-Nettoyage Auto / Pause / Retour à la base
-Réglage de la vitesse d'aspiration 
-Réglage du débit d'eau

Certains paramètres sont remontés par le robot mais je n'ai pas compris leur usage :
-carpet_pressurization (true/false)
-continue_clean (true/false)

* Sur la branche beta (mais non fonctionnel complètement)
Nettoyage coin (spot)
Nettoyage pièce (rectangle)

