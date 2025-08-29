/**
 ------------------------------------------------------------------------
 JS - Vue Expédition

 Copyright (C) 2020 Intersed
 http://www.intersed.fr/
 ------------------------------------------------------------------------

 @author    Cédric Bouillon
 @copyright Copyright (c) 2020 Intersed
 @version   1.0
 @since     2019

 ------------------------------------------------------------------------
 */
$('document').ready(function() {
    "use strict";

    var id_bl = parseInt($('#stkAjaxVue').data('id-bl'));
    if (isNaN(id_bl)) { id_bl = 0; }

    // Si pas de BL à précharger
    if (id_bl === 0) {
        chargeEtape(0, 0);
    // SI on a un BL à précharger (création bon de transfert = redirection vers PRPOP)
    } else {

        // On génère le PRP pour ce BL (BT) et on va a l'étape 3
        $.fn.ajax({
            rep_script_execute: "../scripts/ajax/", // Gestion de l'URL Rewriting
            script_execute: 'fct_vue_exp.php',
            arguments: 'mode=createPrpFromBt&id_bl='+id_bl,
            callBack : function(id_prp) {
                id_prp+= '';
                if (parseInt(id_prp) <= 0) {
                    alert('Echec de création du PRP !\r\nERR_CREATEPRPFROMBL_#'+id_bl);
                    return false;
                }
                chargeEtape(3,id_prp);
            }
        }); // FIN ajax

    } // FIN test préchargement BL

}); // FIN ready



// Fonction chargeant les étapes pour intégrer leur contenu
function chargeEtape(numeroEtape, identifiant) {

    $('#stkAjaxVue').html('<div class="padding-50 text-center"><i class="fa fa-spin fa-spinner fa-5x gris-c"></i></div>');

    // Ajax qui charge le contenu de l'étape

    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_exp.php',
        'arguments': 'mode=chargeEtapeVue&etape=' + numeroEtape + '&id='+identifiant,
        'return_id': 'stkAjaxVue',
        'done': function () {
            var fctnom = "listenerEtape"+numeroEtape;
            var fn  = window[fctnom];
            fn(identifiant);
        } // FIN Callback
    }); // FIN ajax


} // FIN fonction chargeEtape


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Charge le Ticket
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function chargeTicket(etape, identifiant) {
    "use strict";

    $('#ticketContent').html('<div class="text-center"><i class="fa fa-spin fa-spinner gris-c"></i></div>');

    // Ajax qui charge le contenu du ticket
    $.fn.ajax({
        'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
        'script_execute': 'fct_vue_exp.php',
        'arguments': 'mode=chargeTicket&etape='+etape+'&id=' + identifiant,
        'return_id': 'ticketContent',
        'done' : function() {

            listenerTicket();
        }
    }); // FIN ajax

} // FIN fonction

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener du ticket
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerTicket() {
    "use strict";

    // Retour vers le point d'entrée
    $('.btnRetourEtape0').off("click.btnretouretape0'").on("click.btnretouretape0", function(e) {
        e.preventDefault();

        var id_prp = parseInt($(this).data('id-prp'));
        if (isNaN(id_prp)) { id_prp = 0; }
        if (id_prp > 0) {
            $.fn.ajax({
                'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
                'script_execute': 'fct_vue_exp.php',
                'arguments': 'mode=supprPrp&id_prp='+id_prp,
                'done' : function() {
                    chargeEtape(0, 0);
                }
            }); // FIN ajax
        } else {
            chargeEtape(0, 0);
        }
    }); // FIN Retour vers le point d'entrée

    // Sélection client (valider)
    $('.btnSelectBlsOk').off("click.btnSelectBlsOk").on("click.btnSelectBlsOk", function(e) {
        e.preventDefault();

        var id_prp = parseInt($('#id_prp').val());
        var ids_bls_array = [];
        $('.carte-exp-bl.choisi').each(function () {
            ids_bls_array.push(parseInt($(this).data('id-bl')));
        });

        var ids_bls = ids_bls_array.join(',');
        if (isNaN(id_prp)) { id_prp = 0; }

        if (ids_bls === '') { alert("Aucun BL sélectionné ou identification des BL client échouée !"); return false; }
        if (id_prp === 0) { alert("Identification du PRP échouée !"); return false; }

        // On enregitre
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=savePrp&id_prp='+id_prp+'&ids_bls='+ids_bls,
            'done' : function() {
                chargeEtape(3,id_prp);
            }
        }); // FIN ajax
    });

    // Retour vers une étape du PRP
    $('.btnRetourEtape').off("click.btnRetourEtape'").on("click.btnRetourEtape", function(e) {
        e.preventDefault();
        var etape = parseInt($(this).data('id-etape'));
        var id_prp = parseInt($(this).data('id-prp'));
        if (isNaN(etape)) { etape = 0; }
        if (isNaN(id_prp)) { id_prp = 0; }
        chargeEtape(etape, id_prp);
    }); // FIN Retour vers l'étape du PRP




} // FIN listener ticket




// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 0
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape0(identifiant, skip_ticket) {
    "use strict";

    chargeTicket(0, 0);


    $('.btnAction').off("click.btnAction").on("click.btnAction", function(e) {
        e.preventDefault();

        var etape = parseInt($(this).data('etape'));
        if (isNaN(etape)) { etape = 0; }
        if (etape === 0) { return false; }

        // Si on appelle l'étape 1, on crée un nouveau PRP
       if (etape === 1) {
        // On enregitre
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=createPrpOp',
            'callBack' : function(id_prp) {
                id_prp+='';
                // Le retour est l'ID du PRP enregistré en base
                chargeEtape(etape,id_prp);
            }
        }); // FIN ajax
    } else {
        chargeEtape(etape,0);
    }
        return false;
    });


} // FIN listener etape

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 20
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape20(identifiant) {
    "use strict";

    chargeTicket(20, identifiant);

   // Sélection transporteur
    $('.carte-exp-trans').off("click.carteexptrans").on("click.carteexptrans", function(e) {
        e.preventDefault();

        var id_trans = parseInt($(this).data('id-trans'));
        if (isNaN(id_trans)) { id_trans = 0; }

        if (id_trans === 0) { alert("Identification du transporteur échouée !"); return false; }

        chargeEtape(21, id_trans);
        return false;
    });

} // FIN listener étape


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 1
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape1(identifiant) {
    "use strict";

    chargeTicket(1, identifiant);

    // Sélection transporteur
    $('.carte-exp-trans').off("click.carteexptrans").on("click.carteexptrans", function(e) {
        e.preventDefault();

        var id_prp = parseInt($('#id_prp').val());
        var id_trans = parseInt($(this).data('id-trans'));
        if (isNaN(id_trans)) { id_trans = 0; }
        if (isNaN(id_prp)) { id_prp = 0; }

        if (id_trans === 0) { alert("Identification du transporteur échouée !"); return false; }
        if (id_prp === 0) { alert("Identification du PRP échouée !"); return false; }

        // On enregitre
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=savePrp&id_prp='+id_prp+'&id_trans='+id_trans,
            'done' : function() {
                chargeEtape(2,id_prp);
            }
        }); // FIN ajax
        return false;
    });

} // FIN listener étape

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 2
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape2(identifiant) {
    "use strict";

    chargeTicket(2, identifiant);


    // Sélection client (choix)
    $('.carte-exp-bl').off("click.carteexpbl").on("click.carteexpbl", function(e) {
        e.preventDefault();

        $(this).toggleClass('choisi');
        if ($('.carte-exp-bl.choisi').length > 0) {
            $('.btnSelectBlsOk').show();
        } else {
            $('.btnSelectBlsOk').hide();
        }
    });





} // FIN listener etape

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 3
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape3(identifiant) {
    "use strict";

    chargeTicket(3, identifiant);

    $('.selectpicker').selectpicker('render');

    // On valide la date
    $('.btnValideDate').off("click.btnValideDate").on("click.btnValideDate", function(e) {
        e.preventDefault();

        var id_prp = parseInt($('#id_prp').val());
        if (isNaN(id_prp)) { id_prp = 0; }
        if (id_prp === 0) { alert("Identification du PRP échouée !"); return false; }


        var prp_jour = parseInt($('.prp-jour option:selected').val());
        var prp_mois = parseInt($('.prp-mois option:selected').val());
        var prp_an = parseInt($('.prp-an option:selected').val());

        if (isNaN(prp_jour)) { prp_jour = 0; }
        if (isNaN(prp_mois)) { prp_mois = 0; }
        if (isNaN(prp_an)) { prp_an = 0; }
        if (prp_jour < 10) { prp_jour = '0'+prp_jour;}
        if (prp_mois < 10) { prp_mois = '0'+prp_mois;}

        var date = prp_an+'-'+prp_mois+'-'+prp_jour;

        // On enregitre
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=savePrp&date='+date+'&id_prp=' + id_prp,
            'callBack' : function(retour) {
                retour+='';

                // Le retour est l'ID du PRP enregistré en base
                chargeEtape(4,retour);
            }
        }); // FIN ajax

    });

} // FIN listener etape




// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 4
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape4(identifiant) {
    "use strict";

    chargeTicket(4, identifiant);

    // On valide la conformité commande
    $('.btnCmdConforme').off("click.btnCmdConforme").on("click.btnCmdConforme", function(e) {
        e.preventDefault();

        var id_prp = parseInt($('#id_prp').val());
        if (isNaN(id_prp)) { id_prp = 0; }
        if (id_prp === 0) { alert("Identification du PRP échouée !"); return false; }

        var conf_cmd = parseInt($(this).data('conformite'));
        if (isNaN(conf_cmd)) { conf_cmd = -1; }
        if (conf_cmd === -1) { alert('Echec identification choix !'); return false; }

        // On enregitre
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=savePrp&conf_cmd='+conf_cmd+'&id_prp=' + id_prp,
            'callBack' : function(retour) {
                retour+='';

                // Le retour est l'ID du PRP enregistré en base
                chargeEtape(5,retour);
            }
        }); // FIN ajax

    }); // FIN conformité commande

} // FIN listener etape


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 5
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape5(identifiant) {
    "use strict";

    chargeTicket(5, identifiant);

    var champ = $('#temp');
    if (champ === undefined) { return false; }

    // Clavier
    $('.clavier .btn').off("click.clavier").on("click.clavier", function(e) {
        e.preventDefault();

        var touche = $(this).data('valeur');
        if (touche === undefined) { return false; }

        // Si chiffre, on ajoute au champ
        if (touche !== 'S' && touche !== '.') {
            // Sauf si on a trop de caractères
            if (champ.val().length > 5) { return false; }
            champ.val(champ.val() + touche);
        }
        // Si point décimal
        if (touche === '.') {
            // Sauf si on a déjà une décimale dans le champ
            if (champ.val().indexOf('.') !== -1) { return false; }
            champ.val(champ.val() + touche);
        }
        // Si signe +/-
        if (touche === 'S') {
            var temperature = champ.val().slice(0,1) === '-' ?  champ.val().substring(1, champ.val().length) : '-' + champ.val();
            champ.val(temperature);
        }
    }); // FIN touches clavier

    // Touche effacer
    $('.btnSupprChar').off("click.btnSupprChar").on("click.btnSupprChar", function(e) {
        e.preventDefault();
        if (champ.val().length === 0) { return false; }
        champ.val(champ.val().slice(0,-1));
    });

    // Conforme/Non-conforme
    $('.btnTempConforme').off("click.btnTempConforme").on("click.btnTempConforme", function(e) {
        e.preventDefault();

        var t_surface =  champ.val();
        if (t_surface === '' || t_surface === undefined) { return false; }

        var id_prp = parseInt($('#id_prp').val());
        if (isNaN(id_prp)) { id_prp = 0; }
        if (id_prp === 0) { alert("Identification du PRP échouée !"); return false; }

        var conf_temp = parseInt($(this).data('conformite'));
        if (isNaN(conf_temp)) { conf_temp = -1; }
        if (conf_temp === -1) { alert('Echec identification choix !'); return false; }

        // On enregitre
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=savePrp&conf_t_surface='+conf_temp+'&id_prp='+id_prp+'&t_surface='+t_surface,
            'callBack' : function(retour) {
                retour+='';

                // Le retour est l'ID du PRP enregistré en base
                chargeEtape(6,retour);
            }
        }); // FIN ajax

    }); // FIN conformité commande


} // FIN listener etape

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 6
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape6(identifiant) {
    "use strict";

    chargeTicket(6, identifiant);

    var champ = $('#temp');
    if (champ === undefined) { return false; }

    // Clavier
    $('.clavier .btn').off("click.clavier").on("click.clavier", function(e) {
        e.preventDefault();

        var touche = $(this).data('valeur');
        if (touche === undefined) { return false; }

        // Si chiffre, on ajoute au champ
        if (touche !== 'S' && touche !== '.') {
            // Sauf si on a trop de caractères
            if (champ.val().length > 5) { return false; }
            champ.val(champ.val() + touche);
        }
        // Si point décimal
        if (touche === '.') {
            // Sauf si on a déjà une décimale dans le champ
            if (champ.val().indexOf('.') !== -1) { return false; }
            champ.val(champ.val() + touche);
        }
        // Si signe +/-
        if (touche === 'S') {
            var temperature = champ.val().slice(0,1) === '-' ?  champ.val().substring(1, champ.val().length) : '-' + champ.val();
            champ.val(temperature);
        }
    }); // FIN touches clavier

    // Touche effacer
    $('.btnSupprChar').off("click.btnSupprChar").on("click.btnSupprChar", function(e) {
        e.preventDefault();
        if (champ.val().length === 0) { return false; }
        champ.val(champ.val().slice(0,-1));
    });

    // Conforme/Non-conforme
    $('.btnTempConforme').off("click.btnTempConforme").on("click.btnTempConforme", function(e) {
        e.preventDefault();

        var t_camion =  champ.val();
        if (t_camion === '' || t_camion === undefined) { return false; }

        var id_prp = parseInt($('#id_prp').val());
        if (isNaN(id_prp)) { id_prp = 0; }
        if (id_prp === 0) { alert("Identification du PRP échouée !"); return false; }

        var conf_temp = parseInt($(this).data('conformite'));
        if (isNaN(conf_temp)) { conf_temp = -1; }
        if (conf_temp === -1) { alert('Echec identification choix !'); return false; }
        
        var formData = $('#pointControleExpedition').serialize();
  
        // On enregitre
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=savePrp&conf_t_camion='+conf_temp+'&id_prp='+id_prp+'&t_camion='+t_camion+'&pointcontrol='+formData,
            'callBack' : function(retour) {
                retour+='';

                // Le retour est l'ID du PRP enregistré en base
                chargeEtape(7,retour);
            }
        }); // FIN ajax

    }); // FIN conformité commande

} // FIN listener etape



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


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 7
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape7(identifiant) {
    "use strict";

    chargeTicket(7, identifiant);

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

    
    // On valide la conformité commande
    $('.btnEmbConforme').off("click.btnEmbConforme").on("click.btnEmbConforme", function(e) {
        e.preventDefault();

        var id_prp = parseInt($('#id_prp').val());
        if (isNaN(id_prp)) { id_prp = 0; }
        if (id_prp === 0) { alert("Identification du PRP échouée !"); return false; }

        var conf_emb = parseInt($(this).data('conformite'));
        if (isNaN(conf_emb)) { conf_emb = -1; }
        if (conf_emb === -1) { alert('Echec identification choix !'); return false; }

        var formData = new FormData();
        $('input[name^="point"]').each(function() {
            if (this.checked) {
                formData.append(this.name, this.value);
            }
        });

        formData.append('conf_emb', conf_emb);
        formData.append('id_prp', id_prp);
        formData.append('mode', 'savePvisuApres');

        
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/",
            'script_execute': 'fct_vue_exp.php',
            'form_id': 'pointControleExpedition',
            'data': 'formData',
            'contentType': 'false',
            'processData': 'false',
            'callBack': function(retour) {
                retour += '';
                chargeEtape(8, retour);
            },
        });

    }); // FIN conformité commande

} // FIN listener étape

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 21
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape21(identifiant) {
    "use strict";
    chargeTicket(21, identifiant);

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

    // Valider
    $('.btnValider').off("click.btnValider").on("click.btnValider", function(e) {
        e.preventDefault();

        var id_transporteur = parseInt($('#id_prp').val()); // On utilise le meme pour stocker les deux
        if (isNaN(id_transporteur)) { id_transporteur = 0; }
        if (id_transporteur === 0) { alert("Identification du transporteur échouée !"); return false; }

        var palettes_recues = parseInt($('#palettes_recues').val());
        var palettes_rendues = parseInt($('#palettes_rendues').val());
        if (isNaN(palettes_recues) || palettes_recues < 0) { palettes_recues = 0; }
        if (isNaN(palettes_rendues) || palettes_rendues < 0) { palettes_rendues = 0; }

        var donnees = id_transporteur+'|'+palettes_recues+'|'+palettes_rendues;

        chargeEtape(22,donnees);

    });
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 22
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape22(donnees) {
    "use strict";
    chargeTicket(22, donnees);

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

    // Valider
    $('.btnValider').off("click.btnValider").on("click.btnValider", function(e) {
        e.preventDefault();

        var donnees = $('#id_prp').val();
        if (donnees === '' || donnees === undefined) { alert("Récupération des données échouée !"); return false; }

        var crochets_recus = parseInt($('#crochets_recus').val());
        var crochets_rendus = parseInt($('#crochets_rendus').val());
        if (isNaN(crochets_recus) || crochets_recus < 0) { crochets_recus = 0; }
        if (isNaN(crochets_rendus) || crochets_rendus < 0) { crochets_rendus = 0; }

        var donnees2 = donnees+'|'+crochets_recus+'|'+crochets_rendus;
        chargeEtape(23,donnees2);
    });
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 23
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape23(donnees) {
    "use strict";
    chargeTicket(23, donnees);

    $("#signature").jSignature();

    $('.btnEffacer').off("click.btnEffacer").on("click.btnEffacer", function(e) {
        e.preventDefault();
        $("#signature").jSignature('reset');
    });

    // On valide tout ça
    $('.btnFin').off("click.btnFin").on("click.btnFin", function(e) {
        e.preventDefault();



        var datapair = $("#signature").jSignature("getData", "image");
        var i = new Image();
        i.src = "data:" + datapair[0] + "," + datapair[1];

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=savePalettesCrochetsLibres&donnees='+donnees+'&image=' + i.src,
            'callBack': function (retour) {
                retour+='';
                if (parseInt(retour) !== 1) {
                    alert(retour);
                    return false;
                }
                chargeEtape(0,0);

            } // FIN Callback
        }); // FIN ajax


    }); // FIN terminer

}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 8
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape8(identifiant) {
    "use strict";

    chargeTicket(8, identifiant);


    var champ = $('#poids');
    if (champ === undefined) { return false; }

    // Clavier
    $('.clavier .btn').off("click.clavier").on("click.clavier", function(e) {
        e.preventDefault();

        var touche = $(this).data('valeur');
        if (touche === undefined) { return false; }

        // Si chiffre, on ajoute au champ
        if (touche !== 'S' && touche !== '.') {
            // Sauf si on a trop de caractères
            if (champ.val().length > 6) { return false; }
            champ.val(champ.val() + touche);
        }
        // Si point décimal
        if (touche === '.') {
            // Sauf si on a déjà une décimale dans le champ
            if (champ.val().indexOf('.') !== -1) { return false; }
            champ.val(champ.val() + touche);
        }
        // Si signe +/-
        if (touche === 'S') {
            champ.val('');
        }
    }); // FIN touches clavier

    // Touche effacer
    $('.btnSupprChar').off("click.btnSupprChar").on("click.btnSupprChar", function(e) {
        e.preventDefault();
        if (champ.val().length === 0) { return false; }
        champ.val(champ.val().slice(0,-1));
    });

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

    // Valider
    $('.btnValider').off("click.btnValider").on("click.btnValider", function(e) {
        e.preventDefault();

        var id_prp = parseInt($('#id_prp').val());
        if (isNaN(id_prp)) { id_prp = 0; }
        if (id_prp === 0) { alert("Identification du PRP échouée !"); return false; }

        var poids = parseFloat($('#poids').val());
        var palettes_recues = parseInt($('#palettes_recues').val());
        var palettes_rendues = parseInt($('#palettes_rendues').val());
        if (isNaN(poids) || poids < 0) { poids = 0; }
        if (isNaN(palettes_recues) || palettes_recues < 0) { palettes_recues = 0; }
        if (isNaN(palettes_rendues) || palettes_rendues < 0) { palettes_rendues = 0; }

        // On enregitre
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=savePrp&id_prp=' + id_prp + '&poids='+poids+'&palettes_recues='+palettes_recues+'&palettes_rendues='+palettes_rendues,
            'callBack' : function(retour) {
                retour+='';

                // Le retour est l'ID du PRP enregistré en base
                chargeEtape(9,retour);
            }
        }); // FIN ajax

    });



} // FIN listener étape

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 9
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape9(identifiant) {
    "use strict";

    chargeTicket(9, identifiant);

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

    // Valider
    $('.btnValider').off("click.btnValider").on("click.btnValider", function(e) {
        e.preventDefault();

        var id_prp = parseInt($('#id_prp').val());
        if (isNaN(id_prp)) { id_prp = 0; }
        if (id_prp === 0) { alert("Identification du PRP échouée !"); return false; }

        var crochets_recus = parseInt($('#crochets_recus').val());
        var crochets_rendus = parseInt($('#crochets_rendus').val());
        if (isNaN(crochets_recus) || crochets_recus < 0) { crochets_recus = 0; }
        if (isNaN(crochets_rendus) || crochets_rendus < 0) { crochets_rendus = 0; }

        // On enregitre
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=savePrp&id_prp=' + id_prp + '&crochets_recus='+crochets_recus+'&crochets_rendus='+crochets_rendus,
            'callBack' : function(retour) {
                retour+='';

                // Le retour est l'ID du PRP enregistré en base
                chargeEtape(10,retour);
            }
        }); // FIN ajax

    });

} // FIN listener étape



// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// Listener de l'étape 10
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
function listenerEtape10(identifiant) {
    "use strict";

    chargeTicket(10, identifiant);
    $("#signature").jSignature();

    $('.btnEffacer').off("click.btnEffacer").on("click.btnEffacer", function(e) {
        e.preventDefault();
        $("#signature").jSignature('reset');
    });

    // On valide la conformité commande
    $('.btnFin').off("click.btnFin").on("click.btnFin", function(e) {
        e.preventDefault();

        var id_prp = parseInt($('#id_prp').val());
        if (isNaN(id_prp)) { id_prp = 0; }
        if (id_prp === 0) { alert("Identification du PRP échouée !"); return false; }

        var datapair = $("#signature").jSignature("getData", "image");
        var i = new Image();
        i.src = "data:" + datapair[0] + "," + datapair[1];

        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=saveSignature&id_prp='+id_prp+'&image=' + i.src,
            'callBack': function (retour) {
            } // FIN Callback
        }); // FIN ajax

        // On enregitre
        $.fn.ajax({
            'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=savePrp&fin&id_prp=' + id_prp,
            'done' : function() {
                chargeEtape(0,0);
            }
        }); // FIN ajax


    }); // FIN terminer

} // FIN listener étape
