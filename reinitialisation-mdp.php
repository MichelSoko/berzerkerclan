<?php 

session_start();

include "database.php";

$regexEmail = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';















/*******************************************************************************
-+-+-+-+-+-+-+-+- CONTROLE DES DONNEES RECUS PAR LE FORMULAIRE -+-+-+-+-+-+-+-+-
-+-+-+-+-+-+-+-+-+-+- DE REINITIALISATION DE MOT DE PASSE -+-+-+-+-+-+-+-+-+-+-
-+-+-+-+-+-+-+-+-+- POUR L'ENVOI DU MAIL DE REINITIALISATION -+-+-+-+-+-+-+-+-+-
*******************************************************************************/
//var_dump($_POST);

if (isset($_POST['email_reinitialisation'])) 
{
	$erreursReinitialisationMdp = array();

	$email_reinitialisation = htmlspecialchars($_POST['email_reinitialisation']);

	/************************************ CONTROLE DE L'EMAIL ************************************/
	if (empty($email_reinitialisation)) 
	{
		array_push($erreursReinitialisationMdp, '<p>Veuillez indiquer l\'adresse email.</p>');
	}
	elseif (strlen($email_reinitialisation)>255)
	{
		array_push($erreursReinitialisationMdp, '<p>L\'adresse email est trop longue.</p>');
	}
	elseif (preg_match($regexEmail, $email_reinitialisation)==0) 
	{
		array_push($erreursReinitialisationMdp, '<p>L\'adresse email n\'est pas valide.</p>');
	}
	else
	{
		// Requête pour vérifier qu'un profil avec cet email existe
		$obtenirEmail = "
		SELECT *
		FROM profils
		WHERE email = ?
		";
		$verifierEmail = $pdo->prepare($obtenirEmail);
		$verifierEmail->execute([$email_reinitialisation]);
		$verifierEmail = $verifierEmail->fetch();
 
    	if (empty($verifierEmail)) 
    	{
    		array_push($erreursReinitialisationMdp, '<p>Nous n\'avons pas de compte avec cet email.</p>');
    	}
	}



	/*******************************************************************************
    -+-+-+-+-+-+- ENVOYER UN EMAIL DE REINITIALISATION DE MOT DE PASSE -+-+-+-+-+-+-
	*******************************************************************************/
	//var_dump($erreursReinitialisationMdp);

	// S'il n'y pas d'erreurs dans le formulaire de réinitialisation de mot de passe
	if ( empty($erreursReinitialisationMdp) ) 
	{
		// Générer une nouvelle clé du profil
		$longueurCle = 16;
		$cle = "";
		for ($i=0; $i < $longueurCle; $i++) 
		{ 
			$cle .= mt_rand(0,9);
		}

		// Modifier la clé du profil dans la BDD
		$modifierCleProfil = "
		UPDATE profils
		SET cle = ?
		WHERE email = ?
		";
		$nouvelleCleProfil = $pdo->prepare($modifierCleProfil);
		$nouvelleCleProfil->execute([$cle, $email_reinitialisation]);

		// Récupérer les informations du profil
		$obtenirInformationsProfil = "
		SELECT *
		FROM profils
		WHERE email = ?
		";
		$profilReinitialisationMdp = $pdo->prepare($obtenirInformationsProfil);
		$profilReinitialisationMdp->execute([$email_reinitialisation]);
		$profilReinitialisationMdp = $profilReinitialisationMdp->fetch();

		

		// Envoyer un email pour réinitialiser le mot de passe
		$pseudo_profil = htmlspecialchars($profilReinitialisationMdp['pseudo']);
		$cle_profil = $profilReinitialisationMdp['cle'];

		
		$header="MIME-Version: 1.0\r\n";
		$header.='From:"berzerker-clan.com"<support@berzerkerclan.com>'."\n";
		$header.='Content-Type:text/html; charset="uft-8"'."\n";
		$header.='Content-Transfer-Encoding: 8bit';

		$message='
		<html><body>
			<p>Bonjour '.urlencode($pseudo_profil).'</p>
			<p>Vous avez fait une demande de réinitialisation de mot de passe.</p>
			<p>Cliquez sur le lien suivant pour réinitialiser votre mot de passe :</p>
			<a href="http://localhost/berzerker_clan/reinitialisation-mdp.php?email='.urlencode($email_reinitialisation).'&cle='.urlencode($cle_profil).'">Réinitialiser mon mot de passe</a>
			<p>Si vous n\'êtes pas à l\'auteur de cette demande, veuillez à ne pas prendre en considération le présent email.</p>
		</body></html>
		';

		mail($email_reinitialisation , "Réinitialisation de mot de passe - Berserker Clan", $message, $header);

		$email_envoye = '<p>Un email avec un lien pour réinitialiser votre mot de passe vous a été envoyé. <br />Pensez à jeter un oeil à vos spam si vous ne le trouvez pas.</p>';
	}
}















