<?php
/*
   _|_|_|  _|_|_|    _|
 _|        _|    _|  _|        CBO FrameWork
 _|        _|_|_|    _|        (c) 2018 Cédric Bouillon
 _|        _|    _|  _|
   _|_|_|  _|_|_|    _|_|_|_|
--------------------------------------------------------
Contrôleur Ajax USER
------------------------------------------------------*/
error_reporting(0);// Évite les problèmes de génération de PDF suite à des Warning
// Initialisation du mode d'appel
$mode       = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';

// Initialisation des exceptions de l'autorisation d'accès
$skipAuth   = $mode == 'login' || $mode == 'modalUser' || $mode == 'loginAdmin';

// Intégration de la configuration du FrameWork et des autorisations
require_once '../php/config.php';

// Instanciation des Managers
$userManager = new UserManager($cnx);
$logsManager = new LogManager($cnx);

$fonctionNom = 'mode'.ucfirst($mode);
if (function_exists($fonctionNom)) {
	$fonctionNom();
}


/* ------------------------------------
MODE - Login
------------------------------------*/
function modeLogin() {

    global
	    $userManager,
		$logsManager;

	$code = isset($_REQUEST['code']) ? $_REQUEST['code'] : false;
	if (!$code || strlen($code) != 4) { exit; }

	$log = new Log([]);

	// Si le code correspond à un utilisateur actif :
	$checkUser = $userManager->checkLoginUser($code);
	if ($checkUser instanceof User) {

		$log->setLog_type('success');
		$log->setLog_texte("Indentification de " . $checkUser->getNomComplet() . " par code");

		$_SESSION['logged_user'] = serialize($checkUser);

		// On stocke un cookie pour faire perdurer la session jusqu'à minuit
		setcookie("IPREXCNXUSERID",$checkUser->getId(),strtotime('today 23:59'), '/');

		// Retour positif pour redirection
		echo 1;
	} else {

		$log->setLog_type('danger');
		$log->setLog_texte("Echec d'indentification à l'intranet, code invalide : " . $code);

    } // FIN test identification

	$logsManager->saveLog($log);

	exit;
} // FIN mode


