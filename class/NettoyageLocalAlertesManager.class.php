<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet NettoyageLocalAlerte
Généré par CBO FrameWork le 02/04/2021 à 11:30:26
------------------------------------------------------*/
class NettoyageLocalAlertesManager {

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

	// Retourne la liste des NettoyageLocalAlerte
	public function getListeNettoyageLocalAlertes($params = []) {

		$start = isset($params['start'])          ? intval($params['start'])          : 0;
		$nb    = isset($params['nb_result_page']) ? intval($params['nb_result_page']) : 10000000;

		$query_liste = "SELECT SQL_CALC_FOUND_ROWS `id`, `id_nett_local`, `mois`, `semaine`, `jour`, `heure`, `minute` FROM `pe_nett_locaux_alerte` WHERE 1 
            ORDER BY `id` DESC ";  $query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = new NettoyageLocalAlerte($donnee);
		}
		return $liste;

	} // FIN liste des NettoyageLocalAlerte


	// Retourne un NettoyageLocalAlerte
	public function getNettoyageLocalAlerte($id) {

		$query_object = "SELECT `id`, `id_nett_local`, `mois`, `semaine`, `jour`, `heure`, `minute` 
                FROM `pe_nett_locaux_alerte` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee) ? new NettoyageLocalAlerte($donnee) : false;

	} // FIN get NettoyageLocalAlerte

	// Retourne un NettoyageLocalAlerte par l'ID du local nett
	public function getNettoyageLocalAlerteByLocalNett($id_nett_local) {

		$query_object = "SELECT `id`, `id_nett_local`, `mois`, `semaine`, `jour`, `heure`, `minute` 
                FROM `pe_nett_locaux_alerte` WHERE `id_nett_local` = " . (int)$id_nett_local . " LIMIT 0,1";
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee) ? new NettoyageLocalAlerte($donnee) : false;

	} // FIN get NettoyageLocalAlerte

	// Enregistre & sauvegarde (Méthode Save)
	public function saveNettoyageLocalAlerte(NettoyageLocalAlerte $objet) {

		$table      = 'pe_nett_locaux_alerte'; // Nom de la table
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

	// Supprime les alertes d'un local
	public function supprAlertesLocal($id_nett_local) {

		$queryDel = 'DELETE FROM `pe_nett_locaux_alerte` WHERE `id_nett_local` = ' . (int)$id_nett_local;
		$query = $this->db->prepare($queryDel);
		return $query->execute();

	} // FIN méthode

	// Retourne l'alerte verbose d'un local de nettoyage
	public function getAlerteVerboseForLocal($id_local_nett) {

		$verbose = '';

		$alerte = $this->getNettoyageLocalAlerteByLocalNett((int)$id_local_nett);
		if (!$alerte instanceof NettoyageLocalAlerte) { return $verbose; }

		if (empty($alerte->getJoursArray())) {
			$verbose = 'Tous les jours';
		} else if (count($alerte->getJoursArray()) == 1) {
			$verbose = 'Tous les ';
			if (count($alerte->getSemainesArray()) == 1) {

				if ((int)$alerte->getSemainesArray()[0] == 1) {
					$verbose.= 'premiers ';
				} else if ((int)$alerte->getSemainesArray()[0] == 2) {
					$verbose.= 'deuxièmes ';
				} else if ((int)$alerte->getSemainesArray()[0] == 3) {
					$verbose.= 'troisièmes ';
				} else {
					$verbose.= 'derniers ';
				}
			} else if  (count($alerte->getSemainesArray()) > 1) {
				$i = 0;
				foreach ($alerte->getSemainesArray() as $v) {
					$i++;
					$sep = $i == count($alerte->getSemainesArray()) -1 ? ' et  ' : ', ';
					if ($i == count($alerte->getSemainesArray())) { $sep = ' '; }
					if ((int)$v == 1) {
						$verbose.= 'premiers'.$sep;
					} else if ((int)$v == 2) {
						$verbose.= 'deuxièmes'.$sep;
					} else if ((int)$v == 3) {
						$verbose.= 'troisièmes'.$sep;
					} else {
						$verbose.= 'derniers'.$sep;
					}
				}
			}
			$verbose.= Outils::getJourSemaine($alerte->getJoursArray()[0]).'s';
		} else if (count($alerte->getJoursArray()) > 1) {
			$verbose = 'Tous les ';
			if (count($alerte->getSemainesArray()) == 1) {
				if ((int)$alerte->getSemainesArray()[0] == 1) {
					$verbose.= 'premiers ';
				} else if ((int)$alerte->getSemainesArray()[0] == 2) {
					$verbose.= 'deuxièmes ';
				} else if ((int)$alerte->getSemainesArray()[0] == 3) {
					$verbose.= 'troisièmes ';
				} else {
					$verbose.= 'derniers ';
				}
			}  else if  (count($alerte->getSemainesArray()) > 1) {
				$i = 0;
				foreach ($alerte->getSemainesArray() as $v) {
					$i++;
					$sep = $i == count($alerte->getSemainesArray()) -1 ? ' et  ' : ', ';
					if ($i == count($alerte->getSemainesArray())) { $sep = ' '; }
					if ((int)$v == 1) {
						$verbose.= 'premiers'.$sep;
					} else if ((int)$v == 2) {
						$verbose.= 'deuxièmes'.$sep;
					} else if ((int)$v == 3) {
						$verbose.= 'troisièmes'.$sep;
					} else {
						$verbose.= 'derniers'.$sep;
					}
				}
			}

			$i = 0;
			foreach ($alerte->getJoursArray() as $v) {
				$i++;
				$sep = $i == count($alerte->getJoursArray()) - 1 ? ' et ' : ', ';
				if ($i == count($alerte->getJoursArray())) { $sep =  ' '; }
				$verbose.=Outils::getJourSemaine($v).'s'.$sep;
			}
		}

		if (count($alerte->getSemainesArray()) > 0 && empty($alerte->getMoisArray())) {
			$verbose.= ' du mois ';
		} else if (count($alerte->getMoisArray()) == 1) {
			$verbose.= ' de '. Outils::getMoisIntListe()[$alerte->getMoisArray()[0]];
		} else if (count($alerte->getMoisArray()) > 1) {
			$verbose.= ' de ';
			$i = 0;
			foreach ($alerte->getMoisArray() as $v) {
				$i++;
				$sep = $i == count($alerte->getMoisArray()) - 1 ? ' et  ' : ', ';
				if ($i == count($alerte->getMoisArray())) { $sep = ' '; }
				$verbose.= Outils::getMoisIntListe()[(int)$v].$sep;
			}
		}
		$verbose.= ' à ' . sprintf("%02d", $alerte->getHeure()). ':'.sprintf("%02d", $alerte->getMinute());

		// Si incohérence semaine/!mois
		if (count($alerte->getSemainesArray()) > 0 &&
			empty($alerte->getJoursArray())) {
			return '';
		}
		else if (empty($alerte->getMoisArray()) && empty($alerte->getSemainesArray()) && empty($alerte->getJoursArray()) && intval($alerte->getHeure()) == 0 && intval($alerte->getMinute()) == 0) {
			return '';
		} else {

			$verbose = str_replace(["de o", "de a"], ["d'o", "d'a"], $verbose);

			return $verbose;
		}

	} // FIN méthode

	// Retourne les IDs des locaux concernés si l'alerte doit se déclancher, un string vide sinon.
	public function getIdsLocauxByAlerteNow($code_vue) {


		if ($code_vue == '') { return ''; }

		// On cherche les alertes qui sont à déclancher pour la vue actuelle à ce moment
		$query_liste = 'SELECT a.`id_nett_local`, a.`mois`, a.`semaine`, a.`jour`
							FROM `pe_nett_locaux_alerte` a
								JOIN `pe_nett_locaux` nl ON nl.`id` = a.`id_nett_local`
								JOIN `pe_locaux` l ON l.`id` = nl.`id_local`
							WHERE (l.`vues` LIKE "%'.$code_vue.'%" OR l.`vues` = "" OR l.`vues` IS NULL) 
								AND a.`heure` = '.date('H').' 
								AND a.`minute` = '.date('i').' 
							';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		// On peut avoir des alertes pour l'heure courante mais pas pour aujourd'hui, on vérifie alors en PHP
		$res = $query->fetchAll(PDO::FETCH_ASSOC);
		if (!$res || empty($res)) { return ''; }

		$ids = [];

		foreach ($res as $donnee) {

			$moisArray = explode(',', $donnee['mois']);
			$semainesArray = explode(',', $donnee['semaine']);
			$joursArray = explode(',', $donnee['jour']);

			// Si il y a des jours et que c'est pas aujourd'hui, on ne va pas plus loin
			if ($donnee['jour'] != '' && !in_array((int)date('N'), $joursArray)) {
				continue;
			}

			// Le jour et l'heure est bon, mais est-ce que le mois/semaine correspond ?

			// Si il y a des mois de noté mais par celui en cours, on ne va pas plus loin
			if ($donnee['mois'] != '' && !in_array((int)date('M'), $moisArray)) {
				continue;
			}

			// Si on a des semaines précises et que ça ne correspond pas, on ne vas pas plus loin
			if ((int)date('d') < 8) { $sem = 1; } 		// Semaine 1 = du 1er au 7 inclu
			else if ((int)date('d') < 15) { $sem = 2; }	// Semaine 2 = du 8 au 14 inclu
			else if ((int)date('d') < 22) { $sem = 3; }  // Semaine 3 = du 15 au 21 inclu
			else { $sem = 4; } 									// Semaine 4 = à partir du 22

			if ($donnee['semaine'] != '' && !in_array($sem, $semainesArray)) {
				continue;
			}

			// Ici, par déduction inversée, on sais qu'on est concerné par l'alerte, on intègre l'ID du local concerné
			$ids[] = (int)$donnee['id_nett_local'];

		} // FIN des alertes correspondant à l'heure et au poste

		if (empty($ids)) {  return ''; }

		return implode(',',$ids);

	} // FIN méthode

} // FIN classe