<?php

if( is_int($b['content']) || is_float($b['content']) ){
	$q[] = $b['content'];
}elseif( isset($b['serialize']) ){
	$q[] = '\'';
	$q[] = $this->db->escape(serialize($b['content']));
	$q[] = '\'';
}elseif( isset($b['sql']) ){
	$q[] = $b['content'];
}else{
	$q[] = '\'';
	$q[] = $this->db->escape($b['content']);
	$q[] = '\'';
}

