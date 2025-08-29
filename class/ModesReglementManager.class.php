<?php

/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet ModeReglement
Généré par CBO FrameWork le 16/09/2020 à 15:39:57
------------------------------------------------------*/

class ModesReglementManager
{

	protected $db, $nb_results;

	public function __construct($db)
	{
		$this->setDb($db);
	}

	/* ----------------- GETTERS ----------------- */
	public function getNb_results()
	{
		return $this->nb_results;
	}

	/* ----------------- SETTERS ----------------- */
	public function setDb(PDO $db)
	{
		$this->db = $db;
	}

	/* ----------------- METHODES ----------------- */

	// Retourne la liste des ModeReglement
	public function getListeModesReglements() {

		$query_liste = "SELECT `id`, `nom`, `supprime` FROM `pe_modes_reglement` WHERE `supprime` = 0 ORDER BY `nom` ";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new ModeReglement($donnee);
		}
		return $liste;

	} // FIN liste des ModeReglement


	// Retourne un ModeReglement
	public function getModeReglement($id) {

		$query_object = "SELECT `id`, `nom`, `supprime` 
                FROM `pe_modes_reglement` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new ModeReglement($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get ModeReglement

	// Enregistre & sauvegarde (Méthode Save)
	public function saveModeReglement(ModeReglement $objet) {

		$table = 'pe_modes_reglement'; // Nom de la table
		$champClef = 'id'; // Nom du champ clef
		// FIN Configuration

		$getter = 'get' . ucfirst(strtolower($champClef));
		$setter = 'set' . ucfirst(strtolower($champClef));

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

	// Retourne le mode de règlement par défaut (notamment pour les avoirs)
	public function getIdModeDefaut() {

		$query_id = 'SELECT `id` FROM `pe_modes_reglement` WHERE `nom` LIKE "vir%" AND `supprime` = 0';
		$query = $this->db->prepare($query_id);
		$query->execute();
		$donnee = $query->fetch(PDO::FETCH_ASSOC);

		return isset($donnee['id']) ? intval($donnee['id']) : 0;


	} // FIN méthode

} // FIN classe