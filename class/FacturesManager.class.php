<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Facture
Généré par CBO FrameWork le 06/03/2020 à 14:50:20
------------------------------------------------------*/
class FacturesManager {

	/*
	 * vue `pev_marges_factures` :
	 * ---------------------------
	 * SELECT fl.`id_facture`, fl.`id` AS id_ligne_facture,  fl.`id_produit`, fl.`qte`, fl.`poids`, fl.`pu_ht`, fl.`vendu_piece`, fl.`tva`/100 AS taux_tva, IFNULL(tf.`prix`, 0) AS tarif_frs,
		IF (((fl.`vendu_piece` = 1 OR fl.`id_produit` = 0) AND fl.`qte` > 0), fl.`qte`, fl.`poids`) * fl.`pu_ht` AS prix_vente,
		IF (((fl.`vendu_piece` = 1 OR fl.`id_produit` = 0) AND fl.`qte` > 0), fl.`qte`, fl.`poids`) * IF (fl.`pa_ht` > 0, fl.`pa_ht`, IFNULL(tf.`prix`, 0)) AS prix_achat,
		(IF (((fl.`vendu_piece` = 1 OR fl.`id_produit` = 0) AND fl.`qte` > 0), fl.`qte`, fl.`poids`) * fl.`pu_ht`) - (IF (((fl.`vendu_piece` = 1 OR fl.`id_produit` = 0) AND fl.`qte` > 0), fl.`qte`, fl.`poids`) * 	IF (fl.`pa_ht` > 0, fl.`pa_ht`, IFNULL(tf.`prix`, 0))) AS marge_brute
		FROM `pe_facture_lignes` fl
		LEFT JOIN `pe_factures` f ON f.`id` = fl.`id_facture`
		LEFT JOIN `pe_produits` p ON p.`id` = fl.`id_produit`
		LEFT JOIN `pe_facture_ligne_bl` flb ON flb.`id_ligne_facture` = fl.`id`
		LEFT JOIN `pe_bl_lignes` bll ON bll.`id` = flb.`id_ligne_bl`
		LEFT JOIN `pe_palette_composition` pc ON pc.`id` = bll.`id_compo`
		LEFT JOIN `pe_froid_produits` fp ON fp.`id_lot_pdt_froid` = pc.`id_lot_pdt_froid`
		LEFT JOIN `pe_lots` l ON l.`id` = fp.`id_lot`
		LEFT JOIN `pe_tarif_fournisseur` tf ON tf.`id_produit` = fl.`id_produit` AND tf.`id_tiers` = l.`id_fournisseur`
		WHERE f.`supprime` = 0 AND fl.`supprime` = 0
        GROUP BY fl.`id`
	 */

	protected    $db, $nb_results, $libelle_frais_livraison_web;

	public function __construct($db) {
		$this->setDb($db);
		$this->libelle_frais_livraison_web = 'FRAIS DE LIVRAISON WEB';
	}

	/* ----------------- GETTERS ----------------- */
	public function getNb_results() {
		return $this->nb_results;
	}

	public function getLibelle_frais_livraison_web() {
		return $this->libelle_frais_livraison_web;
	}

	/* ----------------- SETTERS ----------------- */
	public function setDb(PDO $db) {
		$this->db = $db;
	}

	public function setNb_results($nb_results) {
		$this->nb_results = (int)$nb_results;
	}

	/* ----------------- METHODES ----------------- */

	// Retourne la liste des Factures
	public function getListeFactures($params = []) {

		global $utilisateur;
		$t0 = microtime(true);

		$supprime   = isset($params['supprime']) 	? intval($params['supprime']) 	: 0;
		$id 		= isset($params['id']) 			? intval($params['id']) 		: 0;
		$id_tiers 	= isset($params['id_client']) 	? intval($params['id_client']) 	: 0;
		$ids_tiers 	= isset($params['id_clients']) 	? trim($params['id_clients']) 	: '';
		$reglee 	= isset($params['reglee']) 		? intval($params['reglee']) 	: -1;
		$id_bl 		= isset($params['id_bl']) 		? intval($params['id_bl']) 		: 0;
		$get_bls 	= isset($params['bls']) 		? boolval($params['bls']) 		: true;
		$get_lignes = isset($params['lignes']) 		? boolval($params['lignes']) 	: true;

		$factavoirs = isset($params['factavoirs']) 	? strtolower($params['factavoirs']) : '';
		$num_cmd 	= isset($params['num_cmd']) 	? trim($params['num_cmd']) 			: '';
		$num_fact 	= isset($params['num_fact']) 	? trim($params['num_fact']) 		: '';
		$date_du 	= isset($params['date_du']) 	? trim($params['date_du']) 			: '2021-11-01';
		$date_au 	= isset($params['date_au']) 	? trim($params['date_au']) 			: '';

		if ($date_du < '2021-11-01') {
			$date_du = '2021-11-01';
		}

		// Pagination
		$start 				= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 				= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS f.`id`, f.`num_facture`, f.`id_tiers_livraison`, f.`id_tiers_facturation`, f.`id_tiers_transporteur`, f.`id_adresse_facturation`, f.`id_adresse_livraison`, f.`date`, f.`date_add`, f.`num_cmd`, f.`montant_ht`, f.`date_compta`, f.`supprime`, f.`total_ttc`, IFNULL(f.`date_envoi`, "") AS date_envoi, f.`remise_ht`, f.`remise_ttc`  ';

		if ($reglee > -1) {
			$query_liste.= ' , (IF (f.`total_ttc` < 0, f.`total_ttc`*-1, f.`total_ttc`) - (IFNULL(SUM(r.`montant`),0) * (IF (f.`total_ttc` < 0, -1, 1)))) AS reste  ';
		}

		$query_liste.= " FROM `pe_factures` f ";

		if ($reglee > -1) {
			$query_liste.= ' LEFT JOIN `pe_facture_reglements` r ON r.`id_facture` = f.`id`';
		}

		$query_liste.= " WHERE f.`supprime` =  " . $supprime . " ";

		$query_liste.= $id > 0 ? ' AND f.`id` = ' . $id : '';
		$query_liste.= $id_tiers > 0 ? ' AND (f.`id_tiers_livraison` = ' . $id_tiers . ' OR f.`id_tiers_facturation` = ' . $id_tiers . ') ' : '';
		$query_liste.= $ids_tiers != '' ? ' AND (f.`id_tiers_livraison` IN (' . $ids_tiers . ') OR f.`id_tiers_facturation` IN (' . $ids_tiers . ')) ' : '';
		$query_liste.= $id_bl > 0 ? " AND f.`id` IN ( SELECT `id_facture` FROM `pe_bl_facture` WHERE `id_bl` = ".$id_bl." ) " : "";
		$query_liste.= $num_cmd != '' ? ' AND f.`num_cmd` LIKE "%'.$num_cmd.'%" ' : '';
		$query_liste.= $date_du != '' ? ' AND f.`date` >= "'.$date_du.'" ' : '';
		$query_liste.= $date_au != '' ? ' AND f.`date` <= "'.$date_au.'" ' : '';
		$query_liste.= $num_fact != '' ? ' AND f.`num_facture` LIKE "%'.$num_fact.'%" ' : '';
		$query_liste.= $factavoirs == 'a' ? ' AND  f.`montant_ht` < 0 ' : '';
		$query_liste.= $factavoirs == 'f' ? ' AND  f.`montant_ht` >= 0 ' : '';

		if ($reglee > -1) {
			$query_liste.= ' GROUP BY f.`id`';
		}

		$query_liste.= $reglee == 1 ? ' HAVING (reste > -0.2 AND reste < 0.2) ' : '';
		$query_liste.= $reglee == 0 ? ' HAVING (reste < -0.2 OR reste > 0.2) ' : '';


		$query_liste.= " ORDER BY ";
		$query_liste.= $ids_tiers != '' ? "   f.`id_tiers_facturation` ASC, f.`id` DESC " : " f.`id` DESC ";

		$query_liste.= 'LIMIT ' . $start . ',' . $nb;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		if ($get_bls) {
			$blManager = new BlManager($this->db);
		}
		
		if ($utilisateur->isDev()) { 
			$t1 = microtime(true);
			// echo 'Avant la boucle : ' . number_format($t1 - $t0, 2) . ' secondes<br />';	
		}

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {			

			$facture = new Facture($donnee);
			if (!$facture instanceof Facture) { continue; }


			$montant_interbev = $this->getInterbevFacture($facture);
			$facture->setMontant_interbev($montant_interbev);

			$montant_tva = $this->getTvaFacture($facture);
			$facture->setMontant_tva($montant_tva);

			// On rattache les lignes de la facture
			if ($get_lignes) {
				$lignes = $get_lignes ? $this->getListeFactureLignes(['id_facture' => $facture->getId()]) : [];
				$facture->setLignes($lignes);
			}

			// On rattache les BLs liés à la facture
			if ($get_bls) {
				$bls = $get_bls ? $blManager->getListeBl(['id_facture' => $facture->getId(), 'factures' => false]) : [];
				$facture->setBls($bls);
			}
			$liste[] = $facture;
		}

		if ($utilisateur->isDev()) { 
			$t1 = microtime(true);
			// echo 'Après la boucle : ' . number_format($t1 - $t0, 2) . ' secondes<br />';	
		}
		

		return $liste;

	} // FIN méthode

