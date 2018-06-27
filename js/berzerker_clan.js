// JAVASCRIPT
'use strict';   // Mode strict du JavaScript

document.addEventListener("DOMContentLoaded", function(event) 
{ 
	// données
	var lienSinscrire = document.querySelector('#sinscrire');
	var lienQuitterInscription = document.querySelector('#quitterInscription');
	var formulaireInscription = document.querySelector('#inscription');

	var lienConnexion = document.querySelector('#seConnecter');
	var lienQuitterConnexion = document.querySelector('#quitterConnexion');
	var formulaireConnexion = document.querySelector('#connexion');

	// fonctions
	function afficherFormulaireInscription()
	{
		formulaireConnexion.classList.add("invisible");
		formulaireInscription.classList.remove("invisible");
	}

	function cacherFormulaireInscription()
	{
		formulaireInscription.classList.add("invisible");
	}

	function afficherFormulaireConnexion()
	{
		formulaireInscription.classList.add("invisible");
		formulaireConnexion.classList.remove("invisible");
	}

	function cacherFormulaireConnexion()
	{
		formulaireConnexion.classList.add("invisible");
	}

	// code principal
	lienSinscrire.addEventListener("click", afficherFormulaireInscription);
	lienQuitterInscription.addEventListener("click", cacherFormulaireInscription);

	lienConnexion.addEventListener("click", afficherFormulaireConnexion);
	lienQuitterConnexion.addEventListener("click", cacherFormulaireConnexion);
});



// JQUERY
/*$(document).ready(function()
{
	// Cliquer sur s'inscrire faire apparaître le formulaire d'inscription
	$("#sinscrire").click( function()
	{
		$("#connexion").fadeOut();
		$("#inscription").fadeIn();
	});

	// Cliquer sur la croix du formulaire d'inscription le fait disparaître
	$("#quitterInscription").click( function()
	{
		$("#inscription").fadeOut();
	});

	// Cliquer sur se connecter faire apparaître le formulaire de connexion
	$("#seConnecter").click( function()
	{
		$("#inscription").fadeOut();
		$("#connexion").fadeIn();
	});

	// Cliquer sur la croix du formulaire de connexion le fait disparaître
	$("#quitterConnexion").click( function()
	{
		$("#connexion").fadeOut();
	});

});*/