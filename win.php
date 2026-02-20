<?php
session_start();
require_once __DIR__ . '/config.php';

// Sécurité : on arrive ici seulement si la partie est finie
if (empty($_SESSION['partie_finie'])) {
    header('Location: index.php');
    exit;
}

$coups      = $_SESSION['coups']      ?? 0;
$score      = $_SESSION['score']      ?? SCORE_BASE;
$bonus      = $_SESSION['bonus']      ?? 0;
$high_score = $_SESSION['high_score'] ?? $score;
$nouveau_record = ($score >= $high_score);

// Rang en fonction du score
function rang(int $s): array {
    if ($s >= 220) return ['S', 'Légendaire',  '#ffd700'];
    if ($s >= 180) return ['A', 'Excellent',   '#c0c0c0'];
    if ($s >= 140) return ['B', 'Bien joué',   '#cd7f32'];
    return               ['C', 'Complété !',   '#888'];
}
[$lettre, $label, $couleur] = rang($score);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory – Victoire !</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Libre+Baskerville:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-victoire">

    <div class="victoire-wrap">

        <div class="victoire-rang" style="color: <?= $couleur ?>; border-color: <?= $couleur ?>;">
            <?= $lettre ?>
        </div>

        <h1 class="victoire-titre">Victoire !</h1>
        <p class="victoire-sous"><?= $label ?></p>

        <?php if ($nouveau_record): ?>
        <div class="nouveau-record">★ Nouveau record ★</div>
        <?php endif; ?>

        <!-- Tableau du score -->
        <div class="score-table">
            <div class="score-ligne">
                <span>Score de base</span>
                <span><?= SCORE_BASE ?> pts</span>
            </div>
            <div class="score-ligne">
                <span>Coups utilisés</span>
                <span><?= $coups ?> / <?= MAX_COUPS ?></span>
            </div>
            <div class="score-ligne score-ligne--bonus">
                <span>Bonus coups restants (<?= MAX_COUPS - $coups ?> × <?= BONUS_COUP ?>)</span>
                <span>+ <?= $bonus ?> pts</span>
            </div>
            <div class="score-ligne score-ligne--total">
                <span>Total</span>
                <span><?= $score ?> pts</span>
            </div>
        </div>

        <div class="best-score">
            🏆 Meilleur score : <strong><?= $high_score ?> pts</strong>
        </div>

        <!-- Actions -->
        <div class="victoire-actions">
            <a href="nouvelle_partie.php" class="btn-jouer">▶ Rejouer</a>
            <a href="index.php" class="btn-secondaire">⌂ Accueil</a>
        </div>

    </div>

</body>
</html>
