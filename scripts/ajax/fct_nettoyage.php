<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax Nettoyage
------------------------------------------------------*/
// Initialisation du mode d'appel
error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$logsManager = new LogManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}




/* ------------------------------------
MODE - Affiche la liste
------------------------------------*/
function modeShowListePvisu() {

	global $mode, $utilisateur, $cnx;

	$doc = isset($_REQUEST['doc']) ? intval($_REQUEST['doc']) : -1;
	$dateBrute = isset($_REQUEST['date']) ? trim($_REQUEST['date']) : '';
	$dateArray = explode('-', $dateBrute);
	$jour = isset($dateArray[0]) ? $dateArray[0] : date('d');
	$mois = isset($dateArray[1]) ? $dateArray[1] : date('m');
	$an   = isset($dateArray[2]) ? $dateArray[2] : date('Y');
	$date = $an.'-'.$mois.'-'.$jour;

	

	$non_valides = isset($_REQUEST['non_valides']) ? intval($_REQUEST['non_valides']) : 0;

	if (!Outils::verifDateSql($date)) { $date = date('Y-m-d'); }

	// Préparation pagination (Ajax)
	$nbResultPpage      = 20;
	$page               = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$filtresPagination  = '?mode='.$mode;
	$start              = ($page-1) * $nbResultPpage;

	$mois = isset($_REQUEST['mois']) ? intval($_REQUEST['mois']) : 0;
	$annee = isset($_REQUEST['annee']) ? intval($_REQUEST['annee']) : 0;

	if ($mois > 0 && $annee == 0) { $annee = intval(date('Y')); }
	if ($mois > 0 || $annee > 0) { $non_valides = 0; $date = ''; }
	if ($mois > 0) { $filtresPagination.='&mois='.$mois; }
	if ($annee > 0) { $filtresPagination.='&annee='.$annee; }

	$params['start'] 			= $start;
	$params['nb_result_page'] 	= $nbResultPpage;
	$params['non_valides']      = $non_valides;
    if ($mois > 0) {
		$params['mois']      = $mois;
    }
	if ($annee > 0) {
		$params['annee']      = $annee;
	}

	$filtresPagination.='&non_valides='.$non_valides;
	if ($doc > -1 ) {  $filtresPagination.= '&doc='.$doc; }
	if (Outils::verifDateSql($date)) {  $filtresPagination.= '&date='.$date; $params['date'] = $date;}

	$pvisusAvant = [];
	$pvisusPendant = [];
	$nbResults = 0;


	// -1 = Tous / 0 = Avant prod
	if ($doc == -1 || $doc == 0) {
		$pvisuAvantManager = new PvisuAvantManager($cnx);
		$pvisusAvant = $pvisuAvantManager->getListePvisuAvants($params);
		$nbResults+= $pvisuAvantManager->getNb_results();
    }


    // -1 = Tous / 1 = Pendant prod
	if ($doc == -1 || $doc == 1) {
	    $pvisuPendantManager = new PvisuPendantManager($cnx);
	    $pvisusPendant = $pvisuPendantManager->getListePvisuPendants($params);
		$nbResults+= $pvisuPendantManager->getNb_results();
	}
	
	// -1 = Tous / 2 = Apres prod
	if ($doc == -1 || $doc == 2) {
		$pvisuApresManager = new PvisuApresManager($cnx);
		$pvisusApres = $pvisuApresManager->getListePvisuApres($params);
		$nbResults+= $pvisuApresManager->getNb_results();
	}
	
	// Si rien a afficher
	if (empty($pvisusPendant) && empty($pvisusAvant) && empty($pvisusApres)){ ?>

        <div class="alert alert-danger">
            <i class="fa fa-times-circle text-28 vmiddle mr-1"></i> <strong>Aucune feuille de contrôle !</strong>
        </div>

		<?php

		// Sinon, affichage de la liste des transporteurs
	} else {

		// Liste non vide, construction de la pagination...

		$pagination = new Pagination($page);
		$pagination->setUrl($filtresPagination);
		$pagination->setNb_results($nbResults);
		$pagination->setAjax_function(true);
		$pagination->setNb_results_page($nbResultPpage);
		$pagination->setNb_apres(2);
		$pagination->setNb_avant(2);
		?>

        <div class="alert alert-danger d-md-none text-center">
            <i class="fa fa-exclamation-circle text-28 mb-1"></i> <p>Connectez-vous depuis un apareil permettant un affichage plus large pour afficher cet écran&hellip;</p>
        </div>

        <table class="admin w-100 d-none d-md-table">
            <thead>
            <tr>
                <th <?php echo $utilisateur->isDev() ? '' : 'd-none'; ?>>ID</th>
                <th>Date</th>
                <th>Type</th>
                <th class="<?php echo $doc != 1 && $doc != -1 ? 'd-none' : ''; ?>">Lot</th>
                <th>Signature</th>
                <th>Etat</th>
                <th class="t-actions w-court-admin-cell">Edition</th>
                <th class="t-actions w-court-admin-cell">Visa</th>
                <th class="t-actions w-court-admin-cell">PDF
					
				</th>
            </tr>
            </thead>
            <tbody>
			<?php

			
			
			
            // On boucle sur les avant-prod
            foreach ($pvisusAvant as $pvAvant) {			
				
                $type = 1; // AVANT PROD ! (pour boutons ajax)
                $etat = 'Non contrôlé';
                $nbOK = 0;
				$cssEtat = 'secondary';
                foreach ($pvAvant->getPoints_controles() as $pt) {
                    if ($pt->getEtat() == 1) { $nbOK ++; }
				}
                if (!empty($pvAvant->getPoints_controles())) {
					$etat = $nbOK == count($pvAvant->getPoints_controles()) ? 'Satisfaisant' : 'Non satisfaisant';
					$cssEtat = $nbOK == count($pvAvant->getPoints_controles()) ? 'success' : 'danger';
				}

                ?>

                <tr>
                    <td <?php echo $utilisateur->isDev() ? '' : 'd-none'; ?>><code><?php echo $pvAvant->getId();?></code></td>
                    <td><?php echo Outils::dateSqlToFr($pvAvant->getDate()); ?></td>
                    <td>Contrôle avant production</td>
                    <td class="gris-b">&mdash;</td>
                    <td><?php echo $pvAvant->getNom_user(); ?></td>
                    <td><span class="badge text-16 badge-<?php echo $cssEtat; ?>"><?php echo $etat; ?></span></td>
                    <td class="t-actions"><a href="<?php echo __CBO_ROOT_URL__;?>nettoyage/avantid-<?php echo (int)$pvAvant->getId();?>" class="btn btn-secondary btn-sm"><i class="fa fa-edit fa-fw"></i></a></td>
                    <td class="t-actions" id="<?php echo 'vC' . $pvAvant->getId() . 't' . $type; ?>"><?php
                        if ($pvAvant->getId_user_validation() > 0) {
                            ?><i class="fa fa-check text-success mr-1"></i> <?php echo $pvAvant->getNom_validateur();
						} else { ?>
                            <button type="button" class="btn btn-success btn-sm btnValidation w-100" data-type="<?php echo $type; ?>"><i class="fa fa-check mr-1"></i> Valider</button>
						<?php }
                        ?></td>
                    <td class="text-center t-actions"><button type="button" class="btn btn-secondary btn-sm btnPdf" data-type="<?php echo $type; ?>"><i class="fa fa-file-pdf"></i></button></td>
                </tr>

			<?php
            } // FIN boucle avant prod





			// Boucle sur les pvisu pendant
			foreach ($pvisusPendant as $pvPendant) {

				$type = 2; // PENDANT PROD ! (pour boutons ajax)
				$etat = 'Non contrôlé';
				$nbOK = 0;
				$cssEtat = 'secondary';
				$points = $pvisuPendantManager->getListePvisuPendantPoints($pvPendant);
				foreach ($points as $pt) {
					if ($pt->getEtat() == 1) { $nbOK ++; }
				}
				if (!empty($points)) {
					$etat = $nbOK == count($points) ? 'Satisfaisant' : 'Non satisfaisant';
					$cssEtat = $nbOK == count($points) ? 'success' : 'danger';
				}

				?>

                <tr>
                    <td <?php echo $utilisateur->isDev() ? '' : 'd-none'; ?>><code><?php echo $pvPendant->getId();?></code></td>
                    <td><?php echo Outils::dateSqlToFr($pvPendant->getDate()); ?></td>
                    <td>Contrôle pendant la production</td>
                    <td><?php echo $pvPendant->getNumlot(); ?></td>
                    <td><?php echo $pvPendant->getNom_user(); ?></td>
                    <td><span class="badge text-16 badge-<?php echo $cssEtat; ?>"><?php echo $etat; ?></span></td>
                    <td class="t-actions"><a href="<?php echo __CBO_ROOT_URL__;?>nettoyage/pendantid-<?php echo (int)$pvPendant->getId();?>" class="btn btn-secondary btn-sm"><i class="fa fa-edit fa-fw"></i></a></td>
                    <td class="t-actions" id="<?php echo 'vC' . $pvPendant->getId() . 't' . $type; ?>"><?php
						if ($pvPendant->getId_user_validation() > 0) {
							?><i class="fa fa-check text-success mr-1"></i> <?php echo $pvPendant->getNom_validateur();
						} else { ?>
                            <button type="button" class="btn btn-success btn-sm btnValidation w-100" data-type="<?php echo $type; ?>"><i class="fa fa-check mr-1"></i> Valider</button>
						<?php }
						?></td>
                    <td class="text-center t-actions"><button type="button" class="btn btn-secondary btn-sm btnPdf" data-type="<?php echo $type; ?>"><i class="fa fa-file-pdf"></i></button></td>
                </tr>

			<?php } // FIN boucle apres prod

			// Boucle sur les pvisu apres
			foreach ($pvisusApres as $pvApres) {




				$type = 3; // APRES PROD ! (pour boutons ajax)
				$etat = 'Non contrôlé';
				$nbOK = 0;
				$cssEtat = 'secondary';
				$points = $pvisuApresManager->getListePvisuApresPoints($pvApres);
				foreach ($points as $pt) {
					if ($pt->getEtat() == 1) { $nbOK ++; }
				}
				if (!empty($points)) {
					$etat = $nbOK == count($points) ? 'Satisfaisant' : 'Non satisfaisant';
					$cssEtat = $nbOK == count($points) ? 'success' : 'danger';
				}

				?>

                <tr>
                    <td <?php echo $utilisateur->isDev() ? '' : 'd-none'; ?>><code><?php echo $pvApres->getId();?></code></td>
                    <td><?php echo Outils::dateSqlToFr($pvApres->getDate()); ?></td>
                    <td>Contrôle en fin de production</td>
                    <td class="gris-b">&mdash;</td>
                    <td><?php echo $pvApres->getNom_user(); ?></td>
                    <td><span class="badge text-16 badge-<?php echo $cssEtat; ?>"><?php echo $etat; ?></span></td>
                    <td class="t-actions"><a href="<?php echo __CBO_ROOT_URL__;?>nettoyage/apresid-<?php echo (int)$pvApres->getId();?>" class="btn btn-secondary btn-sm"><i class="fa fa-edit fa-fw"></i></a></td>
                    <td class="t-actions" id="<?php echo 'vC' . $pvApres->getId() . 't' . $type; ?>"><?php
						if ($pvApres->getId_user_validation() > 0) {
							?><i class="fa fa-check text-success mr-1"></i> <?php echo $pvApres->getNom_validateur();
						} else { ?>
                            <button type="button" class="btn btn-success btn-sm btnValidation w-100" data-type="<?php echo $type; ?>"><i class="fa fa-check mr-1"></i> Valider</button>
						<?php }
						?></td>
                    <td class="text-center t-actions"><button type="button" class="btn btn-secondary btn-sm btnPdf" data-type="<?php echo $type; ?>"><i class="fa fa-file-pdf"></i></button></td>
                </tr>

			<?php } // FIN boucle apres prod



		    ?>
            </tbody>
        </table>
		<?php

		// Pagination (aJax)
		if (isset($pagination)) {
			// Pagination bas de page, verbose...
			$pagination->setVerbose_pagination(1);
			$pagination->setVerbose_position('right');
			$pagination->setNature_resultats('contrôle');
			$pagination->setNb_apres(2);
			$pagination->setNb_avant(2);

			echo ($pagination->getPaginationHtml());
		} // FIN test pagination

	} // FIN test transporteurs à afficher
	exit;
} // FIN mode