/* ------------------------------------
MODE - Modale User (admin)
------------------------------------*/
function modeModalUser() {

    global
	    $cnx,
		$utilisateur,
        $userManager;

    if (!isset($_SESSION['logged_user'])) { exit;}

	$user_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
	$user    = $user_id > 0 ? $userManager->getUser($user_id) : new User([]);

	$profilManager = new ProfilManager($cnx);

	// On instancie l'utilisateur en session s'il ne l'est pas déjà
	if (!isset($utilisateur)) {
		$utilisateur = unserialize($_SESSION['logged_user']);
    }

	// Retour Titre
	echo '<i class="fa fa-user';
	echo $user_id > 0 ? '' : '-plus';
	echo '"></i>';
	echo $user_id > 0 ? $user->getNomComplet() : "Nouvel utilisateur&hellip;";

	// Séparateur Titre/Body pour le callback aJax
	echo '^';

	// Retour Body ?>

	<form class="container-fluid" id="formUserAddUpd">
        <input type="hidden" name="mode" value="saveUser"/>
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>"/>
		<div class="row">
			<div class="col-12 col-lg-6 input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text">Prénom</span>
				</div>
				<input type="text" class="form-control" placeholder="Prénom" name="user_prenom" id="input_user_prenom" value="<?php echo $user->getPrenom(); ?>">
                <div class="invalid-feedback">Le prénom est obligatoire.</div>
			</div>
			<div class="col-12 col-lg-6  input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text">Nom</span>
				</div>
				<input type="text" class="form-control" placeholder="Nom" name="user_nom" id="input_user_nom" value="<?php echo $user->getNom(); ?>">
                <div class="invalid-feedback">Le nom est obligatoire.</div>
			</div>
		</div>
		<div class="row">
			<div class="col-12 col-lg-6 input-group mb-5">
				<div class="input-group-prepend">
					<span class="input-group-text">Profil</span>
				</div>
				<select class="selectpicker show-tick form-control" name="profil" id="user_select_profil">
					<?php
					foreach ($profilManager->getListeProfils() as $profil) {

						// Si l'utilisateur n'est pas Dev, il ne peux pas sélectionner un dev :
						if (!$utilisateur->isDev() && $profil->getIs_dev() == 1) { continue; }

						?>
						<option value="<?php echo $profil->getId(); ?>" <?php
                        echo $user->getProfil_id() == $profil->getId() ? 'selected' : '';
						?> data-needmail="<?php
                        echo (int)$profil->getIs_dev() == 1 || (int)$profil->getIs_admin() == 1 ? '1' : '0';
                        ?>"><?php echo $profil->getNom(); ?></option>
					<?php }	?>
				</select>
			</div>
			<div class="col-12 col-lg-6 input-group mb-5 <?php echo $user->isAdmin() || $user->isDev() || $user->isGescom() ? '' : 'disabled';?>">
				<div class="input-group-prepend">
					<span class="input-group-text">E-mail</span>
				</div>
				<input type="text" class="form-control" placeholder="exemple@email.com" value="<?php
                echo $user->getEmail(); ?>" name="email" id="input_user_email" <?php echo $user->isAdmin() || $user->isDev() || $user->isGescom() ? '' : 'disabled';?>/>

                <div class="invalid-feedback">Une adresse e-mail valide est obligatoire pour les administrateurs</div>
			</div>

		</div>

		<div class="row">

			<div class="col-8 col-lg-4 input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text">Code</span>
				</div>
				<input type="password" name="code" class="form-control" value="<?php echo $user->getCode()?>" maxlength="4" id="input_user_code"/>
				<div class="input-group-append pointeur">
					<span class="input-group-text btnShowHodeCode pointeur" data-toggle="tooltip" data-placement="bottom" title="Afficher/masquer le code"><i class="fa fa-eye"></i></span>
				</div>
                <div class="invalid-feedback">Un code à 4 chiffres est obligatoire</div>
			</div>
			<div class="col-4 col-lg-2 mb-2">
                <button type="button" class="btn btn-outline-secondary btn-sm margin-top-2 btnGenereCodeDispo"><i class="fa fa-magic"></i> Générer</button>
                <!--data-toggle="tooltip" data-placement="bottom" title="Générer un code disponible"-->
            </div>
			<div class="col-12 col-lg-6 mb-2 text-right">
                <?php if ($user->isAdmin() || $user->isDev() ) { ?>
                <span class="mr-2 gris-5">Reception alertes :</span>
                <input type="checkbox" class="togglemaster" data-toggle="toggle" name="alertes"
                       <?php echo $user->hasAlertes() ? 'checked' : ''; ?>
                       data-on="Oui"
                       data-off="Non"
                       data-onstyle="success"
                       data-offstyle="secondary"
                />
                <?php } ?>
                <span class="ml-3 mr-2 gris-5">Actif :</span>
                <input type="checkbox" class="togglemaster" data-toggle="toggle" name="activation"
					<?php echo $user->isActif() ? 'checked' : ''; ?>
                       data-on="Oui"
                       data-off="Non"
                       data-onstyle="success"
                       data-offstyle="danger"
                />
            </div>
		</div>

	</form>
    <div class="row mt-2">
        <div class="col-12 col-lg-6  doublonMail d-none">
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle mr-1"></i> Cette adresse e-mail est déjà associée à un utilisateur !
            </div>
        </div>
        <div class="col-12 col-lg-6  infoMailAdmin d-none">
            <div class="alert alert-secondary">
                <i class="fa fa-info-circle mr-1"></i>  Un mot de passe de connexion sera envoyé à l'adresse e-mail renseignée.
            </div>
        </div>
    </div>

	<?php
	// Séparateur Body/Footer pour le callback aJax
	echo '^';

	// Retour boutons footer si utilisateur existant (bouton supprimer)
    if ($user_id > 0) {
	?>
	    <button type="button" class="btn btn-danger btn-sm <?php
            echo $user_id != $utilisateur->getId() ? 'btnSupprimeUser' : ''?>" <?php
            echo $user_id == $utilisateur->getId() ? 'disabled' : ''?>><i class="fa fa-times fa-lg vmiddle mr-1"></i> Supprimer
	    </button>
	<?php
    } // FIN test édition utilisateur existant
	exit;
} // FIN mode


/* ---------------------------------------------
MODE - Génère un nouveau code pas déjà utilisé
----------------------------------------------*/
function modeGenereCode() {

    global
	    $userManager;

	do {
		$code = Outils::genereCode(4);

	} while ($userManager->checkCodeExiste($code));

    echo $code;
    exit;

} // FIN mode


