# Projet Symfony avec GEMINI API

## Configuration de l'API

Pour utiliser l'API , vous devez stocker votre clé API dans un fichier `.env`. Ajoutez la ligne suivante dans votre fichier `.env` :

```
GEMINI_API_KEY=your_api_key_here
```

## Lancement du serveur Symfony

Pour démarrer le serveur Symfony, exécutez les commandes suivante :

```sh
Symfony console doctrine:database:create
symfony console doctrine:migration:migrate
symfony server:start --port=8001
```

Cela démarrera votre serveur localement sur le port `8001`. Vous pourrez alors accéder à votre application via `http://127.0.0.1:8001/`.

