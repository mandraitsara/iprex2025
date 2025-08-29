<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Palette
Généré par CBO FrameWork le 31/07/2019 à 15:26:15
------------------------------------------------------*/
class PalettesManager {

	protected    $db, $nb_results;

	public function __construct($db) {
		$this->setDb($db);
	}

	/* ----------------- GETTERS ----------------- */
	public function getNb_results() {
		return $this->nb_results;
	}

	/* ----------------- SETTERS ----------------- */
	public function setDb(PDO $db) {
		$this->db = $db;
	}

	public function setNb_results($nb_results) {
		$this->nb_results = (int)$nb_results;
	}

	/* ----------------- METHODES ----------------- */

	// Retourne une palette par son ID
	public function getPalette($id) {

		$query_palette = 'SELECT SQL_CALC_FOUND_ROWS p.`id`, p.`numero`, p.`date`,  p.`id_user`, p.`statut`, p.`supprime`, CONCAT(u.`prenom`, " ", u.`nom`) AS nom_user, 
                           		p.`id_poids_palette`, pp.`nom` as poids_palette_type, pp.`poids` AS poids_palette, p.`scan_frais`, p.`id_client`, t.`nom` AS nom_client
							FROM `pe_palettes` p
								JOIN `pe_users` u ON u.`id` = p.`id_user`
								LEFT JOIN `pe_poids_palettes` pp ON pp.`id` = p.`id_poids_palette`
								LEFT JOIN `pe_tiers` t ON t.`id` = p.`id_client`
							WHERE p.`id` = ' . intval($id);

		$query = $this->db->prepare($query_palette);

		$query->execute();

		$donnee = $query->fetch();

		$palette = $donnee && !empty($donnee) ? new Palette($donnee) : false;

		if ($palette instanceof Palette) {

			$compos = $this->getCompositionPalette($palette);
			$palette->setComposition($compos);

			if ($palette->getId_client() == 0 && !empty($compos)) {
				if ($compos[0] instanceof PaletteComposition) {
					$palette->setId_client($compos[0]->getId_client());
					$palette->setNom_client($compos[0]->getNom_client());
				}
			}
		}

		return $palette;

	} // FIN méthode

	// Retourne la composition d'une palette
	public function getCompositionPalette(Palette $palette, $id_client = 0, $id_produit = 0) {

		// On rattache la composition de la palette
		$query_compos = 'SELECT pc.`id`, pc.`id_palette`, pc.`id_client`, pc.`id_produit`, pc.`poids`, pc.`nb_colis`, pc.`date`, pc.`id_user`, pc.`supprime`, pc.`id_lot_pdt_froid`, pc.`id_lot_regroupement`, CONCAT(u.`prenom`, " ", u.`nom`) AS nom_user, pt.`nom` AS nom_produit, t.`nom` AS nom_client, pc.`designation`, pc.`id_lot_pdt_negoce`, pc.`id_frais`, pc.`id_lot_hors_stock`, pc.`archive`
								FROM `pe_palette_composition` pc 
									JOIN `pe_users` u ON u.`id` = pc.`id_user`
									JOIN `pe_produits` p ON p.`id` = pc.`id_produit`
									JOIN `pe_tiers` t ON t.`id` = pc.`id_client`
									LEFT JOIN `pe_produit_trad` pt ON pt.`id_produit` = p.`id` AND pt.`id_langue` = 1
								WHERE (pc.`supprime` = 0 OR pc.`archive` = 1)  AND pc.`id_palette` = ' . $palette->getId();

		$query_compos.= $id_client > 0 ? ' AND pc.`id_client` = ' . $id_client : '';
		$query_compos.= $id_produit > 0 ? ' AND pc.`id_produit` = ' . $id_produit : '';
		$query_compos.= ' ORDER BY pc.`date` ASC ';
		$query2 = $this->db->prepare($query_compos);
		$query2->execute();

		$compos = [];

		foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {
			$compos[] = new PaletteComposition($donnee2);
		}

		return $compos;

	} // FIN méthode

	// Retourne les compositions associés à un produit de négoce
	public function getCompositionsNegoceProduit($id_lot_pdt_negoce) {

		$query_compos = 'SELECT pc.`id`, pc.`id_palette`, pc.`id_client`, pc.`id_produit`, pc.`poids`, pc.`nb_colis`, pc.`date`, pc.`id_user`, pc.`supprime`, pc.`id_lot_pdt_froid`, pc.`id_lot_regroupement`, pc.`id_frais`, pc.`id_lot_hors_stock`,
       								CONCAT(u.`prenom`, " ", u.`nom`) AS nom_user, pt.`nom` AS nom_produit, t.`nom` AS nom_client, pc.`designation`, pc.`id_lot_pdt_negoce`
								FROM `pe_palette_composition` pc 
									LEFT JOIN `pe_users` u ON u.`id` = pc.`id_user`
									LEFT JOIN `pe_produits` p ON p.`id` = pc.`id_produit`
									LEFT JOIN `pe_tiers` t ON t.`id` = pc.`id_client`
									LEFT JOIN `pe_produit_trad` pt ON pt.`id_produit` = p.`id` AND pt.`id_langue` = 1
								WHERE (pc.`supprime` = 0 OR pc.`archive` = 1) AND pc.`id_lot_pdt_negoce` = ' . (int)$id_lot_pdt_negoce;

		$query_compos.= ' ORDER BY pc.`date` ASC ';
		$query2 = $this->db->prepare($query_compos);
		$query2->execute();

		$compos = [];

		foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {
			$compos[] = new PaletteComposition($donnee2);
		}

		return $compos;

	} // FIN méthode

