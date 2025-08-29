<?php
/**
------------------------------------------------------------------------
CLASSE de l'API de liaison vers l'application mobile
Copyright (C) 2018 Intersed

http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2018 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */

class AppAPI
{
	const version = '1.0';

	protected	$db;

	public function __construct($db) {
		$this->setDb($db);
	}

	public function incrementLigne() {
		$this->ligne = $this->ligne + 1;
	}

	//##### SETTERS #####
	public function setDb(PDO $db) {
		$this->db = $db;
	}


	public function getLigne() {
		return '<span class="lignenb">' . sprintf("%02d", $this->ligne) . '</span> ';
	}


	/** --------------------------------------------------------------------
	 * METHODE : Retourne la documentation README.md en HTML (parsedown)
	-------------------------------------------------------------------- */
	public function showDoc() {


		require_once ('doc/Parsedown.php');
		require_once ('doc/ParsedownExtra.php');
		$Extra  = new ParsedownExtra();
		echo '<!doctype html><head><title>INTERSES App API</title>
        <link href=\'https://fonts.googleapis.com/css?family=Roboto|Roboto+Mono|Ubuntu|Fira+Mono&subset=latin-ext\' rel=\'stylesheet\' type=\'text/css\'>
        <link href="doc/assets/reset.css" rel="stylesheet" type="text/css" />
        <link href="doc/assets/main.css" rel="stylesheet" type="text/css" />
        <link href="doc/assets/prism.css" rel="stylesheet" type="text/css" />
        <link href="doc/assets/style.css" rel="stylesheet" type="text/css" />
        <script src="doc/assets/prism.js" type="text/javascript"></script>
        <meta name="robots" content="noindex, nofollow">
		</head><body><div class="output">';
		echo $Extra->text(file_get_contents('doc/README.md'));
		echo '</div></body></html>';

	} // FIN méthode

	/** --------------------------------------------------------------------
	 * METHODE : Nettoie une chaine
	-------------------------------------------------------------------- */
	public function cleanString($texte, $sans_accents = false) {

		$utf8 = $sans_accents
			? array('/[áàâãªä]/u' => 'a', '/[ÁÀÂÃÄ]/u' => 'A', '/[ÍÌÎÏ]/u' => 'I', '/[íìîï]/u' => 'i', '/[éèêë]/u' => 'e', '/[ÉÈÊË]/u' => 'E', '/[óòôõºö]/u' => 'o', '/[ÓÒÔÕÖ]/u' => 'O', '/[úùûü]/u' => 'u', '/[ÚÙÛÜ]/u' => 'U', '/ç/' => 'c',
				'/Ç/' => 'C', '/ñ/' => 'n', '/Ñ/' => 'N', '/–/' => '-', '/[’‘‹›‚]/u' => ' ', 	'/[“”«»„]/u' => ' ', '/ /' => ' ', )
			: array( '/–/' => '-', '/[’‘‹›‚]/u' => ' ', '/[“”«»„]/u' => ' ', '/ /' => ' ');

		return strip_tags(preg_replace(array_keys($utf8), array_values($utf8), $texte));

	} // FIN méthode

	/** --------------------------------------------------------------------
	 * METHODE : Retourne le "mode"
	-------------------------------------------------------------------- */
	public function getMode($requetes) {

		$mode = isset($requetes['mode']) ? $requetes['mode'] : '';

		if ($mode == 'api.php') { $mode = ''; }

		if (!isset($requetes['install'])) {
			if (isset($requetes['install'])) {
				$mode = '';
			} else if (isset($requetes['getlotsapi'])) {
				$mode = 'getlotsapi';
			}

		} // FIN racourcis action

		return $mode;

	} // FIN méthode

	/** --------------------------------------------------------------------
	 * METHODE : Retourne le HTML de début de page en mode Debug
	-------------------------------------------------------------------- */
	public function debugHeader() {

		global $mode_debug;

		if (!$mode_debug) { return ''; }

		$html = '<!doctype html>
				<head>
					<title>iPREX API</title>
					<link rel="stylesheet" type="text/css" href="doc/assets/style.css">
				</head>
				<body class="debug">
					<header>
						<h1>API de liaison iPrex/Application</h1>
						<h2>Mode DEBUG</h2>
					</header>';
		return $html;

	} // FIN méthode

	/** --------------------------------------------------------------------
	 * METHODE : Retourne le HTML de fin de page en mode Debug
	-------------------------------------------------------------------- */
	public function debugFooter() {

		global $mode_debug;

		if (!$mode_debug) { return ''; }

		$html = 		'<footer></footer>
					</body>
				</html>';
		return $html;

	} // FIN méthode

