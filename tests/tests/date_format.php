<?php


$db->pessoa->truncate();
// inserir data
$db->pessoa->add(array('nome:date(dd/mm/yyyy)'=>'01/10/2011'));
$p = $db->query('SELECT * FROM pessoa');
$p = $p->fetch();
test( $p['nome'] == '2011-10-01', 'Date format '.$p['nome']);

