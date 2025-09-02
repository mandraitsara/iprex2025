<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Manager de l'objet BL
Généré par CBO FrameWork le 06/03/2020 à 14:50:20
------------------------------------------------------*/
class BlManager
{

	protected    $db, $nb_results;

	public function __construct($db)
	{
		$this->setDb($db);
	}

	/* ----------------- GETTERS ----------------- */
	public function getNb_results()
	{
		return $this->nb_results;
	}

	/* ----------------- SETTERS ----------------- */
	public function setDb(PDO $db)
	{
		$this->db = $db;
	}

	public function setNb_results($nb_results)
	{
		$this->nb_results = (int)$nb_results;
	}

	/* ----------------- METHODES ----------------- */

	// Retourne la liste des Bl
	public function getListeBl($params = [])
	{

		$id 		= isset($params['id']) 			? intval($params['id']) 		: 0;
		$ids 		= isset($params['ids']) && is_array($params['ids']) ? $params['ids'] 				: [];
		$id_tiers 	= isset($params['id_client']) 	? intval($params['id_client']) 	: 0;
		$ids_tiers 	= isset($params['id_clients']) 	&& is_array($params['id_clients']) ? $params['id_clients'] : [];
		$id_packing	= isset($params['id_packing']) 	? intval($params['id_packing']) : 0;
		$num_bl 	= isset($params['num_bl']) 		? trim($params['num_bl']) 		: '';
		$num_cmd 	= isset($params['num_cmd']) 	? trim($params['num_cmd']) 		: '';
		$chiffrage 	= isset($params['chiffre']) 	? intval($params['chiffre']) 	: -1;
		$facture 	= isset($params['facture']) 	? intval($params['facture']) 	: -1; // Si associé à une facture
		$id_facture = isset($params['id_facture']) 	? intval($params['id_facture']) : 0;
		$statut 	= isset($params['statut']) 		? intval($params['statut']) 	: -1;
		$statuts 	= isset($params['statuts']) 	? trim($params['statuts']) 		: '';
		$statut_not	= isset($params['statut_not']) 	? trim($params['statut_not']) 	: '';
		$lignes		= isset($params['lignes'])		? boolval($params['lignes']) 	: true;
		$factures	= isset($params['factures'])	? boolval($params['factures']) 	: true;
		$palettes 	= isset($params['palettes']) 	? boolval($params['palettes']) 	: false;
		$produits 	= isset($params['produits']) 	? boolval($params['produits']) 	: false;
		$colis 		= isset($params['colis']) 		? boolval($params['colis']) 	: false;
		$poids 		= isset($params['poids']) 		? boolval($params['poids']) 	: false;
		$total 		= isset($params['total']) 		? boolval($params['total']) 	: false;
		$show_supprimes 		= isset($params['show_supprimes']) 		? boolval($params['show_supprimes']) 	: false;
		$supprime   = isset($params['supprime']) 	? intval($params['supprime']) 	: 0;
		$bt   		= isset($params['bt']) 			? intval($params['bt']) 		: -1;
		$du			= isset($params['du']) && Outils::verifDateSql($params['du']) ? $params['du'] : '2021-11-01';
		$au			= isset($params['au']) && Outils::verifDateSql($params['au']) ? $params['au'] : '';

		

		if ($du < '2021-11-01') {
			$du = '2021-11-01';
		}

		if (isset($ids_tiers[0]) && $ids_tiers[0] == "") {
			$ids_tiers = [];
		}

		// Pagination
		$start 				= isset($params['start']) 			? intval($params['start']) 			: 0;
		$nb 				= isset($params['nb_result_page']) 	? intval($params['nb_result_page']) : 10000000;

		$query_liste = 'SELECT SQL_CALC_FOUND_ROWS bl.`id`, bl.`id_tiers_livraison`, bl.`id_tiers_facturation`, bl.`id_tiers_transporteur`, bl.`id_adresse_facturation`, bl.`id_adresse_livraison`, bl.`date`, bl.`date_add`, bl.`date_livraison`, bl.`num_cmd`, bl.`supprime`, bl.`nom_client`, bl.`statut`, bl.`regroupement`, bl.`chiffrage`, bl.`id_lot_negoce`, bl.`id_packing_list`, tc.`nom` AS nom_client, bl.`bt`, bl.`num_bl`, IFNULL(bl.`date_envoi`, "") AS date_envoi
				FROM `pe_bl` bl 
					LEFT JOIN `pe_tiers` tc ON tc.`id` = bl.`id_tiers_livraison` ';

		$query_liste .= $facture > -1 ? ' LEFT JOIN `pe_bl_facture` f ON f.`id_bl` = bl.`id` ' : '';

		$query_liste .= " WHERE 1 ";

		$query_liste .= !$show_supprimes ? " AND bl.`supprime` = " . $supprime . " " : "";
		$query_liste .= $statut == -1 && $supprime == 1 ? " AND bl.`supprime` = " . $supprime . " " : "";
		$query_liste .= $id == 1 ? ' AND bl.`id` = ' . $id : '';
		$query_liste .= $facture == 1 ? ' AND f.`id_facture` IS NOT NULL ' : '';
		$query_liste .= $facture == 0 ? ' AND f.`id_facture` IS NULL ' : '';
		$query_liste .= $chiffrage > -1 ? ' AND bl.`chiffrage` = ' . $chiffrage : '';
		$query_liste .= $id_tiers > 0 ? ' AND (bl.`id_tiers_livraison` = ' . $id_tiers . ' OR bl.`id_tiers_facturation` = ' . $id_tiers . ') ' : '';
		$query_liste .= !empty($ids_tiers) ? ' AND (bl.`id_tiers_livraison` IN (' . implode(',', $ids_tiers) . ') OR bl.`id_tiers_facturation` IN (' . implode(',', $ids_tiers) . ')) ' : '';
		$query_liste .= $num_cmd != '' ? ' AND bl.`num_cmd` LIKE "%' . $num_cmd . '%" ' : '';
		$query_liste .= $num_bl != '' ? ' AND bl.`num_bl` LIKE "%' . $num_bl . '%" ' : '';
		$query_liste .= $id_facture > 0 ? " AND bl.`id` IN ( SELECT `id_bl` FROM `pe_bl_facture` WHERE `id_facture` = " . $id_facture . " ) " : "";
		$query_liste .= $id_packing > 0 ? " AND bl.`id_packing_list` = " . $id_packing . " " : "";
		$query_liste .= $statut > -1 ? " AND bl.`statut` = " . $statut : "";
		$query_liste .= $bt > -1 ? " AND bl.`bt` = " . $bt : "";
		$query_liste .= $statuts != '' ? " AND bl.`statut` IN (" . $statuts . ")" : "";
		$query_liste .= $statut_not != '' ? " AND bl.`statut` NOT IN (" . $statut_not . ")" : "";
		$query_liste .= !empty($ids) != '' ? " AND bl.`id` IN (" . implode(',', $ids) . ")" : "";
		$query_liste .= $du != '' ? ' AND bl.`date` >= "' . $du . '" ' : '';
		$query_liste .= $au != '' ? ' AND bl.`date` <= "' . $au . '" ' : '';

		$query_liste .= " ORDER BY ";
		$query_liste .= !empty($ids_tiers) ? " bl.`id_tiers_facturation`, " : "";
		$query_liste .= " bl.`id` DESC ";
		$query_liste .= 'LIMIT ' . $start . ',' . $nb;
		$query = $this->db->prepare($query_liste);

		
		$query->execute();

		$this->setNb_results($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

		$liste = [];		

		$facturesManager = new FacturesManager($this->db);

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {	
			

			$bl = new Bl($donnee);
			if (!$bl instanceof Bl) {
				continue;
			}

			// On rattache les lignes du BL
			$lignes_bl = [];
			if ($lignes) {
				$lignes_bl = $this->getListeBlLignes(['id_bl' => $bl->getId(), 'regroupement' => $bl->getRegroupement()]);
			}
			$bl->setLignes($lignes_bl);

			// On rattache les factures liés au BL
			$factures_bl = [];
			if ($factures) {
				$facture_bl = $facturesManager->getListeFactures(['id_bl' => $bl->getId(), 'bls' => false]);
			}
			$bl->setFactures($facture_bl);

			// On rattache le nb de palettes
			if ($palettes) {
				$bl->setNb_palettes($this->getNbPaletteBl($bl));
			}

			// On rattache le nb de produits
			if ($produits) {
				$bl->setNb_produits($this->getNbProduitsBl($bl));
			}

			// On rattache le nb de colis
			if ($colis) {
				$bl->setNb_colis($this->getNbColisBl($bl));
			}

			// On rattache le poids
			if ($poids) {
				$bl->setPoids($this->getPoidsBl($bl));
			}

			// On rattache le total HT
			if ($total) {
				$bl->setTotal($this->getTotalHt($bl));
			}

			$liste[] = $bl;
		}		
		return $liste;
	} // FIN liste

	// Retourne un Bl
	public function getBl($bl, $details = true, $pdt_simpes = false, $get_facture = true)
	{

		$id = $bl instanceof Bl ? $bl->getId() : intval($bl);

		$query_object = 'SELECT b.`id`, b.`id_tiers_livraison`, b.`id_tiers_facturation`, b.`id_tiers_transporteur`, b.`id_adresse_facturation`, b.`id_adresse_livraison`, b.`date`, b.`date_add`, b.`num_cmd`, b.`supprime` , b.`nom_client`, b.`statut`, b.`regroupement`, b.`chiffrage`, b.`id_lot_negoce`, b.`id_packing_list`, b.`bt`, b.`num_bl`, b.`date_livraison`, IFNULL(b.`date_envoi`, "") AS date_envoi,
       			IF (tl.`id_langue` IS NOT NULL AND tl.`id_langue` > 0, tl.`id_langue`, 
                    IF (tf.`id_langue` > 0, tf.`id_langue`, 1)
                    ) AS id_langue  
                FROM `pe_bl` b
					LEFT JOIN `pe_tiers` tl ON tl.`id` = b.`id_tiers_livraison`
					LEFT JOIN `pe_tiers` tf ON tf.`id` = b.`id_tiers_facturation`
				WHERE b.`id` = ' . (int)$id;


		$query = $this->db->prepare($query_object);

		if ($query->execute()) {

			$facturesManager = new FacturesManager($this->db);

			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);

			$bl =  $donnee && isset($donnee[0]) ? new Bl($donnee[0]) : false;

			if (!$bl instanceof Bl) {
				return false;
			}

			// Rattachement des sous-objets

			if ($details) {
				$params = [
					'id_bl' => $bl->getId(),
					'regroupement' => $bl->getRegroupement(),
					'pdts_simples' => $pdt_simpes
				];

				// On rattache les lignes du BL
				$lignes = $this->getListeBlLignes($params);
				$bl->setLignes($lignes);

				// On rattache la facture liée au BL
				if ($get_facture) {
					$factures = $facturesManager->getListeFactures(['id_bl' => $bl->getId(), 'bls' => false]);
					$bl->setFactures($factures);
				}
			} // FIN rattachement des sous-objets

			return $bl;
		} else {
			return false;
		}
	} // FIN get

