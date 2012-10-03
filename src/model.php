<?php

// tomar cuidado com isset em arrays
// se for null irá retornar false
// use array_key_exists no lugar
class Model implements arrayaccess, Countable{

	private $db;
	private $args;
	private $alias; // model name
	private $name; // table scaped name
	private $pk = 'id';
	private $pkt = array('id' => true );
	private $mode;
	private $a;
	private $b;
	private $schema;

	function Model( &$link, &$name, $args = false, $mode = 0, $schema = false ){
		$this->mode = $mode;
		$this->db =& $link;
		$this->alias =& $name;
		$this->schema =& $schema;
		// only variable should be assigned by reference
		$this->name = $link->_escape( $name ); 
		$this->args =& $args;
		if( $mode === DB::POSTGRESQL ){
			$this->a = '"';
			$this->b = '"';
		}else{
			$this->a = '`';
			$this->b = '`';
		}
	}

	function __toString(){
		if( $this->schema )
			return $this->a.$this->schema.$this->b.'.'.$this->a.$this->name.$this->b;
		else
			return $this->a.$this->name.$this->b;
	}
	function pk(){
		return $this->pk;
	}

	// set, update
	function set( $id, $args ){
		if( !is_array($args) ){
			$this->db->fire_error('Argumento inválido, para valores espera-se um array');
		}
		$args = Decoder::decode_array( $args );
		if( is_array($id) ){
			$id = $this->build_id( $id );
		}
		if( is_object($id) or is_resource($id) ){
			$this->db->fire_error("Invalid arguments. Id can't be a object.");
		}
		return $this->_set( $id, $args );
	}

	// se id é array já deve ter sido testado
	private function build_id( &$id ){
		if( count($this->pkt) != count($id) )
			$this->db->fire_error('Id inválido! '.
				'Quantidade valores especificados para o id não bate com a quantidade de '.
				'colunas da chave primária. ');
		$pk = array();
		$falta = array();
		foreach( $this->pkt as $c => $v ){
			if( isset($id[$c]) ){
				$pk[$c] = $id[$c];
			}else{
				$falta[] = $c;
			}
		}
		$c2 = 0;
		foreach( $id as $c => $v ){
			if( is_string($c) ){
				if( !isset($this->pkt[$c]) ){
					$this->db->fire_error('Coluna <strong>'.$c.'<strong> não faz parte da chave primária.');
				}
				$pk[$c] = $v;
			}elseif( is_int($c) ){
				$pk[ $falta[$c2] ] = $v;
				$c2++;
			}else{
				$this->db->fire_error('Id inválido! Indices devem ser string ou inteiro.');
			}
		}
		return $pk;
	}


	function _set(&$id, &$attrs){
		$vals = false;
		if( !$this->validate($attrs,$vals,'set') ){ // e o replace?
			return;
		}
		$n = array();
		$q = array();
		$q[] = 'UPDATE ';
		$q[] = $this->a;
		if( $this->schema ){
			$q[] = $this->schema;
			$q[] = $this->b;
			$q[] = '.';
			$q[] = $this->a;
		}
		$q[] = $this->name;
		$q[] = $this->b;
		$q[] = ' SET ';
		$b = false;
		foreach( $attrs as $name => $attr ){
			$v;
			if( $b ){
				$q[] = ', ';
			}else{
				$b = true;
			}
			$q[] = $this->a;
			$q[] = $this->db->_escape($name);
			$q[] = $this->b;
			$q[] = ' = ';
			if( isset($attr[1]) ){ // ponteiro
				$v = $attr[1];
			}else{
				$this->db->fire_error('Chave sem valor!');
				return false;
			}
			if( isset($attr['sql']) ){
				$q[] = $attr[1];
			}else{
				$q[] = '\'';
				// $q[] = $this->db->escape($attr[1]);
				$q[] = $attr[1];
				$q[] = '\'';
			}
			// $this->sql_value( $q, $v, $attr, $name );
		}
		$b = false;
		$aux = array();
		if( is_array( $id ) ){
			$this->sql_where( $q, $id );
		}else{
			$this->sql_where_id_eq( $q, $id);
		}
		return !!$this->db->_query(implode('',$q));
	}

	// add, insert
	function add( $e ){
		// sem multiplas insersoes por enquanto
		// if( $e2 !== false ){ $e2=false; $e=func_get_args(); }
		return $this->_add( $e, false );
	}
	function replace( $e ){
		return $this->_add( $e, true );
	}

