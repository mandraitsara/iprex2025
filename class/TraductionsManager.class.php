<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Traduction
Généré par CBO FrameWork le 06/03/2020 à 15:35:37
------------------------------------------------------*/
class TraductionsManager {

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

	// Retourne la liste des Traduction
	public function getListeTraductions($params = []) {

		$langue_active = isset($params['langue_active']) ? boolval($params['langue_active']) : true;

		$query_liste = "SELECT t.`id`, t.`zone`, t.`id_langue`, t.`texte`, l.`iso`, t.`scope`
							FROM `pe_traductions` t
							JOIN `pe_langues` l ON l.`id` =  t.`id_langue` AND l.`supprime` = 0 
							WHERE 1 ";

		$query_liste.= $langue_active ? " AND l.`actif` = 1 " : "";

		$query_liste.= "	ORDER BY t.`scope`, t.`zone`, t.`id_langue`";
		$query = $this->db->prepare($query_liste);
		$query->execute();
		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Traduction($donnee);
		}
		return $liste;

	} // FIN liste des Traduction


	// Retourne un Traduction
	public function getTraduction($id) {

		$query_object = "SELECT `id`, `zone`, `id_langue`, `texte`, `scope`
                FROM `pe_traductions` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new Traduction($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get Traduction

	// Enregistre & sauvegarde (Méthode Save)
	public function saveTraduction(Traduction $objet) {

		$table      = 'pe_traductions'; // Nom de la table
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

	// Retourne les traductions sous forme d'objets consultables pour les docs
	public function getObjetTraductions() {

		// format de sortie : $objet->code->iso
		// Retourne NULL si n'existe pas

		$traductions = [];

		foreach ($this->getListeTraductions() as $trad) {

			if (!isset($traductions[$trad->getScope()])) {
				$traductions[$trad->getScope()] = [];
			}

			if (!isset($traductions[$trad->getScope()][$trad->getZone()])) {
				$traductions[$trad->getScope()][$trad->getZone()] = [];
			}

			if (!isset($traductions[$trad->getScope()][$trad->getZone()][$trad->getIso()])) {
				$traductions[$trad->getScope()][$trad->getZone()][$trad->getIso()] = '';
			}

			$traductions[$trad->getScope()][$trad->getZone()][$trad->getIso()] = $trad->getTexte();
		}

		return json_decode(json_encode($traductions), false);

	} // FIN méthode

	// Enregistre les traductions
	public function saveTrad($code_zone, $langue_id,  $trad_texte, $scope = 'g') {

		$query_save = 'INSERT INTO `pe_traductions` (`zone`, `id_langue`, `texte`, `scope`) VALUES (:zone, :id_langue , :texte, :scope) ON DUPLICATE KEY UPDATE `texte` = :texte ';
		$query = $this->db->prepare($query_save);
		$query->bindValue(':zone', strtolower(trim(str_replace(' ', '_', $code_zone))));
		$query->bindValue(':id_langue', (int)$langue_id);
		$query->bindValue(':texte', trim($trad_texte));
		$query->bindValue(':scope', trim($scope));

		$query_log = str_replace(':zone', strtolower(trim(str_replace(' ', '_', $code_zone))),$query_save);
		$query_log = str_replace(':id_langue', $langue_id,$query_log);
		$query_log = str_replace(':texte', trim($trad_texte),$query_log);
		$query_log = str_replace(':scope', trim($scope),$query_log);
		Outils::saveLog($query_log);

		return $query->execute();

	} // FIN méthode


	// Retourne un bloc traduit
	public function getTrad($bloc, $id_langue = 1) {

		$query_trad = 'SELECT `texte` FROM `pe_traductions` WHERE `id_langue` = ' . (int)$id_langue . ' AND `zone` = "'.trim(strtolower($bloc)).'"';
		$query = $this->db->prepare($query_trad);
		$query->execute();
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee['texte']) ? $donnee['texte'] : '';

	} // FIN méthode

} // FIN classe