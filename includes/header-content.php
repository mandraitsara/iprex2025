<?php
/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
En-tête de page
------------------------------------------------------*/
?>
<header>

    <!-- Ouverture Navbar -->
    <nav class="navbar navbar-inverse navbar-expand-lg navbar-dark bg-dark">

        <!-- Conteneur Navbar -->
        <div class="container-fluid mb-0 pl-0 pr-0">

            <!-- Header Navbar pour le titre -->
            <div class="navbar-header mr-3">

                <!-- Lien vers l'accueil -->
                <a class="navbar-brand" href="<?php echo __CBO_ROOT_URL__?>"> <img src="<?php echo __CBO_IMG_URL__; ?>logo-pe-header-32-g2.png" class="mr-1 vmiddle" alt="Profil Export"/></a>

                <?php
                // Si mode maintenance, on affiche l'info
                if ($modeMaintenance == 1) { ?>
                    <span class="badge badge-danger text-18">Mode maintenance</span>
                <?php } // FIN test mode maintenance

                 // Si préprod


                if (preg_match('#intersed.info\/iprex#',__CBO_ROOT_URL__)) { ?>
                    <a href="https://iprex.profilexport.local//index.php" class="badge badge-success text-14 text-uppercase padding-5-10"><i class="fa fa-hdd mr-1 fa-lg"></i> DEV</a>
                <?php } else if (preg_match('#iprex.intersed.info#',__CBO_ROOT_URL__)) { ?>
                    <span class="badge badge-warning text-14 text-uppercase padding-5-10"><i class="fa fa-hdd mr-1 fa-lg"></i> Préprod</span>
				<?php } else if (isset($utilisateur) && $utilisateur instanceof User) {
                    if ($utilisateur->isDev()) { ?>
                    <span class="badge badge-danger text-14 text-uppercase padding-5-10"><i class="fa fa-hdd mr-1 fa-lg"></i> PROD</span>
				<?php }  // FIN TEST uset DEV
                } // FIN tests PREPROD/PROD

				if (isset($utilisateur) && $utilisateur instanceof User) {
					if ($utilisateur->isDev()) {
						if (isset($_SESSION['DEVTEST']) && $_SESSION['DEVTEST']) { ?>
                            <span class="badge badge-danger text-14 text-uppercase padding-5-10"><i class="fa fa-database mr-1"></i> TEST</span>
						<?php } else { ?>
                            <span class="badge badge-primary text-14 text-uppercase padding-5-10"><i class="fa fa-database mr-1"></i> PROD</span>
						<?php }
					}
				}

                ?>




            </div> <!-- FIN header Navbar pour le titre -->

            <!-- Conteneur corps de la Navbar -->
            <div class="navbar-collapse">

                <?php
                // Menu utilisateurs connecté (on affiche quand même le header sur la page de connexion)
                if (isset($utilisateur) && $utilisateur instanceof User) { ?>

                    <!-- Premier bloc de navigation -->
                    <ul class="navbar-nav mr-auto">

                        <?php
                        // SI administrateur, on lui affiche le menu des admins
                        if ($utilisateur->isAdmin() || $utilisateur->isGescom()) {


							if ($utilisateur->isAdmin()) { ?>
                                <!-- Menu VUES -->
                                <li class="nav-item dropdown mr-2 d-none d-lg-flex">

                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdminLinkVues"
                                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i
                                                class="fa fa-eye mr-1"></i>Vues</a>

                                    <!-- Menu déroulant -->
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdownAdminLinkVues">
										<?php

										$vueManager = new VueManager($cnx);

										$countSeparator = 0;
										$separations = [8,16];

										foreach ($vueManager->getVuesListe([]) as $vueH) {

											// On affiche le lien vers le scan que pour le profil dev
											if (strtolower($vueH->getCode()) == 'scf' && !$utilisateur->isDev()) { continue; }


											$countSeparator++;
											if (in_array($countSeparator,$separations)) { ?>

                                                <div class="dropdown-divider"></div>

											<?php } // FIN test séparateur ?>

                                            <a class="dropdown-item"
                                               href="<?php echo __CBO_ROOT_URL__ . $vueH->getUrl() . '/'; ?>"><i
                                                        class="fa-fw <?php echo $vueH->getFa(); ?>"></i><?php echo $vueH->getNom();

												echo strtolower($vueH->getCode()) == 'scf' ? '<span class="float-right"><i class="fa fa-user-secret gris-a"></i></span>' : '';

												?>
                                            </a>

										<?php } // FIN boucle sur les vues
										?>
                                    </div> <!-- FIN menu déroulant -->

                                </li> <!-- FIN menu VUES -->
							<?php }
                            ?>




                                    <?php

                            if ($utilisateur->isAdmin()) { ?>

                                <!-- Menu LOTS -->
                                <li class="nav-item dropdown mr-2 d-none d-lg-flex">

                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdminLinkLot"
                                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i
                                                class="fa fa-box mr-1"></i>Lots</a>

                                    <!-- Menu déroulant -->
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdownAdminLinkLot">
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-lots-add.php"><i
                                                    class="fa fa-fw fa-plus-square"></i>Création d'un nouveau
                                            lot&hellip;</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-lots.php?s=<?php echo base64_encode(1) ?>"><i
                                                    class="fa fa-fw fa-clock"></i>Suivi des lots en cours</a>
                                        <a class="dropdown-item"
                                        href="<?php echo __CBO_ROOT_URL__; ?>admin-gestion-lots-negoce.php?ng=<?php echo base64_encode(1) ?>"><i
                                                    class="fa fa-fw fa-clock"></i>Suivi des lots negoce en cours</a>

                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-lots-validations.php"><i
                                                    class="fa fa-fw fa-clipboard-check"></i>Gestion des lots à valider</a>
                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-lots.php?s=<?php echo base64_encode(0) ?>"><i
                                                    class="fa fa-fw fa-flag-checkered"></i>Liste des lots terminés</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-lots-negoce.php"><i
                                                    class="fa fa-fw fa-sort fa-rotate-90"></i>Composition des lots de négoce</a>
                                    </div> <!-- FIN menu déroulant -->

                                </li> <!-- FIN menu LOTS -->

                                      <!-- Menu ADMINISTRATION -->
                                <li class="nav-item dropdown mr-2 d-none d-lg-flex">

                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdminLinkAdmin"
                                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i
                                                class="fa fa-clipboard-list mr-1"></i>Administration</a>

                                    <!-- Menu déroulant -->
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdownAdminLinkAdmin">
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-params.php"><i
                                                    class="fa fa-fw fa-cogs"></i>Paramètres</a>
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-pays.php"><i
                                                    class="fa fa-fw fa-globe-americas"></i>Pays</a>
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-users.php"><i
                                                    class="fa fa-fw fa-users"></i>Utilisateurs</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-types-incidents.php"><i
                                                    class="fas fa-fw fa-exclamation-triangle"></i>Types d'incidents</a>
                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-types-documents.php"><i
                                                    class="fas fa-fw fa-copy"></i>Types de documents</a>
                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-communications.php"><i
                                                    class="fas fa-fw fa-book-open"></i>Communications</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-clients.php"><i
                                                    class="fa fa-fw fa-address-card"></i>Clients / Stocks</a>
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-frs.php"><i
                                                    class="fa fa-fw fa-industry"></i>Fournisseurs</a>
                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-abattoirs.php"><i
                                                    class="fa fa-fw fa-skull-crossbones"></i>Abattoirs</a>
                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-transporteurs.php"><i
                                                    class="fa fa-fw fa-truck-moving"></i>Transporteurs</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-poids.php"><i
                                                    class="fa fa-fw fa-weight"></i>Poids emballages & palettes&hellip;</a>
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-planning-nett.php"><i
                                                    class="fa fa-fw fa-calendar-day"></i>Planning nettoyage&hellip;</a>
                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-consommables-types.php"><i
                                                    class="fa fa-fw fa-leaf"></i>Consommables&hellip;</a>
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-palettes.php"><i
                                                    class="fa fa-fw fa-pallet"></i>Palettes</a>
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-produits.php"><i
                                                    class="fa fa-fw fa-dolly"></i>Produits&hellip;</a>
                                    </div> <!-- FIN menu déroulant -->

                                </li> <!-- FIN du menu ADMINISTRATION -->

                                      <!-- Menu SURVEILLANCE -->
                                <li class="nav-item dropdown mr-2 d-none d-lg-flex">

                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdminLinkSurv"
                                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i
                                                class="fa fa-search mr-1"></i>Surveillance</a>

                                    <!-- Menu déroulant -->
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdownAdminLinkSurv">
                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-dashboard.php"><i
                                                    class="fa fa-fw fa-tachometer-alt"></i>Tableau de bord</a>
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-alertes.php"><i
                                                    class="fa fa-fw fa-bullhorn"></i>Gestion des alertes</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-pdts-jour.php"><i
                                                    class="fa fa-fw fa-calendar-check"></i>Produits du jour</a>
                                        <a class="dropdown-item"
                                           href="<?php echo __CBO_ROOT_URL__; ?>admin-corrections.php"><i
                                                    class="fa fa-fw fa-highlighter"></i>Correction de traitements</a>

                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-prp.php"><i class="fa fa-fw fa-thermometer-three-quarters"></i>Suivi PRP OP</a>
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-nettoyage.php"><i
                                                    class="fa fa-fw fa-shower"></i>Suivi nettoyage&hellip;</a>
                                        <!--<a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-blocages.php"><i class="fa fa-fw fa-pause"></i>Blocages</a>-->
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>admin-search.php"><i
                                                    class="fa fa-fw fa-map-marked"></i>Recherche de traçabilité&hellip;</a>
                                    </div> <!-- FIN menu dérouant -->

                                </li>

							<?php }

                            ?>



							<?php if ($gescom || $utilisateur->isDev()) { ?>
                                <!-- Menu GESCOM -->
                            <li class="nav-item dropdown mr-2 d-none d-lg-flex">

                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdminLinkGescom"
                                   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i
                                            class="fa fa-briefcase mr-1"></i>Gescom</a>

                                <!-- Menu déroulant -->
                            <div class="dropdown-menu" aria-labelledby="navbarDropdownAdminLinkGescom">
                                <?php
                                if ($utilisateur->isAdmin()) { ?>
                                    <a class="dropdown-item"
                                       href="<?php echo __CBO_ROOT_URL__; ?>gc-params.php"><i
                                                class="fa fa-fw fa-cogs"></i>Paramètres</a>
                                    <a class="dropdown-item"
                                       href="<?php echo __CBO_ROOT_URL__; ?>gc-trads.php"><i
                                                class="fa fa-fw fa-language"></i>Traductions</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item"
                                       href="<?php echo __CBO_ROOT_URL__; ?>gc-tarifs-frs.php">
                                        <i class="fa fa-fw fa-industry"></i>
                                        <i class="fa fa-fw fa-dollar-sign margin-left--20 text-11 gris-5 mr-0"></i>
                                        Tarifs fournisseurs</a>
                                    <a class="dropdown-item"
                                       href="<?php echo __CBO_ROOT_URL__; ?>gc-tarifs-clt.php">
                                        <i class="fa fa-fw fa-address-card"></i>
                                        <i class="fa fa-fw fa-dollar-sign margin-left--20 text-11 gris-5 mr-0"></i>
                                        Tarifs clients</a>
                                    <a class="dropdown-item"
                                       href="<?php echo __CBO_ROOT_URL__; ?>gc-frais-fonctionnement.php">
                                        <i class="fa fa-fw fa-tachometer-alt"></i>
                                        <i class="fa fa-fw fa-dollar-sign margin-left--20 text-11 gris-5 mr-0"></i>
                                        Frais de fonctionnement</a>
                                    <div class="dropdown-divider"></div>
								<?php }
                                ?>




                                <a class="dropdown-item"
                                   href="<?php echo __CBO_ROOT_URL__; ?>gc-web.php"><i
                                            class="fa fa-fw fa-globe"></i>Commandes Web</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item"
                                   href="<?php echo __CBO_ROOT_URL__; ?>gc-bts.php"><i
                                            class="fa fa-fw fa-file-contract"></i>Bons de transfert</a>
                                <a class="dropdown-item"
                                   href="<?php echo __CBO_ROOT_URL__; ?>gc-bls.php"><i
                                            class="fa fa-fw fa-file-invoice"></i>Bons de livraison</a>
                                <a class="dropdown-item"
                                   href="<?php echo __CBO_ROOT_URL__; ?>gc-factures.php"><i
                                            class="fa fa-fw fa-file-invoice-dollar"></i>Factures</a>


                                <?php
								if ($utilisateur->isAdmin()) { ?>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item"
                                       href="<?php echo __CBO_ROOT_URL__; ?>gc-statistiques.php"><i
                                                class="fa fa-fw fa-chart-pie"></i>Statistiques</a>
								<?php }
									?>


                            </div> <!-- FIN menu dérouant -->

                            </li> <!-- FIN du menu SURVEILLANCE -->

							<?php
						} // FIN test GesCom active




                        } // FIN test profil administrateur

                        // SI profil Développeur, on affiche le menu Développeur
			            if ($utilisateur->isDev()) { ?>

                            <!-- Menu DEVELOPPEUR -->
                            <li class="nav-item dropdown d-none d-lg-flex">

                                <a class="nav-link dropdown-toggle text-warning" href="#" id="navbarDropdownDevLinkDev" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-code mr-1"></i>Développeur</a>

                                <!-- Menu déroulant -->
                                <div class="dropdown-menu" aria-labelledby="navbarDropdownDevLinkDev">
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>iprex_doctech.pdf" target="_blank"><i class="fa fa-fw fa-book"></i>Documentation technique</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-maintenance.php"><i class="fa fa-fw fa-wrench"></i>Mode maintenance</a>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-bdd.php"><i class="fa fa-fw fa-database"></i>Base de données</a>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-vues.php"><i class="fa fa-fw fa-desktop"></i>Gestion des vues</a>
                                    <div class="dropdown-divider"></div>

                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-profils.php"><i class="fa fa-fw fa-id-card"></i>Profils utilisateur</a>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-ext-documents.php"><i class="fas fa-fw fa-file-excel"></i>Formats de fichiers</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-kill-documents.php"><i class="fa fa-fw fa-trash-alt"></i>Documents supprimés</a>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-kill-users.php"><i class="fa fa-fw fa-trash-alt"></i>Utilisateurs supprimés</a>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-kill-bls.php"><i class="fa fa-fw fa-trash-alt"></i>BLs supprimées</a>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-kill-factures.php"><i class="fa fa-fw fa-trash-alt"></i>Factures supprimées</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-zones.php"><i class="fa fa-fw fa-columns"></i>Zones de traductions</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-logs.php"><i class="fa fa-fw fa-user-secret"></i>Journal des logs</a>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-logsmodifs.php"><i class="fa fa-fw fa-highlighter"></i>Historique des modifications</a>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-logsmails.php"><i class="fa fa-fw fa-paper-plane"></i>Mails envoyés</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>dev-crons.php"><i class="fa fa-fw fa-robot"></i>Tâches CRON</a>
                                </div> <!-- FIN menu déroulant -->

                            </li> <!-- FIN du menu DEVELOPPEUR -->

                        <?php
                        } // FIN test profil développeur

                        // Menu d'aide pour les profil administrateur
			            if ($utilisateur->isAdmin()) {
                        ?>

                            <!-- Menu AIDE (pour admins) -->
                            <li class="nav-item dropdown d-none d-lg-flex">

                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAideLinkAide" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-question-circle mr-1"></i>Aide</a>

                                <!-- Menu déroulant -->
                                <div class="dropdown-menu" aria-labelledby="navbarDropdownAideLinkAide">
                                 <!--   <a class="dropdown-item" href="<?php /*echo __CBO_ROOT_URL__; */?>docs.php"><i class="fa fa-fw fa-file-pdf"></i>Guides d'utilisation&hellip;</a>-->
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>iprex-photo-app.php"><i class="fa fa-fw fa-camera"></i>iPrex Photo App</a>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>contact.php"><i class="fa fa-fw fa-comments"></i>Assistance technique</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>docs/mementos/" target="_blank"><i class="fa fa-fw fa-book"></i>Mementos des versions</a>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>docs/changelog.pdf" target="_blank"><i class="fa fa-fw fa-newspaper"></i>Changelog</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo __CBO_ROOT_URL__; ?>credits.php"><i class="fa fa-fw fa-info-circle"></i>A propos d'iPrex&hellip;</a>
                                </div> <!-- FIN menu déroulant -->

                            </li> <!-- FIN du menu AIDE -->

                        <?php
			            } // FIN test profil administrateur pour le menu AIDE
                        ?>

                    </ul> <!-- FIN premier bloc de navigation navbar-nav -->

                    <?php

                      if (((isset($vue) && $vue instanceof Vue) || __CBO_PAGE__ == 'index') &&  !$utilisateur->isAdmin() && !$utilisateur->isGescom()) { ?>
                          <i class="fa fa-clock pr-1 pl-1 text-24 gris-9"></i>
                <span class="text-20 padding-0 margin-0 gris-c">

        <?php echo Outils::getDate_only_verbose(date('Y-m-d'), true, false); ?>

        </span>

                <span class="heure-dynamique text-24 gris-c pr-3 pl-2"></span>


				<?php }

                    // Si l'utilisateur est admin ou dev
                    if ($utilisateur->isAdmin() || $utilisateur->isDev()) { ?>

                        <!-- Second bloc de navigation  pour administrateurs et développeurs -->
                        <ul class="navbar-nav">

                            <!-- Menu UTILISATEUR -->
                            <li class="nav-item dropdown">

                                <!-- Nom pour menu déroulant et icone selon profil admin/dev -->
                                <a class="nav-link dropdown-toggle " href="#" id="navbarDropdownDevLinkCompte" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-<?php echo isset($cnxAdmin) && $cnxAdmin == true ? 'user-shield' : 'user-circle';?>"></i> <?php echo $utilisateur->getNomComplet(); ?>
                                </a>

                                <!-- Menu déroulant -->
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownDevLinkCompte">
                                    <a class="dropdown-item" href="admin-mdp.php"><i class="fa fa-key fa-flip-horizontal mr-1 fa-fw"></i>Changer de mot de passe</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo __CBO_SCRIPTS_PHP_URL__.'deconnexion.php'; ?>"><i class="fa fa-power-off mr-1 fa-fw"></i>Déconnexion</a>
                                </div> <!-- Fin menu déroulant -->

                            </li> <!-- FIN menu UTILISATEUR -->

                        </ul> <!-- FIN second bloc de navigation pour administrateurs et développeurs -->

                    <?php

                    // Sinon, l'utilisateur a un profil opérateur ou responsable, on propose juste la déconnexion, sans menu déroulant
                    } else { ?>

                        <!-- Affichage du nom -->
                        <span class="navbar-text text-20 mr-3">
                            <i class="fas fa-user-circle"></i> <?php echo $utilisateur->getNomComplet(); ?>
                        </span>

                        <!-- Bouton de déconnexion -->
                        <span class="navbar-text mr-2">
                            <a class="nav-link btn btn-secondary" href="<?php echo __CBO_SCRIPTS_PHP_URL__.'deconnexion.php'; ?>"><i class="fa fa-power-off mr-1"></i>Déconnexion</a>
                        </span>

                    <?php

                    } // FIN test profil pour affichage personnalisé du nom et des options de déconnexion

                // Si utilisateur pas connecté et si on est sur la page d'autentification par code, on propose le lien pour la connexion admin par login/mdp
                } else if (__CBO_PAGE__ == 'auth') { ?>

                    <!-- Bloc de navigation navbar pour le lien de connexion Administrateurs -->
                    <ul class="navbar-nav mr-auto"></ul>
                        <div class="my-2 my-lg-0">
                            <a href="<?php echo __CBO_ROOT_URL__.'auth_admin.php'; ?>" class="btn btn-outline-secondary mr-2"><i class="fa fa-lock mr-1"></i> Administrateurs</a>
                        </div>
                    </div> <!-- FIN bloc de navigation pour lien de connexion Administrateurs -->

                 <?php

                // Si utilisateur pas connecté et si on est sur la page d'autentification administrateur par login/mdp, on propose le lien pour la revenir à la connexion simple par code
                }  else if (__CBO_PAGE__ == 'auth_admin') { ?>

                     <!-- Bloc de navigation navbar pour le lien de onnexion par code (Opérateur / Responsable) -->
                    <ul class="navbar-nav mr-auto"></ul>
                        <div class="my-2 my-lg-0">
                            <a href="<?php echo __CBO_ROOT_URL__.'auth.php'; ?>" class="btn btn-outline-secondary mr-2"><i class="fa fa-lock mr-1"></i> Retour</a>
                        </div>
                    </div> <!-- FIN bloc de navigation pour lien de retour vers connexion par code -->

		        <?php
                }// FIN test utilisateur connecté / page de connexion
                ?>

            </div> <!-- FIN conteneur corps de la Navbar (collapse), hors header -->

            <!-- Bouton plein écran (pour les vues) -->
            <?php if ((isset($vue) && $vue instanceof Vue) || __CBO_PAGE__ == 'index') { ?>
            <span class="navbar-text">
                <button type="button" class="nav-link btn btn-secondary d-none" id="btnFullScreen"><i class="fa fa-expand-arrows-alt fa-lg"></i></button>
            </span>
            <?php } ?>

        </div> <!-- FIN conteneur Navbar -->

    </nav> <!-- FIN Navbar -->

