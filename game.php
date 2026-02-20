<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';

// ── Sécurité : pas de session active → retour accueil ────────────────
if (!isset($_SESSION['deck'])) {
    header('Location: index.php');
    exit;
}

// ── Traitement du clic sur une carte ─────────────────────────────────
if (isset($_POST['carte']) && !$_SESSION['partie_finie']) {
    jouer_carte((int) $_POST['carte']);

    // Si victoire, on redirige vers win.php
    if ($_SESSION['partie_finie']) {
        header('Location: win.php');
        exit;
    }
}

// ── Lecture de l'état ────────────────────────────────────────────────
$e             = etat();
$coups_restants = MAX_COUPS - $e['coups'];
$progress       = round(($e['paires'] / NB_PAIRES) * 100);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory – Partie en cours</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Libre+Baskerville:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-jeu">

    <!-- ══ En-tête / HUD ══════════════════════════════════════════════ -->
    <header class="hud">
        <div class="hud-bloc">
            <span class="hud-label">Paires</span>
            <span class="hud-val"><?= $e['paires'] ?> <small>/ <?= NB_PAIRES ?></small></span>
        </div>

        <h1 class="hud-titre">MEMORY</h1>

        <div class="hud-bloc hud-bloc--droite">
            <span class="hud-label">Coups restants</span>
            <span class="hud-val <?= $coups_restants <= 5 ? 'hud-val--danger' : '' ?>">
                <?= $coups_restants ?>
            </span>
        </div>
    </header>

    <!-- ══ Barre de progression ═══════════════════════════════════════ -->
    <div class="progress-wrap">
        <div class="progress-bar" style="width: <?= $progress ?>%"></div>
    </div>

    <!-- ══ Message feedback ═══════════════════════════════════════════ -->
    <?php if ($e['message']): ?>
    <div class="message <?= str_starts_with($e['message'], '✅') ? 'message--ok' : (str_starts_with($e['message'], '❌') ? 'message--ko' : '') ?>">
        <?= htmlspecialchars($e['message']) ?>
    </div>
    <?php endif; ?>

    <!-- ══ Plateau ════════════════════════════════════════════════════ -->
    <main class="plateau">
        <?php for ($i = 0; $i < 16; $i++): ?>
            <?php
                $trouvee    = $e['trouvees'][$i];
                $retournee  = $e['retournees'][$i];
                $en_attente = in_array($i, $e['selection']);
                $emoji      = $e['deck'][$i];
            ?>

            <?php if ($trouvee): ?>
                <!-- Carte déjà matchée : affichée fixe, pas cliquable -->
                <div class="carte carte--trouvee">
                    <span class="carte-emoji"><?= $emoji ?></span>
                </div>

            <?php elseif ($retournee): ?>
                <!-- Carte retournée en attente de paire : affichée, pas cliquable -->
                <div class="carte carte--retournee carte--attente">
                    <span class="carte-emoji"><?= $emoji ?></span>
                </div>

            <?php else: ?>
                <!-- Carte face cachée : formulaire POST -->
                <form method="POST" action="game.php" class="carte-form">
                    <input type="hidden" name="carte" value="<?= $i ?>">
                    <button type="submit" class="carte carte--cachee">
                        <span class="carte-dos">?</span>
                    </button>
                </form>

            <?php endif; ?>
        <?php endfor; ?>
    </main>

    <!-- ══ Actions bas de page ════════════════════════════════════════ -->
    <footer class="actions">
        <a href="nouvelle_partie.php" class="btn-secondaire">↩ Recommencer</a>
        <span class="actions-info">
            Coup <?= $e['coups'] ?> / <?= MAX_COUPS ?>
        </span>
    </footer>

</body>
</html>
