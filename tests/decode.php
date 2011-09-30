<?php


include 'include.php';

function decode( $string, $expected = array() ){
	match( 'decoding "'.htmlspecialchars($string).'"', 
		Decoder::decode_string($string),
		$expected );
}


?><html>
<head>
<title>Testing decoding</title>
<style>

table td{
	border: solid 2px #888; 
	padding: 3px;
}
table{border-collapse:collapse; }

</style>
</head>
<body>
<?

echo '<table>';
decode( "asdfasdf:asdfasdf",
	array ( 0 => 'asdfasdf', 'asdfasd' => true )) ;
decode( "word:asdfasdf(asdfasd(asdf)fasdf)",
	array ( 0 => 'word', 'asdfasdf' => 'asdfasd(asdf)fasdf' )) ;
decode( ":asdfasdf(data)",
	array ( 'asdfasdf' => 'data' ) );
decode( ":asdfasdf",
	array ( 'asdfasd' => true ) );
decode( "asdfasdf", array ( 0 => 'asdfasdf' ) ) ;
echo "</table>";

$ms;
preg_match_all( "/[:)(]/","asdfasdf:asdfasdf(asdfasd(asdf)fasdf)",$ms,PREG_OFFSET_CAPTURE);
 // var_dump($ms);
