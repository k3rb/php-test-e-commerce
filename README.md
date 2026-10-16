# Test technique Blissim — PHP / Symfony

Ce dépôt contient les deux parties du test technique :

- une modélisation PostgreSQL dans `blissim.sql` ;
- une application Symfony affichant les produits de Fake Store API et permettant de gérer leurs commentaires.

## Prérequis

- Docker avec Docker Compose ;
- le port `8085` disponible pour l'application ;
- le port `54322` disponible pour PostgreSQL.

## Installation et lancement

J'ai fait une commande unique pour lancer le projet.

Après avoir cloné le dépôt, exécuter depuis sa racine :

```sh
docker compose up --build
```

Le catalogue est ensuite accessible sur [http://localhost:8085](http://localhost:8085).

Pour lancer les conteneurs en arrière-plan :

```sh
docker compose up --build -d
```

Pour arrêter le projet en conservant les données PostgreSQL :

```sh
docker compose down
```

## Configuration PostgreSQL

Les valeurs de développement se trouvent dans `.env` :

| Paramètre | Valeur |
| --- | --- |
| Hôte depuis Symfony | `database` |
| Hôte depuis la machine locale | `127.0.0.1` |
| Port depuis Symfony | `5432` |
| Port depuis la machine locale | `54322` |
| Base de données | `blissim` |
| Utilisateur | `blissim` |
| Mot de passe | `blissim_dev` |

## Partie SQL

Le fichier `blissim.sql` contient :

- les tables `products`, `customers`, `orders` et `orders_products` ;
- un jeu de données de test ;
- les deux requêtes demandées.

## Partie PHP / Symfony

J'ai suivie les directives de l'exercice mais j'ai peut etre sur interprété celle sur les insertions en bdd via PDO.
J'ai pas utilisé Doctrine du coup et j'ai tout fait en SQL a l'ancienne.

Sur le coup ça m'a pas choqué mais une fois le test finit... j'ai eu un gros doute.

Le projet utilise Symfony 7.4 LTS, PHP 8.4, PostgreSQL 16 et Bootstrap 5.3.8.

## Tests

Une fois les conteneurs lancés, exécuter :

```sh
docker compose exec -T app php bin/phpunit
```
