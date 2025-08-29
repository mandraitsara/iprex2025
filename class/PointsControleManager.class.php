<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet PointControle
Généré par CBO FrameWork le 03/09/2020 à 11:08:50
------------------------------------------------------*/
class PointsControleManager {

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

	// Retourne le nb de points de controles actifs
	public function getNbPointsControlesActifs($doc = 0) {

		$id_parent = $doc > 0 ? -1 : 0;

		$query_nb = "SELECT COUNT(*) AS nb 
							FROM `pe_points_controle` 
								WHERE `supprime` = 0 AND `id_parent` > ".$id_parent." AND `activation` = 1 AND `doc` = " . (int)$doc;
		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetch();
		return $donnee && !empty($donnee) ? intval($donnee['nb']) : 0;

	}

	// Retourne la liste des PointControle
	public function getListePointsControles($show_inactifs = false, $doc = 0) {

		$query_liste = "SELECT `id`, `nom`, `id_parent`, `position`, `doc`, `activation`, `supprime`
							FROM `pe_points_controle` 
								WHERE `supprime` = 0 AND `id_parent` = 0 AND `doc` = " . (int)$doc;


		$query_liste.= !$show_inactifs ? ' AND `activation` = 1 ' : '';
		$query_liste.= "				ORDER BY `position` ";

		$query = $this->db->prepare($query_liste);
		$query->execute();



		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$parent = new PointControle($donnee);
			$liste[] = $parent;

			$enfants = $this->getListeEnfantsPoint($parent);
			if (empty($enfants) || !$enfants) { continue; }
			foreach ($enfants as $enfant) {
				if (!$enfant instanceof PointControle) {
					continue;
				}
				$liste[] = $enfant;
			}
		}
		return $liste;

	} // FIN liste des PointControle

	// Retourne la lsite des points de controles enfants d'un parent
	public function getListeEnfantsPoint(PointControle $parent) {

		$query_liste = "SELECT `id`, `nom`, `id_parent`, `position`, `doc`, `activation`, `supprime`
							FROM `pe_points_controle` 
								WHERE `supprime` = 0 AND `id_parent` = ".(int)$parent->getId()."
							ORDER BY `position` ";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new PointControle($donnee);
		}

		return $liste;

	} // FIN méthode


	// Retourne un PointControle
	public function getPointControle($id) {

		$query_object = "SELECT `id`, `nom`, `id_parent`, `position`, `doc`, `activation`, `supprime` 
                FROM `pe_points_controle` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new PointControle($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get PointControle

	// Enregistre & sauvegarde (Méthode Save)
	public function savePointControle(PointControle $objet) {

		$table      = 'pe_points_controle'; // Nom de la table
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

	// Retourne la liste des points de contrôles parents
	public function getPointsParents($doc = 0) {

		$query_liste = "SELECT `id`, `nom`, `id_parent`, `position`, `doc`, `activation`, `supprime` 
							FROM `pe_points_controle` 
								WHERE `supprime` = 0 
								  AND `activation` = 1
								  AND `id_parent` = 0
								  AND `doc` = ".(int)$doc."
							ORDER BY `position`";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new PointControle($donnee);
		}
		return $liste;

	} // FIN méthode

	// Retourne la position suivante
	public function getNextPosition($id_parent = 0, $doc = 0) {

		$query_pos = 'SELECT MAX(`position`) + 1 AS pos FROM `pe_points_controle` WHERE `id_parent` = ' . (int)$id_parent . ' AND `doc` = ' . (int)$doc;
		$query = $this->db->prepare($query_pos);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && isset($donnee['pos']) ? intval($donnee['pos']) : 1;

	} // FIN méthode


	// Change la position d'un point
	public function movePositionPoint($id_parent, $old_position, $new_position) {

		$query_upd = 'UPDATE `pe_points_controle` SET `position` = ' . (int)$new_position . ' WHERE `position` = ' . (int)$old_position . ' AND `id_parent` = ' . (int)$id_parent;

		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();

	} // FIN méthode

} // FIN classe