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
			// não boa ideia, e a chave primaira for com duas colunas?
			$this->db->fire_error("Id não é um valor válido");
			// $id = Decoder::decode_string( $id );
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
		foreach( $attrs as $attr ){
			$v;
			if( isset($attr['col']) ){
				$c =& $attr['col'];
			}else if( isset($attr['name']) ){
				$c =& $attr['name'];
			}else{
				continue;
			}
			if( $b ){
				$q[] = ', ';
			}else{
				$b = true;
			}
			$q[] = '`';
			$q[] = $this->db->escape($c);
			$q[] = '` = ';
			
			$this->add_val( $q, $attr, $n );
		}
		$q[] = ' WHERE ';
		
		$b = false;
		$aux = array();
		if( !is_array($id) ){
			
			$q[] = '`id` = \'';
			$q[] = $this->db->escape($id);
			$q[] = '\'';
			
		}else foreach( $id as $v ){
			if( $b ){
				$q[] = ' AND ';
			}else{
				$b = true;
			}
			$q[] = '`';
			$q[] = $this->get_col_name( $v );
			$q[] = '` = ';
			$this->add_val( $q, $v, $aux );
		}
		// $q[] = $id;
		
		return $this->db->_query(implode('',$q));
	}
	
	// add, insert
	public function add( $e, $e2=false ){
		if( $e2 !== false ){
			$e2=false;
			$e=func_get_args();
		}
		return $this->_add( $e, false );
	}
	public function replace( $e, $e2=false ){
		if( $e2 !== false ){
			$e2=false;
			$e=func_get_args();
		}
		return $this->_add( $e, true );
	}

	public function _add(&$e,$replace=false){
		$e =& Decoder::decode_array( $e );
		// if( ! validate ) add error , return
		return $this->__add($e,$replace);
	}
	
	public function __add(&$e,$replace=false,$multiple_inserctions=false){

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

		// special é uma merda, tenho que melhorar isso
		// coisa mal explicada
		// $special = array();
		// $specials = array();
		// $specials[] = array();

		$b = false; // precisa de virgula?
		$entries_count = 0;
		$has_more_entries = true;
		while( $has_more_entries ){
			if( !$multiple_insercations ){
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


				// if( $name && true ){ // $this->is_col($attr) ) // is col
				// 	
				// 	$entries[0][$name] =& $e[$c];
				// 	
				// 	if( !isset($names[$name]) ){
				// 		if( $b )
				// 			$q[] = ', ';
				// 		else{
				// 			$b = true;
				// 		}
				// 		$q[] = '`';
				// 		$q[] = $this->db->escape($name);
				// 		$q[] = '`';
				// 		$names[$name] = true;
				// 	}
				// 	
				// }else if( !$name && !isset($attr['content']) && !isset($attr['!']) ){ 
				// 	
				// 	$actual_entry = array();
				// 	$actual_special = array();
				// 	foreach( $attr as $attr2 ){
				// 		$name2 = $this->get_col_name( $attr2 );
				// 		
				// 		if( $name2 && $this->is_col($attr2) ){ // is col
				// 			
				// 			$actual_entry[ $name2 ] = $attr2;
				// 			if( !isset($names[$name2]) ){
				// 				if( $b )
				// 					$q[] = ', ';
				// 				else{
				// 					$b = true;
				// 				}
				// 				$q[] = '`';
				// 				$q[] = $this->db->escape($name2);
				// 				$q[] = '`';
				// 				$names[$name2] = true;
				// 			}
				// 			
				// 		}elseif( false ){ // has many, query after
				// 			
				// 			$fazer_por_partes = true;
				// 			$actual_special[] = $attr2;
				// 			$need_transaction = true;
				// 			
				// 		}else{ 
				// 			die('nao aceita macros complicadas para insersoes '.
				// 				'multiplas que nao na primeira insersao');
				// 		}
				// 	}
				// 	$entries[] = $actual_entry;
				// 	$specials[] = $actual_special;
				// 	
				// 	
				// }else if( $this->has_many($attr) ){ // has many, query after,
				// 	
				// 	$specials[0][] = $attr;
				// 	$need_transaction = true;
				// }
			}
			$entries_count++;
		}
		$q[] = ') VALUES ';

		if( !$multiple_insercations ){
			return $this->_values( $q, $entries, $names, $need_transaction,
					$default_values );
		}
		// $default_values;
		// if( count($entries) > 1 ){
		// 	$default_values =& $entries[0];
		// 	array_shift( $entries );
		// }
		// $default_special;
		// if( count($specials) > 1 ){
		// 	$default_special =& $specials[0];
		// 	array_shift( $specials );
		// }
		// 
		// 
		// if( $fazer_por_partes ){
		// 	$len = count( $q );
		// 	foreach( $entries as $c => $e ){
		// 		$es = array();
		// 		$es[] =& $e;
		// 		$aux = $default_values;
		// 		
		// 		$r0 = $this->_values( $names, $es, $q, $need_transaction, 
		// 				$specials[$c], $aux, $default_special );
		// 		$q = array_slice($q, 0, $len);
		// 	}
		// 	
		// }else{
		// 	$a = array();
		// 	foreach( $specials as $s ){
		// 		foreach( $s as $c => $v ){
		// 			$a[] =& $s[$c];
		// 		}
		// 	}
		// 	return $this->_values( $names, $entries, $q, $need_transaction, 
		// 			$a, $default_values, $default_special );
		// 	
		// }
		
		
		return $r0;
	}
	
	// private function _values( &$names, &$entries, &$q, &$need_transaction, 
	// 	&$special, &$default_values, &$default_special ){
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
					$this->add_val( $q, $attr, $attr );
				}else{
					$q[] = 'DEFAULT';
				}
				// if( isset($entry[$name])  ){ // coisa maluca, esquisito
				// 	$attr =& $entry[$name];            
				// 	$this->add_val( $q, $attr, $default_values );  
				// }elseif( isset($default_values[$name]) ){
				// 	$attr =& $default_values[$name];    
				// 	$this->add_val( $q, $attr, $default_values );  
				// }else{
				// 	$q[] = 'DEFAULT';
				// 	$b = true;
				// 	continue;
				// }
				$b = true;
			}
			$q[] = ')';
			$b2 = true;
		}
		
		return $this->db->_query( implode('',$q) );
		
		
		// if( $need_transaction ){
		// 	$this->db->begin(true);
		// 	$r1 = $this->db->_query( implode('',$q) );
		// 	$id = $this->db->insert_id();
		// 	foreach( $special as $attr ){
		// 		$n = $this->get_col_name($attr);
		// 		if( is_array($attr['content']) && $n ){
		// 			$fk = $this->name.'_id';
		// 			if( isset($attr['fk']) )
		// 			$fk = $attr['fk'];
		// 			$attr['content'][] = array('!'=>true,'col'=>$fk,
		// 					'content'=>''.$id ,'name'=>$fk);
		// 			$r = $this->db->$n->__add( $attr['content'] );
		// 			if( $r === false ){
		// 				$this->db->end();
		// 				return $r;
		// 			}
		// 		}
		// 	}
		// 	$this->db->end();
		// 	return $r1;
		// 	
		// }else{
		// 	return $this->db->_query( implode('',$q) );
		// }
	}
	
	private function add_val( &$q, &$attr, &$default_values ){
		$v;
		if( isset($attr['content']) ){
			$v =& $attr['content'];
		}else if( isset($default_values['content']) ){
			$v =& $default_values['content'];
			$attr =& $default_values;
		}else{
			// if now: poe o now; else:
			$q[] = 'DEFAULT';
			return;
		}
		
		if( isset($attr['sql']) ){
			if( is_string($attr['sql']) ){
				
				$a = $attr['content']; 
				if( is_string($a) ){
					$a = array(array('name'=>$a));
				}
				// se nao for array, error!
				$sql = $attr['sql']; // & ?
				foreach( $a as $c => $v ){
					// solução estranha
					// modo mais facil era usar o $v['name']
					$a[$c] = '\''.$this->db->escape($v['name']).'\''; 
				}
				$sql = strtr($sql,array('%'=>"%%",'?'=>"%s"));
				$q[] = vsprintf($sql,$a);
			}else{
				$q[] = $attr['content']; // & ?
			}
			
			
		}else if( $v === NULL ){
			$q[] = 'NULL';
		}else{
			if( isset($attr['serialize']) ){ 
				// || is_object($attr['serialize']) || 
				// is_array($attr['serialize'])
				// content array poderiam estar maculados pela funcao que 
				// decodifica as macros
				// eh preciso fazer colocar exeções na função
				$v =& serialize($attr['content']);
			}
			if( isset($attr['trim']) ){ 
				$v =& trim($attr['content']);
			}
			if( isset($attr['trim']) ){ 
				$v =& trim($attr['content']);
			}
			if( isset($attr['upper_case']) || isset($attr['upper']) ){ 
				$v =& strtoupper($attr['content']);
			}
			if( isset($attr['lower_case']) || isset($attr['lower']) ){ 
				$v =& strtolower($attr['content']);
			}
			
			$type = false;
			if( isset($attr['int']) ){
				$valid = is_int($v) || ((int)$v).'' == ''.$v;
				$this->add_error($this->get_alias_name($attr),'not_int');
				$type = true;
			}
			if( isset($attr['date']) ){
				if( $type )error();
				
				if( !is_string($attr['date']) )error();
				
				$aux = $this->sql_date($v,$attr['date']);
				if( $aux === false ){
					$this->add_error($this->get_alias_name($attr),'format_date');
				}
				$v =& $aux;
				
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
				if( strlen($v)>$t ){
					// error
					$this->add_error($this->get_alias_name($attr),'grande');
				}
				if( strlen($v)<$b ){
					$this->add_error($this->get_alias_name($attr),'pequeno');
				}
				$type = true;
			}
			if( isset($attr['time']) ){
				if( $type )error();
				$type = true;
			}
			
			$q[] = '\'';
			$q[] = $this->db->escape( $v );
			$q[] = '\'';
		}
	}
	


	// ajuda o set e o add
	// pega nome da coluna, alias, vê ser é coluna, 
	// se é relação é muitos, etc
