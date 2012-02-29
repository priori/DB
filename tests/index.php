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
<title>DB Tests</title>
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

include "tests/connecting.php";
// $db->echo_queries = true;

echo "<h2>Basic Manipulation</h2>";
include "tests/basic_manipulation.php";

include "tests/date_format.php";

include "tests/array_like.php";
// inserir sql modo 1, modo 2, modo 3

?>
</td></table>
</body>
</html>

