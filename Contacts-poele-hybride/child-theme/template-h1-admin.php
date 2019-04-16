<?php
/*
Template Name: H1 - Admin
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

		$pros = get_users(array('role' => 'reseau_premium'));
		$tabPros = array();
		foreach ($pros as $pro) {
			$tabPros[$pro->data->ID] = get_user_meta($pro->data->ID,'company_name',true);
		}
		$tabAffectation = $tabPros;
		$optionsPro = '';
		foreach ($tabAffectation as $id => $pro) {
			$optionsPro.='<option value="'.$pro.'" label="'.$id.'"></option>"';
		}
		if(isset($_GET['contact']))
		{
			get_header();
			$idContact = str_replace('#', '', $_GET['contact']);
			global $wpdb;
			$contactInfos = $wpdb->get_results("SELECT * FROM H1_Donnees_Part WHERE idH1_Donnees_Part = (".$idContact.")");
			if($contactInfos[0])
			{
				echo '<a href="http://pro.lcdp-distribution.com/contacts-aduro/">Retour</a><br/>';
				echo "<b>Client</b> #".$contactInfos[0]->idH1_Donnees_Part.", ".$contactInfos[0]->prenom." ".$contactInfos[0]->nom."<br/>";
				echo $contactInfos[0]->adresse.", ".$contactInfos[0]->cp." ".$contactInfos[0]->ville."<br/>";
				echo "Souhaite ".$contactInfos[0]->demande."<br/>";
				echo "<b>Description du projet</b> : ".$contactInfos[0]->projet_desc."<br/><br/>";
				echo "<b>Historique</b><br/>";
				$actionInfos = $wpdb->get_results("SELECT * FROM H1_Actions WHERE part = '".$idContact."' ORDER BY dateStatut DESC");
				foreach($actionInfos as $action)
				{
					$dt = DateTime::createFromFormat('Y-m-d', $action->dateStatut);
					if(!$dt)
						$dt = DateTime::createFromFormat('Y-m-d H:i:s', $action->dateStatut);
					echo $action->statut." le ".$dt->format('d/m/Y')." par <b>".get_user_meta($action->pro,'company_name',true)."</b><br/>";
				}
				get_footer();
				die();//Pour éviter de charger tout le reste, on stoppe le processus ici
			}
		}
		

		global $wpdb;
		if(isset($_POST['cgtinstallateur']))
		{
			foreach ($tabPros as $key => $pro) {
				if($pro == $_POST['cgtinstallateur'])
				{
					$dateJour = date("Y-m-d");
					$wpdb->insert("H1_Actions",array(
                            "part" => $_POST['idPart'],
                            "pro" => $key,  //id du pro localisé le plus proche, ou NULL si aucun n'a été localisé
                            "statut" => "En attente",
                            "dateStatut" => $dateJour,
                            "isActive" => 1
                	));
					/*echo $wpdb->last_error;
					die();*/
					$wpdb->update( 
						'H1_Actions', 
						array(
							'isActive' => 0
						), 
						array( 'idH1_Actions' => $_POST['idAction'] ), 
						array( 
							'%u',	// value1
						), 
						array( '%s' ) 
					);

					$mailPro = get_userdata($key);
	                $to = $mailPro->user_email;
					$subject = 'Réception d\'un projet particulier';
					$contenuMail .= 'Bonjour<br/>
	                <br/>
	                Vous avez reçu un nouveau contact intéressé par les poêles hybrides d\'Aduro.<br/>
	                Connectez-vous sur votre <a href="http://pro.lcdp-distribution.com/">espace pro</a> pour voir tous vos contacts<br/>
	                <br/>
	                Cordialement</br>
	                L\'équipe LCDP<br/>
	                -----------------------------------------------------------------------------------------------<br/>
	                Ceci est un mail automatique, merci de ne pas répondre.';
	                $headers = array('Content-Type: text/html; charset=UTF-8');
	                wp_mail($to, $subject, $contenuMail, $headers );//Envoi du mail de notification à la personne concernée
	                header('Location: http://pro.lcdp-distribution.com/contacts-aduro/');
    				exit;
				}
			}
		}

		if(isset($_POST['suppression_contact']))
		{
			$wpdb->delete('H1_Actions', array('idH1_Actions' => $_POST['idAction']), array('%d'));
			$wpdb->delete('H1_Donnees_Part', array('idH1_Donnees_Part' => $_POST['idPart']), array('%d'));
			header('Location: http://pro.lcdp-distribution.com/contacts-aduro/');
		}

		$actionsReaffecter = $wpdb->get_results("SELECT * FROM H1_Actions WHERE (relance2=1 OR statut='Refusé') AND isActive=1 ORDER BY idH1_Actions DESC");
		$actionsReaffecter = json_decode(json_encode($actionsReaffecter), True);
		$idContacts = array();
		foreach ($actionsReaffecter as $reaffect)
		{
			$idContacts[] = $reaffect['part'];
		}
		$listeID = implode(', ', $idContacts);
		$contactsReaffecter = $wpdb->get_results("SELECT * FROM H1_Donnees_Part WHERE idH1_Donnees_Part IN (".$listeID.")");
		$contactsReaffecter = json_decode(json_encode($contactsReaffecter), True);
		
