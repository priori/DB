<?php


class Decoder{

	static function mmacthed( &$mats, &$ant, &$attr_name, $content ){
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




	static function decode_string( $text ){
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
					$r = Decoder::mmacthed($matches, $ant, $attr_name, substr($text, $last, $c-$last));
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
					$r = Decoder::mmacthed($matches, $ant, $attr_name, substr($text, $last, $c-$last));
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
					$r = Decoder::mmacthed($matches, $ant, $attr_name, substr($text, $last, $c-$last));
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

	static function decode(){
		echo "super";
	}


}

$c = new Decoder();

echo '<pre>';
var_dump( Decoder::decode_string( "asdfasdf:asdfasdf" ) );
var_dump( Decoder::decode_string( "asdfasdf:asdfasdf(asdf\\)asd(asdf)fasdf)" ) );
var_dump( Decoder::decode_string( ":asdfasdf(data)" ) );
var_dump( Decoder::decode_string( ":asdfasdf" ) );
var_dump( Decoder::decode_string( "asdfasdf" ) );



var_dump( preg_split( "/[:\\\\()]/" , "asdfasdf:asdfasdff(asdf\\)asd(asdf)fasdf)") );