/* ------------------------------------
MODE - Enregistre un user (add/upd)
------------------------------------*/
function modeSaveUser() {

    global
        $cnx,
		$utilisateur,
		$conf_email,
		$conf_nomsite,
		$userManager;

	// Vérification des données
	$user_id    = isset($_REQUEST['user_id'])       ? intval($_REQUEST['user_id'])                          : 0;
	$profil_id  = isset($_REQUEST['profil'])        ? intval($_REQUEST['profil'])                           : 0;
	$prenom     = isset($_REQUEST['user_prenom'])   ? ucwords(strtolower(trim($_REQUEST['user_prenom'])))   : '';
	$nom        = isset($_REQUEST['user_nom'])      ? strtoupper(trim($_REQUEST['user_nom']))               : '';

	$email      = isset($_REQUEST['email']) && Outils::verifMail(trim(strtolower($_REQUEST['email'])))  ? trim(strtolower($_REQUEST['email'])) : '';
	$code       = isset($_REQUEST['code'])  && preg_match('/^[0-9]{4}$/', $_REQUEST['code'])    ? $_REQUEST['code']                     : '';

	$activation = isset($_REQUEST['activation'])    ? 1 : 0;
	$alertes    = isset($_REQUEST['alertes'])       ? 1 : 0;

	// Si pas de prénom, de nom, de profil ou de code, on ne vas pas plus loin...
	if ($prenom == '' || $nom == '' || $profil_id == 0 || $code == '') {
		echo '-1';
		exit;
	} // FIN test champs requis

	// On vérifie que le mail n'existe pas déjà s'il est renseigné (double contrôle après aJax)
    if ($email !== '' && $user_id == 0 && $userManager->checkMailExiste($email)) {
        echo '-1';
        exit;
    } // FIN contrôle adresse e-mail unique si requise pour la création


    // On instancie l'objet profil pour les tests liés
	$profilManager  = new ProfilManager($cnx);
	$profil         = $profilManager->getProfil($profil_id);

	// Instanciation de l'objet USER (hydraté ou vide)
	$user = $user_id > 0 ? $userManager->getUser($user_id) : new User([]);

	// mise à jour des champs de base
	$user->setPrenom($prenom);
	$user->setNom($nom);
	$user->setCode($code);
	$user->setAlertes($alertes);

	// Si on met à jour un utilisateur admin, on ne peux pas supprimer le mail !
	if ($email == '' && $user_id > 0 && (int)$profil->getIs_admin() == 1) {
	} else {
		$user->setEmail($email);
    } // FIN test mise à jour du mail

	// Si c'est un nouvel utilisateur, on enregistre la date de création
	if ($user_id == 0) {
		$user->setDate_creation(date('Y-m-d H:i:s'));
	} // FIN date de création

    // Date de modification
	$user->setDate_modif(date('Y-m-d H:i:s'));

	// On ne peux pas modifier le profil ni l'activation sur soi-même !
	if ($utilisateur->getId() != $user_id) {

	    // Prise en compte du changement de profil et activation
		$user->setProfil_id($profil_id);
		$user->setActif($activation);

	// Si on a supprimé son propre e-mail, on ne l'enregistre pas, c'est a cause d'une changement de statut non autorisé
	} else if ($email == '') {
		$user->setEmail($utilisateur->getEmail());

	} // FIN test modification sur soi-même

	// Si création d'un nouvel admin, on envoie un mot de passe par e-mail
    // On vérifie d'abord qu'on est en création
	if ($user_id == 0) {

	    // On vérifie ensuite que c'est bien un admin
        if ((int)$profil->getIs_admin() == 1) {

            // On génère un mot de passe en clair et on l'hydrate en hash dans l'objet
            $mdp = Outils::genereMotDePasse();
            $user->setAdmin_hash(password_hash($mdp, PASSWORD_DEFAULT));

            // Définition du header du mail
            $destinataires  = [$user->getEmail()];
            $from           = $conf_email;
            $titre          = 'Bienvenue sur ' . $conf_nomsite;

			// Contenu du mail
            $corps = "<h2>Bienvenue " . $user->getNomComplet() . "</h2>";
            $corps .= "<h3>Votre espace administrateur viens d'être créé sur l'intranet Profil Export.</h3>";
            $corps .= "<p>Connectez-vous à <a href='" . __CBO_ROOT_PATH__ . "'>" . $conf_nomsite . "</a> via l'accès <em>Administrateurs</em>.</p>";
            $corps .= "<ul><li>Utilisez votre adresse e-mail comme identifiant.</li>";
            $corps .= "<li>Votre mot de passe : <b>" . $mdp . "</b></li></ul>";
            $corps .= "<p>Vous pouvez changer votre mot de passe à tout moment.</p>";

            // Si l'utilisateur n'est pas encore activé, on le précise dans le mails
            if ((int)$user->getActif() == 0) {
                $corps .= "<p>ATTENTION, votre accès n'est pas encore actif. Contactez le service administratif pour terminer votre inscription.</p>";
            } // FIN test utilisateur activé

            // Envoi du mail
            if (!isset($modeMaintenance)) {
                if (!isset($configManager)) {
					$configManager = new ConfigManager($cnx);
				}
                $config_maintenance  = $configManager->getConfig('maintenance');
                if ($config_maintenance instanceof Config) {
                    $modeMaintenance = intval($config_maintenance->getValeur()) == 1;
                } else {
					$modeMaintenance = true;
                }
            }
            if ($modeMaintenance) {
				$destinataires = [];
				$destinataires[] = isset($conf_email) ? $conf_email : 'patrice.pactol.ext@koesio.com';
            }

            Outils::envoiMail($destinataires, $from, $titre, utf8_decode($corps));

        } // FIN test admins
    } // FIN test création

    // Enregistrement et retour pour callBack ajax
	$retour = $userManager->saveUser($user);

	// Logs
	$logsManager = new LogManager($cnx);
	$log = new Log([]);
	if ($retour) {
		$log->setLog_type('info');
		if ($user_id == 0) {
			$log->setLog_texte("Création d'un nouvel utilisateur : " . $user->getNomComplet());
		} else {
			$log->setLog_texte("Mise à jour des informations de l'utilisateur #" . (int)$user_id);
		}
	} else {
		$log->setLog_type('danger');
		if ($user_id == 0) {
			$log->setLog_texte("ERREUR lors de la création d'un nouvel utilisateur : " . $nom . " " . $prenom);
		} else {
			$log->setLog_texte("ERREUR lors de la mise à jour des informations de l'utilisateur #" . (int)$user_id);
		}
	} // FIN test retour création/maj pour log

	$logsManager->saveLog($log);

	echo $retour !== false ? '1' : '0';


	exit;
} // FIN mode