get_header();//LE GET_HEADER() NE DOIT PAS ÊTRE PLACÉ AVANT LE CODE header('Location: http://pro.lcdp-distribution.com/contacts-aduro/') juste avant
			
			if(isset($_POST['date-debut'])&&isset($_POST['date-fin']))
			{
				$nbContactsRecus = $wpdb->get_var("SELECT COUNT(DISTINCT part) FROM H1_Actions WHERE dateStatut BETWEEN '".$_POST['date-debut']."' AND '".$_POST['date-fin']."'");
				$nbContactsAcceptes = $wpdb->get_var("SELECT COUNT(*) FROM H1_Actions WHERE statut='Accepté' AND dateStatut BETWEEN '".$_POST['date-debut']."' AND '".$_POST['date-fin']."'");
				//$nbContactsRefuses = $wpdb->get_var("SELECT COUNT(*) FROM H1_Actions WHERE statut='Refusé' AND dateStatut BETWEEN '".$_POST['date-debut']."' AND '".$_POST['date-fin']."'");
				echo '<b>'.$nbContactsRecus.'</b> contacts proposés depuis entre le '.$_POST['date-debut'].' et le '.$_POST['date-fin'].', dont <b>'.$nbContactsAcceptes.'</b> acceptés.';// et <b>'.$nbContactsRefuses.'</b> refusés<br/>';
			}
			else
			{
				$nbContactsRecus = $wpdb->get_var("SELECT COUNT(DISTINCT part) FROM H1_Actions");
				$nbContactsAcceptes = $wpdb->get_var("SELECT COUNT(*) FROM H1_Actions WHERE statut='Accepté'");
				//$nbContactsRefuses = $wpdb->get_var("SELECT COUNT(*) FROM H1_Actions WHERE statut='Refusé'");
				echo '<b>'.$nbContactsRecus.'</b> contacts proposés depuis le début, dont <b>'.$nbContactsAcceptes.'</b> acceptés.';// et <b>'.$nbContactsRefuses.'</b> refusés<br/>';
			}

?>
<form name="calcul_contacts" method="POST" action="">
	Date de début<input type="date" name="date-debut"/>
	Date de fin<input type="date" name="date-fin"/>
	<input type="submit" value="Calculer"/>
</form>

