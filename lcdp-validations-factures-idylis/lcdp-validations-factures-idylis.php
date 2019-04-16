<?php
/*
Plugin Name: LCDP - Idylis Paiements automatiques
Plugin URI: http://www.lacentraledupoele.com
Description: Utilise l'API d'Idylis. Donne acces a une page pour valider les paiements. Utilise le compte Idylis de LCDP pour la creation de facture et les validations
Version: 0.1
Author: Adrien GARCIA
Author URI: http://www.lacentraledupoele.com
License: GPL2
*/

//0144523193


//Ajouter le menu sur le back office de Wordpress
// add_menu_page(string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', string $icon_url = '', int $position = null);
add_action('admin_menu', 'my_menu_pages');
function my_menu_pages(){
    add_menu_page('Valider paiements du site', 'Valider paiements Idylis', 'manage_options', 'valider-paiements', 'page_render', 'dashicons-thumbs-up' );
}

function page_render()
{
	
	//Affichage du titre
    global $title;

    print '<div class="wrap">';
    print "<h1>$title</h1>";
    print '</div>';
	
	//Recuperation du fichier
	$plugins_url = plugin_dir_url( __FILE__ );//Recuperation du chemin du plugin
	$affichage = '<b>Commande-Type de paiement</b><br/>';
	
	echo "<form method='post' action='".$plugins_url."validation_factures.php'>";
	echo "<input type='submit' class='button button-primary' value='Validation'>
	</form>
	<br/>";
	
}

function creer_facture_idylis($order_id)
{
	//Initialisation client SOAP et authentification placée dans le Header
	require("Connexion_Idylis_LCDP.php");

	//Appel de la méthode pour sélectionner la base de données sur laquelle se connecter en fonction du module à utiliser
	$oWS->DefinirModule(array('_codemodule'=>'FA'));
	
	//Récupération et mise en forme XML des données
	require("genererXML.php");
	
	//Envoi XML sur Idylis pour créer la facture
	$result = $oWS->InsererTable(array('_cFiche'=>$retourXML));

	
}
//Ajout de l'action HOOK lors du passage "en cours - processing" d'une commande
add_action( 'woocommerce_order_status_processing', 'creer_facture_idylis', 10, 1);

?>