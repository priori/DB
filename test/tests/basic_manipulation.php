<?php

$db->query("TRUNCATE $db->pessoa");
$db->pessoa[] = array('nome'=>'super');
$db->pessoa[] = array('nome'=>'Leonardo Priori');
$db->pessoa->add( array('nome'=>'Filipe') );
$db->pessoa[] = array('nome'=>'Rafael');
$db->pessoa->replace( array('nome'=>'Homem Aranha') );
$db->pessoa[] = array('nome'=>'Dois');
$r = $db->query('SELECT * FROM pessoa');
test( count($r) == 6, 'inserir 6 valores (replace, simple insert, array like)');


$nomes = array('super','Leonardo Priori','Filipe','Rafael','Homem Aranha','Dois');
$c = 0;
$b = true;
foreach( $r as $v ){
	if( $v['nome'] != $nomes[$c] ){
		$b = false;
	}
	$c++;
}
test( $b, 'valores conferem' );
