<?php 

include 'include.php'; 


function test( $b, $msg ){
	static $first = true;
	static $s;
	if( !$first ){
		echo '</td></tr>';
	}
	if( $b ){
		echo "<tr class=sucess><th>Sucess!</th><td>";
	}else{
		echo "<tr class=error><th>Error!</th><td>";
	}
	echo htmlentities($msg);
	echo "</td></tr><tr><td colspan=2>";
	// ob_start();
	$first = true;
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
.sucess{ background-color: #d4e4f8 }
</style>
</head>
<body>
<table>
<?php

// primeiro teste
$db = new DB();
$db->select_db('test');
test(true,'');
// $db->echo_queries = true;
$db->query("TRUNCATE $db->pessoa");
$db->pessoa[] = array('nome'=>'super');
$db->pessoa[] = array('nome'=>'Leonardo Priori');
$db->pessoa->add( array('nome'=>'Filipe') );
$db->pessoa[] = array('nome'=>'Rafael');
$db->pessoa->replace( array('nome'=>'Homem Aranha') );
$db->pessoa[] = array('nome'=>'Dois');
$r = $db->query('SELECT * FROM pessoa');
test( count($r) == 6, 'inserir 6 valores');
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

$db->pessoa->truncate();
// inserir data
$db->pessoa->add(array('nome:date(dd/mm/yyyy)'=>'01/10/2011'));
$p = $db->query('SELECT * FROM pessoa');
$p = $p->fetch();
test( $p['nome'] == '2011-10-01', 'Date format '.$p['nome']);


$db->pessoa->truncate();
$db->pessoa->add(array("nome" => "AA", "id" => 51));
test( isset($db->pessoa[51]), 'Inserting entry!' );
$db->pessoa[51] = array("nome" => "Super" );
$p = $db->pessoa[51]->fetch();
test( $p['nome'] == "Super", 'Value updated!' );
echo $db->query("SELECT * FROM $db->pessoa");
unset($db->pessoa[51]);
test( !isset($db->pessoa[51]), 'Dropping entry!' );

// inserir sql modo 1, modo 2, modo 3

?>
</td></table>
</body>
</html>

