<?php
/**
------------------------------------------------------------------------
PAGE - ADMIN

Copyright (C) 2021 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2021 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */
ini_set('display_errors',1);
require_once 'scripts/php/config.php';
require_once 'scripts/php/check_admin.php';

$h1     = 'Signatures calendrier planning nettoyage';
$h1fa   = 'fa-fw fa-signature';

include('includes/header.php');

$nettoyageSignaturesManager = new NettoyageSignaturesManager($cnx);

$an     = isset($_REQUEST['an'])    ? $_REQUEST['an']   : date('Y');
$mois   = isset($_REQUEST['mois'])  ? $_REQUEST['mois'] : date('m');
?>
<input type="hidden" id="anCalendrier" value="<?php echo $an;?>"/>
<input type="hidden" id="moisCalendrier" value="<?php echo $mois;?>"/>
<div class="container-fluid page-admin">
    <div class="row">
        <div class="col-12 col-xl-10">
            <form class="row d-none d-xl-flex mb-2" id="filtres" action="admin-planning-nettsign.php" method="get">
                <div class="col-xl-2 col-lg-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Mois</span>
                        </div>
                        <select class="selectpicker form-control show-tick" name="mois">
                            <?php
                            foreach (Outils::getMoisListe() as $k => $v) {
                                $selected = $k == $mois ? 'selected' : '';
                                echo '<option value="'.$k.'" '.$selected.'>'.ucfirst($v).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-xl-2">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Année</span>
                        </div>
                        <select class="selectpicker form-control show-tick" name="an">
							<?php
							$minAnnee = $nettoyageSignaturesManager->getMinAnnee();
							if ((int)date('Y') - (int)$minAnnee > 5) {
								$minAnnee = (int)date('Y') - 5;
                            }
							for ($a = date('Y'); $a >= $minAnnee; $a--) {
								$selected = $a == $an ? 'selected' : '';
								echo '<option value="'.$a.'">'.$a.'</option>';
							}
							?>
                        </select>
                    </div>
                </div>
                <div class="col">
                    <button type="submit" class="btn btn-info"><i class="fa fa-search mr-1"></i> Afficher</button>
                </div>
            </form>
            <table id="calendrier" class="table admin">
                <thead>
                <tr>
                    <th colspan="2">Jour</th>
                    <th class="text-center">Heure</th>
                    <th>Agent d'entretien</th>
                    <th>Signatures</th>
                    <th>Validateur</th>
                </tr>
                </thead>
                <tbody>
				<?php
				$listeSignaruresMois = $nettoyageSignaturesManager->getListeNettoyageSignatures(['mois' => $mois, 'an' => $an]);
				$signaruresMois = [];
				foreach ($listeSignaruresMois as $sm) {
					$signaruresMois[$sm->date_only][] = $sm;
                }
                $nbJoursMois = cal_days_in_month(CAL_GREGORIAN, intval($mois), intval($an));
                for ($j = 1; $j <= $nbJoursMois; $j++) {
                    $j0 = $j < 10 ? '0'.$j : $j;
                    $dateJour = $an.'-'.$mois.'-'.(string)$j0;
                    $jourSem = date('w', strtotime($dateJour));
                    $signaturesJour = isset($signaruresMois[$dateJour]) ? $signaruresMois[$dateJour] : [];
					$futur = $dateJour > date('Y-m-d');
					$vide = !$futur && empty($signaturesJour);
                    ?>
                    <tr class="<?php
                        echo $jourSem == 0 ? ' plnet-dimanche ' : '';
                        echo $futur ? ' plnet-futur ' : '';
                        echo $vide ? ' plnet-vide ' : '';
                        ?>">
                        <td class="w-75px"><?php echo ucfirst(Outils::getJourFromSql($jourSem+1)); ?></td>
                        <td class="w-50px"><?php echo $j; ?></td>
                        <td class="w-150px text-center"><?php
                                if (!empty($signaturesJour)) {
                                    echo '<ul class="nomargin">';
                                    foreach ($signaturesJour as $sj) {
                                        echo '<li>'.$sj->getHeure().'</li>';
                                    }
                                    echo '</ul>';
                                }
                            ?></td>
                        <td class="w-300px"><?php
							if (!empty($signaturesJour)) {
								echo '<ul class="nomargin">';
								foreach ($signaturesJour as $sj) {
									$btnSuppr = '<i class="fa fa-trash-alt text-danger mr-1 pointeur btnSupprSignature" data-id="'.$sj->getId().'"></i>';

									echo '<li>'.$btnSuppr.$sj->getNom_user().'</li>';
								}
								echo '</ul>';
							}
							?></td>
                        <td><?php
							if (!empty($signaturesJour)) {
								foreach ($signaturesJour as $key=>$sj) {                                                                                                   ;
                                    ?>                                
                                    <input type="hidden" id="code" name="id_signature" value="<?php echo $sj->getId(); ?>"/>
                                    <code><?php if($key == 0){ echo $sj->getId();}?></code>
                                    <?php                           
									$png_url =__CBO_UPLOADS_PATH__.'signatures/nett/'.$sj->getId().".png";                                    
								    if (file_exists($png_url)) {
								        echo '<img src="'.__CBO_UPLOADS_URL__.'signatures/nett/'.$sj->getId().'.png" class="img-max-width-200"/>';                                        
									}
								}
							}
							?></td>
                        <td
                        
                        <?php if (!empty($signaturesJour)) {
                            foreach($signaturesJour as $key => $validateur){
                                if($validateur->getId_validateur()>0){
                                    ?>
                                    id="blocValide<?php echo $validateur->getId();?>"
                                    <?php
                                }
                            }                            
                        }
                        ?>
                        >
                    <?php if (!empty($signaturesJour)) {
                        foreach($signaturesJour as $key => $validateur){
                            if($validateur->getId_validateur() > 0){
                                ?>
                                <i class="fa fa-check text-success mr-1"></i><?php echo $validateur->getNom_validateur();
                                }else{
                                if($key == 0){
                                ?>
                                <button type="button" id="blocValide" class="btn btn-success btn-sm btnValiderSignature form-control text-center"><i class="fa fa-check mr-1"></i> Valider</button>
                                <?php
                                }
                            }
                        ?>
                        <?php } } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="col-12 col-xl-2 text-right boutons-droite d-none d-md-block">
            <div class="alert alert-secondary">
                <a href="<?php echo __CBO_ROOT_URL__?>admin-planning-nett.php" class="btn btn-secondary form-control text-left pl-3"><i class="fa fa-undo fa-fw fa-lg mr-2"></i>Retour planning</a>
                <button type="button" class="btn btn-info form-control text-left pl-3 mt-2 btnExportPdf"><i class="fa fa-file-pdf fa-fw fa-lg mr-2"></i>Exporter en PDF</button>
                <a href="" target="_blank" download id="lienPdf"></a>
            </div>
        </div>
    </div>
</div>
<?php
include('includes/footer.php');