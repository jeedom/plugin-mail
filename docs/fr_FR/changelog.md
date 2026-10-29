# Changelog Mail

>**IMPORTANT**
>
>Pour rappel s'il n'y a pas d'information sur la mise à jour, c'est que celle-ci concerne uniquement de la mise à jour de documentation, de traduction ou de texte

- Réception des mails (IMAP) : un équipement peut surveiller une boîte mail et déclencher des scénarios à chaque nouveau mail
    - Nouvel onglet « Réception » : serveur, sécurité (SSL/TLS, STARTTLS), dossier surveillé, marquage des mails comme lus
    - Filtres sur l'expéditeur et sur le sujet
    - Nouvelles commandes info : date de réception, expéditeur, nom de l'expéditeur, destinataire, texte, HTML, pièces jointes et sujet. La commande « Sujet » est mise à jour en dernier pour servir de déclencheur, y compris pour deux mails consécutifs de même sujet
    - Enregistrement optionnel des pièces jointes, avec suppression automatique après un nombre de jours configurable
    - Réception instantanée (IMAP IDLE) avec reconnexion automatique ; les mails reçus pendant un arrêt de Jeedom sont traités au redémarrage
    - Nouveau démon de réception, lancé uniquement si au moins un équipement a la réception activée (aucune dépendance supplémentaire)
- Authentification OAuth2 pour Google (Gmail, Google Workspace) et Microsoft (Microsoft 365, Outlook.com, Hotmail), en envoi (SMTP) comme en réception (IMAP)
    - Nouveau champ « Authentification » sur les équipements, avec pré-remplissage des serveurs
    - Connexion du compte depuis l'équipement, renouvellement automatique de l'accès
    - Identifiants des applications OAuth2 à renseigner dans la configuration du plugin
- Ajout d'une page de configuration du plugin et d'un bouton « Configuration » sur la page du plugin
- Les mots de passe IMAP et les accès OAuth2 sont chiffrés en base
- Mise à jour de la documentation
- Support des images d'équipement personnalisées (Jeedom 4.5)
- Passage en mode dépendances

# 24/08/2024

- Mise à jour de la documentation

# 15/01/2024

- Préparation pour jeedom 4.4
- Meilleur support de php 8
- Mise à jour de la librairie de gestion des mails

# 18/03/2022

- Mise à jour de présentation de l'écran de configuration des équipements

# 06/04/2021

- Possibilité d'utiliser les services Jeedom Cloud pour l'envoi de mails (en beta limité à 5 mails jour)

# 19/11/2020

- Nouvelle présentation de la liste des objets
- Ajout du tag "Compatibilité V4"
- Mise à jour de l'icône du plugin

# 02/08/2019

- Prise en charge de php 7.3

# 30/03/2018

- Correction de la perte de l'icône
