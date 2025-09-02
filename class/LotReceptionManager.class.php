<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Manager de l'Objet LotReception
------------------------------------------------------*/
class LotReceptionManager {

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

	public function setNb_results($nb_results) {
		$this->nb_results = (int)$nb_results;
	}
	
	/****************
	 * METHODES
	 ***************/

	// Retourne une reception par son ID
	public function getLotReception($id) {

		$query_reception = 'SELECT r.`id`, r.`id_lot`, r.`etat_visuel`, r.`conformite`, r.`observations`, r.`id_user`, r.`date_validation`, `temp`, `temp_d`, `temp_m`, `temp_f`, r.`id_transporteur`,
       							CONCAT(CONCAT(UCASE(LEFT(u.`prenom`, 1)),LCASE(SUBSTRING(u.`prenom`, 2))), " ", UCASE(u.`nom`)) as user_nom, r.`crochets_recus`, r.`crochets_rendus`,
       							IFNULL(t.`nom`, "") AS nom_transporteur
								FROM `pe_lot_reception` r
									LEFT JOIN `pe_users` u ON u.`id` = r.`id_user`
									LEFT JOIN `pe_tiers` t ON t.`id` = r.`id_transporteur`
							WHERE `id` = :id';
		$query = $this->db->prepare($query_reception);
		$query->bindValue(':id', (int)$id);
		$query->execute();

		$donnee = $query->fetch();

		return $donnee && !empty($donnee) ? new LotReception($donnee) : false;

	} // FIN méthode

	// Retourne une reception par l'ID du lot
	public function getLotReceptionByIdLot($id_lot) {

		$query_reception = 'SELECT r.`id`, r.`id_lot`, r.`etat_visuel`, r.`conformite`, r.`observations`, r.`id_user`, r.`date_confirmation`, `temp`, `temp_d`, `temp_m`, `temp_f`, 
       							r.`crochets_recus`, r.`crochets_rendus`, r.`id_transporteur`, IFNULL(t.`nom`, "") AS nom_transporteur,
       							CONCAT(CONCAT(UCASE(LEFT(u.`prenom`, 1)),LCASE(SUBSTRING(u.`prenom`, 2))), " ", UCASE(u.`nom`)) as user_nom 
								FROM `pe_lot_reception` r
									LEFT JOIN `pe_users` u ON u.`id` = r.`id_user`
									LEFT JOIN `pe_tiers` t ON t.`id` = r.`id_transporteur`
								WHERE r.`id_lot` = :id_lot';

		$query = $this->db->prepare($query_reception);
		$query->bindValue(':id_lot', (int)$id_lot);
		$query->execute();

		$donnee = $query->fetch();

		$reception = $donnee && !empty($donnee) ? new LotReception($donnee) : false;

		if (!$reception instanceof LotReception) { return false; }

		// On rattache les données de validation de l'administrateur
		$query_validation = 'SELECT v.`validation_date`,
       								IF (u.`prenom` IS NOT NULL, 
       								CONCAT(CONCAT(UCASE(LEFT(u.`prenom`, 1)),LCASE(SUBSTRING(u.`prenom`, 2))), " ", UCASE(u.`nom`)), "") as validateur_nom
       							FROM `pe_validations` v 
								JOIN `pe_users` u ON u.`id` = v.`validation_id_user`  
									WHERE v.`type` = 1 
									  AND v.`id_liaison` IN (SELECT id_lot FROM `pe_validations_lots` WHERE `id_lot` = '.(int)$id_lot.') ';

		$queryV = $this->db->prepare($query_validation);
		$queryV->execute();

		$donneeV = $queryV->fetch();

		$validation = $donneeV && !empty($donneeV) ? $donneeV : [];

		$rcp_validation = !empty($validation) &&  isset($validation['validateur_nom']) && isset($validation['validation_date']) ? 1 : 0;
		$rcp_validateur = isset($validation['validateur_nom']) ? $validation['validateur_nom'] : '';
		$rcp_validation_date = isset($validation['validation_date']) ? $validation['validation_date'] : '';

		$reception->setValidateur_nom($rcp_validateur);
		$reception->setValidation($rcp_validation);
		$reception->setValidation_date($rcp_validation_date);

		return $reception;

	} // FIN méthode


