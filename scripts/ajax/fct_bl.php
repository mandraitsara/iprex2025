<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax Bons de Livraisons
------------------------------------------------------*/
error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$tiersManager = new TiersManager($cnx);
$logsManager  = new LogManager($cnx);
$blsManagers  = new BlManager($cnx);
$logManager	  = new LogManager($cnx);


$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}

/* ------------------------------------------------
MODE - Retourne l'URL de création de BL (vue front)
-------------------------------------------------*/
function modeGetBlFrontUrl() {

	global $cnx;

    $ids_compos = isset($_REQUEST['ids_compos']) ? trim($_REQUEST['ids_compos']) : '';
    if ($ids_compos == '') { exit('-1'); }

    // On vérifie que les compos soient pour le même client !
	$compoManager = new PalettesManager($cnx);

	// On transforme en Array les compos passées
	$composArray = explode(',', $ids_compos);

	// Si problème avec l'array...
	if (!is_array($composArray)) { exit('-2'); }

	// On prépare une liste de clients
	$clients = [];

	// Boucle sur les ID compos passées
	foreach ($composArray as $id_compo) {

		// Instanciation
		$compo = $compoManager->getComposition($id_compo);

		// Si pb avec la compo on passe et on la retire des compos à traiter
		if (!$compo instanceof PaletteComposition) { $ids_compos = str_replace($id_compo, '0', $ids_compos); continue; }

		// On ajoute le client à la liste s'il n'y est pas déjà
		$clients[$compo->getId_client()] = $compo->getId_client();

	} // FIN boucle compos


	// Si on a plus qu'un seul client, pas possible de faire un BL
	if (count($clients) != 1) { exit('0'); }

    echo __CBO_ROOT_URL__.'bl-addupd.php?c='.base64_encode($ids_compos);
    exit;


} // FIN mode


/* ------------------------------------------------
MODE - Genère le PDF du BL (FO)
-------------------------------------------------*/
function modeGenerePdfBl() {

	global $cnx, $blsManagers;

	$produitManager = new ProduitManager($cnx);

	// Récupération de l'ID et instanciation de l'objet
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id == 0) { exit('-1'); }

	$bl = $blsManagers->getBl($id, true, true); // Compatbilité avec les lignes sans compo (négoce...)
	if (!$bl instanceof Bl) { exit('-2'); }

	// Récupération des variables
	$date_bl 		= isset($_REQUEST['date_bl']) 		? trim($_REQUEST['date_bl']) 				: date('d/m/Y');
	$num_cmd 		= isset($_REQUEST['num_cmd']) 		? trim(mb_strtolower($_REQUEST['num_cmd'])) : '';
	$id_t_fact  	= isset($_REQUEST['id_t_fact']) 	? intval($_REQUEST['id_t_fact']) 			: 0;
	$id_t_livr  	= isset($_REQUEST['id_t_livr']) 	? intval($_REQUEST['id_t_livr']) 			: 0;
	$id_adresse  	= isset($_REQUEST['id_adresse']) 	? intval($_REQUEST['id_adresse']) 			: 0;
	$id_transp  	= isset($_REQUEST['id_transp']) 	? intval($_REQUEST['id_transp']) 			: 0;

	$date_livraison = isset($_REQUEST['date_livraison']) ? Outils::dateFrToSql(trim($_REQUEST['date_livraison']))        : '';

	// Mise à jour de l'objet
	$bl->setNum_cmd($num_cmd);
	$bl->setDate(Outils::dateFrToSql($date_bl));
	$bl->setId_tiers_facturation($id_t_fact);
	$bl->setId_tiers_livraison($id_t_livr);
	$bl->setId_adresse_livraison($id_adresse);
	$bl->setId_tiers_transporteur($id_transp);
    if ($date_livraison != '') {
		$bl->setDate_livraison($date_livraison);
	}
	$blsManagers->saveBl($bl);

	$tiersManager = new TiersManager($cnx);

	// On rend persistant les données des lignes du BL en dur
	foreach ($blsManagers->getListeBlLignes(['id_bl' => $bl->getId(), 'regroupement' => 0]) as $ligne) {

		$pdt = $produitManager->getProduit($ligne->getId_produit(), false);
		if ((int)$pdt->getVendu_piece() == 0) { $ligne->setQte(1); }

		$tva = round($produitManager->getTvaProduit($ligne->getId_produit()),2);
        if ($tva < 0) { $tva = 0;}

		// Si on a un id_pays déjà renseigné dans la ligne de BL et si on en as pas on prends alors comme avant celui du lot
		$id_pays = (int)$ligne->getId_pays() > 0 ? $ligne->getId_pays() : $blsManagers->getIdPaysFromLot($ligne->getId_lot());

		$ligne->setId_palette($ligne->getId_palette());
		$ligne->setId_produit($ligne->getId_produit());
		$ligne->setId_produit_bl($ligne->getId_produit_bl());
		$ligne->setId_lot($ligne->getId_lot());
		$ligne->setNumlot($ligne->getNumlot());
		$ligne->setPoids($ligne->getPoids());
		$ligne->setNb_colis($ligne->getNb_colis());
		$ligne->setQte($ligne->getQte() > 0 ? $ligne->getQte() : 1);
		$ligne->setPu_ht($ligne->getPu_ht());
		$ligne->setTva($tva);
		$ligne->setId_pays($id_pays);

		$blsManagers->saveBlLigne($ligne);

	} // FIN boucle sur les lignes

	// Choix de langue et chiffrage pour PDF
	$id_langue  	= isset($_REQUEST['id_langue']) 	? intval($_REQUEST['id_langue']) 			: 1;
	$bl->setId_langue($id_langue);

    $client = $id_t_livr > 0 ? $tiersManager->getTiers($id_t_livr) : $tiersManager->getTiers($id_t_fact);

    if ($client instanceof Tiers) {
        $client->setId_langue($id_langue);
        $tiersManager->saveTiers($client);
    }

	$configManager = new ConfigManager($cnx);
	$pdf_top_bl = $configManager->getConfig('pdf_top_bl');
	$margeEnTetePdt = $pdf_top_bl instanceof Config ?  (int)$pdf_top_bl->getValeur() : 0;

	// Génère le PDF
	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content_fichier = genereContenuPdf($bl, 1, false, false);
	$html_additionnel = getDebutTableauBl($bl);
    $content_header = genereHeaderPdf($bl, $html_additionnel);
	$contentPdf = '<page backtop="'.(int)$margeEnTetePdt.'mm">';
	$contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style>'.$content_header.'</page_header>';
	// $contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_ROOT_PATH__.'/css/pdf.css').'</style>'.$content_header.'</page_header>';
	$contentPdf.= $content_fichier;
	$contentPdf.= '</page>'. ob_get_clean();

	$dir = $blsManagers->getDossierBlPdf($bl);

    $num_bl = $bl->getNum_bl() != '' ? $bl->getNum_bl() : $bl->getCode();

	try {
		$marges = [7, 12, 7, 12];
		$nom_fichier = $num_bl . '.pdf';
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'ISO-8859-15', $marges);
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($contentPdf));;
		$savefilepath = __CBO_ROOT_PATH__ . $dir . $nom_fichier;
		$html2pdf->Output($savefilepath, 'F');

	} catch (HTML2PDF_exception $e) {
		vd($e);
		exit;
	}

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("Génération et téléchargement depuis STK du BL #".$bl->getId());
	$logsManager = new LogManager($cnx);
	$logsManager->saveLog($log);

	// OK, on met à jour le statut
	$bl->setStatut(2); // 2 = Généré
	$bl->setSupprime(0);
	$blsManagers->saveBl($bl);

	$blsManagers->supprComposBl($bl);

	$blsManagers->razDateEnvoiBl($bl);

	exit;

} // FIN mode

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le contenu HTML du header
-----------------------------------------------------------------------------*/
function genereHeaderPdf(Bl $bl, $html_additionnel) {

    global $cnx;
	$documentsManager = new DocumentManager($cnx);

	$tiersManager = new TiersManager($cnx);
	$client = $tiersManager->getTiers($bl->getId_tiers_livraison());
	if (!$client instanceof Tiers) {
		$client = $tiersManager->getTiers($bl->getId_tiers_facturation());
	}
	if (!$client instanceof Tiers) { $client = new Tiers([]); } // Gestion des erreurs
	$id_langue = $bl->getId_langue();

	$type = $bl->isBt() ? 'bt' : 'bl';

	return $documentsManager->getHeaderDocumentPdf($client, 'l', $type, $id_langue, true, true, $bl->getCode(), $html_additionnel);
} // FIN fonction

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère les lignes de titre du tableau (header)
-----------------------------------------------------------------------------*/
function getDebutTableauBl(Bl $bl) {

    global $cnx, $blsManagers;
	$traductionsManager = new TraductionsManager($cnx);

	$poidsBruts = $blsManagers->isBlHasPoidsBruts($bl);

	$w1 = $bl->isChiffre() ? 40 : 64;

	$showQte = false;
	foreach ($bl->getLignes() as $ligne) {
		if (!$ligne->getProduit() instanceof Produit) {
			$ligne->setProduit(new Produit([]));
		}
		if (intval($ligne->getProduit()->getVendu_piece()) == 1) {
			$showQte = true;
			break;
		}
	}
	if (!$showQte) {
		$w1+= 8;
    }

	$date_bl = Outils::dateSqlToFr($bl->getDate());
	$date_liv = Outils::dateSqlToFr($bl->getDate_livraison());
	$tiersManager = new TiersManager($cnx);
	$transporteur = $tiersManager->getTiers($bl->getId_tiers_transporteur());
	if (!$transporteur instanceof Tiers) { $transporteur = new Tiers([]); } // Gestion erreur

	$contenu ='<table class="table table-blfact w100 mt-15">';
	$contenu.='<tr class="entete">';
	$contenu.='<td class="w40 border-l border-t">';
	$contenu.= $traductionsManager->getTrad('date', $bl->getId_langue()) . ' : ' . $date_bl;
	$contenu.= $date_liv != '' ? ' - '. $traductionsManager->getTrad('date_liv', $bl->getId_langue()) . ' : ' . $date_liv : '';
	$contenu.='</td>';
	$contenu.='<td class="w40 text-left border-t">';

	if ($transporteur->getNom() != '') {
		$contenu.= $traductionsManager->getTrad('transport', $bl->getId_langue()) . ' : ' . $transporteur->getNom();
	}

	$contenu.='</td>';
	$contenu.='<td class="w20 border-t border-r text-right">';
	if ($bl->getNum_cmd() != '' && $bl->getNum_cmd() != 0) {
		$contenu .= $traductionsManager->getTrad('votre_cmd', $bl->getId_langue()) . ' : ' . strtoupper($bl->getNum_cmd());
	}

    if ($poidsBruts) {
		$w1-=12; // Ajout du poids brut, on adapte la largeur de la désignation
    }

    $contenu.='</td>';
	$contenu.='</tr>';
	$contenu.='</table>';
	$contenu.='<table class="table table-blfact w100">';
	$contenu.='<tr class="entete">';
	$contenu.='<td class="w8 border-t border-l border-r border-b">'.$traductionsManager->getTrad('code', $bl->getId_langue()).'</td>';
	$contenu.='<td class="w'.$w1.' border-t border-l border-r border-b">'.$traductionsManager->getTrad('designation', $bl->getId_langue()).'</td>';
	$contenu.='<td class="w8 text-center border-t border-l border-r border-b">'.$traductionsManager->getTrad('colis', $bl->getId_langue()).'</td>';
	$contenu.= $showQte ? '<td class="w8 text-center border-t border-l border-r border-b">'.$traductionsManager->getTrad('qte', $bl->getId_langue()).'</td>' : '';
	$contenu.='<td class="w12 text-right border-t border-l border-r border-b">'.$traductionsManager->getTrad('poids', $bl->getId_langue()).' (kg)</td>';
	$contenu.= $poidsBruts ? '<td class="w12 text-right border-t border-l border-r border-b">'.$traductionsManager->getTrad('poidsbrut', $bl->getId_langue()).' (kg)</td>' : '';
	if ($bl->isChiffre()) {
		$contenu.='<td class="w12 text-right border-t border-l border-r border-b">';
		$contenu.= $traductionsManager->getTrad('puht', $bl->getId_langue());
		$contenu.='</td>';
		$contenu.='<td class="w12 text-right border-t border-l border-r border-b"> ';
		$contenu.= $traductionsManager->getTrad('totht', $bl->getId_langue());
		$contenu.='</td>';
	}

	$contenu.='</tr>';
	$contenu.='</table>';

	return $contenu;
} // FIN fonction

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le contenu HTML du lot pour le PDF
-----------------------------------------------------------------------------*/
function genereContenuPdf(Bl $bl, $copies = 1, $show_pages = true, $html = true) {

	global $cnx, $blsManagers;

    // SI la date ne correspond pas au numéro de BL on modifie le numéro de BL

    $date_mois = substr($bl->getDate(), 5,2);
    $date_an = substr($bl->getDate(), 2,2);

    $num_mois = substr($bl->getNum_bl(), 4,2);
    $num_an = substr($bl->getNum_bl(), 2,2);

    if ($date_mois != $num_mois || $date_an != $num_an) {
        $bt = $bl->getBt() == 1;
        $numBl = $blsManagers->getNextNumeroBl($bt, $date_an, $date_mois);
        if (strlen($numBl) < 7) {  exit('ERREUR REGENERE NUM BL');}

        $bl->setNum_bl($numBl);
        $blsManagers->saveBl($bl);
    }


	$tiersManager = new TiersManager($cnx);
	$client = $tiersManager->getTiers($bl->getId_tiers_livraison());
	if (!$client instanceof Tiers) {
		$client = $tiersManager->getTiers($bl->getId_tiers_facturation());
	}
	if (!$client instanceof Tiers) { $client = new Tiers([]); } // Gestion des erreurs

	// On met à jour le chiffrage selon le client car toujours relatif à la fiche client !
    $bl->setChiffrage((int)$client->getBl_chiffre());
	$blsManagers->saveBl($bl);

	$poidsBruts = $blsManagers->isBlHasPoidsBruts($bl);

	$contenu = $html ? '<!DOCTYPE html>
        <html>
        <head>
          <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
          <style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style> 
        </head>
        <body id="blhtml">' : '';

	$traductionsManager = new TraductionsManager($cnx);
	$id_langue = $bl->getId_langue();

	$nb_palettes = 0;

	$w1 = $bl->isChiffre() ? 40 : 64;

		$date_bl = Outils::dateSqlToFr($bl->getDate());
		$tiersManager = new TiersManager($cnx);
		$transporteur = $tiersManager->getTiers($bl->getId_tiers_transporteur());
		if (!$transporteur instanceof Tiers) { $transporteur = new Tiers([]); } // Gestion erreur

		$contenu.='<table class="table table-blfact w100">';

		$total_colis 	= 0;
		$total_colis_bl = 0;
		$id_palette 	= -1;
		$num_palette 	= 0;
		$total_poids 	= 0.0;
		$total_poids_palette = 0.0;
		$total_poids_bl = 0.0;
		$total_pu_bl 	= 0.0;
		$total_total_bl = 0.0;
	    $total_qte      =  0;
	    $total_qte_bl   = 0;
	    $total_palette_poids_palette_bl = 0.0;

	    $poidsPalettesManager = new PoidsPaletteManager($cnx);

	    // On affiche la quantitié si au moins un produit à la pièce dans le BL

        $showQte = false;
		
        foreach ($bl->getLignes() as $ligne) {
			if (!$ligne->getProduit() instanceof Produit) {
				$ligne->setProduit(new Produit([]));
			}
            if (intval($ligne->getProduit()->getVendu_piece()) == 1) {
				$showQte = true;				
				break;
            }
        }

	    if (!$showQte) {
		    $w1+=  8;
	    }

        if ($poidsBruts) {
			$w1-=12; // Poids brut, on adapte la largeur de la colonne désignation
        }


        // Calcul du poids brut par ligne
	    $poidsPalettes = $blsManagers->getPoidsBrutsByPalettes($bl->getId());

        // on fais une première boucle sur les lignes du bl pour en sortir un array [id_palette => nb_colis_total]
        $nb_colis_palettes = [];
	    foreach ($bl->getLignes() as $ligne) {
            if ($ligne->getId_palette() > 0) {
                if (!isset($nb_colis_palettes[$ligne->getId_palette()])) {
					$nb_colis_palettes[$ligne->getId_palette()] = 0;
                }
				$nb_colis_palettes[$ligne->getId_palette()]+=$ligne->getNb_colis();
            }
	    }

	$id_client_web = $tiersManager->getId_client_web();
	$web = $id_client_web > 0 && $bl->getId_tiers_facturation() == $id_client_web;

	if ($web) {
		$orderPrestashopManager = new OrdersPrestashopManager($cnx);
		$orderPrestashopManager->cleanIdOrderDetailsLignesBl();
	}

	$total_poids_brut_palette = 0;
	$total_poids_brutt_total = 0;
	$total_poids_palette = 0;
	$total_poids_brut = 0;
        foreach ($bl->getLignes() as $ligne) {

            $piece = false;
            if ($ligne->getProduit() instanceof Produit) {
                $piece = $ligne->getProduit()->isVendu_piece();
            }

			//Modification de con
            $nb_colis_ligne = $piece ? $ligne->getNb_colis() : $ligne->getNb_colis();

			if ($web) {
				$nb_colis_ligne = $ligne->getNb_colis() > 0 ? $ligne->getNb_colis() : 1;
			}

			if ($id_palette < 0) {
                $id_palette = $ligne->getId_palette();
                $num_palette = $ligne->getNumero_palette();
                $nb_palettes++;
            }

			// pour chaque ligne on calcule le poids brut en faisant poids_net + ((nb_colis * poids) / nb_colis_total)
            $poidsBrut = isset($poidsPalettes[$ligne->getId_palette()]) &&  isset($nb_colis_palettes[$ligne->getId_palette()]) && $nb_colis_palettes[$ligne->getId_palette()] > 0
                ? $ligne->getPoids() + ( ($ligne->getNb_colis() * $poidsPalettes[$ligne->getId_palette()]) / $nb_colis_palettes[$ligne->getId_palette()])
                : $ligne->getPoids();

            if ($ligne->getId_palette() != $id_palette) {

                $total_palette_poids_palette = $poidsPalettesManager->getTotalPoidsPalette($id_palette);
                $total_palette_poids_palette_bl+=$total_palette_poids_palette;
                $total_colis_palette =  $total_colis > 0 ? $total_colis : '-';
                $total_colis_paletteTxt = $total_colis_palette > 0 ? $total_colis_palette : '';

				$total_palette_poids_palette = $poidsPalettesManager->getTotalPoidsPalette($id_palette);
				$total_poids_total = $total_poids_palette + $total_palette_poids_palette;

				$total_poids_totalTxt = $total_poids_total == $total_poids ? '' : number_format($total_poids_total, 3, '.', ' ');

                $txtP = intval($num_palette) > 0 ? $traductionsManager->getTrad('totpalnum', $bl->getId_langue()) . $num_palette : 'Total';

				$totalQte = $total_qte > 0 ? $total_qte : '';

                $contenu .= '<tr class="soustotal">';
                $contenu .= '<td class="w8 border-l border-r pb-10"></td>';
                $contenu .= '<td class="w'.$w1.' text-right border-l border-r pb-10">' . $txtP . '</td>';
                $contenu .= '<td class="w8 text-center border-l border-r border-t-d pb-10">' . $total_colis_paletteTxt . '</td>';
                $contenu .= $showQte ? '<td class="w8 text-center  border-l border-r border-t-p pb-10">'. $totalQte.'</td>' : '';
                $contenu .= '<td class="w12 text-right border-l border-r border-t-d pb-10">' . number_format($total_poids, 3, '.', ' ') . '</td>';
                $contenu .= $poidsBruts ? '<td class="w12 text-right border-l border-r border-t-d pb-10">' . $total_poids_totalTxt . '</td>' : '';
                if ($bl->isChiffre()) {
                    $contenu .= '<td class="w12 border-l border-r pb-10"></td>';
                    $contenu .= '<td class="w12 border-l border-r pb-10"></td>';
                }
                $contenu .= '</tr>';

                $num_palette = $ligne->getNumero_palette();
                $id_palette = $ligne->getId_palette();
                if ($ligne->getId_palette() > 0) {
                    $nb_palettes++;
                }
				$total_poids_brutt_total+=$total_poids_total;
                $total_colis = 0;
                $total_poids = 0.0;
				$total_poids_palette = 0.0;
                $total_qte = 0;
				$total_poids_brut_palette = 0;
            }
			$total_qte += $piece ? 0 : '';
            $total_colis += $piece && !$web ? (int)$ligne->getNb_colis() : (int)$ligne->getNb_colis();
            $total_poids += (float)$ligne->getPoids();
			$total_poids_palette+= (float)$ligne->getPoids();
			//ajout de condition
            $total_colis_bl +=  $piece && !$web ? (int)$ligne->getNb_colis() : (int)$ligne->getNb_colis();
            $total_poids_bl += (float)$ligne->getPoids();
            $total_pu_bl += (float)$ligne->getPu_ht();
            $total_total_bl += round((float)$ligne->getTotal(),2);
            $total_qte+= $piece ? (int)$ligne->getQte() : '';
            $total_qte_bl+= $piece ?  (int)$ligne->getQte() : '';

            $designation = 'Non défini !';
            if ($ligne->getDesignation() != '') {
                $designation = $ligne->getDesignation();
            } else if ($ligne->getProduit() instanceof Produit) {
                $noms = $ligne->getProduit()->getNoms();
                $designation = isset($noms[$bl->getId_langue()]) ? $noms[$bl->getId_langue()] : 'Traduction manquante !';

            } // FIN test désignation libre / nom du produit

            // Si client web, on récup l'order_ligne si on l'a
			if ($web) {

				$od = $orderPrestashopManager->getOrderLigneByIdLigneBl($ligne->getId());
				if ($od instanceof OrderDetailPrestashop) {
					$designation.= '<br>Commande Web '.$od->getReference_order() . '<br>'.$od->getNom().'<br>'.$od->getNom_client();
                }

			} // FIN web

            if (!$html) {
                // DEBUG symbole non géré par HTML2PDF
                $designation =str_replace('œ', 'oe', $designation);
                $designation =str_replace('Œ', 'OE', $designation);
                $designation =str_replace('&OElig;', 'OE', $designation);
                $designation =str_replace('&oelig;', 'oe', $designation);
                $designation =str_replace('&#140;', 'OE', $designation);
                $designation =str_replace('&#156;', 'oe', $designation);
            }


			$poidsBrutTxt = $poidsBrut == $ligne->getPoids() ? '' : number_format($poidsBrut,3,'.', ' ');


            $nb_colis_ligneTxt = $nb_colis_ligne > 0 ? $nb_colis_ligne : '';


            $code = (int)$ligne->getCode() > 0 ? $ligne->getCode() :  '-';
            //$qte = $ligne->getQte() > 1 ? $ligne->getQte() : 1;
			//Modification de condition, n'affiche pas la valeur de quantité s'il est vendu en kg
			$qte = $piece ? $ligne->getQte() : '';
            $contenu .= '<tr>';
            $contenu .= '<td class="w8 border-l border-r">' . $code . '</td>';
            $contenu .= '<td class="w'.$w1.' border-l border-r">' . $designation;
            $contenu .= $ligne->getNumlot() != '' ? '<br>' . $traductionsManager->getTrad('lot', $bl->getId_langue()) . ' : ' . $ligne->getNumlot() : '';
            $contenu .=  $ligne->getOrigine() != '' ? ' Orig : ' . $ligne->getOrigine() : '';
            $contenu .= '</td>';
            $contenu .= '<td class="w8 text-center border-l border-r border-b-d">' . $nb_colis_ligneTxt . '</td>';
            $contenu .= $showQte ? '<td class="w8 text-center border-l border-r border-b-d">' .  $qte . '</td>' : '';
            $contenu .= '<td class="w12 text-right border-l border-r border-b-d">' . number_format($ligne->getPoids(), 3, ".", " ") . '</td>';
            $contenu .= $poidsBruts ? '<td class="w12 text-right border-l border-r border-b-d">' . $poidsBrutTxt . '</td>' : '';
            if ($bl->isChiffre()) {
                $contenu .= '<td class="w12 text-right border-l border-r">';
                $contenu .= $ligne->getPu_ht() > 0 ? number_format($ligne->getPu_ht(), 2, ".", " ") : '';
                $contenu .= '</td>';
                $contenu .= '<td class="w12 text-right border-l border-r">';
                $contenu .= $ligne->getPu_ht() > 0 ? number_format($ligne->getTotal(), 2, ".", " ") : '';
                $contenu .= '</td>';
            }
            $contenu .= '</tr>';

			$total_poids_brut_palette+=$poidsBrut;

        } // FIN boucle sur les lignes

	    $total_colis_palette =  $total_colis > 0 ? $total_colis : $total_colis;

        if ($id_palette > 0) {
			$total_palette_poids_palette = $poidsPalettesManager->getTotalPoidsPalette($id_palette);
			$total_poids_total = $total_poids_palette + $total_palette_poids_palette;

			$total_poids_totalTxt = $total_poids_total == $total_poids ? '' : number_format($total_poids_total,3, '.', ' ');

			$txtP = intval($num_palette) > 0 ? $traductionsManager->getTrad('totpalnum', $bl->getId_langue()) . $num_palette : 'Total';

			$contenu.='<tr class="soustotal">';
			$contenu.='<td class="w8 border-l border-r"></td>';
			$contenu.='<td class="w'.$w1.' text-right border-l border-r">'.$txtP.'</td>';
			$contenu.='<td class="w8 text-center border-l border-r border-t-d">'.$total_colis_palette.'</td>';
			$contenu.= $showQte ? '<td class="w8 text-center border-t border-l border-r border-t-d">'.$total_qte.'</td>' : '';
			$contenu.='<td class="w12 text-right border-l border-r border-t-d">'.number_format($total_poids,3, '.', ' ').'</td>';
			$contenu.= $poidsBruts ? '<td class="w12 text-right border-l border-r border-t-d">'.$total_poids_totalTxt.'</td>' : '';
			if ($bl->isChiffre()) {
				$contenu .= '<td class="w12 no-border-t border-l border-r"></td>';
				$contenu .= '<td class="w12 no-border-t border-l border-r"></td>';
			}
			$contenu.='</tr>';
			$total_poids_brutt_total+=$total_poids_total;
			$total_poids_brut_palette = 0;

        }

	if ($piece && !$web) {
		$total_colis_blTxt = $total_colis_bl > 0 ? $total_colis_bl : '';
    } else {
		$total_colis_blTxt = (int)$total_colis_bl;
    }


	$total_poids_brutt_totalTxt = $total_poids_brutt_total == $total_poids_bl ? '' : number_format($total_poids_brutt_total,3, '.', ' ');


    $s = $nb_palettes > 1 ? 's' : '';
	$contenu.='<tr class="entete">';
	$contenu.='<td class="w8 border-l border-r border-b border-t"></td>';
	$contenu.='<td class="w'.$w1.' text-right border-l border-r border-b border-t">'.$traductionsManager->getTrad('total_exp', $bl->getId_langue()).' ('.$nb_palettes.' '.strtolower($traductionsManager->getTrad('palette', $bl->getId_langue())).$s.')</td>';
	$contenu.='<td class="w8 text-center border-l border-r border-b border-t vmiddle">'.$total_colis_blTxt.'</td>';
	$contenu.= $showQte ? '<td class="w8 text-center border-l border-t border-r border-b">'.$total_qte_bl.'</td>' : '';
	$contenu.='<td class="w12 text-right border-l border-r border-t border-b vmiddle">'.number_format($total_poids_bl,3, '.', ' ').'</td>';
	$contenu.=$poidsBruts  ? '<td class="w12 text-right border-l border-r border-t border-b vmiddle">'.$total_poids_brutt_totalTxt.'</td>' : '';
	if ($bl->isChiffre()) {
		$contenu .= '<td class="w12 text-right border-l border-r border-t border-b vmiddle">';
		$contenu .= '</td>';
		$contenu .= '<td class="w12 text-right border-l border-r border-t border-b vmiddle">';
		$contenu .= number_format($total_total_bl, 2, ".", " ");
		$contenu .= '</td>';
	}
	$contenu.='</tr>';
    $contenu.='</table>';

    // Message fixe client
    if (trim($client->getMessage()) != '') {
        $contenu.='<p class="text-10 mt-10">'.nl2br(strip_tags($client->getMessage())).'</p>';
    }

	$contenu = str_replace('Œ', 'OE', $contenu);

	// RETOUR CONTENU
	return $contenu;

} // FIN fonction déportée

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le header du PDF (logo, n° de lot...)
-----------------------------------------------------------------------------*/
function genereEntetePagePdf(Bl $bl) {

	global $blsManagers , $cnx;

	$traductionsManager = new TraductionsManager($cnx);
	$configManager 		= new ConfigManager($cnx);
	$adressesManager 	= new AdresseManager($cnx);
	$tiersManager		= new TiersManager($cnx);

	$i_raison_sociale_conf 	= $configManager->getConfig('i_raison_sociale');
	$i_raison_sociale 		= $i_raison_sociale_conf instanceof Config ? $i_raison_sociale_conf->getValeur() : '';

	$i_adresse_1_conf 	= $configManager->getConfig('i_adresse_1');
	$i_adresse_1 		= $i_adresse_1_conf instanceof Config ? $i_adresse_1_conf->getValeur() : '';

	$i_adresse_2_conf 	= $configManager->getConfig('i_adresse_2');
	$i_adresse_2 		= $i_adresse_2_conf instanceof Config ? $i_adresse_2_conf->getValeur() : '';

	$i_sec_adresse_1_conf 	= $configManager->getConfig('i_sec_adresse_1');
	$i_sec_adresse_1 		= $i_sec_adresse_1_conf instanceof Config ? $i_sec_adresse_1_conf->getValeur() : '';

	$i_sec_adresse_2_conf 	= $configManager->getConfig('i_sec_adresse_2');
	$i_sec_adresse_2 		= $i_sec_adresse_2_conf instanceof Config ? $i_sec_adresse_2_conf->getValeur() : '';

	$i_tel_conf 			= $configManager->getConfig('i_tel');
	$i_tel 					= $i_tel_conf instanceof Config ? $i_tel_conf->getValeur() : '';

	$i_fax_conf 			= $configManager->getConfig('i_fax');
	$i_fax 					= $i_fax_conf instanceof Config ? $i_fax_conf->getValeur() : '';

	$i_sec_tel_conf 		= $configManager->getConfig('i_sec_tel');
	$i_sec_tel 				= $i_sec_tel_conf instanceof Config ? $i_sec_tel_conf->getValeur() : '';

	$i_sec_fax_conf 		= $configManager->getConfig('i_sec_fax');
	$i_sec_fax 				= $i_sec_fax_conf instanceof Config ? $i_sec_fax_conf->getValeur() : '';

	$i_info_1_conf 			= $configManager->getConfig('i_info_1');
	$i_info_1 				= $i_info_1_conf instanceof Config ? $i_info_1_conf->getValeur() : '';

	$i_info_2_conf 			= $configManager->getConfig('i_info_2');
	$i_info_2 				= $i_info_2_conf instanceof Config ? $i_info_2_conf->getValeur() : '';

	$i_num_agr_conf 		= $configManager->getConfig('i_num_agr');
	$i_num_agr 				= $i_num_agr_conf instanceof Config ? $i_num_agr_conf->getValeur() : '';

	$i_ifs_conf 			= $configManager->getConfig('i_ifs');
	$i_ifs 					= $i_ifs_conf instanceof Config ? $i_ifs_conf->getValeur() : '';

	$adresseObj 			= $adressesManager->getAdresse($bl->getId_tiers_livraison());
	$adresse 				= '';


	if ($adresseObj instanceof Adresse) {

		$adresse.= $adresseObj->getAdresse_1() . '<br>';
		$adresse.= $adresseObj->getAdresse_2() != '' ? $adresseObj->getAdresse_2().'<br>' : '';
		$adresse.= $adresseObj->getCp() != '' ?  $adresseObj->getCp().' ' : '';
		$adresse.= $adresseObj->getVille() != '' ? $adresseObj->getVille().' ' : '';
		$adresse.= $adresseObj->getNom_pays() != '' ? '<br>'.$adresseObj->getNom_pays().' ' : '';

	} // FIN test instanciation Adresse

	$nom_client = '';

	$client = $tiersManager->getTiers($bl->getId_tiers_livraison());
	if ($client instanceof Tiers) {

		$nom_client = strtoupper($client->getNom());

	} // FIN test instanciation Tiers

	$entete = '<div class="header">
                <table class="table w100">
                    <tr>
                        <td class="w50">
                        	<p class="text-26">'.$i_raison_sociale.'</p>
                        	<p class="text-italic">'.$i_sec_adresse_1.'</p>
                        	<p class="text-italic">'.$i_sec_adresse_2.'</p>
                        	<p class="text-italic">Tél. '.$i_tel.'</p>
                        	<p class="text-italic">Fax. '.$i_fax.'</p>
                        	<p class="text-italic">'.$i_info_1.'</p>
                        	<p class="text-italic">'.$i_info_2.'</p>
                        	<p>'.$traductionsManager->getTrad('atelier', $bl->getId_langue()).' :</p>
                        	<p>'.$i_adresse_1.'</p>
                        	<p>'.$i_adresse_2.'</p>
                        	<p>Tél. '.$i_sec_tel.'</p>
                        	<p>Fax. '.$i_sec_fax.'</p>
                        	<p>'.$traductionsManager->getTrad('agrement', $bl->getId_langue()).' : . '.$i_num_agr.'</p>
                        	<p>'.$traductionsManager->getTrad('ifs', $bl->getId_langue()).' : '.$i_ifs.'</p>
						</td>
                        <td class="w50">
                        	<p class="text-right mt-20 text-italic text-16 pr-15">'.$traductionsManager->getTrad('bl', $bl->getId_langue()).' : '.strtoupper($bl->getCode()).'</p>
                        	<p class="mt-75"> </p>
                        	<table class="table w100 adresse-border">
                        		<tr>
                        			<td class="w100 p-10">
                        				<p><b>'.$nom_client.'</b></p>
                        				<br>
                        				<p>'.strtoupper($adresse).'</p>
									</td>
								</tr>
							</table>
						</td>
                      
                    </tr>                
                </table>
               </div>';

	return $entete;

} // FIN fonction déportée

