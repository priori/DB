<?php

class Decoder{

	static function mmacthed( &$mats, &$ant, &$attr_name, $content ){
		if( $ant === false ){
			$mats[] =& $content;
		}else{
			if( $ant === ':' ){
				if( $attr_name !== false ){
					$mats[$attr_name] = true;
				}
				$attr_name = $content; // nao funciona com =&
				if( !preg_match('/^[a-z_]+$/',$attr_name) ){
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

	static function decode_string( $text )
	{
		$parentesis_count = 0;
		$attr_name = false;
		$matches = array();
		$len = strlen($text);
		$last = 0;
		$ant = false;

		$ms;
		preg_match_all( "/[:)(]/",$text,$ms,PREG_OFFSET_CAPTURE); 
		$ms = $ms[0];
		foreach( $ms as $m ){
			$ch = $m[0];
			$c = $m[1];
			if( $ch == ':' && !$parentesis_count ){
				if( $c > 0 ){
					$r = Decoder::mmacthed($matches, $ant, $attr_name, 
							substr($text, $last, $c-$last));
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
					$r = Decoder::mmacthed($matches, $ant, $attr_name, 
							substr($text, $last, $c-$last));
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
					$r = Decoder::mmacthed($matches, $ant, $attr_name, 
							substr($text, $last, $c-$last));
					if( !$r )return false;
				}
				$ant = $ch;
				$last = $c+1;

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
				$matches[] = $text;
			}else{
				$matches[substr($text, $last/*, $c-$last*/ )] = true;
			}
		}
		return $matches;

	}

	// 1 = attr value in some macro
	const ATTR_VALUE_ERROR = 1;
	const KEY_VALYE_ERROR = 2;
	static function decode_array( $a ){
		$count = 0;
		$r = array();
		foreach( $a as $c => $v ){
			$aux;
			if( is_string( $c ) ){
				$aux = Decoder::decode_string( $c );
				if( isset($aux['value']) ){
					return 1;
				}
				$aux['content'] = $v;

			}elseif( $c === $count && is_string($v) ){
				$count++;
				$aux = Decoder::decode_string( $v );
			}else{
				return 2;
			}
			if( isset($aux[0]) ){
				$r[$aux[0]] =& $aux;
				unset( $aux[0] );
			}else{
				$r[] =& $aux;
			}
		}    
		return $r;   
	}
}
