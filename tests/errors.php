<?php 

include 'include.php'; 

?><html>
<head>
<title></title>
</head>
<body>
<?php

$db = new DB();
$db->select_db('test');
$db->query('TRUNCATE pessoa');


$db->pessoa[] = array('nome'=>'super');
$db->pessoa[] = array('nome'=>'Leonardo Priori');
$db->pessoa[] = array('nome'=>'Filipe');
$db->pessoa[] = array('nome'=>'Rafael');
$db->pessoa[] = array('nome'=>'Homem Aranha');
$db->pessoa[] = array('nome'=>'Dois');

var_dump( $r = $db->query('SELECT * FROM pessoa') );

echo $r;

?>
</body>
</html>
