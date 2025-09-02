/**
 ------------------------------------------------------------------------
 JS - Lots

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

    // Affiche la liste des lots (aJax)
    chargeListeLots();

    // Rafraichissement de la liste toutes les minutes
    setInterval(function() {
        chargeListeLots();
    }, 60 * 1000);

    // Chargement du contenu de la modale Lot à son ouverture
    $('#modalLotInfo').on('show.bs.modal', function (e) {

        // On récupère l'ID du lot
        var lot_id = e.relatedTarget.attributes['data-lot-id'] === undefined ? 0 : parseInt(e.relatedTarget.attributes['data-lot-id'].value);

        // On récupère le contenu de la modale
        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments': 'mode=modalLotInfo&id=' + lot_id,
            'callBack': function (retour) {

                if (parseInt(retour) === -1 ) {
                    alert('Une erreur est survenue\r\nCode erreur : VMMT0W79');
                    return false;
                }

                // On intègre les différents contenus
                var retours = retour.toString().split('^');
                var titre = retours[0];
                var body = retours[1];

                $('#modalLotInfoTitre').html(titre);
                $('#modalLotInfoBody').html(body);

                modaleLotDetailsListener(lot_id);


            } // FIN Callback
        }); // FIN aJax
    }); // Fin chargement du contenu de la modale


    // Chargement du contenu de la modale Lot à son ouverture
    $('#modalLotEdit').on('show.bs.modal', function (e) {

        // On récupère l'ID du lot
        var lot_id = e.relatedTarget.attributes['data-lot-id'] === undefined ? 0 : parseInt(e.relatedTarget.attributes['data-lot-id'].value);

        // On récupère le contenu de la modale
        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments': 'mode=modalLotEdit&id=' + lot_id,
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
            'arguments': 'mode=modalLotDocs&id=' + lot_id,
            'callBack': function (retour) {

                if (parseInt(retour) === -1 ) {
                    alert('Une erreur est survenue !\r\nCode erreur : VTUU6A3S');
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

    // Nettoyage du contenu de la modale Détails à sa fermeture
    $('#modalLotInfo').on('hidden.bs.modal', function (e) {

        $('#modalLotInfoTitre').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#modalLotInfoBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');

    }); // FIN fermeture modale Détails

    // Nettoyage du contenu de la modale édition à sa fermeture
    $('#modalLotEdit').on('hidden.bs.modal', function (e) {

        $('#modalLotEditTitre').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#modalLotEditBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#modalLotEditFooter').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');

    }); // FIN fermeture modale édition

    // Nettoyage du contenu de la modale Documents à sa fermeture
    $('#modalLotDocs').on('hidden.bs.modal', function (e) {

        $('#modalLotDocsTitre').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#modalLotDocsBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');

    }); // FIN fermeture modale documents

    // Nettoyage du contenu de la modale Poids du lot à sa fermeture
    $('#modalLotPoids').on('hidden.bs.modal', function (e) {

        $('#modalLotPoidsBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');

    }); // FIN fermeture modale documents


    $('#recherche_numlot').keyup(function (e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code === 13) {
            e.preventDefault();
            chargeListeLots();

        }
    });

    $('#recherche_numlot').parent().find('.pointeur').off("click.btnRechLot").on("click.btnRechLot", function(e) {
        e.preventDefault();

        chargeListeLots();
    });



}); // FIN ready


/** ******************************************
 * Affiche la liste des lots
 ****************************************** */
function chargeListeLots() {
    "use strict";

    var statut =  $('#listeLots').data('statut');

    var page = parseInt($('.pagination .page-item.active a').text());
    if (isNaN(page)) { page = 1;  }


    var recherche = $('#recherche_numlot').val().trim();
    if (recherche === undefined) { recherche = ''; }

    $.fn.ajax({
        'script_execute': 'fct_lots.php',
        'arguments': 'mode=showListeLots&statut='+statut+'&page='+page+'&recherche='+recherche,
        'return_id': 'listeLots',
        'done': function() {
            switchListener();
        },
        'callBack': function (retour) {
            listeLotsListener();
        } // FIN Callback
    }); // FIN ajax

    $.fn.ajax({
        'script_execute': 'fct_validations.php',
        'arguments': 'mode=updateBadgeCompteurMenu',
        'return_id': 'ajaxCompteurValid'
    }); // FIN aJax

    return true;

} // FIN fonction


/** ******************************************
 * Listener de la liste des lots
 ****************************************** */
