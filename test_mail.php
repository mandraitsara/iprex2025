<?php
    // Définir les informations de l'e-mail
    // $to = 'ppactol@boostervente.com'; // Remplacez par l'adresse e-mail du destinataire
    $to = 'ppactol@boostervente.com'; // Remplacez par l'adresse e-mail du destinataire
    $subject = 'Test d\'envoi de mail'; // Sujet de l'e-mail
    $message = 'Ceci est un test pour vérifier l\'envoi de mail depuis le serveur.'; // Corps du message
    $headers = 'From: info@profilexport.fr' . "\r\n" . // Remplacez par votre adresse e-mail
        'Reply-To: info@profilexport.fr' . "\r\n" . // Remplacez par votre adresse e-mail
        'X-Mailer: PHP/' . phpversion();

    // Envoyer l'e-mail
    if(mail($to, $subject, $message, $headers)) {
        echo 'Le mail a été envoyé avec succès à ' . $to;
    } else {
        echo 'Échec de l\'envoi du mail à ' . $to;
    }
?>
