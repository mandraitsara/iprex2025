<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Modification
------------------------------------------------------*/
class ModificationManager {

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

	// Retourne la liste des Modification
	public function getListeModifications($params) {


		$start			= isset($params['start'])			? $params['start']			 	: 0;
		$nb				= isset($params['nb_results_p_page']) ? $params['nb_results_p_page'] : 1000;

		$filtre_debut 	= isset($params['debut']) 			? Outils::dateFrToSql($params['debut']) . ' 00:00:00' 	: false;
		$filtre_fin 	= isset($params['fin']) 			? Outils::dateFrToSql($params['fin']) . '23:59:59' 		: false;

		$query_liste = "SELECT SQL_CALC_FOUND_ROWS m.`id`, m.`user_id`, m.`date`, m.`id_froid`, m.`id_lot_pdt_froid`, m.`champ`, m.`valeur_old`, m.`valeur_new`,
                           		CONCAT(u.`prenom`, ' ', u.`nom`) AS nom_user
							FROM `pe_modifs` m 
							JOIN `pe_users` u ON u.`id` = m.`user_id` ";
		
		$query_liste.= $filtre_debut 	? ' AND m.`date` >= "' . $filtre_debut . '"' 	: '';
		$query_liste.= $filtre_fin 		? ' AND m.`date` <= "' . $filtre_fin . '"' 		: '';
		$query_liste.= 'ORDER BY m.`id` DESC ';
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;

		
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Modification($donnee);
		}
		return $liste;

	} // FIN liste des Modification


	// Retourne un Modification
	public function getModification($id) {

		$query_object = "SELECT `id`, `user_id`, `date`, `id_froid`, `id_lot_pdt_froid`, `champ`, `valeur_old`, `valeur_new` 
                FROM `pe_modifs` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new Modification($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get Modification

	// Enregistre & sauvegarde (Méthode Save)
	public function saveModification(Modification $objet) {

		$table      = 'pe_modifs'; // Nom de la table
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

} // FIN classe