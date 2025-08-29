/**
 ------------------------------------------------------------------------
 JS - Lots de Négoce

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
    'use strict';
   


    // Affiche la liste des lots de Negoce (aJax)
    chargeListeLotsNegoce();

    // Chargement du contenu de la modale Produits d'un lot de négoce à son ouverture
    $('#modalLotNegProduits').on('show.bs.modal', function (e) {

        var lot_id = e.relatedTarget.attributes['data-lot-id'] === undefined ? 0 : parseInt(e.relatedTarget.attributes['data-lot-id'].value);

        // On récupère le contenu de la modale
        $.fn.ajax({
            'script_execute': 'fct_lots_negoce.php',
            'arguments': 'mode=modalLotNegProduits&id=' + lot_id,
            'callBack': function (retour) {

                if (parseInt(retour) === -1 ) {
                    alert('Une erreur est survenue !\r\nCode erreur : JN2I81LB');
                    return false;
                }

                // On intègre les différents contenus
                var retours = retour.toString().split('^');
                var titre = retours[0];
                var body = retours[1];
                var footer = retours[2];

                $('#modalLotNegProduitsTitre').html(titre);
                $('#modalLotNegProduitsBody').html(body);
                $('#modalLotNegProduitsFooter').html(footer);

                modalLotNegProduitsListener(lot_id);

            } // FIN Callback
        }); // FIN aJax

    }); // FIN chargement modale

    // Chargement du contenu de la modale Lot à son ouverture
    $('#modalLotEdit').on('show.bs.modal', function (e) {

        // On récupère l'ID du lot
        var lot_id = e.relatedTarget.attributes['data-lot-id'] === undefined ? 0 : parseInt(e.relatedTarget.attributes['data-lot-id'].value);

        // On récupère le contenu de la modale
        $.fn.ajax({
            'script_execute': 'fct_lots_negoce.php',
            'arguments': 'mode=modalLotEdit&id=' + lot_id,
            'callBack': function (retour) {

                if (parseInt(retour) === -1 ) {
                    alert('Une erreur est survenue !\r\nCode erreur : 6TEK2HW7');
                    return false;
                }

                // On intègre les différents contenus
                var retours = retour.toString().split('^');
                var titre = retours[0];
                var body = retours[1];
                var footer = retours[2];

                $('#modalLotEditTitre').html(titre);
                $('#modalLotEditBody').html(body);
                $('#modalLotEditFooter').html(footer);

                modalEditLotListener();

            } // FIN Callback
        }); // FIN aJax
    }); // Fin chargement du contenu de la modale


    // Chargement du contenu de la modale documents du lot
    $('#modalLotDocs').on('show.bs.modal', function (e) {

        // On récupère l'ID du lot
        var lot_id = e.relatedTarget.attributes['data-lot-id'] === undefined ? 0 : parseInt(e.relatedTarget.attributes['data-lot-id'].value);

        // On récupère le contenu de la modale
        $.fn.ajax({
            'script_execute': 'fct_documents.php',
            'arguments': 'mode=modalLotDocs&id=' + lot_id+'&type_lot=1',
            'callBack': function (retour) {

                if (parseInt(retour) === -1 ) {
                    alert('Une erreur est survenue !\r\nCode erreur : 9VFZKEB1');
                    return false;
                }

                // On intègre les différents contenus
                var retours = retour.toString().split('^');
                var titre = retours[0];
                var body = retours[1];

                $('#modalLotDocsTitre').html(titre);
                $('#modalLotDocsBody').html(body);

                modaleDocumentsListener();


            } // FIN Callback
        }); // FIN aJax
    }); // Fin chargement du contenu de la modale

}); // FIN ready


/** ******************************************
 * Affiche la liste des lots de négoce
 ****************************************** */
function chargeListeLotsNegoce() {
    "use strict";   
    var statut =  $('#listeLotsNegoce').data('statut');

    var page = parseInt($('.pagination .page-item.active a').text());
    if (isNaN(page)) { page = 1;  }


    $.fn.ajax({
        'script_execute': 'fct_lots_negoce.php',
        'arguments': 'mode=showListeLotsNegoce&statut='+statut+'&page='+page,
        'return_id': 'listeLotsNegoce', 
        'done': function() {
            switchListener();
        },
        'callBack': function (retour) {
            listeLotsListener();
        } // FIN Callback
    }); // FIN ajax


    return true;

} // FIN fonction


