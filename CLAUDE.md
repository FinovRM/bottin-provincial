# Règles du projet - Bottin Provincial

## Environnement technique
- Backend : Laravel Sail (Docker)
- Production : Coolify

## Directives d'économie de Tokens (Strict)
- **Réponses ultra-courtes** : Va droit au but. Pas de salutations, pas de conclusions, pas de phrases d'introduction.
- **Pas de réécriture** : Ne réécris jamais un fichier entier. Ne fournis QUE les lignes modifiées ou le bloc de code qui change (diff).
- **Explications minimales** : Donne le code d'abord. N'explique la logique que si je te le demande explicitement avec "pourquoi".
- **Garde le contexte léger** : Ne demande pas à lire un fichier entier si tu n'as besoin que d'une fonction ou d'une méthode spécifique.

## Commandes utiles
- Démarrer le projet : `sail up -d`
- Arrêter le projet : `sail down`
- Exécuter les tests : `sail artisan test`
- Vider les caches : `sail artisan optimize:clear`
- Migrations : `sail artisan migrate`
- Composer : `sail composer install` / `sail composer update`
- Node/NPM : `sail npm run dev` / `sail npm run build`
