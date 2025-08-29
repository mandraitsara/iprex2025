<?php
/**
------------------------------------------------------------------------
PAGE - Liste des BL en attente depuis le Front

Copyright (C) 2020 Intersed
http://www.intersed.fr/
------------------------------------------------------------------------

@author    Cédric Bouillon
@copyright Copyright (c) 2020 Intersed
@version   1.0
@since     2020

------------------------------------------------------------------------
*/
require_once 'scripts/php/config.php';
$blManager = new BlManager($cnx);
$tiersManager = new TiersManager($cnx);

include('includes/header.php');

// On a besoin des vues pour affichage du menu vues
if (!isset($vueManager)) {
	$vueManager = new VueManager($cnx);
}
$vue_code = 'stk'; // On force l'affichage de la vue STK comme active
$skipDocs = true;  // Pour ne pas afficher le bouton des documents (debug)

include('includes/_menu-vues.php'); ?>
<h1 class="text-center">Bons de livraison en attente</h1>
<div class="container-fluid">
    <div class="row justify-content-center">
        <?php
        $listeBlsAttente = $blManager->getListeBl(['statut' => 1]); // Statut 1 = en attente
        if (empty($listeBlsAttente)) { ?>

            <div class="col">
                <div class="alert alert-warning text-center padding-50">
                    <i class="fa fa-info-circle fa-3x"></i>
                    <p class="text-20">Aucun BL en attente&hellip;</p>
                    <a href="<?php
                    $vueStk = $vueManager->getVueByCode($vue_code);
                    echo __CBO_ROOT_URL__.$vueStk->getUrl();?>/" class="btn btn-secondary">Retour</a>
                </div>
            </div>
        <?php } else {
            foreach ($listeBlsAttente as $bl) { ?>
                <div class="col-3 mb-3">
                    <div class="card text-white bg-secondary carte-bl pointeur" data-id="<?php echo $bl->getId();?>">
                        <div class="card-header">
                            <span class="float-left text-24"><i class="fa fa-fw text-24 gris-c fa-file-invoice"></i></span>
                            <span class="float-right text-24"><?php echo $bl->getCode(); ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-5 texte-fin text-13">
                                    N° de commande
                                </div>
                                <div class="col-7 text-right">
                                    <?php echo $bl->getNum_cmd() != '' && (string)$bl->getNum_cmd() != '0' ? $bl->getNum_cmd() : '&mdash;'; ?>
                                </div>
                                <?php
                                // Si client livré = client facturé, on affiche qu'une ligne "client"
                                if ($bl->getId_tiers_livraison() == $bl->getId_tiers_facturation()) {

                                    $client = $tiersManager->getTiers($bl->getId_tiers_livraison());
                                    if (!$client instanceof Tiers) { $client = new Tiers([]); } // Evite les erreurs
                                    ?>
                                    <div class="col-5 texte-fin text-13">
                                        Client
                                    </div>
                                    <div class="col-7 text-right">
                                        <?php echo $client->getNom() != '' ?  $client->getNom() : '&mdash;'; ?>
                                    </div>
                                <?php
                                // Clients distincts livraison/facturation
                                } else {
                                    $client_livraison = $tiersManager->getTiers($bl->getId_tiers_livraison());
                                    $client_facturation = $tiersManager->getTiers($bl->getId_tiers_facturation());
                                    if (!$client_livraison instanceof Tiers) { $client_livraison = new Tiers([]); } // Evite les erreurs
                                    if (!$client_facturation instanceof Tiers) { $client_facturation = new Tiers([]); } // Evite les erreurs
                                    ?>
                                    <div class="col-5 texte-fin text-13">
                                        Client facturé
                                    </div>
                                    <div class="col-7 text-right">
                                        <?php echo $client_facturation->getNom() != '' ?  $client_facturation->getNom() : '&mdash;'; ?>
                                    </div>
                                    <div class="col-5 texte-fin text-13">
                                        Client livré
                                    </div>
                                    <div class="col-7 text-right">
                                        <?php echo $client_livraison->getNom() != '' ?  $client_livraison->getNom() : '&mdash;'; ?>
                                    </div>
                                    <?php
                                } // FIN test clients distincts livraison/facturation
                                ?>
                                <div class="col-5 texte-fin text-13">
                                    Nombre de palettes
                                </div>
                                <div class="col-7 text-right">
                                    <?php echo $blManager->getNbPaletteBl($bl); ?>
                                </div>
                                <div class="col-5 texte-fin text-13">
                                    Nombre de produits
                                </div>
                                <div class="col-7 text-right">
                                    <?php echo $blManager->getNbProduitsBl($bl); ?>
                                </div>
                                <div class="col-5 texte-fin text-13">
                                    Nombre de colis
                                </div>
                                <div class="col-7 text-right">
                                    <?php echo $blManager->getNbColisBl($bl); ?>
                                </div>
                                <div class="col-5 texte-fin text-13">
                                    Poids total (kg)
                                </div>
                                <div class="col-7 text-right">
                                    <?php echo number_format($blManager->getPoidsBl($bl),3, '.', ' '); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } // FIN boucle sur les BL en attente
        } // FIN test BL en attente trouvés
        ?>
    </div>
</div>
<?php
include_once('includes/footer.php');
