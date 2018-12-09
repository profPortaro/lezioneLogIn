<?php
    include_once "script//setup.php";
    if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
        $mail = $_POST['mail'];
        $psw = $_POST['psw'];
        $db = new db("amministrazione.ini");
        if( !$db->get_connessione() ) {
            //gestione dell'errore
            echo "Connessione al server fallita. Impossibile procedere. Contattare ...";
            die;  
        } else {
            echo "BENVENUTO";
        }


    } else {
        header("Location: index.php?errore=4"); //non autenticato
        exit;  
    }

?>
