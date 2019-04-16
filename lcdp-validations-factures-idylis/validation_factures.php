<?php
require("Connexion_Idylis_LCDP.php");//Fichier contenant la connexion à Idylis
$oWS->DefinirModule(array('_codemodule'=>'FA'));

//Vérifier commandes non validées avec objet contenant un mode de paiement
$oRes= $oWS->LireTable(array('_nomtable'=>"FA_Factures", '_criteres'=>"ISNULL(VALIDEE,'Non')<>'Oui'", '_ordre'=>"CODEFACTURE DESC"));
$facturesXML= new SimpleXMLElement($oRes->LireTableResult);
$nbFacturesValidees = 0;

foreach ($facturesXML->FICHE as $ficheFactures)
{
	// Affichage à utiliser si on veut vérifier tous les champs récupérés
	// foreach($ficheFactures as $key => $valeur)
	// {
		// echo "$key : $valeur<br/>";
	// }
	
	/************************************  Verification commande  *******************************************
	On vérifie si la commande vient du site. Le champ objet etant rempli avec uniquement le mode de paiement.
	$traiter permettra de dire si on fait la validation
	On en profite pour recuperer l'appelation du type de paiement chez Idylis
	*********************************************************************************************************/
	$traiter=false;
	switch($ficheFactures->OBJETFACTURE)
	{
		case 'cheque' ://CHEQUE
			$typePaiement = 'Chèque';
			$traiter=true;
			break;
		case 'bacs' ://VIREMENT
			$typePaiement = 'Virement';
			$traiter=true;
			break;
		case 'paypal' :
		case 'paypal_hss' ://PAYPAL
			$typePaiement = 'PAYPAL';
			$traiter=true;
			// echo $typePaiement;
			// die();
			break;
		default ://AUCUN, facture ne provenant pas du site
			if(strpos('paypal_hss',$ficheFactures->OBJETFACTURE)!==false)
			{
				$typePaiement = 'PAYPAL';
				$traiter=true;
			}
			break;
	}
	
	/************************************  Validation commande  **********************************************
	Avec $traiter, on sait si la facture a été generee par une commande du site.
	Si c'est bien le cas, on rentre dans le processus de validation, sinon, on continue
	*********************************************************************************************************/
	if($traiter)
	{
		//Validation de la facture
		$oWS->GC_ValiderPiece(array('_piece'=>'FA_FACTURES', '_codepiece'=>$ficheFactures->CODEFACTURE));//VALIDATION FACTURE


		/**********************************************  Règlement  *************************************************
		/ On vérifie si la commande vient du site. Le champ objet etant rempli avec uniquement le mode de paiement.
		/ $traiter permettra de dire si on fait la validation
		/ Une fois la facture validée, une echeance est creee. On peut la lire ici
		************************************************************************************************************/
		
		
		$oResEcheance= $oWS->LireTable(array('_nomtable'=>"FA_ECHEANCES", '_criteres'=>"CODEFACTURE = '".$ficheFactures->CODEFACTURE."'", '_ordre'=>"CODEFACTURE DESC"));
		$echeanceXML= new SimpleXMLElement($oResEcheance->LireTableResult);
		foreach ($echeanceXML->FICHE as $ficheEcheance)
		{
			//foreach($ficheEcheance as $key => $valeur)
			//{
				//echo "$key : $valeur<br/>";
			//}
			
			//On crée un XML contenant les informations obligatoires, et les references au client et a la facture
			//FA_REGLEMENTS etant la table des reglements, et FICHE un contenant d'informations

			$refReglement = NULL;

			// Suite a un bug qui faisait passer des règlements sans les lier à l'échéance, on verifie d'abord si un règlement existe, pour eviter d'en creer un nouveau
			// En cas de bug comme l'autre fois, ce test devrait marcher en retestant une validation
			// Ok, en ligne

			$testReglement= $oWS->LireTable(array('_nomtable'=>"FA_REGLEMENTS", '_criteres'=>"NUMEROPIECE='".$ficheEcheance->CODEFACTURE."'", '_ordre'=>"REFREGLEMENT DESC"));
			$testReglementXML= new SimpleXMLElement($testReglement->LireTableResult);
			foreach($testReglementXML->FICHE as $ficheReglement)
			{
				if ($ficheReglement->NUMEROPIECE == $ficheEcheance->CODEFACTURE)
				{
					$refReglement = $ficheReglement->REFREGLEMENT;
				}
			}

			if ($refReglement == NULL)
			{
				$requeteReglement=
					"<FA_REGLEMENTS>
						<FICHE>
							<CODECLIENT>".$ficheEcheance->CODECLIENT."</CODECLIENT>
							<COMMENTAIRES>Commande du site, validée et réglée automatiquement par le plugin d'Adrien</COMMENTAIRES>
							<DATEREGLEMENT>".date('d/m/Y')."</DATEREGLEMENT>
							<MONTANT>".$ficheEcheance->MONTANT."</MONTANT>
							<NUMEROPIECE>".$ficheEcheance->CODEFACTURE."</NUMEROPIECE>
							<TYPEPAIEMENT>".$typePaiement."</TYPEPAIEMENT>
						</FICHE>
					</FA_REGLEMENTS>";
				//A utiliser pour envoyer la requete et voir la réponse, sinon, utiliser l'autre
				//var_dump($oWS->InsererTable(array('_cFiche'=>$requeteReglement)));
				//die();
				$resultReglement = $oWS->InsererTable(array('_cFiche'=>$requeteReglement));
				//************************  Le reglement est créé ************************************
				//
				//            Il faut maintenant lier le règlement à son echeance
				//
				//************************************************************************************

				$oResReglement= $oWS->LireTable(array('_nomtable'=>"FA_REGLEMENTS", '_criteres'=>"NUMEROPIECE='".$ficheEcheance->CODEFACTURE."'", '_ordre'=>"REFREGLEMENT DESC"));
				$reglementXML= new SimpleXMLElement($oResReglement->LireTableResult);
				foreach($reglementXML->FICHE as $ficheReglement)
				{
					/*foreach($ficheReglement as $key => $valeur)
					{
						echo "$key : $valeur<br/>";
					}
					*/
					$refReglement = $ficheReglement->REFREGLEMENT;
					break;
				}
			}
			
			
				$requeteReglementEcheance=
					"
					<FA_ReglementsEcheances>
						<FICHE>
							<MONTANT>".$ficheEcheance->MONTANT."</MONTANT>
							<REFECHEANCE>".$ficheEcheance->REFECHEANCE."</REFECHEANCE>
							<REFREGLEMENT>".$refReglement."</REFREGLEMENT>
						</FICHE>
					</FA_ReglementsEcheances>";
				//var_dump($requeteReglementEcheance);
				//var_dump($oWS->InsererTable(array('_cFiche'=>$requeteReglementEcheance)));
				$resultReglementEcheance = $oWS->InsererTable(array('_cFiche'=>$requeteReglementEcheance));
		}
		$nbFacturesValidees++;
		echo "<br/>Facture $nbFacturesValidees<br/>";
		var_dump($resultReglement);
		var_dump($resultReglementEcheance);
	}
}
echo "$nbFacturesValidees facture(s) validées.<br/><br/><br/>";
echo '<a href="http://www.lacentraledupoele.com/wp-admin/admin.php?page=valider-paiements">Revenir à la page précédente</a>';
/*
Olivier Meterie par mail :
Il faut insérer dans la table FA_REGLEMENTS (créer le règlement) et aussi dans FA_REGLEMENTSECHEANCES (affecter le règlement à l’échéance de la facture).
Il faut lire avant la table FA_ECHEANCES pour retrouver la référence de l’échéance de facture concernée.
*/