/* ------------------------------------
MODE - Affiche la liste des users
------------------------------------*/
function modeShowListeUsers() {

    global
        $cnx,
		$utilisateur,
		$userManager;

    // Instanciation des managers
	$profilManager  = new ProfilManager($cnx);

	// Récupèration des utilisateurs en fonction du profil :
    // On affiche les Développeurs que si on est soi-même connecté comme développeur

    $params = [];
    $params['show_devs'] = $utilisateur->isDev();

    $filtre_nom     = isset($_REQUEST['filtre_nom'])    ? trim(strtolower($_REQUEST['filtre_nom'])) : '';
    $filtre_profil  = isset($_REQUEST['filtre_profil'])  && $_REQUEST['filtre_profil'] != '' ? intval($_REQUEST['filtre_profil'])        : 0;
    $filtre_actif   = isset($_REQUEST['filtre_actif']) && $_REQUEST['filtre_actif'] != '' ? intval($_REQUEST['filtre_actif'])         : -1;

    if ($filtre_nom != '')  { $params['filtre_nom']     = $filtre_nom;      }
    if ($filtre_profil > 0) { $params['filtre_profil']  = $filtre_profil;   }
    if ($filtre_actif > -1) { $params['filtre_actif']   = $filtre_actif;    }


	$listeUsers = $userManager->getListeUsers($params);

	// Si aucun utilisateur a afficher
	if (empty($listeUsers)) { ?>

        <div class="alert alert-danger">
            <i class="fa fa-times-circle text-28 vmiddle mr-1"></i> <strong>Aucun utilisateur !</strong>
        </div>

    <?php

	// Sinon, affichage de la liste des utilisateurs
	} else { ?>

        <table class="admin w-100">
            <thead>
                <tr>
				    <?php
                    // On affiche l'ID que si on est développeur
                    if ($utilisateur->isDev()) { ?><th class="d-none d-xl-table-cell">ID</th><?php } ?>
                    <th>Nom</th>
                    <th>Profil</th>
                    <th class="d-none d-xl-table-cell">E-mail</th>
                    <th class="text-center d-none d-md-table-cell">Actif</th>
                    <th class="text-center d-none d-md-table-cell">Alertes</th>
                    <th class="t-actions">Détails</th>
                </tr>
            </thead>
            <tbody>
			    <?php
                // Boucle sur les utilisateurs
				foreach ($listeUsers as $user) {

				    if (strtolower($user->getNom()) == 'bot') { continue; }

				    // On récupère le profil et on met en forme pour l'affichage
					$profil     = $profilManager->getProfil($user->getProfil_id());
					$profilNom  = is_object($profil) ? $profil->getNom()    : '<em class="text-danger">Inconnu</em>';
					$profilFa   = is_object($profil) ? $profil->getFa()     : 'user-slash';
					?>
                    <tr>
						<?php
						// On affiche l'ID que si on est développeur
                        if ($utilisateur->isDev()) { ?>
                            <td class="d-none d-xl-table-cell"><span class="badge badge-pill badge-warning"><?php echo $user->getId();?></span></td>
                        <?php } ?>
                        <td><?php echo $user->getNomComplet();?></td>
                        <td class="gris-7"><i class="fa fa-<?php echo $profilFa; ?> fa-lg mr-1"></i> <?php echo $profilNom; ?></td>
                        <td class="gris-7 d-none d-xl-table-cell"><?php echo $user->getEmail() != '' ? $user->getEmail() : '&mdash;';?></td>
                        <td class="text-center d-none  d-md-table-cell"><i class="fa fa-fw fa-lg fa-<?php echo $user->isActif() ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i></td>
                        <td class="text-center d-none  d-md-table-cell">
                            <?php if ($user->isAdmin() || $user->isDev()) { ?>
                            <i class="fa fa-fw fa-lg fa-<?php echo $user->hasAlertes() ? 'check-circle text-success' : 'times-circle gris-9'; ?>"></i>
                        <?php } else { ?>
                                <i class="fa fa-fw fa-minus gris-c"></i>
                        <?php } // FIN test user admin/dev?>
                        </td>
                        <td class="t-actions"><button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#modalUser" data-user-id="<?php
                            echo $user->getId(); ?>"><i class="fa fa-user-edit"></i> </button></td>
                        </tr>
                <?php
				} // FIN boucle utilisateurs ?>
            </tbody>
        </table>
	<?php } // FIN test utilisateurs à afficher
    exit;
} // FIN mode