// Valise un controle
function modeValideControle() {

	global $utilisateur, $cnx, $logsManager;

	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
	if ($id == 0) { exit('Erreur H59HA3B');}
	if ($type == 0) { exit('Erreur H58HA4A');}

	// SI controle AVANT prod
    if ($type == 1) {

        $pvAvantManager = new PvisuAvantManager($cnx);
        $pvAvant = $pvAvantManager->getPvisuAvant($id);
        if (!$pvAvant instanceof PvisuAvant) { exit('Erreur JL546HD');}
        $pvAvant->setId_user_validation($utilisateur->getId());
        $pvAvant->setDate_validation(date('Y-m-d H:i:s'));
        if (!$pvAvantManager->savePvisuAvant($pvAvant)) { exit('Erreur SAVE4684314354'); }
        ?>
        <i class="fa fa-check text-success mr-1"></i><?php echo $utilisateur->getNomComplet(); ?>
        <?php
        $log = new Log([]);
        $log->setLog_type('info');
        $log->setLog_texte("Validation du contrôle avant production ID #" . $id);
        $logsManager->saveLog($log);

    } // FIN avant prod

	// SI controle PENDANT prod
	else if ($type == 2) {

		$pvPendantManager = new PvisuPendantManager($cnx);
		$pvPendant = $pvPendantManager->getPvisuPendant($id);
		if (!$pvPendant instanceof PvisuPendant) { exit('Erreur JL54JH3');}
		$pvPendant->setId_user_validation($utilisateur->getId());
		$pvPendant->setDate_validation(date('Y-m-d H:i:s'));
		if (!$pvPendantManager->savePvisuPendant($pvPendant)) { exit('Erreur SAVE46p2314354'); }
		?>
        <i class="fa fa-check text-success mr-1"></i><?php echo $utilisateur->getNomComplet(); ?>
		<?php
		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("Validation du contrôle pendant production ID #" . $id);
		$logsManager->saveLog($log);

	} // FIN pendant prod

	// SI controle APRES prod
	else if ($type == 3) {

		$pvApresManager = new PvisuApresManager($cnx);
		$pvApres = $pvApresManager->getPvisuApres($id);
		if (!$pvApres instanceof PvisuApres) { exit('Erreur JL5d9h3');}
		$pvApres->setId_user_validation($utilisateur->getId());
		$pvApres->setDate_validation(date('Y-m-d H:i:s'));
		if (!$pvApresManager->savePvisuApres($pvApres)) { exit('Erreur SAVE9yw23pmdgt'); }
		?>
        <i class="fa fa-check text-success mr-1"></i><?php echo $utilisateur->getNomComplet(); ?>
		<?php
		$log = new Log([]);
		$log->setLog_type('info');
		$log->setLog_texte("Validation du contrôle en fin de production ID #" . $id);
		$logsManager->saveLog($log);

	} // FIN pendant prod

    exit;
} // FIN mode


