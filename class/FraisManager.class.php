<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Frais
Généré par CBO FrameWork le 27/02/2020 à 16:21:17
------------------------------------------------------*/
class FraisManager {

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
	public function setNb_results($nb) {
		$this->nb_results = $nb;
	}

	/* ----------------- METHODES ----------------- */


	// Retourne la liste des Frais
	public function getListeFrais($params = []) {

		$envoye = isset($params['envoye']) ? intval($params['envoye']) : 1;
		$compos = isset($params['compos']) ? boolval($params['compos']) : false;
		$ids_compos 	= isset($params['ids']) 	? $params['ids'] : '';
		$id_palette = isset($params['id_palette']) 	? intval($params['id_palette']) : 0;
		$ids_palettes = isset($params['palettes']) 	? str_replace('|', ',', $params['palettes']) : '';
		$ids_clients = isset($params['client']) 	? $params['client'] : '';
		$ids_produits = isset($params['produits']) 	? str_replace('|', ',', $params['produits']) : '';
		$exclure_bl = isset($params['exclure_bl']) ? boolval($params['exclure_bl']) : true;

		$query_liste = 'SELECT fc.`id`, pc.`id` AS id_compo, fc.`id_lot`, fc.`quantieme`, fc.`dlc`, fc.`envoye`, pc.`id_produit`, pc.`poids`, pc.`id_palette`, fc.`date_scan`,
       							IF(l.`numlot` IS NOT NULL, CONCAT(l.`numlot`, fc.`quantieme`), IFNULL(ln.`num_bl`,"")) AS numlot, p.`numero` as num_palette, t.`nom` as nom_client, fc.`id_lot_negoce`,
       							pdts.`code` AS code_produit, ptrad.`nom` AS nom_produit
							FROM `pe_frais` fc
							    LEFT JOIN `pe_palette_composition` pc ON pc.`id_frais` = fc.`id`
							    LEFT JOIN `pe_palettes` p ON p.`id` = pc.`id_palette`
							    LEFT JOIN `pe_tiers` t ON t.`id` = pc.`id_client`
								LEFT JOIN `pe_lots` l ON l.`id` = fc.`id_lot`
								LEFT JOIN `pe_lots_negoce` ln ON ln.`id` = fc.`id_lot_negoce`
								LEFT JOIN `pe_produits` pdts ON pdts.`id` = pc.`id_produit`
								LEFT JOIN `pe_produit_trad` ptrad ON ptrad.`id_produit` = pc.`id_produit` AND ptrad.`id_langue` = 1 ';

		$query_liste.= $exclure_bl ? ' LEFT JOIN `pe_bl_lignes` bll ON bll.`id_compo` = pc.`id` AND bll.`supprime` = 0 ' : '';

		$query_liste.= '			WHERE pc.`supprime` = 0 AND pc.`archive` = 0 AND pc.`id_lot_hors_stock` = 0 ';

		$query_liste.= $exclure_bl ? ' AND bll.`id` IS NULL ' : '';
		$query_liste.= $ids_palettes != '' ? ' AND pc.`id_palette` IN (' . $ids_palettes . ') ' : '';
		$query_liste.= $ids_clients != '' ? ' AND pc.`id_client` IN (' . $ids_clients. ') ' : '';
		$query_liste.= $ids_produits != '' ? ' AND pc.`id_produit` IN (' . $ids_produits. ') ' : '';
		$query_liste.= $envoye > -1 ? ' AND fc.`envoye` = ' . $envoye : '';
		$query_liste.= $ids_compos 	  != '' ? '  AND pc.`id` IN (' . $ids_compos . ') ' : '';
		$query_liste.= $id_palette 	> 0 ? '  AND pc.`id_palette` = ' . $id_palette . ' ' : '';
		$query_liste.= " ORDER BY pc.`id_palette`, fc.`id` DESC ";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		$palettesManager = new PalettesManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$frais = new Frais($donnee);

			if ($compos) {

				$compo = $palettesManager->getComposition($frais->getId_compo());
				if (!$compo instanceof PaletteComposition) { $compo = new PaletteComposition([]); }
				$frais->setCompo($compo);
			}

			$liste[] = $frais;
		}
		return $liste;

	} // FIN liste des Frais

	// Retourne un Frais
	public function getFrais($id) {

		$query_object = 'SELECT fc.`id`, pc.`id`, fc.`id_lot`, fc.`quantieme`, fc.`dlc`, fc.`envoye`, pc.`id_produit`, pc.`poids`, pc.`id_palette`, fc.`date_scan`, fc.`id_lot_negoce`,
       							IF(l.`numlot` IS NOT NULL, CONCAT(l.`numlot`, fc.`quantieme`), "") AS numlot
							FROM `pe_frais` fc
							    LEFT JOIN `pe_palette_composition` pc ON pc.`id_frais` = fc.`id`
								LEFT JOIN `pe_lots` l ON l.`id` = fc.`id_lot` WHERE fc.`id` = ' . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new Frais($donnee[0]) : false;
		} else {
			return false;
		}
	} // FIN get Frais

	// Retourne un Frais
	public function getFraisFromIdCompo($id_compo) {

		$query_object = 'SELECT fc.`id`, pc.`id`, fc.`id_lot`, fc.`quantieme`, fc.`dlc`, fc.`envoye`, pc.`id_produit`, pc.`poids`, pc.`id_palette`, fc.`date_scan`, fc.`id_lot_negoce`,
       							IF(l.`numlot` IS NOT NULL, CONCAT(l.`numlot`, fc.`quantieme`), "") AS numlot
							FROM `pe_frais` fc
							    LEFT JOIN `pe_palette_composition` pc ON pc.`id_frais` = fc.`id`
								LEFT JOIN `pe_lots` l ON l.`id` = fc.`id_lot` WHERE pc.`id` = ' . (int)$id_compo;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new Frais($donnee[0]) : false;
		} else {
			return false;
		}
	} // FIN get Frais


	// Enregistre & sauvegarde (Méthode Save)
	public function saveFrais(Frais $objet) {

		$table      = 'pe_frais'; // Nom de la table
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


	// On retourne un objet Frais si on le retrouve par ses détails de compo, pour regrouper les données d'une compo déja existante
	public function getFraisDeja($id_pdt, $id_palette, $dlc, $poids, $id_lot, $quantieme) {

		$query_object = 'SELECT fc.`id`, pc.`id`, fc.`id_lot`, fc.`quantieme`, fc.`dlc`, fc.`envoye`, pc.`id_produit`, pc.`poids`, pc.`id_palette`, fc.`date_scan`, fc.`id_lot_negoce`,
       							IF(l.`numlot` IS NOT NULL, CONCAT(l.`numlot`, fc.`quantieme`), "") AS numlot
							FROM `pe_frais` fc
							    LEFT JOIN `pe_palette_composition` pc ON pc.`id_frais` = fc.`id`
								LEFT JOIN `pe_lots` l ON l.`id` = fc.`id_lot`
							WHERE pc.`supprime` = 0 
							  AND pc.`scan_frais` > 0 
							  AND pc.`id_produit` = '.$id_pdt.'
							  AND pc.`id_palette` = '.$id_palette.'
							  AND fc.`dlc` = "'.$dlc.'"
							  AND pc.`poids` = '.$poids.'
							  AND fc.`id_lot` = '.$id_lot.'
							  AND fc.`quantieme` = "'.$quantieme.'" ';

		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new Frais($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN méthode


	// Supprime une frais (depuis scan)
	public function supprFrais($id_frais_compo) {

		$logManager = new LogManager($this->db);

		// On vérifie que ce frais n'est pas déjà dans un BL !
		$query_verif = 'SELECT  COUNT(*) AS nb
							FROM pe_bl_lignes bll
							JOIN pe_palette_composition pc ON pc.id = bll.id_compo
							WHERE pc.id_frais = ' . $id_frais_compo;

		$query = $this->db->prepare($query_verif);
		$query->execute();
		$donnee = $query->fetch();
		$nb = isset($donnee['nb']) ? intval($donnee['nb']) : 0;
		if ($nb > 0) {
			$log = new Log([]);
			$log->setLog_type('warning');
			$log->setLog_texte('Suppression impossible du produit frais #'.$id_frais_compo .' et de la compo associée car déjà dans un BL !');
			$logManager->saveLog($log);
			return true;
		}

		$log = new Log([]);
		$log->setLog_type('warning');
		$log->setLog_texte('Suppression du produit frais #'.$id_frais_compo .' et de la compo associée');
		$logManager->saveLog($log);

		$query_del1 = 'DELETE FROM `pe_frais` WHERE `id` = ' . (int)$id_frais_compo .';';
		$query_del2= 'DELETE FROM `pe_palette_composition` WHERE `id_frais` = ' . (int)$id_frais_compo;
		$query_del = $query_del1.$query_del2;
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del1);
		Outils::saveLog($query_del2);
		return $query->execute();

	} // FIN méthode


	// Retourne si un produit déjà en scan correspond à ce qui est scanné
	public function isDoublonFraisScan(Produit $produit, Lot $lot, $quantieme, $poids, $dlc) {

		$query_verif = 'SELECT COUNT(fc.`id`) AS nb FROM `pe_frais` fc
							    LEFT JOIN `pe_palette_composition` pc ON pc.`id_frais` = fc.`id`
							WHERE fc.`envoye` = 0 
							AND fc.`id_lot` =  ' . $lot->getId() . '
							AND fc.`quantieme` = "'.$quantieme.'"
							AND fc.`dlc` = "'.$dlc.'"
							AND pc.`id_produit` = ' . $produit->getId() . '
							AND pc.`poids` = ' . $poids;

		$query = $this->db->prepare($query_verif);
		$query->execute();
		$donnee = $query->fetch();
		if (!$donnee || !isset($donnee['nb'])) { return false; }
		return intval($donnee['nb']) > 0;

	} // FIN méthode

	// Retourne si un produit déjà en scan correspond à ce qui est scanné et enregistré il y a moins d'une seconde
	public function isDoublonFraisScanTime(Produit $produit, Lot $lot, $quantieme, $poids, $dlc) {

		$query_verif = 'SELECT COUNT(fc.`id`) AS nb FROM `pe_frais` fc
							    LEFT JOIN `pe_palette_composition` pc ON pc.`id_frais` = fc.`id`
							WHERE fc.`envoye` = 0 
							AND fc.`date_scan` >= DATE_SUB(NOW(), INTERVAL 1 SECOND)
							AND fc.`id_lot` =  ' . $lot->getId() . '
							AND fc.`quantieme` = "'.$quantieme.'"
							AND fc.`dlc` = "'.$dlc.'"
							AND pc.`id_produit` = ' . $produit->getId() . '
							AND pc.`poids` = ' . $poids;

		$query = $this->db->prepare($query_verif);
		$query->execute();
		$donnee = $query->fetch();
		if (!$donnee || !isset($donnee['nb'])) { return false; }
		return intval($donnee['nb']) > 0;

	} // FIN méthode


	public function dechargeScans() {

		$query_upd = 'UPDATE `pe_frais` SET `envoye` = 1 WHERE `envoye` = 0;';
		$query_upd.= 'UPDATE `pe_palettes` SET `scan_frais` = 2 WHERE `scan_frais` = 1;';
		$query_upd.= 'UPDATE `pe_config` SET `valeur` = "1" WHERE `clef` = "dechargescan";';
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();

	} // FIN méthode

	public function checkDechargeScan() {

		$query_check = 'SELECT `valeur` FROM `pe_config` WHERE `clef` = "dechargescan"';

		$query = $this->db->prepare($query_check);
		$query->execute();
		$donnee = $query->fetch();
		$valeur = $donnee && isset($donnee['valeur']) ?  intval($donnee['valeur']) : 0;
		if ($valeur == 0) { return false; }

		$query_upd = 'UPDATE `pe_config` SET `valeur` = "0" WHERE `clef` = "dechargescan";';
		$query = $this->db->prepare($query_upd);
		$query->execute();
		Outils::saveLog($query_upd);
		return true;

	} // FIN méthode

} // FIN classe