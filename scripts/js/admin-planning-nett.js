/**
 ------------------------------------------------------------------------
 JS

 Copyright (C) 2020 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2020 Intersed
 @version   1.0
 @since     2018

 ------------------------------------------------------------------------
 */
$(document).ready(function() {
    "use strict";

    // Affiche la liste
    chargeListe();

    $('.btnRecherche').click(function () {
        chargeListe();
    });


    // Nettoyage du contenu de la modale à sa fermeture
    $('#modalAddUpd').on('hidden.bs.modal', function (e) {

        $('#modalAddUpdTitre').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#modalAddUpdBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        $('#modalAddUpd .modal-dialog.modal-xxl').removeClass('modal-xxl').addClass('modal-md');
        $('#modalAddUpd .btnSupprimer').show();

    }); // FIN fermeture modale

    $('.btnAddNew').off("click.btnAddNew").on("click.btnAddNew", function(e) {
        e.preventDefault();

        $('#modalAddUpd .btnSupprimer').hide();
        $.fn.ajax({
            script_execute: 'fct_planning_nettoyage.php',
            arguments: 'mode=chargeModaleNewPlanning',
            callBack: function(retour) {

                var retours = retour.toString().split('^');
                var titre = retours[0];
                var body = retours[1];

                $('#modalAddUpdTitre').html(titre);
                $('#modalAddUpdBody').html(body);

                $('#modalAddUpd').modal('show');

                modaleAddUpdListener();

            } // FIN Callback
        }); // FIN ajax
    });

    $('.btnExportPdf').off("click.btnExportPdf").on("click.btnExportPdf", function(e) {
        e.preventDefault();

        var objetDomBtnExport = $(this);
        objetDomBtnExport.find('i').removeClass('fa-file-pdf').addClass('fa-spin fa-spinner');
        objetDomBtnExport.attr('disabled', 'disabled');

        $('#filtres input[name=mode]').val('exportPdf');

        $.fn.ajax({
            script_execute: 'fct_planning_nettoyage.php',
            form_id: 'filtres',
            callBack: function (url_fichier) {
                url_fichier+='';
                $('#filtres input[name=mode]').val('showListePlannings');

                objetDomBtnExport.find('i').removeClass('fa-spin fa-spinner').addClass('fa-file-pdf');
                objetDomBtnExport.prop('disabled', false);
                var url = url_fichier.trim();                
                if (url.slice(0,4) !== 'http') { alert('ERREUR\r\nLe fichier n\'a pas pu être généré !');return false; }
                $('#lienPdf').attr('href', url_fichier);
                $('#lienPdf')[0].click();

            } // FIN Callback
        }); // FIN ajax
    });


    // Nettoyage du contenu de la modale  à sa fermeture
    $('#modalHeuresAlarmes').on('hidden.bs.modal', function (e) {
        $('#modalHeuresAlarmesBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    }); // FIN fermeture modale


    // Chargement du contenu de la modale documents du lot
    $('#modalHeuresAlarmes').on('show.bs.modal', function (e) {
        $.fn.ajax({
            script_execute: 'fct_planning_nettoyage.php',
            arguments: 'mode=modalHeuresAlarme',
            return_id: 'modalHeuresAlarmesBody',
            done:function () {
                modaleHeuresAlarmesListener();
            }
        });
    });

}); // FIN ready

function modaleHeuresAlarmesListener() {
    "use strict";

    $('.selectpicker').selectpicker('render');

    $('#modalHeuresAlarmes select').on('change', function () {
        if (parseInt($(this).val()) === -1) {
            $(this).parents('.col').addClass('hid');
        }
    });


    $('#modalHeuresAlarmes .btnSaveHeures').off("click.btnValidation").on("click.btnValidation", function(e) {
        e.preventDefault();
        $.fn.ajax({
            script_execute: 'fct_planning_nettoyage.php',
            form_id: 'modalHeuresAlarmesBody',
            callBack:function (retour) {
                retour+= '';
                if (parseInt(retour) !== 1) { alert('Une erreur est survenue !\r\n'+retour); return false;}
                $('#modalHeuresAlarmes').modal('hide');
            }
        });
    });

} // FIn listener

/** **********************
 * Affiche la liste
 ********************** */
function chargeListe() {
    "use strict";

    $.fn.ajax({
        script_execute: 'fct_planning_nettoyage.php',
        form_id: 'filtres',
        return_id: 'liste',
        done: function () {

            listeListener();

        } // FIN Callback
    }); // FIN ajax

    return true;

} // FIN fonction


/** ************************
 * Listener de la liste
 ************************ */
function listeListener() {
    "use strict";


    $('.btnEdit').off("click.btnEdit").on("click.btnEdit", function(e) {
        e.preventDefault();

        var id = parseInt($(this).data('id'));
        if (isNaN(id)) { id = 0; }
        if (id === 0) { alert('Identification ID échouée !'); return false; }

        $.fn.ajax({
            script_execute: 'fct_planning_nettoyage.php',
            arguments: 'mode=chargeModaleUpdNettLocal&id='+id,
            callBack: function (retour) {
                retour+= '';
                var donnees = retour.split('^');
                if (donnees[0] !== undefined) { $('#modalAddUpdTitre').html(donnees[0]); }
                if (donnees[1] !== undefined) { $('#modalAddUpdBody').html(donnees[1]); }

                $('#modalAddUpd .modal-dialog.modal-md').removeClass('modal-md').addClass('modal-xxl');
                $('#modalAddUpd').modal('show');
                modaleEditNettLocalListener();


            } // FIN Callback
        }); // FIN ajax
    });


} // FIN fonction


/** **************************
 * Listener de la modale Add
 ************************** */
function modaleAddUpdListener() {
    "use strict";

    $('.selectpicker').selectpicker('render');

    $('.btnSave').off("click.btnSave").on("click.btnSave", function(e) {
        e.preventDefault();


        var ln = loadingBtn($(this));

        $.fn.ajax({
            script_execute: 'fct_planning_nettoyage.php',
            form_id: 'modalAddUpdBody',
            callBack: function (id) {
                id+= '';
                removeLoadingBtn(ln);
                if (parseInt(id) === 0) { alert('ERREUR !\r\nEchec de l\'enregistement...'); return false; }
                $('#modalAddUpd').modal('hide');
                chargeListe();


                $.fn.ajax({
                    script_execute: 'fct_planning_nettoyage.php',
                    arguments: 'mode=chargeModaleUpdNettLocal&id='+id,
                    callBack: function (retour) {
                        retour+= '';
                        var donnees = retour.split('^');
                        if (donnees[0] !== undefined) { $('#modalAddUpdTitre').html(donnees[0]); }
                        if (donnees[1] !== undefined) { $('#modalAddUpdBody').html(donnees[1]); }

                        $('#modalAddUpd .modal-dialog.modal-md').removeClass('modal-md').addClass('modal-xxl');
                        $('#modalAddUpd').modal('show');
                        modaleEditNettLocalListener();


                    } // FIN Callback
                }); // FIN ajax

            } // FIN Callback
        }); // FIN ajax
    }); // FIN SAVE



} // FIN fonction

/** *************************************
 * Listener de la modale Edit Nett Local
 ************************************* */
function modaleEditNettLocalListener() {
    "use strict";

    alerteVerbose();

    $('.selectpicker').selectpicker('render');

    $('.btnSave').off("click.btnSave").on("click.btnSave", function(e) {
        e.preventDefault();

        var ln = loadingBtn($(this));

        $.fn.ajax({
            script_execute: 'fct_planning_nettoyage.php',
            form_id: 'modalAddUpdBody',
            callBack: function (retour) {
                retour+= '';
                removeLoadingBtn(ln);
                if (parseInt(retour) !== 1) { alert('ERREUR !\r\nEchec de l\'enregistement...\r\nCode erreur : '+retour); return false; }
                $('#modalAddUpd').modal('hide');
                chargeListe();
            } // FIN Callback
        }); // FIN ajax
    });

    $('.btnSupprimer').off("click.btnSupprimer").on("click.btnSupprimer", function(e) {
        e.preventDefault();

        var id = parseInt($('#modalAddUpdBody input[name=id]').val());
        if (isNaN(id)) { id = 0; }
        if (id === 0) { alert('Identification ID échouée !');return false;}

        if (!confirm("Supprimer ce planning de nettoyage ?")) { return false; }

        var ln = loadingBtn($(this));

        $.fn.ajax({
            script_execute: 'fct_planning_nettoyage.php',
            arguments: 'mode=supprNettoyageLocal&id='+id,
            callBack: function (retour) {
                retour+= '';
                removeLoadingBtn(ln);
                if (parseInt(retour) !== 1) { alert('ERREUR !\r\nEchec de l\'enregistement...\r\nCode erreur : '+retour); return false; }
                $('#modalAddUpd').modal('hide');
                chargeListe();
            } // FIN Callback
        }); // FIN ajax
    });




    $('.alertes-nett .selectpicker').on('change', function () {
        alerteVerbose();
    }); // FIN Verbose

    $('.btnNoAlerte').off("click.btnNoAlerte").on("click.btnNoAlerte", function(e) {
        $('select.alerte_mois').selectpicker('deselectAll');
        $('select.alerte_semaine').selectpicker('deselectAll');
        $('select.alerte_jour').selectpicker('deselectAll');
        $('select[name=alerte_heure]').selectpicker('val', 0);
        $('select[name=alerte_minutes]').selectpicker('val', 0);
        $('#alerteVerbose').text('Aucune alerte');
        $(this).addClass('hid');
    });

} // FIN listener

// Fonction déportée alerte verbose
function alerteVerbose() {
    "use strict";
    $('.selectpicker').selectpicker();
    var verbose = '';
    var mois    = $('select.alerte_mois').val();
    var semaine = $('select.alerte_semaine').val();
    var jour    = $('select.alerte_jour').val();
    var i       = 0;
    var sep     = '';

    if (jour.length === 0) {
        verbose = 'Tous les jours';
    } else if (jour.length === 1) {
        verbose = 'Tous les ';
        if (semaine.length === 1) {
            if (parseInt($('select.alerte_semaine').val()) === 1) {
                verbose+= 'premiers ';
            } else if (parseInt($('select.alerte_semaine').val()) === 2) {
                verbose+= 'deuxièmes ';
            } else if (parseInt($('select.alerte_semaine').val()) === 3) {
                verbose+= 'troisièmes ';
            } else {
                verbose+= 'derniers ';
            }
        } else if  (semaine.length > 1) {
            i = 0;
            $.each(semaine,function(k, v) {
                i++;
                sep = i === semaine.length - 1 ? ' et ' : ', ';
                if (i === semaine.length) { sep = ' '; }
                if (parseInt(v) === 1) {
                    verbose+= 'premiers'+sep;
                } else if (parseInt(v) === 2) {
                    verbose+= 'deuxièmes'+sep;
                } else if (parseInt(v) === 3) {
                    verbose+= 'troisièmes'+sep;
                } else {
                    verbose+= 'derniers'+sep;
                }
            });
        }
        verbose+= $('select.alerte_jour option:selected').text().toLowerCase()+'s';
    } else if (jour.length > 1) {
        verbose = 'Tous les ';
        if (semaine.length === 1) {
            if (parseInt($('select.alerte_semaine').val()) === 1) {
                verbose+= 'premiers ';
            } else if (parseInt($('select.alerte_semaine').val()) === 2) {
                verbose+= 'deuxièmes ';
            } else if (parseInt($('select.alerte_semaine').val()) === 3) {
                verbose+= 'troisièmes ';
            } else {
                verbose+= 'derniers ';
            }
        } else if  (semaine.length > 1) {
            i = 0;
            $.each(semaine,function(k, v) {
                i++;
                sep = i === semaine.length - 1 ? ' et ' : ', ';
                if (i === semaine.length) { sep = ' '; }
                if (parseInt(v) === 1) {
                    verbose+= 'premiers'+sep;
                } else if (parseInt(v) === 2) {
                    verbose+= 'deuxièmes'+sep;
                } else if (parseInt(v) === 3) {
                    verbose+= 'troisièmes'+sep;
                } else {
                    verbose+= 'derniers'+sep;
                }
            });
        }
        i = 0;
        $.each(jour,function(k, v) {
            i++;
            sep = i === jour.length - 1 ? ' et ' : ', ';
            if (i === jour.length) { sep = ' '; }
            verbose+= $('select.alerte_jour option[value='+v+']').text().toLowerCase()+'s'+sep;
        });
    }
    if (semaine.length > 0 && mois.length === 0) {
        verbose+= ' du mois ';
    } else if (mois.length === 1) {
        verbose+= ' de '+$('select.alerte_mois option:selected').text().toLowerCase();
    } else if (mois.length > 1) {
        verbose+= ' de ';
        i = 0;
        $.each(mois,function(k, v) {
            i++;
            sep = i === mois.length - 1 ? ' et ' : ', ';
            if (i === mois.length) { sep = ' '; }
            verbose+= $('select.alerte_mois option[value='+v+']').text().toLowerCase()+sep;
        });
    }
    verbose+= ' à ' + $('select[name=alerte_heure] option:selected').text() + ':'+$('select[name=alerte_minutes] option:selected').text();

    if (
        $('select.alerte_semaine').val().length > 0 &&
        $('select.alerte_jour').val().length === 0 ) {
        verbose = 'Paramétrage non cohérent, précisez le jour.';
        if (!$('.btnNoAlerte').hasClass('hid')) { $('.btnNoAlerte').addClass('hid');  }
    } else if (
        $('select.alerte_mois').val().length === 0 &&
        $('select.alerte_semaine').val().length === 0 &&
        $('select.alerte_jour').val().length === 0 &&
        parseInt($('select[name=alerte_heure]').val()) === 0 &&
        parseInt($('select[name=alerte_minutes]').val()) === 0) {
        verbose = 'Aucune alerte';
        if (!$('.btnNoAlerte').hasClass('hid')) { $('.btnNoAlerte').addClass('hid');  }
    } else if ($('.btnNoAlerte').hasClass('hid')) { $('.btnNoAlerte').removeClass('hid');  }


    $('#alerteVerbose').text(verbose);
} // FIN fonction