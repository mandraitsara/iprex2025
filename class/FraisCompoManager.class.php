<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet FraisCompo
Généré par CBO FrameWork le 27/02/2020 à 16:21:17
------------------------------------------------------*/
class FraisCompoManager {

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

	// Retourne la liste des FraisCompo
	public function getListeFraisCompos() {

		$query_liste = "SELECT `id`, `id_compo`, `id_lot`, `quantieme`, `dlc` FROM `pe_frais_compos` ORDER BY `id_compo` DESC";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$_SESSION['pdoq']['FraisCompoManager'][] = $query_liste;

		$liste = [];

		foreach ($donnees = $query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new FraisCompo($donnee);
		}
		return $liste;

	} // FIN liste des FraisCompo


	// Retourne un FraisCompo
	public function getFraisCompo($id) {

		$query_object = "SELECT `id`, `id_compo`, `id_lot`, `quantieme`, `dlc` 
                FROM `pe_frais_compos` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new FraisCompo($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get FraisCompo

	// Retourne un FraisCompo
	public function getFraisCompoFromIdCompo($id_compo) {

		$query_object = "SELECT `id`, `id_compo`, `id_lot`, `quantieme`, `dlc` 
                FROM `pe_frais_compos` WHERE `id_compo` = " . (int)$id_compo;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new FraisCompo($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get FraisCompo



	// Enregistre & sauvegarde (Méthode Save)
	public function saveFraisCompo(FraisCompo $objet) {

		$table      = 'pe_frais_compos'; // Nom de la table
		$champClef  = 'id'; // Nom du champ clef

		// FIN Configuration

		$getter     = 'get'.ucfirst(strtolower($champClef));
		$setter     = 'set'.ucfirst(strtolower($champClef));

		if ($objet->$getter() == '' && !empty($objet->attributs)) {

			$query_add = 'INSERT INTO '.$table.' (';

			foreach ($objet->attributs as $attribut)	{
				$query_add.= strtoupper($attribut).',';
			}
			$query_add = substr($query_add,0,-1);
			$query_add.=') VALUES (';

			foreach ($objet->attributs as $attribut)	{
				$query_add.= ':'.strtolower($attribut).',';
			}
			$query_add = substr($query_add,0,-1);
			$query_add.=')';

			$query = $this->db->prepare($query_add);

			foreach ($objet->attributs as $attribut)	{
				$attributget = 'get'.ucfirst($attribut);
				$query->bindvalue(':'.strtolower($attribut), $objet->$attributget());
			}

			if ($query->execute()) {
				$_SESSION['pdoq']['FraisCompoManager'][] = $query_add;
				$objet->$setter($this->db->lastInsertId());
				return $objet->$getter();
			}

		} else if ($objet->$getter() != '' && !empty($objet->attributs)) {

			$query_upd = 'UPDATE '.$table.' SET ';

			foreach($objet->attributs as $attribut) {
				$query_upd.= strtoupper($attribut).' = :'.strtolower($attribut).',';
			}
			$query_upd = substr($query_upd,0,-1);
			$query_upd .= ' WHERE '.$champClef.' = '.$objet->$getter();

			$query = $this->db->prepare($query_upd);

			foreach($objet->attributs as $attribut) {
				$attributget = 'get'.ucfirst($attribut);
				$query->bindvalue(':'.strtolower($attribut), $objet->$attributget());
			}

			try	{
				$query->execute();
				$_SESSION['pdoq']['FraisCompoManager'][] = $query_upd;
				return true;
			} catch(PDOExeption $e) {return false;}
		}
		return false;

	} // FIN méthode


	// On retourne un objet FraisCompo si on le retrouve par ses détails de compo, pour regrouper les données d'une compo déja existante
	public function getCompoFraisDeja($id_pdt, $id_palette, $dlc, $poids, $id_lot, $quantieme) {

		$query_object = 'SELECT fc.`id`, fc.`id_compo`, fc.`id_lot`, fc.`quantieme`, fc.`dlc` 
			                FROM `pe_frais_compos` fc
							JOIN `pe_palette_composition` pc ON pc.`id` = fc.`id_compo`
							WHERE pc.`supprime` = 0 
							  AND pc.`frais` = 1 
							  AND pc.`id_produit` = '.$id_pdt.'
							  AND pc.`id_palette` = '.$id_palette.'
							  AND fc.`dlc` = "'.$dlc.'"
							  AND pc.`poids` = '.$poids.'
							  AND fc.`id_lot` = '.$id_lot.'
							  AND fc.`quantieme` = "'.$quantieme.'" ';

		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new FraisCompo($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN méthode

} // FIN classe