// Changement de produit sur la ligne d'un BL
function modeChangeProduitLigne() {

	global $blsManagers, $cnx;

	$logManager = new LogManager($cnx);

	$id_ligne_bl = isset($_REQUEST['id_ligne_bl']) ? intval($_REQUEST['id_ligne_bl']) : 0;
	$id_produit = isset($_REQUEST['id_produit']) ? intval($_REQUEST['id_produit']) : 0;
	if ($id_ligne_bl == 0 || $id_produit == 0) { exit; }

	$ligne = $blsManagers->getBlLigne($id_ligne_bl);
	if (!$ligne instanceof BlLigne) { exit; }

	if ($ligne->getId_produit() == 0) {
		$ligne->setId_produit($id_produit);
		$ligne->setId_produit_bl(0);
	} else {
		if ($ligne->getId_produit() == $id_produit) {
			$ligne->setId_produit_bl(0);
		} else {
			$ligne->setId_produit_bl($id_produit);
		}
	}

	$res = $blsManagers->saveBlLigne($ligne) ? 1 : 0;
	echo $res ? 1 : 0;
	$log = new Log([]);
	$logTxt = $res
		? 'Changement du produit (#'.$id_produit.') sur la ligne de BL #'.$id_ligne_bl
		: 'Echec lors du changement de produit (#'.$id_produit.') sur la ligne de BL #'.$id_ligne_bl;
	$logType = $res ? 'info' : 'danger';
	$log->setLog_texte($logTxt);
	$log->setLog_type($logType);
	$logManager->saveLog($log);
	exit;


} // FIN mode

// Changement de numéro de palette sur la ligne d'un BL
function modeChangeNumPaletteLigneBySelect() {

	global $blsManagers, $logManager;

	$id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;
	$id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) : 0;
	$old_id_palette = isset($_REQUEST['old_id_palette']) ? intval($_REQUEST['old_id_palette']) : '';
	if ($id_palette == '') { exit; }

	$res = $blsManagers->changeNumeroPaletteBl($id_bl, $id_palette, $old_id_palette);

	echo $res ? 1 : 0;
	$log = new Log([]);
	$logTxt = $res
		? 'Changement du numéro de palette par select en #'.$id_palette.' au lieu de #'.$old_id_palette.' du BL ID#'.$id_bl
		: 'Echec lors du changement du numéro de palette par select en #'.$id_palette.' au lieu de #'.$old_id_palette.' du BL ID#'.$id_bl;
	$logType = $res ? 'info' : 'danger';
	$log->setLog_texte($logTxt);
	$log->setLog_type($logType);
	$logManager->saveLog($log);
	exit;


} // FIN mode

// Changement de numéro de palette sur la ligne d'un BL
function modeChangeNumPaletteLigne() {

	global $blsManagers, $logManager;

	$id_palette = isset($_REQUEST['id_palette']) ? intval($_REQUEST['id_palette']) : 0;     // ID actuel et réel de la palette
	$id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) : 0;                    // ID du BL
	$num_palette = isset($_REQUEST['num_palette']) ? trim($_REQUEST['num_palette']) : '';   // Numéro de palette souhaité
	if ($id_palette == '') { exit; }

    // recherche d'une palette ayant le meme numéro dans le BL,
	$id_palette_numero = $blsManagers->getIdPaletteBlByNumero($id_bl, $num_palette);

    // si oui, on fais un transfert de palette….
    if ($id_palette_numero > 0) {

		$res = $blsManagers->changeNumeroPaletteBl($id_bl, $id_palette_numero, $id_palette);

		echo $res ? 1 : 0;
		$log = new Log([]);
		$logTxt = $res
			? 'Changement du numéro de palette par en #'.$id_palette_numero.' (déjà sur ce BL) au lieu de #'.$id_palette.' du BL ID#'.$id_bl.' par saisie manuelle du numéro '.$num_palette
			: 'Echec lors du changement du numéro de palette par select en #'.$id_palette_numero.' au lieu de #'.$id_palette.' du BL ID#'.$id_bl;
		$logType = $res ? 'info' : 'danger';
		$log->setLog_texte($logTxt);
		$log->setLog_type($logType);
		$logManager->saveLog($log);
		exit;

    // sinon on renseigne le numéro comme indicatif.
    } else {

		$res = $blsManagers->forceNumeroPaletteBl($id_bl, $id_palette, $num_palette);

		echo $res ? 1 : 0;
		$log = new Log([]);
		$logTxt = $res
			? 'Changement du numéro de palette ('.$num_palette.') pour la palette ID#'.$id_palette.' du BL ID#'.$id_bl
			: 'Echec lors du changement du numéro de palette  pour la palette ID#'.$id_palette.' du BL ID#'.$id_bl;
		$logType = $res ? 'info' : 'danger';
		$log->setLog_texte($logTxt);
		$log->setLog_type($logType);
		$logManager->saveLog($log);

    } // FIN test



	exit;


} // FIN mode

// Changement de numéro de lot sur la ligne d'un BL
function modeChangeNumLotLigne() {

	global $blsManagers, $logManager;

	$id_ligne_bl = isset($_REQUEST['id_ligne_bl']) ? intval($_REQUEST['id_ligne_bl']) : 0;
	$numlot = isset($_REQUEST['numlot']) ? trim($_REQUEST['numlot']) : '';
	if ($id_ligne_bl == 0) { exit; }

	$ligne = $blsManagers->getBlLigne($id_ligne_bl);
	if (!$ligne instanceof BlLigne) { exit; }

	$ligne->setNumlot($numlot);

	$res = $blsManagers->saveBlLigne($ligne) ? 1 : 0;
	echo $res ? 1 : 0;
	$log = new Log([]);
	$logTxt = $res
		? 'Changement du numéro de lot sur la ligne de BL #'.$id_ligne_bl
		: 'Echec lors du changement du numéro de lot  sur la ligne de BL #'.$id_ligne_bl;
	$logType = $res ? 'info' : 'danger';
	$log->setLog_texte($logTxt);
	$log->setLog_type($logType);
	$logManager->saveLog($log);
	exit;


} // FIN mode

// Changement de l'id_pays sur la ligne d'un BL
function modeChangeOrigLigne() {

	global $blsManagers, $logManager;

	$id_ligne_bl = isset($_REQUEST['id_ligne_bl']) ? intval($_REQUEST['id_ligne_bl']) : 0;
	$is_pays = isset($_REQUEST['id_pays']) ? intval($_REQUEST['id_pays']) : 0;
	if ($id_ligne_bl == 0 || $is_pays == 0) { exit; }

	$ligne = $blsManagers->getBlLigne($id_ligne_bl);
	if (!$ligne instanceof BlLigne) { exit; }

	$ligne->setId_pays($is_pays);

	$res = $blsManagers->saveBlLigne($ligne) ? 1 : 0;
	echo $res;
	$log = new Log([]);
	$logTxt = $res == 1
		? 'Changement du pays d\'origine sur la ligne de BL #'.$id_ligne_bl
		: 'Echec lors du changement du pays d\'origine sur la ligne de BL #'.$id_ligne_bl;
	$logType = $res ? 'info' : 'danger';
	$log->setLog_texte($logTxt);
	$log->setLog_type($logType);
	$logManager->saveLog($log);
	exit;


} // FIN mode

// Supprime une ligne d'un BL
function modeSupprLigneBl() {

	global $blsManagers, $logManager, $cnx;

	$compoManager = new PalettesManager($cnx);

	$id_ligne_bl = isset($_REQUEST['id_ligne_bl']) ? intval($_REQUEST['id_ligne_bl']) : 0;
	if ($id_ligne_bl == 0) { exit('-1'); }

	$ligne = $blsManagers->getBlLigne($id_ligne_bl);
	if (!$ligne instanceof BlLigne) { exit('-2'); }

	$ligne->setSupprime(1);

	$compo = $compoManager->getComposition($ligne->getId_compo());
	if ($compo instanceof PaletteComposition) {
	    $compo->setSupprime(1);
	    $compo->setArchive(0);
	    $compoManager->savePaletteComposition($compo);
    }
	$res = $blsManagers->saveBlLigne($ligne) ? 1 : 0;
	echo $res ? 1 : 0;
	$log = new Log([]);
	$logTxt = $res
		? 'Suppression (flag) de la ligne de BL #'.$id_ligne_bl
		: 'Echec lors de la suppression de la ligne de BL #'.$id_ligne_bl;
	$logType = $res ? 'info' : 'danger';
	$log->setLog_texte($logTxt);
	$log->setLog_type($logType);
	$logManager->saveLog($log);
	exit;

} // FIN mode

// Changement du nombre de colis sur la ligne d'un BL
function modeChangeNbColisLigne() {

	global $blsManagers, $logManager;

	$id_ligne_bl = isset($_REQUEST['id_ligne_bl']) ? intval($_REQUEST['id_ligne_bl']) : 0;
	$nb_colis = isset($_REQUEST['nb_colis']) ? intval($_REQUEST['nb_colis']) : 0;
	if ($id_ligne_bl == 0) { exit; }

	$ligne = $blsManagers->getBlLigne($id_ligne_bl);
	if (!$ligne instanceof BlLigne) { exit; }

	$bl = $blsManagers->getBl($ligne->getId_bl(), false);
	if (!$bl instanceof Bl) { exit('ERR INSTOBJ BL #'.$ligne->getId_bl()); }

	// Ici il faut savoir si cette ligne en cache d'autres (comme les trains) a cause du regroupement de lignes actif.
	$lignesRegroupees = $blsManagers->getBlLignesRegroupeesFromLigne($ligne);

	// SI on a d'autres lignes regroupées
	if (!empty($lignesRegroupees) && $bl->getRegroupement() == 1) {

	    // On répartie sur toutes les lignes le nouveau total pour pouvoir dé-regrouper sans tout perdre
		$nb_colis = $nb_colis / (count($lignesRegroupees) + 1);
		if ($nb_colis < 1) { $nb_colis = 1; }

	    foreach ($lignesRegroupees as $lr) {

			$lr->setNb_colis($nb_colis);

	        if (!$blsManagers->saveBlLigne($lr)) { exit('-6'); }
	        $log = new Log([]);
	        $log->setLog_type('info');
	        $log->setLog_texte("Modif auto ligne BL #".$lr->getId()." suite modif manuelle nb_colis total sur la ligne #".$id_ligne_bl." (regroupement)");
			$logManager->saveLog($log);

        } // FIN boucle lignes regroupées

    } // FIN test lignes regroupées

	$ligne->setNb_colis($nb_colis);

	$res = $blsManagers->saveBlLigne($ligne) ? 1 : 0;
	echo $res ? 1 : 0;
	$log = new Log([]);
	$logTxt = $res
		? 'Changement du nombre de colis sur la ligne de BL #'.$id_ligne_bl
		: 'Echec lors du changement du nombre de colis sur la ligne de BL #'.$id_ligne_bl;
	$logType = $res ? 'info' : 'danger';
	$log->setLog_texte($logTxt);
	$log->setLog_type($logType);
	$logManager->saveLog($log);
	exit;

} // FIN mode

// Changement de la quantité sur la ligne d'un BL
function modeChangeQteLigne() {

	global $blsManagers, $logManager;

	$id_ligne_bl = isset($_REQUEST['id_ligne_bl']) ? intval($_REQUEST['id_ligne_bl']) : 0;
	$qte = isset($_REQUEST['qte']) ? intval($_REQUEST['qte']) : 0;
	if ($id_ligne_bl == 0) { exit; }

	$ligne = $blsManagers->getBlLigne($id_ligne_bl);
	if (!$ligne instanceof BlLigne) { exit; }

	$bl = $blsManagers->getBl($ligne->getId_bl(), false);
	if (!$bl instanceof Bl) { exit('ERR INSTOBJ BL #'.$ligne->getId_bl()); }

	// Ici il faut savoir si cette ligne en cache d'autres (comme les trains) a cause du regroupement de lignes actif.
	$lignesRegroupees = $blsManagers->getBlLignesRegroupeesFromLigne($ligne);

	// SI on a d'autres lignes concernées, on les flag à supprimé pour ne pas les impacter, car on change pour le total
	if (!empty($lignesRegroupees) && $bl->getRegroupement() == 1) {

		// On répartie sur toutes les lignes le nouveau total pour pouvoir dé-regrouper sans tout perdre
		$qte = $qte / (count($lignesRegroupees) + 1);
		if ($qte < 1) { $qte = 1; }

		foreach ($lignesRegroupees as $lr) {

			$lr->setQte($qte);

			if (!$blsManagers->saveBlLigne($lr)) { exit('-6'); }
			$log = new Log([]);
			$log->setLog_type('info');
			$log->setLog_texte("Modif auto ligne BL #".$lr->getId()." suite modif manuelle qté totale sur la ligne #".$id_ligne_bl." (regroupement)");
			$logManager->saveLog($log);

		} // FIN boucle lignes regroupées

	} // FIN test lignes regroupées

	$ligne->setQte($qte);

	$res = $blsManagers->saveBlLigne($ligne) ? 1 : 0;
	echo $res ? 1 : 0;
	$log = new Log([]);
	$logTxt = $res
		? 'Changement de la quantité sur la ligne de BL #'.$id_ligne_bl
		: 'Echec lors du changement de la quantité sur la ligne de BL #'.$id_ligne_bl;
	$logType = $res ? 'info' : 'danger';
	$log->setLog_texte($logTxt);
	$log->setLog_type($logType);
	$logManager->saveLog($log);
	exit;

} // FIN mode

// Changement du poids sur la ligne d'un BL
function modeChangePoidsLigne() {

	global $blsManagers, $logManager, $cnx;

	$id_ligne_bl = isset($_REQUEST['id_ligne_bl']) ? intval($_REQUEST['id_ligne_bl']) : 0;
	$poids = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : 0;
	if ($id_ligne_bl == 0) { exit; }

	$ligne = $blsManagers->getBlLigne($id_ligne_bl);
	if (!$ligne instanceof BlLigne) { exit; }

	$bl = $blsManagers->getBl($ligne->getId_bl(), false);
	if (!$bl instanceof Bl) { exit('ERR INSTOBJ BL #'.$ligne->getId_bl()); }

	// Ici il faut savoir si cette ligne en cache d'autres a cause du regroupement de lignes actif.
	$lignesRegroupees = $blsManagers->getBlLignesRegroupeesFromLigne($ligne);

	// SI on a d'autres lignes concernées, on les flag à supprimé pour ne pas les impacter, car on change pour le total
	if (!empty($lignesRegroupees) && $bl->getRegroupement() == 1) {

		// On répartie sur toutes les lignes le nouveau total pour pouvoir dé-regrouper sans tout perdre
		$poids = $poids / (count($lignesRegroupees) + 1);

		foreach ($lignesRegroupees as $lr) {

			//$lr->setSupprime(1);
            $lr->setPoids($poids);
			if (!$blsManagers->saveBlLigne($lr)) { exit('-6'); }
			$log = new Log([]);
			$log->setLog_type('info');
			$log->setLog_texte("Modif auto ligne BL #".$lr->getId()." suite modif manuelle poids total sur la ligne #".$id_ligne_bl." (regroupement)");
			$logManager->saveLog($log);

		} // FIN boucle lignes regroupées

	} // FIN test lignes regroupées

	$ligne->setPoids($poids);

	$res = $blsManagers->saveBlLigne($ligne) ? 1 : 0;

    // On change aussi le poids de la compo
    if ($res) {
        $palettesManager = new PalettesManager($cnx);
        $compo = $palettesManager->getComposition($ligne->getId_compo());
        if ($compo instanceof PaletteComposition) {
			$compo->setPoids($poids);
			$palettesManager->savePaletteComposition($compo);
			$log = new Log([]);
			$log->setLog_type('info');
			$log->setLog_texte("Modif du poids de la compo #".$compo->getId()." suite modif manuelle poids sur la ligne #".$id_ligne_bl." du BL #".$bl->getId());
			$logManager->saveLog($log);
        }
    }
	echo $res ? 1 : 0;
	$log = new Log([]);
	$logTxt = $res
		? 'Changement du poids sur la ligne de BL #'.$id_ligne_bl
		: 'Echec lors du changement du poids sur la ligne de BL #'.$id_ligne_bl;
	$logType = $res ? 'info' : 'danger';
	$log->setLog_texte($logTxt);
	$log->setLog_type($logType);
	$logManager->saveLog($log);
	exit;

} // FIN mode