function listeLotsListener() {

    // Clic sur un icone d'incident (supprimer)
    $(document).on('click','#listeLots .ico-incident',function(){

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
                    chargeListeLots();
                    $('#modalLotIncident').modal('hide');
                }
            }); // FIN ajax

            return false;

    }); // Fin clic supprimer incident




    // Pagination Ajax
    $(document).on('click','#listeLots .pagination li a',function(){        
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#listeLots').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_lots.php'+$(this).attr('data-url'),return_id:'listeLots',done:function() { switchListener();} });
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax


    // Bouton saisie rapide du poids à la réception
    $(document).on('click','#listeLots .btnPoidsReceptionLot',function(){

        var id_lot = parseInt($(this).parents('tr').data('id-lot'));

        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments': 'mode=modalPoidsReception&id_lot='+id_lot,
            'return_id': 'modalLotPoidsBody',
            'done': function () {

                // Listener
                modalePoidsReceptionListener();

            } // FIN Callback
        }); // FIN ajax

        $('#modalLotPoids').modal('show');

    }); // FIN bouton rapide poids réception lot


} // FIN listener liste des lots


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
        prevText:			"Mois précédent"

    }); // FIN DatePicker

    // --------------------------------
    // Bouton Enregistrer
    // --------------------------------
    $('.btnSaveLot').off("click.btnSaveLot").on("click.btnSaveLot", function(e) {

        e.preventDefault();

        // On vérifie que le numéro de lot n'est pas vide
        if ($('#updNumLot').val().length < 6) {
            $('#updNumLot').addClass('is-invalid');
            setTimeout(function(){
                $('#updNumLot').removeClass('is-invalid');
            }, 3000);
            return false;
        }

        // On vérifie que les dates sont valides
        var regexDate = new RegExp('^(([1-2][0-9]|0[1-9]|[3][0-1])\/([0][1-9]|[1][0-2])\/[2][0-9][0-9]{2})$');

        if ($('#updDateAbattage').val().length > 0 && !regexDate.test($('#updDateAbattage').val())) {
            $('#updDateAbattage').addClass('is-invalid');
            setTimeout(function(){
                $('#updDateAbattage').removeClass('is-invalid');
            }, 3000);
            return false;
        }

        if ($('#updDateReception').val().length > 0 && !regexDate.test($('#updDateReception').val())) {
            $('#updDateReception').addClass('is-invalid');
            setTimeout(function(){
                $('#updDateReception').removeClass('is-invalid');
            }, 3000);
            return false;
        }

        // On vérifie que si on a déclaré un incident, on a bien un commentaire
        var incident = parseInt($('select[name=incident]').val());
        var commentaire_len =  $('textarea[name=incident_commentaire]').val().trim().length;
        if (incident > 0 && commentaire_len === 0) {
            alert("ATTENTION !\r\nUn commentaire est obligatoire pour toute déclaration d'incident...\r\nMerci de compléter.");
            return false;
        }


        // On enregistre...
        goUpdLot();

    }); // FIN bouton enregistrer


    // --------------------------------
    // Bouton Sortie du lot
    // --------------------------------
    $('.btnSortieLot').off("click.btnSortieLot").on("click.btnSortieLot", function(e) {

        e.preventDefault();

        var texteConfirm = "ATTENTION\nVous allez sortir ce lot du process de production.\nIl sera visible dans les lots terminés.\nContinuer ?";
        if (!confirm(texteConfirm)) { return false; }

        var id_lot = parseInt($('#updLotIdLot').val());

        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments': 'mode=sortieLot&id_lot='+id_lot,
            'callBack': function (retour) {

                // SI erreur
                if (parseInt(retour) === -1) {

                    alert('Une erreur est survenur, sortie du lot impossible !\r\nCode erreur : W1U56HPL');
                    return false;

                } // FIN erreur

                // on recharge la liste à jour et on ferme la modale
                chargeListeLots();
                $('#modalLotEdit').modal('hide');

            } // FIN Callback
        }); // FIN ajax

    }); // FIN bouton Sortie du lot

    // --------------------------------
    // Bouton Supprimer lot
    // --------------------------------
    $('.btnDelLot').off("click.btnDelLot").on("click.btnDelLot", function(e) {

        e.preventDefault();

        var texteConfirm = "ATTENTION\nVous allez supprimer ce lot.\nIl ne sera plus accessible ni visible dans les lots terminés.\nContinuer ?";
        if (!confirm(texteConfirm)) { return false; }

        var id_lot = parseInt($('#updLotIdLot').val());

        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments': 'mode=supprimeLot&id_lot='+id_lot,
            'callBack': function (retour) {

                // SI erreur
                if (parseInt(retour) === -1) {

                    alert('Une erreur est survenur, suppression du lot impossible !\r\nCode erreur : 9SL011EV');
                    return false;

                } // FIN erreur

                // on recharge la liste à jour et on ferme la modale
                chargeListeLots();
                $('#modalLotEdit').modal('hide');

            } // FIN Callback
        }); // FIN ajax

    }); // FIN bouton Supprimer lot

    // Sélection d'un incident..
    $('select[name=incident]').change(function(){

        var type = parseInt($(this).val());
        if (type === 0) {
            $('textarea[name=incident_commentaire]').removeClass('d-none').addClass('d-none');
            $('textarea[name=incident_commentaire]').val('');
        } else {
            $('textarea[name=incident_commentaire].d-none').removeClass('d-none');
        }

    });


} // FIN listener modale édition d'un lot