	// Retourne une composition (unique, par son ID)
	public function getComposition($id_compo) {

		$query_compo = 'SELECT pc.`id`, pc.`id_palette`, pc.`id_client`, pc.`id_produit`, pc.`poids`, pc.`nb_colis`, pc.`date`, pc.`id_user`, pc.`supprime`, pc.`id_lot_pdt_froid`, pc.`id_lot_regroupement`, pal.`numero` AS numero_palette, pc.`id_frais`, pc.`id_lot_hors_stock`,
       								CONCAT(u.`prenom`, " ", u.`nom`) AS nom_user, pt.`nom` AS nom_produit, t.`nom` AS nom_client, pc.`designation`, pc.`id_lot_pdt_negoce`, IFNULL(reg.`numlot`, "") AS num_lot_regroupement
								FROM `pe_palette_composition` pc 
									JOIN `pe_users` u ON u.`id` = pc.`id_user`
									JOIN `pe_produits` p ON p.`id` = pc.`id_produit`
									JOIN `pe_tiers` t ON t.`id` = pc.`id_client`
									LEFT JOIN `pe_produit_trad` pt ON pt.`id_produit` = p.`id` AND pt.`id_langue` = 1
									LEFT JOIN `pe_palettes` pal ON pc.`id_palette` = pal.`id`
									LEFT JOIN `pe_lots_regroupement` reg ON reg.`id` = pc.`id_lot_regroupement`
								WHERE pc.`id` = ' . intval($id_compo);

		$query = $this->db->prepare($query_compo);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && !empty($donnee) ? new PaletteComposition($donnee) : false;

	} // FIN méthode


	public function getLastCompositionByIdLotPdtFroid($id_lot_pdt_froid) {

		$query_compo = 'SELECT pc.`id`, pc.`id_palette`, pc.`id_client`, pc.`id_produit`, pc.`poids`, pc.`nb_colis`, pc.`date`, pc.`id_user`, pc.`supprime`, pc.`id_lot_pdt_froid`, pc.`id_lot_regroupement`, pal.`numero` AS numero_palette, pc.`id_frais`, pc.`id_lot_hors_stock`,
       								CONCAT(u.`prenom`, " ", u.`nom`) AS nom_user, pt.`nom` AS nom_produit, t.`nom` AS nom_client, pc.`designation`, pc.`id_lot_pdt_negoce`
								FROM `pe_palette_composition` pc 
									JOIN `pe_users` u ON u.`id` = pc.`id_user`
									JOIN `pe_produits` p ON p.`id` = pc.`id_produit`
									JOIN `pe_tiers` t ON t.`id` = pc.`id_client`
									LEFT JOIN `pe_produit_trad` pt ON pt.`id_produit` = p.`id` AND pt.`id_langue` = 1
									LEFT JOIN `pe_palettes` pal ON pc.`id_palette` = pal.`id`
								WHERE pc.`supprime` = 0 AND pc.`id_lot_pdt_froid` = ' . intval($id_lot_pdt_froid) . ' ORDER BY pc.`id` DESC LIMIT 0,1';

		$query = $this->db->prepare($query_compo);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && !empty($donnee) ? new PaletteComposition($donnee) : false;

	} // FIN méthode

	// Retourne la liste des palettes
	public function getListePalettes($params) {

		$numero 		= isset($params['numero']) 			? intval($params['numero'])  		: 0;
		$id_client 		= isset($params['id_client']) 		? intval($params['id_client']) 		: 0;
		$ids_clients	= isset($params['ids_clients']) && is_array($params['ids_clients'])	? $params['ids_clients']			: [];
		$id_produit 	= isset($params['id_produit']) 		? intval($params['id_produit']) 	: 0;
		$id_froid 		= isset($params['id_froid']) 		? intval($params['id_froid']) 		: 0;
		$id_lot 		= isset($params['id_lot']) 			? intval($params['id_lot']) 		: 0;
		$statut 		= isset($params['statut']) 			? intval($params['statut']) 		: -1;
		$statuts 		= isset($params['statuts']) 		? $params['statuts'] 				: '';
		$vides 			= isset($params['vides']) 			? boolval($params['vides']) 		: true;
		$mixte 			= isset($params['mixte']) 			? boolval($params['mixte']) 		: false;
		$debut 			= isset($params['debut']) 			? $params['debut'] 					: '';
		$fin 			= isset($params['fin']) 			? $params['fin'] 					: '';
		$froid_termine	= isset($params['froid_termine']) 	? boolval($params['froid_termine'])	: false;
		$frais			= isset($params['frais']) 			? boolval($params['frais'])			: false;
		$hors_frais		= isset($params['hors_frais']) 		? boolval($params['hors_frais'])	: false;
		$scan_frais		= isset($params['scan_frais'])		? intval($params['scan_frais'])		: -1;

		// Pagination
		$start 			= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 			= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT p.`id`, p.`numero`, p.`date`,  p.`id_user`, p.`statut`, p.`supprime`, CONCAT(u.`prenom`, " ", u.`nom`) AS nom_user, 
                                    p.`id_poids_palette`, pp.`nom` as poids_palette_type, pp.`poids` AS poids_palette, p.`scan_frais`, p.`id_client`, t.`nom` AS nom_client
							FROM `pe_palettes` p
								JOIN `pe_users` u ON u.`id` = p.`id_user` 
								LEFT JOIN `pe_poids_palettes` pp ON pp.`id` = p.`id_poids_palette` 
								LEFT JOIN `pe_tiers` t ON t.`id` = p.`id_client` ';

		$query_liste.= ($id_client > 0 || !empty($ids_clients)) && !$froid_termine ? ' LEFT JOIN `pe_palette_composition` pc ON pc.`id_palette` = p.`id` ' : '';
		$query_liste.= $froid_termine  ? ' JOIN `pe_palette_composition` pc ON pc.`id_palette` = p.`id` ' : '';
		$query_liste.= $froid_termine ? ' LEFT JOIN `pe_froid_produits` fp ON fp.`id_lot_pdt_froid` = pc.`id_lot_pdt_froid` ' : '';
		$query_liste.= $froid_termine ? ' LEFT JOIN `pe_froid` f ON f.`id` = fp.`id_froid` ' : '';

		$query_liste.= '		WHERE p.`supprime` = 0 ';

		$query_liste.= $numero > 0   ? ' AND p.`numero` = ' . $numero : '';
		$query_liste.= $statut > -1  ? ' AND p.`statut` = '.$statut : '';
		$query_liste.= $statuts != ''  ? ' AND p.`statut` IN ('.$statuts .') ' : '';
		$query_liste.= $frais ? ' AND p.`scan_frais` IN (1,2) ' : '';
		$query_liste.= $hors_frais ? ' AND p.`scan_frais` = 0 ' : '';
		$query_liste.= $scan_frais > -1 ? ' AND p.`scan_frais` = ' . $scan_frais : '';

		$query_liste.= 	$debut != '' ? ' AND p.`date` > "'.$debut.' 00:00:00" ' : '';
		$query_liste.= 	$fin   != '' ? ' AND p.`date` < "'.$fin . ' 23:59:59" ' : '';

		$query_liste.= $froid_termine ? ' AND ((f.`date_sortie` IS NOT NULL AND f.`statut` > 1 AND f.`supprime` = 0) OR (pc.`id_lot_pdt_negoce` > 0))' : '';
		$query_liste.= $froid_termine ? ' AND  pc.`supprime` = 0 ' : '';
		$query_liste.= $froid_termine && $id_froid > 0 ? ' AND  f.`id` = ' . $id_froid : '';
		$query_liste.= $froid_termine && $id_client > 0 ? ' AND  pc.`supprime` = 0 AND pc.`id_client` = ' . $id_client : '';
		$query_liste.= $id_client > 0 ? ' AND pc.`id_client` = ' . $id_client : '';
		$query_liste.= !empty($ids_clients) ? ' AND pc.`id_client` IN (' . implode(',',$ids_clients).') ' : '';
		$query_liste.= $froid_termine && $id_lot > 0 ? ' AND  fp.`id_lot` = ' . $id_lot : '';

		$query_liste.= ' ORDER BY p.`numero`, p.`date` ASC ';
		$query_liste.= ' LIMIT ' . $start . ',' . $nb;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$palette = new Palette($donnee);

			$compos = $mixte ?  $this->getCompositionPaletteMixte($palette) : $this->getCompositionPalette($palette, $id_client, $id_produit);


			// Si la palette ne contiens aucun produit et qu'on avais un filtre sur le client, on ne la prends pas en compte
			if (empty($compos) && $id_client > 0) {

				continue; }

			// Si la palette ne contiens aucun produit et qu'on avais un filtre sur le produit, on ne la prends pas en compte
			if (empty($compos) && $id_produit > 0) { continue; }

			$palette->setComposition($compos);

			// Si on ne veux pas de palettes vides...
			if (!$vides && empty($compos)) {
				continue;
			}

			// Sinon on ajoute au retour
			$liste[] = $palette;

		} // FIN boucle

