<?php


class DB{

	public $link;

	const PHP_ERROR_PARODY = 1;
	const TRIGGER_ERROR = 2;
	const THROW_ERROR = 4;

	private $db_selected;

	const MYSQL = 1;
	const MYSQLI = 2;
	const POSTGRESQL = 3;

	const PLAIN_TEXT = -1;

	private $error_mode = 4;
	private $mode = '';

	private $echo_queries = false;

	public function __set( $a, $b ){
		if( $a === 'echo_queries' ){
			if( $b !== true and $b !== false and $b !== DB::PLAIN_TEXT ){   
				$this->fire_error( "echo_queries espera um valor do tipo boleano!" );   
			}
			$this->echo_queries = $b;
		}elseif( $a === 'error_mode' ){
			if( $b !== 1 and $b !== 2 and $b !== 4 ){   
				$this->fire_error( "Modo de erro invalido!" );   
			}
			$this->error_mode = $b;
		}elseif( $a === 'charset' ){
			if( $this->mode === DB::POSTGRESQL ){ //TODO pg_setclientencoding
				$r = pg_set_client_encoding( $this->link, $b );
				$r = $r===0;
			}elseif( $this->mode === DB::MYSQLI )
				$r = $this->link->set_charset( $b );
			elseif( $this->mode === DB::MYSQL )
				$r = mysql_set_charset( $b, $this->link );
			if( !$r )
				$this->fire_error('Não foi possível selecionar esta codificação!');
		}else{
			$this->fire_error( "Tentativa de setar attributos somente leitura ".
				"(use o metodo set_module caso queira configurar modelos)" );
		}
	}


	public function fire_error( $err ){
		if( $this->error_mode == 1 ){
			$bt = debug_backtrace();
			$d = false;
			foreach( $bt as $t ){
				 // and basename($t['file']) == 'db.php' 
				if( isset($t['class']) and ($t['class'] === 'DB' or $t['class'] === 'Model')){ 
					// aqui esta o segredo
					$d = $t;  
					continue;
				}
				break;
			}
			echo "<br/><strong>Error</strong>: ";
			if( $d['function'] === 'offsetSet' ){ // and mais alguma coisa
				if( isset($d['class']) ){
					echo $d['class'];
				}
				if( $d['args'][0] === NULL ){
					echo '[] = ';
				}else{
					echo '[';
					if( is_string($d['args'][0]) ){
						echo htmlspecialchars( $d['args'][0] );
					}else{
						//
					}
					echo '] = ';
				}
				if( is_string($d['args'][1]) ){
					echo htmlspecialchars( $d['args'][1] );
				}elseif( is_array($d['args'][1]) ){
					echo 'array()';
				}else{
					echo $d['args'][1];
				}
				echo ' ';

			}else{
				echo htmlspecialchars( $d['function'] );
				echo '(';
				$c = 0;
				foreach( $d['args'] as $arg ){
					if( $c > 5 ){
						echo '...';
						break;
					}
					if( $c > 0 )
						echo ', ';
					if( is_string($arg) ){
						echo htmlspecialchars( $arg );
					}else if( is_array($arg) ){
						echo 'array()';
					}else{
						echo $arg;
					}
					$c++;
				}
				echo ') ';
			}
			echo $err;
			echo " in <strong>";
			echo htmlspecialchars( $d['file'] );
			echo '</strong> on line <strong>';
			echo $d['line'];
			echo '</strong><br/>';
			 die();
		}else if( $this->error_mode == 2 ){
		     trigger_error( $err, E_USER_ERROR );
		}else{
		     throw new Exception( $err );
		}
	}

