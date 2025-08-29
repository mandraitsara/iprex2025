/**
 ------------------------------------------------------------------------
 JS - Vue Réception

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

    $('#ticketLot').show();
    listenerEtape1();

    $('.verify-link').on('click', function(e){
        e.preventDefault();
        location.reload();
    })

    // Nettoyage du contenu de la modale Incident
    $('#modalIncidentFront').on('hidden.bs.modal', function (e) {
        $('#modalIncidentFrontBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        if (!$('.btnSaveIncident').hasClass('d-none')) {
            $('.btnSaveIncident').addClass('d-none');
        }
        $('.btnFermerModale.d-none').removeClass('d-none');
        $('.btnSaveIncident').prop('disabled', false);
        $('.btnSaveIncident').html('<i class="fa fa-check mr-2"></i>Enregistrer');
    }); // FIN fermeture modale  Incident

}); // FIN ready


// Listener Etape 1 : Sélection du lot
function listenerEtape1() {
    "use strict";
    /*$('.btnIncident').show();*/
    ticketLotListener();

    $('.carte-lot').off("click.selectcartelot").on("click.selectcartelot", function(e) {
        e.preventDefault();

        var id_lot = parseInt($(this).data('id-lot'));
        if (id_lot === 0 || isNaN(id_lot) || id_lot === undefined) { return false; }

        var objetDomCarte = $(this);

        // On identifie la composition et le type
        var composition         = parseInt($(this).data('composition'));
        var composition_viande  = parseInt($(this).data('composition-viande'));

        // Si c'est de la viande (1) on demande de préciser sous vide ou carcasse si pas déjà renseigné
        if (composition === 1 && composition_viande === 0) {

            // On ouvre la modale pour obliger à définir
            $('#modalCompositionViande').modal('show');
            compositionViandeListener(objetDomCarte, id_lot);
            return false;

        } // FIN test composition viande à définir

        lotSelectionne(objetDomCarte, id_lot);

    });

} // FIN listener Sélection du lot

// Listener du ticket lot
function ticketLotListener() {

    // Retour palettes
    $('#ticketLot .btnRetourPalettes').off("click.btnRetourPalettes").on("click.btnRetourPalettes", function(e) {
        e.preventDefault();

        var etape_en_cours = $('.etape-workflow:visible').attr('id');
        if (etape_en_cours === undefined) { alert('Echec identification étape !');return false; }

        $('#'+etape_en_cours).hide();
        $('#etape11').show('fast');

        $('#ticketLotContent').hide();
        $('.btnsTicketsLot').hide();
        $('.btnRetourPalettes').hide();
        $('.btnAnnulerPalettes').show();

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=chargeEtapeRetourPalettes&etape=1',
            'return_id': 'etape11',
            'done': function () {

                listenerEtape11(etape_en_cours);

            } // FIN Callback
        }); // FIN ajax




    }); // FIN retour palettes



    // Bouton changment de lot
    $('#ticketLot .btnChangeLot').click(function() {

        $('#etape1').show('fast');
        $('#ticketLot .btnsTicketsLot, #ticketLotContent').hide('fast');
        $('.etapes-suivantes').hide('fast');

        listenerEtape1();

    }); // FIN changement de lot

    // Déclarer un incident
    $('#modalIncidentFront').on('show.bs.modal', function (e) {

        // Sélection du type d'incident
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=selectTypeIncidentModale',
            'return_id': 'modalIncidentFrontBody',
            'done': function () {

                modalIncidentFrontListener_1();

            } // FIN Callback
        }); // FIN ajax

    }); // FIN modale déclarer un incident




} // FIN listener du ticket lot



// Fonction chargeant les étapes pour intégrer leur contenu
function chargeEtape(numeroEtape, id_lot) {


    $('.etapes-suivantes').hide('fast');

    $('#etape'+numeroEtape).show('fast');
    $('#etape'+numeroEtape).html('<div class="padding-50 text-center"><i class="fa fa-spin fa-spinner fa-5x gris-c"></i></div>');

    // Ajax qui charge le contenu de l'étape
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_rcp.php',
        'arguments': 'mode=chargeEtapeVue&id_lot='+id_lot+'&etape=' + numeroEtape,
        'return_id': 'etape'+numeroEtape,
        'done': function () {


            switch(numeroEtape) {
                case 2: listenerEtape2(id_lot);break;
                case 3: listenerEtape3(id_lot);break;
                case 4: listenerEtape4(id_lot);break;
                case 5: listenerEtape5(id_lot);break;
                case 6: listenerEtape6(id_lot);break;
                case 7: listenerEtape7(id_lot);break;
                case 8: listenerEtape8(id_lot);break;
                case 10: listenerEtape10(id_lot);break;
                case 12: listenerEtape12(id_lot);break;
            }
        } // FIN Callback
    }); // FIN ajax

} // FIN fonction chargeEtape