/* ------------------------------------------
MODE - Export PDF
-------------------------------------------*/
function modeGenerePdf() {

	global $utilisateur, $cnx, $logsManager;

	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
	if ($id == 0) { exit('Erreur H59HA43B');}
	if ($type == 0) { exit('Erreur H58H9A4A');}
	$contentType = '';
	$nom_fichier = 'controle.pdf';

    // SI controle AVANT prod
	if ($type == 1) {

		$pvAvantManager = new PvisuAvantManager($cnx);
		$pvAvant = $pvAvantManager->getPvisuAvant($id);
		if (!$pvAvant instanceof PvisuAvant) { exit('Erreur JL546HD');}

		$nom_fichier = str_replace('/', '', Outils::dateSqlToFr($pvAvant->getDate())).'-controle-avant.pdf';
		$contentType = genereContenuPdfPvAvant($pvAvant);

	} // FIN avant prod

	// SI controle PENDANT prod
	else if ($type == 2) {

		$pvisuPendantManager = new PvisuPendantManager($cnx);
		$pvPendant = $pvisuPendantManager->getPvisuPendant($id);

		if (!$pvPendant instanceof PvisuPendant) { exit('Erreur JL69W1D');}

		$nom_fichier = str_replace('/', '', Outils::dateSqlToFr($pvPendant->getDate())).'-'.strtolower($pvPendant->getNumlot()).'-controle.pdf';
		$contentType = genereContenuPdfPvPendant($pvPendant);


	} // FIN pendant prod

	// SI controle APRES prod
	else if ($type == 3) {

		$pvisuApresManager = new PvisuApresManager($cnx);
		$pvApres = $pvisuApresManager->getPvisuApres($id);

		if (!$pvApres instanceof PvisuApres) { exit('Erreur Pl9fW1D');}

		$nom_fichier = str_replace('/', '', Outils::dateSqlToFr($pvApres->getDate())).'-controle-fin.pdf';
		$contentType = genereContenuPdfPvApres($pvApres);


	} // FIN pendant prod


	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content = $contentType;
	$content .= ob_get_clean();
		$chemin = __CBO_ROOT_PATH__.'/temp/'.$nom_fichier;
	if (file_exists($chemin)) { unlink($chemin); }

	try {
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'UTF-8', [15, 15, 15, 15]);
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($content));;
		$savefilepath = __CBO_ROOT_PATH__.'/temp/'.$nom_fichier;
		$html2pdf->Output($savefilepath, 'F');
		echo __CBO_TEMP_URL__.$nom_fichier;
	}
	catch(HTML2PDF_exception $e) {
		vd($e);
		exit;
	}

	exit;

} // FIN mode

