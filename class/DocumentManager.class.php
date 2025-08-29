<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Manager de l'Objet Document
------------------------------------------------------*/
class DocumentManager {

	protected	$db, $nb_results;

	public function __construct($db) {
		$this->setDb($db);
	}
	
	//##### GETTERS #####
	public function getNb_results() {
		return $this->nb_results;
	}
	
	//##### SETTERS #####
	public function setDb(PDO $db) {
		$this->db = $db;
	}

	public function setNb_results($nb_results) {
		$this->nb_results = (int)$nb_results;
	}
	
	/****************
	 * METHODES
	 ***************/

	// Retourne un Document par son ID
	public function getDocument($id) {

		$query_document = 'SELECT d.`id`, d.`lot_id`, d.`filename`, d.`type_id`, d.`nom`, d.`date`, d.`supprime`, dt.`nom` AS type_nom, d.`type_lot`
							  FROM `pe_documents` d
								LEFT JOIN `pe_documents_types` dt ON dt.`id` = d.`type_id`
							WHERE d.`id` = :id';
		$query = $this->db->prepare($query_document);
		$query->bindValue(':id', (int)$id);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && !empty($donnee) ? new Document($donnee) : false;

	} // FIN méthode

	// Retourne la liste des Documents
	public function getListeDocuments($params = array()) {

		$start			= isset($params['start'])				? $params['start']			 	: 0;
		$nb				= isset($params['nb_results_p_page']) 	? $params['nb_results_p_page'] 	: 100000;
		$supprime 		= isset($params['supprime']) 			? intval($params['supprime']) 	: -1;
		$lot_id			= isset($params['lot_id']) 				? intval($params['lot_id']) 	: 0;
		$type_lot		= isset($params['type_lot']) 			? intval($params['type_lot']) 	: 0;

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS d.`id`, d.`lot_id`, d.`filename`, d.`type_id`, d.`nom`, d.`date`, d.`supprime`, dt.`nom` AS type_nom, l.`numlot`, d.`type_lot`
							FROM `pe_documents` d
								LEFT JOIN `pe_documents_types` dt ON dt.`id` = d.`type_id`
								LEFT JOIN `pe_lots` l ON l.`id` = d.`lot_id`
						  WHERE 1 ';
		$query_liste.= $supprime !== -1 ? 'AND d.`supprime` = ' .$supprime . ' ' : '';
		$query_liste.= $lot_id > 0 != '' ? 'AND d.`lot_id` = ' . $lot_id . ' ' : '';
		$query_liste.= $type_lot > -1 ? 'AND d.`type_lot` = ' . $type_lot . ' ' : '';
		$query_liste.= 'ORDER BY d.`lot_id`, d.`nom` ASC ';
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];
		foreach ($donnees = $query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Document($donnee);
		}

