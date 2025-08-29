<?php if ($show_marges && !empty($liste)) {
    ?>
    <h2>Marges</h2>
    <table class="table admin table-v-middle table-marges">
        <tr>
            <td>CA sur la période</td>
            <th class="text-right w-court-admin-cell text-16"><?php echo number_format($total_ca,$deci,'.', ' ');?> €</th>
        </tr>
        <tr>
            <td>Prix d'achats</td>
            <th class="text-right w-court-admin-cell texte-fin text-16"><?php echo number_format($total_ca - $total_marge_brute,$deci,'.', ' ');?> €</th>
        </tr>
        <tr>
            <td>Marge Brute</td>
            <th class="text-right w-court-admin-cell text-18"><?php echo number_format($total_marge_brute,$deci,'.', ' ');?> €</th>
        </tr>
        <tr>
            <td>Taux de marge brute</td>
            <th class="text-right w-court-admin-cell texte-fin bg-secondary"><?php echo number_format(($total_marge_brute * 100) / $total_ca,$deci,'.', ' ');?> %</th>
        </tr>
        <tr>
            <td>Frais de fonctionnement</td>
            <th class="text-right w-court-admin-cell texte-fin text-16"><?php echo number_format($ff,$deci,'.', ' ');?> €</th>
        </tr>
        <tr>
            <td>Marge nette</td>
            <th class="text-right w-court-admin-cell text-18"><?php echo number_format($total_marge_brute-$ff,$deci,'.', ' ');?> €</th>
        </tr>
        <tr>
            <td>Taux de marge nette</td>
            <th class="text-right w-court-admin-cell texte-fin bg-secondary"><?php echo number_format((($total_marge_brute-$ff) * 100) / $total_ca,0,'.', ' ');?> %</th>
        </tr>
    </table>
    <?php
    $graph_datas_marges = $total_ca - $total_marge_brute < 0 ? 0 : round($total_ca - $total_marge_brute, 0);
    $graph_datas_marges.= ',';
    $graph_datas_marges.= $ff < 0 ? 0 : round($ff, 0);
    $graph_datas_marges.= ',';
    $graph_datas_marges.= ($total_marge_brute) - $ff < 0 ? 0 : round(($total_marge_brute) - $ff, 0);
}
