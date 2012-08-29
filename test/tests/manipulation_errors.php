<?php

$db->error_mode = DB::THROW_ERROR;

$e = false;
$msg = "Seting using array as id:";
try{
	$db->pessoa->set(array("id"=>5),array("nome"=>"ajskdf"));
}catch(Exception $err){ $e = true; $msg = $msg." \n".$err->getMessage(); }
test( $e, $msg );


$e = false; $e2 = false;
$msg = "Values without string keys:";
try{
	$db->pessoa->set("id",array("ajskdf"));
}catch(Exception $err){ $e = true; $msg = $msg." \n".$err->getMessage(); }
try{
	$db->pessoa->add(array("ajskdf"));
}catch(Exception $err){ $e2 = true; $msg = $msg." \n".$err->getMessage(); }
test( $e, "False result in test unit! ".$msg );
