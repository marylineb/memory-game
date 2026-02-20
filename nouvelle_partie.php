<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';

init_jeu();

header('Location: game.php');
exit;
