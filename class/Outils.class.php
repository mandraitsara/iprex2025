<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Classe outils
------------------------------------------------------*/
class Outils
{
	public static function setAttributs($nom,$objet) {

		if (!is_array($objet->attributs)) { return false; }
		if (!in_array($nom, $objet->attributs)) {
			$objet->attributs[] = $nom;
		}
	}

	public static function getMoisIntListe($courts = false) {
		return
			$courts
				? array(
				1=>'jan',
				2=>'fév',
				3=>'mars',
				4=>'avr',
				5=>'mai',
				6=>'juin',
				7=>'juil',
				8=>'aoôt',
				9=>'sept',
				10=>'oct',
				11=>'nov',
				12=>'déc'
			)
				: array(
				1=>'janvier',
				2=>'février',
				3=>'mars',
				4=>'avril',
				5=>'mai',
				6=>'juin',
				7=>'juillet',
				8=>'août',
				9=>'septembre',
				10=>'octobre',
				11=>'novembre',
				12=>'décembre'
			);
	}

	/*****************************
	Retourne un array des mois
	 ****************************/
	public static function getMoisListe($courts = false) {
		return
			$courts
				? array(
				'01'=>'jan',
				'02'=>'fév',
				'03'=>'mars',
				'04'=>'avr',
				'05'=>'mai',
				'06'=>'juin',
				'07'=>'juil',
				'08'=>'aoôt',
				'09'=>'sept',
				'10'=>'oct',
				'11'=>'nov',
				'12'=>'déc'
			)
				: array(
				'01'=>'janvier',
				'02'=>'février',
				'03'=>'mars',
				'04'=>'avril',
				'05'=>'mai',
				'06'=>'juin',
				'07'=>'juillet',
				'08'=>'août',
				'09'=>'septembre',
				'10'=>'octobre',
				'11'=>'novembre',
				'12'=>'décembre'
			);
	}


	/*****************************
	Retourne un array des jours
	 ****************************/
	public static function getJoursListe($courts = false) {
		return !$courts ?
			array(
				1=>'lundi',
				2=>'mardi',
				3=>'mercredi',
				4=>'jeudi',
				5=>'vendredi',
				6=>'samedi',
				7=>'dimanche'
			) : array(
				1=>'lun',
				2=>'mar',
				3=>'mer',
				4=>'jeu',
				5=>'ven',
				6=>'sam',
				7=>'dim'
			);
	}



	/*******************************
	Retourne un jour SQL
	 ******************************/
	public static function getJourFromSql($dayofweek) {
		$jours =  array(
			'1'=>'dimanche',
			'2'=>'lundi',
			'3'=>'mardi',
			'4'=>'mercredi',
			'5'=>'jeudi',
			'6'=>'vendredi',
			'7'=>'samedi'
		);

		return $jours[$dayofweek];
	}

	public static function getJourSemaine($jour)
	{
		$jours =  array(

			'1'=>'lundi',
			'2'=>'mardi',
			'3'=>'mercredi',
			'4'=>'jeudi',
			'5'=>'vendredi',
			'6'=>'samedi',
			'7'=>'dimanche'
		);

		return $jours[$jour];
	}

	/**********************************
	Retourne la date du jour en clair
	 *********************************/
	public static function getDateVerbose($date = '', $heure = false, $jour = false) {

		if ($date == '') {

			$mois = self::getMoisListe();

			$date = $jour ? ucwords(self::getJourSemaine(date('N'))).' '  : '';

			$date.=  date('d') . ' ' . $mois[date('m')] . ' ' . date('Y');

			if ($heure) { $date .= ' à '.date('H:m'); }

		} else 	{

			$mois_annee = Outils::getMoisListe();

			$annee 	= substr($date,0,4);
			$mois  	= str_pad(substr($date,5,2),2,'0',STR_PAD_LEFT);
			$jours 	= intval(substr($date,8,2));
			$heures = intval(substr($date,11,2));
			$min	= intval(substr($date,14,2));

			$date = $jours.' '.$mois_annee[$mois].' '.$annee;

			if ($heure) { $date .= ' à ' . $heures . ':' . $min; }

		}
		return $date;
	}