<body>
	<?php if($actionsReaffecter!=NULL)
	{ ?>
	<h2>Contacts à ré-affecter</h2>
	<table class="tab_contact">
		<tr>
			<th>Demande</th>
			<th>Cordonnées</th>
			<th>Contact</th>
			<th>Projet</th>
			<th>Affecté à</th>
			<th>Ré-affecter</th>
		</tr>
	<?php
	
	foreach ($actionsReaffecter as $reaffect)
	{ 
		$contact = array();
		foreach($contactsReaffecter as $contactTest)
		{
			if($contactTest['idH1_Donnees_Part']==$reaffect['part'])
			{
				$contact = $contactTest;
				break;
			}
		}
		$dt = DateTime::createFromFormat('Y-m-d', $reaffect['dateStatut']);
			if(!$dt)
				$dt = DateTime::createFromFormat('Y-m-d H:i:s', $reaffect['dateStatut']);
			if($contact['demande']=='')
			{
				$contact['demande'] = "Demande non spécifiée";
			}
			?>
		<tr>
			<td><?php echo $contact['demande']."<br/>".$dt->format('d/m/Y'); ?></td>
			<td><?php echo $contact['nom']." ".$contact['prenom']."<br/>".$contact['adresse']."<br/>".$contact['ville']." ".$contact['cp'];?></td>
			<td><?php echo $contact['telephone']."<br/><a href=\"mailto:".$contact['email']."\">".$contact['email']."</a>";?></td>
			<td class="h1_desc"><?php echo $contact['projet_desc']; ?></td>
			<td><?php
				if($tabPros[$reaffect['pro']]!== NULL)
					echo $tabPros[$reaffect['pro']];
				else
					echo "Pas de pro associé";
				if($reaffect['motif_refus']!=NULL)
					echo ", Motif refus : ".$reaffect['motif_refus'];?></td>
			<td>
			<?php
			switch($reaffect['statut'])
			{
				case "Refusé":
				case "En attente" :
				if($reaffect['isActive']=="1")
				{
				?>

				<form action="" method="post">
						<input list="installateurs" name="cgtinstallateur">
						<datalist id="installateurs">
							<?php
								foreach ($tabAffectation as $id => $pro) {
									echo '<option value="'.$pro.'" label="'.$id.'"></option>"';
								}
								?>
						</datalist>
						<input type="hidden" name="idAction" value=<?php echo '"'.$reaffect['idH1_Actions'].'"'; ?>>
						<input type="hidden" name="idPart" value=<?php echo '"'.$contact['idH1_Donnees_Part'].'"'; ?>>
						<input type="submit" value="Affecter">
				</form>
				<script type="text/javascript">
			      function delete_confirm(form) {
			      	if(confirm("Supprimer ce contact?"))
			      	{
			      		var contactDel = "supprimer_contact"+form;
			      		document.getElementById(contactDel).submit();
			      	}
			        
			      }
			    </script>
			    <?php ?>
			    <?php echo '<form id="supprimer_contact'.$reaffect['idH1_Actions'].'" action="" method="post">';?>
				<form id="supprimer_contact" action="" method="post">
					<input type="hidden" name="idAction" value=<?php echo '"'.$reaffect['idH1_Actions'].'"'; ?>>
					<input type="hidden" name="idPart" value=<?php echo '"'.$contact['idH1_Donnees_Part'].'"'; ?>>
					<input type="hidden" name="suppression_contact">
					<?php echo '<input type="button" class="button_delete" value="Supprimer" onClick="delete_confirm('.$reaffect['idH1_Actions'].');">';?>
				</form>
				<?php 
				}
				else
				{
					echo "Ce contact a été ré-affecté";
				}
				default: break;
			}?>
		</tr>
		<?php
	}
}
	?>
	</table>
	<h2>Listes des contacts</h2>
	<div class="h1_recherche">
		<table class="h1_recherche_nom">
			<tr>
<!--				<td>
					<form action="" name="recherche_nom" method="get">
						Contact 
						<input list="contacts" name="contact">
						<datalist id="contacts">
						-->
							<?php
								/*foreach($contacts as $key => $contact)
								{	//recherche par contact à retirer?
									echo '<option name="'.$key.'"value = "#'.$contact['idH1_Donnees_Part'].'">'.$contact['prenom'].' '.$contact['nom'].'</option>';
								}*/
								?>
