<?php










/*******************************************************************************
-+-+-+-+- CONTROLE DES DONNEES RECUS PAR LE FORMULAIRE D'INSCRIPTION -+-+-+-+-
*******************************************************************************/
//var_dump($_POST);

// Vérifier qu'un formulaire d'inscription a été envoyé
if (isset($_POST['email_inscription'], $_POST['pseudo_inscription'], $_POST['mdp_inscription_1'], $_POST['mdp_inscription_2'])) 
{
	$erreursInscription = array();

	/************************************ CONTROLE DE L'EMAIL ************************************/
	if (empty($_POST['email_inscription'])) 
	{
		array_push($erreursInscription, '<p>Veuillez indiquer une adresse email.</p>');
	}
	elseif (strlen($_POST['email_inscription'])>255)
	{
		array_push($erreursInscription, '<p>L\'adresse email ne peut contenir plus de 255 caractères.</p>');
	}
	elseif (preg_match($regexEmail, $_POST['email_inscription'])==0) 
	{
		array_push($erreursInscription, '<p>Veuillez saisir une adresse email valide.</p>');
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
		$verifierEmail->execute([$_POST['email_inscription']]);
		$verifierEmail = $verifierEmail->fetch();
 
    	if (!empty($verifierEmail)) 
    	{
    		array_push($erreursInscription, '<p>Un compte avec cet email existe déjà !</p>');
    	}
	}

	/************************************* CONTROLE DU PSEUDO *************************************/
	if (empty($_POST['pseudo_inscription'])) 
	{
		array_push($erreursInscription, '<p>Veuillez indiquer un pseudo.</p>');
	}
	elseif (strlen($_POST['pseudo_inscription'])>50) 
	{
		array_push($erreursInscription, '<p>Le pseudo ne peut contenir plus de 50 caractères.</p>');
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
		$verifierPseudo->execute([$_POST['pseudo_inscription']]);
		$verifierPseudo = $verifierPseudo->fetch();
 
    	if (!empty($verifierPseudo)) 
    	{
    		array_push($erreursInscription, '<p>Un compte avec ce pseudo existe déjà !</p>');
    	}
	}

	/********************************** CONTROLE DU MOT DE PASSE **********************************/
	if (empty($_POST['mdp_inscription_1'])) 
	{
		array_push($erreursInscription, '<p>Veuillez indiquer un mot de passe.</p>');
	}
	elseif (strlen($_POST['mdp_inscription_1'])>64) 
	{
		array_push($erreursInscription, '<p>Votre mot de passe est trop long. 64 caractères maximum autorisés.</p>');
	}
	elseif (strlen($_POST['mdp_inscription_1'])<8) 
	{
		array_push($erreursInscription, '<p>Le mot de passe doit contenir 8 caractères au minimum.</p>');
	}

	/************************* CONTROLE DE LA CONFIRMATION DE MOT DE PASSE *************************/
	if (empty($_POST['mdp_inscription_2'])) 
	{
		array_push($erreursInscription, '<p>Veuillez confirmer le mot de passe.</p>');
	}
	elseif ($_POST['mdp_inscription_2']!=$_POST['mdp_inscription_1']) 
	{
		array_push($erreursInscription, '<p>Les mots de passes ne correspondent pas !</p>');
	}




	

	/*******************************************************************************
	-+-+-+-+-+-+-+-+-+-+-+-+- CREATION D'UN NOUVEAU PROFIL -+-+-+-+-+-+-+-+-+-+-+-+-
	*******************************************************************************/
	//var_dump($erreursInscription);

	// S'il n'y pas d'erreurs dans le formulaire d'inscription
	if (empty($erreursInscription)) 
	{
		$email_inscription = htmlspecialchars($_POST['email_inscription']);
		$pseudo_inscription = htmlspecialchars($_POST['pseudo_inscription']);
		$mdp_inscription = htmlspecialchars($_POST['mdp_inscription_1']);

		// Hashage du mot de passe
		$chaineAleatoire = bin2hex(openssl_random_pseudo_bytes(32));
		$sel = '$2y$11$'.substr($chaineAleatoire, 0, 22);
		$mdp_hashe = crypt($mdp_inscription, $sel);

		// Créer une clé pour le profil (pour la confirmation de profil par email)
		$longueurCle = 16;
		$cle = "";
		for ($i=0; $i < $longueurCle; $i++) 
		{ 
			$cle .= mt_rand(0,9);
		}

		$valeursNouveauProfil = [
			$email_inscription,
			$pseudo_inscription,
			$mdp_hashe,
			$cle
		];

		// Requête pour créer un profil
		$nouveauProfil = "
		INSERT INTO profils
		VALUES (NULL, ?, ?, ?, '', NOW(), ?, 1)
		";
		$creerProfil = $pdo->prepare($nouveauProfil);
		$creerProfil->execute($valeursNouveauProfil);

		// Envoyer un email pour confirmer la création du compte
		$header="MIME-Version: 1.0\r\n";
		$header.='From:"berzerker-clan.com"<support@berzerkerclan.com>'."\n";
		$header.='Content-Type:text/html; charset="uft-8"'."\n";
		$header.='Content-Transfer-Encoding: 8bit';

		$message='
		<html><body><div align="center">
			<p>Confirmer votre compte en cliquant sur le lien suivant :</p>
			<a href="http://localhost/berzerker_clan/validation.php?email='.urlencode($email_inscription).'&cle='.urlencode($cle).'">Confirmer mon compte Berzerker Clan</a>
			<p>ou copier-coller cette adresse dans la barre de votre navigateur :</p>
			<p>http://localhost/berzerker_clan/validations.php?email='.urlencode($email_inscription).'&cle='.urlencode($cle).'</p>
		</div></body></html>
		';

		mail($email_inscription , "Confirmation de compte Berserker Clan", $message, $header);
	}
}