// Listener de l'étape 2
function listenerEtape2(id_lot) {

    $('.btn-confirme-reception').off("click.btnconfirmereception").on("click.btnconfirmereception", function(e) {

        e.preventDefault();

        // Validation de la réception du lot
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=actionValideReception&id_lot='+id_lot,
            'callBack': function (etapesuivante) {

                if (etapesuivante === undefined || isNaN(parseInt(etapesuivante)) || parseInt(etapesuivante) === 0) { alert("ERREUR !\r\nEnregistrement impossible !\r\nCode erreur : HIKGMBA4");return false; }

                // On met à jour la carte (invisible) pour la vue suivante, si changement de lot pour ne pas revenir à une vue déjà complétée
                $('#etape1').find('.card[data-id-lot='+id_lot+']').attr('data-etape-suivante', etapesuivante);

                // On met à jour la date de réception dans le ticket (il s'agit toujours de la date du jour ici, pas besoin de récupérer l'info)
                var fullDate = new Date();
                var mois = [ "janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre" ];
                var currentDate = fullDate.getDate() + " " + mois[fullDate.getMonth()] + " " + fullDate.getFullYear();
                $('.ticket-date-reception').text(currentDate)

                // On charge l'étape suivante...
                chargeEtape(parseInt(etapesuivante), id_lot);


            } // FIN Callback
        }); // FIN ajax

    });

} // FIN listener de l'étape 2

// Listener de l'étape 3
function listenerEtape3(id_lot) {

    champ = ''; // Super-variable de champ

    if ($('input[name=temp]').length) {
        $('input[name=temp]').focus();
        champ = '';
    } else {
        $('input[name=tempd]').focus();
        champ = 'd';
    }

    $('input[name=temp]').focus(function()  { champ = '';  });
    $('input[name=tempd]').focus(function() { champ = 'd'; });
    $('input[name=tempm]').focus(function() { champ = 'm'; });
    $('input[name=tempf]').focus(function() { champ = 'f'; });


    // Saisie via le pavé numérique
    $('.clavier-temperature .btn').off("click.appuietoucheclavier").on("click.appuietoucheclavier", function(e) {

        e.preventDefault();

        // On efface le message d'erreur si il était affiché
        if (!$('.temperatureInvalide').hasClass('d-none')) {
            $('.type-temperature-invalide-txt').text('');
            $('.temperatureInvalide').addClass('d-none');
        }

        // On récupère la valeur de la touche
        var touche = $(this).data('valeur');

        // Si touche "effacer", on reset le champ en cours
        if (touche === 'C') {
            $('input[name=temp'+champ+']').val('');
            return false;
        }

        // Si touche "signe" (+/-)
        if (touche === 'S') {
            var temperature =  $('input[name=temp'+champ+']').val().trim();
            temperature = temperature.slice(0,1) === '-' ?  temperature.substring(1, temperature. length) : '-' + temperature;
            $('input[name=temp'+champ+']').val(temperature);
            return false;
        }

        // SI touche "Valider", on teste...
        if (touche === 'V') {

            // On passe au champ suivant s'il est vide
            if (champ === 'd' &&  $('input[name=tempm]').val().trim() === '') {
                $('input[name=tempm]').focus();
                return false;
            } else if (champ === 'm' &&  $('input[name=tempf]').val().trim() === '') {
                $('input[name=tempf]').focus();
                return false;
            } else if (champ === 'f' &&  $('input[name=tempd]').val().trim() === '') {
                $('input[name=tempd]').focus();
                return false;

            // On teste la validité du ou des champs
            } else {

                // Champ unique (température abats)
                if (champ === '') {
                    if (!verifChampTemperature('')) {
                        $('.temperatureInvalide').removeClass('d-none');
                        return false;
                    }
                } else {

                    if (!verifChampTemperature('d')) {
                        $('.type-temperature-invalide-txt').text('«D»');
                        $('.temperatureInvalide').removeClass('d-none');
                        return false;
                    }

                    if (!verifChampTemperature('m')) {
                        $('.type-temperature-invalide-txt').text('«M»');
                        $('.temperatureInvalide').removeClass('d-none');
                        return false;
                    }

                    if (!verifChampTemperature('f')) {
                        $('.type-temperature-invalide-txt').text('«F»');
                        $('.temperatureInvalide').removeClass('d-none');
                        return false;
                    }
                }

            } // FIN test champ source

            // Modal confirmation si température hors normes

            // Températures de référence pour contrôle
            var temp_controle_min = parseFloat($('.temp-controles').data('temp-controle-min'));
            var temp_controle_max = parseFloat($('.temp-controles').data('temp-controle-max'));

            // Si erreur de récup des températures de contrôle (mauvais paramétrage dans le BO...), on ne bloque pas le process... ici on test qu'on est ok
            if (!isNaN(temp_controle_min) && temp_controle_min !== undefined && !isNaN(temp_controle_min) && temp_controle_min !== undefined) {

                var ok = true;
                var rappelSaisieTemp = '';

                // SI une des températures est hors norme...
                if ($('input[name=temp]').length) {
                    var tempTest = parseFloat($('input[name=temp]').val());
                    if (tempTest < temp_controle_min || tempTest > temp_controle_max) { ok = false; rappelSaisieTemp+= '<span class="text-danger">' + tempTest + '°C</span>'; }
                } else {
                    var tempTestD = parseFloat($('input[name=tempd]').val());
                    if (tempTestD < temp_controle_min || tempTestD > temp_controle_max) { ok = false; rappelSaisieTemp+= '<span class="text-danger">' + tempTestD + '°C</span> (D), ';}
                    var tempTestM = parseFloat($('input[name=tempm]').val());
                    if (tempTestM < temp_controle_min || tempTestM > temp_controle_max) { ok = false; rappelSaisieTemp+= '<span class="text-danger">' + tempTestM + '°C</span> (M), ';}
                    var tempTestF = parseFloat($('input[name=tempf]').val());
                    if (tempTestF < temp_controle_min || tempTestF > temp_controle_max) { ok = false; rappelSaisieTemp+= '<span class="text-danger">' + tempTestF + '°C</span> (F), ';}

                    rappelSaisieTemp = rappelSaisieTemp.substr(0, rappelSaisieTemp.length-2);
                }


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

                    listenerModaleTempHs(id_lot);

                    return false;

                } // FIN test températures valides



            } // FIN vérif températures de contrôles bien récupérées


            saveTemperature(id_lot);

            return false;

        } // FIN test touche valider

        // Autre touche numérique : on complète la température

        var temperatureVal = $('input[name=temp'+champ+']').val().trim();
        if (temperatureVal.length === 0) {
            $('input[name=temp'+champ+']').val(touche);
            // Si on saisi un point, on vérifie qu'il n'y en a pas déjà un
        } else if (touche === '.' && temperatureVal.indexOf('.') !== -1) {
            return false;
        } else {
            $('input[name=temp'+champ+']').val(temperatureVal + touche);
        } // FIN saisie touhches numériques

    }); // FIN touche clavier

} // FIN listener de l'étape 3





