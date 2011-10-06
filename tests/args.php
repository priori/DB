<?php 

include 'include.php'; 

function test( $b, $msg ){
	if( $b ){
		echo "<tr class=sucess><th>Sucess!</th><td>";
	}else{
		echo "<tr class=error><th>Error!</th><td>";
	}
	echo htmlentities($msg);
	echo '</td></tr>';
}
           

// primeiro teste
$db = new DB();
$db->select_db('test');
$db->query('TRUNCATE pessoa');
$db->echo_queries = true;    
function v( $a ){
	global $db;
   
var_dump($db->pessoa->build_args($a));    
}

?><html>
<head>
<title></title>
<style>
body{ font-family: Arial; font-size: 11px }
table{ border-collapse: collapse }
td, th{ border: solid 1px #aaa; padding: 2px 5px; text-align: left;
	font-size: 11px  }
.error{ color: #e22 }
</style>
</head>
<body>
<table><pre>
<?php

v( array('asdfasdf'=>'asldkfja','nome'=>'Super',
	array('nome'=>'Leonardo'),
	'x' => 'xv'
) );



?>
</table>
</body>
</html>