// Changement du prix unitaire sur la ligne d'un BL
function modeChangePuLigne() {

	global $blsManagers, $logManager;

	$id_ligne_bl = isset($_REQUEST['id_ligne_bl']) ? intval($_REQUEST['id_ligne_bl']) : 0;
	$pu = isset($_REQUEST['pu']) ? floatval($_REQUEST['pu']) : 0;
	if ($id_ligne_bl == 0) { exit; }

	$ligne = $blsManagers->getBlLigne($id_ligne_bl);
	if (!$ligne instanceof BlLigne) { exit; }

	$ligne->setPu_ht($pu);

	$res = $blsManagers->saveBlLigne($ligne) ? 1 : 0;
	echo $res ? 1 : 0;

	// Il faut aussi changer le prix pour toutes les autres lignes ayant le meme id_pdt, id_lot et id_palette si le BL est regroupé
	$autresLignes = $blsManagers->getBlLignesRegroupeesFromLigne($ligne);

    if (!empty($autresLignes)) {
        foreach ($autresLignes as $ligneBl) {
            if ($ligneBl instanceof BlLigne) {
                $ligneBl->setPu_ht($pu);
				$res2 = $blsManagers->saveBlLigne($ligneBl);
				$log = new Log([]);
				$logTxt = $res2
					? 'Changement du prix unitaire sur ligne BL #'.$ligneBl->getId(). ' (auto par regroupement depuis modif ligne #'.$id_ligne_bl.')'
					: 'Echec lors du changement du prix unitaire auto regroupement sur la ligne de BL #'.$ligneBl->getId(). ' depuis modif sur ligne #'.$id_ligne_bl;
				$logType = $res ? 'info' : 'danger';
				$log->setLog_texte($logTxt);
				$log->setLog_type($logType);
				$logManager->saveLog($log);
            }
        }
    }

	$log = new Log([]);
	$logTxt = $res
		? 'Changement du prix unitaire sur la ligne de BL #'.$id_ligne_bl
		: 'Echec lors du changement du prix unitaire sur la ligne de BL #'.$id_ligne_bl;
	$logType = $res ? 'info' : 'danger';
	$log->setLog_texte($logTxt);
	$log->setLog_type($logType);
	$logManager->saveLog($log);
	exit;

} // FIN mode

// Mise en attente d'un BL
function modeMiseEnAttenteBl() {

	global $blsManagers, $logManager, $cnx;
	$produitManager = new ProduitManager($cnx);

	$id 				= isset($_REQUEST['id']) 			? intval($_REQUEST['id']) 			: 0;
	$num_cmd 			= isset($_REQUEST['num_cmd']) 		? trim($_REQUEST['num_cmd']) 		: '';
	$date_livraison = isset($_REQUEST['date_livraison']) ? Outils::dateFrToSql(trim($_REQUEST['date_livraison']))        : '';
	$date_bl 			= isset($_REQUEST['date_bl']) 		? $_REQUEST['date_bl'] 				: '';
	$id_client_facture 	= isset($_REQUEST['id_t_fact']) 	? intval($_REQUEST['id_t_fact']) 	: 0;
	$id_client_livre 	= isset($_REQUEST['id_t_livr']) 	? intval($_REQUEST['id_t_livr']) 	: 0;
	$id_adresse 		= isset($_REQUEST['id_adresse']) 	? intval($_REQUEST['id_adresse']) 	: 0;
	$id_transporteur 	= isset($_REQUEST['id_transp']) 	? intval($_REQUEST['id_transp']) 	: 0;
	$id_langue 			= isset($_REQUEST['id_langue']) 	? intval($_REQUEST['id_langue']) 	: 0;
	$nom_client 		= isset($_REQUEST['nom_client']) 	? trim($_REQUEST['nom_client']) 	: '';
	$id_pdt_negoce = isset($_REQUEST['id_pdt_negoce']) 			? intval($_REQUEST['id_pdt_negoce']) 			: 0;
	//$chiffrage 			= isset($_REQUEST['chiffrage']) 	? intval($_REQUEST['chiffrage']) 	: 0;

	if ($id == 0) { exit('-1'); }
	$bl = $blsManagers->getBl($id);
	if (!$bl instanceof Bl) { exit('-2'); }

	// Si le BL est en cours d'édition et qu'il est au statut en cours, on le met en attente pour ne pas perdre de données.
	$init_attente = isset($_REQUEST['init_attente']) ? intval($_REQUEST['init_attente']) : 0;
    if ($init_attente && (int)$bl->getStatut() > 1) { exit; }

	// On s'assure que la dates est correctement formatée
	if ($date_bl == '') { $date_bl = date('d/m/Y'); }
	$date_bl = Outils::dateFrToSql($date_bl);
	if (!Outils::verifDateSql($date_bl)) { $date_bl = date('Y-m-d'); }

	$bl->setNum_cmd($num_cmd);
	$bl->setId_tiers_facturation($id_client_facture);
	$bl->setId_tiers_livraison($id_client_livre);
	$bl->setId_tiers_transporteur($id_transporteur);
	$bl->setId_adresse_livraison($id_adresse);
	$bl->setId_langue($id_langue);
	$bl->setNom_client($nom_client);
	//$bl->setChiffrage($chiffrage);
	$bl->setStatut(1);
	$bl->setSupprime(0);
    if ($date_livraison != '') {
		$bl->setDate_livraison($date_livraison);
    }
	$res = $blsManagers->saveBl($bl);


	// On rend persistant les données des lignes du BL en dur (on réinstancie ici les lignes sans regroupement)
	foreach ($blsManagers->getListeBlLignes(['id_bl' => $bl->getId(), 'regroupement' => 0]) as $ligne) {

	    $tva = round($produitManager->getTvaProduit($ligne->getId_produit()),2);
        if ($tva < 0) { $tva = 0; }

        $pdt = $produitManager->getProduit($ligne->getId_produit(), false);
        if ((int)$pdt->getVendu_piece() == 0) { $ligne->setQte(1); }

		$ligne->setId_palette($ligne->getId_palette());
		$ligne->setId_produit($ligne->getId_produit());
		$ligne->setId_produit_bl($ligne->getId_produit_bl());
		$ligne->setId_lot($ligne->getId_lot());
//		$ligne->setId_pdt_negoce($ligne->getId_pdt_negoce());
		$ligne->setNumlot($ligne->getNumlot());
		$ligne->setPoids($ligne->getPoids());
		$ligne->setNb_colis($ligne->getNb_colis());
		$ligne->setPu_ht($ligne->getPu_ht());
		$ligne->setQte($ligne->getQte() > 0 ? $ligne->getQte() : 1);
		$ligne->setTva($tva);
		$ligne->setSupprime(0);

		$blsManagers->saveBlLigne($ligne);

	} // FIN boucle sur les lignes

	echo $res ? 1 : 0;
	$log = new Log([]);
	$logTxtAtt = $init_attente ? 'automatique ' : '';
	$logTxt = $res
		? 'Mise en attente '.$logTxtAtt.'du BL #'.$id
		: 'Echec lors de la mise en attente '.$logTxtAtt.'du BL #'.$id;
	$logType = $res ? 'info' : 'danger';
	$log->setLog_texte($logTxt);
	$log->setLog_type($logType);
	$logManager->saveLog($log);
	exit;


} // FIN mode

// Retourne l'URL de téléchargement direct d'un BL en PDF
function modeGetUrlBlPdf() {

	global $blsManagers;
	$id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) : 0;
	if ($id_bl == 0) { exit('-1'); }

	$bl = $blsManagers->getBl($id_bl);
	if (!$bl instanceof Bl) { exit('-2'); }

	$dir = $blsManagers->getDossierBlPdf($bl);

	$fichier = $bl->getFichier();
	echo __CBO_ROOT_URL__.$dir.$fichier;

} // FIN mode

// Enregistre les modifs d'un Bl
function modeSaveBl() {

	global $blsManagers, $cnx;
	$id_bl = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id_bl == 0) { exit('-1'); }

	$bl = $blsManagers->getBl($id_bl);
	if (!$bl instanceof Bl) { exit('-2'); }

	// Récupération des variables
	$date_bl 		= isset($_REQUEST['date_bl']) 		? trim($_REQUEST['date_bl']) 				: date('d/m/Y');
	$regroupement 	= isset($_REQUEST['regroupement']) 	? intval($_REQUEST['regroupement']) 		: 0;
	$date_livraison = isset($_REQUEST['date_livraison']) ? Outils::dateFrToSql(trim($_REQUEST['date_livraison']))        : '';
	$num_cmd 		= isset($_REQUEST['num_cmd']) 		? trim(mb_strtolower($_REQUEST['num_cmd'])) : '';
	$id_t_fact  	= isset($_REQUEST['id_t_fact']) 	? intval($_REQUEST['id_t_fact']) 			: 0;
	$id_t_livr  	= isset($_REQUEST['id_t_livr']) 	? intval($_REQUEST['id_t_livr']) 			: 0;
	$id_adresse  	= isset($_REQUEST['id_adresse']) 	? intval($_REQUEST['id_adresse']) 			: 0;
	$id_transp  	= isset($_REQUEST['id_transp']) 	? intval($_REQUEST['id_transp']) 			: 0;
	$statut         = isset($_REQUEST['statut'])        ? intval($_REQUEST['statut'])               : -1;

	$tiersManager = new TiersManager($cnx);
	$chiffrage = 0;
	$client = $id_t_livr > 0 ? $tiersManager->getTiers($id_t_livr) : $tiersManager->getTiers($id_t_fact);
	if ($client instanceof Tiers) {
		$chiffrage = (int)$client->getBl_chiffre();
    }

	$bl->setSupprime(0); // Pour terminer l'enreistrement d'un BL créé depuis le BO
	$bl->setNum_cmd($num_cmd);
	$bl->setDate(Outils::dateFrToSql($date_bl));
	$bl->setId_tiers_facturation($id_t_fact);
	$bl->setId_tiers_livraison($id_t_livr);
	$bl->setId_adresse_livraison($id_adresse);
	$bl->setId_tiers_transporteur($id_transp);
	$bl->setRegroupement($regroupement);
    if ($date_livraison != '') {
		$bl->setDate_livraison($date_livraison);
    }
	$bl->setChiffrage($chiffrage);
	if ($statut > -1) { $bl->setStatut($statut); }
	echo $blsManagers->saveBl($bl) ? 1 : 0;
	exit;

} // FIN mode

// Modale de sélection de l'adresse pour envoi du BL à un client par mail
function modeModalEnvoiMail() {

	global $cnx, $tarifsManager;

	$tiersManager = new TiersManager($cnx);

	$id_client = isset($_REQUEST['id_client']) ? intval($_REQUEST['id_client']) : 0;
	if ($id_client == 0) { exit; }

	$client = $tiersManager->getTiers($id_client);
	if (!$client instanceof Tiers) { exit; }

	$contactsManager = new ContactManager($cnx);
	$contacts = $contactsManager->getListeContacts(['id_tiers' => $id_client]);
	$emails = [];
	foreach ($contacts as $ctc) {

		if (!Outils::verifMail($ctc->getEmail())) { continue; }

		$tmp = [];
		$tmp['nom'] = $ctc->getNom_complet();
		$tmp['id'] = $ctc->getId();

		$emails[$ctc->getEmail()] = $tmp;
	}

	// Si aucune adresse e-mail
	if (empty($emails)) { ?>
        <div class="alert alert-danger">
            ENVOI IMPOSSIBLE !<p>Aucune adresse e-mail valide renseignée pour ce client...</p>
        </div>
		<?php exit; }
	?>

    <input type="hidden" class="idclt" value="<?php echo $id_client; ?>"/>
    <div class="row mb-0">
        <div class="col-12 texte-fin text-13">
            Sélectionnez le contact destinataire :
        </div>
        <div class="col-12">
            <select class="selectpicker form-control">
				<?php
				foreach ($emails as $mail => $donnees) {

					$nom = isset($donnees['nom']) ? trim($donnees['nom']) : '';
					$id = isset($donnees['id']) ? intval($donnees['id']) : 0;
					if ($id == 0) { continue; }
					?>
                    <option value="<?php echo $id; ?>" data-subtext="<?php echo $nom; ?>"><?php echo $mail; ?></option>
				<?php }
				?>
            </select>
        </div>
        <div class="col-12 mt-2 texte-fin text-13 text-center">
            Recevoir une copie

            <input type="checkbox" class="togglemaster"
                   data-toggle              = "toggle"
                   data-on                  = "Oui"
                   data-off                 = "Non"
                   data-onstyle             = "info"
                   data-offstyle            = "secondary"
                   data-height                = "20"
                   checked
            />
        </div>
        <div class="col-12">
            <div class="alert alert-info mt-3 texte-fin text-12">
                <i class="fa fa-info-circle mr-1 text-infof"></i>
                La ou les adresses en copies sont modifiables dans les paramètres de la Gescom.
            </div>
        </div>
    </div>

	<?php

} // FIN mode

function modeModalEnvoiBlMail() {
	global $cnx, $blsManagers;

	$tiersManager = new TiersManager($cnx);

	$id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) : 0;
	if ($id_bl == 0) { exit; }

	$bl = $blsManagers->getBl($id_bl);
	if (!$bl instanceof Bl) { exit; }


	$tiers = [$bl->getId_tiers_facturation(), $bl->getId_tiers_livraison()];

	$contactsManager = new ContactManager($cnx);
	$contacts = $contactsManager->getListeContacts(['id_tiers' => implode(',', $tiers)]);
	$emails = [];
	foreach ($contacts as $ctc) {

		if (!Outils::verifMail($ctc->getEmail())) { continue; }

		$tmp = [];
		$tmp['nom'] = $ctc->getNom_complet();
		$tmp['id'] = $ctc->getId();

		$emails[$ctc->getEmail()] = $tmp;
	}

	// Si aucune adresse e-mail
	if (empty($emails)) { ?>
        <input type="hidden" id="idBlFromModaleMail" value="<?php echo $bl->getId(); ?>"/>
        <div class="alert alert-danger">
            ENVOI IMPOSSIBLE !<p>Aucune adresse e-mail valide renseignée pour le client du BL...</p>
        </div>
		<?php exit; }
	?>

    <div class="row mb-0">
        <input type="hidden" id="idBlFromModaleMail" value="<?php echo $bl->getId(); ?>"/>
        <div class="col-12 texte-fin text-13">
            Sélectionnez les destinataires :
        </div>
        <div class="col-12">
            <select class="selectpicker form-control" multiple>
				<?php
				foreach ($emails as $mail => $donnees) {

					$nom = isset($donnees['nom']) ? trim($donnees['nom']) : '';
					$id = isset($donnees['id']) ? intval($donnees['id']) : 0;
					if ($id == 0) { continue; }
					?>
                    <option value="<?php echo $id; ?>" data-subtext="<?php echo $mail; ?>"><?php echo $nom; ?></option>
				<?php }
				?>
            </select>
        </div>
        <div class="col-12 texte-fin text-13 mt-2">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Autre</span>
                </div>
                <input type="text" id="emailcustom" placeholder="exemple@domaine.com" value="" class="form-control"/>
            </div>
        </div>

		<?php
		if ($bl->getDate_envoi() != '') { ?>
            <div class="col-6">
                <div class="alert alert-info mt-3 texte-fin text-12">
                    <i class="fa fa-info-circle mr-1 text-infof"></i>
                    BL déjà envoyé le <?php echo Outils::dateSqlToFr($bl->getDate_envoi()); ?>
                </div>
            </div>
		<?php }
		?>
        <div class="col-<?php echo $bl->getDate_envoi() != '' ? '6' : '12';?> text-right">
            <div class="mt-3 texte-fin text-13 ">
                Recevoir une copie

                <input type="checkbox" class="togglemaster"
                       data-toggle              = "toggle"
                       data-on                  = "Oui"
                       data-off                 = "Non"
                       data-onstyle             = "info"
                       data-offstyle            = "secondary"
                       data-height                = "20"
                       checked
                />
            </div>
        </div>
    </div>

	<?php
}

// Envoi le Bl au client par e-mail
function modeEnvoiPdfBlClient() {

	global $cnx, $blsManagers, $conf_email; // $conf_email = from

	$tiersManager = new TiersManager($cnx);

	$id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) : 0;
	if ($id_bl == 0) { exit; }

	$bl = $blsManagers->getBl($id_bl);
	if (!$bl instanceof Bl) { exit; }

	$client_id = $bl->getId_tiers_livraison() > 0 ? $bl->getId_tiers_livraison() : $bl->getId_tiers_facturation();
	if ($client_id == 0) { exit; }
	$client = $tiersManager->getTiers($client_id);
	if (!$client instanceof Tiers) { exit; }

	$ids_ctcs = isset($_REQUEST['id_ctc']) ? explode(',',$_REQUEST['id_ctc']) : [];

	$autre_mail = isset($_REQUEST['mail']) ? trim(strtolower($_REQUEST['mail'])) : '';
	if ($autre_mail != '' && !Outils::verifMail($autre_mail)) { $autre_mail = ''; }

	if ($autre_mail == '' && empty($ids_ctcs)) { exit; }

	$dest_cc = [];
	$dest = [];

	$contactsManager = new ContactManager($cnx);

	foreach ($ids_ctcs as $id_ctc) {
		$ctc = $contactsManager->getContact($id_ctc);
		if (!$ctc instanceof Contact) { continue; }
		if (!Outils::verifMail($ctc->getEmail())) { continue; }
		$dest[] = $ctc->getEmail();
	}

	if ($autre_mail != '') {
		$dest[] = $autre_mail;
	}

	if (empty($dest)) { exit; }


	$cc = isset($_REQUEST['cc']) ? intval($_REQUEST['cc']) : 0;
	if ($cc == 1) {
		$configManager = new ConfigManager($cnx);
		$cc_mails = $configManager->getConfig('cc_mails');
		$mails_cc = explode(';', $cc_mails->getValeur());
		$dest_cc = [];
		foreach ($mails_cc as $mcc) {
			$mcc = trim(strtolower($mcc));
			if (!Outils::verifMail($mcc)) { continue; }
			$dest_cc[] = $mcc;
		}
	}

	$configManager = new ConfigManager($cnx);
	$pdf_top_bl = $configManager->getConfig('pdf_top_bl');
	$margeEnTetePdt = $pdf_top_bl instanceof Config ?  (int)$pdf_top_bl->getValeur() : 0;

	// On génère le PDF
	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content_fichier = genereContenuPdf($bl, 1, false, false);
	$html_additionnel = getDebutTableauBl($bl);
	$content_header = genereHeaderPdf($bl, $html_additionnel);
	$contentPdf = '<page backtop="'.(int)$margeEnTetePdt.'mm">';
	$contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style>'.$content_header.'</page_header>';
	$contentPdf.= $content_fichier;
	$contentPdf.= '</page>'. ob_get_clean();


	$nom_fichier = time().'.pdf';

	$chemin = __CBO_ROOT_PATH__.'/temp/'.$nom_fichier;
	if (file_exists($chemin)) { unlink($chemin); }

	try {
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'UTF-8', [15, 15, 15, 15]);
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($contentPdf));;
		$savefilepath = __CBO_ROOT_PATH__.'/temp/'.$nom_fichier;
		$html2pdf->Output($savefilepath, 'F');
	}
	catch(HTML2PDF_exception $e) {
		vd($e);
		exit;
	}




	$traductionsManager = new TraductionsManager($cnx);
	$titre = 'PROFIL EXPORT - '. $traductionsManager->getTrad('bl', $client->getId_langue());
	$contenu = Outils::formatContenuMailClient(nl2br($traductionsManager->getTrad('mail_bl_clt', $client->getId_langue())));
	if (!Outils::envoiMail($dest, $conf_email, $titre, utf8_decode($contenu), 0, $dest_cc, [$chemin])) {
        exit;
    } else {
        $bl->setDate_envoi(date('Y-m-d H:i:s'));
        $blsManagers->saveBl($bl);
    }
    echo '1';
	exit;

} // FIN mode

// Envoi le Bl au client par e-mail
function modeEnvoiPdfClient() {

	global $cnx, $blsManagers, $conf_email; // $conf_email = from

	$tiersManager = new TiersManager($cnx);

	$id_client = isset($_REQUEST['id_client']) ? intval($_REQUEST['id_client']) : 0;
	if ($id_client == 0) { exit; }

	$client = $tiersManager->getTiers($id_client);
	if (!$client instanceof Tiers) { exit; }

	$id_ctc = isset($_REQUEST['id_ctc']) ? intval($_REQUEST['id_ctc']) : 0;
	$contactsManager = new ContactManager($cnx);
	$ctc = $contactsManager->getContact($id_ctc);
	if (!$ctc instanceof Contact) { exit; }
	if (!Outils::verifMail($ctc->getEmail())) { exit; }
	$dest_cc = [];
	$dest = [$ctc->getEmail()];

	$cc = isset($_REQUEST['cc']) ? intval($_REQUEST['cc']) : 0;
	if ($cc == 1) {
		$configManager = new ConfigManager($cnx);
		$cc_mails = $configManager->getConfig('cc_mails');
		$mails_cc = explode(';', $cc_mails->getValeur());
		$dest_cc = [];
		foreach ($mails_cc as $mcc) {
			$mcc = trim(strtolower($mcc));
			if (!Outils::verifMail($mcc)) { continue; }
			$dest_cc[] = $mcc;
		}
	}

	// On génère le PDF
	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content = genereContenuPdfClient($id_client);
	$content .= ob_get_clean();

	$nom_fichier = time().'.pdf';

	$chemin = __CBO_ROOT_PATH__.'/temp/'.$nom_fichier;
	if (file_exists($chemin)) { unlink($chemin); }

	try {
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'UTF-8', [15, 15, 15, 15]);
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($content));;
		$savefilepath = __CBO_ROOT_PATH__.'/temp/'.$nom_fichier;
		$html2pdf->Output($savefilepath, 'F');
	}
	catch(HTML2PDF_exception $e) {
		vd($e);
		exit;
	}

	$fichier = __CBO_TEMP_URL__.$nom_fichier;

	$traductionsManager = new TraductionsManager($cnx);
	$titre = 'PROFIL EXPORT - '. $traductionsManager->getTrad('bl', $client->getId_langue());
	$contenu = Outils::formatContenuMailClient(nl2br($traductionsManager->getTrad('mail_bl_clt', $client->getId_langue())));
	echo Outils::envoiMail($dest, $conf_email, $titre, utf8_decode($contenu), 0, $dest_cc, [$chemin]) ? 1 : 0;
	exit;

} // FIN mode


