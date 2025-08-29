<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2020 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet OrdersPrestashop
Généré par CBO FrameWork le 21/05/2021 à 16:40:19
------------------------------------------------------*/
class OrdersPrestashopManager {

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

	// Retourne la liste des OrdersPrestashop
	public function getListeOrdersPrestashop($params = []) {

		$start = isset($params['start'])          ? intval($params['start'])          : 0;
		$nb    = isset($params['nb_result_page']) ? intval($params['nb_result_page']) : 10000000;
		$rech    = isset($params['rech']) ? trim($params['rech']) : '';
		$date_du    = isset($params['date_du']) ? trim($params['date_du']) : '';
		$date_au    = isset($params['date_au']) ? trim($params['date_au']) : '';
		$traites    = isset($params['traites']) ? boolval($params['traites']) : false;
		if ($date_du != '' && !Outils::verifDateSql(Outils::dateFrToSql($date_du))) {
			$date_du = '';
		}
		if ($date_au != '' && !Outils::verifDateSql(Outils::dateFrToSql($date_au))) {
			$date_au = '';
		}

		$query_liste = "SELECT SQL_CALC_FOUND_ROWS `id`, `id_order`, `reference`, `total_ht`, `total_ttc`, `total_produits_ht`, `total_produits_ttc`, `reductions_ht`, `reductions_ttc`, `traitee`, `livraison_ht`, `livraison_ttc`, `id_adresse`, `id_client`, `id_transporteur`, `transporteur`, `adresse`, `nom_client`, `date_facture`, `date_import` FROM `pe_ps_orders` WHERE 1 ";

		$query_liste.= $date_du != '' ? ' AND  `date_facture` >= "'.Outils::dateFrToSql($date_du).'" ' : '';
		$query_liste.= $date_au != '' ? ' AND  `date_facture` <= "'.Outils::dateFrToSql($date_au).'" ' : '';
		$query_liste.= $rech != '' ? ' AND (`reference` LIKE "%'.$rech.'%" OR `nom_client` LIKE "%'.$rech.'%" OR `transporteur` LIKE "%'.$rech.'%") ' : '';
		$query_liste.= !$traites ? ' AND `traitee` = 0 ' : '';
		$query_liste.= " ORDER BY `date_facture` DESC ";
		$query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$order = new OrderPrestashop($donnee);

			// Intégration des order_details
			$order_details = $this->getListeOrderDetailPrestashops(['id_order' => $order->getId_order()]);
			$order->setOrder_details($order_details);

			$liste[] = $order;
		}
		return $liste;

	} // FIN liste des OrdersPrestashop


	// Retourne un OrdersPrestashop
	public function getOrdersPrestashop($id, $isIdOrder = false) {

		$champ = $isIdOrder ? 'id_order' : 'id';

		$query_object = "SELECT `id`, `id_order`, `reference`, `total_ht`, `total_ttc`, `total_produits_ht`, `total_produits_ttc`, `reductions_ht`, `reductions_ttc`, `livraison_ht`, `livraison_ttc`, `id_adresse`, `id_client`, `id_transporteur`, `transporteur`, `adresse`, `nom_client`, `date_facture`, `date_import`,`traitee` 
                FROM `pe_ps_orders` WHERE `".$champ."` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee) ? new OrderPrestashop($donnee) : false;

	} // FIN get OrdersPrestashop

	// Enregistre & sauvegarde (Méthode Save)
	public function saveOrdersPrestashop(OrderPrestashop $objet) {

		$table      = 'pe_ps_orders'; // Nom de la table
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

	// Retourne la liste des OrderDetailPrestashop
	public function getListeOrderDetailPrestashops($params = []) {

		$start = isset($params['start'])          ? intval($params['start'])          : 0;
		$nb    = isset($params['nb_result_page']) ? intval($params['nb_result_page']) : 10000000;
		$id_order = isset($params['id_order']) ? intval($params['id_order']) : 0;
		$ids_order = isset($params['ids_order']) && is_array($params['ids_order']) ? $params['ids_order'] : [];
		$ids_order_detail = isset($params['ids_order_detail']) && is_array($params['ids_order_detail']) ? $params['ids_order_detail'] : [];
		$no_bl = isset($params['no_bl']) ? boolval($params['no_bl']) : false;

		$query_liste = "SELECT SQL_CALC_FOUND_ROWS `id`, `id_order`, `nom`, `ref`, `qte`, `pu_ht`, `pu_ttc`, `id_bl_ligne` FROM `pe_ps_order_details` WHERE 1 ";

		$query_liste.= $id_order > 0 ? ' AND `id_order` =' . $id_order : '';
		$query_liste.= count($ids_order) > 0 ? ' AND `id_order` IN (' . implode(',',$ids_order).')' : '';
		$query_liste.= count($ids_order_detail) > 0 ? ' AND `id` IN (' . implode(',',$ids_order_detail).')' : '';
		$query_liste.= $no_bl ? ' AND (`id_bl_ligne` = 0 OR `id_bl_ligne`  IS NULL) ' : '';

		$query_liste.= " ORDER BY `id` DESC ";  $query_liste.= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$od = new OrderDetailPrestashop($donnee);
			$id_bl = 0;
			$num_bl = '';
			if ($od->getId_bl_ligne() > 0) {
				$blInfos_query = 'SELECT b.`id`, b.`num_bl` FROM `pe_bl` b JOIN `pe_bl_lignes` l ON l.`id_bl` = b.`id` WHERE l.`id` = ' . $od->getId_bl_ligne();
				$query2 = $this->db->prepare($blInfos_query);
				$query2->execute();
				$blInfos = $query2->fetch(PDO::FETCH_ASSOC);
				$id_bl = isset($blInfos['id']) ? intval($blInfos['id']) : 0;
				$num_bl = isset($blInfos['num_bl']) ? $blInfos['num_bl'] : '';
			}
			$od->setId_bl($id_bl);
			$od->setNum_bl($num_bl);
			$liste[] = $od;
		}
		return $liste;

	} // FIN liste des OrderDetailPrestashop


	// Retourne un OrderDetailPrestashop
	public function getOrderDetailPrestashop($id) {

		$query_object = "SELECT `id`, `id_order`, `nom`, `ref`, `qte`, `pu_ht`, `pu_ttc` , `id_bl_ligne`
                FROM `pe_ps_order_details` WHERE `id` = " . (int)$id;
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);

		if (!$donnee || empty($donnee)) { return false; }

		$od = new OrderDetailPrestashop($donnee);
		$id_bl = 0;
		$num_bl = '';
		if ($od->getId_bl_ligne() > 0) {
			$blInfos_query = 'SELECT b.`id`, b.`num_bl` FROM `pe_bl` b JOIN `pe_bl_lignes` l ON l.`id_bl` = b.`id` WHERE l.`id` = ' . $od->getId_bl_ligne();
			$query2 = $this->db->prepare($blInfos_query);
			$query2->execute();
			$blInfos = $query->fetch(PDO::FETCH_ASSOC);
			$id_bl = isset($blInfos['id']) ? intval($blInfos['id']) : 0;
			$num_bl = isset($blInfos['num_bl']) ? $blInfos['num_bl'] : '';
		}
		$od->setId_bl($id_bl);
		$od->setNum_bl($num_bl);

		return $od;

	} // FIN get OrderDetailPrestashop

	// Enregistre & sauvegarde (Méthode Save)
	public function saveOrderDetailPrestashop(OrderDetailPrestashop $objet) {

		$table      = 'pe_ps_order_details'; // Nom de la table
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

	// Retourne les ID order prestashop qui ont été intégrés dans iprex et datant à minima de la date passée
	public function getIdsOrdersPrestashop($date_min) {

		if (!Outils::verifDateSql($date_min)) { return []; }

		$query_liste = 'SELECT DISTINCT `id_order` FROM `pe_ps_orders` WHERE `date_import` IS NOT NULL AND  `date_import` != "0000-00-00 00:00:00" AND `date_facture` >= "'.$date_min.'"';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = intval($donnee['id_order']);
		}
		return $liste;
	} // FIN méthode


	public function getNbLignesTraitees(OrderPrestashop $order) {

		$orderDetails =  !$order->getOrder_details() || empty($order->getOrder_details())
			? $this->getListeOrderDetailPrestashops(['id_order' => $order->getId_order()])
			: $order->getOrder_details();

		$nbTraites = 0;
		foreach ($orderDetails as $orderDetail) {
			if ($orderDetail->isTraitee()) {
				$nbTraites++;
			}
		}
		return $nbTraites;

	} // FIN méthode


	// Nettoie la laision des id_lignes_bl sur les order_detail pour les lignes de bl ou bl supprimés
	public function cleanIdOrderDetailsLignesBl() {

		$query_liste = 'SELECT od.`id` 
							FROM `pe_ps_order_details` od
							  JOIN `pe_ps_orders` o ON o.`id` = od.`id_order`
							  LEFT JOIN `pe_bl_lignes` bll ON bll.`id` = od.`id_bl_ligne`
							  LEFT JOIN `pe_bl` bl ON bl.`id` = bll.`id_bl`
							WHERE od.`id_bl_ligne` > 0
							  AND o.`traitee` = 0
							  AND (bll.`supprime` = 1 
							    OR bll.`id` IS NULL 
							    OR bl.`id` IS NULL)';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$liste[] = intval($donnee['id']);
		}
		if (empty($liste)) { return true; }

		$query_upd = 'UPDATE `pe_ps_order_details` SET `id_bl_ligne` = 0 WHERE `id` IN ('.implode(',', $liste).')' ;
		$query2 = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query2->execute();

	} // FIN méthode

	public function getOrderLigneByIdLigneBl($id_ligne_bl) {

		$query_object = "SELECT od.`id`, od.`id_order`, od.`nom`, od.`ref`, od.`qte`, o.`reference` AS reference_order, o.`nom_client`, od.`id_bl_ligne`
                			FROM `pe_ps_order_details` od
								JOIN `pe_ps_orders` o ON o.`id_order` = od.`id_order`
								WHERE od.`id_bl_ligne` = " . (int)$id_ligne_bl;
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);

		if (!$donnee || empty($donnee)) { return false; }

		return new OrderDetailPrestashop($donnee);


	} // FIN méthode

	public function getOrderLigneByIdLigneFacture($id_ligne_facture) {

		$query_object = "SELECT od.`id`, od.`id_order`, od.`nom`, od.`ref`, od.`qte`, o.`reference` AS reference_order, o.`nom_client`, od.`id_bl_ligne`
                			FROM `pe_ps_order_details` od
								JOIN `pe_ps_orders` o ON o.`id_order` = od.`id_order`
								JOIN `pe_facture_ligne_bl` l ON l.`id_ligne_bl` = od.`id_bl_ligne`
								WHERE l.`id_ligne_facture` = " . (int)$id_ligne_facture;
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);

		if (!$donnee || empty($donnee)) { return false; }

		return new OrderDetailPrestashop($donnee);

	} // FIN méthode

	public function getOrderLigneByIdBl($id_bl) {

		$query_object = "SELECT od.`id`, od.`id_order`, od.`nom`, od.`ref`, od.`qte`, o.`reference` AS reference_order, o.`nom_client`, od.`id_bl_ligne`
                			FROM `pe_ps_order_details` od
								JOIN `pe_ps_orders` o ON o.`id_order` = od.`id_order`
								JOIN `pe_bl_lignes` b ON b.`id` = od.`id_bl_ligne`
								WHERE b.`id_bl` = " . (int)$id_bl;
		$query = $this->db->prepare($query_object);
		if (!$query->execute()) { return false; }
		$donnee = $query->fetch(PDO::FETCH_ASSOC);

		if (!$donnee || empty($donnee)) { return false; }

		return new OrderDetailPrestashop($donnee);

	} // FIN méthode

	public function getOrdersPrestashopByFacture(Facture $facture) {

		$query_liste = 'SELECT od.`id_order`
							FROM `pe_ps_order_details` od 
								JOIN `pe_bl_lignes` bll ON bll.`id` = od.`id_bl_ligne`
								JOIN `pe_bl_facture` blf ON blf.`id_bl` = bll.`id_bl`
							WHERE blf.`id_facture` = ' . $facture->getId();
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$order = $this->getOrdersPrestashop($donnee['id_order'], true);

			if ($order instanceof OrderPrestashop) {

				$order_details = $this->getListeOrderDetailPrestashops(['id_order' => $order->getId_order()]);
				$order->setOrder_details($order_details);
				$liste[] = $order;
			}
		}
		return $liste;

	} // FIN méthode

	public function getNbLignesOrderPrestashopInFacture(OrderPrestashop $order, Facture $facture) {

		$query_nb = 'SELECT COUNT(*) AS nb
						FROM `pe_facture_lignes` fl 
						JOIN `pe_bl_facture` blf ON blf.`id_facture` = fl.`id_facture` AND fl.`id_facture` = '.$facture->getId().'
						JOIN `pe_bl_lignes` bll ON bll.`id_bl` = blf.`id_bl`                      
                        JOIN `pe_ps_order_details` od ON od.`id_bl_ligne` = bll.`id` AND od.`id_order` = ' . $order->getId_order();

		$query = $this->db->prepare($query_nb);
		$query->execute();
		$res = $query->fetch();

		return isset($res['nb']) ? intval($res['nb']) : 0;

	} // FIN méthode


	public function getLivraisonsOrdersFacture(Facture $facture) {

		$query_liste = 'SELECT o.id_order, o.livraison_ht, COUNT(od.id) AS nb_produits, COUNT(fl.id) AS nb_lignes_fact_order
						FROM pe_ps_orders o
							JOIN pe_ps_order_details od ON od.id_order = o.id_order
							JOIN pe_facture_ligne_bl bf ON bf.id_ligne_bl = od.id_bl_ligne
							JOIN pe_facture_lignes fl ON fl.id = bf.id_ligne_facture
						WHERE fl.id_facture = '.$facture->getId().'
							GROUP BY od.id_order
						';

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$total_livraison = 0;

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$livraison_ht = isset($donnee['livraison_ht']) ? floatval($donnee['livraison_ht']) : 0;
			$nb_produits = isset($donnee['nb_produits']) ? intval($donnee['nb_produits']) : 1;
			$nb_lignes_fact_order = isset($donnee['nb_lignes_fact_order']) ? intval($donnee['nb_lignes_fact_order']) : 1;
			if ($nb_produits == 0) { $nb_produits = 1; }
			$livraison_par_pdt =  $livraison_ht > 0 ? $livraison_ht / $nb_produits : 0;
			$livraison_par_pdt*= $nb_lignes_fact_order;
			$total_livraison+= round($livraison_par_pdt,2);
		}
		return $total_livraison;

	} // FIN méthode

	// Si rémise sur l'order, on l'applique au prorata sur le pu_ht de la ligne de bl
	public function setRemiseLigneBlFromOrder($id_order_detail, $id_bl_ligne) {

		$query_id_order = 'SELECT `id_order` FROM `pe_ps_order_details` WHERE `id` = '.(int)$id_order_detail;
		$query0 = $this->db->prepare($query_id_order);
		if (!$query0->execute()) { return false; }
		$res_id_order = $query0->fetch(PDO::FETCH_ASSOC);
		$id_order = isset($res_id_order['id_order']) ? intval($res_id_order['id_order']) : 0;
		if ($id_order == 0) { return false; }

		// On récupère la remise HT de l'order
		$query_remise_order = 'SELECT  `reductions_ht` FROM `pe_ps_orders` WHERE `id_order` = '.$id_order;
		$query1 = $this->db->prepare($query_remise_order);
		if (!$query1->execute()) { return false; }
		$res_remise_order = $query1->fetch(PDO::FETCH_ASSOC);
		$remise_order = isset($res_remise_order['reductions_ht']) ? floatval($res_remise_order['reductions_ht']) : 0;

		// Si 0, on return
		if ($remise_order == 0) { return true; }

		// On divise la remise de l'order par le nb de produits pour avoir la remise à appliquer par produit.
		$query_nb_pdts = 'SELECT COUNT(*) AS nb FROM `pe_ps_order_details` WHERE `id_order` = '.$id_order;
		$query2 = $this->db->prepare($query_nb_pdts);
		if (!$query2->execute()) { return false; }
		$res_nb_pdts = $query2->fetch(PDO::FETCH_ASSOC);
		$nb_pdts = isset($res_nb_pdts['nb']) ? intval($res_nb_pdts['nb']) : 0;
		if ($nb_pdts == 0) { return false; }
		$remiseParProduit = round(($remise_order / $nb_pdts),2);

		// on update le pu_ht de la ligne de bl en déduisant la remise par produit
		// total_ligne = pu_ht * (si produit vendu piece alors qte sinon poids)
		// Si on récupère pour un bl_litne le pu_ht et le total_ligne, on peut faire une règle de 3 pour avoir le nouveau pu_ht en déduisant la remise_produit du total_ligne
		$query_ligne_bl = 'SELECT l.`pu_ht`, l.`qte`, l.`poids`,
								IF (p.`vendu_piece` IS NOT NULL, p.`vendu_piece`, 
									IF (p2.`vendu_piece` IS NOT NULL, p2.`vendu_piece`, p3.`vendu_piece`))
									 AS vendu_piece
							FROM `pe_bl_lignes` l	
							    	LEFT JOIN `pe_palette_composition` pc ON pc.`id` =  l.`id_compo`
									LEFT JOIN `pe_produits` p ON p.`id` =  pc.`id_produit`
									LEFT JOIN `pe_produits` p2 ON p2.`id` =  l.`id_produit`
									LEFT JOIN `pe_produits` p3 ON p3.`id` =  l.`id_produit_bl`
							WHERE l.`id` = ' . (int)$id_bl_ligne;
		$query3 = $this->db->prepare($query_ligne_bl);
		if (!$query3->execute()) { return false; }
		$res_ligne_bl = $query3->fetch(PDO::FETCH_ASSOC);
		$pu_ht = isset($res_ligne_bl['pu_ht']) ? floatval($res_ligne_bl['pu_ht']) : 0;
		$poids = isset($res_ligne_bl['poids']) ? floatval($res_ligne_bl['poids']) : 0;
		$qte = isset($res_ligne_bl['qte']) ? intval($res_ligne_bl['qte']) : 0;
		$vendu_piece = isset($res_ligne_bl['vendu_piece']) ? intval($res_ligne_bl['vendu_piece']) : 0;

		$pu_reduit = round($pu_ht - $remiseParProduit,2);

		$query_update = 'UPDATE `pe_bl_lignes` SET `pu_ht` = '.$pu_reduit.' WHERE `id` = ' . (int)$id_bl_ligne;
		$query4 = $this->db->prepare($query_update);
		if (!$query4->execute()) { return false; }
		Outils::saveLog($query_update);

		$logManager = new LogManager($this->db);
		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("Maj pu_ht (".$pu_ht." -> ".$pu_reduit.") ligne_bl #".$id_bl_ligne." suite association order_detail #".$id_order_detail." : remise total order = ".$remise_order." /  nb_pdts_order ".$nb_pdts." = remise/pdt_order ".$remiseParProduit.".  ".$pu_ht." - ".$remiseParProduit." = ".$pu_reduit);
		$logManager->saveLog($log);
		return true;

	} // FIN méthode

	public function affectePuQteFromOrderDetailToBlLigne($id_order_detail,$id_bl_ligne) {

		$query_update = 'UPDATE `pe_bl_lignes` SET `qte` = (SELECT `qte` FROM `pe_ps_order_details` WHERE `id` = '.$id_order_detail.'), `pu_ht` = (SELECT `pu_ht` FROM `pe_ps_order_details` WHERE `id` = '.$id_order_detail.') WHERE `id` = ' . (int)$id_bl_ligne;

		$query4 = $this->db->prepare($query_update);
		if (!$query4->execute()) { return false; }
		Outils::saveLog($query_update);
		return true;
	} // FIN méthode

	public function getNbOrdersDetailsPrestashop($id_order) {

		$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_ps_order_details` WHERE `id_order` = ' . (int)$id_order;
		$query = $this->db->prepare($query_nb);
		$query->execute();
		$res = $query->fetch();

		return isset($res['nb']) ? intval($res['nb']) : 0;

	} // FIN méthode

} // FIN classe