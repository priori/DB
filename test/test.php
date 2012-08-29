<?php

include 'include.php';
$db = new DB();
$db->select_db('test');

echo '<pre>';
$db->query('SELECT * FROM pessoa');
$db->pessoa->get_where(array('id:in'=>array('asdf','10','50'),'or',
	'casa_id:in:sql'=>0));