// Listener de l'étape 4
function listenerEtape4(id_lot) {

    // Validation de l'état visuel du lot
    $('.btnValideEtatVisuel').off("click.btnvalideetatvisuel").on("click.btnvalideetatvisuel", function(e) {

        e.preventDefault();

        var etat    = parseInt($(this).data('etat-visuel'));
        var id_lot  = parseInt($(this).data('id-lot'));

        // Enregistrement de l'état en BDD et étape suivante
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=actionValideEtatVisuel&id_lot='+id_lot+'&etat='+etat,
            'callBack': function (etapesuivante) {

                if (etapesuivante === undefined || isNaN(parseInt(etapesuivante)) || parseInt(etapesuivante) === 0) { alert("ERREUR !\r\nEnregistrement impossible !\r\nCode erreur : ZWLZ19W4");return false; }

                // On met à jour la carte (invisible) pour la vue suivante, si changement de lot pour ne pas revenir à une vue déjà complétée
                $('#etape1').find('.card[data-id-lot='+id_lot+']').attr('data-etape-suivante', etapesuivante);

                // On met à jour l'état visuel dans le ticket, on affiche l'info si elle était masquée
                var etatsVerbose = { 0 : 'Contestable', 1 : 'Satisfaisant'};
                $('.ticket-etat-visuel').text(etatsVerbose[etat]);
                if ($('.ticket-etat-visuel-conteneur').hasClass('ticket-hide')) {
                    $('.ticket-etat-visuel-conteneur').show('fast');
                }


                // On charge l'étape suivante...
                chargeEtape(parseInt(etapesuivante), id_lot);

            } // FIN Callback
        }); // FIN ajax
        return false;

    }); // FIN validation du poids admin

} // FIN listener de l'étape 4



function handleSatisfactionChange() {
    const anyNotSatisfied = document.querySelector('.icheck.icheck-rouge:checked');
    const allSatisfied = document.querySelectorAll('.icheck.icheck-vert:checked').length === document.querySelectorAll('.icheck.icheck-vert').length;
    
    const conformeButton = document.getElementById('conforme-button');
    const nonConformeButton = document.getElementById('non-conforme-button');
    
    if (anyNotSatisfied) {
        nonConformeButton.disabled = false;
        conformeButton.disabled = true;
    } else if (allSatisfied) {
        nonConformeButton.disabled = true;
        conformeButton.disabled = false;
    } else {
        nonConformeButton.disabled = true;
        conformeButton.disabled = true;
    }



}

