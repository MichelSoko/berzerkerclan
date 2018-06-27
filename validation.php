<?php 

session_start();

include "database.php";

$regexEmail = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';

















/*******************************************************************************
-+-+-+-+-+-+-+-+ CONTROLE DES DONNEES RECUS PAR LE FORMULAIRE -+-+-+-+-+-+-+-+
-+-+-+-+-+-+-+-+-+ DE RENVOI D'EMAIL DE VALIDATION DE PROFIL -+-+-+-+-+-+-+-+-+
*******************************************************************************/
//var_dump($_POST);

if (isset($_POST['email_validation'])) 
{
	$erreursValidation = array();

	$email_validation = htmlspecialchars($_POST['email_validation']);



	/************************************ CONTROLE DE L'EMAIL ************************************/
	if (empty($email_validation)) 
	{
		array_push($erreursValidation, '<p>Veuillez indiquer l\'adresse email.</p>');
	}
	elseif (strlen($email_validation)>255)
	{
		array_push($erreursValidation, '<p>L\'adresse email est trop longue.</p>');
	}
	elseif (preg_match($regexEmail, $email_validation)==0) 
	{
		array_push($erreursValidation, '<p>L\'adresse email n\'est pas valide.</p>');
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
		$verifierEmail->execute([$email_validation]);
		$verifierEmail = $verifierEmail->fetch();
 
    	if (empty($verifierEmail)) 
    	{
    		array_push($erreursValidation, '<p>Nous n\'avons pas de compte avec cet email.</p>');
    	}
	}



	if ( empty($erreursValidation) ) 
	{
		// Requête pour obtenir les informations lié à l'email fourni
		$obtenirInformationsProfil = "
		SELECT *
		FROM profils
		WHERE email = ?
		";
		$profilAValider = $pdo->prepare($obtenirInformationsProfil);
		$profilAValider->execute([$email_validation]);
		$profilAValider = $profilAValider->fetch();

		$cle_profil = $profilAValider['cle'];
		//var_dump($cle_profil);



		/******************************* CONTROLE DU STATUT DU PROFIL *******************************/
		if ( $profilAValider['id_statut']!=1 ) 
		{
			array_push($erreursValidation, '<p>Votre compte a déjà été validé !</p>');
		}
	}



	/*******************************************************************************
    -+-+-+-+-+-+-+-+- RENVOYER UN EMAIL DE CONFIRMATION DE PROFIL -+-+-+-+-+-+-+-+-
	*******************************************************************************/
	//var_dump($erreursValidation);

	// S'il n'y pas d'erreurs dans le formulaire de validation
	if ( empty($erreursValidation) ) 
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
		$nouvelleCleProfil->execute([$cle, $email_validation]);

		// Envoyer un email pour confirmer la création du compte
		$header="MIME-Version: 1.0\r\n";
		$header.='From:"berzerker-clan.com"<support@berzerkerclan.com>'."\n";
		$header.='Content-Type:text/html; charset="uft-8"'."\n";
		$header.='Content-Transfer-Encoding: 8bit';

		$message='
		<html><body>
			<p>Confirmer votre compte en cliquant sur le lien suivant :</p>
			<a href="http://localhost/berzerker_clan/validation.php?email='.urlencode($email_validation).'&cle='.urlencode($cle).'">Confirmer mon compte Berzerker Clan</a>
			<p>ou copier-coller cette adresse dans la barre de votre navigateur :</p>
			<p>http://localhost/berzerker_clan/validations.php?email='.urlencode($email_validation).'&cle='.urlencode($cle).'</p>
		</body></html>
		';

		mail($email_validation , "Confirmation de compte - Berserker Clan", $message, $header);

		$email_renvoye = '<p>Un nouvel email de confirmation vous a été envoyé. <br />Pensez à jeter un oeil à vos spam si vous ne le trouvez pas.</p>';
	}
}

















/*******************************************************************************
-+-+-+-+-+- CONTROLE DES DONNEES RECUS POUR LA VALIDATION DU PROFIL -+-+-+-+-+-                         
*******************************************************************************/
//var_dump($_GET);

