<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Langue
Généré par CBO FrameWork le 06/03/2020 à 15:32:54
------------------------------------------------------*/
class LanguesManager {

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

	// Retourne la liste des Langue
	public function getListeLangues($params = []) {

		$actif = isset($params['actif']) ? intval($params['actif']) : -1;

		$query_liste = "SELECT `id`, `nom`, `iso`, `actif`, `supprime`, `ordre` FROM `pe_langues`  WHERE 1 ";

		$query_liste.= $actif > -1 ? ' AND `actif` = ' . $actif : '';

		$query_liste.= " ORDER BY `ordre` ASC";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Langue($donnee);
		}
		return $liste;

	} // FIN liste des Langue


	// Retourne un Langue
	public function getLangue($id) {

		$query_object = "SELECT `id`, `nom`, `iso`, `actif`, `supprime` 
                FROM `pe_langues` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new Langue($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get Langue

	// Enregistre & sauvegarde (Méthode Save)
	public function saveLangue(Langue $objet) {

		$table      = 'pe_langues'; // Nom de la table
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

	// Met à jour l'activation des langues
	public function activeLangues(array $langues_actives) {

		$query_upd_1 = 'UPDATE `pe_langues` SET `actif` = 1 WHERE `id` IN ('.implode(',', $langues_actives).')';
		$query1 = $this->db->prepare($query_upd_1);
		if (!$query1->execute()) { return false; }
		Outils::saveLog($query_upd_1);
		$query_upd_2 = 'UPDATE `pe_langues` SET `actif` = 0 WHERE `id` NOT IN ('.implode(',', $langues_actives).')';
		$query2 = $this->db->prepare($query_upd_2);
		Outils::saveLog($query_upd_2);
		return $query2->execute();

	} // FIN méthode

	// Débug - réactive le français
	public function activeFr() {
		$query_upd = 'UPDATE `pe_langues` SET `actif` = 1 WHERE `iso` = "FR"';
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();
	}

} // FIN classe