<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Manager des objets relatifs au traitement de froid
------------------------------------------------------*/
class FroidManager {

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
	// Retourne une OP de froid par son ID
	public function getFroid($id, $with_lots_objets = false) {
		$query_froid= 'SELECT f.`id`, f.`id_type`, f.`date_entree`, f.`date_sortie`, f.`temp_debut`, f.`temp_fin`, f.`conformite`, 
       						  f.`id_visa_controleur`, f.`date_controle`, `id_user_maj`, f.`statut`, ft.`code`, f.`nuit`, f.`supprime`,
       						  f.`test_avant_fe`,f.`test_avant_nfe`,f.`test_avant_inox`, f.`test_apres_fe`,f.`test_apres_nfe`,f.`test_apres_inox`, pdt.`id_lot_pdt_froid`
							FROM `pe_froid` f
								LEFT JOIN `pe_froid_types` ft ON ft.`id` = f.`id_type`								
								LEFT JOIN `pe_froid_produits` pdt ON pdt.`id_froid` = f.`id`								
							WHERE f.`id` = :id';
		$query = $this->db->prepare($query_froid);
		$query->bindValue(':id', (int)$id);
		$query->execute();

		$donnee = $query->fetch();
		if (!$donnee || empty($donnee)) { return false; }
		$froid =  new Froid($donnee);
		$froid->setNb_produits($this->getNbProduitsFroid($froid));
		// On intègre les lots associés
		if ($with_lots_objets) {
			$query_lots = 'SELECT  l.`id`, l.`numlot`, l.`date_add`, l.`date_prod`, l.`date_maj`, l.`date_out`, l.`date_reception`, l.`date_abattage`, l.`id_abattoir`, l.`id_origine`, 
                           			l.`poids_abattoir`, l.`poids_reception`, l.`supprime`,  l.`id_user_maj`, l.`composition`
							FROM `pe_lots` l
								JOIN `pe_froid_produits` fp ON fp.`id_lot` = l.`id` 
							WHERE fp.`id_froid` = ' . $froid->getId() . '
							GROUP BY l.`id`, l.`numlot`, l.`date_add`, l.`date_prod`, l.`date_maj`, l.`date_out`, l.`date_reception`, l.`date_abattage`, l.`id_abattoir`, l.`id_origine`, 
                           			l.`poids_abattoir`, l.`poids_reception`, l.`supprime`,  l.`id_user_maj`, l.`composition` ';
			$queryL = $this->db->prepare($query_lots);
			$queryL->execute();
			$listeLots = [];
			foreach ($queryL->fetchAll(PDO::FETCH_ASSOC) as $donneesL) {
				$listeLots[] = new Lot($donneesL);
			}
			$froid->setLots($listeLots);
		}
		return $froid;
	} // FIN Fonction


	// Retourne les opérations de froid
	public function getFroidsListe($params) {

		$type_code 	 = isset($params['type_code']) 	 ? strtolower(trim($params['type_code'])) 	: '';
		$statuts 	 = isset($params['statuts']) 	 ? trim($params['statuts'])		 			: '';
		$en_cours 	 = isset($params['en_cours']) 	 ? boolval($params['en_cours']) 			: false;
		$en_cours_j	 = isset($params['en_cours_j'])  ? boolval($params['en_cours_j']) 			: false;
		$lots_objets = isset($params['lots_objets']) ? boolval($params['lots_objets']) 			: false;
		$nb_pdts 	 = isset($params['nb_pdts']) 	 ? boolval($params['nb_pdts']) 			 	: false;

		$show_supprimes = isset($params['show_supprimes']) 	 ? boolval($params['show_supprimes']) 			 	: false;

		$start			= isset($params['start'])				? $params['start']			 	: 0;
		$nb				= isset($params['nb_results_p_page']) 	? $params['nb_results_p_page'] 	: 100000;

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS f.`id`, f.`id_type`, f.`date_entree`, f.`date_sortie`, f.`temp_debut`, f.`temp_fin`, f.`conformite`,
                           						   f.`id_visa_controleur`, f.`date_controle`, `id_user_maj`, f.`statut`, f.`nuit`, f.`supprime`,
                           						   f.`test_avant_fe`,f.`test_avant_nfe`,f.`test_avant_inox`, f.`test_apres_fe`,f.`test_apres_nfe`,f.`test_apres_inox`, pdt.`id_lot_pdt_froid`
							FROM `pe_froid` f
								LEFT JOIN `pe_froid_types` ft ON ft.`id` = f.`id_type`
								LEFT JOIN `pe_froid_produits` pdt ON pdt.`id_froid` = f.`id`
						  WHERE 1 ';

		$query_liste.= $type_code != '' ? 'AND LOWER(ft.`code`) = "'.$type_code.'" ' : '';
		$query_liste.= $statuts != '' ? 'AND f.`statut` IN ('.$statuts.') ' : '';
		$query_liste.= $en_cours_j ? 'AND (f.`date_sortie` IS NULL OR f.`date_sortie` = "" OR f.`date_sortie` = "0000-00-00 00:00:00" OR  f.`date_sortie` > (DATE_SUB(CURDATE(), INTERVAL 2 DAY))) ' : '';
		$query_liste.= $en_cours ? 'AND (f.`date_sortie` IS NULL OR f.`date_sortie` = "" OR f.`date_sortie` = "0000-00-00 00:00:00") ' : '';

		$query_liste.= !$show_supprimes ? ' AND f.`supprime` = 0 ' : '';

		$query_liste.= 'ORDER BY f.`id` DESC ';
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($donnees = $query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$froid =  new Froid($donnee);

			// On intègre les lots associés
			if ($lots_objets) {
				$query_lots = 'SELECT  l.`id`, l.`numlot`, l.`date_add`, l.`date_prod`, l.`date_maj`, l.`date_out`, l.`date_reception`, l.`date_abattage`, l.`id_abattoir`, l.`id_origine`, 
                           			l.`poids_abattoir`, l.`poids_reception`, l.`supprime`,  l.`id_user_maj`, l.`composition`
							FROM `pe_lots` l
								JOIN `pe_froid_produits` fp ON fp.`id_lot` = l.`id` 
							WHERE fp.`id_froid` = ' . $froid->getId() . ' AND fp.`attente` = 0
							GROUP BY l.`id`, l.`numlot`, l.`date_add`, l.`date_prod`, l.`date_maj`, l.`date_out`, l.`date_reception`, l.`date_abattage`, l.`id_abattoir`, l.`id_origine`, 
                           			l.`poids_abattoir`, l.`poids_reception`, l.`supprime`,  l.`id_user_maj`, l.`composition`';
				$queryL = $this->db->prepare($query_lots);
				$queryL->execute();

				$listeLots = [];

				foreach ($queryL->fetchAll(PDO::FETCH_ASSOC) as $donneesL) {
					$listeLots[] = new Lot($donneesL);
				}

				$froid->setLots($listeLots);
			}

			// Nombre de produits dans l'op
			if ($nb_pdts) {
				$froid->setNb_produits($this->getNbProduitsFroid($froid));
			} // FIN Nombre de produits dans l'op

			$liste[] = $froid;
		}
		return $liste;

	} // FIN getListe