/* ---------------------------------------
MODE - Supprime un utilisateur
---------------------------------------*/
function modeSupprUser() {

    global
	    $utilisateur,
		$userManager,
		$logsManager;

    //  -> On ne supprime pas réellement pour les raisons de traçabilité, on passe le champ "supprime" à 1

    // On récupère l'ID de l'utilisateur, si il n'est pas clairement identifié, on ne va pas plus loin
    $id_user = isset($_REQUEST['id_user']) ? intval($_REQUEST['id_user']) : 0;
    if ($id_user == 0) { exit; }

    // Si on tente de se supprimer sois-même on ne vas pas plus loin
    if ($id_user == (int)$utilisateur->getId()) { exit; }

    // Instanciation de l'objet User
    $user = $userManager->getUser($id_user);

    // On passe le statut à supprimé
    $user->setSupprime(1);

    // On désactive
    $user->setActif(0);

    // On sufixe un profil_code avec 0 pour pouvoir supprimer un profil ou plus aucun utilisateur n'est rataché, tout en permettant de connaitre son ancien profil pour la page DEV des utilisateurs supprumés
	$user->setProfil_id( $user->getProfil_id()*10);

    // On change le code de connexion en préfixant un "S" à l'ID user (pour être unique)
    // afin de rendre le code inopérant à la connexion (numéros seulement)
    $user->setCode('S'.$id_user);

    // On change l'adresse e-mail aussi, pour pouvoir recréer avec la meme en cas d'erreur
	$user->setEmail(date('YmdHis').$user->getEmail());

    // On enregistre la date de modification
	$user->setDate_modif(date('Y-m-d H:i:s'));

	// Si la mise à jour s'est bien passé en BDD
    if ($userManager->saveUser($user)) {

        // On sauvegarde dans les LOGS
        $log = new Log([]);
        $log->setLog_texte("Suppression d'un utilisateur (champ 'supprime' à 1), user ID " . $id_user);
        $log->setLog_type('warning');
		$log->setLog_user_id($utilisateur->getId());
		$logsManager->saveLog($log);

    } // FIN test suppression OK pour Log
    exit;
} // FIN mode


