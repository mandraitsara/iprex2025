<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet NettLocal
Généré par CBO FrameWork le 27/11/2020 à 11:49:08
------------------------------------------------------*/
class NettoyageLocauxManager {

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

	// Retourne la liste des Locaux
	public function getListeNettLocaux($params = []) {

		$start = isset($params['start'])          ? intval($params['start'])          : 0;
		$nb    = isset($params['nb_result_page']) ? intval($params['nb_result_page']) : 10000000;

		$contexte = isset($params['contexte']) ? intval($params['contexte']) : -1;
		$id_local = isset($params['id_local']) ? intval($params['id_local']) : 0;
		$id_acteur = isset($params['id_acteur']) ? intval($params['id_acteur']) : 0;
		$id_user = isset($params['id_user']) ? intval($params['id_user']) : 0;
		$vue = isset($params['vue']) ? strtolower($params['vue']) : '';

		$alertes_verbose = isset($params['alertes_verbose']) ? boolval($params['alertes_verbose']) : false;

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT nl.`id`, nl.`id_local`, nl.`id_local_zone`, nl.`id_freq_protection`, nl.`id_freq_degrossi`, nl.`id_freq_demontage`, nl.`id_freq_vidage`, nl.`nettoyage_id_fam_conso`, nl.`nettoyage_temps`, nl.`desinfection_id_fam_conso`, nl.`desinfection_temps`, nl.`id_acteur_nett`, nl.`id_freq_prelavage`, nl.`id_freq_deterg_mal`, nl.`id_freq_deterg_mac`, nl.`id_freq_rincage_1`, nl.`id_freq_rincage_2`, nl.`id_freq_desinfection`, l.`numero`, l.`surface`, l.`nom` as nom_local, z.`nom` AS nom_zone, IFNULL(cf1.`nom`, "") AS nom_nettoyage_pdt, IFNULL(cf2.`nom`, "") AS nom_desinfection_pdt, nl.`contexte`, l.`vues`
						FROM `pe_nett_locaux` nl
						JOIN `pe_locaux` l ON l.`id` = nl.`id_local`
    					JOIN `pe_local_zones` z ON z.`id` = nl.`id_local_zone` 
    					LEFT JOIN `pe_consommables_familles` cf1 ON cf1.`id` = nl.`nettoyage_id_fam_conso`
    					LEFT JOIN `pe_consommables_familles` cf2 ON cf2.`id` = nl.`desinfection_id_fam_conso`
    					LEFT JOIN `pe_nett_users` nu ON nu.`id_nett_local` = nl.`id`
						WHERE 1 ';

		$query_liste.= $contexte > -1 ? ' AND nl.`contexte` = ' . $contexte : '';

		$query_liste.= $id_local > 0 ? ' AND nl.`id_local` = ' . $id_local : '';
		$query_liste.= $id_acteur > 0 ? ' AND (nl.`id_acteur_nett` = ' . $id_acteur . ' OR nu.`id_user` = ' .$id_acteur.') '  : '';

		$query_liste.= $vue != '' ? ' AND (l.`vues` = "" OR  l.`vues` LIKE "%'.$vue.'%") ' : '';
		$query_liste.= $vue != '' && $id_user > 0 ? ' AND nu.`id_user` = ' . $id_user : '';

		$query_liste.= " ORDER BY nl.`id_local`, nl.`id_local_zone`";
		$query_liste.= ' LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		if ($alertes_verbose) {
			$alertesNettManager = new NettoyageLocalAlertesManager($this->db);
		}

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$local = new NettoyageLocal($donnee);

			if ($alertes_verbose) {
				$alerte = $alertesNettManager->getAlerteVerboseForLocal($local->getId());
				$local->setAlerteVerbose($alerte);
			}

