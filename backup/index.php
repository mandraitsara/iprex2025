<?php
//exit;

umask(002);	
$time_start = microtime(true);
$version = '7.1';
/**
 * PREREQUIS :
 * L'extension php zip avec la classe ZipArchive doivent être installés
 * La fonction system ne doit pas être bloquée pour appeler mysqldump
*/

// Définition des constantes
ini_set('display_errors',1);
ini_set('max_execution_time', 600); //600 seconds = 10 minutes
date_default_timezone_set("Europe/Paris");

// Définition du ROOT relatif
define('ROOT', '/var/www/iprexdev/');

//inclusion du fichier pour les mails
include_once (ROOT."backup/phpmailer/class.phpmailer.php"); 
	
/* ###########################################################
 * PARAMETRES 	
 * #########################################################*/
	
/**
 * PARAMÈTRES PRINCIPAUX
 */
$siteName 				= 'iprex';  						// Nom de l'archive qui sera créée	
$dirToSave 				= '/var/www/iprexdev';					// ROOT pour sauver tout le site
$dirsNotToSaveArray 	= array('/var/www/iprexdev/saves', 
								'/var/www/iprexdev/vendor',
								'/var/www/iprexdev/logsql',
								'/var/www/iprexdev/_check',
								'/var/www/iprexdev/_backup_check',
								'/var/www/iprexdev/backup',
								'/var/www/iprexdev/temp',
								'/gescom/bl/20',
								'/gescom/bl/21',
								'/gescom/bl/22',
								'/gescom/facture/20',
								'/gescom/facture/21',
								'/gescom/facture/22',
								'/uploads/2046',
								'/uploads/2048',
								'/uploads/2060',
								'/uploads/2084',
								'/uploads/2099',
								'/uploads/2108',
								'/uploads/2111',
								'/uploads/2122',
								'/uploads/2146',
								'/uploads/2150',
								'/uploads/2156',
								'/uploads/2166',
								'/uploads/2170',
								'/uploads/2174',
								'/uploads/2194',
								'/uploads/2197'
							);	// Liste des répertoires à ne pas sauvegarder
$mysqlSaveDir 			= $dirToSave.'/backup/MySQL';  		// Dossier de sauvegarde pour les fichiers de tailles de la BDD

 /**
  * DESTINATION DES SAVES
  * Chemin du répertoire local contenant les archives depuis la racine du site internet
  *(avant un éventuel transfert FTP)
  * - Utiliser ROOT.'/../../saves' pour un sous-dossier de /www (comme sur intersed.info ou web3.pro) 
  * - Utiliser ROOT.'/../saves'    pour un dossier principal (comme www)
  */
$zipSaveDir = $dirToSave.'/saves'; 

/** 
 * DESTINATAIRES DES MAILS 
 */
$destinataires_mail = array('ppactol@boostervente.com', 'jim_robert@profilexport.fr'); 
// $destinataires_mail = array('ppactol@boostervente.com'); 


/**
 * MÉTHODE D'ENVOI DES MAILS :
 * OVH mutualisé : mail
 * Serveur dédié : smtp
 */
$send_mail = 'mail';
 

/**
 * A REMPLIR POUR L'ENVOI DE MAIL PAR SMTP :
 */
$smtp 		= 'localhost'; 				// "ssl0.ovh.net" ou "smtp.votredomaine.com" pour envoyer depuis OVH
$smtp_port 	= 25;						// "465" pour envoyer depuis OVH
$smtpAuth 	= false;					// "true" pour envoyer depuis OVH / "false" si pas d'authentification
$username 	= '';						// A remplir si on utilise l'authentification (compte "postmaster" sur OVH)
$password 	= '';						// A remplir si on utilise l'authentification (compte "postmaster" sur OVH)
$from 		= 'info@profilexport.fr';	// Adresse e-mail de l'expéditeur (pour OVH mettre l'adresse "postmaster")
$smtpSecure = '';						// "ssl" pour envoyer depuis OVH


