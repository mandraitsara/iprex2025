<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Manager de l'Objet lot
------------------------------------------------------*/
class LotManager {

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

	// Retourne un lot par son ID
	public function getLot($id) {

		$query_lot = 'SELECT  l.`id`, l.`numlot`, l.`date_add`, l.`date_prod`, l.`date_maj`, l.`date_out`, l.`date_reception`, l.`date_abattage`, l.`id_abattoir`, l.`id_origine`, l.`composition`, l.`composition_viande`, l.`poids_abattoir`, l.`poids_reception`, l.`supprime` , a.`nom` AS nom_abattoir, ot.`nom` AS nom_origine, a.`numagr` AS numagr_abattoir, l.`id_user_maj`,  r.`id` AS reception_id, l.`visible`, l.`id_espece`, e.`nom` AS nom_espece, e.`couleur` as couleur, l.`test_tracabilite`, l.`bizerba`, CONCAT(u.`prenom`, " ", u.`nom`) AS nom_user_maj, l.`id_fournisseur`, t.`nom` AS nom_fournisseur, l.`date_atelier`
							FROM `pe_lots` l
								LEFT JOIN `pe_abattoirs` 		a ON a.`id` = l.`id_abattoir`
								LEFT JOIN `pe_pays`	  			o ON o.`id` = l.`id_origine` 
								LEFT JOIN `pe_pays_trad`		ot ON ot.`id_pays` = l.`id_origine` AND ot.`id_langue` = 1 
								LEFT JOIN `pe_users`  	 		u ON u.`id` = l.`id_user_maj`
								LEFT JOIN `pe_lot_reception`  	r ON l.`id`	= r.`id_lot` 
								LEFT JOIN `pe_produits_especes` e ON e.`id`	= l.`id_espece` 
								LEFT JOIN `pe_tiers` 			t ON t.`id` = l.`id_fournisseur` 
							WHERE l.`id` = ' . $id;

		$query = $this->db->prepare($query_lot);
		$query->execute();

		$donnees = $query->fetch();

		if ($donnees && !empty($donnees)) {

			$lot = new Lot($donnees);

			// On intègre l'objet Reception si on en a un...
			if (intval($donnees['reception_id']) > 0) {
				$receptionManager = new LotReceptionManager($this->db);
				$reception = $receptionManager->getLotReceptionByIdLot($lot->getId());
				if ($reception instanceof LotReception) {
					$lot->setReception($reception);
				}

			} // FIN test Reception

			// On intègre l'array des objets LotVues
			$lotVuesManager = new LotVueManager($this->db);
			$lotVues = $lotVuesManager->getLotVuesByLot($lot->getId());
			$lot->setVues($lotVues);

			return $lot;

		} else {
			return false;
		}
	} // FIN méthode


	// Retourne un lot par son numéro + quantième éventuellement
	public function getLotFromNumero($numlot) {

		$query_lot = 'SELECT  l.`id`, l.`numlot`, l.`date_add`, l.`date_prod`, l.`date_maj`, l.`date_out`, l.`date_reception`, l.`date_abattage`, l.`id_abattoir`, l.`id_origine`, l.`composition`, l.`composition_viande`, l.`poids_abattoir`, l.`poids_reception`, l.`supprime` , a.`nom` AS nom_abattoir, ot.`nom` AS nom_origine, a.`numagr` AS numagr_abattoir, l.`id_user_maj`,  r.`id` AS reception_id, l.`visible`, l.`id_espece`, e.`nom` AS nom_espece, e.`couleur` as couleur, l.`test_tracabilite`, l.`bizerba`, CONCAT(u.`prenom`, " ", u.`nom`) AS nom_user_maj, l.`id_fournisseur`, t.`nom` AS nom_fournisseur, l.`date_atelier`
							FROM `pe_lots` l
								LEFT JOIN `pe_abattoirs` 		a ON a.`id` = l.`id_abattoir`
								LEFT JOIN `pe_pays`  			o ON o.`id` = l.`id_origine` 
								LEFT JOIN `pe_pays_trad`  		ot ON ot.`id_pays` = l.`id_origine` AND ot.`id_langue` = 1 
								LEFT JOIN `pe_users`  	 		u ON u.`id` = l.`id_user_maj`
								LEFT JOIN `pe_lot_reception`  	r ON l.`id`	= r.`id_lot` 
								LEFT JOIN `pe_produits_especes` e ON e.`id`	= l.`id_espece` 
								LEFT JOIN `pe_tiers` 			t ON t.`id` = l.`id_fournisseur` 
							WHERE l.`numlot` = "' . $numlot . '" OR l.`numlot` = "' . substr($numlot,0,-3). '"';

		$query = $this->db->prepare($query_lot);
		$query->execute();

		$donnees = $query->fetch();

		if ($donnees && !empty($donnees)) {

			return new Lot($donnees);;

		} else { return false; }

	} // FIN méthode

