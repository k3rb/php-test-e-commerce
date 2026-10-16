# php-test-e-commerce

Test technique pour un poste de développeur back-end PHP / Symfony.

## État du projet

- Exercice 1 : modèle SQL, données de test et requêtes dans `blissim.sql`.
- Exercice 2 : environnement de développement Symfony prêt. Le catalogue et la gestion des commentaires restent à implémenter.

## Prérequis

- Docker et Docker Compose, avec le moteur Docker démarré.
- Le port local `8085` disponible, ou une autre valeur pour `APP_PORT` dans `.env`.

PHP, Composer et PostgreSQL sont fournis par Docker ; leur installation sur la machine hôte est facultative.

## Installation et lancement

Depuis la racine du dépôt :

```sh
docker compose build
docker compose run --rm --no-deps app composer install --no-interaction
docker compose up -d
```

Ouvrir [http://localhost:8085](http://localhost:8085). Une page d'accueil confirme que Symfony fonctionne.

Le projet utilise Symfony 7.4 LTS, PHP 8.4 avec Apache et PostgreSQL 16. Les versions des dépendances PHP sont fixées dans `composer.lock`.

Le code est monté dans le conteneur : les modifications locales sont visibles sans reconstruire l'image. Une modification du Dockerfile ou de la configuration Apache/PHP demande un nouveau `docker compose up -d --build`.

## Base de données et PDO

Les paramètres de développement sont définis dans `.env` :

| Paramètre | Valeur par défaut |
| --- | --- |
| Hôte depuis le conteneur PHP | `database` |
| Port interne | `5432` |
| Base | `blissim` |
| Utilisateur | `blissim` |
| Mot de passe | `blissim_dev` |

Ces identifiants sont destinés à l'environnement local de ce test. Le service PostgreSQL ne publie pas de port sur la machine hôte.

Symfony reçoit `DATABASE_DSN`, `DATABASE_USER` et `DATABASE_PASSWORD` depuis Compose. Le service `PDO` est configuré dans `config/services.yaml`, avec les exceptions activées et les requêtes préparées natives. Il pourra être injecté dans les futurs repositories.

Vérifier la connexion :

```sh
docker compose exec app php bin/console app:database:check
```

Ouvrir une console SQL :

```sh
docker compose exec database psql -U blissim -d blissim
```

Les données persistent dans le volume Docker `database_data`. Les variables `POSTGRES_*` initialisent ce volume à sa première création ; les modifier ensuite ne change pas automatiquement les comptes ou les bases déjà créés.

## Commandes utiles

```sh
# Informations sur Symfony
docker compose exec app php bin/console about

# Vérification des configurations et des templates
docker compose exec app php bin/console lint:container
docker compose exec app php bin/console lint:yaml config
docker compose exec app php bin/console lint:twig templates

# Logs et arrêt (les données PostgreSQL sont conservées)
docker compose logs -f app database
docker compose down

# Redémarrage après installation
docker compose up -d
```

Twig, HttpClient, Form, Validator et la protection CSRF sont installés. Les formulaires Symfony utilisent la protection CSRF avec session.

## Exercice SQL

`blissim.sql` reste indépendant de l'application et n'est pas importé automatiquement dans sa base.

Pour l'exécuter une première fois dans une base séparée :

```sh
docker compose exec database createdb -U blissim blissim_sql
docker compose exec -T database psql -U blissim -d blissim_sql -v ON_ERROR_STOP=1 < blissim.sql
```

Le script crée les tables, insère les données et exécute les deux requêtes demandées. Il doit être lancé sur une base vide ; il ne réinitialise pas des tables existantes.