/**
 * BASE DE DONNÉES
 * Ne pas renseigner pour une détection automatique (Joomla / Prestashop / GLPI / Wordpress)
 */
$db_file 		= ''; 				 		// Pour lire les informations de connexion à la BDD depuis un fichier (ex : ROOT.'/config.php'), sinon laisser vide.
$mysql_server 	= 'localhost'; 		 		// Serveur BDD ou variable (ou define) à lire dans le fichier (sans $)
$mysql_login 	= 'iprex_prod'; 		 	// Utilisateur BDD ou variable (ou define) à lire dans le fichier (sans $)
$mysql_bdd 		= 'iprex_prod'; 			// Nom BDD ou variable (ou define) à lire dans le fichier (sans $)
$mysql_passwd 	= 'kVKZmNdMIP8AdKfe'; 		// Mot de passe BDD ou variable (ou define) à lire dans le fichier (sans $)
$mysql_port 	= '3306';

/**
 * FTP 
 * Ne pas compléter en cas de sauvegarde sur le même serveur
 */
 
 $ftp_server 	= "localhost";  		// Serveur FTP de destination connexion sur "Server"
 $ftp_user_name = "iprex";      		// Identifiant FTP
 $ftp_user_pass = "IpreX-38PWD@!+";    	// Mot de passe FTP
 $ftp_dir 		= "/saves";     		// Dossier ou va être envoyé le fichier zippé sur le FTP

// Dossier ou va être envoyé le fichier zippé sur le FTP
/*
$ftp_server 	= "";  					// Serveur FTP de destination connexion sur "Server"
$ftp_user_name 	= "";      				// Identifiant FTP
$ftp_user_pass 	= "";    				// Mot de passe FTP
$ftp_dir 		= "";     				// Dossier ou va être envoyé le fichier zippé sur le FTP
*/
/**
 * DEBUG
 * 
 */
$no_mail 		= false;
$no_zip 		= false;
$no_sql			= false;
$no_ftp			= true;

/* ###########################################################
 * FIN PARAMETRES 	
 * #########################################################*/


/** -------------------------------------------------------
 * Fonction permettant d'envoyer des mails via PHPMAILER
 *------------------------------------------------------- */
function envoiMail($sujet, $message) {   

	global $destinataires_mail, $send_mail, $smtp, $smtp_port, $smtpAuth, $username, $password, $from, $smtpSecure, $no_mail;
	
	if ($no_mail) { return false; }
	
	// Envoi d'un mail via SMTP
	if (strtolower($send_mail) == 'smtp') {
	
		$mail = new PHPmailer();
		$mail->IsSMTP();
	
		$mail->SMTPAuth 	= $smtpAuth; 	// Pas d'authentification pour le serveur SMTP
		$mail->Host 		= $smtp;	 	// Connexion au serveur SMTP
		$mail->Port 		= $smtp_port;
		$mail->Username   	= $username;
		$mail->Password   	= $password;   
		$mail->SMTPSecure 	= $smtpSecure;

		$mail->IsHTML(true); 				// Permet d'écrire un mail en HTML (conversion des balises)
		$mail->CharSet 		= 'UTF-8'; 		// Évite d'avoir des caractères chinois
		$mail->From 		= $from; 		// Adresse mail du compte qui envoi
		$mail->FromName 	= "Backup"; 	// Remplace le nom du destinataire lors de la lecture d'un email
		
		$listeTo = '';
		
		// Adresses des destinataires
		foreach ($destinataires_mail as $destinataire_mail) {
			$mail->AddAddress($destinataire_mail); 	
			$listeTo.=$destinataire_mail.',';
		}
		if (strlen($listeTo) > 0) {
			$listeTo = substr($listeTo,0,-1);
		}
		
		$mail->Subject='=?UTF-8?B?'.base64_encode($sujet).'?='; 	// Entête : nom du sujet
		$mail->Body=$message; 	

		echo '<h2><code>'.date('H:i:s').'</code>Notification...</h2>';
		echo '<p class="info">Protocole SMTP</p>';	
		echo '<p class="info">Titre : '.$sujet.'</p>';
		echo '<p class="info">Destinataire(s) : '.$listeTo.'</p>';
		
		// En cas d'erreur
		if (!$mail->Send()) {
			$_REQUEST['error'] = $mail->ErrorInfo; 	// Affiche une erreur (pas toujours explicite)
			"<p class='ko'>Erreur d'envoi de mail via</p>";
		} else {
			echo '<p class="ok">Mail envoyé</p>';
		}
		$mail->SmtpClose();
		unset($mail);								// Ferme la connexion SMTP et libère la mémoire
		
	// Envoi d'un mail via la fonction mail de PHP		
	} else {
		
		// Adresses des destinataires
		$to = '';
		foreach ($destinataires_mail as $destinataire_mail) {
			$to .= $destinataire_mail.';';
		}
		
		$headers = "MIME-Version: 1.0" . "\r\n" . "Content-type: text/plain; charset=iso-8859-1" . "\r\n";
		
		echo '<h2><code>'.date('H:i:s').'</code>Notification...</h2>';
		echo '<p class="info">Méthode PHP Mail</p>';
		echo '<p class="info">Titre : '.$sujet.'</p>';
		echo '<p class="info">Destinataire(s) : '.$to.'</p>';
		
		echo mail($to, utf8_decode($sujet), utf8_decode($message), $headers)
			? '<p class="ok">Mail envoyé</p>'
			: "<p class='ko'>Erreur d'envoi de mail</p>";
			
	} // FIN test méthode d'envoi
	
	return true;
	
} // FIN Fonction envoiMail()


