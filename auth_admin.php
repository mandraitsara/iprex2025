<?php
/**
------------------------------------------------------------------------
PAGE - Connexion - Administrateurs

Copyright (C) 2018 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2018 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */
$css[]    = 'css/index.css';
$skipAuth = true;

require_once 'scripts/php/config.php';
include('includes/header.php');

?>
<div class="container-fluid">
	<div class="row justify-content-md-center padding-top-25">
		<div class="col-12 col-md-10 col-lg-6 col-xl-5 alert alert-secondary">
			<h2 class="bb-c pb-1"><i class="fa fa-lock gris-7 mr-2"></i> Connexion Administrateurs</h2>
            <p>Veuillez-vous authentifier pour continuer :</p>
            <?php if (isset($_REQUEST['mdpo'])) { ?>
                <div class="alert alert-info text-center">
                    <i class="fa fa-user-lock mr-2"></i>Un nouveau mot de passe vous a été envoyé par e-mail. Consultez votre messagerie.
                </div>
            <?php } ?>
            <div class="row justify-content-md-center">
                <form class="col-md-8" id="accessApp" action="auth_admin.php" method="post">
                    <div class="input-group input-group-lg mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                        </div>
                        <input type="text" class="form-control" placeholder="Identifiant" name="identifiant" maxlength="50" id="identifiant"/>
                    </div>
                    <div class="input-group input-group-lg">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-key"></i></span>
                        </div>
                        <input type="password" class="form-control" placeholder="Mot de passe" name="motdepasse" maxlength="50" id="motdepasse"/>
                        <div class="input-group-append btnVoirMdp">
                            <span class="input-group-text pointeur" data-toggle="tooltip" data-placement="bottom" title="Afficher/masquer"><i class="fa fa-eye"></i></span>
                        </div>
                    </div>
                    <div class="margin-top-0 mb-3"><a href="auth_mdpoublie.php" class="gris-7 text-12 texte-fin">Mot de passe oublié ?</a></div>
                    <div id="msgErreurAccess" class="collapse"><div class="alert alert-danger"><i class="fa fa-exclamation-triangle mr-1"></i><span></span></div></div>
                    <div class="text-center">
                        <button type="button" class="btn btn-lg btn-success btnConnexion"><i class="fa fa-check mr-1"></i> Connexion</button>
                    </div>
                </form>
            </div>
		</div>
	</div>
</div>
<?php
include('includes/footer.php');