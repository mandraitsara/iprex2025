/**
 ------------------------------------------------------------------------
 JS - Vue Atelier

 Copyright (C) 2018 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2018 Intersed
 @version   1.0
 @since     2018

 ------------------------------------------------------------------------
 */

$('document').ready(function () { 
    
//Detecter le lot s'il est déjà dans le storage. S'il n'est pas encore là, il faut qu'il aille l'etape normale.
var id_lot = localStorage.lotId;

if(isNaN(id_lot)){
    chargeEtape(1, 0);
}else{    
    chargeEtape(5, id_lot)
}



// Nettoyage du contenu de la modale Nouvel Emballage à sa fermeture
    $('#modalNouvelEmballage').on('hidden.bs.modal', function (e) {
        $('#modalNouvelEmballageBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    }); // FIN fermeture modale Nouvel Emballage

    $('#modalSignerPlanNett').on('hidden.bs.modal', function (e) {
        $('#modalSignerPlanNettBody').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
    }); // FIN fermeture modale Nouvel Emballage

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

  

    $(window).on("beforeunload", function(e){   
        var id_lot = parseInt($("#etape4 input[name='id_lot']").val());        
        localStorage.lotId = id_lot       
    })  

    $(window).on("unload", function(e){
        $("#nettoyage_atl").each(function(){
            var chemin = $("#nettoyage_atl").attr();
            var pendant  = "/nettoyage/"
            var id_lot = localStorage.lotId;
            if(chemin.includes(pendant)) {        
                if(!isNaN(id_lot)){
                    var url = window.location.href;
                    window.location.replace(url);
                }
            }    


        })
        
    })

}); // FIN ready

// Fonction chargeant les étapes pour intégrer leur contenu
function chargeEtape(numeroEtape, identifiant) {
    $('.etapes-suivantes').hide('fast');
    // Ajax qui charge le contenu de l'étape

    $('#etape' + numeroEtape).show('fast');
    $('#etape' + numeroEtape).html('<div class="padding-50 text-center"><i class="fa fa-spin fa-spinner fa-5x gris-c"></i></div>');

    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_atl.php',
        'arguments': 'mode=chargeEtapeVue&etape=' + numeroEtape + '&id=' + identifiant,
        'return_id': 'etape' + numeroEtape,
        'done': function () {

            if (numeroEtape == 4) {
                if ($('#controleLoma.hidden').length > 0) {
                    let id_lot = parseInt($('#controleLoma input[name=id_lot]').val());
                    // chargeEtape(2, id_lot);
                    chargeEtape(6, id_lot);
                }
            }
            var fctnom = "listenerEtape" + numeroEtape;
            var fn = window[fctnom];
            fn(identifiant);
        } // FIN Callback
    }); // FIN ajax

} // FIN fonction chargeEtape

// Charge le Ticket (maj)
function chargeTicketLot(etape, identifiant) {
    // Ajax qui charge le contenu du ticket
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_atl.php',
        'arguments': 'mode=chargeTicketLot&etape=' + etape + '&id=' + identifiant,
        'return_id': 'ticketLotContent',
        'done': function () {
            listenerTicketLot(identifiant);
        }
    }); // FIN ajax
} // FIN fonction


// Listener de l'étape 1
function listenerEtape1(identifiant) {

    chargeTicketLot(1, identifiant);

    // Selection de la vue sur carte
    $('.carte-lot').off("click.selectcartevue").on("click.selectcartevue", function (e) {

        e.preventDefault();

        var id_lot = parseInt($(this).data('id-lot'));
        if (id_lot === undefined || id_lot === 0 || isNaN(id_lot)) { alert("Une erreur est survenue !\r\nCode erreur : STTXGHAG"); return false; }
        // Appel étape 2
        // chargeEtape(2, id_lot);
        chargeEtape(4, id_lot);

    }); // FIN click vue carte

} // FIN listener étape 1