// Génère le fichier PDF (apres prod)
function genereContenuPdfPvApres($pvApres) {

	global $cnx;

	$pvisuManager           = new PvisuApresManager($cnx);
	$lotsManager            = new LotManager($cnx);
	$visuPoints = $pvisuManager->getListePvisuApresPoints($pvApres);




	if (!is_array($visuPoints)) { $visuPoints = []; }

	$contenu = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style></head><body>
                 <div class="header">
                    <table class="table w100">
                        <tr>
                            <td class="w15"><img src="'
		.__CBO_ROOT_URL__.'img/logo-pe-140.png" style="width: 100px;" alt="PROFIL EXPORT"/><p class="text-10 pl-10 gris-5">PROFIL EXPORT</p></td>
                            <td class="w70 text-center pt-10 text-16">
                                Vérification propreté visuelle en fin de production
                                <p class="text-11 gris-7">Fréquence d\'inspection : Quotidienne (si production)</p>
                            </td>
                            <td class="w15 text-right">
                                <p class="text-12"><b>IPREX</b></p>
                                <p class="text-8 gris-9">Intranet PROFIL EXPORT</p>
                            </td>
                        </tr>                
                    </table>
                    <table class="table w100">
                        <tr>
                            <td class="w70 text-left text-16 pt-10">'.Outils::getDate_only_verbose($pvApres->getDate(), true).'</td>
                            <td class="w30 text-right text-14"><p class="text-12 gris-7">Responsable du contrôle</p>'.$pvApres->getNom_user().'</td>
                        </tr>                
                    </table>
                    <table class="table w100 mt-25">';




	foreach ($visuPoints as $point) {

		$fichenc = (int)$point->getFiche_nc() == 1 ? 'Fiche NC' : '';
		$etat = (int)$point->getEtat() == 1 ? 'Satisfaisant' : 'Non-satisfaisant';

		$contenu.='<tr>
                        <td class="w55">'.$point->getNom().'</td>
                        <td class="w15">'.$etat.'</td>
                        <td class="w20">'.$point->getNom_action().'</td>
                        <td class="w10">'.$fichenc.'</td>
                   </tr>';


	} // FIN boucle points

	$contenu.= '</table>
        <div class="text-12 mt-20"><b>Commentaires :</b><p class="text-12">';

	$contenu.= trim($pvApres->getCommentaires()) == '' ? '<i>Aucun</i>' : $pvApres->getCommentaires();

	$contenu.= '</p></div>';


	// Liste des lots associés (lots de la journée)
	$lots = $lotsManager->getLotsJourAtelier($pvApres->getDate());

	$contenu.= '   <div class="text-12 mt-20"><b>Lots associés :</b> ';
	if (empty($lots)) {
		$contenu.= ' <p class="text-12"><i>Aucun</i></p></div>';
	} else {
		$contenu.= '<br>';
		foreach ($lots as $lot) {
			$contenu.= '<span class="numlotpvisu"> '.$lot->getNumlot().' </span><span class="text-22"> </span>';

		}
		$contenu.= '</div>';

	} // FIN test lots associés

	if ($pvApres->getId_user_validation() > 0) {
		$contenu.= '<div class="mt-50 text-center text-11 gris-5">Validé par ' . $pvApres->getNom_validateur() . ' le ' . Outils::getDate_verbose($pvApres->getDate_validation()). '</div>';
	}

	$contenu.='</div></body></html>';

	return $contenu;


} // FIN fonction