/** ******************************************
 * Valide l'enregistrement
 ****************************************** */
function goUpdLot () {

    $.fn.ajax({
        'script_execute': 'fct_lots.php',
        'form_id': 'formUpdLot',
        'callBack': function (retour) {

            // SI erreur sur le numéro de lot :
            if (parseInt(retour) === -1) {

                $('#updNumLot').addClass('is-invalid');
                setTimeout(function(){
                    $('#updNumLot').removeClass('is-invalid');
                }, 3000);
                return false;

            } // FIN test erreur sur numéro de lot

            // SI erreur sur l'instanciation du lot
            if (parseInt(retour) === -2) {

                alert('Une erreur est survenur, identification du lot impossible !\nCode erreur : QDSEKSJY');
                return false;

            } // FIN erreur sur l'instanciation du lot

            // SI erreur sur l'enregistrement du lot
            if (parseInt(retour) === -3) {

                alert('Une erreur est survenur, enregistrement du lot impossible !\nCode erreur : SJUCEDBO');
                return false;

            } // FIN erreur sur l'instanciation du lot

            // SI aucune modif
            if (parseInt(retour) === -4) {
                console.log('Aucune modif');
            } // FIN retour aucune modif

            // on recharge la liste à jour et on ferme la modale
            chargeListeLots();
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
            alert('ERREUR !\r\nIdentification du lot impossible...\r\nCode erreur : UCOJWDAF');
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

    // Bouton export PDF (liste documents)
    $('.btn-doc-genere-pdf').off("click.btndocgenerepdf").on("click.btndocgenerepdf", function(e) {
        e.preventDefault();

        var id_lot = parseInt($(this).data('id-lot'));
        if (isNaN(id_lot) || id_lot < 0) { alert("ERREUR\r\nUne erreur est survenue.\r\nCode erreur : OH8EKQT6"); return false; }

        var objetDomBtnExport = $(this);
        objetDomBtnExport.find('i').removeClass('fa-external-link-alt').addClass('fa-spin fa-spinner');
        objetDomBtnExport.attr('disabled', 'disabled');

        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments':'mode=generePdf&id_lot='+ id_lot,
            'callBack' : function (url_fichier) {
                objetDomBtnExport.find('i').removeClass('fa-spin fa-spinner').addClass('fa-external-link-alt');
                objetDomBtnExport.prop('disabled', false);

                $('#lienPdfLotDoc').attr('href', url_fichier);
                $('#lienPdfLotDoc')[0].click();

            } // FIN callBack
        }); // FIN ajax
    }); // FIN bouton export PDF

} // FIN listener

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
            case -2: alert(texteErreur+"Récupération du fichier impossible.\r\nCode erreur : VPSAXYNH");break;
            case -3: alert(texteErreur+"Création du dossier upload du lot impossible.\r\nCode erreur : LUZGLRQN");break;
            case -4: alert(texteErreur+"Enregistrement du fichier sur le serveur impossible.\r\nCode erreur : CLYKPJYY");break;
            case -5: alert(texteErreur+"Enregistrement du fichier en BDD après upload impossible.\r\nCode erreur : JEMPXTAM");break;
        }
        alert(texteErreur);
        return false;

    } // FIN test retour ajax

} // FIN CallBack retour Upload

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
            'arguments':'mode=supprDoc&doc_id='+ doc_id,
            'return_id' : 'listeDocumentsLotModale'
        });

        return false;

    }); // FIN supprimer document


} // FIN listener

// Rafraichi la liste des documents du lot (modale)
function refreshListeDocumentsLot() {

    //$('#listeDocumentsLotModale').html('<i class="fa fa-spin fa-spinner fa-lg gris-9"></i>');

    var lot_id = parseInt($('.btnUpload').data('lot-id'));

    $.fn.ajax({
        'script_execute': 'fct_documents.php',
        'arguments':'mode=majListeDocumentsLot&lot_id='+ lot_id,
        'return_id' : 'listeDocumentsLotModale',
        'callBack' : listeDocsLotListener()
    });



} // FIN fonction

