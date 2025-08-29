<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Commentaire
Généré par CBO FrameWork le 30/07/2019 à 17:41:17
------------------------------------------------------*/
class CommentairesManager {

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

	// Retourne la liste des Commentaire
	public function getListeCommentaires($params) {

		$id_lot 	= isset($params['id_lot']) 		? intval($params['id_lot']) 	: 0;
		$negoce 	= isset($params['negoce']) 		? intval($params['negoce']) 	: 0;
		$id_froid 	= isset($params['id_froid']) 	? intval($params['id_froid']) 	: 0;
		$id_froids 	= isset($params['id_froids']) 	? $params['id_froids'] 			: [];
		$incident 	= isset($params['incident']) 	? boolval($params['incident'] )	: false;

		// Si on a passé une liste d'ID froids sour forme d'array, on les prépare pour le IN(...)
		if (is_array($id_froids)) { $id_froids = implode(',', $id_froids);	}


		$query_liste = "SELECT `id`, `id_lot`, `id_froid`, `commentaire`, `date`, `id_user`, `incident`, `negoce`
							FROM `pe_commentaires`  ";

		$query_liste.= 	$id_lot > 0 ? ' WHERE `id_lot` = ' . $id_lot . ' ' : '';
		$query_liste.= 	$id_froid > 0 && $id_lot > 0 ? 'OR ' : '';
		$query_liste.= 	$id_froid > 0 && $id_lot == 0 ? 'WHERE ' : '';
		$query_liste.= 	$id_froid > 0 ? '`id_froid` = ' . $id_froid . ' ' : '';

		$query_liste.= 	$id_froids != '' && $id_lot == 0  && $id_froid == 0 ? ' WHERE ' : '';
		$query_liste.= 	$id_froids != '' && ($id_lot > 0  || $id_froid > 0) ? ' OR ' : '';

		$query_liste.= 	$id_froids != '' ? ' `id_froid` IN (' . $id_froids . ') ' : '';

		$query_liste.= 	$negoce > 0 ? 'AND `negoce` = ' . $negoce . ' ' : '';

		$query_liste.= 	($id_lot > 0 || $id_froid > 0 ) && $incident  ? ' AND `incident` = 1 ' : '';

		$query_liste.= "ORDER BY `id` ASC";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$_SESSION['pdoq']['CommentaireManager'][] = $query_liste;

		$liste = [];

		foreach ($donnees = $query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Commentaire($donnee);
		}
		return $liste;

	} // FIN liste des Commentaire


	// Retourne un Commentaire
	public function getCommentaire($id) {

		$query_object = "SELECT `id`, `id_lot`, `id_froid`, `commentaire`, `date`, `id_user`, `incident`, `negoce`
                FROM `pe_commentaires` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new Commentaire($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get Commentaire

	// Enregistre & sauvegarde (Méthode Save)
	public function saveCommentaire(Commentaire $objet) {

		$table      = 'pe_commentaires'; // Nom de la table
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

} // FIN classe