	// Enregistre & sauvegarde (Méthode Save)
	public function saveBl(Bl $objet)
	{

		$table      = 'pe_bl'; // Nom de la table
		$champClef  = 'id'; // Nom du champ clef

		// FIN Configuration

		$getter     = 'get' . ucfirst(strtolower($champClef));
		$setter     = 'set' . ucfirst(strtolower($champClef));

		if ($objet->$getter() == '' && !empty($objet->attributs)) {

			$query_add = 'INSERT INTO `' . $table . '` (';

			foreach ($objet->attributs as $attribut) {
				$query_add .= '`' . $attribut . '`,';
			}
			$query_add = substr($query_add, 0, -1);
			$query_add .= ') VALUES (';

			foreach ($objet->attributs as $attribut) {
				$query_add .= ':' . strtolower($attribut) . ' ,';
			}
			$query_add = substr($query_add, 0, -1);
			$query_add .= ')';

			$query = $this->db->prepare($query_add);
			$query_log = $query_add;

			foreach ($objet->attributs as $attribut) {
				$attributget = 'get' . ucfirst($attribut);
				$query->bindvalue(':' . strtolower($attribut), $objet->$attributget());
				$dq = is_numeric($objet->$attributget()) ? '' : '"';
				$query_log = str_replace(':' . strtolower($attribut) . ' ', $dq . $objet->$attributget() . $dq . ' ', $query_log);
			}

			if ($query->execute()) {
				$objet->$setter($this->db->lastInsertId());
				Outils::saveLog($query_log);
				return $objet->$getter();
			}
		} else if ($objet->$getter() != '' && !empty($objet->attributs)) {

			$query_upd = 'UPDATE `' . $table . '` SET ';

			foreach ($objet->attributs as $attribut) {
				$query_upd .= '`' . $attribut . '` = :' . strtolower($attribut) . ' ,';
			}
			$query_upd = substr($query_upd, 0, -1);
			$query_upd .= ' WHERE ' . $champClef . ' = ' . $objet->$getter();

			$query = $this->db->prepare($query_upd);
			$query_log = $query_upd;

			foreach ($objet->attributs as $attribut) {
				$attributget = 'get' . ucfirst($attribut);
				$query->bindvalue(':' . strtolower($attribut), $objet->$attributget());
				$dq = is_numeric($objet->$attributget()) ? '' : '"';
				$query_log = str_replace(':' . strtolower($attribut) . ' ', $dq . $objet->$attributget() . $dq . ' ', $query_log);
			}

			try {
				$query->execute();
				Outils::saveLog($query_log);
				return true;
			} catch (PDOExeption $e) {
				return false;
			}
		}
		return false;
	} // FIN méthode

	// Retourne la liste des BlLigne
	public function getListeBlLignes($params = [])
	{

		$id_bl = isset($params['id_bl']) ? intval($params['id_bl']) : 0;
		$iso_langue = isset($params['iso']) ? trim(strtolower($params['iso'])) : 'fr';
		$id_langue = isset($params['id_langue']) ? intval($params['id_langue']) : 1;
		$regroupement = isset($params['regroupement']) && intval($params['regroupement']) == 1;
		$pdts_simples = isset($params['pdts_simples']) ? boolval($params['pdts_simples']) : false;

		$jointure_pdt = $pdts_simples ? 'LEFT ' : '';

		$query_liste = 'SELECT DISTINCT l.`id`, l.`id_bl`, l.`id_compo`, l.`date_add`, l.`supprime`, l.`id_pdt_negoce`, l.`id_pays`,
                						IF (p3.`id` IS NOT NULL, p3.`code`, IF (p2.`id` IS NOT NULL, p2.`code`, 
                						    IF (p.`code` IS NOT NULL, p.`code`, ""))) AS code,
                						IF (p2.`id` IS NOT NULL, p2.`id`,
                						    IF (p.`id` IS NOT NULL, p.`id`, 0)) AS id_produit,
       									IF (pc.`designation` IS NOT NULL AND pc.`designation` != "", pc.`designation`,
       									    IF (l.`libelle` IS NOT NULL AND l.`libelle` != "", l.`libelle`, "")) AS designation,
       									IF (l.`num_palette` IS NOT NULL AND l.`num_palette` != "" AND l.`num_palette` != "0", l.`num_palette`,
       									    IF ( pal.`numero` IS NOT NULL,  pal.`numero`, pal2.`numero`)) AS numero_palette,
                						l.`num_palette`,
                						IF (orig.`nom` IS NOT NULL, orig.`nom`, IF (orig2.`nom` IS NOT NULL,orig2.`nom`,IF (orig3.`nom` IS NOT NULL,orig3.`nom`,""))) AS origine,
                						IF (l.`id_palette` > 0, l.`id_palette`, pc.`id_palette`) AS id_palette,
                						l.`id_produit_bl`,
                						IF (l.`numlot` != "", l.`numlot`, 
                							IF (lot_prod.`numlot` IS NOT NULL, CONCAT(lot_prod.`numlot`,IFNULL(pdtf.`quantieme`,"")) ,
                							    CONCAT(lot_frais.`numlot`,IFNULL(frac.`quantieme`,"")))) AS numlot,
                						IF (l.`id_lot` > 0, l.`id_lot`, 
                						    IF (lot_prod.`numlot` IS NOT NULL, lot_prod.`id` , lot_frais.`id`)) AS id_lot,
                						IF (l.`poids` > 0, l.`poids`, pc.`poids`) AS poids,
                						l.`nb_colis`, l.`qte`,
                						IF (l.`pu_ht` > 0, l.`pu_ht`, 
                						    IF (tc.`prix` IS NOT NULL AND tc.`prix` > 0, tc.`prix`, 
                						        IF(tg.`prix` > 0, tg.`prix`, 0))) AS pu_ht, 
                						IF (l.`tva` > 0, l.`tva`, t.`taux`) AS tva, l.`libelle`,
                						IF (pal.`id_poids_palette` IS NOT NULL, pal.`id_poids_palette`, pal2.`id_poids_palette`) AS id_poids_palette,
                						pp.`nom` as poids_palette_type, pp.`poids` AS poids_palette,
                  						IF (frd.`date_sortie` IS NOT NULL,
       										DATE_ADD(frd.`date_sortie`, INTERVAL p2.`nb_jours_dlc` DAY),
                  						    IF (frac.`dlc` IS NOT NULL, frac.`dlc`, DATE_ADD(l.`date_add`, INTERVAL p2.`nb_jours_dlc` DAY) )
                  						    ) AS dlc  ,
                						IF (frac.`dlc` IS NOT NULL OR lot_hs.`id` IS NOT NULL, "dlc", "dluo") AS type_dlc,
                						IF (lot_hs.`id` IS NOT NULL, "1", "0") AS hors_stock,
                						IF (lot_prod.`date_abattage` IS NOT NULL, lot_prod.`date_abattage`, IF (lot_frais.`date_abattage` IS NOT NULL, lot_frais.`date_abattage`, lot_hs.`date_abattage`) ) AS date_abattage,
                						IF (frac.`id` IS NOT NULL OR lot_hs.`id` IS NOT NULL, "cond", "cong") AS trad_traitement,
                						IF (frd.`date_entree` IS NOT NULL, DATE(frd.`date_entree`), DATE(pc.`date`)) AS date_traitement,
                						IF (p.`vendu_piece` IS NOT NULL, p.`vendu_piece`, IF (p2.`vendu_piece` IS NOT NULL, p2.`vendu_piece`, p3.`vendu_piece`)) AS vendu_piece,
                						IF (pc.`id_frais` > 0,1,0) AS is_frais,
                						l.`id_frs`
									FROM `pe_bl_lignes` l
										' . $jointure_pdt . ' JOIN `pe_palette_composition` pc ON pc.`id` =  l.`id_compo`
										' . $jointure_pdt . ' JOIN `pe_palettes` pal ON pal.`id` =  pc.`id_palette`
										LEFT JOIN `pe_palettes` pal2 ON pal2.`id` =  l.`id_palette`
										LEFT JOIN `pe_poids_palettes` pp ON  pp.`id` = pal.`id_poids_palette`
										
										LEFT JOIN `pe_produits` p ON p.`id` =  pc.`id_produit`
										LEFT JOIN `pe_produits` p2 ON p2.`id` =  l.`id_produit`
										LEFT JOIN `pe_produits` p3 ON p3.`id` =  l.`id_produit_bl`
										' . $jointure_pdt . ' JOIN `pe_tiers` clt ON clt.`id` = pc.`id_client`
										LEFT JOIN `pe_tarif_client` tc ON tc.`id_tiers` = clt.`id` AND tc.`id_produit` = pc.`id_produit`
										LEFT JOIN `pe_tarif_client` tg ON tc.`id_tiers_groupe` = clt.`id_groupe` AND tg.`id_produit` = pc.`id_produit`
										LEFT JOIN `pe_froid_produits` pdtf ON pdtf.`id_lot_pdt_froid` = pc.`id_lot_pdt_froid`
										LEFT JOIN `pe_froid` frd ON frd.`id` = pdtf.`id_froid`
										LEFT JOIN `pe_frais` frac ON frac.`id` = pc.`id_frais`
										LEFT JOIN `pe_lots` lot_prod ON lot_prod.`id` = pdtf.`id_lot`
										LEFT JOIN `pe_lots` lot_frais ON lot_frais.`id` = frac.`id_lot`
										LEFT JOIN `pe_lots` lot_hs ON lot_hs.`id` = l.`id_lot`
										LEFT JOIN `pe_pays_trad` orig ON orig.`id_pays` = l.`id_pays` AND orig.`id_langue` = ' . $id_langue . '
										LEFT JOIN `pe_pays_trad` orig2 ON orig2.`id_pays` = lot_prod.`id_origine` AND orig2.`id_langue` = ' . $id_langue . '
										LEFT JOIN `pe_pays_trad` orig3 ON orig3.`id_pays` = lot_hs.`id_origine` AND orig3.`id_langue` = ' . $id_langue . '
										LEFT JOIN `pe_taxes` t ON t.`id` = p.`id_taxe`
	
									WHERE l.`supprime` = 0 ';

		$query_liste .= $id_bl > 0 ? " AND l.`id_bl` = " . $id_bl : "";
		$query_liste .= " ORDER BY pc.`id_palette`, l.`id` ASC";

		$query = $this->db->prepare($query_liste);
		$query->execute();

		$produitsManager = new ProduitManager($this->db);

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {

			$ligne = new BlLigne($donnee);

			// Rattachement de l'objet Produit
			$id_pdt_ligne = intval($ligne->getId_produit_bl()) > 0 ? $ligne->getId_produit_bl() : $ligne->getId_produit();
			$pdt = $produitsManager->getProduit($id_pdt_ligne);
			$ligne->setProduit($pdt);

			$liste[] = $ligne;
		}

		// Ici, si le BL est regroupé, on va regrouper totues les lignes qui ont le meme id_pdt, id_lot et id_palette en cumlant les donénes
		if ($regroupement) {

			$produitManager = new ProduitManager($this->db);

			$liste_regroupee = [];

			$pu = [];
			foreach ($liste as $ligne) {
				$idpdt = $ligne->getId_produit_bl() > 0 ? $ligne->getId_produit_bl() : $ligne->getId_produit();
				$unicite = 'PAL' . $ligne->getId_palette() . 'PDT' . $idpdt . 'LOT' . $ligne->getNumlot() . 'FRS' . $ligne->getId_frs();
				if (!isset($liste_regroupee[$unicite])) {
					$pu[$unicite] = 0;
				}
				if ($ligne->getPu_ht() > $pu[$unicite]) {
					$pu[$unicite] = $ligne->getPu_ht();
				}
			}
			foreach ($liste as $ligne) {

				if ($ligne->getId_compo() == 0) {
					$ligne->setProduit(new Produit([]));
					$liste_regroupee['Z' . $ligne->getId()] = $ligne;
					continue;
				}

				$pdt = $produitManager->getProduit($ligne->getId_produit(), false);

				$idpdt = $ligne->getId_produit_bl() > 0 ? $ligne->getId_produit_bl() : $ligne->getId_produit();
				$unicite = 'PAL' . $ligne->getId_palette() . 'PDT' . $idpdt . 'LOT' . $ligne->getNumlot() . 'FRS' . $ligne->getId_frs();
				if (!isset($liste_regroupee[$unicite])) {
					$liste_regroupee[$unicite] = new BlLigne([]);
				}

				$oldLigne = $liste_regroupee[$unicite];
				$newLigne = clone $ligne;

				$oldQte = (int)$oldLigne->getQte();
				$newQte = (int)$ligne->getQte() > 0 ? (int)$ligne->getQte() : 1;

				$newLigne->setNb_colis((int)$oldLigne->getNb_colis() + $ligne->getNb_colis());
				$newLigne->setPoids((float)$oldLigne->getPoids() + $ligne->getPoids());
				$newLigne->setQte($oldQte + $newQte);
				$puUnicite = isset($pu[$unicite]) ? $pu[$unicite] : 0;
				$newLigne->setPu_ht($puUnicite);

				// Gestion des entrées manuelles hors produits (lignes libres)
				if ($pdt instanceof Produit) {
					if ((int)$pdt->isVendu_piece() == 0) {
						$newLigne->setQte(1);
					}
				}
				$liste_regroupee[$unicite] = $newLigne;
			} // FIN boucle sur les lignes non regroupées
			ksort($liste_regroupee);
			return $liste_regroupee;
		} // FIN regroupement

		return $liste;
	} // FIN liste des BlLigne