<!--						</datalist>
						<input type="submit" value="Rechercher">
					</form>
				</td>
-->
				<td>
					<form action="http://pro.lcdp-distribution.com/vos-contacts-aduro/" name="recherche_isntallateur" method="get">
						Partenaires 
						<input list="voir_installateurs" name="voir_installateur">
						<datalist id="voir_installateurs">
							<?php
								foreach ($tabAffectation as $id => $pro) {
									echo '<option value="'.$id.'" label="'.$pro.'"></option>"';
								}
								?>
						</datalist>
						<input type="submit" value="Rechercher">
					</form>
				</td>
			</tr>
		</table>
	</div>
	<?php
		$contactActions = $wpdb->get_results("SELECT DISTINCT part FROM H1_Actions ORDER BY idH1_Actions DESC");
		$contactActions = json_decode(json_encode($contactActions), True);
	?>
	<table class="tab_contact" id="tab_contact">
		<tr>
			<th>Demande</th>
			<th>Cordonnées</th>
			<th>Contact</th>
			<th>Projet</th>
			<th>Affecté à</th>
		</tr>
		<?php
		foreach($contactActions as $contact)
		{ 
			$contactData = $wpdb->get_results("SELECT * FROM H1_Donnees_Part WHERE idH1_Donnees_Part=".$contact['part']." ORDER BY idH1_Donnees_Part DESC");
			$contactData = json_decode(json_encode($contactData), True);
			$historiqueContact = $wpdb->get_results("SELECT * FROM H1_Actions WHERE part=".$contact['part']." ORDER BY idH1_Actions DESC");
			$historiqueContact = json_decode(json_encode($historiqueContact), True);

			$dt = DateTime::createFromFormat('Y-m-d', $contactData[0]['date_demande']);
			if(!$dt)
				$dt = DateTime::createFromFormat('Y-m-d H:i:s', $contactData[0]['date_demande']);
			
			$isDernierPro = true;
			$strHistorique = '';
			foreach ($historiqueContact as $historique)
			{
				if($isDernierPro)
				{
					switch($historique['statut'])
					{
						case "Refusé": echo '<tr class="h1_refus">'; break;
						case "En attente" : echo '<tr class="h1_warning">'; break;
						default : echo '<tr class="h1_accepte">'; break;
					}
					$isDernierPro = false;
				}
				$dateStatut = DateTime::createFromFormat('Y-m-d', $historique['dateStatut']);
				if(!$dateStatut) //en cas d'erreur de format
					$dateStatut = DateTime::createFromFormat('Y-m-d H:i:s', $historique['dateStatut']);
				$strHistorique .= "Recu par ".get_user_meta($historique['pro'],'company_name',true).", ".$historique['statut']." le ".$dateStatut->format('d/m/Y');
				if($historique['motif']!= '')
					$strHistorique .= ", motif : ".$historique['motif'];
				$strHistorique .= "<br/>";
			}
			
			if($contact['demande']=='')
			{
				$contact['demande'] = "Demande non spécifiée";
			}
		?>
			<td><?php echo $contactData[0]['demande']."<br/>".$dt->format('d/m/Y'); ?></td>
			<td><?php echo $contactData[0]['prenom']." ".$contactData[0]['nom']."<br/>".$contactData[0]['adresse']."<br/>".$contactData[0]['ville']." ".$contactData[0]['cp'];?></td>
			<td><?php echo $contactData[0]['telephone']."<br/><a href=\"mailto:".$contactData[0]['email']."\">".$contactData[0]['email']."</a>";?></td>
			<td class="h1_desc"><?php echo $contactData[0]['projet_desc']; ?></td>
			<td><?php echo $strHistorique; ?></td>
		</tr>
		<?php
	} ?>
	</table>
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
</body>







<?php get_footer(); ?>