// Listener de l'étape 2
function listenerEtape2(identifiant) {
    "use strict";

    if (identifiant === undefined) {
        identifiant = parseInt($('#numLotTicket').data('id-lot'));
    }

    chargeTicketLot(2, identifiant);


    // Selection d'une catégorie de produit pour affichage des produits correspondants
    $('.btnCategorie').off("click.btncategorie").on("click.btncategorie", function (e) {
        e.preventDefault();

        var id_categorie = parseInt($(this).data('id-categorie'));
        if (id_categorie === undefined || id_categorie === 0 || isNaN(id_categorie)) { alert("Identifaction de la catégorie impossible.\nCode erreur : REROUSSA"); return false; }

        // Catégorie identifiée, on affiche les produits correspondants
        $('#containerCategories .btn').removeClass('btn-success').addClass('btn-secondary');
        $(this).removeClass('btn-primary').addClass('btn-success');
        $('#containerEtiquettesProduits').html('<div class="col text-center"><i class="fa fa-spin fa-spinner fa-lg gris-9"></i></div>');

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_produits.php',
            'arguments': 'mode=showProduitsCourtsCategorie&id_categorie=' + id_categorie,
            'return_id': 'containerEtiquettesProduits',
            'done': function () {
                nomsCourtsListener();
            }
        }); // FIN aJax

    }); // FIN selection catégorie de produit pour étiquetage


    // bouton changer de rouleau direct sur emballage
    $('.btn-emb-change').off("click.btnembchange").on("click.btnembchange", function (e) {
        e.preventDefault();
        var id_fam = parseInt($(this).parents('.card').data('id-fam'));
        var id_old_emb = parseInt($(this).data('id-old-emb'));
        if (id_fam === undefined || id_fam === 0 || isNaN(id_fam)) { alert("Identifaction de la famille impossible.\nCode erreur : WIBKXAQK"); return false; }
        // Famille identifiée, on rechage le body de la modale avec les emballages disponibles ayant du stock et non par défaut:

        $('#modalNouvelEmballage').modal('show');
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_emballages.php',
            'arguments': 'mode=modalNouvelEmballageFrontEtape2&vue_code=atl&id_fam=' + id_fam + '&id_old_emb=' + id_old_emb,
            'return_id': 'modalNouvelEmballageBody',
            'done': function () {
                modalNouvelEmballageFrontEtape2Listener();
            }
        }); // FIN aJax

    }); // FIN bouton changer rouleau sur emballage

    // Bouton déclarer un défectueux sur l'emballage
    $('.btn-emb-defectueux').off("click.btnembchange").on("click.btnembchange", function (e) {
        e.preventDefault();
        var id_emb = parseInt($(this).data('id-emb'));
        var id_lot = parseInt($('#numLotTicket').data('id-lot'));
        if (id_emb === undefined || id_emb === 0 || isNaN(id_emb)) { alert("Identifaction du rouleau impossible.\nCode erreur : UTMCWXWD"); return false; }
        if (id_lot === undefined || id_lot === 0 || isNaN(id_lot)) { alert("Identifaction du lot impossible.\nCode erreur : ZKWOHTBG"); return false; }
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_emballages.php',
            'arguments': 'mode=declareDefectueux&id_emb=' + id_emb + '&id_lot=' + id_lot,
            'done': function () {
                chargeTicketLot(2, id_lot);
                $('#modalInfoBody').html('<h4><i class="fa fa-2x fa-check mb1 gris-5"></i><br>Emballage défectueux enregistré</h4>');
                $('#modalInfo').modal('show');
            }
        }); // FIN aJax

    }); // FIN bouton déclarer un défectueux sur l'emballage

    // Pagination Ajax sur les emballages
    $(document).on('click', '.carte-pdt-pagination', function () {
        if ($(this).attr('data-url') === undefined) { return false; }
        // on affiche le loading d'attente
        $('#etape3').html('<i class="fa fa-spin fa-spinner fa-2x"></i>');
        // on fait l'appel ajax qui va rafraichir la liste
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_vue_atl.php' + $(this).attr('data-url'),
            'return_id': 'containerListeEmballages',
            'done': function () {
                listenerEtape2();
            }
        }); // FIN ajax
        // on désactive le lien hypertexte
        return false;
    }); // FIN pagination ajax

    // Bouton nouvel emballage (à l'ouverture de la modale)

    // Chargement du contenu de la modale Emballage à son ouverture
    $('#modalNouvelEmballage').on('show.bs.modal', function (e) {
        // Si ouverture JS de la modale (via une carte emballage), on ne charge pas la première étape ici
        if (e.relatedTarget === undefined) { return true; }
        // On récupère le contenu de la modale
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_emballages.php',
            'arguments': 'mode=modalNouvelEmballageFront&vue_code=atl',
            'return_id': 'modalNouvelEmballageBody',
            'done': function () {
                modalNouvelEmballageFrontListener();
            }
        }); // FIN aJax
    }); // Fin chargement du contenu de la modale


    // Charger le contrôle LOMA fin lot avant de procéder au nettoyage 
    $(document).ready(function () {
        $(document).on("click", ".btnNettoyage", function (e) {
            e.preventDefault();

            var identifiant = parseInt($('#numLotTicket').data('id-lot'));
            var numeroEtape = 5;
            const href = $(this).attr('href');


            $('.etapes-suivantes').hide('fast');
            $('#etape' + numeroEtape).show('fast');
            $('#etape' + numeroEtape).html('<div class="padding-50 text-center"><i class="fa fa-spin fa-spinner fa-5x gris-c"></i></div>');

            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
                'script_execute': 'fct_vue_atl.php',
                'arguments': 'mode=chargeEtapeVue&etape=' + numeroEtape + '&id=' + identifiant,
                'return_id': 'etape' + numeroEtape,
                'done': function () {
                    chargeTicketLot(1, identifiant);

                    $('.btn-loma-fin').off("click.btnloma").on("click.btnloma", function (e) {
                        e.preventDefault();

                        // Récup test type
                        var testcode = $(this).data('test');
                        var regextest = new RegExp('^(nfe|fe|inox)$');
                        if (testcode === undefined || !regextest.test(testcode)) {
                            alert('ERREUR\r\n\r\nIdentification du test impossible (' + testcode + ') !\r\n\r\nCode erreur : G28C6R17');
                            return false;
                        } // FIN gestion erreur de récupération test code

                        // Résultat du test
                        var resultat = parseInt($(this).data('resultat'));
                        if (resultat === undefined || resultat < 0 || resultat > 1 || isNaN(resultat)) {
                            alert('ERREUR\r\n\r\nIdentification du résultat du test impossible !\r\n\r\nCode erreur : H112SBD8');
                            return false;
                        } // FIN gestion erreur de récupération résultat

                        // On met à jour le resultat dans le DOM pour éviter un triple appel ajax/BDD
                        $('input[name=resultest_fin_' + testcode + ']').val(resultat);

                        // On met à jour l'affichage des boutons
                        var classeAutreBtn = resultat === 1 ? 'danger' : 'success';
                        var classeBtn = resultat === 1 ? 'success' : 'danger';

                        // On inverse s'il s'agit du produit
                        if ($(this).data('test') === 'pdt') {
                            classeAutreBtn = resultat === 1 ? 'success' : 'danger';
                            classeBtn = resultat === 1 ? 'danger' : 'success';
                        }

                        $(this).parents('.loma-test-btns-fin').find('.btn-' + classeAutreBtn).removeClass('btn-' + classeAutreBtn).addClass('btn-outline-secondary');
                        $(this).removeClass('btn-outline-secondary').addClass('btn-' + classeBtn);

                        // Résultat du test produit
                        var testpdtObjet = $('.resultats-fin-tests input[name=resultest_fin_pdt]');
                        var testpdt = parseInt(testpdtObjet.val());

                        // Résultats des plaquettes
                        var testfe = parseInt($('.resultats-fin-tests input[name=resultest_fin_nfe]').val());
                        var testnfe = parseInt($('.resultats-fin-tests input[name=resultest_fin_inox]').val());
                        var testinox = parseInt($('.resultats-fin-tests input[name=resultest_fin_fe]').val());

                        // On vérifie si les trois tests sont renseignés
                        var termine = true;
                        var test0 = false;
                        $('.resultats-fin-tests input').each(function () {
                            var res = parseInt($(this).val());
                            if (res < 0) {
                                termine = false;
                            }
                            if (res === 0) {
                                test0 = true;
                            }
                        });

                        if (testinox > -1 && testfe > -1 && testnfe > -1) {
                            $.fn.ajax({
                                'rep_script_execute': "../scripts/ajax/",
                                'script_execute': 'fct_vue_atl.php',
                                'form_id': 'controleLomaFin',
                                'callBack': function (retour) {
                
                                    retour += ''; // Debug mauvaise interprétation de retours
                                    if (parseInt(retour) !== 1) { alert('Une erreur est survenue !\r\nCode erreur : ' + retour); return false; }
                                    var id_lot = parseInt($("#etape4 input[name='id_lot']").val());
                                    window.location.href = href;
                
                                } // FIN callBack
                
                            }); // FIN aJax
                        } 
                    });
                } // FIN Callback
            });
        });
    });



} // FIN listener étape 2