	private $macros = array('sql'=>true,'now'=>true,'date'=>true,'int'=>true,'text'=>true,
		'format'=>true,'num'=>true,'numeric'=>true,'integer'=>true, 'serialize'=>true,
		'date_time' => true );
	// microtime, date_time, required, length_gt, length_lt, gt, lt
	private $macros_alias = array('num'=>'numeric','int'=>'integer');
	private $macros_not_type = array('sql'=>true,'serialize'=>true,'now'=>true);  
	private $macros_optional_params = array('date'=>true,'text'=>true,'sql'=>true); // numeric(size)
	private $macros_required_params = array('format'=>true);
	private $macros_dont_need_value = array( 'now' => true );
	private function validate( &$es, &$vals, $tipo ){
		// nao vamos pensar em vals por enquanto
		$r = true;
		$msg = array();
		$ok = true;
		// macros sendo usadas de forma errada -> fire_error
		// verifica o tipo dos parametros -> fire_error
		// formata valores
		// verifica valores mal formatados -> add_error
		foreach( $es as $k => $e ){
			// não valida o valor passado
			$this->validate_macros( $e, array_key_exists(1,$es[$k]) );
			if( array_key_exists(1,$es[$k]) ){
				$r = $this->value( $es[$k], $es[$k][1] );
			}else{
				$value;
				$r = $this->value( $es[$k], $value, true );
				$es[$k][1] = $value;
			}
			if( !$r ){
				$ok = $r;
			}
		}
		return $ok;
	}
	// tá errado, a ideia era acumular os erros
	// só está acumulando erros de uma mesma coluna (indice)
	function validate_macros( &$e, $with_value ){
		$msg = array();
		$macro_need_value = true;
		$typed = 0;
		if( isset($e['now']) ){
			$c = count($e);
			if( $with_value ){
				$c--;
				$msg[] = 'Índice "';
				$msg[] = $e[0];
				$msg[] = '" deve ser usado sem valor! Macro "now" é usada sem valor. ';
			}
			if( $e['now'] !== true ){
				$msg[] = 'Macro "now" deve ser usada sem parametros. ';
			}
			if( $c > 2 ){
				$msg[] = 'Macro "now" deve ser usada sozinha. ';
			}
		}else{
			foreach( $e as $macro => $params ){
				if( $macro === 0 or $macro === 1 )continue;
				if( !isset($this->macros[$macro]) ){
					$msg[] = 'Macro "';
					$msg[] = $macro;
					$msg[] = '" desconhecida. ';
					if( isset($this->macros[strtolower($macro)]) ){
						$msg[] = 'Use lower case in macros. ';
					}elseif( isset($this->macros[trim($macro)]) ){
						$msg[] = 'Be aware of white spaces in macros. ';
					}elseif( isset($this->macros[trim(strtolower($macro))]) ){
						$msg[] = 'Use lower case and no white spaces in macros. ';
					}
					continue;
				}
				if( isset($this->macros_alias[$macro]) and isset($e[$this->macros_alias[$macro]]) ){
					$msg[] = 'Redundancy! Dont use "';
					$msg[] = $macro;
					$msg[] = '" with "';
					$msg[] = $this->macros_alias[$macro];
					$msg[] = '"! ';
				}elseif( !isset($this->macros_not_type[$macro]) ){
					$typed++;
				}
				if( isset($this->macros_required_params[$macro]) and $params === true ){
					$msg[] = 'Macro "';
					$msg[] = $macro;
					$msg[] = '" needs parameters! ';
				}
				if( isset($this->macros_dont_need_value[$macro]) ){
					$macro_need_value = false;
					if( $with_value ){
						$msg[] = 'Índice "';
						$msg[] = $e[0];
						$msg[] = '" deve ser usado sem valor! Macro :';
						$msg[] = $macro;
						$msg[] = ' é usada sem valor. ';
					}
				}
			}
			if( $typed > 1 ){
				$ms = array();
				foreach( $e as $macro => $p ){
					if( $macro === 0 or $macro === 1 )continue;
					if( !isset($this->macros_not_type[$macro]) ){
						if( !isset($this->macros_alias[$macro]) or !isset($e[$this->macros_alias[$macro]]) )
							$ms[] = $macro;
					}
				}
				$msg[] = 'Dont use ';
				foreach( $ms as $k => $m ){
					$msg[] = $m;
					if( count($ms) === $k+2 ){
						$msg[] = ' and ';
					}else if( count($ms) !== $k+1 ){
						$msg[] = ', ';
					}
				}
				$msg[] = ' together! ';
			}
			if( $macro_need_value and !$with_value ){
				$msg[] = 'Índice "';
				$msg[] = $e[0];
				$msg[] = '" deve ser ter algum valor!';
			}
		}
		if( count($msg) ){
			$this->db->fire_error(join('',$msg));
		}
	}


