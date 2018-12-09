<?php
class gestore_sessioni {
    public static $db_sessione;
  
    public static function sess_open($sess_path, $sess_name) {
        $cartella_ini = $_SERVER['DOCUMENT_ROOT'].'//ini';    
        self::$db_sessione = new db("amministrazione.ini");
        return true;
        }

    public static function sess_close() {
        self::$db_sessione->close();  
        return true;
    }

    public static function sess_read($sess_id){
        $stringaSQL = "SELECT dati FROM sessioni ".
                       "WHERE idSessione = :sess_id";
        $parametri = array(':sess_id');
        $valori = array($sess_id);

        $risultato = self::$db_sessione->select($stringaSQL, $parametri, $valori);

        $parametri = array(':sess_id' => $sess_id,
                           ':adesso' => time() );  
      
        if ( count($risultato) === 0 ) {
            $stringaSQL = "INSERT INTO sessioni (idSessione, ultimoAccesso) ".
                          "VALUES (:sess_id, :adesso);";      
                        
            $risultato = self::$db_sessione->execute( $stringaSQL, $parametri );     
            return '';
        } else {
            $stringaSQL = "UPDATE sessioni SET ultimoAccesso = :adesso ".
                          "WHERE idSessione = :sess_id";
            self::$db_sessione->execute( $stringaSQL, $parametri );         
            return $risultato['dati'];
        }
    }
    
    public static function sess_write($sess_id, $data) {   
        $stringaSQL = "UPDATE sessioni SET dati = :data, ".
                      "ultimoAccesso = :adesso WHERE idSessione = :sess_id";
        $parametri = array( ':adesso'   => time(),
                            ':sess_id'  => $sess_id,
                            ':data'     => $data );   

        self::$db_sessione->execute( $stringaSQL, $parametri );
        return true;
    }

    public static function sess_destroy($sess_id) {        
        $stringaSQL = "DELETE FROM sessioni WHERE idSessione = :sess_id";
        $parametri = array(':sess_id' => $sess_id );
        self::$db_sessione->execute( $stringaSQL, $parametri );
        return true;
    }
  
    public static function sess_gc($sess_maxlifetime) {
        $stringaSQL = "DELETE FROM sessioni ".
                      "WHERE :adesso - ultimoAccesso > :maxLife";
        $parametri = array( ':adesso'   =>  time(), 
                            ':maxLife'  =>  $sess_maxlifetime ); 
        self::$db_sessione->execute( $stringaSQL, $parametri );
        return true;
    }     
}
    
    session_set_save_handler("gestore_sessioni::sess_open", "gestore_sessioni::sess_close",
                             "gestore_sessioni::sess_read","gestore_sessioni::sess_write",
                             "gestore_sessioni::sess_destroy", "gestore_sessioni::sess_gc");
?>