// Listener des noms courts pour étiquetage
function nomsCourtsListener() {
    "use strict";

    // Selection d'un produit pour étiquetage -> quantité ? (modale)
    $('.btnNomCourt').off("click.btnnomcourt").on("click.btnnomcourt", function (e) {

        e.preventDefault();

        $('#modalQuantiteEtiquettesInfo').text('0');
        var nom_court = $(this).text().trim();

        if (nom_court === undefined || nom_court === '') { alert("Récupération du nom court impossible !.\nCode erreur : YNMNTADN"); return false; }

        $('#modalQuantiteEtiquettesProduit').text(nom_court);
        $('#modalQuantiteEtiquettes').modal('show');


    }); // FIN selection d'un produit pour étiquetag


    // Pavé numérique quantité à imprimer
    $('#modalQuantiteEtiquettes .clavier .btn').off("click.appuietoucheclavier").on("click.appuietoucheclavier", function (e) {

        e.preventDefault();

        // On récupère la valeur de la touche
        var touche = $(this).data('valeur');
        var qte = parseInt($('#modalQuantiteEtiquettesInfo').text().trim());
        if (isNaN(qte)) {
            qte = 0;
            $('#modalQuantiteEtiquettesInfo').text(qte);
            return false;
        }

        if (qte === 0 && touche !== 'C' && parseInt(touche) > 0) {
            qte = parseInt(touche);
        } else if (touche !== 'C') {
            qte = parseInt(qte.toString() + touche.toString());
        } else if (touche === 'C') {
            qte = parseInt(qte.toString().slice(0, -1));
            if (qte === '' || isNaN(parseInt(qte))) {
                qte = 0;
            }
        }
        if (parseInt(qte) > 999) {
            return false;
        }

        $('#modalQuantiteEtiquettesInfo').text(qte);

    }); // FIN pavé numérique quantité

    // Imprimer étiquettes
    $('#modalQuantiteEtiquettes .btn-imprimer-etiquettes').off("click.btnimprimeretiquettes").on("click.btnimprimeretiquettes", function (e) {

        e.preventDefault();
        var copies = parseInt($('#modalQuantiteEtiquettesInfo').text().trim());
        if (isNaN(copies) || copies === 0) { return false; }

        var nom_court = $('#modalQuantiteEtiquettesProduit').text().trim();



        var id_lot = parseInt($('#numLotTicket').data('id-lot'));

        // IMPRESSION
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",

            'script_execute': 'fct_etiquettes.php',
            'arguments': 'mode=imprimerEtiquette&nom_court=' + nom_court + '&id_lot=' + id_lot + '&copies=' + copies,
            'callBack': function (retour) {

                if (retour === '') {
                    alert('Impression impossible !');
                    return false;
                }

                var doc = document.getElementById('etiquetteFrame').contentWindow.document;
                doc.open();
                doc.write(retour);
                doc.close();

                window.frames["imprimerEtiquette"].focus();
                window.frames["imprimerEtiquette"].print();


            }
        }); // FIN aJax

    }); // FIN bouton imprimer étiquettes


} // FIN listener des noms courts pour étiquetage


