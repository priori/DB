<?php 


class Model  implements arrayaccess{
	
	private $db;
	private $args;
	private $alias; // model name
	private $name; // table scaped name
	private $id_name = 'id';

	public function Model( &$link, &$name, $args = false ){
		$this->db =& $link;
		$this->alias =& $name;
		$this->name = $link->escape( $name ); // only variable should be assigned by reference
		$this->args =& $args;
	}
	
	public function __toString(){
		return '`'.$this->name.'`';
	}

	// set, update
	public function set( $id, $args ){
		$args = Decoder::decode_array( $args );
		if( is_array($id) || is_object($id) ){
			// para evitar xss
			$this->db->fire_error("Invalid arguments. Id can't be a array/object.");
		}
		return $this->_set( $id, $args );
	}
	public function _set(&$id, &$attrs){
		$n = array();
		$q = array();
		$q[] = 'UPDATE `';
		$q[] = $this->name;
		$q[] = '` SET ';
		$b = false;
		foreach( $attrs as $name => $attr ){
			$v;
			if( $b ){
				$q[] = ', ';
			}else{
				$b = true;
			}
			$q[] = '`';
			$q[] = $this->db->escape($name);
			$q[] = '` = ';
			if( isset($attr['content']) ){ // ponteiro
				$v = $attr['content'];
			}else{
				$this->db->fire_error('Chave sem valor!');
				return;
			}
			$this->sql_value( $q, $v, $attr, $name );
		}
		$q[] = ' WHERE ';
		$b = false;
		$aux = array();
		$q[] = '`id` = \'';
		$q[] = $this->db->escape($id);
		$q[] = '\'';
		return $this->db->_query(implode('',$q));
	}
	
	// add, insert
	public function add( $e ){
		// sem multiplas insersoes por enquanto
		// if( $e2 !== false ){ $e2=false; $e=func_get_args(); } 
		return $this->_add( $e, false );
	}
	public function replace( $e ){
		return $this->_add( $e, true );
	}

	private $macros = array('sql'=>true,'now'=>true,'date'=>true,'int'=>true,'text'=>true,
		'format'=>true,'num'=>true,'numeric'=>true,'integer'=>true, 'serialize'=>true ); 
	// microtime, date_time, required, length_gt, length_lt, gt, lt
	private $macro_alias = array('num'=>'numeric','int'=>'integer');
	private $macro_optional_params = array('date'=>true,'text'=>true,'sql'=>true); // numeric(size)
	private $macro_required_params = array('format'=>true);
	private function validate( &$e, &$vals, $tipo ){
		// nao vamos pensar em vals por enquanto 
		$r = true;
		$msg = array();
		foreach( $e as $macro => $params ){
			if( !isset($this->macros[$macro]) ){
				$msg[] = 'Invalid macro "';
				$msg[] = $macro;
				$msg[] = '"! ';
				if( isset($this->macros[strtolower($macro)]) ){
					$msg[] = 'Use lower case in macros. ';
				}elseif( isset($this->macros[trim($macro)]) ){
					$msg[] = 'Be aware of white spaces in macros. ';
				}elseif( isset($this->macros[trim(strtolower($macro))]) ){
					$msg[] = 'Use lower case and no white spaces in macros. ';
				} 
				continue;
			}
			if( isset($this->macro_alias[$macro]) && isset($e[$this->macro_alias[$macro]]) ){
				$msg[] = 'Redundancy! Dont use "';
				$msg[] = $macro;
				$msg[] = '" with "';
				$msg[] = $this->macro_alias[$macro];
				$msg[] = '"! ';
			}
			if( isset($this->macro_required_params[$macro]) && false ){
				$msg[] = 'Macro "';
				$msg[] = $macro;
				$msg[] = '" needs parameters! ';
			}
		}
		return true;
	}
	public function _add(&$e,$replace=false){
		$e = Decoder::decode_array( $e );
		$vals = false;
		if( !$this->validate($e,$vals,'add') ){ // e o replace?
			// return;
		}
		return $this->__add($e,$replace);
	}
	
