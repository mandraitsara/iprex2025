<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Manager de l'Objet Consommables et associés
------------------------------------------------------*/
class ConsommablesManager {
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

	// Retourne un type de consommable
	public function getTypeConsommables($id) {

		$query_type = 'SELECT `id`, `nom`, `actif`, `supprime` FROM `pe_consommables_types` WHERE `id` = :id';
		$query = $this->db->prepare($query_type);
		$query->bindValue(':id', (int)$id);
		$query->execute();

		// Log de la requête pour le mode Dev
		if (isset($_SESSION['devmode']) && $_SESSION['devmode']) {
			$_SESSION['pdoq'][get_class($this)][] = ['q' => $query->queryString, 'v' => ':id = "'.$id.'" '];
		}

		$donnee = $query->fetch();

		if (!$donnee || empty($donnee)) { return false; }

		$consommableType =  new ConsommablesTypes($donnee);

		if (!$consommableType instanceof ConsommablesTypes) { return false; }

		// On calcule les totaux : Familles
		$query_familles = 'SELECT COUNT(*) AS nb FROM `pe_consommables_familles` WHERE `supprime` = 0  AND `id_type` = '.$consommableType->getId();
		$queryNbFam = $this->db->prepare($query_familles);
		$queryNbFam->execute();
		$resFamilles = $queryNbFam->fetch();

		if ($resFamilles && isset($resFamilles['nb'])) {
			$consommableType->setNbFamilles(intval($resFamilles['nb']));
		}

		// On calcule les totaux : Consommables
		$query_consos = 'SELECT COUNT(*) AS nb FROM `pe_consommables` WHERE `supprime` = 0  AND `id_famille` IN (SELECT `id` FROM `pe_consommables_familles` WHERE `supprime` = 0  AND `id_type` = '.$consommableType->getId().')';
		$queryNbCons = $this->db->prepare($query_consos);
		$queryNbCons->execute();
		$resConsos = $queryNbCons->fetch();

		if ($resConsos && isset($resConsos['nb'])) {
			$consommableType->setNbConsommables(intval($resConsos['nb']));
		}

		return $consommableType;

	} // FIN méthode