/* ---------------------------------------
MODE - Connexion administrateurs
---------------------------------------*/
function modeLoginAdmin() {

    global
        $userManager,
		$logsManager;

    // On réinitialise la variable de session à FALSE pour le statut connecté via l'admin
	$_SESSION['cnxadmin'] = false;
	$_SESSION['devmode'] = false;

	// Récupèration des variables
	$login  = isset($_REQUEST['login'])  ? trim($_REQUEST['login'])     : '';
	$mdp    = isset($_REQUEST['mdp'])    ? trim($_REQUEST['mdp'])       : '';

	// Si pas de login ou de mot de passe, on ne vas pas plus loin
	if ($login == '' || $mdp == '') { exit; }

	// Instanciation des Managers et du Log
	$log         = new Log([]);

	// Si la connexion est valide
	if ($userManager->checkLoginAdmin($login, $mdp)) {

        // Si le login ne contiens pas le domaine (pas mail)
		if (!Outils::verifMail($login)) {

		    // On recherche le mail d'après lui si il n'y en a qu'un seul
			$login = $userManager->getUniqueUserMailFromPrefix($login);

        } // FIN login pas mail

		$adminUser = $userManager->getUserAdminByMail($login);

		// Si l'instanciation a réussie
		if ($adminUser instanceof User) {

		    // On charge l'objet User en session pour ouvrir l'accès à l'intranet
			$_SESSION['logged_user'] = serialize($adminUser);

			// On stocke la variable de session attestant que l'utilisateur s'est connecté via son login/pw admin
            // (indispensable pour l'acces à l'espace admin)
			$_SESSION['cnxadmin'] = true;

			if ($adminUser->isDev()) {
				$_SESSION['devmode'] = true;
            }

            // On enregistre le Log
			$log->setLog_type('success');
			$log->setLog_texte("Connexion de " . $login . " à l'espace administrateurs");
			$logsManager->saveLog($log);

			// On stocke un cookie pour faire perdurer la session jusqu'à minuit
			setcookie("IPREXCNXUSERID",$adminUser->getId(),strtotime('today 23:59'), '/');


			// Retour positif pour redirection via le callBack ajax
			echo '1';
			exit;

		} // Fin test instanciation de l'utilisateur réussie

    // Sinon, la connexion n'est pas valide !
	} else {

	    // On enregistre le log de connexion refusée avec traçabilité des identifiants et mot de passe saisi pour analyse éventuelle (bot/bruteforce...)
		$log->setLog_type('danger');
		$log->setLog_texte("Connexion refusée à l'espace administrateurs, login : " . $login . ", mdp : " . $mdp);
		$logsManager->saveLog($log);

	} // FIN test connexion

    exit;
} // FIN mode


/* ---------------------------------------
MODE - Changement mot de passe
---------------------------------------*/
function modeUpdMdp() {

    global
	    $cnx,
		$utilisateur,
		$logsManager;

    // On récupère l'ancien et le nouveau mot de passe
    $mdpold = isset($_REQUEST['mdpold']) ? trim($_REQUEST['mdpold']) : '';
    $mdpnew = isset($_REQUEST['mdpnew']) ? trim($_REQUEST['mdpnew']) : '';

    // Si une des variables est vide on retourne le message d'erreur pour le callBack et on stope
    if ($mdpold == '' || $mdpnew == '') { echo 'Une erreur esr survenue...'; exit; }

	// Instanciation des Managers et du Log
	$userManager = new UserManager($cnx);
	$log         = new Log([]);


	// On récupère le login (e-mail) de l'utilisateur connecté
	$login = $utilisateur->getEmail();

	// Si le mot de passe actuel ne correspond pas, on retourne le message d'erreur pour le callBack et on stope
	if (!$userManager->checkLoginAdmin($login, $mdpold)) { echo 'Mot de passe actuel incorrect.'; exit; }

	// OK, jusqu'ici tout vas bien, on peux continuer... :)

    // On hydrate le hash du nouveau mot de passe
	$utilisateur->setAdmin_hash(password_hash($mdpnew, PASSWORD_DEFAULT));

	// Si l'enregistrement en BDD a bien été effectué, on retourne le positif pour le callBack et on stope
	if ($userManager->saveUser($utilisateur)) {

		$log->setLog_type('success');
		$log->setLog_texte("Modification du mot de passe pour " . $login);
		$logsManager->saveLog($log);

	    echo '1';exit;
    } // FIN ok Save

    // Ici c'est qu'il y a eu un problème lors de l'enregistrement en BDD, on fais un retour aJax...
	echo 'Une erreur esr survenue...';
    exit;
} // FIN mode


/* ---------------------------------------
MODE - Vérif adresse e-mail dispo
---------------------------------------*/
function modeCheckMailExiste() {

    global
	    $userManager;

    // On récupère, formate et vérifie que le mail passé est bien rempli...
    $email = isset($_REQUEST['email']) ? trim(strtolower($_REQUEST['email'])) : '';
    if ($email == '') { echo '1'; exit; }

    // On retourne au callback aJax le résultat de la méthode de vérification
	echo $userManager->checkMailExiste($email) ? '1' : '0';
    exit;

} // FIN mode