	/** --------------------------------------------------------------------
	 * METHODE : Retourne une notification d'état en mode debug
	-------------------------------------------------------------------- */
	public function showDebug($res, $vrai, $faux, $complement = null, $exit = false) {

		global $mode_debug, $mode;

		if (!$mode_debug) { return false; }

		$writeMode = '<span class="mode">['.$mode.']</span>';

		echo boolval($res)
			? '<p class="info">'.$this->getLigne().$writeMode.$vrai.'.</p>'
			: '<p class="error">'.$this->getLigne().$writeMode.$faux.' !</p>';

		if ($complement !== null) {
			echo '<pre>';if (is_array($complement)) { print_r($complement); } else { var_dump($complement); };echo '</pre>';
		}

		if ($exit) {
			echo $this->debugFooter();
			exit;
		}

		$this->incrementLigne();
		return true;

	} // FIN méthode


	/** --------------------------------------------------------------------
	 * METHODE : Crée la table des Token en BDD
	-------------------------------------------------------------------- */
	public function install_tokens() {

		$query = 'CREATE TABLE `pe_apitokens` (
				  `id_lot` int(11) NOT NULL COMMENT "ID du lot pour lequel on attend des photos",
				  `id_vue` tinyint(1) NOT NULL DEFAULT "0" COMMENT "ID de la vue appelante",
				  `token` varchar(255) NOT NULL COMMENT "Token généré",
				  `expire` datetime NOT NULL COMMENT "Expiration du token"
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;';

		$res = $this->db->query($query);

		$this->showDebug($res, 'Table "glpi_apptokens" créee ou déjà existante', 'Erreur lors de la creation de la table <em>glpi_apptokens</em>');

		return $res;

	} // FIN méthode


	/** --------------------------------------------------------------------
	 * METHODE : Enregistre un Token
	-------------------------------------------------------------------- */
	public function saveToken() {

		$token = $this->genereToken();

		$query  = 'INSERT INTO `pe_apitokens` (`token`, `expire`) 
				VALUES ("'.$token.'", "'.$this->getDateExpire().'")';

		if (!$this->db->query($query)) {
			return false;
		}

		return $token;
	}

	/** ----------------------------------------------------------------------
	 * METHODE : Initialise un nouveau token pour la prise de photos d'un lot
	---------------------------------------------------------------------- */
	public function initTokenLot(Lot $lot, $id_vue = 0) {

		// Pour éviter tout duplicate avec un token invalide, on purge les tokens du lot
		$query_del = 'DELETE FROM `pe_apitokens` WHERE `id_lot` = ' . $lot->getId();
		$query = $this->db->prepare($query_del);
		$query->execute();

		$token = $this->genereToken();

		$query  = 'INSERT INTO `pe_apitokens` (`id_lot`, `id_vue`, `token`, `expire`) 
				VALUES ('.$lot->getId().', '.intval($id_vue).', "'.$token.'", "'.$this->getDateExpire().'")';

		if (!$this->db->query($query)) {
			return false;
		}

		return $token;

	}

	/** --------------------------------------------------------------------
	 * METHODE : Supprime un Token
	-------------------------------------------------------------------- */
	public function delToken(array $params) {


		$token  = isset($params['token']) 	? $params['token'] 	: false;
		$date  	= isset($params['date']) 	? $params['date'] 	: false;
		$purge 	= isset($params['purge']);

		$query  	= 'DELETE FROM `pe_apitokens` WHERE 1 ';
		$checklen 	= strlen($query);

		$query.= !$purge && $token 	? 'AND `token` LIKE "%'.$token.'%" ' 	: '';
		$query.= !$purge && $date 	? 'AND `expire` > "'.$date.' 00:00:00" AND `expire` < "'.$date.' 23:59:59" ' : '';
		$query.= $purge				? 'AND `expire` < NOW() ' : '';

		if (strlen($query) == $checklen) { return false; }

		return $this->db->query($query);
	}

	/** --------------------------------------------------------------------
	 * METHODE PRIVEE : Génère un Token
	-------------------------------------------------------------------- */
	private function genereToken() {

		global $nbCar_token;

		$sel 		= base64_encode($this->getVraiIp()).'$';
		$sel_len 	= strlen($sel);

		$poivre 	= str_replace('.','',microtime(true));
		$poivre_len = strlen($poivre);

		$longueur 	= $nbCar_token - $sel_len - $poivre_len;

		$string = "";
		$chaine = "a0b1c2d3e4f5g6h7i8j9klmnpqrstuvwxy123456789$";

		srand((double)microtime()*1000000);

		for ($i=0; $i < $longueur; $i++) {
			$string .= $chaine[rand()%strlen($chaine)];
		}

		return $sel.$string.$poivre;

	} // FIN méthode


