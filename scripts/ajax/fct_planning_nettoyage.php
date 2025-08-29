
<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax Planning Nettoyage
------------------------------------------------------*/
ini_set('display_errors',1);
error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$logsManager = new LogManager($cnx);


$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}


/* ---------------------------------------
MODE - Affiche la liste des intervenants
----------------------------------------*/
function modeShowListeIntervenants() {

	global $cnx, $utilisateur;

	$intervenantsManager = new IntervenantsNettoyageManager($cnx);
	$liste = $intervenantsManager->getListeIntervenantsNettoyage();

	if (empty($liste)) { ?>

        <div class="row">
            <div class="col">
                <div class="alert alert-warning padding-50 text-center">
                    <i class="fa fa-exclamation-circle fa-3x mb-3"></i>
                    <p class="mb-0">Aucun intervenant !</p>
                </div>
            </div>
        </div>

    <?php exit; } ?>

    <table class="w-100 admin table-v-middle">
        <thead>
        <tr>
            <th class="w-50px <?php echo $utilisateur->isDev() ? '' : 'd-none';?>">ID</th>
            <th class="w-75px">Code</th>
            <th>Nom</th>
            <th class="t-actions w-100px text-center">Modifier</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($liste as $inter) { ?>
            
            <tr>
                <td class="<?php echo $utilisateur->isDev() ? '' : 'd-none';?>"><kbd><?php echo $inter->getId();?></kbd></td>
                <td><?php echo $inter->getCode();?></td>
                <td><?php echo $inter->getNom();?></td>
                <td class="t-actions text-center">
                    <button type="button" class="btn btn-sm btn-secondary btnEdit"><i class="fa fa-ellipsis-h"></i></button>
                </td>
            </tr>
            
		<?php }
        ?>
        </tbody>
    </table>


    <?php
	exit;
} // FIN mode

/* ----------------------------------------------
MODE - Charge la modale add/upd des intervenants
-----------------------------------------------*/
function modeChargeModaleAddUpdIntervenant() {

	global $cnx;

	$intervenantsManager = new IntervenantsNettoyageManager($cnx);

	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$inter = $id > 0 ? $intervenantsManager->getIntervenantNettoyage($id) : new IntervenantNettoyage([]);
	if (!$inter instanceof IntervenantNettoyage) { exit('ERREUR^Instanciation intervenant échouée !');}

	// Titre
	echo '<i class="mr-1 fa fa-';
	echo $id > 0 ? 'edit' : 'plus-square';
	echo '"></i>';
	echo $id > 0 ? 'Modification de l\'intervenant' : 'Création d\'un nouvel intervenant';
	echo '^';

	// Body
    ?>
    <input type="hidden" name="mode" value="saveIntervenant"/>
    <input type="hidden" name="id" value="<?php echo $id; ?>"/>
    <div class="row">
        <div class="col-5">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Code</span>
                </div>
                <input type="text" name="code" class="form-control text-center" maxlength="8" placeholder="AA" value="<?php echo $inter->getCode();?>"/>
            </div>
        </div>
        <div class="col">
            <div class="alert alert-info texte-left texte-fin text-12">
                <i class="fa fa-arrow-left mr-2"></i>Code court affiché dans le planning.
            </div>
        </div>
    </div>
    <div class="row mt-1">
        <div class="input-group col">
            <div class="input-group-prepend">
                <span class="input-group-text">Libellé</span>
            </div>
            <input type="text" name="nom" class="form-control" maxlength="128" placeholder="Désignation de l'intervenant" value="<?php echo $inter->getNom();?>"/>
        </div>
    </div>
    <?php
    exit;
} // FIN mode

/* -------------------------
MODE - Add/upd intervenant
--------------------------*/
function modeSaveIntervenant() {

	global $cnx, $logsManager;

	$intervenantsManager = new IntervenantsNettoyageManager($cnx);

	$id     = isset($_REQUEST['id'])    ? intval($_REQUEST['id']) : 0;
	$nom    = isset($_REQUEST['nom'])   ? trim(strip_tags(Outils::decodeCharAjac($_REQUEST['nom']))) : '';
	$code   = isset($_REQUEST['code'])  ? trim(strtoupper(preg_replace("/(\W)+/", "", $_REQUEST['code']))) : '';

	$inter = $id > 0 ? $intervenantsManager->getIntervenantNettoyage($id) : new IntervenantNettoyage([]);
	if (!$inter instanceof IntervenantNettoyage) { exit('ERR_INSTOBJ_INTER_'.$id);}
	if ($code == '') { exit('ERR_CODEVIDE');}
	if ($nom  == '') { exit('ERR_NOMVIDE');}

    $inter->setCode($code);
	$inter->setNom($nom);

	echo $intervenantsManager->saveIntervenantNettoyage($inter) ? 1 : 0;

	$log = new Log([]);
	$log->setLog_type('info');
	$logTxt = $id > 0 ? 'Modification intervenant nettoyage #'.$id : 'Création nouvel intervenant nettoyage';
	$log->setLog_texte($logTxt);
	$logsManager->saveLog($log);

	exit;

} // FIN mode

/* -------------------------
MODE - Supprime intervenant
--------------------------*/
function modeSupprIntervenant() {

	global $cnx, $logsManager;

	$intervenantsManager = new IntervenantsNettoyageManager($cnx);

	$id     = isset($_REQUEST['id'])    ? intval($_REQUEST['id']) : 0;
	if ($id == 0) { exit('ERR_ID_0');}

	$inter = $intervenantsManager->getIntervenantNettoyage($id);
	if (!$inter instanceof IntervenantNettoyage) { exit('ERR_INSTOBJ_INTER_'.$id);}

	echo $intervenantsManager->supprimeIntervenantNettoyage($inter) ? 1 : 0;

	$log = new Log([]);
	$log->setLog_type('warning');
	$log->setLog_texte('Suppression intervenant nettoyage #'.$id );
	$logsManager->saveLog($log);

	exit;

} // FIN mode




/* ---------------------------------------
MODE - Affiche la liste des frequences
----------------------------------------*/
function modeShowListeFrequences() {

	global $cnx, $utilisateur;

	$frequencesManager = new FrequencesManager($cnx);
	$liste = $frequencesManager->getListeFrequences();

	if (empty($liste)) { ?>

        <div class="row">
            <div class="col">
                <div class="alert alert-warning padding-50 text-center">
                    <i class="fa fa-exclamation-circle fa-3x mb-3"></i>
                    <p class="mb-0">Aucune frequence !</p>
                </div>
            </div>
        </div>

		<?php exit; } ?>

    <table class="w-100 admin table-v-middle">
        <thead>
        <tr>
            <th class="w-50px <?php echo $utilisateur->isDev() ? '' : 'd-none';?>">ID</th>
            <th class="w-75px">Code</th>
            <th>Nom</th>
            <th class="t-actions w-100px text-center">Modifier</th>
        </tr>
        </thead>
        <tbody>
		<?php
		foreach ($liste as $freq) { ?>

            <tr>
                <td class="<?php echo $utilisateur->isDev() ? '' : 'd-none';?>"><kbd><?php echo $freq->getId();?></kbd></td>
                <td><?php echo $freq->getCode();?></td>
                <td><?php echo $freq->getNom();?></td>
                <td class="t-actions text-center">
                    <button type="button" class="btn btn-sm btn-secondary btnEdit"><i class="fa fa-ellipsis-h"></i></button>
                </td>
            </tr>

		<?php }
		?>
        </tbody>
    </table>


	<?php
	exit;
} // FIN mode

/* ----------------------------------------------
MODE - Charge la modale add/upd des frequences
-----------------------------------------------*/
function modeChargeModaleAddUpdFrequence() {

	global $cnx;

	$frequencesManager = new FrequencesManager($cnx);

	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$freq = $id > 0 ? $frequencesManager->getFrequence($id) : new Frequence([]);
	if (!$freq instanceof Frequence) { exit('ERREUR^Instanciation frequence échouée !');}

	// Titre
	echo '<i class="mr-1 fa fa-';
	echo $id > 0 ? 'edit' : 'plus-square';
	echo '"></i>';
	echo $id > 0 ? 'Modification de la frequence' : 'Création d\'une nouvelle frequence';
	echo '^';

	// Body
	?>
    <input type="hidden" name="mode" value="saveFrequence"/>
    <input type="hidden" name="id" value="<?php echo $id; ?>"/>
    <div class="row">
        <div class="col-5">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Code</span>
                </div>
                <input type="text" name="code" class="form-control text-center" maxlength="8" placeholder="AA" value="<?php echo $freq->getCode();?>"/>
            </div>
        </div>
        <div class="col">
            <div class="alert alert-info texte-left texte-fin text-12">
                <i class="fa fa-arrow-left mr-2"></i>Code court affiché dans le planning.
            </div>
        </div>
    </div>
    <div class="row mt-1">
        <div class="input-group col">
            <div class="input-group-prepend">
                <span class="input-group-text">Libellé</span>
            </div>
            <input type="text" name="nom" class="form-control" maxlength="128" placeholder="Désignation de la frequence" value="<?php echo $freq->getNom();?>"/>
        </div>
    </div>
	<?php
	exit;
} // FIN mode

/* -------------------------
MODE - Add/upd frequence
--------------------------*/
function modeSaveFrequence() {

	global $cnx, $logsManager;

	$frequencesManager = new FrequencesManager($cnx);

	$id     = isset($_REQUEST['id'])    ? intval($_REQUEST['id']) : 0;
	$nom    = isset($_REQUEST['nom'])   ? trim(strip_tags(Outils::decodeCharAjac($_REQUEST['nom']))) : '';
	$code   = isset($_REQUEST['code'])  ? trim(strtoupper(preg_replace("/(\W)+/", "", $_REQUEST['code']))) : '';

	$freq = $id > 0 ? $frequencesManager->getFrequence($id) : new Frequence([]);
	if (!$freq instanceof Frequence) { exit('ERR_INSTOBJ_FREQ'.$id);}
	if ($code == '') { exit('ERR_CODEVIDE');}
	if ($nom  == '') { exit('ERR_NOMVIDE');}

	$freq->setCode($code);
	$freq->setNom($nom);

	echo $frequencesManager->saveFrequences($freq) ? 1 : 0;

	$log = new Log([]);
	$log->setLog_type('info');
	$logTxt = $id > 0 ? 'Modification frequence nettoyage #'.$id : 'Création nouvelle frequence nettoyage';
	$log->setLog_texte($logTxt);
	$logsManager->saveLog($log);

	exit;

} // FIN mode

/* -------------------------
MODE - Supprime frequence
--------------------------*/
function modeSupprFrequence() {

	global $cnx, $logsManager;

	$frequencesManager = new FrequencesManager($cnx);

	$id     = isset($_REQUEST['id'])    ? intval($_REQUEST['id']) : 0;
	if ($id == 0) { exit('ERR_ID_0');}

	$freq = $frequencesManager->getFrequence($id);
	if (!$freq instanceof Frequence) { exit('ERR_INSTOBJ_FREQ_'.$id);}

	echo $frequencesManager->supprimeFrequence($freq) ? 1 : 0;

	$log = new Log([]);
	$log->setLog_type('warning');
	$log->setLog_texte('Suppression frequence nettoyage #'.$id );
	$logsManager->saveLog($log);

	exit;

} // FIN mode