?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<meta name="robots" content="noindex, nofollow">
		<title>Backup <?php echo $siteName; ?></title>
		<style type="text/css">
			body { background:#ddd;font-family:Calibri; }
			h1 { border-bottom:1px solid #999; }
			h2, h3, h4 { font-weight:normal; margin-bottom:4px; }
			h2 code { display:inline-block;width:90px;font-size:13px;color:teal;vertical-align:middle;}
			p {padding-left:90px;margin:0;}
			p.ok { color:#090;}
			p.ko { color:#900;}
			p.info { color:teal;}
			.erreur { background:#900;color:#fff;padding:20px;}
			footer { position:fixed;bottom:0;padding:5px;font-size:13px;color:#bbb;}
			#infoDebug { background:teal;color:#fff;padding:20px; }
			#infoDebug h2, #infoDebug ul { margin:0; }
		</style>
	</head>
	<body>
		<h1>Backup v.<?php echo $version ?></h1>
		<?php
		if ($no_mail || $no_zip || $no_sql || $no_ftp) {
			echo '<div id="infoDebug"><h2>Mode Débug</h2>';
			echo '<ul>';
			echo $no_mail ? '<li>Envoi des e-mails désactivé</li>' 	: '';
			echo $no_zip  ? '<li>Génération du ZIP désactivé</li>' 	: '';
			echo $no_sql  ? '<li>DUMP SQL désactivé</li>' 			: '';
			echo $no_ftp  ? '<li>Transfert FTP désactivé</li>' 		: '';
			echo '</ul></div>';
		
		} // FIN affichage mode débug
		?>
		<h2><code><?php echo date('H:i:s'); ?></code>Sauvegarde de la base de données en cours...</h2>
		<footer>&copy; Cédric BOUILLON | INTERSED <?php echo date('Y'); ?></footer>
		<?php
	
		
		/** -------------------------------------------------------
		 * RECUPERATION DES ACCES A LA BASE DE DONNEE
		 *------------------------------------------------------- */
		
		// Si pas de serveur SQL précisé, détection automatique de la solution utilisée (Joomla / Prestashop / GLPI / Wordpress)
		if (empty($mysql_server) || empty($mysql_login) || empty($mysql_bdd) || empty($mysql_passwd)) {
			
			// JOOMLA
			if (file_exists(ROOT.'/configuration.php')) {
				
				echo "<p class='ok'>Détection d'un site Joomla, base de données récupérée.</p>";
				$db_file 		= ROOT.'/configuration.php';
				$mysql_server 	= 'host';
				$mysql_login 	= 'user';
				$mysql_bdd 		= 'db';
				$mysql_passwd 	= 'password';
				
			// PRESTASHOP 1.7	
			} else if (file_exists(ROOT.'/app/config/parameters.php')) {
				
				echo "<p class='ok'>Détection d'un site Prestashop 1.7, base de données récupérée.</p>";
				$db_file		= ROOT.'/app/config/parameters.php';
				$mysql_server 	= 'database_host';
				$mysql_port 	= 'database_port';
				$mysql_login 	= 'database_user';
				$mysql_bdd 		= 'database_name';
				$mysql_passwd 	= 'database_password';
				
			// PRESTASHOP < 1.7	
			} else if (file_exists(ROOT.'/config/settings.inc.php')) {

				echo "<p class='ok'>Détection d'un site Prestashop <= 1.6, base de données récupérée.</p>";
				$db_file 		= ROOT.'/config/settings.inc.php';
				$mysql_server 	= '_DB_SERVER_';
				$mysql_login 	= '_DB_USER_';
				$mysql_bdd 		= '_DB_NAME_';
				$mysql_passwd 	= '_DB_PASSWD_';
				
			// GLPI	
			} else if (file_exists(ROOT.'/config/config_db.php')) {
				
				echo "<p class='ok'>Détection d'un site GLPI, base de données récupérée.</p>";
				$db_file 		= ROOT.'/config/config_db.php';
				$mysql_server 	= 'dbhost';
				$mysql_login 	= 'dbuser';
				$mysql_bdd 		= 'dbdefault';
				$mysql_passwd 	= 'dbpassword';
				
			// WORDPRESS	
			} else if (file_exists(ROOT.'/wp-config.php')) {
				
				echo "<p class='ok'>Détection d'un site Wordpress, base de données récupérée.</p>";
				$db_file 		= ROOT.'/wp-config.php';
				$mysql_server 	= 'DB_HOST';
				$mysql_login 	= 'DB_USER';
				$mysql_bdd 		= 'DB_NAME';
				$mysql_passwd 	= 'DB_PASSWORD';
				
			} // FIN test du CMS
			
		} // FIN récupération des accès BDD en automatique
		
		
		// Récupération des informations de connexion à la BDD dans un fichier
		// (si détecté d'un CMS ou si renseigné dans la configuration)
		
		if (!empty($db_file)) {
			
			// Ouverture du fichier
			$open_db_file = fopen($db_file, 'r');
			
			// Boucle sur les lignes
			while($ligne = fgets($open_db_file)) {
				
				// Nettoyage des chaines : serveur
				preg_match('/[$|\'|"]'.str_replace(']', '\]', str_replace('[', '\[', $mysql_server)).'[ |\t|\']*[=|,][>]{0,1}[ |\t]*[\'|"](.*)[\'|"]/', $ligne, $mysql_server_matches);
				
				// Nettoyage des chaines : port
				if (isset($mysql_port) && !empty($mysql_port)) {
					preg_match('/[$|\'|"]'.str_replace(']', '\]', str_replace('[', '\[', $mysql_port)).'[ |\t|\']*[=|,][>]{0,1}[ |\t]*[\'|"](.*)[\'|"]/', $ligne, $mysql_port_matches);
				}
				
				// Nettoyage des chaines : utilisateur
				preg_match('/[$|\'|"]'.str_replace(']', '\]', str_replace('[', '\[', $mysql_login)).'[ |\t|\']*[=|,][>]{0,1}[ |\t]*[\'|"](.*)[\'|"]/', $ligne, $mysql_login_matches);
				
				// Nettoyage des chaines : nom de la base
				preg_match('/[$|\'|"]'.str_replace(']', '\]', str_replace('[', '\[', $mysql_bdd)).'[ |\t|\']*[=|,][>]{0,1}[ |\t]*[\'|"](.*)[\'|"]/', $ligne, $mysql_bdd_matches);
				
				// Nettoyage des chaines : mot de passe
				preg_match('/[$|\'|"]'.str_replace(']', '\]', str_replace('[', '\[', $mysql_passwd)).'[ |\t|\']*[=|,][>]{0,1}[ |\t]*[\'|"](.*)[\'|"]/', $ligne, $mysql_passwd_matches);
				
				// On intègre les données trouvées dans les variables
				if (isset($mysql_server_matches[1])){ $mysql_server = $mysql_server_matches[1]; }
				if (isset($mysql_port_matches[1])) 	{ $mysql_port 	= $mysql_port_matches[1];	}
				if (isset($mysql_login_matches[1])) { $mysql_login 	= $mysql_login_matches[1];	}
				if (isset($mysql_bdd_matches[1])) 	{ $mysql_bdd 	= $mysql_bdd_matches[1];	}
				if (isset($mysql_passwd_matches[1])){ $mysql_passwd = $mysql_passwd_matches[1];	}
				
			} // FIN boucle sur les lignes du fichier
			
			// Fermeture du fichier
			fclose($open_db_file); 
			
		} // FIN test fichier de configuration spécifié ou détécté
		
		// Si le serveur contient un port, on le récupère
		if (strpos($mysql_server, ':') > 0) {
			
			$mysql_port 	= substr($mysql_server, strpos($mysql_server, ':')+1);
			$mysql_server 	= substr($mysql_server, 0, strpos($mysql_server, ':'));
		} // FIN test serveur:port
		
		// Si on a bien une base de donnée, on fais un DUMP par la fonction SYSTEM de PHP
		if(!empty($mysql_bdd) && !$no_sql) {
			
			$mysql_passwd = str_replace('"', '\"', $mysql_passwd);
			
			$cmd = "mysqldump --host=".$mysql_server." --user=".$mysql_login." --password='".$mysql_passwd."' ".((isset($mysql_port) && !empty($mysql_port))?"--port=".$mysql_port." ":"").$mysql_bdd." | gzip > ".$mysqlSaveDir."/".$mysql_bdd.".sql.gz";
			$cmd = str_replace('../backup/','', $cmd);
			
			system($cmd);
			
		
			
			echo '<p class="ok">Création du DUMP SQL.</p>';

			
		} else {
			echo $no_sql ? '<p class="info">Le DUMP n\'a pas été généré (mode débug).</p>' : '<p class="ko">Échec de création du DUMP !</p>';
		}
		// FIN DUMP SQL
		?>
		
		<h2><code><?php echo date('H:i:s'); ?></code>Sauvegarde des fichiers du site en cours...</h2>
		
		<?php
		
		// Paramétrage des noms de fichiers
		$fileNamePrefix = $siteName.'_'.date('YmdHis');
		$zipfile 		= $zipSaveDir.'/'.$fileNamePrefix.'.zip';
		$filenames 		= array();

		/** -------------------------------------------------------
		 * Fonction récursive de parcours des dossiers
		 *------------------------------------------------------- */
		function browse($dir) {
			
			global $filenames, $dirsNotToSaveArray;
			
		    if ($handle = opendir($dir)) {
				
		        while (false !== ($file = readdir($handle))) {
					
					// Si on fichier est trouvé
		            if ($file != "." && $file != ".." && is_file($dir.'/'.$file)) {
						
						// On l'intègre à l'array des fichiers à sauvegarder
						// si pas dans le répertoire /var/www/iprexdev/saves/
						if (strpos($file, 'iprex_') == false) {
							$filenames[] = $dir.'/'.$file;
						}
						
					// Sinon, si c'est un dossier et qu'il n'est pas exclu	
		            } else if ($file != "." && $file != ".." && is_dir($dir.'/'.$file) && !in_array($dir.'/'.$file, $dirsNotToSaveArray) ) {
						
						// On fais appel à la récursivité de la fonction pour parcourir ce qu'il contient
		                browse($dir.'/'.$file);
		            }
		        }
		        closedir($handle);
		    }
		    return $filenames;
			
		} // FIN fonction récursive
		
		// On appelle la fonction récursive pour le parcours des fichiers
		browse($dirToSave);
		
		/** -------------------------------------------------------
		 * Création de l'archive ZIP
		 *------------------------------------------------------- */
		if (!$no_zip) { // Si pas mode debug
			
			$zip = new ZipArchive();
			
			// Gestion de l'erreur d'ouverture du ZIP
			if ($zip->open($zipfile, ZIPARCHIVE::CREATE) !== true) {
				echo "<h2 class='erreur'>ERREUR lors de l'instanciation de l'objet ZIP &mdash; Arrêt du script !</h2></body></html>";
				exit;
			}
			
			// On boucle sur les fichiers à intégrer dans le ZIP
			foreach ($filenames as $filename) {
				
				// On récupère le nom de fichier
				$tab_filename 		= explode(ROOT, $filename);
				$filename_backup 	= $tab_filename[1];
				
				// On l'ajoute
				$zip->addFile($filename, $filename_backup);
				
			} // FIN boucle sur les fichiers à intégrer au ZIP
			?>
			<p class="ok">Nombre de fichiers : <?php echo $zip->numFiles; ?></p>
			<?php
			// Si on a bien une base de donnée
			if (!empty($mysql_bdd)) {
				
				// On ajoute le DUMP  à l'archive
				$zip->addFile($mysqlSaveDir."/".$mysql_bdd.".sql.gz", $siteName.'/'.$mysql_bdd.".sql.gz");
				
				// On vérifie le poids de la base de données, qu'il soit au moins de 1Ko !
				if (filesize($mysqlSaveDir."/".$mysql_bdd.".sql.gz") < 100) {
					
					echo "<p class='ko'>Base de données <strong>$mysql_bdd</strong> vide ou corrompue !</p>";
					$sujet 		= "Alerte Backup $siteName";
					$message 	= "La base de données $mysql_bdd du site $siteName semble être vide ou corrompue (taille < 1 Ko lors du dernier backup) !";
					envoiMail($sujet, $message);
					
				} // FIN test DUMP < 1Ko
				
				// On lis le fichier de taille du dernier DUMP s'il existe
				if (file_exists($mysqlSaveDir.'/bdd_size_'.$siteName.'.txt')) {
					
					$file_bdd_size 	= fopen($mysqlSaveDir.'/bdd_size_'.$siteName.'.txt', 'r');
					$old_bdd_size 	= fgets($file_bdd_size);
					fclose($file_bdd_size);
					
					// On vérifie la taille de la BDD par rapport au backup précédent.
					// On ajoute 10% de marge d'erreur d'où la multiplication par 0.9 (et qu'il est cohérent)
					if (filesize($mysqlSaveDir."/".$mysql_bdd.".sql.gz") < $old_bdd_size * 0.9 && $old_bdd_size > 1000) {
						
						echo "<p class='ko'>Le poids base de données <strong>$mysql_bdd</strong> est inférieur de plus de 10% par rapport au backup précédent !</p>";
						$sujet 		= "Alerte Backup $siteName";
						$message 	= "Le poids de la base de données $mysql_bdd du site $siteName est inférieur de plus de 10% par rapport au backup précédent (".filesize($mysqlSaveDir."/".$mysql_bdd.".sql.gz")." < ".$old_bdd_size."). Vérifier l'intégrité de la base de données sauvegardées.";
						envoiMail($sujet, $message);	
						
					} // FIN test taille DUMP < 10% du précédent.
					
				// Si le fichier n'existe pas (1er backup)
				} else {
					
					// On envoie un mail pour indiquer le poids de la base de données
					echo "<p class='ok'>Poids de la base de données au premier backup : ".filesize($mysqlSaveDir."/".$mysql_bdd.".sql.gz")." octets.</p>";
					$sujet 		= "Premier backup $siteName";
					$message 	= "Le poids de la base de données $mysql_bdd du site $siteName lors du 1er backup est de ".filesize($mysqlSaveDir."/".$mysql_bdd.".sql.gz")." octets.";
					envoiMail($sujet, $message);
				}
				
				// Insertion du nouveau poids de la base de données dans le fichier texte
				$file_bdd_size = fopen($mysqlSaveDir.'/bdd_size_'.$siteName.'.txt', 'w+');
				fputs($file_bdd_size, filesize($mysqlSaveDir."/".$mysql_bdd.".sql.gz"));
				
			} // FIN test BDD
			
			?>
			
			<h2><code><?php echo date('H:i:s'); ?></code>Création du fichier ZIP en cours...</h2>
			<?php
			$zip->close();

			// Si le fichier est bien sur le serveur
			if (file_exists($zipfile)) {
				
				echo "<p class='ok'>Fichier <strong>".$fileNamePrefix."</strong> créé.</p>";
				
				$sujet 		= "Backup $siteName réussi";
				$message 	= "Backup $siteName réussi le ".date('d/m/Y à H:i:s');
				
			// Sinon (erreur)	
			} else {
				
				echo "<p class='ko'>Le fichier <strong>".$fileNamePrefix."</strong> n'a pas pu être créé !</p>";
				
				$sujet 		= "Backup $siteName échoué";
				$message 	= "Backup $siteName échoué le ".date('d/m/Y à H:i:s');
			}
			
		} // FIN test mode débug

		
		// Connexion FTP
		if(!empty($ftp_server) && !$no_ftp) {
			?>
			<h2><code><?php echo date('H:i:s'); ?></code>Transfert FTP...</h2>
			<?php
			
			$conn_id 		= ftp_connect($ftp_server);
			$login_result 	= ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
		
			// Si réussi et FTP
			if ($conn_id && $login_result) {
			
				// Envoi du ZIP en FTP
				$type = FTP_BINARY;
				
				echo "<p class='ok'>FTP : Connecté à __ $ftp_server __, en tant que __ $ftp_user_name __</p>";
				
				// Transfert
				$upload = ftp_put($conn_id, "/$ftp_dir/$fileNamePrefix.zip", "$zipfile", $type);
				
				// En cas d'erreur
				if (!$upload) {
					
					echo "<p class='ko'>Le transfert FTP a échoué !</p>";
					$sujet 		= "Backup $siteName échoué";
					$message 	= "Backup $siteName échoué le ".date('d/m/Y à H:i:s');
					
				// Sinon, le transfert FTP s'est bien passé	
				} else {
					echo "<p class='info'>Envoi de  \"$fileNamePrefix.zip\" sur \"$ftp_server\"<br/>";
					echo "Double de la sauvegarde disponible sur le serveur FTP.</p>";

					// On supprime le fichier en local
					//system("rm -r $zipfile");
					
					$sujet 		= "Backup $siteName réussi";
					$message 	= "Backup $siteName réussi le ".date('d/m/Y à H:i:s');
				}
				
				ftp_quit($conn_id);
				
			} // FIN FTP 
			
		} // FIN test FTP
		
		// Envoi du mail
		if (isset($sujet) && isset($message)) {
			envoiMail($sujet, $message);
		}
		?>
		<h2><code><?php echo date('H:i:s'); ?></code>Nettoyage des fichiers temporaires...</h2>
		<?php
		// On supprime tous les fichiers à l'intérieur du dossier MySQL
		$files = glob('MySQL/*');
		foreach($files as $file){
			if(is_file($file) && substr($file,0,15) != 'MySQL/bdd_size_') {
				unlink($file);
			}
		}
		
		echo "<p class='ok'>Fichiers de taills et DUMP SQL temporaires purgées.<br/>";
		// FIN suppression des fichiers dans /MySQL
		
		$time_end = microtime(true);
		$execution_time =  number_format(($time_end - $time_start),3);
		?>

		<h2><code><?php echo date('H:i:s'); ?></code>Sauvegarde terminée.</h2>
		<p class="info">Script exécuté en <?php echo $execution_time; ?> secondes.</p>

	</body>
</html>		