// Génère le fichier PDF (pendant prod)
function genereContenuPdfPvPendant($pvPendant) {

	global $cnx;

	$pvisuManager           = new PvisuPendantManager($cnx);
	$visuPoints = $pvisuManager->getListePvisuPendantPoints($pvPendant);

	if (!is_array($visuPoints)) { $visuPoints = []; }

	$contenu = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style></head><body>
                 <div class="header">
                    <table class="table w100">
                        <tr>
                            <td class="w15"><img src="'
		.__CBO_ROOT_URL__.'img/logo-pe-140.png" style="width: 100px;" alt="PROFIL EXPORT"/><p class="text-10 pl-10 gris-5">PROFIL EXPORT</p></td>
                            <td class="w70 text-center pt-10 text-16">
                                Vérification propreté visuelle pendant la production
                                <p class="text-11 gris-7">RESPECT SPÉCIFICATIONS VIANDES</p>
                                <p class="text-11 gris-7">Fréquence d\'inspection : Quotidienne sur chaque lot</p>
                            </td>
                            <td class="w15 text-right">
                                <p class="text-12"><b>IPREX</b></p>
                                <p class="text-8 gris-9">Intranet PROFIL EXPORT</p>
                            </td>
                        </tr>                
                    </table>
                    <table class="table w100">
                        <tr>
                            <td class="w70 text-left text-16 pt-10">'.Outils::getDate_only_verbose($pvPendant->getDate(), true).' - Lot ' . $pvPendant->getNumlot() . '</td>
                            <td class="w30 text-right text-14"><p class="text-12 gris-7">Responsable du contrôle</p>'.$pvPendant->getNom_user().'</td>
                        </tr>                
                    </table>
                    <table class="table w100 mt-25">';


	foreach ($visuPoints as $point) {

	    $fichenc = (int)$point->getFiche_nc() == 1 ? 'Fiche NC' : '';
	    $etat = (int)$point->getEtat() == 1 ? 'Satisfaisant' : 'Non-satisfaisant';

		$contenu.='<tr>
                        <td class="w55">'.$point->getNom().'</td>
                        <td class="w15">'.$etat.'</td>
                        <td class="w20">'.$point->getNom_action().'</td>
                        <td class="w10">'.$fichenc.'</td>
                   </tr>';


	} // FIN boucle points

	$contenu.= '</table>
        <div class="text-12 mt-20"><b>Commentaires :</b><p class="text-12">';

	$contenu.= trim($pvPendant->getCommentaires()) == '' ? '<i>Aucun</i>' : $pvPendant->getCommentaires();

	$contenu.= '</p></div>';

	if ($pvPendant->getId_user_validation() > 0) {
		$contenu.= '<div class="mt-50 text-center text-11 gris-5">Validé par ' . $pvPendant->getNom_validateur() . ' le ' . Outils::getDate_verbose($pvPendant->getDate_validation()). '</div>';
	}

	$contenu.='</div></body></html>';

	return $contenu;



} // FIN fonction

