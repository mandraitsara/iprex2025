/**
 ------------------------------------------------------------------------
 JS - Vue Surgélation Verticale

 Copyright (C) 2018 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2018 Intersed
 @version   1.0
 @since     2018

 ------------------------------------------------------------------------
 */
$('document').ready(function() {
    "use strict";


    // Charge l'étape 0 : Point d'entrée
    chargeEtape(0, 0);


    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    // Ready -> Nettoyage des modales à leur fermeture
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    // Fin de surgélation
    $('#modalConfirmFinFroid').on('hidden.bs.modal', function (e) {
        $('#modalConfirmFinFroidTitle').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#modalConfirmFinFroidBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    });

    // Nouvel Emballage
    $('#modalNouvelEmballage').on('hidden.bs.modal', function (e) {
        $('#modalNouvelEmballageBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    });

    // Contrôle LOMA
    $('#modalControleLoma').on('hidden.bs.modal', function (e) {
        $('#modalControleLomaTitle').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    });

    // Info
    $('#modalInfo').on('hidden.bs.modal', function (e) {
        $('#modalInfoBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    });

    // Nouvelle palette
    $('#modalPaletteFront').on('hidden.bs.modal', function (e) {
        $('#modalPaletteFrontBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    });

    // Confirmation
    $('#modalConfirm').on('hidden.bs.modal', function (e) {
        $('#modalConfirmBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#dataConfirmContexte').val('');
        $('#dataConfirmInput').val('0');
    });

    // Confirmation de mise en attente
    $('#modalConfirmAttenteProduit').on('hidden.bs.modal', function (e) {
        $('#modalConfirmAttenteProduitTitle').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#dataConfirmAttenteProduitFroidId').val('0');
    }); // FIN fermeture modale confirmation de mise en attente produit


    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    // Ready -> Confirmation de mise en attente d'un produit (depuis modale)
    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $('.btnConfirmAttenteProduitFroid').off("click.btnConfirmAttenteProduitFroid").on("click.btnConfirmAttenteProduitFroid", function(e) {

        e.preventDefault();

        // identification du produit et gestion des erreurs
        var idLotPdtFroid = parseInt($('#dataConfirmAttenteProduitFroidId').val());
        if (isNaN(idLotPdtFroid) || idLotPdtFroid === undefined || idLotPdtFroid === 0) {
            alert("ERREUR !\r\nIdentification du produitlotfroid impossible.\r\nCode erreur : AOFNJWXV");
            return false;
        } // FIN gestion des erreurs

        // Afin d'éviter les erreurs ajax, on bloque le reste...
        $('body').addClass('cursor-wait');
        $('.btn').attr('disabled', 'disabled');
        $('a').attr('disabled', 'disabled');
        $('.btn').addClass('disabled');
        $('a').addClass('disabled');

        // Mise en attente
        // Ajax qui charge le contenu de l'étape
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_produits.php',
            'arguments': 'mode=miseEnAttenteLotPdtFroid&idlotpdtfroid=' + idLotPdtFroid,
            'callBack': function (retour) {

                $('body').removeClass('cursor-wait');
                $('.btn').prop('disabled', false);
                $('a').prop('disabled', false);
                $('.btn').removeClass('disabled');
                $('a').removeClass('disabled');

                // Gestion des erreurs
                if (retour+''.trim() !== '') {
                    alert("ERREUR !\r\nIdentification du produitlotfroid impossible.\r\nCode erreur : "+retour);
                    return false;

                    // OK, on ferme la modale et on recharge l'étape
                } else {

                    $('#modalConfirmAttenteProduit').modal('hide');
                    chargeEtape(10, 0);

                } // FIN test valeur de retour

            } // FIN Callback

        }); // FIN ajax

    }); // FIN mise en attente produit (modale)


}); // FIN ready

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction chargeant les étapes pour intégrer leur contenu
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function chargeEtape(numeroEtape, identifiant) {
    "use strict";

    var id_froid = parseInt($('#inputIdFroid').val());

    $('.etapes-suivantes').hide('fast');
    $('.etapes-suivantes').html('');

    $('#etape'+numeroEtape).show('fast');
    $('#etape'+numeroEtape).html('<div class="padding-50 text-center"><i class="fa fa-spin fa-spinner fa-5x gris-c"></i></div>');

    // Ajax qui charge le contenu de l'étape
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_srgv.php',
        'arguments': 'mode=chargeEtapeVue&etape=' + numeroEtape + '&id='+identifiant+'&id_froid='+id_froid,
        'return_id': 'etape'+numeroEtape,
        'done': function () {

            var fctnom  = "listenerEtape"+numeroEtape;
            var fn      = window[fctnom];
            fn(identifiant);

            $.fn.ajax({
                rep_script_execute: "../scripts/ajax/", // Gestion de l'URL Rewriting
                script_execute: 'fct_vues.php',
                arguments: 'mode=consoleDev&etape=' + numeroEtape + '&id=' + identifiant + '&id_froid=' + id_froid + '&vue=srgh',
                return_id: 'consoleDev'
            });

        } // FIN Callback

    }); // FIN ajax

} // FIN fonction chargeEtape


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Charge le Ticket
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function chargeTicketLot(etape, identifiant) {
    "use strict";

    var id_froid = parseInt($('#inputIdFroid').val());

    // Si on viens de lancer une surgélation, il ne faut pas passer l'ID froid, afin de ne pas conserver la SRG en cours dans la nouvelle SRG
    if (etape === 1 && $('#justFreezed').length) {
        id_froid = 0;
        $('#inputIdFroid').val(0);
    }

    // Ajax qui charge le contenu du ticket
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_srgv.php',
        'arguments': 'mode=chargeTicketLot&etape='+etape+'&id=' + identifiant + '&id_froid='+id_froid,
        'return_id': 'ticketLotContent',
        'done' : function() {
            listenerTicketLot();
        }
    }); // FIN ajax

} // FIN fonction


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 0 (Point d'entrée)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape0(identifiant) {
    "use strict";

    // Nouvelle SRG
    $('.btnNouvelleSrg').off("click.btnnouvellesrg").on("click.btnnouvellesrg", function(e) {

        e.preventDefault();

        $('#inputIdFroid').val(0);
        chargeEtape(1,0);
    });

    // SRGs en cours
    $('.btnSrgsEnCours').off("click.btnsrgsencours").on("click.btnsrgsencours", function(e) {
        e.preventDefault();
        chargeEtape(101,0);
    });

} // FIN listener

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 1 (Sélection du lot)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape1(identifiant) {
    "use strict";

    chargeTicketLot(1, identifiant);

    // Selection de la vue sur carte
    $('.carte-lot').off("click.selectcartevue").on("click.selectcartevue", function(e) {

        e.preventDefault();

        var id_lot = parseInt($(this).data('id-lot'));
        if (id_lot === undefined || id_lot === 0 || isNaN(id_lot)) { alert("Une erreur est survenue !\r\node Erreur : 4UC2Y4SD");return false; }

        // Appel étape 2 (cartes produits)
        chargeEtape(2, id_lot);

    }); // FIN click vue carte

    // Selection d'un produit en attente
    $('.carte-pdt-attente').off("click.selectpdtattente").on("click.selectpdtattente", function(e) {

        e.preventDefault();

        var id_lot_pdt_froid = parseInt($(this).data('id-lot-pdt-froid'));
        if (id_lot_pdt_froid === undefined || id_lot_pdt_froid === 0 || isNaN(id_lot_pdt_froid)) { alert("ERREUR !\r\nIdentification du produit impossible...\r\nCode erreur : CLQV7TAX");return false; }

        var id_froid = parseInt($('#inputIdFroid').val());



        // On ajoute le produit au traitement (avec création si existe pas)
        // et on va a l'étape 10
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute':'fct_produits.php',
            'arguments': 'mode=affecteProduitFroidAttente&id_lot_pdt_froid='+id_lot_pdt_froid+'&id_froid=' + id_froid + '&code_vue=srgv',    // CODE VUE
            'callBack': function (retour) {

                // Indispensable pour fonctions JS sur cette variable en callBack
                retour = retour+'';

                // Si ok
                if (retour.substring(0, 2).toLowerCase() === 'ok') {

                    if (id_froid === 0) {
                        var id_froid_retour = parseInt(retour.toLowerCase().replace('ok', ''));
                        if (isNaN(id_froid_retour) || id_froid_retour === 0) {
                            alert("ERREUR !\r\nAffectation du produit impossible...\r\nCode erreur : 4PWSVD58");
                            return false;
                        }
                        id_froid = id_froid_retour;
                    }

                    $('#inputIdFroid').val(id_froid);
                    chargeEtape(10, 0);
                } else {
                    if (retour === '') { retour = 'SH0UJCGI'; }  // Code erreur 500 invariable


                } // FIN test valeur de retour
            } // FIN callbacl
        }); // FIN ajax

    }); // FIN click produit en attente

} // FIN listener étape 1

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 2 (Sélection des produits du lot)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape2(identifiant) {
    "use strict";

    chargeTicketLot(2, identifiant);
    listeProduitsListener();

    // Pagination Ajax sur les produits
    $(document).on('click','.carte-pdt-pagination',function(){

        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente

        $('#etape2').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');

        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute':'fct_vue_srgv.php'+$(this).attr('data-url'),
            'return_id':'etape2',
            'done': function () {
                listeProduitsListener();
            }
        });

        // on désactive le lien hypertexte
        return false;

    }); // FIN pagination ajax



} // FIN listener étape 2

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 3 (Nb de colis / poids du produit)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape3(identifiant) {
    // Ne pas mettre de use strict ici ! La supervariable "champ" ne fonctionnerait plus...  /!\

    //chargeTicketLot(3, identifiant);

    identifiant = identifiant + ""; // Indispensable pour le split()

    // L'identiant passé contient soit juste l'id_lot_pdt_froid (update), soit les ID de : id_produit, id_lot , id_froid, séprarés par un pipe (ajout nouveau)
    var donnesIds = identifiant.split('|');
    var modeUpd   = identifiant.indexOf('|') === -1;

    // Le mode update peut etre activé si on détecte un id_lot_pdt_froid, ce qui veut dire qu'on viens du "catalogue" mais qu'on est en réalité sur un produit déjà associé au traitement
    var inputIdLotPdtFroid = parseInt($('#inputIdLotPdtFroid').val());
    if (inputIdLotPdtFroid > 0) {
        modeUpd = true;
    }

    // On masque les boutons "Sélection terminée", "Prêt à congeler" et "Ajouter des produits" pour éviter de valider par erreur la page avec ce bouton
    $('.btnFinSelectionPdts').hide();
    $('.btnFinEtiquetage').hide();
    $('.btnRetourSelectionPdts').hide();


    // Initialisation ajax
    $('.togglemaster').bootstrapToggle();
    adapteModeMaj();

    // Bouton Switch méthode mise à jour
    $('.methode-maj').change(function() {
        adapteModeMaj();
    });

    // Si on est en mode update depuis la liste des produits d'une SRG en cours, on masque le bouton "Retour" du ticket
    if (modeUpd) { $('.btnRetourSrgsEnCours').hide(); }

    var vrac = parseInt($('#vrac').val());
    if (isNaN(vrac)) { vrac = 0; }

    champ = vrac === 1 ? 'poids_pdt' : 'nb_colis'; // Super-variable de champ
    if (vrac === 1) { $('input[name=nb_colis]').val(1); }

    $('input[name='+champ+']').focus();

    $('input[name=nb_colis]').focus(function()   { champ = 'nb_colis';  });
    $('input[name=poids_pdt]').focus(function()  { champ = 'poids_pdt'; });

    // ----------------------------------------
    // (Listener Etape 3)
    // Retour vers les produits
    // ----------------------------------------
    $('.btnRetourProduits').off("click.btnretourproduits'").on("click.btnretourproduits", function(e) {

        e.preventDefault();

        // Si on a pas d'op de froid, c'est qu'on à la premiere séléction du premier produit, on reviens donc à l'étape 2
        if (!$('#inputIdFroid').length || parseInt($('#inputIdFroid').val()) == 0) {
            chargeEtape(1,0);
        } else {
            chargeEtape(10,1);
        }

    }); // FIN retour contextuel

    // ----------------------------------------
    // (Listener Etape 3)
    // Selection du quantieme
    // ----------------------------------------
    $('.choix-quantieme').off("click.choixquantieme'").on("click.choixquantieme", function(e) {

        e.preventDefault();

        var quantieme = $(this).text();
        $('input[name=quantieme]').val(quantieme);
        $('.choix-quantieme').removeClass('btn-secondary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-secondary');
        $('.quantiemeInvalide').addClass('d-none');

    }); // FIN selection du quantieme

    // ----------------------------------------
    // (Listener Etape 3)
    // Clôture d'une palette (standard)
    // ----------------------------------------
    $('.btnCloturePalette').off("click.btnCloturePalette'").on("click.btnCloturePalette", function(e) {

        e.preventDefault();

        var id_palette  = parseInt($(this).parents('.carte-palette').data('id-palette'));

        // Message de confirmation
        var htmConfirm = '<div class="alert alert-info text-center"><span class="text-20"><i class="fa fa-box mr-1"></i>Clôture</span><hr><p>Confirmer la clôture de cette palette ?</p></div>';
        $('#modalConfirmBody').html(htmConfirm);
        $('#dataConfirmInput').val(id_palette);
        $('#dataConfirmContexte').val('paletteCloture');
        $('#modalConfirm').modal('show');
        return false;

    });


    // ----------------------------------------
    // (Listener Etape 3)
    // Sélection d'une palette (standard)
    // ----------------------------------------
    $('.carte-palette').off("click.cartepalette'").on("click.cartepalette", function(e) {

        e.preventDefault();

        // Si on clic sur la palette déjà sélectionnée, inutile d'aller plus loin
        if ($(this).hasClass('palette-selectionnee')) { return false; }

        // On retire le flag permette de ne pas créer de palette après la création d'une nouvelle
        $('#skipCreateCompoSave').val(0);

        var nb_colis              = parseInt($('.inputNbColis').val());
        var poids                 = parseFloat($('.inputPoidsPdt').val());

        // Si on est en mode Update et qu'on a pas rajouté de poids, on stope, sinon ça effectue des changement de compos arbitraires sur l'ensemble qui finissent par pourrir la base !
        if (modeUpd && isNaN(poids)) {
            $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>Changement de palette impossible sans ajout...</p></div>");
            $('#modalInfo').modal('show');
            return false;
        }

        // Capacité restante de la palette
        var id_palette            = parseInt($(this).data('id-palette'));
        var numero_palette        = parseInt($(this).data('numero-palette'));
        var palette_restant_poids = parseInt($(this).data('id-poids-restant'));
        var palette_restant_colis = parseInt($(this).data('id-colis-restant'));

        // Valeur du produit

        // Gestion des erreurs
        if (isNaN(id_palette) || id_palette === 0 || isNaN(numero_palette) || numero_palette === 0 || isNaN(palette_restant_poids) || isNaN(palette_restant_colis) ) {
            alert("ERREUR !\r\nEchec lors de la récupération des données...\r\nCode erreur : FVSKUWF5");
            return false;
        } // FIN échap erreurs

        var id_compo            = parseInt($('#inputIdCompo').val());
        var palette_historique  = parseInt($('#inputIdPalette').val());

        // Si on a pas saisi encore le nb de colis / poids...
        if ((isNaN(nb_colis) || isNaN(poids) || nb_colis === 0 || poids === 0) && id_compo === 0 && palette_historique === 0) {

            // Si on a déjà une palette pour ce produit (mode upd) ou pas...
            var textInfo = "Renseignez le nombre de colis<br>avant de sélectionner une palette...";
            $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>"+textInfo+"</p></div>");
            $('#modalInfo').modal('show');

            return false;
        } // FIN poids/colis non saisie à la sélection d'une palette

        // SI on tente de modifier la palette en UPD...
        if ((id_compo > 0 || palette_historique > 0) && (isNaN(nb_colis) || isNaN(poids) || nb_colis === 0 || poids === 0)) {
            // On se base sur le poids du produit déjà dans la palette
            poids = parseFloat($('#poidsPaletteHisto').val());
        }

        // On compare la capacité restante de la palette sélectionnée avec le poids et le nb de colis qu'on viens de valider au niveau du produit

        // Ok, il reste assez de place (poids)
        if (palette_restant_poids >= poids) {

            addProduitPalette(id_palette);
            // On rajoute le flag permette de ne pas créer de palette après la création d'une nouvelle
            $('#skipCreateCompoSave').val(1);

            // Aie aie aie... il y en a un peu plus... j'vous l'met quand même ?
        } else {

            // Confirmation dans la modale
            if (palette_restant_poids <= 0) {
                $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>Cette palette est déjà hors capacité </p></div>");
                $('#modalInfo').modal('show');
                return false;
            } else {

                var htmConfirm = '<div class="alert alert-danger text-center"><span class="text-20"><i class="fa fa-exclamation-circle mr-1"></i>Dépassement de capacité !</span><hr><p class="badge badge-warning text-18">La palette ' + numero_palette + ' ne peut contenir plus que ' + palette_restant_poids + ' kgs.</p><p class="text-center">Confirmer le dépassement de ' + parseInt(poids - palette_restant_poids) + ' kgs ?</p></div>';
                $('#modalConfirmBody').html(htmConfirm);
                $('#dataConfirmInput').val(id_palette);
                $('#dataConfirmContexte').val('paletteOverCapacite');
                $('#modalConfirm').modal('show');
                return false;

            } // FIN test palette déjà hors capacité ou en dépassement prévisionnel

            return false;

        } // FIN test

        // On met à jour d'ID palette et on met en évidence la palette selectionnée
        $('input[name=id_palette]').val(id_palette);
        $('.carte-palette').removeClass('palette-selectionnee');
        $(this).addClass('palette-selectionnee');

    }); // FIN sélection d'une palette

    // ----------------------------------------
    // (Listener Etape 3)
    // Bouton nouvelle palette (ajouter)
    // ----------------------------------------
    $('.btnNouvellePalette').off("click.btnNouvellePalette").on("click.btnNouvellePalette", function(e) {

        e.preventDefault();


        // Confirmation si des palettes sont encore dispos...
        var nbPalettesDispo = $('.carte-palette').not('.carte-palette-complete').length;

        if (!isNaN(nbPalettesDispo) && nbPalettesDispo > 0) {
            var txtConfirm1 =  nbPalettesDispo > 1 ? "Palettes disponibles !" : "Palette disponible !";
            var txtConfirm2 =  nbPalettesDispo > 1 ? "Des palettes non complètes sont déjà en cours..." : "Une palette non complète est déjà en cours...";
            var htmConfirm = '<div class="alert alert-info text-center nomargin"><span class="text-20"><i class="fa fa-exclamation-circle mr-1"></i>'+txtConfirm1+'</span><hr><p>'+txtConfirm2+'</p><p class="nomargin">Souhaitez-vous réellement créer une nouvelle palette ?</p></div>';
            $('#modalConfirmBody').html(htmConfirm);
            $('#dataConfirmInput').val(0);
            $('#dataConfirmContexte').val('palettesDispoMaisCreation');
            $('#modalConfirm').modal('show');
            return false;
        }

        nouvellePalette(identifiant);


    }); // FIN bouton nouvelle palette

    // ----------------------------------------
    // (Listener Etape 3)
    // Affichage des paletes complètes (droite)
    // ----------------------------------------
    $('.btnPalettesCompletes').off("click.btnPalettesCompletes").on("click.btnPalettesCompletes", function(e) {

        e.preventDefault();

        var id_pdt  = parseInt($('#inputIdProduit').val());
        var objDom = $(this);

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_palettes.php',
            'arguments': 'mode=listePalettesCompletes&id_pdt='+id_pdt,
            'append_id' : 'listePalettesPdt',
            'done': function () {

                objDom.hide('fast');
                palettesCompletesListener();

            } // FIN Callback
        }); // FIN ajax

    }); // FIN affichage des paletes complètes

    // ----------------------------------------
    // (Listener Etape 3)
    // Confirmation (modale) - Générique
    // ----------------------------------------
    $('.btnModalConfirmOk').off("click.btnModalConfirmOk").on("click.btnModalConfirmOk", function(e) {

        e.preventDefault();

        // On passe un "contexte" pour définir le périmètre opérationnel de la confirmation
        var data_confirm_contexte = $('#dataConfirmContexte').val();

        // ----------------------------------------
        // (Listener Etape 3 -> Modal confirm OK)
        // Confirmation clôture palette
        // ----------------------------------------
        if (data_confirm_contexte === 'paletteCloture') {

            // Identification de l'id_palette
            var id_palette =  parseInt($('#dataConfirmInput').val());
            if (isNaN(id_palette) || id_palette === undefined || id_palette === 0) {
                alert('ERREUR !\r\nIdentidication de la palette imposible !\r\nCode erreur : 6XW26F91');
                return false;
            }

            // Ajax cloture palette + masque la palette
            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
                'script_execute': 'fct_palettes.php',
                'arguments': 'mode=cloturePalette&id_palette='+id_palette,
                'done': function () {

                    // On dé-sélectionne et masque la palette
                    $('#cartepaletteid'+id_palette).removeClass('palette-selectionnee');
                    $('#cartepaletteid'+id_palette).parent().hide();

                    // Si c'était celle-là qui était selectionnée, on reset la palette choisie
                    if (parseInt($('#inputIdPalette').val()) === id_palette) {
                        $('#inputIdPalette').val(0);
                    }

                    // Fermeture de la modale (propre)
                    $('#modalConfirm').modal('hide');
                    return false;

                } // FIN Callback
            }); // FIN ajax

        } // FIN contexe clôture

        // ----------------------------------------
        // (Listener Etape 3 -> Modal confirm OK)
        // Sélection palette en surcapacité
        // ----------------------------------------
        if (data_confirm_contexte === 'paletteOverCapacite') {

            // Identification de l'id_palette
            var id_palette =  parseInt($('#dataConfirmInput').val());
            if (isNaN(id_palette) || id_palette === undefined || id_palette === 0) {
                alert('ERREUR !\r\nIdentidication de la palette imposible !\r\nCode erreur : 6XW26F91');
                return false;
            }

            // On crée la composition
            addProduitPalette(id_palette);

            // On rajoute le flag permette de ne pas créer de palette après la création d'une nouvelle
            $('#skipCreateCompoSave').val(1);

            // Fermeture de la modale de confirmation
            $('#modalConfirm').modal('hide');

            // Si on force l'enregistrement du produit pour quitter l'étape
            // (Cas de la confirmation après validation finale)
            if ($('#forceSaveProduit').val() !== '') {
                var saveProduitParams = $('#forceSaveProduit').val().split(',');
                if (!saveProduitParams[2].length) { return false; }
                saveProduit(saveProduitParams[0], saveProduitParams[1], saveProduitParams[2]);
            }

            return false;

        } // FIN confirm sélection palette en surcapacité

        // ----------------------------------------
        // (Listener Etape 3 -> Modal confirm OK)
        // Supprimer le produit du traitement
        // (Quantité à zéro en mode "total")
        // ----------------------------------------
        if (data_confirm_contexte === 'supprPdtFroid') {

            // Identification de l'id_pdt_froid
            var id_lot_pdt_froid =  parseInt($('#dataConfirmInput').val());
            if (isNaN(id_lot_pdt_froid) || id_lot_pdt_froid === undefined || id_lot_pdt_froid === 0) {
                alert('ERREUR !\r\nIdentidication du lotpdtfroid imposible !\r\nCode erreur : 08IQJVTS');
                return false;
            }

            // Retrait du traitement + de la palette (compo)
            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
                'script_execute': 'fct_produits.php',
                'arguments': 'mode=supprIdLotPdtFroid&id_lot_pdt_froid='+id_lot_pdt_froid,
                'done': function () {

                    // Redirection contextuelle
                    if (!$('#inputIdFroid').length || parseInt($('#inputIdFroid').val()) === 0) {
                        chargeEtape(1,0);
                    } else {
                        chargeEtape(10,1);
                    }

                    // Fermeture de la modale (propre)
                    $('#modalConfirm').modal('hide');
                    return false;

                } // FIN Callback
            }); // FIN ajax


        } // FIN confirm sélection palette en surcapacité

        // ----------------------------------------
        // Création de palette alors que déjà
        // ----------------------------------------
        if (data_confirm_contexte === 'palettesDispoMaisCreation') {

            $('#modalConfirm').modal('hide');
            nouvellePalette(0);

        } // FIN confirm création palette

        return false;

    }); // FIN confirmation modale générique


    // ----------------------------------------
    // (Listener Etape 3)
    // Saisie via le pavé numérique
    // ----------------------------------------
    $('.clavier .btn').off("click.appuietoucheclaviernbcolis").on("click.appuietoucheclaviernbcolis", function(e) {

        e.preventDefault();

        // Identification du Lot
        var id_lot = parseInt($('#numLotTicket').data('id-lot'));

        // On récupère la valeur de la touche
        var touche = $(this).data('valeur');

        // ----------------------------------------
        // (Listener Etape 3 -> Clavier)
        // Touche [C] - Effacer
        // ----------------------------------------
        if (touche === 'C') {

            // On reset le champ en cours
            var valeurActuelle = $('input[name='+champ+']').val();
            $('input[name='+champ+']').val(valeurActuelle.slice(0,-1));

            // Si le champ en cours est le nb de colis, on remet à 0 le poids
            if (champ === 'nb_colis') {
                $('input[name=poids_pdt]').val('');
            }

            // Si on est en mode ajout en update, on efface le total estimé
            if (modeUpd && $('.methode-maj').prop('checked')) {
                $('.infoMajAjout .estimationtotal').text('');
            }

            return false;
        } // FIN touche C


        // ----------------------------------------
        // (Listener Etape 3 -> Clavier)
        // Touche [V] - Valider l'étape 3
        // ----------------------------------------
        if (touche === 'V') {

            // Si on est en mode update, on peux valider une qté vide, donc on met à zéro le champ si vide
            if (modeUpd && $('.inputNbColis').val() === '') {
                $('.inputNbColis').val('0');
                $('.inputPoidsPdt').val('0');
            }

            // On vérifie le nombre de colis et le poids
            var nbColis     = parseInt($('.inputNbColis').val());
            var poidsPdt    = parseFloat($('.inputPoidsPdt').val());
            var quant       = $('input[name=quantieme]').val();
            if (quant === undefined) { quant = ''; }

            // Si on update, on peux mettre une qté à 0 pour supprimer le pdt, sinon non
            var testColisQte    = modeUpd ? 0 : 1;
            var testColisPoids  = modeUpd ? 0 : 0.1;

            // Contrôle de cohérence du poids et du nombre de colis
            if (nbColis < testColisQte || isNaN(nbColis) || poidsPdt < testColisPoids) {

                $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>Nombre de colis ou poids invalide...</p></div>");
                $('#modalInfo').modal('show');
                return false;
            }

            // Si le quantième n'est pas défini, on ne peut pas valider l'étape
            if (quant === '') {
                $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>Précisez le quantième du jour de découpe...</p></div>");
                $('#modalInfo').modal('show');
                return false;
            }

            // ----------------------------------------
            // (Listener Etape 3 -> Clavier -> Valider)
            // Si poids ou quantité vide...
            // ----------------------------------------
            if (nbColis === 0 || poidsPdt === 0) {

                var quantieme_selectionne   = parseInt($('input[name=quantieme]').val());
                var quantieme_historique    = parseInt($('#inputIdQuantieme').val());
                var idlotpdtfroid           = parseInt($('#inputIdLotPdtFroid').val());
                var modeTotal               = !$('.methode-maj').prop('checked');

                // Si le quantième a été changé :
                if (quantieme_selectionne > 0 && quantieme_selectionne !== quantieme_historique && idlotpdtfroid > 0) {

                    // On fait uniquement le mise à jour du quantième en base
                    $.fn.ajax({
                        'rep_script_execute': "../scripts/ajax/",
                        'script_execute':'fct_produits.php',
                        'arguments': 'mode=saveQuantiemeLotPdtFroid&id_lot_pdt_froid=' + idlotpdtfroid + '&quantieme='+quantieme_selectionne
                    }); // FIN ajax


                    // Sinon (le quantième n'a pas changé et on a pas de poids/colis), et si on est en mode de mise à jour "total", c'est qu'on souhaite sans doute supprimer le produit du traitement...
                } else if (modeTotal) {

                    // Modale de confirmation "Supprimer produit ?" -  Le listener prend la suite...
                    $('#modalConfirmBody').html('<div class="alert alert-danger text-center"><i class="fa fa-trash-alt fa-2x mb-2"></i><p>Supprimer ce produit du traitement ?</p></div>');
                    $('#dataConfirmInput').val(idlotpdtfroid);               // ID en paramètre de la modale de confirmation : id_lot_pdt_froid
                    $('#dataConfirmContexte').val('supprPdtFroid');    // Code du contexte pour le listener de la modale générique de confirmation
                    $('#modalConfirm').modal('show');
                    return false;

                }// FIN supprimer produit

                // ICI on a pas de modif de quantité, pas de changement de quantième et on ne souhaite pas supprimer le produit...
                // Si on a pas d'op de froid, c'est qu'on est à la premiere séléction du premier produit, on reviens donc à l'étape 2 :
                if (!$('#inputIdFroid').length || parseInt($('#inputIdFroid').val()) === 0) {
                    chargeEtape(1,0);

                    // Sinon on retourne à la liste des produits du traitement :
                } else {
                    chargeEtape(10,1);
                }

                return false;

            } // FIN test modification poids / nb de colis à la validation


            // ......................................................................................................
            // /!\ Le changement de palette est impossible sans ajout...
            //     En effet, si cet id_lot_pdt_froid peut être associé à plusieurs compositions dans la même palette,
            //     en changeant de palette, on ne saurait être certain de la quantité à déplacer !
            // ......................................................................................................

            // Identification de la palette et de la compo
            var id_palette   = parseInt($('#inputIdPalette').val());
            var id_compo     = parseInt($('#inputIdCompo').val());

            var palette_selectionne = $('.palette-selectionnee').length > 0;

            // Si on est pas en mode update, on vérifie qu'une palette est bien sélectionnée
            // Elle peut etre vide en mode update dans le cas de figure ou on rajoute une quantitié sans avoir cliqué sur une autre palette que la précédente.
            if ((!modeUpd && (isNaN(id_compo) || id_compo === undefined || id_compo === 0 || isNaN(id_palette) || id_palette === undefined || id_palette === 0)) || !palette_selectionne) {
                $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>Sélectionnez une palette...</p></div>");
                $('#modalInfo').modal('show');
                return false;
            }

            // Si on est en update, qu'on a pas cliqué sur une palette, et qu'on rajoute des quantitiés, il faut créer une nouvelle compo pour ces quantités, sans effacer l'ancienne.
            // Le problème c'est que si on a créé une palette, on a pas eu à cliquer sur l'une d'elles et donc on risquerait une double création !!
            // On teste donc un flag qui permet de bypasser (skip) la création. Celui-ci est apposé lors de la création dans une variable.
            var skip_add_palette = parseInt($('#skipCreateCompoSave').val()) > 0;

            // Si on est en mode update, qu'on a du poids à ajouter au produit et qu'on a pas à ignorer la création de la composition...

            // ----------------------------------------
            // (Listener Etape 3 -> Clavier -> Valider)
            // Création de la composition de palette
            // ----------------------------------------
            if (modeUpd && poidsPdt > 0 && !skip_add_palette) {

                // On compare la capacité restante de la palette avec le poids et le nb de colis a rajouter
                var palette_restant_poids = parseInt($('#cartepaletteid'+id_palette).data('id-poids-restant'));
                var poids                 = parseFloat($('.inputPoidsPdt').val());


                // ----------------------------------------
                // (Listener Etape 3 -> Clavier -> Valider)
                // Capacité palette OK (poids)
                // ----------------------------------------
                if (palette_restant_poids >= poids) {


                    // On retire la compo de référence pour ne pas qu'elle soit supprimée en BDD
                    $('#inputIdCompo').val(0);

                    // On crée la composition
                    addProduitPalette(id_palette, 0); // On force de ne pas supprimer la palette à créer (car validation directe)

                    // On rajoute le flag pour permette de ne pas créer de palette après la création d'une nouvelle
                    $('#skipCreateCompoSave').val(1);


                    // ----------------------------------------
                    // (Listener Etape 3 -> Clavier -> Valider)
                    // Capacité palette INSUFFISANTE ! (poids)
                    // ----------------------------------------
                } else {

                    // Aie aie aie... il y en a un peu plus... j'vous l'met quand même ?

                    // Si la palette est déjà hors capacité (pleine) : message d'alerte...
                    if (palette_restant_poids <= 0) {
                        $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>Cette palette est déjà hors capacité </p></div>");
                        return false;


                        // Confirmation dans la modale générique...
                    } else {

                        // Récupération du numéro de palette pour info dans le texte
                        var numero_palette = $('#cartepaletteid'+id_palette).data('numero-palette');

                        var htmConfirm = '<div class="alert alert-danger text-center"><span class="text-20"><i class="fa fa-exclamation-circle mr-1"></i>Dépassement de capacité !</span><hr><p class="badge badge-warning text-18">La palette ' + numero_palette + ' ne peut contenir plus que ' + palette_restant_poids + ' kgs.</p><p class="text-center">Confirmer le dépassement de ' + parseInt(poids - palette_restant_poids) + ' kgs ?</p></div>';

                        $('#modalConfirmBody').html(htmConfirm);
                        $('#dataConfirmInput').val(id_palette);                         // ID passé pour le listener de la modale de confirmation : id_palette
                        $('#dataConfirmContexte').val('paletteOverCapacite');     // Code du contexte pour le listener de la modale générique de confirmation
                        $('#modalConfirm').modal('show');

                        // Comme on viens déjà de cliquer sur le bouton valider, on flag qu'on souhaite, en cas de confirmation sur le Listener, forcer l'enregistrement du produit pour terminer l'Etape 3...
                        // On passe en paramètre les données nécéssaire à la fonction d'enregistrement, qu'on ne pourrait pas obtenir aisément depuis le listener de la modale de confirmation.
                        $('#forceSaveProduit').val(identifiant+','+id_palette+','+id_compo);
                        return false;

                    } // FIN test palette déjà hors capacité ou en dépassement simple

                    return false;

                } // FIN test sur la capacité restante de la palette sélectionnée

            } // FIN test sur la nécéssité de créer la composition de palette ou non

            // Enregistrement du produit au traitement
            saveProduit(identifiant, id_palette, id_compo);
            return false;

        } // FIN test touche valider


        // ----------------------------------------
        // (Listener Etape 3 -> Clavier)
        // Touche [>] - Changement de champ
        // ---------------------------------------
        if (touche === '>') {

            // Quantité -> Poids
            if (champ === 'nb_colis') {

                champ = 'poids_pdt';
                $('input[name=poids_pdt]').focus();
                $(this).find('i.fa').removeClass('fa-weight').addClass('fa-boxes');

            // Poids > Quantité
            } else {

                champ = 'nb_colis';
                $('input[name=nb_colis]').focus();
                $(this).find('i.fa').removeClass('fa-boxes').addClass('fa-weight');
            } // FIN test sens

            return false;

        } // FIN changement de champ

        // ----------------------------------------
        // (Listener Etape 3 -> Clavier)
        // Autre touche numérique [0-9]
        // ----------------------------------------

        // Autre touche numérique : on complète le code
        var champVal    = $('input[name='+champ+']').val().trim();
        var poidsDefaut = $('.inputPoidsPdt').data('poids-defaut');

        // Si on est sur le champ du poids
        if (champ === 'poids_pdt') {

            var poidsProduit = $('.inputPoidsPdt').val().trim();

            if (poidsProduit.length === 0) {
                $('.inputPoidsPdt').val(touche);

                // Si on saisi un point, on vérifie qu'il n'y en a pas déjà un
            } else if (touche === '.' && poidsProduit.indexOf('.') !== -1) {
                return false;
            } else {
                $('.inputPoidsPdt').val(poidsProduit + touche);
            } // FIN test point [.] ou chanmp vide

        // Sinon, on est sur le champ nb de colis
        } else {

            var nbcolis = $('.inputNbColis').val().trim();

            // Pas de décimales dans le nombre de colis
            if (touche === '.') { return false; }

            // Si le champ était vide, il prend la valeur de la touche
            if (nbcolis.length === 0) {
                $('.inputNbColis').val(touche);

            // Sinon on concatène la valeur...
            } else {
                $('.inputNbColis').val(nbcolis + touche);
            }

            // On formate le poids final proprement
            var poidsFinal = poidsDefaut * parseInt( $('.inputNbColis').val());
            $('.inputPoidsPdt').val(poidsFinal);

        } // FIN test sur le champ (poids/nb_colis)

        // ----------------------------------------
        // (Listener Etape 3 -> Clavier)
        // Mise à jour de l'info sur le total produit
        // (mode update et mode cumul)
        // ----------------------------------------
        if (modeUpd && $('.methode-maj').prop('checked')) {

            var poids_pdt_old   = parseFloat($('input[name=poids_pdt_old]').val());
            var nb_colis_old    = parseInt($('input[name=nb_colis_old]').val());
            var poids_pdt_add   = parseFloat($('input[name=poids_pdt]').val());
            var nb_colis_add    = parseInt($('input[name=nb_colis]').val());
            var nb_colis        = nb_colis_old + nb_colis_add;
            var poids_pdt       = poids_pdt_old + poids_pdt_add;
            var info            = "Total produit mis à jour : " + nb_colis + ' blocs, ' + poids_pdt + ' kg.';
            $('.infoMajAjout .estimationtotal').text(info);
        }


    }); // FIN touche clavier

} // FIN listener étape 3


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 4 (Température de début de surgélation)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape4(identifiant) {
    "use strict";

    chargeTicketLot(4, identifiant);

    // Saisie via le pavé numérique
    $('.clavier .btn').off("click.appuietoucheclavier").on("click.appuietoucheclavier", function(e) {

        e.preventDefault();

        // On efface le message d'erreur si il était affiché
        if (!$('.tempInvalide').hasClass('d-none')) {
            $('.tempInvalide').addClass('d-none');
        }

        var id_froid = parseInt($('.btnValiderTempDebut').data('id-froid'));

        // On récupère la valeur de la touche
        var touche = $(this).data('valeur');

        // Si touche "effacer", on reset le champ en cours
        if (touche === 'C') {
            $('.inputTempDebut').val('');
            return false;
        }

        // SI touche "Valider", on teste...
        if (touche === 'V') {

            var tempDebut = $('.inputTempDebut').val().trim();

            if (tempDebut.trim() === '') { return false; }

            if (parseFloat(tempDebut) < -30 || parseFloat(tempDebut) > 30 || isNaN(parseFloat(tempDebut))) {
                $('.tempInvalide').removeClass('d-none');
                return false;
            }

            // Enregistrement de la température
            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/",
                'script_execute':'fct_vue_srgv.php',
                'arguments': 'mode=saveTempDebut&id_froid=' + id_froid + '&temp_debut='+tempDebut,
                'callBack': function (retour) {

                    if (parseInt(retour) < 0) { alert("Une erreur est survenue !\r\nEnregistrement impossible...\r\nCode erreur : JJWFBEKX"); return false; }
                    chargeEtape(5,0);
                    return false;

                } // FIN callBack
            }); // FIN ajax

            return false;

        } // FIN test touche valider

        // Autre touche numérique : on complète le code
        var tempDebut = $('.inputTempDebut').val().trim();

        if (tempDebut.length === 0 && touche !== '.' && touche !== '+') {
            $('.inputTempDebut').val(touche);

            // Si on saisi un point, on vérifie qu'il n'y en a pas déjà un
        }  else if (touche === '.' && tempDebut.indexOf('.') !== -1) {
            return false;
        } else if (touche === '.' && tempDebut.length === 0) {
            return false;
            // +/-
        } else if (touche === '+') {
            if (tempDebut === '') {
                $('.inputTempDebut').val('-');
                return false;
            } else if  (tempDebut === '-') {
                $('.inputTempDebut').val('');
                return false;
            }
            tempDebut = parseFloat(tempDebut) *-1;
            $('.inputTempDebut').val(tempDebut);
        } else {
            $('.inputTempDebut').val(tempDebut + touche);
        } // FIN saisie touhches numériques

    }); // FIN touche clavier

} // FIN listener

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 5 (Départ surgélation)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape5(identifiant) {
    "use strict";

    chargeTicketLot(5, identifiant);

    // Toogle pour cycle de nuit
    $('.togglemaster-nuit').bootstrapToggle();

    // Début SRG
    $('.btnDebutSrg').off("click.btndebutsrg").on("click.btndebutsrg", function(e) {

        e.preventDefault();

        var id_froid = parseInt($('#inputIdFroid').val());

        // Cycle de nuit ? (si renseigne)
        var nuit = $('.togglemaster-nuit').is(':checked') ? 1 : 0;


        // Enregistrement du début de surgélation
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute':'fct_vue_srgv.php',
            'arguments': 'mode=saveDebutSrg&id_froid=' + id_froid + '&nuit=' + nuit,
            'callBack': function (retour) {
                if (parseInt(retour) < 0) { alert("Une erreur est survenue !\r\nEnregistrement impossible...\r\nCode erreur : KFFWTPAK"); return false; }
                chargeTicketLot(51, identifiant);
                chargeEtape(0,0);
                return false;
            } // FIN callBack
        }); // FIN ajax

    }); // FIN bouton début SRG

} // FIN listener

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 6 (Température de fin de surgélation)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape6(identifiant) {
    "use strict";

    chargeTicketLot(6, identifiant);

    // Saisie via le pavé numérique
    $('.clavier .btn').off("click.appuietoucheclavier").on("click.appuietoucheclavier", function(e) {

        e.preventDefault();

        // On efface le message d'erreur si il était affiché
        if (!$('.tempInvalide').hasClass('d-none')) {
            $('.tempInvalide').addClass('d-none');
        }

        var id_froid = parseInt($('.btnValiderTempFin').data('id-froid'));

        // On récupère la valeur de la touche
        var touche = $(this).data('valeur');

        // Si touche "effacer", on reset le champ en cours
        if (touche === 'C') {
            $('.inputTempFin').val('');
            return false;
        }

        // SI touche "Valider", on teste...
        if (touche === 'V') {

            var tempFin = $('.inputTempFin').val().trim();

            if (tempFin.trim() === '') { return false; }

            if (isNaN(parseFloat(tempFin))) {
                $('.tempInvalide').removeClass('d-none');
                return false;
            }



            // Modal confirmation si température hors normes

            // Températures de référence pour contrôle
            var temp_controle_min = parseFloat($('.temp-controles').data('temp-controle-min'));
            var temp_controle_max = parseFloat($('.temp-controles').data('temp-controle-max'));

            // Si erreur de récup des températures de contrôle (mauvais paramétrage dans le BO...), on ne bloque pas le process... ici on test qu'on est ok
            if (!isNaN(temp_controle_min) && temp_controle_min !== undefined && !isNaN(temp_controle_min) && temp_controle_min !== undefined) {

                var ok = true;
                var rappelSaisieTemp = '';

                // SI la température est hors norme...
                if (tempFin < temp_controle_min || tempFin > temp_controle_max) { ok = false; rappelSaisieTemp+= '<span class="text-danger">' + tempFin + '°C</span>'; }

                // SI erreur, on affiche la modale...
                if (!ok) {

                    rappelSaisieTemp = 'Vous avez saisi ' + rappelSaisieTemp;

                    $('#rappelConsignesTemp').html($('#consignesTemp').html());
                    $('#rappelSaisieTemp').html(rappelSaisieTemp);

                    // On affiche la zone question
                    $('#tempHsQuestion').show();

                    // On masque la zone commentaire
                    $('#tempHsCommentaires').hide();

                    $('#modalConfirmTemp').modal('show');

                    listenerModaleTempHs(id_froid);

                    return false;

                } // FIN test températures valides



            } // FIN vérif températures de contrôles bien récupérées


            saveTemperature(id_froid);

            return false;



        } // FIN test touche valider

        // Autre touche numérique : on complète le code
        var tempFin= $('.inputTempFin').val().trim();

        if (tempFin.length === 0 && touche !== '.' && touche !== '+') {
            $('.inputTempFin').val(touche);
            // Si on saisi un point, on vérifie qu'il n'y en a pas déjà un
        }  else if (touche === '.' && tempFin.indexOf('.') !== -1) {
            return false;
        } else if (touche === '.' && tempFin.length === 0) {
            return false;
            // +/-
        } else if (touche === '+') {
            if (tempFin === '') {
                $('.inputTempFin').val('-');
                return false;
            } else if  (tempFin === '-') {
                $('.inputTempFin').val('');
                return false;
            }
            tempFin = parseFloat(tempFin) *-1;
            $('.inputTempFin').val(tempFin);
        } else {
            $('.inputTempFin').val(tempFin + touche);
        } // FIN saisie touhches numériques

        verifTempFinNegative();

    }); // FIN touche clavier

} // FIN listener

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 7 (Contrôles LOMA)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape7(identifiant) {
    "use strict";

    chargeTicketLot(7,0);
    escapeLOMACOntrole();
    // Carte produit à contrôler
    $('.carte-pdt-loma').off("click.cartepdtloma").on("click.cartepdtloma", function(e) {

        e.preventDefault();

        var id_lot_pdt_froid = parseInt($(this).data('id-lot-pdt-froid'));
        if (id_lot_pdt_froid === undefined || isNaN(id_lot_pdt_froid) || id_lot_pdt_froid === 0) {
            alert('ERREUR\r\n\r\nIdentification du produit impossible !\r\n\r\nCode erreur : 5K1ELNX8');
            return false;
        }

        chargeEtape(7, id_lot_pdt_froid);



    }); // FIN carte produit à contrôler

    // Bouton test ok / ko
    $('.btn-loma').off("click.btnloma").on("click.btnloma", function(e) {

        e.preventDefault();

        // Récup test type
        var testcode = $(this).data('test');
        var regextest = new RegExp('^(nfe|fe|inox|pdt)$');
        if (testcode === undefined || !regextest.test(testcode)) {
            alert('ERREUR\r\n\r\nIdentification du test impossible ('+testcode+') !\r\n\r\nCode erreur : 3T5BQ30K');
            return false;
        } // FIN gestion erreur de récupération test code

        // Résultat du test
        var resultat = parseInt($(this).data('resultat'));
        if (resultat === undefined || resultat < 0 || resultat > 1 || isNaN(resultat)) {
            alert('ERREUR\r\n\r\nIdentification du résultat du test impossible !\r\n\r\nCode erreur : V9DX73MU');
            return false;
        } // FIN gestion erreur de récupération résultat

        // On met à jour le resultat dans le DOM pour éviter un triple appel ajax/BDD
        $('input[name=resultest_'+testcode+']').val(resultat);

        // On met à jour l'affichage des boutons
        var classeAutreBtn = resultat === 1 ? 'danger' : 'success';
        var classeBtn = resultat === 1 ? 'success' : 'danger';

        // On inverse s'il s'agit du produit
        if ($(this).data('test') === 'pdt') {
            classeAutreBtn = resultat === 1 ? 'success' : 'danger';
            classeBtn = resultat === 1 ? 'danger' : 'success';
        }

        $(this).parents('.loma-test-btns').find('.btn-'+classeAutreBtn).removeClass('btn-'+classeAutreBtn).addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-'+classeBtn);

        // Résultat du test produit
        var testpdtObjet = $('.resultats-tests input[name=resultest_pdt]');
        var testpdt      = parseInt(testpdtObjet.val());

        // Résultats des plaquettes
        var testfe      = parseInt($('.resultats-tests input[name=resultest_fe]').val());
        var testnfe     = parseInt($('.resultats-tests input[name=resultest_nfe]').val());
        var testinox    = parseInt($('.resultats-tests input[name=resultest_inox]').val());


        //console.log(testpdt + ' ' + testfe + ' ' + testnfe + ' ' + testinox);

        // Si on a renseigné le test du produit
        if (testpdt > -1) {

            // Si on a du métal dans la viande, on affiche le champ "commentaires" et le bouton valider
            if (testpdt > 0) {

                $('.loma-commentaires').show('blind');  // classe = conteneur
                clavierVirtuel();
                $('#champ_clavier').focus();        // ID = textarea

                // Sinon, on valide direct
            } else {
                valideLoma();

            } // FIN test un résultat au moins à zéro

        } // FIN test terminé

        // SI on a validé les 3 test apres
        if (testfe > -1 && testnfe > -1 && testinox > -1) {


            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/",
                'script_execute': 'fct_vue_srgv.php',
                'form_id': 'controleLoma',
                'callBack': function(retour) {

                    retour+= ''; // Debug mauvaise interprétation de retours
                    if (parseInt(retour) !== 1) { alert('Une erreur est survenue !\r\nCode erreur : '+retour); return false; }

                    chargeEtape(8,0);

                } // FIN callBack

            }); // FIN aJax


        } // FIN test apres terminé


    }); // FIN bouton test loma

    // Bouton valide loma apres commentaire
    $('.btn-valid-loma').off("click.btnvalidloma").on("click.btnvalidloma", function(e) {

        e.preventDefault();

        valideLoma();

    }); // FIN bouton valide loma après commentaire

