<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Manager de l'Objet Validation
------------------------------------------------------*/
class ValidationManager {

	protected	$db, $nb_results;

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
	// Retourne une validation par son ID
	public function getValidation($id) {
		$query_validation = 'SELECT v.`id`, v.`id_vue`, v.`type`, v.`id_liaison`, v.`validation_id_user`, v.`validation_date`, CONCAT(u.`prenom`, " ", u.`nom`) AS nom_controleur, v.`courbe_temp`,
       					IF (v.`type` = 3, l.`date_test`, 
       					  IF (v.`type` = 2, f.`date_controle`,  IF (v.`type` = 4, lotn.`date_maj`,  r.`date_confirmation`)))
       					  AS date,
       					IF (v.`type` = 3,
       					     (IF ((l.`test_pdt`) = 1, 0,
       						  IF (l.`test_pdt` < 0 , -1, 1))), 
       					  IF (v.`type` = 2, f.`conformite`, IF (v.`type` = 4, 1, r.`conformite`)))
       					  AS resultat,
       					IF (v.`type` = 1, lots.`poids_reception`, 0) AS poids
							FROM `pe_validations` v
							LEFT JOIN `pe_loma` l ON l.`id` = v.`id_liaison`
							LEFT JOIN `pe_froid` f ON f.`id` = v.`id_liaison`
							LEFT JOIN `pe_lots` lots ON lots.`id` = v.`id_liaison`
							LEFT JOIN `pe_lot_reception` r ON r.`id_lot` = v.`id_liaison`
							LEFT JOIN `pe_lots_negoce` lotn ON lotn.`id` = v.`id_liaison` 
							LEFT JOIN `pe_users` u ON u.`id` = (  
							  IF (v.`type` = 3, l.`id_user_visa`,
							    IF (v.`type` = 2, f.`id_visa_controleur`, r.`id_user`)) 
							)
						  WHERE v.`id` = ' . $id;
		$query = $this->db->prepare($query_validation);
		$query->execute();

		$donnee = $query->fetch();
		if (!$donnee || empty($donnee)) {
			return false;
		}
		$val = new Validation($donnee);

		// On intègre les objets Lot
		$lotManager = new LotManager($this->db);
		$query_lots = 'SELECT `id_lot` FROM `pe_validations_lots` WHERE `id_validation` = '. $val->getId();
		$queryL = $this->db->prepare($query_lots);
		// Log de la requête pour le mode Dev
		if (isset($_SESSION['devmode']) && $_SESSION['devmode']) { $_SESSION['pdoq'][get_class($this)][] = $queryL->queryString; }
		$queryL->execute();
		$listeLots = [];
		foreach ($queryL->fetchAll(PDO::FETCH_ASSOC) as $donneeLot) {
			$lotTemp = $lotManager->getLot($donneeLot['id_lot']);
			if ($lotTemp instanceof Lot) {
				$listeLots[] = $lotTemp;
			}
		}

		$val->setLots($listeLots);

		// On intègre l'objet vue
		$vueManager = new VueManager($this->db);
		$vue = $vueManager->getVue($val->getId_vue());
		if ($vue instanceof Vue) { $val->setVue($vue); }
		// Si type 1, on hydrate l'objet Détail Réception
		if ($val->getType() == 1) {
			$receptionManager = new LotReceptionManager($this->db);
			$val->setReception($receptionManager->getLotReceptionByIdLot($val->getId_liaison()));
		}
		// Si type 3, on hydrate l'objet Loma
		if ($val->getType() == 3) {
			$lomaManager = new LomaManager($this->db);
			$val->setLoma($lomaManager->getLoma($val->getId_liaison()));
		}
		// // Si type 4, on hydrate l'objet LotNegoce
		// if  ($val->getType() == 4) {
		// 	$lotNegoceManager = new LotNegoceManager($this->db);
		// 	$val->setLot_negoce($lotNegoceManager->getLotNegoce($val->getId_liaison()));
		// }
		return $val;
	} // FIN méthode