	function _add(&$e,$replace=false){
		if( !is_array($e) ){
			$this->db->fire_error('Valor inválido, para inserção de valores espera-se um array');
		}
		$e = Decoder::decode_array( $e );
		$vals = false;
		if( !$this->validate($e,$vals,'add') ){ // e o replace?
			return false;
		}
		return $this->__add($e,$replace);
	}

	function __add(&$e,$replace=false){
		$q = array();
		if( $replace===true )
			$q[] = 'REPLACE INTO ';
		else
			$q[] = 'INSERT INTO ';
		$q[] = $this->a;
		if( $this->schema ){
			$q[] = $this->schema;
			$q[] = $this->b;
			$q[] = '.';
			$q[] = $this->a;
		}
		$q[] = $this->name;
		$q[] = $this->b;
		$q[] = ' (';
		$b = false;
		foreach( $e as $v ){
			$col = $v[0];
			if( $b ){
				$q[] = ',';
			}else{
				$b = true;
			}
			$q[] = $this->a;
			$q[] = $this->db->escape($col);
			$q[] = $this->b;
		}
		$q[] = ') VALUES (';
		$b = false;
		foreach( $e as $v ){
			$value = $v[1];
			if( $b ){
				$q[] = ',';
			}else{
				$b = true;
			}
			if( isset($v['sql']) ){
				$q[] = $value;
			}else{
				$q[] = '\'';
				$q[] = $this->db->escape($value);
				$q[] = '\'';
			}
		}
		$q[] = ')';

		return !!$this->db->_query(implode('',$q));
	}

	// quando não usa aspas
	// não dá fazer resolver no _value (formata e valida)
	private function sql_value( &$q, &$v, &$b, &$c ){
		if( is_int($b[1]) or is_float($b[1]) ){
			$q[] = $b[1];
		}elseif( isset($b['serialize']) ){
			$q[] = '\'';
			$q[] = $this->db->_escape(serialize($b[1]));
			$q[] = '\'';
		}elseif( isset($b['sql']) ){
			$q[] = $b[1];
		}else{
			$c =& $b[1];
			if( is_int($c) or is_float($c) ){
				$q[] = $c;
			}else{
				$q[] = '\'';
				$q[] = $this->db->_escape($c);
				$q[] = '\'';
			}
		}
	}

	private function value( &$attr, &$value, $no_value = false ){
		$r = (include 'value.php');
		if( isset($value) )
			$attr[1] = $value;
		return $r;
	}

	function build_args( &$args, &$parameters=false ){
		include 'build_args.php';
	}

	// remove
	function remove($id){
		$t = $this->__toString();
		$sql = array("DELETE FROM $t ");
		if( is_array($this->pk) and !is_array($id) ){
			$id = func_get_args();
		}else if( func_num_args() != 1 ){
			$this->db->fire_error("Argumentos inválidos. Método espera somente um argumento (id).");
		}
		if( is_object($id) or is_resource($id) ){
			$this->db->fire_error("Invalid arguments. Id can't be a object.");
		}
		if( is_array($id) ){
			$id =& $this->build_id( $id );
			$this->sql_where( $sql, $id );
		}else{
			$this->sql_where_id_eq( $sql, $id );
		}
		return !!$this->db->_query(implode('',$sql));
	}

   // get
	function get($id){
		$t = $this->__toString();
		$sql = array("SELECT * FROM $t ");
		if( is_array($this->pk) and !is_array($id) ){
			$id = func_get_args();
		}else if( func_num_args() != 1 ){
			$this->db->fire_error("Argumentos inválidos. Método espera somente um argumento (id).");
		}
		if( is_object($id) or is_resource($id) ){
			$this->db->fire_error("Invalid arguments. Id can't be a object.");
		}
		if( is_array($id) ){
			$id =& $this->build_id( $id );
			$this->sql_where( $sql, $id );
		}else{
			$this->sql_where_id_eq( $sql, $id );
		}
		$r = $this->db->_query(implode('',$sql));
		return $r->fetch();
	}