			// Rattachement des users
			$query_usrs = 'SELECT u.`id`, UPPER(CONCAT(u.`prenom`, " ", u.`nom`)) AS nom, 
       									  UPPER(CONCAT(LEFT(u.`prenom`,1),LEFT(u.`nom`,2))) AS trigramme
								FROM `pe_nett_users` nu
									JOIN `pe_users` u ON u.`id` = nu.`id_user`
							WHERE nu.`id_nett_local` = ' . $local->getId();
			$query2 = $this->db->prepare($query_usrs);
			$usersPn = [];
			$query2->execute();
			foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {
				$usersPn[] = $donnee2;
			}
			$local->setUsers($usersPn);
			$liste[] = $local;

		}
		return $liste;

	} // FIN liste des Local


	// Retourne un Local
	public function getNettLocal($id) {

		$query_object = 'SELECT nl.`id`, nl.`id_local`, nl.`id_local_zone`, nl.`id_freq_protection`, nl.`id_freq_degrossi`, nl.`id_freq_demontage`, nl.`id_freq_vidage`, nl.`nettoyage_id_fam_conso`, nl.`nettoyage_temps`, nl.`desinfection_id_fam_conso`, nl.`desinfection_temps`, nl.`id_acteur_nett`, nl.`id_freq_prelavage`, nl.`id_freq_deterg_mal`, nl.`id_freq_deterg_mac`, nl.`id_freq_rincage_1`, nl.`id_freq_rincage_2`, nl.`id_freq_desinfection`, l.`numero`, l.`surface`, l.`nom` as nom_local, z.`nom` AS nom_zone, IFNULL(cf1.`nom`, "") AS nom_nettoyage_pdt, IFNULL(cf2.`nom`, "") AS nom_desinfection_pdt, nl.`contexte`
						FROM `pe_nett_locaux` nl
						JOIN `pe_locaux` l ON l.`id` = nl.`id_local`
    					JOIN `pe_local_zones` z ON z.`id` = nl.`id_local_zone` 
    					LEFT JOIN `pe_consommables_familles` cf1 ON cf1.`id` = nl.`nettoyage_id_fam_conso`
    					LEFT JOIN `pe_consommables_familles` cf2 ON cf2.`id` = nl.`desinfection_id_fam_conso` WHERE nl.`id` = ' . (int)$id;
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee) ? new NettoyageLocal($donnee) : false;

	} // FIN get Local

	public function getIdsUsersNettoyageLocal(NettoyageLocal $local) {

		// Rattachement des users
		$query_usrs = 'SELECT `id_user` FROM `pe_nett_users` WHERE `id_nett_local` = ' . $local->getId();
		$query2 = $this->db->prepare($query_usrs);
		$usersPn = [];
		$query2->execute();
		foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {
			$usersPn[] = (int)$donnee2['id_user'];
		}
		return $usersPn;

	}

	// Enregistre & sauvegarde (Méthode Save)
	public function saveNettoyageLocal(NettoyageLocal $objet) {

		$table      = 'pe_nett_locaux'; // Nom de la table
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

	// Supprime un local
	public function supprimeNettoyageLocal(NettoyageLocal $local) {

		$query_del = 'DELETE FROM `pe_nett_locaux` WHERE `id` = ' . $local->getId();
		$query = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		return $query->execute();

	} // FIN méthode

	public function saveUsersNettoyage(NettoyageLocal $local, $ids_users = []) {

		$query_del = 'DELETE FROM `pe_nett_users` WHERE `id_nett_local` = ' .$local->getId();
		$query1 = $this->db->prepare($query_del);
		Outils::saveLog($query_del);
		$query1->execute();

		if (empty($ids_users) || !is_array($ids_users)) { return true; }

		$query_add = 'INSERT IGNORE INTO `pe_nett_users` (`id_nett_local`, `id_user`) VALUES ';
		foreach ($ids_users as $id_user) {
			$query_add.= '('.$local->getId().', '.(int)$id_user.'),';
		}
		$query_add = substr($query_add,0,-1);
		$query2 = $this->db->prepare($query_add);
		Outils::saveLog($query_add);
		return $query2->execute();

	} // FIN méthode


} // FIN classe