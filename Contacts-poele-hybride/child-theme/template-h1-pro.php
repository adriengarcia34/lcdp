<?php
/*
Template Name: H1 - Pro
Template Post Type: page
*/

// File Security Check
if ( ! defined( 'ABSPATH' ) ) { exit; }

$config = Presscore_Config::get_instance();
$config->set('template', 'page');
presscore_config_base_init();

//get_header(); ?>
<?php wp_enqueue_style('h1style', get_stylesheet_directory_uri().'/css/contacts-h1/h1style.css',array('style')); ?>

<?php
	if(isset($_GET['contact']))
	{
		get_header();
		$idContact = str_replace('#', '', $_GET['contact']);
		global $wpdb;
		$contactInfos = $wpdb->get_results("SELECT * FROM H1_Donnees_Part WHERE idH1_Donnees_Part = (".$idContact.")");
		if($contactInfos[0])
		{
			echo '<a href="http://pro.lcdp-distribution.com/vos-contacts-aduro/"><button>Retour</button></a><br/><br/>';
			echo "<b>Numéro client</b> : ".$contactInfos[0]->idH1_Donnees_Part."<br/>";
			echo "<b>Nom client</b> : ".$contactInfos[0]->prenom." ".$contactInfos[0]->nom."<br/>";
			echo "<b>Adresse</b> : ".$contactInfos[0]->adresse.", ".$contactInfos[0]->cp." ".$contactInfos[0]->ville."<br/>";
			echo "<b>Téléphone</b> : ".$contactInfos[0]->telephone."<br/>";
			echo "<b>Email</b> : ".$contactInfos[0]->email."<br/>";
			echo "<b>Description du projet</b> : ".$contactInfos[0]->projet_desc."<br/>";
			
			get_footer();
			die();//Pour éviter de charger tout le reste, on stoppe le processus ici
		}
	}
	if(isset($_GET['id']))//Si un pro clique sur un bouton "voir plus", on va lui afficher le contenu du client sélectionné
	{
		get_header();
		global $wpdb;
		$contactInfos = $wpdb->get_results("SELECT * FROM H1_Donnees_Part WHERE idH1_Donnees_Part = (".$_GET['id'].")");
		if($contactInfos[0])
		{
			echo '<a href="http://pro.lcdp-distribution.com/vos-contacts-aduro/"><button>Retour</button></a><br/><br/>';
			echo "<b>Numéro client</b> : ".$contactInfos[0]->idH1_Donnees_Part."<br/>";
			echo "<b>Nom client</b> : ".$contactInfos[0]->prenom." ".$contactInfos[0]->nom."<br/>";
			echo "<b>Adresse</b> : ".$contactInfos[0]->adresse.", ".$contactInfos[0]->cp." ".$contactInfos[0]->ville."<br/>";
			echo "<b>Téléphone</b> : ".$contactInfos[0]->telephone."<br/>";
			echo "<b>Email</b> : ".$contactInfos[0]->email."<br/>";
			echo "<b>Demande du ".$contactInfos[0]->date_demande."</b> : ".$contactInfos[0]->demande."<br/>";
			echo "<b>Description du projet</b> : ".$contactInfos[0]->projet_desc."<br/>";
			
			get_footer();
			die();//Pour éviter de charger tout le reste, on stoppe le processus ici
		}
	}

	if(! current_user_can( 'administrator' ))
	{
		$currentID = get_current_user_id();
	}
	elseif(isset($_GET['voir_installateur']))
	{
		
		$currentID = $_GET['voir_installateur'];
	}
	else
	{
		wp_redirect(get_site_url());
	}

	$userCredits = get_user_meta($currentID,'h1_credits');
	//var_dump($userCredits);

	if(isset($_POST['action']))
	{
		$actionPost = str_replace('\\', '', $_POST['action']);
		if($actionPost=="J'accepte")
		{
			$wpdb->update( 
				'H1_Actions', 
				array( 
					'statut' => 'Accepté',	// string 
				), 
				array( 'idH1_Actions' => $_POST['idContact'] ), 
				array( 
					'%s',	// value1
				), 
				array( '%s' ) 
			);
			/*
			Décommenter si crédits activés
			(int)$userCredits[0] -= 5;
			update_user_meta( $currentID, 'h1_credits', $userCredits[0] );*/
			//echo '<meta http-equiv="refresh" content="0" />';
		}
		elseif($actionPost=="Je refuse")
		{
			$wpdb->update( 
				'H1_Actions', 
				array( 
					'statut' => 'Refusé',	// string 
					'motif' => $_POST['motif-refus']
				), 
				array( 'idH1_Actions' => $_POST['idContact'] ), 
				array( 
					'%s',	// value1
					'%s'	// value2
				), 
				array( '%s' ) 
			);
			//echo '<meta http-equiv="refresh" content="0" />';
			$userID = get_current_user_id();
			$nomSociete = get_user_meta($userID,'company_name',true);
			wp_mail('lpz@sas-lcdp.com',//$to
					'ESPACE ADURO : Refus de contact de '.$nomSociete,//$subject
					"La société ".$nomSociete." vient de refuser un contact.<br/>
					Motif : ".$_POST['motif-refus']."
					Rediriger les contacts en attente : http://pro.lcdp-distribution.com/contacts-aduro/",//$contenuMail
					'');//$headers );//Envoi du mail de notification à la personne concernée
		}
		
        header('Location: http://pro.lcdp-distribution.com/vos-contacts-aduro/');//Permet de recharger la page après le processus d'acceptation. Cela évite de refaire une validation en cas de F5
	}



		get_header();
		
		$userCredits = get_user_meta($currentID,'h1_credits');
		echo "<h1>".get_user_meta($currentID,'company_name',true)."</h1>";

		//En cas de visite d'un admin, affiche ses contacts recus, acceptés et refusés
		if(current_user_can( 'administrator' ))
		{
			$dateAnDernier = date("Y-m-d", strtotime("-1 year"));
			$nbContactsRecus = $wpdb->get_var("SELECT COUNT(*) FROM H1_Actions WHERE pro=".$currentID." AND dateStatut>".$dateAnDernier);
			$nbContactsAcceptes = $wpdb->get_var("SELECT COUNT(*) FROM H1_Actions WHERE statut='Accepté' AND pro=".$currentID." AND dateStatut>".$dateAnDernier);
			$nbContactsRefuses = $wpdb->get_var("SELECT COUNT(*) FROM H1_Actions WHERE statut='Refusé' AND pro=".$currentID ." AND dateStatut>".$dateAnDernier);
			
			echo '<b>'.$nbContactsRecus.'</b> contacts proposés au total, dont <b>'.$nbContactsAcceptes.'</b> acceptés et <b>'.$nbContactsRefuses.'</b> refusés<br/>';
		}
		//echo "<p>Vos crédits premium : ".$userCredits[0]. '€</p>';
		// x contacts reçus, y acceptés, z refusés
		/*
		if($userCredits[0] == '' || $userCredits[0] < 5)
			echo '<p><strong style="color:red;">Attention !</strong> Vous n\'avez pas assez de crédits pour accepter un contact.</p>';
		echo '<a href=http://www.lacentraledupoele.com/produit/credit-50e-reseau-premium/><button type="button">Recharger</button></a><br/><br/>';
		*/

		//global $wpdb;
		$contactsActionAttente = $wpdb->get_results("SELECT * FROM H1_Actions WHERE statut='En attente' AND isActive=1 AND pro=".$currentID." ORDER BY idH1_Actions DESC");
		$idContacts = array();
		foreach($contactsActionAttente as $contact)
		{
			if($contact->isActive=="1")
				$idContacts[$contact->idH1_Actions] = $contact->part;
		}
		$contactInfos = array();
		if(!empty($idContacts))
		{
			$listeID = implode(', ', $idContacts);
			$contactInfos = $wpdb->get_results("SELECT * FROM H1_Donnees_Part WHERE idH1_Donnees_Part IN (".$listeID.")");
			$contactInfos = json_decode(json_encode($contactInfos), True);
		}


