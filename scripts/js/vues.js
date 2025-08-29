/**
 ------------------------------------------------------------------------
 JS - Vues - général

 Copyright (C) 2018 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2018 Intersed
 @version   1.0
 @since     2018

 ------------------------------------------------------------------------
 */
$(document).ready(function(e) {
    "use strict";

    // Nettoyage du contenu de la modale Communication à sa fermeture
    $('#modalCommunicationFront').on('hidden.bs.modal', function (e) {
        $('#modalCommunicationFrontBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    }); // FIN fermeture modale Communication

    // Nettoyage du contenu de la modale Com à sa fermeture
    $('#modalCom').on('hidden.bs.modal', function (e) {
        $('#modalComBody').attr('src', '');
        $('#modalComBody').hide();
    }); // FIN fermeture modale Communication

    // Nettoyage du contenu de la modale Commenantaires à sa fermeture
    $('#modalCommentairesFront').on('hidden.bs.modal', function (e) {
        $('#modalCommentairesFrontBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');

        $('.btnAddCommentaire.d-none').removeClass('d-none');
        if (!$('.btnSaveNewCommentaire').hasClass('d-none')) {
            $('.btnSaveNewCommentaire').addClass('d-none');
        }
    }); // FIN fermeture modale Communication

    // Reset de l'id user a la fermeture de la modale PlanNett
    $('#modalPlanNett').on('hidden.bs.modal', function (e) {
        $('#modalPlanNettBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    }); // FIN fermeture modale Plannnet

    // Reset de l'id user a l'ouverture de la modale PlanNett
    $('#modalPlanNett').on('show.bs.modal', function (e) {
        $('#modalPlanNettIdUser').val('0');
    }); // FIN ouverture modale Plannnet


    $('.btn-plannnet').off("click.btnplannnet").on("click.btnplannnet", function(e) {
        e.preventDefault();

        $('#modalPlanNett').modal('show');

        var vue = $('#menuVues').data('code');
        if (vue === undefined) { vue = ''; }

        chargePlannNet(vue);
    });

    // Chargement du contenu de la modale Communication avant son ouverture
    $('#modalCommunicationFront').on('show.bs.modal', function (e) {

        e.stopImmediatePropagation();

        // On récupère le code de la vue
        var vue_code = e.relatedTarget.attributes['data-vue-code'] === undefined ? '' : e.relatedTarget.attributes['data-vue-code'].value;
        if (vue_code === undefined || vue_code === '') {
            $('#modalCommunicationFrontBody').html('<b>ERREUR</b><p>Identification de la vue impossible...</p><p>Code erreur : <code>QXYRBZJY</code></p>');
        }

        // On récupère le contenu de la modale
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_communications.php',
            'arguments': 'mode=modalCommunicationVue&vue_code='+vue_code,
            'return_id': 'modalCommunicationFrontBody',
            'done': function() {

                listeDocsListener();

            } // FIN callback
        }); // FIN aJax

    }); // Fin chargement du contenu de la modale Communication



    // Chargement du contenu de la modale des commentaires du lot (incidents...)
    $('#modalCommentairesFront').on('show.bs.modal', function (e) {

        e.stopImmediatePropagation();

        // On récupère l'id lot
        var id_lot = e.relatedTarget.attributes['data-id-lot'] === undefined ? 0 : e.relatedTarget.attributes['data-id-lot'].value;

        if (id_lot.slice(0,1) !== 'N' && (id_lot === undefined || isNaN(id_lot) || id_lot === 0)) {
            $('#modalCommentairesFrontBody').html('<b>ERREUR</b><p>Identification du lot impossible...</p><p>Code erreur : <code>C5LF9DFV</code></p>');
        }

        // On récupère le contenu de la modale
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_lots.php',
            'arguments': 'mode=modalCommentairesFront&id_lot='+id_lot,
            'return_id': 'modalCommentairesFrontBody',
            'done': function() {

                $('input[name=id_lot_cominc]').val(id_lot);

                modalCommentairesFrontListener_1();

            } // FIN callback
        }); // FIN aJax

    }); // Fin chargement du contenu de la modale des commentaires du lot

    // Toutes les minutes, on vérifie si c'est l'heure d'afficher la modale d'alerte
    checkAlerte();
    setInterval(function() {
        checkAlerte();
    }, 60 * 1000);

}); // FIN ready



// Listener commentaires front modale (infos)
function modalCommentairesFrontListener_1() {

    // Ajout d'un autre commentaire sur le lot
    $('.btnAddCommentaire').off("click.btnAddCommentaire").on("click.btnAddCommentaire", function(e) {
        e.preventDefault();

        var id_lot= $('input[name=id_lot_cominc]').val();

        if (id_lot.slice(0,1) !== 'N' && (id_lot === undefined || isNaN(id_lot) || id_lot === 0)) {
            $('#modalCommentairesFrontBody').html('<b>ERREUR</b><p>Identification du lot impossible...</p><p>Code erreur : <code>BLOWRPED</code></p>');
            return false;
        }


        // transformation de la modale pour saisie nouveau commentaire sur le lot
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_lots.php',
            'arguments': 'mode=modalCommentairesFrontAdd&id_lot='+id_lot,
            'return_id': 'modalCommentairesFrontBody',
            'done': function() {

                // On ouvre le clavier virtuel
                clavierVirtuel();
                $('#champ_clavier').focus();

                // On masque le bouton compléter et on affiche le bouton "enregistrer"
                $('.btnAddCommentaire').addClass('d-none');
                $('.btnSaveNewCommentaire').removeClass('d-none');


                modalCommentairesFrontListener_2();



            } // FIN callback
        }); // FIN aJax

    }); // Fin bouton ajout nouveau commentaire incident


} // FIN listener COmmentaires front


