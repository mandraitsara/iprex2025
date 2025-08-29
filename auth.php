<?php
/**
------------------------------------------------------------------------
PAGE - Connexion - Code

Copyright (C) 2018 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2018 Intersed
@version   1.0
@since     2018

------------------------------------------------------------------------
 */
$css[] = 'css/index.css';
require_once 'scripts/php/config.php';
include('includes/header.php');
?>
<div class="container-fluid">
	<div class="row justify-content-md-center padding-top-25">
		<div class="col-12 col-md-10 col-lg-6 col-xl-4 alert alert-secondary">
			<h2 class="bb-c pb-1"><i class="fa fa-lock gris-7 mr-2"></i> Connexion</h2>
			<p>Entrez votre code personnel pour continuer&hellip;</p>
			<div class="row justify-content-md-center">
			<form class="col-12 col-md-8 col-xl-8" id="connectCode">
				<input type="hidden" id="redirectPage" value="<?php echo isset($_REQUEST['redirect']) ? $_REQUEST['redirect'] : 'index'; ?>"/>
				<div class="input-group input-group-lg mb-3">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fa fa-key"></i></span>
					</div>
					<input type="password" class="form-control" placeholder="Code" name="code" maxlength="8" id="inputCode"/>
				</div>
				<div id="msgErreurCode" class="collapse"><div class="alert alert-danger"><i class="fa fa-exclamation-triangle mr-1"></i><span></span></div></div>
				<div class="input-group clavier">
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large">1</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large">2</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large">3</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large">4</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large">5</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large">6</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large">7</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large">8</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large">9</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-danger btn-large btnClearCode"><i class="fa fa-times-circle"></i></button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-secondary btn-large">0</button></div>
					<div class="col-4"><button type="button" class="form-control mb-2 btn btn-success btn-large btnValideCode"><i class="fa fa-check"></i></button></div>
				</div>
			</form>
			</div>
		</div>
	</div>
</div>
<?php
include('includes/footer.php');