	// Retounr le montant de la TVA d'une facture
	public function getTvaFacture(Facture $facture) {

		global $utilisateur;	
		$t0 = microtime(true);
		
		// Si le client n'est pas assujeti à la TVA on retourne 0
		$tiersManager = new TiersManager($this->db);
		$client = $tiersManager->getTiers($facture->getId_tiers_facturation());
		if ($client instanceof Tiers) {
			if ($client->getTva() == 0) {
				return 0;
			}
		}		

		if ($utilisateur->isDev()) { 
			$t1 = microtime(true);
			// echo 'TVA - Avt getListeFactureLignes : ' . number_format($t1 - $t0, 2) . ' secondes<br />';	
		}
		
		if (empty($facture->getLignes())) {
			$lignes = $this->getListeFactureLignes(['id_facture' => $facture->getId()]);
			$facture->setLignes($lignes);
		}

		$total_montant = 0;
		$montant_tva = 0;
		$total_interbev = 0;
		$base_tva = 0;

		if ($utilisateur->isDev()) { 
			$t1 = microtime(true);
			// echo 'TVA - montants à 0 : ' . number_format($t1 - $t0, 2) . ' secondes<br />';	
		}
				
		$tvas_facture = $this->getTvasFacture($facture);		
		
		foreach ($facture->getLignes() as $ligne) {						
			$total_montant+=round($ligne->getTotal(),2);
			$total_interbev+=round($ligne->getInterbev(),2);
			if ($ligne->getTva() != 0) {
				$base_tva+=round($ligne->getTotal(),2);
			}
			// Intégration des frais additionnels
			$frais = $this->getListeFactureFrais($facture->getId());
			// On intègre les TVA des frais s'il y en a
			foreach ($frais as $f) {
				if ((float)$f->getTaxe_taux() == 0) { continue; }
				if (!isset($tvas_facture[(string)$f->getTaxe_taux()])) {
					$tvas_facture[(string)$f->getTaxe_taux()] = 0;
				}
				if ($f->getType() == 0) {
					$tvaFrais = $f->getValeur() * ($f->getTaxe_taux()/100);
				} else {
					$tvaFrais = ($total_montant * ($f->getValeur() / 100)) * ($f->getTaxe_taux()/100);
				}
				$montant_tva+=$tvaFrais;
				$tvas_facture[(string)$f->getTaxe_taux()]+= $tvaFrais;
			} // FIn boucle sur les frais additionnel pour prise en compte des TVA
		}

		if ($utilisateur->isDev()) { 
			$t1 = microtime(true);
			// echo 'TVA - avt interbev : ' . number_format($t1 - $t0, 2) . ' secondes<br />';	
		}

		// On rajoute l'interbev à la TVA 5.5
		if ((float)$total_interbev > 0) {

			if (!isset($configManager)) {
				$configManager = new ConfigManager($this->db);
			}
			$tva_interbev = $configManager->getConfig('tva_interbev');
			if (!$tva_interbev instanceof Config) { exit('ERREUR RECUP TAUX TVA INTERBEV !'); }
			$taxesManager = new TaxesManager($this->db);
			$taxeInterbevTva = $taxesManager->getTaxe((int)$tva_interbev->getValeur());
			if (!$taxeInterbevTva instanceof Taxe) { exit('ERREUR RECUP TAXE TVA POUR INTERBEV !'); }

			$tvaInterbev = $total_interbev * ($taxeInterbevTva->getTaux()/100);

			if (!isset($tvas_facture[$taxeInterbevTva->getTaux()])) {
				$tvas_facture[$taxeInterbevTva->getTaux()] = 0;
			}
			$tvas_facture[(float)$taxeInterbevTva->getTaux()]+=$tvaInterbev;

		}

		if (empty($tvas_facture)) { return 0; }

		if ($utilisateur->isDev()) { 
			$t1 = microtime(true);
			// echo 'TVA - après interbev : ' . number_format($t1 - $t0, 2) . ' secondes<br />';	
		}

		

		foreach ($tvas_facture as $pourcentage => $montantTvaTaux) {						
			$montant_tva+=round($montantTvaTaux,2);
			
		} // FIN boucle sur les taux de tva
		
		if ($utilisateur->isDev()) { 
			$t1 = microtime(true);
			// echo 'TVA - fin calcul par facture : ' . number_format($t1 - $t0, 2) . ' secondes<br />';	
		}		
		return $montant_tva ;

	} // FIN méthode

	// Retourne le montant Interbev d'une facture
	public function getInterbevFacture(Facture $facture) {

		$typeFacture = substr($facture->getNum_facture(), 0, 2);
		$configManager = new ConfigManager($this->db);
		$interbev = $configManager->getConfig('interbev');
		if (!$interbev instanceof Config) { return 0; }
		if (intval($interbev->getValeur()) == 0) { return 0; }

		$query_interbev = 'SELECT ROUND(SUM( `tarif_interbev` * `poids`),3) AS interbev FROM `pe_facture_lignes` WHERE `supprime` = 0 AND `id_facture` = ' . $facture->getId();
		$query = $this->db->prepare($query_interbev);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		
		if ($typeFacture == 'AV'){
			$donnees['interbev'] = -$donnees['interbev'];	
		}
		
		return $donnees && isset($donnees['interbev']) ? floatval($donnees['interbev']) : 0;

	} // FIN méthode


