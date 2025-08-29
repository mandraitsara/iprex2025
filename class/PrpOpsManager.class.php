<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet PrpOp
Généré par CBO FrameWork le 06/10/2020 à 15:25:12
------------------------------------------------------*/
class PrpOpsManager {

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

	// Retourne la liste des PrpOp
	public function getListePrpOps($params = []) {

		// Type de retours contextuels
		$ajd = isset($params['ajd']) ? boolval($params['ajd']) : false;
		$saisis = isset($params['saisis']) ? boolval($params['saisis']) : true; // Par défaut on affiche que ceux qui ont été terminé d'être saisis par l'opérateur

		// Pagination
		$start = isset($params['start'])          ? intval($params['start'])          : 0;
		$nb    = isset($params['nb_result_page']) ? intval($params['nb_result_page']) : 10000000;

		// Filtres
		$date_du = isset($params['date_du']) ? $params['date_du'] : '';
		$date_au = isset($params['date_au']) ? $params['date_au'] : '';
		$id_trans = isset($params['id_trans']) ? $params['id_trans'] : 0;
		$id_client = isset($params['id_client']) ? $params['id_client'] : 0;
		$validation = isset($params['validation']) ? $params['validation'] : 0;
		$num_bl = isset($params['num_bl']) ? $params['num_bl'] : '';

		// SI on a filtré sur un client précis, on récupère d'abord la liste des BLS de ce client pour avoir les PRP correspondants en IN
		if ((int)$id_client > 0) {

			$query_in = 'SELECT DISTINCT `id_prp_op` FROM `pe_prp_bls` WHERE `id_bl` IN (SELECT DISTINCT `id` FROM `pe_bl` WHERE `supprime` = 0 AND `id_tiers_livraison` = ' . (int)$id_client.')';
			$query0 = $this->db->prepare($query_in);
			$query0->execute();
			$ids_prps_clt = [];
			foreach ($query0->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
				$ids_prps_clt[] = (int)$donnee['id_prp_op'];
			}

		} // FIN test filtre client

		// SI on a filtré sur un numéro de BL, on récupère d'abord la liste des BLS qui matchent pour avoir els PRP correspondants en IN
		if ($num_bl != '') {

			$query_in = 'SELECT DISTINCT `id_prp_op` FROM `pe_prp_bls` WHERE `id_bl` IN (
    						SELECT DISTINCT `id` FROM `pe_bl` WHERE `supprime` = 0 AND num_bl LIKE "%'.$num_bl.'%")';

			$query0 = $this->db->prepare($query_in);
			$query0->execute();
			$ids_prps_numbl = [];
			foreach ($query0->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
				$ids_prps_numbl[] = (int)$donnee['id_prp_op'];
			}


		} // FIN test filtre num bl

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS p.`id`, p.`date`, p.`cmd_conforme`, p.`t_surface`, p.`t_surface_conforme`, p.`t_camion`,
                           p.`t_camion_conforme`, p.`emballage_conforme`, p.`id_user`, p.`id_validateur`, p.`date_visa`, p.`date_add`, p.`id_transporteur`,
                           p.`palettes_poids`, p.`palettes_recues`, p.`palettes_rendues`, p.`crochets_recus`, p.`crochets_rendus`,
                           IF (t3.`nom` IS NOT NULL, t3.`nom`, "")AS nom_transporteur,
                           IF(u.`nom` IS NOT NULL, CONCAT(CONCAT(UCASE(LEFT(u.`prenom`, 1)),SUBSTRING(u.`prenom`, 2)), " ", UPPER(u.`nom`)), "") AS nom_user,
                           IF(v.`nom` IS NOT NULL, CONCAT(CONCAT(UCASE(LEFT(v.`prenom`, 1)),SUBSTRING(v.`prenom`, 2)), " ", UPPER(v.`nom`)), "") AS nom_validateur
							FROM `pe_prp_op` p
								LEFT JOIN `pe_tiers` t3 ON t3.`id` = p.`id_transporteur`
								LEFT JOIN `pe_users` u ON u.`id` = p.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` = p.`id_validateur`
								WHERE 1 ';

		$query_liste.= $ajd ? ' AND DATE(p.`date_add`) = CURDATE() ' : '';
		$query_liste.= $saisis ? ' AND p.`date_add` IS NOT NULL AND p.`date_add` != "0000-00-00 00:00:00" AND p.`id_user` > 0 ' : '';

		$query_liste.= $date_du != ' '? ' AND p.`date` >= "'.$date_du.'" ' : '';
		$query_liste.= $date_au != '' ? ' AND p.`date` <= "'.$date_au.'" ' : '';
		$query_liste.= $id_trans > 0  ? ' AND p.`id_transporteur` = '.$id_trans : '';
		$query_liste.= $id_client > 0 && isset($ids_prps_clt) ? ' AND p.`id` IN ('.implode(',', $ids_prps_clt).') ' : '';
		$query_liste.= $num_bl != '' && isset($ids_prps_numbl) ? ' AND p.`id` IN ('.implode(',', $ids_prps_numbl).') ' : '';
		$query_liste.= $validation == 1 ? ' AND p.`id_validateur` > 0 AND p.`date_visa` IS NOT NULL ' : '';
		$query_liste.= $validation == -1 ? ' AND (p.`id_validateur` = 0 OR p.`date_visa` IS NULL) ' : '';

		$query_liste.= " ORDER BY p.`id` DESC ";  $query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];


		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new PrpOp($donnee);
		}

		if (empty($liste)) { return $liste; }

		$blsManager = new BlManager($this->db);
		$tiersManager = new TiersManager($this->db);

		foreach ($liste as $prp) {
			$bls = [];
			$query_bls_liste = 'SELECT `id_bl` FROM `pe_prp_bls` WHERE `id_prp_op` = ' . $prp->getId();
			$query2 = $this->db->prepare($query_bls_liste);
			$query2->execute();
			foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
				 $bl = $blsManager->getBl($donnee['id_bl'], false, true, false);
				 if (!$bl instanceof Bl) { continue; }
				 if ((int)$prp->getId_transporteur() == 0) {
					$prp->setId_transporteur($bl->getId_tiers_transporteur());
				    $t3 =$tiersManager->getTiers($bl->getId_tiers_transporteur());
				    $nomT3 = $t3 instanceof Tiers ? $t3->getNom() : '';
					$prp->setNom_transporteur($nomT3);
				 }
				$t2 =$tiersManager->getTiers($bl->getId_tiers_livraison());
				$nomT2 = $t2 instanceof Tiers ? $t2->getNom() : '';
				$bl->setNom_client($nomT2);

				$bls[] = $bl;
			} // FIN liste BLS
			$prp->setBls($bls);

		} // FIN liste PRP

		return $liste;

	} // FIN liste des PrpOp

	// Retourne un PrpOp
	public function getPrpOp($id) {

		$query_object = 'SELECT p.`id`, p.`date`, p.`cmd_conforme`, p.`t_surface`, p.`t_surface_conforme`, p.`t_camion`, p.`t_camion_conforme`, p.`emballage_conforme`, p.`id_user`, p.`id_validateur`, p.`date_visa`, p.`date_add`, p.`id_transporteur`, p.`palettes_poids`, p.`palettes_recues`, p.`palettes_rendues`, p.`crochets_recus`, p.`crochets_rendus`,
                           IF (t3.`nom` IS NOT NULL, t3.`nom`, "")AS nom_transporteur,
                           IF(u.`nom` IS NOT NULL, CONCAT(CONCAT(UCASE(LEFT(u.`prenom`, 1)),SUBSTRING(u.`prenom`, 2)), " ", UPPER(u.`nom`)), "") AS nom_user,
                           IF(v.`nom` IS NOT NULL, CONCAT(CONCAT(UCASE(LEFT(v.`prenom`, 1)),SUBSTRING(v.`prenom`, 2)), " ", UPPER(v.`nom`)), "") AS nom_validateur
       				
                	FROM `pe_prp_op` p
                	    LEFT JOIN `pe_tiers` t3 ON t3.`id` = p.`id_transporteur`
						LEFT JOIN `pe_users` u ON u.`id` = p.`id_user`
						LEFT JOIN `pe_users` v ON v.`id` = p.`id_validateur`
					WHERE p.`id` = '. (int)$id;
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		$prp = $donnee && isset($donnee) ? new PrpOp($donnee) : false;
		if (!$prp instanceof PrpOp) { return false; }

		$bls = [];

		$blsManager = new BlManager($this->db);
		$tiersManager = new TiersManager($this->db);

		$query_bls_liste = 'SELECT `id_bl` FROM `pe_prp_bls` WHERE `id_prp_op` = ' . $prp->getId();
		$query2 = $this->db->prepare($query_bls_liste);
		$query2->execute();
		foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$bl = $blsManager->getBl($donnee['id_bl'], false, true, false);
			if (!$bl instanceof Bl) { continue; }
			if ((int)$prp->getId_transporteur() == 0) {
				$prp->setId_transporteur($bl->getId_tiers_transporteur());
				$t3 =$tiersManager->getTiers($bl->getId_tiers_transporteur());
				$nomT3 = $t3 instanceof Tiers ? $t3->getNom() : '';
				$prp->setNom_transporteur($nomT3);
			}
			$t2 =$tiersManager->getTiers($bl->getId_tiers_livraison());
			$nomT2 = $t2 instanceof Tiers ? $t2->getNom() : '';
			$bl->setNom_client($nomT2);
			$bls[] = $bl;
		} // FIN liste BLS
		$prp->setBls($bls);

		return $prp;

	} // FIN get PrpOp

	// Enregistre & sauvegarde (Méthode Save)
	public function savePrpOp(PrpOp $objet) {

		$table      = 'pe_prp_op'; // Nom de la table
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

	// Supprime un PRP
	public function supprPrpOp(PrpOp $prp) {

		$query_del = 'DELETE FROM `pe_prp_op` WHERE `id` = ' . $prp->getId();
		$query = $this->db->prepare($query_del);
		$query->execute();
		Outils::saveLog($query_del);
		$query_del2 = 'DELETE FROM `pe_prp_bls` WHERE `id_prp_op` = ' . $prp->getId();
		$query2 = $this->db->prepare($query_del2);
		Outils::saveLog($query_del2);
		return $query2->execute();

	} // FIN méthode

	// retourne les bl pour lesquels on a pas fait de PRP mais qui ont été générés il y a moins d'une semaine pour un transporteur donné
	public function getListeBlsAexpedier(PrpOp $prp) {

		$query_liste = 'SELECT DISTINCT b.`id` 
							FROM `pe_bl` b
						LEFT JOIN `pe_prp_bls` p ON p.`id_bl` = b.`id` AND p.`id_prp_op` != '.$prp->getId().' 
						WHERE p.`id` IS NULL
							AND b.`supprime` = 0
							AND b.`statut` > 1
						  	AND b.`id_tiers_transporteur` = '.$prp->getId_transporteur().'
						
							AND b.`date` >=  CURDATE() - INTERVAL 10 DAY
						ORDER BY b.`id_tiers_livraison`, b.`id` ';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		$blManager = new BlManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = $blManager->getBl($donnee['id']);
		}
		return $liste;

	} // FIN méthode


	//Retourner le bl qu'on est en train de faire de PRP
	public function getBlAexpedier(PrpOp $prp){
		$query_liste = 'SELECT DISTINCT b.`id` FROM `pe_bl` b 
			LEFT JOIN `pe_prp_bls` p ON p.`id_bl` = b.`id` 
			WHERE p.`id_prp_op` = ' .$prp->getId();

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		$blManager = new BlManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee){
			$liste[] = $blManager->getBl($donnee['id']);
		}

		return $liste;

	}



	public function delPrpAbandonnes() {

		$query_liste = 'SELECT `id` FROM `pe_prp_op` WHERE `id_user` = 0 OR `date_add` IS NULL ';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = (int)$donnee['id'];
		}
		if (empty($liste)) { return true; }
		$query_del = 'DELETE FROM `pe_prp_op` WHERE `id` IN ('.implode(',', $liste).')';
		$query = $this->db->prepare($query_del);
		$query->execute();;
		Outils::saveLog($query_del);
		$query_del = 'DELETE FROM `pe_prp_bls` WHERE `id_prp_op` IN ('.implode(',', $liste).')';
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();;

	} // FIN méthode

	public function savePrpOpBls(PrpOp $prp, $ids_bls) {

		if (!is_array($ids_bls)) { return false; }

		$query_del = 'DELETE FROM `pe_prp_bls` WHERE `id_prp_op` = ' . $prp->getId();
		$query = $this->db->prepare($query_del);
		$query->execute();
		Outils::saveLog($query_del);
		$query_ins = 'INSERT IGNORE INTO `pe_prp_bls` (`id_prp_op`, `id_bl`) VALUES ';
		foreach ($ids_bls as $id_bl) {
			$query_ins.= '('.$prp->getId().', '.(int)$id_bl.'),';
		}
		$query_ins = substr($query_ins,0,-1);
		$query2 = $this->db->prepare($query_ins);
		Outils::saveLog($query_ins);
		return $query2->execute();

	} // FIN méthode

} // FIN classe