/* ------------------------------------------------
MODE - Genère le PDF du BL et l'envoi au client
-------------------------------------------------*/
function modeEnvoiBl() {

	global $cnx, $blsManagers, $conf_email; // $conf_email = from

	// Récupération de l'ID et instanciation de l'objet
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id == 0) { exit('-1'); }

	$bl = $blsManagers->getBl($id);
	if (!$bl instanceof Bl) { exit('-2'); }

	// Récupération des variables
	$date_bl 		= isset($_REQUEST['date_bl']) 		? trim($_REQUEST['date_bl']) 				: date('d/m/Y');
	$num_cmd 		= isset($_REQUEST['num_cmd']) 		? trim(mb_strtolower($_REQUEST['num_cmd'])) : '';
	$id_t_fact  	= isset($_REQUEST['id_t_fact']) 	? intval($_REQUEST['id_t_fact']) 			: 0;
	$id_t_livr  	= isset($_REQUEST['id_t_livr']) 	? intval($_REQUEST['id_t_livr']) 			: 0;
	$id_adresse  	= isset($_REQUEST['id_adresse']) 	? intval($_REQUEST['id_adresse']) 			: 0;
	$id_transp  	= isset($_REQUEST['id_transp']) 	? intval($_REQUEST['id_transp']) 			: 0;
	$date_livraison = isset($_REQUEST['date_livraison']) ? Outils::dateFrToSql(trim($_REQUEST['date_livraison']))        : '';
	// Mise à jour de l'objet
	$bl->setNum_cmd($num_cmd);
	$bl->setDate(Outils::dateFrToSql($date_bl));
	$bl->setId_tiers_facturation($id_t_fact);
	$bl->setId_tiers_livraison($id_t_livr);
	$bl->setId_adresse_livraison($id_adresse);
	$bl->setId_tiers_transporteur($id_transp);
    if ($date_livraison != '') {
		$bl->setDate_livraison($date_livraison);
    }
	$blsManagers->saveBl($bl);

	$produitManager = new ProduitManager($cnx);

	// On rend persistant les données des lignes du BL en dur
	foreach ($blsManagers->getListeBlLignes(['id_bl' => $bl->getId(), 'regroupement' => 0]) as $ligne) {

		$pdt = $produitManager->getProduit($ligne->getId_produit(), false);
		if ((int)$pdt->getVendu_piece() == 0) { $ligne->setQte(1); }


		$tva = round((float)$ligne->getTva() / 100 ,2);
		if ($tva < 0) { $tva = 0; }

		$ligne->setId_palette($ligne->getId_palette());
		$ligne->setId_produit($ligne->getId_produit());
		$ligne->setId_produit_bl($ligne->getId_produit_bl());
		$ligne->setId_lot($ligne->getId_lot());
		$ligne->setNumlot($ligne->getNumlot());
		$ligne->setPoids($ligne->getPoids());
		$ligne->setNb_colis($ligne->getNb_colis());
		$ligne->setPu_ht($ligne->getPu_ht());
		$ligne->setQte($ligne->getQte() > 0 ? $ligne->getQte() : 1);
		$ligne->setTva($tva);

		$blsManagers->saveBlLigne($ligne);

	} // FIN boucle sur les lignes

	// Choix de langue et chiffrage pour PDF
	$id_langue  	= isset($_REQUEST['id_langue']) 	? intval($_REQUEST['id_langue']) 			: 1;
	$bl->setId_langue($id_langue);


	$configManager = new ConfigManager($cnx);
	$pdf_top_bl = $configManager->getConfig('pdf_top_bl');
	$margeEnTetePdt = $pdf_top_bl instanceof Config ?  (int)$pdf_top_bl->getValeur() : 0;

	// Génère le PDF
	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content_fichier = genereContenuPdf($bl, 1, false, false);
	$html_additionnel = getDebutTableauBl($bl);
	$content_header = genereHeaderPdf($bl, $html_additionnel);
	$contentPdf = '<page backtop="'.(int)$margeEnTetePdt.'mm">';
	$contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style>'.$content_header.'</page_header>';
	$contentPdf.= $content_fichier;
	$contentPdf.= '</page>'. ob_get_clean();



	try {
		$marges = [7, 12, 7, 12];
		$nom_fichier = $bl->getFichier();
		$dir = $blsManagers->getDossierBlPdf($bl);
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'ISO-8859-15', $marges);
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($contentPdf));;
		$savefilepath = __CBO_ROOT_PATH__ . $dir . $nom_fichier;
		$html2pdf->Output($savefilepath, 'F');
	} catch (HTML2PDF_exception $e) {
		vd($e);
		exit;
	}

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("Génération et envoi du BL au client depuis STK du BL #".$bl->getId());
	$logsManager = new LogManager($cnx);
	$logsManager->saveLog($log);

	// On supprime les compos (flag) -> Le BL est généré, elles ne sont plus en stock
	$blsManagers->supprComposBl($bl);

	// OK, on met à jour le statut
	$bl->setStatut(2); // 2 = Généré
	$blsManagers->saveBl($bl);
	$dir = $blsManagers->getDossierBlPdf($bl);
	$fichier =  __CBO_ROOT_PATH__.$dir.$bl->getFichier();

	$id_ctc = isset($_REQUEST['id_ctc']) ? intval($_REQUEST['id_ctc']) : 0;
	$contactsManager = new ContactManager($cnx);
	$ctc = $contactsManager->getContact($id_ctc);
	if (!$ctc instanceof Contact) { exit; }
	if (!Outils::verifMail($ctc->getEmail())) { exit; }
	$dest_cc = [];
	$dest = [$ctc->getEmail()];

	$cc = isset($_REQUEST['cc']) ? intval($_REQUEST['cc']) : 0;
	if ($cc == 1) {
		$configManager = new ConfigManager($cnx);
		$cc_mails = $configManager->getConfig('cc_mails');
		$mails_cc = explode(';', $cc_mails->getValeur());
		$dest_cc = [];
		foreach ($mails_cc as $mcc) {
			$mcc = trim(strtolower($mcc));
			if (!Outils::verifMail($mcc)) { continue; }
			$dest_cc[] = $mcc;
		}
	}

	$traductionsManager = new TraductionsManager($cnx);
	$titre = 'PROFIL EXPORT - '. $traductionsManager->getTrad('bl', $id_langue);
	$contenu = Outils::formatContenuMailClient(nl2br($traductionsManager->getTrad('mail_bl_clt', $id_langue)));
	if (!Outils::envoiMail($dest, $conf_email, $titre, utf8_decode($contenu), 0, $dest_cc, [$fichier])) {
        echo '0'; exit;
    }
    echo '1';

    $bl->setDate_envoi(date('Y-m-d H:i:s'));

    if ($blsManagers->saveBl($bl)) {
		$log = new Log();
        $log->setLog_type('info');
        $log->setLog_texte("Envoi par mail du BL #".$bl->getId());
        $logsManager->saveLog($log);
    }

	exit;

} // FIN mode


// Retourne la liste des BL générés (Admin)
function modeGetListeBls() {

    global $blsManagers, $mode, $cnx, $utilisateur;

	$tiersManager = new TiersManager($cnx);
	
	$packingListManager = new PackingListManager($cnx);

	/* Si création d'un BL sans ligne on va avoir un problème à l'édition qui contrôle qu'il y a bien au moins un produit,
    donc à chaque chargement de la liste (soit retourà la liste sans rien mettre, soit consultation de la liste) il ne faut pas avoir de BL sans lignes.
	On néttoie donc ici les Bl sans lignes en les passant à supprime = 1.
	On ne le fait pas a la méthode SaveBl qui est aussi utilisée dynamiquement pour changer le regroupement, donc là il faut bien le garder
	*/
    $blsManagers->clearBlSansLignes();

	$nbResultPpage_defaut = 25;

	$page              = isset($_REQUEST['page'])             ? intval($_REQUEST['page'])               : 1;
	$id                = isset($_REQUEST['id'])               ? intval($_REQUEST['id'])                 : 0;
	$bt                = isset($_REQUEST['bt'])               ? intval($_REQUEST['bt'])                 : 0;
	$id_clients        = isset($_REQUEST['id_client'])        ? $_REQUEST['id_client']                  : '';
	$numbl             = isset($_REQUEST['numbl'])            ? trim(strtoupper($_REQUEST['numbl']))    : '';
	$numcmd            = isset($_REQUEST['numcmd'])           ? trim(strtoupper($_REQUEST['numcmd']))   : '';
	$chiffre           = isset($_REQUEST['chiffre'])          ? intval($_REQUEST['chiffre'])            : -1;
	$facture           = isset($_REQUEST['facture'])          ? intval($_REQUEST['facture'])            : -1;
	$statut            = isset($_REQUEST['statut'])           ? intval($_REQUEST['statut'])             : -1;
	$nbResultPpage     = isset($_REQUEST['nb_result_p_page']) ? intval($_REQUEST['nb_result_p_page'])   : 25;
	$date_du           = isset($_REQUEST['date_du'])          ? trim($_REQUEST['date_du'])              : '';
	$date_au           = isset($_REQUEST['date_au'])          ? trim($_REQUEST['date_au'])              : '';

	if ($nbResultPpage == 0) { $nbResultPpage = $nbResultPpage_defaut; }

	if (is_array($id_clients)) {
		$id_clients = implode(',', $id_clients);
	}

	if (trim($numbl) != '') {
		$date_du = '';
		$date_au = '';
		$id = 0;
		$bt = 0;
		$id_clients = '';
		$numcmd = '';
		$chiffre = -1;
		$statut = -1;
    }

	// Préparation pagination (Ajax)
	$filtresPagination  = '?mode='.$mode.'&id='.$id.'&bt='.$bt.'&id_client='.$id_clients.'&numbl='.$numbl;
	$filtresPagination .= '&chiffre='.$chiffre.'&facture='.$facture.'&statut='.$statut.'&date_du='.$date_du.'&date_au='.$date_au;
	$start              = ($page-1) * $nbResultPpage;


	$params = [
        'id'                => $id,
        'id_clients'        => explode(',',$id_clients),
        'num_bl'            => $numbl,
        'num_cmd'           => $numcmd,
        'chiffre'           => $chiffre,
        'facture'           => $facture,
        'statut'            => $statut,
        'du'                => Outils::dateFrToSql($date_du),
        'au'                => Outils::dateFrToSql($date_au),
        'bt'                => $bt,
		'lignes'            => false,
		'factures'          => true,
		'palettes'          => true,
		'produits'          => true,
		'colis'             => true,
		'poids'             => true,
		'total'             => true,
        'statut_not'        => '0',
		'start'             => $start,
		'nb_result_page'    => $nbResultPpage,
	];

	if ($statut == 0) {
		unset($params['statut']);
		$params['show_supprimes'] = true;
		$params['supprime'] = 1;
    } else {
		$params['show_supprimes'] = false;
		$params['supprime'] = 0;
    }




	$liste = $blsManagers->getListeBl($params);		


    $nbResults  = $blsManagers->getNb_results();
    $pagination = new Pagination($page);
	
    $pagination->setUrl($filtresPagination);
    $pagination->setNb_results($nbResults);
    $pagination->setAjax_function(true);
    $pagination->setNb_results_page($nbResultPpage);
    $pagination->setNb_apres(2);
    $pagination->setNb_avant(2);

    if (empty($liste)) { ?>

                <div class="alert alert-warning">
                    Aucun <?php echo $bt == 1 ? 'bon de transfert' : 'BL'; ?> n'a été trouvé...
                </div>

            <?php  } else { ?>
                <table class="table admin table-v-middle">
                    <thead>
                    <tr>
		                <?php if ($utilisateur->isDev()) { ?>
                        <th>ID</th>
                        <?php }  ?>
                        <th>Numéro</th>
                        <th>Date</th>
                        <?php if ($bt == 0) { ?>
                            <th>Commande</th>
                            <th>Client</th>
						<?php } ?>
                        <th class="text-center">Palettes</th>
                        <th class="text-center">Produits</th>
                        <th class="text-center">Colis</th>
                        <th class="text-center">Poids (Kg)</th>
		                <?php if ($bt == 0) { ?>
                            <th class="text-center">Total</th>
                            <th class="text-center">Statut</th>
                        <?php } ?>
                        <th class="t-actions text-center">Supprimer</th>
                        <th class="t-actions text-center">Editer</th>
                        <th class="t-actions text-center">PDF</th>
                        <th class="t-actions text-center">Envoyer</th>
		                <?php if ($bt == 0) { ?>
                            <th class="t-actions text-center">Packing List</th>
                            <th class="t-actions text-center">Facture</th>
                            <th class="text-center w-50px"></th>
						<?php } ?>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					foreach ($liste as $bl) {

                        if ($bl->getNum_bl() == null) {

                            $dateArray = explode('-', $bl->getDate());
                            $annee = isset($dateArray[0]) ? $dateArray[0] : '';
                            $mois = isset($dateArray[1]) ? $dateArray[1] : '';

                            $nbl = $blsManagers->getNextNumeroBl(false, $annee, $mois);
                            $bl->setNum_bl($nbl);
                            $blsManagers->saveBl($bl);

                            $logManager = new LogManager($cnx);
                            $log = new Log();
                            $log->setLog_type('warning');;
                            $log->setLog_texte("Création auto du numéro de BL car NULL sur BL id#".$bl->getId());
                            $logManager->saveLog($log);

						}

                        if ($bt == 1) {
                            if (substr($bl->getNum_bl(),0,2) == 'BL') {
                                $numBt =  str_replace('BL', 'BT',$bl->getNum_bl());
                                $bl->setNum_bl($numBt);
                                $blsManagers->saveBl($bl);
                            }
                        }

						if ($bt == 0) {
							if (intval($bl->getId_tiers_livraison()) == 0) {
								$bl->setId_tiers_livraison($bl->getId_tiers_facturation());
								$blsManagers->saveBl($bl);
							}
							if (intval($bl->getId_tiers_facturation()) == 0) {
								$bl->setId_tiers_facturation($bl->getId_tiers_livraison());
								$blsManagers->saveBl($bl);
							}
							$clt_livr = $tiersManager->getTiers($bl->getId_tiers_livraison());
							$clt_fact = $tiersManager->getTiers($bl->getId_tiers_facturation());

							if (!$clt_livr instanceof Tiers) {
								$clt_livr = new Tiers([]);
							} // DEBUG
							if (!$clt_fact instanceof Tiers) {
								$clt_fact = new Tiers([]);
							} // DEBUG
						}
						?>
                        <tr>
						    <?php if ($utilisateur->isDev()) { ?>
                                <td class="w-50pxpx text-10"><code class="nowrap"><?php echo $bl->getId();?></code></td>
                            <?php } ?>
                            <td><?php echo $bl->getCode(); ?></td>
                            <td><?php echo Outils::dateSqlToFr($bl->getDate()); ?></td>
    						<?php if ($bt == 0) { ?>
                            <td><?php echo $bl->getNum_cmd() != '' ? strtoupper($bl->getNum_cmd()) : '&mdash;'; ?></td>
                                <td><a href="gc-all.php?idclt=<?php echo base64_encode($clt_fact->getId()); ?>"><?php echo $clt_fact->getNom(); ?></a><?php
								if ($bl->getId_tiers_livraison() != $bl->getId_tiers_facturation()) {
									echo '<br>Livraison : '.$clt_livr->getNom();
								} ?>
                            </td>
							<?php } ?>
                            <td class="text-center"><?php echo $bl->getNb_palettes(); ?></td>
                            <td class="text-center"><?php echo $bl->getNb_produits(); ?></td>
                            <td class="text-center"><?php echo $bl->getNb_colis(); ?></td>
                            <td class="text-center nowrap"><?php echo number_format($bl->getPoids(),3, '.', ' '); ?></td>
						    <?php if ($bt == 0) { ?>
                            <td class="text-center nowrap"><?php echo number_format($bl->getTotal(),2,'.', ' '); ?> €</td>
                            <td class="text-center"><?php

                                if ($bl->getSupprime() == 1) { ?>
                                    <span class="badge badge-danger texte-fin text-12">Supprimé</span>
								<?php } else {
									switch ($bl->getStatut()) {
										case 1: ?>
                                            <span class="badge badge-warning texte-fin text-12">En attente</span><?php
											break;
										case 2: ?>
                                            <span class="badge badge-info texte-fin text-12">Généré</span><?php
											break;
										default: ?>
                                            <span class="badge badge-danger texte-fin text-12">Inconnu !</span><?php
									}
								}


                                ?></td>
							<?php } ?>
                            <td class="t-actions text-center">
								<?php if ($bl->getSupprime() == 1) { ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary disabled" disabled><i class="fa fa-fw fa-ban"></i></button>
								<?php } else { ?>
                                    <button type="button" class="btn btn-sm btn-<?php echo !empty($bl->getFactures()) ? 'outline-secondary' : 'danger'; ?> btnSupprBl <?php echo !empty($bl->getFactures()) ? 'disabled' : ''; ?>" <?php echo !empty($bl->getFactures()) ? 'disabled' : ''; ?> data-id="<?php echo $bl->getId(); ?>"><i class="fa fa-fw fa-<?php echo !empty($bl->getFactures()) ? 'ban' : 'trash-alt';?>"></i></button>
								<?php } ?>


                            </td>
                            <td class="t-actions text-center">
								<?php if ($bl->getSupprime() == 1) { ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary disabled" disabled><i class="fa fa-fw fa-ban"></i></button>
								<?php } else { ?>
                                    <a href="bl-addupd.php?bo&i=<?php echo base64_encode($bl->getId());?>" class="btn btn-sm btn-secondary"><i class="fa fa-fw fa-edit"></i></a>
								<?php } ?>
                                <?php
                                // 27/10/2021 : Le client veut pouvoir éditer un BL meme apres la génération d'un facture ! O_o
                                ?>
                            </td>

                            <td class="t-actions text-center">

						<?php if ($bl->getSupprime() == 1) { ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary disabled" disabled><i class="fa fa-fw fa-ban"></i></button>
						<?php } else {   $dir = $blsManagers->getDossierBlPdf($bl);
							$chemin_bl = __CBO_ROOT_PATH__.$dir.$bl->getFichier();

							if (file_exists($chemin_bl)) { ?>
                                <a href="<?php

								echo __CBO_ROOT_URL__.$dir.$bl->getFichier(); ?>" target="_blank" class="btn btn-sm btn-secondary <?php echo $bl->getStatut() < 2 ? 'disabled' : '';?>"><i class="fa fa-fw fa-file-pdf" <?php echo $bl->getStatut() < 2 ? 'disabled' : '';?>></i></a>
							<?php } else { ?>
                                <button type="button" disabled class="btn btn-secondary btn-sm disabled"><i class="fa fa-fw fa-ban"></i></button>
							<?php }
							?>

						<?php } ?>


                                </td>
                            <td class="t-actions text-center">
								<?php if ($bl->getSupprime() == 1) { ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary disabled" disabled><i class="fa fa-fw fa-ban"></i></button>
								<?php } else {   $dir = $blsManagers->getDossierBlPdf($bl);
									$chemin_bl = __CBO_ROOT_PATH__.$dir.$bl->getFichier();

									if (file_exists($chemin_bl)) { ?>
                                        <button type="button" class="btn btn-sm btn-<?php
										echo $bl->getDate_envoi() != '' ? 'success' : 'secondary';
										?> btnEnvoiBlMail" data-id-bl="<?php echo $bl->getId(); ?>" data-id-clt="<?php echo $bl->getId_tiers_facturation(); ?>" ><i class="fa fa-fw fa-paper-plane"></i></button>
									<?php } else { ?>
                                        <button type="button" disabled class="btn btn-secondary btn-sm disabled"><i class="fa fa-fw fa-ban"></i></button>
									<?php }
									?>

								<?php } ?>

                                </td>

						<?php if ($bt == 0) { ?>
                            <td class="t-actions text-center">
								<?php if ($bl->getSupprime() == 1) { ?>
                                <span class="gris-9">&mdash;</span>
								<?php } else {
                                $packingList = $bl->getId_packing_list() > 0;

                                if ($packingList) {
                                $pl = $packingListManager->getPackingList($bl->getId_packing_list());

                                $dir = $packingListManager->getDossierPackingListPdf($pl);
                                if (file_exists(__CBO_ROOT_PATH__.$dir.$pl->getFichier())) { ?>
                                <a href="<?php 	echo __CBO_ROOT_URL__.$dir.$pl->getFichier(); ?>" target="_blank" class="text-info texte-fin text-13"><?php echo $bl->getNum_packing_list();?></a>
							<?php } else {
								?>
                                <span class="gris-9">-</span>
							<?php }
							?>

						<?php } else { ?>
                            <span class="gris-9">&mdash;</span>
						<?php } ?>   </td>
								<?php } ?>





                            <td class="t-actions text-center">
								<?php if ($bl->getSupprime() == 1) { ?>
                                    <span class="gris-9">&mdash;</span>
								<?php } else { ?>
									<?php if (!empty($bl->getFactures())) {

										foreach ($bl->getFactures() as $f) { ?>
                                            <a href="gc-factures.php?i=<?php  echo base64_encode($f->getId()); ?>" class="text-info texte-fin text-13 d-block"><?php   echo $f->getNum_facture(); ?></a>
										<?php }
										?>

									<?php  } else { ?>
                                        <span class="gris-9">&mdash;</span>
									<?php } ?>
								<?php } ?>

                            </td>
                            <td class="text-center">
								<?php if ($bl->getSupprime() == 1) { ?>

								<?php } else { ?>
									<?php
									if ($bl->getStatut() == 2) { ?>
                                        <input type="checkbox" class="icheck checkFacture" data-clt="<?php echo $bl->getId_tiers_facturation(); ?>" data-facture="<?php echo !empty($bl->getFactures()) ? 1 : 0 ; ?>" data-packing="<?php echo $packingList ? 1 : 0 ; ?>" value="<?php echo $bl->getId();?>" />
									<?php } else { ?>

									<?php }
									?>
								<?php } ?>


                            </td>
                            <?php } ?>
                        </tr>
					<?php }


					?>
                    </tbody>
                </table>
			<?php }

			// Pagination (aJax)
			if (isset($pagination)) {
				// Pagination bas de page, verbose...
				$pagination->setVerbose_pagination(1);
				$pagination->setVerbose_position('right');
				$pagination->setNature_resultats('BL');
				$pagination->setNb_apres(2);
				$pagination->setNb_avant(2);

				echo ($pagination->getPaginationHtml());
			} // FIN test pagination

			?>
    <?php
    exit;

} // FIN mode

