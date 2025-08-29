<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Contact
Généré par CBO FrameWork le 04/03/2020 à 09:22:59
------------------------------------------------------*/
class ContactManager {

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

	// Retourne la liste des Contact
	public function getListeContacts($params = []) {

		$id_tiers 	 = isset($params['id_tiers']) 	 ? trim($params['id_tiers']) 		: '';
		$mail_valide = isset($params['mail_valide']) ? boolval($params['mail_valide']) 	: false;
		$recherche 	 = isset($params['recherche']) 	 ? trim($params['recherche']) 		: '';

		$query_liste = "SELECT `id`, `id_tiers`, `nom`, `prenom`, `telephone`, `mobile`, `fax`, `email`, `supprime`, `date_add`, `date_maj` 
							FROM `pe_contacts` 
						WHERE `supprime` = 0 ";

		$query_liste.= $mail_valide	? ' AND `email` IS NOT NULL AND `email` != "" ': '';
		$query_liste.= $id_tiers != '' 	? ' AND `id_tiers` IN ( ' . $id_tiers .') ': '';
		$query_liste.= $recherche != '' ? ' AND (`nom` LIKE "%' . $recherche.'%" OR `prenom` LIKE "%' . $recherche.'%"  OR `email` LIKE "%' . $recherche.'%" ) ' : '';

		$query_liste.= " ORDER BY `id` DESC";

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Contact($donnee);
		}
		return $liste;

	} // FIN liste des Contact


	// Retourne un Contact
	public function getContact($id) {

		$query_object = "SELECT `id`, `id_tiers`, `nom`, `prenom`, `telephone`, `mobile`, `fax`, `email`, `supprime`, `date_add`, `date_maj` 
                FROM `pe_contacts` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new Contact($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get Contact

	// Enregistre & sauvegarde (Méthode Save)
	public function saveContact(Contact $objet) {

		$table      = 'pe_contacts'; // Nom de la table
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