// Génère le fichier PDF (avant prod)
function genereContenuPdfPvAvant($pvAvant) {

    global $cnx;

    $pvisuManager           = new PvisuAvantManager($cnx);
	$pointsControlesManager = new PointsControleManager($cnx);
	$lotsManager            = new LotManager($cnx);

	$points     = $pointsControlesManager->getListePointsControles();
	$visuPoints = $pvisuManager->getListePvisuAvantPoints($pvAvant, true);

	if (!is_array($visuPoints)) { $visuPoints = []; }

	$contenu = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style></head><body>
                 <div class="header">
                    <table class="table w100">
                        <tr>
                            <td class="w15"><img src="'
                            .__CBO_ROOT_URL__.'img/logo-pe-140.png" style="width: 100px;" alt="PROFIL EXPORT"/><p class="text-10 pl-10 gris-5">PROFIL EXPORT</p></td>
                            <td class="w70 text-center pt-10 text-16">
                                Vérification propreté visuelle avant la production
                                <p class="text-11 gris-7">Fréquence d\'inspection : Quotidienne (si production)</p>
                            </td>
                            <td class="w15 text-right">
                                <p class="text-12"><b>IPREX</b></p>
                                <p class="text-8 gris-9">Intranet PROFIL EXPORT</p>
                            </td>
                        </tr>                
                    </table>
                    <table class="table w100">
                        <tr>
                            <td class="w50 text-left text-16 pt-10">'.Outils::getDate_only_verbose($pvAvant->getDate(), true).'</td>
                            <td class="w50 text-right text-14"><p class="text-12 gris-7">Responsable du contrôle</p>'.$pvAvant->getNom_user().'</td>
                        </tr>                
                    </table>
                    <table class="table w100 mt-25">';

    foreach ($points as $point) {

	    $cssParent = $point->getId_parent() == 0 ? 'trptparent' : '';

	    $contenu.='<tr class="'.$cssParent.'">
                        <td class="w75">'.$point->getNom().'</td>
                        <td class="w25">';

	    if ($point->getId_parent() > 0) {
	         $contenu.= isset($visuPoints[$point->getId()]) && (int)$visuPoints[$point->getId()] == 1 ? 'Satisfaisant' : '';
	         $contenu.= isset($visuPoints[$point->getId()]) && (int)$visuPoints[$point->getId()] == 0 ? 'Non-satisfaisant' : '';
	    }
        $contenu.= '</td></tr>';

	} // FIN boucle points

    $contenu.= '</table>
        <div class="text-12 mt-20"><b>Commentaires :</b><p class="text-12">';

	$contenu.= trim($pvAvant->getCommentaires()) == '' ? '<i>Aucun</i>' : $pvAvant->getCommentaires();

	$contenu.= '</p></div>';


	// Liste des lots associés (lots de la journée)
	$lots = $lotsManager->getLotsJourAtelier($pvAvant->getDate());

	$contenu.= '   <div class="text-12 mt-20"><b>Lots associés :</b> ';
	if (empty($lots)) {
		$contenu.= ' <p class="text-12"><i>Aucun</i></p></div>';
	} else {
		$contenu.= '<br>';
		foreach ($lots as $lot) {
			$contenu.= '<span class="numlotpvisu"> '.$lot->getNumlot().' </span><span class="text-22"> </span>';

		}
		$contenu.= '</div>';

	} // FIN test lots associés

	if ($pvAvant->getId_user_validation() > 0) {
		$contenu.= '<div class="mt-50 text-center text-11 gris-5">Validé par ' . $pvAvant->getNom_validateur() . ' le ' . Outils::getDate_verbose($pvAvant->getDate_validation()). '</div>';
	}

	$contenu.='</div></body></html>';

	return $contenu;

} // FIN fonction