	// Retourne le nombre de produits d'une op de froid
	public function getNbProduitsFroid(Froid $froid) {

		$query_count = 'SELECT COUNT(*) AS nb FROM `pe_froid_produits` WHERE `attente` = 0 AND `id_froid` = ' . $froid->getId();
		$query = $this->db->prepare($query_count);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee[0]) && isset($donnee[0]['nb']) ? intval($donnee[0]['nb']) : 0;

	} // FIN méthode

	// retourne le code de froid par son id
	public function getFroidCodeById($id) {

		$query_id = 'SELECT `code` FROM `pe_froid_types` WHERE `id` = ' . intval($id);
		$query = $this->db->prepare($query_id);
		$query->execute();

		$donnee = $query->fetch();
		if (!$donnee || empty($donnee) || !isset($donnee['code'])) { return false; }
		return $donnee['code'];

	} // FIN méthode

	// Retoune le type froid id par code
	public function getFroidTypeByCode($code) {

		$query_id = 'SELECT `id` FROM `pe_froid_types` WHERE `code` = "' . strtolower(trim($code)) . '"';
		$query = $this->db->prepare($query_id);
		$query->execute();

		$donnee = $query->fetch();
		if (!$donnee || empty($donnee)) { return false; }
		return intval($donnee['id']);

	} // FIN méthode

	// Retourne les types de froids
	public function getFroidTypes() {

		$query_liste = 'SELECT `id`, `nom`, `code` FROM `pe_froid_types` WHERE `id` > 0 ORDER BY `nom` ASC';
		$query_f = $this->db->prepare($query_liste);
		$query_f->execute();
		$froids = [];
		foreach ($query_f->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$tmp = [];
			$tmp['id'] = $donnee['id'];
			$tmp['nom'] = $donnee['nom'];
			$tmp['code'] = $donnee['code'];
			$froids[] = $tmp;
		}
		return $froids;

	} // FIN méthode

	// Enregistre une op de froid
	public function saveFroid(Froid $objet) {

		$table		= 'pe_froid';	// Nom de la table
		$champClef	= 'id';			// Nom du champ clef primaire
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

	// Enregistre un lot pdt froid
	public function saveFroidProduit(FroidProduit $objet) {

		$table		= 'pe_froid_produits';	// Nom de la table
		$champClef	= 'id_lot_pdt_froid';	// Nom du champ clef primaire
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


	// Retourne le nombre total de colis d'une op de froid
	public function getNbColisFroid(Froid $froid) {

		$query_nb = 'SELECT IF (pc.`nb_colis` IS NOT NULL, SUM(pc.`nb_colis`), fp.`nb_colis`) AS nb 
						FROM `pe_froid_produits` fp
						LEFT JOIN `pe_palette_composition` pc ON pc.`id_lot_pdt_froid` = fp.`id_lot_pdt_froid` AND pc.`supprime` = 0
						WHERE fp.`attente` = 0 AND fp.`id_froid` = :id_froid ';

		$query = $this->db->prepare($query_nb);
		$query->bindValue(':id_froid', $froid->getId());
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && !empty($donnee) && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode

	// Retourne le poids total des produits d'une op de froid
	public function getPoidsFroid(Froid $froid) {

		$query_nb = 'SELECT IF (pc.`poids` IS NOT NULL, SUM(pc.`poids`), SUM(fp.`poids`)) AS nb 
						FROM `pe_froid_produits` fp
						LEFT JOIN `pe_palette_composition` pc ON pc.`id_lot_pdt_froid` = fp.`id_lot_pdt_froid` AND pc.`supprime` = 0
						WHERE fp.`attente` = 0 AND fp.`id_froid` = :id_froid ';

		$query = $this->db->prepare($query_nb);
		$query->bindValue(':id_froid', $froid->getId());
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && !empty($donnee) && isset($donnee['nb']) ? floatval($donnee['nb']) : 0;

	} // FIN méthode

	// Retourne le poids total des produits d'une op de froid depuis la table des compos
	public function getPoidsFroidFromCompos(Froid $froid) {

		$query_nb = 'SELECT SUM(pc.`poids`) AS nb 
						FROM `pe_palette_composition` pc
							JOIN `pe_froid_produits` fp ON fp.`id_lot_pdt_froid` = pc.`id_lot_pdt_froid`
						WHERE fp.`attente` = 0 AND fp.`id_froid` = :id_froid ';

		$query = $this->db->prepare($query_nb);
		$query->bindValue(':id_froid', $froid->getId());
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && !empty($donnee) && isset($donnee['nb']) ? floatval($donnee['nb']) : 0;

	} // FIN méthode

	// Retourne les ID des produits d'une OP de froid
	public function getIdPdtsFroid($froid) {

		$id_froid = $froid instanceof Froid ? $froid->getid() : intval($froid);

		$query_liste = 'SELECT DISTINCT `id_pdt` 
							FROM `pe_froid_produits`
						WHERE `id_froid` = '.$id_froid;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($donnees = $query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = intval($donnee['id_pdt']);
		}

		return $liste;

	} // FIN méthode


	// Retourne les ID des produits d'une OP de froid pour un lot précis
	public function getIdPdtsFroidLot($froid, $lot) {

		$id_froid 	= $froid instanceof Froid ? $froid->getid() : intval($froid);
		$id_lot		= $lot   instanceof Lot   ? $lot->getId()   : intval($lot);

		$query_liste = 'SELECT DISTINCT `id_pdt`
							FROM `pe_froid_produits`
						WHERE `id_froid` = '.$id_froid.'
							AND `id_lot` = '.$id_lot.'
						';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($donnees = $query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = intval($donnee['id_pdt']);
		}

		return $liste;

	} // FIN méthode

	// Retourne un produit de traitement froid (objet FroidProduit) - par id_lot_pdt_froid
	public function getFroidProduitObjetByIdLotPdtFroid($id_lot_pdt_froid) {

		$query_fpdt = 'SELECT fp.`id_lot_pdt_froid`, fp.`id_lot`, fp.`id_pdt`, fp.`id_froid`, fp.`nb_colis`, fp.`poids`,  fp.`quantite` , fp.`etiquetage`, fp.`quantieme`, fp.`loma`, fp.`id_palette`,
        						ft.`nom` AS nom_traitement, fp.`etiquetage`, CONCAT(LOWER(ft.`code`),LPAD(fp.`id_froid`, 4, 0)) AS code_traitement, pc.`id` AS id_compo, l.`numlot`, p.`numero` as numero_palette
							FROM `pe_froid_produits` fp
								JOIN `pe_froid` f ON f.`id` = fp.`id_froid`
								JOIN `pe_froid_types` ft ON ft.`id` = f.`id_type`
								LEFT JOIN `pe_palette_composition` pc ON pc.`id_lot_pdt_froid` = fp.`id_lot_pdt_froid` AND pc.`id_palette` =  fp.`id_palette`
								LEFT JOIN `pe_palettes` p ON p.`id` = fp.`id_palette` 
								LEFT JOIN `pe_lots` l ON l.`id` = fp.`id_lot` 
						WHERE fp.`id_lot_pdt_froid` = ' . intval($id_lot_pdt_froid);

		$query = $this->db->prepare($query_fpdt);
		$query->execute();
		$donnee = $query->fetch();

		
		if (!$donnee || empty($donnee)) { return false; }

		$froidPdt =  new FroidProduit($donnee);

		$pdtManager = new ProduitManager($this->db);
		$pdt = $pdtManager->getProduit($froidPdt->getId_pdt());
		if($pdt instanceof Produit) {
			$froidPdt->setProduit($pdt);
		}

		$froid = $this->getFroid($froidPdt->getId_froid());
		if ($froid instanceof Froid) {
			$froidPdt->setFroid($froid);
		}

		return $froidPdt;

	}

	// Retourne un produit de traitement froid (objet FroidProduit) - par ids séparés
	public function getFroidProduitObjet($id_lot, $id_pdt, $id_froid, $quantieme = '', $id_palette = 0) {

		$query_fpdt = 'SELECT `id_lot_pdt_froid`
							FROM `pe_froid_produits` 
						WHERE `id_lot` = ' . intval($id_lot) . ' AND `id_pdt` = ' . intval($id_pdt) . ' AND `id_froid` = ' . intval($id_froid);

		$query_fpdt.= $quantieme != '' ? ' AND `quantieme` = "' . $quantieme . '"' : '';
		$query_fpdt.= $id_palette > 0  ? ' AND `id_palette` = "' . $id_palette . '"' : '';

		$query = $this->db->prepare($query_fpdt);
		$query->execute();
		$donnee = $query->fetch();
		if (!$donnee || empty($donnee)) { return false; }

		return $this->getFroidProduitObjetByIdLotPdtFroid(intval($donnee['id_lot_pdt_froid']));

	} // FIN méthode


	// Retourne l'objet Froid avec les objets produits
	public function getFroidProduits(Froid $froid, $order_by = 'pdt', $sens = 'asc') {

		if ($order_by == 'pdt') {
			$order_by_sql ='t.`nom` '.strtoupper($sens).', l.`id`, fp.`quantieme`';
		} else if ($order_by == 'lot') {
			$order_by_sql ='l.`id` '.strtoupper($sens).', fp.`quantieme`, t.`nom`';
		} else {
			$order_by_sql ='t.`nom` ' . strtoupper($sens);
		}
		$order_by_sql.= ', fp.`id_palette`';

		$query_liste = 'SELECT  fp.`id_lot_pdt_froid`, fp.`id_lot`, fp.`id_pdt`,  ft.`nom` AS type_froid_nom, l.`numlot`, fp.`etiquetage`, fp.`quantieme`,fp.`quantite`, fp.`id_palette`, pa.`numero` AS numero_palette, IF (pc.`poids` IS NOT NULL, SUM(pc.`poids`), fp.`poids`) AS poids, IF (pc.`nb_colis` IS NOT NULL, SUM(pc.`nb_colis`), fp.`nb_colis`) AS nb_colis,  fp.`id_froid`
							FROM `pe_froid_produits`  fp
								JOIN `pe_froid` f ON f.`id` = fp.`id_froid`
								JOIN `pe_froid_types` ft ON ft.`id` = f.`id_type`
								JOIN `pe_lots` l ON l.`id` = fp.`id_lot`
								JOIN `pe_produits` p ON p.`id` = fp.`id_pdt` 
								LEFT JOIN `pe_palettes` pa ON pa.`id` = fp.`id_palette`
								LEFT JOIN `pe_palette_composition` pc ON pc.`id_lot_pdt_froid` = fp.`id_lot_pdt_froid` AND pc.`supprime` = 0
								LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = p.`id` AND t.`id_langue` = 1
						WHERE fp.`id_froid` = ' . $froid->getId() . ' AND fp.`attente` = 0
						GROUP BY  fp.`id_lot_pdt_froid`
						ORDER BY  ' . $order_by_sql ;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		$produitsManager = new ProduitManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$froidPdt = new FroidProduit($donnee);

			// On intègre l'objet produit
			$pdt = $produitsManager->getProduit($froidPdt->getId_pdt());
			if ($pdt instanceof Produit) {
				$froidPdt->setProduit($pdt);
			}
			$liste[] = $froidPdt;
		}
		return $liste;

	} // FIN méthode

	// Retourne la liste des produits congelés du jour
	public function getProduitsFroidJour($params) {

		$entree_du 	= isset($params['date_entree_debut']) 	? $params['date_entree_debut'] 		: date('Y-m-d');
		$entree_au 	= isset($params['date_entree_fin']) 	? $params['date_entree_fin'] 		: date('Y-m-d');
		$sortie_du 	= isset($params['date_sortie_debut']) 	? $params['date_sortie_debut'] 		: '';
		$sortie_au 	= isset($params['date_sortie_fin']) 	? $params['date_sortie_fin'] 		: '';
		$type		= isset($params['type'])				? trim(strtolower($params['type'])) : '';
		$start		= isset($params['start'])				? $params['start']			 		: 0;
		$nb			= isset($params['nb_results_p_page']) 	? $params['nb_results_p_page'] 		: 100000;
		$liste 		= [];
		$query_liste = 'SELECT f.`id` AS id_froid, f.`date_entree`, f.`date_sortie`, f.`temp_debut`, f.`temp_fin`, f.`statut`, f.`conformite`, `fp`.`id_lot_pdt_froid`, fp.`id_pdt`, fp.`id_palette`,
       							fp.`id_lot`, fp.`nb_colis`, fp.`poids`, l.`numlot`, pt.`nom`, p.`code`,fp.`quantieme`
							FROM `pe_froid_produits` fp
								LEFT JOIN `pe_froid` f ON f.`id` = fp.`id_froid`
								LEFT JOIN `pe_froid_types` ft ON ft.`id` = f.`id_type`
								LEFT JOIN `pe_lots` l ON l.`id` = fp.`id_lot`
								LEFT JOIN `pe_produits` p ON p.`id` = fp.`id_pdt`
								LEFT JOIN `pe_produit_trad` pt ON pt.`id_produit` = p.`id` AND pt.`id_langue` = 1
						WHERE 1 ';
		$query_liste.= 	$type != '' ? ' AND LOWER(ft.`code`) = "'.$type.'" ' : '';
		$query_liste.= 	$entree_du != '' ? 'AND  f.`date_entree` > "'.$entree_du.' 00:00:00" ' : '';
		$query_liste.= 	$entree_au != '' ? 'AND  f.`date_entree` < "'.$entree_au.' 23:59:59" ' : '';
		$query_liste.= 	$sortie_du != '' ? 'AND  f.`date_sortie` > "'.$sortie_du.' 00:00:00" ' : '';
		$query_liste.= 	$sortie_au != '' ? 'AND  f.`date_sortie` < "'.$sortie_au.' 23:59:59" ' : '';

		$query_liste.= 'LIMIT ' . $start . ',' . $nb;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$produitManager = new ProduitManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$tmp = new FroidProduit($donnee);

			if (!$tmp instanceof FroidProduit) { continue; }

			if (($entree_du != '' || $entree_au != '') && $donnee['date_entree'] == '') { continue; }
			if (($sortie_du != '' || $sortie_au != '') && $donnee['date_sortie'] == '') { continue; }

			$pdt = $produitManager->getProduit($tmp->getId_pdt());
			if ($pdt instanceof Produit) {
				$tmp->setProduit($pdt);
			}

			$froid = $this->getFroid($tmp->getId_froid());
			if ($froid instanceof Froid) {
				$tmp->setFroid($froid);
			}
			$liste[] = $tmp;
		}
		return $liste;

	} // FIN méthode

	// Retourne les OP de froids relatives à un lot (admin BO)
	public function getFroidsListeByLot($id_lot) {

		$query_liste = 'SELECT f.`id`, f.`id_type`, ft.`code`, ft.`nom` AS type_nom, f.`date_entree`, f.`date_sortie`, f.`temp_debut`, f.`temp_fin`, f.`conformite` , f.`nuit`, f.`supprime`
		 					FROM `pe_froid` f 
		 						JOIN `pe_froid_types` ft ON ft.`id` = f.`id_type`
								JOIN `pe_froid_produits` fp ON fp.`id_froid` = f.`id`
		 					WHERE fp.`id_lot` = ' . $id_lot . ' AND  f.`supprime` = 0
		 					GROUP BY f.`id`, f.`id_type`, ft.`code`, ft.`nom`, f.`date_entree`, f.`date_sortie`, f.`temp_debut`, f.`temp_fin`, f.`conformite`  
		 					ORDER BY  f.`date_entree` ASC';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());
		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] =  new Froid($donnee);
		}
		return $liste;

	} // FIN méthode

	// Identifie un produit comme relevant d'un contrôle Loma requis
	public function setProduitLoma(FroidProduit $froidProduit) {

		$query_addloma = 'UPDATE `pe_froid_produits` SET `loma` = 1 WHERE `id_lot_pdt_froid` = :id_lot_pdt_froid';
		$query = $this->db->prepare($query_addloma);
		$query->bindValue(':id_lot_pdt_froid', $froidProduit->getId_lot_pdt_froid());
		$query_log = str_replace(':id_lot_pdt_froid', $froidProduit->getId_lot_pdt_froid(),$query_addloma);
		Outils::saveLog($query_log);
		return $query->execute();

	} // FIN méthode

	// Retoune le nombre de produits de l'op de froid à tester en Loma
	public function getNbProduitsLoma(Froid $froid) {

		$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_froid_produits` WHERE `loma` = 1 AND `id_froid` = ' . $froid->getId();
		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();
		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode

	// Tetourne les pdtlots d'une op de froid ayant un loma à 1 et pour lesquels les tests n'ont pas été faits
	public function getLomaAfaireFromFroid(Froid $froid) {

		$query_liste = 'SELECT fp.`id_lot_pdt_froid` 
							FROM `pe_froid_produits` fp 
							LEFT JOIN `pe_loma` l ON l.`id_lot_pdt_froid` = fp.`id_lot_pdt_froid` 
							    WHERE fp.`id_froid` = ' . $froid->getId() . '
							    AND l.`id` IS NULL 
							    AND fp.`loma` = 1
							ORDER BY fp.`id_lot_pdt_froid` ';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnees) {
			$froidProduit = $this->getFroidProduitObjetByIdLotPdtFroid(intval($donnees['id_lot_pdt_froid']));
			if (!$froidProduit instanceof FroidProduit) { continue; }
			$liste[] = $froidProduit;
		}
		return $liste;

	} // FIN méthode

	// Retourne si les contrôles loma de l'op de froid sont terminés (etapes ticket SRG)
	public function isLomaTermine(Froid $froid) {

		$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_loma` WHERE `id_lot_pdt_froid` IN (SELECT `id_lot_pdt_froid` FROM `pe_froid_produits` WHERE `loma` = 1 AND `id_froid` = '.$froid->getId().')';
		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();
		if (!$donnee || !isset($donnee['nb'])) { return true; }
		return intval($donnee['nb']) == 0 ? true : false;

	} //FIN méthode

	// Enregistre l'étiquetage dans la table froid_produit
	public function saveEtiquetagePdtFroid($id_lot_pdt_froid, $etiquetage) {

		$query_upd = 'UPDATE `pe_froid_produits` SET `etiquetage` = ' . intval($etiquetage) . ' WHERE `id_lot_pdt_froid` = ' . intval($id_lot_pdt_froid);
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();

	} // FIN méthode

	// Enregistre le poids et le nb de colis pour un produit dans la table froid produit
	public function savePoidsColisLotProduit(FroidProduit $froidProduit, $nb_colis, $poids, $quantite ,$id_palette) {

		$query_upd = 'UPDATE `pe_froid_produits` SET `nb_colis` = ' . intval($nb_colis) . ', `poids` = '.floatval($poids).',  `quantite` = '.intval($quantite).', `id_palette` = '.$id_palette.' WHERE `id_lot_pdt_froid` = ' . intval($froidProduit->getId_lot_pdt_froid());
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();

	} // FIN méthode

	// Retourne les quantièmes du lot, si ils sont présent dans le traitement (pdts)
	public function getQuantiemesLotFroid($id_froid, $lot) {

		$query_liste = 'SELECT DISTINCT `quantieme` 
							FROM `pe_froid_produits`
						WHERE `id_froid` = '.$id_froid.' AND `id_lot` = ' . $lot->getId();
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnees) {
			$liste[] = $donnees['quantieme'];
		}
		return $liste;

	} // FIN méthode

	// Supprime un produit d'une opération de froid
	public function supprPdtfroid($idOuObjet) {

		$id_lot_pdt_froid = $idOuObjet instanceof FroidProduit ? $idOuObjet->getId_lot_pdt_froid() : intval($idOuObjet);

		$logManager = new LogManager($this->db);
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Suppression du produit pdt_froid #'.$id_lot_pdt_froid.' et des compositions associées');
		$logManager->saveLog($log);

		$query_del = 'DELETE FROM `pe_froid_produits` WHERE `id_lot_pdt_froid` = ' . $id_lot_pdt_froid;
		$query = $this->db->prepare($query_del);
		if ($query->execute()) {
			Outils::saveLog($query_del);
			$query_del2 = 'DELETE FROM `pe_palette_composition` WHERE `id_lot_pdt_froid` = ' . $id_lot_pdt_froid;
			$query2 = $this->db->prepare($query_del2);
			Outils::saveLog($query_del2);
			return $query2->execute();
		}
		return false;

	} // FIN méthode

	// Retourne la liste des produits de lot
	public function getListeLotProduits($params) {
		$id_lot 	= isset($params['id_lot']) 	 	? intval($params['id_lot'])     : -1;
		$poids 		= isset($params['poids']) 	 	? intval($params['poids'])      : -1;
		$orderById  = isset($params['orderbyid']) 	? boolval($params['orderbyid']) : false;
		$orderByFroid = isset($params['orderbyfroid']) ? boolval($params['orderbyfroid']) : false;
		$memeSiPasCompo = isset($params['meme_si_pas_compo']) ? boolval($params['meme_si_pas_compo']) : false;

		$order = $orderById ? 'fp.`id_pdt`' : 'ftrad.`nom`';

		$query_liste = '(';
		$query_liste.= 'SELECT fp.`id_lot_pdt_froid`, fp.`id_lot`, fp.`id_pdt`, fp.`nb_colis`, fp.`poids`, fp.`user_maj`, fp.`date_maj`,  fp.`user_add`, fp.`date_add`, ft.`nom` AS type_froid_nom, ft.`code` AS froid_code, fp.`id_froid`, fp.`quantieme`,  fp.`id_palette`, pal.`numero` AS numero_palette, 0 AS id_compo,
                           	IF ( udeb.`id` > 0, CONCAT( udeb.`prenom`, " ", udeb.`nom`), "") AS user_debut,
                           	IF ( ufin.`id` > 0, CONCAT( ufin.`prenom`, " ", ufin.`nom`), "") AS user_fin,
       						IF (fp.`id_lot_pdt_froid` > 0, 0, 1) AS is_froid
							FROM `pe_froid_produits` fp 
							    LEFT JOIN `pe_palettes` pal ON pal.`id` = fp.`id_palette`
							    LEFT JOIN `pe_palette_composition` con ON con.`id_lot_pdt_froid` = fp.`id_lot_pdt_froid`
								LEFT JOIN `pe_produits` p ON p.`id` = fp.`id_pdt`
								LEFT JOIN `pe_froid` f ON f.`id` = fp.`id_froid`
								LEFT JOIN `pe_froid_types` ft ON ft.`id` = f.`id_type`
								LEFT JOIN `pe_produit_trad` ftrad ON ftrad.`id_produit` = p.`id` AND ftrad.`id_langue` = 1
								LEFT JOIN `pe_users` udeb ON udeb.`id` = f.`id_user_debut`
								LEFT JOIN `pe_users` ufin ON ufin.`id` = f.`id_user_fin`
						WHERE 1 ';

		$query_liste.= $memeSiPasCompo ? '  ' : ' AND (con.`supprime` IS NULL || con.`supprime` = 0 || con.`archive` = 1) ';
		$query_liste.= $id_lot > 0 ? 'AND fp.`id_lot` = ' . $id_lot . ' ' : '';
		$query_liste.= $poids > 0 ? 'AND fp.`poids` = ' . $poids . ' ' : '';
		$query_liste.= $poids == 0 ? 'AND (fp.`poids` IS NULL OR fp.`poids` = 0) ' : '';
		$query_liste.= 'ORDER BY ';
		$query_liste.= $orderByFroid ?  'is_froid, ' : ' ';
		$query_liste.= ''.$order.', fp.`quantieme` ASC ';
		$query_liste.= ') UNION (';

		$query_liste.= ' SELECT 0 AS id_lot_pdt_froid, f.`id_lot`, pc.`id_produit` AS id_pdt, pc.`nb_colis`, pc.`poids`, pc.`id_user` AS user_maj, f.`date_scan` AS date_maj, pc.`id_user` AS user_add, pc.`date` AS date_add, "Frais" AS type_froid_nom, 0 AS froid_code, 0 AS id_froid, f.`quantieme`, pc.`id_palette`, pal.`numero` AS numero_palette, pc.`id` AS id_compo,
        					"" AS user_debut,
        					"" AS user_fin,
        					1 AS is_froid
		 					FROM `pe_palette_composition` pc
 								JOIN `pe_frais` f on f.`id` = pc.`id_frais` 
 								LEFT JOIN `pe_palettes` pal ON pal.`id` = pc.`id_palette`
		 					WHERE 1 ';

		$query_liste.= $memeSiPasCompo ? '  ' :  ' AND (pc.`supprime` = 0 OR pc.`archive` = 1) ';
		$query_liste.= $id_lot > 0 ? 'AND f.`id_lot` = ' . $id_lot . ' ' : '';
		$query_liste.= 'ORDER BY ';
		$query_liste.= $orderByFroid ?  'is_froid, ' : ' ';
		$query_liste.= ' pc.`id_produit` ASC ';
		$query_liste.= ') UNION (';

		$query_liste.= ' SELECT 0 AS id_lot_pdt_froid, pc.`id_lot_hors_stock`, pc.`id_produit` AS id_pdt, pc.`nb_colis`, pc.`poids`, pc.`id_user` AS user_maj, pc.`date` AS date_maj, pc.`id_user` AS user_add, pc.`date` AS date_add, "Hors Stock" AS type_froid_nom, 0 AS froid_code, 0 AS id_froid, "" AS quantieme, pc.`id_palette`, pal.`numero` AS numero_palette, pc.`id` AS id_compo,
        					"" AS user_debut,
        					"" AS user_fin,
        					0 AS is_froid
		 					FROM `pe_palette_composition` pc
 								LEFT JOIN `pe_palettes` pal ON pal.`id` = pc.`id_palette`
 								JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` 
								JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl`
		 					WHERE bll.`supprime` = 0 AND bl.`supprime` = 0 ';
		$query_liste.= $id_lot > 0 ? 'AND pc.`id_lot_hors_stock` = ' . $id_lot . ' ' : '';
		$query_liste.= 'ORDER BY ';
		$query_liste.= $orderByFroid ?  'is_froid, ' : ' ';
		$query_liste.= ' pc.`id_produit` ASC ';
		$query_liste.= ')';		
		echo '--<br>';
		$query = $this->db->prepare($query_liste);
		$query->execute();
		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];
		$produitsManager = new ProduitManager($this->db);
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$froidPdt = new FroidProduit($donnee);
			// On intègre l'objet produit

			$pdt = $produitsManager->getProduit($froidPdt->getId_pdt());
			if ($pdt instanceof Produit) {
				$froidPdt->setProduit($pdt);
			}
			$liste[] = $froidPdt;
		}

		return $liste;

	} // FIN getListe

	// Retoune le poids total des produits d'un lot
	public function getPoidsLot($id_lot) {

		$query_poids = 'SELECT ';
		$query_poids.= '(SELECT IFNULL(SUM(fp.`poids`),0) FROM `pe_froid_produits` fp LEFT JOIN `pe_palette_composition` pc ON pc.`id_lot_pdt_froid` = fp.`id_lot_pdt_froid` WHERE (pc.`supprime` IS NULL || pc.`supprime` = 0 || pc.`archive` = 1) AND fp.`id_lot` = ' . intval($id_lot) .') + ';
		$query_poids.= '(SELECT IFNULL(SUM(pc.`poids`),0) FROM `pe_palette_composition` pc JOIN `pe_frais` f ON f.`id` = pc.`id_frais` WHERE  (pc.`supprime` = 0 OR pc.`archive` = 1) AND f.`id_lot` = ' . intval($id_lot).') +';
		$query_poids.= '(SELECT IFNULL(SUM(pc.`poids`),0) FROM `pe_palette_composition` pc WHERE  (pc.`supprime` = 0 OR pc.`archive` = 1) AND pc.`id_lot_hors_stock` = ' . intval($id_lot).') AS poids_total';
		$query = $this->db->prepare($query_poids);
		$query->execute();
		$donnee = $query->fetch();
		return !$donnee || empty($donnee) ? '&mdash;' : $donnee['poids_total'];

	} // FIN méthode

	// Retourne les totaux d'une recherche (v1.3)
	public function getFroidsHistoriqueRechercheTotaux($params) {

		$filtre_lot 	= isset($params['lot']) 	 	? preg_replace("/[^a-zA-Z0-9]/", "", $params['lot'])      : '';
		$filtre_froid 	= isset($params['froid']) 	 	? preg_replace("/[^a-zA-Z0-9]/", "", $params['froid'])    : '';
		$filtre_produit = isset($params['produit']) 	? preg_replace("/[^a-zA-Z0-9]/", "", $params['produit'])  : '';
		$filtre_date 	= isset($params['date']) 	 	? preg_replace("/[^a-zA-Z0-9]/", "", $params['date'])     : '';
		$filtre_palette = isset($params['palette'])  	? intval(preg_replace("/[^0-9]/", "", $params['palette'])) : 0;

		$query_liste = 'SELECT SUM(fp.`nb_colis`) AS nb_colis, SUM(fp.`poids`) AS poids
							FROM `pe_froid_produits` fp 
								LEFT JOIN `pe_froid` 		f ON f.`id` 	= fp.`id_froid`
								LEFT JOIN `pe_froid_types` ft ON ft.`id` 	= f.`id_type`
								LEFT JOIN `pe_lots`			l ON l.`id` 	= fp.`id_lot`
								LEFT JOIN `pe_produits` 	p ON p.`id` 	= fp.`id_pdt`
								LEFT JOIN `pe_produit_trad` pt ON pt.`id_produit` = p.`id` AND pt.`id_langue` = 1
						WHERE 1 ';

		$query_liste.= $filtre_lot 	 	!= '' ? 'AND l.`numlot` LIKE "%' . $filtre_lot . '%" ' : '';
		$query_liste.= $filtre_froid 	!= '' ? 'AND CONCAT(LOWER(ft.`code`),LPAD(fp.`id_froid`, 4, 0))  LIKE "%' . $filtre_froid . '%" ' : '';
		$query_liste.= $filtre_produit 	!= '' ? 'AND LOWER(pt.`nom`) LIKE "%' . $filtre_produit . '%" ' : '';
		$query_liste.= $filtre_date 	!= '' ? 'AND (DATE(f.`date_entree`) = "' . $filtre_date . '" OR DATE(f.`date_sortie`) = "' . $filtre_date . '") ' : '';
		$query_liste.= $filtre_palette 	 > 0  ? 'AND fp.`id_palette` = ' . $filtre_palette . ' ' : '';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$donnees = $query->fetch(PDO::FETCH_ASSOC);

		if (!$donnees || !is_array($donnees) || !isset($donnees['nb_colis'])) { return false; }

		return $donnees;

	} // FIN méthode

	// Retourne la liste des produits froids d'apreès recherche historisée (vue FO)
	public function getFroidsHistoriqueRecherche($params) {

		$filtre_lot 	= isset($params['lot']) 	 	? preg_replace("/[^a-zA-Z0-9]/", "", $params['lot'])      : '';
		$filtre_froid 	= isset($params['froid']) 	 	? preg_replace("/[^a-zA-Z0-9]/", "", $params['froid'])    : '';
		$filtre_produit = isset($params['produit']) 	? preg_replace("/[^a-zA-Z0-9]/", "", $params['produit'])  : '';
		$filtre_date 	= isset($params['date']) 	 	? preg_replace("/[^a-zA-Z0-9]/", "", $params['date'])     : '';
		$filtre_palette = isset($params['palette']) 	?  intval(preg_replace("/[^0-9]/", "", $params['palette'])) : 0;

		// Pagination
		$start 			= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 			= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		// Tri
		$order_by		= isset($params['colonne']) 		? trim(strtolower($params['colonne'])) 	: 'pdt';
		$sens 			= isset($params['sens']) 			? trim(strtolower($params['sens'])) 	: 'asc';


		if ($order_by == 'pdt') {
			$order_by_sql ='t.`nom` '.strtoupper($sens).', fp.`id_lot`, fp.`quantieme`';
		} else if ($order_by == 'lot') {
			$order_by_sql ='fp.`id_lot` '.strtoupper($sens).', fp.`quantieme`, t.`nom`';
		} else {
			$order_by_sql ='fp.`id_lot`,  fp.`id_pdt` ' . strtoupper($sens);
		}

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS fp.`id_lot_pdt_froid`, fp.`id_lot`, fp.`id_pdt`, fp.`nb_colis`, fp.`poids`, fp.`user_maj`, fp.`date_maj`,  fp.`user_add`, fp.`date_add`, ft.`nom` AS type_froid_nom, 
                           	fp.`id_palette`, pal.`numero` AS numero_palette, ft.`code` AS froid_code, fp.`id_froid`, fp.`quantieme`, l.`numlot`, 
                           CONCAT(LOWER(ft.`code`),LPAD(fp.`id_froid`, 4, 0)) AS code_traitement, ft.`nom` AS nom_traitement, fp.`etiquetage`
							FROM `pe_froid_produits` fp 
								LEFT JOIN `pe_froid` 		f ON f.`id` 	= fp.`id_froid`
								LEFT JOIN `pe_froid_types` ft ON ft.`id` 	= f.`id_type`
								LEFT JOIN `pe_lots`			l ON l.`id` 	= fp.`id_lot`
								LEFT JOIN `pe_produits` 	p ON p.`id` 	= fp.`id_pdt`
								LEFT JOIN `pe_palettes`   pal ON pal.`id` 	= fp.`id_palette`
								LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = p.`id` AND t.`id_langue` = 1 
						WHERE 1 ';

		$query_liste.= $filtre_lot 	 	!= '' ? 'AND l.`numlot` LIKE "%' . $filtre_lot . '%" ' : '';
		$query_liste.= $filtre_froid 	!= '' ? 'AND CONCAT(LOWER(ft.`code`),LPAD(fp.`id_froid`, 4, 0))  LIKE "%' . $filtre_froid . '%" ' : '';
		$query_liste.= $filtre_produit 	!= '' ? 'AND LOWER(t.`nom`) LIKE "%' . $filtre_produit . '%" ' : '';
		$query_liste.= $filtre_palette 	 >  0 ? 'AND fp.`id_palette` = ' . $filtre_palette . ' ' : '';
		$query_liste.= $filtre_date 	!= '' ? 'AND (DATE(f.`date_entree`) = "' . $filtre_date . '" OR DATE(f.`date_sortie`) = "' . $filtre_date . '") ' : '';
		$query_liste.= $filtre_palette 	 > 0  ? 'AND fp.`id_palette` = ' . $filtre_palette . ' ' : '';

		$query_liste.= 'ORDER BY ' . $order_by_sql . ' ';
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];
		$produitsManager = new ProduitManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$froidPdt = new FroidProduit($donnee);

			// On intègre l'objet produit
			$pdt = $produitsManager->getProduit($froidPdt->getId_pdt());
			if ($pdt instanceof Produit) {
				$froidPdt->setProduit($pdt);
			}

			// On intègre l'objet Froid
			$froid = $this->getFroid($froidPdt->getId_froid());
			if ($froid instanceof Froid) {
				$froidPdt->setFroid($froid);
			}

			$liste[] = $froidPdt;
		}
		return $liste;

	} // FIN méthode

	// Supprime un FroidProduit en base (admin Bo avant historisation)
	public function supprFroidProduit(FroidProduit $froidProduit) {

		$query_del = 'DELETE FROM `pe_froid_produits` WHERE `id_lot_pdt_froid` = ' . $froidProduit->getId_lot_pdt_froid();
		$query = $this->db->prepare($query_del);
		if (!$query->execute()) { return false; }
		Outils::saveLog($query_del);

		$logManager = new LogManager($this->db);
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Suppression du produit pdt_froid #'.$froidProduit->getId_lot_pdt_froid().' et des compositions associées via admin Bo avant historisation');
		$logManager->saveLog($log);

		$query_del2 = 'DELETE FROM `pe_palette_composition` WHERE `id_lot_pdt_froid` = ' . $froidProduit->getId_lot_pdt_froid();
		$query2 = $this->db->prepare($query_del2);
		Outils::saveLog($query_del2);
		return $query2->execute();


	} // FIN méthode

	// On étiquette tous les produits au lancement du traitement
	public function etiquetteTout(Froid $froid) {

		$query_upd = 'UPDATE `pe_froid_produits` SET `etiquetage` = 1 WHERE `id_froid` = ' . $froid->getId();
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();

	} // FIN méthode

	// Retourne la liste des produitsFroid en attente pour la vue
	public function getProduitsFroidsEnAttente($code_vue) {

		$codes_vue = '';
		foreach (explode(',', $code_vue) as $cdevue) {
			$codes_vue.= '"'.$cdevue.'",';
		}
		if (substr($codes_vue,-1) == ',') {
			$codes_vue = substr($codes_vue,0,-1);
		}

		$query_liste = 'SELECT  fp.`id_lot_pdt_froid`, fp.`id_lot`, fp.`id_pdt`,  fp.`nb_colis`,  fp.`poids`, ft.`nom` AS type_froid_nom, l.`numlot`, fp.`etiquetage`, fp.`quantieme`, fp.`id_palette`
							FROM `pe_froid_produits` fp
								JOIN `pe_froid` f ON f.`id` = fp.`id_froid`
								JOIN `pe_froid_types` ft ON ft.`id` = f.`id_type`
								JOIN `pe_lots` l ON l.`id` = fp.`id_lot`
								JOIN `pe_produits` p ON p.`id` = fp.`id_pdt` 
						WHERE fp.`attente` = 1 AND ft.`code` IN ('.$codes_vue.')
 						ORDER BY  fp.`id_lot_pdt_froid` ' ;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		$produitsManager = new ProduitManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$froidPdt = new FroidProduit($donnee);

			// On supprimes les produits en attente devenus vides
			if ($froidPdt->getNb_colis() < 1 || $froidPdt->getPoids() <= 0) {
				$this->supprFroidProduit($froidPdt);
				continue;
			}

			// On intègre l'objet produit
			$pdt = $produitsManager->getProduit($froidPdt->getId_pdt());
			if ($pdt instanceof Produit) {
				$froidPdt->setProduit($pdt);
			}
			$liste[] = $froidPdt;
		}
		return $liste;

	} // FIN méthode

	// Retourne un FroidProduit ayant les mêmes valeurs que celles précisées (mais pas en attente)
	public function getFroidProduitObjetIdem($id_lot, $id_pdt, $id_froid, $quantieme, $id_palette) {

		$query_fpdt = 'SELECT `id_lot_pdt_froid` FROM `pe_froid_produits` 
						  WHERE `attente`    = 0
  				          AND   `id_lot`     = ' . intval($id_lot)     . '
						  AND   `id_pdt`     = ' . intval($id_pdt)     . '
						  AND   `id_froid`   = ' . intval($id_froid)   . '
						  AND   `quantieme`  = ' . intval($quantieme)  . '
						  AND   `id_palette` = ' . intval($id_palette) . '
					   LIMIT 0,1';

		$query = $this->db->prepare($query_fpdt);
		$query->execute();
		$donnee = $query->fetch();
		if (!$donnee || empty($donnee)) { return false; }

		return $this->getFroidProduitObjetByIdLotPdtFroid($donnee['id_lot_pdt_froid']);

	} // FIN méthode

