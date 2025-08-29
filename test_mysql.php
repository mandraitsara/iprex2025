<?php
    $servername = "127.0.0.1:3306"; // Remplacez par le nom de votre serveur MySQL
    $username = "iprex_prod"; // Remplacez par le nom d'utilisateur MySQL
    $password = "kVKZmNdMIP8AdKfe"; // Remplacez par le mot de passe MySQL
    $database = "iprex_prod"; // Remplacez par le nom de la base de données

    // Crée une connexion à la base de données
    $conn = new mysqli($servername, $username, $password, $database);

    // Vérifie la connexion
    if ($conn->connect_error) {
        die("La connexion à la base de données a échoué : " . $conn->connect_error);
    }

    echo "Connexion à la base de données MySQL réussie ! <br />";

    // Exécute une requête SQL simple
    $sql = "SELECT * FROM pe_cron"; // Remplacez "nom_de_la_table" par le nom de votre table
    $result = $conn->query($sql);

    // var_dump($result);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "ID : " . $row["id"] . " - Fichier : " . $row["fichier"] . "<br>";
        }
    } else {
        echo "Aucun résultat trouvé.";
    }

    // Ferme la connexion
    $conn->close();

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
        echo "<br />Connexion en PDO à la base de données MySQL réussie ! <br />";
    
        // Exécute une requête SQL simple
        $sql = "SELECT * FROM pe_cron"; // Remplacez "nom_de_la_table" par le nom de votre table
        $result = $conn->query($sql);
    
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch()) {
                echo "ID : " . $row["id"] . " - Fichier : " . $row["fichier"] . "<br>";
            }
        } else {
            echo "Aucun résultat trouvé.";
        }
    
        // Ferme la connexion
        $conn = null;
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
?>