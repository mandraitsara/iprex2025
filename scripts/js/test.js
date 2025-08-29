$(document).ready(function() {
    $("#signature").jSignature();


    $('.btnEffacer').off("click.btnEffacer").on("click.btnEffacer", function(e) {
        e.preventDefault();
        $("#signature").jSignature('reset');
    });

    $('.btnSave').off("click.btnSave").on("click.btnSave", function(e) {
        e.preventDefault();

        var datapair = $("#signature").jSignature("getData", "image");
        var i = new Image();
        i.src = "data:" + datapair[0] + "," + datapair[1];
        //$(i).appendTo($("#someelement")); // append the image (SVG) to DOM.


        $.fn.ajax({
            //'rep_script_execute': "../scripts/ajax/", // Gestion de l'URL Rewriting
            'script_execute': 'fct_vue_exp.php',
            'arguments': 'mode=saveSignature&image=' + i.src,
            'callBack': function (retour) {
                alert(retour);
            } // FIN Callback
        }); // FIN ajax

    });






}); // FIN ready