	// Retourne la liste des réceptions
	public function getListeLotReception($params) {

		$id_lot = isset($params['id_lot']) ? intval($params['id_lot']) : 0;

		$query_liste = 'SELECT r.`id`, r.`id_lot`, r.`etat_visuel`, r.`conformite`, r.`observations`, r.`id_user`, r.`date_validation`, `temp`, `temp_d`, `temp_m`, `temp_f`, 
       							r.`crochets_recus`, r.`crochets_rendus`, r.`id_transporteur`, IFNULL(t.`nom`, "") AS nom_transporteur,
       							CONCAT(CONCAT(UCASE(LEFT(u.`prenom`, 1)),LCASE(SUBSTRING(u.`prenom`, 2))), " ", UCASE(u.`nom`)) as user_nom
								FROM `pe_lot_reception` r
									LEFT JOIN `pe_users` u ON u.`id` = r.`id_user`
									LEFT JOIN `pe_tiers` t ON t.`id` = r.`id_transporteur`
						WHERE 1 ';

		$query_liste.= $id_lot > 0 ? 'AND r.`id_lot` = ' .$id_lot . ' ' : '';

		$query_liste.= 'ORDER BY r.`id_lot` DESC';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new LotReception($donnee);
		}
		return $liste;

	} // FIN getListe

	
	// Enregistre une reception de lot
	public function saveLotReception(LotReception $objet) {
		
		$table		= 'pe_lot_reception';	// Nom de la table
		$champClef	= 'id';						// Nom du champ clef primaire
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

	// Retourne une reception par l'ID du lot
	public function getLotReceptionByIdLotNegoce($id_lot_negoce) {

		$query_reception = 'SELECT r.`id`, r.`id_lot_negoce`, r.`etat_visuel`, r.`conformite`, r.`observations`, r.`id_user`, r.`date_confirmation`, `temp`, `temp_d`, `temp_m`, `temp_f`, 
       							r.`crochets_recus`, r.`crochets_rendus`, r.`id_transporteur`, IFNULL(t.`nom`, "") AS nom_transporteur,
       							CONCAT(CONCAT(UCASE(LEFT(u.`prenom`, 1)),LCASE(SUBSTRING(u.`prenom`, 2))), " ", UCASE(u.`nom`)) as user_nom 
								FROM `pe_lot_reception` r
									LEFT JOIN `pe_users` u ON u.`id` = r.`id_user`
									LEFT JOIN `pe_tiers` t ON t.`id` = r.`id_transporteur`
								WHERE r.`id_lot_negoce` = :id_lot_negoce';

		$query = $this->db->prepare($query_reception);
		$query->bindValue(':id_lot_negoce', (int)$id_lot_negoce);
		$query->execute();

		$donnee = $query->fetch();

		$reception = $donnee && !empty($donnee) ? new LotReception($donnee) : false;

		if (!$reception instanceof LotReception) { return false; }

		// On rattache les données de validation de l'administrateur
		$query_validation = 'SELECT v.`validation_date`,
       								IF (u.`prenom` IS NOT NULL, 
       								CONCAT(CONCAT(UCASE(LEFT(u.`prenom`, 1)),LCASE(SUBSTRING(u.`prenom`, 2))), " ", UCASE(u.`nom`)), "") as validateur_nom
       							FROM `pe_validations` v 
								JOIN `pe_users` u ON u.`id` = v.`validation_id_user`  
									WHERE v.`type` = 4 
									  AND v.`id_liaison_negoce` IN (SELECT id_lot_negoce FROM `pe_validations_lots` WHERE `id_lot_negoce` = '.(int)$id_lot_negoce.') ';

		$queryV = $this->db->prepare($query_validation);
		$queryV->execute();

		$donneeV = $queryV->fetch();

		$validation = $donneeV && !empty($donneeV) ? $donneeV : [];

		$rcp_validation = !empty($validation) &&  isset($validation['validateur_nom']) && isset($validation['validation_date']) ? 1 : 0;
		$rcp_validateur = isset($validation['validateur_nom']) ? $validation['validateur_nom'] : '';
		$rcp_validation_date = isset($validation['validation_date']) ? $validation['validation_date'] : '';

		$reception->setValidateur_nom($rcp_validateur);
		$reception->setValidation($rcp_validation);
		$reception->setValidation_date($rcp_validation_date);

		return $reception;

	} // FIN méthode

} // FIN classe