	function remove_where( $w ){
		// $w =& Decoder::decode_array( $w );
		// $this->validate_where( $w );
		$t = $this->__toString();
		$q = array("DELETE FROM $t");
		$this->sql_where( $q, $w );
		return !!$this->db->_query(implode($q));
	}
	function get_where( $w ){
		// $w =& Decoder::decode_array( $w );
		// $this->validate_where( $w );
		$t = $this->__toString();
		$q = array("SELECT * FROM $t");
		$this->sql_where( $q, $w );
		$r = $this->db->_query(implode($q));
		return $r->fetch();
	}
	function set_where( $id, $v ){
		$this->_set_where( $id, $v );
	}

	function sql_where( &$q, &$w ){
		include 'sql_where.php';
	}






	function all(){
		$t = $this->__toString();
		return $this->db->_query("SELECT * FROM $t");
	}


	// adiciona erro a transacao
	// ou a ultima (será próxima?) query
	private function add_error( $field, $arg ){
		if( $this->schema ){
			$this->db->_add_error($this->schema.'.'.$this->name,$field,$arg);
		}else
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
			if( $r2[$c] == 'm' or $r2[$c] == 'mm' ){
				if( $month !== false and $month != $v ){
					return false;
				}
				$month = $v;
			}
			if( $r2[$c] == 'd' or $r2[$c] == 'dd' ){
				if( $day !== false and $day != $v ){
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
			if( $r2[$c] == 'yy' or $r2[$c] == 'yyyy' ){
				if( $year !== false and $year != $v ){
					return false;
				}
				$year = $v;
			}
		}
		if( $year === false or $month === false or $day === false )
			return error(); // isso não é erro comum
		return $year.'-'.$month.'-'.$day;
		// return eregi_replace('m',''.((int)$month),$format);
	}
	private function sql_where_id_eq( &$q, &$id ){
		if( is_array($this->pk) ){
			$this->db->fire_error('falta implementar...');
		}else{
			if( is_array($id) or is_object($id) or is_resource($id) ){
				$this->db->fire_error("Valor inválido para id!");
			}
			$q[] = ' WHERE ';
			$q[] = $this->a;
			$q[] = $this->pk;
			$q[] = $this->b;
			$q[] = ' = \'';
			$q[] = $this->db->_escape($id);
			$q[] = '\'';
		}
	}



	// array access
	function offsetSet($id,$val){
		if( $id === NULL ){
			return $this->_add( $val );
		}else{
			if( !is_array($val) ){
				$this->db->fire_error('Argumento inválido, para valores espera-se um array');
			}
			$val = Decoder::decode_array( $val );
			// id pode ser array, object, resource??
			// if( $this->db->errors() )return;
			return $this->_set( $id, $val );
		}
	}
	function offsetUnset($id){
		$t = $this->__toString();
		$sql = array("DELETE FROM $t ");
		$this->sql_where_id_eq( $sql, $id );
		return !!$this->db->_query(implode('',$sql));
	}

   // get
	function offsetGet($id){
		$t = $this->__toString();
		$sql = array("SELECT * FROM $t ");
		$this->sql_where_id_eq( $sql, $id );
		$r = $this->db->_query(implode('',$sql));
		return $r->fetch();
	}
	function offsetExists($id){
		$t = $this->__toString();
		$pk &= $this->pk;
		if( is_array($pk) )
			$this->db->fire_error('Você não pode utilizar o modelo assim tendo id múltiplo!');
		$sql = array("SELECT 1 FROM $t ");
		$this->sql_where_id_eq($sql,$id);
		$r = $this->db->_query(implode('',$sql));
		return $r->count() > 0;
	}
	function count(){
		$t = $this->__toString();
		$r = $this->db->_query("SELECT COUNT(*) AS c FROM $t");
		$r = $r->fetch();
		return (int)$r['c'];
	}
	function truncate(){
		$t = $this->__toString();
		return $this->db->_query("TRUNCATE $t");
	}
	function __set( $a, $b ){
		if( $a === 'pk' ){
			if( is_array($b) ){
				$pkt = array();
				foreach( $b as $c => $v ){
					if( !is_int($c) or !is_string($v) )
						$this->db->fire_error('Valor inválido para coluna chave primaria. '.
								'Parametro deve ser uma string ou um array de string com chaves numéricas!');
					$pkt[$v] = true;
				}
				if( count($b) == 1 )
					$this->pk = array_pop($b);
				else
					$this->pk = $b;
				$this->pkt = $pkt;
			}elseif( !is_string($b) ){
				$this->db->fire_error('Valor inválido para pk (primary key)!');
			}else{
				$this->pk = $this->db->_escape($b);
				$this->pkt = array($b=>true);
			}
		}else{
			$this->db->fire_error('Atributo '.$a.' não editavel');
		}
	}
}