// Retourne la liste éditable et complétalbe des produits d'un BL en cours de création sur l'admin
function modeListeProduitsBlCreation() {

	global $cnx, $blsManagers;

	// Récupération de l'ID et instanciation de l'objet
	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id == 0) { exit('-1'); }

	

	$bl = $blsManagers->getBl($id, true, true);
	if (!$bl instanceof Bl) { exit('-2'); }

	$id_t_fact = isset($_REQUEST['id_t_fact']) ? intval($_REQUEST['id_t_fact']) : 0;
	if ($id_t_fact > 0 && $bl->getId_tiers_facturation() == 0) {
		$bl->setId_tiers_facturation($id_t_fact);
		$blsManagers->saveBl($bl);
	}

	if (empty($bl->getLignes())) { ?>
	    <div class="alert alert-secondary zerolignes text-center gris-9">
            <i class="fa fa-info-circle"></i> Aucun produit&hellip;
        </div>
	<?php  exit; }

	$tiersManager = new TiersManager($cnx);
	$languesManager = new LanguesManager($cnx);
	$langues = $languesManager->getListeLangues(['actif' => 1]);
	$ids_langues = [];
	foreach ($langues as $l) { $ids_langues[] = $l->getId(); }

    // Si client web, on rajoute une colonne pour associer à l'order_detail
    $id_client_web = $tiersManager->getId_client_web();
    $web = $id_client_web > 0 && $bl->getId_tiers_facturation() == $id_client_web;

    if ($web) {
        $orderPrestashopManager = new OrdersPrestashopManager($cnx);
		$orderPrestashopManager->cleanIdOrderDetailsLignesBl();
	}

	?>

    <table class="table table-blfact">
        <thead>
        <tr>
            <th>Code</th>
            <th>Désignation</th>
            <th class="text-center w-75px">Colis</th>
            <th class="text-center w-150px">Qté</th>
            <th class="text-right padding-right-10 w-150px">Poids (Kg)</th>
            <th class="text-right padding-right-10 w-150px">PU HT (€)</th>
            <th class="text-right padding-right-10 w-150px">Total HT (€)</th>
            <?php if ($web) { ?><th class="t-actions text-center w-50px">Web</th><?php } ?>
            <th class="t-actions text-center w-50px"></th>
        </tr>
        </thead>
        <tbody>
		<?php

		$total_colis 	= 0;
		$total_colis_bl = 0;
		$total_qte = 0;
		$id_palette 	= -1;
		$num_palette 	= 0;
		$id_poids_pal 	= 0;
		$total_poids 	= 0.0;
		$total_poids_bl = 0.0;
		$total_pu_bl 	= 0.0;
		$total_total_bl = 0.0;
		$total_palette_poids_palette_bl = 0.0;

		$produitsManager = new ProduitManager($cnx);
		$listeProduits = $produitsManager->getListeProduits();

		
		$poidsPalettesManager = new PoidsPaletteManager($cnx);
		$listePoidsPalettes = $poidsPalettesManager->getListePoidsPalettes(1); // 1 = Palettes et pas emballages/cartons


		$listeFrs = $tiersManager->getListeFournisseurs();

		foreach ($bl->getLignes() as $ligne) {

		    if (!$ligne->getProduit() instanceof Produit) {
		        $ligne->setProduit(new Produit([]));
			}

			$piece = false;
			if ($ligne->getProduit() instanceof Produit) {
				$piece = $ligne->getProduit()->isVendu_piece();
			}

			if ($id_palette < 0) {
				$id_palette 	= $ligne->getId_palette();
				$num_palette 	= $ligne->getNumero_palette();
				$id_poids_pal  = $ligne->getId_poids_palette();
			}



			$idpdt = $ligne->getId_produit_bl() > 0 ? $ligne->getId_produit_bl() : $ligne->getId_produit();
			$unicite =  'PAL'.$ligne->getId_palette().'PDT'.$idpdt.'LOT'.$ligne->getId_lot();

			if ($ligne->getId_palette() != $id_palette) {
				$total_palette_poids_palette = $poidsPalettesManager->getTotalPoidsPalette($id_palette);
				$total_poids_total = $total_poids + $total_palette_poids_palette;
				$total_palette_poids_palette_bl+=$total_palette_poids_palette;
				if ($id_palette > 0) {
				?>

                <tr class="total" data-id="<?php echo $id_palette; ?>">
                    <td></td>
                    <td class="text-right">
						<?php if ($id_palette > 0) { ?>
                        <select class="selectpicker float-left selectTypePoidsPalette" title="Type de palette">
							<?php  foreach ($listePoidsPalettes as $pp) { ?>
                                <option value="<?php echo $pp->getId();?>" <?Php echo $pp->getId() == $id_poids_pal ? 'selected' : ''; ?>><?php echo $pp->getNom(); ?></option>
							<?php } ?>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-secondary float-left ml-1" data-toggle="modal" data-target="#modalEmballagesPalette" data-id-palette="<?php echo $id_palette; ?>">Emb.</button>
						<?php }
                        if ($id_palette == 0) { echo 'Hors palette'; } else { ?>
                              Palette N° <input type="text" class="form-control numPalette w-50px text-center d-inline-block padding-2-10" value="<?php echo $num_palette; ?>"/>
                        <?php } ?>
                      </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class=""></td>
                    <td class=""></td>
                    <td class="t-actions"></td>
                </tr>
                    <?php } ?>
                <tr class="total totalPalette <?php echo $id_palette == 0 ? 'totalPalette0' : '' ?>" data-id="<?php echo $id_palette; ?>">
                    <td></td>
                    <td class="text-right">Total poids <?php echo $id_palette > 0 ? 'net' : 'hors palette'; ?></td>
                    <td class="text-center totalColis"><?php echo $total_colis;?></td>
                    <td></td>
                    <td class="text-right totalPoids"><?php echo number_format($total_poids,3, '.', ' ');?></td>
                    <td class=""></td>
                    <td class=""></td>
					<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
                    <td class="t-actions"></td>
                </tr>
				<?php if ($id_palette > 0) { ?>
                <tr class="total" data-id="<?php echo $id_palette; ?>">
                    <td></td>
                    <td class="text-right">Total poids emballages & palette</td>
                    <td></td>
                    <td></td>
                    <td class="text-right totalPoids"><?php echo number_format($total_palette_poids_palette,3, '.', ' ');?></td>
                    <td class=""></td>
                    <td class=""></td>
					<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
                    <td class="t-actions"></td>
                </tr>
                <tr class="total" data-id="<?php echo $id_palette; ?>">
                    <td></td>
                    <td class="text-right">Total poids brut</td>
                    <td></td>
                    <td></td>
                    <td class="text-right totalPoids"><?php echo number_format($total_poids_total,3, '.', ' ');?></td>
                    <td class=""></td>
                    <td class=""></td>
					<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
                    <td class="t-actions"></td>
                </tr>
				<?php
                } // FIN test palette 0
				$num_palette 	= $ligne->getNumero_palette();
				$id_palette 	= $ligne->getId_palette();
				$id_poids_pal   = $ligne->getId_poids_palette();
				$total_colis 	= 0;
				$total_qte      = 0;
				$total_poids 	= 0.0;

			} // FIN test changement de palette

			$total_colis+= 	(int)$ligne->getNb_Colis();
			//Mettre vide la Qte s'il est vendu en kg
			$total_qte+=  $piece ? (int)$ligne->getQte() : '';
			$total_poids+= 		(float)$ligne->getPoids();
			$total_colis_bl+= 	(int)$ligne->getNb_Colis();
			$total_poids_bl+= 	(float)$ligne->getPoids();
			$total_pu_bl+= 		(float)$ligne->getPu_ht();
			$total_total_bl+= 	(float)$ligne->getTotal();

            $ligneSimple =  $ligne->getId_palette() == 0 && ((int)$ligne->getCode() == 0 || trim($ligne->getCode()) == '');
            $codeLigneSimple = $ligneSimple ? 'LS'.$ligne->getId() : '';
			?>

            <tr data-id-ligne="<?php echo $ligne->getid(); ?>" class=" ligneBl lignePalette<?php echo $ligne->getId_palette();
			?> lignePdt<?php echo (int)$ligne->getId_produit_bl() > 0 ? $ligne->getId_produit_bl() : $ligne->getId_produit(); ?>" data-unicite="<?php echo $unicite; ?>">
                <td class="codeProduit" data-id="<?php echo (int)$ligne->getId_produit_bl() > 0 ? $ligne->getId_produit_bl() : $ligne->getId_produit(); ?>"><?php
                    echo $ligneSimple ? $codeLigneSimple : $ligne->getCode();?></td>
                <td>
                    <?php if ($id_palette == 0 || empty($ligne->getProduit()->getNoms())) {

                        echo '<span class="text-16">'.$ligne->getProduit()->getNoms()[1].'</span>';

                    } else { ?>
                    <select class="selectpicker form-control show-tick selectProduitBl">
						<?php foreach ($listeProduits as $pdt) {?>

                            <option value="<?php echo $pdt->getId(); ?>"

								<?php
								if($pdt->getId() == $ligne->getId_produit_bl()) { echo 'selected'; }
								else if ($pdt->getId() == $ligne->getId_produit()) { echo 'selected'; };
								?>

                                    data-subtext="<?php echo $pdt->getCode(); ?>"><?php echo $pdt->getNoms()[1]; ?></option>

						<?php }?>
                    </select>
                    <?php }


					// Si une désignation a été forcée, on l'affiche
					if ($ligne->getDesignation() != '') {
						echo $ligne->getDesignation();

						// Sinon, on prends le nom du produit
					} else if ($ligne->getProduit() instanceof Produit) {
						$noms = $ligne->getProduit()->getNoms();

						// Boucle sur les langues actives
						foreach ($ids_langues as $id_langue) { ?>
                            <span class="nom_trads nom_trad_<?php echo $id_langue; ?>"><?php echo isset($noms[$id_langue]) ? $noms[$id_langue] : '<span class="text-danger">Traduction manquante !</span>'; ?></span>
							<?php
						} // FIN Boucle sur les langues actives


					} // FIN test désignation libre / nom du produit

                    // Si client web, on récup l'order_ligne si on l'a
                    if ($web) {

						$od = $orderPrestashopManager->getOrderLigneByIdLigneBl($ligne->getId());
                        if ($od instanceof OrderDetailPrestashop) { ?>
                            <div class="bg-info text-white margin-top-2 padding-2-10 w-100pc texte-fin text-13">
                                <span class="badge badge-danger float-right text-13 ml-1 pointeur mt-1 btnDesassocieWebLigne" data-id-od="<?php echo $od->getId(); ?>"><i class="fa fa-times"></i></span>
                                <i class="fa fa-globe mr-1"></i> <?php echo $od->getReference_order() . '<br>'.$od->getNom(); ?>
                            </div>
						<?php }

					} // FIN web


					// Numéro de lot / origine
					?>
                    <div class="nomargin <?php echo $ligneSimple ? 'd-none' : ''; ?>">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Lot</span>
                            </div>
                            <input type="text" class="form-control w-150px numlotLigne" value="<?php echo $ligne->getNumlot();?>" placeholder="N/A" />
                        </div>
                    </div>
					<?php if ($ligne->getOrigine() != '') {?>
                        &nbsp; Orig. <?php echo $ligne->getOrigine(); } ?>
                    <div class="mt-1 mb-0 <?php echo $ligneSimple ? 'd-none' : ''; ?>">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Fournisseur</span>
                            </div>
                            <select class="form-control idFrs selectpicker lh-18 show-tick" data-style="text-13" data-id-ligne="<?php echo $ligne->getId();?>">
								<?php
								foreach ($listeFrs as $frs) { ?>
                                    <option value="<?php echo $frs->getId();?>" <?php
									echo $ligne->getId_frs() == $frs->getId() ? 'selected' : '';
									echo (int)$ligne->getId_frs() == 0 && strtolower($frs->getNom()) == "profil export" ? 'selected' : '';
									?>><?php echo $frs->getNom();?></option>
								<?php }
								?>
                            </select>
                        </div>
                    </div>

                </td>
                <td class="text-center"><input type="text" class="form-control text-center nbColisLigne" placeholder="-" <?php echo $ligneSimple ? 'disabled' : ''; ?> value="<?php
                    echo  $ligne->getNb_colis();
                    ?>"/></td>
                <td class="text-center"><input type="text" class="form-control text-center qteLigne  <?php
					echo $ligne->getProduit()->getVendu_piece() == 1 ? 'multiplicateurQte' : '';
					?>" value="<?php
					echo $ligne->getQte() > 1 ? $ligne->getQte() : 1;?>" placeholder="-"/></td>
                <td class="text-right"><input type="text" placeholder="0.000" <?php echo $ligneSimple ? 'disabled' : ''; ?> class="form-control text-right poidsLigne <?php
					echo $ligne->getProduit()->getVendu_piece() == 0 ? 'multiplicateurQte' : '';
					?>" value="<?php echo $ligne->getPoids() > 0 ? number_format($ligne->getPoids(),3, '.', '') : '';?>"/></td>
                <td class="text-right "><input type="text" class="form-control text-right puLigne <?php echo $ligne->getPu_ht() == 0 ? 'rougeTemp' : ''; ?>" placeholder="0.00" value="<?php echo $ligne->getPu_ht() > 0 ? number_format($ligne->getPu_ht(),2,'.', '') : '';?>" />
                    <input type="hidden" class="puLigneOld" value="<?php echo $ligne->getPu_ht(); ?>"/>
                </td>
                <td class="text-right totalLigne"><?php
                    $mult = $piece || $ligneSimple ? $ligne->getQte() : $ligne->getPoids();
                    echo (float)$ligne->getTotal() == 0 ?   number_format($ligne->getPu_ht() * $mult,2,'.', '')
                    : number_format($ligne->getTotal(),2,'.', '');?></td>
                <?php
                if ($web) { ?>
                    <td class="t-actions text-center">
                        <button type="button" class="btn btn-info btn-sm btnLigneWeb" data-id="<?php echo $ligne->getid(); ?>"><i class="fa fa-globe"></i></button>
                    </td>
				<?php }
                ?>
                <td class="t-actions text-center">
                    <button type="button" class="btn btn-danger btn-sm btnSupprLigneBl" data-id="<?php echo $ligne->getid(); ?>"><i class="fa fa-trash-alt"></i></button>
                </td>
            </tr>

		<?php } // FIN boucle sur les lignes


		$total_palette_poids_palette = $poidsPalettesManager->getTotalPoidsPalette($id_palette);
		$total_poids_total = $total_poids + $total_palette_poids_palette;
		$total_palette_poids_palette_bl+=$total_palette_poids_palette;

		if ($id_palette > 0) {
		?>

        <tr class="total" data-id="<?php echo $id_palette; ?>">
            <td></td>
            <td class="text-right">


                <select class="selectpicker float-left selectTypePoidsPalette" title="Type de palette">
					<?php  foreach ($listePoidsPalettes as $pp) { ?>
                        <option value="<?php echo $pp->getId();?>" <?Php echo $pp->getId() == $id_poids_pal ? 'selected' : ''; ?>><?php echo $pp->getNom(); ?></option>
					<?php } ?>
                </select>
                <button type="button" class="btn btn-sm btn-outline-secondary float-left ml-1" data-toggle="modal" data-target="#modalEmballagesPalette" data-id-palette="<?php echo $id_palette; ?>">Emb.</button>

				<?php if ($id_palette == 0) { echo 'Hors palette'; } else { ?>
                    Palette N° <input type="text" class="form-control numPalette w-50px text-center d-inline-block padding-2-10" value="<?php echo $num_palette; ?>"/>
				<?php } ?>
            </td>
            <td></td>
            <td></td>
            <td></td>
            <td class=""></td>
            <td class=""></td>
			<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
            <td class="t-actions"></td>
        </tr>
            <?php } ?>
        <tr class="total totalPalette <?php echo $id_palette == 0 ? 'totalPalette0' : '' ?>" data-id="<?php echo $id_palette; ?>">
            <td></td>
            <td class="text-right">Total <?php echo $id_palette > 0 ? 'poids net' : 'hors palette'; ?></td>
            <td class="text-center totalColis"><?php echo  $id_palette > 0 ? $total_colis : '';?></td>
            <td class="text-center totalQte"><?php echo  $id_palette == 0 ? $total_qte : '';?></td>
            <td class="text-right totalPoids"><?php echo $total_poids > 0 ? number_format($total_poids,3, '.', ' ') : '';?></td>
            <td class=""></td>
            <td class=""></td>
			<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
            <td class="t-actions"></td>
        </tr>
        <?php if ($id_palette > 0) { ?>
        <tr class="total" data-id="<?php echo $id_palette; ?>">
            <td></td>
            <td class="text-right">Total poids emballages & palette</td>
            <td></td>
            <td></td>
            <td class="text-right totalPoids"><?php echo number_format($total_palette_poids_palette,3, '.', ' ');?></td>
            <td class=""></td>
            <td class=""></td>
			<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
            <td class="t-actions"></td>
        </tr>
        <tr class="total" data-id="<?php echo $id_palette; ?>">
            <td></td>
            <td class="text-right">Total poids brut</td>
            <td></td>
            <td></td>
            <td class="text-right totalPoids"><?php echo number_format($total_poids_total,3, '.', ' ');?></td>
            <td class=""></td>
            <td class=""></td>
			<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
            <td class="t-actions"></td>
        </tr>
        <?php } ?>


        </tbody>


        <tfoot>
        <tr class="ligneTotalBl">
            <td></td>
            <td class="text-right">Total de l'expédition net</td>
            <td class="text-center totalColis"><?php echo $total_colis_bl;?></td>
            <td></td>
            <td class="text-right totalPoids"><?php echo number_format($total_poids_bl,3, '.', ' ');?></td>
            <td class="text-right totalPu"></td>

            <td class="text-right totalBl"><?php echo (int)$total_total_bl > 0 ? $total_total_bl : '';?></td>
			<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
            <td class="t-actions"></td>
        </tr>
        <tr class="">
            <td></td>
            <td class="text-right">Total poids emballages & palettes</td>
            <td></td>
            <td></td>
            <td class="text-right totalPoidsPalettes"><?php echo number_format($total_palette_poids_palette_bl,3, '.', ' ');?></td>
            <td class="text-right"></td>
            <td class="text-right"></td>
			<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
            <td class="t-actions"></td>
        </tr>
        <tr class="">
            <td></td>
            <td class="text-right">Total de l'expédition brut</td>
            <td></td>
            <td></td>
            <td class="text-right totalPoidsTotal"><?php echo number_format($total_palette_poids_palette_bl + $total_poids_bl,3, '.', ' ');?></td>
            <td class="text-right"></td>
            <td class="text-right"></td>
			<?php if ($web) { ?><td class="t-actions"></td><?php } ?>
            <td class="t-actions"></td>
        </tr>
        </tfoot>

    </table>

    <?php
} // FIN mode

// Recherche tous les produits en stock ou non pour ajout d'un produit à un nouveau BL (nouveau)
function modeRechercheProduitsBlManuel() {
    global $cnx;

	$produitsManager = new ProduitManager($cnx);
	$negoceManager   = new LotNegoceManager($cnx);


	// Récupération de l'ID et instanciation de l'objet
	$id_pdt = isset($_REQUEST['id_pdt']) ? intval($_REQUEST['id_pdt']) : 0;
	if ($id_pdt == 0) { exit('Identification impossible !'); }

	$produitsEnStock = $produitsManager->getStocksProduit($id_pdt);
	$produitsNegoce  = $negoceManager->getListeNegoceProduits(['id_pdt' => $id_pdt]);	
	
	$hs = empty($produitsEnStock) && empty($produitsNegoce);	
	?>
    <div class="col mt-2">
        <div class="alert alert-<?php echo $hs ? 'secondary' : 'info'; ?> text-center">
            <p class="text-20"><?php
                echo $hs ? 'Aucune production disponible' : 'Productions disponibles';
                ?></p>
            <p class="text-13 <?php echo $hs ? 'd-none' : ''; ?>">Sélectionnez hors stock ou la production à ajouter au BL :</p>
            <?php
            if ($hs) { ?>
                <button type="button" class="btn btn-success btnAjouterProduitBl"><i class="fa fa-check mr-1"></i> Ajouter ce produit hors stock</button>
			<?php } else { ?>
        <div class="input-group">
                <select class="selectpicker form-control selectProductionProduit">
                    <option value="0">Hors stock</option>
					<?php					
					if (!empty($produitsEnStock)){ ?>
                        <optgroup data-type="stk" label="Production<?php echo count($produitsEnStock) > 1 ? 's' : ''; ?> en stock <?php echo count($produitsEnStock) > 1 ? ' ('.count($produitsEnStock).')' : ''; ?> :">
							<?php
							foreach ($produitsEnStock as $compo) { ?>
                                <option value="<?php echo $compo->getId(); ?>" data-subtext="<?php
                                    echo $compo->getNb_colis().' colis / ' . number_format($compo->getPoids(),3,'.', ' ') . ' kg';?>"><?php
                                    echo'Lot ';
                                    echo $compo->getNum_lot() != '' ? $compo->getNum_lot() : 'N/A';
                                    echo ' &mdash; Palette '.$compo->getNumero_palette() . ' ('.$compo->getNom_client().')'; ?></option>
							<?php }
							?>
                        </optgroup>
						<?php
					}
					if (!empty($produitsNegoce)) {?>
                        <optgroup data-type="neg" label="Produit<?php echo count($produitsNegoce) > 1 ? 's' : ''; ?> de négoce <?php echo count($produitsNegoce) > 1 ? ' ('.count($produitsNegoce).')' : ''; ?> :">
							<?php
							foreach ($produitsNegoce as $pdt_neg) {																									
								?>							
                                <option value="<?php echo $pdt_neg->getId_lot_pdt_negoce(); ?>" data-subtext="<?php
								echo $pdt_neg->getNb_cartons().' cartons / ' . number_format($pdt_neg->getPoids(),3,'.', ' ') . ' kg';?>"><?php
                                echo 'Lot ';
								echo $pdt_neg->getNum_lot() != '' ? $pdt_neg->getNum_lot() : 'N/A';
								echo $pdt_neg->getNumero_palette() != '' ? ' &mdash; Palette '.$pdt_neg->getNumero_palette() : ''; ?></option>
							<?php }
							?>
                        </optgroup>
					<?php } ?>
                </select>
                <div class="input-group-append">
                    <button class="btn btn-success btnAjouterProduitBl" type="button"><i class="fa fa-check mr-1"></i> Ajouter</button>
                </div>
        </div>
			<?php }
            ?>

        </div>
    </div>
    <?php


} // FIN méthode

// Recherche tous les produits en stock pour ajout d'un produit à un nouveau BL (ancien)
function modeRechercheProduitsStock() {

	global $cnx;

	$produitsManager = new ProduitManager($cnx);

	// Récupération de l'ID et instanciation de l'objet
	$id_pdt = isset($_REQUEST['id_pdt']) ? intval($_REQUEST['id_pdt']) : 0;
	if ($id_pdt == 0) { exit('Identification impossible !'); }


	$produitsEnStock = $produitsManager->getStocksProduit($id_pdt);

	// Si aucun en stock...
	if (!$produitsEnStock || empty($produitsEnStock)) {

	    // On recherche des produits de négoce...
        $negoceManager = new LotNegoceManager($cnx);
        $produitsNegoce = $negoceManager->getListeNegoceProduits(['hors_bl' => true, 'id_pdt' => $id_pdt]);
        if (!$produitsNegoce || empty($produitsNegoce)) {
			?>

            <div class="col mt-2">
                <div class="alert alert-warning text-center">
                    <p class="text-20"> Aucun produit en stock.</p>
                    <p class="nomargin text-13">Cliquez sur « Ajouter au BL » pour sélectionner ce produit malgré tout.</p>
                </div>
            </div>

			<?php
        // On a trouvé un produit de négoce
        } else if (count($produitsNegoce) == 1) {
			$pdt_neg = $produitsNegoce[0];
			if (!$pdt_neg instanceof NegoceProduit) { exit("Echec d'instanciation de l'objet NegoceProduit !"); }
			?>

            <div class="col mt-2">
                <input type="hidden" name="id_pdt_neg" value="<?php echo $pdt_neg->getId_lot_pdt_negoce();?>"/>
                <div class="alert alert-info text-center">
                    <p class="text-20"> <?php echo $pdt_neg->getNom_produit(); ?>
                    <span class="badge badge-dark text-13 d-block" style="background: #523307">PRODUIT DE NÉGOCE</span></p>
                    <p class="nomargin">
                        <span class="gris-3 texte-fin text-12 ml-2">Lot : </span>
						<?php echo $pdt_neg->getNum_lot(); ?>
                        <span class="gris-3 texte-fin text-12 ml-2">Palette : </span>
						<?php echo $pdt_neg->getNumero_palette(); ?>
                        <span class="gris-3 texte-fin text-12 ml-2">DLC : </span>
						<?php echo Outils::dateSqlToFr($pdt_neg->getDlc()); ?><br>
                        <span class="gris-3 texte-fin text-12 ml-2">Nombre de cartons : </span>
						<?php echo $pdt_neg->getNb_cartons(); ?>
                        <span class="gris-3 texte-fin text-12 ml-2">Poids : </span>
						<?php echo number_format($pdt_neg->getPoids(),3,'.', ' '); ?> kg
                    </p>
                </div>
            </div>
			<?php
        // On a trouvé plusieurs produits de négoce
		} else {
			?>

            <div class="col mt-2">
                <div class="alert alert-info text-center">
                    <p class="text-20"> Plusieurs produit de négoce ont été trouvés.</p>
                    <p class="text-13">Sélectionnez celui à ajouter au BL :</p>
                    <select class="selectpicker form-control selectNegoceMulti">
						<?php
						foreach ($produitsNegoce as $pdt_neg) { ?>
                            <option value="<?php echo $pdt_neg->getId_lot_pdt_negoce(); ?>" >Palette <?php echo $pdt_neg->getNumero_palette().' - Lot ' . $pdt_neg->getNum_lot() . ' - '.$pdt_neg->getNb_cartons().' cartons / ' . number_format($pdt_neg->getPoids(),3,'.', ' ') . ' kg'; ?></option>
						<?php } ?>
                    </select>
                </div>
            </div>

			<?php

		} // FIN test produits de négoce


    // Si un seul trouvé
    } else if (count($produitsEnStock) == 1) {

	    $compo = $produitsEnStock[0];
	    if (!$compo instanceof PaletteComposition) { exit("Echec d'instanciation de l'objet PaletteComposition !"); }
	    ?>

        <div class="col mt-2">
            <input type="hidden" name="id_compo" value="<?php echo $compo->getId();?>"/>
            <div class="alert alert-info text-center">
                <p class="text-20"> <?php echo $compo->getNom_produit(); ?></p>
                <p class="nomargin">
                    <span class="gris-3 texte-fin text-12">Client : </span>
                    <?php echo $compo->getNom_client(); ?>
                    <span class="gris-3 texte-fin text-12 ml-2">Palette : </span>
					<?php echo $compo->getNumero_palette(); ?>.
                    <span class="gris-3 texte-fin text-12 ml-2">Lot : </span>
					<?php echo $compo->getNum_lot(); ?><br>
                    <span class="gris-3 texte-fin text-12 ml-2">Nombre de colis/blocs : </span>
					<?php echo $compo->getNb_colis(); ?>
                    <span class="gris-3 texte-fin text-12 ml-2">Poids : </span>
					<?php echo number_format($compo->getPoids(),3,'.', ' '); ?> kg
                </p>
            </div>
        </div>

    <?php
    // Si plusieurs trouvés...
	} else { ?>

        <div class="col mt-2">
            <div class="alert alert-info text-center">
                <p class="text-20"> Plusieurs produit en stock ont été trouvés.</p>
                <p class="text-13">Sélectionnez celui à ajouter au BL :</p>
                <select class="selectpicker form-control selectCompoMulti">
                    <?php
					foreach ($produitsEnStock as $compo) { ?>
                        <option value="<?php echo $compo->getId(); ?>" ><?php echo $compo->getNom_client().' - Palette '.$compo->getNumero_palette().' - Lot ' . $compo->getNum_lot() . ' - '.$compo->getNb_colis().' colis / ' . number_format($compo->getPoids(),3,'.', ' ') . ' kg'; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

    <?php
	} // FIN test résultats

    exit;

} // FIN mode


// Rajoute un produit ou une compo au BL (admin) - NPU
function modeAddCompoPdtBl() {

	global $cnx, $blsManagers;

	$produitsManager = new ProduitManager($cnx);
	$palettesManager = new PalettesManager($cnx);

	// Récupération de l'ID et instanciation de l'objet
	$id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) : 0;
	$id_pdt = isset($_REQUEST['id_pdt']) ? intval($_REQUEST['id_pdt']) : 0;
	$id_compo = isset($_REQUEST['id_compo']) ? intval($_REQUEST['id_compo']) : 0;
	$id_pdt_negoce = isset($_REQUEST['id_pdt_negoce']) ? intval($_REQUEST['id_pdt_negoce']) : 0;

	if ($id_bl == 0) { exit('-1');}
	if ($id_pdt == 0 && $id_compo == 0 && $id_pdt_negoce == 0) { exit('-2');}

	$bl = $blsManagers->getBl($id_bl);
	if (!$bl instanceof Bl) { exit('-3'); }

	// Si la compo est déjà dans les lignes du BL, on ne fais rien...
    $ids_compos = [];
    $ids_pdts_negoce = [];
    foreach ($bl->getLignes() as $blligne) {
        $ids_compos[] = intval($blligne->getId_compo());
		$ids_pdts_negoce[] = intval($blligne->getId_pdt_negoce());
    }

    if ($id_compo > 0 && in_array($id_compo, $ids_compos)) {
        exit('2'); // On renvoie malgré tout un retour positif
    }

	if ($id_pdt_negoce > 0 && in_array($id_pdt_negoce, $ids_pdts_negoce)) {
		exit('2'); // On renvoie malgré tout un retour positif
	}

    // Si on ajoute une compo...
    if ($id_compo > 0) {

		$compo = $palettesManager->getComposition($id_compo);
		if (!$compo instanceof PaletteComposition) {
			exit('-5');
		}

		// On vérifie qu'on a pas déjà cette compo dans un BL !
        if ($blsManagers->checkCompoDejaBl($id_compo)) { exit('2'); } // On renvoie malgré tout un retour positif

		$ligne = $blsManagers->getDonneesLigneBl($id_compo);
		if (!$ligne instanceof BlLigne) {
			exit('-6');
		}

		// On rend les données instanciées persistantes
		$ligne->setId_produit($ligne->getId_produit());
		$ligne->setId_palette($ligne->getId_palette());
		$ligne->setNumlot($ligne->getNumlot());
		$ligne->setId_lot($ligne->getId_lot());
		$ligne->setPoids($ligne->getPoids());
		$ligne->setNb_colis($ligne->getNb_colis());
		$ligne->setPu_ht($ligne->getPu_ht());
		$ligne->setTva($ligne->getTva());

		$ligne->setId_compo($id_compo);;
		$ligne->setSupprime(0);
		$ligne->setQte(1);
		$ligne->setId_bl($id_bl);
		$ligne->setId_produit_bl(0);
		$ligne->setDate_add(date('Y-m-d H:i:s'));

		echo $blsManagers->saveBlLigne($ligne) ? 1 : 0;


		// SI on rajoute un produit de négoce
	} else if ($id_pdt_negoce > 0) {

        $negoceManager = new LotNegoceManager($cnx);

		$pdt_neg = $negoceManager->getNegoceProduit($id_pdt_negoce);
		if (!$pdt_neg instanceof NegoceProduit) {
			exit('-8');
		}

		$ligne = $blsManagers->getDonneesLigneBlByNegoce($id_pdt_negoce);

		if (!$ligne instanceof BlLigne) {
			exit('-9');
		}

		// On rend les données instanciées persistantes
		$ligne->setId_produit($ligne->getId_produit());
		$ligne->setId_palette($ligne->getId_palette());
		$ligne->setNumlot($ligne->getNumlot());
		$ligne->setId_lot($ligne->getId_lot());
		$ligne->setPoids($ligne->getPoids());
		$ligne->setNb_colis($ligne->getNb_colis());
		$ligne->setPu_ht($ligne->getPu_ht());
		$ligne->setTva($ligne->getTva());
		$ligne->setId_pdt_negoce($id_pdt_negoce);

		$ligne->setId_compo($id_compo);;
		$ligne->setSupprime(0);
		$ligne->setQte(1);
		$ligne->setId_bl($id_bl);
		$ligne->setId_produit_bl(0);
		$ligne->setDate_add(date('Y-m-d H:i:s'));

		echo $blsManagers->saveBlLigne($ligne) ? 1 : 0;

    // SI on ajoute un produit libre
    } else if ($id_pdt > 0) {

        $pdt = $produitsManager->getProduit($id_pdt);
        if (!$pdt instanceof Produit) { exit('-7'); }

		$tarifsManager = new TarifsManager($cnx);
        $tiersManager = new TiersManager($cnx);

        $client_facture = $tiersManager->getTiers($bl->getId_tiers_facturation());

		$pu_ht = $client_facture instanceof Tiers ? $tarifsManager->getTarifClientProduit($client_facture, $id_pdt) : 0;
		$tva = $client_facture instanceof Tiers ? $produitsManager->getTvaProduit($id_pdt) : 0;
		if ($tva < 0) { $tva = 0; }

        $ligne = new BlLigne([]);
        $ligne->setId_bl($id_bl);
        $ligne->setId_compo(0);
        $ligne->setId_palette(0);
        $ligne->setId_produit($id_pdt);
        $ligne->setId_produit_bl(0);
        $ligne->setId_lot(0);
		$ligne->setNumlot("");
		$ligne->setPoids(0);
		$ligne->setNb_colis(0);
		$ligne->setQte(1);
		$ligne->setDate_add(date('Y-m-d H:i:s'));
		$ligne->setSupprime(0);
        $ligne->setPu_ht($pu_ht);
		$ligne->setTva($tva);
		echo $blsManagers->saveBlLigne($ligne) ? 1 : 0;


    // Ca ne devrait jamais arriver ici
    } else { exit('-4'); }


    exit;
} // FIN mode



