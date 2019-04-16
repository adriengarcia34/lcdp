<?php

//CREATION D'UNE FACTURE EN DEUX TEMPS
//
//1st : Créer la facture (FA_FACTURES)
//
//2nd : Créer les lignes produits(FA_detailArticleFacture)

//Récupération de la commande avec l'ID recu
$order = new WC_Order( $order_id );


//Création d'une variable structurée en XML, et intégration des variables passées par Woocommerce
//FA_FACTURES = Table des factures
//FA_détailsFacture ou autre : Ligne de produit d'une facture

// $calcshipping = $order->calculate_shipping();
// $method = $order->get_shipping_method();
// $methods = $order->get_shipping_methods();

$date = new DateTime($order->order_date);
$retourXML = "<?xml version='1.0' encoding='utf-8'?>
<FA_GRILLEIMPORT>";

foreach($order->get_items() as $ligneCommande)
{	
	$produit = $order->get_product_from_item( $ligneCommande );
	if($order->get_shipping_company!='')
	{
		$raisonSociale = $order->get_shipping_company();
	}
	else
	{
		$raisonSociale = $order->get_billing_last_name();
	}
	
	switch($order->get_shipping_country())
	{
		case 'FR' :
			$pays = 'France';
			break;
		case 'BE' :
			$pays = 'Belgique';
			break;
		default :
			$pays = $order->get_shipping_country();
			break;
	}
	
	
	$retourXML .= "
		<FICHE>
			<CHAMP_001>".$order->get_order_number()."</CHAMP_001>
			<CHAMP_002>".$date->format('d/m/Y')."</CHAMP_002>
			<CHAMP_004>".$raisonSociale."</CHAMP_004>
			<CHAMP_005>\"".$order->get_shipping_address_1()."\"</CHAMP_005>
			<CHAMP_006>\"".$order->get_shipping_address_2()."\"</CHAMP_006>
			<CHAMP_008>".$order->get_shipping_postcode()."</CHAMP_008>
			<CHAMP_009>".$order->get_shipping_city()."</CHAMP_009>
			<CHAMP_010>".$pays."</CHAMP_010>
			<CHAMP_011>".$order->get_billing_last_name()."</CHAMP_011>
			<CHAMP_012>".$order->get_billing_first_name()."</CHAMP_012>
			<CHAMP_021>COME-LAF</CHAMP_021>
			<CHAMP_022>LAFORTUNE</CHAMP_022>
			<CHAMP_023>Come</CHAMP_023>
			<CHAMP_024>".$order->get_payment_method()."</CHAMP_024>
			<CHAMP_025>".$date->format('d/m/Y')."</CHAMP_025>
			<CHAMP_026>".$produit->get_sku()."</CHAMP_026>
			<CHAMP_027>".$ligneCommande['name']."</CHAMP_027>
			<CHAMP_028>".$ligneCommande['qty']."</CHAMP_028>
			<CHAMP_029>".($ligneCommande['subtotal']/$ligneCommande['qty'])."</CHAMP_029>
			<CHAMP_033>1</CHAMP_033>
			<CHAMP_042>".$order->get_billing_email()."</CHAMP_042>
			<CHAMP_043>".$order->get_billing_phone()."</CHAMP_043>
		</FICHE>";
	
}

$expedition = $order->get_items('shipping');

foreach($expedition as $informationsExpeditions)
{
	$retourXML .= "<FICHE>
		<CHAMP_001>".$order->get_order_number()."</CHAMP_001>
		<CHAMP_002>".$date->format('d/m/Y')."</CHAMP_002>
		<CHAMP_004>".$order->get_billing_last_name()."</CHAMP_004>
		<CHAMP_005>\"".$order->get_shipping_address_1()."\"</CHAMP_005>
		<CHAMP_006>\"".$order->get_shipping_address_2()."\"</CHAMP_006>
		<CHAMP_008>".$order->get_shipping_postcode()."</CHAMP_008>
		<CHAMP_009>".$order->get_shipping_city()."</CHAMP_009>
		<CHAMP_010>".$pays."</CHAMP_010>
		<CHAMP_011>".$order->get_billing_last_name()."</CHAMP_011>
		<CHAMP_012>".$order->get_billing_first_name()."</CHAMP_012>
		<CHAMP_021>COME-LAF</CHAMP_021>
		<CHAMP_022>LAFORTUNE</CHAMP_022>
		<CHAMP_023>Come</CHAMP_023>
		<CHAMP_024>".$order->get_payment_method()."</CHAMP_024>
		<CHAMP_025>".$date->format('d/m/Y')."</CHAMP_025>
		<CHAMP_026>".$informationsExpeditions['name']."</CHAMP_026>
		<CHAMP_027>".$informationsExpeditions['method_title']."</CHAMP_027>
		<CHAMP_028>1</CHAMP_028>
		<CHAMP_029>".$informationsExpeditions['total']."</CHAMP_029>
		<CHAMP_033>1</CHAMP_033>
		<CHAMP_042>".$order->get_billing_email()."</CHAMP_042>
		<CHAMP_043>".$order->get_billing_phone()."</CHAMP_043>
	</FICHE>
</FA_GRILLEIMPORT>";
	
	break;
}