/* ---------------------------------------
MODE - Affiche la liste des locaux
----------------------------------------*/
function modeShowListeLocaux() {

	global $cnx, $utilisateur;

	$locauxManager = new LocauxManager($cnx);
	?>
    <div class="row">
        <div class="col-6">
            <?php
			/**
			 * Liste des locaux
			 */
			$liste = $locauxManager->getListeLocaux();

			// Pas de local
            if (empty($liste)) { ?>

                <div class="row">
                    <div class="col">
                        <div class="alert alert-warning padding-50 text-center">
                            <i class="fa fa-exclamation-circle fa-3x mb-3"></i>
                            <p class="mb-0">Aucun local !</p>
                        </div>
                    </div>
                </div>
            <?php
            // Locaux...
            } else { ?>

                <table class="w-100 admin table-v-middle">
                    <thead>
                    <tr>
                        <th class="w-50px <?php echo $utilisateur->isDev() ? '' : 'd-none';?>">ID</th>
                        <th class="nowrap text-center w-100px">Numéro atelier</th>
                        <th>Désignation</th>
                        <th class="text-right w-100px">Surface</th>
                        <th class="text-center">Vues</th>
                        <th class="t-actions w-100px text-center">Modifier</th>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					foreach ($liste as $local) { ?>

                        <tr>
                            <td class="<?php echo $utilisateur->isDev() ? '' : 'd-none';?>"><kbd><?php echo $local->getId();?></kbd></td>
                            <td class="text-center text-primary text-16"><?php echo $local->getNumero();?></td>
                            <td><?php echo $local->getNom();?></td>
                            <td class="text-right"><?php echo $local->getSurface() > 0 ? $local->getSurface() . ' m²' : '&mdash;';?></td>
                            <td class="text-center"><?php
                                //echo $local->getVues() == '' ? 'Toutes' : count(explode(',',$local->getVues()));
                                echo $local->getVues() == '' ? 'Toutes' : strtoupper($local->getVues());
                                ?></td>
                            <td class="t-actions text-center">
                                <button type="button" class="btn btn-sm btn-secondary btnEdit" data-type="local"><i class="fa fa-ellipsis-h"></i></button>
                            </td>
                        </tr>

					<?php }
					?>
                    </tbody>
                </table>
            <?php
            } // FIN test locaux
            ?>
        </div>
        <div class="col-6">
            <?php
			/**
			 * Liste des zones
			 */
			$liste = $locauxManager->getListeLocalZones();
			// Pas de local
			if (empty($liste)) { ?>

                <div class="row">
                    <div class="col">
                        <div class="alert alert-warning padding-50 text-center">
                            <i class="fa fa-exclamation-circle fa-3x mb-3"></i>
                            <p class="mb-0">Aucune zone !</p>
                        </div>
                    </div>
                </div>
				<?php
				// Locaux...
			} else { ?>

                <table class="w-100 admin table-v-middle">
                    <thead>
                    <tr>
                        <th class="w-50px <?php echo $utilisateur->isDev() ? '' : 'd-none';?>">ID</th>
                        <th>Désignation</th>
                        <th class="t-actions w-100px text-center">Modifier</th>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					foreach ($liste as $zone) { ?>

                        <tr>
                            <td class="<?php echo $utilisateur->isDev() ? '' : 'd-none';?>"><kbd><?php echo $zone->getId();?></kbd></td>
                            <td><?php echo $zone->getNom();?></td>
                            <td class="t-actions text-center">
                                <button type="button" class="btn btn-sm btn-secondary btnEdit" data-type="zone"><i class="fa fa-ellipsis-h"></i></button>
                            </td>
                        </tr>

					<?php }
					?>
                    </tbody>
                </table>
				<?php
			} // FIN test locaux

            ?>
        </div>
    </div>



	<?php
	exit;
} // FIN mode    

/* ----------------------------------------------
MODE - Charge la modale add/upd des locaux/zones
-----------------------------------------------*/
function modeChargeModaleAddUpd() {

	global $cnx;

	$locauxManager = new LocauxManager($cnx);

	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$type = isset($_REQUEST['type']) ? trim(strtolower($_REQUEST['type'])) : '';
	if ($type != 'zone' && $type != 'local') { exit('ERREUR^ERR_TYPE_LOCZON');}
    $typeObj = $type == 'zone' ? 'localZone' : $type;

	$methode = 'get'.ucfirst($typeObj);
	$classe = ucfirst($typeObj);
	if (!method_exists($locauxManager, $methode)) { exit('ERREUR^ERR_METHODE_'.strtoupper($methode));}
	if (!class_exists($classe)) { exit('ERREUR^ERR_CLASS_'.$classe);}

	$objet = $id > 0 ? $locauxManager->$methode($id) : new $classe([]);
	if (!$objet instanceof $classe) { exit('ERREUR^ERR_INST_OBJ_'.$classe);}

    $addTxt = $type == 'zone' ? 'd\'une nouvelle ' . $type : 'd\'un nouveau ' . $type;
    $updTxt = $type == 'zone' ? 'd\'une ' . $type : 'd\'un ' . $type;
    $desTxt = $type == 'zone' ? 'de la ' . $type : 'du ' . $type;

	// Titre
	echo '<i class="mr-1 fa fa-';
	echo $id > 0 ? 'edit' : 'plus-square';
	echo '"></i>';
	echo $id > 0 ? 'Modification '.$updTxt : 'Création '.$addTxt;
	echo '^';

	// Body
	?>
    <input type="hidden" name="mode" value="save<?php echo $classe?>"/>
    <input type="hidden" name="type" value="<?php echo $type?>"/>
    <input type="hidden" name="id" value="<?php echo $id; ?>"/>

    <div class="row">
        <div class="input-group col">
            <div class="input-group-prepend">
                <span class="input-group-text">Libellé</span>
            </div>
            <input type="text" name="nom" class="form-control" maxlength="128" placeholder="Désignation <?php echo $desTxt; ?>" value="<?php echo $objet->getNom();?>"/>
        </div>
    </div>
    <?php

    if ($type == 'local') { ?>
    <div class="row mt-2">
        <div class="col-6">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Numéro atelier</span>
                </div>
                <input type="text" name="numero" class="form-control text-center" maxlength="3" placeholder="0" value="<?php echo $objet->getNumero();?>"/>
            </div>
        </div>
        <div class="col-6 text-right">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Surface</span>
                </div>
                <input type="text" name="surface" class="form-control text-center" maxlength="6" placeholder="00.0" value="<?php echo $objet->getSurface();?>"/>
                <div class="input-group-append">
                    <span class="input-group-text">m²</span>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col">
             <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Vues associées</span>
                </div>
                 <select class="form-control selectpicker" multiple title="Toutes" name="vues[]" data-size="8" data-selected-text-format = "count > 2">
					 <?php
					 $vuesManager = new VueManager($cnx);
					 $vues = $vuesManager->getVuesListe();
					 foreach ($vues as $v) { ?>
<option value="<?php echo $v->getCode(); ?>" <?php
echo in_array(strtolower($v->getCode()), explode(',',$objet->getVues())) ? 'selected' : '';
?>><?php echo $v->getNom(); ?></option>
					 <?php }
					 ?>
                 </select>
             </div>

        </div>
    </div>
	<?php
    }
	exit;
} // FIN mode

/* -------------------------
MODE - Add/upd local
--------------------------*/
function modeSaveLocal() {

	global $cnx, $logsManager;

	$locauxManager = new LocauxManager($cnx);

	$id     = isset($_REQUEST['id'])    ? intval($_REQUEST['id']) : 0;
	$nom    = isset($_REQUEST['nom'])   ? trim(strip_tags(Outils::decodeCharAjac($_REQUEST['nom']))) : '';

	$numero = isset($_REQUEST['numero']) ? intval($_REQUEST['numero']) : 0;
	$surface = isset($_REQUEST['surface']) ? floatval($_REQUEST['surface']) : 0;
    $vuesArray = isset($_REQUEST['vues']) && is_array($_REQUEST['vues']) ? $_REQUEST['vues'] : [];



    $vues = empty($vuesArray) ? '' : strtolower(implode(',', $vuesArray));

	$local = $id > 0 ? $locauxManager->getLocal($id) : new Local([]);
	if (!$local instanceof Local) { exit('ERR_INSTOBJ_LOCAL'.$id);}

	if ($nom  == '') { exit('ERR_NOMVIDE');}


	$local->setNom($nom);
	$local->setNumero($numero);
	$local->setSurface($surface);
	$local->setVues($vues);


	echo $locauxManager->saveLocal($local) ? 1 : 0;

	$log = new Log([]);
	$log->setLog_type('info');
	$logTxt = $id > 0 ? 'Modification local nettoyage #'.$id : 'Création nouveau local nettoyage';
	$log->setLog_texte($logTxt);
	$logsManager->saveLog($log);

	exit;

} // FIN mode

/* -------------------------
MODE - Add/upd local zone
--------------------------*/
function modeSaveLocalZone() {

	global $cnx, $logsManager;

	$locauxManager = new LocauxManager($cnx);

	$id     = isset($_REQUEST['id'])    ? intval($_REQUEST['id']) : 0;
	$nom    = isset($_REQUEST['nom'])   ? trim(strip_tags(Outils::decodeCharAjac($_REQUEST['nom']))) : '';

	$zone = $id > 0 ? $locauxManager->getLocalZone($id) : new LocalZone([]);
	if (!$zone instanceof LocalZone) { exit('ERR_INSTOBJ_LOCALZONE'.$id);}

	if ($nom  == '') { exit('ERR_NOMVIDE');}


	$zone->setNom($nom);


	echo $locauxManager->saveLocalZone($zone) ? 1 : 0;

	$log = new Log([]);
	$log->setLog_type('info');
	$logTxt = $id > 0 ? 'Modification zone nettoyage #'.$id : 'Création nouvelle zone nettoyage';
	$log->setLog_texte($logTxt);
	$logsManager->saveLog($log);

	exit;

} // FIN mode

/* -------------------------
MODE - Supprime local
--------------------------*/
function modeSupprLocal() {

	global $cnx, $logsManager;

	$locauxManager = new LocauxManager($cnx);

	$id     = isset($_REQUEST['id'])    ? intval($_REQUEST['id']) : 0;
	if ($id == 0) { exit('ERR_ID_0');}

	$type = isset($_REQUEST['type']) ? trim(strtolower($_REQUEST['type'])) : '';
	if ($type != 'zone' && $type != 'local') { exit('ERREUR^ERR_TYPE_LOCZON');}
	$typeObj = $type == 'zone' ? 'localZone' : $type;

	$methode = 'get'.ucfirst($typeObj);
	$methodeSuppr = 'supprime'.ucfirst($typeObj);
	$classe = ucfirst($typeObj);
	if (!method_exists($locauxManager, $methode)) { exit('ERREUR^ERR_METHODEGET_'.strtoupper($methode));}
	if (!method_exists($locauxManager, $methodeSuppr)) { exit('ERREUR^ERR_METHODESUPPR_'.strtoupper($methode));}
	if (!class_exists($classe)) { exit('ERREUR^ERR_CLASS_'.$classe);}

	$objet = $id > 0 ? $locauxManager->$methode($id) : false;
	if (!$objet instanceof $classe) { exit('ERREUR^ERR_INST_OBJ_'.$classe);}


	echo $locauxManager->$methodeSuppr($objet) ? 1 : 0;

	$log = new Log([]);
	$log->setLog_type('warning');
	$log->setLog_texte('Suppression '.$type.' nettoyage #'.$id );
	$logsManager->saveLog($log);

	exit;

} // FIN mode

/* ----------------------------------------------
MODE - Charge la modale add planning
-----------------------------------------------*/
function modeChargeModaleNewPlanning() {

	global $cnx, $logsManager;

	$localManager = new LocauxManager($cnx);
    // Titre
    echo '<i class="mr-1 fa fa-plus-square"></i>Création d\'un nouveau planning';
    echo '^';

    // Body
    ?>
    <input type="hidden" name="mode" value="saveNewPlanning"/>
    <div class="row">
        <div class="col">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Local</span>
                </div>
                <select class="selectpicker form-control show-tick" name="id_local" title="Sélectionnez" data-live-search="true" data-live-search-placeholder="Rechercher" data-size="10">
					<?php
					foreach ($localManager->getListeLocaux() as $local) { ?>
                        <option value="<?php echo $local->getId(); ?>"><?php echo $local->getNumero(). ' - '.$local->getNom(); ?></option>
					<?php }
					?>
                </select>
            </div>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Zone</span>
                </div>
                <select class="selectpicker form-control show-tick" name="id_local_zone" title="Sélectionnez" data-live-search="true" data-live-search-placeholder="Rechercher" data-size="10">
					<?php
					foreach ($localManager->getListeLocalZones() as $zone) { ?>
                        <option value="<?php echo $zone->getId(); ?>"><?php echo $zone->getNom(); ?></option>
					<?php }
					?>
                </select>
            </div>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">Contexte</span>
                </div>
                <select class="selectpicker form-control show-tick" name="contexte">
			        <option value="0" selected>Process humide & sec</option>
			        <option value="1">Activité haché</option>
                </select>
            </div>
        </div>
    </div>

    <?php
    exit;

} // FIN mode


/* ----------------------------------------------
MODE - Crée une nouvelle ligne de planning
-----------------------------------------------*/
function modeSaveNewPlanning() {

	global $cnx, $logsManager;

	$planningManager = new NettoyageLocauxManager($cnx);

	$id_local = isset($_REQUEST['id_local']) ? intval($_REQUEST['id_local']) : 0;
	$id_local_zone = isset($_REQUEST['id_local_zone']) ? intval($_REQUEST['id_local_zone']) : 0;
    $contexte = isset($_REQUEST['contexte']) ? intval($_REQUEST['contexte']) : 0;

	if ($id_local == 0) { exit;}
	if ($id_local_zone == 0) { exit;}

	$nettLocal = new NettoyageLocal([]);
	$nettLocal->setId_local($id_local);
	$nettLocal->setId_local_zone($id_local_zone);
	$nettLocal->setContexte($contexte);
	$id = $planningManager->saveNettoyageLocal($nettLocal);
	if (intval($id) == 0) { exit;}

	$log = new Log([]);
	$log->setLog_type('info');
	$log->setLog_texte('Création nouveau planning de nettoyage NettoyageLocal ID#'.$id);
	$logsManager->saveLog($log);

	echo $id;
	exit;

} // FIN mode