	// Construtor
	// Padrão é 
	// mysql host:localhost user:root password:'' 
	// postgres host:localhost user:postgres dbname:postgres
	// todo formas de especificar socket
	public function __construct($h='localhost',$u='root',$p='')
	{
		static $alowed_args = array(
			'host' => true, 'user' => true, 'password' => true,
			'dbname' => true, 'port' => true, 'socket' => true
		);
		if( is_array( $h ) ){
			foreach( $h as $c => $v ){
				if( !isset($alowed_args[$c]) ){
					$this->fire_error('Argumento inválido! Conexão não usa parametro '.$c);
				}else if( $v and !is_numeric($v) and !is_string($v) and !is_bool($v) ){
					$this->fire_error('Valor inválido para parametro '.$c);
				}
			}
			if( isset($h['user']) )
				$u = $h['user'];
			if( isset($h['password']) )
				$p = $h['password'];
			if( isset($h['dbname']) )
				$dbname = $h['dbname'];
			if( isset($h['port']) )
				$port = $h['port'];
			if( isset($h['socket']) ){
				if( $h['socket'] == 'postgresql' ){
					$postgresql = true;
				}elseif( $h['socket'] != 'mysql' ){
					$this->fire_error('Socket '.$h['socket'].' desconhecido.');
				}
			}
			if( isset($h['host']) )
				$h = $h['host'];
			else
				$h = 'localhost';
			$u = false;
		}else{
			if( $h and !is_string($h) )
				$this->fire_error('Valor inválido para host.');
			if( $u and !is_string($u) )
				$this->fire_error('Valor inválido para usuário.');
			if( $p and !is_string($p) and !is_int($p) )
				$this->fire_error('Valor inválido para senha.');
		}
		if( isset($postgresql) and $postgresql ){
			$this->mode = DB::POSTGRESQL;
			if( !$u )
				$u = 'postgres';
			if( !$dbname )
				$dbname = 'postgres';
			$s = array();
			if( $h ){
				$s[] = 'host='.$h;
			}
			if( $u ){
				$s[] = 'user='.$u;
			}
			if( $p ){
				$s[] = 'password='.$p;
			}
			if( isset($port) ){
				$s[] = 'port='.$port;
			}
			if( isset($dbname) ){
				$s[] = 'dbname='.$dbname;
			}
			$s = implode( ' ',$s );
			$this->link = pg_connect($s);
		}else{
			if( !$u )
				$u = 'root';
			if( !$p )
				$p = '';
			$this->mode = class_exists('mysqli')? DB::MYSQLI : DB::MYSQL;
			if( $this->mode === DB::MYSQLI ){
				if( isset($port) ){
					if( !isset($dbname) )
						$dbname = null;
					$this->link = new mysqli($h,$u,$p,$dbname,$port);
				}else{
					if( isset($dbname) ){
						$this->link = new mysqli($h,$u,$p,$dbname);
					}else{
						$this->link = new mysqli($h,$u,$p);
					}
				}
				if( mysqli_connect_error() ){
					$this->link = false;
					$this->fire_error('Não foi possível conectar. '.mysqli_connect_error() );
				}
				if( isset($dbname) and $dbname ){
					$r = $this->link->select_db($dbname);
					if( !$r )
						$this->fire_error('Nao foi possivel conectar a esta base!');
				}
			}else{
				if( isset($port) ){
					$h = $h.':'.$port;
				}
				$this->link = mysql_connect($h,$u,$p);
				if( isset($dbname) and $dbname ){
					$r = mysql_select_db($dbname,$this->link);
					if( !$r )
						$this->fire_error('Nao foi possivel conectar a esta base!');
				}
			}
		}
		if( !$this->link ){
			// ou sempre throw??
			$this->fire_error('Nao foi possivel conectar!');
		}
	}

	private $smart_rollback = false;