	/**********************************
	Envoi un e-mail
	 *********************************/
	public static function envoiMail($destinataires, $from, $titre, $corp, $accuse = 0, $copie_cache = array(), $pieces_jointes = array()) {

		require_once('PHPMailer.php');
		require_once('SMTP.php');
		$mail = new PHPMailer(true);

		if ($_SESSION['usesmtp']) {
			$mail->IsSMTP();
			$mail->Username = $_SESSION['smtp_username'];
			$mail->Password = $_SESSION['smtp_password'];
			$mail->Port		= $_SESSION['smtp_port'];
		}

		try {
			if ($_SESSION['usesmtp']) {
				$mail->Host       = $_SESSION['smtp_server']; 		// SMTP server
				//$mail->SMTPDebug  = 2;
			}

			foreach ($destinataires as $desti) {

				if (trim($desti) != '')	{
					if ($mail->ValidateAddress($desti))	{
						$mail->AddAddress($desti, '');
					}
				}
			}

			$titre = Outils::removeAccents($titre);

			$mail->SetFrom($from);
			$mail->AddReplyTo($from);
			$mail->Subject = $titre;
			$mail->AltBody = 'Pour voir ce message votre client de messagerie doit accepter les mails HTML';
			$mail->MsgHTML($corp);

			if (!empty($copie_cache)) {
				foreach ($copie_cache as $bcc) {
					if (trim($bcc) != '') {
						$mail->addCC($bcc,"");
					}
				}
			}

			if ($accuse == 1) {
				$mail->ConfirmReadingTo=$from;
			}

			if (!empty($pieces_jointes)) {

				foreach ($pieces_jointes as $piece) {
					$mail->AddAttachment($piece);
				}
			}

			$mail->Send();

			return true;

		} catch (phpmailerException $e) {

			//vd($e);
			return false;
		} catch (Exception $e) {
			//vd($e);
			return false;
		}
	}

	/**********************************
	Formatte le contenu du mail
	 *********************************/
	public static function formatContenuMail($contenu, $entete = '', $nePasRepondre = true) {

		$message = '<html>
					<head>
						<title>Intranet Profil Export</title>
						<style type="text/css">
						body { font-family: Calibri, "Trebuchet MS", Verdanna, Arial; }
						p { margin: 0 0 5px 0; }
						b { font-size:1.4em; }
						img.logo { max-width: 300px;}
						</style>
					</head>
					<body>';
		$message.= $entete != '' ? '<h1>'.$entete.'</h1>' : '';
		$message.= $contenu;
		$message.= $nePasRepondre ? '<p style="color:grey;font-size:0.8em;">Ceci est un mail automatique, Merci de ne pas y répondre...</p>' : '';
		$message.= ' </body>
				</html>';

		return $message;

	}
	/**********************************
	Formatte le contenu du mail Client
	 *********************************/
	public static function formatContenuMailClient($contenu, $entete = '', $nePasRepondre = true) {

		$message = '<html>
					<head>
						<title>Profil Export</title>
						<style type="text/css">
						body { font-family: Calibri, "Trebuchet MS", Verdanna, Arial; }
						p { margin: 0 0 5px 0; }
						b { font-size:1.4em; }
						img.logo { max-width: 300px;}
						</style>
					</head>
					<body>';
		$message.= $entete != '' ? '<h1>'.$entete.'</h1>' : '';
		$message.= '<img src="'.__CBO_IMG_URL__.'logo-pe-350.jpg" alt="Profil Export" /><br>';
		$message.= $contenu;
		$message.= $nePasRepondre ? '<p style="color:grey;font-size:0.8em;">Ceci est un mail automatique, Merci de ne pas y repondre...</p>' : '';
		$message.= ' </body>
				</html>';

		return $message;

	}



	/*****************************************
	Coupe un texte proprement a la fin du mot
	 ****************************************/
	public static function cleanCut($texte, $longueur) {
		$suite = '...';
		if(strlen($texte) <= $longueur) {
			return $texte;
		}
		$txt = substr($texte,0,$longueur-strlen($suite)+1);
		return substr($txt,0,strrpos($txt,' ')).$suite;
	}


	/************************
	Vérifie une adresse email
	 ************************/
	public static function verifMail($email) {

		if (preg_match("#^[a-z0-9._-]+@[a-z0-9._-]{2,}\.[a-z]{2,7}$#", $email)) {
			return true;
		} else {
			return false;
		}
	}


