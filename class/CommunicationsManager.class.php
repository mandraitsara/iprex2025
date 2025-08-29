<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Communication
------------------------------------------------------*/
class CommunicationsManager {

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

	// Retourne la liste des Communications

	public function getListeCommunications($params) {

		$show_inactifs 	= isset($params['show_inactifs']) 	? boolval($params['show_inactifs']) : false;

		$id_vue			= isset($params['vue']) 			? intval($params['vue']) 				: 0;
		$recherche 		= isset($params['recherche']) 		? trim(strip_tags($params['recherche'])) 	: '';
		$chemin			= isset($params['chemin'])			? trim($params['chemin']) 					: '';
		$activation		= isset($params['actif']) 			? intval($params['actif']) 					: -1;


		$query_liste = "SELECT c.`id`, c.`fichier`, c.`id_vue`, c.`nom`, c.`date`, c.`actif`, c.`supprime`, v.`nom` AS nom_vue, c.`chemin`
							FROM `pe_communications` c
							JOIN `pe_vues` v ON v.`id` = c.`id_vue`
								WHERE c.`supprime` = 0 ";

		$query_liste.= 	$recherche != '' ? 'AND (c.`nom` LIKE "%'.$recherche.'%" OR c.`fichier` LIKE "%'.$recherche.'%") ' : '';
		$query_liste.= 	$chemin != '' ? ' AND (c.`chemin` = "' . $chemin . '" OR c.`chemin` = "' . substr($chemin,1) . '") ' : '';
		$query_liste.= 	$id_vue > 0 ? ' AND c.`id_vue` = ' . $id_vue . ' ' : '';
		$query_liste.= 	!$show_inactifs ? ' AND c.`actif` = 1 ' : '';
		$query_liste.=  $activation > -1 ? 'AND c.`actif` = '.$activation.' ' : '';

		$query_liste.= "ORDER BY c.`chemin` ASC";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($donnees = $query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Communication($donnee);
		}
		return $liste;

	} // FIN liste des communications


	// Retourne une communication
	public function getCommunication($id) {

		$query_object = "SELECT  c.`id`, c.`fichier`, c.`id_vue`, c.`nom`, c.`date`, c.`actif`, c.`supprime`, v.`nom` AS nom_vue, c.`chemin`
							FROM `pe_communications` c
								JOIN `pe_vues` v ON v.`id` = c.`id_vue`
							WHERE c.`id` = " . (int)$id;

		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new Communication($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get communication

	// Enregistre & sauvegarde (Méthode Save)
	public function saveCommunication(Communication $objet) {

		$table      = 'pe_communications'; // Nom de la table
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


	// Retourne les communications par nom de fichier
	public function getCommunicationsByFichier($fichier) {

		$query_object = 'SELECT  c.`id`, c.`fichier`, c.`id_vue`, c.`nom`, c.`date`, c.`actif`, c.`supprime`, v.`nom` AS nom_vue
							FROM `pe_communications` c
								JOIN `pe_vues` v ON v.`id` = c.`id_vue`
							WHERE c.`fichier` = "'.trim($fichier).'"';

		$query = $this->db->prepare($query_object);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Communication($donnee);
		}
		return $liste;


	} // FIN méthode

	// Déplacement d'un fichier : mise à jour des chemin pour l'ensemble des communications faisant appel au fichier
	public function updateCheminByFichier($fichier, $chemin) {

		$liste_coms = $this->getCommunicationsByFichier($fichier);
		foreach ($liste_coms as $com) {
			$com->setChemin($chemin);
			$this->saveCommunication($com);
		}

		return true;

	} // FIN méthode

} // FIN classe