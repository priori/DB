<?php


	include '../include.php';




$c = new Decoder();

echo '<pre>';
var_dump( Decoder::decode_string( "asdfasdf:asdfasdf" ) );
var_dump( Decoder::decode_string( "asdfasdf:asdfasdf(asdf\\)asd(asdf)fasdf)" ) );
var_dump( Decoder::decode_string( ":asdfasdf(data)" ) );
var_dump( Decoder::decode_string( ":asdfasdf" ) );
var_dump( Decoder::decode_string( "asdfasdf" ) );

