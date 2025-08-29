<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Manager de l'Objet Tiers
------------------------------------------------------*/
class TiersManager {

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

	// Retourne un tiers par son ID
	public function getTiers($id) {

		$query_tiers = 'SELECT t.`id`, t.`nom`, t.`tva_intra`, t.`actif`, t.`supprime`, t.`numagr`, t.`date_add`, t.`date_maj`, tt.`type`, tt.`id_famille`, ft.`nom` AS famille, t.`visibilite_palettes`, t.`palette_suiv`, t.`id_groupe`, t.`id_transporteur`, t.`id_langue`, LOWER(l.`iso`) AS langue_iso, t.`bl_chiffre`, t.`nb_ex_bl`, t.`nb_ex_fact`, t.`echeance`, t.`code_comptable`, t.`message`, t.`tva`, t.`ean`, t.`stk_type`, t.`regroupement`, t.`code`, t.`solde_palettes`, t.`solde_crochets`
							FROM `pe_tiers` t
								JOIN `pe_tiers_types` tt ON tt.`id_tiers` = t.`id`
								LEFT JOIN `pe_tiers_familles` ft ON ft.`id` = tt.`id_famille`
								LEFT JOIN `pe_langues` l ON l.`id` = t.`id_langue`
						  WHERE t.`id` = :id';
		$query = $this->db->prepare($query_tiers);
		$query->bindValue(':id', (int)$id);
		$query->execute();

		$donnee = $query->fetch();

		$tiers = $donnee && !empty($donnee) ? new Tiers($donnee) : false;
		if (!$tiers) { return false; }

		$adressesManager = new AdresseManager($this->db);
		$contatcsManager = new ContactManager($this->db);

		// On rattache les adresses et les contacts
		$adresses = $adressesManager->getListeAdresses(['id_tiers' => $tiers->getId()]);
		$contacts = $contatcsManager->getListeContacts(['id_tiers' => $tiers->getId()]);
		$tiers->setAdresses($adresses);
		$tiers->setContacts($contacts);

		return $tiers;

	} // FIN méthode

	// Retourne une famille de tiers par son ID
	public function getTiersFamille($id) {

		$query_fam = 'SELECT `id`, `nom`, `supprime` FROM `pe_tiers_familles` WHERE `id` = ' . intval($id);

		$query = $this->db->prepare($query_fam);
		$query->execute();

		$donnee = $query->fetch();

		$famille = $donnee && !empty($donnee) ? new TiersFamille($donnee) : false;

		if (!$famille) { return false; }

		// On récupère le nombre de tiers non supprimés associés à cette famille
		$nb = $this->getNbTiersFamille($famille);

		$famille->setNb_tiers($nb);

		return $famille;

	} // FIN méthode


	// Retourne le nombre de tiers non supprimés associés à une famille de tiers
	public function getNbTiersFamille(TiersFamille $famille, $meme_supprimes = false) {

		$query_nb = 'SELECT COUNT(tt.`id`) AS nb FROM `pe_tiers_types` tt
							JOIN `pe_tiers` t ON t.`id` = tt.`id_tiers`
						WHERE tt.`id_famille` = ' . $famille->getId();

		$query_nb.= !$meme_supprimes ? ' AND t.`supprime` = 0' : '';

		$query2 = $this->db->prepare($query_nb);
		$query2->execute();

		$donnee2 = $query2->fetch();
		return $donnee2 && !empty($donnee2) ? intval($donnee2['nb']) : 0;

	} // FIN méthode

