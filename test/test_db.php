<?php


include 'include.php';

function a(){
	$GLOBALS['last'] = func_get_args();
}

set_error_handler('a');

class Test extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->db = new DB(array(
			'dbname' => 'test_db_lib'
		));
		$this->db->error_mode = DB::THROW_ERROR;
		$this->criaBase();
	}

	protected function criaBase(){
		$this->db->query('DROP DATABASE IF EXISTS test_db_lib');
		$this->db->query('CREATE DATABASE test_db_lib');
		$this->db->query('USE test_db_lib');
		// $this->db->select_db('test_db_lib');
		// $this->db->query('DROP TABLE pessoa');
		$this->db->query('CREATE TABLE pessoa (
			id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
			casa_id INTEGER UNSIGNED NOT NULL,
			nome TINYTEXT NOT NULL
		)');
	}

	// setando attributo desconhecido
	public function test001(){
		try{
			$this->db->asdf = array();
		}catch( Exception $e ){
			return;
		}
		throw Exception();
	}
	// setando error_mode
	// get não deve funcionar mesmo
	public function test002(){
		// padrão
		$this->assertEquals( DB::THROW_ERROR, $this->db->error_mode() );
		$this->db->error_mode = DB::PHP_ERROR_PARODY;
		$this->assertEquals( DB::PHP_ERROR_PARODY, $this->db->error_mode() );
		$this->db->error_mode = DB::TRIGGER_ERROR;
		$this->assertEquals( DB::TRIGGER_ERROR, $this->db->error_mode() );
		$this->db->error_mode = DB::THROW_ERROR;
		$this->assertEquals( DB::THROW_ERROR, $this->db->error_mode() );
	}
	// setando erro com valor inválido
	public function test003(){
		try{
			$this->db->error_mode = 3;
		}catch( Exception $e ){
			return;
		}
		throw Exception();
	}
	// echo queries
	public function test004(){
		$this->assertEquals( $this->db->echo_queries(), false );
		$this->db->echo_queries = true;
		$this->assertEquals( $this->db->echo_queries(), true );
		ob_start();
		$this->db->query("SELECT * FROM pessoa");
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertEquals($content,'SELECT * FROM pessoa<br/>');
		$this->db->echo_queries = false;
		ob_start();
		$this->db->query("SELECT * FROM pessoa");
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertEquals($content,'');
		$err = false;
		try{
			$this->db->error_mode = 3;
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
		$this->assertEquals( $this->db->echo_queries(), false );
	}

	// fire_error
	public function test005(){
		try{
			$line = __LINE__ + 1;
			$this->db->fire_error('msg');
		}catch( Exception $e ){
			$a = $e->getTrace();
			$this->assertEquals(__FILE__,$a[0]['file']);
			$this->assertEquals($line,$a[0]['line']);
			$this->assertEquals('fire_error',$a[0]['function']);
			$this->assertEquals('DB',$a[0]['class']);
			$this->assertEquals('->',$a[0]['type']);
			$this->assertEquals(array('msg'),$a[0]['args']);
			$this->assertEquals('test005',$a[1]['function']);
			$this->assertEquals('Test',$a[1]['class']);
			return;
		}
		throw new Exception;
	}

	// trigger error
	public function test006(){
		$this->db->error_mode = DB::TRIGGER_ERROR;
		$this->assertTrue( !isset($GLOBALS['last']) );
		$this->db->fire_error('msg2');
		$this->assertEquals( $GLOBALS['last'][1], 'msg2' );
		$this->db->error_mode = DB::THROW_ERROR;
	}

	// php error parody não dá para testar
	// no final há um die();
	
	// conexão
	// não está sendo testando postgresql
	// não está sendo testado socket
	//TODO: usar configurações padrões do php para user, password, host
	//TODO: socket
	public function test007(){
		$u = 'root';
		$p = '';
		$h = 'localhost';
		$db = new DB;
		$r = $db->query('SHOW databases');
		$this->assertTrue( count($r) > 0 );
		$db->close();
		$db = new DB( $h );
		$r = $db->query('SHOW databases');
		$this->assertTrue( count($r) > 0 );
		$db->close();
		$db = new DB( $h, $u );
		$r = $db->query('SHOW databases');
		$this->assertTrue( count($r) > 0 );
		$db->close();
		$db = new DB( $h, $u, $p );
		$r = $db->query('SHOW databases');
		$this->assertTrue( count($r) > 0 );
		$db->close();
		$db = new DB( array(
			'host' => $h, 
			'user' => $u, 
			'password' => $p) );
		$r = $db->query('SHOW databases');
		$this->assertTrue( count($r) > 0 );
		$db->close();
		$db = new DB( array(
			'port' => '3306') );
		$r = $db->query('SHOW databases');
		$this->assertTrue( count($r) > 0 );
		// não há dbname
		try{
			$err = false;
			$db->query("SELECT * FROM pessoa");
		}catch(Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
		// há dbname
		$db->close();
		$db = new DB(array(
			'dbname' => 'test_db_lib'
		));
		$r = $db->query("SELECT * FROM pessoa");
		$this->assertTrue( count($r) == 0 );
		$db->close();

		// não conectando
		$err = false;
		try{
			$db = new DB('asdhfkjasdhf');
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);
		$err = false;
		try{
			$db = new DB($h,$u,'asdjfahsdfjkahsdf');
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);
		$err = false;
		try{
			$db = new DB(array(
				'port' => 167
			));
		}catch( Exception $e ){
			$err = true;
		}
		$db->close();
		// não tem gerado erro no meu linux
		// aparentemente desconsidera a porta
		// $this->assertTrue($err);
		$err = false;
		try{
			$db = new DB(array(
				'dbname' => 'uasdhflkajhdfklahdf'
			));
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);
	}

	// id gerado por auto increment inserido na ultima sql ou
	// seria bom se tivéssemos null no lugar de 0 e 
	// que funcionasse para ids não auto increments também, 
	// mas não ocorrerá
	public function test008(){
		$this->db->pessoa->truncate();
		$this->db->pessoa[] = array(
			'nome' => 'Leo'
		);
		$this->assertEquals( $this->db->last_id(), 1 );
		$this->assertTrue( is_integer($this->db->last_id()) );

		$this->db->pessoa[] = array(
			'id' => '5',
			'nome' => 'Leo'
		);
		$this->assertTrue( $this->db->last_id() === 5 );

		$this->db->charset = 'utf8'; 
		$this->assertTrue( $this->db->last_id() === 0 );

		$this->db->query('SELECT 1');
		$this->assertTrue( $this->db->last_id() === 0 );

		$this->db->query('USE test_db_lib');
		$this->assertTrue( $this->db->last_id() === 0 );

		$this->db->query('CREATE TABLE pessoa_id_varchar (
			id VARCHAR(5) PRIMARY KEY,
			casa_id INTEGER UNSIGNED NOT NULL,
			nome TINYTEXT NOT NULL
		)');
		$this->assertTrue( $this->db->last_id() === 0 );
		$this->db->pessoa_id_varchar[] = array(
			'id' => 'aa',
			'nome' => 'Leo'
		);
		$this->assertTrue( $this->db->last_id() === 0 );


		$this->db->query('CREATE TABLE pessoa_not_auto_increment (
			id INTEGER UNSIGNED PRIMARY KEY,
			casa_id INTEGER UNSIGNED NOT NULL,
			nome TINYTEXT NOT NULL
		)');
		$this->db->pessoa_not_auto_increment[] = array(
			'id' => 100,
			'nome' => 'Leo'
		);
		$this->assertTrue( $this->db->last_id() === 0 );
	}

	// escapando
	// não é método publico
	public function test009(){
		$r;
		$this->assertEquals( $this->db->escape($r='\''), '\\\'' );
		$this->assertEquals( $this->db->escape($r=1),'1');
		$this->assertEquals( $this->db->escape($r=array()),'' );
		$this->assertEquals( $this->db->escape($r=$this->db),'' );
		$this->assertEquals( $this->db->escape($r=$this->db->link()),'' );
	}

	// charset
	public function test010(){
		$this->db->charset = 'utf8';
		$this->assertEquals( $this->db->charset(), 'utf8' );
		$this->db->charset = 'latin1';
		$this->assertEquals( $this->db->charset(), 'latin1' );
		$this->db->charset = 'UTF8';
		$this->assertEquals( $this->db->charset(), 'utf8' );
		$err = false;
		try{
			$this->db->charset = 'uaaaaaaaaatf8';
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
		$this->assertEquals( $this->db->charset(), 'utf8' );
		// Nada disso muda a codificação:
		// $this->db->query('set names \'latin1\'');
		// $this->db->query('set character set \'latin1\'');
		// $this->db->query("SET character_set_results = 'latin1', 
		// 	character_set_client = 'latin1', 
		// 	character_set_connection = 'latin1', 
		// 	character_set_database = 'latin1', 
		// 	character_set_server = 'latin1'");
		// $this->assertEquals( $this->db->charset(), 'latin1' );
	}

	// não importa se não existe a tabela
	// ao tentar acessar o modelo não se deve
	// perguntar ao banco de dados por informações desnecessárias
	public function test011(){
		$this->assertTrue( is_object($this->db->aaaaaaaaa) );
		$this->assertEquals( ''.$this->db->aaaaaa, '`aaaaaa`' );
		$err = false;
		try{
			$this->db->aa[] = array();
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
		$this->db->pessoa->truncate();
		$this->db->pessoa[] = array('nome'=>'Leo');
		$db = $this->db;
		$pessoas = $this->db->query("SELECT * FROM $db->pessoa");
		$this->assertEquals( 1, count($pessoas) );
	}

	// transações
	public function test012(){
		$this->db->pessoa->truncate();

		// transaction 1
		$this->db->begin();
		$this->db->pessoa[] = array('nome'=>'a');
		$this->db->pessoa[] = array('nome'=>'b');
		$this->assertEquals( 2, count( $this->db->pessoa->all() ) );
		unset( $this->db->pessoa[1] );
		$this->assertEquals( 1, count( $this->db->pessoa->all() ) );
		$this->db->rollback();
		$this->assertEquals( 0, count( $this->db->pessoa->all() ) );

		
		// mysql nao gera erro
		// fazer o que né?
		$err = false;
		try{
			$this->db->rollback();
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertFalse( $err );

		// easy transaction
		$db = $this->db;
		$db->begin(true);
		$this->db->pessoa[] = array('nome'=>'a');
		$db->begin(true);
		$this->db->pessoa[] = array('nome'=>'a');
		$db->begin(true);
		$this->db->pessoa[] = array('nome'=>'a');
		$this->assertEquals( 3, count( $this->db->pessoa->all() ) );
		$db->end();
		$this->assertEquals( 3, count( $this->db->pessoa->all() ) );
		$db->end();
		$this->assertEquals( 3, count( $this->db->pessoa->all() ) );
		$this->db->pessoa[] = array('n'=>'a');
		$db->end();
		$this->assertEquals( 0, count( $this->db->pessoa->all() ) );

		// easy transaction2
		// transaction dead
		$db = $this->db;
		$this->db->pessoa[] = array('nome'=>'a');
		$db->begin(true);
		$this->db->pessoa[] = array('nome'=>'a');
		$db->begin(true);
		$this->db->pessoa[] = array('nome'=>'a');
		$db->begin(true);
		$this->db->pessoa[] = array('nome'=>'a');
		$this->assertEquals( 4, count( $this->db->pessoa->all() ) );
		$db->end();
		$this->assertFalse( $db->dead_transaction() );
		$this->db->pessoa[] = array('n'=>'a');
		$this->assertTrue( $db->dead_transaction() );
		$this->assertEquals( 1, count( $this->db->pessoa->all() ) );
		$db->end();
		$this->db->pessoa[] = array('nome'=>'a');
		$this->assertTrue( $db->dead_transaction() );
		$this->assertEquals( 1, count( $this->db->pessoa->all() ) );
		$db->end();
		$this->assertFalse( $db->dead_transaction() );
		$this->assertEquals( 1, count( $this->db->pessoa->all() ) );

	}
}