		return $liste;

	} // FIN getListe

	
	// Enregistre un nouveau document
	public function saveDocument(Document $objet) {
		
		$table		= 'pe_documents';	// Nom de la table
		$champClef	= 'id';				// Nom du champ clef primaire

		// FIN Configuration

		$getter		= 'get'.ucfirst(strtolower($champClef));
		$setter		= 'set'.ucfirst(strtolower($champClef));

		if ($objet->$getter() == '' && !empty($objet->attributs)) {

			$query_add = 'INSERT INTO `'.$table.'` (';

			foreach ($objet->attributs as $attribut)	{
				$query_add.= '`'.$attribut.'`,';
			}
			$query_add = substr($query_add,0,-1);
			$query_add.=') VALUES (';

			foreach ($objet->attributs as $attribut)	{
				$query_add.= ':'.strtolower($attribut).' ,';
			}
			$query_add = substr($query_add,0,-1);
			$query_add.=')';

			$query = $this->db->prepare($query_add);
			$query_log = $query_add;

			foreach ($objet->attributs as $attribut)	{
				$attributget = 'get'.ucfirst($attribut);
				$query->bindvalue(':'.strtolower($attribut), $objet->$attributget());
				$dq = is_numeric($objet->$attributget()) ? '' : '"';
				$query_log = str_replace(':'.strtolower($attribut).' ', $dq.$objet->$attributget().$dq.' ', $query_log);
			}

			if ($query->execute()) {
				$objet->$setter($this->db->lastInsertId());
				Outils::saveLog($query_log);
				return $objet->$getter();
			}

		} else if ($objet->$getter() != '' && !empty($objet->attributs)) {

			$query_upd = 'UPDATE `'.$table.'` SET ';

			foreach($objet->attributs as $attribut) {
				$query_upd.= '`'.$attribut.'` = :'.strtolower($attribut).' ,';
			}
			$query_upd = substr($query_upd,0,-1);
			$query_upd .= ' WHERE `'.$champClef.'` = '.$objet->$getter();

			$query = $this->db->prepare($query_upd);
			$query_log = $query_upd;

			foreach($objet->attributs as $attribut) {
				$attributget = 'get'.ucfirst($attribut);
				$query->bindvalue(':'.strtolower($attribut), $objet->$attributget());
				$dq = is_numeric($objet->$attributget()) ? '' : '"';
				$query_log = str_replace(':'.strtolower($attribut).' ', $dq.$objet->$attributget().$dq.' ', $query_log);
			}
			try	{
				$query->execute();
				Outils::saveLog($query_log);
				return true;
			} catch(PDOExeption $e) {return false;}
		}
		return false;
		
	} // FIN méthode


	// Vérifie si un document existe déjà avec ce nom de fichier
	public function checkExisteDeja($filename, $id_exclu = 0) {

		$query_check = 'SELECT COUNT(*) AS nb FROM `pe_documents` WHERE (LOWER(`filename`) = :filename )';
		$query_check.= (int)$id_exclu > 0 ? ' AND `id` != ' . (int)$id_exclu : '';

		$query = $this->db->prepare($query_check);
		$query->bindValue(':filename', trim(strtolower($filename)));
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);

		if ($donnee && isset($donnee[0]) && isset($donnee[0]['nb']) && intval($donnee[0]['nb']) > 0) {
			return true;
		}
		return false;

	} // FIN méthode

	// Supprime définitivement un document apres avoir détruit le fichier sur le serveur
	public function deleteDocument(Document $doc) {

		$query_del = 'DELETE FROM `pe_documents` WHERE `id` = :id ';
		$query = $this->db->prepare($query_del);
		$query->bindValue(':id', $doc->getId());
		$query_log = str_replace(':id', $doc->getId(),$query_del);
		Outils::saveLog($query_log);
		return $query->execute();

	} // FIN méthode

	// Supprime définitivement une liste de document apres avoir détruit le fichier sur le serveur (array d'IDs)
	public function deleteDocuments($ids_docs) {

		if (!is_array($ids_docs)) 	{ exit; }
		if (empty($ids_docs))  		{ exit; }
		$ids = implode(',', $ids_docs);
		if (strlen($ids) < 1) 		{ exit; }

		$query_del = 'DELETE FROM `pe_documents` WHERE `id` IN ('.$ids.')';
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);

		return $query->execute();

	} // FIN méthode


	/**************************************************************************************
	 * Ci-dessous les méthodes relatives aux documents PROFIL EXPORT (Bl, Factures...)
	************************************************************************************ */

	// Retourne le header des documents selon le type de document et la page
	public function getHeaderDocumentPdf(Tiers $client, $type_adresse = 'f', $type = '', $id_langue = 1, $show_pages = true, $pdf = true, $ref = '', $html_additionnel = '') {

		$types = ['tarifs', 'pl', 'bl', 'facture', 'avoir', 'bt'];
		$types_compta = ['tarifs', 'facture', 'pl', 'avoir'];
		if (!in_array($type, $types)) { $type = ''; }

		$traductionsManager = new TraductionsManager($this->db);
		$adressesManager = new AdresseManager($this->db);
		$titre_doc = $traductionsManager->getTrad($type, $id_langue);
		if ($type == 'tarifs') {
			$titre_doc.= ' ' . $traductionsManager->getTrad('au', $id_langue) . ' ';
			$titre_doc.= $id_langue == 1 ? date('d/m/Y') : date('Y-m-d');
		} elseif($type == 'bl' || $type == 'facture' || $type == 'avoir' || $type == 'bt') {
			$titre_doc.= ' ' .$ref;
		}
		$tel = $traductionsManager->getTrad('tel', $id_langue);
		$compta = $traductionsManager->getTrad('compta', $id_langue);
		$france = $id_langue > 1 ? ' - FRANCE' : '';

		$htmlCssAdresse = $pdf ? '' : 'html';

		$configManager = new ConfigManager($this->db);
		$i_raison_sociale = $configManager->getConfig('i_raison_sociale'); if (!$i_raison_sociale instanceof Config) {exit('Erreur instanciation config identité');}
		$i_capital = $configManager->getConfig('i_capital'); if (!$i_capital instanceof Config) {exit('Erreur instanciation config capital');}
		$i_adresse_1 = $configManager->getConfig('i_adresse_1');			if (!$i_adresse_1 instanceof Config) {exit('Erreur instanciation config identité');}
		$i_adresse_2 = $configManager->getConfig('i_adresse_2');			if (!$i_adresse_2 instanceof Config) {exit('Erreur instanciation config identité');}
		$i_sec_adresse_1 = $configManager->getConfig('i_sec_adresse_1');	if (!$i_sec_adresse_1 instanceof Config) {exit('Erreur instanciation config identité');}
		$i_sec_adresse_2 = $configManager->getConfig('i_sec_adresse_2');	if (!$i_sec_adresse_2 instanceof Config) {exit('Erreur instanciation config identité');}
		$i_tel = $configManager->getConfig('i_tel');						if (!$i_tel instanceof Config) {exit('Erreur instanciation config identité');}
		$i_fax = $configManager->getConfig('i_fax');						if (!$i_fax instanceof Config) {exit('Erreur instanciation config identité');}
		$i_sec_tel = $configManager->getConfig('i_sec_tel');				if (!$i_sec_tel instanceof Config) {exit('Erreur instanciation config identité');}
		$i_sec_fax = $configManager->getConfig('i_sec_fax');				if (!$i_sec_fax instanceof Config) {exit('Erreur instanciation config identité');}
		$i_info_1 = $configManager->getConfig('i_info_1');					if (!$i_info_1 instanceof Config) {exit('Erreur instanciation config identité');}
		$i_info_2 = $configManager->getConfig('i_info_2');					if (!$i_info_2 instanceof Config) {exit('Erreur instanciation config identité');}
		$i_num_tva = $configManager->getConfig('i_num_tva');				if (!$i_num_tva instanceof Config) {exit('Erreur instanciation config TVA intra');}
		$i_web = $configManager->getConfig('i_web');						if (!$i_web instanceof Config) {exit('Erreur instanciation config identité');}
		$i_sec_mail = $configManager->getConfig('i_sec_mail');				if (!$i_sec_mail instanceof Config) {exit('Erreur instanciation config identité');}

		$adresseClient = $adressesManager->getTiersAdresse($client, $type_adresse);

		if (!$adresseClient instanceof Adresse) { exit('Erreur instanciation adresse Tiers'); }

		$html = $show_pages ? '' : '<table></table><tr><td style="border-bottom:1px solid transparent">';
		$txtw = strlen($client->getNom()) > 30 ? 12 : 16;
		$html.= '<div class="relative" id="headerDoc"><div class="adresse '.$htmlCssAdresse.'">';
		$html.= $adresseClient->getNom() == '' ? '<p class="text-'.$txtw.' gras">'.$client->getNom().'</p>' : '';
		$html.= '<p class="text-12">'.$adresseClient->getAdresse($client->getNom()).'</p>';
		$html.= '</div>';
		$html.= $show_pages ? '<div class="text-12 mt-10" style="position: absolute;right:1px;">Page [[page_cu]]/[[page_nb]]</div>' :'';
		$html.= '<table class="w100">';

		$img_ifs = $pdf ? 'ifs-pdf.jpg' : 'ifs-html.jpg';
		$img_rl = $pdf ? 'respectful-life-pdf.jpg' : 'respectful-life-html.jpg';
		$imgCss = $pdf ? '' : 'style="max-width:30px;"';

		$html.= '<tr>
					<td style="width:350px">
						<img src="'.__CBO_ROOT_PATH__. '/img/profil-export.jpg" style="width: 275px"/>
						<p class="text-22">'.$i_raison_sociale->getValeur().'</p>	
						<p class="text-11">'.$i_capital->getValeur().'</p>	
						<p class="text-11">'.$i_adresse_1->getValeur().'</p>	
						<p class="text-11">'.$i_adresse_2->getValeur().$france.'</p>	
						<p class="text-11">'.$tel.'. '.$i_tel->getValeur().' / Fax : '.$i_fax->getValeur().'</p>	
						<p class="text-11">'.$i_info_1->getValeur().'</p>	
						<p class="text-11">'.$i_info_2->getValeur().'</p>	
						<p class="text-11">'.$i_web->getValeur().'</p>	
						<p class="text-11 mb-5">TVA : '.$i_num_tva->getValeur().'</p>	
						<img src="'.__CBO_ROOT_PATH__. '/img/' . $img_ifs.'" '.$imgCss.'/>
						<img src="'.__CBO_ROOT_PATH__. '/img/' . $img_rl.'" '.$imgCss.'/>';


		$html.= in_array($type, $types_compta)
						? '<p class="text-14 mt-5">'.strtoupper(Outils::removeAccents($compta)).'</p>
						   <p class="text-11">'.$i_sec_adresse_1->getValeur().'</p>
						   <p class="text-11">'.$i_sec_adresse_2->getValeur().$france.'</p>
						   <p class="text-11">'.$tel.'. '.$i_sec_tel->getValeur().' / Fax : '.$i_sec_fax->getValeur().'</p>
						   <p class="text-11">'.$i_sec_mail->getValeur().$france.'</p>'
						: '';

		$html.= '	</td>
					<td class="text-16 vtop" style="width: 350px">'.$titre_doc.'</td>
					</tr>';
		$html.= '</table></div>';
		$html.= $show_pages ? '' : '</td></tr></table>';
		$html.= $html_additionnel;

		return $html;

	} // FIN méthode

} // FIN classe