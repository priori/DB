<?php

$db->error_mode = DB::THROW_ERROR;

$e = false;
$msg = "Setting invalid error mode:";
try{
	$db->error_mode = 900;
}catch(Exception $err){ $e = true; $msg = $msg." \n".$err->getMessage(); }
test( $e, $msg );

$e = false;
$msg = "Invalid query:";
try{
	$db->query("aklsdjf alsdkfj ");
}catch(Exception $err){ $e = true; $msg = $msg." \n".$err->getMessage(); }
test( $e, $msg );


$e = false;
$msg = "Wrong table name: ";
try{
	$db->asdfasdfasdf->add(array("asdf"=>"ajsdklfçj"));
}catch(Exception $err){ $e = true; $msg = $msg." \n".$err->getMessage(); }
test( $e, $msg );