// Listener du Ticket
function listenerTicketLot(identifiant) {
    // Retour vers les vues
    $('.btnChangeVue').off("click.btnchangevue'").on("click.btnchangevue", function (e) {
        e.preventDefault();
        location.reload();
    //     var id_lot = parseInt($("#etape4 input[name='id_lot']").val());
    //     if (id_lot === undefined || id_lot === 0 || isNaN(id_lot)) { alert("Une erreur est 	survenue !\r\nCode erreur : STTXGHAG"); return false; }       
    //     chargeEtape(5, id_lot);
     }); // FIN Retour vers les vues

    $('.btnLomaEncours').off("click.btnLomaEncours").on("click.btnLomaEncours", function (e) {
        e.preventDefault();

        var id_lot = parseInt($("#etape4 input[name='id_lot']").val());
        if (id_lot === undefined || id_lot === 0 || isNaN(id_lot)) { alert("Une erreur est survenue !\r\nCode erreur : STTXGHAG"); return false; }
        // Appel étape 4

        console.log('En cours', id_lot);
        chargeEtape(6, id_lot);

    });

    $('.btnLomaFin').off("click.btnLomaFin").on("click.btnLomaFin", function (e) {
        e.preventDefault();

        var id_lot = parseInt($("#etape4 input[name='id_lot']").val());
        if (id_lot === undefined || id_lot === 0 || isNaN(id_lot)) { alert("Une erreur est survenue !\r\nCode erreur : STTXGHAG"); return false; }
        // Appel étape 5

        ///console.log('Fin', id_lot);
        chargeEtape(5, id_lot);

    });

    // $('.btnChangeVue').off("click.btnchangevue'").on("click.btnchangevue", function (e) {
    //     e.preventDefault();
    //     chargeEtape(1, 0);
    // });

    // Déclarer un incident
    $('#modalIncidentFront').on('show.bs.modal', function (e) {

        // Sélection du type d'incident
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_atl.php',
            'arguments': 'mode=selectTypeIncidentModale',
            'return_id': 'modalIncidentFrontBody',
            'done': function () {

                modalIncidentFrontListener_1();

            } // FIN Callback
        }); // FIN ajax

    }); // FIN modale déclarer un incident


} // FIN listener Ticket

