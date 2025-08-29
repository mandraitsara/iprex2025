<?php
/**
------------------------------------------------------------------------
PAGE - Conteneur principal des vues - Contrôleur d'appel

Copyright (C) 2019 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2019 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */

// echo "Site en cours de transfert...";
// die();

$vue_code = isset($_REQUEST['vue']) ? trim(strtolower($_REQUEST['vue'])) : '';

// Pour la vur scan frais (mobile) on se connecte en tant que BOT
if ($vue_code == 'scf') {
    // On ne perds pas de temps avec les contrôles d'identifications
	$skipAuth = true;
	// On demande a la config de se connecter au bot
	$cnxBot = true;
} // Fin connexion BOT
require_once 'scripts/php/config.php';
$languesManager = new LanguesManager($cnx);
$languesManager->activeFr();

// Si on doit afficher une vue, on intègre le CSS et les JS dédiés
$vue_code = isset($_REQUEST['vue']) ? trim(strtolower($_REQUEST['vue'])) : '';
if (!isset($vueManager)) {
	$vueManager = new VueManager($cnx);
}
$vuesExistantes = $vueManager->getCodesVuesExistants();
if ($vue_code != '' && in_array(strtolower($vue_code), $vuesExistantes)) {
	$css[] = 'css/vues.css';
	$css[] = 'css/jkeyboard.css';
	$js[] = 'scripts/js/vue_' . $vue_code . '.js';
	$js[] = 'scripts/js/jkeyboard.js';
	$js[] = 'scripts/js/vues.js';
	$vue = $vueManager->getVueByCode($vue_code);
}

$classBody = strtolower($vue_code) === 'scf' ? 'vue-mobile' : '';
$js[] =  'vendor/jsignature/jSignature.min.js';

include('includes/header.php');
$fichier = '_main.php';
?>
<div class="container-fluid">
<?php
// Si une vue existe passée en paramètre
if (isset($vue) && $vue instanceof Vue) {
    // Si la vue n'est pas en maintenance (ou qu'on est développeur) et que le fichier existe
    if (($vue->getMaintenance() == 0 || $utilisateur->isDev()) && file_exists(__CBO_ROOT_PATH__.'/includes/'.$fichier)) {
        $fichier = '_vue-'.$vue_code.'.php';
    } else if ($vue->getMaintenance() == 1) {
		$fichier = '_vue_maintenance.php';
    }
    // FIN fichier existe/mainteance/dev
} // FIN une vue existe passée en paramètre
include('includes/_menu-vues.php');
include('includes/' . $fichier);
include('includes/modales/modal_info.php');
 ?>
    <div id="clavier_virtuel" class="clavier-<?php echo $vue_code; ?>"></div>
</div>
<input type="hidden" id="idVuePhotos" value="<?php echo isset($vue) && $vue instanceof Vue ? $vue->getId() : ''; ?>" />
<iframe id="etiquetteFrame" name="imprimerEtiquette"></iframe>
<?php
if (isset($utilisateur)) {
	if ($utilisateur->isDev()) { ?>
        <div id="consoleDev"><i class="fa fa-ban text-danger"></i> Aucune donnée</div>
	<?php }
}
include_once('includes/footer.php');
include_once('includes/modales/modal-communication-front.php');
include_once('includes/modales/modal_commentaires_front.php');
include_once('includes/modales/modal_incident_front.php');
include_once('includes/modales/modal_planning_nettoyage.php');
include_once('includes/modales/modal_alarme_plannet.php');
include_once('includes/modales/modal_com.php');