	// Retourne une famille de consommables par son ID
	public function getConsommablesFamille($id) {

		$query_emballage = 'SELECT `id`, `nom`, `code`,`actif`, `supprime`, `date_add`, `date_maj`, `id_type` FROM `pe_consommables_familles` WHERE `id` = :id';
		$query = $this->db->prepare($query_emballage);
		$query->bindValue(':id', (int)$id);
		$query->execute();


		$donnee = $query->fetch();

		if (!$donnee || empty($donnee)) { return false; }

		$fam = new ConsommablesFamille($donnee);

		// On récupère les vues associées à cette famille pour disponibilité si emballages
		if ($fam->getId_type() == 1) {
			$fam->setVues($this->getConsommablesFamillesVues($fam));
		}

		// On récupère le nb de consommables liés
		$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_consommables` WHERE `supprime` = 0 AND `id_famille` = ' . (int)$fam->getId();
		$queryNb = $this->db->prepare($query_nb);
		$queryNb->execute();
		$resNb = $queryNb->fetch();

		if ($resNb && isset($resNb['nb'])) {
			$fam->setNb_consommables(intval($resNb['nb']));
		}

		return $fam;

	} // FIN méthode


	// Retourne la liste des types de consommables
	public function getListeTypesConsommables($params) {

		$show_inactifs 	= isset($params['show_inactifs']) 	? boolval($params['show_inactifs'])  : false;
		$show_supprime 	= isset($params['show_supprimes']) 	? boolval($params['show_supprimes']) : false;

		$query_liste = 'SELECT `id`, `nom`, `actif`, `supprime`
							FROM `pe_consommables_types`
								WHERE 1 ';

		$query_liste.= !$show_inactifs ? 'AND `actif` = 1 ' : '';
		$query_liste.= !$show_supprime ? 'AND `supprime` = 0 ' : '';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$consommableType = new ConsommablesTypes($donnee);
			if (!$consommableType instanceof ConsommablesTypes) { continue; }

			// On calcule les totaux : Familles
			$query_familles = 'SELECT COUNT(*) AS nb FROM `pe_consommables_familles` WHERE `supprime` = 0  AND `id_type` = '.$consommableType->getId();
			$queryNbFam = $this->db->prepare($query_familles);
			$queryNbFam->execute();
			$resFamilles = $queryNbFam->fetch();

			if ($resFamilles && isset($resFamilles['nb'])) {
				$consommableType->setNbFamilles(intval($resFamilles['nb']));
			}

			// On calcule les totaux : Consommables
			$query_consos = 'SELECT COUNT(*) AS nb FROM `pe_consommables` WHERE `supprime` = 0  AND `id_famille` IN (SELECT `id` FROM `pe_consommables_familles` WHERE `supprime` = 0  AND `id_type` = '.$consommableType->getId().')';
			$queryNbCons = $this->db->prepare($query_consos);
			$queryNbCons->execute();
			$resConsos = $queryNbCons->fetch();

			if ($resConsos && isset($resConsos['nb'])) {
				$consommableType->setNbConsommables(intval($resConsos['nb']));
			}

			// On calcule les totaux : Stock actuel
			$query_stock = 'SELECT SUM(`stock_actuel`) AS stock FROM `pe_consommables` WHERE `supprime` = 0  AND `id_famille` IN (SELECT `id` FROM `pe_consommables_familles` WHERE `supprime` = 0  AND `id_type` = '.$consommableType->getId().')';
			$queryStock = $this->db->prepare($query_stock);
			$queryStock->execute();
			$resStock = $queryStock->fetch();

			$stock =  $resStock && isset($resStock['stock']) ? intval($resStock['stock']) : 0;

			$consommableType->setStock_actuel($stock);


			$liste[] = $consommableType;
		}

		return $liste;

	} // FIN méthode

	// Retourne la liste des familles de consommables
	public function getListeConsommablesFamilles($params = []) {

		$show_inactifs 	= isset($params['show_inactifs']) 	? boolval($params['show_inactifs'])  : false;
		$show_supprime 	= isset($params['show_supprimes']) 	? boolval($params['show_supprimes']) : false;
		$get_nonconso 	= isset($params['get_nonconso']) 	? boolval($params['get_nonconso']) 	 : false;
		$get_nom_type 	= isset($params['get_nom_type']) 	? boolval($params['get_nom_type']) 	 : false;
		$get_emb 		= isset($params['get_emb']) 		? boolval($params['get_emb']) 		 : false;	// Retourne l'emballage en cours (Spécificité emballages)
		$get_stock 		= isset($params['get_stock']) 		? boolval($params['get_stock']) 	 : false;
		$no_stock 		= isset($params['no_stock']) 		? boolval($params['no_stock']) 		 : false;
		$has_encours 	= isset($params['has_encours']) 	? boolval($params['has_encours']) 	 : false;
		$recherche  	= isset($params['recherche']) 		? trim($params['$recherche']) 		 : '';
		$id_vue 		= isset($params['id_vue']) 			? intval($params['id_vue']) 		 : 0;
		$not_id_type 	= isset($params['not_id_type']) 	? intval($params['not_id_type']) 		: 0;
		$id_type 		= isset($params['id_type']) 		? intval($params['id_type']) 		 : 0;
		$lot_nonsuppr 	= isset($params['lot_nonsuppr']);

		// Pagination
		$start 			= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 			= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS cf.`id`, cf.`nom`, cf.`code`, cf.`actif`, cf.`supprime`, cf.`date_add`, cf.`date_maj`, cf.`id_type` ';
		$query_liste.= $get_nom_type ? ', ct.`nom` AS nom_type ' : '';
		$query_liste.= ' FROM `pe_consommables_familles` cf
							LEFT JOIN `pe_emballages_famille_vues` efv ON efv.`id_famille_emballage` = cf.`id`
							LEFT JOIN  `pe_consommables` c ON c.`id_famille` = cf.`id` AND c.`encours` > 0 ';

		$query_liste.= $get_nom_type ? ' LEFT JOIN `pe_consommables_types` ct ON ct.`id` = cf.`id_type` ' : '';
		$query_liste.= ' WHERE 1 = 1 ';

		$query_liste.= $recherche != '' ? 'AND cf.`nom` LIKE "%'.$recherche.'%"' : '';
		$query_liste.= !$show_inactifs != '' ? 'AND cf.`actif` = 1 ' : '';
		$query_liste.= !$show_supprime != '' ? 'AND cf.`supprime` = 0 ' : '';
		$query_liste.= $id_vue > 0 ? 'AND efv.`id_vue` = ' . $id_vue . ' ' : '';
		$query_liste.= $id_type > 0 ? 'AND cf.`id_type` = ' . $id_type . ' ' : '';
		$query_liste.= $not_id_type > 0 ? 'AND cf.`id_type` != ' . $not_id_type . ' ' : '';
		$query_liste.= $has_encours ? 'AND c.`id` IS NOT NULL AND c.`id` > 0 ' : '';
		$query_liste.= $no_stock ? 'AND (SELECT `stock` FROM `pe_consommables_histo` WHERE `id_consommable` = c.`id` ORDER BY `date` DESC LIMIT 0,1) = 0 ' : '';
		$query_liste.= $lot_nonsuppr ? 'AND c.`supprime` = 0 ' : '';
		$query_liste.= 'GROUP BY cf.`id`, cf.`nom`, cf.`code`, cf.`actif`, cf.`supprime`, cf.`date_add`, cf.`date_maj` ';
		$query_liste.= 'ORDER BY cf.`id_type`, cf.`nom` ASC ';
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		// Log de la requête pour le mode Dev
		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());
		$liste = [];

		foreach ($donnees = $query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$fam = new ConsommablesFamille($donnee);

			// On récupère les vues associées à cette famille pour disponibilité
			$fam->setVues($this->getConsommablesFamillesVues($fam));

			// Si besoin, on récupère l'emballage en cours (ou si besoin test stock)
			if ($get_emb || $no_stock || $has_encours) {
				$emb = $this->getEmballageEnCoursByFamille($fam);
				if ($emb instanceof Consommable) {
					$fam->setEmb_encours($emb);
				}
			} // FIN récup emballage en cours

			// SI on est pas en emballages, on récupère le nombre de consommables de la famille
			if ($fam->getId_type() > 1) {
				$query_consos = 'SELECT COUNT(*) AS nb FROM `pe_consommables` WHERE `supprime` = 0  AND `id_famille`= '.$fam->getId();
				$queryNbCons = $this->db->prepare($query_consos);
				$queryNbCons->execute();
				$resConsos = $queryNbCons->fetch();

				$nbConsos = $resConsos && isset($resConsos['nb']) ? intval($resConsos['nb']) : 0;
				$fam->setNb_consommables($nbConsos);

			} // FIN test pas emballages

			// On récupère le nombre d'emballages non consomés (spécifique emballages)
			if ($get_nonconso) {
				$query_non_conso = 'SELECT COUNT(*) AS nb FROM `pe_consommables` WHERE `id_famille` = ' . $fam->getId() . ' AND `encours` = 0 AND `supprime` = 0 AND `consomme` = 0';
				$querync = $this->db->prepare($query_non_conso);
				$querync->execute();
				$donneenc = $querync->fetch();
				if ($donneenc && isset($donneenc['nb'])) {
					$nbnonconso = intval($donneenc['nb']);
					$fam->setNon_consommes($nbnonconso);
				}

			} // FIN récup non consommés

			// On récupère le total du stock restant
			if ($get_stock) {

				$query_stock = 'SELECT SUM(`stock_actuel`) AS stock FROM `pe_consommables` WHERE `supprime` = 0  AND `id_famille`= '.$fam->getId();
				$querystock = $this->db->prepare($query_stock);
				$querystock->execute();
				$donneestock = $querystock->fetch();
				$stockFam = $donneestock && isset($donneestock['stock']) ? intval($donneestock['stock']) : 0 ;
				$fam->setStock_actuel($stockFam);

			} // FIN récup stock restant

			$liste[] = $fam;

		} // FIN boucle

		return $liste;

	} // FIN getListe


	// Enregistre un nouvel emballage
	public function saveConsommablesFamille(ConsommablesFamille $objet) {

		$table		= 'pe_consommables_familles';	// Nom de la table
		$champClef	= 'id';							// Nom du champ clef primaire
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
				// Log de la requête pour le mode Dev
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

	// Vérifie si une famille d'emballage existe déjà avec ce code
	public function checkFamilleExisteDeja($code, $id_exclu = 0) {

		$query_check = 'SELECT COUNT(*) AS nb FROM `pe_consommables_familles` WHERE (LOWER(`code`) = :code )';
		$query_check.= (int)$id_exclu > 0 ? ' AND `id` != ' . (int)$id_exclu : '';
		$query = $this->db->prepare($query_check);
		$query->bindValue(':code', trim(strtolower($code)));
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);

		if ($donnee && isset($donnee[0]) && isset($donnee[0]['nb']) && intval($donnee[0]['nb']) > 0) {
			return true;
		}

		return false;
	} // FIN méthode


	// Retourne un array des vues associées à la famille d'emballage (spécificité emballages)
	public function getConsommablesFamillesVues(ConsommablesFamille $fam) {
		$query_liste = 'SELECT v.`id`, v.`code`, v.`bs_color`, v.`fa`, v.`maintenance`, v.`nom`, v.`url`, v.`ordre`, v.`emballage`
							FROM `pe_vues` v
								JOIN `pe_emballages_famille_vues` fv ON fv.`id_vue` = v.`id`
							WHERE fv.`id_famille_emballage` = :idfam
								ORDER BY v.`ordre`';
		$query = $this->db->prepare($query_liste);
		$query->bindValue(':idfam',$fam->getId());
		$query->execute();

		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Vue($donnee);
		}
		return $liste;

	} // FIN méthode


	// Associe les vues à une famille d'emballage
	public function saveConsommablesFamillesVues(ConsommablesFamille $fam, $vues) {

		if (!is_array($vues)) { return false; }
		$query_del = 'DELETE FROM `pe_emballages_famille_vues` WHERE `id_famille_emballage` = :idfam';
		$query1 = $this->db->prepare($query_del);
		$query1->bindValue(':idfam',$fam->getId());

		if ($query1->execute()) {

			$query_log = str_replace(':idfam', $fam->getId(),$query_del);
			Outils::saveLog($query_log);

			if (empty($vues)) { return true; }

			$query_add = 'INSERT INTO `pe_emballages_famille_vues` (`id_famille_emballage`, `id_vue`) VALUES ';
			foreach ($vues as $vue_id) {
				$query_add.= '('.$fam->getId().', '.intval($vue_id).'),';
			}
			$query_add = substr($query_add,0,-1);
			$query2 = $this->db->prepare($query_add);

			Outils::saveLog($query_add);

			return $query2->execute();

		} // FIN réussite suppression

		return false;

	} // FIN méthode


	// Retourne la liste des emaballage pour la MAJ sur ticket (lot/froid)
	public function getListeEmballagesTicket($params) {

		$filtre_lot		= isset($params['id_lot'])		? intval($params['id_lot']) 			: 0;
		$filtre_froid	= isset($params['id_froid'])	? intval($params['id_froid']) 			: 0;

		$query_liste = 'SELECT `id_emballage_lot` AS id FROM `pe_emballages_prod` WHERE ';
		$query_liste.= $filtre_lot > 0 ? '`id_lot` = ' . $filtre_lot : '';
		$query_liste.= $filtre_lot > 0 && $filtre_froid > 0 ? ' AND ' : '';
		$query_liste.= $filtre_froid > 0 ? '`id_froid` = ' . $filtre_froid : '';


		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];
		foreach ($donnees = $query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$tmp = $this->getConsommable($donnee['id']);
			if ($tmp instanceof Consommable) {
				$liste[] = $tmp;
			}
		} // FIN boucle

		return $liste;

	} // FIN méthode

	// Retourne la liste des emballages
	public function getListeEmballages($params) {

		$params['id_type'] = 1;
		return $this->getListeConsommables($params);

	} // FIN méthode

	// Retourne la liste des consommables
	public function getListeConsommables($params) {

		// Limites sur statuts
		$show_supprime 	= isset($params['show_supprime']) 	? $params['show_supprime'] 			: false;

		// Filtres
		$filtre_type 	= isset($params['id_type']) 	? intval($params['id_type']) 			: 0;
		$filtre_famille = isset($params['id_famille']) 	? intval($params['id_famille']) 		: 0;
		$filtre_frs		= isset($params['id_frs']) 		? intval($params['id_frs']) 			: 0;
		$filtre_ref		= isset($params['ref']) 		? preg_replace("/[^0-9a-z]/", "",$params['ref']) : '';
		$filtre_lot		= isset($params['id_lot'])		? intval($params['id_lot']) 			: 0;
		$filtre_froid	= isset($params['id_froid'])	? intval($params['id_froid']) 			: 0;
		$filtre_vue		= isset($params['id_vue'])		? intval($params['id_vue']) 			: 0;
		// Autres filtres
		$conso 			= isset($params['conso']) 		? intval($params['conso']) 				: 0;
		$en_stock		= isset($params['en_stock']) 	? boolval($params['en_stock']) 			: false;
		$not_encours 	= isset($params['not_encours']) ? boolval($params['not_encours']) 		: false;
		$encours 		= isset($params['encours']) 	? boolval($params['encours']) 			: false;
		$exclu			= isset($params['exclu']) 		? intval($params['exclu']) 				: 0;
		// Pagination
		$start 			= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 			= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS e.`id`, e.`id_famille`, e.`id_fournisseur`, e.`numlot_frs`, e.`supprime`, fam.`nom` AS nom_famille, 
                           frs.`nom` AS nom_frs, e.`encours`, ep.`qte` AS qte_prod, e.`id_precedent`, e.`date_upd`, e.`consomme`, e.`stock_initial`, e.`stock_actuel`, e.`date_rcp`, e.`date_dlc`, e.`date_out`
							FROM `pe_consommables` e
								LEFT JOIN `pe_consommables_familles` fam ON fam.`id` = e.`id_famille`
								LEFT JOIN `pe_emballages_famille_vues` famv ON famv.`id_famille_emballage` = fam.`id`
								LEFT JOIN `pe_tiers` frs ON frs.`id` = e.`id_fournisseur`
								LEFT JOIN `pe_emballages_prod` ep ON ep.`id_emballage_lot` = e.`id` 
							WHERE 1 = 1 ';
		// Filtres
		$query_liste.= $filtre_ref  	!= '' ? 'AND e.`numlot_frs` LIKE "%' . 	$filtre_ref.'%" ' 	: '';
		$query_liste.= $filtre_famille   > 0  ? 'AND e.`id_famille` = ' . 		$filtre_famille.' ' : '';
		$query_liste.= $filtre_type 	 > 0  ? 'AND fam.`id_type` = ' . 		$filtre_type.' ' 	: '';
		$query_liste.= $filtre_frs 	 	 > 0  ? 'AND e.`id_fournisseur` = ' . 	$filtre_frs.' ' 	: '';
		$query_liste.= $filtre_lot 		 > 0  ? 'AND ep.`id_lot` = ' . 			$filtre_lot.' ' 	: '';
		$query_liste.= $filtre_froid 	 > 0  ? 'AND ep.`id_froid` = ' . 		$filtre_froid.' ' 	: '';
		$query_liste.= $filtre_vue 		 > 0  ? 'AND famv.`id_vue` = ' . 		$filtre_vue.' ' 	: '';
		// Autres filtres
		$query_liste.= $not_encours 		  ? 'AND e.`encours` = 0 ' : '';
		$query_liste.= $encours 			  ? 'AND e.`encours` = 1 ' : '';
		$query_liste.= $en_stock 			  ? 'AND e.`stock_actuel` > 0 ' : '';
		$query_liste.= $conso == 1 			  ? 'AND e.`consomme` = 1 ' : '';
		$query_liste.= $conso == -1 		  ? 'AND e.`consomme` = 0 ' : '';

		// Limites sur statuts
		$query_liste.= !$show_supprime != ''  ? 'AND e.`supprime`  = 0  ' : '';

		$query_liste.= $exclu > 0 ? ' AND e.`id` != ' . $exclu.' ' : '';

		// Tri et pagination
		$query_liste.= ' GROUP BY e.`id`, e.`id_famille`, e.`id_fournisseur`, e.`numlot_frs`, e.`supprime`, fam.`nom`, frs.`nom`, e.`encours`, ep.`qte`, e.`id_precedent`, e.`date_upd`, e.`consomme`  ';
		$query_liste.= ' ORDER BY fam.`nom` ASC,  e.`id` DESC  ';
		$query_liste.= ' LIMIT ' . $start . ',' . $nb;
		// Requête
		$query = $this->db->prepare($query_liste);
		$query->execute();
		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($donnees = $query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$skipEmballage = false;

			$emb = new Consommable($donnee);

			// On récupère les défectueux
			$def = $this->getDefectueuxFromEmballage($emb);

			$emb->setDefectueux($def);

			if (!$skipEmballage) {
				$liste[] = $emb;
			}
		}

		return $liste;

	} // FIN méthode

	
	// Retourne les defectueux pour un emblallage
	public function getDefectueuxFromEmballage(Consommable $consommable) {

		$query_liste = 'SELECT d.`id`, d.`id_consommable`, d.`date`, d.`qte`, d.`id_lot`, d.`id_froid`, IF (l.`numlot` IS NOT NULL, l.`numlot`, 0) AS num_lot,
       							IF (f.`id` IS NOT NULL,
       							    CONCAT(UPPER(ft.`code`), LPAD(f.`id`,4,0))
       							    , "") AS code_froid
							FROM `pe_consommables_defectueux` d
								LEFT JOIN `pe_lots` l ON l.`id` = d.`id_lot`
								LEFT JOIN `pe_froid` f ON f.`id` = d.`id_froid`
								LEFT JOIN `pe_froid_types` ft ON ft.`id` = f.`id_type`
							WHERE d.`id_consommable` = ' . $consommable->getId() . '
							ORDER BY d.`date`';
		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new ConsommableDefectueux($donnee);
		}

		return $liste;

	} // FIN méthode

	// Retourne un consommable
	public function getConsommable($id) {

		$query_emb = 'SELECT c.`id`, c.`id_famille`, c.`id_fournisseur`, c.`numlot_frs`, c.`supprime`, fam.`nom` AS nom_famille, frs.`nom` AS nom_frs, c.`encours`, c.`id_precedent`, c.`date_upd`,
       							c.`stock_initial`, c.`stock_actuel`, c.`date_rcp`, c.`date_dlc`, c.`date_out`
							FROM `pe_consommables` c
								LEFT JOIN `pe_consommables_familles` fam ON fam.`id` = c.`id_famille`
								LEFT JOIN `pe_tiers` frs ON frs.`id` = c.`id_fournisseur` 
						WHERE c.`id` = '.$id;
		// Requête
		$query = $this->db->prepare($query_emb);
		$query->execute();

		$donnee = $query->fetch();
		if (!$donnee || empty($donnee)) { return false; }

		$consommable = new Consommable($donnee);

		// Rattachement du stock
		$consommable->setStock_logs($this->getConsommablesStocskLogs($consommable));

		return $consommable;
	} // FIN méthode

	// Retourne le stock pour un emballage (derniere entrée dans les logs de stock)
	public function getConsommablesHisto(Consommable $consommable) {

		$query_stock = 'SELECT `stock_mouvement` FROM `pe_consommables_histo` WHERE `id_consommable` = :id ORDER BY `date` DESC LIMIT 0,1';

		$query = $this->db->prepare($query_stock);
		$query->bindValue(':id', $consommable->getId());
		$query->execute();

		$donnee = $query->fetch();

		if (!$donnee || empty($donnee)) { return 0; }

		return $donnee && isset($donnee['stock']) ? intval($donnee['stock']) : 0;

	} // FIN méthode


	// Retourne le log des stocks pour un emballage (
	public function getConsommablesStocskLogs(Consommable $emb, $limite = 10) {

		$query_liste = 'SELECT s.`id`, s.`id_consommable`, s.`stock_mouvement`, s.`date`, s.`user_id`, s.`id_vue`, IF (s.`id_vue` > 0, v.`nom`, "BackOffice") AS nom_vue
							FROM `pe_consommables_histo` s 
								LEFT JOIN `pe_vues` v ON v.`id` = s.`id_vue`
							WHERE `id_consommable` = :id 
							ORDER BY `date` DESC
							LIMIT 0,'.$limite;

		$query = $this->db->prepare($query_liste);
		$query->bindValue(':id', $emb->getId());
		$query->execute();

		$liste = [];

		$userManager = new UserManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$stockLog = new ConsommablesHisto($donnee);

			// Rattachement de l'objet User
			$userStock = $userManager->getUser($stockLog->getUser_id());
			$stockLog->setUser($userStock instanceof User ? $userStock : new User([]));
			$liste[] = $stockLog;

		}

		// On intègre les écarts d'une ligne à l'autre
		$liste 			= array_reverse($liste);
		$stockPrecedent = 0;

		foreach ($liste as $k => $stock) {

			$ecart = $stock->getStock() - $stockPrecedent;
			$stock->setEcart($ecart);
			$liste[$k] = $stock;
			$stockPrecedent = $stock->getStock();

		} // FIN boucle

		$liste = array_reverse($liste);

		return $liste;

	} // FIN méthode


	// Enregistre un nouveau consommable
	public function saveConsommable(Consommable $objet) {
		$table		= 'pe_consommables';	// Nom de la table
		$champClef	= 'id';					// Nom du champ clef primaire

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

	// Enregistre un nouveau type de consommables
	public function saveConsommablesTypes(ConsommablesTypes $objet) {
		$table		= 'pe_consommables_types';	// Nom de la table
		$champClef	= 'id';						// Nom du champ clef primaire

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


	// Enregistre un log de stock consommbable (historisation)
	public function saveConsommablesHisto($consommable, $stock, $id_vue = 0) {

		$id_consommable = $consommable instanceof Consommable ? $consommable->getId() : intval($consommable);

		if ($id_consommable == 0) { return false; }

		$consommable = $this->getConsommable($id_consommable);
		if (!$consommable instanceOf Consommable) { return false; }

		$utilisateur = isset($_SESSION['logged_user']) ? unserialize($_SESSION['logged_user']) : false;

		$user_id = $utilisateur && $utilisateur instanceof User ? $utilisateur->getId() : 0;

		$query_add = 'INSERT IGNORE INTO `pe_consommables_histo` (`id_consommable`, `stock_mouvement`, `date`, `user_id`, `id_vue`) 
						VALUES ('.$id_consommable.', '.$stock.', "'.date('Y-m-d H:i:s').'", '.$user_id.', '.$id_vue.' )';

		$query = $this->db->prepare($query_add);

		Outils::saveLog($query_add);

		$res = $query->execute();

		// on force donc le lot par défaut sur le lot impacté ici afin d'avoir toujours une cohérence
		if ($consommable->getId_famille() == 1) {
			$this->setEmballageEnCours($consommable);
		}

		return $res;

	} // FIN méthode



	// Purge le stock d'un emballage
	public function purgeConsommablesHisto(Consommable $emb) {
		$query_del = 'DELETE FROM `pe_consommables_histo` WHERE `id_consommable` = ' . $emb->getId();
		$query = $this->db->prepare($query_del);

		Outils::saveLog($query_del);

		return $query->execute();

	} // FIN méthode


	// Retourne l'emballage en cours pour une famille (spécificité emballages)
	public function getEmballageEnCoursByFamille($fam) {
		
		$fam_id = $fam instanceof ConsommablesFamille ? $fam->getId() : intval($fam);
		$query_emb = 'SELECT c.`id`, c.`id_famille`, c.`id_fournisseur`, c.`numlot_frs`, c.`supprime`, fam.`nom` AS nom_famille, frs.`nom` AS nom_frs, c.`encours`, c.`id_precedent`, c.`date_upd`,
       						c.`stock_initial`, c.`stock_actuel`, c.`date_rcp`, c.`date_dlc`, c.`date_out`
							FROM `pe_consommables` c
								LEFT JOIN `pe_consommables_familles` fam ON fam.`id` = c.`id_famille`
								LEFT JOIN `pe_tiers` frs ON frs.`id` = c.`id_fournisseur` 
						WHERE c.`encours` = 1 AND fam.`id` = '.$fam_id . ' AND c.`supprime` = 0';
		// Requête
		$query = $this->db->prepare($query_emb);
		$query->execute();

		$donnee = $query->fetch();
		if (!$donnee || empty($donnee)) { return false; }

		$emb = new Consommable($donnee);

		return $emb;

	} // FIN méthode


	// Met à jour l'association Emballage/Lot/Froid (spécificité emballages)
	public function addUpdEmballageProd($params) {

		$id_emb 	= isset($params['id_emb']) 		? intval($params['id_emb']) 	: 0;
		$id_lot 	= isset($params['id_lot']) 		? intval($params['id_lot']) 	: 0;
		$id_froid 	= isset($params['id_froid']) 	? intval($params['id_froid']) 	: 0;
		$qte_upd 	= isset($params['qte_upd']) 	? intval($params['qte_upd']) 	: 0;

		if ($id_emb == 0 || $qte_upd == 0) { return false; }

		$operateur = $qte_upd > 0 ? '+' : '';

		$query_addupd = 'INSERT INTO `pe_emballages_prod` (`id_emballage_lot`, `id_lot`, `id_froid`, `qte`) 
							VALUES ('.$id_emb.', '.$id_lot.', '.$id_froid.', '.$qte_upd.') 
						ON DUPLICATE KEY UPDATE `qte` = `qte` '.$operateur.' ' . $qte_upd;

		$query = $this->db->prepare($query_addupd);
		$retour = $query->execute();

		Outils::saveLog($query_addupd);

		// On supprime si des lignes sont à zéro ou négatives en qauntité si on a retiré du stock
		if ($qte_upd < 0) {
			$this->purgeEmballageProdQteNulles();
		}
		return $retour;

	} // FIN méthode

	// Vide les entrées vides en quantité ou négatives dans la table d'association emballage/lot/froid (spécificité emballages)
	public function purgeEmballageProdQteNulles() {

		$query_del = 'DELETE FROM `pe_emballages_prod` WHERE `qte` < 1';
		$query = $this->db->prepare($query_del);

		Outils::saveLog($query_del);

		return $query->execute();

	} // FIN méthode

	// Attribue un emballage comme en cours au sein de sa famille (spécificité emballages)
	public function setEmballageEnCours(Consommable $emb) {

		// On retire le statut "en cours" aux autres emballages de la famille
		$query_upd = 'UPDATE `pe_consommables` SET `encours` = 0 WHERE `id_famille` = ' . $emb->getId_famille();
		$query = $this->db->prepare($query_upd);

		Outils::saveLog($query_upd);

		if (!$query->execute()) { return false; }

		// On attribue le statut au nouvel emballage
		$emb->setEncours(1);

		return $this->saveConsommable($emb);

	} // FIN méthode


	// Associe tous les lots d'emballages "en cours" de chaque famille d'emballage dont la vue est concernée (à un lot ou une op de froid - source) - (spécificité emballages)
	public function setEmballagesVue(Vue $vue, $objet) {

		// Vérification de la source (lot/froid)
		if (!$objet instanceof Lot && !$objet instanceof Froid) { return false; }

		// On récupère tous les lots d'emballage en cours pour les familles d'emballage concerné par la vue
		$query_liste = 'SELECT DISTINCT er.`id`
							FROM `pe_consommables` er
								JOIN `pe_consommables_familles` f ON f. `id` = er.`id_famille`
								JOIN `pe_emballages_famille_vues` fv ON fv.`id_famille_emballage` = f.`id`
							WHERE f.`actif` = 1 AND f.`supprime` = 0 AND er.`encours` = 1 AND er.`supprime` = 0 AND fv.`id_vue` = ' . $vue->getId();

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($donnees = $query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = $donnee['id'];
		}

		// SI aucun, on renvoie un array vide
		if (empty($liste)) { return false;	}
		$query_ins = 'INSERT IGNORE INTO `pe_emballages_prod` (`id_emballage_lot`, `id_lot`, `id_froid`) VALUES ';

		foreach ($liste as $id) {
			$query_ins.= '('.$id.', ';
			$query_ins.= $objet instanceof Lot ? $objet->getId().',' : '0,';
			$query_ins.= $objet instanceof Froid ? $objet->getId() : '0';
			$query_ins.= '),';
		}

		$query_ins = substr($query_ins,0,-1);
		$query2 = $this->db->prepare($query_ins);
		Outils::saveLog($query_ins);
		return $query2->execute();

	} // FIN méthode

	// Purge l'info des changement de lots tous les jours (spécificité emballages)
	public function purgeChangementLotEmballage() {

		$query_upd = 'UPDATE `pe_consommables` SET `id_precedent` = 0, `date_upd` = null WHERE `date_upd` < CURDATE()' ;
		$query = $this->db->prepare($query_upd);

		Outils::saveLog($query_upd);

		return $query->execute();

	} // FIN méthode


	// Met à jour l'information de changement de lot d'emballage (spécificité emballages)
	public function setChangementRouleau(Consommable $old, Consommable $new, $objet = null) {

		if ($old->getId() == $new->getId()) {
			// ON enregistre la changement de rouleau pour cette famille d'emballage dans la table de suivi
			$query_add = 'INSERT IGNORE INTO `pe_consommables_changements` (`id_consommable_famille`, `date`) VALUES ('.$new->getId_famille().', "'.date('Y-m-d').'");';
			$query_new = $this->db->prepare($query_add);
			Outils::saveLog($query_add);
			return $query_new->execute();
		}

		// Clean ancien rouleau + consommé
		$query_upd_old = 'UPDATE `pe_consommables` SET `id_precedent` = 0, `date_upd` = null, `consomme` = 1 WHERE `id` = ' .$old->getId() ;
		$query_old = $this->db->prepare($query_upd_old);
		$res1 = $query_old->execute();
		Outils::saveLog($query_upd_old);
		// Nouveau rouleau
		$query_upd_new = 'UPDATE `pe_consommables` SET `id_precedent` = '. $old->getId().', `date_upd` = CURDATE(), `encours` = 1 WHERE `id` = ' .$new->getId() ;
		$query_new = $this->db->prepare($query_upd_new);
		$res2 = $query_new->execute();
		Outils::saveLog($query_upd_new);
		// ON enregistre la changement de rouleau pour cette famille d'emballage dans la table de suivi
		$query_add = 'INSERT IGNORE INTO `pe_consommables_changements` (`id_consommable_famille`, `date`) VALUES ('.$new->getId_famille().', "'.date('Y-m-d').'");';
		$query_new = $this->db->prepare($query_add);
		$res3 = $query_new->execute();
		Outils::saveLog($query_add);
		// On rajoute l'attribution du lot ou de l'op de froid au nouveau rouleau
		if (is_object($objet)) {
			$query_ins_prod = 'INSERT IGNORE INTO `pe_emballages_prod` (`id_emballage_lot`, `id_lot`, `id_froid`) 
						VALUES ('.$new->getId().', ';
			$query_ins_prod.=   $objet instanceof Lot ? $objet->getId().',' : '0,';	// id_lot
			$query_ins_prod.=  !$objet instanceof Lot ? $objet->getId().')' : '0)';	// id_froid
			$query_ins = $this->db->prepare($query_ins_prod);
			$res4 = $query_ins->execute();
			Outils::saveLog($query_ins_prod);
		} else { $res4 = true; }

		return $res1 && $res2 && $res3 && $res4;

	} // FIN méthode


	// retourne le détails des rouleaux rattachés à un lot et ses op (admin détail lot) - (spécificité emballages)
	public function getListeEmballagesByLot($params) {

		$id_lot = isset($params['id_lot']) ? intval($params['id_lot']) : 0;		
		$id_produit = isset($params['id_produit']) ? intval($params['id_produit']) : 0;
		if ($id_lot == 0) { return []; }

		// Pagination
		$start 			= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 			= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS `id` FROM `pe_consommables` WHERE id IN (
							SELECT `id_emballage_lot` FROM `pe_emballages_prod` WHERE `id_lot` = '.$id_lot.'
    						UNION 
							SELECT  `id_emballage_lot` FROM `pe_emballages_prod` WHERE `id_froid` IN (
							    SELECT fp.`id_froid` FROM `pe_froid_produits` fp 
							    	WHERE fp.`id_lot` = '.$id_lot.'
							)
    
    					)';

		$query_liste.= $id_produit > 0 ? ' AND `id` IN (SELECT `id` FROM `pe_consommables` WHERE `id_famille` IN (SELECT  `id_famille_emballage` FROM `pe_produits_emballages` WHERE `id_produit` = '.$id_produit.'))' : '';

		$query_liste.= 'LIMIT ' . $start . ',' . $nb;

		echo '--<br />';
		
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());
		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$emb = $this->getConsommable((int)$donnee['id']);
			if ($emb instanceof Consommable) {
				$liste[] = $emb;
			}
		}

	
		// echo '--<br />';

		return $liste;

	} // FIN méthode


	// Ajoute un défectueux
	public function addDefectueux(Consommable $emb, $id_lot = 0 , $id_froid = 0) {

		$query_add = 'INSERT INTO `pe_consommables_defectueux` (`id_consommable`, `date`, `qte`, `id_lot`, `id_froid`) 
						VALUES ('.$emb->getId().', CURDATE(), 1, '.$id_lot.', '.$id_froid.') ON DUPLICATE KEY UPDATE `qte` = `qte` + 1';
		$query = $this->db->prepare($query_add);
		Outils::saveLog($query_add);
		return $query->execute();

	} // FIN méthode

	// Retourne la famille et le nb de défectueux du jour courant (hors production, ticket EMB) - (spécificité emballages)
	public function getEmballagesDefectueuxJourHorsProd() {

		$query_liste = 'SELECT DISTINCT f.nom, d.`qte`
							FROM `pe_consommables` c 
							JOIN `pe_consommables_familles` f ON f.`id` = c.`id_famille`
							JOIN `pe_consommables_defectueux` d ON d.`id_consommable` = c.`id` 
						WHERE f.`id_type` = 1 AND d.`date` = CURDATE() ';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = $donnee;
		}

		return $liste;

	} // FIN méthode

	// Retourne la famille et le nb de défectueux du jour courant - (spécificité emballages)
	public function getEmballagesDefectueuxJour($id_lot = 0, $id_froid = 0) {

		// Si on est dans la gestion des emballages, on prend en compte ceux qui ne sont associés à aucune production
		if ($id_lot == 0 && $id_froid == 0) {
			return $this->getEmballagesDefectueuxJourHorsProd();
		} // FIN test hors production

		$query_liste = 'SELECT DISTINCT f.nom, d.`qte`
							FROM `pe_emballages_prod` p 
							JOIN `pe_consommables` c ON c.`id` = p.`id_emballage_lot` 
							JOIN `pe_consommables_familles` f ON f.`id` = c.`id_famille`
							JOIN `pe_consommables_defectueux` d ON d.`id_consommable` = p.`id_emballage_lot` 
						WHERE f.`id_type` = 1 AND d.`date` = CURDATE() ';

		$query_liste.= $id_lot   > 0 ? ' AND p.`id_lot`   = ' . $id_lot   : '' ;
		$query_liste.= $id_froid > 0 ? ' AND p.`id_froid` = ' . $id_froid : '' ;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = $donnee;
		}
		return $liste;

	} // FIN méthode

	// Retourne la famille et le nb de défectueux du jour courant - (autres consommables)
	public function getConsommablesDefectueuxJour() {

		$query_liste = 'SELECT f.`nom`, SUM(d.`qte`) AS qte
							FROM `pe_consommables` c 
							JOIN `pe_consommables_familles` f ON f.`id` = c.`id_famille`
							JOIN `pe_consommables_defectueux` d ON d.`id_consommable` = c.`id` 
						WHERE f.`id_type` > 1 AND d.`date` = CURDATE() 
						GROUP BY f.`nom`';						// Type > 1 : autres consommables (hors emballages)

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = $donnee;
		}
		return $liste;

	} // FIN méthode


	// Retourne la famille et le numlot de l'ancien et du nouveau pour un changment de rouleau du jour courant (spécificité emballages)
	public function getEmballagesChangementRouleauJour($id_lot = 0, $id_froid = 0) {
		
		if ($id_lot > 0 || $id_froid > 0) {

			$query_liste = 'SELECT cf.`nom`, ec.`id_consommable_famille` AS id
							FROM `pe_consommables_changements` ec
								JOIN `pe_consommables_familles` cf ON cf.`id` = ec.`id_consommable_famille` AND cf.`id_type` = 1
							WHERE ec.`date` = CURDATE() AND  ec.`id_consommable_famille` IN (
							    SELECT `id_famille` FROM `pe_emballages_prod` ep JOIN `pe_consommables` c ON c.`id` = ep.`id_emballage_lot` WHERE 1 ';

			$query_liste.= $id_lot   > 0 ? ' AND ep.`id_lot`   = ' . $id_lot   . ' ' : '';
			$query_liste.= $id_froid > 0 ? ' AND ep.`id_froid` = ' . $id_froid . ' ' : '';
			$query_liste.= ')';

		} else {

			$query_liste = 'SELECT cf.`nom`, ec.`id_consommable_famille` AS id
							FROM `pe_consommables_changements` ec
								JOIN `pe_consommables_familles` cf ON cf.`id` = ec.`id_consommable_famille` 
							WHERE cf.`id_type` = 1 AND ec.`date` = CURDATE() ';
		}

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			if (!isset($liste[$donnee['id']])) {
				$liste[$donnee['id']] = [];
				$liste[$donnee['id']]['nb'] = 0;
			}

			$liste[$donnee['id']]['nom'] = $donnee['nom'];
			$liste[$donnee['id']]['nb'] = $liste[$donnee['id']]['nb'] + 1;
		}

		// On viens rajouter les changements de rouleau du jour sur le meme lot

		return $liste;

	} // FIN méthode

	// Retourne la famille et le numlot de l'ancien et du nouveau pour un changment de référence du jour courant (autres consommables)
	public function getConsommablesChangementReferenceJour() {

		$query_liste = 'SELECT cf.`nom`, ec.`id_consommable_famille` AS id
						FROM `pe_consommables_changements` ec
							JOIN `pe_consommables_familles` cf ON cf.`id` = ec.`id_consommable_famille` 
						WHERE cf.`id_type` > 1 AND ec.`date` = CURDATE() ';								// Type > 1 : autres consommables (hors emballages)

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			if (!isset($liste[$donnee['id']])) {
				$liste[$donnee['id']] = [];
				$liste[$donnee['id']]['nb'] = 0;
			}

			$liste[$donnee['id']]['nom'] = $donnee['nom'];
			$liste[$donnee['id']]['nb'] = $liste[$donnee['id']]['nb'] + 1;
		}

		return $liste;

	} // FIN méthode


	public function repareEnCours() {

		$query_familles = 'SELECT `id` FROM `pe_consommables_familles` WHERE `actif` = 1 AND `supprime` = 0 AND `id_type` = 1';

		$queryF = $this->db->prepare($query_familles);
		$queryF->execute();

		// On boucle sur les familles actives
		foreach ($queryF->fetchAll(PDO::FETCH_ASSOC) as $famille) {

			$query_lots = 'SELECT COUNT(*) AS nb FROM `pe_consommables` WHERE `encours` = 1 AND  `supprime` = 0  AND `id_famille` = ' . $famille['id'];
			$queryL = $this->db->prepare($query_lots);
			$queryL->execute();
			$lotsNb = $queryL->fetch();

			if ($lotsNb && isset($lotsNb['nb'])) {
				$nb_encours = intval($lotsNb['nb']);
				if ($nb_encours == 0) {
					$query_upd = 'UPDATE `pe_consommables` SET `encours` = 1 WHERE `encours` = 0 AND `id_famille` = '. $famille['id'] . ' ORDER BY `stock_actuel` LIMIT 1';
					$queryU = $this->db->prepare($query_upd);
					Outils::saveLog($query_upd);
					$queryU->execute();
				}

				$logManager = new LogManager($this->db);
				$log = new Log([]);
				$log->setLog_type('info');
				$log->setLog_texte('Réparation des consommables en cours pour la famille #'. (int)$famille['id']);
				$logManager->saveLog($log);

			}

		} // FIN boucle familles

		return true;

	} // FIN méthode

	// Retourne l'ID du type de consommables pour les étiquettes
	public function getIdTypeEtiquettes() {

		$query_id = 'SELECT `id` FROM `pe_consommables_types` WHERE `nom` LIKE "%emballages%" ';

		$query = $this->db->prepare($query_id);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);


		return isset($donnee[0]) && isset($donnee[0]['id']) ? intval($donnee[0]['id']) : 0;

	} // FIN méthode


	// Retourne la liste des familles de consommables avec le stock déduit le jour courrant (Front) - Hors emballages
	public function getStocksJourConsommables() {

		// On récupère les mouvements de stocks des consommables du jour
		$query_liste = 'SELECT h.`stock_mouvement` * - 1 AS mouvement, f.`nom`
							FROM `pe_consommables_histo` h
								JOIN `pe_consommables` c ON c.`id` =  h.`id_consommable`
                                JOIN `pe_consommables_familles` f ON f.`id` =  c.`id_famille`
						WHERE DATE(h.`date`) = CURDATE()
							AND h.`stock_mouvement` < 0
                            AND c.`id_famille` IN (
                                SELECT `id` FROM `pe_consommables_familles` WHERE `id_type` NOT IN (
                                    SELECT `id` FROM `pe_consommables_types` WHERE `nom` LIKE "%emballage%"
                                )
                            )';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			if (!isset($liste[$donnee['nom']])) {
				$liste[$donnee['nom']] = intval($donnee['mouvement']);
			} else {
				$liste[$donnee['nom']] = $liste[$donnee['nom']] +  intval($donnee['mouvement']);
			}
		}
		return $liste;

	} // FIN méthode


	//  Vérifie qu'on a bien en emballage "en cours" parmis les restants pour cette famille, et si ce n'est pas le cas, on en attribue un...
	public function cleanConsoParDefaut($id_famille) {

		echo 'cleanConsoParDefaut('.$id_famille.')';

		// On tente de récupérer l'emballage en cours pour cette famille
		$fam = $this->getConsommablesFamille($id_famille);
		if (!$fam instanceof ConsommablesFamille) { echo '<br>Famille non identifiée !'; return false; }
		$encours = $this->getEmballageEnCoursByFamille($fam);

		if ($encours instanceof Consommable) { echo '<br>en cours trouvé : ' . $encours->getId(); return true; }

		// Ici on a pas d'emballage en cours pour cette famille...

		// On cherche l'id
		echo '<br>'.$query_encours = 'SELECT `id` FROM `pe_consommables` WHERE `supprime` = 0 AND `id_famille` = ' . intval($id_famille) . ' ORDER BY `stock_actuel` ASC';

		$query = $this->db->prepare($query_encours);
		$query->execute();

		$donnee = $query->fetch();
		if (!$donnee || !isset($donnee['id'])) { echo '<br>pas de id dispo pour cette famille...'; return false; }

		echo $query_upd = 'UPDATE `pe_consommables` SET `encours` = 1 WHERE `id` = ' . $donnee['id'];
		$query2 = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query2->execute();

	} // FIN methode

} // FIN classe