/*******************************************************************************
-+-+-+-+-+-+-+-+- CONTROLE DES DONNEES RECUS PAR LE FORMULAIRE -+-+-+-+-+-+-+-+-
-+-+-+-+-+-+-+-+-+-+- DE REINITIALISATION DE MOT DE PASSE -+-+-+-+-+-+-+-+-+-+-
-+-+-+-+-+-+-+-+-+-+- POUR LA MODIFICATION DU MOT DE PASSE -+-+-+-+-+-+-+-+-+-+-
*******************************************************************************/
//var_dump($_POST);

if ( isset($_POST['email_modification'],
	 $_POST['cle_modification'], 
	 $_POST['mdp_modification_1'], 
	 $_POST['mdp_modification_2']) ) 
{
	$erreursModificationMdp = array();

	$email_modification = htmlspecialchars($_POST['email_modification']);
	$cle_modification = htmlspecialchars($_POST['cle_modification']);


	/************************************ CONTROLE DE L'EMAIL ************************************/
	if (empty($email_modification)) 
	{
		array_push($erreursModificationMdp, '<p>Aucune adresse email n\'a été envoyée.</p>');
	}
	elseif (strlen($email_modification)>255)
	{
		array_push($erreursModificationMdp, '<p>L\'adresse email envoyée est trop longue.</p>');
	}
	elseif (preg_match($regexEmail, $email_modification)==0) 
	{
		array_push($erreursModificationMdp, '<p>L\'adresse email envoyée n\'est pas valide.</p>');
	}
	else
	{
		// Requête pour vérifier qu'un profil avec cet email existe
		$obtenirEmail = "
		SELECT *
		FROM profils
		WHERE email = ?
		";
		$verifierEmail = $pdo->prepare($obtenirEmail);
		$verifierEmail->execute([$email_modification]);
		$verifierEmail = $verifierEmail->fetch();
 
    	if (empty($verifierEmail)) 
    	{
    		array_push($erreursModificationMdp, '<p>Nous n\'avons pas de compte avec cet email.</p>');
    	}
	}


	// Requête pour obtenir les informations lié à l'email fourni
	$obtenirInformationsProfil = "
	SELECT *
	FROM profils
	WHERE email = ?
	";
	$profilMdpAModifier = $pdo->prepare($obtenirInformationsProfil);
	$profilMdpAModifier->execute([$email_modification]);
	$profilMdpAModifier = $profilMdpAModifier->fetch();

	$cle_profil = $profilMdpAModifier['cle'];
	//var_dump($cle_profil);


	/************************************ CONTROLE DE LA CLE ************************************/
	if ( empty($erreursModificationMdp) && $cle_modification != $cle_profil )
	{
		array_push($erreursModificationMdp, '<p>La clé ne correspond pas au profil !</p>');
	}


	/********************************** CONTROLE DU MOT DE PASSE **********************************/
	if (empty($_POST['mdp_modification_1'])) 
	{
		array_push($erreursModificationMdp, '<p>Veuillez indiquer un mot de passe.</p>');
	}
	elseif (strlen($_POST['mdp_modification_1'])>64) 
	{
		array_push($erreursModificationMdp, '<p>Votre mot de passe est trop long. 64 caractères maximum autorisés.</p>');
	}
	elseif (strlen($_POST['mdp_modification_1'])<8) 
	{
		array_push($erreursModificationMdp, '<p>Le mot de passe doit contenir 8 caractères au minimum.</p>');
	}


	/************************* CONTROLE DE LA CONFIRMATION DE MOT DE PASSE *************************/
	if (empty($_POST['mdp_modification_2'])) 
	{
		array_push($erreursModificationMdp, '<p>Veuillez confirmer le mot de passe.</p>');
	}
	elseif ($_POST['mdp_modification_2']!=$_POST['mdp_modification_1']) 
	{
		array_push($erreursModificationMdp, '<p>Les mots de passes ne correspondent pas !</p>');
	}










	/*******************************************************************************
	-+-+-+-+-+-+-+-+-+-+-+-+ MODIFICATION DU MDP DU PROFIL -+-+-+-+-+-+-+-+-+-+-+-+
	*******************************************************************************/
	//var_dump($erreursModificationMdp);

	if (empty($erreursModificationMdp)) 
	{
		$mdp_reinitialisation = htmlspecialchars($_POST['mdp_modification_1']);

		// Hashage du mot de passe
		$chaineAleatoire = bin2hex(openssl_random_pseudo_bytes(32));
		$sel = '$2y$11$'.substr($chaineAleatoire, 0, 22);
		$mdp_hashe = crypt($mdp_reinitialisation, $sel);

		$valeursProfil = [
			$mdp_hashe,
			$email_modification
		];

		// Requête pour modifier un profil
		$modifierMdpProfil = "
		UPDATE profils
		SET mdp_hashe = ?
		WHERE email = ?
		";
		$nouveauMdpProfil = $pdo->prepare($modifierMdpProfil);
		$nouveauMdpProfil->execute($valeursProfil);

		// Requête pour obtenir les informations lié à l'email fourni
		$obtenirInformationsProfil = "
		SELECT *
		FROM profils
		WHERE email = ?
		";
		$profilMdpModifie = $pdo->prepare($obtenirInformationsProfil);
		$profilMdpModifie->execute([$email_modification]);
		$profilMdpModifie = $profilMdpModifie->fetch();

		$pseudo_profil = htmlspecialchars($profilMdpModifie['pseudo']);

		// Envoyer un email pour confirmer la création du compte
		$header="MIME-Version: 1.0\r\n";
		$header.='From:"berzerker-clan.com"<support@berzerkerclan.com>'."\n";
		$header.='Content-Type:text/html; charset="uft-8"'."\n";
		$header.='Content-Transfer-Encoding: 8bit';

		$message='
		<html><body>
			<p>'.urlencode($pseudo_profil).'</p>
			<p>Votre mot de passe a bien été modifié.</p>
			<p>Si vous n\'êtes pas à l\'origine de cette modification. Contactez un administrateur et pensez également à modifier le mot de passe de votre boite mail.</p>
		</body></html>
		';

		mail($email_modification , "Mot de passe modifié - Berserker Clan", $message, $header);

		$mdp_modifie = '<p>Modification du mot de passe enregistré !</p>';
	}
}















