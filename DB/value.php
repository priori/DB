<?php

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