	// Retourne la liste des lots
	public function getListeLots($params) {

		$start 		= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 		= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 20;
		$statut 	= isset($params['statut']) 			? intval($params['statut']) 	: false;
		$numlot		= isset($params['numlot'])			? trim($params['numlot'])		: '';
		$palette	= isset($params['palette'])			? intval($params['palette'])	: 0;
		$origines	= isset($params['origines'])		? trim($params['origines'])		: '';
		$abattoirs	= isset($params['abattoirs'])		? trim($params['abattoirs'])	: '';
		$frs		= isset($params['frs'])				? trim($params['frs'])			: '';
		$emballage	= isset($params['emballage'])		? trim($params['emballage'])	: '';
		$produits	= isset($params['produits'])		? trim($params['produits'])		: '';
		$date_debut	= isset($params['date_debut'])		? trim($params['date_debut'])	: '';
		$date_fin	= isset($params['date_fin'])		? trim($params['date_fin'])		: '';
		$recherche	= isset($params['recherche'])		? trim($params['recherche'])	: '';

		$order 		= isset($params['order']) 			? $params['order'] 				: 'id';

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS l.`id`, l.`numlot`, l.`date_add`, l.`date_prod`, l.`date_maj`, l.`date_out`, l.`date_reception`, l.`date_abattage`, l.`id_abattoir`, l.`id_origine`, l.`composition_viande`,	l.`poids_abattoir`, l.`poids_reception`, l.`supprime` , a.`nom` AS nom_abattoir, ot.`nom` AS nom_origine,  l.`id_user_maj`, l.`test_tracabilite`, r.`id` AS reception_id, l.`composition`, l.`visible`, l.`id_espece`, e.`nom` AS nom_espece, e.`couleur` as couleur, l.`id_fournisseur`, t.`nom` AS nom_fournisseur, l.`bizerba`, l.`date_atelier`
							FROM `pe_lots` l
								LEFT JOIN `pe_abattoirs`  		a ON a.`id` = l.`id_abattoir`
								LEFT JOIN `pe_pays`   			o ON o.`id` = l.`id_origine` 
								LEFT JOIN `pe_pays_trad`		ot ON ot.`id_pays` = l.`id_origine` AND ot.`id_langue` = 1 
								LEFT JOIN `pe_lot_reception`  	r ON r.`id_lot` = l.`id` 
								LEFT JOIN `pe_produits_especes` e ON l.`id_espece` = e.`id`
								LEFT JOIN `pe_tiers` 			t ON t.`id` = l.`id_fournisseur` ';

		$query_liste.= $produits != '' ? '    LEFT JOIN `pe_froid_produits` fp ON fp.`id_lot` = l.`id`  
                                LEFT JOIN `pe_palette_composition` c ON c.`id_lot_hors_stock` = l.`id` 
								LEFT JOIN `pe_frais` f ON f.`id_lot` = l.`id` 
								LEFT JOIN `pe_palette_composition` cf ON cf.`id_frais` = f.`id` 
								' : '';

		$query_liste.='	WHERE 1 ';
		$query_liste.= $produits != '' ? ' AND (fp.`id_pdt` IN ('.$produits.') OR c.`id_produit` IN ('.$produits.') OR cf.`id_produit` IN ('.$produits.')) ' : '';
		$query_liste.= $statut === 1 ? 'AND (l.`date_out` IS NULL OR l.`date_out` = "" OR l.`date_out` = "0000-00-00 00:00:00") ' 			: '';
		$query_liste.= $statut === 0 ? 'AND (l.`date_out` IS NOT NULL AND l.`date_out` != "" AND l.`date_out` != "0000-00-00 00:00:00") ' 	: '';
		$query_liste.= $numlot 		!= '' ? 'AND l.`numlot` LIKE "%'.$numlot.'%" ' 			: '';
		$query_liste.= $origines	!= '' ? 'AND l.`id_origine` IN ('.$origines.') ' 		: '';
		$query_liste.= $abattoirs	!= '' ? 'AND l.`id_abattoir` IN ('.$abattoirs.') ' 		: '';
		$query_liste.= $frs			!= '' ? 'AND l.`id_fournisseur` IN ('.$frs.') ' 		: '';
		$query_liste.= $date_debut	!= '' ? 'AND IF (l.`date_maj` IS NOT NULL, l.`date_maj`, l.`date_abattage`) >= "'.$date_debut.' 00:00:00" ' 	: '';
		$query_liste.= 'AND l.`supprime` = 0 '; // On échappe les lots supprimés à la création
		$query_liste.= $date_fin	!= '' ? 'AND IF (l.`date_out` IS NOT NULL, l.`date_out`,
                           			  				IF (l.`date_maj` IS NOT NULL, l.`date_maj`,
                           			    				IF (l.`date_reception` IS NOT NULL, l.`date_reception`,
                           			      					l.`date_add`)
                           			    					))
                           			   				<= "'.$date_fin.' 23:59:59" ' 			: '';

		$query_liste.= $recherche != '' ? ' AND (l.`numlot` LIKE "%'.$recherche.'%" OR l.`id` = '.intval($recherche).') ' : '';

