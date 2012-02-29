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
h2{ margin: 0 }
</style>
</head>
<body>
<table>
<?php

include "tests/connecting.php";
// $db->echo_queries = true;

echo "<h2>Basic Manipulation</h2>";
include "tests/basic_manipulation.php";

echo "<h2>Testing Errors</h2>";
include "tests/simple_errors.php";

echo "<h2>Manipulation Errors</h2>";
include "tests/manipulation_errors.php";

echo "<h2>Date Format</h2>";
include "tests/date_format.php";

echo "<h2>Array Like</h2>";
include "tests/array_like.php";
// inserir sql modo 1, modo 2, modo 3

?>
</td></table>
</body>
</html>

