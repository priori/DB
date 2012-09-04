<?php

include 'include.php';


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

	// queries
	function test001(){
		$db = $this->db;
		// simples use
		$r = $db->query('USE test_db_lib');
		$this->assertTrue( $r );
		$r = $db->query('INSERT INTO pessoa (nome) VALUES (?)','Leo');
		$this->assertTrue( $r );
		$id = $db->last_id();
		$this->assertEquals( 1, $id );
		$r = $db->query('SELECT * FROM pessoa WHERE id = ? AND nome = ?',1,'Leo');
		$id = $db->last_id();
		$this->assertEquals( 0, $id );
		$this->assertEquals( array('nome'=>'Leo','id'=>'1','casa_id'=>'0'), $r->fetch() );
		$r = $db->fetch('SELECT * FROM pessoa WHERE id = 1 AND nome = \'Leo\'' );
		$this->assertEquals( array('nome'=>'Leo','id'=>'1','casa_id'=>'0'), $r );
	}

	// inserindo um valor
	function test002() {
		$db = $this->db;
		$db->query('TRUNCATE pessoa');
		$r = $db->pessoa->add( array('nome' => 'Leo') );
		// retorno da insercao
		$this->assertTrue( $r );
      $r = $db->query('SELECT * FROM pessoa');
		// a tabela agora tem 2 valores
		$this->assertTrue( $r->fetch() and !$r->fetch() );
      $r = $db->query('SELECT * FROM pessoa');
		$this->assertCount(1,$r);
		$r = $r->fetch();
		// valores batem
		$this->assertEquals( array('nome'=>'Leo','id'=>'1','casa_id'=>'0'), $r );
	}
}

