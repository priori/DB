DB Priori
=============

DB Priori é uma biblioteca para manuseio de Banco de Dados (por enquanto só MySQL). O desenvolvimento é fortemente ligado a testes de performance. Não é nescessário criar classes para refletir tabelas, nem seguir um padrão para arquivos e pastas. Por isto penso que a ideologia dela é ser menos burocrática possível.

Por enquanto algumas funcionalidades não estam prontas (set_model e algumas chegagens de erros). Mas dentro uma ou duas semanas pretendo colocar a primeira versão disponivel aqui.

O Básico
-------

Conectando
-------

	<?php
	$db = new DB();
	// valores padrão são
	// 'localhost', 'root' e ''
	$db->select_db('test');
	Queries
	<?php
	$db->query('
	  SELECT * FROM pessoa
	  WHERE nome = ?
	', $_POST['nome'] );

Atualizando
-------

	<?php
	$db->pessoa->set( $id, array(
	  'nome' => 'Minha Casa',
	  'data_de_nacimento' => '1984-07-08',
	  'casa_id' => 5
	));

Inserindo Dados
-------

	<?php
	$db->pessoa->add(array(
	  'nome' => 'Minha Casa',
	  'data_de_nacimento' => '1984-07-08',
	  'casa_id' => 5
	));

Removendo
-------

	<?php
	$db->pessoa->remove( $id );

Resgatando
-------

	<?php
	$db->pessoa->get( $id );

WHERE
-------

	<?php
	$db->pessoa->set( array(
		 'id' => $id,
		 'pontos' => 35
	  ), array(
		 'nome' => 'Minha Casa'
	));
	$db->pessoa->remove(array( 'pessId' => $id ));
	$db->pessoa->get(array( 
	  'pessoaId' => $id,
	  'OR',
	  'pessoaId' => $id2
	));

Macros
-------

	<?php
	$db->pessoa->add(array(
	  'nome:text(1-255)' => 'Minha Casa',
	  'data_de_nacimento:date(dd/mm/yyyy)' => 
			'08/07/1984',
	  'casa_id:sql( ? + 1 )' => 5,
	  'asdfadf:sql' => 
		 'SELECT MAX(data_de_nacimento) ... '
	));

Erros
-------

	<?php
	$r = $db->pessoa->add(...);
	if( !$r ){
		echo "Erros no nome: ";
		foreach( $db->errors('nome') as $v )
			echo htmlspecialchars( $v );
		echo 'Errors em geral!';
		foreach( $db->errors() as $v )
			echo htmlspecialchars( $v );
	}

toString
-------

	<?php
	echo $db->errors();
	// <ul>
	//   <li>erro 1</li> 
	//   <li>erro 2</li>
	// </ul>
	echo $db->query('SELECT ...');
	// <table><thead><th>coluna 1 ...

Modo Array
-------

	<?php
	$db->pessoa[] = array( 'nome' => 'Leonardo' );
	$db->pessoa[ 5 ] = array( 'nome' => "Filipe" );
	$eu = $db->pessoa[ 5 ];
	unset($db->pessoa[3]);

has many
-------

	<?php
	$r = $db->pessoa->add(array(
		'nome' => 'Leo',
		'telefone' => array(
			array('numero' => 165165),
			array('numero' => 48945132)
		)
	));
	// utiliza transactions
	// telefone:many ou :has_many(telefone)
	// para setar chave estrangeira :fk(col)
	// utiliza transactions
	// casa:one ou :belongs_to(casa)
	// para setar chave estrangeira :fk(col)

Listando
-------

	<?php
	$r = $db->pessoa->find(array(
		'find_by' => 'nome',
		'find' => $_GET['q'],
		'page' => $_GET['page']
	));
	foreach( $r as $p ){
		echo $p['nome'].' '.$p['idade'];
		echo '<br/>';
	}
	if( $r->has_next_page() )
		echo '...';
	if( $r->has_previous_page() )
		echo '...';
	foreach( $db->query('SELECT ...' as $v ){
	  echo $v['aasdf'];
	  echo '<br/>';
	}
	$r = $db->pessoa->find(array(
		'fields' => array('nome','id','idade'),
		'where' => array( 'idade:gt' => 10 )
	));

comportamento padrão dos campos e das tabelas
-------

	<?php
	$r = $db->set_model('pessoa',array(
		'nome:text(1,255)',
		'telefone:many',
		'data_de_nacimento:text(dd/mm/yyyy)'
	));