	// Retourne une Facture par BL (facture contenant ce BL)
	public function getFactureByBl($id_bl) {

		$query_object = 'SELECT `id`, `num_facture`, `id_tiers_livraison`, `id_tiers_facturation`, `id_tiers_transporteur`, IFNULL(`date_envoi`, "") AS date_envoi, `id_adresse_facturation`, `id_adresse_livraison`, `date`, `date_add`, `num_cmd`, `montant_ht`, `date_compta`, `supprime`,`total_ttc`, `remise_ht`, `remise_ttc`
                FROM `pe_factures` WHERE `supprime` = 0 AND `id` = (SELECT `id_facture` FROM `pe_bl_facture` WHERE `id_bl` = ' . (int)$id_bl . ')';
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }

			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);			

			$facture =  $donnee && isset($donnee[0]) ? new Facture($donnee[0]) : false;
			if (!$facture instanceof Facture) { return false; }

			// On rattache les lignes de la facture
			$lignes = $this->getListeFactureLignes(['id_facture' => $facture->getId()]);
			$facture->setLignes($lignes);

			return $facture;

	} // FIN méthode

	// Retourne un Bl
	public function getFacture($id, $details = true) {

		$query_object = 'SELECT `id`, `num_facture`, `id_tiers_livraison`, `id_tiers_facturation`, `id_tiers_transporteur`, `id_adresse_facturation`, `id_adresse_livraison`, `date`, `date_add`, `num_cmd`, `total_ttc`,  `montant_ht`, `montant_interbev`, `date_compta`, `supprime`, IFNULL(`date_envoi`, "") AS date_envoi , `remise_ht`, `remise_ttc` FROM `pe_factures` WHERE `id` = ' . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {

			$blManager = new BlManager($this->db);

			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			$facture =  $donnee && isset($donnee[0]) ? new Facture($donnee[0]) : false;

			if (!$facture instanceof Facture) { return false; }

			if ($details) {
				$montant_interbev = $this->getInterbevFacture($facture);
				$facture->setMontant_interbev($montant_interbev);

				$montant_tva = $this->getTvaFacture($facture);				

				
				$facture->setMontant_tva($montant_tva);

				// On rattache les lignes de la facture
				$lignes = $this->getListeFactureLignes(['id_facture' => $facture->getId()]);
				$facture->setLignes($lignes);

				// On rattache les BLs liés à la facture
				$bls = $blManager->getListeBl(['id_facture' => $facture->getId()]);
				$facture->setBls($bls);
			}

			return $facture;

		} else {
			return false;
		}
	} // FIN get

	// Enregistre & sauvegarde (Méthode Save)
	public function saveFacture(Facture $objet) {

		$table      = 'pe_factures'; // Nom de la table
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
			} catch(PDOExeption $e) { 	return false;}
		}

		return false;

	} // FIN méthode

	// Retourne la liste des FactureLigne
	public function getListeFactureLignes($params = []) {

		$id_facture = isset($params['id_facture']) ? intval($params['id_facture']) : 0;
		$ids_facture = isset($params['ids_facture']) && is_array($params['ids_facture']) ? $params['ids_facture'] : [];
		$id_langue = isset($params['id_langue']) ? intval($params['id_langue']) : 1;

		$tiersManager = new TiersManager($this->db);
		$id_tiers_profil_export = intval($tiersManager->getIdProfilExport());

		$query_liste = 'SELECT DISTINCT l.`id`, l.`id_facture`, l.`id_facture_avoir`, l.`date_add`, l.`id_produit`, l.`numlot`, l.`poids`,
                						l.`nb_colis`, l.`qte`, l.`pu_ht`, l.`tva`, l.`tarif_interbev`,  p.`code`, y.`nom` AS origine,
                						l.`tva`, l.`tva` AS taux_tva, f.`num_facture`,
                						IFNULL(v.`prix_vente`, 0) AS total, IFNULL(v.`vendu_piece`, 1) AS vendu_piece, l.`designation`,
                						IF (l.`pa_ht` > 0, l.`pa_ht`, tf.`prix`) AS pa_ht, l.`id_frs`, IFNULL(frs.`nom`, "") AS nom_frs, l.`total_ht`
								FROM `pe_facture_lignes` l
									LEFT JOIN `pe_marges_factures` v ON v.`id_ligne_facture` =  l.`id`
									LEFT JOIN `pe_factures` f ON f.`id` =  l.`id_facture`
									LEFT JOIN `pe_produits` p ON p.`id` =  l.`id_produit`
									LEFT JOIN `pe_taxes` t ON t.`id` =  l.`id_produit`
									LEFT JOIN `pe_pays_trad` y ON y.`id_pays` =  l.`id_pays` AND y.`id_langue` = '.$id_langue.'
									LEFT JOIN `pe_tarif_fournisseur` tf ON tf.`id_tiers` = '.$id_tiers_profil_export.' AND tf.`id_produit` = l.`id_produit`
									LEFT JOIN `pe_tiers` frs ON frs.`id` = l.`id_frs`
								WHERE l.`supprime` = 0 ';

		$query_liste.= $id_facture > 0 ? " AND l.`id_facture` = " . $id_facture : "" ;
		$query_liste.= !empty($ids_facture) ? ' AND l.`id_facture` IN ('.implode(',', $ids_facture).') ' : '' ;

		$query_liste.= " ORDER BY l.`id` DESC";

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		$produitsManager = new ProduitManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$ligne = new FactureLigne($donnee);			

			// Rattachement de l'objet Produit
			if ((int)$ligne->getId_produit() > 0) {
				$pdt = $produitsManager->getProduit($ligne->getId_produit());
				$ligne->setProduit($pdt);
			} else {
				$ligne->setProduit(new Produit([]));
			}

			$this->persistancePrixAchat($ligne, $id_tiers_profil_export);

			$liste[] = $ligne;
		}

		return $liste;

	} // FIN liste des FactureLigne


	// Retourne un FactureLigne
	public function getFactureLigne($id) {	

		$query_object = "SELECT `id`, `id_facture`, `id_facture_avoir`, `date_add`, `id_produit`, `numlot`, `poids`, `nb_colis`, `qte`, `pu_ht`, `tva`, `tarif_interbev`, `designation`, `id_frs`,`total_ht`
                FROM `pe_facture_lignes` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);		
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			
			return $donnee && isset($donnee[0]) ? new FactureLigne($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get FactureLigne


	// Retourne un FactureLigne depuis la vue
	public function getFactureLigneFromVue($id) {

		$query_object = "SELECT fl.`id`, fl.`id_facture`, fl.`id_facture_avoir`, fl.`date_add`, fl.`id_produit`, fl.`numlot`, fl.`poids`, fl.`nb_colis`, fl.`qte`, fl.`pu_ht`, fl.`tva`, fl.`tarif_interbev`, v.`prix_vente` AS total, fl.`designation`, fl.`id_frs`
                FROM `pe_marges_factures` v JOIN `pe_facture_lignes` fl ON fl.`id` = v.`id_ligne_facture` WHERE v.`id_ligne_facture` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new FactureLigne($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get FactureLigne

	// Enregistre & sauvegarde (Méthode Save)
	public function saveFactureLigne(FactureLigne $objet) {

		$table      = 'pe_facture_lignes'; // Nom de la table
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

	// Enregistre la liaison entre une facture et ses BLs
	public function saveLiaisonFactureBls($id_facture, $ids_bl) {

		if (!is_array($ids_bl) || $id_facture == 0 || empty($ids_bl)) { return false; }

		$query_del = 'DELETE FROM `pe_bl_facture` WHERE `id_facture` = ' . (int)$id_facture . ' OR `id_bl` IN ('.implode(',',$ids_bl).')';
		$query1 = $this->db->prepare($query_del);
		$query1->execute();
		Outils::saveLog($query_del);

		foreach ($ids_bl as $id_bl) {
			$query_ins = 'INSERT IGNORE INTO `pe_bl_facture` (`id_bl`, `id_facture`) VALUES ('.(int)$id_bl.', '.(int)$id_facture.')';
			$query2 = $this->db->prepare($query_ins);
			$query2->execute();
			Outils::saveLog($query_ins);
		}
		return true;

	} // FIN méthode

	// Enregistre les lignes de factures d'après les lignes de ses BLs
	public function saveLignesFactureFromBl($id_facture, $ids_bl) {

		if (!is_array($ids_bl) || $id_facture == 0 || empty($ids_bl)) { return false; }

		$query_del = 'DELETE FROM `pe_facture_lignes` WHERE `id_facture` = ' . (int)$id_facture;
		$query1 = $this->db->prepare($query_del);
		$query1->execute();
		Outils::saveLog($query_del);

		$blManager = new BlManager($this->db);

		$nb_ok = 0;
		$nb_lignes = 0;

		$total_interbev = 0.0;

		$produitManager = new ProduitManager($this->db);
		$tiersManager = new TiersManager($this->db);

		// Boucle sur les BL
		foreach ($ids_bl as $id_bl) {

			$bl = $blManager->getBl($id_bl, true, true, false);

			// Si le client n'est pas éligible à la TVA, on force la TVA à 0
			$client = $tiersManager->getTiers($bl->getId_tiers_facturation());
			$horsTva = false;
			if ($client instanceof Tiers) {
				$horsTva = $client->getTva() == 0;
			}

			$tiersManager = new TiersManager($this->db);
			$tarifManager = new TarifsManager($this->db);
			$id_tiers_profil_export = intval($tiersManager->getIdProfilExport());

			// On boucle sur les ligne du BL en cours
			foreach ($bl->getLignes() as $ligne) {

				$nb_lignes++;

				$tarif_interbev = $blManager->getTarifInterbevLigneBl($ligne, $bl->getId_adresse_livraison());

				// Si on a un id_pays déjà renseigné dans la ligne de BL et si on en as pas on prends alors comme avant celui du lot
				$id_pays = (int)$ligne->getId_pays() > 0 ? $ligne->getId_pays() : $blManager->getIdPaysFromLot($ligne->getId_lot());

				$total_interbev+=floatval($tarif_interbev) * (float)$ligne->getPoids();

				$id_pdt = (int)$ligne->getId_produit_bl() > 0 ? $ligne->getId_produit_bl() : $ligne->getId_produit();

				$pdt = $produitManager->getProduit($id_pdt, false);
				if (!$pdt instanceof Produit) { $pdt = new Produit([]);	}
				if ($pdt->getId() > 0 && (int)$pdt->getVendu_piece() == 0) { $ligne->setQte(1); }

				$vendu_piece = $pdt->getVendu_piece();

				$id_pdt_web = $produitManager->getIdProduitWeb();

				if (intval($id_pdt) > 0 && $id_pdt != $id_pdt_web) {
					$tva = $produitManager->getTvaProduit($id_pdt);
				} else {
					// Lignes libres, pas d'id produit
					$tva = $ligne->getTva();
				}

				$pa_ht = $tarifManager->getTarifFournisseurByFrsAndProduit($id_tiers_profil_export, $id_pdt);
				if (!$pa_ht) { $pa_ht = 0; }

				$ligne_facture = new FactureLigne([]);
				$ligne_facture->setId_facture($id_facture);
				$ligne_facture->setDate_add(date('Y-m-d H:i:s'));
				$ligne_facture->setPu_ht($ligne->getPu_ht());
				$ligne_facture->setPa_ht($pa_ht);
				$ligne_facture->setNb_colis($ligne->getNb_colis());
				$ligne_facture->setPoids($ligne->getPoids());
				$ligne_facture->setTva($tva);
				$ligne_facture->setQte($ligne->getQte());
				$ligne_facture->setNumlot($ligne->getNumlot());
				$ligne_facture->setId_produit($id_pdt);
				$ligne_facture->setId_pays($id_pays);
				$ligne_facture->setTarif_interbev($tarif_interbev);
				$ligne_facture->setId_frs($ligne->getId_frs());
				$ligne_facture->setVendu_piece($vendu_piece);
				$total_ht = $ligne->getPu_ht() * ($vendu_piece > 0 ? floatval($ligne->getQte()) : floatval($ligne->getPoids()));				
				$ligne_facture->setTotal_ht($total_ht);

				if ($pdt->getId() == 0 && $ligne->getDesignation() != '') {
					$ligne_facture->setDesignation($ligne->getDesignation());
				}

				$id_ligne_facture = $this->saveFactureLigne($ligne_facture);

				if (intval($id_ligne_facture) > 0) {
					$nb_ok++;
					$this->liaisonLignesBlFacture($ligne->getId(), $id_ligne_facture);
				}

			} // FIN boucle sur les lignes du BL

		} // FIN boucle BL

		$query_upd = 'UPDATE `pe_factures` SET `montant_interbev` = ' . (float)$total_interbev . ' WHERE `id` = ' . (int)$id_facture;
		$query2 = $this->db->prepare($query_upd);
		$query2->execute();
		Outils::saveLog($query_upd);

		$this->uniciteLignesFactures($id_facture);

		return $nb_ok == $nb_lignes;

	} // FIN méthode


	// Associe une ligne BL/Facture
	public function liaisonLignesBlFacture($id_ligne_bl, $id_ligne_facture) {

		$ids_lignes_bls = is_array($id_ligne_bl) ? $id_ligne_bl : [(int)$id_ligne_bl];

		foreach ($ids_lignes_bls as $id_lbl) {
			$query_ins = 'INSERT IGNORE INTO `pe_facture_ligne_bl` (`id_ligne_bl`, `id_ligne_facture`) VALUES ('.(int)$id_lbl.', '.(int)$id_ligne_facture.')';
			$query = $this->db->prepare($query_ins);
			$query->execute();
			Outils::saveLog($query_ins);
		}

		return true;

	} // FIN méthode

	// Gére l'unicité des lignes d'une facture fraichement générée
	public function uniciteLignesFactures($id_facture) {


		$query_lignes_facture = 'SELECT fl.`id`, fl.`id_produit`, fl.`numlot`, fl.`poids`, fl.`pu_ht`, fl.`tva`, fl.`nb_colis`, fl.`qte`, fl.`tarif_interbev`, flbl.`id_ligne_bl` , fl.`id_pays`, fl.`designation`, fl.`ìd_frs`
									FROM `pe_facture_lignes` fl
									LEFT JOIN `pe_facture_ligne_bl` flbl ON flbl.`id_ligne_facture` = fl.`id`
								WHERE fl.`id_facture` = ' . (int)$id_facture . ' ORDER BY fl.`id_produit`, fl.`numlot`, fl.`pu_ht`, fl.`tva` ';

		$query = $this->db->prepare($query_lignes_facture);
		$query->execute();
		$liste = [];

		$ids_lignes_factures = [];

		$produitManager = new ProduitManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$unicite_ligne = $donnee['id_produit'].'l'.$donnee['numlot'].'x'.$donnee['pu_ht'] . 't'. $donnee['tva'].'i'.$donnee['tarif_interbev'].'d'.$donnee['designation'];

			$ids_lignes_factures[] = (int)$donnee['id'];

			if (!isset($liste[$unicite_ligne])) {

				$tmp = [
					'id_produit' => intval($donnee['id_produit']),
					'numlot' => trim($donnee['numlot']),
					'pu_ht' => trim($donnee['pu_ht']),
					'designation' => trim($donnee['designation']),
					'tarif_interbev' => trim($donnee['tarif_interbev']),
					'tva' => trim($donnee['tva']),
					'nb_colis' => intval($donnee['nb_colis']),
					'qte' => floatval($donnee['qte']),
					'poids' => floatval($donnee['poids']),
					'id_pays' => intval($donnee['id_pays']),
					'ids_lignes_bl' => [$donnee['id_ligne_bl']]
				];

			} else {

				$tmp = $liste[$unicite_ligne];
				$tmp['nb_colis']+= intval($donnee['nb_colis']);
				$tmp['qte']+= floatval($donnee['qte']);
				$tmp['poids']+= floatval($donnee['poids']);
				$tmp['ids_lignes_bl'][] = $donnee['id_ligne_bl'];
			}

			$pdt = $produitManager->getProduit($donnee['id_produit'], false);
			if (!$pdt instanceof Produit) {
				$pdt = new Produit([]);
			}
			if ((int)$donnee['id_produit'] > 0 && (int)$pdt->getVendu_piece() == 0) {
				$tmp['qte'] = 1;
			}

			$liste[$unicite_ligne] = $tmp;

		} // FIN boucle lignes facture

		// On boucle sur les lignes unicitaires
		foreach ($liste as $donnees) {

			// Et ici à l'unicité on va supprimer et regrouper en créant une ligne par unicité
			$ligneFacture = new FactureLigne([]);
			$ligneFacture->setId_facture($id_facture);
			$ligneFacture->setId_produit($donnees['id_produit']);
			$ligneFacture->setNumlot($donnees['numlot']);
			$ligneFacture->setPoids($donnees['poids']);
			$ligneFacture->setNb_colis($donnees['nb_colis']);
			$ligneFacture->setQte($donnees['qte']);
			$ligneFacture->setPu_ht($donnees['pu_ht']);
			$ligneFacture->setTva($donnees['tva']);
			$ligneFacture->setId_pays($donnees['id_pays']);
			$ligneFacture->setTarif_interbev($donnees['tarif_interbev']);
			$ligneFacture->setDate_add(date('Y-m-d H:i:s'));
			$ligneFacture->setDesignation($donnees['designation']);
			$ligneFacture->setId_frs($donnees['id_frs']);

			$id_ligne_facture = $this->saveFactureLigne($ligneFacture);

			if (intval($id_ligne_facture) > 0) {
				$this->liaisonLignesBlFacture($donnees['ids_lignes_bl'], $id_ligne_facture);
			}

		} // FIN boucle sur les données d'unicité

		// On supprime les anciennes liaison id_lignes_bl/facture temporaire et on supprime les anciennes lignes de facture
		if (!empty($ids_lignes_factures)) {
			$query_del = 'DELETE FROM `pe_facture_ligne_bl` WHERE `id_ligne_facture` IN ('.implode(',', $ids_lignes_factures).');';
			$query_del.= 'DELETE FROM `pe_facture_lignes` WHERE `id` IN ('.implode(',', $ids_lignes_factures).');';
			$queryD = $this->db->prepare($query_del);
			$queryD->execute();
			Outils::saveLog($query_del);
		}

		return true;

	} // FIN méthode


	// Supprime une facture
	public function supprFacture(Facture $facture) {

		$query_del = 'DELETE FROM `pe_facture_ligne_bl` WHERE `id_ligne_facture` IN (SELECT `id` FROM `pe_facture_lignes` WHERE `id_facture` = ' . $facture->getId().');';
		$query_del.= 'DELETE FROM `pe_facture_lignes` WHERE `id_facture` = ' . $facture->getId().';';
		$query_del.= 'DELETE FROM `pe_factures` WHERE `id` = ' . $facture->getId().';';
		$query_del.= 'DELETE FROM `pe_bl_facture` WHERE `id_facture` = ' . $facture->getId().';';
		$queryD = $this->db->prepare($query_del);
		if (!$queryD->execute()) { return false; }
		Outils::saveLog($query_del);
		return $this->supprimeFichierFacture($facture);

	} // FIN méthode

	// Supprime le fichier PDF de la facture sur le serveur
	public function supprimeFichierFacture(Facture $facture) {

		$dossier_facture =  $this->getDossierFacturePdf($facture, false);
		$chemin = __CBO_ROOT_PATH__.$dossier_facture.$facture->getFichier();
		if (!file_exists($chemin)) { return true; }

		return unlink($chemin);

	} // FIN méthode

	// Retourne le tarif Interbev par Produit/Expédition
	public function getTarifInterbevLigneFacture($id_pdt, $id_adresse_expedition) {

		$configManager = new ConfigManager($this->db);
		$interbev = $configManager->getConfig('interbev');
		if (!$interbev instanceof Config) { return 0; }
		if (intval($interbev->getValeur()) == 0) { return 0; }

		$query_interbev = ' SELECT IF (pdt.`pdt_gros` = 1,
 				    				(SELECT `valeur` FROM `pe_config` WHERE `clef` = "interbev_gros"),
 				    				(SELECT `valeur` FROM `pe_config` WHERE `clef` = "interbev_autres"))
 								AS tarif_interbev
 								FROM `pe_produits` pdt
 								WHERE pdt.`id` = '.(int)$id_pdt.'
 								  	AND (
 								  		SELECT UPPER(pays.`iso`) 
 								  			FROM `pe_pays` pays
    											JOIN `pe_adresses` adr ON adr.`id_pays` = pays.`id`
   											WHERE adr.`id` = ' . (int)$id_adresse_expedition . '
										) = "FR" ';

		$query = $this->db->prepare($query_interbev);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);

		return $donnees && isset($donnees['tarif_interbev']) ? floatval($donnees['tarif_interbev']) : 0;

	} // FIN méthode

	// Retourne la liste des FactureFrais
	public function getListeFactureFrais($id_facture) {

		$query_liste = "SELECT ff.`id`, ff.`nom`, ff.`type`, ff.`id_taxe`, ff.`valeur`, ff.`id_facture`, IF (t.`id` IS NOT NULL, t.`nom`, '') AS taxe_nom,  IF (t.`id` IS NOT NULL, t.`taux`, '') AS taxe_taux
							FROM `pe_facture_frais` ff
								LEFT JOIN `pe_taxes` t ON t.`id` = ff.`id_taxe`
						WHERE ff.`id_facture` =  ".(int)$id_facture." 
							ORDER BY ff.`nom` DESC";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new FactureFrais($donnee);
		}
		return $liste;

	} // FIN liste des FactureFrais

	// Supprime les frais additionnels de transports web d'une facture mais en filtrant sur le libellé du frais qui a été auto-généré pour ne pas impacter d'autres frais additionnels évenutuelements mis par J&J
	public function supprFactureFraisLivraisonWeb(Facture $facture) {

		$query_del = 'DELETE FROM `pe_facture_frais` WHERE `nom` = "'.$this->libelle_frais_livraison_web.'" AND `id_facture` = ' . $facture->getId();
		$query = $this->db->prepare($query_del);
		if (!$query->execute()) { return false;	}
		Outils::saveLog($query_del);
		return true;

	} // FIN méthode


	// Retourne un FactureFrais
	public function getFactureFrais($id) {

		$query_object = "SELECT ff.`id`, ff.`nom`, ff.`type`, ff.`id_taxe`, ff.`valeur`, ff.`id_facture`, IF (t.`id` IS NOT NULL, t.`nom`, '') AS taxe_nom,  IF (t.`id` IS NOT NULL, t.`taux`, '') AS taxe_taux
							FROM `pe_facture_frais` ff
								LEFT JOIN `pe_taxes` t ON t.`id` = ff.`id_taxe`
						WHERE ff.`id` =  ".(int)$id;


		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new FactureFrais($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get FactureFrais

	// Enregistre & sauvegarde (Méthode Save)
	public function saveFactureFrais(FactureFrais $objet) {

		$table      = 'pe_facture_frais'; // Nom de la table
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

	// Supprime une ligne de frais de la facture
	public function suppprimeFactureFrais(FactureFrais $frais) {

		$query_del = 'DELETE FROM `pe_facture_frais` WHERE `id` = ' . $frais->getId();
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return	$query->execute();

	} // FIN méthode


	// Retounre un array avec le taux en clef et la valeur pour les différentes tva à payer sur une facture
	public function getTvasFacture(Facture $facture) {
		global $base_tva;
		
		$query_liste = 'SELECT `tva` AS taux FROM `pe_facture_lignes` WHERE `tva` != 0 AND `id_facture` = ' . $facture->getId();

		$query = $this->db->prepare($query_liste);
				
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {			
			$taux = isset($donnee['taux']) ? floatval($donnee['taux']) : 0;

			if ($taux == 0) { continue; }

			// pour chaque taux, on récupère le total de tva payé
			//$query_total = 'SELECT SUM(`prix_vente` * `taux_tva`) AS total FROM `pe_marges_factures` WHERE `id_facture` = ' . $facture->getId() . ' AND `taux_tva`*100 = ' . $taux;
			$query_total = 'SELECT SUM((`pu_ht` * IF(`vendu_piece` > 0, `qte`, `poids`)) * `tva` / 100) AS total  
			FROM `pe_facture_lignes` 
			WHERE `id_facture` = ' . $facture->getId() . ' AND `supprime` = 0';

			$query2= $this->db->prepare($query_total);
			$query2->execute();
			$donnees2 = $query2->fetch(PDO::FETCH_ASSOC);
			$montant = $donnees2 && isset($donnees2['total']) ? floatval($donnees2['total']) : 0;
//			var_dump($montant);
			if ($montant == 0) { continue; }

			$liste[(string)$taux] = round($montant,2);
		} // FIN boucle taux						
		return $liste;

	} // FIN méthode

	// Retounre le détail interbev d'un mois donné (date, facture, client, poids montant) en fonction du taux
	public function getInterbevMoisTaux($mois, $annee, $taux) {

		$date_du = $annee.'-'.$mois.'-01';
		$date_au = date("Y-m-t", strtotime($date_du));

		$query_liste = 'SELECT
       						f.id,
							f.date,
							f.num_facture,
							t.nom,
							SUM(fl.poids) AS poids,
							SUM(IF (fl.montant_interbev > 0, fl.montant_interbev, fl.tarif_interbev * fl.poids)) AS montant_interbev
						FROM 
							pe_factures f
							JOIN pe_facture_lignes fl ON fl.id_facture = f.id
							JOIN pe_tiers t ON t.id = f.id_tiers_facturation
						WHERE 
						    f.`date` >= "'.$date_du.'"  AND f.`date` <= "'.$date_au.'"
							AND fl.tarif_interbev = '.floatval($taux).'
						GROUP BY 
							f.num_facture,
							fl.tarif_interbev
						ORDER BY
							fl.tarif_interbev,
							f.num_facture
						';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			if (!isset($donnee['id']) || intval($donnee['id']) == 0) { continue; }
			$liste[] = $donnee;
		}

		return $liste;
	} // FIN méthode

	// Retounre le détail interbev d'un mois donné (date, facture, client, montant) OLD - NPU
	public function getInterbevMois($mois, $annee) {

		$date_du = $annee.'-'.$mois.'-01';
		$date_au = date("Y-m-t", strtotime($date_du));

		$query_liste = 'SELECT f.`id`, f.`id_tiers_livraison`, f.`id_tiers_facturation`, f.`id_tiers_transporteur`, f.`id_adresse_facturation`, f.`id_adresse_livraison`, f.`date`, f.`date_add`, f.`num_cmd`, f.`montant_ht`, f.`date_compta`,  f.`num_facture`, f.`montant_interbev` FROM `pe_factures` f WHERE  f.`supprime` = 0 AND f.`date` >= "'.$date_du.'"  AND f.`date` <= "'.$date_au.'" ORDER BY f.`date` ';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$facture = new Facture($donnee);
			if (!$facture instanceof Facture) {
				continue;
			}
			if ($facture->getMontant_interbev() <= 0) { continue; }
			$liste[] = $facture;
		}

		return $liste;

	} // FIN méthode

	// Retoune le prochain numéro de facture
	public function getNextNumeroFacture($avoir = false, $date = '') {

		// 27/09/2021 -> Nouvelle numérotation

		if ($date != '') {
			$dt = strtotime($date);
			$annee =  date('y', $dt);
			$annee4 =  date('Y', $dt);
			$mois = date('m', $dt);
		} else {
			$annee = date('y');
			$annee4 = date('Y');
			$mois = date('m');
		}

		$numfact = $avoir ? 'AV' : 'FA';	// FActure / AVoir
		$numfact.= $annee; 					// Année sur 2 chiffres
		$numfact.= $mois; 					// Mois  sur 2 chiffrres
											// Incrément du nombre de factures du mois

		
		$query_num = 'SELECT COUNT(*)+1 AS num FROM `pe_factures` WHERE YEAR(`date`) = '.$annee4.' AND MONTH(`date`)  = '.$mois .' AND SUBSTRING(`num_facture`,3,2) = "'.$annee.'" AND SUBSTRING(`num_facture`,5,2) = "'.$mois.'"';

		

		$query = $this->db->prepare($query_num);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		$num = $donnees && isset($donnees['num']) ? intval($donnees['num']) : 1;

		$next_num_facture = $numfact.sprintf('%03u', $num);

		// On vérifie que ce numéro n'existe pas déjà;
		$query_verif = 'SELECT COUNT(*) AS nb FROM `pe_factures` WHERE `num_facture` = "'.$next_num_facture.'"';
		$query2 = $this->db->prepare($query_verif);
		$query2->execute();
		$donnees2 = $query2->fetch(PDO::FETCH_ASSOC);
		$verif = $donnees2 && isset($donnees2['nb']) ? intval($donnees2['nb']) : 0;

		if ($verif == 0) {
			return $next_num_facture;
		}

		// SInon on essaye de trouver le 1er trou dans la numérotation
		$query_trou_dans_la_raquette = 'SELECT t1. id+1 AS missing
											FROM (SELECT RIGHT(num_facture,3) AS id
												FROM pe_factures
													WHERE MONTH(date) = "'.$mois.'" AND YEAR(date) = "20'.$annee.'"
											    ) AS t1
											LEFT JOIN (SELECT RIGHT(num_facture,3) AS id
												FROM pe_factures
													WHERE MONTH(date) = "'.$mois.'" AND YEAR(date) = "20'.$annee.'"
											    ) AS t2 ON t1. id+1 = t2. id
											    
											WHERE t2. id IS NULL
											ORDER BY t1.id
											LIMIT 0,1';

		$query3 = $this->db->prepare($query_trou_dans_la_raquette);
		$query3->execute();
		$donnees3 = $query3->fetch(PDO::FETCH_ASSOC);
		$trou = $donnees3 && isset($donnees3['missing']) ? intval($donnees3['missing']) : 0;
		if ($trou == 0) {
			return $next_num_facture;
		}

		return $numfact.sprintf('%03u', $trou);

	} // FIN méthode

	// Retourne le poids total des factures
	public function getTotalPoidsFactures($ids_factures) {

		$query_total = 'SELECT SUM(`poids`) AS total FROM `pe_facture_lignes` WHERE `supprime` = 0 AND `id_facture` IN ('.implode(',', $ids_factures).')';

		$query = $this->db->prepare($query_total);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		return $donnees && isset($donnees['total']) ? floatval($donnees['total']) : 0;

	} // FIn méthode

	// Retourne le nb colis total des factures
	public function getTotalColisFactures($ids_factures) {

		$query_total = 'SELECT SUM(`nb_colis`) AS total FROM `pe_facture_lignes` WHERE `supprime` = 0 AND `id_facture` IN ('.implode(',', $ids_factures).')';

		$query = $this->db->prepare($query_total);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		return $donnees && isset($donnees['total']) ? floatval($donnees['total']) : 0;

	} // FIn méthode

	// Retourne la qté totals des factures
	public function getTotalQteFactures($ids_factures) {

		$query_total = 'SELECT SUM(`qte`) AS total FROM `pe_facture_lignes` WHERE `supprime` = 0 AND `id_facture` IN ('.implode(',', $ids_factures).')';

		$query = $this->db->prepare($query_total);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		return $donnees && isset($donnees['total']) ? floatval($donnees['total']) : 0;

	} // FIn méthod


	// Retourne le total des factures
	public function getTotalFactures($ids_factures) {

		$query_total = 'SELECT SUM(`montant_ht`) AS total FROM `pe_factures` WHERE `montant_ht` > 0 AND `id` IN ('.implode(',', $ids_factures).')';

		$query = $this->db->prepare($query_total);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		return $donnees && isset($donnees['total']) ? floatval($donnees['total']) : 0;

	} // FIN méthode

	// Retourne la qte totale des lignes de factures
	public function getTotalQteLignes($ids_lignes_factures) {

		$query_total = 'SELECT SUM(`qte`) AS total FROM `pe_facture_lignes` WHERE `supprime` = 0 AND `id` IN ('.implode(',', $ids_lignes_factures).')';

		$query = $this->db->prepare($query_total);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		return $donnees && isset($donnees['total']) ? floatval($donnees['total']) : 0;

	} // FIN méthode

	// Retourne le nb colis total des lignes de factures
	public function getTotalColisLignes($ids_lignes_factures) {

		$query_total = 'SELECT SUM(`nb_colis`) AS total FROM `pe_facture_lignes` WHERE `supprime` = 0 AND `id` IN ('.implode(',', $ids_lignes_factures).')';

		$query = $this->db->prepare($query_total);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		return $donnees && isset($donnees['total']) ? floatval($donnees['total']) : 0;

	} // FIN méthode

	// Retourne le poids total des lignes de factures
	public function getTotalPoidsLignes($ids_lignes_factures) {

		$query_total = 'SELECT SUM(`poids`) AS total FROM `pe_facture_lignes` WHERE `supprime` = 0 AND `id` IN ('.implode(',', $ids_lignes_factures).')';

		$query = $this->db->prepare($query_total);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		return $donnees && isset($donnees['total']) ? floatval($donnees['total']) : 0;

	} // FIN méthode

	// Retourne le total des lignes de factures
	public function getTotalLignes($ids_lignes_factures) {

		$query_total = 'SELECT SUM(`prix_vente`) AS total FROM `pe_marges_factures` WHERE `pu_ht` > 0 AND `id_ligne_facture` IN ('.implode(',', $ids_lignes_factures).')';

		$query = $this->db->prepare($query_total);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		return $donnees && isset($donnees['total']) ? floatval($donnees['total']) : 0;

	} // FIN méthode


	public function getMontantFraisFacture(Facture $facture) {

		// On rajoute les frais additionnels liés à la facture
		$query_frais = 'SELECT
						(SELECT IFNULL(SUM(`valeur`),0) FROM `pe_facture_frais` WHERE `type` = 0 AND `id_facture` = ' . $facture->getId(). ')
						+
						(SELECT IFNULL(SUM((`valeur`/100) * '.$facture->getMontant_ht().'),0) FROM `pe_facture_frais` WHERE `type` = 1 AND `id_facture` = ' . $facture->getId(). ')
						AS frais' ;

		$query4 = $this->db->prepare($query_frais);
		$query4->execute();
		$donnees4 = $query4->fetch(PDO::FETCH_ASSOC);
		return $donnees4 && isset($donnees4['frais']) ? floatval($donnees4['frais']) : 0;
	}

	public function killSupprimees() {

		global $utilisateur;
		if (!$utilisateur->isDev()) { return false; }

		$query_ids = 'SELECT `id`, `num_facture` FROM `pe_factures` WHERE `supprime` = 1';
		$query = $this->db->prepare($query_ids);
		$query->execute();
		$ids = [];
		$numsFactures = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) { $ids[] = (int)$donnee['id']; $numsFactures[] = trim($donnee['num_facture']); }
		if (empty($ids)) { return true; }

		$query_ids_lignes = 'SELECT `id` FROM `pe_facture_lignes` WHERE `id_facture` IN ('.implode(',', $ids).');';
		$query2 = $this->db->prepare($query_ids_lignes);
		$query2->execute();
		$idsLignes = [];
		foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) { $idsLignes[] = (int)$donnee2['id']; }

		$query_del = 'DELETE FROM `pe_factures` WHERE `id` IN ('.implode(',', $ids).');';
		$query_del.= 'DELETE FROM `pe_bl_facture` WHERE `id_facture` IN ('.implode(',', $ids).');';
		$query_del.= 'DELETE FROM `pe_facture_frais` WHERE `id_facture` IN ('.implode(',', $ids).');';
		$query_del.= 'DELETE FROM `pe_facture_reglements` WHERE `id_facture` IN ('.implode(',', $ids).');';
		$query_del.= 'DELETE FROM `pe_facture_lignes` WHERE `id_facture` IN ('.implode(',', $ids).');';
		$query_del.= !empty($idsLignes) ? 'DELETE FROM `pe_facture_ligne_bl` WHERE `id_ligne_facture` IN ('.implode(',', $idsLignes).');' : '';

		$query3 = $this->db->prepare($query_del);
		if (!$query3->execute()) { return false; }
		Outils::saveLog($query_del);
		if (empty($numsFactures)) { return true; }

		foreach ($numsFactures as $numFacture) {
			$dossier_facture =  $this->getDossierFacturePdfFromNum($numFacture, false);
			$fichier = __CBO_ROOT_PATH__.$dossier_facture.$numFacture.'.pdf';
			if (file_exists($fichier)) { unlink($fichier); }
		}

		return true;

	} // FIN méthode

	public function recalculeMontantHtFacture(Facture $facture) {
	

		$query_total = 'SELECT SUM(ROUND(`total_ht`,2)) AS total FROM `pe_facture_lignes` WHERE `id_facture` = '.$facture->getId();
		$query = $this->db->prepare($query_total);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		$total = isset($donnees['total']) ? floatval($donnees['total']) : '0';
		$query_upd = 'UPDATE `pe_factures` SET `montant_ht` = '.$total.' WHERE `id` = ' . $facture->getId() ;
		$query2 = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query2->execute();

	} // FIN méthode

	public function checkTvaTiers(Facture $facture) {

		// Si le client est en france, on force l'assujeti TVA
		 $query_id_pays = 'SELECT DISTINCT f.`id_tiers_facturation`,         
                        IF (a1.`id_pays` IS NOT NULL, a1.`id_pays`, IFNULL(a2.`id_pays`, 0)) AS id_pays
                    FROM `pe_factures` f
                        LEFT JOIN `pe_adresses` a1 ON a1.`id_tiers` = f.`id_tiers_facturation` AND a1.`type` = 0
                        LEFT JOIN `pe_adresses` a2 ON a2.`id_tiers` = f.`id_tiers_facturation` AND a2.`type` = 1
                    WHERE f.`id` = '.$facture->getId();

		$query2 = $this->db->prepare($query_id_pays);
		$query2->execute();

		$donnees2 = $query2->fetch(PDO::FETCH_ASSOC);
		$id_pays = isset($donnees2['id_pays']) ? intval($donnees2['id_pays']) : 0;
		$id_tiers = isset($donnees2['id_tiers_facturation']) ? intval($donnees2['id_tiers_facturation']) : 0;

		if ($id_pays == 1 && $id_tiers > 0) {
			$query_upd = 'UPDATE `pe_tiers` SET `tva` = 1 WHERE `id` = '.$id_tiers;
			$query3 = $this->db->prepare($query_upd);
			Outils::saveLog($query_upd);
			$query3->execute();
		}
		return true;
	}

	// Retourne un array des id et numéro de factures correspondant à un BL
	public function getNumFacturesByBl($id_bl) {

		$query_liste = 'SELECT f.`id`, f.`num_facture` FROM `pe_factures` f JOIN `pe_bl_facture` bf ON bf.`id_facture` = f.`id` AND f.`supprime` = 0 AND bf.`id_bl` = ' . (int)$id_bl;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnees) {
			$liste[(int)$donnees['id']] = $donnees['num_facture'];
		}
		return $liste;

	} // FIN méthode


	public function getDossierFacturePdf(Facture $facture, $crer_dossiers = true) {
		if ($facture->getDate() >= '2021-09-28') { // Date du changement du système de numérotation
			return $this->getDossierFacturePdfFromFacture($facture, $crer_dossiers);
		} else {
			return $this->getDossierFacturePdfFromNum($facture->getNum_facture(), $crer_dossiers);
		}
	}

	public function getDossierFacturePdfFromFacture(Facture $facture,  $crer_dossiers = true) {
		$chemin = '/gescom/facture/';
		if (!file_exists( __CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir( __CBO_ROOT_PATH__ . $chemin);
		}
		$annee = substr($facture->getNum_facture(), 2, 2);
		$chemin.= $annee.'/';
		if (!file_exists( __CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir( __CBO_ROOT_PATH__ . $chemin);
		}
		$mois = substr($facture->getNum_facture(), 4, 2);
		$chemin.= $mois.'/';
		if (!file_exists( __CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir( __CBO_ROOT_PATH__ . $chemin);
		}
		return $chemin;

	} // Fin méthode

	public function getDossierFacturePdfFromNum($num_facture, $crer_dossiers = true) {
		$chemin = '/gescom/facture/';
		if (!file_exists( __CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir( __CBO_ROOT_PATH__ . $chemin);
		}
		$annee = substr($num_facture,2,2);
		$chemin.= $annee.'/';
		if (!file_exists( __CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir( __CBO_ROOT_PATH__ . $chemin);
		}
		$jour = substr($num_facture,4,3);

		$date = DateTime::createFromFormat('z y', strval($jour) . ' ' . strval($annee));
		$mois = $date->format('m');
		$chemin.= $mois.'/';
		if (!file_exists( __CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir( __CBO_ROOT_PATH__ . $chemin);
		}

		return $chemin;
	} // FIN méthode

	// Retourne si le pays de facturation est considéré comme export
	public function isPaysExport(Facture $facture) {

		$id_adresse = $facture->getId_adresse_facturation() > 0 ? $facture->getId_adresse_facturation() : $facture->getId_adresse_livraison();
		if (intval($id_adresse) == 0) { return false; }
		$query_export = 'SELECT `export` FROM `pe_pays` WHERE `id` = (SELECT `id_pays` FROM `pe_adresses` WHERE `id` = '.$id_adresse.')';
		$query = $this->db->prepare($query_export);
		$query->execute();
		$donnes = $query->fetch(PDO::FETCH_ASSOC);
		if (!$donnes || !isset($donnes['export'])) { return false; }
		return intval($donnes['export']) == 1;

	} // FIN méthode

	public function persistancePrixAchat(FactureLigne $ligne, $id_tiers_profil_export) {

		$query_pa = 'SELECT `pa_ht` FROM `pe_facture_lignes` WHERE `id` = ' . $ligne->getId();
		$query = $this->db->prepare($query_pa);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		if (!$donnees || !isset($donnees['pa_ht'])) { return false; }

		$pa = floatval($donnees['pa_ht']);
		if ($pa > 0) { return true; }

		$query_frs = 'SELECT IFNULL(`prix`, 0) AS prix FROM `pe_tarif_fournisseur` WHERE `id_produit` = ' . $ligne->getId_produit(). ' AND `id_tiers` = '.$id_tiers_profil_export;
		$query1 = $this->db->prepare($query_frs);
		$query1->execute();
		$donnees2 = $query1->fetch(PDO::FETCH_ASSOC);
		if (!$donnees2 || !isset($donnees2['prix'])) { return true; }
		$pafrs = floatval($donnees2['prix']);
		if ($pafrs == 0) { return true; }

		$query_up = 'UPDATE `pe_facture_lignes` SET `pa_ht` = ' . $pafrs . ' WHERE `id` = ' . $ligne->getId();
		$query2 = $this->db->prepare($query_up);
		$query2->execute();
		Outils::saveLog($query_up);
		return true;
	}

	// Id produit identiques sur les lignes d'un meme bl
	public function getFactureLignesMemeProduitFromLigne(FactureLigne $ligne) {

		$query_regroupes = 'SELECT `id` FROM `pe_facture_lignes` WHERE `supprime` = 0 AND `id_facture` = (SELECT `id_facture` FROM `pe_facture_lignes` WHERE `id` = '.$ligne->getId().') AND `id` !=  '.$ligne->getId().' 
    AND `id_produit` = ' . $ligne->getId_produit();

		$query = $this->db->prepare($query_regroupes);
		$query->execute();
		$lignesRegroupees = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$lignesRegroupees[] = $this->getFactureLigne((int)$donnee['id']);
		}
		return $lignesRegroupees;

	} // FIN méthode

	public function razDateEnvoiFacture(Facture $facture) {
		$query_upd = 'UPDATE `pe_factures` SET `date_envoi` = NULL WHERE `id` = ' . $facture->getId();
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();

	} // FIN méthode

	// Retourne si une facture est effectée au client web
	public function isFactureWeb(Facture $facture) {

		$tiersManager = new TiersManager($this->db);
		$id_web = $tiersManager->getId_client_web();

		return $facture->getId_tiers_livraison() == $id_web || $facture->getId_tiers_facturation() == $id_web;

	} // FIN méthode

	public function calculeReductionsCommandesWeb(Facture $facture) { // NPU, récupéré depuis prestashop
		return $facture;
	} // FIN méthode

	public function getAvoirsPossiblesFacture(Facture $facture) {

		$query_liste = 'SELECT 
							a.id as id_avoir,
							a.num_facture as num_avoir,
							a.total_ttc,
							IF (f.id = '.$facture->getId().', 1 ,0) AS selected
						FROM `pe_facture_lignes` fl
							JOIN pe_factures a ON a.id = fl.id_facture
							LEFT JOIN pe_factures f ON f.id = fl.id_facture_avoir
						WHERE a.supprime = 0 AND fl.supprime = 0 AND MONTH(a.date) = '.$facture->getMois().' AND YEAR(a.date) = '.$facture->getAnnee().'
							AND (a.id_tiers_livraison = '.$facture->getId_tiers_livraison().' 
								OR a.id_tiers_facturation = '.$facture->getId_tiers_livraison().'
								OR a.id_tiers_livraison = '.$facture->getId_tiers_facturation().' 
								OR a.id_tiers_facturation = '.$facture->getId_tiers_facturation().'
								)
							AND a.montant_ht < 0
						GROUP BY fl.id_facture';

		// Modif PPL : modification du filtrage des dates
		// $query_liste = 'SELECT 
		// 					a.id as id_avoir,
		// 					a.num_facture as num_avoir,
		// 					a.total_ttc,
		// 					IF (f.id = '.$facture->getId().', 1 ,0) AS selected
		// 				FROM `pe_facture_lignes` fl
		// 					JOIN pe_factures a ON a.id = fl.id_facture
		// 					LEFT JOIN pe_factures f ON f.id = fl.id_facture_avoir
		// 				WHERE 
		// 					a.supprime = 0 
		// 					AND fl.supprime = 0 
		// 					AND (a.id_tiers_livraison = '.$facture->getId_tiers_livraison().' 
		// 						OR a.id_tiers_facturation = '.$facture->getId_tiers_livraison().'
		// 						OR a.id_tiers_livraison = '.$facture->getId_tiers_facturation().' 
		// 						OR a.id_tiers_facturation = '.$facture->getId_tiers_facturation().'
		// 						)
		// 					AND a.montant_ht < 0
		// 				GROUP BY fl.id_facture';

		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = $donnee;
		}

		return $liste;

	} // FIN méthode

	// Rend persistant le montant Interbev à la ligne
	public function saveMontantInterbevLigne(FactureLigne $ligne) {

		$tarif = round((floatval($ligne->getTarif_interbev()) * floatval($ligne->getPoids())),2);;
		$ligne->setMontant_interbev($tarif);
		$this->saveFactureLigne($ligne);
		return $ligne;

	} // FIN méthode

	// Recalcul du montant interbev total depuis les lignes
	public function saveMontantInterbevFactureFromLignes(Facture $facture) {

		$total = 0;

		foreach ($facture->getLignes() as $ligne) {
			$total+=$ligne->getMontant_interbev();
		}

		$facture->setMontant_interbev($total);
		$this->saveFacture($facture);
		return $facture;

	} // FIN méthode

} // FIN classe