	/** --------------------------------------------------------------------
	 * METHODE PRIVEE : Retourne la date d'expiration
	-------------------------------------------------------------------- */
	private function getDateExpire() {

		global $delai_token;

		$stop_date = new DateTime(date("Y-m-d H:i:s"));
		$stop_date->modify($delai_token);
		return $stop_date->format('Y-m-d H:i:s');
	}

	/** --------------------------------------------------------------------
	 * METHODE PRIVEE : Retourne l'IP rélle du client et non celle d'un proxy
	-------------------------------------------------------------------- */
	private function getVraiIp() {
		// IP si internet partagé
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			return $_SERVER['HTTP_CLIENT_IP'];

			// IP derrière un proxy
		} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
			// Sinon : IP normale
		} else {
			return (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
		}
	} // FIN méthode

	/** --------------------------------------------------------------------
	 * METHODE : Vérifie la validité d'un Token
	-------------------------------------------------------------------- */
	public function checkToken($token, $getUser = false) {


		// Si le token n'est pas valide à la base
		if ($token == false) { return false; }

		// On clean le token pour éviter toute intrusion
		$token_clean = preg_replace("/[^a-zA-Z0-9$+\/=]/", "", $token);

		// Si le token ne contiens pas le sallage de l'IP
		$token_array = explode('$', $token_clean);
		if (!is_array($token_array) || empty($token_array) ||!isset($token_array[0])) { return false; }

		// Si l'adresse IP sallée n'est pas valide
		$ip_from_token = base64_decode($token_array[0]);
		if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $ip_from_token)) { return false; }

		// On vérifie en base avec l'IP, le token et la date de validité

		$query = 'SELECT `token` FROM `pe_apitokens` WHERE `token` = "'.$token.'" AND `expire` > NOW()';

		$result = $this->db->query($query);
		$donnee    = $result->fetch(PDO::FETCH_ASSOC);

		if (!$donnee || !isset($donnee['token'])) { return false; }

		if ($getUser) {
			return intval($donnee['token']) > 0 ? intval($donnee['token']) : false;
		} else {
			return intval($donnee['token']) > 0;
		}

	} // FIN méthode


	/** -------------------------------------------------------------------
	 * METHODE : Retourne le token valide 
	-------------------------------------------------------------------- */
	public function checkTokenExistFromName($token) {

		$query  = 'SELECT `token` FROM `pe_apitokens` WHERE `token` = "'.$token.'" AND `expire` > NOW()';

		$result = $this->db->query($query);
		$donnee    = $result->fetch(PDO::FETCH_ASSOC);

		return $donnee == null || !$donnee ||  !isset($donnee['token']) || $donnee['token'] == '' ? false : $donnee['token'];

	} // FIN méthode


	/** --------------------------------------------------------------------
	 * METHODE : Retourne la liste des lots  (toutes vues)
	 * 	Clause : AND LOWER(v.`code`) = "rcp" supprimée
	-------------------------------------------------------------------- */
	public function getListeLotsReception() {

		$query_liste = 'SELECT l.`id`, a.`id_vue`, v.`nom`, l.`numlot`, a.`token`
							FROM `pe_lots` l
								JOIN `pe_lot_vues` lv ON lv.`id_lot` = l.`id`
								JOIN `pe_vues` v ON v.`id` = lv.`id_vue`
								JOIN `pe_apitokens` a ON a.`id_lot` = l.`id`
							WHERE l.`supprime` = 0 
							
								AND a.`token` IS NOT NULL
								AND a.`expire` > NOW()
							GROUP BY  l.`id`, a.`id_vue`, v.`nom`, l.`numlot`, a.`token`
							ORDER BY `id` DESC';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		// boucle sur les lots en réception
		while ($donnee = $query->fetch(PDO::FETCH_ASSOC)) {

			$liste[] = $donnee;

		} // FIN boucle

		return $liste;

	} // FIN méthode

	/** --------------------------------------------------------------------
	 * METHODE : Retourne si le site est en mode maintenance ou non
	-------------------------------------------------------------------- */
	public function isModeMaintenance() {

		$query  = 'SELECT `valeur` FROM `pe_config` WHERE `clef` = "maintenance"';
		$result = $this->db->query($query);
		$donnee    = $result->fetch(PDO::FETCH_ASSOC);

		return $donnee == null || !$donnee ||  !isset($donnee['valeur']) || intval($donnee['valeur']) == 1 ? true : false;

	} // FIN méthode

	/** --------------------------------------------------------------------
	 * METHODE : Retourne si un token est toujours valide pour le lot
	-------------------------------------------------------------------- */
	public function checkTokenFromLot(Lot $lot) {

		$query_check = 'SELECT COUNT(*) AS nb FROM `pe_apitokens` WHERE `id_lot` = ' . $lot->getId() . ' AND `expire` > NOW()';
		$result = $this->db->query($query_check);
		$donnee    = $result->fetch(PDO::FETCH_ASSOC);

		return $donnee == null || !$donnee ||  !isset($donnee['nb']) || intval($donnee['nb']) > 0 ? true : false;

	} // FIN méthode

	/** --------------------------------------------------------------------
	 * METHODE : Retourne si le token passé est toujours valide pour le lot
	-------------------------------------------------------------------- */
	public function checkTokenLot($token, $id_lot) {

		$query_check = 'SELECT COUNT(*) AS nb FROM `pe_apitokens` WHERE `id_lot` = ' . $id_lot . ' AND `token` = "'.$token.'" AND `expire` > NOW()';
		$query = $this->db->prepare($query_check);
		$query->execute();
		$donnee    = $query->fetch(PDO::FETCH_ASSOC);

		return $donnee == null || !$donnee || !isset($donnee['nb']) || intval($donnee['nb']) != 1 ? false : true;

	} // FIN méthode

	/** --------------------------------------------------------------------
	 * METHODE : Prolonge la durée de validité d'un token
	-------------------------------------------------------------------- */
	public function expendTokenLot(Lot $lot) {

		$query_upd = 'UPDATE `pe_apitokens` SET `expire` = "'.$this->getDateExpire().'" WHERE `id_lot` = ' . $lot->getId();
		$query = $this->db->prepare($query_upd);
		return $query->execute();

	} // FIN méthode

	/** --------------------------------------------------------------------
	 * METHODE : Upload une photo et l'enregistre en BDD
	-------------------------------------------------------------------- */
	public function savePhoto($photo_base64, $id_lot, $nom_vue, $codesErreur) {

		if (preg_match('/^data:image\/(\w+);base64,/', $photo_base64, $type)) {
			$photoB64 = substr($photo_base64, strpos($photo_base64, ',') + 1);
			$type = strtolower($type[1]); // jpg, png, gif

			if (!in_array($type, ['jpg', 'jpeg'])) { echo json_encode($codesErreur['EP2']); return false; }

			$photoB64 = base64_decode($photoB64);

			if ($photoB64 === false) { echo json_encode($codesErreur['EP3']); return false; }

		} else { echo json_encode($codesErreur['EP4']); return false; }

		// Pour la préprod, on rajoute le dossier iPrex/
		$chemin = strpos($_SERVER['DOCUMENT_ROOT'], 'intersed.info') || strpos($_SERVER['DOCUMENT_ROOT'], 'intersed') ? $_SERVER['DOCUMENT_ROOT'].'/iprex' : $_SERVER['DOCUMENT_ROOT'];
		$chemin.= '/uploads/'.(int)$id_lot.'/';

		// Si le dossier du lot n'existe pas, on le crée
		if (!file_exists($chemin)) { mkdir($chemin, 0777, true); }

		// On nomme le fichier avec un timestamp + un nombre aléatoire pour les fichiers multiples
		// Le timestamp ne suffit pas car en moins d'une seconde plusieurs images peuvent être générées et donc avoir le même nom de fichier
		$nomFichier = 'p'.time().rand(1,999999).'.'.$type;

		// Upload !
		if (!file_put_contents($chemin.$nomFichier, $photoB64)) { echo json_encode($codesErreur['EP5']); return false; }

		// BDD
		$nom_doc = 'Photo du lot';
		$nom_doc.= $nom_vue != '' ? ' en ' . $nom_vue : '';
		$query_type_doc_api = 'SELECT `id` FROM `pe_documents_types` WHERE `api` = 1 LIMIT 0,1';
		$query_type = $this->db->prepare($query_type_doc_api);
		$query_type->execute();

		$donnee = $query_type->fetch();

		if (!$donnee || empty($donnee) || !isset($donnee['id']) || intval($donnee['id']) == 0)  { json_encode($codesErreur['EB0']); return false; }
		$type_doc_api = intval($donnee['id']);

		$query_add = 'INSERT INTO `pe_documents` (`lot_id`, `filename`, `type_id`, `nom`, `date`, `supprime`) VALUES ('.$id_lot.', "'.$nomFichier.'", '.$type_doc_api.', "'.$nom_doc.'", "'.date('Y-m-d H:i:s').'", 0)';
		$query = $this->db->prepare($query_add);
		if (!$query->execute()) { echo json_encode($codesErreur['EB1']); return false; }

		return true;

	} // FIN méthode

} // FIN classe