	public function __add(&$e,$replace=false,$multiple_insertions=false){
		$q = array();
		if( $replace===true )
			$q[] = 'REPLACE INTO `';
		else
			$q[] = 'INSERT INTO `';
		$q[] = $this->name;
		$q[] = '` (';
		// var_dump( $e );

		// em caso de inserssões multiplas o bixo pega
		// entries tem os valores de cada nova entrada
		// names os nomes das colunas que serão utilizadas
		$entries = array(); // valores de cada nova entrada
		$entries[] = array();
		$names = array(); // nomes das col utilizadas

		// se houver relações has many ou for utilizada outro tipo 
		// de funcionalidade que necessite de transações
		$need_transaction = false;

		// varias entradas, mas cada uma será inserida separadamente
		$fazer_por_partes = false; // uma insersao multipla

		$b = false; // precisa de virgula?
		$entries_count = 0;
		$has_more_entries = true;
		while( $has_more_entries ){
			if( !$multiple_insertions ){
				$has_more_entries = false;
				$entry =& $e;
			}else{
				// mais complicado
				// if default value 
				// guarda default value
				// continue
				// else
				// set entry
			}

			foreach( $entry as $name => $attr ){

				if( !isset($attr['has_many']) ){ // is not has many
					if( isset($names[$name]) ){
						continue;
					}
					if( $b )
						$q[] = ', ';
					else
						$b = true;
					$q[] = '`';
					$q[] = $this->db->escape($name);
					$q[] = '`';

					$entries[$entries_count][$name] = $attr; // tinha um & antes
					$names[$name] = true;

				}else{

				}
			}
			$entries_count++;
		}
		$q[] = ') VALUES ';

		if( !$multiple_insertions ){
			return $this->_values( $q, $entries, $names, $need_transaction,
					$default_values );
		}
		return $r0;
	}
	
	// quando não usa aspas
	// não dá fazer resolver no _value (formata e valida)
	private function sql_value( &$q, &$v, &$b, &$c ){
		if( is_int($b['content']) || is_float($b['content']) ){
			$q[] = $b['content'];
		}elseif( isset($b['serialize']) ){
			$q[] = '\'';
			$q[] = $this->db->escape(serialize($b['content']));
			$q[] = '\'';
		}elseif( isset($b['sql']) ){
			$q[] = $b['content'];
		}else{
			$q[] = '\'';
			$q[] = $this->db->escape($b['content']);
			$q[] = '\'';
		}
	}
	
	private function _values( &$q, &$entries, &$names, &$need_transaction,
			&$default_values ){
		$b2 = false;
		foreach( $entries as $entry ){
			if( $b2 )
				$q[] = ', ';
			$q[] = '(';
			$b = false;
			foreach( $names as $name => $aux ){
				if( $b ){
					$q[] = ', ';
				}
				$attr;
				if( isset($entry[$name]) ){
					$attr =& $entry[$name];  			
					if( isset($attr['content']) ){ // ponteiro
						$v = $attr['content'];
					}else{
						$this->db->fire_error('Chave sem valor!');
						return;
					}                
					$this->sql_value( $q, $v, $attr, $attr );
				}else{
					$q[] = 'DEFAULT';
				}
				$b = true;
			}
			$q[] = ')';
			$b2 = true;
		}
		return $this->db->_query( implode('',$q) );
	}
	
	private function value( &$attr, &$value ){
		include 'value.php';
	}
	
	private $valid_macros = array('date'=>true,'time'=>true,'date_time'=>true,
		'sql'=>true,'int'=>true,'decimal'=>true,'trim'=>true,'bool'=>true,
		'now'=>true,'format'=>true,'parse'=>true);

	public function build_args( &$args, &$parameters=false ){
		include 'build_args.php';
	}

	// remove
	public function remove($id){
		$t = $this->name;
		$id = $this->db->escape($id);
		$id_name &= $this->id_name;
		if( is_array($id_name) )
			$this->db->fire_error('Você não pode utilizar o modelo assim tendo id múltiplo!');
		return $this->db->_query("DELETE FROM `$t` WHERE `$id_name` = '$id'");
	}
	
   // get
	public function get($id){
		$t = $this->name;
		$id = $this->db->escape($id);
		$id_name &= $this->id_name;
		if( is_array($id_name) )
			$this->db->fire_error('Você não pode utilizar o modelo assim tendo id múltiplo!');
		$r = $this->db->_query("SELECT * FROM `$t` WHERE `$id_name` = '$id'");
		return $r->fetch();
	}
	public function remove_where( $w ){
		// $w =& Decoder::decode_array( $w );
		// $this->validate_where( $w );
		$t = $this->name;
		$q = array("DELETE FROM `$t` WHERE ");
		$this->_where( $q, $w );
		return $this->db->_query(implode($q));
	}
	public function get_where( $w ){
		// $w =& Decoder::decode_array( $w );
		// $this->validate_where( $w );
		$t = $this->name;
		$q = array("SELECT * FROM `$t` WHERE ");
		$this->_where( $q, $w );
		$r = $this->db->_query(implode($q));
		return $r->fetch();
	}