// Supprime un BL
function modeSupprBl() {

	global $blsManagers, $cnx;

	$logsManager = new LogManager($cnx);

	$id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) : 0;
	if ($id_bl == 0) { exit('-1'); }

	$bl = $blsManagers->getBl($id_bl, false);
	if (!$bl instanceof Bl) { exit('-2'); }

	$res = $blsManagers->supprBl($bl);

	echo $res ? 1 : 0;
	$logTexte = $res ? 'S' : 'Echec de la s';
	$logTexte.='uppression du BL #'.$id_bl;
	$logtype = $res ? 'info' : 'danger';
	$log = new Log([]);
	$log->setLog_texte($logTexte);
	$log->setLog_type($logtype);
	$logsManager->saveLog($log);


	exit;



} // FIN mode

// Génère un bon de transfert d'après des ids compos
function modeGenereBonTransfertFromCompos() {

    global $cnx, $blsManagers;

	$ids_compos = isset($_REQUEST['ids_compos']) ? trim($_REQUEST['ids_compos']) : '';
	if ($ids_compos == '') { exit('-1'); }

	$configManager  = new ConfigManager($cnx);
	$produitManager = new ProduitManager($cnx);

	// On transforme en Array les compos passées
	$composArray = explode(',', $ids_compos);

	// Si problème avec l'array...
	if (!is_array($composArray)) { exit('-2'); }

    // Création du BL (BT) en base... d'après les ids_comps, s'il correspond déjà on retourne le BL déjà existant
	$bl  = $blsManagers->getOrCreateBlFromCompos($composArray, true);
	if (empty($bl->getLignes())) { exit('-3'); }

	// On rend persistant les données des lignes du BL en dur
	foreach ($blsManagers->getListeBlLignes(['id_bl' => $bl->getId(), 'regroupement' => 0]) as $ligne) {

		$pdt = $produitManager->getProduit($ligne->getId_produit(), false);
		if ((int)$pdt->getVendu_piece() == 0) { $ligne->setQte(1); }

		$tva = round($produitManager->getTvaProduit($ligne->getId_produit()),2);
		if ($tva < 0) { $tva = 0;}

		// Si on a un id_pays déjà renseigné dans la ligne de BL et si on en as pas on prends alors comme avant celui du lot
		$id_pays = (int)$ligne->getId_pays() > 0 ? $ligne->getId_pays() : $blsManagers->getIdPaysFromLot($ligne->getId_lot());

		$ligne->setId_palette($ligne->getId_palette());
		$ligne->setId_produit($ligne->getId_produit());
		$ligne->setId_produit_bl($ligne->getId_produit_bl());
		$ligne->setId_lot($ligne->getId_lot());
		$ligne->setNumlot($ligne->getNumlot());
		$ligne->setPoids($ligne->getPoids());
		$ligne->setNb_colis($ligne->getNb_colis());
		$ligne->setQte($ligne->getQte() > 0 ? $ligne->getQte() : 1);
		$ligne->setPu_ht($ligne->getPu_ht());
		$ligne->setTva($tva);
		$ligne->setId_pays($id_pays);

		$blsManagers->saveBlLigne($ligne);

	} // FIN boucle sur les lignes

	// Choix de langue et chiffrage pour PDF : Bon de transfert = non chiffré en français
	$bl->setId_langue(1);
	$bl->setChiffrage(0);

	$pdf_top_bl = $configManager->getConfig('pdf_top_bl');
	$margeEnTetePdt = $pdf_top_bl instanceof Config ?  (int)$pdf_top_bl->getValeur() : 0;

	// Génère le PDF
	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content_fichier = genereContenuPdf($bl, 1, false, false);
	$html_additionnel = getDebutTableauBl($bl);
	$content_header = genereHeaderPdf($bl, $html_additionnel);
	$contentPdf = '<page backtop="'.(int)$margeEnTetePdt.'mm">';
	$contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style>'.$content_header.'</page_header>';
	$contentPdf.= $content_fichier;
	$contentPdf.= '</page>'. ob_get_clean();

	try {
		$marges = [7, 12, 7, 12];
		$nom_fichier = $bl->getFichier();
		$dir = $blsManagers->getDossierBlPdf($bl);
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'ISO-8859-15', $marges);
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($contentPdf));;
		$savefilepath = __CBO_ROOT_PATH__ . $dir . $nom_fichier;
		$html2pdf->Output($savefilepath, 'F');
	} catch (HTML2PDF_exception $e) {
		vd($e);
		exit;
	}

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	//$log->setLog_texte("Génération et impression de ".$copies." copie(s) du BL #".$bl->getId());
	$log->setLog_texte("Génération et téléchargement depuis STK du BL (Bon de transfert) #".$bl->getId());
	$logsManager = new LogManager($cnx);
	$logsManager->saveLog($log);

	// OK, on met à jour le statut
	$bl->setStatut(2); // 2 = Généré
	$bl->setSupprime(0);
	$blsManagers->saveBl($bl);

	// On supprime les compos (flag) -> Le BL est généré, elles ne sont plus en stock (demande client du 10/11/2020)
	//$blsManagers->supprComposBl($bl);


	$fichier = $bl->getFichier();
	$dir = $blsManagers->getDossierBlPdf($bl);
	echo $bl->getId().'|'.__CBO_ROOT_URL__.$dir.$fichier;
	//echo __CBO_SCRIPTS_PHP_URL__.'download.php?bl&f='.base64_encode($fichier);

	exit;

} // FIN mode

// Génère le PDF du bon de transfert
function modeGenerePdfBonTransfert(BonTransfert $bonTransfert) {

	global $cnx;

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	$configManager = new ConfigManager($cnx);
	$pdf_top_bt = $configManager->getConfig('pdf_top_bt');
	$margeEnTetePdt = $pdf_top_bt instanceof Config ?  (int)$pdf_top_bt->getValeur() : 0;

	ob_start();
	$content_fichier = genereContenuBonTransfertPdf($bonTransfert);
	$html_additionnel = getDebutTableauBonTransfert($bonTransfert);
	$content_header = genereHeaderBonTransfertPdf($bonTransfert, $html_additionnel);
	$contentPdf = '<page backtop="'.(int)$margeEnTetePdt.'mm">';
	$contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style>'.$content_header.'</page_header>';
	$contentPdf.= $content_fichier;
	$contentPdf.= '</page>'. ob_get_clean();

	try {
		$marges = [7, 12, 7, 12];
		$nom_fichier = $bonTransfert->getNum_bon_transfert().'.pdf';
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'ISO-8859-15', $marges);
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($contentPdf));;
		$savefilepath = __CBO_ROOT_PATH__ . '/gescom/bon_transfert/' . $nom_fichier;
		$html2pdf->Output($savefilepath, 'F');
		echo __CBO_ROOT_URL__.'gescom/bon_transfert/'.$nom_fichier;
	} catch (HTML2PDF_exception $e) {
		vd($e);
		exit;
	}

	// Log
	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte("Génération et téléchargement depuis STK du Bon de transfert #".$bonTransfert->getId());
	$logsManager = new LogManager($cnx);
	$logsManager->saveLog($log);

} // FIN fonction

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le contenu HTML du lot pour le PDF
-----------------------------------------------------------------------------*/
function genereContenuBonTransfertPdf(BonTransfert $bon) {

	global $cnx, $blsManagers;

    $traductionsManager = new TraductionsManager($cnx);
	$palettesManager = new PalettesManager($cnx);
	$tiersManager = new TiersManager($cnx);
	$produitManager = new ProduitManager($cnx);

	$client = $tiersManager->getTiers($bon->getId_tiers());
	if (!$client instanceof Tiers) { $client = new Tiers([]); } // Gestion des erreurs

	$id_langue = $client->getId_langue();
	if ($id_langue == 0) { $id_langue = 1; }

	$produitTrad = $traductionsManager->getTrad('produit', $id_langue);
	$paletteTrad = $traductionsManager->getTrad('palette', $id_langue);

	$contenu = '<div class="page">';

	$contenu.='<table class="table table-blfact w100">';


    $idsproduits = [];
    $idspalettes = [];
    $poidsTotal = 0;

	// Boucle sur les lignes
	foreach ($bon->getLignes() as $ligne) {

	    $produit = $produitManager->getProduit($ligne->getId_produit());
	    if (!$produit instanceof Produit) { continue; }


	    $palette = $palettesManager->getPalette($ligne->getId_palette());
	    if (!$palette instanceof Palette) { $palette = new Palette([]); }

		$idsproduits[$ligne->getId_produit()] = $ligne->getId_produit();
		$idspalettes[$ligne->getId_palette()] = $ligne->getId_palette();

		$poidsTotal+=(float)$ligne->getPoids();

		$noms = $produit->getNoms();
		$designation = isset($noms[$id_langue]) ? $noms[$id_langue] : 'Traduction manquante !';

		// DEBUG symbole non géré par HTML2PDF
		$designation =str_replace('œ', 'oe', $designation);
		$designation =str_replace('Œ', 'OE', $designation);
		$designation =str_replace('&OElig;', 'OE', $designation);
		$designation =str_replace('&oelig;', 'oe', $designation);
		$designation =str_replace('&#140;', 'OE', $designation);
		$designation =str_replace('&#156;', 'oe', $designation);


		$contenu.= '<tr>';
		$contenu.= '<td class="w15 border-l border-r">' . $produit->getCode() . '</td>';
		$contenu.= '<td class="w50 border-l border-r">' . $designation . '</td>';
		$contenu.= '<td class="w15 border-l border-r text-center">' . $palette->getNumero() . '</td>';
		$contenu.= '<td class="w20 border-l border-r text-right">' . number_format($ligne->getPoids(),3,'.', ' ') . '</td>';
		$contenu.= '</tr>';


    } // FIN boucle sur le slignes


    $plurielProduits = count($idsproduits) > 1 ? 's' : '';
    $plurielPalettes = count($idspalettes) > 1 ? 's' : '';

	$contenu.= '<tr class="soustotal">';
	$contenu.= '<td class="w15 border-l border-r">TOTAL</td>';
	$contenu.= '<td class="w50 border-l border-r">' . count($idsproduits) . ' ' . $produitTrad.$plurielProduits.'</td>';
	$contenu.= '<td class="w15 border-l border-r text-center">' . count($idspalettes) . ' ' . $paletteTrad.$plurielPalettes.'</td>';
	$contenu.= '<td class="w20 border-l border-r text-right">' . number_format($poidsTotal,3,'.', ' ') . '</td>';
	$contenu.= '</tr>';

	$contenu.='</table>';
	$contenu.='</div>';

	$contenu = str_replace('Œ', 'OE', $contenu);

    return $contenu;

} // FIN fonction


/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère les lignes de titre du tableau (header)
-----------------------------------------------------------------------------*/
function getDebutTableauBonTransfert(BonTransfert $bon) {

	global $cnx;
	$traductionsManager = new TraductionsManager($cnx);
	$tiersManager = new TiersManager($cnx);

	$client = $tiersManager->getTiers($bon->getId_tiers());
	if (!$client instanceof Tiers) { $client = new Tiers([]); } // Gestion des erreurs

	$id_langue = $client->getId_langue();
	if ($id_langue == 0) { $id_langue = 1; }

	$date_bon = Outils::dateSqlToFr($bon->getDate());


	$contenu ='<table class="table table-blfact w100 mt-15">';
	$contenu.='<tr class="entete">';
	$contenu.='<td class="w20 border-l border-t text-left">PROFIL EXPORT</td>';
	$contenu.='<td class="w60 border-t text-center">';
	$contenu.= $client->getNom();
	$contenu.='</td>';
	$contenu.='<td class="w20 border-r border-t text-right">';
	$contenu.= $traductionsManager->getTrad('date',$id_langue) . ' : ' . $date_bon;
	$contenu.='</td>';
	$contenu.='</tr>';
	$contenu.='</table>';
	$contenu.='<table class="table table-blfact w100">';
	$contenu.='<tr class="entete">';

	$contenu.= '<td class="w15 border-l border-t border-b border-r">' . $traductionsManager->getTrad('codepdt', $id_langue) . '</td>';
	$contenu.= '<td class="w50 border-l border-t border-b border-r">' . $traductionsManager->getTrad('designation', $id_langue) . '</td>';
	$contenu.= '<td class="w15 border-l border-t border-b border-r text-center">' . $traductionsManager->getTrad('numpalette', $id_langue).'</td>';
	$contenu.= '<td class="w20 border-l border-t border-b border-r text-right">' . $traductionsManager->getTrad('poidsnet', $id_langue) . '</td>';

	$contenu.='</tr>';
	$contenu.='</table>';

	return $contenu;

} // FIN fonction

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le contenu HTML du header
-----------------------------------------------------------------------------*/
function genereHeaderBonTransfertPdf(BonTransfert $bon, $html_additionnel) {

	global $cnx;
	$documentsManager = new DocumentManager($cnx);

	$tiersManager = new TiersManager($cnx);
	$client = $tiersManager->getTiers($bon->getId_tiers());
	if (!$client instanceof Tiers) { $client = new Tiers([]); } // Gestion des erreurs
	$id_langue = $client->getId_langue();
	if ($id_langue == 0) { $id_langue = 1; }
	return $documentsManager->getHeaderDocumentPdf($client, 'l', 'bt', $id_langue, true, true, $bon->getNum_bon_transfert(), $html_additionnel);

} // FIN fonction

// Retourne la liste des bons de livraison (admin)
function modeGetListeBons() {

	global $mode, $cnx, $utilisateur;
	$bonTransfertManager = new BonsTransfertsManager($cnx);

	$tiersManager = new TiersManager($cnx);

	$nbResultPpage_defaut = 25;

	$page              = isset($_REQUEST['page'])             ? intval($_REQUEST['page'])               : 1;
	$id_tiers          = isset($_REQUEST['id_tiers'])         ? intval($_REQUEST['id_tiers'])           : 0;
	$date_du           = isset($_REQUEST['date_du'])          ? trim($_REQUEST['date_du'])              : '';
	$date_au           = isset($_REQUEST['date_au'])          ? trim($_REQUEST['date_au'])              : '';
	$num_bon           = isset($_REQUEST['num_bon'])          ? trim(strtoupper($_REQUEST['num_bon']))  : '';
	$nbResultPpage     = isset($_REQUEST['nb_result_p_page']) ? intval($_REQUEST['nb_result_p_page'])   : 0;
	if ($nbResultPpage == 0) { $nbResultPpage = $nbResultPpage_defaut; }

	if ($date_du != '') {
		$date_du = Outils::dateFrToSql($date_du);
		if (!Outils::verifDateSql($date_du)) { $date_du = ''; }
	}
	if ($date_au != '') {
		$date_au = Outils::dateFrToSql($date_au);
		if (!Outils::verifDateSql($date_au)) { $date_au = ''; }
	}

	// Préparation pagination (Ajax)
	$filtresPagination  = '?mode='.$mode;
	$start              = ($page-1) * $nbResultPpage;

	$params = [
		'id_tiers'         => $id_tiers,
		'num_bon'          => $num_bon,
		'date_du'          => $date_du,
		'date_au'          => $date_au,
		'start'            => $start,
		'nb_result_page'   => $nbResultPpage
	];

	$liste = $bonTransfertManager->getListeBonsTransferts($params);

	$nbResults  = $bonTransfertManager->getNb_results();
	$pagination = new Pagination($page);

	$pagination->setUrl($filtresPagination);
	$pagination->setNb_results($nbResults);
	$pagination->setAjax_function(true);
	$pagination->setNb_results_page($nbResultPpage);
	$pagination->setNb_apres(2);
	$pagination->setNb_avant(2);

	if (empty($liste)) { ?>

        <div class="alert alert-warning">
            Aucun bon de transfert n'a été trouvé...
        </div>

	<?php  } else { ?>
        <table class="table admin table-v-middle">
            <thead>
            <tr>
				<?php if ($utilisateur->isDev()) { ?>
                    <th>ID</th>
				<?php }  ?>
                <th>Date</th>
                <th>Numéro</th>
                <th>Client</th>
                <th class="text-center">Nombre de palettes</th>
                <th class="text-center">Nombre de produits</th>
                <th class="text-right">Poids Net (Kg)</th>
                <th class="t-actions text-center">PDF</th>
                <th class="t-actions text-center">Supprimer</th>
            </tr>
            </thead>
            <tbody>
			<?php
			foreach ($liste as $bon) {

				$client = $tiersManager->getTiers($bon->getId_tiers());
				if (!$client instanceof Tiers) { $client = new Tiers([]); } // DEBUG

                $pdfOK = file_exists(__CBO_ROOT_PATH__.'/gescom/bon_transfert/'.$bon->getNum_bon_transfert().'.pdf');

				?>
                <tr>
					<?php if ($utilisateur->isDev()) { ?>
                        <td class="w-50pxpx text-10"><code><?php echo $bon->getId();?></code></td>
					<?php } ?>
                    <td><?php echo Outils::dateSqlToFr($bon->getDate()); ?></td>
                    <td><?php echo $bon->getNum_bon_transfert(); ?></td>
                    <td><?php echo $client->getNom(); ?></td>
                    <td class="text-center"><?php echo $bon->getNb_palettes(); ?></td>
                    <td class="text-center"><?php echo $bon->getNb_produits(); ?></td>
                    <td class="text-right"><?php echo number_format($bon->getPoids(),3,'.', ' '); ?></td>

                    <td class="t-actions text-center">
                        <?php if ($pdfOK) { ?>
                            <a href="<?php echo __CBO_ROOT_URL__.'gescom/bon_transfert/'.$bon->getNum_bon_transfert().'.pdf'; ?>" target="_blank" class="btn btn-sm btn-secondary"><i class="fa fa-fw fa-file-pdf"></i></a>
                        <?php } else { ?>
                            <a href="#" class="btn btn-sm btn-secondary disabled" disabled title="Fichier introuvable !"><i class="fa fa-fw fa-exclamation-triangle"></i></a>
                        <?php } ?>
                    </td>

                    <td class="t-actions text-center"><button type="button" class="btn btn-sm btn-danger btnSupprBon" data-id="<?php echo $bon->getId(); ?>"><i class="fa fa-fw fa-trash-alt"></i></button></td>

                </tr>
			<?php }


			?>
            </tbody>
        </table>
	<?php }

	// Pagination (aJax)
	if (isset($pagination)) {
		// Pagination bas de page, verbose...
		$pagination->setVerbose_pagination(1);
		$pagination->setVerbose_position('right');
		$pagination->setNature_resultats('BL');
		$pagination->setNb_apres(2);
		$pagination->setNb_avant(2);

		echo ($pagination->getPaginationHtml());
	} // FIN test pagination

	exit;

} // FIN mode


