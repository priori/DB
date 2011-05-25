<?php 

include dirname(__FILE__).'/lib/db.php';

$db = new DB();
$db->select_db('test');

$db->query('TRUNCATE TABLE pessoa');


$db->pessoa->add(array(
	'nome' => 'Leonardo'
));         
$db->pessoa->add(array(
	'nome' => 'Felipe'
));          
$db->pessoa->add(array(
	'nome' => 'Rafael'
));           
$db->pessoa->add(array(
    'error' => '!'
));
$db->pessoa->add(array(
	'nome' => 'Thiago'
));


foreach( $db->fetchAll('SELECT * FROM pessoa') as $a ){
	echo '<strong>';
	echo htmlspecialchars($a['id']);
	echo '</strong> '; 
	echo htmlspecialchars($a['nome']);
	echo '<br/>';
	echo '</hr>';
}
