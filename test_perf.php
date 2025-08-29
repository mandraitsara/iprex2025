<?php
    echo '<h1>Tests de performances</h1>' . $_SERVER['HTTP_HOST'];

    $t0 = microtime(true);
    echo '<h2>Test sur une simple boucle : 100 000 000 additions</h2>';
    for ($i=0; $i < 100000000; $i++) { 
        $j = $i +1;
    }
    $t1 = microtime(true);
    echo number_format($t1 - $t0, 2) . ' secondes.';

    echo '<h2>Test écriture d\'un fichier de 1 000 000 lignes</h2>';
    $file ="file.txt";
    
    $fileopen=(fopen("$file",'a'));
    for ($i=0; $i < 1000000; $i++) { 
        fwrite($fileopen,$i);
    }
    fclose($fileopen);

    $t2 = microtime(true);
    echo number_format($t2 - $t1, 2) . ' secondes.';

    echo '<h2>Test de lecture d\'un fichier de 1 000 000 lignes</h2>';

    $handle = fopen($file, 'r');
    if ($handle)
    {
        while (!feof($handle))
        {
            $buffer = fgets($handle);
        }
        fclose($handle);
    }
    
    $t3 = microtime(true);
    echo number_format($t3 - $t2, 4) . ' secondes.';

    unlink($file);
?>