</header>

<?php
// SI un titre est précisé, on affiche le bandeau du titre de la page...
if (isset($h1)) { ?>

    <h1><?php

        // Si icone FontAwesome spécifique, on l'intègre
        if (isset($h1fa)) { ?>

            <!-- Icone titre -->
            <span class="fa-stack">
              <i class="fas fa-square fa-stack-2x"></i>
              <i class="fa <?php echo $h1fa; ?> fa-stack-1x fa-inverse"></i>
            </span> <!-- FIN icone titre -->

        <?php } // FIN test icone FontAwesome

        // Affichage du titre dans la balise H1
        echo $h1;


        if (substr(__CBO_PAGE__,0,5) == 'admin') { ?>

            <div class="espAdminTitle"><i class="fa fa-unlock-alt fa-lg mr-1"></i>Espace Administrateur</div>

        <?php

        // Sinon si on se trouve sur une page restreinte aux développeurs (fichier préfixé par "dev-"), on indique Espace Développeur
        } else if (substr(__CBO_PAGE__,0,4) == 'dev-') { ?>

            <div class="espAdminTitle"><i class="fa fa-code  fa-lg mr-1"></i>Espace Développeur</div>



            <?php

        } // FIN test espace Administrateur / Développeur

        ?></h1>

<?php



} // FIN test H1 prédéfini pour affichage du bandeau titre
