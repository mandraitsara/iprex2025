<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet PoidsPalette
Généré par CBO FrameWork le 18/06/2020 à 10:20:32
------------------------------------------------------*/
class PoidsPaletteManager {

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

	// Retourne la liste des PoidsPalette
	public function getListePoidsPalettes($type = -1) {

		$query_liste = "SELECT `id`, `nom`, `poids`, `type`  FROM `pe_poids_palettes` WHERE 1 ";
		$query_liste.= (int)$type > -1 ? ' AND `type` = ' . (int)$type : '';
		$query_liste.= " ORDER BY `type`, `nom` ";

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new PoidsPalette($donnee);
		}
		return $liste;

	} // FIN liste des PoidsPalette


	// Retourne un PoidsPalette
	public function getPoidsPalette($id) {

		$query_object = "SELECT `id`, `nom`, `poids`, `type`
                FROM `pe_poids_palettes` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new PoidsPalette($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get PoidsPalette

	// Enregistre & sauvegarde (Méthode Save)
	public function savePoidsPalette(PoidsPalette $objet) {

		$table      = 'pe_poids_palettes'; // Nom de la table
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

	// Supprime un poids palette et met à jour les produits associés
	public function deletePoidsPalette(PoidsPalette $poidsPalette) {

		$query_del = 'DELETE FROM `pe_poids_palettes` WHERE `id` = ' . $poidsPalette->getId();
		$query = $this->db->prepare($query_del);
		if (!$query->execute()) { return false; }
		Outils::saveLog($query_del);
		$query_upd = 'UPDATE `pe_produits` SET `id_poids_palette` = 0 WHERE `id_poids_palette` = ' . $poidsPalette->getId();
		$query2 = $this->db->prepare($query_upd);
		$query2->execute();
		Outils::saveLog($query_upd);
		return true;

	} // FIN méthode

	// Retourne les poids palettes d'une palette
	public function getPalettePoidsPaletteByPalette($id_palette) {

		$query_liste = 'SELECT `id_poids_palette`, `qte` FROM `pe_palette_poids_palettes` WHERE `id_palette` = ' . (int)$id_palette;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			if (intval($donnee['qte']) == 0) { continue; }
			$liste[(int)$donnee['id_poids_palette']] = intval($donnee['qte']);
		}
		return $liste;

	} // FIN méthode

	// Retourne le poids total d'une palette (tous types)
	public function getTotalPoidsPalette($id_palette) {

		$query_poids = 'SELECT (SELECT `poids` FROM `pe_poids_palettes` WHERE `type` = 1 AND `id` = (SELECT `id_poids_palette` FROM `pe_palettes` WHERE `id` = '.(int)$id_palette.')) +
						(SELECT IFNULL(SUM(pp.`poids` * ppp.`qte`),0) FROM `pe_poids_palettes` pp 
						JOIN `pe_palette_poids_palettes` ppp ON ppp.`id_poids_palette` = pp.`id`
						WHERE ppp.`id_palette` = '.(int)$id_palette . ') AS poids';

		$query = $this->db->prepare($query_poids);
		$query->execute();
		$donnee = $query->fetch();
		return !$donnee || empty($donnee) ? 0 : floatval($donnee['poids']);

	} // FIN méthode

} // FIN classe