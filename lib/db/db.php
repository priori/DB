<?php
	
	
	class DB{
		
		public $link;
		
		private $invalid_value = 1;
		private $db_error = 2;
		private $lib_error = 2;
		
		const STORE_ERROR = 1;
		const TRIGGER_ERROR = 2;
		const THROW_ERROR = 4;
		
		private $db_selected;
		private $mysqli_mode;
		private $debug_mode = false;
		
		// Construtor
		public function DB($h='localhost',$u='root',$p=''){
			$this->mysqli_mode = class_exists('mysqli');
			if( $this->mysqli_mode ){
				$this->link = new mysqli($h,$u,$p);
			}else{
				$this->link = mysql_connect($h,$u,$p);
			}
		}
		
		
		public function debug_mode($b){
			$this->debug_mode = $b;
		}
		
		private $smart_rollback = false;
		
		public function _query( $q ){
			return $this->__query( $q );
		}
		public function _error( $err ){
        	
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
				// se for diferente de rollback, algo estah errado
				
			}else if( !$this->_has_validation_error ){
				$this->errors = false;
				
			}else{
				$this->trans_errno = false;
			}
			
			if( $this->mysqli_mode ){
				$r = $this->link->query( $q );
			}else{
				$r = mysql_query( $q, $this->link );
			}
				
			if( $this->db_errno() && $this->transaction_count ){
				$this->trans_errno = $this->db_errno();
				$this->trans_error = $this->db_error();
				$this->_query('ROLLBACK');
				$this->smart_rollback = true;
				return false;
				// guarda erro e errno
				// $this->transaction_count = 0;
				// die('depois melhoro isso!');
			}
			
			if( $this->db_errno() ){      
				if( $this->db_error == 2 ){
			   		// trigger_error( $this->db_error(), E_USER_ERROR ); 
			   		trigger_error( $this->db_error() ); 
				}
				return false;
			}
			return new DB_Result( $r, $this->link );
		}
		public function select_db( $db ){
			$this->db_selected = $db;
			if( $this->mysqli_mode ){
				$this->link->select_db($db);
			}else{
				mysql_select_db($db,$this->link);
			}
		}
		
		public function insert_id(){
			if( $this->mysqli_mode )
				return $this->link->insert_id;
			return mysql_insert_id($this->link);
		}
		public function escape(&$s){
			if( is_array($s))var_dump($s);
			if( $this->mysqli_mode )
				return $this->link->real_escape_string($s);
			return mysql_real_escape_string($s,$this->link);
		}
		public function set_charset($c){
			if( $this->mysqli_mode )
				return $this->link->set_charset( $c );
			return mysql_set_charset( $c, $this->link );
		}
		
		private $models = array();
		public function __get($n){
			if( !isset($this->models[$n]) )
				$this->models[$n] = new Model($this,$n);
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
					if( $this->smart_rollback || $this->_has_validation_error ){
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
		
		
		private $errors = false;
		public $_has_validation_error = false;
		private $_first_query_with_error = false;
		public function errors(){
			$a = func_get_args();
			if( count($a) && $this->errors )
				return $this->errors->messages( $a );
			return $this->errors;
		}
		
		public function _add_error( $model, $field, $err ){
			if( !$this->errors ){
				$this->errors = new ErrorList();
			}
			
			if( !$this->_has_validation_error ){
				$this->_first_query_with_error = true;
			}
			$this->_has_validation_error = true;
			// $model,
			if( $err == 'grande')
				$this->errors->_add(  $field, $err, 'Grande!' );
			else if( $err == 'pequeno' )
				$this->errors->_add(  $field, $err, 'Pequeno!' );
			else
				$this->errors->_add(  $field, $err, 'Coisa Errada!' );
		}
		private $trans_error = false;
		private $trans_errno = false;
		public function db_error(){
			if( $this->trans_errno ){
				return $this->trans_error;
			}
			if( $this->mysqli_mode )
				return $this->link->error;
			return mysql_error($this->link);
		}
		public function db_errno(){
			if( $this->trans_errno ){
				return $this->trans_errno;
			}
			if( $this->mysqli_mode )
				return $this->link->errno;
			return mysql_errno($this->link);
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
		// Create (add), Read (get), Update (set), D (remove)
		
	}
	// mysql_create_db(), mysql_drop_db(), mysql_list_dbs(), mysql_db_name(),
	// mysql_list_fields(), mysql_list_processes(), mysql_list_tables(), 
	// mysql_db_query(),mysql_table_name()
	
	
	
