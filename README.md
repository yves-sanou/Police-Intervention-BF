# Police Intervention BF — V5 Leaflet / OpenStreetMap

Application web de coordination des interventions, de gestion des équipes et de diffusion des alertes SOS.

## Fonctionnalités présentes dans cette version
- Authentification des utilisateurs et gestion des rôles / permissions
- Tableau de bord du centre de contrôle
- Gestion des interventions : création, édition et suivi
- Gestion des équipes et de leurs membres
- Cartographie Leaflet avec fonds OpenStreetMap
- API de remontée des positions GPS des équipes
- Alertes SOS, diffusion, prise en charge et résolution
- Notifications et service worker / manifest PWA
- Interfaces d'administration

## Stack
- PHP 8+
- MySQL / MariaDB
- PDO
- JavaScript, CSS
- Leaflet / OpenStreetMap

## Installation locale (Laragon / XAMPP)
1. Cloner le dépôt dans le dossier web local.
2. Créer une base MySQL nommée `police_intervention_bf`.
3. Importer `database/schema.sql`.
4. Copier `config/config.example.php` vers `config/config.php` et adapter les constantes de connexion.
5. Ouvrir `http://localhost/Police-Intervention-BF/login.php` (adapter le chemin au dossier installé).

Le fichier `config/config.php` est volontairement ignoré par Git : il doit rester propre à chaque environnement.

## Sécurité et données GPS
- Utiliser HTTPS en production, notamment pour l'accès à la géolocalisation navigateur.
- Configurer un compte MySQL dédié avec privilèges limités ; ne pas utiliser le compte root.
- Remplacer tous les identifiants de démonstration et définir des mots de passe robustes.
- Les coordonnées GPS et alertes SOS sont des données sensibles : limiter les accès, informer les utilisateurs, fixer une durée de conservation et protéger les sauvegardes.
- Vérifier les permissions, les journaux et les paramètres d'hébergement avant toute mise en production.

## Limites
Cette publication correspond au code fourni dans l'archive V5. Elle ne constitue pas une certification de sécurité ni une validation opérationnelle pour un service d'urgence réel. Les cartes OpenStreetMap publiques sont soumises à leur politique d'utilisation ; prévoir un fournisseur adapté au trafic de production.

## Auteur
**Coteamdev** — [GitHub](https://github.com/yves-sanou) · [Portfolio](https://portfolio.coteamdev.tech)
