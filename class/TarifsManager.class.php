<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager des objets Tarif
Généré par CBO FrameWork le 06/03/2020 à 14:32:38
------------------------------------------------------*/
class TarifsManager {

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

	// Retourne la liste des TarifFournisseur
	public function getListeTarifFournisseurs($params = []) {

		$id_frs = isset($params['id_frs']) ? intval($params['id_frs']) : 0;
		$id_pdt = isset($params['id_pdt']) ? intval($params['id_pdt']) : 0;

		// Pagination
		$start 				= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 				= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		$query_liste = "SELECT SQL_CALC_FOUND_ROWS tf.`id`, tf.`id_tiers`, tf.`id_produit`, tf.`prix`, t.`nom` AS nom_fournisseur, pt.`nom` AS nom_produit, p.code AS code_produit
							FROM `pe_tarif_fournisseur` tf
								JOIN `pe_tiers` t ON t.`id` = tf.`id_tiers`
							    JOIN `pe_produits` p ON p.`id` = tf.`id_produit`
								JOIN `pe_produit_trad` pt ON pt.`id_produit` = tf.`id_produit` AND pt.`id_langue` = 1 
							WHERE 1 ";

		$query_liste.= $id_frs > 0 ? ' AND tf.`id_tiers`   = ' . $id_frs : '';
		$query_liste.= $id_pdt > 0 ? ' AND tf.`id_produit` = ' . $id_pdt : '';

		$query_liste.= "	ORDER BY tf.`id_tiers`, tf.`id_produit`
							LIMIT " . $start . "," . $nb;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new TarifFournisseur($donnee);
		}
		return $liste;

	} // FIN liste des TarifFournisseur


	// Retourne un TarifFournisseur
	public function getTarifFournisseur($id) {

		$query_object = "SELECT tf.`id`, tf.`id_tiers`, tf.`id_produit`, tf.`prix`, t.`nom` AS nom_fournisseur, pt.`nom` AS nom_produit
                			FROM `pe_tarif_fournisseur` tf
								JOIN `pe_tiers` t ON t.`id` = tf.`id_tiers`
								JOIN `pe_produit_trad` pt ON pt.`id_produit` = tf.`id_produit` AND pt.`id_langue` = 1
						WHERE tf.`id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new TarifFournisseur($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get TarifFournisseur

	// Enregistre & sauvegarde (Méthode Save)
	public function saveTarifFournisseur(TarifFournisseur $objet) {

		$table      = 'pe_tarif_fournisseur'; // Nom de la table
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

	// Retourne la liste des TarifClient
	public function getListeTarifClients($params = []) {

		$id_clt 	= isset($params['id_clt']) 		? intval($params['id_clt']) 	: 0;
		$id_grp 	= isset($params['id_grp']) 		? intval($params['id_grp']) 	: 0;
		$id_pdt 	= isset($params['id_pdt']) 		? intval($params['id_pdt']) 	: 0;
		$id_langue 	= isset($params['id_langue']) 	? intval($params['id_langue']) 	: 1;

		// Pagination
		$start 				= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 				= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		$query_liste = "SELECT SQL_CALC_FOUND_ROWS tf.`id`, tf.`id_tiers`, tf.`id_tiers_groupe`, tf.`id_produit`, tf.`prix`, t.`nom` AS nom_client, pt.`nom` AS nom_produit, 
                        	tg.`nom` AS nom_groupe, p.code AS code_produit, p.`ean13` 
							FROM `pe_tarif_client` tf
								JOIN `pe_produit_trad` pt ON pt.`id_produit` = tf.`id_produit` AND pt.`id_langue` = ".$id_langue." 
								JOIN `pe_produits` p ON p.`id` = tf.`id_produit`
								LEFT JOIN `pe_tiers` t ON t.`id` = tf.`id_tiers`
							    LEFT JOIN `pe_tiers_groupes` tg ON tg.`id` = tf.`id_tiers_groupe`		    
							WHERE 1 ";

		$query_liste.= $id_clt > 0 ? ' AND tf.`id_tiers`   = ' . $id_clt : '';
		$query_liste.= $id_grp > 0 ? ' AND tf.`id_tiers_groupe`   = ' . $id_grp : '';
		$query_liste.= $id_pdt > 0 ? ' AND tf.`id_produit` = ' . $id_pdt : '';

		$query_liste.= "	ORDER BY tf.`id_tiers`, tf.`id_produit`
							LIMIT " . $start . "," . $nb;

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new TarifClient($donnee);
		}
		return $liste;

	} // FIN liste des TarifClient


	// Retourne un TarifClient
	public function getTarifClient($id) {

		$query_object = "SELECT tf.`id`, tf.`id_tiers`, tf.`id_produit`, tf.`prix`, t.`nom` AS nom_client, pt.`nom` AS nom_produit, tf.`id_tiers_groupe`, tg.`nom` AS nom_groupe
                			FROM `pe_tarif_client` tf
								 JOIN `pe_produit_trad` pt ON pt.`id_produit` = tf.`id_produit` AND pt.`id_langue` = 1
								 LEFT JOIN `pe_tiers` t ON t.`id` = tf.`id_tiers`
                			     LEFT JOIN `pe_tiers_groupes` tg ON tg.`id` = tf.`id_tiers_groupe`
						WHERE tf.`id` = " . (int)$id;

		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new TarifClient($donnee[0]) : false;
		} else {
			return false;
		}

	} // FIN get TarifClient

	// Enregistre & sauvegarde (Méthode Save)
	public function saveTarifClient(TarifClient $objet) {

		$table      = 'pe_tarif_client'; // Nom de la table
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

	// Supprime un tarif fournisseur
	public function supprTarifFrs(TarifFournisseur $tarif) {

		$query_del = 'DELETE FROM `pe_tarif_fournisseur` WHERE `id` = ' . $tarif->getId();
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode

	// Supprime un tarif client
	public function supprTarifClt($tarif) {

		$id_tarif = $tarif instanceof TarifClient ? $tarif->getId() : (int)$tarif;

		echo $query_del = 'DELETE FROM `pe_tarif_client` WHERE `id` = ' . $id_tarif;
		echo '<br>';
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode

	// Supprime un tarif client par client et produit
	public function supprTarifCltByClientPdt($id_client, $id_pdt) {

		echo $query_del = 'DELETE FROM `pe_tarif_client` WHERE `id_produit` = ' . (int)$id_pdt . ' AND `id_tiers` = ' . (int)$id_client;
		echo '<br>';
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode


	// Retourne un array des IDs de produits déjà associés à un client
	public function getIdsProduitsTarifClients(Tiers $client) {

		$query_liste = 'SELECT DISTINCT `id_produit` FROM `pe_tarif_client` WHERE `id_tiers` = ' . $client->getId();
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = (int)$donnee['id_produit'];
		}
		return $liste;

	} // FIN méthode


	// Retourne un tarif client par client et produit
	public function getTarifClientByClientAndProduit($id_client, $id_produit) {

		$query_id = 'SELECT `id` FROM `pe_tarif_client` WHERE `id_tiers` = ' . (int)$id_client . ' AND `id_produit` = ' . (int)$id_produit;
		$query = $this->db->prepare($query_id);
		if (!$query->execute()) { return false; }

		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee['id']) ? $this->getTarifClient((int)$donnee['id']) : false;

	} // FIN méthode

	// Retourne un tarif client précis pour un produit
	public function getTarifClientProduit(Tiers $client, $id_produit) {

		if ($client->getId_groupe() == 0) { $client->setId_groupe(-1); }

		$query_tarif = 'SELECT `prix` FROM `pe_tarif_client` WHERE (`id_tiers` = '.$client->getId().' OR `id_tiers_groupe` = '.$client->getId_groupe().') AND `id_produit` = ' . $id_produit;

		$query = $this->db->prepare($query_tarif);
		if (!$query->execute()) { return false; }

		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee['prix']) ? floatval($donnee['prix']) : 0;

	} // FIN mméthode

	// Retourne un tarif fournisseur par fournisseur et produit
	public function getTarifFournisseurByFrsAndProduit($id_frs, $id_produit) {

		$query_id = 'SELECT `id` FROM `pe_tarif_fournisseur` WHERE `id_tiers` = ' . (int)$id_frs . ' AND `id_produit` = ' . (int)$id_produit;
		$query = $this->db->prepare($query_id);
		if (!$query->execute()) { return false; }

		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee['id']) ? $this->getTarifFournisseur((int)$donnee['id']) : false;

	} // FIN méthode

} // FIN classe