/* ---------------------------------------
MODE - Mot de passe oublié (admin)
---------------------------------------*/
function modeMdpOublie() {

	global
	    $cnx,
	    $conf_email,
		$conf_nomsite,
	    $userManager;

    $login = isset($_REQUEST['login']) && Outils::verifMail($_REQUEST['login']) ? $_REQUEST['login'] : '';
    if ($login == '') { exit; }

    // On vérifie que l'utilisateur existe avec ce mail
	if (!$userManager->checkMailExiste($login)) {  exit; }
	$user = $userManager->getUserAdminByMail($login);
	if (!$user instanceof User) {  exit; }

	$mdp = Outils::genereMotDePasse();
	$user->setAdmin_hash(password_hash($mdp, PASSWORD_DEFAULT));
	
	$userManager->saveUser($user);

	$from           = 'iprex@profilexport.fr';
	$titre          = utf8_decode('['.$conf_nomsite . '] Réinitialisation mot de passe');

	// Contenu du mail
	$corps = "<h2>Bonjour " . $user->getNomComplet() . "</h2>";
	$corps .= "<h3>Une demande de réinitialisation de votre mot de passe viens d'être effectuée sur iPrex.</h3>";
	$corps .= "<p><a href='" . __CBO_ROOT_PATH__ . "'>Connectez-vous</a> via l'accès <em>Administrateurs</em>.</p>";
	$corps .= "<ul><li>Utilisez votre adresse e-mail comme identifiant.</li>";
	$corps .= "<li>Votre nouveau mot de passe : <b>" . $mdp . "</b></li></ul>";
	$corps .= "<p>Vous pouvez changer votre mot de passe à tout moment.</p>";

	// Envoi du mail
	if (!isset($modeMaintenance)) {
		if (!isset($configManager)) {
			$configManager = new ConfigManager($cnx);
		}
		$config_maintenance  = $configManager->getConfig('maintenance');
		if ($config_maintenance instanceof Config) {
			$modeMaintenance = intval($config_maintenance->getValeur()) == 1;
		} else {
			$modeMaintenance = true;
		}
	}

	$destinataires = [$login];

	if ($modeMaintenance) {
		$destinataires = [];
		$destinataires[] = isset($conf_email) ? $conf_email : 'patrice.pactol.ext@koesio.com';
	}

	echo Outils::envoiMail($destinataires, $from, $titre, utf8_decode(Outils::formatContenuMail($corps))) ? '1' : '';


    exit;

} // FIN mode