// Supprime un bon de transfert
function modeSupprBonTransfert() {

    global $cnx;

    $bonTransfertManager = new BonsTransfertsManager($cnx);
	$logsManager = new LogManager($cnx);

	$id_bon = isset($_REQUEST['id_bon']) ? intval($_REQUEST['id_bon']) : 0;
	if ($id_bon == 0) { exit('-1'); }

	$bon = $bonTransfertManager->getBonTransfert($id_bon);
	if (!$bon instanceof BonTransfert) { exit('-2'); }

	$bon->setSupprime(1);
	$res = $bonTransfertManager->saveBonTransfert($bon);

	echo $res ? 1 : 0;
	$logTexte = $res ? 'S' : 'Echec de la s';
	$logTexte.='uppression (flag) du bon de transfert #'.$id_bon;
	$logtype = $res ? 'info' : 'danger';
	$log = new Log([]);
	$log->setLog_texte($logTexte);
	$log->setLog_type($logtype);
	$logsManager->saveLog($log);

	exit;

} // FIN mode

// Ajoute une ligne manuelle à un BL en création Bo (non issue d'une compo)
function modeAddLigneBl() {

	global $cnx;
	$logsManager = new LogManager($cnx);
    $taxesManager = new TaxesManager($cnx);

	$id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) : 0;
	$id_clt = isset($_REQUEST['id_clt']) ? intval($_REQUEST['id_clt']) : 0;
	$nom = isset($_REQUEST['nom']) ? trim($_REQUEST['nom']) : '';
	$num_lot = isset($_REQUEST['lot']) ? trim($_REQUEST['lot']) : '';
	$nb_colis = isset($_REQUEST['nb_colis']) ? intval($_REQUEST['nb_colis']) : 0;
	$num_palette = isset($_REQUEST['palette']) ? intval($_REQUEST['palette']) : 0;
	$qte = isset($_REQUEST['qte']) ? floatval($_REQUEST['qte']) : 1;
	$poids = isset($_REQUEST['poids']) ? floatval($_REQUEST['poids']) : 0;
	$pu_ht = isset($_REQUEST['pu_ht']) ? floatval($_REQUEST['pu_ht']) : 0;
	$id_taxe = isset($_REQUEST['id_taxe']) ? intval($_REQUEST['id_taxe']) : 0;

	$id_palette = 0;
	$id_lot = 0;

	if ($id_bl == 0) { exit('ERREUR : BL non trouvé !'); }
	if ($nom == '') { exit('ERREUR : Libellé absent !'); }
    if ($num_palette > 0 && $id_clt == 0) { exit('ERREUR : Sélectionnez un client à livrer pour identifier la palette '.$num_palette.'.');}

	// On recherche la palette par son numéro et le client
    if ($num_palette > 0) {
		$palettesManager = new PalettesManager($cnx);
		$palettes = $palettesManager->getListePalettes(['numero' => $num_palette, 'id_client' => $id_clt, 'statuts' => '0,1,2']);
		if (empty($palettes)) { exit('ERREUR : Aucune palette '.$num_palette.' non expédiée n\'a été trouvée pour le client livré.'); }
		if (count($palettes) > 1) { exit('ERREUR : Plusieurs palettes '.$num_palette.' non expédiées ont été trouvées pour le client livré.'); }
		$id_palette = $palettes[0]->getId();
    } // FIN recherche palette

    // On recherche le numéro de lot
    if ($num_lot != '') {
		$lotsManager = new LotManager($cnx);
		$lot = $lotsManager->getLotFromNumero($num_lot);
		if (!$lot instanceof Lot) { exit('ERREUR : Aucun lot '.$num_lot.' trouvé !');}
		$id_lot = $lot->getId();
    } // FIN recherche lot

    // On récupère le taux de TVA
    $tva = 0;
    if ($id_taxe > 0) {
		$taxe = $taxesManager->getTaxe($id_taxe);
		if ($taxe instanceof Taxe) {
		    $tva = $taxe->getTaux();
        }
    } // FIN récup taux TVA

	$blsManagers = new BlManager($cnx);

	$ligne = new BlLigne([]);
	$ligne->setId_bl($id_bl);
	$ligne->setId_compo(0);
	$ligne->setId_palette($id_palette);
	$ligne->setId_produit(0);
	$ligne->setId_produit_bl(0);
	$ligne->setId_lot($id_lot);
	$ligne->setNumlot($num_lot);
	$ligne->setPoids($poids);
	$ligne->setNb_colis($nb_colis);
	$ligne->setQte($qte);
	$ligne->setDate_add(date('Y-m-d H:i:s'));
	$ligne->setSupprime(0);
	$ligne->setPu_ht($pu_ht);
	$ligne->setLibelle($nom);
	$ligne->setTva($tva);
	$id_ligne = $blsManagers->saveBlLigne($ligne);
	echo (int)$id_ligne > 0 ? 1 : 0;

    $log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte('Création d\'une ligne manuelle hors produit (#'.$id_ligne.') pour le BL #'.$id_bl);
	$logsManager->saveLog($log);

	exit;
} // FIN mode


// Imprimme l'étiquette d'expédition du BL
function modeImprimEtiquetteBl() {

	global $cnx, $blsManagers;

	$id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) : 0;
	if ($id_bl == 0) { exit('ERREUR : BL non trouvé !'); }

	$bl = $blsManagers->getBl($id_bl);
	if (!$bl instanceof Bl) { exit('ERREUR : BL '.$id_bl.' non instancié !');}

    $tiersManager = new TiersManager($cnx);
    $client = $tiersManager->getTiers($bl->getId_tiers_livraison());
    if (!$client instanceof Tiers) { $client = new Tiers([]); }

    $adressesManager = new AdresseManager($cnx);
    $adresseObj = $adressesManager->getAdresse($bl->getId_adresse_livraison());
    if (!$adresseObj instanceof Adresse) { $adresseObj = new Adresse([]); }

    $transporteur = $tiersManager->getTiers($bl->getId_tiers_transporteur());
	if (!$transporteur instanceof Tiers) { $transporteur = new Tiers([]); }

	$adresse= $adresseObj->getNom() == '' ? $client->getNom() : $adresseObj->getNom();
	$adresse.='<br>'.$adresseObj->getAdresse_1().' '. $adresseObj->getAdresse_2();
	$adresse.='<br>'.$adresseObj->getCp().' '.$adresseObj->getVille().' - '.$adresseObj->getNom_pays();

	// On récupère pour le BL chaque palette avec le total poids et colis
    $palettesBl = $blsManagers->getPalettesBl($bl);
    if (!is_array($palettesBl) || empty($palettesBl)) { exit('ERREUR AUCUNE PALETTE'); }

    // 1 = Frais / 2 = Congelé / 0 = les deux
    $fraisOuCongele = $blsManagers->isFraisOuCongele($bl);


    $header = '  <html>
    <head>
        <style type="text/css" media="print">
            @page {
                size: auto;   /* auto is the initial value */
                margin: 0;  /* this affects the margin in the printer settings */
                margin-top: 0.5cm;
            }

            @page :first {
                margin-top: 0;
            }
            * {
                font-family: Calibri, "Trebuchet MS", sans-serif;
            }
            body {
                text-align: center;
            }

            .page {
                padding: 0;
                margin: 0;
                page-break-after: always;
                height: 15cm;
                max-height: 15cm;
                min-height: 15cm;
                width: 10cm;
                max-width: 10cm;
                min-width: 10cm;
            }


            .conteneur {
                writing-mode: vertical-rl;
                margin:0.25cm;
                height: 14.5cm;
                max-height: 14.5cm;
                min-height: 14.5cm;
                width: 9.5cm;
                max-width: 9.5cm;
                min-width: 9cm;
                text-align: center;
                border: 2px solid #000;
                font-weight:bold;
                page-break-inside: avoid;
            }

            table {
                margin-right: 0.2cm;
                margin-top: 0.2cm;
                border-collapse: collapse;
                height: 13.8cm;
            }

            table.table {
                border:1px solid #000;
                border-radius: 5px;
            }
            table.table td.titre {
                border-left: 1px solid #555;
                border-right: 1px solid #555;
                font-size: 10pt;
            }
            table td p {
                font-size: 8pt;
                padding:2px;
            }

            .text-center  { text-align: center; }
            p,h1,h2,h3 { margin:0; }
            h1 {
                font-size: 14pt;
            }
        </style>
    </head>
    <body>';
    $footer = ' </body>
    </html>';
    $headerTable = '<table class="table">
                    <tr>
                        <td colspan="2" class="titre text-center">Expéditeur</td>
                    </tr>
                    <tr>
                        <td>
                            <h1>PROFIL EXPORT SAS</h1>
                            <p>ZI.Montbertrand<br>24 rue du Claret<br>38230 CHARVIEU CHAVAGNEUX<br>Tél : 04.37.65.71.71<br>Fax : 04.37.65.71.73<br>info@profilexport.fr</p>
                        </td>
                        <td>
                            <p style="font-size: 12pt;"><b>Atelier :</b></p>
                            <p>ZI.Montbertrand<br>24 rue du Claret<br>38230 CHARVIEU CHAVAGNEUX<br>Fax : 04.72.23.03.18 (Commande)<br>Agrément : FR-38.085.003-CE</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="titre text-center">Destinataire</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="text-center">
                            <p style="font-size: 13pt;"><b>'.$adresse.'</b></p>
                        </td>
                    </tr>
                </table>
                <p>Bon de livraison N°'.$bl->getNum_bl().' du '.Outils::dateSqlToFr($bl->getDate()).'</p>';
    $debutPage = '<div class="page">
            <div class="conteneur">';
    $finPage = '</div></div>';

    echo $header;

    // Première page du total (étiquette enveloppe)
	echo $debutPage;
	echo $headerTable;
	$palettes_total = count($palettesBl);
	$s = $palettes_total > 1 ? 's' : '';
	$poids_total = 0;
	$colis_total = 0;
	foreach ($palettesBl as $datas) {
		$poids_total+=(float)$datas['poids'];
		$colis_total+=(float)$datas['colis'];
	}
	echo '<p>';
	echo $palettes_total.' palette'.$s;



	echo $bl->getDate_livraison() != '' ? ' - Date de livraison :'.Outils::dateSqlToFr($bl->getDate_livraison()).'</p>' : '';
	?>
    <table>
        <tr>
            <td class="text-center">
                <p style="font-size:14pt;"><b>
                    Nombre de colis : <?php echo $colis_total; ?><br>
                    Poids : <?php echo number_format($poids_total,2, '.', ' '); ?> Kg<br>
                    Transporteur : <?php echo $transporteur->getNom(); ?>
                    </b></p>

            </td>
        </tr>
    </table>
	<?php

	echo $finPage;


    // Pages pour chaque palette
    foreach ($palettesBl as $datas) {
		echo $debutPage;
		echo $headerTable;
		echo '<p>Palette N°'.$datas['numero'];

		$fraisOuCongelePalette = $blsManagers->isFraisOuCongele($bl, $datas['id_palette']);


		echo $bl->getDate_livraison() != '' ? ' - Date de livraison :'.Outils::dateSqlToFr($bl->getDate_livraison()).'</p>' : '';
		echo '</p>';
		?>
        <table>
            <tr>
                <td class="text-center">
                    <p style="font-size:12pt;">Poids : <?php echo number_format($datas['poids'],2, '.', ' '); ?> Kg<br>
                        Nombre de colis : <?php echo $datas['colis']; ?><br>
                        Transporteur : <?php echo $transporteur->getNom(); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
		echo $finPage;
	}
    echo $footer;

	exit;
} // FIN mode

// Formulaire des données précises de la ligne de produit à ajouter au BL manuel (modale ajout produit)
function modeFormProduitsBlManuel() {
    global $cnx, $utilisateur;

    $id         = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $type       = isset($_REQUEST['type']) ? trim(strtolower($_REQUEST['type'])) : '';
    $typesOK    = ['', 'stk', 'neg'];
    if (!in_array($type, $typesOK)) { exit('ERREUR TYPE INCONNU'); }

	$palettesManager = new PalettesManager($cnx);
    $produitsManager = new ProduitManager($cnx);
	$negoceManager = new LotNegoceManager($cnx);

    if ($type == "stk") {
		$compo = $palettesManager->getComposition($id);
		if (!$compo instanceof PaletteComposition) {
			exit('ERREUR COMPO #'.$id.' NON TROUVEE (STK)');
		}
        $compo = $produitsManager->getNumLotCompo($compo);
    } else if ($type == 'neg') {
        $neg = $negoceManager->getNegoceProduit($id);
		if (!$neg instanceof NegoceProduit) {
			exit('ERREUR NEGPDT #'.$id.' NON TROUVEE');
		}
    } else {
        $type = 'pdt';
    }
    ?>
    <input type="hidden" name="id_item" value="<?php echo $id; ?>" />
    <input type="hidden" name="type_item" value="<?php echo $type; ?>" />
    <div class="col-12 gris-5 text-13">
        <?php 
        if ($type == 'stk') {
            echo 'Produit en stock pour '.$compo->getNom_client();
            $devTxt = 'Compo';
		} else if ($type == 'neg') {
			echo 'Produit de négoce';
			$devTxt = 'Pdt negoce';
		} else {
            echo 'Produit hors stock';
			$devTxt = 'Hors stock';
		}
        ?>
    </div>
	<div class="col">
    <div class="alert alert-secondary">
        <p class="gris-5 mb-0"><i class="fa fa-caret-down mr-1 gris-9"></i> <?php
            echo $id > 0 ? 'Validez' : 'Précisez'; echo ' les détails de la ligne :';
            echo $utilisateur->isDev() && $id > 0 ? '<span class="text-11 gris-9 float-right"><i class="fa fa-user-secret mr-1"></i> '.$devTxt.' #'.$id.'</span>' : '';
        ?></p>
        <div class="row">
            <div class="col-3 pr-1">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Lot</span>
                    </div>					
                    <input type="text" class="form-control" id="id_pdt_negoce" placeholder="N° de lot" data-id-negoce="<?php                           
                        echo isset($neg) ? $neg->getId_lot_pdt_negoce() : '';
                    ?>" name="num_lot" value="<?php
                        echo isset($compo) ? $compo->getNum_lot() : '';
                        echo isset($neg) ? $neg->getNum_lot() : '';
                    ?>" />
                </div>
            </div>
            <div class="col-3 pr-1">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">N° palette</span>
                    </div>
                    <input type="text" class="form-control text-center" placeholder="" name="num_palette" value="<?php
                        echo isset($compo) ? $compo->getNumero_palette() : '';
                        echo isset($neg) ? $neg->getNumero_palette() : '';
                    ?>" />
                </div>
            </div>
            <div class="col-2 pr-1">
                <div class="input-group">
                    <input type="text" class="form-control text-center inputCartons" placeholder="" name="nb_colis" value="<?php
                        echo isset($compo) ? $compo->getNb_colis() : '';
                        echo isset($neg) ? $neg->getNb_cartons() : '';
                    ?>" />
                    <div class="input-group-append">
                        <span class="input-group-text"><?php echo $type == 'neg' ? 'cartons' : 'colis'; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-2 pr-1">
                <div class="input-group">
                    <input type="text" class="form-control text-center inputQuantite" placeholder="" name="quantite" value="<?php
                        echo isset($compo) ? $compo->getQuantite() : '';
                        echo isset($neg) ? $neg->getQuantite() : '';
                    ?>" />
                    <div class="input-group-append">
                        <span class="input-group-text">Pièce</span>
                    </div>
                </div>
            </div>
            <div class="col-2">
                <div class="input-group">
                    <input type="text" class="form-control text-right" placeholder="0.000" name="poids" value="<?php
                        echo isset($compo) ? number_format($compo->getPoids(),3,'.', ' ') : '';
                        echo isset($neg) ? number_format($neg->getPoids(),3,'.', ' ') : '';
                    ?>" />
                    <div class="input-group-append">
                        <span class="input-group-text">Kg</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <?php
    exit;
} // FIN mode

