/**
 ------------------------------------------------------------------------
 JS - Corrections

 Copyright (C) 2018 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2018 Intersed
 @version   1.0
 @since     2018

 ------------------------------------------------------------------------
 */
$(document).ready(function() {
    "use strict";

    chargeResultats();

    $('#btnGoRecherche').off("click.btngorecherche").on("click.btngorecherche", function(e) {
        e.preventDefault();
        chargeResultats();
    });

    $('#btnRazRecherche').off("click.btnrazrecherche").on("click.btnrazrecherche", function(e) {
        e.preventDefault();

        $('#filtre_lot').val('');
        $('#filtre_froid').val('');
        $('#filtre_produit').val('');
        $('#filtre_palette').val('');
        $('#filtre_date').val($('#filtre_date').data('aujourdhui'));

        chargeResultats();
    });


    // Nettoyage du contenu de la modale Correction à sa fermeture
    $('#modalCorrection').on('hidden.bs.modal', function (e) {
        $('#modalCorrectionBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#modalCorrection .btn').prop('disabled', false);
        $('#modalCorrection .btn.disabled').removeClass('disabled');
        $('.btnSaveCorrections').find('i.fa').removeClass('fa-spin fa-spinner').addClass('fa-save');

//Verification numlot

        
    }); // FIN fermeture modale Détails   

}); // FIN ready






/** -------------------------------------------------------------
 * Charge les résultats
 ------------------------------------------------------------- */
function chargeResultats() {
    "use strict";

    $('#resultatsHist').html('<p class="text-center padding-50"><i class="fa fa-spin fa-spinner fa-3x gris-9"></i></p>');

    // Ajax qui charge le contenu du ticket de lot
    $.fn.ajax({
        'script_execute': 'fct_corrections.php',
        'form_id': 'filtresHist',
        'return_id': 'resultatsHist',
        'done': function() {
            resultatsListener();
        }
    }); // FIN ajax


} // FIN fonction

/** -------------------------------------------------------------
 * Listener des résultats
 ------------------------------------------------------------- */
function resultatsListener() {

    // Pagination Ajax
    $(document).on('click','#resultatsHist .pagination li a',function(){
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#listeLots').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({
            script_execute:'fct_corrections.php'+$(this).attr('data-url'),
            return_id:'resultatsHist',
            done: function() {
                resultatsListener();
            }
        });
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax

    // Chargement du contenu de la modale Correction
    $('.btnCorrection').off("click.btncorrection").on("click.btncorrection", function(e) {

        e.preventDefault();

        var id_lot_pdt_froid = parseInt($(this).data('id-lot-pdt-froid'));
        if (id_lot_pdt_froid === 0 || id_lot_pdt_froid === undefined) { alert('ERREUR\r\nIdentification impossible !\r\nCode erreur : RT9TP54P'); return false; }

        $('#modalCorrection').modal('show');

        // Ajax qui charge le contenu de la modale
        $.fn.ajax({
            'script_execute': 'fct_corrections.php',
            'arguments': 'mode=contenuModaleCorrection&id_lot_pdt_froid='+id_lot_pdt_froid,
            'return_id': 'modalCorrectionBody',
            'done': function() {
                modaleCorrectionsListener();
            }
        }); // FIN ajax

    }); // FIN chargement du contenu de la modale Correction


} // FIN listener

/** -------------------------------------------------------------
 * Listener de la modale de correction
 ------------------------------------------------------------- */