?>

<head>
	<script type="text/javascript">
      function afficher_form_refus(form) {
      	var action = "action"+form;
      	var refus = "refus"+form;

        var valeur = document.getElementById(action).value;
        var element = document.getElementById(refus);
        if(valeur == "Je refuse")
        {
        	element.style.visibility = "visible";
        }
        else
        {
        	element.style.visibility = "hidden";
        }
        
      }
    </script>
</head>
<body>
	<?php
	if ($contactsActionAttente!=NULL)
	{//affiche la table des contacts en attente?>
<h2>En attente</h2>
<div style="overflow-x:auto;">
	<table class="tab_contact">
		<tr>
			<th>Date de réception</th>
			<th>Ville</th>
			<th>Code Postal</th>
			<th>Type de demande</th>
			<th>Description du projet</th>
			<th>Action</th>
			<th>Valider</th>
		</tr>
		<?php
		foreach($contactsActionAttente as $action)
		{
			foreach($contactInfos as $info)
			{
				if($info['idH1_Donnees_Part']==$action->part)
				{
					$infoActuelle = $info;
					break;
				}
			}
			if($infoActuelle)
			{
				$dt = DateTime::createFromFormat('Y-m-d', $action->dateStatut);
				if(!$dt)
					$dt = DateTime::createFromFormat('Y-m-d H:i:s', $action->dateStatut);
			?>
		<tr>
			<td><?php echo $dt->format('d/m/Y'); ?></td>
			<td><?php echo $infoActuelle['ville']; ?></td>
			<td><?php echo $infoActuelle['cp']; ?></td>
			<td><?php echo $infoActuelle['demande']; ?></td>
			<td class="h1_desc"><?php echo $infoActuelle['projet_desc']; ?></td>
			<?php echo "<form id=\"form".$action->idH1_Actions.'" action="" method="post">'; ?>
			<td><?php echo '<select name="action" class="h1action" form="form'.$action->idH1_Actions.'" onchange="afficher_form_refus('.$action->idH1_Actions.');" id="action'.$action->idH1_Actions.'">';?>
		          <option>--Choisir--</option>
		          <option>J'accepte</option>
		          <?php //Remplacer la ligne du dessus par celle ci pour prendre en compte les éventuels crédits
		          //if((int)$userCredits[0]>=5) echo "<option>J'accepte -5€</option>";?>
		          <option>Je refuse</option>
		     </select>
		     <br/>
		     <?php echo '<div class="h1_formulaire_refus" id="refus'.$action->idH1_Actions.'">';?>
		     	<b>Motif de refus</b>
		     	<?php echo '<select class="h1action" form="form'.$action->idH1_Actions.'" name="motif-refus" id="refus'.$action->idH1_Actions.'">';?>
					<option>Trop loin</option>
					<option>Pas assez de temps</option>
					<option>Le H1 ne m'interesse plus</option>
					<option>Ne se prononce pas</option>
			    </select>
		     </div>
		 	</td>
		 	<input type="hidden" name="idContact" value=<?php echo '"'.$action->idH1_Actions.'"'; ?>>
			<td><input type="submit" value="Valider"></td>
			</form>
		</tr>
		<?php
			} 
		} ?>
	</table>
</div>
	<br/><br/><br/>

	<?php
	} //fin IF
	$contactsActionAccepte = $wpdb->get_results("SELECT * FROM H1_Actions WHERE statut='Accepté' AND pro=".$currentID." ORDER BY idH1_Actions DESC");
	if ($contactsActionAccepte!=NULL)
	{
		$idContacts = array();
		foreach($contactsActionAccepte as $contact)
		{
			$idContacts[$contact->idH1_Actions] = $contact->part;
		}
		$contactInfosTemp = array();
		if(!empty($idContacts))
		{
			$listeID = implode(', ', $idContacts);
			$contactInfosTemp = $wpdb->get_results("SELECT * FROM H1_Donnees_Part WHERE idH1_Donnees_Part IN (".$listeID.") ORDER BY idH1_Donnees_Part DESC");
			$contactInfosTemp = json_decode(json_encode($contactInfosTemp), True);
			$contactInfos = array();
			foreach ($contactInfosTemp as $info) {
				$contactInfos[$info['idH1_Donnees_Part']] = array(
					'prenom' 		=> $info['prenom'],
					'nom'  			=> $info['nom'],
					'adresse' 		=> $info['adresse'] , 
					'cp' 			=> $info['cp'],
					'ville' 		=> $info['ville'],
					'telephone' 	=> $info['telephone'],
					'email' 		=> $info['email'],
					'date_demande' 	=> $info['date_demande']
				);
			}
		}
	//affiche la table des contacts acceptés
?>
	<h2>Acceptés</h2>
	<div class="h1_recherche">
		<table class="h1_recherche_nom">
			<tr>
				<td>
					<form action="" name="recherche_nom" method="get">
						Nom 
						<input list="contacts" name="contact">
						<datalist id="contacts">
<?php
								foreach($contactInfos as $key => $contact)
								{
									echo '<option name="'.$key.'"value = "#'.$key.'">'.$key.' '.$contact['prenom'].' '.$contact['nom'].'</option>';
								}
?>
						</datalist>
						<input type="submit" value="Rechercher">
					</form>
				</td>
				<td>
					<form action="" method="get">
						Ville 
						<input list="villes" name="ville">
						<datalist id="villes">
							<?php
								$tabVilles = array();
								foreach ($contacts as $contact) {
									if (!in_array($contact->ville, $tabVilles))
									{
										$tabVilles[] = $contact->ville;
									}
								}
								sort($tabVilles);
								foreach ($tabVilles as $ville) {
									echo '<option value="'.$ville.'">';
								}

								?>
						</datalist>
						<input type="submit" value="Rechercher">
					</form>
				</td>
			</tr>
		</table>
	</div>
	<div style="overflow-x:auto;">
	<table width="100%" class="tab_contact">
		<tr>
			<th>Date</th>
			<th>Client</th>
			<th>Adresse</th>
			<th>Contact</th>
			<th>Projet</th>
		</tr>

<?php
			foreach($contactInfos as $key => $info)
			{
				$dt = DateTime::createFromFormat('Y-m-d', $info['date_demande']);
?>
			<tr>
				<td><?php echo "Le ".$dt->format('d/m/Y');?></td>
				<td><?php echo $info['prenom']." ".$info['nom'];?></td>
				<td><?php echo $info['adresse']. "<br/>".$info['cp']." ".$info['ville'];?></td>
				<td><?php echo "Téléphone : ".$info['telephone'] ."<br/>Email : ".$info['email'];?></td>
				<td>
					<form method="get" action="">
						<?php echo '<input type="hidden" name="id" value="'.$key.'">'; ?>
						<input type="submit" value="Voir plus">
					</form>
				</td>
			</tr>
<?php
			} //FIN FOREACH affichage part accetpés
		?></table>
	</div>
			<!-- Script pagination -->
	<script>

var $table = document.getElementById("tab_contact"),
$n = 5,
$rowCount = $table.rows.length,
$firstRow = $table.rows[0].firstElementChild.tagName,
$hasHead = ($firstRow === "TH"),
$tr = [],
$i,$ii,$j = ($hasHead)?1:0,
$th = ($hasHead?$table.rows[(0)].outerHTML:"");
var $pageCount = Math.ceil($rowCount / $n);
if ($pageCount > 1) {
	for ($i = $j,$ii = 0; $i < $rowCount; $i++, $ii++)
		$tr[$ii] = $table.rows[$i].outerHTML;
	// create a div block to hold the buttons
	$table.insertAdjacentHTML("afterend","<div id='buttons'></div");
	// the first 
	sort(1);
}

function sort($p) {
	var $rows = $th,$s = (($n * $p)-$n);
	for ($i = $s; $i < ($s+$n) && $i < $tr.length; $i++)
		$rows += $tr[$i];
	
	// now the table has a processed group of rows ..
	$table.innerHTML = $rows;
	// create the pagination buttons
	document.getElementById("buttons").innerHTML = pageButtons($pageCount,$p);
	// CSS Stuff
	document.getElementById("id"+$p).setAttribute("class","active");
}

function pageButtons($pCount,$cur) {
	var	$prevDis = ($cur == 1)?"disabled":"",
		$nextDis = ($cur == $pCount)?"disabled":"",
		$buttons = "<input type='button' class='reinitialise' value='&lt;&lt; Précédent' onclick='sort("+($cur - 1)+")' "+$prevDis+">";
	for ($i=1; $i<=$pCount;$i++)
		$buttons += "<input type='button' class='reinitialise' id='id"+$i+"'value='"+$i+"' onclick='sort("+$i+")'>";
	$buttons += "<input type='button' class='reinitialise' value='Suivant &gt;&gt;' onclick='sort("+($cur + 1)+")' "+$nextDis+">";
	return $buttons;
}
	</script>
		<?php
	} //fin IF

	if($contactsActionAccepte==NULL && $contactsActionAttente==NULL)
	{
		echo "<h3>Vous n'avez pas encore reçu de contacts.</h3>
		Vous serez notifié par email lors de la réception d'un nouveau contact.";
	}
	?>
</body>







<?php get_footer(); ?>