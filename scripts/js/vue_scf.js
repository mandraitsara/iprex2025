/**
 ------------------------------------------------------------------------
 JS - Vue Scan Frais (mobile)

 Copyright (C) 2020 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2020 Intersed
 @version   1.0
 @since     2020

 ------------------------------------------------------------------------
 */
$('document').ready(function() {
    "use strict";

    selectPalette();
    listenerScan();

    $('#modalConfirmDoublonPdtFraisScan').on('hidden.bs.modal', function (e) {
        $('input[name=cb]').val('');
        $('input[name=cb]').focus();
    });

    $('#modalConfirmSupprPdtFraisScan').on('hidden.bs.modal', function (e) {
        $('input[name=cb]').val('');
        $('input[name=cb]').focus();
    });

    // Toutes les 3 secondes, on vérifie si les scans ont été déchargés, si oui on refresh pour vider l'écran
    setInterval(function() {
        checkDechargeScan();
    }, 3000);

}); // FIN ready


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Charge la liste
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function chargeListeScan() {

    "use strict";
    $('#scfAjaxVue').html(' <i class="fa fa-spin fa-spinner fa-2x mt-3"></i>');
    $('#scanner').removeClass('hid');

    var id_palette = parseInt($('#idPaletteSelectionnee').val());
    if (isNaN(id_palette)) { id_palette = 0; }
    if (id_palette === 0) { alert('Identification de la palette impossible !'); return false;}

    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_scf.php',
        'arguments': 'mode=showInfoPalette&id_palette='+id_palette,
        'return_id': 'infoPalette',
        'done': function () {
            infoPaletteListener();
        }
    }); // FIN ajax

    // Ajax qui charge le contenu de l'étape
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_scf.php',
        'arguments': 'mode=showTotalScan&id_palette='+id_palette,
        'return_id': 'scfAjaxVue',
        'done': function () {
            listenerListeScan();

        } // FIN Callback
    }); // FIN ajax

} // FIN fonction chargeEtape



// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de la liste
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerListeScan() {
    "use strict";
    $('input[name=cb]').val('');
    $('input[name=cb]').focus();

    // Supprimer
    $('.btnSupprFroidCompo').off("click.btnSupprFroidCompo").on("click.btnSupprFroidCompo", function(e) {

        e.preventDefault();

        var id = parseInt($(this).data('id'));
        if (isNaN(id)) { id = 0; }
        if (id === 0) { alert('Erreur de récupération ID'); }

        $('#formSupprProduitFraisScan input[name=id]').val(id);
        $('#modalConfirmSupprPdtFraisScan').modal('show');

    }); // FIN suppr

    // Tout supprimer
    $('.btnToutSupprimer').off("click.btnToutSupprimer").on("click.btnToutSupprimer", function(e) {

        e.preventDefault();

        var ids = $(this).data('ids');

        $('#formSupprPaletteFraisScan input[name=ids]').val(ids);
        $('#modalConfirmSupprPaletteFraisScan').modal('show');

    }); // FIN suppr

    // Supprimer palette vide
    $('.btnSupprimerPalette').off("click.btnSupprimerPalette").on("click.btnSupprimerPalette", function(e) {
        e.preventDefault();

        var id_palette = parseInt($(this).data('id'));
        if (isNaN(id_palette)) { id_palette = 0; }
        if (id_palette === 0) { alert('Palette non identifiée !'); return false; }

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_scf.php',
            'arguments': 'mode=supprPalette&id_palette='+id_palette,
            'done': function () {
                $('#infoPalette').html('');
                $('#scanner').addClass('hid');
                selectPalette();
            } // FIN Callback
        }); // FIN ajax

    });

} // FIN listner de la liste

