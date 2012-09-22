<?php


include 'include.php';

class Test extends PHPUnit_Framework_TestCase {

	protected function setUp() {
		$this->db = new DB(array(
			'dbname' => 'test_db_lib'
		));
		$this->db->error_mode = DB::THROW_ERROR;
		if( false ){
			$this->criaBase();
		}else{
			$this->db->query('USE test_db_lib');
			$this->db->pessoa->truncate();
		}
	}

	protected function criaBase(){
		$this->db->query('DROP DATABASE IF EXISTS test_db_lib');
		$this->db->query('CREATE DATABASE test_db_lib');
		// $this->db->select_db('test_db_lib');
		// $this->db->query('DROP TABLE pessoa');
		$this->db->query('USE test_db_lib');
		$this->db->query('CREATE TABLE pessoa (
			id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
			idade INTEGER UNSIGNED NULL,
			nome TINYTEXT NOT NULL
		)');
		// $this->db->query('CREATE TABLE pessoa_pk (
		// 	pk INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
		// 	nome TINYTEXT NOT NULL
		// )');
		// $this->db->query('CREATE TABLE pessoa_pk2 (
		// 	id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
		// 	id2 INTEGER UNSIGNED NOT NULL,
		// 	nome TINYTEXT NOT NULL,
		// 	PRIMARY KEY (id, id2)
		// )');
	}


	// basico
	public function test001(){
		$p = $this->db->pessoa;
		for( $c = 0; $c < 100; $c++ ){
			$p[] = array('nome' => 'Leo'.($c?' '.$c:''), 'idade' => 6+$c );
		}
		$this->assertEquals(100, count($p) );
		$p->remove(1);
		$this->assertEquals(99, count($p) );
		$p->remove_where(array('id'=>2));
		$this->assertEquals(98, count($p) );
		$p->remove_where(array('id:lt'=>4));
		$this->assertEquals(97, count($p) );
		$p->remove_where(array('id:gt'=>4));
		$this->assertEquals(1, count($p) );
		$p->truncate();
		for( $c = 0; $c < 100; $c++ ){
			$p[] = array('nome' => 'Leo'.($c?' '.$c:''), 'idade' => 6+$c );
		}
		$p->remove_where(array('id:ge'=>4));
		$this->assertEquals(3, count($p) );
		$p->truncate();
		for( $c = 0; $c < 100; $c++ ){
			$p[] = array('nome' => 'Leo'.($c?' '.$c:''), 'idade' => 6+$c );
		}
		$p->remove_where(array('id:le'=>4));
		$this->assertEquals(96, count($p) );

		$p->remove_where(array('id:ne'=>20));
		$this->assertEquals(95, count($p) );

		$p->truncate();
		for( $c = 0; $c < 100; $c++ ){
			$p[] = array('nome' => 'Leo'.($c?' '.$c:''), 'idade' => $c>=49?6+$c:null );
		}
		$p->remove_where(array('idade:null'));
		$this->assertEquals(51, count($p) );
		$p->truncate();
		for( $c = 0; $c < 100; $c++ ){
			$p[] = array('nome' => 'Leo'.($c?' '.$c:''), 'idade' => $c>=49?6+$c:null );
		}
		$p->remove_where(array('idade:not_null'));
		$this->assertEquals(49, count($p) );
		$p->truncate();
		for( $c = 0; $c < 100; $c++ ){
			$p[] = array('nome' => 'Leo'.($c?' '.$c:''), 'idade' => $c>=49?6+$c:null );
		}
		$p->remove_where(array('id:between'=>array(2,99)));
		$this->assertEquals(2, count($p) );
		$p->truncate();
		for( $c = 0; $c < 100; $c++ ){
			$p[] = array('nome' => 'Leo'.($c?' '.$c:''), 'idade' => $c>=49?6+$c:null );
		}
		$p->remove_where(array('id:in'=>array(1,2,3,4,5,6)));
		$this->assertEquals(100-6,count($p) );
	}

	public function test002(){
		$p = $this->db->pessoa;
		$p->truncate();
		for( $c = 0; $c < 100; $c++ ){
			$p[] = array('nome' => 'Leo'.($c?' '.$c:''), 'idade' => $c>=49?6+$c:null );
		}
		$p->remove_where(array(
			'id:in'=>array(1,2,3,4,5,6,7,8,9,10),
			'id:gt'=>2,
			'OR',
			'nome'=>'Leo' 
		) );
		$this->assertEquals(91,count($p));
	}
}