	/************************
	Vérifie une adresse URL
	 ************************/
	public static function verifUrl($url) {

		if (preg_match("((http:\/\/|https:\/\/)(www.)?(([a-zA-Z0-9-]){2,}\.){1,4}([a-zA-Z]){2,6}(\/([a-zA-Z-_\/\.0-9#:?=&;,]*)?)?)", $url)) {
			return true;
		} else {
			return false;
		}
	}


	/************************
	Génère un mot de passe
	 ************************/
	public static function genereMotDePasse($nb_caracteres = 8, $majuscules = true, $chiffres = true, $speciaux = false) {

		$mdp 		= '';
		$caracteres = 'abcdefghjkmnpqrstwxyz';
		$caracteres.= $majuscules ? 'ABCDEFGHJKLMNPQRSTWXYZ' : '';
		$caracteres.= $chiffres ? '23456789' : '';
		$caracteres.= $speciaux ? '$^ù!:;,?./%µ£+-~#{[|`^@]}=)²' : '';

		while (strlen($mdp) < $nb_caracteres) {
			$pioche = rand(0,strlen($caracteres));
			$mdp.=substr($caracteres, $pioche, 1);
		}
		return $mdp;
	}

	/************************
	Génère un code numérique
	 ************************/
	public static function genereCode($nb_caracteres = 4) {

		$mdp 		= '';
		$caracteres = '0123456798';

		while (strlen($mdp) < $nb_caracteres) {
			$pioche = rand(0,strlen($caracteres));
			$mdp.=substr($caracteres, $pioche, 1);
		}
		return $mdp;
	}


	/************************
	Redimentionne une image
	 ************************/
	public static function redimentionneImage($chemin, $extension, $w_s, $h_s, $w = 200, $h = 100) {

		switch ( $extension )
		{
			case "jpg":
			case "peg": //pour le cas où l'extension est "jpeg"
				$img_src = imagecreatefromjpeg( $chemin );
				break;

			case "gif":
				$img_src = imagecreatefromgif( $chemin );
				break;

			case "png":
				$img_src = imagecreatefrompng( $chemin );
				break;
		}

		// Calcul des nouvelles dimensions en gardant les proportions
		if ($w < $w_s || $h < $h_s) {
			if ($w  && ($w_s < $h_s)) {
				$w = ($h / $h_s) * $w_s;
			} else {
				$h = ($w / $w_s) * $h_s;
			}
		}

		//  w_s : largeur de l'image d'origine
		//  h_s : hauteur de l'image d'origine
		//  w	: largeur max à appliquer
		//  h	: hauteur max à appliquer

		$img_dst = imagecreatetruecolor( $w, $h );
		imagecopyresampled($img_dst,$img_src,0,0,0,0,$w,$h,$w_s,$h_s);

		return imagejpeg( $img_dst, $chemin );
	}


	/**************************
	Retourne une durée verbose
	 **************************/
	public static function getEcartJours($date1, $date2, $verbose = false) {

		$duree = (strtotime($date2) - strtotime($date1));
		$jours =  round($duree / 86400,0);
		$nb_jours = $jours < 0 ? $jours * -1 : $jours;
		$pluriel = $nb_jours > 1 ? 's' : '';

		return $verbose ? $nb_jours. ' jour'.$pluriel : (int)$nb_jours;

	}


	/******************************
	Retourne une date au format US
	 *****************************/
	public static function getDateUs($date_fr) {

		$regex_date = "#^(((((0[1-9])|(1\d)|(2[0-8]))\/((0[1-9])|(1[0-2])))|((31\/((0[13578])|(1[02])))|((29|30)\/((0[1,3-9])|(1[0-2])))))\/((20[0-9][0-9])|(19[0-9][0-9])))|((29\/02\/(19|20)(([02468][048])|([13579][26]))))$#";

		if (preg_match($regex_date, $date_fr)) {
			$details = explode('/', $date_fr);
			return  $details[2] . '-' . $details[1] . '-' . $details[0];
		} else {
			return date('Y-m-d');
		}
	}

	/*****************************************
	Retourne verbose date depuis format Y-d-m
	 ****************************************/
	static function getDate_only_verbose($date, $showAn = false, $showJourSem = true) {

		$dateObj 	= new DateTime($date);
		$jour 		= $dateObj->format('j');
		$moisListe 	= Outils::getMoisListe();
		$mois 		= strtolower($moisListe[$dateObj->format('m')]);
		$an 		= $dateObj->format('Y');
		$joursListe = Outils::getJoursListe();
		$jourSem 	= $showJourSem ? strtolower($joursListe[$dateObj->format('N')]) : '';
		$retour 	= $showAn ? ucfirst($jourSem) . ' ' . $jour . ' ' .$mois . ' ' . $an : ucfirst($jourSem) . ' ' . $jour . ' ' .$mois;

		return trim($retour);
	}


