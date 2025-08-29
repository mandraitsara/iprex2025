<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Adresse
Généré par CBO FrameWork le 04/03/2020 à 09:24:51
------------------------------------------------------*/
class AdresseManager {

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

	// Retourne la liste des Adresse
	public function getListeAdresses($params = []) {

		$id_tiers	= isset($params['id_tiers']) 	? intval($params['id_tiers']) 	: 0;
		$id_langue 	= isset($params['id_langue']) 	? intval($params['id_langue']) 	: 1;
		$id_pays 	= isset($params['id_pays']) 	? intval($params['id_pays']) 	: 0;
		$pays 		= isset($params['pays']) 		? trim($params['pays']) 		: '';

		$query_liste = "SELECT a.`id`, a.`id_tiers`, a.`adresse_1`, a.`adresse_2`, a.`cp`, a.`ville`, a.`id_pays`, a.`type`, p.`nom` AS nom_pays, a.`supprime`, a.`nom`
							FROM `pe_adresses` a
							LEFT JOIN `pe_pays_trad` p ON p.`id_pays` = a.`id_pays` AND p.`id_langue` = ".$id_langue."
						WHERE a.`supprime` = 0 ";

		$query_liste.= $id_tiers > 0 ? ' AND a.`id_tiers` = ' . $id_tiers : '';
		$query_liste.= $pays != '' ? ' AND p.`nom` LIKE "%' . $pays. '%"' : '';
		$query_liste.= $id_pays > 0 ? ' AND a.`id_pays` = ' . $id_pays : '';

		$query_liste.= ' ORDER BY `id` DESC ';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($donnees = $query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Adresse($donnee);
		}
		return $liste;

	} // FIN liste des Adresse


	// Retourne un Adresse
	public function getAdresse($id, $id_langue = 1) {

		$query_object = "SELECT a.`id`, a.`id_tiers`, a.`adresse_1`, a.`adresse_2`, a.`cp`, a.`ville`, a.`id_pays`, a.`type`, p.`nom` AS nom_pays, a.`supprime`, a.`nom`
               				FROM `pe_adresses` a
							LEFT JOIN `pe_pays_trad` p ON p.`id_pays` = a.`id_pays` AND p.`id_langue` = ".(int)$id_langue."
							 WHERE a.`id` = " . (int)$id;

		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new Adresse($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get Adresse

	// Enregistre & sauvegarde (Méthode Save)
	public function saveAdresse(Adresse $objet) {

		$table      = 'pe_adresses'; // Nom de la table
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
				// Log de la requête pour le mode Dev
				if (isset($_SESSION['devmode']) && $_SESSION['devmode']) { $_SESSION['pdoq'][get_class($this)][] = $query->queryString; }
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

	// Retourne l'adresse d'un tiers par son tyoe
	public function getTiersAdresse(Tiers $tiers, $type_adresse) { // f = facturation / l = livraison

		$type = strtolower($type_adresse) == 'f' ? 0 : 1;

		$query_id = 'SELECT `id` FROM `pe_adresses` WHERE `id_tiers` = ' . (int)$tiers->getId() . ' AND `supprime` = 0 AND `type` = ' . $type . ' ORDER BY `id` DESC LIMIT 1';

		$query = $this->db->prepare($query_id);
		$query->execute();

		$donnee = $query->fetch();

		$id = $donnee && isset($donnee['id']) ? intval($donnee['id']) : 0;
		if ($id == 0) { return new Adresse([]); }

		$adressesManager = new AdresseManager($this->db);
		return $adressesManager->getAdresse($id);

	} // FIN méthode


} // FIN classe