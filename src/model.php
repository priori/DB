<?php

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

	public function Model( &$link, &$name, $args = false, $mode = 0, $schema = false ){
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

	public function __toString(){
		if( $this->schema )
			return $this->a.$this->schema.$this->b.'.'.$this->a.$this->name.$this->b;
		else
			return $this->a.$this->name.$this->b;
	}
	public function pk(){
		return $this->pk;
	}

	// set, update
	public function set( $id, $args ){
		if( !is_array($args) ){
			$this->db->fire_error('Argumento inválido, para valores espera-se um array');
		}
		$args =& Decoder::decode_array( $args );
		if( is_array($id) ){
			$id =& $this->build_id( $id );
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


	public function _set(&$id, &$attrs){
		$vals = false;
		if( !$this->validate($attrs,$vals,'set') ){ // e o replace?
			// return;
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
			if( isset($attr['content']) ){ // ponteiro
				$v = $attr['content'];
			}else{
				$this->db->fire_error('Chave sem valor!');
				return;
			}
			if( isset($attr['sql']) ){
				$q[] = $attr['content'];
			}else{
				$q[] = '\'';
				$q[] = $this->db->escape($attr['content']);
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
		'format'=>true,'num'=>true,'numeric'=>true,'integer'=>true, 'serialize'=>true,
		'date_time' => true );
	// microtime, date_time, required, length_gt, length_lt, gt, lt
	private $macros_alias = array('num'=>'numeric','int'=>'integer');
	private $macros_not = array('int'=>'date','integer'=>'date');
	private $macros_optional_params = array('date'=>true,'text'=>true,'sql'=>true); // numeric(size)
	private $macros_required_params = array('format'=>true);
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
			// macros que não podem ser usadas em conjunto
			// macro junto a sua alias
			// não valida o valor passado
			$ok = $this->validate_macros( $e );
			if( $ok and isset($es[$k]['content']) ){
				$r = $this->value( $es[$k], $es[$k]['content'] );
			}else{
				$value;
				$r = $this->value( $es[$k], $value );
				$es[$k]['content'] = $value;
			}
			if( !$r ){
				$ok = $r;
			}
		}
		return $ok;
	}
	public function validate_macros( &$e ){
		$ok = true;
		$msg = array();
		foreach( $e as $macro => $params ){
			if( $macro === 0 or $macro === 'content' )continue;
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
				$ok = false;
			}
			if( isset($this->macros_not[$macro]) and isset($e[$this->macros_not[$macro]]) ){
				$msg[] = 'Redundancy! Dont use "';
				$msg[] = $macro;
				$msg[] = '" with "';
				$msg[] = $this->macros_alias[$macro];
				$msg[] = '"! ';
			}
			if( isset($this->macros_alias[$macro]) and isset($e[$this->macros_alias[$macro]]) ){
				$msg[] = 'Redundancy! Dont use "';
				$msg[] = $macro;
				$msg[] = '" with "';
				$msg[] = $this->macros_alias[$macro];
				$msg[] = '"! ';
				$ok = false;
			}
			if( isset($this->macros_required_params[$macro]) and false ){
				$msg[] = 'Macro "';
				$msg[] = $macro;
				$msg[] = '" needs parameters! ';
				$ok = false;
			}
		}
		if( count($msg) )
			$this->db->fire_error(join('',$msg));
		return $ok;
	}


	public function _add(&$e,$replace=false){
		if( !is_array($e) ){
			$this->db->fire_error('Valor inválido, para inserção de valores espera-se um array');
		}
		$e = Decoder::decode_array( $e );
		$vals = false;
		if( !$this->validate($e,$vals,'add') ){ // e o replace?
			// return;
		}
		return $this->__add($e,$replace);
	}

	public function __add(&$e,$replace=false){
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
			$value = $v['content'];
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

		return $this->db->_query(implode('',$q));
	}

	// quando não usa aspas
	// não dá fazer resolver no _value (formata e valida)
	private function sql_value( &$q, &$v, &$b, &$c ){
		if( is_int($b['content']) or is_float($b['content']) ){
			$q[] = $b['content'];
		}elseif( isset($b['serialize']) ){
			$q[] = '\'';
			$q[] = $this->db->_escape(serialize($b['content']));
			$q[] = '\'';
		}elseif( isset($b['sql']) ){
			$q[] = $b['content'];
		}else{
			$c =& $b['content'];
			if( is_int($c) or is_float($c) ){
				$q[] = $c;
			}else{
				$q[] = '\'';
				$q[] = $this->db->_escape($c);
				$q[] = '\'';
			}
		}
	}

	private function value( &$attr, &$value ){
		$r = (include 'value.php');
		if( isset($value) )
			$attr['content'] = $value;
		return $r;
	}

	private $valid_macros = array('date'=>true,'time'=>true,'date_time'=>true,
		'sql'=>true,'int'=>true,'decimal'=>true,'trim'=>true,'bool'=>true,
		'now'=>true,'format'=>true,'parse'=>true);

	public function build_args( &$args, &$parameters=false ){
		include 'build_args.php';
	}

	// remove
	public function remove($id){
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
		$sql = implode('',$sql);
		return $this->db->_query( $sql );
	}

   // get
	public function get($id){
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
		$sql = implode('',$sql);
		$r = $this->db->_query($sql);
		return $r->fetch();
	}
	public function remove_where( $w ){
		// $w =& Decoder::decode_array( $w );
		// $this->validate_where( $w );
		$t = $this->__toString();
		$q = array("DELETE FROM $t");
		$this->sql_where( $q, $w );
		return $this->db->_query(implode($q));
	}
	public function get_where( $w ){
		// $w =& Decoder::decode_array( $w );
		// $this->validate_where( $w );
		$t = $this->__toString();
		$q = array("SELECT * FROM $t");
		$this->sql_where( $q, $w );
		$r = $this->db->_query(implode($q));
		return $r->fetch();
	}
	public function set_where( $id, $v ){
		$this->_set_where( $id, $v );
	}

	public function all(){
		$t = $this->__toString();
		return $this->db->_query("SELECT * FROM $t");
	}


	function sql_where( &$q, &$w ){
		include 'sql_where.php';
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
	public function offsetSet($id,$val){
		if( $id === NULL ){
			return $this->_add( $val );
		}else{
			if( !is_array($val) ){
				$this->db->fire_error('Argumento inválido, para valores espera-se um array');
			}
			$val =& Decoder::decode_array( $val );
			// id pode ser array, object, resource??
			// if( $this->db->errors() )return;
			return $this->_set( $id, $val );
		}
	}
	public function offsetUnset($id){
		$t = $this->__toString();
		$sql = array("DELETE FROM $t ");
		$this->sql_where_id_eq( $sql, $id );
		$sql = implode('',$sql);
		return $this->db->_query( $sql );
	}

   // get
	public function offsetGet($id){
		$t = $this->__toString();
		$sql = array("SELECT * FROM $t ");
		$this->sql_where_id_eq( $sql, $id );
		$sql = implode('',$sql);
		$r = $this->db->_query($sql);
		return $r->fetch();
	}
	public function offsetExists($id){
		$t = $this->__toString();
		$pk &= $this->pk;
		if( is_array($pk) )
			$this->db->fire_error('Você não pode utilizar o modelo assim tendo id múltiplo!');
		$sql = array("SELECT 1 FROM $t ");
		$this->sql_where_id_eq($sql,$id);
		$sql = implode('',$sql);
		$r = $this->db->_query($sql);
		return $r->num_rows() > 0;
	}
	public function count(){
		$t = $this->__toString();
		$sql = "SELECT COUNT(*) AS c FROM $t";
		$r = $this->db->_query($sql);
		$r = $r->fetch();
		return $r['c'];
	}
	public function truncate(){
		$t = $this->__toString();
		return $this->db->_query("TRUNCATE $t");
	}
	public function __set( $a, $b ){
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