// Listener de l'étape 5
function listenerEtape5() {

    clavierVirtuel();


    $('.ichecktout').click(function(e) {
        e.preventDefault();
        $(this).find('.icheck').iCheck('toggle');
   
    });

    $('.icheck.icheck-rouge').iCheck({ radioClass: 'iradio_flat-red'}).on('ifChanged', handleSatisfactionChange);
    $('.icheck.icheck-vert').iCheck({ radioClass: 'iradio_flat-green'}).on('ifChanged', handleSatisfactionChange);
    

    $('.btnToutOk').off("click.btnToutOk").on("click.btnToutOk", function(e) {
        e.preventDefault();

        var id_parent = parseInt($(this).data('id-parent'));
        if (isNaN(id_parent)) { id_parent = 0; }
        if (id_parent === 0) { return false; }

        $('.icheck.parent-'+id_parent).iCheck('check');
        handleSatisfactionChange();
    });


    handleSatisfactionChange();



    // Validation du receptionniste : bouton Conforme / Non Conforme
    $('.btnConformiteLot').off("click.btnconformitelot").on("click.btnconformitelot", function(e) {

        e.preventDefault();

        // On intègre la conformité sélectionné dans le formulaire à passer en aJax
        // (on passe par un formulaire pour éviter les problèmes de caractères saisis dans le textarea via les attributs : * + & ...)
        var conformite      = parseInt($(this).data('conformite'));
        $('#observationsReception input[name=conformite]').val(conformite);

        // On remplace l'esperluette (&) dans le textarea des observation pour éviter le cesure en ajax
        $('textarea[name=observations]').val($('textarea[name=observations]').val().replace(/&/g, ' et ').replace(/\+/g, ' et '));


        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'form_id': 'observationsReception',
            'callBack': function (etapesuivante) {

                if (etapesuivante === undefined || isNaN(parseInt(etapesuivante)) || parseInt(etapesuivante) === 0) { alert("ERREUR !\r\nEnregistrement impossible !\r\nCode erreur : ISX7BSAB");return false; }

                // On met à jour la carte (invisible) pour la vue suivante, si changement de lot pour ne pas revenir à une vue déjà complétée
                $('#etape1').find('.card[data-id-lot='+id_lot+']').attr('data-etape-suivante', etapesuivante);

                // On met à jour l'état visuel dans le ticket, on affiche l'info si elle était masquée
                var validationVerbose = { 0 : 'Non conforme', 1 : 'Conforme'};
                $('.ticket-validation').text(validationVerbose[conformite]);
                if ($('.ticket-validation-conteneur').hasClass('ticket-hide')) {
                    $('.ticket-validation-conteneur').show('fast');
                }


                // On charge l'étape suivante...
                var id_lot =  $('#observationsReception input[name=id_lot]').val();
                chargeEtape(parseInt(etapesuivante), id_lot);

            } // FIN Callback
        }); // FIN ajax

        return false;

    }); // FIN validation bouton Conforme / Non Conforme

} // FIN listener de l'étape 5

// Listener de l'étape 6
function listenerEtape6() {

    // Confirmer récéption
    $('.btnConfirmerReception').off("click.btnconfirmerreception").on("click.btnconfirmerreception", function(e) {

        e.preventDefault();

        $(this).find('i.fa').removeClass('fa-clipboard-check').addClass('fa-spin').addClass('fa-spinner');

        var id_lot = parseInt($(this).data('id-lot'));

        // Enregistrement : passage de la vue RCP à ATL
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=actionTermineReception&id_lot='+id_lot,
            'done': function () {

                location.reload();

            } // FIN Callback
        }); // FIN ajax
        return false;

    }); // FIN confirmer récéption

    // Retour étape 10
    $('.btnRetourEtape10').off("click.btnretouretape10").on("click.btnretouretape10", function(e) {

        e.preventDefault();

        var id_lot = parseInt($(this).data('id-lot'));

        chargeEtape(10, id_lot);

        return false;

    }); // FIN retour étape 5

} // FIN listener de l'étape 6


// Listener de l'étape 7
function listenerEtape7(id_lot) {

    $('.btnIncident').show();

    $('input[name=temp]').focus();

    // Saisie via le pavé numérique
    $('.clavier-temperature .btn').off("click.appuietoucheclavier").on("click.appuietoucheclavier", function(e) {

        e.preventDefault();

        // On efface le message d'erreur si il était affiché
        if (!$('.temperatureInvalide').hasClass('d-none')) {
            $('.type-temperature-invalide-txt').text('');
            $('.temperatureInvalide').addClass('d-none');
        }

        // On récupère la valeur de la touche
        var touche = $(this).data('valeur');

        // Si touche "effacer", on reset le champ en cours
        if (touche === 'C') {
            $('input[name=temp]').val('');
            return false;
        }

        // Si touche "signe" (+/-)
        if (touche === 'S') {
            var temperature =  $('input[name=temp]').val().trim();
            temperature = temperature.slice(0,1) === '-' ?  temperature.substring(1, temperature. length) : '-' + temperature;
            $('input[name=temp]').val(temperature);
            return false;
        }

        // SI touche "Valider", on teste...
        if (touche === 'V') {

            if (!verifChampTemperature('')) {
                $('.temperatureInvalide').removeClass('d-none');
                return false;
            }

            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
                'script_execute': 'fct_vue_rcp.php',
                'form_id': 'formTemperatureNegoce',
                'callBack': function (etapesuivante) {

                    if (etapesuivante === undefined || isNaN(parseInt(etapesuivante)) || parseInt(etapesuivante) === 0) { alert("ERREUR !\r\nEnregistrement impossible !\r\nCode erreur : LNV098DU");return false; }

                    // On met à jour la carte (invisible) pour la vue suivante, si changement de lot pour ne pas revenir à une vue déjà complétée
                    $('#etape1').find('.card[data-id-lot='+id_lot+']').attr('data-etape-suivante', etapesuivante);

                    // On charge l'étape suivante...
                    chargeEtape(parseInt(etapesuivante), id_lot);

                } // FIN Callback
            }); // FIN ajax

            return false;

        } // FIN test touche valider

        // Autre touche numérique : on complète la température

        var temperatureVal = $('input[name=temp]').val().trim();
        if (temperatureVal.length === 0) {
            $('input[name=temp]').val(touche);
            // Si on saisi un point, on vérifie qu'il n'y en a pas déjà un
        } else if (touche === '.' && temperatureVal.indexOf('.') !== -1) {
            return false;
        } else {
            $('input[name=temp]').val(temperatureVal + touche);
        } // FIN saisie touhches numériques

    }); // FIN touche clavier



} // FIN listener étape 7