/* ----------------------------------------------
MODE - Liste des plannings nettoyage
-----------------------------------------------*/
function modeShowListePlannings() {

	global $cnx;

	$planningManager = new NettoyageLocauxManager($cnx);

	$contexte = isset($_REQUEST['contexte']) ? intval($_REQUEST['contexte']) : 0;
	$id_local = isset($_REQUEST['id_local']) ? intval($_REQUEST['id_local']) : 0;
	$id_acteur = isset($_REQUEST['id_acteur']) ? intval($_REQUEST['id_acteur']) : 0;

	$params = [
        'contexte'  => $contexte,
        'id_local'  => $id_local,
        'id_acteur' => $id_acteur,
        'alertes_verbose' => true
    ];

	$liste = $planningManager->getListeNettLocaux($params);
	if (empty($liste)) { ?>

        <div class="row">
            <div class="col">
                <div class="alert alert-warning padding-50 text-center">
                    <i class="fa fa-exclamation-circle fa-3x mb-3"></i>
                    <p class="mb-0">Aucun planning défini !</p>
                </div>
            </div>
        </div>

    <?php exit; }

	$frequencesManager = new FrequencesManager($cnx);
	$frequences = $frequencesManager->getListeFrequences(['associatif' => true]);

	$intervenantsManager = new IntervenantsNettoyageManager($cnx);
	$intervenants = $intervenantsManager->getListeIntervenantsNettoyage(['associatif' => true]);

	?>
    <table class="admin w-100">
        <thead>
            <tr>
                <th rowspan="3">N°Atl</th>
                <th colspan="2">Local</th>
                <th colspan="4">Préparation</th>
                <th colspan="2">Nettoyage</th>
                <th colspan="2">Désinfection</th>
                <th rowspan="3">Qui</th>
                <th>Prél. EC</th>
                <th colspan="2">Détrg</th>
                <th>Rinc. EC</th>
                <th>Désinf°</th>
                <th>Rinc. EC</th>
                <th rowspan="3" class="t-actions">Alarme</th>
                <th rowspan="3" class="t-actions">Modif.</th>
            </tr>
            <tr>
                <th rowspan="2">Désignation</th>
                <th rowspan="2">Surface</th>
                <th rowspan="2">Prot°</th>
                <th rowspan="2">Dég.</th>
                <th rowspan="2">Dém.</th>
                <th rowspan="2">V/A</th>
                <th rowspan="2">Pdts</th>
                <th rowspan="2">Tps</th>
                <th rowspan="2">Pdts</th>
                <th rowspan="2">Tps</th>
                <th>4 ou 25 bar</th>
                <th colspan="2">xL pour 100L</th>
                <th>20 à 35 bar</th>
                <th>xL pour 100L</th>
                <th>4 ou 25 bar</th>
            </tr>
            <tr>
                <th>BP ou MP</th>
                <th>MAL</th>
                <th>MAC</th>
                <th>MP</th>
                <th>P</th>
                <th>BP ou MP</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $old_local = 0;

        $vuesManager = new VueManager($cnx);
        $vuesListe = $vuesManager->getVuesListeArray();
        foreach ($liste as $nett) {

            if ($old_local != $nett->getId_local()) { ?>

                <tr>
                    <td class="text-center text-primary"><?php echo $nett->getNumero() > 0 ? $nett->getNumero() : '&mdash;';?></td>
                    <td class="text-primary"><?php echo $nett->getNom_local(); ?></td>
                    <td class="texte-fin text-12 text-right"><?php echo $nett->getSurface(); ?> m²</td>
                    <td colspan="17">
                        <?php
                            $vuesCodes = explode(',',$nett->getVues());
                            foreach ($vuesCodes as $vcode) {
                                echo (isset($vuesListe[$vcode])) ? '<span class="badge badge-secondary mr-1 texte-fin"><i class="fa fa-bell mr-1"></i> '.$vuesListe[$vcode].'</span>' : '';
							}
                        ?>
                    </td>
                </tr>

                <?php $old_local = $nett->getId_local();
			}
            ?>
            <tr>
                <td></td>
                <td colspan="2"><?php echo $nett->getNom_zone(); ?></td>
                <td class="val"><?php echo isset($frequences[$nett->getId_freq_protection()]) ? $frequences[$nett->getId_freq_protection()]->getCode() : ''; ?></td>
                <td class="val"><?php echo isset($frequences[$nett->getId_freq_degrossi()]) ? $frequences[$nett->getId_freq_degrossi()]->getCode() : ''; ?></td>
                <td class="val"><?php echo isset($frequences[$nett->getId_freq_demontage()]) ? $frequences[$nett->getId_freq_demontage()]->getCode() : ''; ?></td>
                <td class="val"><?php echo isset($frequences[$nett->getId_freq_vidage()]) ? $frequences[$nett->getId_freq_vidage()]->getCode() : ''; ?></td>
                <td class="val"><?php echo $nett->getNom_nettoyage_pdt(); ?></td>
                <td class="val"><?php echo $nett->getNettoyage_temps() > 0 ? $nett->getNettoyage_temps() .' min' : ''; ?></td>
                <td class="val"><?php echo $nett->getNom_desinfection_pdt(); ?></td>
                <td class="val"><?php echo $nett->getDesinfection_temps() > 0 ? $nett->getNettoyage_temps() .' min' : ''; ?></td>
               <!-- <td class="val"><?php /*echo isset($intervenants[$nett->getId_acteur_nett()]) ? $intervenants[$nett->getId_acteur_nett()]->getCode() : ''; */?></td>-->
                <td class="val"><?php
                    foreach ($nett->getUsers() as $u) {
                        echo $u['trigramme']. ' ';
					}
                    ?></td>
                <td class="val"><?php echo isset($frequences[$nett->getId_freq_prelavage()]) ? $frequences[$nett->getId_freq_prelavage()]->getCode() : ''; ?></td>
                <td class="val"><?php echo isset($frequences[$nett->getId_freq_deterg_mal()]) ? $frequences[$nett->getId_freq_deterg_mal()]->getCode() : ''; ?></td>
                <td class="val"><?php echo isset($frequences[$nett->getId_freq_deterg_mac()]) ? $frequences[$nett->getId_freq_deterg_mac()]->getCode() : ''; ?></td>
                <td class="val"><?php echo isset($frequences[$nett->getId_freq_rincage_1()]) ? $frequences[$nett->getId_freq_rincage_1()]->getCode() : ''; ?></td>
                <td class="val"><?php echo isset($frequences[$nett->getId_freq_desinfection()]) ? $frequences[$nett->getId_freq_desinfection()]->getCode() : ''; ?></td>
                <td class="val"><?php echo isset($frequences[$nett->getId_freq_rincage_2()]) ? $frequences[$nett->getId_freq_rincage_2()]->getCode() : ''; ?></td>
                <td class="t-actions texte-fin text-11">
                    <?php
                    echo $nett->getAlerteVerbose() != '' ? $nett->getAlerteVerbose() : '<em class="texte-fin gris-7">Non</em>';
                    ?>
                </td>
                <td class="t-actions"><button type="button" class="btn btn-sm btn-secondary btnEdit" data-id="<?php echo $nett->getId(); ?>"><i class="fa fa-ellipsis-h"></i></button></td>
            </tr>
		<?php }
        ?>
        </tbody>
    </table>

    <?php
    exit;
} // FIN mode


/* ----------------------------------------------
MODE - Charge la modale edit nett local
-----------------------------------------------*/
function modeChargeModaleUpdNettLocal() {

	global $cnx;

	$nettLocalManager = new NettoyageLocauxManager($cnx);
	$consoManager = new ConsommablesManager($cnx);
	$frequencesManager = new FrequencesManager($cnx);
	$intervenantsManager = new IntervenantsNettoyageManager($cnx);
	$usersManager = new UserManager($cnx);
	$listeFc = $consoManager->getListeConsommablesFamilles(['id_type' => 2, 'show_inactifs' => true]);
	$listeFreq = $frequencesManager->getListeFrequences();
	$listeInters = $intervenantsManager->getListeIntervenantsNettoyage();

	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id == 0) { exit('ERREUR^ERR_ID_O');}

	$nettLocal = $nettLocalManager->getNettLocal($id);
	if (!$nettLocal instanceof NettoyageLocal) { exit('ERREUR^ERR_INSTOBJ_'.$id); }

	// Titre
	echo '<i class="mr-1 fa fa-edit"></i>'.$nettLocal->getNom_local().' - '.$nettLocal->getNom_zone().'^';

	// Corps
	?>
    <input type="hidden" name="mode" value="updLocalNett"/>
    <input type="hidden" name="id" value="<?php echo $nettLocal->getId(); ?>>"/>
    <div class="row">

        <div class="col-9">
            <div class="alert alert-secondary">
                <h6 class="text-center gris-9 text-uppercase text-20">Préparation</h6>
                <div class="row">
                    <div class="col">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Protection</span>
                            </div>
                            <?php showSelectPickerFrequences($listeFreq,'id_freq_protection',$nettLocal->getId_freq_protection()); ?>
                        </div>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Dégrossi</span>
                            </div>
							<?php showSelectPickerFrequences($listeFreq,'id_freq_degrossi',$nettLocal->getId_freq_degrossi()); ?>
                        </div>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Démontage</span>
                            </div>
							<?php showSelectPickerFrequences($listeFreq,'id_freq_demontage',$nettLocal->getId_freq_demontage()); ?>
                        </div>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Vidage/Appro.</span>
                            </div>
							<?php showSelectPickerFrequences($listeFreq,'id_freq_vidage',$nettLocal->getId_freq_vidage()); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-secondary mt-2">
                <h6 class="text-center gris-9 text-uppercase text-20">Nettoyage</h6>
                <div class="row">
                    <div class="col">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Produit</span>
                            </div>
                            <?php showSelectPickerProduits($listeFc,'nettoyage_id_fam_conso',$nettLocal->getNettoyage_id_fam_conso()); ?>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Temps de contact</span>
                            </div>
							<input type="text" class="form-control text-center" name="nettoyage_temps" maxlength="3" placeholder="-" value="<?php
                                echo $nettLocal->getNettoyage_temps() > 0 ?  $nettLocal->getNettoyage_temps() : ''; ?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text texte-fin text-12">min.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-secondary mt-2">
                <h6 class="text-center gris-9 text-uppercase text-20">Désinfection</h6>
                <div class="row">
                    <div class="col">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Produit</span>
                            </div>
							<?php showSelectPickerProduits($listeFc,'desinfection_id_fam_conso',$nettLocal->getDesinfection_id_fam_conso()); ?>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Temps de contact</span>
                            </div>
                            <input type="text" class="form-control text-center" name="desinfection_temps" maxlength="3" placeholder="-" value="<?php
							echo $nettLocal->getDesinfection_temps() > 0 ?  $nettLocal->getDesinfection_temps() : ''; ?>"/>
                            <div class="input-group-append">
                                <span class="input-group-text texte-fin text-12">min.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-4">
                    <div class="alert alert-secondary">
                        <h6 class="text-center gris-9 text-uppercase text-20">Intervenants</h6>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Qui</span>
                            </div>
                            <select class="selectpicker form-control show-tick" name="ids_users_nett[]" multiple>
                                <option value="0" title="&mdash;">&mdash; Aucun &mdash;</option>
                                <option data-divider="true"></option>
                                <?php
								$usersNett = $nettLocalManager->getIdsUsersNettoyageLocal($nettLocal);
                                foreach ($usersManager->getListeUsers() as $usrPn) { ?>
                                    <option value="<?php echo $usrPn->getId(); ?>" data-subtext="<?php echo $usrPn->getTrigramme(); ?>" title="<?php echo $usrPn->getTrigramme();?>" <?php
									echo in_array($usrPn->getId(),$usersNett) ? 'selected' : '';
									?>><?php echo $usrPn->getNomComplet(); ?></option>
								<?php }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="alert alert-secondary">
                        <h6 class="text-center gris-9 text-uppercase text-20">Prélavage</h6>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">BP/MP</span>
                            </div>
							<?php showSelectPickerFrequences($listeFreq,'id_freq_prelavage',$nettLocal->getId_freq_prelavage()); ?>
                        </div>
                    </div>
                </div>

                <div class="col-5">
                    <div class="alert alert-secondary">
                        <h6 class="text-center gris-9 text-uppercase text-20">Détergence</h6>
                        <div class="row">
                            <div class="col">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text texte-fin text-12">MAL</span>
                                    </div>
									<?php showSelectPickerFrequences($listeFreq,'id_freq_deterg_mal',$nettLocal->getId_freq_deterg_mal()); ?>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text texte-fin text-12">MAC</span>
                                    </div>
									<?php showSelectPickerFrequences($listeFreq,'id_freq_deterg_mac',$nettLocal->getId_freq_deterg_mac()); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

             <div class="row">
                <div class="col-4">
                    <div class="alert alert-secondary">
                        <h6 class="text-center gris-9 text-uppercase text-20">Rinçage MP</h6>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">20 à 35 bar</span>
                            </div>
							<?php showSelectPickerFrequences($listeFreq,'id_freq_rincage_1',$nettLocal->getId_freq_rincage_1()); ?>
                        </div>
                    </div>
                </div>
                 <div class="col-4">
                     <div class="alert alert-secondary">
                         <h6 class="text-center gris-9 text-uppercase text-20">Désinfection</h6>
                         <div class="input-group">
                             <div class="input-group-prepend">
                                 <span class="input-group-text texte-fin text-12">Pulvérisation</span>
                             </div>
							 <?php showSelectPickerFrequences($listeFreq,'id_freq_desinfection',$nettLocal->getId_freq_desinfection()); ?>
                         </div>
                     </div>
                 </div>
                 <div class="col-4">
                     <div class="alert alert-secondary">
                         <h6 class="text-center gris-9 text-uppercase text-20">Rinçage BP/MP</h6>
                         <div class="input-group">
                             <div class="input-group-prepend">
                                 <span class="input-group-text texte-fin text-12">4 ou 25 bar</span>
                             </div>
							 <?php showSelectPickerFrequences($listeFreq,'id_freq_rincage_2',$nettLocal->getId_freq_rincage_2()); ?>
                         </div>
                     </div>
                 </div>
             </div>





        </div>

        <!-- LEGENDE -->
        <div class="col-3">
            <table class="table w-100pc text-12">
                <tr><th colspan="2" class="text-center">Fréquences</th></tr>
                <?php
                foreach ($listeFreq as $freq) { ?>
                    <tr><td class="w-50px text-center"><?php echo $freq->getCode(); ?></td><td class="text-left texte-fin"><?php echo $freq->getNom(); ?></td></tr>
                <?php }
                ?>
                <tr><th colspan="2" class="text-center">Intervenants</th></tr>
				<?php
				foreach ($listeInters as $inter) { ?>
                    <tr><td class="w-50px text-left"><?php echo $inter->getCode(); ?></td><td class="text-left texte-fin"><?php echo $inter->getNom(); ?></td></tr>
				<?php }
				?>
                <tr><th colspan="2" class="text-center">Méthodes</th></tr>
                <tr><td class="w-50px text-left">BP</td><td class="text-left texte-fin">Basse Pression</td></tr>
                <tr><td class="w-50px text-left">MP</td><td class="text-left texte-fin">Moyenne Pression</td></tr>
                <tr><td class="w-50px text-left">MAL</td><td class="text-left texte-fin">Moussage Alcalin</td></tr>
                <tr><td class="w-50px text-left">MAC</td><td class="text-left texte-fin">Moussage Acide</td></tr>
            </table>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col">
            <div class="alert alert-warning">
