<?php 

session_start();

// Renvoi vers index si aucun utilisateur connecté
if (!isset($_SESSION['berzerker_connecte'])) 
{
	header('Location: index.php');
	exit();
}
else
{
	session_destroy(); 

	header('Location: index.php');
	exit();
}

?>