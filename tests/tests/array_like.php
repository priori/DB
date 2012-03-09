<?php

$db->pessoa->truncate();
$db->pessoa->add(array("nome" => "AA", "id" => 51));
test( isset($db->pessoa[51]), 'Inserting entry!' );
$db->pessoa[51] = array("nome" => "Super" );
$p = $db->pessoa[51]->fetch();
test( $p['nome'] == "Super", 'Value updated!' );
echo $db->query("SELECT * FROM $db->pessoa");
unset($db->pessoa[51]);
test( !isset($db->pessoa[51]), 'Dropping entry!' );