// Listener de la modale de saisie rapide du poids de livraison
function modalePoidsReceptionListener() {

    $('#inputPoidsReception').focus();

    $('#inputPoidsReception').keyup(function(e) {

        if(e.which === 13) {
            savePoidsReception();
        }

        var poids_a = parseFloat($('#poidsAbattoir').text());
        var poids_r = parseFloat($(this).val().replace(',', '.'));

        var ecart =  '—';
        if (!isNaN(poids_a) && !isNaN(poids_r) && poids_a > 0 && poids_r > 0) {
             ecart = (poids_r - poids_a).toFixed(3);
        }
        $('#ecartPoidsReception').text(ecart);

    });


    $('.btnSavePoidsReception').click(function() {
        savePoidsReception();
    });



} // FIN listener


// Enregistre le poids de réception (modale)
function savePoidsReception() {
    "use strict";
    var poids =  parseFloat($('#inputPoidsReception').val().replace(',', '.'));
    if (isNaN(poids) || poids <= 0) { $('#inputPoidsReception').val('');return false; }


    var id_lot = parseInt($('#inputPoidsReception').data('lot-id'));

    $.fn.ajax({
        'script_execute': 'fct_lots.php',
        'arguments':'mode=savePoidsReception&id_lot='+ id_lot+'&poids='+poids,
        'done' : function () {
            chargeListeLots();
            $('#modalLotPoids').modal('hide');
        }
    });


} // FIN fonction