// Listener commentaires front modale (saisie du texte)
function modalCommentairesFrontListener_2() {

    // Enregistre le nouveau commentaire
    $('.btnSaveNewCommentaire').off("click.btnAddCommentaire").on("click.btnAddCommentaire", function(e) {
        e.preventDefault();

        // Si commentaire vide, on n'enregistre pas (normal, non ?)
        var comlen = parseInt($('#champ_clavier').val().trim().length);
        if (comlen === 0) { return false; }

        // Ajax qui enregistre le commentaire
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_lots.php',
            'form_id': 'modalCommentairesFrontBody',
            'callBack': function (retour) {

                // Gestion des codes d'erreur en callback du PHP
                if (parseInt(retour) === -1 ) {
                    $('#modalCommentairesFrontBody').html('<b>ERREUR</b><p>Identification du lot impossible...</p><p>Code erreur : <code>GTDODISS</code></p>');
                    return false;
                }
                if (parseInt(retour) === -2 ) {
                    $('#modalCommentairesFrontBody').html('<b>ERREUR</b><p>Récupération du commentaire impossible...</p><p>Code erreur : <code>RZHEJPGA</code></p>');
                    return false;
                }
                if (parseInt(retour) === 0 ) {
                    $('#modalCommentairesFrontBody').html('<b>ERREUR</b><p>Enregistrement en BDD échoué !</p><p>Code erreur : <code>XSDTIDZQ</code></p>');
                    return false;
                }

                // Tout s'est bien passé, on peut fermer la modale. Zou !
                $('#modalCommentairesFront').modal('hide');

            }
        }); // FIN ajax

    }); // FIN enregistre le nouveau commentaire


} // FIN listener COmmentaires front 2 (saisie du texte)

// Listener de la liste des dossiers
function listeDocsListener() {

    // Selection dossier
    $('.btnDossier').off("click.btnDossier").on("click.btnDossier", function(e) {
        e.preventDefault();

        var dossier = $(this).data('dossier');
        if (dossier === undefined) { dossier = '/'; }


        var vue_code = $(this).data('vue-code');

        // On récupère le contenu de la modale
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_communications.php',
            'arguments': 'mode=modalCommunicationVue&vue_code='+vue_code+'&dossier='+dossier,
            'return_id': 'modalCommunicationFrontBody',
            'done': function() {

                listeDocsListener();

            } // FIN callback
        }); // FIN aJax

    }); // FIN selection dossier


    $('.btnShowCom').off("click.btnShowCom").on("click.btnShowCom", function(e) {
        e.preventDefault();
        var href = $(this).data('href');
        $('#modalComBody').show();
        $('#modalComBody').attr('src', href);
        $('#modalCom').modal('show');

    });

} // FIN listener de la liste des dossiers


