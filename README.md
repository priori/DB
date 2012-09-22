DB 
=============

Esta � uma biblioteca para manuseio de Banco de Dados.

Nela n�o � nescess�rio criar classes para refletir tabelas, nem seguir padr�es de organiza��o de arquivos e pastas. A biblioteca busca ser o menos burocr�tica poss�vel.

Suas vantagens s�o vis�veis mesmo antes de se especificar os modelos.
Apesar de recomendado que se use ela junto a alguma framework, ela pode facilmente ser utilizada fora de uma camada de modelo bem definida.  

Desenvolvida com TDD e valorizando sempre a performance.

Sem erros gen�ricos do tipo "Argumento inv�lido". Todos os argumentos s�o exastivamentes checados.
As mensagens de erro s�o grande preocupa��o. Elas devem ser claras, espec�ficas. 
Ex: Macro :now n�o deve ser usada com valor. Ou: Modelo com chave m�ltipla, utilize o m�todo add caso queira inserir valores.

Pequena quantidade de m�todos diminuindo a curva de aprendizado e facilitando a compree��o de seu escopo por completo.
Junto com �ndices de array � utilizado macros agregando de forma simples poder ao seu c�digo.

	$db->pessoa[5] = array(
	  'code:format([a-Za-z]+)' => 'adssdfasdfqerf',
	  'ultima_visita:date(dd/mm/yyyy)' => '10/05/2013'
	);

Exemplos
-------

	<?php
	$db = new DB; // padr�o � mysql, login root e sem senha
	$db->pessoa[] = array(
	  'primeiro_nome' => 'Leonardo',
	  'sobrenome' => 'Silva'
	);
	unset( $db->pessoa[1] );
	$db->pessoa[2] = array(
	  'sobrenome' => 'Outro',
	  'idade:sql( ? + ? )' => array( 10, 5 )
	);
	$db->pessoa[] = array(
	  'nome:text(1-100)' => 'Leonardo',
	  'idade:int'        => 5
	);
	$db->pessoa->set_model(
	  'nome:text(1-100)',
	  'idade:int',
	  'data:created_at',
	  'ultima_modificacao:updated_at',
	   // fk(pessoa_id) � o padr�o, ent�o na verdade n�o seria necess�rio especificar
	  'tel:many(telefone):fk(pessoa_id)', 
	  'chave1:pk', // chaves multiplas
	  'chave2:pk
	);
	$r = $db->pessoa->set(array(5,10), array( // chave multipla
	  'nome' => 'Lasdhflajksdf'
	));
	if( !$r ){
	  $err = $db->errors();
	  if( $modo1 ){
	    echo $err;
	    // <ul><li>O campo assim assim deve ser assim assim!</li></ul>
	  }else{
	    foreach( $err->campo1 as $e ){
	      if( $e->type === '...' )
	         echo $e;
	    }
	  }
	}
	$db->pessoa[] = array(
	  'nome' => 'ASDFasdf',
	  'tel' => array(
	    array('numero'=>187903,'ddd'=>12),
	    array('numero'=>184551,'ddd'=>12),
	    array('numero'=>871111,'ddd'=>13)
	  )
	);
	$r = $db->pessoa->find(array('id:gt' => 10, 'OR', 'id:lt' => 5));
	foreach( $r as $p ){
	  // $p � um array
	}
	$db->pessoa->save(array(
	  'id' => 5, // como id foi especificado a entrada ir� ser atualizada
	  'name' => 'Novo Nome'
	));
	// processar post automaticamente
	$db->pessoa->post('nome','remove_pessoa:remove','idade');
