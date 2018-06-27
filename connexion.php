<?php 

//var_dump($_POST);
/*******************************************************************************
-+-+-+-+-+ CONTROLE DES DONNEES RECUES PAR LE FORMULAIRE DE CONNEXION -+-+-+-+-+
*******************************************************************************/

if (isset($_POST['email_connexion'], $_POST['mdp_connexion'])) 
{
	$erreursConnexion = array();

	$email_connexion = htmlspecialchars($_POST['email_connexion']);
	$mdp_connexion = htmlspecialchars($_POST['mdp_connexion']);



	/************************************ CONTROLE DE L'EMAIL ************************************/
	if (empty($email_connexion)) 
	{
		array_push($erreursConnexion, '<p>Veuillez indiquer l\'adresse email.</p>');
	}
	elseif (strlen($email_connexion)>255)
	{
		array_push($erreursConnexion, '<p>L\'adresse email est trop longue.</p>');
	}
	elseif (preg_match($regexEmail, $email_connexion)==0) 
	{
		array_push($erreursConnexion, '<p>L\'adresse email n\'est pas valide.</p>');
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
		$verifierEmail->execute([$email_connexion]);
		$verifierEmail = $verifierEmail->fetch();
 
    	if (empty($verifierEmail)) 
    	{
    		array_push($erreursConnexion, '<p>Nous n\'avons pas de compte avec cet email.</p>');
    	}
	}



	if ( empty($erreursConnexion) ) 
	{
		// Requête pour obtenir les informations lié à l'email fourni
		$obtenirInformationsProfil = "
		SELECT *
		FROM profils
		WHERE email = ?
		";
		$profilAConnecter = $pdo->prepare($obtenirInformationsProfil);
		$profilAConnecter->execute([$email_connexion]);
		$profilAConnecter = $profilAConnecter->fetch();


		/******************************* CONTROLE DU STATUT DU PROFIL *******************************/
		if ( $profilAConnecter['id_statut']==1 ) 
		{
			array_push($erreursConnexion, '<p>Votre compte n\'a pas encore été validé !<br /><a href="validation.php">Recevoir un nouvel email pour confirmer mon compte</a></p>');
		}
	}

	

	/********************************* CONTROLE DU MOT DE PASSE *********************************/
	if (empty($_POST['mdp_connexion'])) 
	{
		array_push($erreursConnexion, '<p>Veuillez indiquer un mot de passe.</p>');
	}
	elseif (strlen($_POST['mdp_connexion'])>64) 
	{
		array_push($erreursConnexion, '<p>Votre mot de passe est trop long. 64 caractères maximum autorisés.</p>');
	}
	elseif (strlen($_POST['mdp_connexion'])<8) 
	{
		array_push($erreursConnexion, '<p>Le mot de passe doit contenir 8 caractères au minimum.</p>');
	}



	// Vérification de correspondance des mots de passe hashé
	if ( empty($erreursConnexion) ) 
	{
		$mdp_connexion_hashe = crypt($mdp_connexion, $profilAConnecter['mdp_hashe']);

		if ( $mdp_connexion_hashe != $profilAConnecter['mdp_hashe'] ) 
		{
			array_push($erreursConnexion, '<p>Erreur dans l\'identifiant ou le mot de passe.</p>');
		}	
	}

	
	/*******************************************************************************
	-+-+-+-+-+-+-+-+-+-+-+-+-+-+ CREATION D'UNE SESSION -+-+-+-+-+-+-+-+-+-+-+-+-+-+
	*******************************************************************************/
	//var_dump($erreursConnexion)

	if ( empty($erreursConnexion) )
	{
		$_SESSION['berzerker_connecte'] = 
		[
			$profilAConnecter['id'],
			$profilAConnecter['id_statut'],
			$profilAConnecter['pseudo']
		];

		//var_dump($_SESSION);
	}	
}



/*******************************************************************************
-+-+-+-+-+-+ INITIALISATION DES 'VALUES' DU FORMULAIRE DE CONNEXION -+-+-+-+-+-+
*******************************************************************************/

// Si un formulaire de connexion a été envoyé MAIS qu'il y a des erreurs
elseif (isset($_POST, $erreursConnexion) && !empty($erreursConnexion)) 
{
	if (isset($_POST['email_connexion'])) 
	{
		$email_connexion = htmlspecialchars($_POST['email_connexion']);
	} 
	else 
	{
		$email_connexion = '';
	}

	if (isset($_POST['mdp_connexion'])) 
	{
		$mdp_connexion = htmlspecialchars($_POST['mdp_connexion']);
	} 
	else 
	{
		$mdp_connexion = '';
	}
}

else // Si aucun formulaires
{
	$email_connexion = '';
	$mdp_connexion = '';
}


?>