	public function _query( $q ){
		return $this->__query( $q );
	}
	private $last_return;
	public function __query( &$q ){

		// lidando com as transactions
		// esperando o $db->end();
		if( $this->smart_rollback ){
			return false;

		}else if( $this->_has_validation_error ){
			// if( $this->_first_query_with_error ){
			// 	$this->_first_query_with_error = false;
			// 	$this->last_return = false;
			// 	return false;
			// }
			if( $this->transaction_count ){
				$this->last_return = false;
			   return false;
			}
			$this->_has_validation_error = false;


			// se for difserente de rollback, algo estah erradoVV
		}else{
			$this->validation_errors = false;
		}
		if( $this->echo_queries ){
			if( $this->echo_queries === DB::PLAIN_TEXT )
				echo $q;
			else
				echo strtr($q,array('>'=>'&gt;','<'=>'&lt;','&'=>'&amp;')).'<br/>';
		}
		if( $this->mode === DB::POSTGRESQL ){
			$r = pg_query( $this->link, $q );
		}elseif( $this->mode === DB::MYSQLI ){
			$r = $this->link->query( $q );
		}else{
			$r = mysql_query( $q, $this->link );
		}

		$err;
		if( $this->mode === DB::POSTGRESQL )
			$err = pg_last_error($this->link);
		elseif( $this->mode === DB::MYSQLI )
			$err = $this->link->errno;
		else
			$err = mysql_errno($this->link);

		if( $err and $this->transaction_count ){
			if( $this->mode === DB::MYSQLI ){
				$this->trans_error = $this->link->errno;
				$this->trans_errno = $err;
			}elseif( $this->mode === DB::MYSQL ){
				$this->trans_error = mysql_errno($this->link);
				$this->trans_errno = $err;
			}
			$this->_query('ROLLBACK');
			$this->smart_rollback = true;
			$this->last_return = false;
			return false;
		}

		if( $err ){
			$this->fire_error( "<strong>The data base return an error: </strong>".
				$this->db_error() );
			$this->last_return = false;
			return false;
		}
		if( $r === true ){
			$this->last_return = true;
			return true;
		}
		$this->last_return = 2;
		return new DB_Result( $r, $this->link, $this->mode );
	}
	// caso se esteja usando mysql ainda é possível fazer isso via SQL
	// usando: USE databasename
	// public function select_db( $db ){
	// 	if( $this->mode !== DB::MYSQL and $this->mode !== DB::MYSQLI ){
	// 		$this->fire_error('Caso nao esteja usando MySQL selecione o banco de dados ao contectar!');
	// 	}
	// 	$this->db_selected = $db;
	// 	if( $this->mode === DB::MYSQLI ){
	// 		$r = $this->link->select_db($db);
	// 	}else{
	// 		$r = mysql_select_db($db,$this->link);
	// 	}
	// 	if( !$r )
	// 		$this->fire_error('Nao foi possivel conectar a esta base!');
	// }

	public function last_id(){
		if( $this->mode === DB::POSTGRESQL ){
			$this->fire_error("PostgreSQL não funciona assim!");
		}else{ //if( $this->last_return === true ){
			if( $this->mode === DB::MYSQLI ){
				return $this->link->insert_id;
			}elseif( $this->mode === DB::MYSQL )
				return mysql_insert_id($this->link);
		}
	}
	public function escape($s){
		return $this->_escape( $s );
	}
	public function _escape(&$s){
		if( $this->mode === DB::MYSQLI ){
			return $this->link->real_escape_string($s);
		}elseif( $this->mode === DB::MYSQL ){
			return mysql_real_escape_string($s,$this->link);
		}elseif( $this->mode === DB::POSTGRESQL ){
			if( is_int($s) or is_float($s) ){
				return ''.$s;
			}else{
				return pg_escape_string( $this->link, $s );
			}
		}
	}
	// e o collation???
	public function charset(){
		if( $this->mode === DB::POSTGRESQL ) 
			return pg_client_encoding( $this->link );
		if( $this->mode === DB::MYSQLI ){
			$r = $this->link->get_charset();
			if( $r )
				return $r->charset;
		}
		if( $this->mode === DB::MYSQL )
			return mysql_encoding( $c, $this->link );
	}

	// ou schema
	private $models = array();
	public function __get($n){
		if( $this->mode == DB::POSTGRESQL ){
			if( !isset($this->models[$n]) ){
				$this->models[$n] = new Schema($this,$n,null,$this->mode);
			}
		}else{
			if( !isset($this->models[$n]) ){
				$this->models[$n] = new Model($this,$n,null,$this->mode);
			}
		}
		return $this->models[$n];
	}


