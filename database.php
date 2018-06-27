<?php

$dsn = "mysql:host=localhost;dbname=berzerker_clan;charset=utf8";
$login = "root";	$pass = "";

// CONNEXION A LA BASE DE DONNEES
$pdo = new PDO($dsn, $login, $pass);
// Lancer une exception en cas d'erreur
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

?>