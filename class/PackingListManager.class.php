<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet PackingList
Généré par CBO FrameWork le 16/07/2020 à 10:43:28
------------------------------------------------------*/
class PackingListManager {

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

	/* ----------------- METHODES ----------------- */

	// Retourne la liste des PackingList
	public function getListePackingLists() {

		$query_liste = "SELECT `id`, `date`, `date_envoi` FROM `pe_packing_lists` ORDER BY `id` DESC";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new PackingList($donnee);
		}
		return $liste;

	} // FIN liste des PackingList


	// Retourne un PackingList
	public function getPackingList($id) {

		$query_object = "SELECT `id`, `date`, `date_envoi` 
                FROM `pe_packing_lists` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new PackingList($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get PackingList

	// Enregistre & sauvegarde (Méthode Save)
	public function savePackingList(PackingList $objet) {

		$table      = 'pe_packing_lists'; // Nom de la table
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

	public function getDossierPackingListPdf(PackingList $packing_list, $crer_dossiers = true) {


		$chemin = '/gescom/packing_list/';
		if (!file_exists( __CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir( __CBO_ROOT_PATH__ . $chemin);
		}

		$annee = substr($packing_list->getDate(),2,2);

		$chemin.= $annee.'/';
		if (!file_exists( __CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir( __CBO_ROOT_PATH__ . $chemin);
		}

		$mois =  substr($packing_list->getDate(),5,2);
		$chemin.= $mois.'/';
		if (!file_exists( __CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir( __CBO_ROOT_PATH__ . $chemin);
		}
		return $chemin;
	}

} // FIN classe