// Listener modale plannning nettoyage
function modalePlanNettListner() {
    "use strict";

    var zoneHeader = $('.table-plannett').data('zone');
    if (zoneHeader !== '' && zoneHeader !== undefined && $('#zoneHeader').length) {

        zoneHeader = zoneHeader.replace('[ZONE]', '<span class="mr-2 text-16 gris-9">POSTE :</span>').replace('[USER]', '<span class="mr-2 text-16 gris-9">AGENT :</span>');
        $('#zoneHeader').html(zoneHeader);
    }

    // Signature nettoyage
    $('.btnSignerAgentEntretien').off("click.btnSignerAgentEntretien'").on("click.btnSignerAgentEntretien", function(e) {
        e.preventDefault();
        $('#modalPlanNett').modal('hide');
        $('#modalSignerPlanNett').modal('show');
        chargeModaleSignerPlanNett();
    }); // FIN Signature agents d'entretien

    // Zone/Général
    $('.btnPlanNettZone').off("click.btnPlanNettZone'").on("click.btnPlanNettZone", function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var vue = $('#menuVues').data('code');
        if (vue === undefined) { vue = ''; }
        chargePlannNet(vue);

    }); // FIN zone/general

    $('.btnPlanNettGen').off("click.btnPlanNettGen'").on("click.btnPlanNettGen", function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        chargePlannNet();

    }); // FIN zone/general



} // FIN listener

// Charge la modale pour signer le planning des agents d'entretiens (depuis alarme ou lien atelier)
function chargeModaleSignerPlanNett() {
    "use strict";

    var id_user = parseInt($('#modalPlanNettIdUser').val());

    if (id_user === 0 || isNaN(id_user)) {
        alert('ERREUR ID USER 0');
        return false;
    }

    $.fn.ajax({
        rep_script_execute: "../scripts/ajax/",
        script_execute: 'fct_planning_nettoyage.php',
        arguments: 'mode=getModaleSignerPlanNett&id_user='+id_user,
        callBack: function(retour) {
            retour+='';

            $('#modalSignerPlanNettBody').html(retour);
            $("#signature").jSignature();
            $('.selectpicker-tactile').selectpicker();

            $('.btnUserNett').off("click.btnUserNett").on("click.btnUserNett", function(e) {
                e.preventDefault();
                $('.btnUserNett').removeClass('btn-info btn-secondary text-white').addClass('btn-light gris-9');
                $(this).removeClass('btn-light gris-9').addClass('btn-info text-white');
                var id_user_nett = parseInt($(this).data('id'));
                if (isNaN(id_user_nett) || id_user_nett === 0) { alert('ERR_ID_USER_0'); return false; }
                $('#id_user_nett').val(id_user_nett);
            });

            $('.btnChangeDate').off("click.btnChangeDate").on("click.btnChangeDate", function(e) {
                e.preventDefault();
                $(this).hide();
                $('#signerDateVerbose').hide();
                $('#signerChangeDate').show();

            });


            $('.btnEffacerSignature').off("click.btnEffacerSignature").on("click.btnEffacerSignature", function(e) {
                e.preventDefault();
                $("#signature").jSignature('reset');
            });

            $('.btnSaveSignature').off("click.btnSaveSignature").on("click.btnSaveSignature", function(e) {
                e.preventDefault();

                var datapair = $("#signature").jSignature("getData", "image");
                var i = new Image();
                i.src = "data:" + datapair[0] + "," + datapair[1];

                var jour = parseInt($('.pnett-sign-date-jour option:selected').val());
                var mois = parseInt($('.pnett-sign-date-mois option:selected').val());
                var annee = parseInt($('.pnett-sign-date-annee option:selected').val());
                var status = parseInt($('#statusResponsable').val());
                var date = '';
                if (!isNaN(jour) && jour > 0 && !isNaN(mois) && mois > 0 && !isNaN(annee) && annee > 1970) {
                    if (jour < 10) { jour = '0'+jour.toString(); }
                    if (mois < 10) { mois = '0'+mois.toString(); }
                    date = annee+'-'+mois+'-'+jour;
                }

                var id_user_nett = parseInt($('#id_user_nett').val());
                if (isNaN(id_user_nett) || id_user_nett === 0) { alert('ERR_ID_USER_0'); return false; }

                $.fn.ajax({
                    'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
                    'script_execute': 'fct_planning_nettoyage.php',
                    'arguments': 'mode=saveSignaturePlanNett&id_user_nett='+id_user_nett+'&date='+date+'&image='+ i.src,
                    'callBack': function (retour) {
                        retour+= '';
                        if (parseInt(retour) !== 1) {
                            alert("ERREUR\r\n"+retour);
                            return false;
                        }
                        $('#modalSignerPlanNett').modal('hide');

                    } // FIN Callback
                }); // FIN ajax

            });
            //Btn pour Responsable
            $('.btnSaveSignatureResponsable').off("click.btnSaveSignatureResponsable").on("click.btnSaveSignatureResponsable", function(e) {
                e.preventDefault();

                var datapair = $("#signature").jSignature("getData", "image");
                var i = new Image();
                i.src = "data:" + datapair[0] + "," + datapair[1];

                var jour = parseInt($('.pnett-sign-date-jour option:selected').val());
                var mois = parseInt($('.pnett-sign-date-mois option:selected').val());
                var annee = parseInt($('.pnett-sign-date-annee option:selected').val());

                var date = '';
                if (!isNaN(jour) && jour > 0 && !isNaN(mois) && mois > 0 && !isNaN(annee) && annee > 1970) {
                    if (jour < 10) { jour = '0'+jour.toString(); }
                    if (mois < 10) { mois = '0'+mois.toString(); }
                    date = annee+'-'+mois+'-'+jour;
                }

                var id_user_nett = parseInt($('#id_user_nett').val());
                var status = parseInt($('#statusResponsable').val());                
                if (isNaN(id_user_nett) || id_user_nett === 0) { alert('ERR_ID_USER_0'); return false; }

                $.fn.ajax({
                    'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
                    'script_execute': 'fct_planning_nettoyage.php',
                    'arguments': 'mode=saveSignaturePlanNett&id_user_nett='+id_user_nett+'&date='+date + '&status=' + status +  '&image=' + i.src,
                    'callBack': function (retour) {
                        retour+= '';
                        if (parseInt(retour) !== 1) {
                            alert("ERREUR RESPONSABLE\r\n"+retour);
                            return false;
                        }
                        $('#modalSignerPlanNett').modal('hide');

                    } // FIN Callback
                }); // FIN ajax

            });

            return false;
        } // FIN callback
    }); // FIN aJax


} // FIN fonction