function modaleCorrectionsListener() {
    "use strict";

    $('.selectpicker').selectpicker('render');

    $( ".datepicker" ).datepicker({

        beforeShow:function(input) {
            $(input).css({
                "position": "relative",
                "z-index": 999999
            });
        },

        dateFormat:		"dd/mm/yy",
        dayNames:			[ "Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi" ],
        dayNamesMin:		[ "Di", "Lu", "Ma", "Me", "Je", "Ve", "Sa" ],
        dayNamesShort:		[ "Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam" ],
        firstDay:			1,
        monthNames:		[ "Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "octobre", "Novembre", "Décembre" ],
        monthNamesShort:	[ "Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc" ],
        nextText:			"Mois suivant",
        prevText:			"Mois précédent"

    }); // FIN DatePicker



    // Supprimer le produit du traitement
    $('.btnSupprProduitFroid').off("click.btnsupprproduitfroid").on("click.btnsupprproduitfroid", function(e) {
        e.preventDefault();

        // On vérifie qu'on a pas changé le lot et le produit en amont
        var id_lot_origine = parseInt($(this).data('id-lot'));
        var id_pdt_origine = parseInt($(this).data('id-pdt'));
        var id_lot_select = parseInt($('select[name=id_lot').val());
        var id_pdt_select = parseInt($('select[name=id_pdt').val());

        if (id_lot_origine !== id_lot_select || id_lot_origine === 0 || id_pdt_origine !== id_pdt_select || id_pdt_origine === 0) {
            alert('ATTENTION\r\nLe lot ou le produit a été modifié. Suppression impossible !');
            return false;
        }

        if (!confirm("CONFIRMATION\r\nSupprimer ce produit du traitement ?")) {
            return false;
        }

        $('#modalCorrection input, #modalCorrection select, #modalCorrection .btn').addClass('disabled');
        $('#modalCorrection input, #modalCorrection select, #modalCorrection .btn').attr('disabled', 'disabled');
        $(this).find('i.fa').removeClass('fa-trash').addClass('fa-spin fa-spinner');

        var id_lot_pdt_froid = parseInt($('#correctionIdLotPdtFroid').val());
        if (id_lot_pdt_froid === undefined || id_lot_pdt_froid === 0 || isNaN(id_lot_pdt_froid)) {
            alert("ERREUR !\r\nIdentification impossible.\r\nCode erreur : EFAUF5TX"); return false;
        }

        // Ajax : supprime le produitfroid
        $.fn.ajax({
            'script_execute': 'fct_corrections.php',
            'arguments': 'mode=supprimeProduitFroid&id_lot_pdt_froid='+id_lot_pdt_froid,
            'done': function() {
               $('#modalCorrection').modal('hide');
                chargeResultats();
            }
        }); // FIN ajax

    }); // FIN supprimer le produit du traitement

    // Ajouter le produit au traitement
    $('.btnAddProduitFroid').off("click.btnaddproduitfroid").on("click.btnaddproduitfroid", function(e) {
        e.preventDefault();

        // On vérifie qu'on a bien changé le produit en amont
        var id_pdt_origine = parseInt($(this).data('id-pdt'));
        var id_pdt_select = parseInt($('select[name=id_pdt]').val());

        // On peut avoir le meme produit et le rajouter à autre lot, donc on récupère aussi l'ancien et le nouveau lot
        var id_lot_origine = parseInt($('select[name=id_lot]').data('id-lot'));
        var id_lot_select = parseInt($('input[name=id_lot]').val());


        if (id_pdt_origine === id_pdt_select && id_lot_origine === id_lot_select) {
            alert("ATTENTION\r\nLe produit n'a pas été changé !\r\nSélectionnez un nouveau produit a ajouter au traitement.");
            return false;
        }

        if (!confirm("CONFIRMATION\r\nAjouter ce produit au traitement ?\r\n(Si il en fait déjà parti, l'ajout ne sera pas pris en compte.)")) {
            return false;
        }

        $('#modalCorrection input, #modalCorrection select, #modalCorrection .btn').addClass('disabled');
        $('#modalCorrection input, #modalCorrection select, #modalCorrection .btn').attr('disabled', 'disabled');
        $(this).find('i.fa').removeClass('fa-plus-square').addClass('fa-spin fa-spinner');

        var id_lot      = parseInt($('input[name=id_lot]').val());
        var id_froid    = parseInt($('#correctionIdFroid').val());
        var quantieme   = parseInt($('input[name=quantieme]').val());
        var poids       = parseInt($('input[name=poids]').val());
        var nb_colis    = parseInt($('input[name=nb_colis]').val());

        // Ajax : supprime le produitfroid
        $.fn.ajax({
            'script_execute': 'fct_corrections.php',
            'arguments': 'mode=addProduitFroid&id_pdt='+id_pdt_select+'&id_lot='+id_lot+'&id_froid='+id_froid+'&quantieme='+quantieme+'&poids='+poids+'&nb_colis='+nb_colis,
            'done': function() {
                $('#modalCorrection').modal('hide');
                chargeResultats();
            }
        }); // FIN ajax

    }); // FIN ajouter le produit du traitement

    // Enregistrer les modifications
    $('.btnSaveCorrections').off("click.btnsavecorrections").on("click.btnsavecorrections", function(e) {
        e.preventDefault();

        verifNumeroLot()

        // On vérifie que la température de fin soit correcte
        var temp_fin        = parseFloat($('input[name=temp_fin]').val());
        var temp_fin_min    = parseInt($('input[name=temp_fin]').data('temp-min'));
        var temp_fin_max    = parseInt($('input[name=temp_fin]').data('temp-max'));
        var com_caracs      = $('textarea[name=froid_commentaire]').val().length

        // Si on a modifié la palette, le poids ou le nb de colis et qu'on a plus qu'une seule compo associée, on préviens...
        var nb_compos = parseInt($('#nbCompos').val());
        if (nb_compos > 1 && (
            parseFloat($('input[name=poids]').data('old')) !== parseFloat($('input[name=poids]').val()) ||
            parseInt($('input[name=nb_colis]').data('old')) !== parseInt($('input[name=nb_colis]').val()) ||
            parseInt($('input[name=palette]').data('old')) !== parseInt($('input[name=palette]').val()))) {
                alert("ATTENTION !\r\nCe produit est dispatché en plusieurs compositions de palettes.\r\nAfin d'éviter toute erreur de calcul, merci d'utiliser la vie Stock Produits\r\npour modifier le poids, la palette et/ou le nombre de colis...");
                return false;
        }



        if ((temp_fin < temp_fin_min || temp_fin > temp_fin_max)  && com_caracs < 2 ){

            alert('ATTENTION !\r\nVous avez saisi une température de fin à ' + temp_fin + '°C !\r\nElle doit être comprise entre ' + temp_fin_min + '°C et ' + temp_fin_max + '°C pour être valide.\r\nVeuillez saisir un commentaire pour enregistrer...');
            return false;
        } // FIN test température invalide

        $('#modalCorrection .btn').addClass('disabled');
        $('#modalCorrection .btn').attr('disabled', 'disabled');
        $(this).find('i.fa').removeClass('fa-save').addClass('fa-spin fa-spinner');

        // Ajax qui charge le contenu du ticket de lot
        $.fn.ajax({
            'script_execute': 'fct_corrections.php',
            'form_id': 'modalCorrectionBody',
            'done': function() {
                $('#modalCorrection').modal('hide');
                chargeResultats();
            }
        }); // FIN ajax

    }); // FIN enregistrer les modifications

    //Verification de numlot
    $('.numlot').off("input.numlot").on("input.numlot", function(e) {
        e.preventDefault();
        verifNumeroLot();
    });    

    //Verification d'une numero de lot



} // FIN listener

function verifNumeroLot(){
    "use strict";
    // On vérifie le lot - Ajax
    var numlot = $('#numlot').val();

    if (numlot !== '') {

        $.fn.ajax({
            script_execute: 'fct_corrections.php',
            arguments: 'mode=checkNumLotExiste&numlot=' + numlot,
            callBack: function (retour) {               
                retour += '';                // Si le lot n'existe pas, on demande confirmation pour le créer
                if (parseInt(retour) === 0) {
                    if (!confirm("Le lot " + numlot + " n'existe pas !")) {
                        e.preventDefault();
                        return false;
                    }
                }
               $('#id_lot').val(retour);           

            } // FIN callBack           

        }); // FIN ajax

    }
     // FIN test numéro de lot
} // FIN fonction