function verifCaracteresCodeBarre() {
    "use strict";

    var cb = $('input[name=cb]').val();

    cb = cb.replace(/&/g, '1');
    cb = cb.replace(/é/g, '2');
    cb = cb.replace(/"/g, '3');
    cb = cb.replace(/'/g, '4');
    cb = cb.replace(/\(/g, '5');
    cb = cb.replace(/-/g, '6');
    cb = cb.replace(/è/g, '7');
    cb = cb.replace(/_/g, '8');
    cb = cb.replace(/ç/g, '9');
    cb = cb.replace(/à/g, '0');

    $('input[name=cb]').val(cb);

}

function addProduitFrais() {
    "use strict";

    var cb = $('input[name=cb]').val();
    if (cb === '') { return true; }

    // On vérifie s'il y a doublon...
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_scf.php',
        'arguments': 'mode=verifDoublon&cb=' + cb,
        'callBack': function (retour) {
            retour+= '';

            $('#messageScanAdd').html('');
            $('input[name=cb]').val('');

            // Si doublon
            if (parseInt(retour) !== 1) {

                // Si le scan en doublon a été fait il y a moins d'une seconde on n'intègre pas
                // (debug parfois scan en double remontée Jim du 03/03/2021)
                /*$('#cbDoublon').val(cb);
                $('#modalConfirmDoublonPdtFraisScan').modal('show');*/
                return false;

                // Pas de doublon
            } else {

                goAddScan(cb);

            } // FIN test doublon

        } // FIN Callback
    }); // FIN ajax

} // FIN fonction

function goAddScan(cb) {
    "use strict";

    var id_palette = parseInt($('#idPaletteSelectionnee').val());
    if (isNaN(id_palette)) { id_palette = 0; }
    if (id_palette === 0) { alert('Identification de la palette impossible !'); return false;}

    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_scf.php',
        'arguments': 'mode=addPdtFroidByCodeBarre&cb=' + cb + '&id_palette=' + id_palette,
        'return_id': 'messageScanAdd',
        'done': function () {

            $('input[name=cb]').val('');
            $('input[name=cb]').focus();
            chargeListeScan();

        } // FIN Callback
    }); // FIN ajax

} // FIN fonction

function selectPalette() {
    "use strict";
    $('#scfAjaxVue').html(' <i class="fa fa-spin fa-spinner fa-2x mt-3"></i>');

    // Ajax qui charge le contenu de l'étape

    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_scf.php',
        'arguments': 'mode=showSelectPalette',
        'return_id': 'scfAjaxVue',
        'done': function () {
            listenerSelectPalette();

        } // FIN Callback
    }); // FIN ajax
}

function listenerScan() {
    "use strict";
    var timerCb;
    $('input[name=cb]').keyup(function () {
        clearTimeout(timerCb);
        timerCb = setTimeout(function () {
            verifCaracteresCodeBarre();
            addProduitFrais();
        }, 200);
    });

    // Confirmation suppression (modale)
    $('.btnTestCb').off("click.btnTestCb").on("click.btnTestCb", function(e) {
        e.preventDefault();

        // CB = Position 2 à 15 =  EAN (sur 14 digits)
        // CB.= Position 34 à la fin =  Numéro de lot
        // Exemple DEV :  xx93700293524961xxxxxxxxxxxxxxxx201214DIR094
        var cbTest = 'xx93700293502977xxxx123456xxxxxxxx203915AFR094';
        //var cbTest = 'xx93700293502977xxxx123456xxxxxxxxPTCMBA';
        //var cbTest = 'xx93700293502977xxxx123456xxxxxxxxC33';
        $('input[name=cb]').val(cbTest);
        addProduitFrais();
    });

    // Confirmation suppression (modale)
    $('#messageScanAdd').off("click.messageScanAdd").on("click.messageScanAdd", function(e) {
        e.preventDefault();

        $(this).html('');
        $('input[name=cb]').focus();
    });

    // Confirmation suppression (modale)
    $('.btnConfirmSupprPdtFraisScan').off("click.btnConfirmSupprPdtFraisScan").on("click.btnConfirmSupprPdtFraisScan", function(e) {

        e.preventDefault();
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_scf.php',
            'form_id': 'formSupprProduitFraisScan',
            'done': function () {
                $('#modalConfirmSupprPdtFraisScan').modal('hide');
                $('#formSupprProduitFraisScan input[name=id]').val(0);
                chargeListeScan();
            } // FIN Callback
        }); // FIN ajax

    });

    // Confirmation suppression tout (modale)
    $('.btnConfirmSupprPaletteFraisScan').off("click.btnConfirmSupprPaletteFraisScan").on("click.btnConfirmSupprPaletteFraisScan", function(e) {

        e.preventDefault();
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_scf.php',
            'form_id': 'formSupprPaletteFraisScan',
            'done': function () {
                $('#modalConfirmSupprPaletteFraisScan').modal('hide');
                $('#formSupprPaletteFraisScan input[name=ids]').val('');
                chargeListeScan();
            } // FIN Callback
        }); // FIN ajax

    });

    // Confirmation doublon (modale)
    $('.btnConfirmDoublonPdtFraisScan').off("click.btnConfirmDoublonPdtFraisScan").on("click.btnConfirmDoublonPdtFraisScan", function(e) {

        e.preventDefault();

        $('#modalConfirmDoublonPdtFraisScan').modal('hide');
        var cb = $('#cbDoublon').val();
        if (cb === '' || parseInt(cb) === 0 ) { alert('Récupération CB impossible !'); return false;}
        goAddScan(cb);

    });

}