	// Retourne la liste des Tiers
	public function getListeTiers($params) {

		$show_inactifs 	= isset($params['show_inactifs']) 	? boolval($params['show_inactifs'])  		: false;
		$show_supprime 	= isset($params['show_supprime']) 	? boolval($params['show_supprime'])  		: false;
		$stk_vue 		= isset($params['stk_vue']) 		? boolval($params['stk_vue'])  				: false;

		$cltstock 		= isset($params['cltstock']) 		? intval($params['cltstock'])  				: 0; // Filtre affichage liste admin sur clients/stocks
		$stk_type 		= isset($params['stk_type']) 		? intval($params['stk_type'])  				: 0;
		$type 			= isset($params['type']) 			? intval($params['type']) 					: 0;
		$famille		= isset($params['famille']) 		? intval($params['famille']) 				: 0;

		$recherche 		= isset($params['recherche']) 		? trim(strip_tags($params['recherche'])) 	: '';
		$pays 			= isset($params['pays']) 			? trim(strip_tags($params['pays'])) 		: '';
		$id_pays 		= isset($params['id_pays']) 		? intval($params['id_pays'])				: 0;
		$activation		= isset($params['actif']) 			? intval($params['actif']) 					: -1;

		$visibilite_palettes = isset($params['visibilite_palettes']) ? intval($params['visibilite_palettes']) : -1;

		$id_produit = isset($params['id_produit']) 	? intval($params['id_produit']) : 0;
		$id_palette = isset($params['id_palette']) 	? intval($params['id_palette']) : 0;
		$id_froid 	= isset($params['id_froid']) 	? intval($params['id_froid']) 	: 0;
		$id_lot 	= isset($params['id_lot']) 		? intval($params['id_lot']) 	: 0;
		$id_groupe 	= isset($params['id_groupe']) 	? intval($params['id_groupe']) 	: 0;

		// Pagination
		$start 			= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 			= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		// Tri
		$orderby_champ = isset($params['orderby_champ']) ? $params['orderby_champ'] : 't.`nom`';
		$orderby_sens = isset($params['orderby_sens']) ? $params['orderby_sens'] : 'ASC';

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS 
                        DISTINCT t.`id`, t.`nom`, t.`tva_intra`, t.`actif`, t.`supprime`, t.`date_add`, t.`date_maj`, tt.`type`, tt.`id_famille`, ft.`nom` AS famille, t.`visibilite_palettes`, t.`palette_suiv`, t.`id_groupe`, tg.`nom` AS nom_groupe,  t.`id_transporteur`, t.`bl_chiffre`, t.`nb_ex_bl`, t.`nb_ex_fact`, t.`echeance`, t.`code_comptable`, t.`message`, t.`tva`, t.`ean`, t.`stk_type`, t.`regroupement`, t.`code`,  t.`solde_palettes`, t.`solde_crochets`
							FROM `pe_tiers` t
								JOIN `pe_tiers_types` tt ON tt.`id_tiers` = t.`id`
								LEFT JOIN `pe_tiers_familles` ft ON ft.`id` = tt.`id_famille` 
								LEFT JOIN `pe_tiers_groupes` tg ON tg.`id` = t.`id_groupe` AND tg.`supprime` = 0
								';

		$query_liste.=$id_pays > 0 ? ' JOIN `pe_adresses` a ON a.`id_tiers` = t.`id`' : '';

		$query_liste.= $id_produit > 0 || $id_palette > 0 || $id_froid > 0 || $id_lot > 0 ? ' JOIN `pe_palette_composition` pc ON pc.`id_client` = t.`id` ' : '';
		$query_liste.= $id_produit > 0 || $id_palette > 0 || $id_froid > 0 || $id_lot > 0 ? ' JOIN `pe_froid_produits` fp ON fp.`id_lot_pdt_froid` = pc.`id_lot_pdt_froid` ' : '';
		$query_liste.= $id_produit > 0 || $id_palette > 0 || $id_froid > 0 || $id_lot > 0 ? ' JOIN `pe_froid` f ON f.`id` = fp.`id_froid` ' : '';
		$query_liste.= $recherche != '' ? ' LEFT JOIN `pe_contacts` c ON c.`id_tiers` = t.`id` ' : '';

		$query_liste.= ' WHERE 1 ';
		$query_liste.= $recherche != '' ? 'AND (t.`nom` LIKE "%'.$recherche.'%" OR c.`nom` LIKE "%'.$recherche.'%" OR c.`prenom` LIKE "%'.$recherche.'%") ' : '';
		$query_liste.= $type > 0 ? 'AND tt.`type` = '.$type.'  ' : '';
		$query_liste.= $famille > 0 ? 'AND tt.`id_famille` = '.$famille.'  ' : '';
		$query_liste.= !$show_inactifs != '' ? 'AND t.`actif` = 1 ' : '';
		$query_liste.= !$show_supprime != '' ? 'AND t.`supprime` = 0 ' : '';
		$query_liste.= $activation > -1 ? 'AND t.`actif` = '.$activation.' ' : '';
		$query_liste.= $visibilite_palettes > -1 ? 'AND t.`visibilite_palettes` = '.$visibilite_palettes.' ' : '';
		$query_liste.= $id_pays > 0 ? ' AND a.`id_pays` = ' . $id_pays : '';
		$query_liste.= $id_groupe > 0 ? ' AND t.`id_groupe` = ' . $id_groupe : '';

		$query_liste.= $id_produit > 0 || $id_palette > 0 || $id_froid > 0 || $id_lot > 0 ? 'AND f.`date_sortie` IS NOT NULL ' : '';

		$query_liste.= $id_produit > 0 ? 'AND pc.`id_produit` = '.$id_produit.' ' : '';
		$query_liste.= $id_palette > 0 ? 'AND pc.`id_palette` = ' . $id_palette : '';
		$query_liste.= $id_froid   > 0 ? 'AND f.`id` = ' . $id_froid : '';
		$query_liste.= $id_lot     > 0 ? 'AND fp.`id_lot` = ' . $id_lot : '';
		$query_liste.= $stk_vue        ? 'AND t.`stk_type` > 0 ' : '';
		$query_liste.= $stk_type > 0   ? 'AND t.`stk_type` = ' . $stk_type : '';
		$query_liste.= $cltstock == 1   ? 'AND t.`stk_type` < 2 ' : '';
		$query_liste.= $cltstock == 2   ? 'AND t.`stk_type` = 2 ' : '';

		$query_liste.= ' ORDER BY '.$orderby_champ.' ' . $orderby_sens ;
		$query_liste.= ' LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		$adressesManager = new AdresseManager($this->db);
		$contatcsManager = new ContactManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$tiers =  new Tiers($donnee);

			// On rattache les adresses et les contacts
			$paramsCtcs = ['id_tiers' => $tiers->getId()];
			if ($pays != '') { $paramsCtcs['pays'] = $pays; }
			if ($id_pays > 0) { $paramsCtcs['id_pays'] = $id_pays; }

			$adresses = $adressesManager->getListeAdresses($paramsCtcs);
			$contacts = $contatcsManager->getListeContacts($paramsCtcs);

			$tiers->setAdresses($adresses);
			$tiers->setContacts($contacts);

			$liste[] = $tiers;
		}
		return $liste;

	} // FIN getListe


	// Retourne la liste des clients
	public function getListeClients($params = []) {

		$params['type'] = 1; // 1 = Type client
		return $this->getListeTiers($params);

	} // FIN méthode

	// Retourne la liste des clients
	public function getListeFournisseurs($params = []) {

		$params['type'] = 2; // 2 = Type fournisseur
		return $this->getListeTiers($params);

	} // FIN méthode

	// Retourne la liste des transporteurs
	public function getListeTransporteurs($params = []) {

		$params['type'] = 3; // 3 = Type transporteur
		return $this->getListeTiers($params);

	} // FIN méthode


	// Enregistre un Tiers
	public function saveTiers(Tiers $objet) {
		
		$table		= 'pe_tiers';		// Nom de la table
		$champClef	= 'id';				// Nom du champ clef primaire
		// FIN Configuration

		$getter		= 'get'.ucfirst(strtolower($champClef));
		$setter		= 'set'.ucfirst(strtolower($champClef));
		
		if ($objet->$getter() == '' && !empty($objet->attributs)) {

			// Erreur si type de tiers non défini !
			if (intval($objet->getType()) == 0) { return false;	}

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

				Outils::saveLog($query_log);
				$id_tiers = $this->db->lastInsertId();
				$objet->$setter($id_tiers);

				$query_instype = 'INSERT INTO `pe_tiers_types` (`id_tiers`, `id_famille`, `type`) VALUES ('.$id_tiers.', '.intval($objet->getId_famille()).', '.intval($objet->getType()).')';
				$query2 = $this->db->prepare($query_instype);
				if (!$query2->execute()) { return false; }
				Outils::saveLog($query_instype);
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

				// Mise à jour de la famille
				$query_updfam = 'UPDATE `pe_tiers_types` SET `id_famille` = '.intval($objet->getId_famille()).' WHERE `id_tiers` = ' . $objet->getId();
				$query2 = $this->db->prepare($query_updfam);
				if (!$query2->execute()) { return false; }
				Outils::saveLog($query_updfam);
				return true;
			} catch(PDOExeption $e) {return false;}
		}		
		return false;
		
	} // FIN méthode

	// Enregistre une famille de Tiers
	public function saveTiersFamille(TiersFamille $objet) {

		$table		= 'pe_tiers_familles';		// Nom de la table
		$champClef	= 'id';						// Nom du champ clef primaire
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


	// Vérifie si un tiers existe déjà avec ce nom
	public function checkExisteDeja($nom, $id_exclu = 0) {

		$query_check = 'SELECT COUNT(*) AS nb FROM `pe_tiers` WHERE `supprime` = 0 AND (LOWER(`nom`) = :nom )';
		$query_check.= (int)$id_exclu > 0 ? ' AND `id` != ' . (int)$id_exclu : '';

		$query = $this->db->prepare($query_check);
		$query->bindValue(':nom', trim(strtolower($nom)));
		$query->execute();

		$donnee = $query->fetch(PDO::FETCH_ASSOC);

		if (intval($donnee['nb']) > 0) {
			return true;
		}
		return false;

	} // FIN méthode

	// Reourne la liste des pays des tiers
	public function getPaysTiers() {

		$query_liste = 'SELECT DISTINCT TRIM(UPPER(`pays`)) AS pays FROM `pe_tiers` ORDER BY `pays` ASC';

		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			if (isset($donnee['pays']) && trim($donnee['pays']) != '') {
				$liste[] = $donnee['pays'];
			}
		}
		return $liste;

	} // FIN méthode

	// Retourne la liste des familles de tiers (non supprimés)
	public function getTiersFamillesListe() {

		$query_liste = 'SELECT `id`, `nom`, `supprime` FROM `pe_tiers_familles` WHERE `supprime` = 0 ORDER BY `nom` ASC';

		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$tmp = new TiersFamille($donnee);
			$nb = $this->getNbTiersFamille($tmp);
			$tmp->setNb_tiers($nb);
			$liste[] = $tmp;
		}
		return $liste;

	} // FIN méthode

	// Supprime en BDD une famille de tiers (lorsqu'aucun tiers n'est rattaché meme en flag supprimé)
	public function supprTiersFamille(TiersFamille $famille) {

		$query_del1 = 'DELETE FROM `pe_tiers_types` WHERE `id_famille` = ' . $famille->getId();
		$query1 = $this->db->prepare($query_del1);
		$query1->execute();
		Outils::saveLog($query_del1);
		// Puis on passe a l'essentiel, on supprime la ligne de la table
		$query_del2 = 'DELETE FROM `pe_tiers_familles` WHERE `id` = ' . $famille->getId();
		$query2 = $this->db->prepare($query_del2);
		Outils::saveLog($query_del2);
		return $query2->execute();

	} // FIN méthode

	// Retourne l'ID de la famille de tiers "Consommables" pour BO familles de consommables liste des fournisseurs
	public function getIdFamilleTiersConsommable() {

		$query_id = 'SELECT `id` FROM `pe_tiers_familles` WHERE `nom` LIKE "%consommable%" ';

		$query = $this->db->prepare($query_id);
		$query->execute();
		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);

		return isset($donnee[0]) && isset($donnee[0]['id']) ? intval($donnee[0]['id']) : 0;

	} // FIN méthode

	// Retourne la liste des TiersGroupe
	public function getListeTiersGroupes() {

		$query_liste = "SELECT `id`, `nom`, `actif`, `supprime`, `date_add`, `date_maj` 
							FROM `pe_tiers_groupes`
							WHERE `supprime` = 0
						ORDER BY `id` DESC";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$tmp = new TiersGroupe($donnee);

			// On rattache les tiers du groupe
			$tiers = $this->getListeTiers(['id_groupe' => $tmp->getId()]);
			if (is_array($tiers)) {
				$tmp->setTiers($tiers);
			}
			$liste[] = $tmp;
		}
		return $liste;

	} // FIN liste des TiersGroupe


	// Retourne un TiersGroupe
	public function getTiersGroupe($id) {

		$query_object = "SELECT `id`, `nom`, `actif`, `supprime`, `date_add`, `date_maj` 
                FROM `pe_tiers_groupes` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new TiersGroupe($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get TiersGroupe

	// Enregistre & sauvegarde (Méthode Save)
	public function saveTiersGroupe(TiersGroupe $objet) {

		$table      = 'pe_tiers_groupes'; // Nom de la table
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

	// Retourne les palettes non expédiées du client
	public function getPalettesClient(Tiers $client, $frais = false) {

		$query_ids = 'SELECT DISTINCT p.`id`, p.`numero`
						FROM `pe_palette_composition` pc
						JOIN `pe_palettes` p ON p.`id` = pc.`id_palette`
					WHERE pc.`supprime` = 0
					  AND pc.`archive` = 0
					  AND p.`supprime` = 0
					  AND p.`statut` < 3 ';

		$query_ids.= !$frais ? ' AND pc.`id_client` = ' . $client->getId() : ' AND p.`id_client` = ' . $client->getId();
		$query_ids.= !$frais ? ' AND pc.`id_frais` = 0 ' : ' AND pc.`id_frais` > 0 ';
		$query_ids.= ' ORDER BY p.`numero`, p.`id` ';

		$query = $this->db->prepare($query_ids);
		$query->execute();
		$liste = [];

		$palettesManager = new PalettesManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$id = isset($donnee['id']) ? intval($donnee['id']) : 0;
			if ($id == 0) { continue; }
			$palette = $palettesManager->getPalette($id);
			if (!$palette instanceof Palette) { continue; }
			$liste[] = $palette;
		}
		return $liste;

	} // FIN méthode

	// Retourne les produits non expédiés du client
	public function getProduitsClient(Tiers $client, $distinct_palette = true, $frais = false) {

		$order = $distinct_palette ? ' p.`numero`,' : '';

		$query_ids = 'SELECT DISTINCT pc.`id_produit` AS id '.$distinct_palette.'
						FROM `pe_palette_composition` pc
						JOIN `pe_palettes` p ON p.`id` = pc.`id_palette`
						LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id`
						LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl`
					WHERE pc.`supprime` = 0
					  AND pc.`archive` = 0
					  AND p.`supprime` = 0
					  AND p.`statut` < 3 
					 AND (bll.`id` IS NULL OR bl.`bt` = 1) 
					  AND pc.`id_client` = ' . $client->getId();

		$query_ids.= !$frais ? ' AND pc.`id_frais` = 0 ' : ' AND pc.`id_frais` > 0 ';

		$query_ids.= '  ORDER BY '.$order.' pc.`id_produit` ';
		$query = $this->db->prepare($query_ids);
		$query->execute();
		$liste = [];

		$produitsManager = new ProduitManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$id = isset($donnee['id']) ? intval($donnee['id']) : 0;
			if ($id == 0) { continue; }
			$pdt = $produitsManager->getProduit($id);
			if (!$pdt instanceof Produit) { continue; }
			$liste[] = $pdt;
		}
		return $liste;

	} // FIN méthode

	// Retourne le nombre de palettes distinctes non expédiées d'un client
	public function getNbPalettesClient(Tiers $client, $frais = false, $exclure_bl = false) {

		$query_nb = 'SELECT COUNT(DISTINCT pc.`id_palette`) AS nb 
						FROM `pe_palette_composition` pc
						JOIN `pe_palettes` p ON p.`id` = pc.`id_palette` ';

		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` AND bll.`supprime` = 0 ' : '';
		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl` ' : '';

		$query_nb.= ' WHERE pc.`supprime` = 0
						  AND pc.`archive` = 0
						  AND p.`supprime` = 0
						  AND p.`statut` < 3  ';
		$query_nb.= $frais ? 'AND p.`id_client` = ' . $client->getId() : ' AND pc.`id_client` = ' . $client->getId();
		$query_nb.=	 '	  AND pc.`id_frais` ' ;
		$query_nb.= $frais ? '> 0 ' : ' = 0';

		$query_nb.= $exclure_bl ? ' AND (bll.`id` IS NULL OR bl.`bt` = 1 ) ' : '';

		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode

	// Retourne le nombre de colis ou blocs en compo non expédiée d'un client
	public function getNbColisClient(Tiers $client, $frais = false, $exclure_bl = false) {

		$query_nb = 'SELECT SUM(pc.`nb_colis`) AS nb 
						FROM `pe_palette_composition` pc
						JOIN `pe_palettes` p ON p.`id` = pc.`id_palette` ';
		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` AND bll.`supprime` = 0 ' : '';
		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl` ' : '';

		$query_nb.= ' WHERE pc.`supprime` = 0
					  AND pc.`archive` = 0
					  AND p.`supprime` = 0
					  AND p.`statut` < 3 
					  AND pc.`id_client` = ' . $client->getId(). '
						AND pc.`id_frais` ' ;

		$query_nb.= $frais ? ' > 0 ' : ' = 0';
		$query_nb.= $exclure_bl ? ' AND (bll.`id` IS NULL OR bl.`bt` = 1) ' : '';

		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode

	// Retourne le poids total en compo non expédiée d'un client
	public function getPoidsClient(Tiers $client, $frais = false, $exclure_bl = false) {

		$query_nb = 'SELECT SUM(pc.`poids`) AS nb 
						FROM `pe_palette_composition` pc
						JOIN `pe_palettes` p ON p.`id` = pc.`id_palette` ';

		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` AND bll.`supprime` = 0 ' : '';
		$query_nb.= $exclure_bl ? ' LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl` ' : '';

		$query_nb.= ' WHERE pc.`supprime` = 0
					  AND p.`supprime` = 0
					  AND pc.`archive` = 0
					  AND p.`statut` < 3 
					  AND pc.`id_client` = ' . $client->getId(). '
					  AND pc.`id_frais` ' ;

		$query_nb.= $frais ? '> 0 ' : ' = 0';
		$query_nb.= $exclure_bl ? ' AND (bll.`id` IS NULL OR bl.`bt` = 1) ' : '';

		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? floatval($donnee['nb']) : 0;

	} // FIN méthode


	// Retourne le nombre de produits dans les palettes en stock d'un client
	public function getNbProduitsClient(Tiers $client, $frais = false) {

		$query_nb = 'SELECT COUNT(DISTINCT pc.`id`) AS nb 
						FROM `pe_palette_composition` pc
						JOIN `pe_palettes` p ON p.`id` = pc.`id_palette`
						LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` AND bll.`supprime` = 0
						LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl` 
					WHERE pc.`supprime` = 0
					  AND p.`supprime` = 0
					  AND pc.`archive` = 0
					  AND (bll.`id` IS NULL OR bl.`bt` = 1)
					  AND pc.`id_client` = ' . $client->getId(). '
					  AND pc.`id_frais` ' ;

		$query_nb.= $frais ? ' > 0 ' : ' = 0';

		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode

	//Retourne le prochain code comptable possible
	public function getNextCodeComptable() {

		$query_liste = 'SELECT DISTINCT `code_comptable` AS cc FROM `pe_tiers` WHERE `code_comptable` IS NOT NULL AND TRIM(`code_comptable`) != ""';

		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$cc = intval(preg_replace("/[^0-9]/", "", $donnee['cc']));
			if ($cc > 999 && $cc < 10000) {
				$liste[] = $cc;
			}
		}

		$suivant =  max($liste) + 1;
		return 'C'.$suivant;

	} // FIN méthode

	// Retourne la liste des clients ayant un BL de généré mais dont la facture n'est pas payée
	public function getListeClientsExpedition() {

		$query_liste = 'SELECT DISTINCT t.`id`, t.`nom`, t.`tva_intra`, t.`actif`, t.`supprime`, t.`date_add`, t.`date_maj`, t.`visibilite_palettes`, t.`palette_suiv`, t.`id_groupe`, t.`id_transporteur`, t.`bl_chiffre`, t.`nb_ex_bl`, t.`nb_ex_fact`, t.`echeance`, t.`code_comptable`, t.`message`, t.`tva`, t.`ean`, t.`stk_type`, t.`regroupement`, t.`code`
							FROM `pe_tiers` t
								JOIN `pe_bl` b ON b.`id_tiers_livraison` = t.`id`
							WHERE t.`supprime` = 0 AND b.`supprime` = 0 AND b.`statut` = 2 AND b.`date` >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)  ORDER BY t.`nom`';

		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$id_client = isset($donnee['id']) ? intval($donnee['id']) : 0;

			// Si le client a au moins une facture non reglée on l'intègre sinon, on n'en tiens pas compte
			if ($this->hasClientFacturesNonReglees($id_client)) {
				$liste[] = new Tiers($donnee);
			}
		}
		return $liste;

	} // FIN méthode

	// Retourne si les factures du client on toute au moins un règlement et qu'il y a au moins une facture
	public function hasClientFacturesNonReglees($id_client) {

		$query_nb = 'SELECT COUNT(f.`id`) + (SELECT IF(COUNT(*) = 0, 1,0) FROM  `pe_factures` WHERE `id_tiers_livraison` = '.(int)$id_client.') AS nb 
						FROM `pe_factures`f
						    LEFT JOIN `pe_facture_reglements` r ON r.`id_facture` = f.`id` 
						    WHERE f.`supprime` = 0 AND  r.`id` IS NULL AND f.`id_tiers_livraison` = ' . (int)$id_client;

		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch(PDO::FETCH_ASSOC);

		if ($donnee && isset($donnee) && isset($donnee['nb']) && intval($donnee['nb']) > 0) {
			return true;
		}
		return false;

	} // FIN méthode

	// Retourne la liste des mouvements de palettes et crochets
	public function getListeEchangesPalettesCrochets($params = []) {

		$id_trans = isset($params['id_trans']) ? intval($params['id_trans']) : 0;
		$date_du = isset($params['date_du']) && Outils::verifDateSql($params['date_du']) ? $params['date_du'] : '';
		$date_au = isset($params['date_au']) && Outils::verifDateSql($params['date_au']) ? $params['date_au'] : '';

		$query_liste = 'SELECT pop.`date`, t.`nom` as nom_transporteur, pop.`crochets_recus`, pop.`crochets_rendus`, pop.`palettes_recues`, pop.`palettes_rendues`, 
                           "exp" AS poste, CONCAT(LOWER(u.`prenom`), " ", UPPER(u.`nom`)) AS nom_user, CONCAT("exp/",pop.`id`) AS signature, pop.`date_add` AS date_tri
							FROM  `pe_prp_op` pop
							     JOIN `pe_tiers` t ON pop.`id_transporteur` = t.`id`
							     JOIN `pe_users` u ON pop.`id_user` = u.`id`
							WHERE (pop.`crochets_recus` + pop.`crochets_rendus` + pop.`palettes_recues` + pop.`palettes_rendues` > 0) 
							';

		$query_liste.= $id_trans > 0 ? ' AND pop.`id_transporteur` = ' . $id_trans : '';
		$query_liste.= $date_du != '' ? ' AND pop.`date` >= "' . $date_du.'" ' : '';
		$query_liste.= $date_au != '' ? ' AND pop.`date` <= "' . $date_au.'" ' : '';

		$query_liste.= ' UNION ';

		$query_liste.= 'SELECT DATE(lr.`date_confirmation`) AS date, t.`nom` as nom_transporteur, lr.`crochets_recus`, lr.`crochets_rendus`, 0 AS palettes_recues, 0 AS palettes_rendues,
       			IF (lr.`id_lot` > 0,  "rcp",  "exp")
                           AS poste, CONCAT(LOWER(u.`prenom`), " ", UPPER(u.`nom`)) AS nom_user, "" AS signature, lr.`date_confirmation` AS date_tri
							FROM  `pe_lot_reception` lr
							     JOIN `pe_tiers` t ON lr.`id_transporteur` = t.`id`
							     JOIN `pe_users` u ON lr.`id_user` = u.`id`
							WHERE (lr.`crochets_recus` + lr.`crochets_rendus` > 0) ';

		$query_liste.= $id_trans > 0 ? ' AND lr.`id_transporteur` = ' . $id_trans : '';
		$query_liste.= $date_du != '' ? ' AND DATE(lr.`date_confirmation`) >= "' . $date_du.'" ' : '';
		$query_liste.= $date_au != '' ? ' AND DATE(lr.`date_confirmation`) <= "' . $date_au.'" ' : '';

		$query_liste.= ' UNION ';

		$query_liste.= 'SELECT rp.`date_retour` AS date, t.`nom` as nom_transporteur, 0 AS crochets_recus, 0 AS crochets_rendus, rp.`palettes_recues`, rp.`palettes_rendues`, 
                           "rcp" AS poste, CONCAT(LOWER(u.`prenom`), " ", UPPER(u.`nom`)) AS nom_user, CONCAT("rcp/",rp.`id`) AS signature,
       						rp.`date_add` AS date_tri
							FROM  `pe_retour_palettes_rcp` rp
							     JOIN `pe_tiers` t ON rp.`id_transporteur` = t.`id`
							     JOIN `pe_users` u ON rp.`id_user` = u.`id`
							WHERE (rp.`palettes_recues` + rp.`palettes_rendues` > 0) ';

		$query_liste.= $id_trans > 0 ? ' AND rp.`id_transporteur` = ' . $id_trans : '';
		$query_liste.= $date_du != '' ? ' AND rp.`date_retour` >= "' . $date_du.'" ' : '';
		$query_liste.= $date_au != '' ? ' AND rp.`date_retour` <= "' . $date_au.'" ' : '';

		$query_liste.= ' ORDER BY date_tri DESC LIMIT 0,100';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new MouvementPalettesCrochets($donnee);
		}

		return $liste;


	} // FIN méthode

	// Retourne l'ID du client WEB
	public function getId_client_web() {

		$query_id = 'SELECT `id` FROM `pe_tiers` WHERE `code` = "WEB" LIMIT 0,1';
		$query = $this->db->prepare($query_id);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && isset($donnee['id']) ? intval($donnee['id']) : 0;

	} // FIN méthode

	// Retourne l'id du fournisseur Profil Export
	public function getIdProfilExport() {
		$query_id = 'SELECT t.`id` FROM `pe_tiers` t JOIN `pe_tiers_types` tt ON tt.`id_tiers` = t.`id` WHERE t.`nom` LIKE "%PROFIL EXPORT%" AND tt.`type` = 2 LIMIT 0,1';
		$query = $this->db->prepare($query_id);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && isset($donnee['id']) ? intval($donnee['id']) : 0;
	}

} // FIN classe