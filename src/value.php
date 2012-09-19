<?php

if( $no_value and !isset($attr['now']) )
	$this->db->fire_error("Não foi passado valor para ".$attr[0].'. ');

if( isset($attr['sql'])  ){
	if( is_string($attr['sql']) ){
		// validar valor para chave com macro sql
		$numeric = is_numeric($value);
		if( !is_array($value) ){
			$value = array( $value );
		}
		$sql = $attr['sql']; // & ?
		foreach( $value as $c => $v ){
			if( is_bool($v) ){
				$value[$c] = $v?'1':'0'; 
			}elseif( is_numeric($v) ){
				$value[$c] = ''.$v; 
			}elseif( is_array($v) or is_object($v) or is_resource($v) ){
				$this->db->fire_error("Valor inválido!");
			}else{
				$value[$c] = '\''.$this->db->escape($v).'\''; 
			}
		}
		$sql = strtr($sql,array('%'=>"%%",'?'=>"%s"));
		$value = vsprintf($sql,$value);
		return true;
	}else{
		$value = $value; // & ?
		return true;
	}
	
}elseif( isset($attr['now']) ){
	if( !$no_value )
		$this->db->fire_error("Ao usar a macro :now não especifique um valor");
	if( $attr['now'] !== true )
		$this->db->fire_error("A macro :now não espera parametros.");
	$value = 'NOW()';
	$attr['sql'] = true;
	return true;

}elseif( isset($attr['serialize']) ){
	$value = serialize($value);
	return true;

}else if( $value === NULL ){ // deveria ser diferenciado de quando não foi passado valor
	$value = 'NULL';
	$attr['sql'] = true;
	return true;

}else{
	// if has many ...
	// else
	if( is_array($value) or is_object($value) or is_resource($value) ){
		$this->db->fire_error("Tipo inválido para valor!");
	}
	// upper_case upper trim lower_case lower
	// if( isset($attr['trim']) ){ 
	// 	$value = trim($value);
	// }
	// if( isset($attr['upper_case']) or isset($attr['upper']) ){ 
	// 	$value = strtoupper($value);
	// }
	// if( isset($attr['lower_case']) or isset($attr['lower']) ){ 
	// 	$value = strtolower($value);
	// }
	
	if( isset($attr['int']) or isset($attr['integer']) ){
		$valid = (is_int($value) or ((int)$value).'' == ''.$value);
		if( !$valid ){
			$this->add_error($attr[0],'not_int');
			return false;
		}
		$attr['sql'] = true;
		return true;

	}elseif( isset($attr['numeric']) or isset($attr['num']) ){
		$valid = is_numeric($value);
		if( !$valid ){
			$this->add_error($attr[0],'not_num');
			return false;
		}
		$attr['sql'] = true;
		return true;

	}elseif( isset($attr['date']) ){
		if( $attr['date'] === true ){
			$aux = $this->sql_date($value,$this->db->date_format());
		}elseif( is_string($attr['date']) ){
			$aux = $this->sql_date($value,$attr['date']);
		}
		if( $aux === false ){
			$this->add_error($attr[0],'format_date');
			return false;
		}else{
			$value = $aux;
		}
		return true;

	}elseif( isset($attr['format']) ){
		if( !preg_match('/^'.$attr['format'].'$/',$value) ){
			$this->add_error($attr[0],'format');
			return false;
		}
		return true;

	}elseif( isset($attr['text']) ){
		if( !ereg('^[0-9]+-[0-9]+$',$attr['text']) )
			$this->db->fire_error(':text deve ser usado junto com tamanho ex:text(1-100)');
		$text = explode('-',$attr['text']);
		$b = (int)$text[0];
		$t = (int)$text[1];
		if( $b > $t ){
			$this->db->fire_error('Em :text o maior tem que ser maior que o menor.');
		}
		if( strlen($value)>$t ){
			$this->add_error($this->get_alias_name($attr),'grande');
			return false;
		}
		if( strlen($value)<$b ){
			$this->add_error($this->get_alias_name($attr),'pequeno');
			return false;
		}
		return true;

	}elseif( $value === false ){
		$value = "0";
		return true;
	}elseif( $value === true ){
		$value = "1";
		return true;
	}else{
		$value = $this->db->escape( $value );
		return true;
	}
}
return false;
