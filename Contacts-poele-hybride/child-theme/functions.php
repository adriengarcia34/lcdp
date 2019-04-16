<?php

add_action('wpcf7_before_send_mail', 'save_data_particuliers');
function save_data_particuliers($cf7)
{
    //id formuaire contact actuel
    if($cf7->id == 39587) {//ADURO HYBRID

        $form_to_DB = WPCF7_Submission::get_instance();
        if ( $form_to_DB )
            $formData = $form_to_DB->get_posted_data();

        $contenuMail = '';        

        $from = $formData["adresse"].', '.$formData["ville"];
        $from = urlencode($from);
        $pros = get_users(array('blog_id' => 2 , 'role' => 'reseau_premium'));
        
        $distanceRef = NULL;
        $idRef = NULL;
        if(isset($pros))
        {
            foreach ($pros as $pro)
            {
                $adresse = get_user_meta($pro->data->ID,'addr1');
                $ville = get_user_meta($pro->data->ID,'city');
                $to = $adresse[0].', '.$ville[0];
                $to = urlencode($to);
                $data = file_get_contents("http://maps.googleapis.com/maps/api/distancematrix/json?origins=$from&destinations=$to&language=en-EN&sensor=false");
                $data = json_decode($data);
                $time = 0;
                $distance = 0;
                if($data->status == "OK")
                {
                    foreach($data->rows[0]->elements as $road)
                    {
                        if($road->status == "OK")
                        {
                            $time += $road->duration->value;
                            $distance += $road->distance->value;

                            if($distanceRef === NULL || $distance < $distanceRef)
                            {
                                $distanceRef = $distance;
                                $idRef = $pro->data->ID;

                                //$contenuMail.= "Distance : ".$distanceRef." ID Ref : ".$idRef."<br/>";
                            }
                        }
                    }
                }
            }

            global $wpdb;
            if(isset($wpdb)) //On enregistre le contact particulier
            {
                $dateJour = date("Y-m-d");
                $formData = str_replace('\\', '', $formData); //retirer les \ laissés par CF7...
                if($formData["demande"]=='')
                {
                    $formData["demande"] = "Demande non spécifiée";
                }
                $wpdb->insert("H1_Donnees_Part",array(
                        "nom" => $formData["nom"],
                        "prenom" => $formData["prenom"],
                        "adresse" => $formData["adresse"],
                        "cp" => $formData["cp"],
                        "ville" => $formData["ville"],
                        "date_demande" => $dateJour,
                        "email" => $formData["email"],
                        "telephone" => $formData["num"],
                        "demande" => $formData["demande"],
                        "projet_desc" => $formData["projet"]
                ));

                //On récupère tout de suite l'ID de la ligne que l'on vient de créer pour l'utiliser un peu après
                $getIDs = $wpdb->get_results("SELECT idH1_Donnees_Part FROM H1_Donnees_Part WHERE nom='".$formData["nom"]."' AND cp='".$formData["cp"]."' ORDER BY idH1_Donnees_Part DESC;");
                $ID = '';
                foreach ( $getIDs as $getID ) 
                {
                    $ID = $getID->idH1_Donnees_Part;
                    break;
                }

                

                //PREPARATION ENVOI DE MAIL
                if($idRef != NULL) //Un pro a bien été associé précédemment. On prépare son mail de notification
                {
                    //On crée l'action
                    $wpdb->insert("H1_Actions",array(
                                "part" => $ID,
                                "pro" => $idRef,  //id du pro localisé le plus proche, ou NULL si aucun n'a été localisé
                                "statut" => "En attente",
                                "dateStatut" => $dateJour,
                                "isActive" => 1
                    ));

                    //On prépare le contenu du mail de notification au PRO
                    $mailPro = get_userdata($idRef);
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
                    //Puis mail au particulier
                    $to = $formData["email"];
                    $subject = 'Confirmation de votre demande ';
                    $contenuMail = 'Bonjour,<br/>
                    <br/>
                    Votre demande a été prise en compte. Un professionnel dans votre secteur se rapprochera de vous dans les plus brefs délais pour votre projet d\'installation d\'un poêle Aduro Hybride.<br/>
                    <br/>
                    Cordialement,
                    L\'équipe LCDP
                    <br/>
                    -----------------------------------------------------------------------------------------------<br/>
                    Ceci est un mail automatique, merci de ne pas répondre.';
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    wp_mail($to, $subject, $contenuMail, $headers );//Envoi du mail de notification à la personne concernée

                }
                else //echec de localisation, notification en interne
                {
                    $to = $formData["email"];
                    $subject = 'Erreur de saisie de votre adresse';
                    $contenuMail = 'Bonjour,<br/>
                    <br/>
                    Suite à votre recherche d\'installateur de la gamme Hybride d\'Aduro, nous n\'avons pas pu localiser votre adresse.<br/>
                    Nous vous invitons à vérifier vos informations et à remplir de nouveau le formulaire en <a href="http://lcdp-distribution.com">cliquant ici</a>.<br/>
                    <br/>
                    Vos informations : <br/>
                    Nom : '.$formData["prenom"].' '.$formData["nom"].'<br/>
                    Adresse : '.$formData["adresse"].' '.$formData["ville"].' '.$formData["cp"].'<br/>
                    Demande : '.$formData["demande"].'<br/>
                    Projet : '.$formData["projet"].'<br/>
                    En cas de problème, contactez-nous par téléphone au 09 51 99 51 12.<br/>
                    <br/>
                    -----------------------------------------------------------------------------------------------<br/>
                    Ceci est un mail automatique, merci de ne pas répondre.';
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    wp_mail($to, $subject, $contenuMail, $headers );//Envoi du mail de notification à la personne concernée
                }

                
            }
        }
    }
    elseif($cf7->id == 39829) {//SUTI

        $form_to_DB = WPCF7_Submission::get_instance();
        if ( $form_to_DB )
            $formData = $form_to_DB->get_posted_data();

        $contenuMail = '';        

        $from = $formData["adresse"].', '.$formData["ville"];
        $from = urlencode($from);
        $pros = get_users(array('blog_id' => 2 , 'role' => 'reseau_suti'));
        //blog 2 = site pro, voir table wp_blogs
        
        $distanceRef = NULL;
        $idRef = NULL;
        if(isset($pros))
        {
            foreach ($pros as $pro)
            {
                $adresse = get_user_meta($pro->data->ID,'addr1');
                $ville = get_user_meta($pro->data->ID,'city');
                $to = $adresse[0].', '.$ville[0];
                $to = urlencode($to);
                $data = file_get_contents("http://maps.googleapis.com/maps/api/distancematrix/json?origins=$from&destinations=$to&language=en-EN&sensor=false");
                $data = json_decode($data);
                $time = 0;
                $distance = 0;
                if($data->status == "OK")
                {
                    foreach($data->rows[0]->elements as $road)
                    {
                        if($road->status == "OK")
                        {
                            $time += $road->duration->value;
                            $distance += $road->distance->value;

                            if($distanceRef === NULL || $distance < $distanceRef)
                            {
                                $distanceRef = $distance;
                                $idRef = $pro->data->ID;

                                //$contenuMail.= "Distance : ".$distanceRef." ID Ref : ".$idRef."<br/>";
                            }
                        }
                    }
                }
            }

            global $wpdb;
            if(isset($wpdb)) //On enregistre le contact particulier
            {
                $dateJour = date("Y-m-d");
                $formData = str_replace('\\', '', $formData); //retirer les \ laissés par CF7...
                if($formData["demande"]=='')
                {
                    $formData["demande"] = "Demande non spécifiée";
                }
                $wpdb->insert("SUTI_Donnees_Part",array(
                        "nom" => $formData["nom"],
                        "prenom" => $formData["prenom"],
                        "adresse" => $formData["adresse"],
                        "cp" => $formData["cp"],
                        "ville" => $formData["ville"],
                        "date_demande" => $dateJour,
                        "email" => $formData["email"],
                        "telephone" => $formData["num"],
                        "demande" => $formData["demande"],
                        "projet_desc" => $formData["projet"]
                ));

                //On récupère tout de suite l'ID de la ligne que l'on vient de créer pour l'utiliser un peu après
                $getIDs = $wpdb->get_results("SELECT idSUTI_Donnees_Part FROM SUTI_Donnees_Part WHERE nom='".$formData["nom"]."' AND cp='".$formData["cp"]."' ORDER BY idSUTI_Donnees_Part DESC;");
                $ID = '';
                foreach ( $getIDs as $getID ) 
                {
                    $ID = $getID->idSUTI_Donnees_Part;
                    break;
                }

                

                //PREPARATION ENVOI DE MAIL
                if($idRef != NULL) //Un pro a bien été associé précédemment. On prépare son mail de notification
                {
                    //On crée l'action
                    $wpdb->insert("SUTI_Actions",array(
                                "part" => $ID,
                                "pro" => $idRef,  //id du pro localisé le plus proche, ou NULL si aucun n'a été localisé
                                "statut" => "En attente",
                                "dateStatut" => $dateJour,
                                "isActive" => 1
                    ));

                    //On prépare le contenu du mail de notification au PRO
                    $mailPro = get_userdata($idRef);
                    $to = $mailPro->user_email;
                    $subject = 'Réception d\'un projet particulier';
                    $contenuMail .= 'Bonjour<br/>
                    <br/>
                    Vous avez reçu un nouveau contact intéressé par les poêles SUTI.<br/>
                    Connectez-vous sur votre <a href="http://pro.lcdp-distribution.com/">espace pro</a> pour voir tous vos contacts<br/>
                    <br/>
                    Cordialement</br>
                    L\'équipe LCDP<br/>
                    -----------------------------------------------------------------------------------------------<br/>
                    Ceci est un mail automatique, merci de ne pas répondre.';
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    wp_mail($to, $subject, $contenuMail, $headers );//Envoi du mail de notification à la personne concernée
                    //Puis mail au particulier
                    $to = $formData["email"];
                    $subject = 'Confirmation de votre demande ';
                    $contenuMail = 'Bonjour,<br/>
                    <br/>
                    Votre demande a été prise en compte. Un professionnel dans votre secteur se rapprochera de vous dans les plus brefs délais pour votre projet d\'installation d\'un poêle SUTI.<br/>
                    <br/>
                    Cordialement,
                    L\'équipe LCDP
                    <br/>
                    -----------------------------------------------------------------------------------------------<br/>
                    Ceci est un mail automatique, merci de ne pas répondre.';
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    wp_mail($to, $subject, $contenuMail, $headers );//Envoi du mail de notification à la personne concernée

                }
                else //echec de localisation, notification en interne
                {
                    $to = $formData["email"];
                    $subject = 'Erreur de saisie de votre adresse';
                    $contenuMail = 'Bonjour,<br/>
                    <br/>
                    Suite à votre recherche d\'installateur de la gamme SUTI, nous n\'avons pas pu localiser votre adresse.<br/>
                    Nous vous invitons à vérifier vos informations et à remplir de nouveau le formulaire en <a href="http://lcdp-distribution.com">cliquant ici</a>.<br/>
                    <br/>
                    Vos informations : <br/>
                    Nom : '.$formData["prenom"].' '.$formData["nom"].'<br/>
                    Adresse : '.$formData["adresse"].' '.$formData["ville"].' '.$formData["cp"].'<br/>
                    Demande : '.$formData["demande"].'<br/>
                    Projet : '.$formData["projet"].'<br/>
                    En cas de problème, contactez-nous par téléphone au 09 51 99 51 12.<br/>
                    <br/>
                    -----------------------------------------------------------------------------------------------<br/>
                    Ceci est un mail automatique, merci de ne pas répondre.';
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    wp_mail($to, $subject, $contenuMail, $headers );//Envoi du mail de notification à la personne concernée
                }

                
            }
        }
    }
}