# Base de données ECE In

## Installation

1. Créez la base de données en important le fichier SQL :
```bash
mysql -u root -p < schema.sql
```

2. Ou depuis phpMyAdmin :
   - Créer une base `ecein`
   - Importer `schema.sql`

## Identifiants de test

| Rôle | Pseudo | Email | Mot de passe |
|------|--------|-------|--------------|
| Admin | `admin` | `admin@ece.fr` | `password` |
| Auteur | `jdupont` | `jean.dupont@edu.ece.fr` | `password` |
| Auteur | `mmartin` | `marie.martin@edu.ece.fr` | `password` |

> **Note** : Les mots de passe dans la BD sont hashés avec bcrypt. Le mot de passe `password` correspond au hash `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`.

## Configuration

Modifier [config/database.php](../config/database.php) :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecein');
define('DB_USER', 'root');
define('DB_PASS', '');
```