/* ------------------------------------------
MODE - Export en PDF
-------------------------------------------*/
function modeExportPdf() {

	global $tiersManager;

	require_once(__CBO_ROOT_PATH__.'/vendor/html2pdf/html2pdf.class.php');

	ob_start();
	$content = genereContenuPdf();
	$content .= ob_get_clean();

	// On supprime tous les fichiers du même genre sur le serveur
	foreach (glob(__CBO_ROOT_PATH__.'/temp/iprexusers-*.pdf') as $fichier) {
		unlink($fichier);
	}

	try {
		$nom_fichier = 'iprexusers-'.date('is').'.pdf';
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

	exit;


} // FIN mode

/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le contenu HTML pour le PDF
-----------------------------------------------------------------------------*/
function genereContenuPdf() {

	global $cnx, $userManager;

	// HEAD
	$contenu = '<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <style type="text/css">
  
    * { margin:0; padding: 0; }
  
    .header { border-bottom: 2px solid #ccc; }
    .header img.logo { width: 200px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .table { border-collapse: collapse; }
    .table-donnees th { font-size: 11px; }
    .table-liste th { font-size: 9px; background-color: #d5d5d5; padding:3px; }
    .table-liste td { font-size: 9px; padding-top: 3px; padding-bottom: 3px; border-bottom: 1px solid #ccc;}
    .table-liste td.no-bb { border-bottom: none; padding-bottom: 0px; }
    .titre {
       background-color: teal;
       color: #fff;
       padding: 3px;
       text-align: center;
       font-weight: normal;
       font-size: 14px;
    }
    .recap {
       background-color: #ccc;
       padding: 3px;
       text-align: center;
       font-weight: normal;
       font-size: 10px;
    }
    
    .w100 { width: 100%; }
    .w75 { width: 75%; }
    .w50 { width: 50%; }
    .w40 { width: 40%; }
    .w25 { width: 25%; }
    .w33 { width: 33%; }
    .w34 { width: 34%; }
    .w20 { width: 20%; }
    .w30 { width: 30%; }
    .w15 { width: 15%; }
    .w35 { width: 35%; }
    .w5 { width: 5%; }
    .w10 { width: 10%; }
    .w15 { width: 15%; }
    
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
    
    .text-danger { color: #d9534f; }
    
  </style> 
</head>
<body>';

	$contenu.=  genereEntetePagePdf();

	// PAGE 1

	// GENERAL

	// Préparation des variables
	$na             = '<span class="gris-9 text-11"><i>Non renseigné</i></span>';
	$tiret          = '<span class="gris-9 text-11"><i>-</i></span>';

	// Préparation des variables
	$params = [];
	$liste = $userManager->getListeUsers($params);


	// Génération du contenu HTML
	$contenu.= '<table class="table table-liste w100 mt-10">';

	// Aucun user
	if (empty($liste)) {

		$contenu.= '<tr><td class="w100 text-center gris-9 text-11"><i>Aucun utilisateur</i></td></tr>';

	// Liste des users
	} else {

		$profilManager = new ProfilManager($cnx);

		$contenu.= '<tr>
                        <th class="w40">Nom</th>
                        <th class="w25">Profil</th>
                        <th class="w25">E-mail</th>
                        <th class="w5 text-center">Alertes</th>
                        <th class="w5 text-center">Actif</th>
                    </tr>';

		foreach ($liste as $item) {

			$actif        = $item->getActif() > 0 ? 'Oui' : 'Non';
			$alertes      = $item->hasAlertes() ? 'Oui' : 'Non';

			// On récupère le profil et on met en forme pour l'affichage
			$profil     = $profilManager->getProfil($item->getProfil_id());
			$profilNom  = is_object($profil) ? $profil->getNom() : $na;

			$contenu.= '<tr>
                            <td class="w40">' . $item->getNomComplet() . '</td>
                            <td class="w25">' . $profilNom . '</td>
                            <td class="w25">' . $item->getEmail() . '</td>
                            <td class="w5 text-center">' . $alertes . '</td>
                            <td class="w5 text-center">' . $actif . '</td>
      
                        </tr>';

		} // FIN boucle sur les produits


	} // FIN test produits

	$contenu.= '</table>';

	$contenu.= '<table class="table w100 mt-15"><tr><th class="w100 recap">Nombre d\'utilisateurs : '. count($liste) .'</th></tr></table>';
	// FOOTER
	$contenu.= '<table class="w100 gris-9">
                    <tr>
                        <td class="w50 text-8">Document édité le '.date('d/m/Y').' à '.date('H:i:s').'</td>
                        <td class="w50 text-right text-6">&copy; 2019 IPREX / INTERSED </td>
                    </tr>
                </table>
            </body>
        </html>';



	// RETOUR CONTENU
	return $contenu;

} // FIN fonction déportée


/* ----------------------------------------------------------------------------
FONCTION DEPORTEE - Génère le header du PDF (logo...)
-----------------------------------------------------------------------------*/
function genereEntetePagePdf() {

	global $cnx;

	$entete = '<div class="header">
                <table class="table w100">
                    <tr>
                        <td class="w33"><img src="'.__CBO_ROOT_PATH__.'img/logo-pe-350.jpg" alt="PROFIL EXPORT" class="logo"/></td>
                        <td class="w34 text-center pt-10">
                            Liste des utilisateurs au '.date("d/m/Y").'
                        </td>
                        <td class="w33 text-right text-14">
                            <p class="text-18"><b>IPREX</b></p>
                            <p class="text-12 gris-7">Intranet PROFIL EXPORT</p>
                        </td>
                    </tr>                
                </table>
               </div>';

	return $entete;

} // FIN fonction déportée

// Retourne l'ID user d'après le code passé si trouvé (planning nettoyage)
function modeCheckUserByCode() {

	global
	$userManager,
	$logsManager;

	$code = isset($_REQUEST['code']) ? $_REQUEST['code'] : false;
	if (!$code || strlen($code) != 4) { exit; }

	$log = new Log([]);

	// Si le code correspond à un utilisateur actif :
	$checkUser = $userManager->checkLoginUser($code);
	if ($checkUser instanceof User) {

		$log->setLog_type('success');
		$log->setLog_texte("Indentification de " . $checkUser->getNomComplet() . " au planning nettoyage");

		echo $checkUser->getId();

	} else {

		$log->setLog_type('danger');
		$log->setLog_texte("Echec d'indentification au planning nettoyage, code invalide : " . $code);

	} // FIN test identification

	$logsManager->saveLog($log);

	exit;

} // FIN mode