		return $liste;

	} // FIN getListe

	// Retourne le poids total d'une palette
	public function getPoidsTotalPalette(Palette $palette) {

		if (empty($palette->getComposition())) { return 0; }

		$poidsTotal = 0.0;
		foreach ($palette->getComposition() as $composition) {

			if (!$composition instanceof PaletteComposition) { continue; }
			$poidsTotal+=$composition->getPoids();

		} // FIN boucle

		return $poidsTotal;

	} // FIN méthode

	// Retourne le nombre total de colis d'une palette
	public function getNbColisTotalPalette(Palette $palette) {

		if (empty($palette->getComposition())) { return 0; }

		$nbColis = 0;
		foreach ($palette->getComposition() as $composition) {

			if (!$composition instanceof PaletteComposition) { continue; }
			if ($composition->getArchive() == 1) { continue; }
			$nbColis+=$composition->getNb_colis();

		} // FIN boucle

		return $nbColis;

	} // FIN méthode


	// Retourne les clients d'une palette
	public function getClientsPalette(Palette $palette) {

		if (empty($palette->getComposition())) { return 0; }

		$liste = [];
		foreach ($palette->getComposition() as $composition) {

			if (!$composition instanceof PaletteComposition) { continue; }
			$liste[$composition->getId_client()] = $composition->getNom_client();

		} // FIN boucle

		return $liste;

	} // FIN méthode

	// Retourne les produits d'une palette
	public function getProduitsPalette(Palette $palette) {

		if (empty($palette->getComposition())) { return 0; }

		$liste = [];
		foreach ($palette->getComposition() as $composition) {

			if (!$composition instanceof PaletteComposition) { continue; }
			$liste[$composition->getId_produit()] = $composition->getNom_produit();

		} // FIN boucle

		return $liste;

	} // FIN méthode

	// Retourne la capacité maximale d'une palette (poids)
	public function getCapacitePalettePoids(Palette $palette) {



		// On a besoin de connaitre un minimum la composition pour se baser sur un id_produit
		if (empty($palette->getComposition())) { return 0; }

		// On retiens le produit le plus présent dans la palette (gestion des mixtes)
		$id_produit_qui_a_la_plus_grosse = 0;
		$plus_grosse = 0;

		$idpdts = [];

		foreach ($palette->getComposition() as $composition) {
			$idpdts[$composition->getId_produit()] = $composition->getId_produit();
			if (!$composition instanceof PaletteComposition) { continue; }
			if ($composition->getPoids() > $plus_grosse) {
				$plus_grosse = $composition->getPoids();
				$id_produit_qui_a_la_plus_grosse = $composition->getId_produit();
			}
		} // FIN boucle

		$isMixte = count($idpdts) > 1;
		if ($isMixte) {
			return 2000;
		}

		$produitsManager = new ProduitManager($this->db);
		$pdt = $produitsManager->getProduit($id_produit_qui_a_la_plus_grosse);
		if (!$pdt instanceof Produit) { return 0; }

		if ($pdt->isVrac()) { return 9999; }

		if ($pdt->getPoids() <= 1) {
			return 2000;
		}

		if ($palette->getId() == 3358) {
			return ($pdt->getPoids() * $pdt->getNb_colis()) + 2000;
		}

		return $pdt->getPoids() * $pdt->getNb_colis();

	} // FIN méthode


	// Enregistre & sauvegarde (Méthode Save)
	public function savePalette(Palette $objet) {

		$table      = 'pe_palettes'; // Nom de la table
		$champClef  = 'id'; // Nom du champ clef
		// FIN Configuration

		$getter     = 'get'.ucfirst(strtolower($champClef));
		$setter     = 'set'.ucfirst(strtolower($champClef));

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

	// Enregistre & sauvegarde (Méthode Save)
	public function savePaletteComposition(PaletteComposition $objet) {

		$table      = 'pe_palette_composition'; // Nom de la table
		$champClef  = 'id'; 					// Nom du champ clef
		// FIN Configuration

		$getter     = 'get'.ucfirst(strtolower($champClef));
		$setter     = 'set'.ucfirst(strtolower($champClef));

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

	// Retourne le nombre max de colis du produit le plus présent dans la palette
	public function getCapacitePaletteColis(Palette $palette) {

		// On récupère le nb de colis maxi par palette du produit le plus présent dans la palette (gestion multi)
		// On a besoin de connaitre au minimum une composition pour se baser sur un id_produit
		if (empty($palette->getComposition())) { return 0; }

		// On retiens le produit le plus présent dans la palette (gestion des mixtes)
		$id_produit_qui_a_la_plus_grosse = 0;
		$plus_grosse = 0;
		foreach ($palette->getComposition() as $composition) {
			if (!$composition instanceof PaletteComposition) { continue; }
			if ($composition->getNb_colis() > $plus_grosse) {
				$plus_grosse = $composition->getNb_colis();
				$id_produit_qui_a_la_plus_grosse = $composition->getId_produit();
			}
		} // FIN boucle

		$produitsManager = new ProduitManager($this->db);
		$pdt = $produitsManager->getProduit($id_produit_qui_a_la_plus_grosse);
		if (!$pdt instanceof Produit) { return 0; }

		if ($palette->getId() == 3358) {
			return $pdt->getNb_colis() + 200;
		}

		if ($pdt->isVrac()) { return 999; }

		return $pdt->getNb_colis();


	} // FIN méthode


	// Supprime un produit froid d'une palette (compo)
	public function supprComposition(FroidProduit $froidProduit) {

		$query_ids = 'SELECT `id` FROM `pe_palette_composition`  WHERE `id_palette` = ' . $froidProduit->getId_palette() . ' AND `id_produit` = ' . $froidProduit->getId_pdt() . ' AND `nb_colis` = ' . $froidProduit->getNb_colis() . ' AND `poids` = ' . $froidProduit->getPoids() . ' LIMIT 1';

		$query1 = $this->db->prepare($query_ids);
		$query1->execute();
		$ids = [];
		foreach ($query1->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$ids[] = intval($donnee['id']);
		}

		$logManager = new LogManager($this->db);
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Suppression des composition d\'un pdt_froid par concordance : IDs compos : ' . implode(',', $ids) );
		$logManager->saveLog($log);


		$query_del = 'DELETE FROM `pe_palette_composition` WHERE `id_palette` = ' . $froidProduit->getId_palette() . ' AND `id_produit` = ' . $froidProduit->getId_pdt() . ' AND `nb_colis` = ' . $froidProduit->getNb_colis() . ' AND `poids` = ' . $froidProduit->getPoids() . ' LIMIT 1';

		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode

	// Supprime un produit froid d'une palette (compo) par son ID
	public function supprCompositionFromId($id_compo) {

		$logManager = new LogManager($this->db);
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Suppression manuelle de la compo #'.$id_compo);
		$logManager->saveLog($log);

		$query_del = 'DELETE FROM `pe_palette_composition` WHERE `id` = ' .$id_compo;
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode


	// Affecte un client à toutes les compos d'une palette
	public function setClientPalette(Palette $palette, $id_client) {

		if (intval($id_client) == 0) { return false; }

		$query_upd = 'UPDATE `pe_palette_composition` SET `id_client` = ' . intval($id_client) . ' WHERE `id_palette` = ' . $palette->getId();
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();

	} // FIN méthode

	// Nettoie les compositions vides crées par l'utilisateur
	public function cleanCompoPalettesVides(User $user) {

		$query_ids = 'SELECT `id` FROM `pe_palette_composition` WHERE `id_lot_pdt_froid` = 0 AND `id_lot_hors_stock` = 0 AND `id_user` = ' . $user->getId();

		$query1 = $this->db->prepare($query_ids);
		$query1->execute();
		$ids = [];
		foreach ($query1->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$ids[] = intval($donnee['id']);
		}

		if (empty($ids)) {
			return true;
		}

		$logManager = new LogManager($this->db);
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Suppression des compositions sans pdt_froid pour l\'user #'.$user->getId().'. IDs compos : ' . implode(',', $ids) );
		$logManager->saveLog($log);

		$query_del = 'UPDATE `pe_palette_composition` SET `supprime` = 1 WHERE `id_lot_pdt_froid` = 0 AND `id_lot_hors_stock` = 0 AND `id_user` = ' . $user->getId();
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode

	// Nettoie les palettes vides crées par l'utilisateur
	public function cleanPalettesVides(User $user) {

		$query_ids = 'SELECT p.`id` 
    						FROM pe_palettes p 
    					    LEFT JOIN `pe_palette_composition` c ON c.`id_palette` = p.`id`
    						WHERE c.`id` IS NULL';
		$query1 = $this->db->prepare($query_ids);
		$query1->execute();
		$ids = [];
		foreach ($query1->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$ids[] = intval($donnee['id']);
		}

		if (!empty($ids)) {

			$logManager = new LogManager($this->db);
			$log = new Log([]);
			$log->setLog_type('warning');
			$log->setLog_texte('Suppression auto des palettes vides #'.implode(',', $ids));
			$logManager->saveLog($log);

			$query_del = 'DELETE FROM `pe_palettes` WHERE `id` IN ('.implode(',', $ids).') AND `id_user` = ' . $user->getId();
			$query = $this->db->prepare($query_del);
			Outils::saveLog($query_del);
			return $query->execute();
		}
		return false;

	} // FIN méthode


	// Retourne le poids total des compositions pour un produit du traitement
	public function getPoidsTotalFroidProduit($id_lot_pdt_froid) {

		$query_total = 'SELECT SUM(`poids`) AS total FROM `pe_palette_composition` WHERE `supprime` = 0  AND `id_lot_pdt_froid` = ' . intval($id_lot_pdt_froid);
		$query = $this->db->prepare($query_total);
		$query->execute();

		$donnee = $query->fetch();
		return $donnee && !empty($donnee) ? floatval($donnee['total']) : 0.0;

	} // FIN méthode

	// Retourne le nb de colis total des compositions pour un produit du traitement
	public function getNbColisTotalFroidProduit($id_lot_pdt_froid) {

		$query_total = 'SELECT SUM(`nb_colis`) AS total FROM `pe_palette_composition` WHERE `supprime` = 0  AND `id_lot_pdt_froid` = ' . intval($id_lot_pdt_froid);
		$query = $this->db->prepare($query_total);
		$query->execute();

		$donnee = $query->fetch();
		return $donnee && !empty($donnee) ? intval($donnee['total']) : 0.0;

	} // FIN méthode

	// Supprime les compositions flaguées comme "supprimées" pour un id_lot_pdt_froid donné
	public function purgeComposSupprimeesByFroidProduit($id_lot_pdt_froid) {

		if (intval($id_lot_pdt_froid) == 0) { return false; }

		$query_ids = 'SELECT `id` FROM `pe_palette_composition` WHERE `supprime` = 1 AND `id_lot_pdt_froid` = ' . $id_lot_pdt_froid;
		$query1 = $this->db->prepare($query_ids);
		$query1->execute();
		$ids = [];
		foreach ($query1->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$ids[] = intval($donnee['id']);
		}

		if (empty($ids)) { return true;	}

		$logManager = new LogManager($this->db);
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Suppression des composition flaguées comme "supprimées" pour l\'id_lot_pdt_froid #'.$id_lot_pdt_froid.' : IDs compos : ' . implode(',', $ids) );
		$logManager->saveLog($log);

		$query_del = 'DELETE FROM `pe_palette_composition` WHERE `supprime` = 1 AND `id_lot_pdt_froid` = ' . $id_lot_pdt_froid;
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode

	// Passe en statut supprimé toutes les compositions d'un id_lot_pdt_froid, sauf les exceptions
	public function supprComposFroidProduitExcept($id_lot_pdt_froid, $exceptions = []) {

		if (intval($id_lot_pdt_froid) == 0) { return false; }

		// Compatibilité pour passer un array ou bien un id_compo isolé en tant qu'exception
		$exceptions_in = is_array($exceptions) ? implode(',', $exceptions) : intval($exceptions);

		$query_upd = 'UPDATE `pe_palette_composition` SET `supprime` = 1 WHERE `id_lot_pdt_froid` = ' . $id_lot_pdt_froid . ' AND `id` NOT IN ('.$exceptions_in.')';
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();

	} // FIN méthode

	// Retourne le dernier id client pour les compositions d'un FroidProduit
	public function getClientCompoByIdLotPdtFroid($id_lot_pdt_froid) {

		$query_client = 'SELECT `id_client` FROM `pe_palette_composition` WHERE `id_lot_pdt_froid` = ' . intval($id_lot_pdt_froid) . ' ORDER BY `id` DESC LIMIT 0,1';

		$query = $this->db->prepare($query_client);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee[0]) && isset($donnee[0]['id_client']) ? intval($donnee[0]['id_client']) : 0;

	} // FIN méthode

	// Retourne le dernier id client des compos existantes pour une palette
	public function getClientCompoByPalette($id_palette) {

		$query_client = 'SELECT `id_client` FROM `pe_palette_composition` WHERE `id_palette` = ' . intval($id_palette) . ' ORDER BY `id` DESC LIMIT 0,1';

		$query = $this->db->prepare($query_client);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee[0]) && isset($donnee[0]['id_client']) ? intval($donnee[0]['id_client']) : 0;

	} // FIN méthode

	// Supprime les composition d'un produitFroid donné
	public function supprCompositionFromIdLotPdtFroid($id_lot_pdt_froid) {

		if (intval($id_lot_pdt_froid) == 0) { return false; }

		$query_ids = 'SELECT `id` FROM `pe_palette_composition` WHERE `id_lot_pdt_froid` = ' . $id_lot_pdt_froid;

		$query1 = $this->db->prepare($query_ids);
		$query1->execute();
		$ids = [];
		foreach ($query1->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$ids[] = intval($donnee['id']);
		}

		$logManager = new LogManager($this->db);
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Suppression des compositions de l\'id_pdt_froid #'.$id_lot_pdt_froid.'. IDs compos : ' . implode(',', $ids) );
		$logManager->saveLog($log);

		$query_del = 'DELETE FROM `pe_palette_composition` WHERE  `id_lot_pdt_froid` = ' . $id_lot_pdt_froid;
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode

	// Retourne la composition d'une palette si elle a au moins un produit mixte
	public function getCompositionPaletteMixte(Palette $palette) {

		$query_compos = 'SELECT pc.`id`, pc.`id_palette`, pc.`id_client`, pc.`id_produit`, pc.`poids`, pc.`nb_colis`, pc.`date`, pc.`id_user`, pc.`supprime`, pc.`id_lot_pdt_froid`, pc.`id_lot_regroupement`, pc.`id_frais`, pc.`id_lot_hors_stock`, pc.`archive`,
       								CONCAT(u.`prenom`, " ", u.`nom`) AS nom_user, pt.`nom` AS nom_produit, t.`nom` AS nom_client, pc.`id_lot_pdt_negoce`
								FROM `pe_palette_composition` pc 
									JOIN `pe_users` u ON u.`id` = pc.`id_user`
									JOIN `pe_produits` p ON p.`id` = pc.`id_produit`
									JOIN `pe_tiers` t ON t.`id` = pc.`id_client`
									LEFT JOIN `pe_produit_trad` pt ON pt.`id_produit` = p.`id` AND pt.`id_langue` = 1
								WHERE  pc.`supprime` = 0  AND pc.`id_palette` = ' . $palette->getId() . '
								AND pc.`id_produit` IN (
									SELECT `id` FROM `pe_produits` WHERE `actif` = 1 AND `supprime` = 0 AND `mixte` = 1
								)
								ORDER BY pc.`date` ASC ';

		$query2 = $this->db->prepare($query_compos);
		$query2->execute();

		$compos = [];

		foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {
			$compos[] = new PaletteComposition($donnee2);
		}

		return $compos;

	} // FIN méthode

	// Retourne le nb de compos pour un id_lot_pdt_froid donné
	public function getNbComposByIdLotPdtFroid($id_lot_pdt_froid, $getIds = false) {

		$query_get_compos = 'SELECT `id` FROM `pe_palette_composition` WHERE `supprime` = 0 AND `id_lot_pdt_froid` = ' . (int)$id_lot_pdt_froid;
		$query = $this->db->prepare($query_get_compos);
		$query->execute();
		$donnees = $query->fetchAll(PDO::FETCH_ASSOC);

		return $getIds ? $donnees : count($donnees);

	} // FIN méthode

	// met à jour le poids et le nb de colis d'un produitFroid dans sa compo (si unique) :
	public function updPaletteQteCompoByIdPdtFroid($id_lot_pdt_froid, $poids, $nb_colis, $id_palette) {

		$nbCompos = $this->getNbComposByIdLotPdtFroid($id_lot_pdt_froid);

		if (count($nbCompos) != 1) { return false; }

		$compos = $this->getNbComposByIdLotPdtFroid($id_lot_pdt_froid, true);

		$compo = $this->getComposition($compos[0]['id']);
		if (!$compo instanceof PaletteComposition) { return false; }

		$compo->setId_palette($id_palette);
		$compo->setPoids($poids);
		$compo->setNb_colis($nb_colis);

		if (!$this->savePaletteComposition($compo)) { return false; }

		// On met à jour le statut de la palette
		$this->updStatutPalette($id_palette);
		return true;

	} // FIN méthode


	// Retourne le dernier ID de palette en fonction du numéro
	public function getLastIdPaletteByNumero($numero) {

		$query_palette = 'SELECT `id` FROM `pe_palettes` WHERE `numero` = ' . (int)$numero . ' ORDER BY `date` DESC LIMIT 0,1';
		$query = $this->db->prepare($query_palette);

		$query->execute();

		$donnee = $query->fetch();

		return $donnee && isset($donnee['id']) ? intval($donnee['id']): 0;

	} // FIN méthode

	// Met à jour le total d'un produit froid (poids, nb_colis) en fonction de la somme de ses compos
	public function majTotauxPdtFroidFromCompos($id_lot_pdt_froid) {

		$query_sommes = 'SELECT SUM(`poids`) AS poids, SUM(`nb_colis`) AS nb_colis FROM `pe_palette_composition` WHERE `id_lot_pdt_froid` = ' . (int)$id_lot_pdt_froid;
		$query = $this->db->prepare($query_sommes);

		$query->execute();

		$donnee = $query->fetch();
		$poids =  $donnee && isset($donnee['poids']) ? floatval($donnee['poids']): 0.0;
		$nb_colis =  $donnee && isset($donnee['nb_colis']) ? intval($donnee['nb_colis']): 0;

		if ($poids < 0.01 || $nb_colis < 1) { return false; }

		$query_upd = 'UPDATE `pe_froid_produits` SET `poids` = ' . $poids . ', `nb_colis` = ' . $nb_colis . ' WHERE `id_lot_pdt_froid` = ' . $id_lot_pdt_froid;
		$query2 = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query2->execute();

	} // FIN méthode

	// Met à jour le total d'un produit de négoce (poids, nb_cartons) en fonction de la somme de sa compo
	public function majTotauxPdtNegoceFromCompos($id_lot_pdt_negoce) {

		$query_sommes = 'SELECT SUM(`poids`) AS poids, SUM(`nb_colis`) AS nb_colis FROM `pe_palette_composition` WHERE `id_lot_pdt_negoce` = ' . (int)$id_lot_pdt_negoce;
		$query = $this->db->prepare($query_sommes);

		$query->execute();

		$donnee = $query->fetch();
		$poids =  $donnee && isset($donnee['poids']) ? floatval($donnee['poids']): 0.0;
		$nb_cartons =  $donnee && isset($donnee['nb_colis']) ? intval($donnee['nb_colis']): 0;

		if ($poids < 0.01 || $nb_cartons < 1) { return false; }

		$query_upd = 'UPDATE `pe_negoce_produits` SET `poids` = ' . $poids . ', `nb_cartons` = ' . $nb_cartons . ' WHERE `id_lot_pdt_negoce` = ' . $id_lot_pdt_negoce;
		$query2 = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query2->execute();

	} // FIN méthode

	// Passe le statut à 1 (complète) d'une palette si besoin
	public function updStatutPalette($paletteSource) {

		$palette = $paletteSource instanceof Palette ? $paletteSource : $this->getPalette($paletteSource);
		if (!$palette instanceof Palette) { return false; }

		$capacite = intval($this->getCapacitePalettePoids($palette));
		$total = intval($this->getPoidsTotalPalette($palette));

		if ($total >= $capacite) {
			$palette->setStatut(1);
			$this->savePalette($palette);
		}

		return true;

	} // FIN méthode

	// Passe toutes les palettes complètes en statut 1
	public function updStatutToutesPalettes() {

		$palettes = $this->getListePalettes(['statut' => 0]);
		foreach ($palettes as $palette) {
			$this->updStatutPalette($palette);
		}

	} // FIN méthode

	// Retourne le nombre de produits dans une palette
	public function getNbProduitsPalette(Palette $palette, $exclure_bl = false) {

		$query_nb = 'SELECT COUNT(*) AS nb 
						FROM `pe_palette_composition` pc
						JOIN `pe_palettes` p ON p.`id` = pc.`id_palette` ';

		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` AND bll.`supprime` = 0 ' : '';
		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl` ' : '';

		$query_nb.= ' WHERE pc.`supprime` = 0
					  AND p.`supprime` = 0
					  AND p.`id` = ' . $palette->getId();

		$query_nb.= $exclure_bl ? ' AND (bll.`id` IS NULL OR bl.`bt` = 1) ' : '';

		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode

	// Retourne le nombre de palettes par Produit pour un client
	public function getNbPalettesProduit(Produit $pdt, Tiers $client) {

		$query_nb = 'SELECT COUNT(DISTINCT p.`id`) AS nb 
						FROM `pe_palette_composition` pc
						JOIN `pe_palettes` p ON p.`id` = pc.`id_palette`
                        LEFT JOIN `pe_frais` f ON f.`id` = pc.`id_frais` 
                        LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` 
                        LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl` AND bl.`statut` > 0 
					WHERE pc.`supprime` = 0 
                    AND (f.`envoye` IS NULL OR f.`envoye` = 1)
                    AND (bll.`id` IS NULL OR bll.`supprime` = 1 OR bl.`supprime` = 1  OR bl.`bt` = 1) AND pc.`archive` = 0 
					  AND p.`supprime` = 0
					  AND p.`statut` < 3 
					  AND pc.`id_client` = ' . $client->getId() . '
					  AND pc.`id_produit` = ' . $pdt->getId();
		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode

	// Retourne le nombre de colis par produit pour un client
	public function getNbColisProduit(Produit $pdt, Tiers $client, $exclure_bl = false) {

		$query_nb = 'SELECT SUM(pc.`nb_colis`) AS nb 
						FROM `pe_palette_composition` pc
						JOIN `pe_palettes` p ON p.`id` = pc.`id_palette` ';

		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` AND bll.`supprime` = 0 ' : '';

		$query_nb.= ' WHERE pc.`supprime` = 0
					  AND pc.`archive` = 0
					  AND p.`supprime` = 0
					  AND p.`statut` < 3 
					  AND pc.`id_client` = ' . $client->getId() . '
					  AND pc.`id_produit` = ' . $pdt->getId();

		$query_nb.= $exclure_bl ? ' AND bll.`id` IS NULL ' : '';

		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIHN méthode

	// Retourne le poids par produit pour un client
	public function getPoidsProduit(Produit $pdt, Tiers $client, $frais = false, $exclure_bl = false) {

		$query_nb = 'SELECT SUM(pc.`poids`) AS nb 
						FROM `pe_palette_composition` pc
						JOIN `pe_palettes` p ON p.`id` = pc.`id_palette` ';

		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` AND bll.`supprime` = 0 ' : '';

		$query_nb.= ' WHERE pc.`supprime` = 0
					  AND p.`supprime` = 0
					  AND pc.`archive` = 0
					  AND p.`statut` < 3 
					  AND pc.`id_client` = ' . $client->getId() . '
					  AND pc.`id_produit` = ' . $pdt->getId();
		$query_nb.= $frais ? ' AND pc.`id_frais` > 0 ' : '';

		$query_nb.= $exclure_bl ? ' AND bll.`id` IS NULL ' : '';

		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? floatval($donnee['nb']) : 0;

	} // FIHN méthode


	// Retourne le nombre de colis ou blocs en compo d'une palette
	public function getNbColisPalette(Palette $palette, $exclure_bl = false) {

		$query_nb = 'SELECT SUM(pc.`nb_colis`) AS nb 
						FROM `pe_palette_composition` pc
						JOIN `pe_palettes` p ON p.`id` = pc.`id_palette` ';

		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` AND bll.`supprime` = 0 ' : '';
		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl` ' : '';

		$query_nb.= ' WHERE pc.`supprime` = 0 AND pc.`archive` = 0
					  AND p.`supprime` = 0
					  AND p.`id` = ' . $palette->getId();

		$query_nb.= $exclure_bl ? ' AND (bll.`id` IS NULL OR bl.`bt` = 1) ' : '';

		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode

	// Retourne le poids en compo d'une palette
	public function getPoidsPalette(Palette $palette, $exclure_bl = false) {

		$query_nb = 'SELECT SUM(pc.`poids`) AS nb 
						FROM `pe_palette_composition` pc
						JOIN `pe_palettes` p ON p.`id` = pc.`id_palette` ';

		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` AND bll.`supprime` = 0 ' : '';
		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl` ' : '';

		$query_nb.= ' WHERE pc.`supprime` = 0
					  AND pc.`archive` = 0
					  AND p.`supprime` = 0
					  AND p.`id` = ' . $palette->getId();

		$query_nb.= $exclure_bl ? ' AND (bll.`id` IS NULL OR bl.`bt` = 1) ' : '';

		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? floatval($donnee['nb']) : 0;

	} // FIN méthode

	// Enregistre la liaison entre une palette et ses emballages "poids_palette"
	public function savePoidsPalette($id_palette, $donnees) {

		// On purge d'abord les données pour cette palette
		$query_del = 'DELETE FROM `pe_palette_poids_palettes` WHERE `id_palette` = ' . (int)$id_palette;
		$query = $this->db->prepare($query_del);
		$query->execute();
		Outils::saveLog($query_del);
		$query_add = 'INSERT IGNORE INTO `pe_palette_poids_palettes` (`id_palette`, `id_poids_palette`, `qte`) VALUES ';
		$checklen = strlen($query_add);

		// Puis on boucle sur les données pour intégration
		foreach ($donnees as $id_pp => $qte) {

			// On ne retiens pas les quantitiés à 0 (+ sécurité ID)
			if (intval($qte) == 0 || intval($id_pp == 0)) { continue; }
			$query_add.= '('.$id_palette.', '.intval($id_pp).', '.intval($qte).'),';

		} // FIN boucle

		if (strlen($query_add) == $checklen) { return true; } // Si tout à 0, on termine car on a rien à ajouter

		$query_add = substr($query_add,0,-1); // On retire la dernière virgule

		$query2 = $this->db->prepare($query_add);
		Outils::saveLog($query_add);
		return $query2->execute();

	} // FIN méthode

	// Archive les composition et cloture les palettes d'une facture
	public function archiveComposEtCloturePaletteByFacture($id_facture) {

		if (intval($id_facture) == 0) { return false; }

		// On récupère toutes les compos de la facture
		$query_compos = 'SELECT DISTINCT bll.`id_compo`
							FROM `pe_bl_lignes` bll
								JOIN `pe_facture_ligne_bl` flb ON flb.`id_ligne_bl` = bll.`id`
								JOIN `pe_facture_lignes` fl ON fl.`id` = flb.`id_ligne_facture`
							WHERE  bll.`id_compo` > 0 AND fl.`id_facture` = ' . (int)$id_facture;

		$query = $this->db->prepare($query_compos);
		$query->execute();

		$compos = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$id_compo = intval($donnee['id_compo']);
			if ($id_compo == 0) { continue; }
			$compos[] = $id_compo;
		}

		if (empty($compos)) { return false; }

		// On passe les compos en statut "archivées"
		$query_upd = 'UPDATE `pe_palette_composition` SET `archive` = 1 WHERE `id` IN ('.implode(',', $compos).')';
		$query2 = $this->db->prepare($query_upd);
		if (!$query2->execute()) { return false; }
		Outils::saveLog($query_upd);
		// On boucle sur les palettes des compos de la facture
		$query_palettes = 'SELECT DISTINCT `id_palette` FROM `pe_palette_composition` WHERE `id` IN ('.implode(',', $compos).') ';
		$query3 = $this->db->prepare($query_palettes);
		$query3->execute();

		$palettes = [];

		foreach ($query3->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$id_palette = intval($donnee['id_palette']);
			if ($id_palette == 0) { continue; }
			$palettes[] = $id_palette;
		}

		$palettes_cloture = [];

		// Pour chaque palette, on compte s'il reste des compo non archivés
		foreach ($palettes as $id_palette) {

			$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_palette_composition` WHERE `archive` = 0 AND `id_palette` = ' . (int)$id_palette;
			$query4 = $this->db->prepare($query_nb);
			$query4->execute();
			$resCount = $query4->fetch();

			$nbEncore = $resCount && isset($resCount['nb']) ? intval($resCount['nb']) : 0;

			// Si la palette n'a plus rien de non archivé, alors on la clos
			if ($nbEncore == 0) {
				$palettes_cloture[] = $id_palette;
			}
		} // FIN boucle

		// Boucle sur les palettes à clore
		foreach ($palettes_cloture as $id_palette) {

			$query_upd_pal = 'UPDATE `pe_palettes` SET `statut` = 2 WHERE `statut` < 2 AND `id` = ' . $id_palette;
			$query5 = $this->db->prepare($query_upd_pal);
			$query5->execute();
			Outils::saveLog($query_upd_pal);
		}

		return true;

	} // FIN méthode


	// Raz les poids et nb de colis à 0 des compos pour un id_lot_pdt_froid donné (mise en attente)
	public function razPoidsColisCompoByPdtFroid($froidProduit) {

		$id_lot_pdt_froid = $froidProduit instanceof FroidProduit ? $froidProduit->getId_lot_pdt_froid() : intval($froidProduit);

		$query_upd = 'UPDATE `pe_palette_composition` SET `poids` = 0, `nb_colis` = 0, `supprime` = 0 WHERE `id_lot_pdt_froid` = ' . $id_lot_pdt_froid;
		$query = $this->db->prepare($query_upd);
		return $query->execute();

	} // FIN méthode


	public function checkNumeroPaletteExiste($num_palette, $id_client = 0) {

		$query_nb = 'SELECT COUNT(DISTINCT p.`id`) AS nb
						FROM `pe_palettes` p ';
		$query_nb.= $id_client > 0 ? ' JOIN `pe_palette_composition` pc ON pc.`id_palette` = p.`id`' : '';
		$query_nb.= ' WHERE p.`supprime` = 0 
						  	AND p.`numero` = '.(int)$num_palette;
		$query_nb.= $id_client > 0 ? ' AND pc.`id_client` = '.(int)$id_client.'	AND (pc.`supprime` = 0 OR pc.`archive` = 1) GROUP BY p.`id`' : '';

		$query = $this->db->prepare($query_nb);
		$query->execute();
		$res = $query->fetch();

		return $res && isset($res['nb']) ? intval($res['nb']) : 0;

	} // FIN méthode

	public function getIdPaletteByNumeroAndClient($num_palette, $id_client) {

		$query_id = 'SELECT p.`id` 
						FROM `pe_palettes` p 
						    JOIN `pe_palette_composition` pc ON pc.`id_palette` = p.`id` 
						WHERE p.`supprime` = 0 
						  	AND p.`numero` = '.(int)$num_palette.'
						  	AND pc.`id_client` = '.(int)$id_client.'
						  	AND (pc.`supprime` = 0 OR pc.`archive` = 1)
						GROUP BY p.`id`
						LIMIT 0,1';

		$query = $this->db->prepare($query_id);
		$query->execute();
		$res = $query->fetch();

		return $res && isset($res['id']) ? intval($res['id']) : 0;

	} // FIN méthode


	public function updatePdtCompo($id_lot_pdt_froid, $id_pdt) {

		$query_update = 'UPDATE `pe_palette_composition` SET `id_produit` = '.(int)$id_pdt.' WHERE `id_lot_pdt_froid` = ' . (int)$id_lot_pdt_froid;
		$query = $this->db->prepare($query_update);
		return $query->execute();

	} // FIN méthode

} // FIN classe