/** ******************************************
 * Listener de la liste des lots de négoce
 ****************************************** */
function listeLotsListener() {

    // Clic sur un icone d'incident (supprimer)
    $(document).on('click','#listeLotsNegoce .ico-incident',function(){

        var verbose = $(this).data('verbose');
        var id_incident = parseInt($(this).data('id-incident'));
        var date_incident =  $(this).data('date');
        var user_incident =  $(this).data('user');

        var htmlIncident = '<div class="alert alert-danger text-20 text-center">'+verbose+'</div><p class="margin-0 text-center gris-5 text-14">'+date_incident+'<br/>par '+user_incident+'</br>';


        $('#id_incident_suppr').val(id_incident);
        $('#modalLotIncidentBody').html(htmlIncident);

        $('#modalLotIncident').modal('show');

    }); // Fin clic icone incident


    // Bouton supprimer incident
    $('.btn-suppr-incident').off("click.btnsupprincident").on("click.btnsupprincident", function(e) {
        e.preventDefault();


        var id_incident = parseInt($('#id_incident_suppr').val());

        if (isNaN(id_incident) || id_incident === undefined || id_incident === 0) {
            alert("ERREUR !\r\nIdentification de l'incident impossible\r\nCode erreur : XD3VXTT9"); return false;
        }

        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments': 'mode=supprIncident&id_incident='+id_incident,
            'callBack': function() {
                chargeListeLotsNegoce()
                $('#modalLotIncident').modal('hide');
            }
        }); // FIN ajax

        return false;

    }); // Fin clic supprimer incident


    // Pagination Ajax
    $(document).on('click','#listeLotsNegoce .pagination li a',function(){
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#listeLotsNegoce').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_lots_negoce.php'+$(this).attr('data-url'),return_id:'listeLotsNegoce',done:function() { switchListener();} });
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax




} // FIN listener liste des lots




/** ******************************************
 * Listener des switchs de la liste des lots
 * (isolé car conflit avec le listener général)
 ****************************************** */
function switchListener() {

    // Toogle pour visibilités
    $('.togglemaster').bootstrapToggle();

    // Changement visibilité
    $('.switch-visibilite-lot').change(function() {

        // Récupération de l'ID du lot et gestion des erreurs
        var id_lot = parseInt($(this).parents('tr').data('id-lot'));
        if (isNaN(id_lot) || id_lot === undefined || id_lot === 0) {
            alert('ERREUR\r\nIdentification du lot impossible !\r\nCode erreur : XP3UYS7H');
            return false;
        }

        // On récupère l'état de visibilité à appliquer
        var visible =  $(this).prop('checked') ? 1 : 0;

        // Maj en ajax de la visibilité du lot
        $.fn.ajax({
            'script_execute': 'fct_lots_negoce.php',
            'arguments':'mode=changeVisibilite&id_lot='+ id_lot + '&visible='+visible,
            'callBack' : function (retour) {
                if (parseInt(retour) !== 1) {
                    alert('ERREUR\r\nEchec lors du changement de visibilité !\r\nCode erreur : EH4XFMLZ');
                }
            } // FIN callBack
        }); // FIN ajax

    }); // FIN Changement visibilité



} // FIN listener



/** ******************************************
 * Listener de la modalde édition du lot
 ****************************************** */
function modalEditLotListener() {

    $('.selectpicker').selectpicker('render');
    $('.icheck').iCheck({
        radioClass: 'iradio_square-blue'});
    $( ".datepicker" ).datepicker({

        dateFormat:		"dd/mm/yy",
        dayNames:			[ "Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi" ],
        dayNamesMin:		[ "Di", "Lu", "Ma", "Me", "Je", "Ve", "Sa" ],
        dayNamesShort:		[ "Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam" ],
        firstDay:			1,
        monthNames:		[ "Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "octobre", "Novembre", "Décembre" ],
        monthNamesShort:	[ "Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc" ],
        nextText:			"Mois suivant",
        prevText:			"Mois précédent",
        beforeShow: function() {
            setTimeout(function(){
                $('.ui-datepicker').css('z-index', 10000);
            }, 0);
        }

    }); // FIN DatePicker



    // --------------------------------
    // Bouton Enregistrer
    // --------------------------------
    $('.btnSaveLot').off("click.btnSaveLot").on("click.btnSaveLot", function(e) {

        e.preventDefault();

        // On vérifie que les dates sont valides
        var regexDate = new RegExp('^(([1-2][0-9]|0[1-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/[2][0-9][0-9]{2})$');


        if ($('#updDateReception').val().length > 0 && !regexDate.test($('#updDateReception').val())) {
            $('#updDateReception').addClass('is-invalid');
            setTimeout(function(){
                $('#updDateReception').removeClass('is-invalid');
            }, 3000);
            return false;
        }

        // On enregistre...
        goUpdLot();

    }); // FIN bouton enregistrer

    // --------------------------------
    // Bouton Supprimer lot
    // --------------------------------
    $('.btnDelLot').off("click.btnDelLot").on("click.btnDelLot", function(e) {

        e.preventDefault();

        var texteConfirm = "ATTENTION\r\nVous allez supprimer ce lot de négoce.\r\nContinuer ?";
        if (!confirm(texteConfirm)) { return false; }

        var id_lot = parseInt($('#updLotIdLot').val());

        $.fn.ajax({
            'script_execute': 'fct_lots_negoce.php',
            'arguments': 'mode=supprimeLot&id_lot='+id_lot,
            'callBack': function (retour) {

                // SI erreur
                if (parseInt(retour) === -1) {

                    alert('Une erreur est survenur, suppression du lot impossible !\r\nCode erreur : HG13J1EH');
                    return false;

                } // FIN erreur

                // on recharge la liste à jour et on ferme la modale
                chargeListeLotsNegoce();
                $('#modalLotEdit').modal('hide');

            } // FIN Callback
        }); // FIN ajax

    }); // FIN bouton Supprimer lot

    // --------------------------------
    // Bouton Sortie du lot
    // --------------------------------
    $('.btnSortieLot').off("click.btnSortieLot").on("click.btnSortieLot", function(e) {

        e.preventDefault();

        var texteConfirm = "CONFIRMATION\r\nDéclarer ce lot de négoce comme expédié ?";
        if (!confirm(texteConfirm)) { return false; }

        var id_lot = parseInt($('#updLotIdLot').val());

        $.fn.ajax({
            'script_execute': 'fct_lots_negoce.php',
            'arguments': 'mode=sortieLot&id_lot='+id_lot,
            'callBack': function (retour) {

                // SI erreur
                if (parseInt(retour) === -1) {

                    alert('Une erreur est survenur, sortie du lot impossible !\r\nCode erreur : WWHY2J85');
                    return false;

                } // FIN erreur

                // on recharge la liste à jour et on ferme la modale
                chargeListeLotsNegoce();
                $('#modalLotEdit').modal('hide');

            } // FIN Callback
        }); // FIN ajax

    }); // FIN bouton Sortie du lot


} // FIN listener modale édition d'un lot

/** ******************************************
 * Valide l'enregistrement
 ****************************************** */
function goUpdLot () {

    $.fn.ajax({
        'script_execute': 'fct_lots_negoce.php',
        'form_id': 'formUpdLot',
        'callBack': function (retour) {


            // SI erreur sur l'instanciation du lot
            if (parseInt(retour) === -2) {

                alert('Une erreur est survenur, identification du lot impossible !\nCode erreur : GJWN54SY');
                return false;

            } // FIN erreur sur l'instanciation du lot

            // SI erreur sur l'enregistrement du lot
            if (parseInt(retour) === -3) {

                alert('Une erreur est survenue, enregistrement du lot impossible !\nCode erreur : ME76WPYY');
                return false;

            } // FIN erreur sur l'instanciation du lot

            // SI aucune modif
            if (parseInt(retour) === -4) {
                console.log('Aucune modif');
            } // FIN retour aucune modif

            // on recharge la liste à jour et on ferme la modale
            chargeListeLotsNegoce();
            $('#modalLotEdit').modal('hide');

        } // FIN callback
    }); // FIN ajax

    return false;

} // FIN fonction

// Listener de la modale documents du lot
function modaleDocumentsListener() {

    $('.selectpicker').selectpicker('render');

    listeDocsLotListener();

    $("input:file").change(function (){
        var fichierArray = $(this).val().split('\\');
        var nomFichier = fichierArray[fichierArray.length-1];
        $(".nom-fichier-a-uploader").text(nomFichier);
    });

    // Upload aJax
    $('.btnUpload').off("click.btnUpload").on("click.btnUpload", function(e) {

        e.preventDefault();

        // Récupération des variables du formulaire
        var lot_id  = parseInt($(this).data('lot-id'));
        var type_id = parseInt($('.type-document-id option:selected').val());
        var nom_doc = $('.nom-document').val();

        // Vérification : lot non défini (ne devrait pas arriver)
        if (isNaN(lot_id) || lot_id < 0) {
            alert('ERREUR !\r\nIdentification du lot impossible...\r\nCode erreur : B7MITNDC');
            return false;
        }

        // Vérification : fichier non sélectionné
        if ($('#inputGroupFile01').val() === '') {
            alert('Sélectionnez un fichier...');
            return false;
        }

        // Vérification : type de document non précisé
        if (isNaN(type_id) || type_id === 0) {
            alert('Précisez le type de document...');
            return false;
        }

        // Ici tout est OK, on prépare les données à envoyer en aJax
        var formData = new FormData();
        formData.append('file', $('#inputGroupFile01')[0].files[0]);
        formData.append('mode', 'uploadDoc');
        formData.append('lot_id', lot_id);
        formData.append('type_id', type_id);
        formData.append('type_lot', 1);
        formData.append('nom', nom_doc);

        // Appel aJax en XHR natif pour upload
        $.ajax({
            url: 'scripts/ajax/fct_documents.php',
            type: 'POST',
            beforeSend: traitementAvantEnvoi(),
            success: function(retour){
                callBackRetour(retour);
            },
            data: formData,
            //Options signifiant à jQuery de ne pas s'occuper du type de données
            cache: false,
            contentType: false,
            processData: false
        }); // FIN aJax

    }); // FIN bouton Upload


} // FIN listener

// Listener sur la liste des documents du lot dans la modale documents
function listeDocsLotListener() {

    // --------------------------------
    // Bouton Supprimer document
    // --------------------------------
    $(document).off("click.btnSupprDoc").on('click.btnSupprDoc', '.btnSupprDoc', function(e) {

        e.preventDefault();

        if (!confirm("ATTENTION !\nVous allez supprimer un document !\nCelui-ci ne sera plus consultable dans l'intranet.\nContinuer ?")) { return false; }

        var doc_id = parseInt($(this).data('doc-id'));

        $.fn.ajax({
            'script_execute': 'fct_documents.php',
            'arguments':'mode=supprDoc&doc_id='+ doc_id+'&type_lot=1',
            'return_id' : 'listeDocumentsLotModale'
        });

        return false;

    }); // FIN supprimer document


} // FIN listener

// Fonction de traitement avant upload (disabled form)
function traitementAvantEnvoi() {

    if (!$('.retour-upload-erreur').hasClass('d-none')) {
        $('.retour-upload-erreur').addClass('d-none');
    }

    if (!$('.retour-upload-ok').hasClass('d-none')) {
        $('.retour-upload-ok').addClass('d-none');
    }

    $('#inputGroupFile01').prop('disabled', 'disabled');
    $('#inputGroupFile01').parents('.form-group').css('opacity', '.5');
    $('.nom-document').parents('.form-group').css('opacity', '.5');
    $('.type-document-id').parents('.form-group').css('opacity', '.5');
    $('.nom-document').prop('disabled', 'disabled');
    $('.type-document-id').prop('disabled', true);
    $('.type-document-id').selectpicker('refresh');
    $('.btnUpload').prop('disabled', true);
    $('.btnUpload').html('<i class="fa fa-spin fa-spinner"></i>');

    // PAS DE RETURN SINON CA NE MARCHE PAS !

} // FIN fonction traitement avant upload

// Fonction CallBack retour Upload
function callBackRetour(retour){

    $('#inputGroupFile01').prop('disabled', false);
    $('#inputGroupFile01').parents('.form-group').css('opacity', '1');
    $('.nom-document').parents('.form-group').css('opacity', '1');
    $('.type-document-id').parents('.form-group').css('opacity', '1');
    $('.nom-document').prop('disabled', false);
    $('.type-document-id').prop('disabled', false);
    $('.type-document-id').selectpicker('refresh');
    $('.btnUpload').prop('disabled', false);
    $('.btnUpload').html('<i class="fa fa-check mr-1"></i>Ajouter');

    // Si retour OK, on vide les champs et met à jour la liste
    if (parseInt(retour) === 1) {

        $('#inputGroupFile01').val('');
        $('.nom-document').val('');
        $('.nom-document').val('');
        $('.type-document-id').val('');
        $('.type-document-id').selectpicker('refresh');
        $(".nom-fichier-a-uploader").text('Cliquez ici pour sélectionnez un fichier...');

        if ( $('.retour-upload-ok').hasClass('d-none')) {
            $('.retour-upload-ok').removeClass('d-none');
        }

        // On raffraichit la liste des documents
        refreshListeDocumentsLot();



        // SI erreur lors de l'upload aJax
    } else if (parseInt(retour) === 0) {
        if ( $('.retour-upload-erreur').hasClass('d-none')) {
            $('.retour-upload-erreur').removeClass('d-none');
        }
    } else {

        var texteErreur = "ERREUR !\r\n"

        switch(parseInt(retour)) {
            case -1: alert(texteErreur+"Identification du lot impossible.\r\nCode erreur : FSLYDROI");break;
            case -2: alert(texteErreur+"Récupération du fichier impossible.\r\nCode erreur : ZLNN8SV5");break;
            case -3: alert(texteErreur+"Création du dossier upload du lot impossible.\r\nCode erreur : M8O4WVAC");break;
            case -4: alert(texteErreur+"Enregistrement du fichier sur le serveur impossible.\r\nCode erreur : K2X65B5H");break;
            case -5: alert(texteErreur+"Enregistrement du fichier en BDD après upload impossible.\r\nCode erreur : A9QWIRUN");break;
        }
        alert(texteErreur);
        return false;

    } // FIN test retour ajax

} // FIN CallBack retour Upload


// Rafraichi la liste des documents du lot (modale)
function refreshListeDocumentsLot() {
    "use strict";

    var lot_id = parseInt($('.btnUpload').data('lot-id'));

    $.fn.ajax({
        'script_execute': 'fct_documents.php',
        'arguments':'mode=majListeDocumentsLot&lot_id='+ lot_id+'&type_lot=1',
        'return_id' : 'listeDocumentsLotModale',
        'callBack' : listeDocsLotListener()
    });



} // FIN fonction

// Listener de la modale produits du lot
function modalLotNegProduitsListener(lot_id) {
    "use strict";
    $('#listeProduitsLotNegoce').hover(function(){
        datepickers()
    })
    if (lot_id === undefined) { lot_id  = parseInt($('#listeProduitsLotNegoce').data('lot-id')); }

    $('.selectpicker').selectpicker('render');

    $(".datepicker" ).datepicker({

        dateFormat:		"dd/mm/yy",
        dayNames:			[ "Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi" ],
        dayNamesMin:		[ "Di", "Lu", "Ma", "Me", "Je", "Ve", "Sa" ],
        dayNamesShort:		[ "Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam" ],
        firstDay:			1,
        monthNames:		[ "Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "octobre", "Novembre", "Décembre" ],
        monthNamesShort:	[ "Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc" ],
        nextText:			"Mois suivant",
        prevText:			"Mois précédent",
        beforeShow: function() {
            setTimeout(function(){
                $('.ui-datepicker').css('z-index', 10000);
            }, 0);
        }

    }); // FIN DatePicker


    $.fn.ajax({
        'script_execute': 'fct_lots_negoce.php',
        'arguments':'mode=listeProduitsLotNegoce&lot_id='+ lot_id,
        'return_id' : 'listeProduitsLotNegoce',
        'callBack' : listeProduitsLotNegoceListener()
    });

    // Ajout d'un produit de négoce
    $('.btnAddPdtNegoce').off("click.btnAddPdtNegoce").on("click.btnAddPdtNegoce", function(e) {

        e.preventDefault();      

        var id_pdt = parseInt($('#formAddPdtNegoce select[name=id_pdt]').val());
        var cartons = parseInt($('#formAddPdtNegoce input[name=cartons]').val());
        var poids = parseFloat($('#formAddPdtNegoce input[name=poids]').val());       
        var dlc = $('#formAddPdtNegoce input[name=dlc]').val();
        
        if (isNaN(id_pdt) || id_pdt === 0) { alert('Aucun produit sélectionné !');return false; }
        if (isNaN(cartons) || cartons < 1 || isNaN(poids) || poids < 0.001) { alert('Poids ou Cartons absente ou incomplète !');return false; }
        if (dlc == "") { alert('La date DLC/DDM absente ou incomplète !');return false; }

        $.fn.ajax({
            'script_execute': 'fct_lots_negoce.php',
            'form_id':'formAddPdtNegoce',
            'return_id' : 'listeProduitsLotNegoce',
            'done': function () {
                // Réinit le formulaire
                $('#formAddPdtNegoce select[name=id_pdt]').selectpicker('val', '');
                $('#formAddPdtNegoce select[name=id_pdt]').selectpicker('refresh');
                $('#formAddPdtNegoce input[name=cartons]').val('');
                $('#formAddPdtNegoce input[name=poids]').val('');            
                $('#formAddPdtNegoce input[name=quantite]').val('');
                $('#formAddPdtNegoce input[name=num_lot]').val('');
                $('#formAddPdtNegoce input[name=dlc]').val('');
            }
        });


    }); // FIN ajouter
    


} // FIN listener de la modale produits du lot

// Listener de la liste des produits du lot
function listeProduitsLotNegoceListener() {    
    
"use strict";   
    // Pagination Ajax
    $(document).on('click','#listeProduitsLotNegoce .pagination li a',function(){
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#listeProduitsLotNegoce').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_lots_negoce.php'+$(this).attr('data-url'),return_id:'listeProduitsLotNegoce' });
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax

    // Modif ligne produit négoce
    $(document).on('click','#listeProduitsLotNegoce .btnSavePdtNegoce',function(e){
        e.stopImmediatePropagation();
        e.preventDefault();        
        var id_pdt_negoce = parseInt($(this).data('id'));
        if (isNaN(id_pdt_negoce) || id_pdt_negoce === 0) { alert("ERREUR !\r\nIdentification du ProduitNegoce impossible...\r\nCode erreur : 1ZQ0DVL3"); return false; }

        var nb_cartons = parseInt($(this).parents('tr').find('.inputCartons').val());
        var poids = parseFloat($(this).parents('tr').find('.inputPoids').val());        
        var num_lot = $(this).parents('tr').find('.inputNum_lot').val();
        var qte = $(this).parents('tr').find('.inputQuantite').val();
        var dlc = $(this).parents('tr').find('.inputDlc').val();

        if (num_lot === '') {
            alert('Le numéro de lot est obligatoire !');
            return false;
        }

        if (isNaN(nb_cartons) || isNaN(poids) || nb_cartons === 0 || poids === 0) {
            if (!confirm("CONFIRMATION\r\nSupprimer ce produit du lot de négoce ?")) { return false; }
        }

        var ifa = $(this).find('i.fa');
        ifa.removeClass('fa-save').addClass('fa-spin fa-spinner');
        ifa.parent().addClass('disabled');
        ifa.parents('tr').find('input').prop('disabled', true);

        $.fn.ajax({
            'script_execute': 'fct_lots_negoce.php',
            'arguments':'mode=updPdtNegoce&id_pdt_negoce='+ id_pdt_negoce + '&nb_cartons=' + nb_cartons + '&poids=' + poids + '&quantite=' + qte + '&num_lot='+num_lot+'&dlc='+dlc,
            'return_id' : 'listeProduitsLotNegoce',
            'done' : function (){
               
            }
        });



    }); // FIN modif ligne produit

    
    
    //Suppression ligne produit negoce    
    $(document).on('click','#listeProduitsLotNegoce .btnDeletePdtNegoce', function(){

        var id_lot_pdt_negoce = parseInt($(this).data('id'));
        var id_lot = parseInt($('#listeProduitsLotNegoce').data('lot-id'))

        var texteConfirm = "ATTENTION\r\nVous allez supprimer ce produit lot de négoce.\r\nContinuer ?";
        if (!confirm(texteConfirm)) { return false; }

        $.fn.ajax({
            'script_execute': 'fct_lots_negoce.php',
            'arguments':'mode=supprPdtLotNegoce&id_lot_pdt_negoce='+ id_lot_pdt_negoce +'&id_lot='+id_lot,
            'return_id' : 'listeProduitsLotNegoce',
            'done': function(retour) {                

                console.log(retour)                
            },
             
            
        });



    }); // FIN suppr ligne produit

} // FIN listener de la liste des produits du lot




//Nouveau structure 

$('#genlot_date').change(function() {
    genereLot();
});

$('#genlot_abattoir').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
    genereLot();
});

$('#genlot_origine').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
    
    genereLot();
});

$('#composition').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {

    var composition =  $('#composition').val();

    var num_bl =  $('#num_bl').val();
    if (num_bl.length < 6) { return false; }

    // Si c'est un lot Abats et qu'on a pas le A, on le rajoute
    if (composition.substr(0,1) === 'A' &&  numlot.substr(numlot.length - 1).toUpperCase() !== 'A') {
        $('#numlot').val(numlot + 'A');
    // Si c'est pas un lot Abats et qu'on a un A, on l'enlève.
    } else if (composition.substr(0,1) !== 'A' &&  numlot.substr(numlot.length - 1).toUpperCase() === 'A') {
        $('#numlot').val(numlot.slice(0,-1));
    } // FIN contrôle du numéro de lot
});


