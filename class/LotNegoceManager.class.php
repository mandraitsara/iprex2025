<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet LotNegoce
Généré par CBO FrameWork le 13/01/2020 à 11:30:07
------------------------------------------------------*/
class LotNegoceManager {

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

	// Retourne la liste des LotNegoce
	public function getListeLotNegoces($params = []) {

		$start 		= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 		= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;
		$statut 	= isset($params['statut']) 			? intval($params['statut']) 	: false;
		$date_debut	= isset($params['date_debut'])		? trim($params['date_debut'])	: '';
		$date_fin	= isset($params['date_fin'])		? trim($params['date_fin'])		: '';
		$get_nb_traites	= isset($params['get_nb_traites'])		? boolval($params['get_nb_traites'])		: false;
		$visibles	= isset($params['visibles'])		? boolval($params['visibles'])		: false;
		$receptionnes	= isset($params['receptionnes'])		? boolval($params['receptionnes'])		: false;
		$non_receptionnes	= isset($params['non_receptionnes'])		? boolval($params['non_receptionnes'])		: false;

		$order 		= isset($params['order']) 			? $params['order'] 				: 'id';
		
		
		$query_liste = "SELECT SQL_CALC_FOUND_ROWS l.`id`,l.`num_bl`,l.`poids_fournisseur`, l.`date_add`, l.`date_maj`, l.`dlc`, l.`date_reception` , l.`date_entree`, l.`date_out`, l.`id_espece`, l.`id_fournisseur`, l.`poids_reception`, l.`id_user_maj`, l.`visible`, l.`supprime`, e.`nom` AS nom_espece, t.`nom` AS nom_fournisseur, t.`numagr` AS numagr, pdt.`nom_court` AS nom_produit
							FROM `pe_lots_negoce` l
   								LEFT JOIN `pe_produits_especes` e ON e.`id`	= l.`id_espece` 
								LEFT JOIN `pe_tiers` 			t ON t.`id` = l.`id_fournisseur`
								LEFT JOIN `pe_produits` 			pdt ON pdt.`id` = l.`id_pdt`
								
														
								 ";

		$query_liste.='	WHERE 1 ';
		$query_liste.= $statut === 1 ? 'AND (l.`date_out` IS NULL OR l.`date_out` = "" OR l.`date_out` = "0000-00-00 00:00:00") ' 			: '';
		$query_liste.= $statut === 0 ? 'AND (l.`date_out` IS NOT NULL AND l.`date_out` != "" AND l.`date_out` != "0000-00-00 00:00:00") ' 	: '';
		$query_liste.= $date_debut	!= '' ? 'AND IF (l.`date_maj` IS NOT NULL, l.`date_maj`, l.`date_bl`) >= "'.$date_debut.' 00:00:00" ' 	: '';
		$query_liste.= $visibles ? 'AND l.`visible` = 1 ' 	: '';
		$query_liste.= $non_receptionnes ? 'AND (l.`date_entree` IS NULL OR l.`date_entree` = "0000-00-00") ' 	: '';
		$query_liste.= $receptionnes ? 'AND (l.`date_entree` IS NOT NULL AND l.`date_entree` != "0000-00-00") ' 	: '';
		$query_liste.= 'AND l.`supprime` = 0 '; // On échappe les lots supprimés à la création
		$query_liste.= $date_fin	!= '' ? 'AND IF (l.`date_out` IS NOT NULL, l.`date_out`,
                           			  				IF (l.`date_maj` IS NOT NULL, l.`date_maj`,
                           			    				IF (l.`date_entree` IS NOT NULL, l.`date_entree`,
                           			      					l.`date_add`)
                           			    					))
                           			   				<= "'.$date_fin.' 23:59:59" ' 			: '';

		$query_liste.= 'ORDER BY l.`'.$order.'` DESC ';
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		$blManager = new BlManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$lotNegoce = new LotNegoce($donnee);				
			

			if ($get_nb_traites) {
				$lotNegoce->setNb_produits( $this->getNbProduitsByLot($lotNegoce));
				//$lotNegoce->setNb_produits_traites($this->getNbProduitsTraitesByLot($lotNegoce));
			}

			// On calcule le poids restant (non associé à un BL généré)
			$poidsRestant = $this->getPoidsRestantLotNegoce($lotNegoce->getId());
			

			// On rattache les BLs sortants associés
			$bls = $blManager->getListeBlsByNegoce($lotNegoce->getId());
			
