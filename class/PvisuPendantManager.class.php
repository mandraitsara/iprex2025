<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet PvisuPendant
Généré par CBO FrameWork le 03/09/2020 à 11:11:31
------------------------------------------------------*/
class PvisuPendantManager {

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

	// Retourne la liste des PvisuPendant
	public function getListePvisuPendants($params = []) {
		// Pagination
		$start 			= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 			= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;
		$date 			= isset($params['date']) 			? trim($params['date']) 			: '';
		$mois 			= isset($params['mois']) 			? intval($params['mois']) 			: 0;
		$annee 			= isset($params['annee']) 			? intval($params['annee']) 			: 0;
		$nonValides 	= isset($_REQUEST['non_valides']) 	? intval($_REQUEST['non_valides']) 	: 0;

		if (!Outils::verifDateSql($date)) { $date = ''; }
		if ($mois > 0 || $annee > 0) { $date = ''; }

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS pvp.`id`, pvp.`date`, pvp.`commentaires`, pvp.`id_user_validation`, pvp.`date_validation`,  pvp.`id_lot`,
                           			CONCAT(u.`prenom`, " ", UPPER(u.`nom`)) AS nom_user,
									CONCAT(v.`prenom`, " ", UPPER(v.`nom`)) AS nom_validateur, l.`numlot`
							FROM `pe_pvisu_pendant` pvp
								LEFT JOIN `pe_users` u ON u.`id` =  pvp.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` =  pvp.`id_user_validation`
								LEFT JOIN `pe_lots`  l ON l.`id` =  pvp.`id_lot`
								WHERE 1 ';

		$query_liste.= $date != '' && $nonValides == 0 ? ' AND pvp.`date` = "'.$date.'" ' : '';
		$query_liste.= $date != '' && $nonValides == 1 ? ' AND pvp.`date` <= "'.$date.'" ' : '';
		$query_liste.= $mois > 0 ? ' AND MONTH(pvp.`date`) = ' . $mois : '';
		$query_liste.= $annee > 0 ? ' AND YEAR(pvp.`date`) = ' . $annee : '';
		$query_liste.= $nonValides == 1 ? ' AND pvp.`id_user_validation` = 0 ' : '';

		$query_liste.= ' ORDER BY pvp.`date` DESC, pvp.`id` DESC ';
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$pvPendant =  new PvisuPendant($donnee);

			$points = $this->getListePvisuPendantPoints($pvPendant);
			$pvPendant->setPoints_controles($points);

			$liste[] = $pvPendant;
		}
		return $liste;

	} // FIN liste des PvisuPendant


	// Retourne les PvisuPendants pour un lot donné
	public function getPvisuPendantByLot(Lot $lot) {

		$query_object = 'SELECT pvp.`id`, pvp.`date`, pvp.`commentaires`, pvp.`id_user_validation`, pvp.`date_validation`, pvp.`id_lot`,
                           			CONCAT(u.`prenom`, " ", UPPER(u.`nom`)) AS nom_user,
									CONCAT(v.`prenom`, " ", UPPER(v.`nom`)) AS nom_validateur, l.`numlot`
							FROM `pe_pvisu_pendant` pvp
								LEFT JOIN `pe_users` u ON u.`id` =  pvp.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` =  pvp.`id_user_validation`	
								LEFT JOIN `pe_lots`  l ON l.`id` =  pvp.`id_lot`
                 			WHERE pvp.`id_lot` = ' . $lot->getId();
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {

			$liste = [];

			foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
				$liste[] = new PvisuPendant($donnee);
			}
			return $liste;

		} else {
			return false;
		}

	} // FIN méthode

	// Retourne un PvisuPendant
	public function getPvisuPendant($id) {

		$query_object = 'SELECT pvp.`id`, pvp.`date`, pvp.`commentaires`, pvp.`id_user_validation`, pvp.`date_validation`, pvp.`id_lot`,
                           			CONCAT(u.`prenom`, " ", UPPER(u.`nom`)) AS nom_user,
									CONCAT(v.`prenom`, " ", UPPER(v.`nom`)) AS nom_validateur, l.`numlot`
							FROM `pe_pvisu_pendant` pvp
								LEFT JOIN `pe_users` u ON u.`id` =  pvp.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` =  pvp.`id_user_validation`	
								LEFT JOIN `pe_lots`  l ON l.`id` =  pvp.`id_lot`
                 			WHERE pvp.`id` = ' . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new PvisuPendant($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get PvisuPendant


	// Retourne le PvisuPendant par son Lot pour la date du jour
	public function getPvisuPendantJourByLot(Lot $lot) {

		$query_object = 'SELECT pvp.`id`, pvp.`date`, pvp.`commentaires`, pvp.`id_user_validation`, pvp.`date_validation`, pvp.`id_lot`,
                           			CONCAT(u.`prenom`, " ", UPPER(u.`nom`)) AS nom_user,
									CONCAT(v.`prenom`, " ", UPPER(v.`nom`)) AS nom_validateur
							FROM `pe_pvisu_pendant` pvp
								LEFT JOIN `pe_users` u ON u.`id` =  pvp.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` =  pvp.`id_user_validation`				
                 			WHERE pvp.`date` = CURDATE() AND pvp.`id_lot` = ' . (int)$lot->getId();
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new PvisuPendant($donnee[0]) : new PvisuPendant([]);
		} else {
			return new PvisuPendant([]);
		}

	} // FIN méthode

	// Enregistre & sauvegarde (Méthode Save)
	public function savePvisuPendant(PvisuPendant $objet) {

		$table      = 'pe_pvisu_pendant'; // Nom de la table
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

	// Retourne la liste des PvisuPendantPoints d'un Pvisu
	public function getListePvisuPendantPoints(PvisuPendant $pvisu, $retour_array = false) {

		$query_liste = "SELECT ppp.`id`, ppp.`id_pvisu_pendant`, ppp.`id_point_controle`, ppp.`etat`, ppp.`id_pvisu_action`, ppp.`fiche_nc`, ppp.`id_user`, ppp.`date`,
       							pa.`nom` AS nom_action, pc.`nom`
							FROM `pe_pvisu_pendant_points` ppp
								LEFT JOIN `pe_pvisu_actions` pa ON pa.`id` = ppp.`id_pvisu_action`
								LEFT JOIN `pe_points_controle` pc ON pc.`id` = ppp.`id_point_controle`
						WHERE ppp.`id_pvisu_pendant` = ".(int)$pvisu->getId()." ORDER BY pc.`position`, ppp.`id` DESC";
		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new PvisuPendantPoints($donnee);
		}

		if (!$retour_array) {
			return $liste;
		}

		// Retour sous forme d'array
		$retour = [];
		foreach ($liste as $pvp) {
			$tmp = [];
			$tmp['etat'] 			= (int)$pvp->getEtat();
			$tmp['id_pvisu_action'] = (int)$pvp->getId_pvisu_action();
			$tmp['fiche_nc'] 		= (int)$pvp->getFiche_nc();
			$retour[(int)$pvp->getId_point_controle()] = $tmp;
		}
		return $retour;

	} // FIN liste des PvisuPendantPoints

	// Retourne un PvisuPendantPoints
	public function getPvisuPendantPoints($id) {

		$query_object = "SELECT ppp.`id`, ppp.`id_pvisu_pendant`, ppp.`id_point_controle`, ppp.`etat`, ppp.`id_pvisu_action`, ppp.`fiche_nc`, ppp.`id_user`, ppp.`date`,
       							pa.`nom` AS nom_action
							FROM `pe_pvisu_pendant_points` ppp
								JOIN `pe_pvisu_actions` pa ON pa.`id` = ppp.`id_pvisu_action` WHERE ppp.`id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new PvisuPendantPoints($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get PvisuPendantPoints

	public function savePvisuPendantPoints(PvisuPendant $pvisu, $points) {

		global $utilisateur;

		if (!is_array($points)) { return false; }

		$query_del = 'DELETE FROM `pe_pvisu_pendant_points` WHERE `id_pvisu_pendant` = ' . (int)$pvisu->getId();
		$query1 = $this->db->prepare($query_del);
		$query1->execute();
		Outils::saveLog($query_del);
		$query_add = 'INSERT IGNORE INTO `pe_pvisu_pendant_points` (`id_pvisu_pendant`, `id_point_controle`, `etat`, `id_pvisu_action`, `fiche_nc`, `id_user`, `date`) VALUES ';
		$checklen = strlen($query_add);

		foreach ($points as $id_point => $donnees) {

			$etat 				= isset($donnees['etat']) 			 ? intval($donnees['etat']) 			: 0;
			$id_pvisu_action 	= isset($donnees['id_pvisu_action']) ? intval($donnees['id_pvisu_action'])  : 0;
			$fiche_nc 			= isset($donnees['fiche_nc']) 		 ? intval($donnees['fiche_nc']) 		: 0;

			$query_add.= '('.(int)$pvisu->getId().', '.(int)$id_point.', '.$etat.', '.$id_pvisu_action.', '.$fiche_nc.', '.(int)$utilisateur->getId().', NOW()),';

		} // FIN boucle sur les points

		if (strlen($query_add) == $checklen) {  return false; }
		$query_add = substr($query_add,0,-1);

		$query2 = $this->db->prepare($query_add);
		Outils::saveLog($query_add);
		return $query2->execute();

	} // FIN méthode

	// Retourne le Pvisu pendant du jour s'il existe, sinon le crée
	public function getPvisuPendantJour($date = '', $creation = true) {

		if ($date == '' || !Outils::verifDateSql($date)) {
			$date = date('Y-m-d');
		}

		$query_object = 'SELECT SQL_CALC_FOUND_ROWS pvp.`id`, pvp.`date`, pvp.`commentaires`, pvp.`id_user_validation`, pvp.`date_validation`, pvp.`id_lot`,
                           			CONCAT(u.`prenom`, " ", UPPER(u.`nom`)) AS nom_user,
									CONCAT(v.`prenom`, " ", UPPER(v.`nom`)) AS nom_validateur, l.`numlot`
							FROM `pe_pvisu_pendant` pvp
								LEFT JOIN `pe_users` u ON u.`id` =  pvp.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` =  pvp.`id_user_validation`
								LEFT JOIN `pe_lots`  l ON l.`id` =  pvp.`id_lot`
						WHERE pvp.`date` = "'.$date.'"';

		$query = $this->db->prepare($query_object);

		if ($query->execute()) {
			$donnees = $query->fetch(PDO::FETCH_ASSOC);
			if ($donnees) {
				$pvisu = new PvisuPendant($donnees);
			}
		}

		if (!isset($pvisu) || !$pvisu instanceof PvisuPendant) {
			$pvisu = new PvisuPendant([]);
		}

		if ((int)$pvisu->getId() == 0 && $creation) {
			$pvisu->setCommentaires('');
			$pvisu->setDate($date);
			$pvisu_id = $this->savePvisuPendant($pvisu);
			if ((int)$pvisu_id == 0) { return false; }
			$pvisu->setId($pvisu_id);
		}

		return $pvisu;

	} // FIN méthode

	// Supprime un pvisu pendant (en cas d'échec d'enregistrement des points lors d'une création)
	public function supprPvisupendant(PvisuPendant $pvisu) {

		$query_del = 'DELETE FROM `pe_pvisu_pendant` WHERE `id` = ' . (int)$pvisu->getId().';';
		$query_del.= 'DELETE FROM `pe_pvisu_pendant_points` WHERE `id_pvisu_pendant` = ' . (int)$pvisu->getId();
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode

} // FIN classe