	private $transaction_count = 0;
	public function begin( $b = false ){
		if( $b === true ){
			$r;
			if( !$this->transaction_count )
				$r = $this->_query('BEGIN');
			$this->transaction_count++;
			return $r;
		}else{
			if( $this->transaction_count ){
				$this->fire_error("Não se pode usar transações normais com easy transaction");
			}else{
				return $this->_query('BEGIN');
			}
		}
	}
	public function rollback(){
		return $this->_query('ROLLBACK');
	}
	public function commit( $b = false ){
		if( $b === true ){
			$this->transaction_count--;
			if( !$this->transaction_count ){
				if( $this->smart_rollback or $this->_has_validation_error ){
					$this->smart_rollback = false;
					$this->_query('ROLLBACK');
				}else{
					return $this->_query('COMMIT');
				}
			}
			if( $this->transaction_count<0 ){
				$this->transaction_count = 0;
				$this->fire_error('commit fora de transaction');
			}
		}else{
			if( $this->transaction_count ){
				$this->fire_error("Não se pode usar commit normal com easy transaction. Use DB::end() no lugar!");
			}else{
				return $this->_query('COMMIT');
			}
		}
	}
	public function end(){
		return $this->commit(true);
	}
	public function dead_transaction(){
		return !!($this->transaction_count and ($this->smart_rollback or $this->_has_validation_error));
	}


	private $validation_errors = false;
	public $_has_validation_error = false;
	private $_first_query_with_error = false;
	public function errors(){
		$a = func_get_args();
		if( count($a) and $this->validation_errors )
			return $this->validation_errors->messages( $a );
		return $this->validation_errors;
	}

	public function _add_error( $model, $field, $err ){
		if( !$this->validation_errors ){
			$this->validation_errors = new ErrorList();
		}

		if( !$this->_has_validation_error ){
			$this->_first_query_with_error = true;
		}
		$this->_has_validation_error = true;
		// $model,
		if( $err == 'grande')
			$this->validation_errors->_add(  $field, $err, 'Grande!' );
		else if( $err == 'pequeno' )
			$this->validation_errors->_add(  $field, $err, 'Pequeno!' );
		else
			$this->validation_errors->_add(  $field, $err, 'Coisa Errada!' );
	}
	private $trans_error = false;
	private $trans_errno = false;
	public function db_error(){
		if( $this->trans_errno ){
			return $this->trans_error;
		}
		if( $this->mode === DB::MYSQLI ){
			return $this->link->error;
		}elseif( $this->mode === DB::MYSQL ){
			return mysql_error($this->link);
		}elseif( $this->mode === DB::POSTGRESQL ){
			return pg_last_error($this->link);
		}
	}
	public function db_errno(){
		if( $this->trans_errno ){
			return $this->trans_errno;
		}
		if( $this->mode === DB::MYSQLI )
			return $this->link->errno;
		elseif( $this->mode === DB::MYSQL )
			return mysql_errno($this->link);
		elseif( $this->mode === DB::POSTGRESQL )
			$this->fire_error("PostgreSQL não trabalha com numero de erro! Não que eu saiba! LOL");
	}


	public function query( $a ){
		if( !is_array($a) )
			$a = func_get_args();
		$q = array_shift($a);
		if( count($a) ){
			foreach( $a as $c => $v ){
				$a[$c] = '\''.$this->escape($v).'\'';
			}
			$q = strtr($q,array('%'=>"%%",'?'=>"%s"));
			$q = vsprintf($q,$a);
		}
		// query
		return $this->_query( $q );
		// query end
	}


	public function fetch(){
		$r = $this->query(func_get_args());
		return $r->fetch();
	}

	public function fetch_all(){
		$r = $this->query(func_get_args());
		$a = array();
		while( $aux = $r->fetch() ){
			$a[] = $aux;
		}
		return $a;
	}
	public function fetch_col(){
		$r = $this->query(func_get_args());
		while( $aux = $r->fetch() ){
			foreach( $aux as $v ){
				return $v;
			}
		}
	}
	public function link(){
		return $this->link;
	}
	public function error_mode(){
		return $this->error_mode;
	}
	public function echo_queries(){
		return $this->echo_queries;
	}
	public function date_format(){
		return 'dd/mm/yyyy';
	}

	public function close(){
		if( $this->mode === DB::MYSQLI ){
			return $this->link->close();
		}elseif( $this->mode === DB::MYSQL ){
			return mysql_close($this->link);
		}elseif( $this->mode === DB::POSTGRESQL ){
			return pg_close($this->link);
		}
	}
	// list_processes(), thread_id(), stat()
}

