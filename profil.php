<?php 

session_start();

include "database.php";

$regexEmail = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';

$regexSteam = '#(?:https?:\/\/)?steamcommunity\.com\/(?:profiles|id)\/[a-zA-Z0-9]+#';













/*******************************************************************************
-+-+-+-+-+-+-+-+-+-+- AFFICHER LES INFORMATIONS DE PROFIL -+-+-+-+-+-+-+-+-+-+-
*******************************************************************************/
//var_dump($_SESSION['berzerker_connecte']);

if ( isset($_SESSION['berzerker_connecte']) ) 
{
	$erreursProfil = array();

	if ( isset($_SESSION['berzerker_connecte'][0]) )
	{
		$id_profil = htmlspecialchars( $_SESSION['berzerker_connecte'][0] );

		// Requête pour obtenir les informations lié au profil connecté
		$obtenirInformationsProfil = "
		SELECT *
		FROM profils
		WHERE id = ?
		";
		$profilAModifier = $pdo->prepare($obtenirInformationsProfil);
		$profilAModifier->execute([$id_profil]);
		$profilAModifier = $profilAModifier->fetch();

		//var_dump($profilAModifier);

		$email_profil = htmlspecialchars( $profilAModifier['email'] );
		$pseudo_profil = htmlspecialchars( $profilAModifier['pseudo'] );
		$steam_profil = htmlspecialchars( $profilAModifier['profil_steam'] );

		$date_inscription_profil = htmlspecialchars( $profilAModifier['date_inscription'] );
		$date_inscription_profil = date_create_from_format('Y-m-d G:i:s', $date_inscription_profil);
		$date_inscription_profil = date_format($date_inscription_profil, 'd/m/Y \à G\hi');
		$id_statut = htmlspecialchars( $profilAModifier['id_statut'] );
	}
	else
	{
		array_push($erreursProfil, '<p>Erreur lors de la creation de session. Essayez de vous reconnecter à votre compte.<br />Si le problème persiste, contactez un administrateur du site.</p>');
	}
}
else
{
	$profil_nonConnecte = '<p>Vous devez être connecté avec votre compte pour accèder à cette page.</p>';
}















/*******************************************************************************
-+-+-+-+-+ CONTROLE DES DONNEES RECUS POUR LA MODIFICATION DU PROFIL -+-+-+-+-+                         
*******************************************************************************/
//var_dump($_POST);

