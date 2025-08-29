<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Pays
Généré par CBO FrameWork le 04/03/2020 à 09:25:59
------------------------------------------------------*/
class PaysManager {

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

	// Retourne la liste des Pays
	public function getListePays($params = []) {

		$query_liste = "SELECT p.`id`, p.`iso`, p.`code`, t.`nom`, p.`export`
							FROM `pe_pays` p
								LEFT JOIN `pe_pays_trad` t ON t.`id_pays` = p.`id` AND t.`id_langue` = 1
						WHERE p.`supprime` = 0 ";

		$query_liste.= " ORDER BY t.`nom`, p.`iso` ASC";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$pays = new Pays($donnee);

			// On rattache les noms traduits
			$noms = $this->getNomsPaysTrad($pays->getId());
			$pays->setNoms($noms);

			$liste[] = $pays;
		}
		return $liste;

	} // FIN liste des Pays


	// Retourne un Pays
	public function getPays($id) {

		$query_object = "SELECT p.`id`, p.`iso`, p.`code`, t.`nom`, p.`export`
                			FROM `pe_pays` p
								LEFT JOIN `pe_pays_trad` t ON t.`id_pays` = p.`id` AND t.`id_langue` = 1
						WHERE p.`id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			if (!$donnee || !isset($donnee[0])) { return false; }
			$pays = new Pays($donnee[0]);
			if (!$pays instanceof Pays) { return false; }

			// On rattache les noms traduits
			$noms = $this->getNomsPaysTrad($pays->getId());
			$pays->setNoms($noms);

			return $pays;
		} else {
			return false;
		}

	} // FIN get Pays

	// Retourne les traductions d'un pays
	public function getNomsPaysTrad($id_pays) {

		$query_liste = 'SELECT `id_langue` , `nom` FROM `pe_pays_trad` WHERE `id_pays` = ' . (int)$id_pays . ' ORDER BY `id_langue` ';
		// Requête
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[(int)$donnee['id_langue']] = $donnee['nom'];
		}

		return $liste;

	} // FIN fonction

	// Enregistre & sauvegarde (Méthode Save)
	public function savePays(Pays $objet) {

		$table      = 'pe_pays'; // Nom de la table
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

	// Vérifie si un pays existe déjà avec ce code
	public function checkExisteDeja($iso, $id_exclu = 0) {

		$query_check = 'SELECT COUNT(*) AS nb FROM `pe_pays` WHERE (LOWER(`iso`) = :iso) ';
		$query_check.= (int)$id_exclu > 0 ? ' AND `id` != ' . (int)$id_exclu : '';

		$query = $this->db->prepare($query_check);
		$query->bindValue(':iso', trim(strtolower($iso)));
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);

		if ($donnee && isset($donnee[0]) && isset($donnee[0]['nb']) && intval($donnee[0]['nb']) > 0) {
			return true;
		}
		return false;

	} // FIN méthode

	// Enregistre la traduction d'un  pays
	public function saveTradPays(Pays $pays, $id_langue, $traduction) {

		// Si on supprime une traduction
		if (trim($traduction) == '') {
			$query_del = 'DELETE FROM `pe_pays_trad` WHERE `id_pays` = ' . $pays->getId() . ' AND `id_langue` = ' . $id_langue;
			$query = $this->db->prepare($query_del);
			Outils::saveLog($query_del);
			return $query->execute();
		}

		// Sinon on met à jour ou on ajoute...
		$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_pays_trad` WHERE `id_pays` = ' . $pays->getId() . ' AND `id_langue` = ' . $id_langue . '';
		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);

		$nb = $donnee && isset($donnee[0]) && isset($donnee[0]['nb']) ? intval($donnee[0]['nb']) : 0;

		$query_addupd = $nb > 0
			? 'UPDATE `pe_pays_trad` SET `nom` = :nom WHERE `id_pays` = ' . $pays->getId() . ' AND `id_langue` = ' . $id_langue
			: 'INSERT IGNORE INTO `pe_pays_trad` (`id_pays`, `id_langue`, `nom`) VALUES ( ' . $pays->getId() . ', '.$id_langue.', :nom)';

		$query2 = $this->db->prepare($query_addupd);
		$query2->bindValue(':nom', $traduction);
		$query_log = str_replace(':nom', '"'.$traduction.'"',$query_addupd);
		Outils::saveLog($query_log);
		return $query2->execute();

	} // FIN méthode

} // FIN classe