<div class="row">
    <div class="col">
        <h6 class="text-uppercase text-16 padding-left-5"><i class="fa fa-clock mr-1"></i> Définition des alertes</h6>
    </div>
</div>

                <?php

                // On récupère l'alerte pour ce localnett
                $alertesManager = new NettoyageLocalAlertesManager($cnx);
                $alerte = $alertesManager->getNettoyageLocalAlerteByLocalNett($nettLocal->getId());
                if (!$alerte instanceof NettoyageLocalAlerte) { $alerte = new NettoyageLocalAlerte(); }



                ?>
                <div class="row alertes-nett">
                    <div class="col">
                        <div class="input-group d-inline-flex w-250px">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Mois</span>
                            </div>
                            <select class="selectpicker form-control show-tick alerte_mois" name="alerte_mois[]" multiple data-deselect-all-text="Tous les mois" data-actions-box="true" title="Tous les mois" data-selected-text-format = "count > 4">
								<?php
								foreach (Outils::getMoisIntListe() as $k => $v) {
								    $c = ucfirst(mb_substr($v,0,3, 'UTF-8'));
								    if ($v == 'juin' || $v == 'juillet') {
										$c = ucfirst(mb_substr($v,0,4, 'UTF-8'));
									}

									$selected = in_array(intval($k), $alerte->getMoisArray()) ? 'selected' : '';

									echo '<option value="'.$k.'" title="'.$c.'" '.$selected.'>'.ucfirst($v).'</option>';
								}
								?>
                            </select>
                        </div>

                        <div class="input-group d-inline-flex w-200px">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Semaine</span>
                            </div>
                            <select class="selectpicker form-control show-tick alerte_semaine" name="alerte_semaine[]" multiple data-deselect-all-text="Tous les mois" data-actions-box="true" title="Tous les mois">
                                <option value="1" title="1er" <?php echo in_array(1, $alerte->getSemainesArray()) ? 'selected' : ''; ?>>Le 1er</option>
                                <option value="2" title="2e" <?php echo in_array(2, $alerte->getSemainesArray()) ? 'selected' : ''; ?>>Le 2eme</option>
                                <option value="3" title="3e" <?php echo in_array(3, $alerte->getSemainesArray()) ? 'selected' : ''; ?>>Le 3eme</option>
                                <option value="4" title="Dern." <?php echo in_array(4, $alerte->getSemainesArray()) ? 'selected' : ''; ?>>Le dernier</option>
                            </select>
                        </div>

                        <div class="input-group d-inline-flex w-250px">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Jour</span>
                                <option data-divider="true"></option>
                            </div>
                            <select class="selectpicker form-control show-tick alerte_jour" name="alerte_jour[]" multiple data-deselect-all-text="Tous les jours" data-actions-box="true" title="Tous les jours" data-selected-text-format = "count > 4">
								<?php
								foreach (Outils::getJoursListe() as $k => $v) {
									$c = ucfirst(mb_substr($v,0,3, 'UTF-8'));

									$selected = in_array(intval($k), $alerte->getJoursArray()) ? 'selected' : '';

									echo '<option value="'.$k.'" title="'.$c.'" '.$selected.'>'.ucfirst($v).'</option>';
								}
								?>
                            </select>
                        </div>

                        <div class="input-group d-inline-flex w-150px">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Heure</span>
                            </div>
                            <select class="selectpicker form-control show-tick" name="alerte_heure" data-size="10">
								<?php
								for ($i = 0; $i <=23; $i++) {
									$selected = $i == intval($alerte->getHeure()) ? 'selected' : '';
									echo '<option value="'.$i.'" '.$selected.'>'.sprintf("%02d", $i).'</option>';
								}
								?>
                            </select>
                        </div>

                        <div class="input-group d-inline-flex w-150px">
                            <div class="input-group-prepend">
                                <span class="input-group-text texte-fin text-12">Minutes</span>
                            </div>
                            <select class="selectpicker form-control show-tick" name="alerte_minutes">
								<?php
								for ($i = 0; $i <=55; $i+=5) {
									$selected = $i == intval($alerte->getMinute()) ? 'selected' : '';
									echo '<option value="'.$i.'" '.$selected.'>'.sprintf("%02d", $i).'</option>';
								}
								?>
                            </select>
                        </div>
                        <div class="float-right">
                            <button type="button" class="btn btn-sm btn-warning btnNoAlerte hid"><i class="fa fa-times mr-1"></i> Effacer</button>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col padding-left-20" id="alerteVerbose">Aucune alerte</div>
                </div>
            </div>
        </div>
    </div>
	<?php
	exit;
} // FIN mode

/* ----------------------------------------------
FONCTION DEPORTEE - Selectpicker frequences
-----------------------------------------------*/
function showSelectPickerFrequences($listeFreq, $name, $id) {

    ?>
    <select class="selectpicker form-control show-tick" name="<?php echo $name; ?>">
        <option value="0" title="&mdash;">&mdash; Aucune &mdash;</option>
        <option data-divider="true"></option>
        <?php
		foreach ($listeFreq as $freq) { ?>
            <option value="<?php echo $freq->getId(); ?>" data-subtext="<?php echo $freq->getCode(); ?>" title="<?php echo $freq->getCode();?>" <?php
            echo $freq->getId() == $id ? 'selected' : '';
            ?>><?php echo $freq->getNom(); ?></option>
		<?php }
        ?>
    </select>
    <?php
    return true;
} // FIN fonction déportée

/* ----------------------------------------------
FONCTION DEPORTEE - Selectpicker produits nett
-----------------------------------------------*/
function showSelectPickerProduits($liste, $name, $id) {

	?>
    <select class="selectpicker form-control show-tick" name="<?php echo $name; ?>">
        <option value="0" title="&mdash;">&mdash; Aucun &mdash;</option>
        <option data-divider="true"></option>
		<?php
		foreach ($liste as $pdt) { ?>
            <option value="<?php echo $pdt->getId(); ?>" <?php
			echo $pdt->getId() == $id ? 'selected' : '';
			?>><?php echo $pdt->getNom(); ?></option>
		<?php }
		?>
    </select>
	<?php
	return true;
} // FIN fonction déportée


/* --------------------------
MODE - Update local nettoyage
---------------------------*/
function modeUpdLocalNett() {

    global $cnx,$logsManager;

    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    if ($id == 0) { exit('ERR_ID_0');}

    $nettoyageLocauxManager = new NettoyageLocauxManager($cnx);
    $nettLocal = $nettoyageLocauxManager->getNettLocal($id);

    if (!$nettLocal instanceof NettoyageLocal) { exit('ERR_INSTOBJ_'.$id); }

    $id_freq_protection         = isset($_REQUEST['id_freq_protection'])        ? intval($_REQUEST['id_freq_protection'])        : 0;
    $id_freq_degrossi           = isset($_REQUEST['id_freq_degrossi'])          ? intval($_REQUEST['id_freq_degrossi'])          : 0;
    $id_freq_demontage          = isset($_REQUEST['id_freq_demontage'])         ? intval($_REQUEST['id_freq_demontage'])         : 0;
    $id_freq_vidage             = isset($_REQUEST['id_freq_vidage'])            ? intval($_REQUEST['id_freq_vidage'])            : 0;
    $nettoyage_id_fam_conso     = isset($_REQUEST['nettoyage_id_fam_conso'])    ? intval($_REQUEST['nettoyage_id_fam_conso'])    : 0;
    $nettoyage_temps            = isset($_REQUEST['nettoyage_temps'])           ? intval($_REQUEST['nettoyage_temps'])           : 0;
    $desinfection_id_fam_conso  = isset($_REQUEST['desinfection_id_fam_conso']) ? intval($_REQUEST['desinfection_id_fam_conso']) : 0;
    $desinfection_temps         = isset($_REQUEST['desinfection_temps'])        ? intval($_REQUEST['desinfection_temps'])        : 0;
    //$id_acteur_nett             = isset($_REQUEST['id_acteur_nett'])            ? intval($_REQUEST['id_acteur_nett'])            : 0;
    $id_freq_prelavage          = isset($_REQUEST['id_freq_prelavage'])         ? intval($_REQUEST['id_freq_prelavage'])         : 0;
    $id_freq_deterg_mal         = isset($_REQUEST['id_freq_deterg_mal'])        ? intval($_REQUEST['id_freq_deterg_mal'])        : 0;
    $id_freq_deterg_mac         = isset($_REQUEST['id_freq_deterg_mac'])        ? intval($_REQUEST['id_freq_deterg_mac'])        : 0;
	$id_freq_rincage_1          = isset($_REQUEST['id_freq_rincage_1'])         ? intval($_REQUEST['id_freq_rincage_1'])         : 0;
	$id_freq_desinfection       = isset($_REQUEST['id_freq_desinfection'])      ? intval($_REQUEST['id_freq_desinfection'])      : 0;
	$id_freq_rincage_2          = isset($_REQUEST['id_freq_rincage_2'])         ? intval($_REQUEST['id_freq_rincage_2'])         : 0;

	$ids_users_nett             = isset($_REQUEST['ids_users_nett']) && is_array($_REQUEST['ids_users_nett']) ? $_REQUEST['ids_users_nett'] : [];
	$id_acteur_nett = 0;

	$alerte_mois        = isset($_REQUEST['alerte_mois'])    && is_array($_REQUEST['alerte_mois'])      ? $_REQUEST['alerte_mois']      : [];
	$alerte_semaine     = isset($_REQUEST['alerte_semaine']) && is_array($_REQUEST['alerte_semaine'])   ? $_REQUEST['alerte_semaine']   : [];
	$alerte_jour        = isset($_REQUEST['alerte_jour'])    && is_array($_REQUEST['alerte_jour'])      ? $_REQUEST['alerte_jour']      : [];
	$alerte_heure       = isset($_REQUEST['alerte_heure'])      ? intval($_REQUEST['alerte_heure'])     : 0;
	$alerte_minutes     = isset($_REQUEST['alerte_minutes'])    ? intval($_REQUEST['alerte_minutes'])   : 0;

	$nettLocal->setId_freq_protection($id_freq_protection);
	$nettLocal->setId_freq_degrossi($id_freq_degrossi);
	$nettLocal->setId_freq_demontage($id_freq_demontage);
	$nettLocal->setId_freq_vidage($id_freq_vidage);
	$nettLocal->setNettoyage_id_fam_conso($nettoyage_id_fam_conso);
	$nettLocal->setNettoyage_temps($nettoyage_temps);
	$nettLocal->setDesinfection_id_fam_conso($desinfection_id_fam_conso);
	$nettLocal->setDesinfection_temps($desinfection_temps);
	$nettLocal->setId_acteur_nett($id_acteur_nett);
	$nettLocal->setId_freq_prelavage($id_freq_prelavage);
	$nettLocal->setId_freq_deterg_mal($id_freq_deterg_mal);
	$nettLocal->setId_freq_deterg_mac($id_freq_deterg_mac);
	$nettLocal->setId_freq_rincage_1($id_freq_rincage_1);
	$nettLocal->setId_freq_desinfection($id_freq_desinfection);
	$nettLocal->setId_freq_rincage_2($id_freq_rincage_2);



	if (!$nettoyageLocauxManager->saveNettoyageLocal($nettLocal)) { exit('ERR_SAVE_'.$id); }

	// Users nettoyage
	if (!$nettoyageLocauxManager->saveUsersNettoyage($nettLocal, $ids_users_nett)) { exit('ERR_SAVE_USERS_NETT'); }

	$log = new Log([]);
	$log->setLog_texte('Modification du planning nettoyage local #'.$id);
	$log->setLog_type('info');
	$logsManager->saveLog($log);


	$alerte = new NettoyageLocalAlerte();
	$alerte->setId_nett_local($id);
	$alerte->setMois(implode(',',$alerte_mois));
	$alerte->setSemaine(implode(',',$alerte_semaine));
	$alerte->setJour(implode(',',$alerte_jour));
	$alerte->setHeure($alerte_heure);
	$alerte->setMinute($alerte_minutes);

	$aleresManager = new NettoyageLocalAlertesManager($cnx);
	$aleresManager->supprAlertesLocal($id);

	if (empty($alerte_mois) && empty($alerte_semaine) && empty($alerte_jour) && $alerte_heure == 0 && $alerte_minutes == 0) {
		$log = new Log([]);
		$log->setLog_texte('Suppression de l\'alerte du planning nettoyage local #'.$id);
		$log->setLog_type('info');
		$logsManager->saveLog($log);
	    exit('1');
    }
	$log = new Log([]);
	if ($aleresManager->saveNettoyageLocalAlerte($alerte)) {
		$log->setLog_texte('Alerte du planning nettoyage local #'.$id." mise à jour");
		$log->setLog_type('info');
    } else {
		$log->setLog_texte('Erreur SAVE alerte du planning nettoyage local #'.$id);
		$log->setLog_type('danger');
    }
	$logsManager->saveLog($log);
	exit('1');

} // FIN mode


