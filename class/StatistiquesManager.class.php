<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Manager commun pour les statistiques
------------------------------------------------------*/
class StatistiquesManager {

	protected    $db, $nb_results;

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

	// Retourne les stats CLIENTS - Général
	public function getStatsClientsGeneral($params = []) {

		$date_du = isset($params['date_du']) ? Outils::dateFrToSql($params['date_du']) : '';
		$date_au = isset($params['date_au']) ? Outils::dateFrToSql($params['date_au']) : '';
		$mois 	 = isset($params['mois'])  && intval($params['mois'])  > 0 ? intval($params['mois'])  : 0;
		$annee 	 = isset($params['annee']) && intval($params['annee']) > 0 ? intval($params['annee']) : 0;

		if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
		if (!Outils::verifDateSql($date_au)) { $date_au = ''; }

		$query = '
			SELECT 
				m.`id_tiers_facturation` AS `id`, 
				m.`nom_client` AS `nom`, 
				m.`code_client` AS `code`,
				SUM(CASE WHEN m.`vendu_piece` = 1 THEN m.`qte` ELSE 0 END) AS qte,
				SUM(CASE WHEN m.`vendu_piece` = 0 THEN m.`poids` ELSE 0 END) AS poids,
				SUM(m.`marge_brute`) AS marge_brute,
				SUM(m.`prix_vente`) AS ca 
			FROM 
				`stat_marges_factures` m
		';

		$query_conditions = ' WHERE 1=1 '; // Condition de base

		if ($date_du != '') $query_conditions .= ' AND m.`date_facture` >= "'.$date_du.'" ';
		if ($date_au != '') $query_conditions .= ' AND m.`date_facture` <= "'.$date_au.'" '; 
		if ($mois > 0) $query_conditions .= ' AND m.`mois_facture` = '.$mois;
		if ($annee > 0) $query_conditions .= ' AND m.`annee_facture` = '.$annee;

		$query_group_by = ' 
			GROUP BY 
				m.`id_tiers_facturation` 
			ORDER BY 
				ca DESC';

		$query_final = $query . $query_conditions . $query_group_by;

		$query = $this->db->prepare($query_final);
		$query->execute();

		// echo '79 - Verif/optim - ' . $query_final . '<br />';

		$liste = [];

		while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
			$tmp = [
				'id_client' 	=> intval($row['id']), 
				'clt_code' 		=> $row['code'], 
				'clt_nom' 		=> $row['nom'], 
				'marge_brute' 	=> floatval($row['marge_brute']), 
				'poids' 		=> floatval($row['poids']), 
				'qte' 			=> floatval($row['qte']) > 0 ? floatval($row['qte']) : 1, 
				'ca' 			=> round(floatval($row['ca']), 2)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          
			];
			$liste[] = $tmp;
		}