function listenerSelectPalette() {
    "use strict";
    // nouvelle palette
    $('.btnNouvellePalette').off("click.btnNouvellePalette").on("click.btnNouvellePalette", function(e) {

        e.preventDefault();

        $('#scfAjaxVue').html(' <i class="fa fa-spin fa-spinner fa-2x mt-3"></i>');

        // Ajax qui charge le contenu de l'étape

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_scf.php',
            'arguments': 'mode=showSelectClient',
            'return_id': 'scfAjaxVue',
            'done': function () {
                listenerSelectClient();

            } // FIN Callback
        }); // FIN ajax

    }); // FIN nouvelle palette

    // Sélection palette
    $('.btnSelectPalette').off("click.btnSelectPalette").on("click.btnSelectPalette", function(e) {

        e.preventDefault();

        var id_palette = parseInt($(this).data('id'));
        if (isNaN(id_palette)) { id_palette = 0; }
        if (id_palette === 0) { alert('Identification de la palette impossible !');return false; }

        $('#idPaletteSelectionnee').val(id_palette);
        chargeListeScan();

    }); // FIn selection palette

} // FIn listener


function listenerSelectClient() {
    "use strict";
    $('.btnRetourSelectPalette').off("click.btnRetourSelectPalette").on("click.btnRetourSelectPalette", function(e) {

        e.preventDefault();
        selectPalette();
    });

    $('.btnSelectClient').off("click.btnSelectClient").on("click.btnSelectClient", function(e) {

        e.preventDefault();

        var id_client = parseInt($(this).data('id'));
        if (isNaN(id_client)) { id_client = 0; }
        if (id_client === 0) { alert('Identification du client impossible !');return false; }

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_palettes.php?skipAuth',
            'arguments': 'mode=creationNouvellePaletteFrais&id_client='+id_client,
            'done': function () {
                selectPalette();
            } // FIN Callback
        }); // FIN ajax

    });

} // FIN listener


function infoPaletteListener() {
    "use strict";

    $('.btnChangePalette').off("click.btnChangePalette").on("click.btnChangePalette", function(e) {

        e.preventDefault();

        $('#infoPalette').html('');
        $('#scanner').addClass('hid');

        selectPalette();
    });

} // FIN fonction

function checkDechargeScan() {
    "use strict";

    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_scf.php',
        'arguments': 'mode=checkDechargeScan',
        'callBack': function (retour) {
            retour+='';
            if (parseInt(retour) === 1) {
                $('#infoPalette').html('');
                $('#scanner').addClass('hid');
                selectPalette();
            }
        } // FIN Callback
    }); // FIN ajax

} // FIN fonction