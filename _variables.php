<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|

--------------------------------------------------------
Liste des variables
------------------------------------------------------*/
$onlyDebug = true;
require_once 'scripts/php/config.php';
if (!$utilisateur->isDev()) {header('Location: '.__CBO_ROOT_URL__);exit;}
$title = "Liste des variables";
include('includes/header.php');
?>
<div class="titre">
    <img src="<?php echo __CBO_IMG_URL__; ?>favicon.png" class="float-left margin-right-5 margin-left-15 margin-top-5" style="max-width: 40px;"/>
    <h1>Liste des variables</h1>
    <hr>
</div>

<div class="col-md-12">
   <div class="container-fluid row">
        <div class="col-md-6">
            <h3>Variables globales</h3>
            <div class="alert alert-default">
               <table class="table table-small">
                    <tr>
                        <th>Variable</th>
                        <th>Description</th>
                        <th>Valeur</th>
                    </tr>
                    <tbody>
                    <tr>
                            <td><code>__CBO_ROOT_URL__</code></td>
                            <td>Chemin abosulu racine</td>
                            <td><kbd><?php var_dump(__CBO_ROOT_URL__); ?></kbd></td>
                        </tr>                        <tr>
                            <td><code>__CBO_ROOT_PATH__</code></td>
                            <td>Chemin abosulu racine</td>
                            <td><kbd><?php var_dump(__CBO_ROOT_PATH__); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>__CBO_IMG_URL__</code></td>
                            <td>Chemin du dossier images</td>
                            <td><kbd><?php var_dump(__CBO_IMG_URL__); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>__CBO_CLASS_URL__</code></td>
                            <td>Chemin du dossier des classes</td>
                            <td><kbd><?php var_dump(__CBO_CLASS_URL__); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>__CBO_CSS_URL__</code></td>
                            <td>Chemin des feuilles des styles</td>
                            <td><kbd><?php var_dump(__CBO_CSS_URL__); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>__CBO_SCRIPTS_JS_URL__</code></td>
                            <td>Chemin des scripts JS</td>
                            <td><kbd><?php var_dump(__CBO_SCRIPTS_JS_URL__); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>__CBO_SCRIPTS_PHP_URL__</code></td>
                            <td>Chemin des scripts PHP</td>
                           <td><kbd><?php var_dump(__CBO_SCRIPTS_PHP_URL__); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>__CBO_TEMP_URL__</code></td>
                            <td>Chemin des fichier temporaires</td>
                            <td><kbd><?php var_dump(__CBO_TEMP_URL__); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>__CBO_UPLOADS_URL__</code></td>
                            <td>Chemin des fichier téléchargés</td>
                            <td><kbd><?php var_dump(__CBO_UPLOADS_URL__); ?></kbd></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Variables de session</h3>
            <div class="alert alert-default">
                <table class="table table-small">
                    <tr>
                        <th>Variable</th>
                        <th>Description</th>
                        <th>Valeur</th>
                    </tr>
                    <tbody>
                        <tr>
                            <td><code>$_SESSION['sub_domain']</code></td>
                            <td>Sous-domaine pour le chargeur de classes</td>
                            <td><kbd><?php var_dump($_SESSION['sub_domain']); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['url_site']</code></td>
                            <td>Chemin absolu pour les globales</td>
                            <td><kbd><?php var_dump($_SESSION['url_site']); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['usesmtp']</code></td>
                            <td>Utilisation du SMTP</td>
                            <td><kbd><?php var_dump($_SESSION['usesmtp']); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['nom_site']</code></td>
                            <td>Nom du site ou de l'application</td>
                            <td><kbd><?php var_dump($_SESSION['nom_site']); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['cbofv']</code></td>
                            <td>Version du framework</td>
                            <td><kbd><?php var_dump($_SESSION['cbofv']); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['cbofurl']</code></td>
                            <td>URL du framework</td>
                            <td><kbd><?php var_dump($_SESSION['cbofurl']); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['cbofurlreal']</code></td>
                            <td>URL réelle du framework</td>
                            <td><kbd><?php var_dump($_SESSION['cbofurlreal']); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['cbofyear']</code></td>
                            <td>Année d'initialisation du framework</td>
                            <td><kbd><?php var_dump($_SESSION['cbofyear']); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['cbofbsv']</code></td>
                            <td>Version de Bootstrap embarquée</td>
                            <td><kbd><?php var_dump($_SESSION['cbofbsv']); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['smtp_server']</code></td>
                            <td>Hôte du serveur SMTP</td>
                            <td><kbd><?php var_dump($_SESSION['smtp_server']); ?></kbd></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['smtp_username']</code></td>
                            <td>Utilisateur SMTP (si SMTP actif)</td>
                            <td><?php if (isset($_SESSION['smtp_username'])) { echo '<kbd>'; var_dump($_SESSION['smtp_username']); echo '<kbd>'; } else { echo '<i class="fa fa-times padding-left-5"></i>'; } ?></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['smtp_password']</code></td>
                            <td>Mot de passe SMTP (si SMTP actif)</td>
                            <td><?php if (isset($_SESSION['smtp_password'])) { echo '<kbd>'; var_dump($_SESSION['smtp_password']); echo '</kbd>'; } else { echo '<i class="fa fa-times padding-left-5"></i>'; } ?></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['smtp_port']</code></td>
                            <td>Port SMTP (si SMTP actif)</td>
                            <td><?php if (isset($_SESSION['smtp_port'])) { echo '<kbd>'; var_dump($_SESSION['smtp_port']);  echo '</kbd>'; } else { echo '<i class="fa fa-times padding-left-5"></i>'; } ?></td>
                        </tr>

                        <tr>
                            <td><code>$_SESSION['cnxadmin']</code></td>
                            <td>Connexion administrateur</td>
                            <td><?php if (isset($_SESSION['cnxadmin'])) { echo '<kbd>'; var_dump($_SESSION['cnxadmin']); echo '</kbd>'; } else { echo '<i class="fa fa-times padding-left-5"></i>'; } ?></td>
                        </tr>
                        <tr>
                            <td><code>$_SESSION['devmode']</code></td>
                            <td>Mode Développeur</td>
                            <td><?php if (isset($_SESSION['devmode'])) { echo '<kbd>'; var_dump($_SESSION['devmode']); echo '</kbd>'; } else { echo '<i class="fa fa-times padding-left-5"></i>'; } ?></td>
                        </tr>

                        <tr>
                            <td><code>$_SESSION['logged_user']</code></td>
                            <td>Utilisateur connecté</td>
                            <td><?php if (isset($_SESSION['logged_user'])) { echo '<kbd style="overflow: auto;max-width: 320px;display: block;">'; var_dump($_SESSION['logged_user']);  echo '</kbd>'; } else { echo '<i class="fa fa-times padding-left-5"></i>'; } ?></td>
                        </tr>

                    </tbody>
                </table>
                <div class="clearfix"></div>
            </div>



            <h3>Cookies</h3>
            <div class="alert alert-default">
                <table class="table table-small">
                    <tr>
                        <th>Cookie</th>
                        <th>Valeur</th>
                    </tr>
                    <tbody>
                    <?php
					foreach ($_COOKIE as $c => $v) { ?>
                        <tr>
                            <td><code><?php echo $c;?></code></td>
                            <td><kbd><?php echo $v;?></kbd></td>

                        </tr>
                    <?php } ?>

                    </tbody>
                </table>
                <div class="clearfix"></div>
            </div>

        </div>



        <div class="col-md-6">
            <h3>Variables serveur</h3>
            <div class="alert alert-default">
                <table class="table table-small">
                    <tr>
                        <th>Variable</th>
                        <th>Valeur</th>
                    </tr>
                    <tbody>
                    <?php
			        foreach ($_SERVER as $var_name => $var_val) { ?>
                        <tr>
                            <td><code><?php echo $var_name; ?></code></td>
                            <td><?php echo strlen($var_val) > 0 ? '<kbd>'.strip_tags($var_val).'</kbd>' : '<i class="fa fa-times padding-left-5"></i>'; ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>

<?php include('includes/footer.php');