// Contenu de la modale heures des alarmes
function modeModalHeuresAlarme() {

    global $cnx;
    $configManager = new ConfigManager($cnx);

    $heuresConfig = $configManager->getConfig('heures_alarmes');
    if (!$heuresConfig instanceof Config) { exit('Instanciation objet config échjouée !');}
    $heures = explode(',', $heuresConfig->getValeur());
    if (!empty($heures) && $heures[0] == '') { $heures = []; }
    $i = 0;
    foreach ($heures as $heure) {
        $i++;
        $hmin = explode(':', $heure);
        $h = $hmin[0];
        $m = $hmin[1];
        ?>
        <div class="row">
            <div class="col input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-bell mr-2 gris-9"></i>Alarme <?php echo $i; ?></span>
                </div>
                <select class="selectpicker form-control select-centre show-tick" data-size="8" name="alarmes[<?php echo $i; ?>][h]">
                    <option value="-1">Supprimer</option>
                    <option data-divider="true"></option>
                    <?php
                    for ($ih = 0; $ih <= 23; $ih++) { ?>
                        <option value="<?php echo $ih; ?>" <?php echo $ih == intval($h) ? 'selected' : ''; ?>><?php echo sprintf("%02d", $ih); ?></option>
                    <?php }
                    ?>
                </select>
                <div class="input-group-append"><span class="input-group-text">H</span></div>
                <select class="selectpicker form-control select-centre show-tick" data-size="8" name="alarmes[<?php echo $i; ?>][m]">
                    <option value="-1">Supprimer</option>
                    <option data-divider="true"></option>
                    <?php
                    for ($im = 0; $im <= 55; $im+=5) { ?>
                        <option value="<?php echo $im; ?>" <?php echo $im == intval($m) ? 'selected' : ''; ?>><?php echo sprintf("%02d", $im); ?></option>
                    <?php }
                    ?>
                </select>
                <div class="input-group-append"><span class="input-group-text">min</span></div>
            </div>
        </div>
    <?php }
    ?>
        <div class="row mt-3">
            <div class="col input-group mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-plus-circle mr-2 gris-9"></i>Nouvelle alarme</span>
                </div>
                <select class="selectpicker form-control select-centre show-tick" name="alarmes[<?php echo $i+1; ?>][h]">
                    <option value="-1">&mdash;</option>
                    <option data-divider="true"></option>
                    <?php
                    for ($ih = 0; $ih <= 23; $ih++) { ?>
                        <option value="<?php echo $ih; ?>"><?php echo sprintf("%02d", $ih); ?></option>
                    <?php }
                    ?>
                </select>
                <div class="input-group-append"><span class="input-group-text">H</span></div>
                <select class="selectpicker form-control select-centre show-tick" name="alarmes[<?php echo $i+1; ?>][m]">
                    <option value="-1">&mdash;</option>
                    <option data-divider="true"></option>
                    <?php
                    for ($im = 0; $im <= 55; $im+=5) { ?>
                        <option value="<?php echo $im; ?>"><?php echo sprintf("%02d", $im); ?></option>
                    <?php }
                    ?>
                </select>
                <div class="input-group-append"><span class="input-group-text">min</span></div>
            </div>
                    </div>
    <input type="hidden" name="mode" value="saveHeuresAlarmes" />
    <?php
    exit;
} // FIN mode


