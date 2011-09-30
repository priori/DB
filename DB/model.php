<?php 


class Model{
	
	private $link;
	private $args;
	private $alias; // model name
	private $name; // table scaped name


	public function Model( &$link, &$name, $args = false )
	{
		$this->db =& $link;
		$this->alias =& $name;
		$this->name =& $link->escape( $name );
		$this->args =& $args;
	}
	
	public function __toString()
	{
		return '`'.$this->name.'`';
	}

	public function set( $where, $args )
	{
		$args = decode( $args );
		if( is_array($where) )
			$where = decode( $where );
		return $this->_set( $where, $args );
	}
	
	
	public function _set(&$where, &$attrs)
	{
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
		if( !is_array($where) ){
			
			$q[] = '`id` = \'';
			$q[] = $this->db->escape($where);
			$q[] = '\'';
			
		}else foreach( $where as $v ){
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
		// $q[] = $where;
		
		
		return $this->db->_query(implode('',$q));
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	public function add( $e, $e2=false ){
		if( $e2 !== false ){
			$e2=false;
			$e=func_get_args();
		}
		return $this->_add( $e, false );
	}
	public function _add(&$e,$replace=false){
		$e =& decode( $e );
		return $this->__add($e,$replace);
	}
	
	
	
	private function add_error( $field, $arg ){
		$this->db->_add_error($this->name,$field,$arg);
	}
	
	private function get_col_name( &$a ){
		return isset($a['col'])?$a['col']:(isset($a['name'])?
				$a['name']:false);
	}
	private function get_alias_name( &$a ){
		return isset($a['alias'])?$a['alias']:(isset($a['name'])?
				$a['name']:false);
	}
	private function is_col( &$attr ){
		return isset($attr['!']) && !isset($attr['model']) && 
			!is_array($attr['content']) || 
			is_array($attr['content']) && 
				(isset($attr['sql']) || isset($attr['serialize']) );
	}
	private function has_many( &$attr ){
		return isset($attr['model']) || is_array($attr['content']);
	}
	private function add_val( &$q, &$attr, &$default_entry ){
		
		$v;
		if( isset($attr['content']) ){
			$v =& $attr['content'];
		}else if( isset($default_entry['content']) ){
			$v =& $default_entry['content'];
			$attr =& $default_entry;
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
	
	
	
	public function __add(&$e,$replace=false){
		
		
		$q = array();
		if( $replace===true )
			$q[] = 'REPLACE INTO `';
		else
			$q[] = 'INSERT INTO `';
		$q[] = $this->name;
		$q[] = '` (';
		$names = array();
		$fazer_por_partes = false; // para pegar o inserted id correto
		
		$special = array();
		$need_transaction = false;
		
		$entries = array();
		$entries[] = array();
		$specials = array();
		$specials[] = array();
		
		$debugando = false; 
		// $entries[0] = array('!'=>array());
		foreach( $e as $c => $attr ){
			$name = $this->get_col_name( $attr );
			
			// talvez surgirah mais casos tipo o de table
			// que ha nome e nao deve ser usado agora
			if( $name && $this->is_col($attr) ){ // is col
				
				$entries[0][$name] =& $e[$c];
				
				if( !isset($names[$name]) ){
					if( $b )
						$q[] = ', ';
					else{
						$b = true;
					}
					$q[] = '`';
					$q[] = $this->db->escape($name);
					$q[] = '`';
					$names[$name] = true;
				}
				
			// is array, sei lá!
			}else if( !$name && !isset($attr['content']) && !isset($attr['!']) ){ 
				
				//$actual_entry = array('!'=>array());
				$actual_entry = array();
				$actual_special = array();
				// $tem_que_ter_algo = false
				foreach( $attr as $attr2 ){
					$name2 = $this->get_col_name( $attr2 );
					
					if( $name2 && $this->is_col($attr2) ){ // is col
						
						// $tem_que_ter_algo = true
						$actual_entry[ $name2 ] = $attr2;
						if( !isset($names[$name2]) ){
							if( $b )
								$q[] = ', ';
							else{
								$b = true;
							}
							$q[] = '`';
							$q[] = $this->db->escape($name2);
							$q[] = '`';
							$names[$name2] = true;
						}
						
					}elseif( $this->has_many($attr2) ){ // has many, query after
						
						$fazer_por_partes = true;
						$actual_special[] = $attr2;
						$need_transaction = true;
						
					// }elseif( query before ){
						
					// }elseif( is special ){
						
					}else{ 
						die('nao aceita macros complicadas para insersoes '.
							'multiplas que nao na primeira insersao');
						// $actual_entry['!'][] =& $attr2;
						// insiste em nao ter nome
					}
					// if( !$tem_que_ter_algo )que_maluquece();
				}
				$entries[] = $actual_entry;
				$specials[] = $actual_special;
				
				
			// }elseif( query before ){
				
			// }elseif( is special ){
				
			}else if( $this->has_many($attr) ){ // has many, query after,
				
				$specials[0][] = $attr;
				// if( isset($attr['model']) || is_array($attr['content']) ){
				$need_transaction = true;
				// }
				// $entries[0]['!'][] =& $attr;
				// insiste em nao ter nome
			}
		}
		$q[] = ') VALUES ';
		
		//if( !count($entries[0]) );
		//	array_shift( $entries );
		//
		$default_entry;
		if( count($entries) > 1 ){
			$default_entry =& $entries[0];
			array_shift( $entries );
		}
		$default_special;
		if( count($specials) > 1 ){
			$default_special =& $specials[0];
			array_shift( $specials );
		}
		
		
		// if( $fazer_por_partes ); // ai eh diferente
		if( $fazer_por_partes ){
			$len = count( $q );
			// var_dump( $specials );
			// die();
			foreach( $entries as $c => $e ){
				$es = array();
				$es[] =& $e;
				$aux = $default_entry;
				
				$r0 = $this->_values( $names, $es, $q, $need_transaction, 
						$specials[$c], $aux, $default_special );
				$q = array_slice($q, 0, $len);
			}
			
		}else{
			$a = array();
			foreach( $specials as $s ){
				foreach( $s as $c => $v ){
					$a[] =& $s[$c];
				}
			}
			return $this->_values( $names, $entries, $q, $need_transaction, 
					$a, $default_entry, $default_special );
			
		}
		
		
		return $r0;
		// 
	}
	private function _values( &$names, &$entries, &$q, &$need_transaction, 
			&$special, &$default_entry, &$default_special ){
		
		
		$b2 = false;
		foreach( $entries as $entry ){
			if( $b2 )
				$q[] = ', ';
			$q[] = '(';
			$b = false;
			foreach( $names as $name => $t ){
				if( $b ){
					$q[] = ', ';
				}
				$attr;
				if( isset($entry[$name])  ){ // coisa maluca, esquisito
					$attr =& $entry[$name];
				}elseif( isset($default_entry[$name]) ){
					$attr =& $default_entry[$name];
				}else{
					$q[] = 'DEFAULT';
					$b = true;
					continue;
				}
				$this->add_val( $q, $attr, $default_entry );
				$b = true;
			}
			$q[] = ')';
			$b2 = true;
		}
		
		
		
		if( $need_transaction ){
			$this->db->begin(true);
			$r1 = $this->db->_query( implode('',$q) );
			$id = $this->db->insert_id();
			foreach( $special as $attr ){
				$n = $this->get_col_name($attr);
				if( is_array($attr['content']) && $n ){
					$fk = $this->name.'_id';
					if( isset($attr['fk']) )
					$fk = $attr['fk'];
					$attr['content'][] = array('!'=>true,'col'=>$fk,
							'content'=>''.$id ,'name'=>$fk);
					$r = $this->db->$n->__add( $attr['content'] );
					if( $r === false ){
						$this->db->end();
						return $r;
					}
				}
			}
			$this->db->end();
			return $r1;
			
		}else{
			return $this->db->_query( implode('',$q) );
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	public function remove($t,$id)
	{
		$t = $this->db->escape($t);
		$id = (int)$id;
		return $this->db->_query("DELETE FROM `$t` WHERE id = '$id'");
	}

	public function get($t,$id)
	{
		$t = $this->db->escape($t);
		$id = (int)$id;
		return $this->db->_query("SELECT * FROM `$t` WHERE id = '$id'");
	}
	
	private function sql_date_format($d,$format)
	{
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
	
	private function sql_date($d,$format)
	{
		$f = strtr($format,array( 
			'dd' => '([0-9][0-9])','mm' => '([0-9][0-9])','yyyy' => '([0-9][0-9][0-9][0-9])',
			'd' => '([0-9][0-9]?)','m' => '([0-9][0-9]?)','yy' => '([0-9][0-9])'
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

}
