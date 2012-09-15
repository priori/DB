<?php


include 'include.php';

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
			idade INTEGER UNSIGNED NULL,
			nome TINYTEXT NOT NULL
		)');
		$this->db->query('CREATE TABLE pessoa_pk (
			pk INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
			nome TINYTEXT NOT NULL
		)');
	}


	// basico
	public function test001(){
		$db = $this->db;
		$db->pessoa->truncate();
		$this->assertTrue( !!$db->pessoa );
		$this->assertTrue( is_object($db->pessoa) );
		$this->assertTrue( is_object($db->asdfasdfasdf) );
		$this->assertTrue( is_object($db->asdfasdfasdf) );
		$this->assertEquals( $db->asdfasdfasdf, '`asdfasdfasdf`' );
		$err = false;
		try{
			$db->asdfasdf->add(array('a' => 'b'));
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
		$err = false;
		try{
			$db->pessoa->add(array('nome' => 'b'));
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( !$err and count($db->pessoa->all()) == 1 );
	}

	public function test002(){
		$db = $this->db;
		$db->pessoa->truncate();
		$db->pessoa[] = array( 'nome' => 'Leo','idade'=>'18');
		$r = $db->pessoa->add(array( 'nome' => 'Filipe'));
		$this->assertTrue( $r );
		$db->pessoa[] = array( 'nome' => 'Rafael');
		$this->assertEquals( 3, count($ps = $db->pessoa->all()) );
		// o tipo é perdido pelo socket
		// ele retorna tudo como string
		$this->assertEquals( array(
			'idade'=>18,
			'nome'=>'Leo',
			'id'=>1
		), $ps->fetch() );
		$this->assertEquals( array(
			'idade'=>null,
			'nome'=>'Filipe',
			'id'=>2
		), $ps->fetch() );
		$this->assertEquals( array(
			'idade'=>null,
			'nome'=>'Rafael',
			'id'=>3
		), $db->pessoa[3] );
	}

	// set (pk)
	public function test003(){
		$db = $this->db;
		$db->pessoa->truncate();
		$db->pessoa_pk->truncate();
		$db->pessoa[] = array( 'nome' => 'Leo','idade'=>'18');
		$db->pessoa[1] = array(
			'nome' => 'LEO'
		);
		$this->assertEquals(array(
			'id' => 1, 
			'nome' => 'LEO',
			'idade' => 18
		),$db->pessoa[1]);
		$db->pessoa_pk[] = array('nome'=>'Leo PK');
		$err = false;
		try{
			$db->pessoa_pk[1] = array('nome'=>'Leo PK modificado');
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
		$this->assertEquals( 'id', $db->pessoa_pk->pk() );
		// não é hora de checar get
		$ps = $db->pessoa_pk->all();
		$p = $ps->fetch();
		$this->assertEquals( array('pk'=>1,'nome'=>'Leo PK'), $p );
		$err = false;
		$db->pessoa_pk->pk = 'pk';
		try{
			$db->pessoa_pk[1] = array('nome'=>'Leo PK modificado');
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertFalse( $err );
		$this->assertEquals( array('pk'=>1,'nome'=>'Leo PK modificado'), 
				$db->pessoa_pk[1] );
		$this->assertEquals( 'pk', $db->pessoa_pk->pk() );
		// invalido tipo para id
		$err = false;
		try{
			$db->pessoa->set( array(0,1), array('nome'=>'Leo modificado') );
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
		$err = false;
		try{
			$db->pessoa->set( $db, array('nome'=>'Leo modificado') );
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
		$err = false;
		try{
			$db->pessoa->set( $db->link(), array('nome'=>'Leo modificado') );
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
	}

}
