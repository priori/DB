<?php
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
			$sql = $this->value($aux,$v);

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
