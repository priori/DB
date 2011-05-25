<?php

function mmacthed( &$mats, &$ant, &$attr_name, $content ){
	if( $ant === false ){
		$mats['name'] =& $content;
	}else{
		if( $ant === ':' ){
			if( $attr_name !== false ){
				$mats[$attr_name] = true;
			}
			$attr_name = $content; // nao funciona com =&
			if( !ereg('^[a-z_]+$',$attr_name) ){
				return false;
			}

		}elseif( $ant == '(' ){
			if( $attr_name === false ){
				return false;
			}
			$mats[$attr_name] =& $content;
			$attr_name = false;
		}elseif( $ant == ')' && strlen($content) ){
			return false;
		}
	}
	return true;
}

function decode_macro( &$text ){
	$parentesis_count = 0;
	$attr_name = false;
	$matches = array();
	$len = strlen($text);
	$last = 0;
	$scape = false;
	$ant = false;
	for( $c=0; $c <$len; $c++ ){
		$ch = $text{$c};
		if( $scape ){
			$scape = false;
		}elseif( $ch == ':' && !$parentesis_count ){
			if( $c > 0 ){
				$r = mmacthed( $matches, $ant, $attr_name, substr($text, $last, $c-$last) );
				if( !$r )return false;
			}
			$ant = $ch;
			$last = $c+1;

		}elseif( $ch == '(' ){
			if( $parentesis_count ){
				$parentesis_count++;
				continue;
			}
			$parentesis_count++;
			if( $c > 0 ){
				$r = mmacthed( $matches, $ant, $attr_name, substr($text, $last, $c-$last) );
				if( !$r )return false;
			}
			$ant = $ch;
			$last = $c+1;

		}elseif( $ch == ')' ){
			$parentesis_count--;
			if( $parentesis_count ){
				continue;
			}
			if( $c > 0 ){
				$r = mmacthed( $matches, $ant, $attr_name, substr($text, $last, $c-$last) );
				if( !$r )return false;
			}
			$ant = $ch;
			$last = $c+1;

		}elseif( $ch == '\\' ){
			$scape = true;
		}
	}
	$ch = $text{$len-1};
	if( $ch == ':' || $ch == '(' || $parentesis_count ){
		return false;
	}
	if( $ch != ')' ){
		if( $attr_name !== false ){
			$matches[$attr_name] = true;
		}
		if( $ant === false ){
			$matches['name'] = $text;
		}else{
			$matches[substr($text, $last, $c-$last )] = true;
		}
	}
	return $matches;
}

function decode( &$a ){
	$r = array();
	foreach( $a as $c => $v ){
		if( is_numeric($c) && is_array($a[$c]) ){
			$r[$c] = decode($a[$c],$vals);
			continue;
		}else if( is_numeric($c) ){
			$c = $v;
			unset( $v );
		}
		$attrs = decode_macro($c);
		if( $attrs === false ){
			trigger_error('Ninguém consegue enteder o que você quis dizer com: "'.htmlspecialchars($c).'"',E_USER_ERROR);
		}
		$attrs['!'] = true;
		if( is_array($a[$c]) && !isset($attrs['serialize']) ){ // exeções ao decode recurso para arrays
			$attrs['content'] =& decode($a[$c],$vals);
		}else{
			$attrs['content'] =& $a[$c];
		}

		// muita coisa poderia ser feita aqui


		$r[] = $attrs;
	}
	return $r;
}