// Listener de la modale nouvel emballage (Etape 1)
function modalNouvelEmballageFrontListener() {
    // Sélection d'une famille
    $('.carte-fam-add-emb').off("click.cartefamaddemb'").on("click.cartefamaddemb", function (e) {
        e.preventDefault();
        var id_fam = parseInt($(this).data('id-famille'));
        var id_old_emb = parseInt($(this).data('id-old-emb'));
        if (id_fam === undefined || id_fam === 0 || isNaN(id_fam)) { alert("Identifaction de la famille impossible.\nCode erreur : IGJOLLQJ"); return false; }
        // Famille identifiée, on rechage le body de la modale avec les emballages disponibles ayant du stock et non par défaut:
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_emballages.php',
            'arguments': 'mode=modalNouvelEmballageFrontEtape2&vue_code=atl&id_fam=' + id_fam + '&id_old_emb=' + id_old_emb,
            'return_id': 'modalNouvelEmballageBody',
            'done': function () {
                modalNouvelEmballageFrontEtape2Listener();
            }
        }); // FIN aJax


    }); // FIN Sélection d'une famille

} // FIN Listener

// Listener de la modale changement de rouleau (Etape 2)
function modalNouvelEmballageFrontEtape2Listener() {
    // Sélection d'une famille
    $('.carte-new-emb-defaut').off("click.cartenewembdefaut'").on("click.cartenewembdefaut", function (e) {
        e.preventDefault();
        var id_emb = parseInt($(this).data('id-emballage'));
        if (id_emb === undefined || id_emb === 0 || isNaN(id_emb)) { alert("Identifaction de l'emballage impossible.\nCode erreur : QBWNMCYA"); return false; }
        var id_lot = parseInt($('#numLotTicket').data('id-lot'));
        // Emballage identifiée, on le définie comme en cours et on recharge l'étape du lot...
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_emballages.php',
            'arguments': 'mode=setNewEmballageEnCours&id_emb=' + id_emb + '&id_lot=' + id_lot + '&code_vue=atl',
            'done': function () {
                // On ferme la modale
                $('#modalNouvelEmballage').modal('hide');
                // On met à jour la liste des emballages dans la vue principale (avec le numéro de lot)
                var id_lot = parseInt($('#numLotTicket').data('id-lot'));
                // chargeEtape(2, id_lot);
                chargeEtape(5, id_lot);
            } // FIN callback
        }); // FIN aJax
    }); // FIN Sélection d'une famille
} // FIN Listener



/** -------------------------------------------------------------
 * Clavier virtuel - Ne pas changer le noms des champs
 * Ou adapater le fichier jkeyboard.js
 ------------------------------------------------------------- */