// Affiche l'alerte si c'est l'heure
function checkAlerte() {
    "use strict";

    if ($('#vue').hasClass('vue-scf')) {
        return false;
    }

    // Alarmes opérateurs
    if (!$('#modalAlarme').hasClass('in')) {

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_vue_atl.php',
            'arguments': 'mode=checkAlerteModale',
            'callBack': function(retour) {
                retour+='';
                if (parseInt(retour) === 1) {
                    $('#modalAlarme').modal('show');
                }
                return false;
            } // FIN callback
        }); // FIN aJax
    } // FIN test pas déjà affichée

    // Alarmes équipes de nettoyage
    if (!$('#modalAlarmePlanNett').hasClass('in')) {

        var vue = $('#menuVues').data('code');
        if (vue === undefined) { vue = ''; }

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_planning_nettoyage.php',
            'arguments': 'mode=checkAlertePlanNettoyage&vue='+vue,
            'callBack': function(retour) {
                retour+='';

                    if (retour !== '') {
                    $.fn.ajax({
                        'rep_script_execute': "../scripts/ajax/",
                        'script_execute': 'fct_planning_nettoyage.php',
                        'arguments': 'mode=getModaleAlertePlanNett&id_nett_local='+retour,
                        'callBack': function(retour) {
                            retour+='';

                            if (retour.length < 3) { return false; }

                            $('#modalAlarmePlanNettBody').html(retour);
                            $('#modalAlarmePlanNett').modal('show');

                            // Bouton signer
                            $('.btnSignerPlanNett').off("click.btnSignerPlanNett").on("click.btnSignerPlanNett", function(e) {
                                e.preventDefault();

                                $('#modalAlarmePlanNett').modal('hide');
                                chargeModaleSignerPlanNett();

                            }); // FIN click bouton signer

                            return false;
                        } // FIN callback
                    }); // FIN aJax
                }
                return false;
            } // FIN callback
        }); // FIN aJax
        return false;
    } // FIN test pas déjà affichée

} // FIN fonction

// Charge la modale planning nettoyage pour demander le code
function chargePlannNetCode(vue) {
    "use strict";

    $('#modalPlanNett .btnPlanNettZone, #modalPlanNett .btnPlanNettGen, #modalPlanNett .btnSignerAgentEntretien ').hide();

    $.fn.ajax({
        rep_script_execute: "../scripts/ajax/",
        script_execute: 'fct_planning_nettoyage.php',
        arguments: 'mode=modalPlanNettCode',
        return_id: 'modalPlanNettBody',
        done: function() {

            modalePlanNettCodeListner(vue);

        } // FIN callback
    }); // FIN aJax

} // FIN fonction

