<?php

include '../include.php';       

function similar( $a, $b ){
	if( $a === $b ) return true;
	foreach( $a as $ch => $v )
		if( $v != $b[$v] )
			return false;

	foreach( $b as $ch => $v )
		if( $v != $a[$v] )
			return false;
	return true;
}

function match( $msg, $result, $espected ){

	if( !similar($result, $espected) ){    
		echo "<tr class=\"error\">";
		echo "<td>";
		echo htmlspecialchars($msg);
		echo "</td>";              
	    echo "<td>";    
		echo "error! ";
		echo "</td><td>";
		var_export( $result );
		echo "</td><td>";
	   var_export( $espected );
		echo "</td></tr>";
	}else{

		echo "<tr>";
		echo "<td>";
		echo htmlspecialchars($msg);
		echo "</td>";              
	    echo "<td>";    
		echo "sucess! ";
		echo "</td><td>";
		var_export( $result );
		echo "</td><td>";
		var_export( $espected );
		echo "</td></tr>";
	}
}