	/***********************************************
	Retourne verbose date depuis format Y-d-m H:i:s
	 **********************************************/
	static function getDate_verbose($dateTime, $showJourSem = true, $separateur = ', ', $showAnnee = true, $moisCourts = false) {
		$dateObj 	= new DateTime($dateTime);
		$jour 		= $dateObj->format('j');
		$moisListe 	= Outils::getMoisListe($moisCourts);
		$mois 		= strtolower($moisListe[$dateObj->format('m')]);
		$an 		= $showAnnee ? $dateObj->format('Y') : '';
		$heures 	= $dateObj->format('H');
		$min 		= $dateObj->format('i');
		$joursListe = Outils::getJoursListe();
		$jourSem 	= strtolower($joursListe[$dateObj->format('N')]);
		if ($showJourSem) {
			return $jourSem . ' ' . $jour . ' ' .$mois . ' ' . $an . ' à ' . $heures . ':' . $min;
		} else {
			return $jour . ' ' .$mois . ' ' . $an . $separateur . $heures . ':' . $min;
		}
	}

	/***********************************************
	Retourne heure minute date depuis format Y-d-m H:i:s
	 **********************************************/
	static function getHeureOnly($dateTime) {

		$dateObj 	= new DateTime($dateTime);
		$heures 	= $dateObj->format('H');
		$min 		= $dateObj->format('i');
		return $heures . ':' . $min;
	}


	/*************
	BR en NL
	 ************/
	static function br2nl($texte) {
		return preg_replace("/\<br\s*\/?\>/i", "\n", $texte);
	}

	static function getAlertesTypes() {

		return [
			'primary'     => 'Primaire',
			'secondary'   => 'Notification',
			'success'     => 'Opération réussie',
			'danger'      => 'Erreur',
			'warning'     => 'Alerte',
			'info'        => 'Information',
			'light'       => 'Notice',
			'dark'        => 'Développement'
		];

	}


	/***********************************************************************
	Retourne la date au format fr si renseignée et différente de 0000-00-00
	 **********************************************************************/
	static function dateSqlToFr($chaine, $defaut = "") {

		if ($chaine == '' || $chaine == '0000-00-00') { return $defaut; }

		return  date("d/m/Y", strtotime($chaine));

	} // FIN méthode


	/************************************************************************
	Retourne la date au format SQL si renseignée et différente de 0000-00-00
	 ***********************************************************************/
	static function dateFrToSql($chaine, $defaut = "") {

		if ($chaine == '' || $chaine == '00/00/0000') { return $defaut; }

		$dateArray = explode('/',$chaine);
		if (!isset($dateArray[2])) { return $defaut; }

		return $dateArray[2].'-'.$dateArray[1].'-'.$dateArray[0];

	} // FIN méthode


	/***************
	Upload fichier
	 **************/
	public static function uploadFile($file, $path, $name, $image_x = 0, $image_y = 0, $image_ratio = true, $auto_rename = false, $extension = 'png'){

		$handle = new upload($file);

		if ($handle->uploaded) 	{

			$handle->file_new_name_body   = $name;
			$handle->file_overwrite = true;
			$handle->file_new_name_ext = $extension;

			if($image_x != 0 or $image_y != 0) {
				$handle->image_resize = true;
				if ($image_x!=0)	{
					if ($image_x<=$handle->image_src_x)	{
						$handle->image_x = $image_x;
					} else {
						$handle->image_x = $handle->image_src_x;
					}
				}
				if ($image_y != 0) {
					if ($image_y<=$handle->image_src_y) {
						$handle->image_y = $image_y;
					} else {
						$handle->image_y = $handle->image_src_y;
					}
				}
				$handle->image_ratio = $image_ratio;
			}
			if ($auto_rename == false) {
				$handle->file_auto_rename = false;
			}
			$handle->Process($path);

			if ($handle->processed)	{
				return true;
			} else {
				return $handle->error;
			}
		} else {
			return $handle->error;
		}
	}

