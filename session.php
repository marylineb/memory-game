<?php
require_once __DIR__ . '/config.php';

// ─── Initialise ou réinitialise une partie
function init_jeu(): void {
    $emojis = EMOJIS;
    $deck   = array_merge($emojis, $emojis);
    shuffle($deck);

    $_SESSION['deck']        = $deck;
    $_SESSION['retournees']  = array_fill(0, 16, false);
    $_SESSION['trouvees']    = array_fill(0, 16, false);
    $_SESSION['attente']     = [];
    $_SESSION['selection']   = [];
    $_SESSION['coups']       = 0;
    $_SESSION['paires']      = 0;
    $_SESSION['message']     = '';
    $_SESSION['partie_finie']= false;
}

// ─── Traite le clic sur une carte
function jouer_carte(int $idx): void {

    // Sécurité : carte déjà trouvée ou hors limites
    if ($idx < 0 || $idx > 15)      return;
    if ($_SESSION['trouvees'][$idx]) return;

    $sel = &$_SESSION['selection'];

    if (!empty($_SESSION['attente'])) {
        foreach ($_SESSION['attente'] as $i) {
            $_SESSION['retournees'][$i] = false;
        }
        $_SESSION['attente']   = [];
        $_SESSION['selection'] = [];
    }

    // Deuxième clic sur la même carte ignoré
    if (count($sel) === 1 && $sel[0] === $idx) return;

    // --- PHASE 1 : première carte retournée
    if (count($sel) === 0) {
        $sel[] = $idx;
        $_SESSION['retournees'][$idx] = true;
        $_SESSION['message'] = 'Choisissez une deuxième carte…';
        return;
    }

    // --- PHASE 2 : deuxième carte retournée
    $premier = $sel[0];
    $_SESSION['retournees'][$idx] = true;
    $_SESSION['coups']++;

    $deck = $_SESSION['deck'];

    if ($deck[$premier] === $deck[$idx]) {
        // ✅ Paire trouvée
        $_SESSION['trouvees'][$premier] = true;
        $_SESSION['trouvees'][$idx]     = true;
        $_SESSION['paires']++;
        $_SESSION['message'] = '✅ Bonne paire !';
    } else {
        // ❌ Pas une paire : on recouvre les deux au prochain clic
        $_SESSION['attente'] = [$premier, $idx];
        $_SESSION['message'] = '❌ Pas une paire...';
    }

    $_SESSION['selection'] = [];   // vider la sélection

    // Vérifier victoire
    if ($_SESSION['paires'] === NB_PAIRES) {
        $coups_restants          = MAX_COUPS - $_SESSION['coups'];
        $bonus                   = max(0, $coups_restants) * BONUS_COUP;
        $score                   = SCORE_BASE + $bonus;

        $_SESSION['score']       = $score;
        $_SESSION['bonus']       = $bonus;
        $_SESSION['partie_finie']= true;

        // High score
        if (!isset($_SESSION['high_score']) || $score > $_SESSION['high_score']) {
            $_SESSION['high_score'] = $score;
        }
    }

    // Coups épuisés → on signale mais on laisse finir
    if ($_SESSION['coups'] >= MAX_COUPS && !$_SESSION['partie_finie']) {
        $_SESSION['message'] .= ' ⚠️ Plus de coups bonus possibles.';
    }

} // ← accolade fermante de jouer_carte()

// ─── Accesseur sûr
function etat(): array {
    return [
        'deck'       => $_SESSION['deck'],
        'retournees' => $_SESSION['retournees'],
        'trouvees'   => $_SESSION['trouvees'],
        'selection'  => $_SESSION['selection'],
        'coups'      => $_SESSION['coups'],
        'paires'     => $_SESSION['paires'],
        'message'    => $_SESSION['message'],
        'finie'      => $_SESSION['partie_finie'],
    ];
}