		return $liste;

	} // FIN méthode


	// Retourne les stats client : produits pour un client
	public function getStatsClientProduits($params = []) {

		$date_du 	= isset($params['date_du'])   ? Outils::dateFrToSql($params['date_du']) : '';
		$date_au 	= isset($params['date_au'])   ? Outils::dateFrToSql($params['date_au']) : '';
		$mois 	 	= isset($params['mois'])  	  && intval($params['mois'])  > 0 ? intval($params['mois'])  : 0;
		$annee 	 	= isset($params['annee']) 	  && intval($params['annee']) > 0 ? intval($params['annee']) : 0;
		$id_client 	= isset($params['id_client']) ?  intval($params['id_client']) : 0;

		if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
		if (!Outils::verifDateSql($date_au)) { $date_au = ''; }
		if ($id_client == 0) { return []; }

		$query =  '
			SELECT 
				m.`id_produit`,
				m.`code_produit`,
				m.`nom_produit`,
				SUM(CASE WHEN m.`vendu_piece` = 1 THEN m.`qte` ELSE 0 END) AS qte,
				SUM(CASE WHEN m.`vendu_piece` = 0 THEN m.`poids` ELSE 0 END) AS poids,
				SUM(m.`prix_vente`) as ca,
				SUM(m.`marge_brute`) as marge_brute
			FROM 
				`stat_marges_factures` m
			WHERE 
				m.`id_tiers_facturation` = '.$id_client.' 
			';

		// Conditions facultatives pour la période
		if ($date_du != '') { $query .= 'AND m.`date_facture` >= "'.$date_du.'" '; }
		if ($date_au != '') { $query .= 'AND m.`date_facture` <= "'.$date_au.'" '; }
		if ($mois > 0) { $query .= 'AND m.`mois_facture` = '.$mois.' '; }
		if ($annee > 0) { $query .= 'AND m.`annee_facture` = '.$annee.' '; }

		$query .= '
			GROUP BY 
				m.`id_produit`, 
				m.`code_produit`, 
				m.`nom_produit` 
			ORDER BY 
				ca DESC'
			;

		// echo '144 - Verif/optim - ' . $query . '<br />';

		// Préparation et exécution de la requête
		$query1 = $this->db->prepare($query);
		$query1->execute();

		// Traitement du résultat
		$liste = [];
		foreach ($query1->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$tmp = [
				'id_produit' => isset($donnee['id_produit']) ? intval($donnee['id_produit']) : 0,
				'pdt_code' => isset($donnee['code_produit']) ? $donnee['code_produit'] : '',
				'pdt_nom' => isset($donnee['nom_produit']) ? $donnee['nom_produit'] : '',
				'poids' => floatval($donnee['poids']),
				'qte' => floatval($donnee['qte']) > 0 ? floatval($donnee['qte']) : 1,
				'ca' => floatval($donnee['ca']),
				'marge_brute' => floatval($donnee['marge_brute'])
			];
			$liste[] = $tmp;
		}
		return $liste;

	} // FIN méthode


	// Retourne les stats groupe : produits pour un groupe de clients
	public function getStatsGroupeProduits($params = []) {

		$date_du 	= isset($params['date_du'])   ? Outils::dateFrToSql($params['date_du']) : '';
		$date_au 	= isset($params['date_au'])   ? Outils::dateFrToSql($params['date_au']) : '';
		$mois 	 	= isset($params['mois'])  	  && intval($params['mois'])  > 0 ? intval($params['mois'])  : 0;
		$annee 	 	= isset($params['annee']) 	  && intval($params['annee']) > 0 ? intval($params['annee']) : 0;
		$id_groupe 	= isset($params['id_groupe']) ?  intval($params['id_groupe']) : 0;

		if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
		if (!Outils::verifDateSql($date_au)) { $date_au = ''; }
		if ($id_groupe == 0) { return []; }

		$query = '
			SELECT 
				m.`id_produit`,
				m.`code_produit`,
				m.`nom_produit`,
				SUM(CASE WHEN m.`vendu_piece` = 1 THEN m.`qte` ELSE 0 END) AS qte,
				SUM(CASE WHEN m.`vendu_piece` = 0 THEN m.`poids` ELSE 0 END) AS poids,
				SUM(m.`prix_vente`) as ca,
				SUM(m.`marge_brute`) as marge_brute
			FROM 
				`stat_marges_factures` m
			WHERE 
				m.`id_groupe` = '.$id_groupe.'
		';

		if ($date_du != '') { $query .= 'AND m.`date_facture` >= "'.$date_du.'" '; }
		if ($date_au != '') { $query .= 'AND m.`date_facture` <= "'.$date_au.'" '; }
		if ($mois > 0) { $query .= 'AND m.`mois_facture` = '.$mois.' '; }
		if ($annee > 0) { $query .= 'AND m.`annee_facture` = '.$annee.' '; }

		$query .= '
			GROUP BY 
				m.`id_produit`,	
				m.`code_produit`, 
				m.`nom_produit` 
			ORDER BY 
				ca DESC'
		;

		// echo '211 - Verif/optim - ' . $query . '<br />';

		// Préparation et exécution de la requête
		$query1 = $this->db->prepare($query);
		$query1->execute();

		// Traitement du résultat
		$liste = [];
		foreach ($query1->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$tmp = [
				'id_produit' => isset($donnee['id_produit']) ? intval($donnee['id_produit']) : 0,
				'pdt_code' => isset($donnee['code_produit']) ? $donnee['code_produit'] : '',
				'pdt_nom' => isset($donnee['nom_produit']) ? $donnee['nom_produit'] : '',
				'poids' => floatval($donnee['poids']),
				'qte' => floatval($donnee['qte']) > 0 ? floatval($donnee['qte']) : 1,
				'ca' => floatval($donnee['ca']),
				'marge_brute' => floatval($donnee['marge_brute'])
			];
			$liste[] = $tmp;
		}

		return $liste;

	} // FIN méthode

	// Retourne les stats produits : général
	public function getStatsProduitsGeneral($params = []) {

		$date_du = isset($params['date_du']) ? Outils::dateFrToSql($params['date_du']) : '';
		$date_au = isset($params['date_au']) ? Outils::dateFrToSql($params['date_au']) : '';
		$mois 	 = isset($params['mois'])  && intval($params['mois'])  > 0 ? intval($params['mois'])  : 0;
		$annee 	 = isset($params['annee']) && intval($params['annee']) > 0 ? intval($params['annee']) : 0;
		$par_fam = isset($params['sep_fam']) ? boolval($params['sep_fam']) : false;
		$par_clt = isset($params['sep_clt']) ? boolval($params['sep_clt']) : false;

		if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
		if (!Outils::verifDateSql($date_au)) { $date_au = ''; }

		// // On récupère les IDS factures concernés pour la période
		// $querys_ids_factures = 'SELECT `id` FROM `pe_factures` WHERE `supprime` = 0 ';

		// $querys_ids_factures.= $date_du != '' ? ' AND `date` >= "'.$date_du.'" ' 	: '';
		// $querys_ids_factures.= $date_au != '' ? ' AND `date` <= "'.$date_au.'" ' 	: '';
		// $querys_ids_factures.= $mois    > 0   ? ' AND MONTH(`date`) = '.$mois 		: '';
		// $querys_ids_factures.= $annee   > 0   ? ' AND YEAR(`date`) = '.$annee 		: '';
		// $querys_ids_factures.= ' ORDER BY `id_tiers_facturation` ';

		// $query1 = $this->db->prepare($querys_ids_factures);
		// $query1->execute();
		// $ids_factures_produit = [];
		// foreach ($query1->fetchAll(PDO::FETCH_ASSOC) as $donnee1) {
		// 	$id_facture = isset($donnee1['id']) ? intval($donnee1['id']) : 0;
		// 	if ($id_facture > 0) {
		// 		$ids_factures_produit[intval($donnee1['id'])] = intval($donnee1['id']);
		// 	} // FIN test résultat
		// } // FIN boucle factures de la période

		// if (!$ids_factures_produit || empty($ids_factures_produit)) { return []; } // Si aucune facture dans cette période

		$liste = [];

		// Si on fait un sous-total par famille (=especes)
		if ($par_fam) {

			// // On boucle sur les familles de produit
			// $query_fam_pdts = 'SELECT `id`, `nom` FROM `pe_produits_especes`'; // Pas supprime = 0 car on peut intéroger des stats sur une vieille période ou c'était pas supprimé
			// $query2 = $this->db->prepare($query_fam_pdts);
			// $query2->execute();
			// foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {

			// 	$id_espece = isset($donnee2['id']) ? intval($donnee2['id']) : 0;
			// 	$nom_espece = isset($donnee2['nom']) ? trim($donnee2['nom']) : '';
			// 	if ($id_espece == 0) { continue; }

			// 	$tmp = ['id_fam' => $id_espece, 'nom_fam' => $nom_espece];

			// 	$distinct = $par_clt ? ' f.`id_tiers_facturation`, ' : '';

			// 	// On récupère tous les produits facturés pendant cette période
			// 	$query_produits = 'SELECT DISTINCT pl.`id_produit`, '.$distinct.'  p.`code`, IFNULL(pt.`nom`, p.`nom_court`) AS nom_produit, p.`vendu_piece` ';

			// 	$query_produits.= $par_clt ? ', f.`id_tiers_facturation`, t.`nom` AS nom_client ' : '';

			// 	$query_produits.= ' FROM `pe_facture_lignes` pl
			// 							JOIN `pe_produits` p ON p.`id` = pl.`id_produit` AND p.`id_espece` = '.$id_espece.'
			// 							LEFT JOIN `pe_produit_trad` pt ON pt.`id_produit` = p.`id` AND pt.`id_langue` = 1  ';

			// 	$query_produits.= $par_clt ? ' LEFT JOIN `pe_factures` f ON f.`id` = pl.`id_facture` ' : '';
			// 	$query_produits.= $par_clt ? ' LEFT JOIN `pe_tiers` t ON t.`id` = f.`id_tiers_facturation` ' : '';

			// 	$query_produits.= '	WHERE pl.`id_facture` IN ('.implode(',', $ids_factures_produit).') ORDER BY pl.`id_produit`';

			// 	echo '644 - ' . $query_produits . '<br />';

			// 	$query = $this->db->prepare($query_produits);
			// 	$query->execute();

			// 	foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			// 		// Infos de base sur le produit
			// 		$tmp['id_produit'] = intval($donnee['id_produit']);
			// 		$tmp['pdt_code'] = $donnee['code'];
			// 		$tmp['pdt_nom'] = $donnee['nom_produit'];
			// 		$tmp['vendu_piece'] = $donnee['vendu_piece'];

			// 		if ($par_clt) {
			// 			$tmp['id_clt'] =  intval($donnee['id_tiers_facturation']);
			// 			$tmp['nom_clt'] =  $donnee['nom_client'];
			// 		}

			// 		// Données facturées
			// 		$query_stats = '
			// 					SELECT
			// 						SUM(CASE WHEN m.`vendu_piece` = 0 THEN m.`qte` ELSE 0 END) AS qte,
			// 						SUM(CASE WHEN m.`vendu_piece` = 1 THEN m.`poids` ELSE 0 END) AS poids,	
        	// 						SUM(m.`prix_vente`)  AS ca ,	
       		// 						SUM(m.`marge_brute`) AS marge_brute
			// 					FROM 
			// 						`stat_marges_factures` m 
			// 						LEFT JOIN `pe_factures` f ON f.`id` = m.`id_ligne_facture`';

			// 		$query_stats.= $par_clt ? ' LEFT JOIN `pe_tiers` t ON t.`id` = f.`id_tiers_facturation` ' : '';

			// 		$query_stats.= ' WHERE m.`id_facture` IN ('.implode(',', $ids_factures_produit).')
			// 							AND m.`id_produit` = ' . intval($donnee['id_produit']);

			// 		$query_stats.= $par_clt ? ' AND f.`id_tiers_facturation` =' .  intval($donnee['id_tiers_facturation']) : '';

			// 		echo '339 - ' . $query_stats . '<br />';

			// 		$query2 = $this->db->prepare($query_stats);
			// 		$query2->execute();

			// 		// Boucle stats
			// 		$donnee2 = $query2->fetch(PDO::FETCH_ASSOC);
			// 		$tmp['poids'] 		= floatval($donnee2['poids']);
			// 		$tmp['qte'] 		= floatval($donnee2['qte']) > 0 ? floatval($donnee2['qte']) : 1;
			// 		$tmp['ca'] 			= floatval($donnee2['ca']);
			// 		$tmp['marge_brute'] = floatval($donnee2['marge_brute']);

			// 		$liste[] = $tmp;

			// 	} // FIN boucle sur les produits

			// } // FIN boucle sur les familles de produit

			$distinct = $par_clt ? ' f.`id_tiers_facturation`, ' : '';

			$query_stats = '
				SELECT 
					'.$distinct.' 
					m.`code_produit`, 
					m.`nom_produit`,
					pe.`nom` AS nom_fam,
					m.`id_espece` AS id_fam, 
					m.`vendu_piece`,
					SUM(CASE WHEN m.`vendu_piece` = 1 THEN m.`qte` ELSE 0 END) AS qte,
					SUM(CASE WHEN m.`vendu_piece` = 0 THEN m.`poids` ELSE 0 END) AS poids,	
					SUM(m.`prix_vente`) AS ca,	
					SUM(m.`marge_brute`) AS marge_brute
				FROM 
					`stat_marges_factures` m
					LEFT JOIN `pe_produits_especes` pe ON pe.id = m.id_espece
				WHERE
					1 = 1
				';

			$query_stats.= $date_du != '' ? ' AND `date_facture` >= "'.$date_du.'" ' 	: '';
			$query_stats.= $date_au != '' ? ' AND `date_facture` <= "'.$date_au.'" ' 	: '';
			$query_stats.= $mois    > 0   ? ' AND `mois_facture` = '.$mois 		: '';
			$query_stats.= $annee   > 0   ? ' AND `annee_facture` = '.$annee 		: '';

			$query_stats.= $par_clt ? ' AND m.`id_tiers_facturation` =' .  intval($donnee['id_tiers_facturation']) : '';
			$query_stats.= ' 
				GROUP BY 
					m.code_produit, 
					pe.`nom`
				ORDER BY 
					pe.`nom`,
					ca DESC'
				;

			// echo '390 - ' . $query_stats . '<br />';

			$query2 = $this->db->prepare($query_stats);
			$query2->execute();		

			// Traitement du résultat
			$liste = [];
			foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {
				$tmp = [
					'id_produit' 	=> isset($donnee2['id_produit']) 			? intval($donnee2['id_produit']) : 0,
					'pdt_code' 		=> isset($donnee2['code_produit']) 			? $donnee2['code_produit'] : '',
					'pdt_nom' 		=> isset($donnee2['nom_produit']) 			? $donnee2['nom_produit'] : '',
					'id_fam' 		=> isset($donnee2['id_fam']) 				? intval($donnee2['id_fam']) : 0,
					'nom_fam' 		=> isset($donnee2['nom_fam']) 				? $donnee2['nom_fam'] : '',
					'poids' 		=> floatval($donnee2['poids']),
					'vendu_piece' 	=> floatval($donnee2['vendu_piece']) > 0 	? floatval($donnee2['vendu_piece']) : 0,
					'qte' 			=> floatval($donnee2['qte']) > 0 			? floatval($donnee2['qte']) : 1,
					'ca' 			=> floatval($donnee2['ca']),
					'marge_brute' 	=> floatval($donnee2['marge_brute'])
				];
				$liste[] = $tmp;
			}

		// Pas par famille : tous les produits
		} else {

			$distinct = $par_clt ? ' f.`id_tiers_facturation`, ' : '';

			$query_stats = '
				SELECT 
					'.$distinct.' 
					m.`code_produit`, 
					m.`nom_produit`,
					m.`vendu_piece`,
					SUM(CASE WHEN m.`vendu_piece` = 1 THEN m.`qte` ELSE 0 END) AS qte,
					SUM(CASE WHEN m.`vendu_piece` = 0 THEN m.`poids` ELSE 0 END) AS poids,	
					SUM(m.`prix_vente`)  AS ca ,	
					SUM(m.`marge_brute`) AS marge_brute
				FROM 
					`stat_marges_factures` m
				WHERE
					1 = 1
				';

			$query_stats.= $date_du != '' ? ' AND `date_facture` >= "'.$date_du.'" ' 	: '';
			$query_stats.= $date_au != '' ? ' AND `date_facture` <= "'.$date_au.'" ' 	: '';
			$query_stats.= $mois    > 0   ? ' AND `mois_facture` = '.$mois 		: '';
			$query_stats.= $annee   > 0   ? ' AND `annee_facture` = '.$annee 		: '';

			$query_stats.= $par_clt ? ' AND m.`id_tiers_facturation` =' .  intval($donnee['id_tiers_facturation']) : '';
			$query_stats.= ' 
				GROUP BY 
					m.code_produit 
				ORDER BY 
					ca DESC'
				;

			// echo '390 - Verif/optim - ' . $query_stats . '<br />';

			$query2 = $this->db->prepare($query_stats);
			$query2->execute();		

			// Traitement du résultat
			$liste = [];
			foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {
				$tmp = [
					'id_produit' 	=> isset($donnee2['id_produit']) 			? intval($donnee2['id_produit']) : 0,
					'pdt_code' 		=> isset($donnee2['code_produit']) 			? $donnee2['code_produit'] : '',
					'pdt_nom' 		=> isset($donnee2['nom_produit']) 			? $donnee2['nom_produit'] : '',
					'poids' 		=> floatval($donnee2['poids']),
					'vendu_piece' 	=> floatval($donnee2['vendu_piece']) > 0 	? floatval($donnee2['vendu_piece']) : 0,
					'qte' 			=> floatval($donnee2['qte']) > 0 			? floatval($donnee2['qte']) : 1,
					'ca' 			=> floatval($donnee2['ca']),
					'marge_brute' 	=> floatval($donnee2['marge_brute'])
				];
				$liste[] = $tmp;
			}
			
		} // FIN regroupement par familles ou non

		return $liste;

	} // FIN méthode


	// CA par origines
	public function getStatsProduitsOrigines($params = []) {

		$date_du = isset($params['date_du']) ? Outils::dateFrToSql($params['date_du']) : '';
		$date_au = isset($params['date_au']) ? Outils::dateFrToSql($params['date_au']) : '';
		$mois 	 = isset($params['mois'])  && intval($params['mois'])  > 0 ? intval($params['mois'])  : 0;
		$annee 	 = isset($params['annee']) && intval($params['annee']) > 0 ? intval($params['annee']) : 0;

		if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
		if (!Outils::verifDateSql($date_au)) { $date_au = ''; }

		// On récupère les IDS factures concernés pour la période
		$querys_ids_factures = 'SELECT `id` FROM `pe_factures` WHERE `supprime` = 0 ';

		$querys_ids_factures.= $date_du != '' ? ' AND `date` >= "'.$date_du.'" ' 	: '';
		$querys_ids_factures.= $date_au != '' ? ' AND `date` <= "'.$date_au.'" ' 	: '';
		$querys_ids_factures.= $mois    > 0   ? ' AND MONTH(`date`) = '.$mois 		: '';
		$querys_ids_factures.= $annee   > 0   ? ' AND YEAR(`date`) = '.$annee 		: '';

		$query1 = $this->db->prepare($querys_ids_factures);
		$query1->execute();
		$ids_factures_produit = [];
		foreach ($query1->fetchAll(PDO::FETCH_ASSOC) as $donnee1) {
			$id_facture = isset($donnee1['id']) ? intval($donnee1['id']) : 0;
			if ($id_facture > 0) {
				$ids_factures_produit[] = intval($donnee1['id']);
			} // FIN test résultat
		} // FIN boucle factures de la période

		if (!$ids_factures_produit || empty($ids_factures_produit)) { return []; } // Si aucune facture dans cette période

		$liste = [];

		// On récupère tous les pays distincts (origines) facturés pendant cette période
		$query_pays = 'SELECT DISTINCT pl.`id_pays`, p.`nom` AS nom_pays
									FROM `pe_facture_lignes` pl
										JOIN `pe_pays_trad` p ON p.`id_pays` = pl.`id_pays` AND `id_langue` = 1
								WHERE pl.`id_facture` IN ('.implode(',', $ids_factures_produit).') ';

		// echo '818 - ' . $query_pays . '<br />';

		$query = $this->db->prepare($query_pays);
		$query->execute();

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			// Infos de base sur le produit
			$tmp = ['id_pays' => intval($donnee['id_pays']), 'pays_nom' => $donnee['nom_pays']];

			// Données facturées
			$query_stats = '
					SELECT  
						SUM(v.`qte`) AS qte,	
        				SUM(v.`poids`) AS poids,
						SUM(v.`prix_vente`) AS ca
					FROM 
						`pe_facture_lignes` pl 
						LEFT JOIN `pe_factures` f ON f.`id` = pl.`id_facture`
						LEFT JOIN `stat_marges_factures` v ON v.`id_ligne_facture` = pl.`id`
					WHERE 
						pl.`id_facture` IN ('.implode(',', $ids_factures_produit).')
						AND pl.`id_pays` = ' . intval($donnee['id_pays']);

			// echo '842 - ' . $query_stats . '<br />';

			$query2 = $this->db->prepare($query_stats);
			$query2->execute();

			// Boucle stats
			$donnee2 = $query2->fetch(PDO::FETCH_ASSOC);
			$tmp['poids'] 	= floatval($donnee2['poids']);
			$tmp['qte'] 	= floatval($donnee2['qte']) > 0 ? floatval($donnee2['qte']) : 1;
			$tmp['ca'] 		= floatval($donnee2['ca']);

			$liste[] = $tmp;

		} // FIN boucle sur les produits facturés dans la période

		usort($liste, function($a, $b) {
			return $b['ca'] <=> $a['ca'];
		});

		return $liste;

	} // FIN méthode

	// Retourne les clients ayant achetés un produit précis
	public function getStatsProduitClients($params = []) {

		$date_du 	= isset($params['date_du'])   ? Outils::dateFrToSql($params['date_du']) : '';
		$date_au 	= isset($params['date_au'])   ? Outils::dateFrToSql($params['date_au']) : '';
		$mois 	 	= isset($params['mois'])  	  && intval($params['mois'])  > 0 ? intval($params['mois'])  : 0;
		$annee 	 	= isset($params['annee']) 	  && intval($params['annee']) > 0 ? intval($params['annee']) : 0;
		$id_produit	= isset($params['id_produit']) ?  intval($params['id_produit']) : 0;

		if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
		if (!Outils::verifDateSql($date_au)) { $date_au = ''; }
		if ($id_produit == 0) { return []; }
		
		$query_stats = '
			SELECT 
				m.`id_tiers_facturation`, 
				m.`code_client`, 
				m.`nom_client`,
				m.`vendu_piece`,
				SUM(CASE WHEN m.`vendu_piece` = 1 THEN m.`qte` ELSE 0 END) AS qte,
				SUM(CASE WHEN m.`vendu_piece` = 0 THEN m.`poids` ELSE 0 END) AS poids,	
				SUM(m.`prix_vente`) AS ca,	
				SUM(m.`marge_brute`) AS marge_brute
			FROM 
				`stat_marges_factures` m
			WHERE
				m.`id_produit` = '.$id_produit.' 
		';

		$query_stats.= $date_du != '' ? ' AND `date_facture` >= "'.$date_du.'" ' 	: '';
		$query_stats.= $date_au != '' ? ' AND `date_facture` <= "'.$date_au.'" ' 	: '';
		$query_stats.= $mois    > 0   ? ' AND `mois_facture` = '.$mois 		: '';
		$query_stats.= $annee   > 0   ? ' AND `annee_facture` = '.$annee 		: '';

		$query_stats.= ' 
			GROUP BY 
				m.`id_tiers_facturation`
			ORDER BY 
				ca DESC'
			;

		// echo '605 - ' . $query_stats . '<br />';

		$query2 = $this->db->prepare($query_stats);
		$query2->execute();		

		// Traitement du résultat
		$liste = [];
		foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {
			$tmp = [
				'clt_code' 		=> isset($donnee2['code_client'])			? $donnee2['code_client'] : '',
				'clt_nom' 		=> isset($donnee2['nom_client'])			? $donnee2['nom_client'] : '',
				'poids' 		=> floatval($donnee2['poids']),
				'vendu_piece' 	=> floatval($donnee2['vendu_piece']) > 0 	? floatval($donnee2['vendu_piece']) : 0,
				'qte' 			=> floatval($donnee2['qte']) > 0 			? floatval($donnee2['qte']) : 1,
				'ca' 			=> floatval($donnee2['ca']),
				'marge_brute' 	=> floatval($donnee2['marge_brute'])
			];
			$liste[] = $tmp;
		}

		return $liste;

	} // FIN méthode

	// Retourne les clients ayant achetés une espèce précise
	public function getStatsEspeceClients($params = []) {
		
		$date_du 	= isset($params['date_du'])   ? Outils::dateFrToSql($params['date_du']) : '';
		$date_au 	= isset($params['date_au'])   ? Outils::dateFrToSql($params['date_au']) : '';
		$mois 	 	= isset($params['mois'])  	  && intval($params['mois'])  > 0 ? intval($params['mois'])  : 0;
		$annee 	 	= isset($params['annee']) 	  && intval($params['annee']) > 0 ? intval($params['annee']) : 0;
		$id_espece	= isset($params['id_espece']) ?  intval($params['id_espece']) : 0;

		if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
		if (!Outils::verifDateSql($date_au)) { $date_au = ''; }
		if ($id_espece == 0) { return []; }

		// on récupère toutes les factures de cette période pour cette espece
		$query_clients = '
				SELECT 
					f.`id_tiers_facturation`, 
					GROUP_CONCAT(f.`id`) AS ids_factures, 
					IFNULL(t.`code`, "") AS code, 
					t.`nom`
				FROM 
					`pe_factures` f
					JOIN `pe_facture_lignes` fl ON fl.`id_facture` = f.`id` 
					JOIN `pe_produits` p ON p.`id` = fl.`id_produit` AND p.`id_espece` = '.$id_espece.'
					JOIN `pe_tiers` t ON t.`id` = f.`id_tiers_facturation`
				WHERE 
					f.`supprime` = 0 ';

		$query_clients.= $date_du != '' ? ' AND f.`date` >= "'.$date_du.'" ' 	: '';
		$query_clients.= $date_au != '' ? ' AND f.`date` <= "'.$date_au.'" ' 	: '';
		$query_clients.= $mois    > 0   ? ' AND MONTH(f.`date`) = '.$mois 		: '';
		$query_clients.= $annee   > 0   ? ' AND YEAR(f.`date`) = '.$annee 		: '';

		$query_clients.= ' GROUP BY f.`id_tiers_facturation` ORDER BY t.`nom`';

		// echo '986 - ' . $query_clients . '<br />';

		$query1 = $this->db->prepare($query_clients);
		$query1->execute();

		$liste = [];

		// Boucle sur les clients concernées par ce produit pour la période
		foreach ($query1->fetchAll(PDO::FETCH_ASSOC) as $donnee1) {

			// On a le client ici et les IDS de ses factures concernées
			$id_client = isset($donnee1['id_tiers_facturation']) ? intval($donnee1['id_tiers_facturation']) : 0;
			$ids_factures = isset($donnee1['ids_factures']) ? $donnee1['ids_factures'] : '';
			if ($id_client == 0 || $ids_factures == '') { continue; }

			$tmp = ['id_client' => $id_client, 'clt_code' => $donnee1['code'], 'clt_nom' => $donnee1['nom']];

			// Données facturées
			$query_stats = '
				SELECT
					SUM(v.`qte`) AS qte,	
					SUM(v.`poids`) AS poids,
					SUM(v.`prix_vente`)  AS ca ,	
					SUM(v.`marge_brute`) AS marge_brute
				FROM 
					`pe_facture_lignes` pl 
					LEFT JOIN `pe_factures` f ON f.`id` = pl.`id_facture`
					LEFT JOIN `stat_marges_factures` v ON v.`id_ligne_facture` = pl.`id`
				WHERE 
					pl.`id_facture` IN (' . $ids_factures . ')';

			// echo '657 - ' . $query_stats . '<br />';

			$query2 = $this->db->prepare($query_stats);
			$query2->execute();

			// Boucle stats
			$donnee2 = $query2->fetch(PDO::FETCH_ASSOC);
			$tmp['poids'] = floatval($donnee2['poids']);
			$tmp['qte'] = floatval($donnee2['qte']) > 0 ? floatval($donnee2['qte']) : 1;
			$tmp['ca'] = floatval($donnee2['ca']);
			$tmp['marge_brute'] = floatval($donnee2['marge_brute']);

			// On intègre au retour
			$liste[] = $tmp;

		} // FIN boucle factures

		usort($liste, function($a, $b) {
			return $b['ca'] <=> $a['ca'];
		});

		return $liste;

	} // FIN méthode


	public function getTotalCaPerdiod($params = []) {

		$date_du = isset($params['date_du']) ? Outils::dateFrToSql($params['date_du']) : '';
		$date_au = isset($params['date_au']) ? Outils::dateFrToSql($params['date_au']) : '';
		$mois 	 = isset($params['mois'])  && intval($params['mois'])  > 0 ? intval($params['mois'])  : 0;
		$annee 	 = isset($params['annee']) && intval($params['annee']) > 0 ? intval($params['annee']) : 0;

		if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
		if (!Outils::verifDateSql($date_au)) { $date_au = ''; }

		$query_stats = '
			SELECT 
				SUM(v.`prix_vente`)  AS ca 
			FROM 
				`stat_marges_factures` v 
			WHERE 
				1=1 ';

		$query_stats.= $date_du != '' ? ' AND `date_facture` >= "'.$date_du.'" ' 	: '';
		$query_stats.= $date_au != '' ? ' AND `date_facture` <= "'.$date_au.'" ' 	: '';
		$query_stats.= $mois    > 0   ? ' AND `mois_facture` = '.$mois 		: '';
		$query_stats.= $annee   > 0   ? ' AND `annee_facture` = '.$annee 		: '';
		
		// echo '706 - Verif/optim -' . $query_stats . '<br />';

		$query2 = $this->db->prepare($query_stats);
		$query2->execute();

		// Boucle stats
		$donnee2 = $query2->fetch(PDO::FETCH_ASSOC);
		if (!$donnee2 || !isset($donnee2['ca'])) { return 0; }
		return round(floatval($donnee2['ca']),2);

	}

} // FIN classe