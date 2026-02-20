# 🃏 Memory – Jeu des paires en PHP pur

Mini-jeu de mémoire visuelle développé entièrement en **PHP côté serveur**, sans JavaScript, sans base de données. Toute la logique de jeu repose sur les **sessions PHP** et les **formulaires HTML POST**. La navigation entre les pages utilise exclusivement `header('Location: ...')`.

---

## Sommaire

1. [Prérequis](#1-prérequis)
2. [Installation](#2-installation)
3. [Structure des fichiers](#3-structure-des-fichiers)
4. [Règles du jeu](#4-règles-du-jeu)
5. [Architecture et flux de navigation](#5-architecture-et-flux-de-navigation)
6. [Description détaillée de chaque fichier](#6-description-détaillée-de-chaque-fichier)
7. [La session PHP – cœur du jeu](#7-la-session-php--cœur-du-jeu)
8. [Logique de jeu pas à pas](#8-logique-de-jeu-pas-à-pas)
9. [Calcul du score](#9-calcul-du-score)
10. [Personnalisation](#10-personnalisation)

---

## 1. Prérequis

| Outil | Version minimale |
|---|---|
| PHP | 8.0+ |
| Serveur web | Apache / Nginx / MAMP / XAMPP |
| Extensions PHP | `session` (activée par défaut) |

> ⚠️ Ce projet **ne nécessite aucune base de données** et **aucune dépendance externe** (pas de Composer, pas de npm).

---

## 2. Installation

1. Copiez tous les fichiers dans un dossier de votre serveur local :
   ```
   /Applications/MAMP/htdocs/memory/
   ```
2. Ouvrez votre navigateur et accédez à :
   ```
   http://localhost:8888/memory/index.php
   ```
   *(Le port peut varier selon votre configuration MAMP/XAMPP)*

3. Cliquez sur **"Commencer la partie"** – c'est tout.

> **Pourquoi `__DIR__` dans les `require_once` ?**
> PHP résout les chemins relatifs depuis le *répertoire courant du serveur*, pas depuis le fichier PHP lui-même. `__DIR__` est une constante magique qui retourne le chemin absolu du dossier contenant le fichier en cours, ce qui garantit que les `require_once` fonctionnent peu importe l'endroit où le projet est installé.

---

## 3. Structure des fichiers

```
memory/
│
├── index.php            ← Page d'accueil (règles + meilleur score)
├── nouvelle_partie.php  ← Action : initialise la session et redirige
├── game.php             ← Plateau de jeu (affichage + traitement des clics)
├── win.php              ← Page de victoire (score détaillé + rang)
│
├── config.php           ← Constantes du jeu (coups max, score, emojis…)
├── session.php          ← Fonctions de logique : init_jeu(), jouer_carte(), etat()
│
└── style.css            ← Feuille de styles (thème Art Déco, aucun framework CSS)
```

---

## 4. Règles du jeu

- Le plateau contient **16 cartes** (8 paires d'emojis) mélangées aléatoirement.
- À chaque tour, le joueur retourne **2 cartes** en cliquant dessus.
- Si les 2 cartes sont **identiques** → elles restent visibles et comptent comme une paire trouvée.
- Si elles sont **différentes** → elles se recouvrent automatiquement.
- Le joueur dispose d'un **maximum de 30 coups** pour optimiser son score.
- Au-delà de 30 coups, le jeu continue jusqu'à la victoire (la partie ne se termine jamais sur un échec — **on gagne toujours**).
- La victoire est déclenchée dès que les **8 paires sont trouvées**.

---

## 5. Architecture et flux de navigation

Le jeu ne repose sur **aucun formulaire de navigation** — uniquement des liens `<a href>` et des redirections `header('Location:')`.

```
index.php
    │
    │  <a href="nouvelle_partie.php">
    ▼
nouvelle_partie.php ──── init_jeu() ──── header('Location: game.php')
                                              │
                                              ▼
                                          game.php  ◄────────────────┐
                                              │                       │
                                    [clic sur une carte]             │
                                              │                       │
                                    jouer_carte($idx)                │
                                              │                       │
                                    ┌─────────┴──────────┐           │
                                    │ partie_finie ?      │           │
                                   OUI                   NON         │
                                    │                     │           │
                     header('Location: win.php')    re-affiche game.php
                                    │
                                    ▼
                                 win.php
                                    │
                          <a href="nouvelle_partie.php">   → repart en haut
                          <a href="index.php">             → accueil
```

### Pourquoi `header('Location:')` plutôt que des formulaires ?

Un formulaire POST classique (`<form action="game.php" method="POST">`) affiche une alerte "Voulez-vous renvoyer les données ?" si l'utilisateur recharge la page (F5). En passant par une redirection HTTP, le navigateur fait une requête GET sur la nouvelle page — ce problème disparaît. C'est le patron **Post/Redirect/Get (PRG)**.

> ⚠️ `header()` doit toujours être appelé **avant tout affichage HTML** (avant le premier `echo`, `?>`, espace ou saut de ligne). Sinon PHP lève une erreur "headers already sent".

---

## 6. Description détaillée de chaque fichier

### `config.php` — Constantes globales

Ce fichier définit toutes les valeurs configurables du jeu via `define()`. Il est inclus en premier par tous les autres fichiers.

```php
define('MAX_COUPS',  30);   // Seuil de coups pour le bonus de score
define('NB_PAIRES',   8);   // Nombre de paires (= 16 cartes)
define('SCORE_BASE', 100);  // Points attribués à la victoire de base
define('BONUS_COUP',   5);  // Points bonus par coup non utilisé

define('EMOJIS', ['🍒','🍋','🍇','🎰','💎','⭐','🔔','🃏']);
```

> Les constantes `define()` sont globales et accessibles partout sans `global` ni passage en paramètre.

---

### `session.php` — Logique de jeu

Contient trois fonctions qui constituent le moteur du jeu. Ce fichier ne produit **aucun affichage**.

#### `init_jeu()` — Initialise une nouvelle partie

```php
function init_jeu(): void
```

1. Duplique le tableau `EMOJIS` pour avoir 16 cartes.
2. Mélange le deck avec `shuffle()`.
3. Écrit l'état initial complet dans `$_SESSION`.

Variables de session créées :

| Clé | Type | Description |
|---|---|---|
| `deck` | `array[16]` | Valeurs des 16 cartes dans l'ordre mélangé |
| `retournees` | `array[16]` de `bool` | `true` si la carte est visible (retournée) |
| `trouvees` | `array[16]` de `bool` | `true` si la carte fait partie d'une paire trouvée |
| `selection` | `array` (0 ou 1 index) | Index de la carte retournée en attente de paire |
| `coups` | `int` | Nombre de coups joués (incrémenté à la 2e carte) |
| `paires` | `int` | Nombre de paires trouvées |
| `message` | `string` | Texte de feedback affiché sous le HUD |
| `partie_finie` | `bool` | `true` dès que toutes les paires sont trouvées |

#### `jouer_carte(int $idx)` — Traite un clic sur une carte

```php
function jouer_carte(int $idx): void
```

Appelée depuis `game.php` avec l'index (0–15) de la carte cliquée. Gère deux phases :

**Phase 1 — Première carte du tour :**
- L'index est ajouté à `$_SESSION['selection']`.
- La carte passe à `retournee = true`.
- Le coup n'est **pas encore compté** (on attend la 2e carte).

**Phase 2 — Deuxième carte du tour :**
- On compare `$deck[$premier]` et `$deck[$idx]`.
- Si **identiques** : les deux cartes passent à `trouvee = true`, `paires++`.
- Si **différentes** : les deux cartes repassent à `retournee = false`.
- Dans les deux cas : `coups++` et `selection` est vidée.
- Si toutes les paires sont trouvées : calcul du score et `partie_finie = true`.

Protections intégrées :
- Index hors bornes → retour immédiat sans effet.
- Carte déjà trouvée → retour immédiat.
- Double-clic sur la même carte → retour immédiat.

#### `etat()` — Snapshot de l'état courant

```php
function etat(): array
```

Retourne un tableau associatif propre avec les données de `$_SESSION`. Utilisé dans `game.php` pour alimenter le template HTML sans accéder directement à `$_SESSION` partout.

---

### `index.php` — Page d'accueil

- Démarre la session (`session_start()`).
- Inclut `config.php` pour afficher les constantes dans les règles.
- Affiche le meilleur score s'il existe dans `$_SESSION['high_score']`.
- Le bouton "Commencer" est un simple `<a href="nouvelle_partie.php">` — aucun formulaire.

---

### `nouvelle_partie.php` — Action de démarrage

- Démarre la session.
- Appelle `init_jeu()` pour (ré)initialiser toutes les variables de session.
- Redirige immédiatement vers `game.php` via `header('Location: game.php')`.
- Ce fichier ne contient **aucun HTML** — c'est un pur contrôleur d'action.

> Ce fichier centralise l'initialisation : qu'on vienne de l'accueil, d'un "Recommencer" ou d'un "Rejouer", on passe toujours par lui.

---

### `game.php` — Plateau de jeu

C'est le fichier principal. Il joue deux rôles selon la requête reçue :

**Réception d'un POST (clic sur une carte) :**
```php
if (isset($_POST['carte']) && !$_SESSION['partie_finie']) {
    jouer_carte((int) $_POST['carte']);
    if ($_SESSION['partie_finie']) {
        header('Location: win.php');
        exit;
    }
}
```

**Affichage du plateau :**

Chaque carte est rendue différemment selon son état :

| État | Rendu HTML |
|---|---|
| `trouvee = true` | `<div class="carte carte--trouvee">` — emoji visible, pas cliquable |
| `retournee = true` (en attente) | `<div class="carte carte--retournee carte--attente">` — emoji visible, pas cliquable |
| face cachée | `<form><button type="submit" name="carte" value="$i">` — cliquable |

> Chaque carte cliquable est dans son propre `<form method="POST" action="game.php">` avec un `<input type="hidden" name="carte" value="$i">`. Un seul clic = un seul POST.

---

### `win.php` — Page de victoire

- Vérifie que `$_SESSION['partie_finie']` est `true`, sinon redirige vers `index.php`.
- Calcule et affiche le détail du score.
- Attribue un **rang** (S / A / B / C) selon le score total.
- Affiche le badge "Nouveau record" si le score dépasse `$_SESSION['high_score']`.
- Les boutons "Rejouer" et "Accueil" sont de simples liens `<a href>`.

---

### `style.css` — Styles

Thème **Art Déco** : papier crème (`#f5f0e8`), accents dorés (`#b8952a`), brun profond (`#2c1f14`). Typographie avec **Playfair Display** (titres) et **Libre Baskerville** (corps de texte), chargées via Google Fonts.

Les classes CSS principales :

| Classe | Usage |
|---|---|
| `.carte--cachee` | Carte face cachée (bouton sombre) |
| `.carte--retournee` | Carte visible en attente de paire (bordure dorée) |
| `.carte--trouvee` | Carte matchée (fond vert pâle, opacité réduite) |
| `.hud-val--danger` | Coups restants ≤ 5 (texte rouge) |
| `.message--ok` | Feedback paire trouvée (texte vert) |
| `.message--ko` | Feedback mauvaise paire (texte rouge) |
| `.btn-jouer` | Bouton/lien principal (fonctionne sur `<a>` et `<button>`) |
| `.btn-secondaire` | Bouton/lien secondaire (transparent, bordure fine) |

---

## 7. La session PHP – cœur du jeu

PHP n'est pas un langage "avec état" entre les requêtes HTTP : chaque appel de page repart de zéro. Les **sessions** permettent de conserver des données entre les requêtes en les stockant côté serveur (par défaut dans un fichier temporaire) et en identifiant le navigateur via un cookie (`PHPSESSID`).

```
Navigateur                          Serveur PHP
    │                                    │
    │── GET index.php ──────────────────►│ session_start() → crée PHPSESSID
    │◄─ HTML + cookie PHPSESSID ─────────│
    │                                    │
    │── GET nouvelle_partie.php ────────►│ session_start() → retrouve la session
    │                      (cookie)      │ init_jeu() écrit dans $_SESSION
    │◄─ 302 Location: game.php ──────────│
    │                                    │
    │── GET game.php ────────────────────►│ session_start() → $_SESSION disponible
    │◄─ HTML du plateau ─────────────────│
    │                                    │
    │── POST game.php (carte=7) ────────►│ session_start() → jouer_carte(7)
    │◄─ HTML du plateau mis à jour ──────│
```

> `session_start()` doit être appelé **en tout premier** dans chaque fichier PHP qui utilise `$_SESSION`, avant tout affichage.

---

## 8. Logique de jeu pas à pas

Voici ce qui se passe exactement quand le joueur clique sur la carte n°7, sachant que la carte n°3 était déjà retournée :

```
[Requête POST] game.php reçoit carte=7

jouer_carte(7)
  ├─ idx=7, pas hors bornes, pas trouvée
  ├─ selection = [3]  → count = 1  → Phase 2
  ├─ retournees[7] = true
  ├─ coups++ (ex: coups = 5)
  ├─ deck[3] === deck[7] ?
  │     OUI → trouvees[3] = true, trouvees[7] = true, paires++ (ex: paires=4)
  │           message = '✅ Bonne paire !'
  │     NON → retournees[3] = false, retournees[7] = false
  │           message = '❌ Pas une paire, réessayez…'
  ├─ selection = []
  └─ paires === NB_PAIRES (8) ?
        OUI → calcul score, partie_finie = true
              → header('Location: win.php')
        NON → re-affichage du plateau
```

---

## 9. Calcul du score

```
score_final = SCORE_BASE + max(0, MAX_COUPS - coups_joués) × BONUS_COUP
            = 100       + max(0, 30 - coups_joués)         × 5
```

Exemples :

| Coups joués | Coups restants | Bonus | Score final | Rang |
|---|---|---|---|---|
| 8 | 22 | 110 pts | **210 pts** | S |
| 14 | 16 | 80 pts | **180 pts** | A |
| 22 | 8 | 40 pts | **140 pts** | B |
| 35 | 0 | 0 pts | **100 pts** | C |

Le meilleur score est conservé dans `$_SESSION['high_score']` pour toute la durée de la session navigateur.

> ℹ️ Si le joueur dépasse 30 coups, il ne perd pas — la partie continue jusqu'à la victoire, mais le bonus tombe à 0.

---

## 10. Personnalisation

Toutes les valeurs clés sont dans `config.php`. Modifiez-les selon vos besoins :

```php
// Rendre le jeu plus difficile
define('MAX_COUPS', 20);   // Moins de coups pour le bonus

// Augmenter la récompense
define('BONUS_COUP', 10);  // 10 pts par coup économisé

// Changer les icônes (8 exactement)
define('EMOJIS', ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼']);
```

Pour changer le nombre de paires, modifiez `NB_PAIRES` **et** le tableau `EMOJIS` en conséquence (ils doivent avoir la même taille), puis adaptez la grille CSS dans `style.css` :

```css
/* Pour 4 paires (8 cartes) → grille 4×2 */
#board { grid-template-columns: repeat(4, 1fr); }

/* Pour 6 paires (12 cartes) → grille 4×3 */
#board { grid-template-columns: repeat(4, 1fr); }

/* Pour 8 paires (16 cartes) → grille 4×4 (défaut) */
#board { grid-template-columns: repeat(4, 1fr); }
```

---

*Projet développé en PHP 8.3 · Testé sous MAMP sur macOS*