//	private function get_col_name( &$a ){
//		return isset($a['col'])?$a['col']:(isset($a['name'])?
//				$a['name']:false);
//	}
//	private function get_alias_name( &$a ){
//		return isset($a['alias'])?$a['alias']:(isset($a['name'])?
//				$a['name']:false);
//	}
//	private function is_col( &$attr ){
//		return isset($attr['!']) && !isset($attr['model']) && 
//			!is_array($attr['content']) || 
//			is_array($attr['content']) && 
//				(isset($attr['sql']) || isset($attr['serialize']) );
//	}
//	private function has_many( &$attr ){
//		return isset($attr['model']) || is_array($attr['content']);
//	}
	
	private $valid_macros = array('date'=>true,'time'=>true,'date_time'=>true,
		'sql'=>true,'int'=>true,'decimal'=>true,'trim'=>true,'bool'=>true,
		'now'=>true,'format'=>true,'parse'=>true);

	public function build_args( &$args ){
		if( !is_array( $args ) ){
			$this->db->fire_error("Argumentos inválidos!");
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
			foreach( $e as $c => $v ){
				$has_content = false;
				if( is_string( $c ) ){
					$aux =& Decoder::decode_string( $c );
					if( isset($aux['content']) ){
						return 1;
					}
					$has_content = true;
					$aux['content'] = $v;

				// haverá esse caso mesmo?
				}elseif( $c === $count && is_string($v) ){ 
					$count++;
					$aux = Decoder::decode_string( $v );

				}else if( $c===$count && $args_count == 0 && is_array($v) ){
					$args[] =& $e[$c]; 
					unset( $e[$c] );
					continue;
					
				}else{
					return 2;
				} 

				if( isset($aux[0]) ){
					$name =& $aux[0];
					unset( $aux[0] );
					// if( has_value )
					//		$values[$name] = $this->value($v,$aux);
					$values[$name] = $aux;

				}else{ // que merda é essa?
					// como você chegou aqui?
					$values[] = $aux;
				}
				// valida, gera valores, diz se é sql ou precisa escapar e aspas
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
	public function get($t,$id){
		$t = $this->db->escape($t);
		$id = (int)$id;
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
		
	}
	public function offsetUnset($id){
		
	}
	public function offsetExists($id){
		
	}
	
}