	function sql_where( &$q, &$w ){
		include 'sql_where.php';
	}




	
	// adiciona erro a transacao
	// ou a ultima (será próxima?) query
	private function add_error( $field, $arg ){
		$this->db->_add_error($this->name,$field,$arg);
	}
	

	private function sql_date_format($d,$format){
		$d = explode('/',$d);
		$day = $d[0];
		$month = $d[1];
		$year = (int)$d[2];
		if( $year <= 99 ){
			if( $year < 70 ){
				$year+=2000;
			 }else{
				$year+=1900;
			 }
		}
		$time = mktime(0,0,0,(int)$month,(int)$day,$year);
		// $format = str_split($format);
		// array_unshift($format,'');
		// $format = implode('\\',$format);
		$format = eregi_replace('dd',$day,$format);
		$format = eregi_replace('d',''.''.((int)$day),$format);
		$format = eregi_replace('yyyy',''.$year,$format);
		$format = eregi_replace('yy',''.($year%100),$format);
		$format = eregi_replace('mm',$month,$format);
		return eregi_replace('m',''.((int)$month),$format);
	}
	
	private function sql_date($d,$format){
		$f = strtr($format,array( 
			'dd' => '([0-9][0-9])','mm' => '([0-9][0-9])',
			'yyyy' => '([0-9][0-9][0-9][0-9])',
			'd' => '([0-9][0-9]?)','m' => '([0-9][0-9]?)',
			'yy' => '([0-9][0-9])',
			'(' => '\\(', '/' => '\\/' , ')' => '\\)' // falta muito o que validar
		));
		$r;
		preg_match_all('/^'.$f.'$/',$d,$r);
		$r2;
		preg_match_all('/dd|mm|yyyy|d|m|yy/',$format,$r2);
		$r2 = $r2[0];
		if( !count($r) )return false;
		array_shift($r);
		
		$day = false;
		$year = false;
		$month = false;
		foreach( $r as $c => $v ){
			if( count($v) != 1 )return false;
			$v = $v[0];
			if( strlen($v)==1 ){
				$v = '0'.$v;
			}
			if( $r2[$c] == 'm' || $r2[$c] == 'mm' ){
				if( $month !== false && $month != $v ){
					return false;
				}
				$month = $v;
			}
			if( $r2[$c] == 'd' || $r2[$c] == 'dd' ){
				if( $day !== false && $day != $v ){
					return false;
				}
				$day = $v;
			}
			if( $r2[$c] == 'yy' ){
				$v = (int)$v;
				if( $v <= 99 ){
					if( $v < 70 ){
						$v+=2000;
					 }else{
						$v+=1900;
					 }
				}
			}
			if( $r2[$c] == 'yy' || $r2[$c] == 'yyyy' ){
				if( $year !== false && $year != $v ){
					return false;
				}
				$year = $v;
			}
		}
		if( $year === false || $month === false || $day === false )
			return error(); // isso não é erro comum
		return $day.'-'.$month.'-'.$year;
		// return eregi_replace('m',''.((int)$month),$format);
	}

















	// array access
	public function offsetSet($id,$val){
		if( $id === NULL ){
			return $this->_add( $val );
		}else{
			return $this->set( $id, $val );
		}
	}
	public function offsetGet($id ){
		$t = $this->name;
		$id = $this->db->escape($id);
		$id_name &= $this->id_name;
		if( is_array($id_name) )
			$this->db->fire_error('Você não pode utilizar o modelo assim tendo id múltiplo!');
		$r = $this->db->_query("SELECT * FROM `$t` WHERE `$id_name` = '$id'");
		return $r->fetch();
	}
	public function offsetUnset($id){
		$t = $this->name;
		$id = $this->db->escape($id);
		$id_name &= $this->id_name;
		if( is_array($id_name) )
			$this->db->fire_error('Você não pode utilizar o modelo assim tendo id múltiplo!');
		return $this->db->_query("DELETE FROM `$t` WHERE `$id_name` = '$id'");
	}
	public function offsetExists($id){
		$t = $this->name;
		$id = $this->db->escape($id);
		$id_name &= $this->id_name;
		if( is_array($id_name) )
			$this->db->fire_error('Você não pode utilizar o modelo assim tendo id múltiplo!');
		$r = $this->db->_query("SELECT 1 FROM `$t` WHERE `$id_name` = '$id'");
		return $r->num_rows() > 0;
	}
	public function truncate(){
		$t = $this->name;
		return $this->db->_query("TRUNCATE `$t`");
	}
}