/*
    // Bouton FIN loma, on charge l'étape suivante
    $('.btn-fin-loma').off("click.btnfinloma").on("click.btnfinloma", function(e) {

        chargeEtape(8,0);

    }); // FIN Bouton FIN loma
*/





} // FIN listener


function escapeLOMACOntrole(){

    var testpdtObjet = $('.resultats-tests input[name=resultest_pdt]');
    var testpdt      = parseInt(testpdtObjet.val());

    // Résultats des plaquettes
    var testfe      = parseInt($('.resultats-tests input[name=resultest_fe]').val());
    var testnfe     = parseInt($('.resultats-tests input[name=resultest_nfe]').val());
    var testinox    = parseInt($('.resultats-tests input[name=resultest_inox]').val());


    //console.log(testpdt + ' ' + testfe + ' ' + testnfe + ' ' + testinox);

    // Si on a renseigné le test du produit
    if (testpdt > -1) {

        // Si on a du métal dans la viande, on affiche le champ "commentaires" et le bouton valider
        if (testpdt > 0) {

            $('.loma-commentaires').show('blind');  // classe = conteneur
            clavierVirtuel();
            $('#champ_clavier').focus();        // ID = textarea

            // Sinon, on valide direct
        } else {
            valideLoma();

        } // FIN test un résultat au moins à zéro

    } // FIN test terminé

    // SI on a validé les 3 test apres
    if (testfe > -1 && testnfe > -1 && testinox > -1) {


        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_vue_srgv.php',
            'form_id': 'controleLoma',
            'callBack': function(retour) {

                retour+= ''; // Debug mauvaise interprétation de retours
                if (parseInt(retour) !== 1) { alert('Une erreur est survenue !\r\nCode erreur : '+retour); return false; }

                chargeEtape(8,0);

            } // FIN callBack

        }); // FIN aJax


    }
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 8 (Emballages)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape8(identifiant) {
    "use strict";

    chargeTicketLot(8,0);

    // bouton changer de rouleau direct sur emballage
    $('.btn-emb-change').off("click.btnembchange").on("click.btnembchange", function(e) {
        e.preventDefault();

        var id_fam      = parseInt($(this).parents('.card').data('id-fam'));
        var id_old_emb  = parseInt($(this).data('id-old-emb'));

        if (id_fam === undefined || id_fam === 0 || isNaN(id_fam)) { alert("Identifaction de la famille impossible.\nCode erreur : W0OYT7EA"); return false; }

        // Famille identifiée, on rechage le body de la modale avec les emballages disponibles ayant du stock et non par défaut:
        $('#modalNouvelEmballage').modal('show');

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_emballages.php',
            'arguments': 'mode=modalNouvelEmballageFrontEtape2&vue_code=atl&id_fam='+id_fam+'&id_old_emb='+id_old_emb,
            'return_id' : 'modalNouvelEmballageBody',
            'done': function() {
                modalNouvelEmballageFrontEtape2Listener();
            }
        }); // FIN aJax


    }); // FIN bouton changer rouleau sur emballage

    // Bouton déclarer un défectueux sur l'emballage
    $('.btn-emb-defectueux').off("click.btnembchange").on("click.btnembchange", function(e) {
        e.preventDefault();

        var id_emb = parseInt($(this).data('id-emb'));
        if (id_emb === undefined || id_emb === 0 || isNaN(id_emb)) {
            alert("Identifaction du rouleau impossible.\nCode erreur : MSONPCAH");
            return false;
        }

        var id_froid    = parseInt($('#inputIdFroid').val());

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_emballages.php',
            'arguments': 'mode=declareDefectueux&id_emb=' + id_emb + '&id_froid='+id_froid,
            'done': function () {
                $('#modalInfoBody').html('<h4><i class="fa fa-2x fa-check mb1 gris-5"></i><br>Emballage défectueux enregistré</h4>');
                $('#modalInfo').modal('show');
            }
        }); // FIN aJax

    }); // FIN bouton déclarer un défectueux sur l'emballage

} // FIN listener

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 11 (Test Loma avant)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function listenerEtape11(identifiant) {
    "use strict";

    // Bouton test ok / ko
    $('.btn-loma').off("click.btnloma").on("click.btnloma", function(e) {

        e.preventDefault();

        // Récup test type
        var testcode = $(this).data('test');
        var regextest = new RegExp('^(nfe|fe|inox)$');
        if (testcode === undefined || !regextest.test(testcode)) {
            alert('ERREUR\r\n\r\nIdentification du test impossible ('+testcode+') !\r\n\r\nCode erreur : G28C6R17');
            return false;
        } // FIN gestion erreur de récupération test code

        // Résultat du test
        var resultat = parseInt($(this).data('resultat'));
        if (resultat === undefined || resultat < 0 || resultat > 1 || isNaN(resultat)) {
            alert('ERREUR\r\n\r\nIdentification du résultat du test impossible !\r\n\r\nCode erreur : H112SBD8');
            return false;
        } // FIN gestion erreur de récupération résultat

        // On met à jour le resultat dans le DOM pour éviter un triple appel ajax/BDD
        $('input[name=resultest_'+testcode+']').val(resultat);

        // On met à jour l'affichage des boutons
        var classeAutreBtn = resultat === 1 ? 'danger' : 'success';
        var classeBtn = resultat === 1 ? 'success' : 'danger';

        // On inverse s'il s'agit du produit
        if ($(this).data('test') === 'pdt') {
            classeAutreBtn = resultat === 1 ? 'success' : 'danger';
            classeBtn = resultat === 1 ? 'danger' : 'success';
        }

        $(this).parents('.loma-test-btns').find('.btn-'+classeAutreBtn).removeClass('btn-'+classeAutreBtn).addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-'+classeBtn);

        // Résultat du test produit
        var testpdtObjet = $('.resultats-tests input[name=resultest_pdt]');
        var testpdt      = parseInt(testpdtObjet.val());

        // Résultats des plaquettes
        var testfe      = parseInt($('.resultats-tests input[name=resultest_fe]').val());
        var testnfe     = parseInt($('.resultats-tests input[name=resultest_nfe]').val());
        var testinox    = parseInt($('.resultats-tests input[name=resultest_inox]').val());

        // On vérifie si les trois tests sont renseignés
        var termine = true;
        var test0   = false;
        $('.resultats-tests input').each(function() {
            var res = parseInt($(this).val());
            if (res < 0) {
                termine = false;
            }
            if (res === 0) {
                test0 = true;
            }
        });

        // Si on a passé les 3 tests témoins, on save et on va a l'étape suivante
        if (testinox > -1 && testfe > -1 && testnfe > -1) {

            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/",
                'script_execute': 'fct_vue_srgv.php',
                'form_id': 'controleLoma',
                'callBack': function(retour) {

                    retour+= ''; // Debug mauvaise interprétation de retours
                    if (parseInt(retour) !== 1) { alert('Une erreur est survenue !\r\nCode erreur : '+retour); return false; }
                    var id_lot    = parseInt($('#numLotTicket').data('id-lot'));
                    chargeEtape(7,id_lot);

                } // FIN callBack

            }); // FIN aJax


        } // FIN tests passés

    }); // FIN bouton test loma

} // FIN listener

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 9 (Conformité/fin)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape9(identifiant) {
    "use strict";

    chargeTicketLot(9,0);

    // Bouton conforme/non conforme
    $('.btnConformiteSrg').off("click.btnconformitesrg'").on("click.btnconformitesrg", function(e) {
        e.preventDefault();

        var conformite  = parseInt($(this).data('conformite'));
        var id_froid    = parseInt($('#inputIdFroid').val());

        if (conformite < 0 || conformite > 1 || isNaN(conformite) || conformite === undefined) { alert("Une erreur est survenue !\r\nCode Erreur : 1N6J2EDV"); return false; }

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_vue_srgv.php',
            'arguments': 'mode=conformiteSrg&id_froid='+id_froid + '&conformite='+conformite,
            'callBack': function(retour) {

                retour+= ''; // Debug mauvaise interprétation de retours
                if (parseInt(retour) < 0) { alert('Une erreur est survenue !\r\nCode Erreur : PQRCVNSA'); return false; }

                chargeEtape(100,0);

            } // FIN callBack

        }); // FIN aJax

    }); // FIN Bouton conforme/non conforme

} // FIN listener


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 10 (Liste des produits sélectionnés)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape10(identifiant) {
    "use strict";

    if (identifiant === undefined) { identifiant = 0; }

    chargeTicketLot(10, identifiant);

    // Nettoie les composition abandonnées
    cleanCompoPalettesVides();

    // Mise en attente produit
    $('.btnAttenteProduit').off("click.btnchangepoidspdt'").on("click.btnchangepoidspdt", function(e) {
        e.preventDefault();

        var idLotPdtFroid = parseInt($(this).data('id-lot-pdt-froid'));
        var nomProduitLigne = $(this).parents('tr').find('.nomProduitLigne').text().trim();
        $('#modalConfirmAttenteProduitTitle').text(nomProduitLigne);
        $('#dataConfirmAttenteProduitFroidId').val(idLotPdtFroid);

    }); // FIn chargement de la modale de confirmation mide en attente du produit

    // Etiquetage
    $('.togglemaster').bootstrapToggle();

    setTimeout(function () {
        checkToutEtiquetePourDebutFroid();
    }, 500);

    $('.togglemaster').change(function () {

        var etiquetage = $(this).prop('checked') === true ? 1 : 0;
        var id_lot_pdt_froid = parseInt($(this).data('id-lot-pdt-froid'));

        if (id_lot_pdt_froid < 1 || isNaN(id_lot_pdt_froid)) {
            alert("ERREUR !\r\nIdentification du traitement impossible.\r\nCode erreur : DFO1P3YA");
            return false;
        }

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_vue_srgv.php',
            'arguments': 'mode=etiquetageProduitLot&id_lot_pdt_froid=' + id_lot_pdt_froid + '&etiquetage=' + etiquetage,
            'done': function () {

                checkToutEtiquetePourDebutFroid();

            } // FIN done

        }); // FIN aJax

    }); // FIN étiquetage

    // Bouton changer le poids du produit
    $('.btnChangePoidsPdt').off("click.btnchangepoidspdt'").on("click.btnchangepoidspdt", function(e) {

        e.preventDefault();

        var id_lot_pdt_froid = parseInt($(this).data('id-lot-pdt-froid'));

        chargeEtape(3,id_lot_pdt_froid);

    }); // FIN bouton changer poids

    // Tri des colonnes - Produits
    $('.tri-produits').off("click.triproduits'").on("click.triproduits", function(e) {
        e.preventDefault();

        var sens     = $(this).data('sens');
        var id_froid = parseInt($(this).parents('table').data('id-froid'));
        var actif    = parseInt($(this).data('tri-actif'));

        // Si on change le sens du tri actif
        if (actif === 1) { sens = sens === 'asc' ? 'desc' : 'asc'; }

        triTableProduits(id_froid, 'pdt', sens, identifiant);

    }); // FIN tri colonne produits

    // Tri des colonnes - Lots
    $('.tri-lots').off("click.trilots'").on("click.trilots", function(e) {
        e.preventDefault();

        var sens      = $(this).data('sens');
        var id_froid  = parseInt($(this).parents('table').data('id-froid'));
        var actif     = parseInt($(this).data('tri-actif'));

        // Si on change le sens du tri actif
        if (actif === 1) { sens = sens === 'asc' ? 'desc' : 'asc'; }

        triTableProduits(id_froid,'lot', sens, identifiant);

    }); // FIN tri colonne lots

} // FIN listener étape 10


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 101 (Liste des surgélations en cours)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape101(identifiant) {
    "use strict";

    chargeTicketLot(101,0);

    // Retour vers le point d'entrée
    $('.btnRetourEtape0').off("click.btnretouretape0'").on("click.btnretouretape0", function(e) {

        e.preventDefault();
        $('#inputIdFroid').val(0);
        chargeEtape(0, 0);

    }); // FIN Retour vers le point d'entrée

    // Sélection d'une SRG pour cartesrg
    $('.carte-srg').off("click.cartesrg'").on("click.cartesrg", function(e) {

        e.preventDefault();

        var id_froid = parseInt($(this).data('id-froid'));
        $('#inputIdFroid').val(id_froid);
        chargeEtape(10, 1);

    }); // FIN click carte SRG

    // Supprimer traitement
    $('.btnSupprimerFroidVide').off("click.btnSupprimerFroidVide'").on("click.btnSupprimerFroidVide", function(e) {

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var id_froid = parseInt($(this).data('id-froid'));
        if (isNaN(id_froid) || id_froid === 0) {
            alert("ERREUR !\r\nIdentification du traitement impossible...\r\nCode erreur : 28LH6P7M");
            return false;
        }

        // On supprime l'OP de froid
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_produits.php',
            'arguments': 'mode=supprimeFroid&id_froid='+id_froid,
            'done': function() {
                chargeEtape(101,0);
            }
        }); // FIN aJax

    }); // FIN click carte SRG

} // FIN listener


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 100 (Fin du traitement)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape100(identifiant) {
    "use strict";

    chargeTicketLot(100,0);

    // Confirmation dans la modale de la clôture du traitemenr

    $('.btnConfirmClotureSrg').off("click.btnconfirmcloturesrg'").on("click.btnconfirmcloturesrg", function(e) {

        e.preventDefault();

        var id_froid = parseInt($('#inputIdFroid').val());
        if (id_froid === 0 || isNaN(id_froid) || id_froid === undefined) { alert('Une erreur est survenue !\r\nCode erreur : 94A5NTKG');return false;}

        // On enregistre (visa, staut, etc...)
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_vue_srgv.php',
            'arguments': 'mode=clotureSrg&id_froid='+id_froid,
            'done': function() {
                location.reload();
            }
        }); // FIN aJax
    });

} // FIN listener


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener général du ticket
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerTicketLot() {
    "use strict";

    // Retour vers les lots
    $('.btnChangeLot').off("click.btnchangelot'").on("click.btnchangelot", function(e) {
        e.preventDefault();

        chargeEtape(1, 0);

    }); // FIN Retour vers les lots

    // Retour à la sélection des produits (bouton ajouter des produits)
    $('.btnRetourSelectionPdts').off("click.btnretourselectionpdts'").on("click.btnretourselectionpdts", function(e) {
        e.preventDefault();

        chargeEtape(1, 0);
    });

    // Chargement du contenu de la modale de confirmation de fin de surgélation à son ouverture
    $('#modalConfirmFinFroid').on('show.bs.modal', function (e) {

        e.stopImmediatePropagation();

        var id_froid = parseInt($('#inputIdFroid').val());

        // On récupère le contenu de la modale
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_vue_srgv.php',
            'arguments': 'mode=modalConfirmFinFroid&id_froid='+id_froid,
            'callBack': function(retour) {

                retour+= '';
                var donnees = retour.split('^');
                if (donnees[0] !== undefined) { $('#modalConfirmFinFroidTitle').html(donnees[0]); }
                if (donnees[1] !== undefined) { $('#modalConfirmFinFroidBody').html(donnees[1]); }

                $('.btnConfirmFinFroid').click(function() {
                    $('#modalConfirmFinFroid').modal('hide');
                    chargeEtape(6,id_froid);
                });

            } // FIN callback
        }); // FIN aJax
    }); // Fin chargement du contenu de la modale

    // Retour vers la température de début de srg
    $('.btnModifTempDebut').off("click.btnmodiftempdebut'").on("click.btnmodiftempdebut", function(e) {

        e.preventDefault();
        chargeEtape(4, 0);

    }); // FIN Retour vers les lots


    // Retour vers le point d'entrée
    $('.btnRetourEtape0').off("click.btnretouretape0'").on("click.btnretouretape0", function(e) {

        e.preventDefault();
        $('#inputIdFroid').val(0);
        $('#ticketLotContent').html('');
        chargeEtape(0, 0);

    }); // FIN Retour vers le point d'entrée

    // Retour vers les surgélations en cours
    $('.btnRetourSrgsEnCours').off("click.btnretoursrgsencours'").on("click.btnretoursrgsencours", function(e) {

        e.preventDefault();
        $('#inputIdFroid').val('');
        chargeEtape(101, 0);

    }); // FIN Retour vers les SRGs en cours

    // Bouton continuer la surgélation
    $('.btnContinuerSrg').off("click.btncontinuersrg'").on("click.btncontinuersrg", function(e) {

        e.preventDefault();
        var etape_suivante = parseInt($(this).data('etape-suivante'));
        if (etape_suivante === 0 || etape_suivante === undefined || isNaN(etape_suivante)) { alert('Une erreur est survenue !\r\nCode erreur : YAQLYDXM'); return false; }
        chargeEtape(etape_suivante, 0);

    }); // FIN Bouton continuer la surgélation

    // Bouton Produits Séléctionnés
    $('.btnPdtsSelectionnes').off("click.btnpdtsselectionnes'").on("click.btnpdtsselectionnes", function(e) {

        e.preventDefault();
        chargeEtape(10, 0);

    }); // FIN Bouton Produits Séléctionnés

    // Bouton fin étiquetage -> vers température début SRG
    $('.btnFinEtiquetage').off("click.btnfinetiquetage'").on("click.btnfinetiquetage", function(e) {

        e.preventDefault();
        chargeEtape(4, 0);

    }); // FIN bouton Fin Etiquetage

    // Chargement du contenu de la modale Emballage à son ouverture
    $('#modalNouvelEmballage').on('show.bs.modal', function (e) {

        e.stopPropagation();

        // Si ouverture JS de la modale (via une carte emballage), on ne charge pas la première étape ici
        if (e.relatedTarget === undefined) { return true; }

        // On récupère le contenu de la modale
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_emballages.php',
            'arguments': 'mode=modalNouvelEmballageFront&vue_code=srgv',
            'return_id' : 'modalNouvelEmballageBody',
            'done': function() {
                modalNouvelEmballageFrontListener();

            }
        }); // FIN aJax


    }); // Fin chargement du contenu de la modale

} // FIN listener Ticket

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de la liste des produits de l'étape 2 (Catalogue)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listeProduitsListener() {
    "use strict";

    $('.carte-pdt').off("click.cartefamille").on("click.cartefamille", function(e) {

        e.preventDefault();

        var pdtId = parseInt($(this).data('pdt-id'));
        var lotId = parseInt($(this).data('lot-id'));
        if (isNaN(parseInt(pdtId)) || pdtId === undefined || pdtId === 0) { pdtId('Identification du produit impossible !');return false; }

        // Si produit sélectionné
        addProduitSrg(lotId, pdtId);
        return false;

    }); // FIN touche produit

} // FIN listener liste des produits

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction -> Associe un produit à la surgélation
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function addProduitSrg(id_lot, id_pdt) {
    "use strict";

    // Gestion des ereurs
    if (id_lot === undefined || isNaN(id_lot) || parseInt(id_lot) === 0 || id_pdt === undefined || isNaN(id_pdt) || parseInt(id_pdt) === 0) {
        alert('Erreur lors de l\'identification du couple produit/lot !\r\nCode erreur : BAICEGUN')
        return false;
    }

    var id_froid    = parseInt($('#inputIdFroid').val());
    var identifiant = id_pdt + '|' + id_lot + '|' + id_froid;

    chargeEtape(3, identifiant);

} // FIN fonction

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de la modale des contrôles LOMA
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function modalLomaListener(id_pdt, id_lot, id_froid) {
    "use strict";
    

    if (isNaN(parseInt(id_pdt)) || parseInt(id_pdt) === 0 || id_pdt === undefined) { alert("Identification du produit impossible !\r\bnCode erreur : BX3DCIHL"); return false; }
    if (isNaN(parseInt(id_lot)) || parseInt(id_lot) === 0 || id_lot === undefined) { alert("Identification du lot impossible !\r\bnCode erreur : MLEC49VK"); return false; }
    if (isNaN(parseInt(id_froid)) || parseInt(id_froid) === 0 || id_froid === undefined) { alert("Identification de la surgélation impossible !\r\nCode erreur : QEAE2T94 / " + id_pdt+'-'+id_froid+'-'+id_lot); return false; }


    $('.btnCtrlLoma').off("click.btnctrlloma").on("click.btnctrlloma", function(e) {

        e.preventDefault();

        var loma = parseInt($(this).data('loma'));
        if (loma < 0 || loma > 1 || isNaN(loma) || loma === undefined) { alert("Identification du choix impossible !\r\bnCode erreur : DC50Q9SS"); return false; }

        // Enregistrement loma pdt O/N
        if (loma === 1) {

            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/",
                'script_execute': 'fct_vue_srgv.php',
                'arguments': 'mode=controleLomaPdt&id_pdt='+id_pdt+'&id_lot='+id_lot+'&id_froid='+id_froid,
                'callBack': function (retour) {


                    if (parseInt(retour) < 1) {
                        alert('Une erreur est survenue !\r\nCode erreur : ZGK93SDQ-'+id_pdt+'-'+id_lot+'-'+id_froid);
                        return false;
                    }


                    var etapeSuivante = 2;

                    $('#modalControleLoma').modal('hide');
                    chargeEtape(etapeSuivante, id_lot);



                }
            }); // FIN aJax

        // Si on a choisi NON, on passe direct à la suite
        } else {
            $('#modalControleLoma').modal('hide');
            chargeEtape(2, id_lot);
        } // FIN test Loma demandé

    });

    $('#modalControleLoma').modal('hide');
    chargeEtape(2, id_lot);

} // FIN fonction

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction -> check que tous les produits sont bien étiquetés
//             pour pouvoir lancer le début de l'OP de froid
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function checkToutEtiquetePourDebutFroid() {
    "use strict";

    var nb   = $('.check-etiquetage').length;
    var nbOk = 0;

    // Débug lors d'un retour depuis la fiche édition et réminiscence d'un switch sur la méthode de calcul
    $('.check-etiquetage').each(function() {
        if ($(this).hasClass('methode-maj')) {
            nb = nb - 1;
        }
    });


    // Pas de produit
    if (nb === 0 || isNaN(nb) || nb === undefined) {
        $('.btnFinEtiquetage').hide();
        return false;
    }

    $('.check-etiquetage').each(function() {
        nbOk = $(this)[0].checked ? nbOk + 1 : nbOk;
    });

    // Si tous sont cochés
    if (nbOk < nb) {
        $('.btnFinEtiquetage').hide('fade');
        // Sinon
    } else {
        $('.btnFinEtiquetage').show('slide');
    } // Fin test tout coché

    return true;

} // FIN fonction


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de la modale nouvel emballage -> Famille
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function modalNouvelEmballageFrontListener() {

    // Sélection d'une famille
    $('.carte-fam-add-emb').off("click.cartefamaddemb'").on("click.cartefamaddemb", function(e) {

        e.preventDefault();

        var id_fam = parseInt($(this).data('id-famille'));
        var id_old_emb = parseInt($(this).data('id-old-emb'));
        if (id_fam === undefined || id_fam === 0 || isNaN(id_fam)) { alert("Identifaction de la famille impossible.\nCode erreur : CDFFSOVI"); return false; }

        // Famille identifiée, on rechage le body de la modale avec les emballages disponibles ayant du stock et non par défaut:

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_emballages.php',
            'arguments': 'mode=modalNouvelEmballageFrontEtape2&vue_code=atl&id_fam='+id_fam+'&id_old_emb='+id_old_emb,
            'return_id' : 'modalNouvelEmballageBody',
            'done': function() {
                modalNouvelEmballageFrontEtape2Listener();
            }
        }); // FIN aJax
    }); // FIN Sélection d'une famille
} // FIN Listener



// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de la modale changement de rouleau -> Etape 2
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function modalNouvelEmballageFrontEtape2Listener() {
    "use strict";
    // Sélection d'une famille
    $('.carte-new-emb-defaut').off("click.cartenewembdefaut'").on("click.cartenewembdefaut", function(e) {

        e.preventDefault();

        var id_emb = parseInt($(this).data('id-emballage'));
        if (id_emb === undefined || id_emb === 0 || isNaN(id_emb)) { alert("Identifaction de l'emballage impossible.\nCode erreur : DX6JIF4C"); return false; }

        var id_froid = parseInt($('#inputIdFroid').val());

        var etapeEnCours = 9;
        if ($('#etapeEnCours').length) {
            etapeEnCours = parseInt($('#etapeEnCours').val()) > 0 ? parseInt($('#etapeEnCours').val()) : etapeEnCours;
        }

        // Gestion de la double vue Loma
        var identifiant = etapeEnCours === 7 ? 0 : 1;


        // Emballage identifiée, on le définie comme en cours et on recharge l'étape du lot...
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_emballages.php',
            'arguments': 'mode=setNewEmballageEnCours&code_vue=srgv&id_emb='+id_emb+'&id_froid='+id_froid,
            'done': function() {

                // On ferme la modale
                $('#modalNouvelEmballage').modal('hide');

                // On met à jour la liste des emballages dans la vue principale
                chargeEtape(etapeEnCours, identifiant);

            } // FIN callback
        }); // FIN aJax

    }); // FIN Sélection d'une famille

} // FIN Listener

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction déportée -> Valide le contrôle LOMA pour un produit
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function valideLoma() {

    var id_lot = parseInt($('#controleLoma input[name=id_lot]').val());

    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/",
        'script_execute': 'fct_vue_srgv.php',
        'form_id': 'controleLoma',
        'callBack': function(retour) {



            if ($.trim(retour) !== '') {
                alert("ERREUR !\r\n\r\nUne erreur est survenue...\r\n\r\nCode erreur : " + retour);
                return false;
            }

            // On met à jour la liste en revenant à l'étape 7 (loma) sans identifiant de produit lot
            chargeEtape(7, 0);

        } // FIN callback
    }); // FIN aJax

} // FIN fonction valide Loma pour un produit


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction déportée -> Clavier virtuel
// Ne pas changer le noms des champs, ou adapater le fichier jkeyboard.js
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function clavierVirtuel() {

    $('#champ_clavier').focus(function() {
        $('#clavier_virtuel').show("slide" ,{ direction: "down"  });
        $('.masque-clavier-virtuel').hide("slide" ,{ direction: "up"  });
    });

    $('.btn').click(function() {
        $('#clavier_virtuel').hide("slide" ,{ direction: "down"  });
    });

    $('#clavier_virtuel').jkeyboard({
        input: $('#champ_clavier'),
        layout: 'francais',
        customLayouts: {
            selectable: ["francais"],
            francais: [
                ['a', 'z', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p'],
                ['q', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm'],
                ['shift', 'w', 'x', 'c', 'v', 'b', 'n', 'backspace'],
                ['numeric_switch', 'space', 'layout_switch']
            ],
        },
    });

}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction déportée -> Vérification si température de fin bien négative
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function verifTempFinNegative() {
    "use strict";

    $('.inputTempFin.bg-warning').removeClass('bg-warning');
    $('.ifa-tempfin.fa-thermometer-full').removeClass('fa-thermometer-full').addClass('fa-thermometer-half');
    $('.ifa-tempfin').removeClass('text-danger').addClass('gris-9');

    var tempFin = parseFloat($('.inputTempFin').val());

    if (tempFin > 0) {
        $('.inputTempFin').addClass('bg-warning');
        $('.ifa-tempfin').removeClass('fa-thermometer-half').addClass('fa-thermometer-full');
        $('.ifa-tempfin').removeClass('gris-9').addClass('text-danger');
    }
    return true;

} // FIN fonction

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction déportée de maj de tri des produits (étape 10)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function triTableProduits(id_froid, colonne, sens, identifiant) {
    "use strict";

    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/",
        'script_execute': 'fct_vue_srgv.php',
        'arguments': 'mode=showListeProduitsFroid&id_froid='+id_froid+'&colonne='+colonne+'&sens='+sens,
        'return_id' : 'etape10',
        'done': function () {

            listenerEtape10(identifiant);

        } // FIN done

    }); // FIN aJax

} // FIN fonction

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de la modale teméprature HS
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerModaleTempHs(id_froid) {

    // Clic bouton OK continuer quand meme
    $('#btnConfirmTempHorsNorme').off("click.btnconfirmtemphs").on("click.btnconfirmtemphs", function(e) {
        e.preventDefault();

        // On masque la zone question
        $('#tempHsQuestion').hide();

        // On affiche la zone commentaire
        $('#tempHsCommentaires').show();

        // On ouvre le clavier virtuel
        clavierVirtuel();
        $('#champ_clavier').focus();

    }); // FIN clic bouton OK continuer quand meme

    // Clic sur le bouton envoyer commentaire
    $('#envoyerCommentaireTempHs').off("click.envoyerommentairetemphs").on("click.envoyerommentairetemphs", function(e) {
        e.preventDefault();

        // Si le message est vide on refuse de valider !
        if ( $('#champ_clavier').val().length < 1 ) {
            return false;
        }

        // On intègre la valeur du id_lot pour l'envoi du formulaire (vue réception = lot)
        $('#tempHsCommentaires input[name=id_froid]').val($('#inputIdFroid').val());

        // Ajax qui enregistre le commentaire
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_srgv.php',
            'form_id': 'tempHsCommentaires',
            'done': function () {

                // Une fois le commentaire bien enregistré, on enregistre la température
                saveTemperature(id_froid);
                $('#modalConfirmTemp').modal('hide');
            }
        }); // FIN ajax

    }); // FIN clic sur le bouton envoyer commentaire

} // FIN listener modale temp HS


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction déportée -> Enregistrement de la T° de fin
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function saveTemperature(id_froid) {
    "use strict";

    var tempFin = $('.inputTempFin').val().trim();

    if (tempFin.trim() === '') { return false; }


    // Enregistrement de la température
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/",
        'script_execute':'fct_vue_srgv.php',
        'arguments': 'mode=saveTempFin&id_froid=' + id_froid + '&temp_fin='+tempFin,
        'callBack': function (retour) {

            if (parseInt(retour) < 0) { alert("Une erreur est survenue !\r\nEnregistrement impossible...\r\nCode erreur : VBTMEVRG"); return false; }


            // var etapeSuivante =  parseInt(retour) === 1 ? 11 : 7;
            var etapeSuivante =  parseInt(retour) === 1 ? 8 : 8;

            // Contrôles Loma : Etape 7 / Etape 11
            chargeEtape(etapeSuivante,0);

            return false;

        } // FIN callBack

    }); // FIN ajax

    return false;


} // FIN fonction save température


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction déportée -> Enregistre (add/upd) le produit du traitement
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function saveProduit(identifiant, id_palette, id_compo) {
    'use strict';

    var donnesIds = identifiant.split('|');
    var modeUpd   = donnesIds[2] === undefined;
    var id_lot    = parseInt($('#numLotTicket').data('id-lot'));

    var id_froid  = parseInt($('#inputIdFroid').val());
    var nbColis   = parseInt($('.inputNbColis').val());
    var poidsPdt  = parseFloat($('.inputPoidsPdt').val());

    if (id_palette === undefined) {
        id_palette = parseInt($('input[name=id_palette]').val());
    }

    // Le mode update peut etre activé si on détecte un id_lot_pdt_froid, ce qui veut dire qu'on viens du "catalogue" mais qu'on est en réalité sur un produit déjà associé au traitement
    var inputIdLotPdtFroid = parseInt($('#inputIdLotPdtFroid').val());
    if (inputIdLotPdtFroid > 0) {
        modeUpd = true;
    }

    // Identification du quantième
    var quant = $('input[name=quantieme]').val();
    if (quant === undefined) { quant = ''; }

    // Si on est en mode upd depuis la liste des produits d'un traitement en cours, on met à jour l'info du pdt et on retourne sur la liste des produits
    if (modeUpd) {

        var poids_pdt_add   = poidsPdt;
        var nb_colis_add    = nbColis;

        // Si on est en mode "ajout", on modifie les valeurs ici pour le cumul

        var oldQuantieme = $('#inputIdQuantieme').val();
        if ($('.methode-maj').prop('checked') && quant === oldQuantieme) {  // On vérifie que le quantième n'as pas changé sinon on est sur un autre pdt_froid

            var poids_pdt_old   = parseFloat($('input[name=poids_pdt_old]').val());
            var nb_colis_old    = parseInt($('input[name=nb_colis_old]').val());
            poids_pdt_add   = parseFloat($('input[name=poids_pdt]').val());
            nb_colis_add    = parseInt($('input[name=nb_colis]').val());
            nbColis             = nb_colis_old + nb_colis_add;
            poidsPdt            = poids_pdt_old + poids_pdt_add;
        }

        // Si on est en mode update et qu'on viens du catalogue, on un identifiant splité, on doit donc récupérer l'id_lot_pdt_froid pour le passer au contrôleur
        var id_lot_pdt_froid = parseInt($('#inputIdLotPdtFroid').val());
        if (id_lot_pdt_froid > 0) {
            identifiant = id_lot_pdt_froid;
        }

        if (poids_pdt_add === undefined) { poids_pdt_add = poidsPdt; }
        if (nb_colis_add === undefined) { nb_colis_add = nbColis; }


        // La compo a pu changer si on a modifié le quantième de manière assyncrhone, il faut attendre le retour DOM
        $('.btnValiderCode i.fa-check').removeClass('fa-check').addClass('fa-spin fa-spinner');
        setTimeout(function(){

            id_compo = parseInt($('#inputIdCompo').val());
            var ajout = $('.methode-maj').is(':checked') ? 1 : 0;

            // Enregistrement (Update) et retour
            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
                'script_execute': 'fct_vue_srgv.php',
                'arguments': 'mode=updPdtLotPoids&id_lot_pdt_froid='+identifiant+'&nb_colis='+nbColis+'&poids='+poidsPdt+'&quantieme='+quant+'&poids_add='+poids_pdt_add+'&nb_colis_add='+nb_colis_add+'&id_palette='+id_palette+'&id_compo='+id_compo+'&ajout='+ajout,
                'done': function () {

                    $('#inputIdFroid').val(id_froid);
                    chargeEtape(10,0);

                } // FIN Callback
            }); // FIN ajax
        }, 800);



    // Sinon, c'est qu'on est dans l'ajout d'un produit dans le traitement
    } else {

        var id_pdt = parseInt(donnesIds[0]);

        // Enregistrement et redirection
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_srgv.php',
            'arguments': 'mode=addPdtFroid&id_pdt='+id_pdt+'&id_lot='+id_lot+'&id_froid='+id_froid+'&nb_colis='+nbColis+'&poids='+poidsPdt+'&quantieme='+quant+'&id_palette='+id_palette+'&id_compo='+id_compo,
            'callBack': function (retour_id_froid) {
                retour_id_froid+= '';

                // Gestion des erreurs
                if (parseInt(retour_id_froid) < 0) {
                    alert("Une erreur est survenue !\r\nCode erreur : 8KV1JP3S-"+id_pdt+"-"+id_lot+"-"+id_froid+"-"+nbColis+"-"+poidsPdt+"-"+quant+"-"+id_palette);
                    return false;
                }

                // Intégration de l'id froid si on viens de la créer et que le retour est valide
                if (parseInt(retour_id_froid) > 0 && retour_id_froid !== undefined && !isNaN(retour_id_froid) && parseInt($('#inputIdFroid').val()) === 0) {
                    $('#inputIdFroid').val(retour_id_froid);
                    //id_froid = retour_id_froid;
                }

                // Surgélation : ici on intéroge sur le conrrôle LOMA (modale)
                var nomProduit = 'Produit';
                if ($('#nomProduitPourLoma').length) {
                    nomProduit =$('#nomProduitPourLoma').text();
                }

                $('#modalControleLomaTitle').html(nomProduit);
                // $('#modalControleLoma').modal('show');

                modalLomaListener(id_pdt, id_lot, retour_id_froid);

            } // FIN Callback
        }); // FIN ajax

    } // FIN test mode Upd

} // FIN fonction déportée add/upd produit


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction déportée -> Adopte le mode de mise à jour (total/ajout)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function adapteModeMaj() {
    "use strict";

    var ajout = $('.methode-maj').prop('checked');

    // Si on passe en mode "Ajout"
    if (ajout) {

        var poids_pdt_old = parseFloat($('input[name=poids_pdt_old]').val());
        var nb_colis_old  = parseInt($('input[name=nb_colis_old]').val());

        // On RAZ les valeurs des inputs
        $('input[name=nb_colis]').val('');
        $('input[name=poids_pdt]').val('');

        var info = "D'ores et déjà en préparation : " + nb_colis_old + " blocs, " + poids_pdt_old + " kg.";

        $('.infoMajAjout .doresetdeja').text(info);
        $('.infoMajAjout').show();
        $('input[name=nb_colis]').focus();

        // Si on passe en mode "Cumul"
    } else {

        // On masque l'info du calcul de cumul
        $('.infoMajAjout').hide();

    } // FIN test méthode de MAJ

} // FIN fonction


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction déportée -> Crée une composition de palette avec le produit
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function addProduitPalette(id_palette, supprime) {
    "use strict";

    if (supprime === undefined) {
        supprime = 1;
    }

    // Valeur du produit
    var nb_colis    = parseInt($('.inputNbColis').val());
    var poids       = parseFloat($('.inputPoidsPdt').val());
    var id_produit  = parseInt($('.btnValiderCode').data('id-pdt'));

    // Si on avait déjà une compo de renseignée, il faudra la supprimer pour ne pas affecter deux fois...
    var id_compo = parseInt($('#inputIdCompo').val());

    // Si on a un poids et un nb_colis spécifié dans les input, mais qu'on a un id_compo historisé, c'est qu'on est en rajout sur un meme idlotpdtfroid,
    // alors pour ne pas supprimer l'ancienne compo, on vide l'id_compo qui servait alors à supprimer l'ancienne compo
    if (poids > 0 && nb_colis > 0 && id_compo > 0) {
        $('#inputIdCompo').val(0);
        id_compo = 0;
    }

    // Attention, si on repasse sur une autre palette il faut reinitialiser la compo historisée si le poids est à zéro.
    if (isNaN(poids) || poids === 0 || isNaN(nb_colis) || nb_colis === 0) {
        $('#inputIdCompo').val(parseInt($('#inputIdCompo').data('id-histo')));
        id_compo = parseInt($('#inputIdCompo').val());
    }

    // Si poids ou nb_colis vides, c'est peut-être qu'on est en update... on tente de récupérer les valeurs historiques
    if (isNaN(nb_colis)) {  nb_colis = parseInt($('#nbColisPaletteHisto').val()); }
    if (isNaN(poids)) {  poids = parseFloat($('#poidsPaletteHisto').val()); }

    // On récupère l'ID client de la palette si besoin
    var id_client = parseInt($('#cartepaletteid'+id_palette).data('id-client'));

    // Gestion des erreurs
    if (id_palette === undefined || isNaN(parseInt(id_palette)) || parseInt(id_palette) === 0) {
        alert("ERREUR !\r\nIdentification de la palette impossible...\r\nCode erreur : YWAEXZIS");
        return false;
    }
    if (id_produit === undefined || id_produit === 0 || isNaN(id_produit)) {
        alert("ERREUR !\r\nIdentification du produit impossible...\r\nCode erreur : ZOZOUDZD");
        return false;
    }
    if (nb_colis === undefined || nb_colis === 0 || isNaN(nb_colis) || poids === undefined || poids === 0) {
        alert("ERREUR !\r\nRécupération des données du produit impossible...\r\nCode erreur : BAOAMXFA");
        return false;
    }

    // Dans le cadre de l'ajout d'une compo (mode update, poids > 0, id_compo_histo = 0), il faut passer l'id_lot_pdt_froid car sinon a pas moyen de faire le lien à la fonction "save"
    // et le ramasse-miettes risquer de supprimer la nouvelle compo dès qu'elle sera crée...
    var id_lot_pdt_froid = parseInt($('#inputIdLotPdtFroid').val());


    // Ajout de la composition à la palette
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/",
        'script_execute': 'fct_palettes.php',
        'arguments': 'mode=affecteCompoPalette&id_palette=' + id_palette + '&id_produit=' + id_produit + '&nb_colis='+nb_colis+'&poids='+poids+'&old_compo='+id_compo+'&id_client='+id_client+'&id_lot_pdt_froid='+id_lot_pdt_froid+'&supprime='+supprime,
        'callBack': function (retour) {

            // Indispensable pour fonctions JS sur cette variable en callBack
            retour = retour+'';

            // Gestion des erreurs
            if (retour.substring(0, 2).toLowerCase() !== 'ok') {

                alert("ERREUR !\r\nEchec de la mise en palette du produit...\r\nCode erreur : "+retour);
                return false;

                // OK, on ferme la modale et on passe à l'étape suivante
            } else {

                var ids_retour = retour.toLowerCase().replace('ok', '');

                var retoursArray = ids_retour.split('|');
                var id_palette  = parseInt(retoursArray[0]);
                var id_compo    = parseInt(retoursArray[1]);

                if (isNaN(id_compo) || id_compo === undefined || id_compo === 0) {
                    alert('ERREUR !\r\nIdentification de la composition impossible...\r\nCode erreur : GS5WYMKY');
                    return false;
                }

                $('#inputIdPalette').val(id_palette);
                $('#inputIdCompo').val(id_compo);

                $('input[name=id_palette]').val(id_palette);

                $('.carte-palette').removeClass('palette-selectionnee');
                $('#cartepaletteid'+id_palette).addClass('palette-selectionnee');
                return false;


            } // FIN gestion des erreurs
        } // FIN CallBack
    }); // FIN ajax

    return false;

} // FIN méthode ajoute le produit à la palette


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de la modale "Nouvelle Palette"
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function nouvellePaletteListener(identifiant) {
    "use strict";


    // Voir plus de clients...
    $('.btnVoirTousClient').off("click.btnVoirTousClient").on("click.btnVoirTousClient", function(e) {

        e.preventDefault();


        var id_produit    = parseInt($('.btnValiderCode').data('id-pdt'));

        // On recharge le contenu de la modale avec plus de clients
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_palettes.php',
            'arguments': 'mode=modaleNouvellePalette&tous&id_produit='+id_produit,
            'return_id' : 'modalPaletteFrontBody',
            'done': function () {

                nouvellePaletteListener(identifiant);

            } // FIN Callback
        }); // FIN ajax

    }); // FIn bouton voir plus de clients


    // Sélection du client
    $('.btnClientNewPalette').off("click.btnClientNewPalette").on("click.btnClientNewPalette", function(e) {

        e.preventDefault();

        var id_client     = parseInt($(this).data('id-client'));
        var nb_colis      = parseInt($('.inputNbColis').val());
        var poids         = parseFloat($('.inputPoidsPdt').val());
        var id_produit    = parseInt($('.btnValiderCode').data('id-pdt'));
        var num_palette   = $('#numeroNextPalette').length ?  parseInt($('#numeroNextPalette').text()) : parseInt($(this).data('palette-suiv'));
        var old_compo    = parseInt($('#inputIdCompo').val());

        // Gestion des erreurs

        if (isNaN(num_palette) || num_palette === 0) {
            alert("ERREUR !\r\nIdentification du numéro de palette impossible.\r\nCode erreur : HS0MVFN9");
            return false;
        } // FIN gestion des erreurs

        if (isNaN(id_client) || id_client === 0) {
            alert("ERREUR !\r\nIdentification du client impossible.\r\nCode erreur : KKMRQ1D5");
            return false;
        } // FIN gestion des erreurs

        if (id_produit === undefined || id_produit === 0 || isNaN(id_produit)) {
            alert("ERREUR !\r\nIdentification du produit impossible...\r\nCode erreur : JOSC1P0Y");
            return false;
        }
        if (nb_colis === undefined || nb_colis === 0 || isNaN(nb_colis) || poids === undefined || poids === 0) {

            $('#modalPaletteFront').modal('hide');
            $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>Renseignez le nombre de colis<br>avant de créer une nouvelle palette...</p></div>");
            $('#modalInfo').modal('show');

            return false;
        }

        // En mode update, on ne viens pas supprimer l'ancien si on rajoute, et on empeche la création de compo à la save...
        var modeUpd = parseInt($('#inputIdLotPdtFroid').val()) > 0;
        if (modeUpd && poids > 0) {
            $('#skipCreateCompoSave').val(1);
            old_compo = 0;
        }

        // Création de la nouvelle palette
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_palettes.php',
            'arguments': 'mode=creationNouvellePalette&id_client='+id_client+'&id_produit='+id_produit+'&nb_colis='+nb_colis+'&poids='+poids+'&num_palette='+num_palette+'&old_compo='+old_compo,
            'callBack': function (retour) {

                retour = retour+'';

                // Gestion des erreurs
                if (retour.substring(0, 2).toLowerCase() !== 'ok') {

                    alert("ERREUR !\r\nEchec de la mise en palette du produit...\r\nCode erreur : "+retour);
                    return false;

                    // OK, on ferme la modale et on passe à l'étape suivante
                } else {

                    var ids_retour   = retour.toLowerCase().replace('ok', '');
                    var retoursArray = ids_retour.split('|');
                    var id_palette   = parseInt(retoursArray[0]);
                    var id_compo     = parseInt(retoursArray[1]);
                    var htmlCarte    = retoursArray[2];

                    $('#inputIdPalette').val(id_palette);
                    $('#inputIdCompo').val(id_compo);

                    // On affiche la carte de la nouvelle palette créée
                    $('#listePalettesPdt').append(htmlCarte);

                    $('.carte-palette').removeClass('palette-selectionnee');
                    $('#cartepaletteid'+id_palette).addClass('palette-selectionnee');

                    $('#modalPaletteFront').modal('hide');

                    // On masque le bouton ajouter nouvelle palette
                    $('.btnPalettesCompletes').removeClass('mt-2');
                    $('.btnPalettesCompletes').parent().find('br').remove();
                    $('.btnNouvellePalette').hide('fast');

                    // On masque le bloc "aucune palette en cours"
                    $('.aucunePaletteEnCours').hide('fast');

                    // Si plus aucun bouton, on masque le bloc des boutons
                    if ($('.btnPalettesCompletes').length === 0 || !$('.btnPalettesCompletes').is(':visible')) {
                        $('.btnNouvellePalette').parents('.col-2').hide('fast');
                    }

                    nouvellesPalettesCreeListener();

                } // FIN gestion des erreurs

            } // FIN Callback
        }); // FIN ajax

    }); // FIn sélection client

} // FIN listener Modale Nouvelle palette


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'affichage des palettes complètes (Etape 3)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function palettesCompletesListener() {

    $('.carte-palette-complete').off("click.cartepalettecomplete'").on("click.cartepalettecomplete", function(e) {

        e.preventDefault();

        if ($(this).hasClass('palette-selectionnee')) { return false; }

        // On retire le flag permette de ne pas créer de palette après la création d'une nouvelle
        $('#skipCreateCompoSave').val(0);

        // Valeur du produit
        var nb_colis     = parseInt($('.inputNbColis').val());
        var poids        = parseFloat($('.inputPoidsPdt').val());

        var modeUpd = parseInt($('#inputIdLotPdtFroid').val()) > 0;

        // Si on est en mode Update et qu'on a pas rajouté de poids, on stope, sinon ça effectue des changement de compos arbitraires sur l'ensemble qui finissent par pourrir la base !
        if (modeUpd && isNaN(poids)) {
            $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>Changement de palette impossible sans ajout...</p></div>");
            $('#modalInfo').modal('show');
            return false;
        }

        // Capacité restante de la palette
        var id_palette            = parseInt($(this).data('id-palette'));
        var numero_palette        = parseInt($(this).data('numero-palette'));
        var palette_restant_poids = parseInt($(this).data('id-poids-restant'));
        var palette_restant_colis = parseInt($(this).data('id-colis-restant'));

        // Gestion des erreurs
        if (isNaN(id_palette) || id_palette === 0 || isNaN(numero_palette) || numero_palette === 0) {
            alert("ERREUR !\r\nEchec lors de la récupération des données...\r\nCode erreur : GVKIHBZA");
            return false;
        } // FIN échap erreurs

        var id_compo            = parseInt($('#inputIdCompo').val());
        var palette_historique  = parseInt($('#inputIdPalette').val());

        // Si on a pas saisi encore le nb de colis / poids...
        if ((isNaN(nb_colis) || isNaN(poids) || nb_colis === 0 || poids === 0) && id_compo === 0 && palette_historique === 0) {

            // Si on a déjà une palette pour ce produit (mode upd) ou pas...
            var textInfo = "Renseignez le nombre de colis<br>avant de sélectionner une palette...";

            $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>"+textInfo+"</p></div>");
            $('#modalInfo').modal('show');

            return false;
        } // FIN poids/colis non saisie à la sélection d'une palette


        // Ici pas de test, on sais qu'on est déjà hors capacité...
        addProduitPalette(id_palette);

        // On rajoute le flag permette de ne pas créer de palette après la création d'une nouvelle
        $('#skipCreateCompoSave').val(1);

        $('input[name=id_palette]').val(id_palette);
        $('.carte-palette').removeClass('palette-selectionnee');
        $(this).addClass('palette-selectionnee');

    }); // FIN sélection d'une palette

} // FIN listener palettes complètes



// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'affichage d'une nouvelle palette fraichement créée
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function nouvellesPalettesCreeListener() {
    "use strict";

    // Clic sur une carte d'une palette nouvellement créee
    $('.carte-palette-nouvelle').off("click.cartepalettenouvelle'").on("click.cartepalettenouvelle", function(e) {

        e.preventDefault();

        // SI c'est celle qui est déjà sélectionnée, on ne vas pas plus loin
        if ($(this).hasClass('palette-selectionnee')) { return false; }

        // On retire le flag permette de ne pas créer de palette après la création d'une nouvelle
        $('#skipCreateCompoSave').val(0);

        // Valeur du produit et identification du mode update
        var nb_colis    = parseInt($('.inputNbColis').val());
        var poids       = parseFloat($('.inputPoidsPdt').val());
        var modeUpd     = parseInt($('#inputIdLotPdtFroid').val()) > 0;

        // Si on est en mode Update et qu'on a pas rajouté de poids, on stope, sinon ça effectue des changement de compos arbitraires sur l'ensemble qui finissent par pourrir la base !
        if (modeUpd && isNaN(poids)) {
            $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>Changement de palette impossible sans ajout...</p></div>");
            $('#modalInfo').modal('show');
            return false;
        }

        // Capacité restante de la palette
        var id_palette            = parseInt($(this).data('id-palette'));
        var numero_palette        = parseInt($(this).data('numero-palette'));
        var palette_restant_poids = parseInt($(this).data('id-poids-restant'));
        var palette_restant_colis = parseInt($(this).data('id-colis-restant'));

        // Gestion des erreurs
        if (isNaN(id_palette) || id_palette === 0 || isNaN(numero_palette) || numero_palette === 0) {
            alert("ERREUR !\r\nEchec lors de la récupération des données...\r\nCode erreur : RIGNLFWM");
            return false;
        } // FIN échap erreurs

        var id_compo              = parseInt($('#inputIdCompo').val());
        var palette_historique    = parseInt($('#inputIdPalette').val());

        // Si on a pas saisi encore le nb de colis / poids...
        if ((isNaN(nb_colis) || isNaN(poids) || nb_colis === 0 || poids === 0) && id_compo === 0 && palette_historique === 0) {

            // Si on a déjà une palette pour ce produit (mode upd) ou pas...
            var textInfo = "Renseignez le nombre de colis<br>avant de sélectionner une palette...";

            $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>"+textInfo+"</p></div>");
            $('#modalInfo').modal('show');

            return false;
        } // FIN poids/colis non saisie à la sélection d'une palette

        // Ici pas de test, on sais qu'on a la capacité puisque c'est une nouvelle palette...

        addProduitPalette(id_palette);

        // On rajoute le flag permette de ne pas créer de palette après la création d'une nouvelle
        $('#skipCreateCompoSave').val(1);

        $('input[name=id_palette]').val(id_palette);
        $('.carte-palette').removeClass('palette-selectionnee');
        $(this).addClass('palette-selectionnee');

    }); // FIN sélection d'une palette

} // FIN listner apres création nouvelle palette


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction déportée -> Nettoie les composition abandonnées
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function cleanCompoPalettesVides() {
    "use strict";

    var id_froid = parseInt($('#inputIdFroid').val());

    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_palettes.php',
        'arguments': 'mode=cleanCompoPalettesVides&id_froid='+id_froid

    }); // FIN ajax

} // FIN fonction



// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction déportée -> Création nouvelle palette (ouverture modale)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function nouvellePalette(identifiant) {
    "use strict";

    if (identifiant === undefined) { identifiant = 0; }

    var nb_colis    = parseInt($('.inputNbColis').val());
    var poids       = parseFloat($('.inputPoidsPdt').val());

    // Si on a précisé aucune quantité, on ne peut pas créer une nouvelle palette
    if (nb_colis === undefined || nb_colis === 0 || isNaN(nb_colis) || poids === undefined || poids === 0) {

        $('#modalPaletteFront').modal('hide');
        $('#modalInfoBody').html("<div class='alert alert-danger'><i class='fa fa-exclamation-circle fa-lg mb-2'></i><p>Renseignez le nombre de colis<br>avant de créer une nouvelle palette... </p></div>");
        $('#modalInfo').modal('show');

        return false;
    }

    var id_produit  = parseInt($('#inputIdProduit').val());

    // On recharge le contenu de la modale pour création nouvelle palette...
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_palettes.php',
        'arguments': 'mode=modaleNouvellePalette&id_produit='+id_produit,
        'return_id' : 'modalPaletteFrontBody',
        'done': function () {

            $('#modalPaletteFront').modal('show');
            nouvellePaletteListener(identifiant);

        } // FIN Callback
    }); // FIN ajax


} // FIN fonction