	// Retourne un BlLigne
	public function getBlLigne($id, $iso_langue = 'fr')
	{

		$query_object = 'SELECT l.`id`, l.`id_bl`, l.`id_compo`, l.`date_add`, l.`supprime`, p.`code`, l.`id_pays`, l.`id_lot`, l.`id_palette`,
       									IF (pc.`designation` IS NOT NULL AND pc.`designation` != "", pc.`designation`, t.`nom`) AS designation,
       									pc.`nb_colis` AS qte, pc.`nb_colis` AS colis, pc.`poids`,
       									IF (tc.`prix` IS NOT NULL AND tc.`prix` > 0, tc.`prix`, tg.`prix`) AS pu,
       									l.`id_produit`, l.`id_produit_bl`, l.`libelle`, l.`id_frs`
									FROM `pe_bl_lignes` l
										LEFT JOIN `pe_palette_composition` pc ON pc.`id` =  l.`id_compo`
										LEFT JOIN `pe_produits` p ON p.`id` =  pc.`id_produit`
										LEFT JOIN `pe_produit_trad` t ON t.`id_produit` =  p.`id`
									    LEFT JOIN `pe_langues` lang ON lang.`id` = t.`id_langue` AND LOWER(lang.`iso`) = "' . $iso_langue . '"
										LEFT JOIN `pe_tiers` clt ON clt.`id` = pc.`id_client`
										LEFT JOIN `pe_tarif_client` tc ON tc.`id_tiers` = pc.`id_client` AND tc.`id_produit` = pc.`id_produit`
										LEFT JOIN `pe_tarif_client` tg ON tc.`id_tiers_groupe` = clt.`id_groupe` AND tg.`id_produit` = pc.`id_produit`
										 WHERE l.`id` = ' . (int)$id;

		$query = $this->db->prepare($query_object);
		if ($query->execute()) {
			$donnee = $query->fetchAll(PDO::FETCH_ASSOC);
			return $donnee && isset($donnee[0]) ? new BlLigne($donnee[0]) : false;
		} else {
			return false;
		}
	} // FIN get BlLigne

	// Enregistre & sauvegarde (Méthode Save)
	public function saveBlLigne(BlLigne $objet)
	{

		$table      = 'pe_bl_lignes'; // Nom de la table
		$champClef  = 'id'; // Nom du champ clef

		// FIN Configuration

		$getter     = 'get' . ucfirst(strtolower($champClef));
		$setter     = 'set' . ucfirst(strtolower($champClef));

		if ($objet->$getter() == '' && !empty($objet->attributs)) {

			$query_add = 'INSERT INTO `' . $table . '` (';

			foreach ($objet->attributs as $attribut) {
				$query_add .= '`' . $attribut . '`,';
			}
			$query_add = substr($query_add, 0, -1);
			$query_add .= ') VALUES (';

			foreach ($objet->attributs as $attribut) {
				$query_add .= ':' . strtolower($attribut) . ' ,';
			}
			$query_add = substr($query_add, 0, -1);
			$query_add .= ')';

			$query = $this->db->prepare($query_add);
			$query_log = $query_add;

			foreach ($objet->attributs as $attribut) {
				$attributget = 'get' . ucfirst($attribut);
				$query->bindvalue(':' . strtolower($attribut), $objet->$attributget());
				$dq = is_numeric($objet->$attributget()) ? '' : '"';
				$query_log = str_replace(':' . strtolower($attribut) . ' ', $dq . $objet->$attributget() . $dq . ' ', $query_log);
			}

			if ($query->execute()) {
				// Log de la requête pour le mode Dev
				if (isset($_SESSION['devmode']) && $_SESSION['devmode']) {
					$_SESSION['pdoq'][get_class($this)][] = $query->queryString;
				}
				$objet->$setter($this->db->lastInsertId());
				Outils::saveLog($query_log);
				return $objet->$getter();
			}
		} else if ($objet->$getter() != '' && !empty($objet->attributs)) {

			$query_upd = 'UPDATE `' . $table . '` SET ';

			foreach ($objet->attributs as $attribut) {
				$query_upd .= '`' . $attribut . '` = :' . strtolower($attribut) . ' ,';
			}
			$query_upd = substr($query_upd, 0, -1);
			$query_upd .= ' WHERE `' . $champClef . '` = ' . $objet->$getter();

			$query = $this->db->prepare($query_upd);
			$query_log = $query_upd;

			foreach ($objet->attributs as $attribut) {
				$attributget = 'get' . ucfirst($attribut);
				$query->bindvalue(':' . strtolower($attribut), $objet->$attributget());
				$dq = is_numeric($objet->$attributget()) ? '' : '"';
				$query_log = str_replace(':' . strtolower($attribut) . ' ', $dq . $objet->$attributget() . $dq . ' ', $query_log);
			}
			try {
				$query->execute();
				Outils::saveLog($query_log);
				return true;
			} catch (PDOExeption $e) {
				return false;
			}
		}
		return false;
	} // FIN méthode

	// Retourne le prochain ID
	private function getNextId()
	{

		$base = $this->db->query('select database()')->fetchColumn();
		return (int)$this->db->query('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = "' . $base . '" AND TABLE_NAME = "pe_bl"');;
	}

	// Retou