function genereLot() {
    "use strict";

    var date        = $('#genlot_date').val();
    var abattoir    = $('#genlot_abattoir option:selected').data('subtext');
    var origine     = $('#genlot_origine option:selected').data('subtext');
    var abats       = $('#composition').val().substr(0,1);

    if (date === '' || abattoir === '' || origine === '' || date === undefined || abattoir === undefined || origine === undefined) { return false; }

    $.fn.ajax({
        'script_execute': 'fct_lots.php',
        'arguments': 'mode=genereNumLot&date='+date+'&abattoir='+abattoir+'&origine='+origine,
        'callBack': function (retour) {

            if (abats === 'A') {
                retour+= 'A';
            }

            $('#numlot').val(retour);

        } // FIN callback
    }); // FIN aJax
}

//Fin

//Pour faire la boucle sur la datepicker de DLC/DDM

function datepickers(){
    
    $(".datepickers").each(function(i){   
        let ind = i + 1;
        $(this).addClass('datepickers'+ind);
        $(this).datepicker({
            dateFormat:		"dd/mm/yy",
            dayNames:			[ "Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi" ],
            dayNamesMin:		[ "Di", "Lu", "Ma", "Me", "Je", "Ve", "Sa" ],
            dayNamesShort:		[ "Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam" ],
            firstDay:			1,
            monthNames:		[ "Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "octobre", "Novembre", "Décembre" ],
            monthNamesShort:	[ "Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc" ],
            nextText:			"Mois suivant",
            prevText:			"Mois précédent",
            beforeShow: function() {
                setTimeout(function(){
                    $('.ui-datepicker').css('z-index', 10000);
                }, 0);
            }
    
        }); // FIN DatePicker
    })

}

//Fin