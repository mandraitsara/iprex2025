<canvas id="graphEspClients2" width="400" height="200" class="graph-t2b"></canvas>
<canvas id="graphEspClientseuille2" width="400" height="200" class="graph-t2b"></canvas>
<?php if ($show_marges) { ?><canvas id="graphEspClients2Marges" width="400" height="200" class="graph-t2b graph-marges"></canvas><?php } ?>
<script type="text/javascript">
    var ctx = document.getElementById("graphEspClients2").getContext('2d');
    var graphAbattoirs = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [<?php echo $graph_labels; ?>],
            datasets: [{
                data: [<?php echo $graph_datas; ?>],
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
	<?php if ($show_marges  && !empty($liste)) { ?>
    var ctx = document.getElementById("graphEspClients2Marges").getContext('2d');
    var graphEspClients2Marges = new Chart(ctx, {
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

     var ctxseuille = document.getElementById("graphEspClientseuille2").getContext('2d');
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