/*
   _|_|_|  _|_|_|      _|_|
 _|        _|    _|  _|    _|  CBO FrameWork
 _|        _|_|_|    _|    _|  (c) 2018 Cédric Bouillon
 _|        _|    _|  _|    _|
   _|_|_|  _|_|_|      _|_|
--------------------------------------------------------
Initialisation des dépendances globales t services JS
------------------------------------------------------*/
$(document).ready(function() {

	// Bug MSIE 11 selectPicker
	var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE");
    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {}
    else {
        $('.selectpicker').selectpicker({
			liveSearchPlaceholder:'Rechercher...'
		});
    } // FIN patch bug MSIE 11 selectPicker

	function hms(){
		var today=new Date();
		var hrs=today.getHours(),mns=today.getMinutes(),scd=today.getSeconds();
		//var str=(hrs<10?"0"+hrs:hrs)+":"+(mns<10?"0"+mns:mns)+":"+(scd<10?"0"+scd:scd);
		var str=(hrs<10?"0"+hrs:hrs)+"<span class='reveil-clignote'>:</span>"+(mns<10?"0"+mns:mns);
		$('.heure-dynamique').html(str);
		setTimeout(hms,1000);// réécriture toutes les 1000 millisecondes
	}

	if ($('.menu-vues').length) {
		hms();// lancement de la fonction pour mise à jour de l'heure

		$('.menu-vues a').click(function() {
			$(this).find('i').removeClass().addClass('fa fa-fw fa-spin fa-spinner');
		});
	}


	/**--------------------------------------
	 Retour en haut de page
     --------------------------------------*/
	if ($('body').innerWidth() > 1100) {
	
		$('body').append('<a href="#top" class="top_link hidden-xs" title="Revenir en haut de page"><i class="fa fa-arrow-alt-circle-up fa-lg"></i></a>');
		$('.top_link').css({  
			'position'              :   'fixed',  
			'right'                 :   '10px',  
			'bottom'                :   '25px',
			'display'               :   'none',  
			'padding'               :   '5px',  
			'background'            :   '#fff',  
			'border-radius'         :   '5px',  
			'opacity'               :   '0.9',  
			'z-index'               :   '2000'  
		});  

		 $(window).scroll(function(){  
			var posScroll = $(document).scrollTop();  
			if (posScroll >= 100) {
				$('.top_link').fadeIn(600);  
				$('footer.main-footer').show();
			} else {  
				$('.top_link').fadeOut(600);  
				$('footer.main-footer').hide();
			}
		});
		$('a[href="#top"]').click(function(){
			$('html, body').animate({scrollTop:0}, 'slow');
				return false;
		});
		
	} // FIN Retour en haut de page

    /**--------------------------------------
     DatePicker
     --------------------------------------*/
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

    /**--------------------------------------
     Tooltips
     --------------------------------------*/
    $('[data-toggle="tooltip"]').tooltip();


    /**--------------------------------------
     iCheck
     --------------------------------------*/
	$("input[type=radio].icheck").iCheck(
		{radioClass: "iradio_square-blue"}
	);
	$("input[type=checkbox].icheck").iCheck(
		{checkboxClass: "icheckbox_square-blue"}
	);

    /**--------------------------------------
     Classe CSS Autocomplete
     --------------------------------------*/
	$('.input-autocomplete').after('<i class="fa fa-list-ul icon-autocomplete"></i><div class="autocomplete-spinner"></div>');
   	$(".input-autocomplete").on( "autocompleteopen", function( event, ui ) {
   		if (!$('.autocomplete-backdrop').length) {
   				$('<div class="modal-backdrop autocomplete-backdrop"></div>').appendTo(document.body);
			}
	   	});
	   	$( ".input-autocomplete" ).on( "autocompleteclose", function( event, ui ) {
	   		$(".modal-backdrop").remove();
		});

    /**--------------------------------------
     Popover
     --------------------------------------*/
    $('.infobulle').popover({
        container: 'body',
        trigger: 'focus'
    });

    /**--------------------------------------
     Debug bar
     --------------------------------------*/
	   	if ($('#debugbar').length) {
	   		$('#debugbar').prependTo('body');

            $('.btnCloseDebugBar').click(function() {

            	alert("Le mode DEBUG va être désactivé pendant 1H.\r\nPour le réactivez, passez le paramètre ?debugmode=on dans l'URL.");

                $.fn.ajax({
                    'script_execute':'fct_ajax.php',
                    'arguments':'mode=closeDebugBar',
                    done:function() {
                        location.reload();
                    }
                });
            });

            $('.debug-requettes').click(function() {

            	if ($('#detailsRequetesPdoDebug').hasClass('d-none')) {
					$('#detailsRequetesPdoDebug').removeClass('d-none');

					if (document.location.href.match(/\/$/g) === null) {
						$.fn.ajax({
							'script_execute':'fct_ajax.php',
							'arguments':'mode=detailsRequetesPdoDebug',
							'return_id':'detailsRequetesPdoDebug'
						});
					} else {
						$.fn.ajax({
							'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
							'script_execute':'fct_ajax.php',
							'arguments':'mode=detailsRequetesPdoDebug',
							'return_id':'detailsRequetesPdoDebug'
						});
					}



				} else {
					$('#detailsRequetesPdoDebug').html('<i class="fa fa-spin fa-spinner"></i>');
					$('#detailsRequetesPdoDebug').addClass('d-none');
				}

            });

            $('.btn-clean-debug-requettes').click(function() {

				if (document.location.href.match(/\/$/g) === null) {
					$.fn.ajax({
						'script_execute':'fct_ajax.php',
						'arguments':'mode=cleanDetailsRequetesPdoDebug',
						'done':function() {
							$.fn.ajax({
								'script_execute':'fct_ajax.php',
								'arguments':'mode=detailsRequetesPdoDebug',
								'return_id':'detailsRequetesPdoDebug'
							});
						}
					});
				} else {
					$.fn.ajax({
						'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
						'script_execute':'fct_ajax.php',
						'arguments':'mode=cleanDetailsRequetesPdoDebug',
						'done':function() {
							$.fn.ajax({
								'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
								'script_execute':'fct_ajax.php',
								'arguments':'mode=detailsRequetesPdoDebug',
								'return_id':'detailsRequetesPdoDebug'
							});
						}
					});
				}



			});



		} // FIN test debug barre

	/**--------------------------------------
	 Plein Ecran
	 --------------------------------------*/
	$("#btnFullScreen").on('click', function() {
		if(IsFullScreenCurrently()) {
			GoOutFullscreen();
		} else {
			GoInFullscreen($("#body2fs").get(0));
		}
	});
	$(document).on('fullscreenchange webkitfullscreenchange mozfullscreenchange MSFullscreenChange', function() {
		if(IsFullScreenCurrently()) {
			$("#btnFullScreen i.fa").removeClass('fa-expand-arrows-alt').addClass('fa-window-maximize');
			$('#vue.vue-cgl .table-scroll-contenu  tbody').addClass('pleinecran');
		} else {
			$("#btnFullScreen i.fa").removeClass('fa-window-maximize').addClass('fa-expand-arrows-alt');
			$('#vue.vue-cgl .table-scroll-contenu  tbody').removeClass('pleinecran');
		}
	}); // FIN plein écran


}); // FIN Ready