// Listener de l'étape 8
function listenerEtape8(id_lot) {


    $('.btnIncident').show();

    $('.btn-confirme-reception').off("click.btnconfirmereception").on("click.btnconfirmereception", function(e) {

        e.preventDefault();

        // Validation de la réception du lot
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=actionValideReceptionNegoce&id_lot='+id_lot,
            'callBack': function () {


                $('#etape7').hide('fast');
                $('#etape1').show('fast');
                $('#ticketLot .btnsTicketsLot, #ticketLotContent').hide('fast');
                $('.etapes-suivantes').hide('fast');

                listenerEtape1();

            } // FIN Callback
        }); // FIN ajax

    });

} // FIN listener étape 8


// Listener de l'étape 10
function listenerEtape10(id_lot) {
    "use strict";

    $('.btnMoins').off("click.btnMoins").on("click.btnMoins", function(e) {
        e.preventDefault();
        var champQte = $(this).parents('.input-group').find('input');
        var valQte = parseInt(champQte.val());
        if (isNaN(valQte)) { valQte = 0; }
        if (valQte === 0) { return false; }
        champQte.val(valQte-1);
    });

    $('.btnPlus').off("click.btnPlus").on("click.btnPlus", function(e) {
        e.preventDefault();
        var champQte = $(this).parents('.input-group').find('input');
        var valQte = parseInt(champQte.val());
        if (isNaN(valQte)) { valQte = 0; }
        champQte.val(valQte+1);
    });

    $('.btnValiderCrochets').off("click.btnValiderCrochets").on("click.btnValiderCrochets", function(e) {
        e.preventDefault();

        var crochets_recus = parseInt($('#crochets_recus').val());
        var crochets_rendus = parseInt($('#crochets_rendus').val());
        if (isNaN(crochets_recus) || crochets_recus < 0) { crochets_recus = 0; }
        if (isNaN(crochets_rendus) || crochets_rendus < 0) { crochets_rendus = 0; }

        // On enregitre
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=actionValideCrochets&id_lot=' + id_lot + '&crochets_recus='+crochets_recus+'&crochets_rendus='+crochets_rendus,
            'callBack' : function(etape_suivante) {
                etape_suivante+='';
                chargeEtape(parseInt(etape_suivante), id_lot);
            }
        }); // FIN ajax

    });




} // FIN listener étape 10



// Teste une température
function verifChampTemperature(code_champ) {

    var temperature =  $('input[name=temp'+code_champ+']').val().trim();

    // Si le dernier caractère est un point, on le retire.
    if (temperature.substr(temperature.length - 1) === '.') {
        temperature = temperature.slice(0,-1);
    }

    var regexTemp = new RegExp('^-?[0-9]{1,2}(\\.([0-9]){0,2})?$');

    return regexTemp.test(temperature);

} // FIN fonction

// Listener de la modale pour la composition viande
function compositionViandeListener(objetDomCarte, id_lot) {

    // Clic bouton sous-vide/carcasse (modale)
    $('.btn-viande').off("click.btnviande").on("click.btnviande", function(e) {
        e.preventDefault();

        var valeur = parseInt($(this).val());
        if (isNaN(valeur) || valeur < 1 || valeur > 2) { alert("ERREUR !\r\nIdentification de la composition impossible.\r\nCode erreur : J1BLQWY4"); return false; }

        // Enregistrement en base
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=saveCompisitionViande&id_lot=' + id_lot + '&composition_viande='+valeur,
            'done': function () {
                $('#modalCompositionViande').modal('hide');
                lotSelectionne(objetDomCarte, id_lot);
            }
        }); // FIN ajax
    }); // FIN clic bouton sous-vide/carcasse


} // FIN listener

