<?php


$db->query("TRUNCATE $db->pessoa");
$db->pessoa[] = array('nome:sql'=>'"LEONARDO"');
$db->pessoa[] = array('nome:sql'=>'1+1');
$db->pessoa->add( array('nome:sql(10+?)'=>10) );
$db->pessoa[] = array('nome:sql(?+?)'=>array(100,100));
$r = $db->query('SELECT * FROM pessoa');

$nomes = array('LEONARDO',2,20,200);
$c = 0;
$b = true;
foreach( $r as $v ){
	if( $v['nome'] != $nomes[$c] ){
		$b = false;
	}
	$c++;
}
test( $b, 'valores conferem' );

// argumentos invalidos
