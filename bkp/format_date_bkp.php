<?php

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
			'd' => '([0-9][0-9]?)','m' => '([0-9][0-9]?)','yy' => '([0-9][0-9])'
		));
		$r;
		preg_match_all('/^'.$f.'$/',$d,$r);
		$r2;
		var_dump( $format );
		echo ', ';
		var_dump( $r2 );
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