// Fonction déportée de selection du lot (via clic carte ou modale composition)
function lotSelectionne(objetDomCarte, id_lot) {

    // Data ne détecte pas les modifs, donc on utilise attr pour récupérer l'étape suivante qui peux être mise à jour
    var etape_suivante =  parseInt(objetDomCarte.attr('data-etape-suivante'));

    if (etape_suivante === 0 || isNaN(etape_suivante) || etape_suivante === undefined || !$('#etape'+etape_suivante).length) { return false; }


    // Ajax qui charge le contenu du ticket de lot
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_rcp.php',
        'arguments': 'mode=chargeTicketLot&id_lot=' + id_lot + '&etape=' + etape_suivante,
        'return_id': 'ticketLotContent',
        'done': function () {

            $('#ticketLot .btnsTicketsLot, #ticketLotContent').show('fast');
            $('#etape1').hide('fast');

            // ~~~~~~~~ Déprécié : la couleur est gérée par l'espèce ~~~~~~~~
/*            objetDomCarte.addClass('bg-secondary').removeClass('bg-info');
            objetDomCarte.find('.list-group-item').addClass('bg-secondary').removeClass('bg-info');*/

            ticketLotListener();
            chargeEtape(etape_suivante, id_lot);

        } // FIN Callback
    }); // FIN ajax

} // FIN fonction

/** -------------------------------------------------------------
 * Clavier virtuel - Ne pas changer le noms des champs
 * Ou adapater le fichier jkeyboard.js
 ------------------------------------------------------------- */
function clavierVirtuel() {

    $('#champ_clavier').focus(function() {
        $('#clavier_virtuel').show("slide" ,{ direction: "down"  });
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
                ['numeric_switch', 'layout_switch', 'space', 'return']
            ],
        },
    });

    return true;
}

function saveTemperature(id_lot) {
    // Enregistrement et étape suivante
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_rcp.php',
        'form_id': 'formTemperatures',
        'callBack': function (etapesuivante) {

            if (etapesuivante === undefined || isNaN(parseInt(etapesuivante)) || parseInt(etapesuivante) === 0) { alert("ERREUR !\r\nEnregistrement impossible !\r\nCode erreur : LNV098DU");return false; }

            // On met à jour la carte (invisible) pour la vue suivante, si changement de lot pour ne pas revenir à une vue déjà complétée
            $('#etape1').find('.card[data-id-lot='+id_lot+']').attr('data-etape-suivante', etapesuivante);

            // On met à jour les températures dans le ticket
            var infoTemperatures = '';
            if ($('input[name=temp]').length &&  $('input[name=temp]').val().trim() !== '') {
                infoTemperatures+=  $('input[name=temp]').val() + ' °C';
            } else if ($('input[name=tempd]').length &&  $('input[name=tempd]').val().trim() !== '') {
                infoTemperatures+= '<em>D</em>' + $('input[name=tempd]').val() + ' °C<br>';
                infoTemperatures+= '<em>M</em>' + $('input[name=tempm]').val() + ' °C<br>';
                infoTemperatures+= '<em>F</em>' + $('input[name=tempf]').val() + ' °C';
            }

            $('.ticket-temperatures').html(infoTemperatures);
            if ($('.ticket-temperatures-conteneur').hasClass('ticket-hide')) {
                $('.ticket-temperatures-conteneur').show('fast');
            }

            // On charge l'étape suivante...
            chargeEtape(parseInt(etapesuivante), id_lot);

        } // FIN Callback
    }); // FIN ajax
} // FIN fonction save température


// Listener de la modale teméprature HS
function listenerModaleTempHs(id_lot) {

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
        $('#tempHsCommentaires input[name=id_lot]').val($('#lotid_photo').val());

        // Ajax qui enregistre le commentaire
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'form_id': 'tempHsCommentaires',
            'done': function () {

                // Une fois le commentaire bien enregistré, on enregistre la température
                saveTemperature(id_lot);
                $('#modalConfirmTemp').modal('hide');


            }
        }); // FIN ajax



    }); // FIN clic sur le bouton envoyer commentaire

} // FIN listener modale temp HS

// Listener modale incident (1ere étape)
function modalIncidentFrontListener_1() {

    // Clic sur sélection d'un type d'incident à déclarer
    $('.btnDeclareTypeIncident').off("click.btndeclaretypeincident").on("click.btndeclaretypeincident", function(e) {
        e.preventDefault();

        var type = parseInt($(this).data('type-incident'));
        if (type === undefined || isNaN(type) || type === 0) {
            alert("ERREUR !\r\nIdentification du type d'incident impossible...\r\nCode erreur : ZCODAPM7");
            return false;
        }

        var id_lot = $('#lotid_photo').val();

        if (id_lot.slice(0,1) !== 'N' && (id_lot === undefined || isNaN(parseInt(id_lot)) || parseInt(id_lot) === 0)) {
            alert("ERREUR !\r\nIdentification du lot impossible...\r\nCode erreur : X7J7178W");
            return false;
        }

        $(this).html('<i class="fa fa-spin fa-spinner fa-lg"></i>');

        // On met à jour le contenu de la modale
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=modalDeclareIncident&type='+type+'&id_lot='+id_lot,
            'return_id': 'modalIncidentFrontBody',
            'done': function () {

                modalIncidentFrontListener_2();

            } // FIN Callback
        }); // FIN ajax

    }); // FIN clic choix type d'incident

} // FIN listener modale Incident (1)