/*******************************************************************************
-+-+-+-+-+- INITIALISATION DES 'VALUES' DU FORMULAIRE D'INSCRIPTION -+-+-+-+-+-
*******************************************************************************/

// Si un formulaire d'inscription a été envoyé et qu'il n'y a pas d'erreurs
if (isset($_POST, $erreursInscription) && empty($erreursInscription))
{
	$email_inscription = '';
	$pseudo_inscription = '';
	$mdp_inscription_1 = '';
	$mdp_inscription_2 = '';

	$profil_cree = '<p>Votre compte a bien été créé ! <br />
	Un email vous a été envoyé pour valider votre compte !</p>';
}

// Si un formulaire d'inscription a été envoyé MAIS qu'il y a des erreurs
elseif (isset($_POST, $erreursInscription) && !empty($erreursInscription)) 
{
	if (isset($_POST['email_inscription'])) 
	{
		$email_inscription = htmlspecialchars($_POST['email_inscription']);
	} 
	else 
	{
		$email_inscription = '';
	}

	if (isset($_POST['pseudo_inscription'])) 
	{
		$pseudo_inscription = htmlspecialchars($_POST['pseudo_inscription']);
	} 
	else 
	{
		$pseudo_inscription = '';
	}

	if (isset($_POST['mdp_inscription_1'])) 
	{
		$mdp_inscription_1 = htmlspecialchars($_POST['mdp_inscription_1']);
	} 
	else 
	{
		$mdp_inscription_1 = '';
	}

	if (isset($_POST['mdp_inscription_2'])) 
	{
		$mdp_inscription_2 = htmlspecialchars($_POST['mdp_inscription_2']);
	} 
	else 
	{
		$mdp_inscription_2 = '';
	}
}

else // Si aucun formulaires
{
	$email_inscription = '';
	$pseudo_inscription = '';
	$mdp_inscription_1 = '';
	$mdp_inscription_2 = '';
}

?>