<?php 


class Model  implements arrayaccess{
	
	private $db;
	private $args;
	private $alias; // model name
	private $name; // table scaped name

	public function Model( &$link, &$name, $args = false ){
		$this->db =& $link;
		$this->alias =& $name;
		$this->name =& $link->escape( $name );
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
			$this->add_val( $q, $v, $attr, $name );
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
			'format'=>true,'num'=>true,'numeric'=>true,'integer'=>true);
	private $macro_alias = array('num'=>'numeric','int'=>'integer');
	private $macro_optional_params = array('date'=>true,'text'=>true,'sql'=>true);
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
		$e =& Decoder::decode_array( $e );
		$vals = false;
		if( !$this->validate($e,$vals,'add') ){ // e o replace?
			$this->db->fire_error("asdjfklajsdf");
			return;
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

					$entries[$entries_count][$name] =& $attr;
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

	private function add_val( &$q, &$v, &$b, &$c ){
		if( is_int($b['content']) || is_float($b['content']) ){
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
					$this->add_val( $q, $v, $attr, $attr );
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
	
	private function _value( &$attr, &$value ){
		if( isset($attr['sql']) ){
			if( is_string($attr['sql']) ){
				
				if( is_string($value) ){
					$value = array( $value );
				}
				// se nao for array, error!
				$sql = $attr['sql']; // & ?
				foreach( $value as $c => $v ){
					$value[$c] = '\''.$this->db->escape($v).'\''; 
				}
				$sql = strtr($sql,array('%'=>"%%",'?'=>"%s"));
				$value =& vsprintf($sql,$value);
				return true;
			}else{
				$value =& $value; // & ?
				return true;
			}
			
		}else if( $value === NULL ){
			$value = 'NULL';
			return true;

		}elseif( isset($attr['serialize']) ){
			$value =& serialize($value);

		}else{
			// if has many ...
			// else
			
			if( isset($attr['trim']) ){ 
				$value =& trim($value);
			}
			if( isset($attr['upper_case']) || isset($attr['upper']) ){ 
				$value =& strtoupper($value);
			}
			if( isset($attr['lower_case']) || isset($attr['lower']) ){ 
				$value =& strtolower($value);
			}
			
			$type = false;
			if( isset($attr['int']) ){
				$valid = is_int($value) || ((int)$value).'' == ''.$value;
				$this->add_error($this->get_alias_name($attr),'not_int');
				$type = true;
			}
			if( isset($attr['date']) ){
				if( $type )error();
				
				if( !is_string($attr['date']) )error();
				
				$aux = $this->sql_date($value,$attr['date']);
				if( $aux === false ){
					$this->add_error($this->get_alias_name($attr),'format_date');
				}
				$value =& $aux;
				
				$type = true;
			}
			if( isset($attr['text']) ){
				if( $type )error();
				if( !ereg('^[0-9]+-[0-9]+$',$attr['text']) )
					die('não é assim que se faz');
				$text = explode('-',$attr['text']);
				$b = (int)$text[0];
				$t = (int)$text[1];
				if( $b > $t ){
					die('o maior tem que ser maior que o menor');
				}
				if( strlen($value)>$t ){
					// error
					$this->add_error($this->get_alias_name($attr),'grande');
				}
				if( strlen($value)<$b ){
					$this->add_error($this->get_alias_name($attr),'pequeno');
				}
				$type = true;
			}
			if( isset($attr['time']) ){
				if( $type )error();
				$type = true;
			}
			
			
			if( $value === false ){
				$value = "0";
				return true;
			}
			if( $value === true ){
				$value = "1";
				return true;
			}
			$value =& $this->db->escape( $value );
		}


		return false;
	}
	
	private $valid_macros = array('date'=>true,'time'=>true,'date_time'=>true,
		'sql'=>true,'int'=>true,'decimal'=>true,'trim'=>true,'bool'=>true,
		'now'=>true,'format'=>true,'parse'=>true);

	public function build_args( &$args, &$parameters=false ){
		if( !is_array( $args ) ){
			$this->db->fire_error("Invalid arguments!");
		}

		$args = array( $args );
		$defaults = false;
		$entries = array();
              
		$args_count = 0;
		$has_more_entries = true;

		while( isset($args[$args_count]) ){

			$count = 0;
			$e =& $args[$args_count];
			$values = array();
			$sqls = array();
			foreach( $e as $c => $v ){
				$sql = false;
				$has_content = false;
				if( is_string( $c ) ){
					$aux =& Decoder::decode_string( $c );
					$has_content = true;
					// if valid macros combination (attrs, model)
					$sql = $this->_value($aux,$v);

				// haverá esse caso mesmo?
				}elseif( $c === $count && is_string($v) ){ 
					// if tiver parameters
					// usa o valor do parameter
					// else
					$this->db->fire_error("Invalid arguments! ".
						"Os valores no Array devem ter chave String.");
					// um dia posso dar suporte a isto, para setagem de opções
					// now será uma exessão
					$count++;
					$aux = Decoder::decode_string( $v );

				// Array de Array
				}else if( $c===$count && $args_count == 0 && is_array($v) ){
					$args[] =& $e[$c]; 
					unset( $e[$c] );
					continue;
					
				// Chave não string e valor de tipo não array ou array de array de array
				}else{
					$this->db->fire_error("Invalid arguments!");
				} 

				if( isset($aux[0]) ){
					$name =& $aux[0];
					unset( $aux[0] );
					// if( has_value )
					//		$values[$name] = $this->value($v,$aux);
					if( isset( $values[$name] ) ){
						$this->db->fire_error("Invalid arguments! ".
								"Tentativa de setar o mesmo campo mais de uma vez.");
						// campo, atributo ou coluna? qual o melhor nome?
					}
					$values[$name] = $v;
					if( $sql ){
						$sqls[$name] = true;
					}

				}else{ // que merda é essa?
					// como você chegou aqui?
					die("Por enquanto não tem como chegar aqui");
					// $values[] = $aux;
				}
				// valida, gera valores, diz se é sql ou precisa escapar e aspas
			}
			if( count($sqls) > 0 ){
				$values[] = $sqls;
			}
			if( $args_count == 0 && count($args) > 1 ){
				$defaults =& $values;
				unset( $values );
			}else{
				$entries[] =& $values;
			}
			$args_count++;
		}

		$r = array(
			'entries' =>& $entries,
			'defaults' =>& $defaults
		);
		return $r;
	}

	// remove
	public function remove($t,$id){
		$t = $this->db->escape($t);
		$id = (int)$id;
		return $this->db->_query("DELETE FROM `$t` WHERE id = '$id'");
	}
	
   // get
	public function get($id){
		$t = $this->name;
		$id = $this->db->escape($id);
		return $this->db->_query("SELECT * FROM `$t` WHERE id = '$id'");
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
			'dd' => '([0-9][0-9])','mm' => '([0-9][0-9])','yyyy' => '([0-9][0-9][0-9][0-9])',
			'd' => '([0-9][0-9]?)','m' => '([0-9][0-9]?)','yy' => '([0-9][0-9])',
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
		return $this->db->_query("SELECT * FROM `$t` WHERE id = '$id'");
	}
	public function offsetUnset($id){
		$t = $this->name;
		$id = $this->db->escape($id);
		return $this->db->_query("DELETE FROM `$t` WHERE id = '$id'");
	}
	public function offsetExists($id){
		$t = $this->name;
		$id = $this->db->escape($id);
		$r = $this->db->_query("SELECT 1 FROM `$t` WHERE id = '$id'");
		return $r->num_rows() > 0;
	}
	public function truncate(){
		$t = $this->name;
		return $this->db->_query("TRUNCATE `$t`");
	}
}
