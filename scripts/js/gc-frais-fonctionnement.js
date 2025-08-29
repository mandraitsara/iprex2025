/**
 ------------------------------------------------------------------------
 JS - Frais de fonctionnements

 Copyright (C) 2020 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2020 Intersed
 @version   1.0
 @since     2020

 ------------------------------------------------------------------------
 */
$(document).ready(function() {
    "use strict";

    chargeListeFraisFonctionnement();

    $('#modaleFraisFonctionnement').on('hide.bs.modal', function (e) {
        $('#modaleFraisFonctionnementBody').html('<i class="fa fa-spin fa-spinner"></i>');
        $('#modaleFraisFonctionnement .btnDelFrais').show();
    });

    // Nouveau
    $('.btnCreerFrais').off("click.btnCreerFrais").on("click.btnCreerFrais", function(e) {
        e.preventDefault();

        $('#modaleFraisFonctionnement .btnDelFrais').hide();
        $('#modaleFraisFonctionnement').modal('show');

        $.fn.ajax({
            script_execute: 'fct_frais_fonctionnements.php',
            arguments: 'mode=showModaleFrais',
            return_id:'modaleFraisFonctionnementBody',
            done : function () {

                modaleFraisFonctionnementListener();

            } // FIN callBack
        }); // FIN ajax

    }); // FIN créer frais

}); // FIN ready


// Listener de la modale add/upd
function modaleFraisFonctionnementListener() {
    "use strict";

    $('.selectpicker').selectpicker('render');

    // Save
    $('.btnSaveFrais').off("click.btnSaveFrais").on("click.btnSaveFrais", function(e) {
        e.preventDefault();

        var nom = cleanCharsToAjax($('#modaleFraisFonctionnementBody input[name=nom]').val());
        $('#modaleFraisFonctionnementBody input[name=nom]').val(nom);
        if (nom === '') { return false; }
        var montant = parseFloat($('#modaleFraisFonctionnementBody input[name=montant]').val());
        if (isNaN(montant) || montant <= 0) { return false; }

        $.fn.ajax({
            script_execute: 'fct_frais_fonctionnements.php',
            form_id: 'modaleFraisFonctionnementBody',
            callBack : function (retour) {
                retour+='';

                if (parseInt(retour) !== 1) {
                    alert("Echec de l'enregistrement !\r\n"+retour);
                    return false;
                }

                $('#modaleFraisFonctionnement').modal('hide');
                chargeListeFraisFonctionnement();

            } // FIN callBack
        }); // FIN ajax
    }); // FIN save


    // Suppr
    $('.btnDelFrais').off("click.btnDelFrais").on("click.btnDelFrais", function(e) {
        e.preventDefault()

        var id = parseInt($('#modaleFraisFonctionnementBody input[name=id]').val());
        if (isNaN(id)) { id = 0;}
        if (id === 0) { alert("Identification du frais échouée !"); return false; }

        if (!confirm("Supprimer ce frais de fonctionnement ?")) { return false; }

        $.fn.ajax({
            script_execute: 'fct_frais_fonctionnements.php',
            arguments: 'mode=supprFrais&id='+id,
            callBack : function (retour) {
                retour+= '';
                if (parseInt(retour) !== 1) {
                    alert("Echec de la suppression !\r\n"+retour);
                    return false;
                }

                $('#modaleFraisFonctionnement').modal('hide');
                chargeListeFraisFonctionnement();

            } // FIN callBack
        }); // FIN ajax

    }); // FIN suppr


} // FIN listener

// Charge la liste
function chargeListeFraisFonctionnement() {
    "use strict";

    $('#listeFraisFonctionnement').html('<i class="fa fa-spin fa-spinner"></i>');

    $.fn.ajax({
        script_execute: 'fct_frais_fonctionnements.php',
        arguments: 'mode=showListeFrais',
        return_id:'listeFraisFonctionnement',
        done : function () {

            listeFraisFonctionnementListener();

        } // FIN callBack
    }); // FIN ajax

} // FIN fonction

// Listener de la liste des frais de fonctionnement
function listeFraisFonctionnementListener() {
    "use strict";

    $('.togglemaster').bootstrapToggle();

    // Upd
    $('.btnEditFrais').off("click.btnEditFrais").on("click.btnEditFrais", function(e) {
        e.preventDefault();

        var id = parseInt($(this).parents('tr').find('code').text());
        if (isNaN(id)) { id = 0;}
        if (id === 0) { alert("Identification du frais échouée !"); return false; }

        $('#modaleFraisFonctionnement').modal('show');

        $.fn.ajax({
            script_execute: 'fct_frais_fonctionnements.php',
            arguments: 'mode=showModaleFrais&id='+id,
            return_id:'modaleFraisFonctionnementBody',
            done : function () {

                modaleFraisFonctionnementListener();

            } // FIN callBack
        }); // FIN ajax
    }); // FIN upd

    // Changement de l'activation
    $('.togglemaster[name=activation]').change(function() {

        var active  = $(this).prop('checked') === true ? 1 : 0;
        var id = parseInt($(this).parents('tr').find('code').text());
        if (isNaN(id)) { id = 0;}
        if (id === 0) { alert("Identification du frais échouée !"); return false; }

        // Save
        $.fn.ajax({

            'script_execute': 'fct_frais_fonctionnements.php',
            'arguments': 'mode=switchActivationFrais&id=' + id + '&active='+active,
            'callBack': function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) {
                    alert('ERREUR !\r\nUne erreur est survenue...\r\n'+retour);
                }
            } // FIN callback
        }); // FIN aJax
    }); // Fin changement de l'activation


} // FIN listener