<?php
session_start();
require_once __DIR__ . '/config.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory – Accueil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Libre+Baskerville:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-accueil">

    <div class="accueil-wrap">

        <div class="accueil-deco">◈</div>
        <h1 class="accueil-titre">MEMORY</h1>
        <p class="accueil-sous">Le jeu des paires</p>

        <div class="accueil-regles">
            <h2>Règles du jeu</h2>
            <ul>
                <li>Retournez deux cartes à la fois</li>
                <li>Trouvez les <strong><?= NB_PAIRES ?> paires</strong> cachées</li>
                <li>Vous disposez de <strong><?= MAX_COUPS ?> coups</strong> maximum</li>
                <li>Bonus de <strong><?= BONUS_COUP ?> pts</strong> par coup non utilisé</li>
                <li>Vous gagnerez forcément !</li>
            </ul>
        </div>

        <div class="accueil-score-max">
            Score maximum : <span><?= SCORE_BASE + (MAX_COUPS * BONUS_COUP) ?> pts</span>
        </div>

        <?php if (isset($_SESSION['high_score'])): ?>
        <div class="high-score">
            🏆 Meilleur score&nbsp;: <strong><?= $_SESSION['high_score'] ?> pts</strong>
        </div>
        <?php endif; ?>

        <a href="nouvelle_partie.php" class="btn-jouer">▶ Commencer la partie</a>

    </div>

</body>
</html>
