<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet FactureReglement
Généré par CBO FrameWork le 16/09/2020 à 16:17:25
------------------------------------------------------*/
class FactureReglementsManager {

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

	// Retourne la liste des FactureReglement
	public function getListeFactureReglements($facture = false) {

		$query_liste = "SELECT fr.`id`, fr.`id_facture`, fr.`date`, fr.`montant`, fr.`id_mode`, m.`nom` AS nom_mode
									FROM `pe_facture_reglements` fr 
										LEFT JOIN `pe_modes_reglement` m ON m.`id` = fr.`id_mode` 
										WHERE 1 ";

		if ($facture instanceof Facture) {
			$query_liste.= ' AND fr.`id_facture` = ' . $facture->getId();
		}

		$query_liste.= "			ORDER BY fr.`id`";
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new FactureReglement($donnee);
		}
		return $liste;

	} // FIN liste des FactureReglement

	public function getListeAvoirsLiesReglements(Facture $facture) {

		$facturesManager = new FacturesManager($this->db);

		$ids_avoirs_array = [];
		$avoirs = $facturesManager->getAvoirsPossiblesFacture($facture);
		if (empty($avoirs)) { return []; }

		foreach ($avoirs as $avoir) {
			$id_avoir = isset($avoir['id_avoir']) ? intval($avoir['id_avoir']) : 0;
			if ($id_avoir > 0) { $ids_avoirs_array[] = $avoir['id_avoir']; }
		}

		if (empty($ids_avoirs_array)) { return []; }

		$query_liste = "SELECT fr.`id`, fr.`id_facture`, fr.`date`, fr.`montant`, fr.`id_mode`, f.`num_facture` AS nom_mode
							FROM `pe_facture_reglements` fr 
								LEFT JOIN `pe_modes_reglement` m ON m.`id` = fr.`id_mode` 
								LEFT JOIN `pe_factures` f ON f.`id` = fr.`id_facture` 
							WHERE fr.`id_facture` IN (".implode(',',$ids_avoirs_array).")  
						ORDER BY fr.`id`";

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new FactureReglement($donnee);
		}
		return $liste;

	} // FIn liste


	// Retourne un FactureReglement
	public function getFactureReglement($id) {

		$query_object = "SELECT fr.`id`, fr.`id_facture`, fr.`date`, fr.`montant`, fr.`id_mode`, m.`nom` AS nom_mode
									FROM `pe_facture_reglements` fr 
										LEFT JOIN `pe_modes_reglement` m ON m.`id` = fr.`id_mode`
							WHERE fr.`id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new FactureReglement($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get FactureReglement

	// Retourne un FactureReglement
	public function getFactureReglementByFacture(Facture $facture) {

		$query_object = "SELECT fr.`id`, fr.`id_facture`, fr.`date`, fr.`montant`, fr.`id_mode`, m.`nom` AS nom_mode
									FROM `pe_facture_reglements` fr 
										LEFT JOIN `pe_modes_reglement` m ON m.`id` = fr.`id_mode`
							WHERE fr.`id_facture` = " . (int)$facture->getId();
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new FactureReglement($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get FactureReglement

	// Enregistre & sauvegarde (Méthode Save)
	public function saveFactureReglement(FactureReglement $objet) {

		$table      = 'pe_facture_reglements'; // Nom de la table
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

	// Supprime un règlement
	public function supprimeReglement($id_reglement) {

		$query_del = 'DELETE FROM `pe_facture_reglements` WHERE `id` = ' . (int)$id_reglement;
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode

	// Retourne ce qui reste à payer sur une facture (négatif si trop perçu)
	public function getResteApayerFacture(Facture $facture) {

		// Mode debug si besoin
		if (in_array($_SERVER['REMOTE_ADDR'], array('!89.2.244.59'))){
			$debug = true;
		} else {
			$debug = false;
		}
		
		If ($debug) {
			var_dump($facture);
		}

		$montant = $facture->getTotal_ttc() < 0 ? $facture->getTotal_ttc()*-1 : $facture->getTotal_ttc();
		$mult =  $facture->getTotal_ttc() < 0 ?  '*-1' : '';

		$query_reste = 'SELECT '.$montant.' - IFNULL(SUM(`montant`)'.$mult.',0) AS reste FROM `pe_facture_reglements` WHERE `id_facture` = ' . $facture->getId();

		$query = $this->db->prepare($query_reste);
		if (!$query->execute()) { return 0; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		$reste = $donnee && isset($donnee['reste']) ? round(floatval($donnee['reste']),2) : 0;
		if ($facture->getTotal_ttc() < 0 ) { $reste*=-1; }
		return $reste;

	} // FIN méthode

} // FIN classe