//Fin du fichier, notes ci-dessous
/*
Structure pour ecriture sur facture
Faisable mais bien plus contraignant que FA_GRILLEIMPORT car une requete sur FA_FACTURES ne gère pas la création automatique d'un client ou d'un produit si inexistant
Donc sujet à beaucoup d'erreurs

$nouvelleFacture = 
	"<FA_FACTURES>
		<FICHE>
			<Adresse1>".$order->get_billing_address_1()."</Adresse1>
			<Adresse2>".$order->get_billing_address_2()."</Adresse2>
			<Adresse3></Adresse3>
			<CiviliteContact></CiviliteContact>
			<Civilitevendeur></Civilitevendeur>
			<CodeClient></CodeClient>
			<CodeDevis></CodeDevis>
			<CodeDossier>".$order_id."</CodeDossier>
			<CodeFacture></CodeFacture>
			<CodeTva></CodeTva>
			<CodeVendeur>COME-LAF</CodeVendeur>
			<Comptabilisee></Comptabilisee>
			<Contactclient></Contactclient>
			<CP>".$order->get_billing_postcode()."</CP>
			<CpVille>".$order->get_billing_postcode()."</CpVille>
			<DateCrea>".$order->get_date_paid()."</DateCrea>
			<DateDernierReglement></DateDernierReglement>
			<DateEcheance>".$order->get_date_paid()."</DateEcheance>
			<CHAMP_042>".$order->get_billing_email()."</CHAMP_042>
			<Email_Expediteur></Email_Expediteur>
			<Emailvendeur></Emailvendeur>
			<FraisPortHT><FraisPortHT>
			<FraisPortTotTVA><FraisPortTotTVA>
			<FraisPortTTC></FraisPortTTC>
			<ModeTransport></ModeTransport>
			<MONTANTREGLE></MONTANTREGLE>
			<MONTANTRESTANTDU></MONTANTRESTANTDU>
			<Nom></Nom>
			<NomContact>".$order->get_billing_last_name()."</NomContact>
			<NomPrenomVendeur></NomPrenomVendeur>
			<NomVendeur>".$order->get_shipping_last_name()."</NomVendeur>
			<NumeroPiece>".$order->get_order_number( )."</NumeroPiece>
			<ObjetFacture></ObjetFacture>
			<CHAMP_010>".$order->get_billing_country()."</CHAMP_010>
			<PrenomContact>".$order->get_billing_first_name()."</PrenomContact>
			<Prenomvendeur>".$order->get_shipping_first_name()."</Prenomvendeur>
			<RaisonSociale>".$order->get_billing_last_name()."</RaisonSociale>
			<RefUtilisateur></RefUtilisateur>
			<Reglement></Reglement>
			<Societes_Adresse1>".$order->get_shipping_address_1()."</Societes_Adresse1>
			<Societes_Adresse2>".$order->get_shipping_address_2()."</Societes_Adresse2>
			<Societes_Adresse3></Societes_Adresse3>
			<Societes_CodePostal>".$order->get_shipping_postcode()."</Societes_CodePostal>
			<Societes_Fax></Societes_Fax>
			<Societes_Pays>".$order->get_shipping_country()."</Societes_Pays>
			<Societes_RaisonSociale>".$order->get_shipping_company()."</Societes_RaisonSociale>
			<Societes_Siren></Societes_Siren>
			<Societes_Telephone></Societes_Telephone>
			<Societes_Ville></Societes_Ville>
			<Solde></Solde>
			<SoldeAFacturer></SoldeAFacturer>
			<TauxTVA>1</TauxTVA>
			<Tot_Poid></Tot_Poid>
			<TotalAvRemise></TotalAvRemise>
			<TotalTTCAvRemise></TotalTTCAvRemise>
			<TotalTVAAvRemise></TotalTVAAvRemise>
			<TotColis></TotColis>
			<TotHT></TotHT>
			<TotHTAvecPort></TotHTAvecPort>
			<TotHTPrecedente></TotHTPrecedente>
			<TotHTRealise></TotHTRealise>
			<TotHTSansPort></TotHTSansPort>
			<TotTTC>".$order->get_order_item_totals()."</TotTTC>
			<TotTTCPrecedente></TotTTCPrecedente>
			<TotTTCRealise></TotTTCRealise>
			<TotTTCSansPort></TotTTCSansPort>
			<TotTVA></TotTVA>
			<TotTVA_2></TotTVA_2>
			<TOTTVAPRECEDENTE></TOTTVAPRECEDENTE>
			<TOTTVAREALISE></TOTTVAREALISE>
			<TotTVASansPort></TotTVASansPort>
			<TVA_Intra></TVA_Intra>
			<TYPEPAIEMENT>".$order->get_payment_method()."</TYPEPAIEMENT>
			<Validee></Validee>
			<Ville>".$order->get_billing_city()."</Ville>
		</FICHE>
	</FA_FACTURES>";
*/