<?php

$count = 0;
$or = true;
if( count( $w ) ){
	$q[] = 'WHERE ';
}
foreach( $w as $c => $v ){
	if( $c === $count ){
		if( $v === 'or' or $v === 'OR' ){
			$q[] = " OR ";
		}else{
			$v = Decoder::decode_string($v);
			if( !$or ){
				$q[] = ' AND `';
			}else{
				$q[] = '`';
			}
			if( isset($v['null']) ){
				$q[] = $this->db->escape($v[0]);
				$q[] = '`';
				$q[] = " IS NULL ";
			}elseif( isset($v['not_null']) ){
				$q[] = $this->db->escape($v[0]);
				$q[] = '`';
				$q[] = " IS NOT NULL ";
			}else{
				$o = htmlspecialchars($v);
				$this->db->fire_error("Operador desconhecido: <strong>$o</strong>");
			}
		}
		$count++;
		$or = true;
		continue;
	}else{
		if( !$or ){
			$q[] = ' AND `';
		}else{
			$q[] = '`';
		}
		$c = Decoder::decode_string($c);
		$q[] = $this->db->escape($c[0]);
		// verificar mais de um dentre gt, lt, ge, le, in
		// verificar se tem alguma macro errada
		if( isset($c['gt']) )
			$q[] = '` > ';
		elseif( isset($c['lt']) )
			$q[] = '` < ';
		elseif( isset($c['ge']) )
			$q[] = '` >= ';
		elseif( isset($c['le']) )
			$q[] = '` <= ';
		elseif( isset($c['in']) )
			$q[] = '` in ';
		elseif( isset($c['between']) )
			$q[] = '` BETWEEN ';
		else
			$q[] = '` = ';
		if( isset($c['sql']) ){
			$q[] = '(';
			$q[] = $v;
			$q[] = ')';
		}else{
			// fazer data funcionar e verificacoes
			if( is_array($v) and isset($c['in']) ){
				// verificar se o array está normal
				$b = false;
				$q[] = '(';
				foreach( $v as $c2 => $v2 ){
					if( $b ){
						$q[] = ', \'';
					}else{
						$q[] = '\'';
					}
					$q[] = $this->db->escape($v2);
					$q[] = '\'';
					$b = true;
				}
				$q[] = ')';
			}elseif( isset($c['between']) ){
				$q[] = '\'';
				$q[] = $this->db->escape($v[0]);
				$q[] = '\' AND \'';
				$q[] = $this->db->escape($v[1]);
				$q[] = '\'';
			}else{
				// verficar se v está normal
				$q[] = '\'';
				$q[] = $this->db->escape($v);
				$q[] = '\'';
			}
		}
		// falta outras macros
	}
	$or = false;
}