// Listener modale incident (2eme étape)
function modalIncidentFrontListener_2() {


    clavierVirtuel();
    $('#champ_clavier').focus();
    $('.btnSaveIncident').removeClass('d-none');
    $('.btnFermerModale').addClass('d-none');

    // Bouton Enregistrement
    $('.btnSaveIncident').off("click.btnSaveIncident").on("click.btnSaveIncident", function(e) {
        e.preventDefault();

        // On vérifie que le commentaire est pas vide...
        var comlen = $('#champ_clavier').val().trim().length;
        if (comlen === 0) { return false; }

        // OK
        $('.btnSaveIncident').attr('disabled', 'disabled');
        $('.btnSaveIncident').html('<i class="fa fa-spin fa-spinner fa-lg"></i>');


        var id_lot = $('#lotid_photo').val();

        if (id_lot.slice(0,1) !== 'N' && (id_lot === undefined || isNaN(parseInt(id_lot)) || parseInt(id_lot) === 0)) {
            alert("ERREUR !\r\nIdentification du lot impossible...\r\nCode erreur : X7J7178W");
            return false;
        }

        // On enregistre...
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'form_id': 'modalIncidentFrontBody',
            'done': function () {

                $('#modalIncidentFront').modal('hide');

                // Ajax qui charge le contenu du ticket de lot
                $.fn.ajax({
                    'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
                    'script_execute': 'fct_vue_rcp.php',
                    'arguments': 'mode=chargeTicketLot&id_lot=' + id_lot,
                    'return_id': 'ticketLotContent',
                    'done': function () {


                        ticketLotListener();


                    } // FIN Callback
                }); // FIN ajax



            } // FIN Callback
        }); // FIN ajax



    }); // FIN bouton save incident

} // FIN listener modale Incident (2)