// Vérifier qu'un formulaire de modification a été envoyé
if ( isset($_POST['email_profil'], $_POST['pseudo_profil'], $_POST['steam_profil']) ) 
{
	$erreursProfil = array();



	if ( $_POST['email_profil'] != $email_profil ) 
	{
		/************************************ CONTROLE DE L'EMAIL ************************************/
		if (empty($_POST['email_profil'])) 
		{
			array_push($erreursProfil, '<p>Veuillez indiquer une adresse email.</p>');
		}
		elseif (strlen($_POST['email_profil'])>255)
		{
			array_push($erreursProfil, '<p>L\'adresse email ne peut contenir plus de 255 caractères.</p>');
		}
		elseif (preg_match($regexEmail, $_POST['email_profil'])==0) 
		{
			array_push($erreursProfil, '<p>Veuillez saisir une adresse email valide.</p>');
		}
		else
		{
			// Requête pour vérifier que l'email n'existe pas déjà
			$obtenirEmail = "
			SELECT *
			FROM profils
			WHERE email = ?
			";
			$verifierEmail = $pdo->prepare($obtenirEmail);
			$verifierEmail->execute([$_POST['email_profil']]);
			$verifierEmail = $verifierEmail->fetch();
	 
	    	if (!empty($verifierEmail)) 
	    	{
	    		array_push($erreursProfil, '<p>Un compte avec cet email existe déjà !</p>');
	    	}
		}
	}



	if ( $_POST['pseudo_profil'] != $pseudo_profil ) 
	{
		/************************************* CONTROLE DU PSEUDO *************************************/
		if (empty($_POST['pseudo_profil'])) 
		{
			array_push($erreursProfil, '<p>Veuillez indiquer un pseudo.</p>');
		}
		elseif (strlen($_POST['pseudo_profil'])>50) 
		{
			array_push($erreursProfil, '<p>Le pseudo ne peut contenir plus de 50 caractères.</p>');
		}
		else
		{
			// Requête pour vérifier que le pseudo n'existe pas déjà
			$obtenirPseudo = "
			SELECT *
			FROM profils
			WHERE pseudo = ?
			";
			$verifierPseudo = $pdo->prepare($obtenirPseudo);
			$verifierPseudo->execute([$_POST['pseudo_profil']]);
			$verifierPseudo = $verifierPseudo->fetch();
	 
	    	if (!empty($verifierPseudo)) 
	    	{
	    		array_push($erreursProfil, '<p>Un compte avec ce pseudo existe déjà !</p>');
	    	}
		}
	}



	if ( $_POST['steam_profil'] != $steam_profil ) 
	{
		/********************************** CONTROLE DU PROFIL STEAM **********************************/
		if (strlen($_POST['steam_profil'])>255)
		{
			array_push($erreursProfil, '<p>Le lien vers votre profil Steam ne peut contenir plus de 255 caractères.</p>');
		}
		elseif (preg_match($regexSteam, $_POST['steam_profil'])==0) 
		{
			array_push($erreursProfil, '<p>Veuillez saisir un lien vers votre profil Steam valide.</p>');
		}
	}







	/*******************************************************************************
	-+-+-+-+-+-+-+-+-+-+-+-+-+-+ MODIFICATION DU PROFIL -+-+-+-+-+-+-+-+-+-+-+-+-+-+
	*******************************************************************************/
	//var_dump($erreursProfil);

	// S'il n'y pas d'erreurs dans le formulaire de modification du profil
	if (empty($erreursProfil)) 
	{
		if ( $_POST['email_profil'] != $email_profil )
		{
			$ancien_email_profil = $email_profil;
			$email_profil = $_POST['email_profil'];

			$nouvelEmail = "
			UPDATE profils
			SET email = ?
			WHERE id = ?
			";
			$modifierEmail = $pdo->prepare($nouvelEmail);
			$modifierEmail->execute([$email_profil, $id_profil]);

			// Envoyer un email pour confirmer la modification de l'email du profil
			$header="MIME-Version: 1.0\r\n";
			$header.='From:"berzerker-clan.com"<support@berzerkerclan.com>'."\n";
			$header.='Content-Type:text/html; charset="uft-8"'."\n";
			$header.='Content-Transfer-Encoding: 8bit';

			$message='
			<html><body><div align="center">
				<p>'.urlencode($pseudo_profil).' !</p>
				<p>Nous vous confirmons votre changement d\'adresse email.</p>
				<p>Si vous n\'êtes pas l\'auteur de cette modification. Contactez l\'administrateur du site.</p>
			</div></body></html>
			';

			mail($email_profil , "Changement d'adresse email - Berserker Clan", $message, $header);
		}

		if ( $_POST['pseudo_profil'] != $pseudo_profil ) 
		{
			$pseudo_profil = $_POST['pseudo_profil'];

			$nouveauPseudo = "
			UPDATE profils
			SET pseudo = ?
			WHERE id = ?
			";
			$modifierPseudo = $pdo->prepare($nouveauPseudo);
			$modifierPseudo->execute([$pseudo_profil, $id_profil]);
		}

		if ( $_POST['steam_profil'] != $steam_profil )
		{
			$steam_profil = $_POST['steam_profil'];

			$nouveauSteam = "
			UPDATE profils
			SET profil_steam = ?
			WHERE id = ?
			";
			$modifierSteam = $pdo->prepare($nouveauSteam);
			$modifierSteam->execute([$steam_profil, $id_profil]);
		}
	}



}















/*******************************************************************************
-+-+- INITIALISATION DES 'VALUES' DU FORMULAIRE DE MODIFICATION DE PROFIL -+-+-
*******************************************************************************/

// Si un formulaire de modification de profil a été envoyé et qu'il n'y a pas d'erreurs
if ( isset($_POST, $erreursProfil) && !empty($_POST) && empty($erreursProfil) )
{
	$profil_modifie = '<p>Vos modifications ont bien été prises en comptes.</p>';
}

// Si un formulaire de modification de profil a été envoyé MAIS qu'il y a des erreurs
elseif (isset($_POST, $erreursProfil) && !empty($erreursProfil)) 
{
	if ( isset($_POST['email_profil']) ) 
	{
		$email_profil = htmlspecialchars($_POST['email_profil']);
	}

	if ( isset($_POST['pseudo_profil']) ) 
	{
		$pseudo_profil = htmlspecialchars($_POST['pseudo_profil']);
	} 

	if ( isset($_POST['steam_profil']) ) 
	{
		$steam_profil = htmlspecialchars($_POST['steam_profil']);
	}
}

include "profil.phtml";

?>