<canvas id="graphCltGeneral" width="400" height="200" class="graph-t2b"></canvas>
<canvas id="graphCltGeneralseuille" width="400" height="200" class="graph-t2b"></canvas>
<?php if ($show_marges) { ?><canvas id="graphCltGeneralMarges" width="400" height="200" class="graph-t2b"></canvas><?php }


$toto = explode(',', $graph_labels);
$toto2 = array_slice($toto, 0, 5);
$toto3 = implode(',', $toto2);

$titi = explode(',', $graph_datas);
$titi2 = array_slice($titi, 0, 5);
$titi3 = implode(',', $titi2);

?>
<script type="text/javascript">
    var ctx = document.getElementById("graphCltGeneral").getContext('2d');
    var graphAbattoirs = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [<?php echo $toto3; ?>],
            datasets: [{
                data: [<?php echo $titi3; ?>],
                backgroundColor: [
                    'rgba(75, 192, 75, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 99, 132, 0.2)'
                ],
                borderColor: [
                    'rgba(75, 192, 75, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgb(54,162,235)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {

            legend: {
                position: 'bottom'
            },
            title: {
                display: false
            }
        }
    });
	<?php if ($show_marges  && !empty($liste)) { ?>
    var ctx = document.getElementById("graphCltGeneralMarges").getContext('2d');
    var graphCltGeneralMarges = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [["Achats"], ["Fonctionnement"], ["Marge nette"]],
            datasets: [{
                data: [<?php echo $graph_datas_marges; ?>],
                backgroundColor: [
                    'rgba(255, 159, 64, 0.2)',
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 159, 64, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {

            legend: {
                position: 'bottom'
            },
            title: {
                display: false
            }
        }
    });
	<?php } ?>
    /**
     * seuille
     */

     var ctxseuille = document.getElementById("graphCltGeneralseuille").getContext('2d');
    var graphAbattoirs = new Chart(ctxseuille, {
        type: 'doughnut',
        data: {
            labels: [<?php echo $graph_labels_seuille; ?>],
            datasets: [{
                data: [<?php echo $graph_datas_seuille; ?>],
                backgroundColor: [
                    'rgba(75, 192, 75, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 99, 132, 0.2)'
                ],
                borderColor: [
                    'rgba(75, 192, 75, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {

            legend: {
                position: 'bottom'
            },
            title: {
                display: false
            }
        }
    });
</script>