<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet PvisuApres
Généré par CBO FrameWork le 03/09/2020 à 11:11:31
------------------------------------------------------*/
class PvisuApresManager {

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

	// Retourne la liste des PvisuApres
	public function getListePvisuApres($params = []) {
		// Pagination
		$start 			= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 			= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;
		$date 			= isset($params['date']) 			? trim($params['date']) 			: '';
		$mois 			= isset($params['mois']) 			? intval($params['mois']) 			: 0;
		$annee 			= isset($params['annee']) 			? intval($params['annee']) 			: 0;
		$nonValides 	= isset($_REQUEST['non_valides']) 	? intval($_REQUEST['non_valides']) 	: 0;

		if (!Outils::verifDateSql($date)) { $date = ''; }
		if ($mois > 0 || $annee > 0) { $date = ''; }

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS pva.`id`, pva.`date`, pva.`commentaires`, pva.`id_user_validation`, pva.`date_validation`, pva.`id_lot`,
                           			CONCAT(u.`prenom`, " ", UPPER(u.`nom`)) AS nom_user,
									CONCAT(v.`prenom`, " ", UPPER(v.`nom`)) AS nom_validateur
							FROM `pe_pvisu_apres` pva
								LEFT JOIN `pe_users` u ON u.`id` =  pva.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` =  pva.`id_user_validation`
								LEFT JOIN `pe_lots`  l ON l.`id` =  pva.`id_lot`
								WHERE 1 ';

		$query_liste.= $date != '' && $nonValides == 0 ? ' AND pva.`date` = "'.$date.'" ' : '';
		$query_liste.= $date != '' && $nonValides == 1 ? ' AND pva.`date` <= "'.$date.'" ' : '';
		$query_liste.= $mois > 0 ? ' AND MONTH(pvp.`date`) = ' . $mois : '';
		$query_liste.= $annee > 0 ? ' AND YEAR(pvp.`date`) = ' . $annee : '';
		$query_liste.= $nonValides == 1 ? ' AND pva.`id_user_validation` = 0 ' : '';

		$query_liste.= ' ORDER BY pva.`date` DESC, pva.`id` DESC ';
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$pvApres =  new PvisuApres($donnee);

			$points = $this->getListePvisuApresPoints($pvApres);
			$pvApres->setPoints_controles($points);

			$liste[] = $pvApres;
		}
		return $liste;

	} // FIN liste des PvisuApres

	// Retourne un PvisuApres pour une date de lot
	public function getPvisuApresByDateLot(Lot $lot) {
		
		$dateAtelierDt = new DateTime($lot->getDate_atelier());
		$dateAtelier = $dateAtelierDt->format('Y-m-d'); 
		$dateProdDt = new DateTime($lot->getDate_prod());
		$dateProd = $dateProdDt->format('Y-m-d');


		if ($lot->getDate_atelier() == '') { $dateAtelier = '1970-01-01'; }
		if ($lot->getDate_prod() == '') { $dateProd = '1970-01-01'; }
	

		$query_object = 'SELECT pva.`id`, pva.`date`, pva.`commentaires`, pva.`id_user_validation`, pva.`date_validation`, 
                           			CONCAT(u.`prenom`, " ", UPPER(u.`nom`)) AS nom_user,
									CONCAT(v.`prenom`, " ", UPPER(v.`nom`)) AS nom_validateur
							FROM `pe_pvisu_apres` pva
								LEFT JOIN `pe_users` u ON u.`id` =  pva.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` =  pva.`id_user_validation`				
                 			WHERE DATE(pva.`date`) = "'.$dateAtelier.'" OR DATE(pva.`date`) = "'.$dateProd.'" ';
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new PvisuApres($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN méthode

	//Retourne les PvisuApres pour un lot donnée
	public function getPvisuApresByLot(Lot $lot){
		$query_object = 'SELECT pva.`id`, pva.`date`, pva.`commentaires`, pva.`id_user_validation`, pva.`date_validation`, pva.`id_lot`,
                           			CONCAT(u.`prenom`, " ", UPPER(u.`nom`)) AS nom_user,
									CONCAT(v.`prenom`, " ", UPPER(v.`nom`)) AS nom_validateur, l.`numlot`
							FROM `pe_pvisu_apres` pva
								LEFT JOIN `pe_users` u ON u.`id` =  pva.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` =  pva.`id_user_validation`	
								LEFT JOIN `pe_lots`  l ON l.`id` =  pva.`id_lot`
                 			WHERE pva.`id_lot` = ' . $lot->getId();

		$query = $this->db->prepare($query_object);

		if ($query->execute()) {

			$liste = [];

			foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
				$liste[] = new PvisuApres($donnee);
			}
			return $liste;

		} else {
			return false;
		}

	}

	// Retourne un PvisuApres
	public function getPvisuApres($id) {

		$query_object = 'SELECT pva.`id`, pva.`date`, pva.`commentaires`, pva.`id_user_validation`, pva.`date_validation`, pva.`id_lot`,
                           			CONCAT(u.`prenom`, " ", UPPER(u.`nom`)) AS nom_user,
									CONCAT(v.`prenom`, " ", UPPER(v.`nom`)) AS nom_validateur
							FROM `pe_pvisu_apres` pva
								LEFT JOIN `pe_users` u ON u.`id` =  pva.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` =  pva.`id_user_validation`
								LEFT JOIN `pe_lots`  l ON l.`id` =  pva.`id_lot`				
                 			WHERE pva.`id` = ' . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new PvisuApres($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get PvisuApres


	//Retourne le PvisuApres par son lot pour la date du jour
	public function getPvisuApresJourByLot(Lot $lot)
	{
		$query_object = 'SELECT pva.`id`, pva.`date`, pva.`commentaires`, pva.`id_user_validation`, pva.`date_validation`, pva.`id_lot`,
                           			CONCAT(u.`prenom`, " ", UPPER(u.`nom`)) AS nom_user,
									CONCAT(v.`prenom`, " ", UPPER(v.`nom`)) AS nom_validateur
							FROM `pe_pvisu_apres` pva
								LEFT JOIN `pe_users` u ON u.`id` =  pva.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` =  pva.`id_user_validation`				
                 			WHERE pva.`date` = CURDATE() AND pva.`id_lot` = ' . (int)$lot->getId();
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new PvisuApres($donnee[0]) : new PvisuApres([]);
		} else {
			return new PvisuApres([]);
		}
	}


	// Enregistre & sauvegarde (Méthode Save)
	public function savePvisuApres(PvisuApres $objet) {

		$table      = 'pe_pvisu_apres'; // Nom de la table
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

	// Retourne la liste des PvisuApresPoints d'un Pvisu
	public function getListePvisuApresPoints(PvisuApres $pvisu, $retour_array = false) {

		$query_liste = "SELECT ppp.`id`, ppp.`id_pvisu_apres`, ppp.`id_point_controle`, ppp.`etat`, ppp.`id_pvisu_action`, ppp.`fiche_nc`, ppp.`id_user`, ppp.`date`,
       							pa.`nom` AS nom_action, pc.`nom`
							FROM `pe_pvisu_apres_points` ppp
								LEFT JOIN `pe_pvisu_actions` pa ON pa.`id` = ppp.`id_pvisu_action`
								LEFT JOIN `pe_points_controle` pc ON pc.`id` = ppp.`id_point_controle`
						WHERE ppp.`id_pvisu_apres` = ".(int)$pvisu->getId()." ORDER BY pc.`position`, ppp.`id` DESC";
		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new PvisuApresPoints($donnee);
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

	} // FIN liste des PvisuApresPoints

	// Retourne un PvisuApresPoints
	public function getPvisuApresPoints($id) {

		$query_object = "SELECT ppp.`id`, ppp.`id_pvisu_apres`, ppp.`id_point_controle`, ppp.`etat`, ppp.`id_pvisu_action`, ppp.`fiche_nc`, ppp.`id_user`, ppp.`date`,
       							pa.`nom` AS nom_action
							FROM `pe_pvisu_apres_points` ppp
								JOIN `pe_pvisu_actions` pa ON pa.`id` = ppp.`id_pvisu_action` WHERE ppp.`id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new PvisuApresPoints($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get PvisuApresPoints

	public function savePvisuApresPoints(PvisuApres $pvisu, $points) {

		global $utilisateur;

		if (!is_array($points)) { return false; }

		$query_del = 'DELETE FROM `pe_pvisu_apres_points` WHERE `id_pvisu_apres` = ' . (int)$pvisu->getId();
		$query1 = $this->db->prepare($query_del);
		$query1->execute();
		Outils::saveLog($query_del);
		$query_add = 'INSERT IGNORE INTO `pe_pvisu_apres_points` (`id_pvisu_apres`, `id_point_controle`, `etat`, `id_pvisu_action`, `fiche_nc`, `id_user`, `date`) VALUES ';
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



	public function savePvisuAfterPoints(PvisuApres $pvisu, $points) {
		global $utilisateur;
	
		if (!is_array($points)) {
			return false;
		}

		$query_add = 'INSERT INTO `pe_pvisu_apres_points` (`id_pvisu_apres`, `id_point_controle`, `etat`, `id_pvisu_action`, `fiche_nc`, `id_user`, `date`) VALUES ';
		$values = [];
	
		foreach ($points as $id_point => $etat) {
			$values[] = '(' . (int)$pvisu->getId() . ', ' . (int)$id_point . ', ' . $etat . ', 0, 0, ' . (int)$utilisateur->getId() . ', NOW())';
		}
	
		if (empty($values)) {
			return false;
		}
	
		$query_add .= implode(',', $values);
		
	
		$query_add .= ' ON DUPLICATE KEY UPDATE `etat` = VALUES(`etat`), `id_pvisu_action` = VALUES(`id_pvisu_action`), `fiche_nc` = VALUES(`fiche_nc`), `id_user` = VALUES(`id_user`), `date` = VALUES(`date`)';
	

		$query2 = $this->db->prepare($query_add);
		Outils::saveLog($query_add); 
		return $query2->execute();
	}
	




	// Retourne le Pvisu apres du jour s'il existe, sinon le crée
	public function getPvisuApresJour($date = '', $creation = true) {

		global $utilisateur;

		if ($date == '' || !Outils::verifDateSql($date)) {
			$date = date('Y-m-d');
		}

		$query_object = 'SELECT SQL_CALC_FOUND_ROWS pva.`id`, pva.`date`, pva.`commentaires`, pva.`id_user_validation`, pva.`date_validation`, pva.`id_lot`,
                           			CONCAT(u.`prenom`, " ", UPPER(u.`nom`)) AS nom_user,
									CONCAT(v.`prenom`, " ", UPPER(v.`nom`)) AS nom_validateur
							FROM `pe_pvisu_apres` pva
								LEFT JOIN `pe_users` u ON u.`id` =  pva.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` =  pva.`id_user_validation`
								LEFT JOIN `pe_lots`  l ON l.`id` =  pva.`id_lot`
						WHERE pva.`date` = "'.$date.'"';

		$query = $this->db->prepare($query_object);

		if ($query->execute()) {
			$donnees = $query->fetch(PDO::FETCH_ASSOC);
			if ($donnees) {
				$pvisu = new PvisuApres($donnees);
			}
		}

		if (!isset($pvisu) || !$pvisu instanceof PvisuApres) {
			$pvisu = new PvisuApres([]);
		}

		if ((int)$pvisu->getId() == 0 && $creation) {
			$pvisu->setCommentaires('');
			$pvisu->setDate($date);
			$pvisu->setId_user($utilisateur->getId());
			$pvisu_id = $this->savePvisuApres($pvisu);
			if ((int)$pvisu_id == 0) { return false; }
			$pvisu->setId($pvisu_id);
		}

		return $pvisu;

	} // FIN méthode

} // FIN classe