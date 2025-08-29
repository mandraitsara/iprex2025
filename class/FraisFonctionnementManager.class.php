<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet FraisFonctionnement
Généré par CBO FrameWork le 28/10/2020 à 10:32:11
------------------------------------------------------*/
class FraisFonctionnementManager {

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

	// Retourne la liste des FraisFonctionnement
	public function getListeFraisFonctionnement($params = []) {

		$start = isset($params['start'])          ? intval($params['start'])          : 0;
		$nb    = isset($params['nb_result_page']) ? intval($params['nb_result_page']) : 10000000;

		$query_liste = "SELECT SQL_CALC_FOUND_ROWS `id`, `montant`, `periodicite`, `nom`, `activation`, `supprime` FROM `pe_frais_fonctionnement` WHERE `supprime` = 0 
            ORDER BY `id` DESC ";  $query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new FraisFonctionnement($donnee);
		}
		return $liste;

	} // FIN liste des FraisFonctionnement


	// Retourne un FraisFonctionnement
	public function getFraisFonctionnement($id) {

		$query_object = "SELECT `id`, `montant`, `periodicite`, `nom`, `activation`, `supprime`
                FROM `pe_frais_fonctionnement` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee) ? new FraisFonctionnement($donnee) : false;

	} // FIN get FraisFonctionnement

	// Enregistre & sauvegarde (Méthode Save)
	public function saveFraisFonctionnement(FraisFonctionnement $objet) {

		$table      = 'pe_frais_fonctionnement'; // Nom de la table
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


	// Retourne le total des frais de fonctionnement sur une période
	function getTotalFraisFonctionnementPeriode($date_du, $date_au, $mois, $annee) {

		// Si par dates bornées
		if (intval($annee) == 0) {
			$du = Outils::verifDateSql($date_du) ? $date_du : Outils::dateFrToSql($date_du);
			$au = Outils::verifDateSql($date_au) ? $date_au : Outils::dateFrToSql($date_au);
		// Si par mois/an
		} else if (intval($mois) > 0) {
			$du = $annee.'-'.$mois.'-01';
			$au = date('Y-m-t', strtotime($du));
		// Si par annnée
		} else {
			$du = $annee.'-01-01';
			$au = $annee.'-12-31';
		} // Fin récup période

		// Valeur par défaut au jour
		if ($du == '') { $du = date('Y-m-d'); }
		if ($au == '') { $au = date('Y-m-d'); }

		// Nb de jours pour la période
		$date1 = new DateTime($du);
		$date2 = new DateTime($au);
		$jours = intval($date2->diff($date1)->format("%a")) + 1;

		$ff = 0;
		$liste = $this->getListeFraisFonctionnement();
		if (!is_array($liste) || empty($liste)) { return $ff; }

		// Boucle sur les frais
		foreach ($liste as $frais) {
			$ff+= ($frais->getMontant() * $jours) / FraisFonctionnement::PERIODICITE_JOURS[$frais->getPeriodicite()];
		} // FIn boucle sur les frais

		return $ff;

	} // FIN méthode

} // FIN classe