	// Retourne le BL correspondant aux ids_compositions ou bien crée un nouveau BL...
	public function getOrCreateBlFromCompos($ids_compos, $bt = false)
	{

		// On classe les ID compo par ordre croissant pour comparer avec la BDD
		sort($ids_compos);

		$bt_flag = $bt ? 1 : 0;

		// On ne filtre pas sur le statut car quoiqu'il arrive il faut retourner un BL ou un BT s'il existe déjà
		// Par contre on filtre sur le flag BT car on pourrait avoir un BL et un BT pour les memes compos

		$query_bl = 'SELECT l.`id_bl`, GROUP_CONCAT(`id_compo` ORDER BY `id_compo` SEPARATOR \',\') AS ids_compos 
						FROM `pe_bl_lignes` l
							JOIN `pe_bl` b ON l.`id_bl` = b.`id`
						WHERE b.`supprime` = 0 AND l.`supprime` = 0 AND b.`bt` = ' . $bt_flag . ' 
						GROUP BY l.`id_bl`
						HAVING ids_compos =  "' . implode(',', $ids_compos) . '"
					 ORDER BY l.`id_bl` DESC 
					 LIMIT 0,1';


		$query = $this->db->prepare($query_bl);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		$id_bl = $donnees && isset($donnees['id_bl']) ? intval($donnees['id_bl']) : 0;

		// Si on a un bien un BL correspondant à ces compos, on le retourne
		if ($id_bl > 0) {
			return $this->getBl($id_bl);
		}

		// On récupère l'Id_client en fonction des compos (c'est toujours le même ici, on s'est assuré en amont que les compos avaient le même client)
		$compoManager = new PalettesManager($this->db);
		$compo = $compoManager->getComposition($ids_compos[0]);
		if (!$compo instanceof PaletteComposition) {
			return false;
		}

		// On récupère l'adresse de facturation et de livraison si elle est unique
		$tiersManager = new TiersManager($this->db);
		$client = $tiersManager->getTiers($compo->getId_client());

		$bt_flag = $bt ? 1 : 0;

		// Sinon, on crée un nouveau BL avec ces compos, supprimé par défaut en cas de retour arrière ou d'annulation

		$num_bl = $this->getNextNumeroBl($bt);

		$bl = new Bl([]);
		$bl->setBt($bt_flag);
		$bl->setSupprime(1);
		$bl->setDate_add(date('Y-m-d H:i:s'));
		$bl->setDate(date('Y-m-d'));
		$bl->setNum_cmd(0);
		$bl->setId_tiers_facturation($compo->getId_client());
		$bl->setId_tiers_livraison($compo->getId_client());
		$bl->setId_tiers_transporteur($client->getId_transporteur());
		$bl->setRegroupement($client->getRegroupement());
		$bl->setChiffrage($client->getBl_chiffre());
		$bl->setNum_bl($num_bl);


		if ($bt) {
			$configManager  = new ConfigManager($this->db);
			$bt_clt = $configManager->getConfig('bt_clt');
			if (!$bt_clt instanceof Config) {
				return false;
			}
			$id_clt = intval($bt_clt->getValeur());
			if ($id_clt == 0) {
				return false;
			}
			$bl->setId_tiers_livraison($id_clt);
			$bl->setId_tiers_facturation($id_clt);
		}

		// Si on a au moins une adresse
		if (!empty($client->getAdresses())) {

			// Si on a une seule adresse, on l'affecte comme livraison + facturation
			if (count($client->getAdresses()) == 1) {
				$adresse = $client->getAdresses()[0];
				$bl->setId_adresse_facturation($adresse->getId());
				$bl->setId_adresse_livraison($adresse->getId());
				// Si le client a plusieurs adresses
			} else {
				// On boucle sur les adresses du client
				foreach ($client->getAdresses() as $adresse) {
					// Si c'est une adresse de livraison
					if ($adresse->getType() == 1) {
						$bl->setId_adresse_livraison($adresse->getId());
						// Sinon c'est une adresse de facturation
					} else {
						$bl->setId_adresse_facturation($adresse->getId());
					} // FIN test type adresse
				} // FIN boucle sur les adresses du client
			} // FIN test nombre d'adresses
		} // FIN test au moins une adresse

		// On enregistre le BL
		$new_id_bl = $this->saveBl($bl);
		if (!$new_id_bl || (int)$new_id_bl == 0) {
			return false;
		}

		$nbLignes = 0;

		// On crée les lignes
		foreach ($ids_compos as $id_compo) {

			$compoLigne = $compoManager->getComposition($id_compo);
			$nb_colis = $compoLigne instanceof PaletteComposition ? $compoLigne->getNb_colis() : 0;

			$ligne = new BlLigne([]);
			$ligne->setDate_add(date('Y-m-d H:i:s'));
			$ligne->setSupprime(0);
			$ligne->setId_compo((int)$id_compo);
			$ligne->setId_bl($new_id_bl);
			$ligne->setNb_colis($nb_colis);
			if ($compoLigne->getNum_lot_regroupement() != '') {
				$ligne->setNumlot($compoLigne->getNum_lot_regroupement());
			}
			if ($this->saveBlLigne($ligne)) {
				$nbLignes++;
			}
		} // FIN boucle sur les lignes

		$log = new Log([]);
		$type = $bt ? '(Bon de Transfert)' : '';
		$logType = $nbLignes < count($ids_compos) ? 'danger' : 'info';
		$logText = $nbLignes < count($ids_compos) ? "Création du BL " . $type . " #" . $new_id_bl . " " . $nbLignes . " lignes / " . count($ids_compos) : "Création du BL " . $type . " #" . $new_id_bl . " : " . $nbLignes . " lignes ";
		$logText .= ' IDS compos ' . implode(',', $ids_compos);
		$log->setLog_type($logType);
		$log->setLog_texte($logText);
		$logManager = new LogManager($this->db);
		$logManager->saveLog($log);

		return $this->getBl($new_id_bl);
	} // FIN méthode

	// Retourne le nombre de palettes d'un BL
	public function getNbPaletteBl(Bl $bl)
	{

		$query_nb = 'SELECT COUNT(DISTINCT `id_palette`) AS nb FROM `pe_bl_lignes` WHERE `id_bl` = ' . $bl->getId() . ' AND `supprime` = 0 AND `id_palette` > 0';
		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;
	} // FIN méthode

	// Retourne le nombre de produits d'un BL
	public function getNbProduitsBl(Bl $bl)
	{

		$query_nb = 'SELECT COUNT(DISTINCT IF(`id_produit_bl` > 0, `id_produit_bl`, `id_produit`)) AS nb
					FROM `pe_bl_lignes` WHERE `id_bl` = ' . $bl->getId() . ' AND `supprime` = 0 ';
		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;
	} // FIN méthode

	// Retourne le nombre de colis d'un BL
	public function getNbColisBl(Bl $bl)
	{

		$query_nb = 'SELECT SUM(`nb_colis`) AS nb
					FROM `pe_bl_lignes` WHERE `id_bl` = ' . $bl->getId() . ' AND `supprime` = 0 ';
		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;
	} // FIN méthode

	// Retourne le poids total des produits d'un BL
	public function getPoidsBl(Bl $bl)
	{

		$query_nb = 'SELECT SUM(`poids`) AS nb
					FROM `pe_bl_lignes` WHERE `id_bl` = ' . $bl->getId() . ' AND `supprime` = 0 ';
		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();

		return $donnee && isset($donnee['nb']) ? floatval($donnee['nb']) : 0;
	} // FIN méthode

	// Retourne le total HT du Bl
	public function getTotalHt(Bl $bl)
	{

		// Si on a une facture associée, on récupère le total de la facture
		$facturesManager = new FacturesManager($this->db);
		$facture = $facturesManager->getFactureByBl($bl->getId());
		$totalFacture = $facture instanceof Facture ? $facture->getTotal_ttc() : 0;
		if ($totalFacture > 0) {
			return $totalFacture;
		}

		// Faussé si regroupement avec des produits sans prix, on fais donc une opération de nettoyage
		$query_pus = 'SELECT `id_produit`, `id_lot`, `id_palette`, `id_bl`, MIN(`pu_ht`) AS pu_min, MAX(`pu_ht`) AS pu_max
						FROM `pe_bl_lignes` WHERE `id_bl` = ' . $bl->getId() . ' AND `supprime` = 0
					GROUP BY CONCAT(`id_produit`, `id_lot`, `id_palette`)
						HAVING pu_min = 0 AND pu_max > 0';

		$query = $this->db->prepare($query_pus);
		$query->execute();
		$query_upd = '';
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			if (floatval($donnee['pu_max']) == 0) {
				continue;
			}
			$query_upd .= 'UPDATE `pe_bl_lignes` SET `pu_ht` = ' . floatval($donnee['pu_max']) . ' 
							WHERE `pu_ht` = 0 AND `supprime` = 0 
								AND `id_produit` = ' . (int)$donnee['id_produit'] . ' 
								AND `id_lot` = ' . (int)$donnee['id_lot'] . '
								AND `id_palette` = ' . (int)$donnee['id_palette'] . '
								AND `id_bl` = ' . $bl->getId() . ';';
		}
		if (strlen($query_upd) > 0) {
			$query = $this->db->prepare($query_upd);
			$query->execute();
		}

		$query_nb = 'SELECT SUM(IF (p.`vendu_piece` = 1, l.`qte`, l.`poids`) * l.`pu_ht`)AS nb
					FROM `pe_bl_lignes` l
						 LEFT JOIN `pe_produits` p ON p.`id` = l.`id_produit`
					WHERE l.`id_bl` = ' . $bl->getId() . ' AND l.`supprime` = 0 ';
		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();

		$total = $donnee && isset($donnee['nb']) ? floatval($donnee['nb']) : 0;

