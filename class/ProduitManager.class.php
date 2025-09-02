<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Manager de l'Objet Produit
------------------------------------------------------*/
class ProduitManager {

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

	// Retourne un produit par son ID
	public function getProduit($id, $details = true) {
		$query_produit = 'SELECT p.`id`, t.`nom`, p.`nom_court`, p.`code`, p.`ean13`, p.`ean14`, p.`id_espece`, p.`poids`, p.`actif`, p.`supprime`, p.`date_add`, p.`date_maj`, f.`nom` AS nom_espece, p.`nb_colis`,  p.`nb_jours_dlc`, p.`mixte`, p.`palette_suiv`, p.`id_taxe`, p.`pdt_gros`, p.`stats`, p.`id_pdt_emballage`, p.`pcb`, p.`poids_unitaire`, p.`ean7`, p.`ean7_type`, p.`vrac`, p.`id_client`, p.`vendu_piece`,  p.`id_poids_palette`, p.`nomenclature`,p.`vendu_negoce` 
							FROM `pe_produits` p
								LEFT JOIN `pe_produits_especes` f ON f.`id` = p.`id_espece`
								LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = p.`id` AND t.`id_langue` = 1
						  WHERE p.`id` = :id';
		$query = $this->db->prepare($query_produit);
		$query->bindValue(':id', (int)$id);
		$query->execute();

		$donnee = $query->fetch();

		$pdt = $donnee && !empty($donnee) ? new Produit($donnee) : false;

		if ($pdt instanceof Produit && $details) {

			// On rattache les noms traduits
			$noms = $this->getNomsProduitsTrad($pdt->getId());
			$pdt->setNoms($noms);

			$categoriesManager = new ProduitCategoriesManager($this->db);

			$query_froids = 'SELECT `id_froid_type` FROM `pe_produits_froid_types` WHERE `id_pdt` = ' . $pdt->getId();
			$query_f = $this->db->prepare($query_froids);
			$query_f->execute();
			$froids = [];
			foreach ($query_f->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

				$froids[] = $donnee['id_froid_type'];
			}
			$pdt->setFroids($froids);
			$pdt->setVrai_poids($pdt->getPoids());;

			// Si le poids par défaut a été mis à 0 on prends le poids configuré par défaut dans les paramètres
			if ($pdt->getPoids() < 1) {

				$configManager = new ConfigManager($this->db);
				$poidsDefautObj = $configManager->getConfig('poids_defaut');

				// On anticipe aussi si la valeur en config à été mise à 0
				if (!$poidsDefautObj instanceof Config) { $poidsDefaut = 1; } else {
					$poidsDefaut = $poidsDefautObj->getValeur();
				}

				if ($poidsDefaut < 1) {
					// On doit pouvoir mettre le poids à zéro, donc :
					$pdt->setVrai_poids(0);;
					$poidsDefaut = 1;
				}

				$pdt->setPoids($poidsDefaut);
			}

			// Récupération des catégories liées
			$pdt->setCategories($categoriesManager->getCategoriesByProduit($pdt));

			$ids_familles = $this->getIdsFamillesEmballagePdt($pdt->getId());
			if (!is_array($ids_familles)) { $ids_familles = []; }
			$pdt->setIds_familles_emballages($ids_familles);

		}
		return $pdt;

	} // FIN méthode

	// Retourne un produit par son EAN13
	public function getProduitByEan($ean13) {

		$query_produit = 'SELECT p.`id`, t.`nom`, p.`nom_court`, p.`code`, p.`ean13`, p.`ean14`, p.`id_espece`, p.`poids`, p.`actif`, p.`supprime`, p.`date_add`, p.`date_maj`, f.`nom` AS nom_espece, p.`nb_colis`, p.`nb_jours_dlc`, p.`mixte`, p.`palette_suiv`, p.`id_taxe`, p.`pdt_gros`, p.`stats`, p.`id_pdt_emballage`, p.`pcb`, p.`poids_unitaire`,  p.`ean7`, p.`ean7_type`, p.`vrac`, p.`id_client`, p.`vendu_piece`, p.`id_poids_palette`, p.`nomenclature`
							FROM `pe_produits` p
								LEFT JOIN `pe_produits_especes` f ON f.`id` = p.`id_espece`
								LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = p.`id` AND t.`id_langue` = 1
						  WHERE p.`ean14` = "'.$ean13.'"';
		$query = $this->db->prepare($query_produit);
		$query->execute();

		$donnee = $query->fetch();

		$pdt = $donnee && !empty($donnee) ? new Produit($donnee) : false;

		return $pdt;

	} // FIN méthode

	// Retourne la liste des produits
	public function getListeProduits($params = []) {

		// Limites sur statuts
		$show_inactifs 		= isset($params['show_inactifs']) 	? $params['show_inactifs'] 			: false;
		$show_supprime 		= isset($params['show_supprime']) 	? $params['show_supprime'] 			: false;

		// Filtres
		$filtre_nom 		= isset($params['nom'])    			? trim(strtolower($params['nom'])) 			: '';
		$filtre_nom_court	= isset($params['nom_court'])   	? trim(strtolower($params['nom_court'])) 	: '';
		$filtre_espece  	= isset($params['id_espece']) 		? intval($params['id_espece']) 				: 0;
		$filtre_categorie 	= isset($params['id_categorie'])	? intval($params['id_categorie']) 			: 0;
		$filtre_vue			= isset($params['vue']) 			? intval($params['vue']) 					: 0;
		$filtre_actif 		= isset($params['actif']) 			? intval($params['actif']) 					: 0;

		// Récupération des ID de familles d'emballage
		$get_ids_fam_emb 	= isset($params['get_ids_fam_emb']) ? boolval($params['get_ids_fam_emb']) 		: false;

		// Pagination
		$start 				= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 				= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		// Tri
		$orderby_champ = isset($params['orderby_champ']) ? $params['orderby_champ'] : 't.`nom`';
		$orderby_sens = isset($params['orderby_sens']) ? $params['orderby_sens'] : 'ASC';

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS 
							p.`id`,
							p.`pcb`, 
							t.`nom`, 
							p.`nom_court`, 
							p.`code`, 
							p.`ean13`, 
							p.`ean14`, 
							p.`id_espece`, 
							p.`poids`, 
							p.`actif`, 
							p.`supprime`, 
							p.`date_add`, 
							p.`date_maj`, 
							f.`nom` AS nom_espece, 
							p.`nb_colis`,  
							p.`vendu_negoce`, 
							p.`nb_jours_dlc`, 
							p.`mixte`, 
							p.`palette_suiv`,
							p.`ean7`, 
							p.`ean7_type`, 
							p.`vrac`, 
							p.`id_client`,
							p.`vendu_piece`, 
							p.`id_poids_palette`, 
							p.`nomenclature`
						FROM 
							`pe_produits` p 
							LEFT JOIN `pe_produits_especes` f ON f.`id` = p.`id_espece` 
							LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = p.`id` AND t.`id_langue` = 1 ';
		$query_liste.= $filtre_vue != 0 ? ' LEFT JOIN `pe_produits_froid_types` pft ON pft.`id_pdt` = p.`id` ' : '';
		$query_liste.= '	WHERE 1 = 1 ';

		// Filtres
		$query_liste.= $filtre_nom   != ''  ? 'AND (LOWER(t.`nom`) LIKE "%'.$filtre_nom.'%"  OR p.`code` LIKE "%'.$filtre_nom.'%" OR p.`ean13` LIKE "%'.$filtre_nom.'%" OR p.`ean14` LIKE "%'.$filtre_nom.'%") ' : '';
		$query_liste.= $filtre_nom_court != ''  ? 'AND TRIM(LOWER(p.`nom_court`)) = "'.trim(strtolower($filtre_nom_court)).'" ' : '';
		$query_liste.= $filtre_vue  > 0  ? 'AND pft.`id_froid_type` = '.$filtre_vue.' ' 	: '';
		$query_liste.= $filtre_vue  < 0  ? 'AND pft.`id_froid_type` IS NULL ' 	: '';
		$query_liste.= $filtre_actif == 1  ? 'AND p.`actif` = 1 ' 			: '';
		$query_liste.= $filtre_actif == -1  ? 'AND p.`actif` = 0 ' 			: '';
		$query_liste.= $filtre_espece > 0  ? 'AND p.`id_espece` = '.$filtre_espece.' ' 	: '';

		// Limites sur statuts
		$query_liste.= !$show_inactifs != ''  && $filtre_actif == '' ? 'AND p.`actif` = 1 AND f.`actif` = 1 ' 	: '';
		$query_liste.= !$show_supprime != ''  ? 'AND p.`supprime`  = 0  AND f.`supprime` = 0 ' 					: '';

		// Tri et pagination
		$query_liste.= 'ORDER BY '.$orderby_champ.' ' . $orderby_sens . ', p.`id` ';
		$query_liste.= $filtre_categorie == 0 ? 'LIMIT ' . $start . ',' . $nb : '';

		// Requête
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		$categoriesManager = new ProduitCategoriesManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$tmp = new Produit($donnee);
			
			// Récupération des catégories liées
			$tmp->setCategories($categoriesManager->getCategoriesByProduit($tmp));

			// Filtre sur les catégories (liste des produits)
			if ($filtre_categorie > 0) {
				$trouve = false;
				foreach ($tmp->getCategories($tmp) as $cate) {
					if ($filtre_categorie == $cate->getId()) {
						$trouve = true;
					}
				}
				if (!$trouve) { continue; }
			}

			// On rattache les noms traduits
			$noms = $this->getNomsProduitsTrad($tmp->getId());
			$tmp->setNoms($noms);

			// On rattache les ID de familles d'emballage si besoin
			if ($get_ids_fam_emb) {

				$ids_familles = $this->getIdsFamillesEmballagePdt($tmp->getId());
				if (!is_array($ids_familles)) { $ids_familles = []; }
				$tmp->setIds_familles_emballages($ids_familles);

			} // FIN rattachement IDs de familles d'emballages

			$liste[] = $tmp;
		}

