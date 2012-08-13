<?php

require_once dirname(__FILE__).'/db_result.php';
require_once dirname(__FILE__).'/decoder.php';
require_once dirname(__FILE__).'/error.php';
require_once dirname(__FILE__).'/model.php';

class DB{

	public $link;

	private $invalid_value = 1;
	private $db_error = 2;
	private $lib_error = 2;

	const PHP_ERROR_PARODY = 1;
	const TRIGGER_ERROR = 2;
	const THROW_ERROR = 4;

	private $db_selected;

	const MYSQL = 1;
	const MYSQLI = 2;
	const POSTGRESQL = 3;

	private $debug_mode = false;
	private $error_mode = 1;
	private $mode = '';
	public $echo_queries = false;

	public function __set( $a, $b ){
		if( $a === 'error_mode' ){
			if( $b !== 1 && $b !== 2 && $b !== 4 ){
				$this->fire_error( "Modo de erro invalido!" );
			}
			$this->error_mode = $b;
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
				 // && basename($t['file']) == 'db.php'
				if( isset($t['class']) && ($t['class'] === 'DB' || $t['class'] === 'Model')){ // aqui esta o segredo
					$d = $t;
					continue;
				}
				break;
			}
			echo "<br/><strong>Error</strong>: ";
			if( $d['function'] === 'offsetSet' ){ // && mais alguma coisa
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
	public function __construct($h='localhost',$u='root',$p='')
	{
		if( is_array( $h ) ){
			if( isset($h['user']) )
				$u = $h['user'];
			if( isset($h['password']) )
				$p = $h['password'];
			if( isset($h['db']) )
				$db = $h['db'];
			if( isset($h['port']) )
				$port = $h['port'];
			if( isset($h['postgresql']) )
				$postgresql = $h['postgresql'];
			if( isset($h['host']) )
				$h = $h['host'];
			else
				unset($h);
		}
		if( isset($postgresql) and $postgresql ){
			$this->mode = DB::POSTGRESQL;
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
			if( isset($db) ){
				$s[] = 'dbname='.$db;
			}
			$s = implode( ' ',$s );
			$this->link = pg_connect($s);
		}else{
			$this->mode = class_exists('mysqli')? DB::MYSQLI : DB::MYSQL;
			if( $this->mode === DB::MYSQLI ){
				$this->link = new mysqli($h,$u,$p);
			}else{
				$this->link = mysql_connect($h,$u,$p);
			}
		}
	}


	public function debug_mode($b)
	{
		$this->debug_mode = $b;
	}

	private $smart_rollback = false;

	public function _query( $q ){
		return $this->__query( $q );
	}
	public function __query( &$q ){

		// lidando com as transactions
		// esperando o $db->end();
		if( $this->smart_rollback ){
			return false;

		}else if( $this->_has_validation_error ){
			if( $this->_first_query_with_error ){
				$this->_first_query_with_error = false;
				return false;
			}
			if( $this->transaction_count ){
				return false;
			}
			$this->_has_validation_error = false;


			// se for difserente de rollback, algo estah erradoVV
		}else{
			$this->validation_errors = false;
		}
		if( $this->echo_queries ){
			echo htmlspecialchars($q).'<br/>';
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

		if( $err && $this->transaction_count ){
			if( $this->mode === DB::MYSQLI ){
				$this->trans_error = $this->link->errno;
				$this->trans_errno = $err;
			}elseif( $this->mode === DB::MYSQL ){
				$this->trans_error = mysql_errno($this->link);
				$this->trans_errno = $err;
			}
			$this->_query('ROLLBACK');
			$this->smart_rollback = true;
			return false;
		}

		if( $err ){
			$this->fire_error( "<strong>The data base return an error: </strong>".
				$this->db_error() );
			return false;
		}
		if( $r === true )
			return true;
		return new DB_Result( $r, $this->link, $this->mode );
	}
	public function select_db( $db ){
		if( $this->mode !== DB::MYSQL and $this->mode !== DB::MYSQLI ){
			$this->fire_error('Caso não esteja usando MySQL selecione o banco de dados ao contectar!');
			return;
		}
		$this->db_selected = $db;
		if( $this->mode === DB::MYSQLI ){
			$this->link->select_db($db);
		}else{
			mysql_select_db($db,$this->link);
		}
	}

	public function insert_id(){
		if( $this->mode === DB::MYSQLI )
			return $this->link->insert_id;
		elseif( $this->mode === DB::MYSQLI )
			return mysql_insert_id($this->link);
		elseif( $this->mode === DB::POSTGRESQL )
			$this->fire_error("PostgreSQL não funciona assim!");
	}
	public function escape(&$s){
		// if( is_array($s))var_dump($s);
		if( $this->mode === DB::MYSQLI ){
			return $this->link->real_escape_string($s);
		}elseif( $this->mode === DB::MYSQL ){
			return mysql_real_escape_string($s,$this->link);
		}elseif( $this->mode === DB::POSTGRESQL ){
			if( is_int($s) || is_float($s) ){
				return $s;
			}else{
				return pg_escape_string( $this->link, $s );
			}
		}
	}
	public function set_charset($c){
		if( $this->mode === DB::POSTGRESQL ) //TODO pg_setclientencoding
			return pg_set_client_encoding( $this->link, $c );
		if( $this->mode === DB::MYSQLI )
			return $this->link->set_charset( $c );
		elseif( $this->mode === DB::MYSQL )
		return mysql_set_charset( $c, $this->link );
	}

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
			return $this->_query('BEGIN');
		}
	}
	public function rollback(){
		return $this->_query('ROLLBACK');
	}
	public function commit( $b = false ){
		if( $b === true ){
			$this->transaction_count--;
			if( !$this->transaction_count ){
				if( $this->smart_rollback ||
					$this->_has_validation_error ){
				$this->smart_rollback = false;
				$this->_query('ROLLBACK');
			}else{
					return $this->_query('COMMIT');
				}
			}
			if( $this->transaction_count<0 )
				die('commit fora de transaction');
		}else{
			return $this->_query('COMMIT');
		}
	}
	public function end(){
		return $this->commit(true);
	}


	private $validation_errors = false;
	public $_has_validation_error = false;
	private $_first_query_with_error = false;
	public function errors(){
		$a = func_get_args();
		if( count($a) && $this->validation_errors )
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

	public function fetchAll(){
		$r = $this->query(func_get_args());
		$a = array();
		while( $aux = $r->fetch() ){
			$a[] = $aux;
		}
		return $a;
	}
	public function link(){
		return $this->link;
	}
}
// mysql_create_db(), mysql_drop_db(), mysql_list_dbs(), mysql_db_name(),
// mysql_list_fields(), mysql_list_processes(), mysql_list_tables(),
// mysql_db_query(),mysql_table_name()