		return $total;
	} // FIN méthode

	public function changeNumeroPaletteBl($id_bl, $id_palette, $old_id_palette)
	{

		// on récupère toutes les lignes avec l'id compo du bl qui correspondent à l'ancien id_palette
		$query_liste = 'SELECT `id`, `id_compo` FROM `pe_bl_lignes` WHERE `id_bl` = ' . $id_bl . ' AND `id_palette` = ' . $old_id_palette . ' AND `supprime` = 0';
		$query = $this->db->prepare($query_liste);

		$query->execute();
		$listeLignes = [];
		$listeCompos = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$listeLignes[] = (int)$donnee['id'];
			$listeCompos[] = (int)$donnee['id_compo'];
		}
		if (empty($listeLignes)) {
			return false;
		}

		$query_upd1 = 'UPDATE `pe_bl_lignes` SET `num_palette` = 0, `id_palette` = ' . $id_palette . ' WHERE `id` IN (' . implode(',', $listeLignes) . ')';
		$query1 = $this->db->prepare($query_upd1);
		Outils::saveLog($query_upd1);
		if (!$query1->execute()) {
			return false;
		}

		$query_upd2 = 'UPDATE `pe_palette_composition` SET `id_palette` = ' . $id_palette . ' WHERE `id` IN (' . implode(',', $listeCompos) . ')';
		$query2 = $this->db->prepare($query_upd2);
		Outils::saveLog($query_upd2);
		if (!$query2->execute()) {
			return false;
		}
		return true;
	}

	// Force un numéro de palette sur les lignes correspondantes d'un BL
	public function forceNumeroPaletteBl($id_bl, $id_palette, $numero_palette)
	{

		$query_upd = 'UPDATE `pe_bl_lignes` SET `num_palette` = ' . $numero_palette . ' WHERE `id_bl` = ' . (int)$id_bl . ' AND ( `id_palette` = ' . (int)$id_palette . ' OR `id_compo` IN (SELECT `id` FROM `pe_palette_composition` WHERE `id_palette` = ' . (int)$id_palette . ' AND `supprime` = 0) )';
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		if (!$query->execute()) {
			return false;
		}

		// Si le numéro de palette passé correspond à un id_palette existant dans le bl, on remplace l'id_palette
		$query_palette = 'SELECT DISTINCT(bll.`id_palette`) FROM `pe_bl_lignes` bll 
							JOIN `pe_palettes` p ON p.`id` = bll.`id_palette`
						WHERE bll.`id_bl` = ' . $id_bl . ' 
							AND bll.`supprime` = 0 
							AND p.`numero` = ' . $numero_palette;

		$query = $this->db->prepare($query_palette);
		Outils::saveLog($query_palette);
		$query->execute();
		$donnee = $query->fetch();
		if ($donnee && isset($donnee['id_palette']) && intval($donnee['id_palette']) > 0) {
			$new_id_palette = intval($donnee['id_palette']);

			$query_ids_bll = 'SELECT `id` FROM `pe_bl_lignes` WHERE `id_bl` = ' . (int)$id_bl . ' AND ( `id_palette` = ' . (int)$id_palette . ' OR `id_compo` IN (SELECT `id` FROM `pe_palette_composition` WHERE `id_palette` = ' . (int)$id_palette . ' AND `supprime` = 0) )';

			$query2 = $this->db->prepare($query_ids_bll);
			Outils::saveLog($query_ids_bll);
			$query2->execute();
			$liste = [];
			foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnees) {
				$liste[] = intval($donnees['id']);
			}
			if (empty($liste)) {
				return true;
			}

			$query_upd = 'UPDATE `pe_bl_lignes` SET `id_palette` = ' . $new_id_palette . ' WHERE `id` IN (' . implode(',', $liste) . ')';
			$query = $this->db->prepare($query_upd);
			Outils::saveLog($query_upd);
			if (!$query->execute()) {
				return false;
			}
			$log = new Log([]);
			$log->setLog_type('info');
			$log->setLog_texte("Transfert de palette " . $id_palette . " vers " . $new_id_palette . " par modification du numéro de palette sur l'édition du BL #" . $id_bl);
		}
		return true;
	} // FIN méthode

	// Récupère les données calculées d'une ligne de BL (Compo)
	public function getDonneesLigneBl($id_compo)
	{

		$query_donnees = 'SELECT DISTINCT 
                						p.`id` AS id_produit,
                						pc.`id_palette`,
										IF (lot_prod.`numlot` IS NOT NULL, CONCAT(lot_prod.`numlot`,pdtf.`quantieme`) ,
											CONCAT(lot_frais.`numlot`,frac.`quantieme`)) AS numlot,
                						IF (lot_prod.`numlot` IS NOT NULL, lot_prod.`id` , lot_frais.`id`) AS id_lot,
                						pc.`poids`,
                						pc.`nb_colis`,
										IF (tc.`prix` IS NOT NULL AND tc.`prix` > 0, tc.`prix`, 
											IF(tg.`prix` > 0, tg.`prix`, 0)) AS pu_ht, 
                						t.`taux` AS tva, p.`vendu_piece`
									FROM `pe_palette_composition` pc
										LEFT JOIN `pe_produits` p ON p.`id` =  pc.`id_produit`
										JOIN `pe_tiers` clt ON clt.`id` = pc.`id_client`
										LEFT JOIN `pe_tarif_client` tc ON tc.`id_tiers` = clt.`id` AND tc.`id_produit` = pc.`id_produit`
										LEFT JOIN `pe_tarif_client` tg ON tc.`id_tiers_groupe` = clt.`id_groupe` AND tg.`id_produit` = pc.`id_produit`
										LEFT JOIN `pe_froid_produits` pdtf ON pdtf.`id_lot_pdt_froid` = pc.`id_lot_pdt_froid`
										LEFT JOIN `pe_frais` frac ON frac.`id` = pc.`id_frais`
										LEFT JOIN `pe_lots` lot_prod ON lot_prod.`id` = pdtf.`id_lot`
										LEFT JOIN `pe_lots` lot_frais ON lot_frais.`id` = frac.`id_lot`
										LEFT JOIN `pe_taxes` t ON t.`id` = p.`id_taxe`
	
									WHERE pc.`id` =  ' . (int)$id_compo;

		$query = $this->db->prepare($query_donnees);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);

		$blLigne = new BlLigne($donnees);
		return $blLigne;
	} // FIN méthode


	// Récupère les données calculées d'une ligne de BL (Negoce)
	public function getDonneesLigneBlByNegoce($id_pdt_negoce)
	{

		$query_donnees = 'SELECT DISTINCT 
                						p.`id` AS id_produit,
                						np.`id_palette`,
                						pal.`numero` AS numero_palette,
										np.`num_lot` AS numlot,
                						np.`poids`,
                						np.`nb_cartons` AS nb_colis,
                						t.`taux` AS tva				
									FROM `pe_negoce_produits` np
										LEFT JOIN `pe_produits` p ON p.`id` =  np.`id_pdt`
										LEFT JOIN `pe_palettes` pal ON pal.`id` =  np.`id_palette`
										LEFT JOIN `pe_lots_negoce` l ON l.`id` = np.`id_lot_negoce`
										LEFT JOIN `pe_taxes` t ON t.`id` = p.`id_taxe`
									WHERE np.`id_lot_pdt_negoce` =  ' . (int)$id_pdt_negoce;

		$query = $this->db->prepare($query_donnees);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);

		$blLigne = new BlLigne($donnees);
		return $blLigne;
	} // FIN méthode

	// Retourne le montant Interbev calculé pour un BL
	public function getMontantInterbevByBl(Bl $bl)
	{

		$configManager = new ConfigManager($this->db);
		$interbev = $configManager->getConfig('interbev');
		if (!$interbev instanceof Config) {
			return 0;
		}
		if (intval($interbev->getValeur()) == 0) {
			return 0;
		}

		// ON ne prends en compte que les lignes de BL pour lequelles le lot n'a pas comme origine FRANCE
		// et si le BL a une adresse de livraison en France.
		// On multiplie alors le montant configuré selon le type de produits (gros ou autre) par le poids de la ligne dans le BL

		$query_interbev = ' SELECT 
 								ROUND(SUM(IF (pdt.`pdt_gros` = 1,
 				    				(SELECT `valeur` FROM `pe_config` WHERE `clef` = "interbev_gros"),
 				    				(SELECT `valeur` FROM `pe_config` WHERE `clef` = "interbev_autres")) * bligne.`poids` ),3)
 								AS montant_interbev 
 								FROM `pe_bl_lignes` bligne
 									JOIN `pe_lots` lot ON lot.`id` = bligne.`id_lot`
 									JOIN `pe_pays` origine ON origine.`id` = lot.`id_origine`
 									JOIN `pe_produits` pdt ON pdt.`id` = bligne.`id_produit`
 								WHERE bligne.`supprime` = 0
 								  	AND bligne.`id_bl` = ' . $bl->getId() . '
 								  	AND UPPER(origine.`iso`) != "FR"
 								  	AND (
 								  		SELECT UPPER(pays.`iso`) 
 								  			FROM `pe_pays` pays
    											JOIN `pe_adresses` adr ON adr.`id_pays` = pays.`id`
   											WHERE adr.`id` = ' . $bl->getId_adresse_facturation() . '
										) = "FR" ';

		$query = $this->db->prepare($query_interbev);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);

		return $donnees && isset($donnees['montant_interbev']) ? floatval($donnees['montant_interbev']) : 0;
	} // FIN méthode

	// Retourne le tarif Interbev retenu pour une ligne de BL en fonction de l'adresse de livraison (l'origine est récupérée par la ligne)
	public function getTarifInterbevLigneBl(BlLigne $ligne, $id_adresse_livraison)
	{

		$configManager = new ConfigManager($this->db);
		$interbev = $configManager->getConfig('interbev');
		if (!$interbev instanceof Config) {
			return 0;
		}
		if (intval($interbev->getValeur()) == 0) {
			return 0;
		}

		$query_interbev = ' SELECT 
        							IF (pdt.`pdt_gros` = 1,
 				    				(SELECT `valeur` FROM `pe_config` WHERE `clef` = "interbev_gros"),
 				    				(SELECT `valeur` FROM `pe_config` WHERE `clef` = "interbev_autres"))
 								AS tarif_interbev
 								FROM `pe_bl_lignes` bligne
 									LEFT JOIN `pe_lots` lot ON lot.`id` = bligne.`id_lot`
 									JOIN `pe_produits` pdt ON pdt.`id` = bligne.`id_produit`
                                    LEFT JOIN `pe_produits_especes` esp ON esp.`id` = pdt.`id_espece`
 								WHERE bligne.`supprime` = 0
 								  	AND bligne.`id` = ' . $ligne->getId() . '
									AND esp.`nom` LIKE "%CHEVAL%"
 								  	AND (
 								  		SELECT UPPER(pays.`iso`) 
 								  			FROM `pe_pays` pays
    											JOIN `pe_adresses` adr ON adr.`id_pays` = pays.`id`
   											WHERE adr.`id` = ' . $id_adresse_livraison . '
										) = "FR" ';

		$query = $this->db->prepare($query_interbev);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);

		return $donnees && isset($donnees['tarif_interbev']) ? floatval($donnees['tarif_interbev']) : 0;
	} // FIN méthode

	// Retourne l'ID du pays d'origine depuis l'ID du lot de la ligne de BL
	public function getIdPaysFromLot($id_lot)
	{

		$query_id = 'SELECT `id_origine` FROM `pe_lots` WHERE `id` = ' . (int)$id_lot;
		$query = $this->db->prepare($query_id);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);

		return $donnees && isset($donnees['id_origine']) ? intval($donnees['id_origine']) : 0;
	} // FIN méthode

	// Supprime un BL
	public function supprBl(Bl $bl)
	{

		// On s'assure qu'il n'y a aucune facure associée au BL
		$query_nb = 'SELECT COUNT(*) AS nb FROM `pe_bl_facture` bf LEFT JOIN `pe_factures` f ON f.`id` = bf.`id_facture` WHERE f.`supprime` = 0 AND bf.`id_bl` = ' . $bl->getId();
		$query = $this->db->prepare($query_nb);
		$query->execute();
		$donnee = $query->fetch();
		$nb_factures =  $donnee && isset($donnee['nb']) ? intval($donnee['nb']) : 0;
		if ($nb_factures > 0) {
			return false;
		}

		// On supprime le BL en BDD
		$query_del = 'UPDATE `pe_palette_composition` SET `archive` = 0, `supprime` = 0 WHERE `id` IN (SELECT `id_compo` FROM `pe_bl_lignes` WHERE `id_bl` = ' . $bl->getId() . ');';
		$query_del .= 'DELETE FROM `pe_bl_lignes` WHERE `id_bl` = ' . $bl->getId() . ';';
		$query_del .= 'DELETE FROM `pe_bl` WHERE `id` = ' . $bl->getId() . ';';
		$queryD = $this->db->prepare($query_del);
		if (!$queryD->execute()) {
			return false;
		}
		Outils::saveLog($query_del);
		// Puis le fichier
		return $this->supprimeFichierBl($bl);
	} // FIN méthode

	// Supprime le fichier PDF du BL sur le serveur
	public function supprimeFichierBl(Bl $bl)
	{

		$dir = $bl->isBt() ? 'bon_transfert' : 'bl';

		$chemin = __CBO_ROOT_URL__ . '/gescom/' . $dir . '/' . $bl->getFichier();
		if (!file_exists($chemin)) {
			return true;
		}

		return unlink($chemin);
	} // FIN méthode

	// Crée ou retourne un BL depuis un lot de négoce
	public function getOrCreateBlFromNegoce($id_lot_negoce)
	{

		// On cherche à récupérer un ID de BL qui viendrait de ce lot de négoce
		$query_id = 'SELECT `id` FROM `pe_bl` WHERE `id_lot_negoce` = ' . (int)$id_lot_negoce;
		$query = $this->db->prepare($query_id);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);

		// Si on a un bien un BL correspondant à ce lot de négoce, on le retourne
		$id_bl = $donnees && isset($donnees['id']) ? intval($donnees['id']) : 0;
		if ($id_bl > 0) {
			return $this->getBl($id_bl, true, true);
		} // On a besoin de passer à true 'produits simples' var pas d'ID compo

		// On instancie le lot de négoce
		$lotNegoceManager = new LotNegoceManager($this->db);
		$lot_negoce = $lotNegoceManager->getLotNegoce($id_lot_negoce);
		if (!$lot_negoce instanceof LotNegoce) {
			return false;
		}

		// Sinon, on crée un nouveau BL avec les produits du lot de négoce, supprimé par défaut en cas de retour arrière ou d'annulation

		$num_bl = $this->getNextNumeroBl();

		$bl = new Bl([]);
		$bl->setSupprime(1);
		$bl->setDate_add(date('Y-m-d H:i:s'));
		$bl->setDate(date('Y-m-d'));
		$bl->setNum_cmd(0);
		$bl->setId_lot_negoce($id_lot_negoce);
		$bl->setId_tiers_facturation(0);
		$bl->setId_tiers_livraison(0);
		$bl->setId_tiers_transporteur(0);
		$bl->setRegroupement(0);
		$bl->setChiffrage(0);
		$bl->setNum_cmd($num_bl);
		// Créer num_bl

		// On enregistre le BL
		$new_id_bl = $this->saveBl($bl);
		if (!$new_id_bl || (int)$new_id_bl == 0) {
			return false;
		}

		// On crée les lignes
		foreach ($lot_negoce->getProduits() as $pdt_negoce) {

			if ($pdt_negoce->getTraite() == 0) {
				continue;
			}

			$ligne = new BlLigne([]);
			$ligne->setDate_add(date('Y-m-d H:i:s'));
			$ligne->setSupprime(0);
			$ligne->setId_compo(0);
			//			$ligne->setId_palette($pdt_negoce->getId_palette());
			$ligne->setNumlot($pdt_negoce->getNum_lot());
			$ligne->setId_produit($pdt_negoce->getId_pdt());
			$ligne->setPoids($pdt_negoce->getPoids());
			$ligne->setId_pdt_negoce($pdt_negoce->getId_lot_pdt_negoce());
			$ligne->setNb_colis($pdt_negoce->getNb_cartons());
			$ligne->setId_bl($new_id_bl);

			$this->saveBlLigne($ligne);
		} // FIN boucle sur les lignes

		$log = new Log([]);
		$logType = 'info';
		$logText = "Création du BL #" . $new_id_bl . " depuis lot de négoce #" . $id_lot_negoce;
		$log->setLog_type($logType);
		$log->setLog_texte($logText);
		$logManager = new LogManager($this->db);
		$logManager->saveLog($log);

		return $this->getBl($new_id_bl, true, true); // On a besoin de passer à true 'produits simples' car pas d'ID compo

	} // FIN méthode

	// Retourne la liste des BL sortants relatifs à un lot de négoce
	public function getListeBlsByNegoce($id_lot_negoce)
	{

		$query_liste = 'SELECT `id`, `id_tiers_livraison`, `id_tiers_facturation`, `id_tiers_transporteur`, `id_adresse_facturation`, `id_adresse_livraison`, `date`,`date_add`, `num_cmd`, `supprime`, `nom_client`, `statut`, `regroupement`, `chiffrage`, `id_lot_negoce` FROM `pe_bl` WHERE `supprime` = 0  AND `id_lot_negoce` = ' . (int)$id_lot_negoce . ' ORDER BY `id` DESC ';
		$query = $this->db->prepare($query_liste);
		$query->execute();

		$liste = [];

		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$bl = new Bl($donnee);
			if (!$bl instanceof Bl) {
				continue;
			}
			$liste[] = $bl;
		}
		return $liste;
	} // FIN méthode

	// Retourne le dernier BL d'un client
	public function getLastBlClient($id_client)
	{

		$query_bl = 'SELECT `id`, `id_tiers_livraison`, `id_tiers_facturation`, `id_tiers_transporteur`, `id_adresse_facturation`, `id_adresse_livraison`, `date`, `date_add`, `num_cmd`, `supprime` , `nom_client`, `statut`, `regroupement`, `chiffrage`, `id_lot_negoce`, `id_packing_list`
                FROM `pe_bl` WHERE `id_tiers_livraison` = ' . (int)$id_client . ' ORDER BY `date` DESC LIMIT 0,1';

		$query = $this->db->prepare($query_bl);

		if (!$query->execute()) {
			return false;
		}

		$donnee = $query->fetch(PDO::FETCH_ASSOC);

		return $donnee ? new Bl($donnee) : false;
	} // FIN méthode

	// Vérifie si une compo fait déja partie d'un BL
	public function checkCompoDejaBl($id_compo)
	{

		$query_check = 'SELECT COUNT(*) AS nb FROM `pe_bl_lignes` WHERE `id_compo` = ' . $id_compo . ' AND `supprime` = 0';
		$result = $this->db->query($query_check);
		$donnee    = $result->fetch(PDO::FETCH_ASSOC);

		return $donnee && isset($donnee['nb']) && intval($donnee['nb']) > 0;
	} // FIN méthode

	public function killSupprimes()
	{

		global $utilisateur;
		if (!$utilisateur->isDev()) {
			return false;
		}

		$query_ids = 'SELECT `id`, `date` FROM `pe_bl` WHERE `supprime` = 1';
		$query = $this->db->prepare($query_ids);
		$query->execute();
		$ids = [];
		$numsBls = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$numBl = 'BL' . $donnee['id'] . date("y", strtotime($donnee['date'])) . sprintf('%03d', intval(date("z", strtotime($donnee['date']))) + 1);
			$ids[] = (int)$donnee['id'];
			$numsBls[] = $numBl;
		}
		if (empty($ids)) {
			return true;
		}

		$query_ids_lignes = 'SELECT `id` FROM `pe_bl_lignes` WHERE `id_bl` IN (' . implode(',', $ids) . ');';
		$query2 = $this->db->prepare($query_ids_lignes);
		$query2->execute();
		$idsLignes = [];
		foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {
			$idsLignes[] = (int)$donnee2['id'];
		}

		$query_del = 'DELETE FROM `pe_bl` WHERE `id` IN (' . implode(',', $ids) . ');';
		$query_del .= 'DELETE FROM `pe_bl_facture` WHERE `id_bl` IN (' . implode(',', $ids) . ');';
		$query_del .= 'DELETE FROM `pe_bl_lignes` WHERE `id_bl` IN (' . implode(',', $ids) . ');';
		$query_del .= !empty($idsLignes) ? 'DELETE FROM `pe_facture_ligne_bl` WHERE `id_ligne_bl` IN (' . implode(',', $idsLignes) . ');' : '';

		$query3 = $this->db->prepare($query_del);
		if (!$query3->execute()) {
			return false;
		}
		Outils::saveLog($query_del);

		$query_ids_factures = 'SELECT DISTINCT `id_facture` FROM `pe_bl_facture` WHERE `id_bl` IN (' . implode(',', $ids) . ');';
		$query3 = $this->db->prepare($query_ids_factures);
		$query3->execute();
		$idsFactures = [];
		foreach ($query3->fetchAll(PDO::FETCH_ASSOC) as $donnee3) {
			$idsFactures[] = (int)$donnee2['id_facture'];
		}

		if (!empty($idsFactures)) {
			$query_suppr_factures_bls = 'UPDATE `pe_factures` SET `supprime` = 1 WHERE `id` IN (' . implode(',', $idsFactures) . ')';
			$query4 = $this->db->prepare($query_suppr_factures_bls);
			$query4->execute();
			Outils::saveLog($query_suppr_factures_bls);
		}

		if (empty($numsBls)) {
			return true;
		}

		foreach ($numsBls as $numsBl) {
			$fichier = __CBO_ROOT_URL__ . '/gescom/bl/' . $numsBl . '.pdf';
			if (file_exists($fichier)) {
				unlink($fichier);
			}
		}

		return true;
	} // FIN méthode

	public function supprPdfBlsHorsBdd()
	{

		$query_numBls = 'SELECT `id`, `date` FROM `pe_bl` WHERE `supprime` = 0'; // non supprimés
		$query = $this->db->prepare($query_numBls);
		$query->execute();
		$numsBls = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$numBl = 'BL' . $donnee['id'] . date("y", strtotime($donnee['date'])) . sprintf('%03d', intval(date("z", strtotime($donnee['date']))) + 1);
			$numsBls[] = $numBl;
		}
		foreach (glob(__CBO_ROOT_URL__ . '/gescom/bl/*.pdf') as $fichier) {
			if (!in_array(basename($fichier, '.pdf'), $numsBls)) {
				unlink($fichier);
			}
		}
		return true;
	} // FIN méthode


	public function supprComposBl(Bl $bl)
	{

		$ids_compos = [];
		foreach ($bl->getLignes() as $ligne) {
			$ids_compos[] = $ligne->getId_compo();
		}

		if (empty($ids_compos)) {
			return false;
		}

		$query_upd = 'UPDATE `pe_palette_composition` SET `supprime` = 1, `archive` = 1 WHERE `id` IN (' . implode(',', $ids_compos) . ')';
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();
	}

	// Id produit identiques + id lot identique + id palette identique sur les lignes d'un meme bl
	public function getBlLignesRegroupeesFromLigne(BlLigne $ligne, $palettesEtlot = true)
	{

		$idpdt = $ligne->getId_produit_bl() > 0 ? $ligne->getId_produit_bl() : $ligne->getId_produit();
		$query_regroupes = 'SELECT `id` FROM `pe_bl_lignes` WHERE `supprime` = 0 AND `id_bl` = (SELECT `id_bl` FROM `pe_bl_lignes` WHERE `id` = ' . $ligne->getId() . ') AND `id` !=  ' . $ligne->getId() . ' 
    AND IF (`id_produit_bl` > 0, `id_produit_bl`, `id_produit`)  = ' . $idpdt . ' AND `id_frs` = ' . $ligne->getId_frs();

		$query_regroupes .= $palettesEtlot ? ' AND `id_palette` = ' . $ligne->getId_palette() . ' AND `id_lot` = ' . $ligne->getId_lot() : '';

		$query = $this->db->prepare($query_regroupes);
		$query->execute();
		$lignesRegroupees = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$lignesRegroupees[] = $this->getBlLigne($donnee['id']);
		}
		return $lignesRegroupees;
	} // FIN méthode


	// Au save d'un nouveau BL : persistance du num_bl
	private function saveNUmBL(Bl $bl)
	{

		$prefixe = $bl->isBt() ? 'BT' : 'BL';
		$query_upd = 'UPDATE `pe_bl` SET `num_bl` = CONCAT("' . $prefixe . '",`id`,DATE_FORMAT(`date`, "%y"), LPAD(DAYOFYEAR(`date`),3, "0")) WHERE `id` = ' . (int)$bl->getId();
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();
	} // FIN méthode privée au Save d'un nouveau BL

	// Retourne les infos de chaque palette d'un BL
	public function getPalettesBl(Bl $bl)
	{

		$query_palettes = 'SELECT DISTINCT `id_palette`, `num_palette` FROM `pe_bl_lignes` WHERE `supprime` = 0 AND `id_bl` = ' . $bl->getId();
		$query = $this->db->prepare($query_palettes);
		$query->execute();
		$palettes = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$palette_id = intval($donnee['id_palette']);
			$query_liste = 'SELECT p.`numero`, SUM(l.`poids`) AS poids, SUM(l.`nb_colis`) AS colis  
								FROM `pe_bl_lignes` l 
								JOIN `pe_palettes` p ON p.`id` = l.`id_palette`
							WHERE  l.`supprime` = 0 AND l.`id_bl` = ' . $bl->getId() . ' AND l.`id_palette` = ' . $palette_id;
			$query2 = $this->db->prepare($query_liste);
			$query2->execute();
			foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $donnee2) {

				$tmp = $donnee2;
				$tmp['id_palette'] = $donnee['id_palette'];
				if ((int)$donnee['num_palette'] > 0) {
					$tmp['numero'] = $donnee['num_palette'];
				}
				$palettes[] = $tmp;
			}
		}
		return $palettes;
	} // FIN méthode

	public function getDossierBlPdf(Bl $bl, $crer_dossiers = true)
	{
		if ($bl->getDate() >= '2021-09-28') { // Date du changement du système de numérotation
			return $this->getDossierBlPdfFromBl($bl, $crer_dossiers);
		} else {
			return $this->getDossierBlPdfFromNum($bl->getNum_bl(), $bl->isBt(), $crer_dossiers);
		}
	}

	public function getDossierBlPdfFromBl(Bl $bl,  $crer_dossiers = true)
	{
		$chemin = '/gescom/';
		$chemin .= $bl->isBt() ? 'bon_transfert/' : 'bl/';
		if (!file_exists(__CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir(__CBO_ROOT_PATH__ . $chemin);
		}
		$annee = substr($bl->getNum_bl(), 2, 2);
		$chemin .= $annee . '/';
		if (!file_exists(__CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir(__CBO_ROOT_PATH__ . $chemin);
		}
		$mois = substr($bl->getNum_bl(), 4, 2);
		$chemin .= $mois . '/';
		if (!file_exists(__CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir(__CBO_ROOT_PATH__ . $chemin);
		}
		return $chemin;
	} // Fin méthode

	public function getDossierBlPdfFromNum($num_bl, $is_bt, $crer_dossiers = true)
	{

		$chemin = '/gescom/';
		$chemin .= $is_bt ? 'bon_transfert/' : 'bl/';

		if (!file_exists(__CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir(__CBO_ROOT_PATH__ . $chemin);
		}

		$annee = substr($num_bl, -5, 2);

		$chemin .= $annee . '/';
		if (!file_exists(__CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir(__CBO_ROOT_PATH__ . $chemin);
		}
		$jour = substr($num_bl, -3);

		$date = DateTime::createFromFormat('z y', strval($jour) . ' ' . strval($annee));
		$mois = $date->format('m');
		$chemin .= $mois . '/';
		if (!file_exists(__CBO_ROOT_PATH__ . $chemin) && $crer_dossiers) {
			mkdir(__CBO_ROOT_PATH__ . $chemin);
		}

		return $chemin;
	}

	// Retourne si un BL contient uniquement du frais, du congelé ou les deux
	public function isFraisOuCongele(Bl $bl, $id_palette = 0)
	{
		if (empty($bl->getLignes()) || !is_array($bl->getLignes())) {
			return false;
		}

		$nbFrais = 0;
		$nbCongeles = 0;
		foreach ($bl->getLignes() as $l) {
			if ($id_palette > 0 && $id_palette != $l->getId_palette()) {
				continue;
			}
			if ($l->isFrais()) {
				$nbFrais++;
			} else {
				$nbCongeles++;
			}
		}

		if ($nbFrais > 0 && $nbCongeles == 0) {
			return 1;
		} else if ($nbFrais == 0 && $nbCongeles > 0) {
			return 2;
		} else {
			return 0;
		}
	} // FIN méthode

	// Supprimes (flag) les BL sans lignes
	public function clearBlSansLignes()
	{

		$query_liste = 'SELECT DISTINCT(b.`id`) FROM `pe_bl` b LEFT JOIN `pe_bl_lignes` l On l.`id_bl` = b.`id` WHERE l.`id` IS NULL';
		$query = $this->db->prepare($query_liste);
		$query->execute();
		$ids_bls = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {
			$ids_bls[] = intval($donnee['id']);
		}
		if (!$ids_bls || empty($ids_bls)) {
			return true;
		}
		$query_upd = 'UPDATE `pe_bl` SET `supprime` = 1 WHERE `id` IN (' . implode(',', $ids_bls) . ')';
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();
	} // FIN méthode
	public function getNomClientBl($id_bl)
	{

		$query_nom = 'SELECT IFNULL(t.`nom`, "") AS nom FROM `pe_tiers` t JOIN `pe_bl` b ON b.`id_tiers_facturation` = t.`id` WHERE b.`id` = ' . (int)$id_bl;

		$query = $this->db->prepare($query_nom);
		$query->execute();

		if (!$query->execute()) {
			return "";
		}
		$donnee = $query->fetch(PDO::FETCH_ASSOC);
		return $donnee && isset($donnee['nom']) ? $donnee['nom'] : '';
	} // FIN méthode

	// Retounre le prochain numéro de BL à la date passée
	public function getNextNumeroBl($bt = false, $annee = 0, $mois = 0)
	{

		do {
			$numeroBl = $this->searchNextNumeroBl($bt, $annee, $mois);
		} while ($this->checkNumeroBlExisteDeja($numeroBl));

		return $numeroBl;
	} // FIN méthode

	public function searchNextNumeroBl($bt, $annee, $mois)
	{

		if ($annee == 0) {
			$annee = date('y');
			$annee4 = date('Y');
		} else if ($annee < 100) {
			$annee4 = $annee + 2000;
		} else {
			$annee4 = $annee;
			$annee = $annee - 2000;
		}
		if ($mois == 0) {
			$mois = date('m');
		}

		$numBl = $bt ? 'BT' : 'BL';
		$numBl .= $annee; 					// Année sur 2 chiffres
		$numBl .= $mois; 					// Mois  sur 2 chiffrres


		// On cherche combien de BL ont été faite ce mois-ci, et on incrémente
		$query_num = 'SELECT CONVERT(MAX(SUBSTRING(`num_bl`,-3)),UNSIGNED INTEGER)+1 AS num FROM `pe_bl` WHERE YEAR(`date`) = ' . $annee4 . ' AND MONTH(`date`)  = ' . $mois . ' AND SUBSTRING(`num_bl`,3,2) = "' . $annee . '" AND SUBSTRING(`num_bl`,5,2) = "' . $mois . '" ';

		$query = $this->db->prepare($query_num);

		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		$num = $donnees && isset($donnees['num']) ? intval($donnees['num']) : 1;
		return $numBl . sprintf('%03u', $num);
	}

	public function checkNumeroBlExisteDeja($numeroBl)
	{

		$query_verif = 'SELECT COUNT(*) AS nb FROM `pe_bl` WHERE `supprime` = 0 AND `num_bl` =  "' . $numeroBl . '"';
		$query2 = $this->db->prepare($query_verif);
		$query2->execute();
		$donnee2 = $query2->fetch(PDO::FETCH_ASSOC);
		return isset($donnee2['nb']) && (int)$donnee2['nb'] > 1;
	}

	// Retourne un array avec le poids à dispatcher pour chaque ligne de Bl en fonction de sa palette
	public function getPoidsBrutsByPalettes($id_bl)
	{

		// On construit un array pour chaque palette du Bl
		$query_palettes_bl = 'SELECT DISTINCT `id_palette` FROM `pe_bl_lignes` WHERE `supprime` = 0 AND `id_bl` = ' . $id_bl;
		$query = $this->db->prepare($query_palettes_bl);
		$query->execute();
		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnees) {
			$id_palette = isset($donnees['id_palette']) ? intval($donnees['id_palette']) : 0;
			if ($id_palette > 0) {
				$liste[$id_palette] = $id_palette;
			}
		}

		// SI aucune palette on ne va pas plus loin
		if (empty($liste)) {
			return [];
		}

		// On récupère maintenant le poids total des emballages pour chaque palette
		$liste2 = [];
		foreach ($liste as $id_palette) {

			$query_poids_emb_palette = 'SELECT SUM(pp.`poids` * ppp.`qte`) AS poids_emb_palette  FROM `pe_poids_palettes` pp JOIN `pe_palette_poids_palettes` ppp ON ppp.`id_poids_palette` = pp.`id` AND pp.`type` = 0 WHERE ppp.`id_palette` = ' . $id_palette;
			$query = $this->db->prepare($query_poids_emb_palette);
			$query->execute();
			$donnees = $query->fetch(PDO::FETCH_ASSOC);
			$poids = $donnees && isset($donnees['poids_emb_palette']) ? floatval($donnees['poids_emb_palette']) : 0;
			if ($poids > 0) {
				$liste2[$id_palette] = $poids;
			}
		} // FIN boucle sur les id_palettes

		// SI aucun poids on ne va pas plus loin (possible si on a pas encore saisi les emballages de la palette sur le BL)
		if (empty($liste2)) {
			return [];
		}

		return $liste2; // On retourne le poids par palette, car le reste se fait au prorata du nb de colis

	} // FIN méthode

	public function purgeComposBlOrphelines()
	{

		$queryPurge = 'UPDATE `pe_palette_composition` SET `supprime` = 1 WHERE `id_lot_hors_stock` > 0 AND `id` NOT IN
                        ( SELECT `id_compo` FROM `pe_bl_lignes`) 
                        AND  `id_lot_hors_stock` NOT IN (SELECT `id` FROM `pe_lots`)';
		$query = $this->db->prepare($queryPurge);
		Outils::saveLog($queryPurge);
		return $query->execute();
	}

	// ID de la palette ayant ce numéro et faisait partie du BL
	public function getIdPaletteBlByNumero($id_bl, $num_palette)
	{
		$query_id = 'SELECT b.`id_palette` 
						FROM `pe_bl_lignes` b
						JOIN `pe_palettes` p ON p.`id` = b.`id_palette`
					WHERE  p.`supprime` = 0
				       AND p.`numero` = ' . $num_palette . '
				       AND b.`id_bl` = ' . $id_bl;
		$query = $this->db->prepare($query_id);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		return $donnees && isset($donnees['id_palette']) ? intval($donnees['id_palette']) : 0;
	}

	public function isBlHasPoidsBruts(Bl $bl)
	{

		$query_poids = 'SELECT COUNT(*) AS nb 
							FROM `pe_palette_poids_palettes` ppp 
								JOIN `pe_poids_palettes` pp ON pp.`id` = ppp.`id_poids_palette`
						WHERE `qte` > 0 AND `id_palette` IN (SELECT DISTINCT `id_palette` FROM `pe_bl_lignes` WHERE `id_palette` > 0 AND `id_bl` = ' . $bl->getId() . ')';

		$query = $this->db->prepare($query_poids);
		$query->execute();
		$donnees = $query->fetch(PDO::FETCH_ASSOC);
		if (!$donnees || !isset($donnees['nb'])) {
			return false;
		}
		return intval($donnees['nb']) > 0;
	}

	public function razDateEnvoiBl(Bl $bl)
	{
		$query_upd = 'UPDATE `pe_bl` SET `date_envoi` = NULL WHERE `id` = ' . $bl->getId();
		$query = $this->db->prepare($query_upd);
		Outils::saveLog($query_upd);
		return $query->execute();
	}

	public function forcePalettesRegroupement(Bl $bl)
	{

		if ((int)$bl->getRegroupement() == 0) {
			return $bl;
		}

		$palettes = [];
		// boucle sur les lignes
		foreach ($bl->getLignes() as $ligne) {
			if (!isset($palettes[(int)$ligne->getNum_palette()])) {
				$palettes[(int)$ligne->getNum_palette()] = [];
			}
			$palettes[(int)$ligne->getNum_palette()][] = $ligne->getId_palette();
		} // FIN boucle sur les lignes

		if (empty($palettes)) {
			return $bl;
		}

		$numPalettesToUpdate = [];

		// boucle sur les ids_palettes d'un meme numéro de palette forcé
		foreach ($palettes as $num_palette => $ids_palettes) {
			if ((int)$num_palette == 0) {
				continue;
			}

			// On ne garde que les id_palettes disctincts en supprimant les doublons
			$ids_palettes = array_unique($ids_palettes);

			// si pour ce numéro de palette forcé on a plusieurs id_palettes
			if (count($ids_palettes) > 1) {

				// On prends le premier comme référence
				$numPalettesToUpdate[$num_palette] = $ids_palettes[0];
			} // FIN test plusieurs id_palette pour un meme numéro palette forcé

		} // FIN boucle sur les ids_palettes d'un meme numéro de palette forcé

		if (empty($numPalettesToUpdate)) {
			return $bl;
		}
		$logManager = new LogManager($this->db);

		// boucle sur les palettes à regrouper
		foreach ($numPalettesToUpdate as $num_palette => $id_palette) {

			$query_upd = 'UPDATE `pe_bl_lignes` SET `id_palette` = ' . $id_palette . ' 
							WHERE `id_bl` = ' . $bl->getId() . ' 
								AND `num_palette` = "' . $num_palette . '"';
			$query = $this->db->prepare($query_upd);
			Outils::saveLog($query_upd);
			if ($query->execute()) {
				$log = new Log();
				$log->setLog_type('info');
				$log->setLog_texte("Modification auto de l'id palette #" . $id_palette . " sur le numéro de palette forcé " . $num_palette . " du BL #" . $bl->getId());
				$logManager->saveLog($log);
			}
		} // FIN boucle sur les palettes à regrouper

		$lignes_bl = $this->getListeBlLignes(['id_bl' => $bl->getId(), 'regroupement' => $bl->getRegroupement()]);
		$bl->setLignes($lignes_bl);

		return $bl;
	} // FIN méthode


	public function getProduitsNegoceProduitStock($id_lot_pdt_negoce)
	{
		$query_object = "SELECT 
		bl.`id_bl`, 
		fs.`id` as id_facture, 
		bl.`id_compo`, 
		b.`num_bl`, 
		fs.`num_facture`, 
		t.`nom` AS libelle,  
		bl.`id_palette`, 
		bl.`id_produit`, 
		bl.`numlot`, 
		bl.`poids`, 
		bl.`date_add`
	FROM `pe_bl_lignes` bl
	JOIN `pe_bl` b ON b.`id` = bl.`id_bl`
	JOIN `pe_tiers` t ON t.`id` = b.`id_tiers_facturation`
	LEFT JOIN `pe_bl_facture` f ON f.`id_bl` = bl.`id_bl`
	LEFT JOIN `pe_factures` fs ON fs.`id` = f.`id_facture`
	WHERE bl.`supprime` = 0 
	  AND bl.`id_pdt_negoce` = ".(int)$id_lot_pdt_negoce;	  	
		$query = $this->db->prepare($query_object);

		$query->execute();
		$liste = [];
		foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $donnee) {			
			$liste[] = new BlLigne($donnee);
		}
		
		return $liste;
	} // FIN méthode

} // FIN classe