// Listener de la modale détails du lot
function modaleLotDetailsListener(lot_id) {

    if (lot_id === undefined) { return false; }
    $('.cbo-popover').popover({
        trigger: 'focus'
    });

    // Pagination Ajax (Produits)
    $(document).on('click','#pdts .pagination li a',function(e){
        e.stopImmediatePropagation();
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#pdts').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_lots.php'+$(this).attr('data-url'),return_id:'pdts'});
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax Produits

    // Pagination Ajax (Emballages)
    $(document).on('click','#emb .pagination li a',function(e){
        e.stopImmediatePropagation();
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#emb').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_lots.php'+$(this).attr('data-url'),return_id:'emb'});
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax Emballages

    // Pagination Ajax (Loma)
    $(document).on('click','#lom .pagination li a',function(e){
        e.stopImmediatePropagation();
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#lom').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({script_execute:'fct_lots.php'+$(this).attr('data-url'),return_id:'lom', 'done' : function() {
                $('.cbo-popover').popover({
                    trigger: 'focus'
                });
            }});
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax Emballages

    // Bouton export PDF
    $('.btn-export-lot-pdf').off("click.btnexportlotpdf").on("click.btnexportlotpdf", function(e) {
        e.preventDefault();

        var id_lot = parseInt($('#general').data('id-lot'));
        if (isNaN(id_lot) || id_lot < 0) { alert("ERREUR\r\nUne erreur est survenue.\r\nCode erreur : 0J18GB46"); return false; }

        var objetDomBtnExport = $(this);
        objetDomBtnExport.find('i').removeClass('fa-file-pdf').addClass('fa-spin fa-spinner');
        objetDomBtnExport.attr('disabled', 'disabled');

        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments':'mode=generePdf&id_lot='+ id_lot,
            'callBack' : function (url_fichier) {
                objetDomBtnExport.find('i').removeClass('fa-spin fa-spinner').addClass('fa-file-pdf');
                objetDomBtnExport.prop('disabled', false);

                $('#lienPdfLot').attr('href', url_fichier);
                $('#lienPdfLot')[0].click();

            } // FIN callBack
        }); // FIN ajax
    }); // FIN bouton export PDF

    // Boutton Ré-ourvrir un lot terminé
    $('.btn-reopenlot').off("click.btnreopenlot").on("click.btnreopenlot", function(e) {
        e.preventDefault();

        var id_lot = parseInt($('#general').data('id-lot'));
        if (isNaN(id_lot) || id_lot < 0) {
            alert("ERREUR\r\nUne erreur est survenue.\r\nCode erreur : J0COJT0P");
            return false;
        }

        if (!confirm("ATTENTION\r\nVous allez ré-ouvrir un lot terminé !\r\nContinuer ?")) { return false; }

        $(this).html('<i class="fa fa-spin fa-spinner"></i>');

        // OK, ajax qui réouvre le lot...
        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments':'mode=reouvrirLotTermine&id_lot='+ id_lot,
            'done' : function () {
                $(location).attr('href', 'admin-lots.php')
            } // FIN callBack
        }); // FIN ajax


    }); // FIN bouton révouvrir lot terminé


    // Bouton re-envoyer vers Bizerba
    $('.btnReBizerba').off("click.btnReBizerba").on("click.btnReBizerba", function(e) {
        e.preventDefault();
        if (!confirm("Re-envoyer ce lot à BizTrack ?\r\nAttention : risque de doublon !\r\nÊtes-vous sûr ?")) { return false; }

        var id_lot = parseInt($('#general').data('id-lot'));
        if (isNaN(id_lot) || id_lot < 0) {
            alert("ERREUR\r\nIdentification du lot impossible !");
            return false;
        }

        var ifa = $(this).find('i.fa');
        ifa.removeClass('fa-redo-alt').addClass('fa-spin fa-spinner');
        // OK, ajax qui réouvre le lot...
        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments':'mode=envoiLotBizerna&id_lot='+ id_lot,
            'callBack' : function (retour) {

                ifa.removeClass('fa-spin fa-spinner').addClass('fa-redo-alt');

                retour = retour + '';
                if (parseInt(retour) !== 1) {
                    alert('ERREUR !\r\nEchec lors de l\'envoi du lot vers Bizerba...\r\nCode erreur : ' + retour);
                    return false;
                }

                $('.btnBizerba').hide();
                alert('Lot envoyé vers Bizerba.');
            } // FIN callBack
        }); // FIN ajax

    }); // FIN re-bizerba

        // Bouton envoyer vers Bizerba
    $('.btnBizerba').off("click.btnBizerba").on("click.btnBizerba", function(e) {
        e.preventDefault();

        var id_lot = parseInt($('#general').data('id-lot'));
        if (isNaN(id_lot) || id_lot < 0) {
            alert("ERREUR\r\nUne erreur est survenue.\r\nCode erreur : HMOWI7IF");
            return false;
        }

        var ifa = $(this).find('i.fa');
        ifa.removeClass('fa-share-alt').addClass('fa-spin fa-spinner');
        // OK, ajax qui réouvre le lot...
        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments':'mode=envoiLotBizerna&id_lot='+ id_lot,
            'callBack' : function (retour) {

                ifa.removeClass('fa-spin fa-spinner').addClass('fa-share-alt');

                retour = retour + '';
                if (parseInt(retour) !== 1) {
                    alert('ERREUR !\r\nEchec lors de l\'envoi du lot vers Bizerba...\r\nCode erreur : ' + retour);
                    return false;
                }

                $('.btnBizerba').hide();
                alert('Lot envoyé vers Bizerba.');
            } // FIN callBack
        }); // FIN ajax



    }); // FIN bouton envoyer vers Bizerba



} // FIN fonction

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
            alert('ERREUR\r\nIdentification du lot impossible !\r\nCode erreur : GI4UF0VA');
            return false;
        }

        // On récupère l'état de visibilité à appliquer
        var visible =  $(this).prop('checked') ? 1 : 0;

        // Maj en ajax de la visibilité du lot
        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments':'mode=changeVisibilite&id_lot='+ id_lot + '&visible='+visible,
            'callBack' : function (retour) {
                if (parseInt(retour) !== 1) {
                    alert('ERREUR\r\nEchec lors du changement de visibilité !\r\nCode erreur : 6AU1QIUN');
                }
            } // FIN callBack
        }); // FIN ajax

    }); // FIN Changement visibilité


    // Changement test traçabilité
    $('.switch-tracabilite-lot').change(function() {

        // Récupération de l'ID du lot et gestion des erreurs
        var id_lot = parseInt($(this).parents('tr').data('id-lot'));
        if (isNaN(id_lot) || id_lot === undefined || id_lot === 0) {
            alert('ERREUR\r\nIdentification du lot impossible !\r\nCode erreur : 1LKCCONJ');
            return false;
        }

        // On récupère l'état à appliquer
        var tracabilite =  $(this).prop('checked') ? 1 : 0;

        // Maj en ajax de la visibilité du lot
        $.fn.ajax({
            'script_execute': 'fct_lots.php',
            'arguments':'mode=changeTracabilite&id_lot='+ id_lot + '&tracabilite='+tracabilite,
            'callBack' : function (retour) {
                if (parseInt(retour) !== 1) {
                    alert('ERREUR\r\nEchec lors du changement de visibilité !\r\nCode erreur : 7IX66NH1');
                }
            } // FIN callBack
        }); // FIN ajax

    }); // FIN Changement test traçabilité

} // FIN listener


