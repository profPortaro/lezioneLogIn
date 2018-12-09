<?php 
//la classe db è pensata per lavorare con un db specifico
//per cui se servisse cambiare database bisognerebbe istanziare un nuovo
//oggetto db
class db 
{				
  private
    $conn,				   //riferimento alla connessione	
    $accessData,     	   //credenziali lette da .ini
    $nl = "<br />";
      
  private function connessione() {
    if( !isset($this->conn) ) {         
      $this->conn = new PDO("mysql:host=".$this->accessData['host'].";dbname=".$this->accessData['schema'],
                            $this->accessData['username'],$this->accessData['password'],
                            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
      //$this->ATTIVAZIONE ECCEZIONI PER METODO QUERY 
      $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } 
  }
    
  public function __construct($iniFile) {
    //recupero credenziali da file ESTERNO alla cartella pubblica del sito
    $this->accessData=parse_ini_file($_SERVER['DOCUMENT_ROOT']."//ini//".$iniFile);
    $this->connessione();
  }
    
  public function close() {
    $this->conn = null;
  }
      
  public function get_connessione() {
    //return $this->conn->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    return ($this->conn != false);
  }
      
  public function get_dbname(){
      return $this->accessData['schema'];
  }	      
    
  public function escape_string($parametro) {
    return mysqli_real_escape_string( $this->conn, $parametro );
  }
    
  private function esegui($comando, $parametri = null, $nonQuery = false){
    try {
      $this->conn->exec("SET CHARACTER SET utf8");
      if ($parametri !== null) {
          $comando->execute($parametri);	
      } else {
          $comando->execute();
      }        	
      if ($nonQuery === false ){
          if ( $this->controllaRisultato($comando) ) {
            return $this->stampaRisultato($comando);
          }  
      } else {
          return $comando;
      }
    } catch (Exception $e) {
      echo 'ERRORE: '.$e->getMessage()."<br/>";
      $this->close();
    }
  }
        
  private function controllaRisultato($comando) {
    $stato = false;
    if($comando === false) {
      $this->close(); 
    } else { $stato = true; }
      return $stato; 
    }   
          
  private function stampaRisultato($comando){
    $righe_estratte = array();  
    while ( $riga = $comando->fetch(PDO::FETCH_ASSOC) ) 
      { $righe_estratte[] = $riga; }
    return $righe_estratte;
  } 
    
  private function traduciTipoParametri($tipoParametro){
    $tipo = '';
    switch ($tipoParametro) {            
      case "str":
          $tipo = PDO::PARAM_STR;
          break;
      case "int":
          $tipo = PDO::PARAM_INT;
          break;
    }         
    // echo $tipo.$this->nl;
    return $tipo;
  }
    
  public static function mostraSQL($sql){
    $sostituzioni = array(","           => "<b>,</b><br>",
                          "SELECT "     => "<b>SELECT </b>",
                          "INSERT "     => "<b>INSERT </b>",
                          "UPDATE "     => "<b>UPDATE </b>",
                          "DISTINCT"    => "<b>DISTINCT</b>", 
                          " FROM "      => "<b><br>FROM </b>", 
                          " WHERE "     => "<b><br>WHERE </b>", 
                          " UNION "     => "<b><br>UNION <br></b>", 
                          " LEFT JOIN"  => "<b><br>LEFT JOIN </b>", 
                          "CONCAT"      => "<b>CONCAT</b>", 
                          " AS "        => "<b> AS </b>", 
                          " ON "        => "<b> ON </b>",
                          " AND "       => "<b><br>AND </b>",
                          " OR "        => "<b><br>OR </b>",
                          "="           => "<b>=</b>",
                          "("           => "<b>(</b>",
                          ")"           => "<b>)</b>",
                          ";"           => "<b>;</b><br>");
    $debug = str_replace( array_keys($sostituzioni), array_values($sostituzioni), $sql );
    return $debug;
  }
    
  /* valido anche per update, ma non in grado di ritornare ultima riga aggiornata */
  public function insert( $sql, $parametri = null ) {        
    $comandoSQL = $this->conn->prepare($sql);
    $esito = $this->esegui( $comandoSQL, $parametri, true );           					
    if($esito) {
      return $this->conn->lastInsertId();
    } else {
      $this->close();				
      return false;
    }
  }
        
  public function execute($sql, $parametri = null) {        
    $comandoSQL = $this->conn->prepare($sql);
    $esito = $this->esegui($comandoSQL, $parametri, true);           					
    if($esito) {
      return true; 
     } else {
      $this->close(); 							
      return false;
    }    
  }
    
  //restituisce false in caso di problemi nell'esecuzione del comando
  //oppure un array con le righe restituite da mysql
  public function select($query, $campi = null, $parametri = null) {
    $risultato_query = $this->conn->prepare($query);
    if ($parametri !== null && $campi !== null) {
      for ($p = 0; $p < count($parametri); $p++ ){
          $risultato_query->bindParam( $campi[$p], $parametri[$p] );
        }  
    }
    return $this->esegui($risultato_query);		     			
  }
        
  /* restituisce false in caso di problemi nell'esecuzione del comando
    oppure un array con le righe restituite da mysql.
    aggiunge una parte WHERE non complessa secondo la regola:
    SE tutti i campi-where sono diversi aggiunge solo degli AND
    SE anche solo due campi-where sono uguali, prima del secondo aggiunge un OR */   
  public function selectWhere($sql, $coppieWhere ){
    if ( $coppieWhere[0] !== ""  ) {            
      $parteWhere = " WHERE";
      $parametri = array();
      $tipiParametri = array();
      $campi = array();
      $ind = 0;
      foreach ($coppieWhere as $campo => $valore){               
          $indiceCampo = array_search( $campo, array_keys($coppieWhere) );
          $campo = substr( $campo,0,- ( $indiceCampo > 9 ? 2 : 1) );
          $alias = str_replace('.','',$campo).$ind;
          $ind++; 
          $congiunzione = " AND";
          if (in_array(':'.str_replace('.','',$campo).($ind - 2),$campi) )
            { $congiunzione = " OR"; }
          if ($indiceCampo > 0) $parteWhere.=$congiunzione;
          $parteWhere.=" {$campo} = :".$alias;
          $campi[] = ":".$alias;
          $parametri[] = $valore;
      }
      $sql = $sql.$parteWhere;
    }        
      $comando = $this->conn->prepare($sql);
      for ($p = 0; $p < count($parametri); $p++ ){
          $comando->bindParam( $campi[$p], $parametri[$p] );
      }
      return $this->esegui($comando);  
  }
    
  public function ottieniCampi($tabella){
    if($tabella !== "" || $tabella !== null) {
      $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS ".
             "WHERE TABLE_SCHEMA = '{$this->accessData['dbname']}' ".
             "AND TABLE_NAME = '{$tabella}'";
      return $this->select($sql); 
    } else { return "Tabella non inserita"; }
  }  

} // classe
?>