/* -----------------------------
MODE - Supprime local nettoyage
------------------------------*/
function modeSupprNettoyageLocal() {

	global $cnx,$logsManager;

	$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	if ($id == 0) { exit('ERR_ID_0');}

	$nettoyageLocauxManager = new NettoyageLocauxManager($cnx);
	$nettLocal = $nettoyageLocauxManager->getNettLocal($id);

	if (!$nettLocal instanceof NettoyageLocal) { exit('ERR_INSTOBJ_'.$id); }

	if (!$nettoyageLocauxManager->supprimeNettoyageLocal($nettLocal)) { exit('ERR_SAVE_'.$id); }

	$log = new Log([]);
	$log->setLog_texte('Suppression du planning nettoyage local #'.$id);
	$log->setLog_type('warning');
	$logsManager->saveLog($log);

	exit('1');

} // FIN mode

/* -----------------------------
MODE - Export PDF
------------------------------*/
function modeExportPdf() {

	global $cnx;

	$planningManager = new NettoyageLocauxManager($cnx);

	$contexte = isset($_REQUEST['contexte']) ? intval($_REQUEST['contexte']) : 0;
	$id_local = isset($_REQUEST['id_local']) ? intval($_REQUEST['id_local']) : 0;
	$id_acteur = isset($_REQUEST['id_acteur']) ? intval($_REQUEST['id_acteur']) : 0;

	$params = [
		'contexte'  => $contexte,
		'id_local'  => $id_local,
		'id_acteur' => $id_acteur
	];

	$liste = $planningManager->getListeNettLocaux($params);
	if (empty($liste)) { exit('-1'); }

	$frequencesManager = new FrequencesManager($cnx);
	$frequences = $frequencesManager->getListeFrequences(['associatif' => true]);

	$intervenantsManager = new IntervenantsNettoyageManager($cnx);
	$intervenants = $intervenantsManager->getListeIntervenantsNettoyage(['associatif' => true]);

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	$contexteTxt = $contexte == 0 ? 'PROCESS HUMIDE & SEC' : 'ACTIVITE HACHE'; // (i) Les accents ne passent pas en majuscule lors de la conversion en PDF

	ob_start();

	$configManager = new ConfigManager($cnx);
	$pdf_top_nett = $configManager->getConfig('pdf_top_nett');
	$margeEnTetePdf = $pdf_top_nett instanceof Config ?  (int)$pdf_top_nett->getValeur() : 0;

	$content_header = '<div class="header"><table class="table w100">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10 titredoc">
                           <h1>PLANNING NETTOYAGE - '.$contexteTxt.'</h1>
                           <img src="'.__CBO_ROOT_URL__.'img/icones-nett.jpg" alt="" class="icones-nett"/>
                           <img src="'.__CBO_ROOT_URL__.'img/logo-qse.jpg" alt="" class="logo-qse"/>
                        </td>
                        <td class="w33 text-right">
                            <p class="text-18"><b>PROFIL EXPORT</b></p>
                            <p class="text-12 gris-7">Cahier des charges de nettoyage</p>
                            <p class="text-10 gris-7">Page [[page_cu]]/[[page_nb]]</p>
                        </td>
                    </tr>                
                </table></div>
                <div class="legendes">
                <table class="w100">
                <tr>
                    <td class="w25"><h2>FREQUENCES</h2>
                        <table class="w100">';
	foreach ($frequences as $freq) {
		$content_header.= '<tr><td class="w10 text-bleu">'.$freq->getCode().'</td><td class="w90">'.$freq->getNom().'</td></tr>';
    }
	$content_header.= '</table>
                    </td>
                    <td class="w25"><h2>MATERIELS</h2>
                        <table class="w100">
                            <tr><td class="w20"><img src="'.__CBO_ROOT_URL__.'img/nett-mat-nhp.jpg" alt="" class="nett-materiel"/></td><td class="w80">Nettoyeur haute pression</td></tr>
                            <tr><td class="w20"><img src="'.__CBO_ROOT_URL__.'img/nett-mat-nhp2.jpg" alt="" class="nett-materiel"/></td><td class="w80">Nettoyeur haute pression</td></tr>
                            <tr><td class="w20"><img src="'.__CBO_ROOT_URL__.'img/nett-mat-pul.jpg" alt="" class="nett-materiel"/></td><td class="w80">Pulvérisateur</td></tr>
                            <tr><td class="w20"></td><td class="w80 text-jaune">Autolaveuse (Roctonic)</td></tr>
                            <tr><td class="w20"><img src="'.__CBO_ROOT_URL__.'img/nett-mat-cnd.jpg" alt="" class="nett-materiel"/></td><td class="w80">Centrale de nettoyage et désinfection</td></tr>
                            <tr><td class="w20"><img src="'.__CBO_ROOT_URL__.'img/nett-mat-rac.jpg" alt="" class="nett-materiel"/></td><td class="w80">Essuyage Manuel (à la raclette)</td></tr>
                        </table>
                    </td>
                    <td class="w20"><h2>INTERVENANTS</h2>
                       <table class="w100">';
	foreach ($intervenants as $inter) {
		$content_header.= '<tr><td class="w10 text-bleu">'.$inter->getCode().'</td><td class="w90">'.$inter->getNom().'</td></tr>';
	}
	$content_header.= '</table></td>
                    <td class="w30"><h2>METHODES</h2>
                    <table class="w100">
                        <tr><td class="w10 text-bleu">BP/MP</td><td class="w90">Basse ou Moyenne Pression</td></tr>
                        <tr><td class="w100" colspan="2"><h3>Prélavage à l\'eau chaude</h3></td></tr>
                        <tr><td class="w10 text-bleu">MAL</td><td class="w90">Moussage Alcalain = Détergence / Désinfection facultative</td></tr>
                        <tr><td class="w10 text-bleu"></td><td class="w90 text-jaune">Deptal Mcl à 3% à température ambiante (eau froide)</td></tr>
                        <tr><td class="w10 text-bleu">MAC</td><td class="w90">Moussage Acide = Détergence</td></tr>
                        <tr><td class="w10 text-bleu"></td><td class="w90 text-jaune">Deptacid SM à 3%</td></tr>
                        <tr><td class="w10 text-bleu">P</td><td class="w90">Pulvérisation</td></tr>
                        <tr><td class="w10 text-bleu">O/P</td><td class="w90">Pulvérisation (deptil HDS sans rinçage) / Ordonnancement</td></tr>
                        <tr><td class="w100" colspan="2"><h3>Rinçage à l\'eau chaude</h3></td></tr>
                        <tr><td class="w10 text-bleu"></td><td class="w90 text-jaune">Deptil Mycocide désinfectant à 1%</td></tr>
                        <tr><td class="w10 text-bleu">NSF</td><td class="w90">Nettoyage Surfaces Fermées</td></tr>
                        <tr><td class="w10 text-bleu">LA</td><td class="w90">Lavage des sols à l\'autolaveuse</td></tr>
                        <tr><td class="w10 text-bleu">D</td><td class="w90">Degrossissage à sec : aspiration</td></tr>
                    </table>
                    </td>
                </tr>
                </table>
                </div>
                <div class="entete-table">
                    <table class="w100 mt-5" cellspacing="0">
                        <tr>
                            <td class="w3 bg-bleu" style="position: relative" rowspan="3"><div style="rotate:90;position:absolute;left:12px;top:5px;">Nombre / N° atelier</div></td>
                            <td class="w20 p-5-5 bg-bleu" colspan="2">Désignation des locaux</td>
                            <td class="w16 p-5-5 bg-gris" colspan="4">Préparation</td>
                            <td class="w12 p-5-5 bg-gris" colspan="2">Nettoyage</td>
                            <td class="w12 p-5-5 bg-gris" colspan="2">Désinfection</td>
                            <td class="w4 p-5-5" rowspan="3">Qui</td>
                            <td class="w7 p-5-5 bg-vert">Prélavage à l\'eau chaude</td>
                            <td class="w8 p-5-5 bg-vert" colspan="2">Détergence</td>
                            <td class="w6 p-5-5 bg-jaune ">Rinçage à l\'eau chaude</td>
                            <td class="w6 p-5-5 bg-vert">Désinfection</td>
                            <td class="w6 p-5-5 bg-jaune ">Rinçage à l\'eau chaude</td>
                        </tr>
                        <tr>
                            <td class="w15" rowspan="2"></td>
                            <td class="w5" rowspan="2"><div style="rotate:90;position:relative;left:-5px;top:5px;">Surface m²</div></td>
                            <td class="w4" rowspan="2"><div style="rotate:90;position:relative;left:-5px;top:10px;">Protection</div></td>
                            <td class="w4" rowspan="2"><div style="rotate:90;position:relative;left:-5px;top:10px;">Dégrossi</div></td>
                            <td class="w4" rowspan="2"><div style="rotate:90;position:relative;left:-5px;top:10px;">Démontage</div></td>
                            <td class="w4" rowspan="2"><div style="rotate:90;position:relative;left:-10px;top:5px;height:15px;width:50px;">Vidage / Appro.</div></td>
                            <td class="w7" rowspan="2"><div style="rotate:90;">Produits</div></td>
                            <td class="w5" rowspan="2"><div style="rotate:90;position:relative;left:-10px;top:5px;height:15px;width:50px;">Temps de contact</div></td>
                            <td class="w7" rowspan="2"><div style="rotate:90;">Produits</div></td>
                            <td class="w5" rowspan="2"><div style="rotate:90;position:relative;left:-10px;top:5px;height:15px;width:50px;">Temps de contact</div></td>
                            <td class="w7 vmiddle bg-jaune">4 ou 25 bar</td>
                            <td class="w8 vmiddle gris-5" colspan="2">xL pour 100L</td>
                            <td class="w6 vmiddle gris-5">20 à 35 bar</td>
                            <td class="w6 vmiddle gris-5">xL pour 100L</td>
                            <td class="w6 vmiddle gris-5">4 ou 25 bar</td>
                        </tr>
                        <tr>
                            <td class="w7 vmiddle">BP ou MP</td>
                            <td class="w4 vmiddle bg-bleu">MAL</td>
                            <td class="w4 vmiddle bg-rouge">MAC</td>
                            <td class="w6 vmiddle bg-jaune">MP</td>
                            <td class="w6 vmiddle">P</td>
                            <td class="w6 vmiddle">BP ou MP</td>
                        </tr>
                    </table>
                </div>';
	$contentPdf = '<page backtop="'.(int)$margeEnTetePdf.'mm">';
	$contentPdf.= '<page_header><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf.css').'</style><style type="text/css">'.file_get_contents(__CBO_CSS_URL__.'pdf-nett.css').'</style>'.$content_header.'</page_header>';


	$contentPdf.= '<table class="w100 table-contenu" cellspacing="0">';

	$old_local = 0;
	foreach ($liste as $nett) {

		if ($old_local != $nett->getId_local()) {

            $num = $nett->getNumero() > 0 ? $nett->getNumero() : '&mdash;';
			$contentPdf.= '
            <tr>
                <td class="w3 text-center text-bleu">'.$num.'</td>
                <td class="w15 text-bleu">'.$nett->getNom_local().'</td>
                <td class="w5 text-center">'.$nett->getSurface().' m²</td>
                <td class="w77" colspan="15"></td>
            </tr>';

			$old_local = $nett->getId_local();
		}

		$f1 = isset($frequences[$nett->getId_freq_protection()]) ? $frequences[$nett->getId_freq_protection()]->getCode() : '';
		$f2 = isset($frequences[$nett->getId_freq_degrossi()]) ? $frequences[$nett->getId_freq_degrossi()]->getCode() : '';
		$f3 = isset($frequences[$nett->getId_freq_demontage()]) ? $frequences[$nett->getId_freq_demontage()]->getCode() : '';
		$f4 =  isset($frequences[$nett->getId_freq_vidage()]) ? $frequences[$nett->getId_freq_vidage()]->getCode() : '';

		$t1 = $nett->getNettoyage_temps() > 0 ? $nett->getNettoyage_temps() .' min' : '';
		$t2 = $nett->getDesinfection_temps() > 0 ? $nett->getNettoyage_temps() .' min' : '';

		$ki = isset($intervenants[$nett->getId_acteur_nett()]) ? $intervenants[$nett->getId_acteur_nett()]->getCode() : '';

		$f5 = isset($frequences[$nett->getId_freq_prelavage()]) ? $frequences[$nett->getId_freq_prelavage()]->getCode() : '';
		$f6 = isset($frequences[$nett->getId_freq_deterg_mal()]) ? $frequences[$nett->getId_freq_deterg_mal()]->getCode() : '';
		$f7 =  isset($frequences[$nett->getId_freq_deterg_mac()]) ? $frequences[$nett->getId_freq_deterg_mac()]->getCode() : '';
		$f8 = isset($frequences[$nett->getId_freq_rincage_1()]) ? $frequences[$nett->getId_freq_rincage_1()]->getCode() : '';
        $f9 = isset($frequences[$nett->getId_freq_desinfection()]) ? $frequences[$nett->getId_freq_desinfection()]->getCode() : '';
        $f0 = isset($frequences[$nett->getId_freq_rincage_2()]) ? $frequences[$nett->getId_freq_rincage_2()]->getCode() : '';

		$contentPdf.= '
        <tr>
            <td class="w3 text-center"></td>
            <td class="w20" colspan="2">'. $nett->getNom_zone().'</td>
            <td class="w4 text-center">'.$f1.'</td>
            <td class="w4 text-center">'.$f2.'</td>
            <td class="w4 text-center">'.$f3.'</td>
            <td class="w4 text-center">'.$f4.'</td>
            <td class="w7">'.$nett->getNom_nettoyage_pdt().'</td>
            <td class="w5 text-center">'.$t1.'</td>
            <td class="w7">'.$nett->getNom_desinfection_pdt().'</td>
            <td class="w5 text-center">'.$t2.'</td>
            <td class="w4 text-center">'.$ki.'</td>
            <td class="w7 text-center">'.$f5.'</td>
            <td class="w4 text-center">'.$f6.'</td>
            <td class="w4 text-center">'.$f7.'</td>
            <td class="w6 text-center">'.$f8.'</td>
            <td class="w6 text-center">'.$f9.'</td>
            <td class="w6 text-center">'.$f0.'</td>
         
        </tr>';
	}
	$contentPdf.= '</table>';


	$contentPdf.= '</page>'. ob_get_clean();


	$nom_fichier = 'plannett.pdf';
	$chemin = __CBO_ROOT_PATH__.'/temp/'.$nom_fichier;
	if (file_exists($chemin)) { unlink($chemin); }

	try {
		$html2pdf = new HTML2PDF('L', 'A4', 'fr', true, 'UTF-8', [5, 5, 5, 5]);
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML($contentPdf);;
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

// Contenu de la modale heures des alarmes
function modeModalHeuresAlarme() {

	global $cnx;
	$configManager = new ConfigManager($cnx);

	$heuresConfig = $configManager->getConfig('heures_alarmes_plannett');
	if (!$heuresConfig instanceof Config) {

		$heuresConfig = new Config([]);
		$heuresConfig->setClef('heures_alarmes_plannett');
		$heuresConfig->setDescription("Heures des alarmes pour le planning de nettoyage des agents d'entretien");
		$heuresConfig->setValeur('');
		$heuresConfig->setDate_maj(date('Y-m-d H:i:s'));
		$configManager->saveConfig($heuresConfig);
		$heuresConfig = $configManager->getConfig('heures_alarmes_plannett');
    }
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

	$heuresConfig = $configManager->getConfig('heures_alarmes_plannett');
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

// Génère le PDF des signatures du planning (calendrier)
function modeGenerePdfSignatures() {

	$mois   = isset($_REQUEST['mois'])  ? $_REQUEST['mois'] : '';
	$an     = isset($_REQUEST['an'])    ? $_REQUEST['an']   : '';

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');
	ob_start();
	$content = genereContenuPdf($mois, $an);
	$content .= ob_get_clean();

    // On supprime tous les fichiers du même genre sur le serveur
	foreach (glob(__CBO_ROOT_PATH__.'/temp/plannettsign-*.pdf') as $fichier) {
		unlink($fichier);
	}

	try {
		$nom_fichier = 'plannettsign-'.$an.$mois.'.pdf';
		$html2pdf = new HTML2PDF('P', 'A4', 'fr', false, 'ISO-8859-15');
		$html2pdf->pdf->SetAutoPageBreak(false, 0);
		$html2pdf->setDefaultFont('helvetica');
		$html2pdf->writeHTML(utf8_decode($content));;
		$savefilepath = __CBO_ROOT_PATH__.'/temp/'.$nom_fichier;
		$html2pdf->Output($savefilepath, 'F');
		echo __CBO_TEMP_URL__.$nom_fichier;
	}
	catch(HTML2PDF_exception $e) {
		exit;
	}

} // FIN mode

// Fonction déportée pour la génération du contenu du PDF
function genereContenuPdf($mois, $an) {

	global $cnx;

	// HEAD
	$contenu = '<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <style type="text/css">
  
    * { margin:0; padding: 0; }
  
    .nomargin { margin: 0;}
    .header { border-bottom: 2px solid #ccc; }
    .header img.logo { width: 200px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .table { border-collapse: collapse; }
    .table-donnees th { font-size: 11px; }
    .table-liste th { font-size: 9px; background-color: #d5d5d5; padding:3px; }
    .table-liste td { font-size: 9px; padding-top: 3px; padding-bottom: 3px; border-bottom: 1px solid #ccc;}
    .table-liste td.no-bb { border-bottom: none; padding-bottom: 0px; }
    .table-liste tr.soustotal td { background-color: #d5d5d5; }
    .titre {
       background-color: teal;
       color: #fff;
       padding: 3px;
       text-align: center;
       font-weight: normal;
       font-size: 14px;
    }
    
    .w100 { width: 100%; }
    .w75 { width: 75%; }
    .w50 { width: 50%; }
    .w40 { width: 40%; }
    .w25 { width: 25%; }
    .w33 { width: 33%; }
    .w34 { width: 34%; }
    .w30 { width: 30%; }
    .w20 { width: 20%; }
    .w30 { width: 30%; }
    .w15 { width: 15%; }
    .w35 { width: 35%; }
    .w5  { width: 5%;  }
    .w10 { width: 10%; }
    
    .text-6 { font-size: 6px; }
    .text-7 { font-size: 7px; }
    .text-8 { font-size: 8px; }
    .text-9 { font-size: 9px; }
    .text-10 { font-size: 10px; }
    .text-11 { font-size: 11px; }
    .text-12 { font-size: 12px; }
    .text-14 { font-size: 14px; }
    .text-16 { font-size: 16px; }
    .text-18 { font-size: 18px; }
    .text-20 { font-size: 20px; }
    
    .gris-3 { color:#333; }
    .gris-5 { color:#555; }
    .gris-7 { color:#777; }
    .gris-9 { color:#999; }
    .gris-c { color:#ccc; }
    .gris-d { color:#d5d5d5; }
    .gris-e { color:#e5e5e5; }
    
    .mt-0 { margin-top: 0px; }
    .mt-2 { margin-top: 2px; }
    .mt-5 { margin-top: 5px; }
    .mt-10 { margin-top: 10px; }
    .mt-15 { margin-top: 15px; }
    .mt-20 { margin-top: 20px; }
    .mt-25 { margin-top: 25px; }
    .mt-50 { margin-top: 50px; }
    
    .mb-0 { margin-bottom: 0px; }
    .mb-2 { margin-bottom: 2px; }
    .mb-5 { margin-bottom: 5px; }
    .mb-10 { margin-bottom: 10px; }
    .mb-15 { margin-bottom: 15px; }
    .mb-20 { margin-bottom: 20px; }
    .mb-25 { margin-bottom: 25px; }
    .mb-50 { margin-bottom: 50px; }
    
    .mr-0 { margin-right: 0px; }
    .mr-2 { margin-right: 2px; }
    .mr-5 { margin-right: 5px; }
    .mr-10 { margin-right: 10px; }
    .mr-15 { margin-right: 15px; }
    .mr-20 { margin-right: 20px; }
    .mr-25 { margin-right: 25px; }
    .mr-50 { margin-right: 50px; }
    
    .ml-0 { margin-left: 0px; }
    .ml-2 { margin-left: 2px; }
    .ml-5 { margin-left: 5px; }
    .ml-10 { margin-left: 10px; }
    .ml-15 { margin-left: 15px; }
    .ml-20 { margin-left: 20px; }
    .ml-25 { margin-left: 25px; }
    .ml-50 { margin-left: 50px; }
    
    .pt-0 { padding-top: 0px; }
    .pt-2 { padding-top: 2px; }
    .pt-5 { padding-top: 5px; }
    .pt-10 { padding-top: 10px; }
    .pt-15 { padding-top: 15px; }
    .pt-20 { padding-top: 20px; }
    .pt-25 { padding-top: 25px; }
    .pt-50 { padding-top: 50px; }
    
    .pb-0 { padding-bottom: 0px; }
    .pb-2 { padding-bottom: 2px; }
    .pb-5 { padding-bottom: 5px; }
    .pb-10 { padding-bottom: 10px; }
    .pb-15 { padding-bottom: 15px; }
    .pb-20 { padding-bottom: 20px; }
    .pb-25 { padding-bottom: 25px; }
    .pb-50 { padding-bottom: 50px; }
    
    .pr-0 { padding-right: 0px; }
    .pr-2 { padding-right: 2px; }
    .pr-5 { padding-right: 5px; }
    .pr-10 { padding-right: 10px; }
    .pr-15 { padding-right: 15px; }
    .pr-20 { padding-right: 20px; }
    .pr-25 { padding-right: 25px; }
    .pr-50 { padding-right: 50px; }
    
    .pl-0 { padding-left: 0px; }
    .pl-2 { padding-left: 2px; }
    .pl-5 { padding-left: 5px; }
    .pl-10 { padding-left: 10px; }
    .pl-15 { padding-left: 15px; }
    .pl-20 { padding-left: 20px; }
    .pl-25 { padding-left: 25px; }
    .pl-50 { padding-left: 50px; }
    
    .plnet-dimanche td { background-color: #500; color:#ddd; }
    .text-danger { color: #d9534f; }
    
    .img-signature { height: 40px; display: inline-block; }
    ul, li { list-style-type: none; margin:0; padding: 0; }
    
  </style> 
</head>
<body>';

	$contenu.= '<div class="header">
                <table class="table w100">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_URL__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            <span class="text-12">Calendrier des interventions journalières</span><br><span class="text-16"><b>'.strtoupper(Outils::getMoisListe()[$mois]).' '.$an.'</b></span>
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-12 gris-7">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div>';

	$nettoyageSignaturesManager = new NettoyageSignaturesManager($cnx);

	$listeSignaruresMois = $nettoyageSignaturesManager->getListeNettoyageSignatures(['mois' => $mois, 'an' => $an]);

	$signaruresMois = [];
	foreach ($listeSignaruresMois as $sm) {
		$signaruresMois[$sm->date_only][] = $sm;
	}

	$nbJoursMois = cal_days_in_month(CAL_GREGORIAN, intval($mois), intval($an));


	$contenu.= '<table class="table table-liste w100 mt-15">
                <tr>
                    <th class="w15" colspan="2">Jour</th>
                    <th class="w15 text-center">Heure</th>
                    <th class="w20 text-center">Agent d\'entretien</th>
                    <th class="w50">Signatures</th>
                </tr>';

	for ($j = 1; $j <= $nbJoursMois; $j++) {
		$j0 = $j < 10 ? '0' . $j : $j;
		$dateJour = $an . '-' . $mois . '-' . (string)$j0;
		$jourSem = date('w', strtotime($dateJour));

		$signaturesJour = isset($signaruresMois[$dateJour]) ? $signaruresMois[$dateJour] : [];
		$futur = $dateJour > date('Y-m-d');

		$classes = '';
		$classes.= $jourSem == 0 ? ' plnet-dimanche ' : '';


		$contenu.= '<tr class="'.$classes.'">
                        <td class="w10 pl-5">'.ucfirst(Outils::getJourFromSql($jourSem+1)).'</td>
                        <td class="w5 text-center">'.$j.'</td>
                        <td class="w15 text-center">';
	        if (!empty($signaturesJour)) {
				$i = 0;
				foreach ($signaturesJour as $sj) {
					$contenu.= $i > 0 ? '<br>' : '';
					$contenu .= $sj->getHeure();
					$i++;
				}
			}
	$contenu.='</td>
        <td class="w20 text-center">';

	if (!empty($signaturesJour)) {

	    $i = 0;
		foreach ($signaturesJour as $sj) {
		    $contenu.= $i > 0 ? '<br>' : '';
			$contenu.= $sj->getNom_user();
			$i++;
		}
	}
	$contenu.= '</td>
                <td class="w50">';
	if (!empty($signaturesJour)) {
		foreach ($signaturesJour as $sj) {
			$png_url =__CBO_UPLOADS_PATH__.'signatures/nett/'.$sj->getId().".png";
			if (file_exists($png_url)) {
				$contenu.= '<img src="'.__CBO_UPLOADS_URL__.'signatures/nett/'.$sj->getId().'" class="img-signature"/>';
			}
		}
	}
	$contenu.= '
	</td>
</tr>';
	}


	$contenu.= '</table>';

	// FOOTER
	$contenu.= '<table class="w100 gris-9">
                    <tr>
                        <td class="w50 text-8">Document édité le '.date('d/m/Y').' à '.date('H:i:s').'</td>
                        <td class="w50 text-right text-6">&copy; 2019 IPREX / INTERSED </td>
                    </tr>
                </table>
            </body>
        </html>';


	$contenu = str_replace('Œ', 'OE', $contenu);

	// RETOUR CONTENU
	return $contenu;

	return $contenu;
} // FIN fonction

// Vérifie si c'est l'heure d'afficher une alarme (agents d'entretiens)
function modeCheckAlertePlanNettoyage() {
	global $cnx;

    $alertesNettManager = new NettoyageLocalAlertesManager($cnx);

    // Les alertes sont minutés pas paliers de 5 minutes, donc on ne peux avoit 14h03 par exemple
    // Si on est pas en multiple de 5 dans l'heure du serveur, on ne surcharge pas le réseau...
    if ((int)date('i') % 5 !== 0) {
        exit;  // Toujours retourner un string vide si rien à faire.
    }

    $vue = isset($_REQUEST['vue']) ? strtolower(trim($_REQUEST['vue'])) : '';

    // On renvoie les ID des locaux nettoyages correspondant aux alertes s'il y en a, rien sinon
    echo $alertesNettManager->getIdsLocauxByAlerteNow($vue); // Toujours retourner un string vide si rien à faire

	exit; // Toujours retourner un string vide si rien à faire.

} // FIN mode

// Retourne le contenu de la modale d'alerte du planning nettoyage pour les agents d'entretien
function modeGetModaleAlertePlanNett() {
	global $cnx;

	$ids_nett_locaux = isset($_REQUEST['id_nett_local']) ? trim($_REQUEST['id_nett_local']) : '';
    if ($ids_nett_locaux == '') { exit; }
	$idsArray = explode(',', $ids_nett_locaux);
    if (empty($idsArray)) { exit; }

	$nettLocalManager = new NettoyageLocauxManager($cnx);
	$contenu = '';
    foreach ($idsArray as $id_nett_local) {
		$nettLocal = $nettLocalManager->getNettLocal($id_nett_local);
		if ($nettLocal instanceof NettoyageLocal) {
			$contenu.= '<div>'.$nettLocal->getNom_local() . ' ' . $nettLocal->getNom_zone();
			if ($nettLocal->getNom_nettoyage_pdt() != '' || $nettLocal->getNom_desinfection_pdt() != '') {
			    $contenu.= '<p class="text-20">';
				$contenu.= $nettLocal->getNom_nettoyage_pdt() != '' ? $nettLocal->getNom_nettoyage_pdt() : '';
				$contenu.= $nettLocal->getNettoyage_temps() != '' ? ' ('.$nettLocal->getNettoyage_temps().' min)' : '';
				$contenu.= $nettLocal->getNom_nettoyage_pdt() != '' && $nettLocal->getNom_desinfection_pdt() != '' ? ', ' : '';
				$contenu.= $nettLocal->getNom_desinfection_pdt() != '' ? $nettLocal->getNom_desinfection_pdt() : '';
				$contenu.= $nettLocal->getDesinfection_temps() != '' ? ' ('.$nettLocal->getDesinfection_temps().' min)' : '';
				$contenu.= '</p>';
            }
			$contenu.= '<divs>';
		}
    }
	?>
    <i class="fa fa-exclamation-circle fa-5x mb-2 clignotte pt-5 pb-2"></i>
    <h2 class="pb-1">Agents d'entretien</h2>
    <h3 class="pb-5">Calendrier des interventions journalières</h3>
    <!--<button type="button" class="btn btn-light btn-lg btnSignerPlanNett">Appuyez ici pour signer</button>-->
	<?php
    echo $contenu;
	exit;

} // FIN mode

// Retourne le contenu de la modale de signature du planning nettoyage pour les agents d'entretien
function modeGetModaleSignerPlanNett() {
	global $cnx;

	$id_user = isset($_REQUEST['id_user']) ? intval($_REQUEST['id_user']) : 0;
	if ($id_user == 0) {echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle fa-3x mb-3"></i><h3>Utilisateur non trouvé (ID0)</p></div>';
		exit;
	}

	// On récupère les utilisateur de profil nettoyage actifs
	$usersManager = new UserManager($cnx);
	$userNet = $usersManager->getUser($id_user);
	if (!$userNet instanceof User) {
		echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle fa-3x mb-3"></i><h3>Utilisateur invalide (erreur instanciation ID#'.$id_user.')</p></div>';
		exit;
	}


	// Date
	echo '<h2 class="text-3em" id="signerDateVerbose">'.ucfirst(Outils::getDate_only_verbose(date('Y-m-d'), true)).'</h2>';
	?>
        <div class="hid" id="signerChangeDate">
            <div class="row justify-content-md-center">
                <div class="col-2">
                    <select class="form-control selectpicker selectpicker-tactile pnett-sign-date-jour">
						<?php
						for($i = 1; $i <=31; $i++) { ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == intval(date('d')) ? 'selected': ''; ?>><?php echo $i; ?></option>
						<?php }
						?>
                    </select>
                </div>
                <div class="col-3">
                    <select class="form-control selectpicker selectpicker-tactile pnett-sign-date-mois">
						<?php
						for($i = 1; $i <=12; $i++) { ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == intval(date('m')) ? 'selected': ''; ?>><?php echo ucfirst(Outils::getMoisIntListe()[$i]) ; ?></option>
						<?php }
						?>
                    </select>
                </div>
                <div class="col-2">
                    <select class="form-control selectpicker selectpicker-tactile pnett-sign-date-annee">
						<?php
						$a = intval(date('Y'));
						for($i = $a; $i >=$a-2; $i--) { ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == intval(date('Y')) ? 'selected': ''; ?>><?php echo $i; ?></option>
						<?php }
						?>
                    </select>
                </div>
            </div>
        </div>


	<?php
	echo '<h2 class="text-2em">'.$userNet->getNomComplet().'</h2>';

	echo '<input type="hidden" id="id_user_nett" value="'.$id_user.'"/>';

	echo '</div>';
	echo '<div class="row justify-content-center margin-top-25">';
	echo '<div class="col-10 padding-right-0"><div id="signature"></div></div>';
	echo '<div class="col-2 padding-right-50">
            <p class="text-18 text-left gris-3"><i class="fa fa-hand-point-left mr-1"></i>Signez dans le cadre ci-contre</p>
            <button type="button" class="btn btn-secondary text-24 btn-lg text-left form-control padding-20-40 btnChangeDate"><i class="fa fa-calendar mr-3"></i>Autre jour</button>
            <button type="button" class="btn btn-warning text-24 btn-lg text-left form-control margin-top-25 padding-20-40 btnEffacerSignature"><i class="fa fa-eraser mr-3"></i>Effacer</button>
            <button type="button" class="btn btn-success text-24 btn-lg text-left form-control margin-top-15 padding-20-40 btnSaveSignature"><i class="fa fa-check mr-3"></i>Valider</button>
        </div>
        </div>';
	exit;
} // FIN mode

// Enregistre la signature d'un agent de nettoyage
function modeSaveSignaturePlanNett() {
	global $cnx, $logsManager;

	$id_user_nett   = isset($_REQUEST['id_user_nett'])  ? intval($_REQUEST['id_user_nett']) : 0;
	$image          = isset($_REQUEST['image'])         ? $_REQUEST['image']                : '';
	$date           = isset($_REQUEST['date'])          ? trim($_REQUEST['date'])           :  date('Y-m-d');

	if (!Outils::verifDateSql($date)) { $date = date('Y-m-d'); }
	$date.= ' '.date('H:i:s');

	if ($id_user_nett == 0) { exit('ERR_AJAX_ID_USER_0'); }

	$nettoyageSignature = new NettoyageSignature([]);
	$nettoyageSignature->setId_user($id_user_nett);
	$nettoyageSignature->setDate($date);

	// Enregistrement
	$nettSignaturesManager = new NettoyageSignaturesManager($cnx);
	$id_sign = $nettSignaturesManager->saveNettoyageSignature($nettoyageSignature);
	if (!$id_sign || (int)$id_sign == 0) { exit('ERR_SAVE_OBJ_NETTSIGN');}

	// Si image
	if ($image != '') {

		$base64_str = str_replace('data:image/png;base64,', '', $image);
		$base64_str = str_replace(' ', '+', $base64_str);
		$decoded = base64_decode($base64_str);

		if (!file_exists(__CBO_UPLOADS_PATH__.'signatures/nett/')) {
			mkdir(__CBO_UPLOADS_PATH__.'signatures/nett/');
		}

		$png_url =__CBO_UPLOADS_PATH__.'signatures/nett/'.$id_sign.".png";

		$resSign = file_put_contents($png_url, $decoded);

	} // FIN test image

	// Logs
	$log = new Log([]);
	$log->setLog_texte('Enregistrement signature nettoyage #'.$id_sign.' pour agent user #'.$id_user_nett);
	$log->setLog_type('info');
	$logsManager->saveLog($log);

	if (!$resSign) {
		$log = new Log([]);
		$log->setLog_texte('Echec enregistrement image signature nettoyage #'.$id_sign);
		$log->setLog_type('danger');
		$logsManager->saveLog($log);
	}

	exit('1');
} // FIN mode

// Mode affichage de la modale du planning nettoyage front
function modeModalPlanNett() {

    global $cnx;

    $usersManager = new UserManager($cnx);

    $vue = isset($_REQUEST['vue']) ? $_REQUEST['vue'] : '';
    $id_user = isset($_REQUEST['id_user']) ? intval($_REQUEST['id_user']) : 0;
	if ($id_user == 0) { exit('ERREUR RÉCUPERATION OPÉRATEUR !'); }
    $userNett = $usersManager->getUser($id_user);
    if (!$userNett instanceof User) { exit('ERREUR D\'IDENTIFICATION OPÉRATEUR !'); }

	$frequencesManager = new FrequencesManager($cnx);
	$frequences = $frequencesManager->getListeFrequences(['associatif' => true]);

	$intervenantsManager = new IntervenantsNettoyageManager($cnx);
	$intervenants = $intervenantsManager->getListeIntervenantsNettoyage(['associatif' => true]);

	$planningManager = new NettoyageLocauxManager($cnx);

	$contexte = isset($_REQUEST['contexte']) ? intval($_REQUEST['contexte']) : 0;
	$id_local = isset($_REQUEST['id_local']) ? intval($_REQUEST['id_local']) : 0;
	$id_acteur = isset($_REQUEST['id_acteur']) ? intval($_REQUEST['id_acteur']) : 0;


	$params = [
		'vue'  => $vue,
        'id_user' => $id_user
	];

	$liste = $planningManager->getListeNettLocaux($params);

	$zoneHeader = $vue == '' ? 'TOUT LE PLANNING' : '';

	if ($vue != '') {
		$vuesManager = new VueManager($cnx);
		$vueZone = $vuesManager->getVueByCode($vue);
		if ($vueZone instanceof Vue) {
			$zoneHeader = '[ZONE]'.strtoupper($vueZone->getNom()).'<br>[USER]';
			$zoneHeader.= $userNett->getPrenom() != '' ? substr(strtoupper($userNett->getPrenom()),0,1). '. ' : '';
            $zoneHeader.= ucfirst(strtolower($userNett->getNom()));
        } else {
			$zoneHeader = 'ZONE ' . $vue;
        }

	}


    // On affiche le header commun
    ?>
    <div id="plannNett">
	    <div class="legendes row">
            <div class="col-3">
                <div class="row">
                    <div class="col titrepn">Fréquences</div>
                </div>
                <?php
				foreach ($frequences as $freq) { ?>
                    <div class="row">
						<div class="col-2 text-info"><?php echo $freq->getCode(); ?></div><div class="col"><?php echo $freq->getNom(); ?></div>
                    </div>
				<?php } ?>
            </div>


            <div class="col-3">
                <div class="row">
                    <div class="col titrepn">Matériels</div>
                </div>
                <div class="row">
                    <div class="col-2">
                        <img src="<?php echo __CBO_ROOT_URL__.'img/nett-mat-nhp.jpg'; ?>" class="img-mw100"/>
                    </div>
                    <div class="col">Nettoyeur haute pression</div>
                </div>
                <div class="row">
                    <div class="col-2">
                        <img src="<?php echo __CBO_ROOT_URL__.'img/nett-mat-nhp2.jpg'; ?>" class="img-mw100"/>
                    </div>
                    <div class="col">Nettoyeur haute pression</div>
                </div>
                <div class="row">
                    <div class="col-2">
                        <img src="<?php echo __CBO_ROOT_URL__.'img/nett-mat-pul.jpg'; ?>" class="img-mw100"/>
                    </div>
                    <div class="col">Pulvérisateur</div>
                </div>
                <div class="row">
                    <div class="col-2">
                    </div>
                    <div class="col text-marron">Autolaveuse (Roctonic)</div>
                </div>
                <div class="row">
                    <div class="col-2">
                        <img src="<?php echo __CBO_ROOT_URL__.'img/nett-mat-cnd.jpg'; ?>" class="img-mw100"/>
                    </div>
                    <div class="col">Centrale de nettoyage et désinfection</div>
                </div>
                <div class="row">
                    <div class="col-2">
                        <img src="<?php echo __CBO_ROOT_URL__.'img/nett-mat-rac.jpg'; ?>" class="img-mw100"/>
                    </div>
                    <div class="col">Essuyage Manuel (à la raclette)</div>
                </div>
            </div>
          <!--  <div class="col-2">
                <div class="row">
                    <div class="col titrepn">Intervenants</div>
                </div>
                <?php /*foreach ($intervenants as $inter) { */?>
                    <div class="row">
                        <div class="col-2 text-info"><?php /*echo $inter->getCode(); */?></div><div class="col"><?php /*echo $inter->getNom(); */?></div>
                    </div>
				<?php /*} */?>
            </div>-->
            <div class="col-6">
                <div class="icos">
                    <img src="<?php echo __CBO_ROOT_URL__.'img/icones-nett.jpg'; ?>" alt="" class="icones-nett"/>
                    <img src="<?php echo __CBO_ROOT_URL__.'img/logo-qse.jpg'; ?>" alt="" class="logo-qse"/>
                    <div id="zoneHeader"></div>
                </div>
                <div class="row">
                    <div class="col titrepn">Méthodes</div>
                </div>
                <div class="row">
                    <div class="col-2 text-info">BP/MP</div>
                    <div class="col">Basse ou Moyenne Pression</div>
                </div>
                <div class="row">
                    <div class="col text-marron"><b>Prélavage à l'eau chaude</b></div>
                </div>
                <div class="row">
                    <div class="col-2 text-info">MAL</div>
                    <div class="col">Moussage Alcalain = Détergence / Désinfection facultative</div>
                </div>
                <div class="row">
                    <div class="col-2"></div>
                    <div class="col text-marron">Deptal Mcl à 3% à température ambiante (eau froide)</div>
                </div>
                <div class="row">
                    <div class="col-2 text-info">MAC</div>
                    <div class="col">Moussage Acide = Détergence</div>
                </div>
                <div class="row">
                    <div class="col-2"></div>
                    <div class="col text-marron">Deptacid SM à 3%</div>
                </div>
                <div class="row">
                    <div class="col-2 text-info">P</div>
                    <div class="col">Pulvérisation</div>
                </div>
                <div class="row">
                    <div class="col-2 text-info">O/P</div>
                    <div class="col">Pulvérisation (deptil HDS sans rinçage) / Ordonnancement</div>
                </div>
                <div class="row">
                    <div class="col text-marron"><b>Rinçage à l'eau chaude</b></div>
                </div>
                <div class="row">
                    <div class="col-2"></div>
                    <div class="col text-marron">Deptil Mycocide désinfectant à 1%</div>
                </div>
                <div class="row">
                    <div class="col-2 text-info">NSF</div>
                    <div class="col">Nettoyage Surfaces Fermées</div>
                </div>
                <div class="row">
                    <div class="col-2 text-info">LA</div>
                    <div class="col">Lavage des sols à l'autolaveuse</div>
                </div>
                <div class="row">
                    <div class="col-2 text-info">D</div>
                    <div class="col">Degrossissage à sec : aspiration</div>
                </div>
            </div>
        </div>

        <?php
	    if (empty($liste)) { ?>

            <div class="alert alert-secondary padding-50 mt-3 text-22">Aucun nettoyage à effectuer pour vous ici.<p class="gris-5 text-16">Cliquez sur le bouton AFFICHER TOUT ci-dessous pour consulter l'intégralité du planning&hellip;</p></div>
            <span class="d-none table-plannett" data-zone="<?php echo $zoneHeader; ?>"></span>

		<?php } else {?>
    <table class="table-plannett" data-zone="<?php echo $zoneHeader; ?>">
        <thead>
        <tr>
            <td class="bg-info rotate w-50px" rowspan="3"><div class="w-50px">Nombre / N° atelier</div></td>
            <td class="bg-info" colspan="2">Désignation des locaux</td>
            <td class="bg-gris" colspan="4">Préparation</td>
            <td class="bg-gris" colspan="2">Nettoyage</td>
            <td class="bg-gris" colspan="2">Désinfection</td>
            <td class="vtop padding-top-10 w-50px" rowspan="3">Qui</td>
            <td class="bg-vert">Prélavage à l'eau chaude</td>
            <td class="bg-vert" colspan="2">Détergence</td>
            <td class="bg-jaune ">Rinçage à l'eau chaude</td>
            <td class="bg-vert">Désinfection</td>
            <td class="bg-jaune ">Rinçage à l'eau chaude</td>
        </tr>
        <tr>
            <td rowspan="2"></td>
            <td class="rotate45 w-50px" rowspan="2"><div class="w-50px">Surface m²</div></td>
            <td class="rotate45 w-50px" rowspan="2"><div class="w-50px">Protection</div></td>
            <td class="rotate45 w-50px" rowspan="2"><div class="w-50px">Dégrossi</div></td>
            <td class="rotate45 w-50px" rowspan="2"><div class="w-50px">Démontage</div></td>
            <td class="rotate45 w-60px" rowspan="2"><div class="w-60px">Vidage / Appro.</div></td>
            <td class="w-150px" rowspan="2">Produits</td>
            <td class="w-100px" rowspan="2">Temps de contact</td>
            <td class="w-150px" rowspan="2">Produits</td>
            <td class="w-100px" rowspan="2">Temps de contact</td>
            <td class="vmiddle bg-jaune">4 ou 25 bar</td>
            <td class="vmiddle gris-5" colspan="2">xL pour 100L</td>
            <td class="vmiddle gris-5">20 à 35 bar</td>
            <td class="vmiddle gris-5">xL pour 100L</td>
            <td class="vmiddle gris-5">4 ou 25 bar</td>
        </tr>
        <tr>
            <td class="w-75px vmiddle">BP ou MP</td>
            <td class="w-75px vmiddle bg-info">MAL</td>
            <td class="w-75px vmiddle bg-rouge">MAC</td>
            <td class="w-75px vmiddle bg-jaune">MP</td>
            <td class="w-75px vmiddle">P</td>
            <td class="w-75px vmiddle">BP ou MP</td>
        </tr>

        </thead>
        <tbody>
        <?php
		$old_local = 0;
		$process = -1;
		foreach ($liste as $nett) {

		    if ($process != $nett->getContexte()) {

				$contextTxt = $nett->getContexte() == 0 ? 'Process humide & sec' : 'Activité haché';
		        ?>

                <tr class="contexttr">
                    <td class="text-left" colspan="18"><?php echo $contextTxt; ?></td>
                </tr>

		        <?php $process = $nett->getContexte();
			}

			if ($old_local != $nett->getId_local()) {

				$num = $nett->getNumero() > 0 ? $nett->getNumero() : '&mdash;';
				?>
            <tr class="localtr">
                <td class="text-info"><?php echo $num; ?></td>
                <td class="text-left text-info"><?php echo $nett->getNom_local(); ?></td>
                <td><?php echo $nett->getSurface(); ?> m²</td>
                <td colspan="15"></td>
            </tr>
                <?php

				$old_local = $nett->getId_local();
			}

			$f1 = isset($frequences[$nett->getId_freq_protection()]) ? $frequences[$nett->getId_freq_protection()]->getCode() : '';
			$f2 = isset($frequences[$nett->getId_freq_degrossi()]) ? $frequences[$nett->getId_freq_degrossi()]->getCode() : '';
			$f3 = isset($frequences[$nett->getId_freq_demontage()]) ? $frequences[$nett->getId_freq_demontage()]->getCode() : '';
			$f4 =  isset($frequences[$nett->getId_freq_vidage()]) ? $frequences[$nett->getId_freq_vidage()]->getCode() : '';

			$t1 = $nett->getNettoyage_temps() > 0 ? $nett->getNettoyage_temps() .' min' : '';
			$t2 = $nett->getDesinfection_temps() > 0 ? $nett->getNettoyage_temps() .' min' : '';

            $kiArray = [];
            if (!empty($nett->getUsers())) {
                foreach ($nett->getUsers() as $usn) {
                    if (isset($usn['trigramme']) && $usn['trigramme'] != '') {
						$kiArray[] = $usn['trigramme'];
					}
				}
			}
            $ki =  implode('<br>', $kiArray);

			$f5 = isset($frequences[$nett->getId_freq_prelavage()]) ? $frequences[$nett->getId_freq_prelavage()]->getCode() : '';
			$f6 = isset($frequences[$nett->getId_freq_deterg_mal()]) ? $frequences[$nett->getId_freq_deterg_mal()]->getCode() : '';
			$f7 =  isset($frequences[$nett->getId_freq_deterg_mac()]) ? $frequences[$nett->getId_freq_deterg_mac()]->getCode() : '';
			$f8 = isset($frequences[$nett->getId_freq_rincage_1()]) ? $frequences[$nett->getId_freq_rincage_1()]->getCode() : '';
			$f9 = isset($frequences[$nett->getId_freq_desinfection()]) ? $frequences[$nett->getId_freq_desinfection()]->getCode() : '';
			$f0 = isset($frequences[$nett->getId_freq_rincage_2()]) ? $frequences[$nett->getId_freq_rincage_2()]->getCode() : '';
			?>

            <tr>
                <td></td>
                <td class="text-left" colspan="2"><?php echo $nett->getNom_zone(); ?></td>
                <td><?php echo $f1; ?></td>
                <td><?php echo $f2; ?></td>
                <td><?php echo $f3; ?></td>
                <td><?php echo $f4; ?></td>
                <td><?php echo $nett->getNom_nettoyage_pdt(); ?></td>
                <td><?php echo $t1; ?></td>
                <td ><?php echo $nett->getNom_desinfection_pdt(); ?></td>
                <td><?php echo $t2; ?></td>
                <td><?php echo $ki; ?></td>
                <td><?php echo $f5; ?></td>
                <td><?php echo $f6; ?></td>
                <td><?php echo $f7; ?></td>
                <td><?php echo $f8; ?></td>
                <td><?php echo $f9; ?></td>
                <td><?php echo $f0; ?></td>

            </tr>

        <?php
		} // FIN boucle sur les lignes du tableau
        ?>
        </tbody>
    </table>
        <?php } // FIN test néttoyages pas vides
        ?>
    </div>
    <?php
    exit;
} // FIN mode

// Supprime une signature
function modeSupprSignature() {

    global $cnx, $logsManager;

    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    if ($id == 0) { exit; }

	$nettoyageSignaturesManager = new NettoyageSignaturesManager($cnx);
    $nettoyageSignaturesManager->supprimeSignature($id);
    exit;

} // FIN mode

// Identification par code pour la modale du planning nettoyage
function modeModalPlanNettCode() {

    global $cnx;
    ?>
    <div class="row justify-content-md-center padding-top-25">
        <div class="col-12 col-md-10 col-lg-6 col-xl-4 alert alert-secondary">
            <h2 class="bb-c pb-1"><i class="fa fa-user-circle gris-7 mr-2"></i> Identification</h2>
            <p>Entrez votre code personnel<br>pour accéder au planning nettoyage</p>
            <div class="row justify-content-md-center">
                <form class="col-12 col-md-8 col-xl-8" id="connectCode">
                    <div class="input-group input-group-lg mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-key"></i></span>
                        </div>
                        <input type="password" class="form-control" placeholder="Code" name="code" maxlength="8" id="inputCode"/>
                    </div>
                    <div id="msgErreurCode" class="collapse"><div class="alert alert-danger"><i class="fa fa-exclamation-triangle mr-1"></i><span></span></div></div>
                    <div class="input-group clavier">
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large text-30">1</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large text-30">2</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large text-30">3</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large text-30">4</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large text-30">5</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large text-30">6</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large text-30">7</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large text-30">8</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large text-30">9</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-danger btn-large btnClearCode text-30"><i class="fa fa-times-circle"></i></button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large text-30">0</button></div>
                        <div class="col-4"><button type="button" class="form-control mb-2 btn btn-success btn-large text-30 btnValideCode"><i class="fa fa-check"></i></button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php
    exit;
} // FIN mode