// Fonction déportée pour le chargement de la modale du planning nettoyage
function chargePlannNet(vue) {
    "use strict";

    var id_user = parseInt($('#modalPlanNettIdUser').val());
    if (vue === undefined) { vue = ''; }

    if (isNaN(id_user) || id_user === 0) { chargePlannNetCode(vue); return false; }

    $('.btnPlanNettZone , .btnPlanNettGen').removeClass('btn-primary btn-outline-secondary');
    if (vue === '') {
        $('.btnPlanNettZone').addClass('btn-primary');
        $('.btnPlanNettGen').addClass('btn-outline-secondary');
    } else {
        $('.btnPlanNettGen').addClass('btn-primary');
        $('.btnPlanNettZone').addClass('btn-outline-secondary');
    }


    $.fn.ajax({
        rep_script_execute: "../scripts/ajax/",
        script_execute: 'fct_planning_nettoyage.php',
        arguments: 'mode=modalPlanNett&vue='+vue+'&id_user='+id_user,
        return_id: 'modalPlanNettBody',
        done: function() {
            $('#modalPlanNett .btnPlanNettZone, #modalPlanNett .btnPlanNettGen, #modalPlanNett .btnSignerAgentEntretien ').show();
            modalePlanNettListner(vue);

        } // FIN callback
    }); // FIN aJax

} // FIN fonction



// Listener de la modale planning nettoyage pour saisie du code
function modalePlanNettCodeListner(vue) {

    "use strict";
    if (vue === undefined) { vue = ''; }
    $('#modalPlanNettIdUser').val(0);

    $('.clavier button').click(function() {

        $('#msgErreurCode').collapse('hide');

        if ($(this).hasClass('btnClearCode')) {
            $('#inputCode').val('');
            return false;
        }

        if ($(this).hasClass('btnValideCode')) {
            if ($('#inputCode').val().length !== 4) {
                $('#msgErreurCode span').html('Votre code comporte 4 chiffres !');
                $('#msgErreurCode').collapse('show');
                $('#inputCode').val('');
                return false;
            } else if ($('#inputCode').val() === '0000') {
                $('#msgErreurCode span').html('Code invalide !<br>Utilisez votre code personnel');
                $('#msgErreurCode').collapse('show');
                $('#inputCode').val('');
                return false;
            } else {
                $.fn.ajax({
                    rep_script_execute: "../scripts/ajax/",
                    script_execute:'fct_user.php',
                    arguments:'mode=checkUserByCode&code='+ $('#inputCode').val(),
                    callBack:function(retour) {
                        retour+='';
                        if (parseInt(retour) === 0 || isNaN(parseInt(retour))) {
                            $('#msgErreurCode span').html('Utilisateur inconnu !');
                            $('#msgErreurCode').collapse('show');
                            $('#inputCode').val('');
                            return false;
                        } else {
                            $('#modalPlanNettIdUser').val(parseInt(retour));
                            $('.btnPlanNettZone , .btnPlanNettGen').removeClass('btn-primary btn-secondary');
                            if (vue === '') {
                                $('.btnPlanNettZone').addClass('btn-primary');
                                $('.btnPlanNettGen').addClass('btn-outline-secondary');
                            } else {
                                $('.btnPlanNettGen').addClass('btn-primary');
                                $('.btnPlanNettZone').addClass('btn-outline-secondary');
                            }

                            $.fn.ajax({
                                rep_script_execute: "../scripts/ajax/",
                                script_execute: 'fct_planning_nettoyage.php',
                                arguments: 'mode=modalPlanNett&vue='+vue+'&id_user='+parseInt(retour),
                                return_id: 'modalPlanNettBody',
                                done: function() {
                                    $('#modalPlanNett .btnPlanNettZone, #modalPlanNett .btnPlanNettGen, #modalPlanNett .btnSignerAgentEntretien ').show();
                                    modalePlanNettListner();

                                } // FIN callback
                            }); // FIN aJax
                        }
                    }

                });
            }
        }

        var nombre = $(this).text();
        if ($('#inputCode').val().length < 4) {
            $('#inputCode').val($('#inputCode').val() + nombre);
        }

    });


}  // FIN listner