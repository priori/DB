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
$db->pessoa[] = array('nome'=>'super');

?>
</body>
</html>