	// Retourne la liste des validations
	public function getListeValidations($justeNonValidees = true) {
		$query_liste = 'SELECT v.`id`, v.`id_vue`, v.`type`, v.`id_liaison`, v.`validation_id_user`, v.`validation_date`, CONCAT(u.`prenom`, " ", u.`nom`) AS nom_controleur, v.`courbe_temp`,
       					IF (v.`type` = 3, l.`date_test`, 
       					  IF (v.`type` = 2, f.`date_controle`, 
       					         IF (v.`type` = 4, lotn.`date_maj`, 
       					      r.`date_confirmation`)))
       					  AS date,
       					IF (v.`type` = 3, 
       					  (IF ((l.`test_pdt`) = 1, 0,
       						  IF (l.`test_pdt` < 0 , -1, 1))), 
       					  IF (v.`type` = 2, f.`conformite`,
       					      IF (v.`type` = 4, 1, 
       					      r.`conformite`)))
       					  AS resultat,
       					IF (v.`type` = 1, lots.`poids_reception`, 0) AS poids
							FROM `pe_validations` v
							LEFT JOIN `pe_loma` l ON l.`id` = v.`id_liaison`
							LEFT JOIN `pe_froid` f ON f.`id` = v.`id_liaison`
							LEFT JOIN `pe_lot_reception` r ON r.`id_lot` = v.`id_liaison`
							LEFT JOIN `pe_lots` lots ON lots.`id` = v.`id_liaison` 
							LEFT JOIN `pe_lots_negoce` lotn ON lotn.`id` = v.`id_liaison`
							LEFT JOIN `pe_users` u ON u.`id` = (
							  IF (v.`type` = 3, l.`id_user_visa`,
							    IF (v.`type` = 2, f.`id_visa_controleur`, r.`id_user`)) 
							)';
		$query_liste.= $justeNonValidees ? 'WHERE (
						v.`validation_id_user` IS NULL 
					    OR v.`validation_id_user` = 0 
					    OR v.`validation_date` IS NULL
						OR v.`validation_date` = "0000-00-00 00:00:00")
						AND v.`type` != 4':'';
						
		$query_liste.= '
						ORDER BY v.`id` DESC ';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];
		$lotManager = new LotManager($this->db);
		$vueManager = new VueManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$val = new Validation($donnee);

			// On intègre les objets Lot
			$query_lots = 'SELECT `id_lot` FROM `pe_validations_lots` WHERE `id_validation` = '. $val->getId();
			$queryL = $this->db->prepare($query_lots);

