<?php


include 'include.php';

function a(){
	$GLOBALS['last'] = func_get_args();
}

set_error_handler('a');

class Test extends PHPUnit_Framework_TestCase {
	protected function setUp() {
		$this->db = new DB;
		$this->db->error_mode = DB::THROW_ERROR;
		$this->criaBase();
	}

	protected function criaBase(){
		$this->db->query('DROP DATABASE IF EXISTS test_db_lib');
		$this->db->query('CREATE DATABASE test_db_lib');
		$this->db->select_db('test_db_lib'); // use
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
				'port' => 165
			));
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);
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
}