function modeSaveHeuresAlarmes() {

    global $cnx;
	$configManager = new ConfigManager($cnx);
	$logManager = new LogManager($cnx);

	$heuresConfig = $configManager->getConfig('heures_alarmes');
	if (!$heuresConfig instanceof Config) { exit('ERR INST_OBJET_CONFIG');}


	$configValeur = '';
    $alarmesArray = isset($_REQUEST['alarmes']) && is_array($_REQUEST['alarmes']) ? $_REQUEST['alarmes'] : [];
    foreach ($alarmesArray as $alarme) {

        //vd($alarme);

        $h = isset($alarme['h']) ? intval($alarme['h']) : -1;
        $m = isset($alarme['m']) ? intval($alarme['m']) : -1;
        if ($h < 0 || $m < 0) { continue;}
		$configValeur.=  sprintf("%02d", $h).':'.sprintf("%02d", $m).',';

    }

    //exit;

    if (strlen($configValeur) > 0) { $configValeur = substr($configValeur,0,-1); }
	$heuresConfig->setValeur($configValeur);
	$heuresConfig->setDate_maj(date('Y-m-d H:i:s'));
    if (!$configManager->saveConfig($heuresConfig)) { exit('ERR SAVE_CONFIG');}

    $log = new Log([]);
	$log->setLog_type('info');
    $log->setLog_texte("Modif des heures alarmes nettoyage");
    $logManager->saveLog($log);

    exit('1');

} // FIN mode