// Listener étape 11 (Retour palettes)
function listenerEtape11(etape_en_cours) {
    "use strict";

    $('.selectpicker').selectpicker('render');
    if ($('#signature').length) {
        $("#signature").jSignature();
    }

    $('.btnEffacer').off("click.btnEffacer").on("click.btnEffacer", function(e) {
        e.preventDefault();
        $("#signature").jSignature('reset');
    });

    // Retour palettes
    $('#ticketLot .btnAnnulerPalettes').off("click.btnAnnulerPalettes").on("click.btnAnnulerPalettes", function(e) {
        e.preventDefault();

        if (etape_en_cours === undefined) { alert('Echec identification étape !');return false; }

        $('#etape11').hide();
        $('#'+etape_en_cours).show('fast');

        $('.btnRetourPalettes').show();
        $('#ticketLotContent').show();
        $('.btnsTicketsLot').show();
        $('.btnAnnulerPalettes').hide();

        $('#etape11').html('<i class="fa fa-spin fa-spinner fa-2x mt-3"></i>');

    }); // FIN retour palettes

    // Retour palettes
    $('#etape11 .carte-trans').off("click.transporteur").on("click.transporteur", function(e) {
        e.preventDefault();

        var id_trans = parseInt($(this).data('id-trans'));
        if (isNaN(id_trans)) { id_trans = 0; }
        if (id_trans === 0) { alert('Identification transporteur échouée !'); return false; }

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=chargeEtapeRetourPalettes&etape=2&id_trans='+id_trans,
            'return_id': 'etape11',
            'done': function () {

                listenerEtape11(etape_en_cours);

            } // FIN Callback
        }); // FIN ajax

    }); // FIN sélection transporteur

    $('#etape11 .btnMoins').off("click.btnMoins").on("click.btnMoins", function(e) {
        e.preventDefault();
        var champQte = $(this).parents('.input-group').find('input');
        var valQte = parseInt(champQte.val());
        if (isNaN(valQte)) { valQte = 0; }
        if (valQte === 0) { return false; }
        champQte.val(valQte-1);
    });

    $('#etape11 .btnPlus').off("click.btnPlus").on("click.btnPlus", function(e) {
        e.preventDefault();
        var champQte = $(this).parents('.input-group').find('input');
        var valQte = parseInt(champQte.val());
        if (isNaN(valQte)) { valQte = 0; }
        champQte.val(valQte+1);
    });

    $('#etape11 .btnValiderPalettes').off("click.btnValiderPalettes").on("click.btnValiderPalettes", function(e) {
        e.preventDefault();

        var palettes_recues = parseInt($('#palettes_recues').val());
        var palettes_rendues = parseInt($('#palettes_rendues').val());
        if (isNaN(palettes_recues) || palettes_recues < 0) { palettes_recues = 0; }
        if (isNaN(palettes_rendues) || palettes_rendues < 0) { palettes_rendues = 0; }

        if (palettes_recues === 0 && palettes_rendues === 0) { return false; }

        var id_trans =  parseInt($('#etape11 #idTrans').val());
        if (isNaN(id_trans)) { id_trans = 0; }
        if (id_trans === 0) { alert('Identification transporteur échoué !'); return false; }

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=chargeEtapeRetourPalettes&etape=3&id_trans='+id_trans+'&palettes_recues='+palettes_recues+'&palettes_rendues='+palettes_rendues,
            'return_id': 'etape11',
            'done': function () {

                listenerEtape11(etape_en_cours);

            } // FIN Callback
        }); // FIN ajax
    });


    $('#etape11 .btnValideRetPalDate').off("click.btnValideRetPalDate").on("click.btnValideRetPalDate", function(e) {
        e.preventDefault();

        var palettes_recues = parseInt($('#palettes_recues').val());
        var palettes_rendues = parseInt($('#palettes_rendues').val());
        if (isNaN(palettes_recues) || palettes_recues < 0) { palettes_recues = 0; }
        if (isNaN(palettes_rendues) || palettes_rendues < 0) { palettes_rendues = 0; }

        if (palettes_recues === 0 && palettes_rendues === 0) { return false; }

        var id_trans =  parseInt($('#etape11 #idTrans').val());
        if (isNaN(id_trans)) { id_trans = 0; }
        if (id_trans === 0) { alert('Identification transporteur échoué !'); return false; }

        var retpal_jour = parseInt($('.retpal-jour option:selected').val());
        var retpal_mois = parseInt($('.retpal-mois option:selected').val());
        var retpal_an = parseInt($('.retpal-an option:selected').val());

        if (isNaN(retpal_jour)) { retpal_jour = 0; }
        if (isNaN(retpal_mois)) { retpal_mois = 0; }
        if (isNaN(retpal_an)) { retpal_an = 0; }
        if (retpal_jour < 10) { retpal_jour = '0'+retpal_jour;}
        if (retpal_mois < 10) { retpal_mois = '0'+retpal_mois;}

        var date = retpal_an+'-'+retpal_mois+'-'+retpal_jour;

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=chargeEtapeRetourPalettes&etape=4&id_trans='+id_trans+'&palettes_recues='+palettes_recues+'&palettes_rendues='+palettes_rendues+'&date='+date,
            'return_id': 'etape11',
            'done': function () {

                listenerEtape11(etape_en_cours);

            } // FIN Callback
        }); // FIN ajax
    });

    $('#etape11 .btnFin').off("click.btnFin").on("click.btnFin", function(e) {
        e.preventDefault();

        var palettes_recues = parseInt($('#palettes_recues').val());
        var palettes_rendues = parseInt($('#palettes_rendues').val());
        if (isNaN(palettes_recues) || palettes_recues < 0) { palettes_recues = 0; }
        if (isNaN(palettes_rendues) || palettes_rendues < 0) { palettes_rendues = 0; }

        if (palettes_recues === 0 && palettes_rendues === 0) { return false; }

        var id_trans =  parseInt($('#etape11 #idTrans').val());
        if (isNaN(id_trans)) { id_trans = 0; }
        if (id_trans === 0) { alert('Identification transporteur échoué !'); return false; }

        var date = $('#dateRetPal').val();

        var datapair = $("#signature").jSignature("getData", "image");
        var i = new Image();
        i.src = "data:" + datapair[0] + "," + datapair[1];

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=chargeEtapeRetourPalettes&etape=5&id_trans='+id_trans+'&palettes_recues='+palettes_recues+'&palettes_rendues='+palettes_rendues+'&date='+date+'&signature=' + i.src,
            'callBack': function (retour) {
                retour+= '';
                if (parseInt(retour) !== 1) {
                    alert('Echec enregistement !\r\n'+retour);
                    return false;
                }

                // on recharge l'étape où on en était
                $('#'+etape_en_cours).show();
                $('#etape11').html('');
                $('#etape11').hide();
                $('#ticketLotContent').show();
                $('.btnsTicketsLot').show();
                $('.btnRetourPalettes').show();
                $('.btnAnnulerPalettes').hide();

            } // FIN Callback
        }); // FIN ajax
    });


} // FIN listener

// listener étape 2 (sélection transporteur si crochets)
function listenerEtape12(id_lot) {
    "use strict";


    $('#etape12 .carte-trans').off("click.cartetrans").on("click.cartetrans", function(e) {
        e.preventDefault();

        var id_trans = parseInt($(this).data('id-trans'));
        if (isNaN(id_trans)) { id_trans = 0; }
        if (id_trans === 0) { alert('Identification du transporteur échouée !'); }


        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_rcp.php',
            'arguments': 'mode=saveTransporteurCrochets&id_lot='+id_lot+'&id_trans='+id_trans,
            'done': function () {
                chargeEtape(6, id_lot);
            }
        });

    });



} // FIN listener