			$queryL->execute();
			$listeLots = [];
			foreach ($queryL->fetchAll(PDO::FETCH_ASSOC) as $donneeLot) {
				$lotTemp = $lotManager->getLot($donneeLot['id_lot']);
				if ($lotTemp instanceof Lot) {
					$listeLots[] = $lotTemp;
				}
			}
			$val->setLots($listeLots);
			// On intègre l'objet vue
			$vue = $vueManager->getVue($val->getId_vue());
			if ($vue instanceof Vue) { $val->setVue($vue); }
			$liste[] =  $val;
		}
		return $liste;

	} // FIN getListe

	
	// Enregistre une nouvelle validation
	public function saveValidation(Validation $objet) {
		
		$table		= 'pe_validations';	// Nom de la table
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


	// Retourne le nombre de lots à valider par les responsables
	public function getNbAValider() {

		$query_nb = 'SELECT COUNT(*) AS nb
		FROM `pe_validations`
		WHERE (`validation_id_user` IS NULL OR `validation_id_user` = 0 OR `validation_date` IS NULL OR `validation_date` = "0000-00-00 00:00:00")
		  AND `type` != 4';
		// Requête
		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetch();
		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;
	} // FIN méthode

	// Valide tout
	public function validerTout(User $user) {

		$query_upd = 'UPDATE `pe_validations` SET `validation_id_user` = ' . $user->getId() . ', `validation_date` = "' . date('Y-m-d H:i:s') . '" WHERE `validation_id_user` IS NULL AND `validation_date` IS NULL AND `type`!=4';
		$query = $this->db->prepare($query_upd);

		Outils::saveLog($query_upd);

		return $query->execute();

	} // FIN méthode

	

	// Associe les lots à une validation
	public function addValidationLot(Validation $val, $lots, $lot_negoce = false) {

		$negoce = $lot_negoce ? 1 : 0;

		$query_add = 'INSERT IGNORE INTO `pe_validations_lots` (`id_validation`, `id_lot`, `negoce`) VALUES ';
		if (!is_array($lots)) {
			$id_lot = $lots instanceof Lot ? $lots->getId() : intval($lots);
			$query_add.= ' ('.$val->getId().', '.$id_lot.', '.$negoce.')';
		} else {
			foreach ($lots as $lot) {
				$id_lot = $lot instanceof Lot ? $lot->getId() : intval($lot);
				$query_add.= ' ('.$val->getId().', '.$id_lot.', '.$negoce.'),';
			}
			if (is_array($lots) && count($lots) > 0) {
				$query_add = substr($query_add,0,-1);
			}
		}
		$query = $this->db->prepare($query_add);

		Outils::saveLog($query_add);
		return $query->execute();
	} // FIN méthode

	// Retourne si un lot est concerné par une validation de réception (pour check modifiable/supprimable BO)
	public function checkReceptionLotValidation(Lot $lot) {

		$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_validations` WHERE `type` = 1 AND `id_liaison` = ' . $lot->getId();
		$query = $this->db->prepare($query_nb);
		$query->execute();

		$donnee = $query->fetch();
		if (!$donnee || !isset($donnee['nb'])) { return false; }
		return $donnee['nb'] > 0 ? true :false;

	} // FIN méthode


	// Retounre rapidement la validité de la courbe de température d'une OP de froid
	public function getCourbeTempFroid($id_froid) {

		$query_check = 'SELECT `courbe_temp` FROM `pe_validations` WHERE `type` = 2 AND `id_liaison` = ' . $id_froid;

		$query = $this->db->prepare($query_check);
		$query->execute();

		$donnee = $query->fetch();
		if (!$donnee || !isset($donnee['courbe_temp'])) { return false; }
		return intval($donnee['courbe_temp']);

	} // FIN méthode

//Pour le lot negoce
public function getNbNegoceAValider() {

	$query_nb = 'SELECT COUNT(*) AS nb 					
					FROM `pe_validations` v
				 WHERE (`validation_id_user` IS NULL 
					OR `validation_id_user` = 0 
					OR `validation_date` IS NULL
					OR `validation_date` = "0000-00-00 00:00:00")
				AND v.`type` = 4';
	// Requête
	$query = $this->db->prepare($query_nb);
	$query->execute();

	$donnee = $query->fetch();

	return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;
} // FIN méthode


public function getValidationNegoce($id) {
	$query_validation = 'SELECT v.`id`, v.`id_vue`, v.`type`, v.`id_liaison_negoce`, v.`validation_id_user`, v.`validation_date`, CONCAT(u.`prenom`, " ", u.`nom`) AS nom_controleur, v.`courbe_temp`,
					   IF (v.`type` = 3, l.`date_test`, 
						 IF (v.`type` = 2, f.`date_controle`,  IF (v.`type` = 4, lotn.`date_maj`,  r.`date_confirmation`)))
						 AS date,
					   IF (v.`type` = 3,
							(IF ((l.`test_pdt`) = 1, 0,
							 IF (l.`test_pdt` < 0 , -1, 1))), 
						 IF (v.`type` = 2, f.`conformite`, IF (v.`type` = 4, 1, r.`conformite`)))
						 AS resultat,
					   IF (v.`type` = 1, lots.`poids_reception`, 0) AS poids
						FROM `pe_validations` v
						LEFT JOIN `pe_loma` l ON l.`id` = v.`id_liaison`
						LEFT JOIN `pe_froid` f ON f.`id` = v.`id_liaison`
						LEFT JOIN `pe_lots` lots ON lots.`id` = v.`id_liaison`
						LEFT JOIN `pe_lot_reception` r ON r.`id_lot_negoce` = v.`id_liaison_negoce`
						JOIN `pe_lots_negoce` lotn ON lotn.`id` = v.`id_liaison_negoce` 
						LEFT JOIN `pe_users` u ON u.`id` = (  
						  IF (v.`type` = 3, l.`id_user_visa`,
							IF (v.`type` = 2, f.`id_visa_controleur`, r.`id_user`)) 
						)
					  WHERE v.`id` = ' . $id;
	$query = $this->db->prepare($query_validation);
	$query->execute();

	$donnee = $query->fetch();
	if (!$donnee || empty($donnee)) {
		return false;
	}
	$val = new Validation($donnee);

	// On intègre les objets Lot
	$lotNegoceManager = new LotNegoceManager($this->db);
	$query_lots = 'SELECT `id_lot_negoce` FROM `pe_validations_lots` WHERE `id_validation` = '. $val->getId();
	$queryL = $this->db->prepare($query_lots);

	
	// Log de la requête pour le mode Dev
	if (isset($_SESSION['devmode']) && $_SESSION['devmode']) { $_SESSION['pdoq'][get_class($this)][] = $queryL->queryString; }
	$queryL->execute();
	$listeLots = [];
	foreach ($queryL->fetchAll(PDO::FETCH_ASSOC) as $donneeLot) {		
		$lotTemp = $lotNegoceManager->getLotNegoce($donneeLot['id_lot_negoce']);
		if ($lotTemp instanceof LotNegoce) {
			$listeLots[] = $lotTemp;
		}
	}

	
	$val->setLot_negoce($listeLots);

	// On intègre l'objet vue
	$vueManager = new VueManager($this->db);
	$vue = $vueManager->getVue($val->getId_vue());
	if ($vue instanceof Vue) { $val->setVue($vue); }	
	// // Si type 4, on hydrate l'objet LotNegoce
	 if  ($val->getType() == 4) {
	 	$lotNegoceManager = new LotNegoceManager($this->db);
	 	$val->setLot_negoce($lotNegoceManager->getLotNegoce($val->getId_liaison_negoce()));
	 }
	return $val;
} // FIN méthode




public function addValidationLotNegoce(Validation $val, $lotsNegoce) {	

	$query_add = 'INSERT IGNORE INTO `pe_validations_lots` (`id_validation`, `id_lot_negoce`) VALUES ';
	if (!is_array($lotsNegoce)) {
		$id_lot_negoce = $lotsNegoce instanceof LotNegoce ? $lotsNegoce->getId() : intval($lotsNegoce);
		$query_add.= ' ('.$val->getId().', '.$id_lot_negoce.')';
	} else {
		foreach ($lotsNegoces as $lotsNegoce) {
			$id_lot = $lotsNegoces instanceof LotNegoce ? $lotsNegoces->getId() : intval($lotsNegoce);
			$query_add.= ' ('.$val->getId().', '.$id_lot.'),';
		}
		if (is_array($lotsNegoces) && count($lotsNegoces) > 0) {
			$query_add = substr($query_add,0,-1);
		}
	}
	$query = $this->db->prepare($query_add);
	
	Outils::saveLog($query_add);
	return $query->execute();
} // FIN méthode


public function getListeValidationsNegoce($justeNonValidees = true) {
	$query_liste = 'SELECT v.`id`, v.`id_vue`, v.`type`, v.`id_liaison_negoce`, v.`validation_id_user`, v.`validation_date`, CONCAT(u.`prenom`, " ", u.`nom`) AS nom_controleur, v.`courbe_temp`,
					   IF (v.`type` = 3, l.`date_test`, 
						 IF (v.`type` = 2, f.`date_controle`, 
								IF (v.`type` = 4, lotn.`date_maj`, 
							 r.`date_confirmation`)))
						 AS date,
					   IF (v.`type` = 3, 
						 (IF ((l.`test_pdt`) = 1, 0,
							 IF (l.`test_pdt` < 0 , -1, 1))), 
						 IF (v.`type` = 2, f.`conformite`,
							 IF (v.`type` = 4, 1, 
							 r.`conformite`)))
						 AS resultat,
					   IF (v.`type` = 1, lots.`poids_reception`, 0) AS poids
						FROM `pe_validations` v
						LEFT JOIN `pe_loma` l ON l.`id` = v.`id_liaison`
						LEFT JOIN `pe_froid` f ON f.`id` = v.`id_liaison`
						LEFT JOIN `pe_lot_reception` r ON r.`id_lot_negoce` = v.`id_liaison_negoce`
						LEFT JOIN `pe_lots` lots ON lots.`id` = v.`id_liaison` 
						JOIN `pe_lots_negoce` lotn ON lotn.`id` = v.`id_liaison_negoce`
						LEFT JOIN `pe_users` u ON u.`id` = (
						  IF (v.`type` = 3, l.`id_user_visa`,
							IF (v.`type` = 2, f.`id_visa_controleur`, r.`id_user`)) 
						)';
	$query_liste.= $justeNonValidees ? 'WHERE (v.`validation_id_user` IS NULL 
					OR v.`validation_id_user` = 0 
					OR v.`validation_date` IS NULL
					OR v.`validation_date` = "0000-00-00 00:00:00" )
					AND v.`type` = 4' : '';
	$query_liste.= '
					ORDER BY v.`id` DESC ';

	$query = $this->db->prepare($query_liste);
	$query->execute();

	$liste = [];
	$lotManager = new LotNegoceManager($this->db);
	$vueManager = new VueManager($this->db);

	foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

		$val = new Validation($donnee);

		// On intègre les objets Lot
		$query_lots = 'SELECT `id_lot_negoce` FROM `pe_validations_lots` WHERE `id_validation` = '. $val->getId();
		$queryL = $this->db->prepare($query_lots);

		$queryL->execute();
		$listeLots = [];
		foreach ($queryL->fetchAll(PDO::FETCH_ASSOC) as $donneeLot) {
			$lotTemp = $lotManager->getLotNegoce($donneeLot['id_lot_negoce']);
			if ($lotTemp instanceof LotNegoce) {
				$listeLots[] = $lotTemp;
			}
		}
		$val->setLots($listeLots);
		// On intègre l'objet vue
		$vue = $vueManager->getVue($val->getId_vue());
		if ($vue instanceof Vue) { $val->setVue($vue); }
		$liste[] =  $val;
	}
	return $liste;

} // FIN getListe

public function validerToutNegoce(User $user) {

	$query_upd = 'UPDATE `pe_validations` SET `validation_id_user` = ' . $user->getId() . ', `validation_date` = "' . date('Y-m-d H:i:s') . '" WHERE `validation_id_user` IS NULL AND `validation_date` IS NULL AND `type`=4';
	$query = $this->db->prepare($query_upd);

	Outils::saveLog($query_upd);

	return $query->execute();

} // FIN méthode




// public function getControleurNegoce($id) {
// 	$query_validation = 'SELECT v.`id`, v.`id_vue`, v.`type`, v.`id_liaison_negoce`, v.`validation_id_user`, v.`validation_date`, CONCAT(u.`prenom`, " ", u.`nom`) AS nom_controleur, v.`courbe_temp`,					
// 						FROM `pe_validations_lots` v
// 						LEFT JOIN `pe_lot_reception` r ON r.`id_lot_negoce` = v.`id_liaison_negoce`
// 						JOIN `pe_lots_negoce` lotn ON lotn.`id` = v.`id_liaison_negoce` 
// 						LEFT JOIN pe_validations pv ON pv.`id_liaison_negoce` = lotn.`id`						
// 						LEFT JOIN `pe_users` u ON u.`id` = 	pv.`validation_id_user`
						
// 					  WHERE v.`id` = ' . $id;
// 	$query = $this->db->prepare($query_validation);
// 	$query->execute();

// 	$donnee = $query->fetch();
// 	if (!$donnee || empty($donnee)) {
// 		return false;
// 	}
// 	$val = new Validation($donnee);
	
// 	return $val;
// } // FIN méthode
} // FIN classe