	/*************************************************************
	Retourne l'OS, le navigateur et la version depuis l'User-Agent
	 ************************************************************/
	public static  function getInfosClient($u_agent = null) {

		$os			= null;
		$nav		= null;
		$version	= null;
		$vide		= array( 'os' => $os, 'nav' => $nav, 'version' => $version);

		if (is_null($u_agent)) {

			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				$u_agent = $_SERVER['HTTP_USER_AGENT'];
			} else {
				return $vide;
			}

			if (!$u_agent) { return $vide; }

			if (preg_match('/\((.*?)\)/im', $u_agent, $parent_matches)) {

				preg_match_all('/(?P<platform>BB\d+;|Android|CrOS|iPhone|iPad|Linux|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|Nintendo\ (WiiU?|3DS)|Xbox(\ One)?)(?:\ [^;]*)?(?:;|$)/imx', $parent_matches[1], $result, PREG_PATTERN_ORDER);

				$priorite = array( 'Android', 'Xbox One', 'Xbox' );
				$result['platform'] = array_unique($result['platform']);

				if (count($result['platform']) > 1 ) {

					if ($keys = array_intersect($priorite, $result['platform']) ) {
						$os = reset($keys);
					} else {
						$os = $result['platform'][0];
					}

				} elseif (isset($result['platform'][0]) ) {
					$os = $result['platform'][0];
				}
			}

			// Système d'exploitation
			$os_array = array (
				'/windows nt 6.3/i'     =>  'Windows 8.1',
				'/windows nt 6.2/i'     =>  'Windows 8',
				'/windows nt 6.1/i'     =>  'Windows 7',
				'/windows nt 6.0/i'     =>  'Windows Vista',
				'/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
				'/windows nt 5.1/i'     =>  'Windows XP',
				'/windows xp/i'         =>  'Windows XP',
				'/windows nt 5.0/i'     =>  'Windows 2000',
				'/windows me/i'         =>  'Windows ME',
				'/win98/i'              =>  'Windows 98',
				'/win95/i'              =>  'Windows 95',
				'/win16/i'              =>  'Windows 3.11',
				'/macintosh|mac os x/i' =>  'Mac OS X',
				'/mac_powerpc/i'        =>  'Mac OS 9',
				'/linux/i'              =>  'Linux',
				'/ubuntu/i'             =>  'Ubuntu',
				'/iphone/i'             =>  'iPhone',
				'/ipod/i'               =>  'iPod',
				'/ipad/i'               =>  'iPad',
				'/android/i'            =>  'Android',
				'/blackberry/i'         =>  'BlackBerry',
				'/webos/i'              =>  'Mobile'
			);

			foreach ($os_array as $regex => $value) {
				if (preg_match($regex, $u_agent)) {
					$os = $value;
				}
			}

			// On affine pour les OS particuliers
			if ($os == 'linux-gnu' ) { $os = 'Linux'; }
			elseif ($os == 'CrOS' )  { $os = 'Chrome OS'; }

			preg_match_all('%(?P<browser>Camino|Kindle(\ Fire\ Build)?|Firefox|Iceweasel|Safari|MSIE|Trident/.*rv|AppleWebKit|Chrome|IEMobile|Opera|OPR|Silk|Lynx|Midori|Version|Wget|curl|NintendoBrowser|PLAYSTATION\ (\d|Vita)+)(?:\)?;?)(?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix',$u_agent, $result, PREG_PATTERN_ORDER);

			if (!isset($result['browser'][0]) || !isset($result['browser'][0])) { return $vide;}

			$nav	 = $result['browser'][0];
			$version = $result['version'][0];

			$find = function($search, &$key) use ($result) {

				$xkey = array_search(strtolower($search), array_map('strtolower', $result['browser']));
				if ($xkey !== false) {
					$key = $xkey;
					return true;
				}
				return false;
			};

			$key = 0;
			if ($nav == 'Iceweasel') {
				$nav = 'Firefox';
			} elseif ($find('Playstation Vita', $key)) {
				$os = 'PlayStation Vita';
				$nav = 'Browser';
			} elseif ($find('Kindle Fire Build', $key) || $find('Silk', $key)) {
				$nav = $result['browser'][$key] == 'Silk' ? 'Silk' : 'Kindle';
				$os = 'Kindle Fire';

				if (!($version = $result['version'][$key]) || !is_numeric($version[0])) {
					$version = $result['version'][array_search('Version', $result['browser'])];
				}

			} elseif ($find('NintendoBrowser', $key) || $os == 'Nintendo 3DS') {
				$nav = 'NintendoBrowser';
				$version = $result['browser'][$key];
			} elseif ($find('Kindle', $key)) {
				$nav = $result['browser'][$key];
				$os = 'Kindle';
				$version = $result['version'][$key];
			} elseif ($find('OPR', $key)) {
				$nav = 'Opera Next';
				$version = $result['version'][$key];
			} elseif ($find('Opera', $key) ) {
				$nav = 'Opera';
				$find('Version', $key);
				$version = $result['version'][$key];
			} elseif ($find('Midori', $key) ) {
				$nav = 'Midori';
				$version = $result['version'][$key];
			} elseif ($nav == 'MSIE' || strpos($nav, 'Trident') !== false) {
				if ($find('IEMobile', $key)) {
					$nav = 'IEMobile';
				} else {
					$nav = 'MSIE';
					$key = 0;
				}
				$version = $result['version'][$key];
			} elseif ($find('Chrome', $key)) {
				$nav = 'Chrome';
				$version = $result['version'][$key];
			} elseif ($find('Edge', $key)) {
				$nav = 'Edge';
				$version = $result['version'][$key];
			} elseif ($nav == 'AppleWebKit' ) {
				if (($os == 'Android' && !($key = 0))) {
					$nav = 'Android Browser';
				} elseif (strpos($os, 'BB') === 0 ) {
					$nav = 'BlackBerry Browser';
					$os = 'BlackBerry';
				} elseif ($os == 'BlackBerry' || $os == 'PlayBook') {
					$nav = 'BlackBerry Browser';
				} elseif ($find('Safari', $key) ) {
					$nav = 'Safari';
				}
				$find('Version', $key);
				$version = $result['version'][$key];
			} elseif ($key = preg_grep('/playstation \d/i', array_map('strtolower', $result['browser']))) {
				$key = reset($key);
				$os = 'PlayStation ' . preg_replace('/[^\d]/i', '', $key);
				$nav = 'NetFront';
			}

			return array('os' => $os, 'nav' => $nav, 'version' => $version);
		}
	}


	/*************************************************************
	Retourne une chaine sans accents
	 ************************************************************/
	public static function removeAccents($texte, $remove_allcharspe = false, $replace_spaces = false, $charset='utf-8') {
		$texte = htmlentities($texte, ENT_NOQUOTES, $charset);

		$texte = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $texte);
		$texte = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $texte); // pour les ligatures e.g. '&oelig;'
		$texte = preg_replace('#&[^;]+;#', '', $texte); // supprime les autres caractères

		if ($replace_spaces) {
			$texte = str_replace(' ', '-', $texte);
		}

		if ($remove_allcharspe) {
			$texte = preg_replace('/[^A-Za-z0-9\-]/', '', $texte);
		}

		return $texte;
	}


	/*************************************************************
	Retourne un nombre d'octet en clair
	 ************************************************************/
	static function getNiceFileSize($bytes, $binaryPrefix=true) {
		if ($binaryPrefix) {
			$unit=array('O','Ko','Mo','Go','To','Po');
			if ($bytes==0) return '0 ' . $unit[0];
			return @round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2) .' '. (isset($unit[$i]) ? $unit[$i] : 'B');
		} else {
			$unit=array('O','KO','MO','GO','TO','PO');
			if ($bytes==0) return '0 ' . $unit[0];
			return @round($bytes/pow(1000,($i=floor(log($bytes,1000)))),2) .' '. (isset($unit[$i]) ? $unit[$i] : 'B');
		}
	}

	/*************************************************************
	Vérifie la validité d'une date au format SQL (après 2000)
	 ************************************************************/
	public static function verifDateSql($dateSql) {

		$regexDateSql =  '#^([2][0-9][0-9]{2}-([0][1-9]|[1][0-2])-([1-2][0-9]|0[1-9]|[3][0-1]))$#';
		return preg_match($regexDateSql, $dateSql);

	}

	/*************************************************************
	Retourne le numéro de jour dans l'année depuis une date (FR ou SQL)
	 ************************************************************/
	public static function getJourAnByDate($date) {

		if (!self::verifDateSql($date)) {
			$date = self::dateFrToSql($date);
		}
		return sprintf('%03d',date('z', strtotime($date)) + 1);

	}

	public static function pdoDebugStrParams($stmt) {
		ob_start();
		$stmt->debugDumpParams();
		$r = ob_get_contents();
		ob_end_clean();
		return htmlspecialchars($r);
	}

	public static function ecartDatesHeures($date1, $date2) {
		$datetime1 = new DateTime($date1);
		$datetime2 = new DateTime($date2);
		$interval = $datetime1->diff($datetime2);
		$jours   = $interval->format('%d');
		$hours   = $interval->format('%h');
		$minutes = $interval->format('%i');
		$retour = '';
		$hours = (int)$hours + (int)$jours * 24;
		$retour.= (int)$hours . ' h ' . sprintf('%02d',$minutes) . "'";

		return $retour;
	}

	// Retourne un nom avec l'initale du prénom à la place du prénom
	public static function initialePrenom($nom_prenom) {

		$array_nom = explode(' ', $nom_prenom);

		// Si on a pas deux mots, on retourne tel quel
		if (count($array_nom) < 2) { return $nom_prenom; }

		$initiale = strtoupper(substr($nom_prenom, 0, 1));
		$array_nom[0] = $initiale . '.';
		for ($i = 1; $i < count($array_nom); $i++) {
			$mot = $array_nom[$i];
			$array_nom[$i] = ucfirst(strtolower($mot));
		}

		return implode(' ', $array_nom);

	}

	// Retourne heure et minutes depuis un datetime
	public static function getHeureMinutesFromDateTime($dateTimeSql) {

		$date_time_obj = new DateTime($dateTimeSql);
		return $date_time_obj->format('H:i');

	}


	// Retourne une couleur hexadécimale unique générée depuis une variable texte/int
	public static function genereHexaCouleur($n) {
		$n = crc32($n);
		$n &= 0xFFFFFF / 1.5;
		$hexa = "#".substr("000000".dechex($n),-6);
		return $hexa;
	}

	// Retourne si une couleur hexadécimale est considérée comme claire (true) ou sombre (false)
	public static function isCouleurHexaClaire($hexRGB, $curseur = 350) {
		return hexdec(substr($hexRGB,0,2))+hexdec(substr($hexRGB,2,2))+hexdec(substr($hexRGB,4,2)) > $curseur;
	}

	// Var_dump en PRE
	public static function vd($variable) {

		echo '<br><pre>';
		var_dump($variable);
		echo '</pre>';

	}

	// Retourne une date SQL au format AAAMMJJ
	public static function dateSqlToAaaMmJj($dateSql) {

		$date = new DateTime($dateSql);
		return $date->format('Ymd');

	} // FIN méthode


	// Envoie un lot à Bizerba
	public static function envoiLotBizerba($lot, $abattoir, $decale = false) {

		// Déconnexion car pas d'envoi vers bizerba possible suite au piratge du 15/11/22
		// PPL 221206
		return;

		// Envoi du lot vers Bizerba : création du fichier (séparateur : #)
		if (!$lot instanceof Lot || !$abattoir instanceof Abattoir) { return false; }

		// On envoie le quantième du jour si le lot n'est pas abbats et que c'est configuré pour...
		$numeroLot = $lot->getComposition() != 2 ? $lot->getNumlot(). Outils::getJourAnByDate(date('Y-m-d')) : $lot->getNumlot();

		$dateFichier = $decale ? date('YmdHis', strtotime('+1 sec')) : date('YmdHis');
		$nom_fichier = 'LOT' . $dateFichier . '.TXT';
		$chemin = __CBO_ROOT_PATH__ . '/bizerba/';

		$separateur = '#';
		$gtin = '00000000000000'; // Clef unique, en complément du numéro de lot
		$numargPe = '38085003';       // Numéro d'agrément de Profil Export

		$bizlot = 'LOT' . $separateur;                                                                                          //  1. Type LOT
		$bizlot .= 'C' . $separateur;                                                                                           //  2. Création
		$bizlot .= $gtin . $separateur;                                                                                         //  3. GTIN
		$bizlot .= $numeroLot . $separateur;                                                                             		//  4. Numéro de lot
		$bizlot .= '01' . $separateur;                                                                                          //  5. Numéro de famille
		$bizlot .= '01' . $separateur;                                                                                          //  6. Numéro de rayon
		$bizlot .= '' . $separateur;                                                                                            //  7. Unité de mesure du lot
		$bizlot .= '000' . $separateur;                                                                                         //  8. Quantité entrée LS
		$bizlot .= '1.0000' . $separateur;                                                                                      //  9. Quantité entrée TRAD
		$bizlot .= $lot->getDate_reception() != '' ? Outils::dateSqlToAaaMmJj($lot->getDate_reception()) : date('Ymd');  // 10. Date de récéption, ou date de création si non définie
		$bizlot .= $separateur . '' . $separateur;                                                                              // 11. Date ou nombre de jours avant activation
		$bizlot .= '' . $separateur;                                                                                            // 12. Date ou nombre de jours avant clôture
		$bizlot .= '' . $separateur;                                                                                            // 13. Date ou nombre de jours avant archive
		$bizlot .= '' . $separateur;                                                                                            // 14. Prix d’achat au kg
		$bizlot .= '' . $separateur;                                                                                            // 15. Commentaire
		$bizlot .= '011' . $separateur;                                                                                         // 16. Pays d'origine
		$bizlot .= '011' . $separateur;                                                                                         // 17. Pays de naissance
		$bizlot .= '011' . $separateur;                                                                                         // 18. Pays d'élevage
		$bizlot .= '' . $separateur;                                                                                            // 19. Numéro d'éleveur
		$bizlot .= trim($abattoir->getNumagr()) . $separateur;                                                                  // 20. Numéro d’agrément de l’abattoir
		$bizlot .= '011' . $separateur;                                                                                         // 21. Pays d'abattage
		$bizlot .= Outils::dateSqlToAaaMmJj($lot->getDate_abattage()) . $separateur;                                            // 22. Date d'abattage
		$bizlot .= $numargPe . $separateur;                                                                                     // 23. Numéro de l’atelier de découpe
		$bizlot .= '250' . $separateur;                                                                                         // 24. Pays de découpe (250 = France)
		$bizlot .= '5' . $separateur;                                                                                           // 25. Espèce de viande (5 = Cheval)
		$bizlot .= '99' . $separateur;                                                                                          // 26. Catégorie de la bête


		$bizfile = fopen($chemin . $nom_fichier, "w");
		fputs($bizfile, $bizlot);
		fclose($bizfile);

		// On teste si le fichier a bien été créé et qu'il n'est pas vide
		return file_exists($chemin . $nom_fichier) && filesize($chemin . $nom_fichier) > 1;

	} // FIN méthode


	// Retourne les codes de tva
	public static function getTvas() {

		return [
			'red' => [
				'id' => 2,
				'nom' => 'Taux réduit'
			],
			'int' => [
				'id' => 1,
				'nom' => 'Taux intermédiaire'
			],
			'std' => [
				'id' => 0,
				'nom' => 'Taux standard'
			],
		];

	} // FIN méthode



	public static function decodeCharAjac($chaine) {

		$chaine = str_replace('{{P}}', '+', $chaine);
		$chaine = str_replace('{{E}}', '&', $chaine);
		$chaine = str_replace('{{D}}', '"', $chaine);
		$chaine = str_replace('{{S}}', "'", $chaine);
		return $chaine;
	}

	/** Save Log (auto)
	 * @info Paramètre : *string* Requête SQL
	 */
	public static function saveLog($query, $separateur = ';') {
		$chemin = __CBO_LOGSQL_PATH__.date('Y_m');
		if (!file_exists($chemin)) { mkdir($chemin, 0775, true); }

		$fichier = date('Ymd').'.log';
		$fichier = $chemin.'/'.$fichier;
		$handle = fopen($fichier, "a");
		$log = date('Y-m-d H:i:s').$separateur.$query."\n";
		fwrite($handle, $log);
		fclose($handle);

	} // FIN savelog

	/** Trouver sous-chaine
	 * @info Paramètre : *string* chaine initiale
	 * @info Paramètre : *string* chaine préfixe
	 * @info Paramètre : *string* chaine suffixe
	 */
	public static function trouverSousChaine($string, $start, $end){
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
	} // FIN trouver sous-chaine

	public static function utf8_encode_array_multi($array) {
		foreach ($array as $k => $val) {
			if (!is_array($val)) {
				$array[$k] = utf8_encode($val);
			} else {
				$array[$k] = Outils::utf8_encode_array_multi($val);
			}
		}
		return $array;
	}

} // FIN classe