<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Zone
Généré par CBO FrameWork le 10/03/2020 à 14:35:04
------------------------------------------------------*/
class ZonesManager {

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

	// Retourne la liste des Zone
	public function getListeZones($params = []) {

		$actif = isset($params['actif']) ? intval($params['actif']) : -1;

		$query_liste = "SELECT `id`, `code`, `actif`, `large`, `supprime` FROM `pe_traductions_zones` WHERE `supprime` = 0 ";
		$query_liste.= $actif > -1 ? ' AND `actif` = ' . $actif : '';
		$query_liste.= " ORDER BY `large`,  `code` ";
		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Zone($donnee);
		}
		return $liste;

	} // FIN liste des Zone


	// Retourne un Zone
	public function getZone($id) {

		$query_object = "SELECT `id`, `code`, `actif`, `supprime`, `large`
                FROM `pe_traductions_zones` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new Zone($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get Zone

	// Retourne une zone par son code
	public function getZoneByCode($code) {

		$query_zone = 'SELECT `id`, `code`, `actif`, `supprime` FROM `pe_traductions_zones` WHERE TRIM(LOWER(`code`)) = "'.trim(strtolower($code)).'"';
		$query = $this->db->prepare($query_zone);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		if (!$donnee || !isset($donnee['id'])) { return false; }
		return new Zone($donnee);

	} // FIN méthode

	// Enregistre & sauvegarde (Méthode Save)
	public function saveZone(Zone $objet) {

		$table      = 'pe_traductions_zones'; // Nom de la table
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