/*******************************************************************************
-+-+- CONTROLE DES DONNEES RECUS POUR LA REINITIALISATION DU MOT DE PASSE -+-+-                         
*******************************************************************************/
//var_dump($_GET);

// Si des paramètres sont envoyés dans l'url
if (isset($_GET['email'], $_GET['cle'])) 
{
	$erreursReinitialisationMdp = array();

	$email_reinitialisation = htmlspecialchars($_GET['email']);
	$cle_reinitialisation = htmlspecialchars($_GET['cle']);



	/************************************ CONTROLE DE L'EMAIL ************************************/
	if (empty($email_reinitialisation)) 
	{
		array_push($erreursReinitialisationMdp, '<p>Aucune adresse email n\'a été envoyée.</p>');
	}
	elseif (strlen($email_reinitialisation)>255)
	{
		array_push($erreursReinitialisationMdp, '<p>L\'adresse email envoyée est trop longue.</p>');
	}
	elseif (preg_match($regexEmail, $email_reinitialisation)==0) 
	{
		array_push($erreursReinitialisationMdp, '<p>L\'adresse email envoyée n\'est pas valide.</p>');
	}
	else
	{
		// Requête pour vérifier qu'un profil avec cet email existe
		$obtenirEmail = "
		SELECT *
		FROM profils
		WHERE email = ?
		";
		$verifierEmail = $pdo->prepare($obtenirEmail);
		$verifierEmail->execute([$email_reinitialisation]);
		$verifierEmail = $verifierEmail->fetch();
 
    	if (empty($verifierEmail)) 
    	{
    		array_push($erreursReinitialisationMdp, '<p>Nous n\'avons pas de compte avec cet email.</p>');
    	}
	}



	// Requête pour obtenir les informations lié à l'email fourni
	$obtenirInformationsProfil = "
	SELECT *
	FROM profils
	WHERE email = ?
	";
	$profilMdpAModifier = $pdo->prepare($obtenirInformationsProfil);
	$profilMdpAModifier->execute([$email_reinitialisation]);
	$profilMdpAModifier = $profilMdpAModifier->fetch();

	$cle_profil = $profilMdpAModifier['cle'];
	//var_dump($cle_profil);



	/************************************ CONTROLE DE LA CLE ************************************/
	if ( empty($erreursReinitialisationMdp) && $cle_reinitialisation != $cle_profil )
	{
		array_push($erreursReinitialisationMdp, '<p>La clé ne correspond pas au profil !</p>');
	}
}


include "reinitialisation-mdp.phtml";

?>