// Retourne un produit de traitement froid (objet FroidProduit) - par ids séparés
    public function getFroidProduitObjetSansFroid($id_lot, $id_pdt, $id_froid = 0, $quantieme = '', $id_palette = 0) {

        $query_fpdt = 'SELECT `id_lot_pdt_froid`
							FROM `pe_froid_produits` 
						WHERE `id_lot` = ' . intval($id_lot) . ' AND `id_pdt` = ' . intval($id_pdt);

        $query_fpdt.= intval($id_froid) > 0  ? ' AND `id_froid` = "' . intval($id_froid) . '"' : '';
        $query_fpdt.= $quantieme != '' ? ' AND `quantieme` = "' . $quantieme . '"' : '';
        $query_fpdt.= $id_palette > 0  ? ' AND `id_palette` = "' . $id_palette . '"' : '';

        $query = $this->db->prepare($query_fpdt);
        $query->execute();
        $donnee = $query->fetch();
        if (!$donnee || empty($donnee)) { return false; }

        return $this->getFroidProduitObjetByIdLotPdtFroid(intval($donnee['id_lot_pdt_froid']));

    } // FIN méthode


	public function getQuantiteFroid(Froid $froid) {
		$query_nb = 'SELECT SUM(CASE WHEN fp.`quantite` IS NOT NULL THEN fp.`quantite` ELSE 0 END) AS nb FROM `pe_froid_produits` fp WHERE fp.`attente` = 0 AND fp.`id_froid`= :id_froid ';
		$query = $this->db->prepare($query_nb);
		$query->bindValue(':id_froid', $froid->getId());
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && !empty($donnee) && isset($donnee['nb']) ? floatval($donnee['nb']) : 0;

	} // FIN méthode
} // FIN classe