function clavierVirtuel() {

    $('#champ_clavier').focus(function () {
        $('#clavier_virtuel').show("slide", { direction: "down" });
    });

    $('.btn').click(function () {
        $('#clavier_virtuel').hide("slide", { direction: "down" });
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

function clavierVirtuelEncours() {

    $('#champ_clavier_encours').focus(function () {
        $('#clavier_virtuel').show("slide", { direction: "down" });
    });

    $('.btn').click(function () {
        $('#clavier_virtuel').hide("slide", { direction: "down" });
    });

    $('#clavier_virtuel').jkeyboard({
        input: $('#champ_clavier_encours'),
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
/*
// Affiche l'alerte si c'est l'heure
function checkAlerteAtl() {
    "use strict";

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



} // FIN fonction*/

// Listener modale incident (1ere étape)
function modalIncidentFrontListener_1() {

    // Clic sur sélection d'un type d'incident à déclarer
    $('.btnDeclareTypeIncident').off("click.btndeclaretypeincident").on("click.btndeclaretypeincident", function (e) {
        e.preventDefault();

        var type = parseInt($(this).data('type-incident'));
        if (type === undefined || isNaN(type) || type === 0) {
            alert("ERREUR !\r\nIdentification du type d'incident impossible...\r\nCode erreur : ZCODAPM7");
            return false;
        }

        var id_lot = $('#lotid_photo').val();

        if (id_lot.slice(0, 1) !== 'N' && (id_lot === undefined || isNaN(parseInt(id_lot)) || parseInt(id_lot) === 0)) {
            alert("ERREUR !\r\nIdentification du lot impossible...\r\nCode erreur : X7J7178W");
            return false;
        }

        $(this).html('<i class="fa fa-spin fa-spinner fa-lg"></i>');

        // On met à jour le contenu de la modale
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_atl.php',
            'arguments': 'mode=modalDeclareIncident&type=' + type + '&id_lot=' + id_lot,
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
    $('.btnSaveIncident').off("click.btnSaveIncident").on("click.btnSaveIncident", function (e) {
        e.preventDefault();

        // On vérifie que le commentaire est pas vide...
        var comlen = $('#champ_clavier').val().trim().length;
        if (comlen === 0) { return false; }

        // OK
        $('.btnSaveIncident').attr('disabled', 'disabled');
        $('.btnSaveIncident').html('<i class="fa fa-spin fa-spinner fa-lg"></i>');


        var id_lot = $('#lotid_photo').val();

        if (id_lot.slice(0, 1) !== 'N' && (id_lot === undefined || isNaN(parseInt(id_lot)) || parseInt(id_lot) === 0)) {
            alert("ERREUR !\r\nIdentification du lot impossible...\r\nCode erreur : X7J7178W");
            return false;
        }

        // On enregistre...
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_atl.php',
            'form_id': 'modalIncidentFrontBody',
            'done': function () {

                $('#modalIncidentFront').modal('hide');

                chargeTicketLot(2, id_lot);

            } // FIN Callback
        }); // FIN ajax

    }); // FIN bouton save incident

} // FIN listener modale Incident (2)

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 4 (Contrôles LOMA)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape4(identifiant) {
    "use strict";


    chargeTicketLot(1, identifiant);

    // Bouton test ok / ko
    $('.btn-loma').off("click.btnloma").on("click.btnloma", function (e) {

        e.preventDefault();

        // Récup test type
        var testcode = $(this).data('test');
        var regextest = new RegExp('^(nfe|fe|inox|pdt)$');
        if (testcode === undefined || !regextest.test(testcode)) {
            alert('ERREUR\r\n\r\nIdentification du test impossible (' + testcode + ') !\r\n\r\nCode erreur : G28C6R17');
            return false;
        } // FIN gestion erreur de récupération test code

        // Résultat du test
        var resultat = parseInt($(this).data('resultat'));
        if (resultat === undefined || resultat < 0 || resultat > 1 || isNaN(resultat)) {
            alert('ERREUR\r\n\r\nIdentification du résultat du test impossible !\r\n\r\nCode erreur : H112SBD8');
            return false;
        } // FIN gestion erreur de récupération résultat

        // On met à jour le resultat dans le DOM pour éviter un triple appel ajax/BDD
        $('input[name=resultest_' + testcode + ']').val(resultat);

        // On met à jour l'affichage des boutons
        var classeAutreBtn = resultat === 1 ? 'danger' : 'success';
        var classeBtn = resultat === 1 ? 'success' : 'danger';

        // On inverse s'il s'agit du produit
        if ($(this).data('test') === 'pdt') {
            classeAutreBtn = resultat === 1 ? 'success' : 'danger';
            classeBtn = resultat === 1 ? 'danger' : 'success';
        }

        $(this).parents('.loma-test-btns').find('.btn-' + classeAutreBtn).removeClass('btn-' + classeAutreBtn).addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-' + classeBtn);

        // Résultat du test produit
        var testpdtObjet = $('.resultats-tests input[name=resultest_pdt]');
        var testpdt = parseInt(testpdtObjet.val());

        // Résultats des plaquettes
        var testfe = parseInt($('.resultats-tests input[name=resultest_fe]').val());
        var testnfe = parseInt($('.resultats-tests input[name=resultest_nfe]').val());
        var testinox = parseInt($('.resultats-tests input[name=resultest_inox]').val());

        // On vérifie si les trois tests sont renseignés
        var termine = true;
        var test0 = false;
        $('.resultats-tests input').each(function () {
            var res = parseInt($(this).val());
            if (res < 0) {
                termine = false;
            }
            if (res === 0) {
                test0 = true;
            }
        });

        // Si on a passé les 3 tests témoins, on affiche celui du produit
        if (testinox > -1 && testfe > -1 && testnfe > -1) {

            $('.header-loma .badge-dark').toggleClass('hid');
            $('.tests-plaquettes').hide('fast');
            $('.test-produit').show('fast');

        }

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


    }); // FIN bouton test loma

    // Bouton valide loma apres commentaire
    $('.btn-valid-loma').off("click.btnvalidloma").on("click.btnvalidloma", function (e) {

        e.preventDefault();
        valideLoma();

    }); // FIN bouton valide loma après commentaire

} // FIN listener


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Fonction déportée -> Valide le contrôle LOMA pour un produit
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function valideLoma() {
    var id_lot = parseInt($('#controleLoma input[name=id_lot]').val());
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/",
        'script_execute': 'fct_vue_atl.php',
        'form_id': 'controleLoma',
        'callBack': function (retour) {
            if ($.trim(retour) !== '') {
                alert("ERREUR !\r\n\r\nUne erreur est survenue...\r\n\r\nCode erreur : " + retour);
                return false;
            }

            // Surgélation verticale : on reviens à la liste des produits
            chargeEtape(2, id_lot);

        } // FIN callback
    }); // FIN aJax

} // FIN fonction valide Loma pour un produit


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 5 (Loma apres)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape5(identifiant) {
    "use strict";
    chargeTicketLot(5, identifiant); // On prends déjà le ticket du l'étape 2 car on ne va pas garder celui de la section produits

    // Bouton test ok / ko
    $('.btn-loma-fin').off("click.btnloma").on("click.btnloma", function (e) {

        e.preventDefault();

        // Récup test type
        var testcode = $(this).data('test');
        var regextest = new RegExp('^(nfe|fe|inox)$');
        if (testcode === undefined || !regextest.test(testcode)) {
            alert('ERREUR\r\n\r\nIdentification du test impossible (' + testcode + ') !\r\n\r\nCode erreur : G28C6R17');
            return false;
        } // FIN gestion erreur de récupération test code

        // Résultat du test
        var resultat = parseInt($(this).data('resultat'));
        if (resultat === undefined || resultat < 0 || resultat > 1 || isNaN(resultat)) {
            alert('ERREUR\r\n\r\nIdentification du résultat du test impossible !\r\n\r\nCode erreur : H112SBD8');
            return false;
        } // FIN gestion erreur de récupération résultat

        // On met à jour le resultat dans le DOM pour éviter un triple appel ajax/BDD
        $('input[name=resultest_fin_' + testcode + ']').val(resultat);

        // On met à jour l'affichage des boutons
        var classeAutreBtn = resultat === 1 ? 'danger' : 'success';
        var classeBtn = resultat === 1 ? 'success' : 'danger';

        // On inverse s'il s'agit du produit
        if ($(this).data('test') === 'pdt') {
            classeAutreBtn = resultat === 1 ? 'success' : 'danger';
            classeBtn = resultat === 1 ? 'danger' : 'success';
        }

        $(this).parents('.loma-test-btns-fin').find('.btn-' + classeAutreBtn).removeClass('btn-' + classeAutreBtn).addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-' + classeBtn);

        // Résultat du test produit
        var testpdtObjet = $('.resultats-fin-tests input[name=resultest_fin_pdt]');
        var testpdt = parseInt(testpdtObjet.val());

        // Résultats des plaquettes
        var testfe = parseInt($('.resultats-fin-tests input[name=resultest_fin_nfe]').val());
        var testnfe = parseInt($('.resultats-fin-tests input[name=resultest_fin_inox]').val());
        var testinox = parseInt($('.resultats-fin-tests input[name=resultest_fin_fe]').val());

        // On vérifie si les trois tests sont renseignés
        var termine = true;
        var test0 = false;
        $('.resultats-fin-tests input').each(function () {
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
                'script_execute': 'fct_vue_atl.php',
                'form_id': 'controleLomaFin',
                'callBack': function (retour) {

                    retour += ''; // Debug mauvaise interprétation de retours
                    if (parseInt(retour) !== 1) { alert('Une erreur est survenue !\r\nCode erreur : ' + retour); return false; }
                    var id_lot = parseInt($("#etape4 input[name='id_lot']").val());
                    chargeEtape(1, 0);

                } // FIN callBack

            }); // FIN aJax


        } // FIN tests passés

    }); // FIN bouton test loma

} // FIN listener


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 6 (Contrôles LOMA)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape6(identifiant) {
        "use strict";

    chargeTicketLot(5, identifiant);

    // Bouton test ok / ko
    $('.btn-loma-encours').off("click.btnloma").on("click.btnloma", function (e) {

        e.preventDefault();    

        // Récup test type
        var testcode = $(this).data('test');
        var regextest = new RegExp('^(nfe|fe|inox|pdt)$');
        
        if (testcode === undefined || !regextest.test(testcode)) {
            alert('ERREUR\r\n\r\nIdentification du test impossible (' + testcode + ') !\r\n\r\nCode erreur : G28C6R17');
            return false;
        } // FIN gestion erreur de récupération test code

        // Résultat du test
        var resultat = parseInt($(this).data('resultat'));
        if (resultat === undefined || resultat < 0 || resultat > 1 || isNaN(resultat)) {
            alert('ERREUR\r\n\r\nIdentification du résultat du test impossible !\r\n\r\nCode erreur : H112SBD8');
            return false;
        } // FIN gestion erreur de récupération résultat

        // On met à jour le resultat dans le DOM pour éviter un triple appel ajax/BDD
        $('input[name=resultest_encours_' + testcode + ']').val(resultat);

        // On met à jour l'affichage des boutons
        var classeAutreBtn = resultat === 1 ? 'danger' : 'success';
        var classeBtn = resultat === 1 ? 'success' : 'danger';

        // On inverse s'il s'agit du produit
        if ($(this).data('test') === 'pdt') {
            classeAutreBtn = resultat === 1 ? 'success' : 'danger';
            classeBtn = resultat === 1 ? 'danger' : 'success';
        }

        $(this).parents('.loma-test-btns-encours').find('.btn-' + classeAutreBtn).removeClass('btn-' + classeAutreBtn).addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-' + classeBtn);

        // Résultat du test produit
        var testpdtObjet = $('.resultats-tests-encours input[name=resultest_encours_pdt]');
        var testpdt = parseInt(testpdtObjet.val());

        // Résultats des plaquettes
        var testfe = parseInt($('.resultats-tests-encours input[name=resultest_encours_fe]').val());
        var testnfe = parseInt($('.resultats-tests-encours input[name=resultest_encours_nfe]').val());
        var testinox = parseInt($('.resultats-tests-encours input[name=resultest_encours_inox]').val());

        // On vérifie si les trois tests sont renseignés
        var termine = true;
        var test0 = false;
        $('.resultats-tests-encours input').each(function () {
            var res = parseInt($(this).val());
            if (res < 0) {
                termine = false;
            }
            if (res === 0) {
                test0 = true;
            }
        });

        // Si on a passé les 3 tests témoins, on affiche celui du produit
        if (testinox > -1 && testfe > -1 && testnfe > -1) {
            $('.header-loma-encours .badge-dark').toggleClass('hid');
            $('.tests-encours-plaquettes').hide('fast');
            $('.test-encours-produit').show('fast');

        }

        // Si on a renseigné le test du produit
        if (testpdt > -1) {

            // Si on a du métal dans la viande, on affiche le champ "commentaires" et le bouton valider
            if (testpdt > 0) {
                if ($('.loma-commentaires').length > 0)
                    $('.loma-commentaires').remove();

                if ($('#commentaire_etape_4').length > 0)
                    $('#commentaire_etape_4').remove();

                clavierVirtuelEncours();
                $('.loma-commentaires-encours').show('blind');  // classe = conteneur
                $('#champ_clavier_encours').focus();        // ID = textarea

                // Sinon, on valide direct
            } else {
                valideLoma6();

            } // FIN test un résultat au moins à zéro

        } // FIN test terminé


    }); // FIN bouton test loma

    // Bouton valide loma apres commentaire
    $('.btn-valid-loma-encours').off("click.btnvalidloma").on("click.btnvalidloma", function (e) {

        e.preventDefault();
        valideLoma6();

    }); // FIN bouton valide loma après commentaire

} // FIN listener


function valideLoma6() {
    var id_lot = parseInt($('#controleLoma input[name=id_lot]').val());
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/",
        'script_execute': 'fct_vue_atl.php',
        'form_id': 'controleLomaEncours',
        'callBack': function (retour) {
            if ($.trim(retour) !== '') {
                alert("ERREUR !\r\n\r\nUne erreur est survenue...\r\n\r\nCode erreur : " + retour);
                return false;
            }

            // Surgélation verticale : on reviens à la liste des produits
            chargeEtape(2, id_lot);

        } // FIN callback
    }); // FIN aJax

}