// Fonction passage en plein écran
function GoInFullscreen(element) {

	if (element.requestFullscreen) {
		element.requestFullscreen();
	} else if(element.mozRequestFullScreen) {
		element.mozRequestFullScreen();
	} else if(element.webkitRequestFullscreen) {
		element.webkitRequestFullscreen();
	} else if(element.msRequestFullscreen) {
		element.msRequestFullscreen();
	}
} // FIN fonction vers plein écran


// Fonction quitter le plein écran
function GoOutFullscreen() {
	if(document.exitFullscreen) {
		document.exitFullscreen();
	} else if(document.mozCancelFullScreen) {
		document.mozCancelFullScreen();
	} else if(document.webkitExitFullscreen) {
		document.webkitExitFullscreen();
	} else if(document.msExitFullscreen) {
		document.msExitFullscreen();
	}
} // FIN fonction quitter le plein écran


// Fonction test mode plein écran pour adapter le bouton
function IsFullScreenCurrently() {

	var full_screen_element = document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement || null;

	if(full_screen_element === null) {
		return false;
	} else {
		return true;
	}
} // FIN fonction test mode plein écran



// Equivalent de la fonction PHP number_format
function number_format(number, decimals, dec_point, thousands_sep) {
	"use strict";
	number = parseFloat(number).toFixed(decimals);

	var nstr = number.toString();
	nstr += '';
	var x = nstr.split('.');
	var x1 = x[0];
	var x2 = x.length > 1 ? dec_point + x[1] : '';
	var rgx = /(\d+)(\d{3})/;

	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + thousands_sep + '$2');
	}

	return x1 + x2;
}