// Si des paramètres sont envoyés dans l'url
if (isset($_GET['email'], $_GET['cle'])) 
{
	$erreursValidation = array();

	$email_validation = htmlspecialchars($_GET['email']);
	$cle_validation = htmlspecialchars($_GET['cle']);



	/************************************ CONTROLE DE L'EMAIL ************************************/
	if (empty($email_validation)) 
	{
		array_push($erreursValidation, '<p>Aucune adresse email n\'a été envoyée.</p>');
	}
	elseif (strlen($email_validation)>255)
	{
		array_push($erreursValidation, '<p>L\'adresse email envoyée est trop longue.</p>');
	}
	elseif (preg_match($regexEmail, $email_validation)==0) 
	{
		array_push($erreursValidation, '<p>L\'adresse email envoyée n\'est pas valide.</p>');
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
		$verifierEmail->execute([$email_validation]);
		$verifierEmail = $verifierEmail->fetch();
 
    	if (empty($verifierEmail)) 
    	{
    		array_push($erreursValidation, '<p>Nous n\'avons pas de compte avec cet email.</p>');
    	}
	}



	// Requête pour obtenir les informations lié à l'email fourni
	$obtenirInformationsProfil = "
	SELECT *
	FROM profils
	WHERE email = ?
	";
	$profilAValider = $pdo->prepare($obtenirInformationsProfil);
	$profilAValider->execute([$email_validation]);
	$profilAValider = $profilAValider->fetch();

	$cle_profil = $profilAValider['cle'];
	//var_dump($cle_profil);



	/******************************* CONTROLE DU STATUT DU PROFIL *******************************/
	if ( empty($erreursValidation) && $profilAValider['id_statut']!=1 ) 
	{
		array_push($erreursValidation, '<p>Votre compte a déjà été validé !</p>');
	}

	/************************************ CONTROLE DE LA CLE ************************************/
	if ( empty($erreursValidation) && $cle_validation != $cle_profil )
	{
		array_push($erreursValidation, '<p>La clé ne correspond pas au profil ! <br />Si vous avez fait plusieurs demandes pour valider votre compte, seul le lien de la dernière demande fonctionnera.</p>');
	}







	/*******************************************************************************
    -+-+-+-+-+-+-+-+-+-+-+-+-+-+- VALIDATION DU PROFIL -+-+-+-+-+-+-+-+-+-+-+-+-+-+-
	*******************************************************************************/
	//var_dump($erreursValidation);

	// S'il n'y pas d'erreurs dans le lien de validation de profil
	if (empty($erreursValidation)) 
	{
		// Requête pour changer le statut du profil (EnAttenteConfirmationEmail-->Recrue)
		$majProfil = "
		UPDATE profils
		SET id_statut = 2
		WHERE email = ?
		";
		$changerStatutEnRecrue = $pdo->prepare($majProfil);
		$changerStatutEnRecrue->execute([$email_validation]);

		// Envoyer un email pour confirmer la validation du compte
		$header="MIME-Version: 1.0\r\n";
		$header.='From:"berzerker-clan.com"<support@berzerkerclan.com>'."\n";
		$header.='Content-Type:text/html; charset="uft-8"'."\n";
		$header.='Content-Transfer-Encoding: 8bit';

		$message='
		<html><body><div>
			<p>Félicitations '.urlencode($profilAValider['pseudo']).' ! <br />
			Votre compte a bien été validé !<br />
			Il vous reste toutefois une dernière étape à franchir.<br />
			Vous êtes actuellement une "recrue" du Berzerker Clan !<br />
			A ce titre, vous ne pouvez pour l\'instant que modifier votre profil.<br />
			Pour pouvoir poster vos vidéos sur le site ou afficher votre armure devant vos frères d\'armes,<br />
			vous devez contactez un modérateur ou un administrateur du site pour qu\'il vous promeut au rang de "membre".<br />
			Interpellez-les sur notre serveur Discord ou sur Steam !<br />
			Pour connaître nos modérateurs et administrateurs actuels, <br />
			rendez-vous dans la section contact de notre site ou directement via ce <a href="localhost/berzerker_clan/contacts.php">lien</a>.</p>
		</div></body></html>
		';

		mail($profilAValider['email'] , "Bienvenue dans le Berzerker Clan", $message, $header);

		$profil_valide = '<p>Votre compte a bien été validé !</p>';
	}
}

include "validation.phtml";

?>