			$liste[] = $lotNegoce;
			

		}
		return $liste;

	} // FIN liste des LotNegoce


	// Retourne le nombre de produits d'un lot de négoce
	public function getNbProduitsByLot(LotNegoce $lotNegoce) {

		$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_negoce_produits` WHERE `supprime` = 0 AND `id_lot_negoce` = ' . $lotNegoce->getId();
		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode

	// Retourne le nombre de produits traités d'un lot de négoce
	public function getNbProduitsTraitesByLot(LotNegoce $lotNegoce) {

		$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_negoce_produits` WHERE `supprime` = 0 AND `traite` = 1 AND `id_lot_negoce` = ' . $lotNegoce->getId();
		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;

	} // FIN méthode


	// Retourne un LotNegoce
	public function getLotNegoce($id) {

		$query_object = "SELECT l.`id`, l.`date_add`, l.`num_bl`, l.`date_maj`, l.`date_entree`, l.`date_out`, l.`id_espece`, l.`id_fournisseur`, l.`id_pdt`, l.`chasse`, l.`poids_fournisseur`, l.`poids_reception`, l.`composition`, l.`id_user_maj`, l.`visible`, l.`supprime`, e.`nom` AS nom_espece, p.`nom_court` AS nom_produit, t.`nom` AS nom_fournisseur, l.`ddm`,l.`dlc`,l.`id_origine`,l.`num_bl`,l.`date_reception`
                		FROM `pe_lots_negoce` l 
							LEFT JOIN `pe_produits_especes` e ON l.`id_espece` = e.`id`
							LEFT JOIN `pe_tiers` 			t ON t.`id` = l.`id_fournisseur`
							LEFT JOIN `pe_produits` p ON p.`id` = l.`id_pdt`
				WHERE l.`id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);					
			if (!$donnee || !isset($donnee[0]) ) { return false; }			

			$lot = new LotNegoce($donnee[0]);	
			
			if (!$lot instanceof LotNegoce) { return false; }



			// On rattache les produits
			$produits = $this->getListeNegoceProduits(['id_lot' => $lot->getId()]);

			
			$lot->setProduits($produits);

			return $lot;

		} else {
			return false;
		}

	} // FIN get LotNegoce

	// Enregistre & sauvegarde (Méthode Save)
	public function saveLotNegoce(LotNegoce $objet) {

		$table      = 'pe_lots_negoce'; // Nom de la table
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

	// Retourne la liste des NegoceProduit
	public function getListeNegoceProduits($params = []) {

		$start 		= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 		= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;
		
		$id_lot 	= isset($params['id_lot']) 			? intval($params['id_lot']) 	: 0;
		$id_pdt 	= isset($params['id_pdt']) 			? intval($params['id_pdt']) 	: 0;
		$hors_bl 	= isset($params['hors_bl']) 		? boolval($params['hors_bl']) 	: false;

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT np.`id_lot_pdt_negoce`,  bl.`num_bl` as numero_bl,  np.`quantite`, np.`id_lot_negoce`, np.`id_pdt`, np.`nb_cartons`, np.`poids`, np.`traite`, np.`date_add`, b.`id_bl`, np.`user_add`, np.`date_maj`, np.`user_maj`, np.`supprime`, t.`nom` as nom_produit, np.`dlc`, np.`num_lot`, 
                           						   IF (bl.`id` IS NULL, "", CONCAT("L",bl.`id`,DATE_FORMAT(bl.`date`, "%y"), LPAD(DAYOFYEAR(bl.`date`)-1,3, "0"))) AS num_bl
							FROM `pe_negoce_produits` np
								JOIN `pe_produits` p ON p.`id` = np.`id_pdt`
								LEFT JOIN `pe_palettes` pa ON pa.`id` = np.`id_palette` 
								LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = p.`id` AND t.`id_langue` = 1 
								LEFT JOIN `pe_bl_lignes` b ON b.`id_pdt_negoce` = np.`id_lot_pdt_negoce`
								LEFT JOIN `pe_bl` bl ON bl.`id` = b.`id_bl`
							WHERE np.`supprime` = 0 ';

		$query_liste.= $id_lot > 0 ? ' AND np.`id_lot_negoce` = ' .$id_lot : '';
		$query_liste.= $id_pdt > 0 ? ' AND np.`id_pdt` = ' .$id_pdt : '';

		$query_liste.= $hors_bl ? ' AND  np.`id_lot_negoce` NOT IN (SELECT DISTINCT `id_lot_negoce` FROM `pe_bl` ) AND b.`id_bl` IS NULL ': '';


		$query_liste.= ' ORDER BY np.`id_lot_pdt_negoce` DESC ';

		$query_liste.= 'LIMIT ' . $start . ',' . $nb;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new NegoceProduit($donnee);
		}
		return $liste;

	} // FIN liste des NegoceProduit


	// Retourne un NegoceProduit
	public function getNegoceProduit($id) {

		$query_object = "SELECT np.`id_lot_pdt_negoce`, np.`id_lot_negoce`, np.`id_pdt`, np.`quantite`, np.`nb_cartons`, np.`poids`, np.`id_palette`, np.`traite`, np.`date_add`, np.`user_add`, np.`date_maj`, np.`user_maj`, np.`supprime` , t.`nom` as nom_produit, np.`dlc`, np.`num_lot`
                					FROM `pe_negoce_produits` np
                					    JOIN `pe_produits` p ON p.`id` = np.`id_pdt`
										LEFT JOIN `pe_palettes` pa ON pa.`id` = np.`id_palette` 
										LEFT JOIN `pe_produit_trad` t ON t.`id_produit` = p.`id` AND t.`id_langue` = 1 
							WHERE np.`id_lot_pdt_negoce` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new NegoceProduit($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get NegoceProduit

	// Enregistre & sauvegarde (Méthode Save)
	public function saveNegoceProduit(NegoceProduit $objet) {

		$table      = 'pe_negoce_produits'; // Nom de la table
		$champClef  = 'id_lot_pdt_negoce'; // Nom du champ clef

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


	// Retourne l'ID produit d'un produit de négoce
	public function getIdProduitByNegoceProduit($id_lot_pdt_negoce) {

		$query_id = 'SELECT `id_pdt` FROM `pe_negoce_produits` WHERE `id_lot_pdt_negoce` = ' . (int)$id_lot_pdt_negoce;
		$query = $this->db->prepare($query_id);
		$query->execute();
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee['id_pdt']) ? intval($donnee['id_pdt']) : 0;


	} // FIN méthode

	// Retourne les objets pour la gestion des stocks produits relatifs aux produits de négoce
	public function getProduitsNegoceProduitStock($id_lot_pdt_negoce) {


		$query_object = "SELECT l.`id_lot_pdt_negoce`, b.`id_bl`, fact.`num_facture`, fact.`id` as id_facture, bl.`num_bl` as numero_bl, b.`poids` as poids_clients, l.`dlc`, t.`nom` as nom_client,  l.`id_lot_negoce`, l.`num_lot`,  l.`numero_palette`,  l.`id_palette`, t.`numagr`, l.`date_add` AS date_reception,   l.`date_add`, l.`nb_cartons`,  pl.`num_bl`, l.`date_maj`, pl.`date_entree`, pl.`date_out`, pl.`id_espece`, pl.`id_fournisseur`, l.`id_pdt`, pl.`chasse`, l.`poids`,  l.`quantite`, pl.`id_user_maj`, pl.`visible`, pl.`supprime`,  p.`nom_court` AS nom_produit, t.`nom` AS fournisseur
		FROM `pe_negoce_produits` l	
			JOIN `pe_lots_negoce` pl ON pl.`id` = l.`id_lot_negoce`
			JOIN `pe_produits` p ON p.`id` = l.`id_pdt`		
			JOIN `pe_bl_lignes` b ON b.`id_pdt_negoce` = l.`id_lot_pdt_negoce`
			JOIN `pe_bl` bl ON bl.`id` = b.`id_bl`			
			LEFT JOIN `pe_tiers` t ON t.`id` = bl.`id_tiers_facturation`
			LEFT JOIN `pe_palette_composition` compo ON compo.`id_palette` = l.`id_palette`			
			LEFT JOIN `pe_bl_facture` fb ON fb.`id_bl` = bl.`id`
			LEFT JOIN `pe_factures` fact ON fact.`id` = fb.`id_facture`
			
WHERE l.`id_lot_pdt_negoce` = ".(int)$id_lot_pdt_negoce;

$query = $this->db->prepare($query_object);


if ($query->execute()) {
$donnee = $query->fetchAll(PDO::FETCH_ASSOC);					
if (!$donnee || !isset($donnee[0]) ) { return false; }			

$lot = new NegoceProduit($donnee[0]);


return $lot;
} // FIN méthode

}

	// Retourne le poids restant d'un lot de négoce
	public function getPoidsRestantLotNegoce($id_lot_pdt_negoce) {

		$query_poids = ' SELECT (
							SELECT IFNULL(SUM(`poids`),0) FROM `pe_negoce_produits` WHERE `supprime` = 0 AND `id_lot_pdt_negoce` = '.(int)$id_lot_pdt_negoce.') - ( 
							SELECT IFNULL(SUM(`poids`),0) FROM `pe_bl_lignes` WHERE `supprime` = 0 AND `id_pdt_negoce` IN
								(SELECT `id_lot_pdt_negoce` FROM `pe_negoce_produits` WHERE `supprime` = 0 AND `id_lot_pdt_negoce` = '.(int)$id_lot_pdt_negoce.')) AS poids';
		$query = $this->db->prepare($query_poids);
		$query->execute();
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee['poids']) ? floatval($donnee['poids']) : 0;

	} // FIN méthode


	public function getLotNegoceByNumLot($numlot) {

		$query_object = 'SELECT l.`id`, l.`date_add`, l.`date_maj`, l.`date_entree`, l.`date_out`, l.`id_espece`, l.`id_fournisseur`, l.`chasse`, l.`poids_bl`, l.`poids_reception`, l.`composition`, l.`id_user_maj`, l.`visible`, l.`supprime`, e.`nom` AS nom_espece, t.`nom` AS nom_fournisseur, l.`temp`, l.`num_bl`
                		FROM `pe_lots_negoce` l 
							LEFT JOIN `pe_produits_especes` e ON l.`id_espece` = e.`id`
							LEFT JOIN `pe_tiers` 			t ON t.`id` = l.`id_fournisseur`
				WHERE l.`num_bl` = "'.$numlot.'" ';
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			if (!$donnee || !isset($donnee[0]) ) { return false; }

			$lot = new LotNegoce($donnee[0]);
			if (!$lot instanceof LotNegoce) { return false; }

			// On rattache les produits
			$produits = $this->getListeNegoceProduits(['id_lot' => $lot->getId()]);
			$lot->setProduits($produits);

			return $lot;

		} else {
			return false;
		}

	}

	public function supprLotProduitNegoce($id_lot_pdt_negoce){
		$querySuppr = 'DELETE FROM `pe_negoce_produits` WHERE `id_lot_pdt_negoce` ='.$id_lot_pdt_negoce;
		$query = $this->db->prepare($querySuppr);		
		if (!$query->execute()) { return false; }

		Outils::saveLog($querySuppr);
		$logManager = new LogManager($this->db);
		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Suppression du produit negoce #'.$id_lot_pdt_negoce.' via admin Bo avant historisation');
		$logManager->saveLog($log);

	}


	// Retourne le poids restant d'un lot de négoce
	public function getPoidsProduitLotNegoce($id_lot_pdt_negoce) {
		$query_poids = ' SELECT ( SELECT IFNULL(SUM(`poids`),0) FROM `pe_negoce_produits` WHERE `id_lot_pdt_negoce` = '.(int)$id_lot_pdt_negoce.')  AS poids';
		$query = $this->db->prepare($query_poids);
		$query->execute();
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee['poids']) ? floatval($donnee['poids']) : 0;

	} // FIN méthode

	// Retourne les quantièmes du lot
	public function getLotQuantiemes(LotNegoce $lot) {

		$query_liste = 'SELECT `quantieme` FROM `pe_lot_quantieme` WHERE `id_lot` = ' . $lot->getId() . ' ORDER BY `quantieme` DESC';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnees) {
			$liste[] = $donnees['quantieme'];
		}

		return $liste;

	} // FIN méthode


	public function getListeNegoceLots($params = []){
		$start 		= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 		= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		$query_liste = "select `ln`.`id` AS `id_lot_negoce`, `np`.`id_lot_pdt_negoce`, `p`.`nom_court` AS nom_produit, `ln`.`date_entree` AS `date_reception`,`p`.`nom_court` AS `nom_pr`,`ln`.`num_bl` AS `num_bl`,`ln`.`date_entree` AS `date_entree`,`t`.`nom` AS `fournisseur`,`np`.`nb_cartons` AS `nb_cartons`,`np`.`poids` AS `poids`,`np`.`quantite` AS `quantite`,`np`.`num_lot` AS `num_lot`,`np`.`dlc` AS `dlc`,`p`.`nom_court` AS `produit` from (((`iprex_dev`.`pe_negoce_produits` `np` join `iprex_dev`.`pe_lots_negoce` `ln` on((`ln`.`id` = `np`.`id_lot_negoce`))) join `iprex_dev`.`pe_tiers` `t` on((`t`.`id` = `ln`.`id_fournisseur`))) join `iprex_dev`.`pe_produits` `p` on((`p`.`id` = `np`.`id_pdt`)))
		";
		$query_liste.= ' ORDER BY ln.`id` DESC ';
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new NegoceProduit($donnee);
		}
		return $liste;

	}

	public function getDetailsProduitsNegoce($id_lot_pdt_negoce){
		$query_object = "SELECT l.`id_lot_pdt_negoce`,   l.`dlc`, l.`id_lot_negoce`, l.`num_lot`,  l.`numero_palette`,  l.`id_palette`, t.`numagr`, l.`date_add` AS date_reception,   l.`date_add`, l.`nb_cartons`,  pl.`num_bl`, l.`date_maj`, pl.`date_entree`, pl.`date_out`, pl.`id_espece`, pl.`id_fournisseur`, l.`id_pdt`, pl.`chasse`, l.`poids`,  l.`quantite`, pl.`id_user_maj`, pl.`visible`, pl.`supprime`,  p.`nom_court` AS nom_produit, t.`nom` AS fournisseur
                		FROM `pe_negoce_produits` l													
							LEFT JOIN `pe_produits` p ON p.`id` = l.`id_pdt`
							LEFT JOIN `pe_lots_negoce` pl ON pl.`id` = l.`id_lot_negoce`
							LEFT JOIN `pe_tiers` 			t ON t.`id` = pl.`id_fournisseur`
				WHERE l.`id_lot_pdt_negoce` = " . (int)$id_lot_pdt_negoce;

		$query = $this->db->prepare($query_object);
		

		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);					
			if (!$donnee || !isset($donnee[0]) ) { return false; }			

			$lot = new NegoceProduit($donnee[0]);	
			
			if (!$lot instanceof NegoceProduit) { return false; }
	
			return $lot;
	}
}


	public function getPoidsExpedie($id_lot_pdt_negoce){
		$query_poids = 'SELECT IFNULL(SUM(`poids`),0) AS poids FROM `pe_bl_lignes` WHERE `id_pdt_negoce` ='.$id_lot_pdt_negoce;
			$query = $this->db->prepare($query_poids);
			$query->execute();
			$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee['poids']) ? floatval($donnee['poids']) : 0;

	}


	public function getProduitsExpedie($id_lot_pdt_negoce){
		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT np.`id_lot_pdt_negoce`, np.`quantite`, np.`id_lot_negoce`, np.`id_pdt`, np.`nb_cartons`,np.`num_lot`, np.`id_palette`,p.`nom_court` AS nom_produit,np.`poids`, np.`traite`, np.`date_add`, b.`id_bl`, np.`user_add`, np.`date_maj`, np.`user_maj`, np.`supprime`, np.`dlc`,
                           						   IF (bl.`id` IS NULL, "", CONCAT("L",bl.`id`,DATE_FORMAT(bl.`date`, "%y"), LPAD(DAYOFYEAR(bl.`date`)-1,3, "0"))) AS num_bl, clt.`nom` AS nom_client
							FROM `pe_negoce_produits` np
								JOIN `pe_produits` p ON p.`id` = np.`id_pdt`								
								JOIN `pe_bl_lignes` b ON b.`id_pdt_negoce` = np.`id_lot_pdt_negoce`
								JOIN `pe_bl` bl ON bl.`id` = b.`id_bl`
								LEFT JOIN `pe_palette_composition` compo ON compo.`id_lot_pdt_negoce` = np.`id_lot_pdt_negoce`
								LEFT JOIN `pe_tiers` clt ON clt.`id` = compo.`id_client`
								LEFT JOIN `pe_bl_facture` fb ON fb.`id_bl` = bl.`id`
								LEFT JOIN `pe_factures` fact ON fact.`id` = fb.`id_facture`
							WHERE np.`id_lot_pdt_negoce` ='.$id_lot_pdt_negoce ;
		

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new NegoceProduit($donnee);
		}
		return $liste;



	}


} // FIN classe