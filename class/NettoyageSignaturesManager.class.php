<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet NettoyageSignature
Généré par CBO FrameWork le 18/03/2021 à 11:07:33
------------------------------------------------------*/
class NettoyageSignaturesManager {

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

	// Retourne la liste des NettoyageSignature
	public function getListeNettoyageSignatures($params = []) {

		$start = isset($params['start'])          ? intval($params['start'])          : 0;
		$nb    = isset($params['nb_result_page']) ? intval($params['nb_result_page']) : 10000000;

		$mois = isset($params['mois']) ? $params['mois'] : '';
		$an = isset($params['an']) ? $params['an'] : '';

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS ns.`id`, ns.`id_user`, ns.`date`, ns.`id_user`, ns.`id_validateur`, ns.`date_visa`, 
                           	CONCAT(CONCAT(UCASE(LEFT(u.`prenom`, 1)),SUBSTRING(u.`prenom`, 2)), " ", UPPER(u.`nom`)) AS nom_user,
							IF(v.`nom` IS NOT NULL, CONCAT(CONCAT(UCASE(LEFT(v.`prenom`, 1)),SUBSTRING(v.`prenom`, 2)), " ", UPPER(v.`nom`)), "") AS nom_validateur
							FROM `pe_nett_signatures` ns
								LEFT JOIN `pe_users` u ON u.`id` = ns.`id_user`
								LEFT JOIN `pe_users` v ON v.`id` = ns.`id_validateur` WHERE 1 ';

		$query_liste.= $mois != '' && $an != '' ? ' AND MONTH(ns.`date`) = "'.$mois.'" AND YEAR(ns.`date`) = '.$an : '';

         $query_liste.= ' ORDER BY ns.`date` ';
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new NettoyageSignature($donnee);
		}
		return $liste;

	} // FIN liste des NettoyageSignature


	// Retourne un NettoyageSignature
	public function getNettoyageSignature($id) {

		$query_object = "SELECT `id`, `id_user`, `date`, `id_validateur`,`date_visa`
                FROM `pe_nett_signatures` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee) ? new NettoyageSignature($donnee) : false;

	} // FIN get NettoyageSignature

	// Enregistre & sauvegarde (Méthode Save)
	public function saveNettoyageSignature(NettoyageSignature $objet) {

		$table      = 'pe_nett_signatures'; // Nom de la table
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




	// Retourne l'année la plus ancienne de signature
	public function getMinAnnee() {

		$query_annee = "SELECT IFNULL(YEAR(MIN(`date`)),0) AS minannee FROM `pe_nett_signatures` ";
		$query = $this->db->prepare($query_annee);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return isset($donnee['minannee']) && intval($donnee['minannee'] > 0) ? $donnee['minannee'] : date('Y');

	} // FIN méthode


	public function supprimeSignature($id) {

		$query_del = 'DELETE FROM `pe_nett_signatures` WHERE `id` = ' . (int)$id;
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();



	}

	// Enregistre & sauvegarde (Méthode Save)
	public function saveNettoyageSignatureResponsable(NettoyageSignature $objet) {

		$table      = 'pe_nett_signatures'; // Nom de la table
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