		return $liste;

	} // FIN getListe

	// Recherche un produit par code
	public function searchProduitsByCode($code, $like = false) {

		$query_pdt = 'SELECT p.`id`, t.`nom`, p.`code`, p.`ean13`, p.`ean14`, p.`id_espece`, p.`poids`, p.`actif`, p.`supprime`, p.`date_add`, p.`date_maj`, f.`nom` AS nom_espece, p.`nb_colis`, p.`nb_jours_dlc`, p.`mixte`, p.`palette_suiv`, p.`id_taxe`, p.`pdt_gros`, p.`pcb`, p.`poids_unitaire`,  p.`ean7`, p.`ean7_type`, p.`vrac`, p.`id_client`, p.`vendu_piece`, p.`id_poids_palette`,
       p.`nomenclature`
							FROM `pe_produits` p 
							LEFT JOIN `pe_produits_especes` f ON f.`id` = p.`id_espece`
							LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = p.`id` AND t.`id_langue` = 1 
						WHERE ';

		$query_pdt.= $like
			? ' (p.`code` LIKE "%'.$code.'%" OR p.`ean13` LIKE "%'.$code.'%" OR p.`ean14` LIKE "%'.$code.'%") '
			: ' (p.`code` = '.$code.' OR p.`ean13` = '.$code.' OR p.`ean14` = '.$code.') ';

		$query_pdt.= '		AND p.`actif` = 1
							AND p.`supprime` =0
							AND f.`id` IS NOT NULL
							AND f.`actif` = 1
							AND f.`supprime` = 0 
						ORDER BY t.`nom`, p.`nom_court` ASC';
		$query = $this->db->prepare($query_pdt);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$tmp = new Produit($donnee);

			// On rattache les noms traduits
			$noms = $this->getNomsProduitsTrad($tmp->getId());
			$tmp->setNoms($noms);

			$liste[] = $tmp;
		}

		return $liste;

	} // FIN méthode

	
	// Enregistre un nouveau produit
	public function saveProduit(Produit $objet) {
		
		$table		= 'pe_produits';	// Nom de la table
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


	// Vérifie si un produit existe déjà avec ce code
	public function checkExisteDeja($id_exclu, $ean7, $ean13, $ean14) {

		if (trim($ean13) == '' || intval($ean13) == 0) { $ean13 = 'TESTUNICITE';}
		if (trim($ean14) == '' || intval($ean14) == 0) { $ean14 = 'TESTUNICITE';}

		$query_test = 'SELECT COUNT(*) AS nb FROM `pe_produits` WHERE `id` != ' . (int)$id_exclu . ' AND 
							(`ean13` = "'.$ean13.'" OR `ean14` = "'.$ean14.'") ';

		$query = $this->db->prepare($query_test);
		$query->execute();

		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		if (!$donnee || !isset($donnee['nb'])) { return false; }

		return intval($donnee['nb']) > 0;

	} // FIN méthode

	// Retourne le nombre de produits rattachés à une opération de froid (congélation/surgélation)
	public function getNbProduitsFroid($id_froid) {

		$query_nb = 'SELECT COUNT(*) AS nb	FROM `pe_froid_produits` WHERE `id_froid` = ' . (int)$id_froid;
		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);

		return $donnee && isset($donnee[0]) && isset($donnee[0]['nb']) ? intval($donnee[0]['nb']) : 0;

	} // FIN méthode

	// Enregistre les types de froids associés à un produit
	public function saveFroidsProduit(Produit $produit, $froids) {

		if (!is_array($froids)) { return false; }

		$query_del = 'DELETE FROM `pe_produits_froid_types` WHERE `id_pdt` = ' . $produit->getId();
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		$query->execute();

		foreach ($froids as $froid) {
			$query_ins = 'INSERT INTO `pe_produits_froid_types` (`id_pdt`, `id_froid_type`) VALUES ('.$produit->getId().', '.$froid.')';
			$query = $this->db->prepare($query_ins);
			$query->execute();
			Outils::saveLog($query_ins);
		}

		return true;

	} // FIN méthode


	// Retourne la liste unique des noms courts utilisés
	public function getListeNomsCourts() {

		$query_liste = 'SELECT DISTINCT `nom_court` FROM `pe_produits` WHERE `supprime` = 0 ORDER BY `nom_court` ASC';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			if (strlen(trim($donnee['nom_court']) ) == 0) { continue; }
			$liste[] = trim($donnee['nom_court']);
		}

		return $liste;


	} // FIN méthode

	// Retourne la liste unique des produits noms courts relevants d'une catégorie
	public function getProduitsNomsCourtsByCategorie($id_categorie) {

		$query_liste = 'SELECT DISTINCT p.`nom_court`
							FROM `pe_produits_categories_pdt` cp
								JOIN `pe_produits` p ON p.`id` = cp.`id_produit` 
						WHERE cp.`id_categorie` = ' . intval($id_categorie) .' 
						ORDER BY p.`nom_court` ASC ';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			if (strlen(trim($donnee['nom_court']) ) == 0) { continue; }
			$liste[] = trim($donnee['nom_court']);
		}

		return $liste;

	} // FIN méthode

	// Réparre les éventuels écarts entre poids produit froid et poids de la composition pour le même id_lot_pdt_froid
	public function cleanCoherencePoids() {

		// Tous les produits où il y a une différence (ça peut être aussi parce qu'il y a plusieurs lignes dans la composition)
		$query_liste = 'SELECT pc.`id_lot_pdt_froid`, SUM(pc.`poids`) AS total_palette, fp.`poids`, fp.`nb_colis`
                        FROM `pe_palette_composition` pc
                            JOIN `pe_froid_produits` fp ON fp.`id_lot_pdt_froid` = pc.`id_lot_pdt_froid`
                            GROUP BY pc.`id_lot_pdt_froid`, fp.`poids`, fp.`nb_colis`
                        HAVING fp.`poids` != total_palette
                        ';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$tmp = [];
			$tmp['poids'] = floatval($donnee['poids']);
			$tmp['nb_colis'] = intval($donnee['nb_colis']);
			$liste[intval($donnee['id_lot_pdt_froid'])] = $tmp;
		}

		// Tout est ok...
		if (empty($liste)) { return true; }

		// Il y a des différences...
		$logsManager = new LogManager($this->db);

		// On boucle sur les id_lot_pdt_froid pour analyse...
		foreach ($liste as $id_lot_pdt_froid => $donnees ) {

			$poids 		= isset($donnees['poids'])		? $donnees['poids'] 	: false;
			$nb_colis 	= isset($donnees['nb_colis']) 	? $donnees['nb_colis'] 	: false;
			if (!$poids || !$nb_colis) { continue; }

			$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_palette_composition` WHERE `id_lot_pdt_froid` = '.(int)$id_lot_pdt_froid;

			$query = $this->db->prepare($query_nb);
			$query->execute();
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			$nb = $donnee && isset($donnee[0]) && isset($donnee[0]['nb']) ? intval($donnee[0]['nb']) : 0;

			// Si on a qu'une seule composition de palette et que celle-ci est donc différente, on update
			if ($nb == 1) {

				$query_upd = 'UPDATE `pe_palette_composition` SET `poids` = ' . $poids . ', `nb_colis` = ' . $nb_colis . ' WHERE `id_lot_pdt_froid` = ' . $id_lot_pdt_froid;
				$queryU = $this->db->prepare($query_upd);
				if ($queryU->execute()) {
					Outils::saveLog($query_upd);
					$log = new Log([]);
					$log->setLog_type('warning');
					$log->setLog_texte('Nettoyage auto de composition palette incohérente sur id_lot_pdt_froid #' . $id_lot_pdt_froid);
					$logsManager->saveLog($log);
				}

			// Si on en a plusieurs...
			} else {

				// On supprime les lignes en palette qui ne correspondent pas au total, comme ça on a forcément en palette le même total qu'en prod ou alors on a plus rien en palette
				$query_del = 'DELETE FROM `pe_palette_composition` WHERE `id_lot_pdt_froid` = ' . $id_lot_pdt_froid . ' AND (`poids` != ' . $poids . ' OR  `nb_colis` != ' . $nb_colis . ')';
				$queryD = $this->db->prepare($query_del);
				if ($queryD->execute()) {
					Outils::saveLog($query_del);
					$log = new Log([]);
					$log->setLog_type('warning');
					$log->setLog_texte('Nettoyage auto de composition palette incohérente (suppression car plusieurs lignes) sur id_lot_pdt_froid #' . $id_lot_pdt_froid);
					$logsManager->saveLog($log);
				}

			} // FIN test nb de lignes compo pour ce produit

			// Reste le cas si il y avait plusieurs lignes idem en composition avec chacune le total de la prod
			if ($nb > 1) {
				$query_verif = 'SELECT `id` FROM  `pe_palette_composition` WHERE `id_lot_pdt_froid` = ' . $id_lot_pdt_froid . '  AND `poids` = ' .$poids . ' AND `nb_colis` = ' . $nb_colis;
				$queryV = $this->db->prepare($query_verif);
				$queryV->execute();

				$listeDoublonsRestants = [];

				foreach ($queryV->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
					$listeDoublonsRestants[] = intval($donnee['id']);
				}

				// Si il y en a
				if (count($listeDoublonsRestants) > 1) {

					// On retire le premier ID qu'on conservera
					$id_a_supprimer = array_shift($listeDoublonsRestants);
					if (!is_array($id_a_supprimer)) {
						$id_a_supprimer = [$id_a_supprimer];
					}

					// On supprime tous sauf la premiere pour n'en conserver qu'une
					$query_del2 = 'DELETE FROM  `pe_palette_composition`  WHERE `id_lot_pdt_froid` = ' . $id_lot_pdt_froid . ' AND `id` IN (' . implode(',', $id_a_supprimer) . ')';

					$queryD2 = $this->db->prepare($query_del2);
					if ($queryD2->execute()) {
						Outils::saveLog($query_del2);
						$log = new Log([]);
						$log->setLog_type('warning');
						$log->setLog_texte('Nettoyage auto de composition palette incohérente (suppression car plusieurs lignes identiques) sur id_lot_pdt_froid #' . $id_lot_pdt_froid);
						$logsManager->saveLog($log);
					}

				}
			}

		} // FIN boucle sur les id_lot_pdt_froid

		return false;

	} // FIN méthode

	public function cleanBlLignesSupprimees() {

		$query_upd = 'UPDATE `pe_bl_lignes` SET `supprime` = 1 WHERE `supprime` = 0  AND `id_bl` IN (SELECT `id` FROM `pe_bl` WHERE `supprime` = 1)';
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();

	} // FIN méthode

	// Retourne les produits hors stock d'un lot
	public function getProduitsHorsStockByLot($id_lot) {

		if (intval($id_lot) == 0) { return []; }
		$query_liste = 'SELECT pc.`id_produit`, t.`nom` AS nom_produit, pc.`id_client`, clt.`nom` AS nom_client, pc.`id_palette`, IFNULL(pal.`numero`, 0) AS numero_palette, pc.`nb_colis`, pc.`poids`, pc.`id` AS id_compo, pc.`id_lot_regroupement`, "" AS numlot_regroupement, pc.`designation`, pdt.`palette_suiv`, pc.`id_frais`,
                           		IFNULL(bl.`id`, 0) AS id_bl,
                           		IF (bl.`id` IS NOT NULL,num_bl,"") AS num_bl,
       							pc.`id_lot_hors_stock` AS id_lot,
       							"" AS quantieme,
       							0 AS id_froid,
       							IF (lots.`numlot` IS NOT NULL, lots.`numlot` , "") AS numlot,
       							0 AS id_type_froid,
       							"HORS STOCK" AS code_froid,
                           		"" AS date_dlc,
                           		pdt.`code` AS code_produit,
                          		pc.`date` AS date_froid
							FROM `pe_palette_composition` pc
								JOIN `pe_produits` pdt ON pdt.`id` = pc.`id_produit`
								JOIN `pe_tiers` clt ON clt.`id` = pc.`id_client`
								LEFT JOIN `pe_palettes` pal ON pal.`id` = pc.`id_palette`
								LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = pdt.`id` AND t.`id_langue` = 1
						    	LEFT JOIN `pe_lots` lots ON lots.`id` = pc.`id_lot_hors_stock`
								JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` 
								JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl` AND bl.`statut` > 0 
							WHERE (pc.`supprime` = 0 OR pc.`archive` = 1) AND pc.`id_lot_hors_stock` = ' . (int)$id_lot . '
							 AND bll.`id` IS NOT NULL AND bll.`supprime` = 0 AND bl.`supprime` = 0 ';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$tmp = new ProduitStock($donnee);

			// On rattache les noms traduits
			$noms = $this->getNomsProduitsTrad($tmp->getId_produit());
			$tmp->setNoms_produit($noms);

			$liste[] =$tmp;
		}

		return $liste;

	} // FIN méthode

	// Retourne les produits en stock
	public function getProduitsStock($params = []) {

		$filtre_journee = isset($params['journee']);
		$filtre_froid 	= isset($params['froid']) 	? intval($params['froid']) 	 : 0;
		$filtre_client 	= isset($params['client']) 	? intval($params['client'])  : 0;
		$filtre_produit = isset($params['produit']) ? intval($params['produit']) : 0;
		$filtre_produits = isset($params['produits']) ? trim($params['produits']) : '';
		$filtre_palette = isset($params['palette']) ? intval($params['palette']) : 0;
		$filtre_palettes = isset($params['palettes']) ? trim($params['palettes']) : '';
		$filtre_lot 	= isset($params['lot']) 	? intval($params['lot']) 	 : 0;
		$filtre_lot_r 	= isset($params['lot_r']) 	? intval($params['lot_r']) 	 : 0;
		$filtre_lot_n 	= isset($params['lot_n']) 	? intval($params['lot_n']) 	 : 0;
		$ids_compos 	= isset($params['ids']) 	? $params['ids'] 			 : '';
		$pdts_frais 	= isset($params['frais']) 	? boolval($params['frais'])  : false;
		$en_bl 			= isset($params['en_bl']) ? boolval($params['en_bl'])  : false;
		$hors_bl 		= isset($params['hors_bl']) ? boolval($params['hors_bl'])  : false;
		$hors_frais		= isset($params['hors_frais']) ? boolval($params['hors_frais'])  : false;

		if ($filtre_lot_n > 0) {return []; } // Si on a filtré sur un lot de négoce, on ne l'aura pas ici...
		if ($filtre_froid < 0) {return []; } // Si on a filtré sur les lots de négoce (id_froid = -1)

		// Pagination
		$start 				= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 				= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		// Conversion
		if ($filtre_palettes != '') {
			$filtre_palettesArray = explode('|',$filtre_palettes);
			$filtre_palettes = implode(',', $filtre_palettesArray);
		}
		if ($filtre_produits != '') {
			$filtre_produitsArray = explode('|',$filtre_produits);
			$filtre_produits = implode(',', $filtre_produitsArray);
		}

		$joinFrais = $pdts_frais ? '' : 'LEFT ';

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS compo.`id_produit`, t.`nom` AS nom_produit, compo.`id_client`, clt.`nom` AS nom_client, compo.`id_palette`, pal.`numero` AS numero_palette, compo.`nb_colis`, compo.`poids`, compo.`id` AS id_compo, compo.`id_lot_regroupement`, lotr.`numlot` AS numlot_regroupement, compo.`designation`, pdt.`palette_suiv`, compo.`id_frais`,
                           		IFNULL(bl.`id`, 0) AS id_bl,
                           		IF (bl.`id` IS NOT NULL,num_bl,"") AS num_bl,
       							IF (fpdt.`id_lot` IS NOT NULL, fpdt.`id_lot` , 
       							    IF (fc.`id_lot` IS NOT NULL, fc.`id_lot`, 0)
       							    ) AS id_lot,
       							IF (fpdt.`quantieme` IS NOT NULL, fpdt.`quantieme` , 
       							      IF (fc.`quantieme` IS NOT NULL, fc.`quantieme`, 0)
       							    ) AS quantieme,
       							IF (fpdt.`id_froid` IS NOT NULL, fpdt.`id_froid` , 0) AS id_froid,
       							IF (lots.`numlot` IS NOT NULL, lots.`numlot` , "") AS numlot,
       							IF (frd.`id_type` IS NOT NULL, frd.`id_type` , 0) AS id_type_froid,
       							IF (ftype.`code` IS NOT NULL AND fpdt.`id_froid` IS NOT NULL, CONCAT(UPPER(ftype.`code`), LPAD(fpdt.`id_froid`, 4, 0)) , "") AS code_froid,
                           IF (frd.`date_sortie` IS NOT NULL,
       							DATE_ADD(frd.`date_sortie`, INTERVAL pdt.`nb_jours_dlc` DAY), fc.`dlc`) AS date_dlc,
                           		pdt.`code` AS code_produit,
                           IF (fc.`id` > 0, fc.`date_scan`, 
                           		IF (frd.`date_entree` IS NOT NULL AND frd.`date_entree`  != "" AND frd.`date_entree` != "0000-00-00 00:00:00", frd.`date_entree`,"")) AS date_froid
							FROM `pe_palette_composition` compo 
								JOIN `pe_produits` pdt ON pdt.`id` = compo.`id_produit`
								JOIN `pe_tiers` clt ON clt.`id` = compo.`id_client`
								JOIN `pe_palettes` pal ON pal.`id` = compo.`id_palette`
								'.$joinFrais.' JOIN `pe_frais` fc ON fc.`id` = compo.`id_frais`
								LEFT JOIN `pe_froid_produits` fpdt ON fpdt.`id_lot_pdt_froid` = compo.`id_lot_pdt_froid`
								LEFT JOIN `pe_froid` frd ON frd.`id` = fpdt.`id_froid`
								LEFT JOIN `pe_froid_types` ftype ON ftype.`id` = frd.`id_type`
								LEFT JOIN `pe_lots` lots ON lots.`id` = IF (fpdt.`id_lot` IS NOT NULL, fpdt.`id_lot`, fc.`id_lot`)
								LEFT JOIN `pe_lots_regroupement` lotr ON lotr.`id` = compo.`id_lot_regroupement`
								LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = pdt.`id` AND t.`id_langue` = 1
								LEFT JOIN `pe_frais` f ON f.`id` = compo.`id_frais` 
								
								LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = compo.`id` 
								LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl` AND bl.`statut` > 0 
							WHERE (compo.`supprime` = 0 OR compo.`archive` = 1) AND (f.`envoye` IS NULL OR f.`envoye` = 1) ';

		$query_liste.= $hors_frais ? ' AND compo.`id_frais` = 0 ' : '';
		$query_liste.= $hors_bl ? ' AND (bll.`id` IS NULL OR bll.`supprime` = 1 OR bl.`supprime` = 1  OR bl.`bt` = 1) AND compo.`archive` = 0 ' : '';
		$query_liste.= $en_bl ? ' AND bll.`id` IS NOT NULL AND bll.`supprime` = 0 AND bl.`supprime` = 0 ' : '';
		$query_liste.= $filtre_journee 		? '  AND (DATE(frd.`date_sortie`) = CURDATE() OR DATE(compo.`date`) = CURDATE())' : '';
		$query_liste.= $filtre_froid 	> 0 ? '  AND frd.`id` = ' . $filtre_froid . ' ' : '';
		$query_liste.= $filtre_client 	> 0 ? '  AND compo.`id_client` = ' . $filtre_client . ' ' : '';
		$query_liste.= $filtre_produit 	> 0 ? '  AND compo.`id_produit` = ' . $filtre_produit . ' ' : '';
		$query_liste.= $filtre_produits	!= '' ? '  AND compo.`id_produit` IN (' . $filtre_produits . ') ' : '';
		$query_liste.= $filtre_palette 	> 0 ? '  AND compo.`id_palette` = ' . $filtre_palette . ' ' : '';
		$query_liste.= $filtre_palettes != '' ? '  AND compo.`id_palette` IN (' . $filtre_palettes . ') ' : '';
		$query_liste.= $filtre_lot	 	> 0 ? '  AND lots.`id` = ' . $filtre_lot . ' ' : '';
		$query_liste.= $filtre_lot_r	> 0 ? '  AND compo.`id_lot_regroupement` = ' . $filtre_lot_r . ' ' : '';
		$query_liste.= $ids_compos 	  != '' ? '  AND compo.`id` IN (' . $ids_compos . ') ' : '';

		$query_liste.= 'GROUP BY compo.`id` ORDER BY  compo.`id_produit`, compo.`id_palette`,  compo.`date`
							LIMIT ' . $start . ',' . $nb;

		// Requête
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$tmp = new ProduitStock($donnee);

			// On rattache les noms traduits
			$noms = $this->getNomsProduitsTrad($tmp->getId_produit());
			$tmp->setNoms_produit($noms);

			$liste[] =$tmp;
		}

		return $liste;

	}  // FIN méthode

	// Retourne un array des noms traduits d'un produit
	public function getNomsProduitsTrad($id_pdt) {

		$query_liste = 'SELECT `id_langue` , `nom` FROM `pe_produit_trad` WHERE `id_produit` = ' . (int)$id_pdt . ' ORDER BY `id_langue` ';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[(int)$donnee['id_langue']] = $donnee['nom'];
		}

		return $liste;

	} // FIN méthode

	// Enregistre la traduction du nom du produit
	public function saveTradProduit(Produit $produit, $id_langue, $traduction) {

		// Si on supprime une traduction
		if (trim($traduction) == '') {
			$query_del = 'DELETE FROM `pe_produit_trad` WHERE `id_produit` = ' . $produit->getId() . ' AND `id_langue` = ' . $id_langue;
			$query = $this->db->prepare($query_del);
			Outils::saveLog($query_del);
			return $query->execute();
		}

		// Sinon on met à jour ou on ajoute...
		$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_produit_trad` WHERE `id_produit` = ' . $produit->getId() . ' AND `id_langue` = ' . $id_langue;
		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);

		$nb = $donnee && isset($donnee[0]) && isset($donnee[0]['nb']) ? intval($donnee[0]['nb']) : 0;

		$query_addupd = $nb > 0
			? 'UPDATE `pe_produit_trad` SET `nom` = :nom WHERE `id_produit` = ' . $produit->getId() . ' AND `id_langue` = ' . $id_langue
			: 'INSERT IGNORE INTO `pe_produit_trad` (`id_produit`, `id_langue`, `nom`) VALUES ( ' . $produit->getId() . ', '.$id_langue.', :nom)';

		$query2 = $this->db->prepare($query_addupd);
		$query2->bindValue(':nom', $traduction);
		$query_log = str_replace(':nom', '"'.$traduction.'" ',$query_addupd);
		Outils::saveLog($query_log);
		return $query2->execute();

	} // FIN méthode

	// Retourne les taux de TVA et le libellé par codes -- DEPRECIE --
	public function getTaxes() {
		return [];
	} // FIN méthode

	// Retourne les valeurs de Stats Produits
	public function getProduitStats() {

		return [
			new ProduitStats([
				'id' 	=> 0,
				'code' 	=> 'K',
				'nom' 	=> 'Au Kg'
			]),
			new ProduitStats([
				'id' 	=> 1,
				'code' 	=> 'P',
				'nom' 	=> 'A la pièce'
			])
		];

	} // FIN méthode

	// Retourne la dernière séquence d'EAN13
	private function getLastSequenceEan13() {

		$query_sequence = 'SELECT MAX(SUBSTRING(`ean13` FROM -5 FOR 4)) AS sequence FROM `pe_produits` ';
		$query = $this->db->prepare($query_sequence);
		$query->execute();

		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		if (!$donnee || !isset($donnee['sequence'])) { return false; }

		return intval($donnee['sequence']);

	} // FIN méthode

	// Retourne la clef de contrôle d'un code EAN
	public function getClefEan($ean) {

		// Pour le calcul du modulo final
		$total_resultats = 0;

		// On boucle sur les chiffres de l'EAN
		foreach (str_split($ean) as $position => $chiffre) {

			// Pour rajouter 1 à la position, l'array Key 0 doit donner la position 1;
			$position++;

			// Pour LES ean 13
			if (strlen($ean) == 13) {
				// Si la position dans la chaine est impaire, la pondération est de 1, sinon de 3.
				$ponderation = $position%2 == 1 ?  3 : 1;
			} else if (strlen($ean) == 12) {
				// Pour les EAN 14 c'est le contraire
				$ponderation = $position%2 == 1 ?  1 : 3;
			}

			$resultat = $chiffre * $ponderation;
			$total_resultats+=$resultat;

		} // FIN boucle sur les chiffres de l'EAN

		// On calcule le reste de la division par 10 de la somme des résultats
		$modulo =  $total_resultats%10;

		// Si le reste de la division est égal à 0, alors la clef est 0, sinon on ôte à 10 le reste ainsi trouvé (clef = 10 - reste)
		$clef = $modulo == 0 ? 0 : 10 - $modulo;
		return $clef;

	} // FIN méthode

	// Retourne la prochaine séquence disponible pour les ean7
	public function getNextSequenceDispoEan7() {

		// On récupère d'abord les plages de séquances autorisées
		$query_sequences = 'SELECT `min`, `max` FROM `pe_ean7_sequences` WHERE `supprime` = 0 ORDER BY `min`';
		$query = $this->db->prepare($query_sequences);
		$query->execute();

		$sequencesAtorisees = $query->fetchAll(PDO::FETCH_ASSOC);

		// On boucle sur les séquences autorisées à partir de la plus petite
		foreach ($sequencesAtorisees as $sequence) {

			// On cherche la plus grosse séquence déjà utilisée
			 $query_sequence = 'SELECT MAX(SUBSTRING(`ean7` FROM 3)) + 1 AS sequence FROM `pe_produits` HAVING sequence >= ' . (int)$sequence['min'] . ' AND sequence <= ' . (int)$sequence['max'];
			$query = $this->db->prepare($query_sequence);
			$query->execute();

			$donnee = $query->fetch(PDO::FETCH_ASSOC);
			if ($donnee && isset($donnee['sequence']) && intval($donnee['sequence']) > 0) { return intval($donnee['sequence']); }

		} // FIN boucle sur les séquences autorisées

		return false;

	} // FIN méthode


	// Retourne le prochain code EAN 13 à utiliser
	public function getNextEan13() {

			$racine = $this->getRacineEan13active();
			$ean = $racine;
			$max_sequence = $this->getLastSequenceEan13();
			if (!$max_sequence) { return false; }
			$sequence = $max_sequence + 1;
			// On vérifie que la séquence est bien sur 4 digits
			if (strlen($sequence) < 4) {
				sprintf("%04d", $sequence);
			} else if (strlen($sequence) > 4) {
				return false;
			}
			$ean.= $sequence;
			$clef = $this->getClefEan($ean);
			$ean = $ean.$clef;
			return $ean;

	}// FIN méthode

	// Retourne le prochain code EAN 7
	public function getNextEan7($type = 'poids') {

		$types = ['poids', 'prix'];
		if (!in_array($type, $types)) { return false; }
		$racine = $type == 'poids' ? 21 : 22; // 23 aussi possible à la place du 21 pour le poids
		$ean = $racine;
		$sequence = $this->getNextSequenceDispoEan7();
		// On vérifie que la séquence est bien sur 4 digits
		if (strlen($sequence) < 5) {
			sprintf("%04d", $sequence);
		} else if (strlen($sequence) > 5) {
			return false;
		}
		$ean.= $sequence;
		// Pas de clef ici
		return $ean;

	} // FIN méthode

	// Retourne les plages réservées de séquences EAN7
	public function getPlagesEan7() {

		// On récupère d'abord les plages de séquances autorisées
		$query_sequences = 'SELECT `id`, `min`, `max` FROM `pe_ean7_sequences` WHERE `supprime` = 0 ORDER BY `min`';
		$query = $this->db->prepare($query_sequences);
		$query->execute();

		return $query->fetchAll(PDO::FETCH_ASSOC);


	} // FIN méthode

	// Retourne le nombre d'utilisation d'une plage d'EAN7
	public function getNbUtilisationsPlageEan7($id) {

		$query_nb = 'SELECT COUNT(`id`) AS nb
						FROM `pe_produits`
							WHERE `supprime` = 0
						AND CAST(SUBSTR(`ean7`, 3) AS SIGNED) >= 
						    (SELECT `min` FROM `pe_ean7_sequences` WHERE `id` = '.(int)$id.')
						AND CAST(SUBSTR(`ean7`, 3) AS SIGNED) <= 
						    (SELECT `max` FROM `pe_ean7_sequences` WHERE `id` = '.(int)$id.')';

		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();
		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode


	public function getNbUtilisationsRacineEan13($racine) {
		$query_nb = 'SELECT COUNT(`id`) AS nb
						FROM `pe_produits`
							WHERE `supprime` = 0
						AND (LEFT(`ean13`, 8) = "'.$racine.'" OR LEFT(`ean14`, 8) = "'.$racine.'")';

		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();
		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;
	}

	// Vérifie qu'une plage n'empiète pas sur une autre
	public function isPlageDispo($min, $max, $id_exclu = 0) {

		$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_ean7_sequences` WHERE (
				('.(int)$min.' >= CAST(`min` AS SIGNED) AND '.(int)$min.' <= CAST(`max` AS SIGNED))
			OR  ('.(int)$max.' >= CAST(`min` AS SIGNED) AND '.(int)$max.' <= CAST(`max` AS SIGNED)))';

		$query_nb.= $id_exclu > 0 ? ' AND `id` != ' . (int)$id_exclu : '';

		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();
		$nb = $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;
		return $nb == 0;

	} // FIN méthode

	// Ajoute une plage
	public function addPlage($min, $max) {

		$query_ins = 'INSERT IGNORE INTO `pe_ean7_sequences` (`min`, `max`, `supprime`) VALUES ('.(int)$min.', '.(int)$max.', 0)';
		$query = $this->db->prepare($query_ins);
		Outils::saveLog($query_ins);
		return $query->execute();

	} // FIN méthode

	// Supprime une plage d'EAN7
	public function supprPlageEan7($id) {

		$query_del = 'DELETE FROM `pe_ean7_sequences` WHERE `id` = ' . (int)$id;
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode

	// Mise à jour d'une plage
	public function updatePlage($id, $min, $max) {

		$query_upd = 'UPDATE `pe_ean7_sequences` SET `min` = '.(int)$min.', `max` = '.(int)$max.' WHERE `id` = ' . (int)$id;
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();

	} // FIN méthode

	// Retourne la liste des infos de base des produits classés par EAN13
	public function getListeProduitsEans($ean = 13) {

		$query_liste = 'SELECT p.`id`, p.`code`, p.`ean13`, p.`ean14`, p.`ean7`, t.`nom` 
							FROM `pe_produits` p
								LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = p.`id` AND t.`id_langue` = 1
						WHERE p.`supprime` = 0
						  AND p.`ean'.(int)$ean.'` IS NOT NULL AND p.`ean'.(int)$ean.'` != ""
						ORDER BY p.`ean'.(int)$ean.'`';

		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Produit($donnee);
		}

		return $liste;


	} // FIN méthode

	// Retourne les produits en stock (STK, NEG, FRAIS) correspondant à l'id_produit
	public function getStocksProduit($id_pdt) {

		// IDs des compo palettes en stock avec ce produit (STk+FRAIS) - Le frais est géré sur le même table
		$query_liste = 'SELECT `id` FROM `pe_palette_composition` WHERE `supprime` = 0 AND `archive` = 0 AND `id_produit` = ' . (int)$id_pdt . ' AND `id` NOT IN (SELECT DISTINCT `id_compo` FROM `pe_bl_lignes` WHERE `supprime` = 0) AND `id_lot_hors_stock` = 0';

		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];

		$palettesManager = new PalettesManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$compo = $palettesManager->getComposition((int)$donnee['id']);
			if (!$compo instanceof PaletteComposition) { continue; }
			$compo = $this->getNumLotCompo($compo);
			$liste[] = $compo;
		}

		return $liste;

	} // FIN méthode

	// Rattache le numéro de lot à une compo
	public function getNumLotCompo(PaletteComposition $compo) {

		// On rattache le numéro de lot si on a un id_lot_pdt_froid
		if ($compo->getId_lot_pdt_froid() > 0) {
			$query_numlot = 'SELECT DISTINCT CONCAT(l.`numlot`,  IFNULL(fp.`quantieme`, "")) AS numlot
							FROM `pe_froid_produits` fp
								LEFT JOIN `pe_lots` l ON l.`id` = fp.`id_lot` 
						WHERE fp.`id_lot_pdt_froid` = ' . $compo->getId_lot_pdt_froid();
			$query2 = $this->db->prepare($query_numlot);
			$query2->execute();
			$donnee2 = $query2->fetch();

			$numlot = $donnee2 && isset($donnee2['numlot']) ? $donnee2['numlot'] : '';
			$compo->setNum_lot($numlot);
		}

		return $compo;

	} // FIN méthode

	// Retourne le taux de TVA d'un produit
	public function getTvaProduit($id_pdt) {

		$query_tva = 'SELECT `taux` FROM `pe_taxes` WHERE `id` = (SELECT `id_taxe` FROM `pe_produits` WHERE `id` = ' . (int)$id_pdt . ')';

		$query = $this->db->prepare($query_tva);
		if (!$query->execute()) { return false; }

		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee['taux']) ? floatval($donnee['taux']) : 0;

	} // FIN méthode

	// Associe un produits aux familles d'emballages potentielles
	public function liaisonProduitFamillesEmballages($id_produit, $ids_famsemb) {

		if ($id_produit == 0 || !is_array($ids_famsemb)) { return false; }

		// On supprime toutes les liaisons pour ce produit
		$query_del = 'DELETE FROM `pe_produits_emballages` WHERE `id_produit` = ' . (int)$id_produit;
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		if (!$query->execute()) { return false; }

		if (empty($ids_famsemb)) { return true; }

		$query_ins = 'INSERT IGNORE INTO `pe_produits_emballages` (`id_produit`, `id_famille_emballage`) VALUES ';
		$checklen = strlen($query_ins);

		// Boucle sur les id de familles d'emballage à associer
		foreach ($ids_famsemb as $id_fam_emb) {

			$query_ins.= '('.(int)$id_produit.', '.(int)$id_fam_emb.'),';

		} // FIN boucle sur les id de familles d'emballage à associer

		if (strlen($query_ins) == $checklen) { return false; }
		$query_ins = substr($query_ins,0,-1);

		$query2 = $this->db->prepare($query_ins);
		Outils::saveLog($query_ins);
		return $query2->execute();

	} // Fin méthode

	// Retourne un array des IDs de familles d'emballages associées au produit
	public function getIdsFamillesEmballagePdt($id_produit) {

		$query_ids = 'SELECT DISTINCT `id_famille_emballage` FROM `pe_produits_emballages` WHERE `id_produit` = ' . (int)$id_produit;

		$query = $this->db->prepare($query_ids);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = (int)$donnee['id_famille_emballage'];
		}

		return $liste;

	} // FIN méthode

	// Retourne une liste de produits en stock pour un lot avec les données de poids frais/froid/restant
	public function organiseDonneesProduitsStock($produits_stock) {

		if (!is_array($produits_stock) || empty($produits_stock)) { return []; }
		// On regroupe les produits ensembles, 1 par ligne, mais on veux avoir pour chacun les poids frais / froid / restant
		// Restant = total poids pour ce produit dans le lot qui n'est pas dans ceux retrournés

		// Tri par id produit
		usort($produits_stock, function($a, $b) {
			return $a->getId_produit() > $b->getId_produit() ? 1 : -1;
		});

		$retour = [];
		$id_pdt = 0;
		foreach ($produits_stock as $pdtStock) {
			// Changement de produit
			if ($pdtStock->getId_produit() != $id_pdt) {
				$id_pdt = $pdtStock->getId_produit();
				if (isset($retour[$pdtStock->getId_produit()])) {exit('ERR_ARRAY_DEJA_IDPDT_'.$pdtStock->getId_produit());}

				// On met en forme pour la multiplicité des ids_bl
				if ($pdtStock->getId_bl() > 0) {
					$pdtStock->setNums_bls([$pdtStock->getId_bl() => $pdtStock->getNum_bl()]);
				}

				// Si c'est du frais
				if ((int)$pdtStock->getId_type_froid() == 0) {
					$poids_frais = $pdtStock->getPoids();
					$pdtStock->setPoids_frais($poids_frais);

				// Sinon, froid (srg/cgl)
				} else {
					$poids_froid = $pdtStock->getPoids();
					$pdtStock->setPoids_froid($poids_froid);

				} // FIN test type froid/frais

				// Poids total produit dans le lot
				$poids_total_pdt_lot = $this->getPoidsTotalPdtLot($pdtStock->getId_produit(), $pdtStock->getId_lot());
				$pdtStock->setPoids_total_lot($poids_total_pdt_lot);

				$retour[$pdtStock->getId_produit()] = $pdtStock;

			// Si meme id_produit, alons on combine les valeurs
			} else {
				if (!isset($retour[$pdtStock->getId_produit()])) {exit('ERR_ARRAY_PAS_DEJA_IDPDT_'.$pdtStock->getId_produit());}
				$tmp = $retour[$pdtStock->getId_produit()];
				if (!$tmp instanceof ProduitStock) { exit('ERR_OBJ_ID#'.$pdtStock->getId_produit().'_PAS_PRODUIT_STOCK'); }

				// Si client différent, on concatène
				if ($tmp->getId_client() != $pdtStock->getId_client()) {
					$nom_client = $tmp->getNom_client().'|'.$pdtStock->getNom_client();
					$tmp->setNom_client($nom_client);
				}

				// Si c'est du frais
				if ((int)$pdtStock->getId_type_froid() == 0) {
					$oldPoidsFrais = $tmp->getPoids_frais();
					$poids_frais = $oldPoidsFrais+= $pdtStock->getPoids();
					$tmp->setPoids_frais($poids_frais);

				// Sinon, froid (srg/cgl)
				} else {
					$oldPoidsFroid = $tmp->getPoids_froid();
					$poids_froid = $oldPoidsFroid+= $pdtStock->getPoids();
					$tmp->setPoids_froid($poids_froid);

				} // FIN test type froid/frais

				// Si date froid différente, on combine
				if ((int)$pdtStock->getId_type_froid() > 0 && $tmp->getDate_froid() != $pdtStock->getDate_froid()
						&& Outils::verifDateSql($tmp->getDate_froid()) != "" && Outils::verifDateSql($pdtStock->getDate_froid())) {
					$date_froid = $tmp->getDate_froid().'<br>'.$pdtStock->getDate_froid();
					$tmp->setDate_froid($date_froid);
				}

				// Si DLC/DLUO différente, on combine
				if ($tmp->getDate_dlc() != $pdtStock->getDate_dlc()
						&& Outils::verifDateSql($tmp->getDate_dlc()) != "" && Outils::verifDateSql($pdtStock->getDate_dlc())) {
					$date_dlc = $tmp->getDate_dlc().'<br>'.$pdtStock->getDate_dlc();
					$tmp->setDate_dlc($date_dlc);
				}

				// Si Id_bl différent et > 0, on combine
				$ids_bls_pdt = $tmp->getNums_bls() != null ? $tmp->getNums_bls() : [];
				if (!is_array($ids_bls_pdt)) { exit('ERR_IDS_BLS_NOTARRAY');}
				if ($pdtStock->getId_bl() > 0 && !key_exists($pdtStock->getId_bl(), $ids_bls_pdt)) {
					$ids_bls_pdt[$pdtStock->getId_bl()] = $pdtStock->getNum_bl();
					$tmp->setNums_bls($ids_bls_pdt);
				}

				// On rajoute à l'array de retour
				$retour[$pdtStock->getId_produit()] = $tmp;

			} // FIN test changement de produit
		} // FIN boucle sur les produits stock

		// Puis tri par traitement puis frais + unicité des noms de clients
		$retourFrais = [];
		$retourMixte = [];
		foreach ($retour as $k => $pdtF) {

			$clients = [];
			foreach (explode('|',$pdtF->getNom_client()) as $clt) {
				$clients[$clt] = $clt;
			}
			$pdtF->setNom_client(implode('<br>', $clients));

			if ($pdtF->getPoids_frais() > 0 && $pdtF->getPoids_froid() == 0) {
				$retourFrais[$k] = $pdtF;
				unset($retour[$k]);
			} else if ($pdtF->getPoids_frais() > 0 && $pdtF->getPoids_froid() > 0) {
				$retourMixte[$k] = $pdtF;
				unset($retour[$k]);
			}

		} // FIN boucle sur les produits premier tri

		$retourFinal = array_merge($retour, $retourMixte, $retourFrais);

		return $retourFinal;
	} // FIN méthode

	// Retourne le poids total d'un produit dans le lot
	public function getPoidsTotalPdtLot($id_pdt, $id_lot) {

		$query_poids = 'SELECT ';
		$query_poids.= '(SELECT IFNULL(SUM(`poids`),0) FROM `pe_froid_produits` WHERE `id_lot` = ' . intval($id_lot) .' AND `id_pdt` = '.intval($id_pdt).') + ';
		$query_poids.= '(SELECT IFNULL(SUM(pc.`poids`),0) FROM `pe_palette_composition` pc JOIN `pe_frais` f ON f.`id` = pc.`id_frais` WHERE pc.`supprime` = 0 AND f.`id_lot` = ' . intval($id_lot).' AND pc.`id_produit` =  '.intval($id_pdt).') AS poids_total';
		$query = $this->db->prepare($query_poids);
		$query->execute();
		$donnee = $query->fetch();
		return !$donnee || empty($donnee) ? 0 : floatval($donnee['poids_total']);

	} // FIN méthode

	// Retourne les racines EAN 13 disponibles
	public function getRacinesEan13() {

		$query_liste = 'SELECT `id`, `racine`, `active` FROM `pe_ean13_racines` WHERE `supprime` = 0';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = $donnee;
		}

		return $liste;

	} // FIN méthode

	public function  getRacineEan13active() {
		$query_racine = 'SELECT `racine` FROM `pe_ean13_racines` WHERE `supprime` = 0 AND `active` = 1 ORDER BY `id` DESC LIMIT 0,1';
		$query = $this->db->prepare($query_racine);
		$query->execute();

		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return isset($donnee['racine']) ? $donnee['racine'] : '';
	}

	public function createRacine($racine) {
		if (strlen($racine) > 8 || strlen($racine) < 1) { return false; }
		$query_add = 'INSERT IGNORE INTO `pe_ean13_racines` (`racine`, `active`, `supprime`) VALUES ("'.$racine.'", 0, 0) ';
		$query = $this->db->prepare($query_add);
		Outils::saveLog($query_add);
		return $query->execute();
	}

	public function activeRacineEan13($id) {

		if ($id == 0) { return false; }
		$query_raz = 'UPDATE `pe_ean13_racines` SET `active` = 0';
		$query0 = $this->db->prepare($query_raz);
		Outils::saveLog($query_raz);
		$query0->execute();

		$query_upd = 'UPDATE `pe_ean13_racines` SET `active` = 1 WHERE `id` = ' . (int)$id;
		$query1 = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query1->execute();
	}

	public function supprRacineEan13($id) {
		$query_del = 'DELETE FROM `pe_ean13_racines` WHERE `id` = ' . (int)$id;
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();
	}

	// Retourne l'array des produits expédiés d'un lot pour le détail du lot
	public function getProduitsExpediesLot($id_lot) {

		if ($id_lot == 0) { return []; }

		$query_liste = 'SELECT * FROM (
						(
							SELECT compo.`id` AS id_compo, clt.`id` AS id_client, clt.`nom` AS nom_client, t.`nom` AS nom_produit, DATE(compo.`date`) AS date,
									IF (f.`id` IS NOT NULL, compo.`poids`, 0) AS poids_frais, IF (f.`id` IS NOT NULL, 0, compo.`poids`) AS poids_froid, compo.`id_produit`,
									IFNULL(IF (frd.`date_sortie` IS NOT NULL, DATE(DATE_ADD(frd.`date_sortie`, INTERVAL pdt.`nb_jours_dlc` DAY)), fc.`dlc`),"") AS date_dlc,
       								IFNULL(bl.`id`, 0) AS id_bl,
                           			IF (bl.`id` IS NOT NULL,num_bl,"") AS num_bl,
       								IFNULL(fact.`id`, 0) AS id_facture,
       								IFNULL(fact.`num_facture`, "") AS num_facture
							FROM `pe_palette_composition` compo  
    							JOIN `pe_produits` pdt ON pdt.`id` = compo.`id_produit`
								JOIN `pe_palettes` pal ON pal.`id` = compo.`id_palette`
							    LEFT JOIN `pe_frais` fc ON fc.`id` = compo.`id_frais`
							    LEFT JOIN `pe_froid_produits` fpdt ON fpdt.`id_lot_pdt_froid` = compo.`id_lot_pdt_froid`
								LEFT JOIN `pe_froid` frd ON frd.`id` = fpdt.`id_froid`
								LEFT JOIN `pe_froid_types` ftype ON ftype.`id` = frd.`id_type`
								LEFT JOIN `pe_lots` lots ON lots.`id` = IF (fpdt.`id_lot` IS NOT NULL, fpdt.`id_lot`, fc.`id_lot`)
								LEFT JOIN `pe_lots_regroupement` lotr ON lotr.`id` = compo.`id_lot_regroupement`
								LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = pdt.`id` AND t.`id_langue` = 1
								LEFT JOIN `pe_frais` f ON f.`id` = compo.`id_frais` 
								LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = compo.`id` 
								LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl` AND bl.`statut` > 0
								LEFT JOIN `pe_bl_facture` fb ON fb.`id_bl` = bl.`id`
								LEFT JOIN `pe_factures` fact ON fact.`id` = fb.`id_facture`
								JOIN `pe_tiers` clt ON clt.`id` = bl.`id_tiers_facturation`
							WHERE compo.`archive` = 1 AND (f.`envoye` IS NULL OR f.`envoye` = 1)
 								AND lots.`id` = ' . $id_lot . '
 								AND bll.`id` IS NOT NULL AND bll.`supprime` = 0 AND bl.`supprime` = 0
 								
 						) UNION (
 						
 								SELECT compo.`id` AS id_compo, clt.`id` AS id_client, clt.`nom` AS nom_client, t.`nom` AS nom_produit, DATE(compo.`date`) AS date,
									compo.`poids` AS poids_frais, 0 AS poids_froid,
									compo.`id_produit` AS id_produit,
									"" AS date_dlc,
									IFNULL(bl.`id`, 0) AS id_bl,
                           			IF (bl.`id` IS NOT NULL,num_bl,"") AS num_bl,
                           			IFNULL(fact.`id`, 0) AS id_facture,
       								IFNULL(fact.`num_facture`, "") AS num_facture
 							FROM `pe_palette_composition` compo  
							JOIN `pe_produits` pdt ON pdt.`id` = compo.`id_produit`
							LEFT JOIN `pe_palettes` pal ON pal.`id` = compo.`id_palette`
							LEFT JOIN `pe_produit_trad` t ON t.`id_produit` =  compo.`id_produit` AND t.`id_langue` = 1
							LEFT JOIN `pe_lots` lots ON lots.`id` = compo.`id_lot_hors_stock`
							LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = compo.`id` 
							LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl` AND bl.`statut` > 0 
							LEFT JOIN `pe_bl_facture` fb ON fb.`id_bl` = bl.`id`
							LEFT JOIN `pe_factures` fact ON fact.`id` = fb.`id_facture`
							JOIN `pe_tiers` clt ON clt.`id` = bl.`id_tiers_facturation`
						WHERE compo.`archive` = 1 AND compo.`id_lot_hors_stock` = ' . (int)$id_lot . '
						 	AND bll.`id` IS NOT NULL AND bll.`supprime` = 0 AND bl.`supprime` = 0 
 					)) AS i ORDER BY nom_produit, id_client, id_bl ';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach($query->fetchAll(PDO::FETCH_ASSOC) as $donnees) {
			$liste[] = new ProduitStock($donnees);
		} // FIN boucle sur les résultats

		$precedent = new ProduitStock([]);

		foreach ($liste as $i => $courant) {

			if (   $courant->getId_produit() == $precedent->getId_produit()
				&& $courant->getId_client() == $precedent->getId_client()
				&& $courant->getId_bl() == $precedent->getId_bl()
				&& $courant->getId_facture() == $precedent->getId_facture()
			) {
				$courant->setPoids_frais($precedent->getPoids_frais() + $courant->getPoids_frais());
				$courant->setPoids_froid($precedent->getPoids_froid() + $courant->getPoids_froid());
				unset($liste[$i-1]);
			}
			$precedent = $courant;

		} // Fin boucle

		return $liste;

	} // FIN méthode


	// Retourne l'ID du produit générique web
	public function getIdProduitWeb() {

		$query_produit = 'SELECT `id` FROM `pe_produits` WHERE `nom_court` = "PDTWEB"';
		$query = $this->db->prepare($query_produit);
		$query->execute();
		$donnee = $query->fetch();

		return $donnee && isset($donnee['id']) ? intval($donnee['id']) : 0;

	} // FIN méthode


    // Retourne un produit par son EAN13
    public function getProduitByNomCourt($nom_court) {

        $query_produit = 'SELECT p.`id`, t.`nom`, p.`nom_court`, p.`code`, p.`ean13`, p.`ean14`, p.`id_espece`, p.`poids`, p.`actif`, p.`supprime`, p.`date_add`, p.`date_maj`, f.`nom` AS nom_espece, p.`nb_colis`, p.`nb_jours_dlc`, p.`mixte`, p.`palette_suiv`, p.`id_taxe`, p.`pdt_gros`, p.`stats`, p.`id_pdt_emballage`, p.`pcb`, p.`poids_unitaire`,  p.`ean7`, p.`ean7_type`, p.`vrac`, p.`id_client`, p.`vendu_piece`, p.`id_poids_palette`, p.`nomenclature`
							FROM `pe_produits` p
								LEFT JOIN `pe_produits_especes` f ON f.`id` = p.`id_espece`
								LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = p.`id` AND t.`id_langue` = 1
						  WHERE p.`nom_court` = "'.trim($nom_court).'"';
        $query = $this->db->prepare($query_produit);
        $query->execute();

        $donnee = $query->fetch();

        $pdt = $donnee && !empty($donnee) ? new Produit($donnee) : false;

        return $pdt;

    } // FIN méthode

} // FIN classe