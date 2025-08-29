<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2021 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet Gesdoc
------------------------------------------------------*/
class GesdocManager {


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

	public function getAllDocuments($params = []) {

		$id_client   = isset($params['id_client']) 		? intval($params['id_client']) 		  : 0;
		$id_clients  = isset($params['id_clients']) 	? trim($params['id_clients']) 		  : '';
		$date_du 	 = isset($params['date_du']) 		? trim($params['date_du']) 			  : '2021-11-01'; // Début de Gescom OK
		$date_au 	 = isset($params['date_au']) 		? trim($params['date_au']) 			  : '';
		$numblorfact = isset($params['numblorfact']) 	? trim($params['numblorfact']) 		  : '';
		$status 	 = isset($params['status']) 		? intval($params['status']) 		  : -1;
		$envoi 	 	 = isset($params['envoi']) 			? intval($params['envoi']) 			  : -1;

		if ($date_du < '2021-11-01') { $date_du = '2021-11-01';	}

		$query_liste = '(SELECT b.`id`, b.`num_bl` AS ref, b.`date`, b.`date_envoi`,
							IF (b.`id_tiers_facturation` > 0, b.`id_tiers_facturation`, b.`id_tiers_livraison`) AS id_client, 
							IF (b.`id_tiers_facturation` > 0, tf.`nom`, tl.`nom`) AS nom_client, 
							IF (b.`bt` = 1, "BT", "BL") AS type_code,
							IF (b.`bt` = 1, "Bon de transfert", "Bon de livraison") AS type_texte,
							IF (bf.`id_facture` IS NOT NULL, 1, 0) AS statut,
							IF (b.`date_envoi` IS NOT NULL, 1, 0) AS envoi,
							IF (f.`total_ttc` > 1, f.`total_ttc`, 
								SUM(IF(p.`vendu_piece` = 1, bll.`qte`, bll.`poids`) * bll.`pu_ht`)) AS total
						FROM `pe_bl` b
							LEFT JOIN `pe_tiers` tf ON tf.`id` = b.`id_tiers_facturation`
							LEFT JOIN `pe_tiers` tl ON tl.`id` = b.`id_tiers_livraison`
							LEFT JOIN `pe_bl_facture` bf ON bf.`id_bl` = b.`id` 
							LEFT JOIN `pe_factures` f ON f.`id` = bf.`id_facture` 
							LEFT JOIN `pe_bl_lignes` bll ON bll.`id_bl` = b.`id` AND bll.`supprime` = 0
							LEFT JOIN `pe_produits` p ON p.`id` = bll.`id_produit` 
							WHERE b.`supprime` = 0 ';

		$query_liste.= $id_client > 0 ? ' AND (b.`id_tiers_facturation` = '.$id_client.' OR b.`id_tiers_livraison` = '.$id_client.') ' : '';
		$query_liste.= $id_clients != '' ? ' AND (b.`id_tiers_facturation` IN ('.$id_clients.') OR b.`id_tiers_livraison` IN ('.$id_clients.')) ' : '';
		$query_liste.= $date_du != '' ? ' AND b.`date` >= "'.$date_du.'" ' : '';
		$query_liste.= $date_au != '' ? ' AND b.`date` <= "'.$date_au.'" ' : '';
		$query_liste.= $numblorfact != '' ? ' AND b.`num_bl` LIKE "%'.$numblorfact.'%" ' : '';
		$query_liste.= $status == 0  ? ' AND bf.`id_facture` IS NULL ' : '';
		$query_liste.= $status == 1  ? ' AND bf.`id_facture` IS NOT NULL ' : '';
		$query_liste.= $envoi == 0  ? ' AND b.`date_envoi` IS NULL ' : '';
		$query_liste.= $envoi == 1  ? ' AND b.`date_envoi` IS NOT NULL ' : '';

		$query_liste.= ' GROUP BY b.`id`
		
				) UNION (
				
						 SELECT f.`id`, f.`num_facture` AS ref, f.`date`, f.`date_envoi`,
						 	IF (f.`id_tiers_facturation` > 0, f.`id_tiers_facturation`, f.`id_tiers_livraison`) AS id_client,
						 	IF (f.`id_tiers_facturation` > 0, tf.`nom`, tl.`nom`) AS nom_client, 
						 	IF (f.`montant_ht` < 0, "AV", "FA") AS type_code,
							IF (f.`montant_ht` < 0, "Avoir", "Facture") AS type_texte,
							IF (SUM(fr.`montant`) > (f.`total_ttc` - 0.02) , 1, 0) AS statut,
							IF (f.`date_envoi` IS NOT NULL, 1, 0) AS envoi,
							f.`total_ttc` AS total
						 FROM `pe_factures` f
						 	LEFT JOIN `pe_tiers` tf ON tf.`id` = f.`id_tiers_facturation`
							LEFT JOIN `pe_tiers` tl ON tl.`id` = f.`id_tiers_livraison`
							LEFT JOIN `pe_facture_reglements` fr ON fr.`id_facture` = f.`id` 
							WHERE f.`supprime` = 0 ';

		$query_liste.= $id_client > 0 ? ' AND (f.`id_tiers_facturation` = '.$id_client.' OR f.`id_tiers_livraison` = '.$id_client.') ' : '';
		$query_liste.= $id_clients != '' ? ' AND (f.`id_tiers_facturation` IN ('.$id_clients.') OR f.`id_tiers_livraison` IN ('.$id_clients.')) ' : '';
		$query_liste.= $date_du != '' ? ' AND f.`date` >= "'.$date_du.'" ' : '';
		$query_liste.= $date_au != '' ? ' AND f.`date` <= "'.$date_au.'" ' : '';
		$query_liste.= $numblorfact != '' ? ' AND f.`num_facture` LIKE "%'.$numblorfact.'%" ' : '';
		$query_liste.= $status == 0  ? ' AND SUM(fr.`montant`) < (f.`total_ttc` - 0.02) ' : '';
		$query_liste.= $status == 1  ? ' AND SUM(fr.`montant`) > (f.`total_ttc` - 0.02) ' : '';
		$query_liste.= $envoi == 0  ? ' AND f.`date_envoi` IS NULL ' : '';
		$query_liste.= $envoi == 1  ? ' AND f.`date_envoi` IS NOT NULL ' : '';

		$query_liste.= ' GROUP BY f.`id`
			 	) ORDER BY id_client, date ';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			if ((int)$donnee['id_client'] == 0) { continue; }
			$liste[] = new Gesdoc($donnee);
		}

		// Intégration des documents associés
		foreach ($liste as $i => $gesdoc) {

			$query_docs = in_array($gesdoc->getType_code(), ['BL', 'BT'])
				? 'SELECT f.`id`, f.`num_facture` AS ref FROM `pe_factures` f JOIN `pe_bl_facture` bf ON bf.`id_facture` = f.`id` WHERE bf.`id_bl` = ' . $gesdoc->getId()
				: 'SELECT b.`id`, b.`num_bl` AS ref FROM `pe_bl` b JOIN `pe_bl_facture` bf ON bf.`id_bl` = b.`id` WHERE bf.`id_facture` = ' . $gesdoc->getId(). ' UNION SELECT f.`id`, f.`num_facture` AS ref FROM `pe_factures` f JOIN `pe_facture_lignes` fl ON fl.`id_facture_avoir` = f.`id` WHERE fl.`id_facture` = '.$gesdoc->getId();

			$query2 = $this->db->prepare($query_docs);
			$query2->execute();
			$associes = [];
			foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {
				$associes[(int)$donnee2['id']] = $donnee2['ref'];
			}

			$gesdoc->setAssocies($associes);
			$liste[$i] = $gesdoc;
		}

		return $liste;

	} // FIN méthode

} // FIN classe