<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet BonTransfert
Généré par CBO FrameWork le 29/09/2020 à 17:21:04
------------------------------------------------------*/
class BonsTransfertsManager {

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

	// Retourne la liste des BonTransfert
	public function getListeBonsTransferts($params = []) {


		$id_tiers 	= isset($params['id_tiers'])        ? intval($params['id_tiers'])       : 0;
		$num_bon 	= isset($params['num_bon'])         ? trim($params['num_bon'])          : '';
		$date_du 	= isset($params['date_du'])         ? trim($params['date_du'])          : '';
		$date_au 	= isset($params['date_au'])         ? trim($params['date_au'])          : '';
		$start 		= isset($params['start'])          	? intval($params['start'])          : 0;
		$nb    		= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		$query_liste = "SELECT SQL_CALC_FOUND_ROWS `id`, `id_tiers`, `date`, `date_add`, `num_bon_transfert`, `supprime` FROM `pe_bons_transfert` WHERE `supprime` = 0  ";

		$query_liste.=$id_tiers > 0 ? ' AND `id_tiers` = '.$id_tiers : '';
		$query_liste.=$date_du != '' ? ' AND `date` >= "'.$date_du . '" ' : '';
		$query_liste.=$date_au != '' ? ' AND `date` <= "'.$date_au . '" ' : '';
		$query_liste.=$num_bon != '' ? ' AND `num_bon_transfert` LIKE "%'.$num_bon. '%" ' : '';

		$query_liste.= " ORDER BY `id` DESC ";  $query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$bon = new BonTransfert($donnee);

			$lignes = $this->getListeBonTransfertLignes(['id_bon' => $bon->getId()]);
			if (!$lignes || !is_array($lignes)) { $lignes = []; }
			$bon->setLignes($lignes);

			$ids_produits = [];
			$ids_palettes = [];
			$poids 		  = 0.0;

			foreach ($lignes as $ligne) {
				$poids+=(float)$ligne->getPoids();
				if ((int)$ligne->getId_produit() > 0) {
					$ids_produits[$ligne->getId_produit()] = $ligne->getId_produit();
				}
				if ((int)$ligne->getId_palette() > 0) {
					$ids_palettes[$ligne->getId_palette()] = $ligne->getId_palette();
				}
			}
			$bon->setNb_palettes(count($ids_palettes));
			$bon->setNb_produits(count($ids_produits));
			$bon->setPoids($poids);

			$liste[] = $bon;
		}
		return $liste;

	} // FIN liste des BonTransfert


	// Retourne un BonTransfert
	public function getBonTransfert($id) {

		$query_object = "SELECT `id`, `id_tiers`, `date`, `date_add`, `num_bon_transfert`, `supprime` 
                FROM `pe_bons_transfert` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee) ? new BonTransfert($donnee) : false;

	} // FIN get BonTransfert

	// Enregistre & sauvegarde (Méthode Save)
	public function saveBonTransfert(BonTransfert $objet) {

		$table      = 'pe_bons_transfert'; // Nom de la table
		$champClef  = 'id'; // Nom du champ clef

		// FIN Configuration

		$getter     = 'get'.ucfirst(strtolower($champClef));
		$setter     = 'set'.ucfirst(strtolower($champClef));

		if ($objet->$getter() == '' && !empty($objet->attributs)) {

			$query_add = 'INSERT INTO '.$table.' (';

			foreach ($objet->attributs as $attribut)	{
				$query_add.= '`'.$attribut.'`,';
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
				$objet->$setter($this->db->lastInsertId());
				return $objet->$getter();
			}

		} else if ($objet->$getter() != '' && !empty($objet->attributs)) {

			$query_upd = 'UPDATE '.$table.' SET ';

			foreach($objet->attributs as $attribut) {
				$query_upd.= '`'.$attribut.'` = :'.strtolower($attribut).',';
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
				return true;
			} catch(PDOExeption $e) {return false;}
		}
		return false;

	} // FIN méthode

	// Retourne la liste des BonTransfertLigne
	public function getListeBonTransfertLignes($params = []) {

		$id_bon = isset($params['id_bon'])          ? intval($params['id_bon'])          : 0;

		$start = isset($params['start'])          ? intval($params['start'])          : 0;
		$nb    = isset($params['nb_result_page']) ? intval($params['nb_result_page']) : 10000000;

		$query_liste = "SELECT SQL_CALC_FOUND_ROWS `id`, `id_bon_transfert`, `id_compo`, `id_produit`, `id_palette`, `poids`, `date_add`, `supprime` FROM `pe_bons_transfert_lignes` WHERE `supprime` = 0 ";

		$query_liste.= $id_bon > 0 ? ' AND `id_bon_transfert` = ' . $id_bon : '';

		$query_liste.= " ORDER BY `id` DESC ";
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);

		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new BonTransfertLigne($donnee);
		}
		return $liste;

	} // FIN liste des BonTransfertLigne


	// Retourne un BonTransfertLigne
	public function getBonTransfertLigne($id) {

		$query_object = "SELECT `id`, `id_bon_transfert`, `id_compo`, `id_produit`, `id_palette`, `poids`, `date_add`, `supprime`, `num_palette`, `nom_produit` 
                FROM `pe_bons_transfert_lignes` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee) ? new BonTransfertLigne($donnee) : false;

	} // FIN get BonTransfertLigne

	// Enregistre & sauvegarde (Méthode Save)
	public function saveBonTransfertLigne(BonTransfertLigne $objet) {

		$table      = 'pe_bons_transfert_lignes'; // Nom de la table
		$champClef  = 'id'; // Nom du champ clef

		// FIN Configuration

		$getter     = 'get'.ucfirst(strtolower($champClef));
		$setter     = 'set'.ucfirst(strtolower($champClef));

		if ($objet->$getter() == '' && !empty($objet->attributs)) {

			$query_add = 'INSERT INTO '.$table.' (';

			foreach ($objet->attributs as $attribut)	{
				$query_add.= '`'.$attribut.'`,';
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
				$objet->$setter($this->db->lastInsertId());
				return $objet->$getter();
			}

		} else if ($objet->$getter() != '' && !empty($objet->attributs)) {

			$query_upd = 'UPDATE '.$table.' SET ';

			foreach($objet->attributs as $attribut) {
				$query_upd.= '`'.$attribut.'` = :'.strtolower($attribut).',';
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
				return true;
			} catch(PDOExeption $e) {return false;}
		}
		return false;

	} // FIN méthode

	// Retourne le prochain numéro de bon de transfert
	public function getNextNumeroBonTransfert() {

		$numbon = 'BT';
		$numbon.= date('yz'); // Année sur 2 chiffre + numéro du jour dans l'année

		// On cherche combien de bons ont été faite ce jour là, et on incrémente
		$query_num = 'SELECT COUNT(*)+1 AS num FROM `pe_bons_transfert` WHERE `date` = CURDATE()';
		$query = $this->db->prepare($query_num);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		$num = $donnees && isset($donnees['num']) ? intval($donnees['num']) : 1;


		return $numbon.$num;

	} // FIN méthode

} // FIN classe