// Rajoute un produit ou une compo au BL (admin)
function modeAddLigneProduitBl() {

	global $cnx, $blsManagers, $utilisateur;

	$produitsManager = new ProduitManager($cnx);
	$palettesManager = new PalettesManager($cnx);
	$negoceManager   = new LotNegoceManager($cnx);
	$lotsManager     = new LotManager($cnx);
	$logsManager     = new LogManager($cnx);

	$id_bl       = isset($_REQUEST['id_bl'])        ? intval($_REQUEST['id_bl'])                : 0;
	$id_client   = isset($_REQUEST['id_client'])    ? intval($_REQUEST['id_client'])            : 0;
	$id_item     = isset($_REQUEST['id_item'])      ? intval($_REQUEST['id_item'])              : 0;
	$type_item   = isset($_REQUEST['type_item'])    ? trim(strtolower($_REQUEST['type_item']))  : '';
	$num_lot     = isset($_REQUEST['num_lot'])      ? trim(strtoupper($_REQUEST['num_lot']))    : '';
	$num_pal     = isset($_REQUEST['num_palette'])  ? intval($_REQUEST['num_palette'])          : 0;
	$nb_colis    = isset($_REQUEST['nb_colis'])     ? intval($_REQUEST['nb_colis'])             : 0;
	$poids       = isset($_REQUEST['poids'])        ? floatval($_REQUEST['poids'])              : 0.0;
    $id_bl_ligne = isset($_REQUEST['id_bl_ligne'])  ? intval($_REQUEST['id_bl_ligne'])          : 0;
	$qte_web     = isset($_REQUEST['quantite'])      ? intval($_REQUEST['quantite'])              : 1;
	$id_pdt_negoce =  isset($_REQUEST['id_pdt_negoce'])  ? intval($_REQUEST['id_pdt_negoce'])  : 0;


	// Gestion des erreurs
	$typesOK    = ['pdt', 'stk', 'neg'];
	if (!in_array($type_item, $typesOK)) { exit('ERREUR TYPE INCONNU'); }
	if ($id_item == 0) { exit('ERREUR ID VIDE ('.$type_item.')');}
	if ($nb_colis == 0 && $poids == 0) { exit('ERREUR POIDS ET COLIS NULS');}
	$bl = $blsManagers->getBl($id_bl);
	if (!$bl instanceof Bl) { exit('ERREUR BL #'.$id_bl.' INCONNU'); }

	$codeLog = '';

	// Stock (compo) -----------------------------------------------------------------------------
	if ($type_item == 'stk') {
	    $codeLog = strtoupper($type_item);
		$id_compo = $id_item;
        $compo = $palettesManager->getComposition($id_compo);
        if (!$compo instanceof PaletteComposition) { exit('ERREUR INST OBJ COMPO #'.$id_compo); }

		// On vérifie qu'on a pas déjà cette compo dans un BL !
		if ($blsManagers->checkCompoDejaBl($id_compo)) { exit('2'); } // On renvoie malgré tout un retour positif

        // On crée une ligne de BL depuis la compo (via manager)
        if ($id_bl_ligne == 0) {
			$ligne = $blsManagers->getDonneesLigneBl($id_compo);
			if (!$ligne instanceof BlLigne) { exit('ERREUR CREATION LIGNE BL DEPUIS COMPO #'.$id_compo); }
        } else {
			$ligneTmp =  $blsManagers->getDonneesLigneBl($id_compo);
			if (!$ligneTmp instanceof BlLigne) { exit('ERREUR CREATION LIGNE BL DEPUIS COMPO #'.$id_compo); }
            $ligne = $blsManagers->getBlLigne($id_bl_ligne);
			if (!$ligne instanceof BlLigne) { exit('ERREUR INSTANCIATION LIGNE BL #'.$id_bl_ligne.' VIA WEB/COMPO #'.$id_compo); }
			$ligne->setId_compo($id_compo);
            $ligne->setId_produit($ligneTmp->getId_produit());
            $ligne->setId_palette($ligneTmp->getId_palette());
            $ligne->setNum_palette($ligneTmp->getNum_palette());
            $ligne->setVendu_piece($ligneTmp->getVendu_piece());
            $ligne->setNumlot($ligneTmp->getNumlot());
            $ligne->setId_lot($ligneTmp->getId_lot());
            $ligne->setId_pays($ligneTmp->getId_pays());
            $ligne->setLibelle($ligneTmp->getLibelle());
			$ligne->setQte($qte_web);
        }

		// Si le numLot n'est pas le même (s'il est vide, on conserve celui de la compo)
		if ($num_lot != '' && $num_lot != $ligne->getNumlot()) {
            // On retire le quantième pour comparer le numéro de lot uniquement
			$quantieme = '';
			if (preg_match('/^[0-9]*$/', (substr($num_lot,-3))) && preg_match('^([0-9]{5,})([A-Z]{2,})([0-9]{3})$', $num_lot)) {
				$quantieme = substr($num_lot,-3);
                $num_lot_avec_quantieme = $num_lot;
				$num_lot= substr($num_lot,0,-3);
			}
			// On recherche si ce numéro de lot correspond à un lot existant
            $lot = $lotsManager->getLotFromNumero($num_lot);
            if ($lot instanceof Lot) { // lot trouvé, on rattache l'id du lot correspondant
				$id_lot = $lot->getId();
            // Lot non trouvé : création + quantième si il est fourni
            } else {
				$especesManager = new ProduitEspecesManager($cnx);
                $lot = new Lot([]);
                $lot->setNumlot($num_lot);
				$lot->setDate_add(date('Y-m-d H:i:s'));
				$lot->setVisible(0);
				$lot->setId_fournisseur(0);
				$lot->setId_espece($especesManager->getIdEspeceViande());
				$lot->setComposition(1);
                // On récupère l'id du lot créé
				$id_lot =  $lotsManager->saveLot($lot);
				if (!$id_lot || intval($id_lot) == 0) { exit('ERREUR CREATION DU LOT '.$num_lot); }

				$log = new Log();
				$log->setLog_type('info');
				$log->setLog_texte("Création auto du lot #".$id_lot. " via ajout produit BL Manuel #".$bl->getId()." et saisie numéro de lot libre ".$num_lot);
				$logsManager->saveLog($log);
				$codeLog.='/CREATE_LOT#'.$id_lot;

				// On rattache le quantième spécifié si on en a un (on vérifie aussi qu'on a bien un lot de type iprex et pas un lot de négoce
				// On vérifie si le numéro de lot semble bien être un numéro de lot iprex et pas un lot de négoce (commence par 6 chiffres ou plus et termine par 3 ou 4 lettres)
                $isLotIprex = preg_match('/^([0-9]{6,})([A-Z]{3,4})$/', strtoupper($num_lot));
				if (intval($quantieme) > 0 && $isLotIprex) {
					if (!$lotsManager->addQuantiemeIfNotExist($id_lot, $quantieme)) { exit('ERREUR RATTACHEMENT DU QUANTIEME '.$quantieme.' AU LOT CREE #'.$id_lot);}
					$log = new Log();
					$log->setLog_type('info');
					$log->setLog_texte("Rattachement du quantième ".$quantieme." au lot #".$id_lot." via ajout produit BL Manuel #".$bl->getId()." et saisie numéro de lot libre ".$num_lot);
					$logsManager->saveLog($log);
					$codeLog.='/ADD_QUANTIEME'.$quantieme;
                }
				// On rajoute un commentaire dans le lot créé
                $commentairesManager = new CommentairesManager($cnx);
				$commentaire = new Commentaire([]);
				$commentaire->setId_lot($id_lot);
				$commentaire->setCommentaire("Lot créé via BL manuel " . $bl->getNum_bl());
				$commentaire->setId_user($utilisateur->getId());
				$commentaire->setDate(date('Y-m-d H:i:s'));
				$commentairesManager->saveCommentaire($commentaire);

            } // FIn test lot trouvé

            // Si on a changé le numéro de lot, que l'on ai créé un nouveau lot ou choisi un lot existant, on modifie le lot dans le lot_pdt_froid, sinon on perd la traçabilité au niveau du lot
			$froidManager = new FroidManager($cnx);
            $pdt_froid = $froidManager->getFroidProduitObjetByIdLotPdtFroid($compo->getId_lot_pdt_froid());
            if (!$pdt_froid instanceof FroidProduit) { exit('ERREUR INST OBJ LOTPDTFOID #'.$compo->getId_lot_pdt_froid()); }
			$oldLotPdtFroid = $pdt_froid->getId_lot();
			$pdt_froid->setId_lot($id_lot);
            if (!$froidManager->saveFroidProduit($pdt_froid)) { exit('ERREUR SAVE FROID PRODUIT #'.$pdt_froid->getId_lot_pdt_froid()); }

			$log = new Log();
			$log->setLog_type('info');
			$log->setLog_texte("Ajout produit BL manuel (STK) Modification id_lot dans id_lot_pdt froid #".$pdt_froid->getId_lot_pdt_froid()." : Ancien ".$oldLotPdtFroid." changé pour ".$id_lot);
			$logsManager->saveLog($log);
			$codeLog.='/IDLOTPDTFROID'.$id_lot;

		} else {
		    $id_lot = $ligne->getId_lot();
        } // FIN test numéro de lot différent de la compo

		// Si la palette n'est pas la même (si elle est vide, on conserve celle de la compo)
		if ($num_pal > 0 && $num_pal != $compo->getNumero_palette()) {

			// On recherche l'id_palette correspondant à ce numéro de palette - et par rapport au client du BL
			$id_pal = $palettesManager->getIdPaletteByNumeroAndClient($num_pal, $id_client);

			// Si on a bien une palette identifiée
			if (intval($id_pal) > 0) {
				$id_palette = $id_pal;

			// Sinon, on crée la palette
            } else {

			    $palette = new Palette();
			    $palette->setNumero($num_pal);
			    $palette->setDate(date('Y-m-d h:i:s'));
				$palette->setId_user($utilisateur->getId());
				$palette->setStatut(0);
				$palette->setId_poids_palette(0);
				$palette->setId_client(0);
				$palette->setScan_frais(0);
				$palette->setSupprime(0);

				$id_palette = $palettesManager->savePalette($palette);
				if (intval($id_palette) == 0) { exit('ERREUR CREATION PALETTE NUMERO ' . $num_pal); }

				$log = new Log();
				$log->setLog_type('info');
				$log->setLog_texte("Création auto palette #".$id_palette. " via ajout produit BL Manuel #".$bl->getId()." et saisie numéro de palette ".$num_pal." non trouvé pour client #".$id_client);
				$logsManager->saveLog($log);
				$codeLog.='/CREATE_PAL'.$id_palette;

            } // FIN test palette trouvée/créee

            // Que l'on ai créé une nouvelle palette ou changé de palette, on change l'id_palette de la compo pour que la palette soit visibile en admin (si pas de compo, pas de palette, et si pas de palette, pas de palais) et garder une cohérance de traça si changement de palette. En gros, si dans un BL manuel on change de palette, on change corrige pour le stock, ça évite d'avoir n'importe quoi et de détourner des données hors des rail...
            $oldPaletteCompo = $compo->getId_palette();
            $compo->setId_palette($id_palette);
			if (!$palettesManager->savePaletteComposition($compo)) { exit('ERREUR SAVE COMPO CHANGMEMENT DE PALETTE');}
			$log = new Log();
			$log->setLog_type('info');
			$log->setLog_texte("Changement id_palette de #".$oldPaletteCompo." à #".$id_palette." sur la compo #".$compo->getId()." suite modif manuelle numéro_palette dans ajout produit BL manuel");
			$logsManager->saveLog($log);
			$codeLog.='/IDPAL_COMPO'.$oldPaletteCompo.'>'.$id_palette;


		} else {

			$id_palette = $compo->getId_palette();
		} // FIN test numéro de palette différent de la compo

		$num_lot_ligne = isset($num_lot_avec_quantieme) ? $num_lot_avec_quantieme : $num_lot;

		// On rend les données instanciées persistantes
		$ligne->setId_produit($ligne->getId_produit());
		$ligne->setId_palette($id_palette);
		$ligne->setNumlot($num_lot_ligne);
		$ligne->setId_lot($id_lot);
		$ligne->setPoids($poids);
		$ligne->setNb_colis($nb_colis);
		$ligne->setId_compo($id_compo);;
		$ligne->setNum_palette($num_pal);
		$ligne->setId_bl($id_bl);
		$ligne->setId_produit_bl(0);
		if ($id_bl_ligne == 0) {
			$ligne->setPu_ht($ligne->getPu_ht());
			$ligne->setTva($ligne->getTva());
			$ligne->setQte(1);
			$ligne->setSupprime(0);
			$ligne->setDate_add(date('Y-m-d H:i:s'));
		}


		if ($id_bl_ligne == 0) {
			$id_ligne = $blsManagers->saveBlLigne($ligne);
			if ($id_ligne == false) { exit('ERREUR SAVE LIGNE BL (FALSE)'); }
			if (intval($id_ligne) == 0) { exit('ERREUR SAVE LIGNE BL (0)'); }
		} else {
			if (!$blsManagers->saveBlLigne($ligne)) {
				exit('ERREUR SAVE LIGNE BL WEB UPD (FALSE)');
            }
			$id_ligne = $id_bl_ligne;
        }

        $log = new Log();
        $log->setLog_type('info');
        $log->setLog_texte("Création ligne #".$id_ligne." sur BL manuel #".$bl->getId()." (".$codeLog.")");
        $logsManager->saveLog($log);
		echo '1';
        exit;

	// Négoce -----------------------------------------------------------------------------
    } else if ($type_item == 'neg') {

		$codeLog = strtoupper($type_item);
		
        $id_pdt_neg = $id_item;
		$pdt_neg = $negoceManager->getNegoceProduit($id_pdt_neg);		

		if (!$pdt_neg instanceof NegoceProduit) { exit('ERREUR INST OBJ NEGOCEPDT #'.$id_pdt_neg); }

		if ($id_bl_ligne == 0) {
			// On crée une ligne de BL depuis le produit de négoce (via manager)
			$ligne = $blsManagers->getDonneesLigneBlByNegoce($id_pdt_neg);	
			if (!$ligne instanceof BlLigne) { exit('ERREUR CREATION LIGNE BL DEPUIS PDT_NEGOCE #'.$id_pdt_neg); }
		} else {
			$ligneTmp = $blsManagers->getDonneesLigneBlByNegoce($id_pdt_neg);
			if (!$ligneTmp instanceof BlLigne) { exit('ERREUR CREATION LIGNE BL DEPUIS PDT_NEGOCE #'.$id_pdt_neg); }
			$ligne = $blsManagers->getBlLigne($id_bl_ligne);
			if (!$ligne instanceof BlLigne) { exit('ERREUR INSTANCIATION LIGNE BL #'.$id_bl_ligne.' VIA WEB/NEG #'.$id_pdt_neg); }
			$ligne->setId_compo(0);
			$ligne->setId_pdt_negoce($id_pdt_negoce);
			$ligne->setId_produit($ligneTmp->getId_produit());
			$ligne->setId_palette($ligneTmp->getId_palette());
			$ligne->setNum_palette($ligneTmp->getNum_palette());
			$ligne->setVendu_piece($ligneTmp->getVendu_piece());
			$ligne->setNum_lot($ligneTmp->getNumlot());
			$ligne->setId_lot($ligneTmp->getId_lot());
			$ligne->setId_pays($ligneTmp->getId_pays());
			$ligne->setLibelle($ligneTmp->getLibelle());
		
        }


		if ($num_lot != '' && $num_lot != $ligne->getNumlot()) {
			$ligne->setNumlot($num_lot);

			// On change le numlot dans le pdt neg
            $pdt_neg->setNum_lot($num_lot);
            if (!$negoceManager->saveNegoceProduit($pdt_neg)) { exit('ERREUR SAVE PDT NEG CHANGEMENT NUM LOT'); }
			$log = new Log();
			$log->setLog_type('info');
			$log->setLog_texte("Changement du numéro de lot du produit de négoce #".$id_pdt_neg." lors de l'ajout ligne BL manuel #".$bl->getId());
			$logsManager->saveLog($log);
		}

		// Si la palette n'est pas la même (si elle est vide, on conserve celle de la compo)
		if ($num_pal > 0 && $num_pal != $ligne->getNumero_palette()) {

			// On recherche l'id_palette correspondant à ce numéro de palette - et par rapport au client du BL
			$id_pal = $palettesManager->getIdPaletteByNumeroAndClient($num_pal, $id_client);

			// Si on a bien une palette identifiée
			if ($id_pal > 0) {
				$id_palette = $id_pal;

			// Sinon, on crée la palette
			} else {

				$palette = new Palette();
				$palette->setNumero($num_pal);
				$palette->setDate(date('Y-m-d h:i:s'));
				$palette->setId_user($utilisateur->getId());
				$palette->setStatut(0);
				$palette->setId_poids_palette(0);
				$palette->setId_client(0);
				$palette->setScan_frais(0);
				$palette->setSupprime(0);

				$id_palette = $palettesManager->savePalette($palette);
				if (intval($id_palette) == 0) { exit('ERREUR CREATION PALETTE NUMERO ' . $num_pal); }

				$log = new Log();
				$log->setLog_type('info');
				$log->setLog_texte("Création auto palette #".$id_palette. " via ajout produit BL Manuel #".$bl->getId()." et saisie numéro de palette ".$num_pal." non trouvé pour client #".$id_client);
				$logsManager->saveLog($log);
				$codeLog.='/CREATE_PAL'.$id_palette;

			} // FIN test palette trouvée/créee

			// Que l'on ai créé une nouvelle palette ou changé de palette, on change l'id_palette du pdt negoce pour que la palette soit visibile en admin
			$oldPalettePdtNeg = $pdt_neg->getId_palette();			
			$pdt_neg->setId_palette($id_palette);

			if (!$negoceManager->saveNegoceProduit($pdt_neg)) { exit('ERREUR SAVE PDT NEGOCE CHANGMEMENT DE PALETTE');}
			$log = new Log();
			$log->setLog_type('info');
			$log->setLog_texte("Changement id_palette de #".$oldPalettePdtNeg." à #".$id_palette." sur le pdt negoce #".$pdt_neg->getId_lot_pdt_negoce()." suite modif manuelle numéro_palette dans ajout produit BL manuel");
			$logsManager->saveLog($log);
			$codeLog.='/IDPAL_PDTNEG'.$oldPalettePdtNeg.'>'.$id_palette;

		} else {
			$id_palette = $pdt_neg->getId_palette();
		} // FIN test numéro de palette différent du pdt neg

		// On rend les données instanciées persistantes
		$ligne->setId_produit($ligne->getId_produit());
		$ligne->setId_palette($id_palette);
		$ligne->setNumlot($num_lot);
		$ligne->setId_lot(0);
		$ligne->setPoids($poids);
		$ligne->setNb_colis($nb_colis);
		$ligne->setId_compo(0);
		$ligne->setNum_palette($num_pal);
		$ligne->setId_bl($id_bl);
		$ligne->setId_produit_bl(0);		
		$ligne->setId_pdt_negoce($id_pdt_negoce);
		
		if ($id_bl_ligne == 0) {
			$ligne->setPu_ht($ligne->getPu_ht());
			$ligne->setTva($ligne->getTva());
			$ligne->setQte($qte_web);
			$ligne->setSupprime(0);
			$ligne->setDate_add(date('Y-m-d H:i:s'));
		} else {
			$ligne->setQte($qte_web);
		}


		$id_ligne = $blsManagers->saveBlLigne($ligne);


		if ($id_ligne == false) { exit('ERREUR SAVE LIGNE BL (FALSE)'); }
		if (intval($id_ligne) == 0) { exit('ERREUR SAVE LIGNE BL (0)'); }

		$log = new Log();
		$log->setLog_type('info');
		$log->setLog_texte("Création ligne #".$id_ligne." sur BL manuel #".$bl->getId()." (".$codeLog.")");
		$logsManager->saveLog($log);
		echo '1';
		exit;

	// Produit (hors stock) -----------------------------------------------------------------------------
    } else {
		$tarifsManager = new TarifsManager($cnx);
		$taxesManager = new TaxesManager($cnx);
		$codeLog = 'HORSTOCK';
	    $pdt = $produitsManager->getProduit($id_item);
		if (!$pdt instanceof Produit) { exit('ERREUR INST OBJ PRODUIT #'.$id_item); }
        $id_produit = $id_item;

		// Création lot si besoin
		// Si le numLot n'est pas le même (s'il est vide, on aura pas de traça, le client a été prévenu en JS)
		if ($num_lot != '') {
			// On retire le quantième pour comparer le numéro de lot uniquement
			$quantieme = '';
			if (preg_match('/^[0-9]*$/', (substr($num_lot,-3))) && preg_match('^([0-9]{5,})([A-Z]{2,})([0-9]{3})$', $num_lot)) {
				$quantieme = substr($num_lot,-3);
                $num_lot_avec_quantieme = $num_lot;
				$num_lot= substr($num_lot,0,-3);
			}
			// On recherche si ce numéro de lot correspond à un lot existant
			$lot = $lotsManager->getLotFromNumero($num_lot);
			if ($lot instanceof Lot) { // lot trouvé, on rattache l'id du lot correspondant
				$id_lot = $lot->getId();
				// Lot non trouvé : création + quantième si il est fourni
			} else {
				$especesManager = new ProduitEspecesManager($cnx);
				$lot = new Lot([]);
				$lot->setNumlot($num_lot);
				$lot->setDate_add(date('Y-m-d H:i:s'));
				$lot->setVisible(0);
				$lot->setId_fournisseur(0);
				$lot->setId_espece($especesManager->getIdEspeceViande());
				$lot->setComposition(1);
				// On récupère l'id du lot créé
				$id_lot =  $lotsManager->saveLot($lot);
				if (!$id_lot || intval($id_lot) == 0) { exit('ERREUR CREATION DU LOT '.$num_lot); }

				$log = new Log();
				$log->setLog_type('info');
				$log->setLog_texte("Création auto du lot #".$id_lot. " via ajout produit hors stock BL Manuel #".$bl->getId()." et saisie numéro de lot libre ".$num_lot);
				$logsManager->saveLog($log);
				$codeLog.='/CREATE_LOT#'.$id_lot;

				// On rattache le quantième spécifié si on en a un (on vérifie aussi qu'on a bien un lot de type iprex et pas un lot de négoce
				// On vérifie si le numéro de lot semble bien être un numéro de lot iprex et pas un lot de négoce (commence par 6 chiffres ou plus et termine par 3 ou 4 lettres)
				$isLotIprex = preg_match('/^([0-9]{6,})([A-Z]{3,4})$/', strtoupper($num_lot));
				if (intval($quantieme) > 0 && $isLotIprex) {
					if (!$lotsManager->addQuantiemeIfNotExist($id_lot, $quantieme)) { exit('ERREUR RATTACHEMENT DU QUANTIEME '.$quantieme.' AU LOT CREE #'.$id_lot);}
					$log = new Log();
					$log->setLog_type('info');
					$log->setLog_texte("Rattachement du quantième ".$quantieme." au lot #".$id_lot." via ajout produit hors stock BL Manuel #".$bl->getId()." et saisie numéro de lot libre ".$num_lot);
					$logsManager->saveLog($log);
					$codeLog.='/ADD_QUANTIEME'.$quantieme;
				}

				// On rajoute un commentaire dans le lot créé
				$commentairesManager = new CommentairesManager($cnx);
				$commentaire = new Commentaire([]);
				$commentaire->setId_lot($id_lot);
				$commentaire->setCommentaire("Lot créé via BL manuel " . $bl->getNum_bl());
				$commentaire->setId_user($utilisateur->getId());
				$commentaire->setDate(date('Y-m-d H:i:s'));
				$commentairesManager->saveCommentaire($commentaire);

			} // FIn test lot trouvé

		} else {
			$id_lot = 0;
		} // FIN test numéro de lot différent de la compo

		// Si la palette n'est pas la même (si elle est vide, on conserve celle de la compo)
		if ($num_pal > 0) {

			// On recherche l'id_palette correspondant à ce numéro de palette - et par rapport au client du BL
			$id_pal = $palettesManager->getIdPaletteByNumeroAndClient($num_pal, $id_client);

			// Si on a bien une palette identifiée
			if ($id_pal > 0) {
				$id_palette = $id_pal;

			// Sinon, on crée la palette
			} else {

				$palette = new Palette();
				$palette->setNumero($num_pal);
				$palette->setDate(date('Y-m-d h:i:s'));
				$palette->setId_user($utilisateur->getId());
				$palette->setStatut(0);
				$palette->setId_poids_palette(0);
				$palette->setId_client(0);
				$palette->setScan_frais(0);
				$palette->setSupprime(0);

				$id_palette = $palettesManager->savePalette($palette);
				if (intval($id_palette) == 0) { exit('ERREUR CREATION PALETTE NUMERO ' . $num_pal); }

				$log = new Log();
				$log->setLog_type('info');
				$log->setLog_texte("Création auto palette #".$id_palette. " via ajout produit hors stock BL Manuel #".$bl->getId()." et saisie numéro de palette ".$num_pal." non trouvé pour client #".$id_client);
				$logsManager->saveLog($log);
				$codeLog.='/CREATE_PAL'.$id_palette;

			} // FIN test palette trouvée/créee

		} else {
			$id_palette = 0;
		} // FIN test numéro de palette différent de la compo

        $compo = new PaletteComposition();
		$compo->setId_palette($id_palette);
		$compo->setId_client($id_client);
		$compo->setId_produit($id_produit);
		$compo->setId_lot_pdt_froid(0);
		$compo->setId_frais(0);
		$compo->setId_lot_pdt_negoce($id_pdt_negoce);
		$compo->setId_lot_regroupement(0);
		$compo->setPoids($poids);
		$compo->setNb_colis($nb_colis);
		$compo->setDate(date('Y-m-d H:i:s'));
		$compo->setId_user($utilisateur->getId());
		$compo->setId_lot_hors_stock($id_lot);
		$compo->setArchive(0);
		$compo->setSupprime(0);

		$id_compo = $palettesManager->savePaletteComposition($compo);
		if (intval($id_compo) == 0) { exit('ERREUR SAVE COMPO PRODUIT HORS STOCK !'); }

		// LOG
		$log = new Log();
		$log->setLog_type('info');
		$log->setLog_texte("Création auto compo #".$id_compo. " via ajout produit hors stock BL Manuel #".$bl->getId());
		$logsManager->saveLog($log);
		$codeLog.='/CREATE_COMPO'.$id_compo;

		// Tarif client/produit + TVA
        $tarif = $tarifsManager->getTarifClientByClientAndProduit($id_client, $id_produit);
        $pu_ht = $tarif instanceof TarifClient ? $tarif->getPrix() : 0;
        $taxe = $taxesManager->getTaxe($pdt->getId_taxe());
        $tva = $taxe instanceof Taxe ? $taxe->getTaux() : 0;

        $num_lot_ligne = isset($num_lot_avec_quantieme) ? $num_lot_avec_quantieme : $num_lot;


		if ($id_bl_ligne == 0) {
			// Création ligne bl
			$ligne = new BlLigne([]);
			$ligne->setId_bl($bl->getId());
			$ligne->setId_compo($id_compo);
			$ligne->setId_palette($id_palette);
			$ligne->setId_produit($id_produit);
			$ligne->setId_lot($id_lot);
			$ligne->setNumlot($num_lot_ligne);
			$ligne->setPoids($poids);
			$ligne->setNb_colis($nb_colis);
			$ligne->setId_pdt_negoce($id_pdt_negoce);
			$ligne->setQte($qte_web);
			$ligne->setPu_ht($pu_ht);
			$ligne->setTva($tva);
			$ligne->setDate_add(date('Y-m-d H:i:s'));
			$ligne->setSupprime(0);
		} else {

			$ligne = $blsManagers->getBlLigne($id_bl_ligne);
			if (!$ligne instanceof BlLigne) { exit('ERREUR INSTANCIATION LIGNE BL #'.$id_bl_ligne); }
			$ligne->setId_compo($id_compo);
			$ligne->setId_palette($id_palette);
			$ligne->setId_produit($id_produit);
			$ligne->setId_lot($id_lot);
			$ligne->setNumlot($num_lot_ligne);
			$ligne->setPoids($poids);
			$ligne->setId_pdt_negoce($id_pdt_negoce);
			$ligne->setNb_colis($nb_colis);

		}



		$id_ligne = $blsManagers->saveBlLigne($ligne);
		if ($id_ligne == false) { exit('ERREUR SAVE LIGNE BL (FALSE)'); }
		if (intval($id_ligne) == 0) { exit('ERREUR SAVE LIGNE BL (0)'); }

		$log = new Log();
		$log->setLog_type('info');
		$log->setLog_texte("Création ligne #".$id_ligne." sur BL manuel #".$bl->getId()." (".$codeLog.")");
		$logsManager->saveLog($log);

		echo '1';
		exit;

        // Tester génération du BL + PDF
        // Tester facture depuis ce Bl

    } // FIN type stk/neg/pdt -----------------------------------------------------------------------------

	exit;

} // FIN mode


// Retourne un array avec le poids à dispatcher pour chaque ligne de Bl en fonction de sa palette
// On récupére la somme des emballages par palettes (array) SOMEMBPAL
// on boucle sur chaque palette pour en sortir le nombre de lignes NBPAL
// On calcule :SOMEMBPAL / NBPAL qui correspond au poids qu'on doit rajouter à chaque ligne pour chaque palette (array du poids additionnel par ligne pour chaque palette)
function modeGetPoidsBrutsByPalettes() {

	global $blsManagers;

    $id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) :0;
	
    if ($id_bl == 0) { exit; }

    // On récupère le poids des emballage pour chaque palette du BL
	$poidsPalettes = $blsManagers->getPoidsBrutsByPalettes($id_bl);

    if (!is_array($poidsPalettes) || empty($poidsPalettes)) { exit; }
    echo json_encode($poidsPalettes);
    exit;


} // FIN mode


function modeUpdFrsLigne() {

	global $blsManagers, $cnx;

	$logsManager = new LogManager($cnx);

	$id_frs = isset($_REQUEST['id_frs']) ? intval($_REQUEST['id_frs']) : 0;
	$id_ligne = isset($_REQUEST['id_ligne']) ? intval($_REQUEST['id_ligne']) : 0;

	if ($id_frs == 0) { exit('ERREUR fournisseur non identifié !');}
	if ($id_ligne == 0) { exit('ERREUR ligne de BL non identifiée !');}

	$ligne = $blsManagers->getBlLigne($id_ligne);
	if (!$ligne instanceof BlLigne) { exit('ERREUR instanciation ligne !');}

    $lignes = $blsManagers->getBlLignesRegroupeesFromLigne($ligne);

	$ligne->setId_frs($id_frs);
	if (!$blsManagers->saveBlLigne($ligne)) { exit('ERREUR enregistrement ligne !'); }

	$log = new Log();
	$log->setLog_type('info');
	$log->setLog_texte("Attribution du fournisseur #".$id_frs." pour la ligne de BL " . $id_ligne);
	$logsManager->saveLog($log);

    if (!empty($lignes)) {
        foreach ($lignes as $l) {
			$l->setId_frs($id_frs);
			if (!$blsManagers->saveBlLigne($l)) { exit('ERREUR enregistrement ligne !'); }
			$log = new Log();
			$log->setLog_type('info');
			$log->setLog_texte("Attribution du fournisseur #".$id_frs." pour la ligne de BL " . $l->getId(). " (via regroupement depuis ligne référence ".$id_ligne.")");
			$logsManager->saveLog($log);
        }
    }

	exit('1');

}
function modeMarquerBlEnvoye() {

	global $blsManagers, $cnx;

	$id_bl = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id_bl == 0) { exit("ERREUR BL non identifé !"); }

	$bl = $blsManagers->getBl($id_bl, false, false, false);
	if (!$bl instanceof Bl) { exit("ERREUR instanciation objet BL échoué sur ID #".$id_bl);}

	$bl->setDate_envoi(date('Y-m-d H:i:s'));
	if (!$blsManagers->saveBl($bl)) {
		exit('ERREUR enregsitrement BL !');
	}

	$logManager = new LogManager($cnx);
	$log = new Log();
	$log->setLog_type('info');
	$log->setLog_texte('BL #' . $id_bl . ' marqué manuellement comme envoyé.');
	$logManager->saveLog($log);
	echo '1';
	exit;

} // FIN mode

function modeAddProduitWeb() {

    global $blsManagers, $cnx;

	$id_bl = isset($_REQUEST['id_bl']) ? intval($_REQUEST['id_bl']) : 0;
	if ($id_bl == 0) { exit("ERREUR BL non identifé !"); }

	$bl = $blsManagers->getBl($id_bl, false, false, false);
	if (!$bl instanceof Bl) { exit("ERREUR instanciation objet BL échoué sur ID #".$id_bl);}

	$produitsManager = new ProduitManager($cnx);
	$tiersManager = new TiersManager($cnx);
	$logManager = new LogManager($cnx);
	$id_produit_web = $produitsManager->getIdProduitWeb();
	$id_frs_profilexport = $tiersManager->getIdProfilExport();

    $ligne = new BlLigne([]);
	$ligne->setDate_add(date('Y-m-d H:i:s'));
	$ligne->setSupprime(0);
	$ligne->setId_compo(0);
	$ligne->setId_bl($id_bl);
	$ligne->setNb_colis(1);
	$ligne->setId_produit($id_produit_web);
	$ligne->setPoids(0);
	$ligne->setQte(1);
	$ligne->setPu_ht(0);
	$ligne->setTva(0);
	$ligne->setId_frs($id_frs_profilexport);

	$id_ligne_bl = $blsManagers->saveBlLigne($ligne);
	if ((int)$id_ligne_bl == 0 || !$id_ligne_bl) {
		$blsManagers->supprBl($bl);
		exit("ERREUR CREATION LIGNE BL PRODUIT WEB GENERIQUE POUR BL #".$id_bl);
	}
    $log = new Log([]);
    $log->setLog_type('info');
    $log->setLog_texte("Ajout d'un produit générique Produit Web sur le BL #".$id_bl);
    $logManager->saveLog($log);

    echo '1';
    exit;

} // FIN mode