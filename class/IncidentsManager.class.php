<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Manager de l'Objet Incident
------------------------------------------------------*/
class IncidentsManager {

	protected	$db;

	public function __construct($db) {
		$this->setDb($db);
	}
	
	//##### GETTERS #####
	public function getNb_results() {
		return $this->nb_results;
	}
	
	//##### SETTERS #####
	public function setDb(PDO $db) {
		$this->db = $db;
	}
	
	/****************
	 * METHODES
	 ***************/

	// Retourne un Incident par son ID
	public function getIncident($id) {

		$query_incident = 'SELECT i.`id`, i.`id_lot`, i.`type_incident`, i.`date`, i.`id_user`, CONCAT(u.`prenom`, " ", u.`nom`) AS nom_user, t.`nom` AS nom_type_incident, i.`negoce`
										FROM `pe_incidents` i
											JOIN `pe_users` u ON u.`id` = i.`id_user`
											JOIN `pe_incidents_types` t ON t.`id` = i.`type_incident`
									WHERE i.`id` = :id';
		$query = $this->db->prepare($query_incident);
		$query->bindValue(':id', (int)$id);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && !empty($donnee) ? new Incident($donnee) : false;

	} // FIN méthode

	// Retourne un type Incident par son ID
	public function getTypeIncident($id) {

		$query_type = 'SELECT `id`, `nom`, `actif`
								FROM `pe_incidents_types` 
							WHERE `id` = :id';
		$query = $this->db->prepare($query_type);
		$query->bindValue(':id', (int)$id);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && !empty($donnee) ? new IncidentType($donnee) : false;

	} // FIN méthode


	// Retourne la liste des Incidents
	public function getIncidentsListe($params) {

		$negoce = isset($params['negoce']) 	? intval($params['negoce']) : 0;
		$id_lot = isset($params['id_lot']) 	? intval($params['id_lot']) : 0;
		$type 	= isset($params['type']) 	? intval($params['type']) 	: 0;
		$id_lot_negoce = isset($params['id_lot_negoce']) 	? intval($params['id_lot_negoce']) : 0;

		$query_liste = 'SELECT i.`id`, i.`id_lot`, i.`id_lot_negoce`, i.`type_incident`, i.`date`, i.`id_user`, CONCAT(u.`prenom`, " ", u.`nom`) AS nom_user, t.`nom` AS nom_type_incident, i.`negoce`
										FROM `pe_incidents` i
											JOIN `pe_users` u ON u.`id` = i.`id_user`
											JOIN `pe_incidents_types` t ON t.`id` = i.`type_incident`
									WHERE  1  ';
		$query_liste.= $id_lot > 0 ? 'AND  i.`id_lot` = ' . $id_lot : '';
		$query_liste.= $type > 0 ? ' AND  i.`type_incident` = ' . $type : '';
		$query_liste.= $id_lot_negoce > 0 ? ' AND  i.`id_lot_negoce` = ' . $id_lot_negoce : '';		
		$query_liste.= ' ORDER BY i.`date` ASC';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new Incident($donnee);
		}

		return $liste;

	} // FIN getListe

	// Retourne la liste des Incidents
	public function getListeTypesIncidents($params) {

		$show_inactifs = isset($params['show_inactifs']) 	? boolval($params['show_inactifs']) : false;

		$query_liste = 'SELECT `id`, `nom`, `actif`
							FROM `pe_incidents_types` t
								WHERE  1 ';
		$query_liste.= !$show_inactifs ? 'AND  `actif` = 1 ' : '';
		$query_liste.= ' ORDER BY `nom` ASC';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new IncidentType($donnee);
		}

		return $liste;

	} // FIN getListe


	// Retourne le nombre d'incident par type
	public function getNbIncidentsByType(IncidentType $incidentType) {

		$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_incidents` WHERE `type_incident` = ' . $incidentType->getId();
		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);

		return $donnee && isset($donnee[0]) && isset($donnee[0]['nb'])  ?  intval($donnee[0]['nb']) : 0;

	} // FIN méthode

	
	// Enregistre un nouvel Incident
	public function saveIncident(Incident $objet) {
		
		$table		= 'pe_incidents';	// Nom de la table
		$champClef	= 'id';				// Nom du champ clef primaire
		// FIN Configuration

		$getter		= 'get'.ucfirst(strtolower($champClef));
		$setter		= 'set'.ucfirst(strtolower($champClef));

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


	// Enregistre un Type d'incident
	public function saveTypeIncident(IncidentType $objet) {

		$table		= 'pe_incidents_types';	// Nom de la table
		$champClef	= 'id';				// Nom du champ clef primaire
		// FIN Configuration

		$getter		= 'get'.ucfirst(strtolower($champClef));
		$setter		= 'set'.ucfirst(strtolower($champClef));

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


	// Supprime un Incident
	public function supprimeIncident(Incident $incident) {

		$query_del = 'DELETE FROM `pe_incidents` WHERE `id` = ' . (int)$incident->getId();
		$query = $this->db->prepare($query_del);

		if ($query->execute()) {
			Outils::saveLog($query_del);
			$query_del2 = 'DELETE FROM `pe_commentaires` WHERE `incident` = 1 AND `id_lot` =  ' . (int)$incident->getId_lot();
			$query2 = $this->db->prepare($query_del2);
			Outils::saveLog($query_del2);
			return $query2->execute();
		}

		return true;

	} // FIN méthode

	// Retourne si un lot a au moins un un commentaire d'incident
	public function asLotIncidentsCommentaire($id_lot) {

		$negoce = 0;
		if (substr($id_lot,0,1) == 'N') {
			$id_lot = str_replace('N', '', $id_lot);
			$negoce = 1;
		}

		$query_nb = 'SELECT COUNT(*) AS nb  FROM `pe_commentaires` WHERE `incident` = 1  AND `id_lot` = ' . intval($id_lot) . ' AND `negoce` = ' .$negoce;
		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetchAll(PDO::FETCH_ASSOC);

		return $donnee && isset($donnee[0]) && isset($donnee[0]['nb']) && intval($donnee[0]['nb']) > 0 ? true: false;

	} // FIN méthode

	// Retourne les ID de lots d'un traitement froid pour lequel il y a des commentaires d'incidentp
	public function getLotsIncidentsByFroid(Froid $froid) {

		$query_liste = 'SELECT DISTINCT `id_lot` FROM `pe_commentaires` WHERE `incident` = 1  AND `negoce` = 0 AND `id_lot` IN (SELECT `id_lot` FROM `pe_froid_produits` WHERE `id_froid` = ' . $froid->getId() . ' )';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = intval($donnee['id_lot']);
		}

		return $liste;

	} // FIN méthode

	// Supprime un type d'incident
	public function supprimeTypeIncident(IncidentType $incidentType) {

		$query_del = 'DELETE FROM `pe_incidents_types` WHERE `id` = ' . (int)$incidentType->getId();
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode

} // FIN classe