		$query_liste.= 'GROUP BY l.`id` ORDER BY l.`'.$order.'` DESC ';
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();
		
		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());		

		$liste = [];
		$receptionManager = new LotReceptionManager($this->db);
		$lotVuesManager   = new LotVueManager($this->db);
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$lot = new Lot($donnee);
			$skipLot = false;
			// Si on a filtré sur un code emballage
			if ($emballage != '') {
				$query_emb = 'SELECT COUNT(*) AS nb 
										FROM `pe_consommables` er 
										    JOIN `pe_emballages_prod` ep ON ep.`id_emballage_lot` = er.`id` 
									WHERE ep.`id_lot` = ' . $lot->getId() . ' 
										AND er.`numlot_frs` LIKE "%'.$emballage.'%" ';
				$queryE = $this->db->prepare($query_emb);
				$queryE->execute();

				$donneeE = $queryE->fetchAll(PDO::FETCH_ASSOC);
				if ($donneeE && isset($donneeE[0]) && isset($donneeE[0]['nb']) && intval($donneeE[0]['nb']) == 0) {
					$skipLot = true;
				}
			} // FIN test filtre emballage

			// Si on a filtré sur une palette
			if ($palette > 0) {

				$query_pal = 'SELECT COUNT(*) AS nb FROM `pe_froid_produits` WHERE `id_lot` = ' . $lot->getId() . ' AND `id_palette` = '.$palette;
				$queryP2 = $this->db->prepare($query_pal);
				$queryP2->execute();
				$donneeP2 = $queryP2->fetchAll(PDO::FETCH_ASSOC);
				if ($donneeP2 && isset($donneeP2[0]) && isset($donneeP2[0]['nb']) && intval($donneeP2[0]['nb']) == 0) {
					$skipLot = true;
				}
			} // FIN test filtre produit

			// On vérifie si on est toujours avec les filtres produit / emballabe
			if ($skipLot) {	continue; }
			// On intègre l'objet Reception si on en a un...
			if (intval($donnee['reception_id']) > 0) {
				$reception = $receptionManager->getLotReceptionByIdLot($lot->getId());
				if ($reception instanceof LotReception) {
					$lot->setReception($reception);
				}
			} // FIN test Reception
			// On intègre l'array des objets LotVues
			$lotVues = $lotVuesManager->getLotVuesByLot($lot->getId());
			$lot->setVues($lotVues);
			$liste[] = $lot;
		}
		return $liste;
	} // FIN getListe


	public function getListeLotsTracabiliteByVue($params){

		$lotVuesManager   = new LotVueManager($this->db);
		$order 		= isset($params['order']) 			? $params['order'] 				: 'id';
		$start 		= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 		= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 20;
		$numlot		= isset($params['numlot'])			? trim($params['numlot'])		: '';
		$origines	= isset($params['origines'])		? trim($params['origines'])		: '';
		$abattoirs	= isset($params['abattoirs'])		? trim($params['abattoirs'])	: '';
		$frs		= isset($params['frs'])				? trim($params['frs'])			: '';
		$clients	= isset($params['clients'])			? trim($params['clients'])		: '';
		$emballage	= isset($params['emballage'])		? trim($params['emballage'])	: '';
		$date_debut	= isset($params['date_debut'])		? trim($params['date_debut'])	: '';
		$date_fin	= isset($params['date_fin'])		? trim($params['date_fin'])		: '';
		$produits	= isset($params['produits'])		? trim($params['produits'])		: '';
		$palette	= isset($params['palette'])			? intval($params['palette'])	: 0;

		
		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS
		l.`id`, 
		l.`numlot`, 
		l.`date_add`, 
		l.`date_prod`, 
		l.`date_maj`, 
		l.`date_out`, 
		l.`date_reception`, 
		l.`date_abattage`, 
		l.`id_abattoir`, 
		l.`id_origine`, 
		l.`composition_viande`, 
		l.`poids_abattoir`, 
		l.`poids_reception`, 
		l.`supprime` , 
		a.`nom` AS nom_abattoir, 
		ot.`nom` AS nom_origine, 
		l.`id_user_maj`, 
		l.`test_tracabilite`, 
		r.`id` AS reception_id, 
		l.`composition`, 
		l.`visible`, 
		l.`id_espece`, 
		e.`nom` AS nom_espece, 
		e.`couleur` as couleur, 
		l.`id_fournisseur`, 
		t.`nom` AS nom_fournisseur, 
		l.`bizerba`, 
		l.`date_atelier`,
		vle.`numlot_frs`
	FROM 
		`pe_lots` l 
		LEFT JOIN `pe_abattoirs` a ON a.`id` = l.`id_abattoir` 
		LEFT JOIN `pe_pays` o ON o.`id` = l.`id_origine` 
		LEFT JOIN `pe_pays_trad` ot ON ot.`id_pays` = l.`id_origine` AND ot.`id_langue` = 1 
		LEFT JOIN `pe_lot_reception` r ON r.`id_lot` = l.`id` 
		LEFT JOIN `pe_produits_especes` e ON l.`id_espece` = e.`id` 
		LEFT JOIN `pe_tiers` t ON t.`id` = l.`id_fournisseur`
		LEFT JOIN `pe_consommables` vle ON vle.`id` = l.`id`
		';

	$query_liste.= $emballage	!= '' ? 'LEFT JOIN `pe_consommables` ep ON l.`id` = ep.`id_fournisseur`
	LEFT JOIN `pe_emballages_prod` pr ON l.`id` = pr.`id_lot`
	' : '';
	$query_liste.= $palette != '' || $clients != '' ? 'LEFT JOIN `v_lot_palette` vlp ON l.`id` = vlp.`id_lot`' 	: '';

	$query_liste.= $produits != '' ? '    LEFT JOIN `pe_froid_produits` fp ON fp.`id_lot` = l.`id`  
	LEFT JOIN `pe_palette_composition` c ON c.`id_lot_hors_stock` = l.`id` 
	LEFT JOIN `pe_frais` f ON f.`id_lot` = l.`id` 
	LEFT JOIN `pe_palette_composition` cf ON cf.`id_frais` = f.`id` 
	' : '';

		// LEFT JOIN `v_lot_emballage` vle ON l.`id` = vle.`id_lot`
		// LEFT JOIN `v_lot_palette` vlp ON l.`id` = vlp.`id_lot`';
	
	$query_liste.='WHERE 1 AND l.`supprime` = 0 ';
	// $query_liste.= $produits != '' ? ' AND vlp.`id_produit` IN ('.$produits.') ' : '';
	$query_liste.= $produits != '' ? ' AND (fp.`id_pdt` IN ('.$produits.') OR c.`id_produit` IN ('.$produits.') OR cf.`id_produit` IN ('.$produits.')) ' : '';

	$query_liste.= $numlot 		!= '' ? 'AND l.`numlot` LIKE "%'.$numlot.'%" ' 			: '';
	$query_liste.= $origines	!= '' ? 'AND l.`id_origine` IN ('.$origines.') ' 		: '';
	$query_liste.= $abattoirs	!= '' ? 'AND l.`id_abattoir` IN ('.$abattoirs.') ' 		: '';
	$query_liste.= $palette		!= '' ? 'AND vlp.`numPalette`IN ('.$palette.') ' 		: '';
	$query_liste.= $frs			!= '' ? 'AND l.`id_fournisseur` IN ('.$frs.') ' 		: '';
	$query_liste.= $clients		!= '' ? 'AND vlp.`id_client` 	IN ('.$clients.') ' 	: '';
	$query_liste.= $emballage	!= '' ? 'AND vle.`numlot_frs` LIKE "%'.$emballage.'%"' 	: '';
	$query_liste.= $date_debut	!= '' ? 'AND IF (l.`date_maj` IS NOT NULL, l.`date_maj`, l.`date_abattage`) >= "'.$date_debut.' 00:00:00" ' 	: '';
	$query_liste.= $date_fin	!= '' ? 'AND IF (l.`date_out` IS NOT NULL, l.`date_out`,
	IF (l.`date_maj` IS NOT NULL, l.`date_maj`,
	  IF (l.`date_reception` IS NOT NULL, l.`date_reception`,
			l.`date_add`)
		  ))
	 <= "'.$date_fin.' 23:59:59" ' 			: '';

	$query_liste.= ' GROUP BY l.`id` ORDER BY l.`'.$order.'` DESC ';
	$query_liste.= 'LIMIT ' . $start . ',' . $nb;
	
	

	$query = $this->db->prepare($query_liste);
	$query->execute(); 

	$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());
	$liste = [];

	foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
		$lot = new Lot($donnee);
		$lotVues = $lotVuesManager->getLotVuesByLot($lot->getId());
		$lot->setVues($lotVues);
		$liste[] = $lot;
	};
 
	return $liste;
	}
	// Retourne la liste des lots dans une vue
	public function getListeLotsByVue($code_vue, $invisibles = false) {
		
		// On récupère l'objet vue (sécurisation)
		$vuesManager = new VueManager($this->db);
		$vue = $vuesManager->getVueByCode($code_vue);
		if (!$vue instanceof Vue) { return false; }

		$query_lots_id = 'SELECT `id_lot` FROM `pe_lot_vues` WHERE `id_vue` = ' . $vue->getId() . ' ORDER BY `date_entree`';
		$query_ids 	   = $this->db->prepare($query_lots_id);

		$query_ids->execute();

		$liste 		= [];
		$numlots 	= [];

		foreach ($query_ids->fetchAll(PDO::FETCH_ASSOC) as $donnee) {			

			$lot = $this->getLot($donnee['id_lot']);

			

			if ($lot instanceof Lot && !$lot->isSupprime()) {

				// On évite tout doublon sur les numéros de lots
				if (in_array($lot->getNumlot(), $numlots)) {
					continue;
				}

				// Si le lot est invisible, on l'ignore
				if (!$invisibles && !$lot->isVisible()) {
					continue;
				}

				$numlots[] = $lot->getNumlot();
				$liste[] = $lot;
			}

		} // FIN boucle sur les ID de lots

		return $liste;

	} // FIN méthode

	
	// Enregistre un lot
	public function saveLot(Lot $objet) {
		
		$table		= 'pe_lots';	// Nom de la table
		$champClef	= 'id';			// Nom du champ cléf
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


	// Enregistre un lot de regroupement
	public function saveLotRegroupement(LotRegroupement $objet) {

		$table		= 'pe_lots_regroupement';	// Nom de la table
		$champClef	= 'id';						// Nom du champ cléf
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


	// Retourne si un numéro de lot existe déjà en dehors de l'ID spécifié (facultatif)
	public function checkLotExiste($numlot, $id_exclu = 0) {

		$query_check = 'SELECT COUNT(*) AS nb, id FROM `pe_lots` WHERE (UPPER(`numlot`) = :numlot )';
		$query_check.= (int)$id_exclu > 0 ? ' AND `id` != ' . (int)$id_exclu : '';

		$query = $this->db->prepare($query_check);
		$query->bindValue(':numlot', trim(strtoupper($numlot)));

		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);

		if ($donnee && isset($donnee[0]) && isset($donnee[0]['nb']) && intval($donnee[0]['nb']) > 0) {
			$id_lot = $donnee[0]['id'];	
			echo $id_lot; //recuperer l'id de lot modifié.
			return true;              
		}
		return false;

	} // FIN méthode

	// Retourne le nombre de produit dans un lot (NPU)
	// Ancienne méthode pour le nombre de types de produits remplacé par nb de produits et renommée getNbTypesProduitsByLot au lieu de getNbProduitsByLot (réécrite)
	public function getNbTypesProduitsByLot(Lot $lot) {

		$query_nb = 'SELECT (
    			SELECT COUNT(*) FROM `pe_froid_produits` WHERE `id_lot` = ' . (int)$lot->getId() . '
    			) + (
    			SELECT COUNT(*) FROM `pe_palette_composition` WHERE `id_lot_hors_stock` =  ' . (int)$lot->getId(). '
    			) AS nb';
		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetch(PDO::FETCH_ASSOC);

		return $donnee && isset($donnee) && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode

	// Retourne le nombre de produit dans un lot
	public function getNbProduitsByLot(Lot $lot) {

		$query_nb = 'SELECT (
    			SELECT COUNT(*) FROM `pe_froid_produits` WHERE `attente` = 0 AND `id_lot` = ' . (int)$lot->getId() . '
    			) + (
    			SELECT COUNT(pc.`id`) FROM `pe_palette_composition` pc JOIN `pe_frais`f ON f.`id` = pc.`id_frais` WHERE pc.`supprime` = 0 AND f.`id_lot` =  ' . (int)$lot->getId(). '
    			) + (
    			SELECT COUNT(pc.`id`) FROM `pe_palette_composition` pc 
    				JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id`
    				JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl`
    			WHERE (pc.`supprime` = 0 OR pc.`archive` = 1) AND bll.`supprime` = 0 AND bl.`supprime` = 0 AND pc.`id_lot_hors_stock` =  ' . (int)$lot->getId(). '
    			) AS nb';
		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetch(PDO::FETCH_ASSOC);

		return $donnee && isset($donnee) && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode

	// Retourne la liste des produits du lot
	public function getListeProduitsLot(Lot $lot) {

		$query_liste = 'SELECT fp.`id_lot_pdt_froid`, fp.`id_lot`, fp.`id_pdt`, fp.`nb_colis`, fp.`poids`, fp.`date_maj`, fp.`user_maj`, fp.`date_add`, fp.`user_add`
							FROM `pe_froid_produits` fp 
							JOIN `pe_produits` p ON p.`id` = fp.`id_pdt`
						WHERE fp.`id_lot` = ' . $lot->getId() . '
							AND p.`actif` = 1
							AND p.`supprime` = 0
						ORDER BY fp.`date_add` DESC
						';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		$produitsManager = new ProduitManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$froidProduit = new FroidProduit($donnee);
			if (!$froidProduit instanceof FroidProduit) { continue; }

			$pdt = $produitsManager->getProduit($froidProduit->getId_pdt());
			if (!$pdt instanceof Produit) { continue; }

			$froidProduit->setProduit($pdt);

			$liste[] = $froidProduit;

		} // FIN boucle sur les ID de lots

		return $liste;

	} // FIN méthode

	// Retourne le nombre de lots terminés
	public function getNbLotsTermines() {

		$query_nb = 'SELECT COUNT(*) AS nb 
							FROM `pe_lots` 
						WHERE `supprime` = 0 
                        	AND `date_out` IS NOT NULL AND `date_out` != "" AND `date_out` != "0000-00-00 00:00:00"
							AND `date_prod` IS NOT NULL AND `date_prod` != "" AND `date_prod` != "0000-00-00 00:00:00"';
		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee[0]) && isset($donnee[0]['nb']) ? intval($donnee[0]['nb']) : 0;

	} // FIN méthode

	// Retourne le temps moyen par lot terminé (date réception - date out)
	public function getTempsMoyenParLot() {

		// Si aucun lot terminé, pas la peine d'aller plus loin...
		$nbLotsTermines = $this->getNbLotsTermines();
		if (!$nbLotsTermines || (int)$nbLotsTermines == 0) { return '0:00'; }
		$query_temps = 'SELECT TIMEDIFF(`date_out`, `date_prod`) AS temps 
							FROM `pe_lots` 
						WHERE `supprime` = 0 
						  	AND `date_out` IS NOT NULL AND `date_out` != "" AND `date_out` != "0000-00-00 00:00:00"
							AND `date_prod` IS NOT NULL AND `date_prod` != "" AND `date_prod` != "0000-00-00 00:00:00"';
		$query = $this->db->prepare($query_temps);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
		$temps = $donnee && isset($donnee[0]) && isset($donnee[0]['temps']) ? $donnee[0]['temps'] : 0;
		return substr($temps,0,-3);

	} // FIN méthode

	// Retourne le nombre de produits moyen par lot terminé
	public function getNbProduitsMoyenParLot() {

		// Si aucun lot terminé, pas la peine d'aller plus loin...
		$nbLotsTermines = $this->getNbLotsTermines();
		if (!$nbLotsTermines || (int)$nbLotsTermines == 0) { return '0'; }

		// On récupère le nombre total de produits sur les lots terminés
		$query_nb = 'SELECT COUNT(lp.`id_lot_pdt_froid`) AS nb
						FROM `pe_froid_produits` lp 
  							JOIN `pe_lots` l ON l.`id` = lp.`id_lot`
						WHERE l.`supprime` = 0 
						  	AND `date_out` IS NOT NULL AND `date_out` != "" AND `date_out` != "0000-00-00 00:00:00"
							AND `date_prod` IS NOT NULL AND `date_prod` != "" AND `date_prod` != "0000-00-00 00:00:00"';

		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);

		// Si retour on divise par le nombre de lots terminés
		return $donnee && isset($donnee[0]) && isset($donnee[0]['nb']) ? intval(intval($donnee[0]['nb']) / $nbLotsTermines) : 0;

	} // FIN méthode

	// Retourne la perte de poids moyenne par lot (poids du lot - somme poids des produits) sur lots terminés
	public function getPerteMoyeneParLot() {

		// Si aucun lot terminé, pas la peine d'aller plus loin...
		$nbLotsTermines = $this->getNbLotsTermines();
		if (!$nbLotsTermines || (int)$nbLotsTermines == 0) { return '0.000'; }

		// On récupère la perte totale de poids sur les produits de lots terminés
		$query_poids = 'SELECT (l.`poids_reception` - SUM(lp.`poids`)) AS perte 
		 					FROM `pe_froid_produits` lp 
								JOIN `pe_lots` l ON l.`id` = lp.`id_lot`
							WHERE l.`supprime` = 0 
						  		AND `date_out` IS NOT NULL AND `date_out` != "" AND `date_out` != "0000-00-00 00:00:00"
								AND `date_prod` IS NOT NULL AND `date_prod` != "" AND `date_prod` != "0000-00-00 00:00:00"
								GROUP BY l.`poids_reception`';
		$query = $this->db->prepare($query_poids);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
		// Si retour on divise par le nombre de lots terminés
		return $donnee && isset($donnee[0]) && isset($donnee[0]['perte']) ? number_format(floatval($donnee[0]['perte']) / $nbLotsTermines,3, '.', ' '): '0.000';
	} // FIN méthode

	// Retourne un array du top des produits les plus produits dans les lots
	public function getTopProduitsLots($nbTop = 5) {

		$query_top = 'SELECT fp.`id_pdt`, pt.`nom`, SUM(fp.`nb_colis`) AS nb
		 					FROM `pe_froid_produits` fp 
								JOIN `pe_produits` p ON p.`id` = fp.`id_pdt`
								LEFT JOIN `pe_produit_trad` pt ON pt.`id_produit` = p.`id` AND pt.`id_langue` = 1
								JOIN `pe_lots` l ON l.`id` = fp.`id_lot`
							WHERE l.`supprime` = 0 
							 GROUP BY fp.`id_pdt`,  pt.`nom`
							ORDER BY nb DESC
							LIMIT 0,'.$nbTop;
		$query = $this->db->prepare($query_top);
		$query->execute();

		$liste = [];
		foreach($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[ucwords(strtolower($donnee['nom']))] = intval($donnee['nb']);
		}
		return $liste;

	} // FIN méthode

	// Retourne la proportion d'utilisation des différents abattoirs dans les lots
	public function getProportionAbattoirsLots() {

		$query_ab = 'SELECT a.`nom`, COUNT(l.`id`) AS nb 
						FROM `pe_lots` l 
							JOIN `pe_abattoirs` a ON a.`id` = l.`id_abattoir`
						WHERE l.`supprime` = 0
						GROUP BY l.`id_abattoir`
						ORDER BY nb DESC';

		$query = $this->db->prepare($query_ab);
		$query->execute();

		$liste = [];

		foreach($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[ucwords(strtolower($donnee['nom']))] = intval($donnee['nb']);
		}

		return $liste;

	}  // FIN méthode

	// Retourne la proportion des origines dans les lots
	public function getProportionOriginesLots() {

		$query_o = 'SELECT o.`nom`, COUNT(l.`id`) AS nb 
						FROM `pe_lots` l 
							JOIN `pe_pays_trad` o ON o.`id_pays` = l.`id_origine` AND o.`id_langue` = 1
						WHERE l.`supprime` = 0
						GROUP BY l.`id_origine`
						ORDER BY nb DESC';

		$query = $this->db->prepare($query_o);
		$query->execute();

		$liste = [];

		foreach($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[ucwords(strtolower($donnee['nom']))] = intval($donnee['nb']);
		}

		return $liste;

	} // FIN méthode


	// Retourne l'objet Lot avec l'hydratation des sous-objets DétailsFroid et DétailsFroidProduits
	public function getDetailsFroidLot(Lot $lot) {

		$detailsFroid = new DetailsFroid([]);
		$detailsFroid->setProduits([]);
		$lot->setDetailsFroid($detailsFroid);
		return $lot;

		$query_details = 'SELECT f.`id`, f.`id_type`, f.`date_entree`, f.`date_sortie`, f.`temp_debut`, f.`temp_fin`, f.`conformite`, f.`id_visa_controleur`, f.`date_controle`, f.`id_user_maj`,
       						CONCAT(uctrl.`prenom`, " ", uctrl.`nom`) AS nom_controleur
							FROM `pe_froid` f
								LEFT JOIN `pe_users` uctrl ON uctrl.`id` = f.`id_visa_controleur`
								LEFT JOIN `pe_froid_produits` fp ON fp.`id_froid` = f.`id`
							WHERE f.`id_lot` = ' . $lot->getId() . ' GROUP BY f.`id`,  f.`id_type`, f.`date_entree`, f.`date_sortie`, f.`temp_debut`, f.`temp_fin`, f.`conformite`, f.`id_visa_controleur`, f.`date_controle`, f.`id_user_maj` ';

		$query = $this->db->prepare($query_details);
		$query->execute();

		$donnee = $query->fetch();

		if ($donnee && !empty($donnee)) {
			$detailsFroid = new DetailsFroid($donnee);
		} else {
			$detailsFroid = new DetailsFroid([]);
			$detailsFroid->setProduits([]);
			$lot->setDetailsFroid($detailsFroid);
			return $lot;
		}

		// On ajoute les produits
		$query_produits = 'SELECT lp.`id_froid`, lp.`id_lot_pdt`, fp.`nb_colis`, fp.`poids`, pt.`nom`
							FROM `pe_lot_produits` lp
								JOIN `pe_produits` p ON p.`id` = lp.`id_produit`
								LEFT JOIN `pe_produit_trad` pt ON pt.`id_produit` = p.`id` AND pt.`id_langue` = 1
								JOIN `pe_froid_produits` fp ON fp.`id_lot_pdt` = lp.`id_lot_pdt`
							WHERE lp.`id_froid` = ' . $detailsFroid->getId() .' ORDER BY fp.`id_lot_pdt` ';


		$queryPdts = $this->db->prepare($query_produits);
		$queryPdts->execute();

		$liste = [];

		foreach ($queryPdts->fetchAll(PDO::FETCH_ASSOC) as $donneePdt) {
			$liste[] = new DetailsFroidProduit($donneePdt);
		}

		$detailsFroid->setProduits($liste);

		$lot->setDetailsFroid($detailsFroid);

		return $lot;

	} // FIN méthode


	// Retire l'affectation d'un lot à une vue
	public function removeLotVue(Lot $lot, $code_vue) {		

		// On récupère l'objet vue (sécurisation)
		$vuesManager = new VueManager($this->db);
		$vue = $vuesManager->getVueByCode($code_vue);
		if (!$vue instanceof Vue) { return false; }

		// On supprime
		$query_del = 'DELETE FROM `pe_lot_vues` 
						  WHERE `id_lot` = ' . $lot->getId() . ' 
						  AND `id_vue` = ' . $vue->getId();
		$query = $this->db->prepare($query_del);

		Outils::saveLog($query_del);

		return $query->execute();

	} // FIN méthode

	// Affecture un lot à une vue si il ne l'est pas déjà
	public function addLotVue(Lot $lot, $code_vue) {

		// On récupère l'objet vue (sécurisation)
		$vuesManager = new VueManager($this->db);
		$newVue = $vuesManager->getVueByCode($code_vue);
		if (!$newVue instanceof Vue) { return false; }

		// On ajoute (+IGNORE)
		$query_add = 'INSERT IGNORE INTO `pe_lot_vues` (`id_lot`, `id_vue`, `date_entree`) VALUES ('.$lot->getId().', '.$newVue->getId().', "'.date('Y-m-d H:i:s').'")';
		$query = $this->db->prepare($query_add);

		Outils::saveLog($query_add);

		return $query->execute();

	} // FIN méthode


	// Ajoute le quantième du jour au numéro de lot, s'il n'existe pas encore
	public function addQuantiemeIfNotExist($lot_id, $quantieme = '') {

		// Si undefined (JS)
		if ($quantieme == 'und') { $quantieme = ''; }

		// On peux passer un objet Lot ou son ID
		$lot = $lot_id instanceof Lot ? $lot_id : $this->getLot($lot_id);

		if (!$lot instanceof Lot) { echo 'ERR1'; return false; }

		// Ici on rajoute le quantième s'il n'est pas en paramètre
		if ($quantieme == '') {
			$quantieme 	= sprintf("%03d", Outils::getJourAnByDate(date('Y-m-d')));
		}

		$query_add = 'INSERT IGNORE INTO `pe_lot_quantieme` (`id_lot`, `quantieme`) VALUES('.(int)$lot->getId().', "'.$quantieme.'")';
		$query = $this->db->prepare($query_add);

		if (!$query->execute()) { return false; }
		Outils::saveLog($query_add);

		// Envoi du quantième à Bzerba... on vérifie qu'on a pas déjà envoyé cette info (ignore)

		$query_verif_bizerba = 'SELECT `bizerba` FROM `pe_lot_quantieme` WHERE `id_lot` = ' . $lot->getId() . ' AND `quantieme` = ' . $quantieme . ' LIMIT 0,1';
		$query2 = $this->db->prepare($query_verif_bizerba);
		$query2->execute();
		$donnees2 = $query2->fetch();
		$bizerba = $donnees2 && isset($donnees2['bizerba']) ? (string)$donnees2['bizerba'] : '';

		// Si on a pas de date, on envoie à Bizerba le nouveau quantième pour le lot...
		if ($bizerba == '') {

			$abattoirsManager = new AbattoirManager($this->db);
			$abattoir = $abattoirsManager->getAbattoir($lot->getId_abattoir());

			// On récupère la configuration...
			$configManager = new ConfigManager($this->db);
			$config_bizerba_actif = $configManager->getConfig('biz_actif');

			// Si désactivé, on retourne true (false génèrerai un message d'erreur)...
			if (!$config_bizerba_actif instanceof Config || intval($config_bizerba_actif->getValeur()) != 1) { return true; }

			$envoiOk = Outils::envoiLotBizerba($lot, $abattoir);

			$log = new Log([]);
			$logsManager = new LogManager($this->db);

			if ($envoiOk) {

				// Si c'est bon,  on enregistre l'info en BDD
				$query_upd_biz = 'UPDATE `pe_lot_quantieme` SET `bizerba` = "'.date('Y-m-d H:i:s').'" WHERE `id_lot` = ' . $lot->getId() . ' AND `quantieme` = ' . $quantieme;
				$query3 = $this->db->prepare($query_upd_biz);

				// Envoi Bizerba OK et save BDD ok
				if ($query3->execute()) {
					Outils::saveLog($query_upd_biz);
					$log->setLog_type('success');
					$log->setLog_texte('Envoi du quantième '.$quantieme.'  pour le lot ' . $lot->getNumlot() . ' vers Bizerba OK');

				// Envoi Bizerba OK mais erreur save BDD
				} else {

					$log->setLog_type('danger');
					$log->setLog_texte('Envoi du quantième '.$quantieme.'  pour le lot ' . $lot->getNumlot() . ' vers Bizerba OK mais erreur Save BDD date Bizerba !');

				} // FIN test save BDD

			// Erreur envoi Bizerba
			} else {

				$log->setLog_type('danger');
				$log->setLog_texte('Echec de l\'envoi du quantième '.$quantieme.'  pour le lot ' . $lot->getNumlot() . ' vers Bizerba !');

			} // FIN test envoi Bizerba

			$logsManager->saveLog($log);
			return $envoiOk;

		} // FIN test quantième déjà envoyé à Bizerba

		return true;

	} // FIN méthode

	// Retourne la volumétrie des traitements (array nom[total])
	public function getVolumetrieFroids() {

		$liste = [];
		for ($i = 1; $i < 4; $i++) {
			$query_liste = 'SELECT COUNT(*) AS total, ft.`nom` FROM `pe_froid` f JOIN `pe_froid_types` ft ON ft.`id` = f.`id_type` WHERE f.`id_type` = ' . $i;
			$query = $this->db->prepare($query_liste);
			$query->execute();
			$donnees = $query->fetch();
			if ($donnees && !empty($donnees) && isset($donnees['total'])) {
				$liste[$donnees['nom']] = intval($donnees['total']);
			}
		} // FIN boucle types $i
		return $liste;

	} // FIN méthode

	// Retourne les quantièmes du lot
	public function getLotQuantiemes(Lot $lot) {

		$query_liste = 'SELECT `quantieme` FROM `pe_lot_quantieme` WHERE `id_lot` = ' . $lot->getId() . ' ORDER BY `quantieme` DESC';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnees) {
			$liste[] = $donnees['quantieme'];
		}

		return $liste;

	} // FIN méthode

	// Supprime toutes les données relatives à un lot supprimé
	public function killLot($id_lot) {

		$query_del_lot = 'DELETE FROM `pe_lots` WHERE `id` = ' . intval($id_lot);
		$query_lot = $this->db->prepare($query_del_lot);
		$query_lot->execute();
		Outils::saveLog($query_del_lot);
		$query_del_quantieme = 'DELETE FROM `pe_lot_quantieme` WHERE `id_lot` = ' . intval($id_lot);
		$query_quantieme = $this->db->prepare($query_del_quantieme);
		$query_quantieme->execute();
		Outils::saveLog($query_del_quantieme);
		$query_del_validationslots = 'DELETE FROM `pe_validations_lots` WHERE `id_lot` = ' . intval($id_lot);
		$query_validationslots = $this->db->prepare($query_del_validationslots);
		$query_validationslots->execute();
		Outils::saveLog($query_del_validationslots);
		$query_del_validations = 'DELETE FROM `pe_validations` WHERE `type` = 1 AND `id_liaison` = ' . intval($id_lot);
		$query_validations = $this->db->prepare($query_del_validations);
		$query_validations->execute();
		Outils::saveLog($query_del_validations);
		$query_del_lotvues = 'DELETE FROM `pe_lot_vues` WHERE `id_lot` = ' . intval($id_lot);
		$query_lotvues = $this->db->prepare($query_del_lotvues);
		$query_lotvues->execute();
		Outils::saveLog($query_del_lotvues);
		$query_del_rcp = 'DELETE FROM `pe_lot_reception` WHERE `id_lot` = ' . intval($id_lot);
		$query_rcp = $this->db->prepare($query_del_rcp);
		$query_rcp->execute();
		Outils::saveLog($query_del_rcp);
		$query_del_froidpdt = 'DELETE FROM `pe_froid_produits` WHERE `id_lot` = ' . intval($id_lot);
		$query_froidpdt = $this->db->prepare($query_del_froidpdt);
		$query_froidpdt->execute();
		Outils::saveLog($query_del_froidpdt);
		$query_del_embprod = 'DELETE FROM `pe_emballages_prod` WHERE `id_lot` = ' . intval($id_lot);
		$query_embprod = $this->db->prepare($query_del_embprod);
		$query_embprod->execute();
		Outils::saveLog($query_del_embprod);
		$query_upd_alertes = 'UPDATE `pe_alertes` SET `supprime` = 1 WHERE `id_lot` = ' . intval($id_lot);
		$query_alertes = $this->db->prepare($query_upd_alertes);
		$query_alertes->execute();
		Outils::saveLog($query_upd_alertes);
		return true;
	}

	public function reouvreLot(Lot $lot) {

		$query_upd = 'UPDATE `pe_lots` SET `visible` = 0, `date_out` = NULL WHERE `id` = ' . $lot->getId();
		$query_upd_go = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query_upd_go->execute();

	}

	// Retourne un lot de regroupements
	public function getLotRegroupement($id) {

		$query_liste = 'SELECT `id`, `numlot`, `statut`, `date_add`, `user_id`, `supprime` 
							FROM `pe_lots_regroupement`
						WHERE `id` = ' . (int)$id;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee ? new LotRegroupement($donnee) : false;

	} // FIN méthode

	// Retourne la liste des lots de regroupements
	public function getListeLotsRegroupement($params = []) {

		$statut = isset($params['statut']) ? intval($params['statut']) : -1;

		$query_liste = 'SELECT `id`, `numlot`, `statut`, `date_add`, `user_id`, `supprime` 
							FROM `pe_lots_regroupement`
						WHERE `supprime` = 0 ';

		$query_liste.= $statut > -1 ? ' AND `statut` = ' . $statut : '';
		$query_liste.= ' ORDER BY `date_add` ';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new LotRegroupement($donnee);
		}

		return $liste;

	} // FIN méthode

	// Passe le flag "supprime" à 0 pour les lots de regroupement n'ayant aucune compo associée
	public function supprimeLotsRegroupementsVides() {

		$query_liste = 'SELECT lr.`id` FROM `pe_lots_regroupement` lr
									LEFT JOIN `pe_palette_composition` pc ON pc.`id_lot_regroupement` = lr.`id`
								WHERE pc.`id` IS NULL';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = (int)$donnee['id'];
		}

		if (empty($liste)) { return true; }

		$query_upd = 'UPDATE `pe_lots_regroupement` SET `supprime` = 1 WHERE `supprime` = 0 AND `id` IN ('.implode(',', $liste).')';
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();


	} // FIN méthode


	// Retourne la liste des lots en date d'atelier (Pvisu)
	public function getLotsJourAtelier($date_atelier) {

		if (!Outils::verifDateSql($date_atelier)) { return false; }

		$query_liste = 'SELECT  `id`, `numlot`, `date_add`, `date_prod`, `date_maj`, `date_out`, `date_reception`, `date_abattage`, `id_abattoir`, `id_origine`, `composition`, `composition_viande`, `poids_abattoir`, `poids_reception`, `supprime`, `id_user_maj`, `visible`, `id_espece`, `test_tracabilite`, `bizerba`, `id_fournisseur`, `date_atelier`
							FROM `pe_lots` 
							WHERE  `supprime` = 0 AND (DATE(`date_atelier`) = "' . $date_atelier . '" OR DATE(`date_prod`) = "' . $date_atelier . '")
							ORDER BY `id` ';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Lot($donnee);
		}

		return $liste;

	} // FIN méthode


	public function updateComposBlArchives() {

		$query_liste = 'SELECT bll.`id_compo` 
			FROM `pe_bl_lignes` bll
			LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl`
			LEFT JOIN `pe_palette_composition` pc ON pc.`id` = bll.`id_compo`
				WHERE 
					bll.`supprime` = 0
					AND bl.`supprime` = 0
					AND pc.`archive` = 0
					AND pc.`supprime` = 0';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = (int)$donnee['id_compo'];
		}

		if (empty($liste)) { return true; }

		$query_update = 'UPDATE `pe_palette_composition` SET `archive` = 1, `supprime` = 1 WHERE `id` IN ('.implode(',', $liste).')';
		$query2 = $this->db->prepare($query_update);

		if (!$query2->execute()) { return false; }

		Outils::saveLog($query_update);
		$log = new Log([]);
		$logsManager = new LogManager($this->db);
		$log->setLog_type('warning');
		$log->setLog_texte("[DETAIL LOT] Changement automatique de " . count($liste) . " compo(s) en archivées + supprimés car liées à un BL");
		$logsManager->saveLog($log);

		return true;

	}

	public function updatePoidsProduitsFromCompos($id_lot) {

		$query_liste_pdts_froid = 'SELECT `id_lot_pdt_froid`, `poids`, `nb_colis` FROM `pe_froid_produits` WHERE `id_lot` = ' . (int)$id_lot;
		$query1 = $this->db->prepare($query_liste_pdts_froid);
		$query1->execute();

		$liste = [];
		foreach ($query1->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[(int)$donnee['id_lot_pdt_froid']] = ['poids' => (float)$donnee['poids'], 'nb_colis' => (int)$donnee['nb_colis']];
		}

		if (empty($liste)) { return true; }

		$liste2 = [];

		foreach ($liste as $id_lot_pdt_froid => $donnees) {

			$query_liste_by_compos = 'SELECT SUM(`nb_colis`) AS nb_colis, SUM(`poids`) AS poids FROM `pe_palette_composition` WHERE (`supprime` = 0 OR (`archive` = 1 AND `supprime` = 1  ))  AND `id_lot_pdt_froid` = ' . (int)$id_lot_pdt_froid;

			$query2 = $this->db->prepare($query_liste_by_compos);
			$query2->execute();


			foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {
				$liste2[(int)$id_lot_pdt_froid] = ['poids' => (float)$donnee2['poids'], 'nb_colis' => (int)$donnee2['nb_colis']];
			}

		}

		$id_lot_pdt_froid_maj_poids = [];
		$id_lot_pdt_froid_maj_colis = [];

		foreach ($liste as $id_lot_pdt_froid => $donnees) {

			if (!isset($liste2[$id_lot_pdt_froid]['poids']) || !isset($liste2[$id_lot_pdt_froid]['nb_colis'])) { continue; }
			if ((float)$liste2[$id_lot_pdt_froid]['poids'] == 0 || (int)$liste2[$id_lot_pdt_froid]['nb_colis'] == 0) { continue; }


			if ((float)$donnees['poids'] != (float)$liste2[$id_lot_pdt_froid]['poids']) {
				$q = 'UPDATE `pe_froid_produits` SET `poids` = '.$liste2[$id_lot_pdt_froid]['poids'].' WHERE `id_lot_pdt_froid` = '.$id_lot_pdt_froid;
				$q2 = $this->db->prepare($q);
				if ($q2->execute()) {
					Outils::saveLog($q);
					$id_lot_pdt_froid_maj_poids[] = $id_lot_pdt_froid;
				}
			}
			if ((int)$donnees['nb_colis'] != (int)$liste2[$id_lot_pdt_froid]['nb_colis']) {
				$q = 'UPDATE `pe_froid_produits` SET `nb_colis` = '.$liste2[$id_lot_pdt_froid]['nb_colis'].' WHERE `id_lot_pdt_froid` = '.$id_lot_pdt_froid;
				$q2 = $this->db->prepare($q);
				if ($q2->execute()) {
					Outils::saveLog($q);
					$id_lot_pdt_froid_maj_colis[] = $id_lot_pdt_froid;
				}
			}
		}

		$logsManager = new LogManager($this->db);

		if (!empty($id_lot_pdt_froid_maj_poids)) {
			$log = new Log([]);
			$log->setLog_type('warning');
			$log->setLog_texte("[DETAIL LOT] Changement automatique du poids des pdt_froids " . implode(',',$id_lot_pdt_froid_maj_poids) . " car différence avec la somme des compos sur le lot ".$id_lot);
			$logsManager->saveLog($log);
		}

		if (!empty($id_lot_pdt_froid_maj_colis)) {
			$log = new Log([]);
			$log->setLog_type('warning');
			$log->setLog_texte("[DETAIL LOT] Changement automatique du nb_colis des pdt_froids " . implode(',',$id_lot_pdt_froid_maj_colis) . " car différence avec la somme des compos sur le lot ".$id_lot);
			$logsManager->saveLog($log);
		}

		return true;
	}


} // FIN classe