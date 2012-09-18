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
		$this->db->query('CREATE TABLE pessoa_pk2 (
			id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
			id2 INTEGER UNSIGNED NOT NULL,
			nome TINYTEXT NOT NULL,
			PRIMARY KEY (id, id2)
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
		$this->assertTrue( !$err and count($db->pessoa) == 1 );
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

	// primeiro sem trabalhar com macros, modelos, relação has_many
	// somente pk multipla e operações básicas

	// set (pk)
	// para chaves multiplas por enquanto array obrigatório
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
		// valor inválido
		$err = false;
		try{
			$db->pessoa_pk[1] = 'aasdf';
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
		$err = false;
		try{
			$db->pessoa_pk[1] = true;
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
		$err = false;
		try{
			$db->pessoa_pk[1] = (object)array('nome'=>'A');
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );

		// chave multipla
		$db->pessoa_pk2->pk = array('id','id2');
		$db->pessoa_pk2[] = array('id2'=>3,'nome'=>'Nome');
		$db->pessoa_pk2->set(array(1,3),array('nome'=>'NOME'));
		$p = $db->pessoa_pk2->get_where(array('id'=>1,'id2'=>3));
		$this->assertEquals(array('id'=>1,'id2'=>3,'nome'=>'NOME'),$p);

		$db->pessoa_pk2->set(array(3,'id'=>1),array('nome'=>'1'));
		$p = $db->pessoa_pk2->get_where(array('id'=>1,'id2'=>3));
		$this->assertEquals(array('id'=>1,'id2'=>3,'nome'=>'1'),$p);

		$db->pessoa_pk2->set(array('id'=>1,3),array('nome'=>'2'));
		$p = $db->pessoa_pk2->get_where(array('id'=>1,'id2'=>3));
		$this->assertEquals(array('id'=>1,'id2'=>3,'nome'=>'2'),$p);

		$db->pessoa_pk2->set(array('id2'=>3,1),array('nome'=>'3'));
		$p = $db->pessoa_pk2->get_where(array('id'=>1,'id2'=>3));
		$this->assertEquals(array('id'=>1,'id2'=>3,'nome'=>'3'),$p);

		$db->pessoa_pk2->set(array(1,'id2'=>3),array('nome'=>'4'));
		$p = $db->pessoa_pk2->get_where(array('id'=>1,'id2'=>3));
		$this->assertEquals(array('id'=>1,'id2'=>3,'nome'=>'4'),$p);

		$db->pessoa_pk2->set(array('id'=>1,'id2'=>3),array('nome'=>'5'));
		$p = $db->pessoa_pk2->get_where(array('id'=>1,'id2'=>3));
		$this->assertEquals(array('id'=>1,'id2'=>3,'nome'=>'5'),$p);

		$db->pessoa_pk2->set(array('id2'=>3,'id'=>1),array('nome'=>'6'));
		$p = $db->pessoa_pk2->get_where(array('id'=>1,'id2'=>3));
		$this->assertEquals(array('id'=>1,'id2'=>3,'nome'=>'6'),$p);

		$err = false;
		try{
			$db->pessoa_pk2->set(array('id3'=>3,'id'=>1),array('nome'=>'6'));
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);

		$err = false;
		try{
			$db->pessoa_pk2->set(array(3,'ida'=>1),array('nome'=>'6'));
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);

		$err = false;
		try{
			$db->pessoa_pk2->set(array(3,'id'=>1,4),array('nome'=>'6'));
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);

		$err = false;
		try{
			$db->pessoa_pk2->set(array(3,1,4),array('nome'=>'6'));
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);
	}


	// add
	public function test004(){
		$db = $this->db;
		$p = $db->pessoa;
		$p->truncate();

		$p[] = array('nome'=>'Leo');
		$this->assertEquals(1,count($p));
		$p[] = array('id'=>5,'nome'=>'A');
		$this->assertEquals(2,count($p));
		$this->assertEquals(array('id'=>5,'nome'=>'A','idade'=>null),
				$p[5]);
		$err = false;
		try{
			$p[] = array('a'=>'b');
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);

		$err = false;
		try{
			$p[] = 'a';
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);

		$err = false;
		try{
			$p[] = (object)array('nome'=>'Leo');
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);

		$db->pessoa_pk2->truncate();
		$db->pessoa_pk2[] = array('id2'=>1,'nome'=>'a');
		$p = $db->pessoa_pk2->get_where(array('id2'=>1,'id'=>1));
		$this->assertEquals(array('id2'=>1,'id'=>1,'nome'=>'a'),$p);
	}

	// get
	// array opcional
	public function test005(){
		$pk2 = $this->db->pessoa_pk2;
		$p = $this->db->pessoa;
		$pk2->truncate();
		$p->truncate();
		$pk2->pk = array('id','id2');

		$p[] = array('nome'=>'Leo','id'=>55);
		$this->assertEquals(array('nome'=>'Leo','id'=>55,'idade'=>null),
				$p[55]);

		$pk2[] = array('nome'=>'Leo','id2'=>55);
		$pk2[] = array('nome'=>'B','id'=>55,'id2'=>2);
		$pk2[] = array('nome'=>'C','id2'=>1);
		$pk2[] = array('nome'=>'D','id'=>3,'id2'=>1);
		$pk2[] = array('nome'=>'E','id'=>1,'id2'=>3);
		$this->assertEquals(array('nome'=>'Leo','id2'=>55,'id'=>1),
				$pk2->get(1,55));
		$this->assertEquals(array('nome'=>'B','id'=>55,'id2'=>2),
				$pk2->get(55,2));
		$this->assertEquals(array('nome'=>'C','id'=>56,'id2'=>1),
				$pk2->get(56,1));

		$this->assertEquals(array('nome'=>'D','id'=>3,'id2'=>1),
				$pk2->get(3,1));
		$this->assertEquals(array('nome'=>'E','id'=>1,'id2'=>3),
				$pk2->get(1,3));

		$this->assertEquals(array('nome'=>'D','id'=>3,'id2'=>1),
				$pk2->get(array(3,1)));
		$this->assertEquals(array('nome'=>'E','id'=>1,'id2'=>3),
				$pk2->get(array(1,3)));

		$this->assertEquals(array('nome'=>'D','id'=>3,'id2'=>1),
				$pk2->get(array(1,'id'=>3)));
		$this->assertEquals(array('nome'=>'E','id'=>1,'id2'=>3),
				$pk2->get(array(3,'id'=>1)));

		$this->assertEquals(array('nome'=>'D','id'=>3,'id2'=>1),
				$pk2->get(array('id2'=>1,3)));
		$this->assertEquals(array('nome'=>'E','id'=>1,'id2'=>3),
				$pk2->get(array(1,'id2'=>3)));

		$err = false;
		try{
			$pk2->get(1,3,4);
		}catch(Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);

		$err = false;
		try{
			$pk2->get(1);
		}catch(Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);

		$err = false;
		try{
			$p->get(array(1,2));
		}catch(Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);

		$this->assertEquals($p[1],$p->get(array(1)));

		$err = false;
		try{
			$p->get((object)array(1,2));
		}catch(Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
	}

	// remove
	public function test006(){
		$pk2 = $this->db->pessoa_pk2;
		$p = $this->db->pessoa;
		$pk2->truncate();
		$p->truncate();
		$pk2->pk = array('id','id2');
		$pk2[] = array('nome'=>'Leo','id2'=>55);
		$pk2[] = array('nome'=>'B','id'=>55,'id2'=>2);
		$pk2[] = array('nome'=>'C','id2'=>1);
		$pk2[] = array('nome'=>'D','id'=>3,'id2'=>1);
		$pk2[] = array('nome'=>'E','id'=>1,'id2'=>3);
		$p[] = array('nome'=>'A');
		$p[] = array('nome'=>'B');
		$p[] = array('nome'=>'C','id'=>50);
		
		$this->assertEquals(5,count($pk2));
		$this->assertEquals(array('nome'=>'E','id'=>1,'id2'=>3),
				$pk2->get(1,3));
		$pk2->remove(1,3);
		$this->assertEquals(4,count($pk2));
		$this->assertEquals(null,$pk2->get(1,3));

		$pk2[] = array('nome'=>'E','id'=>1,'id2'=>3);
		$this->assertEquals(5,count($pk2));
		$this->assertEquals(array('nome'=>'E','id'=>1,'id2'=>3),
				$pk2->get(1,3));
		$pk2->remove(array(1,3));
		$this->assertEquals(4,count($pk2));
		$this->assertEquals(null,$pk2->get(1,3));

		$pk2[] = array('nome'=>'E','id'=>1,'id2'=>3);
		$this->assertEquals(5,count($pk2));
		$this->assertEquals(array('nome'=>'E','id'=>1,'id2'=>3),
				$pk2->get(1,3));
		$pk2->remove(array('id2'=>3,'id'=>1));
		$this->assertEquals(4,count($pk2));
		$this->assertEquals(null,$pk2->get(1,3));

		$pk2[] = array('nome'=>'E','id'=>1,'id2'=>3);
		$this->assertEquals(5,count($pk2));
		$this->assertEquals(array('nome'=>'E','id'=>1,'id2'=>3),
				$pk2->get(1,3));
		$pk2->remove(array(3,'id'=>1));
		$this->assertEquals(4,count($pk2));
		$this->assertEquals(null,$pk2->get(1,3));

		$err = false;
		try{
			$pk2->remove(array('id'=>5));
		}catch(Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );

		$this->assertEquals(3,count($p));
		$this->assertEquals(array('nome'=>'A','idade'=>null,
				'id'=>1),$p[1]);
		unset( $p[1] );
		$this->assertEquals(null,$p[1]);
		$this->assertEquals(2,count($p));

		$this->assertEquals(2,count($p));
		$this->assertEquals(array('nome'=>'C','idade'=>null,
				'id'=>50),$p[50]);
		unset( $p[50] );
		$this->assertEquals(null,$p[50]);
		$this->assertEquals(1,count($p));
	}


	// protected function macro( $m, $v, $v2 ){
	// 	$this->db->pessoa->truncate();
	// 	$this->db->pessoa[] = array(
	// 		'nome:'.$m => 'Leo'
	// 	);
	// }
	
	// macros
	// date
	public function test007(){
		$db = $this->db;
		$p = $db->pessoa;
		$p[] = array('nome:date' => 'Leo');
		$this->assertTrue( !!$db->errors() );

		$p[1] = array('nome:date(dd/mm/yyyy)' => '30/01/2012');
		$e = $p[1];
		$this->assertEquals('2012-01-30',$e['nome']);
		$this->assertTrue( !$db->errors() );

		$p->truncate();
		$p[] = array('nome:date(mm/dd/yyyy)' => '01/30/2012');
		$e = $p[1];
		$this->assertEquals('2012-01-30',$e['nome']);
		$this->assertTrue( !$db->errors() );

		$p[1] = array('nome:date(mm/dd/yyyy)' => '01/34/2012');
		$this->assertTrue( !!$db->errors() );

		$p->truncate();
		$err = false;
		try{
			$p[] = array('nome:date(asdfasdf)' => '01/34/2012');
		}catch( Exception $e ){
			$err = true;
		}
		$this->assertTrue( $err );
	}

	// integer, numeric
	public function test008(){
		$p = $this->db->pessoa;
		$p->truncate();

		$p[] = array('nome:int' => '100');
		$e = $p[1];
		$this->assertEquals(100,$e['nome']);

		$p[1] = array('nome:integer' => '10');
		$e = $p[1];
		$this->assertEquals(10,$e['nome']);

		$p[1] = array('nome:integer' => '20.0');
		$e = $p[1];
		$this->assertEquals(20,$e['nome']);

		$p[1] = array('nome:integer' => '20.1');
		$this->assertTrue( !!$this->db->errors() );

		$p[1] = array('nome:integer' => '10a');
		$this->assertTrue( !!$this->db->errors() );

		$p[1] = array('nome:int' => '10a');
		$this->assertTrue( !!$this->db->errors() );

		// numeric
		$p[1] = array('nome:numeric' => '15');
		$e = $p[1];
		$this->assertEquals(15,$e['nome']);

		$p[1] = array('nome:numeric' => '15.1');
		$e = $p[1];
		$this->assertEquals(15.1,$e['nome']);

		$p[1] = array('nome:num' => '15.1');
		$e = $p[1];
		$this->assertEquals(15.1,$e['nome']);

		$p[1] = array('nome:num' => '15.1a');
		$this->assertEquals(!$this->db->errors());

		$p[1] = array('nome:numeric' => 'a15.1');
		$this->assertEquals(!$this->db->errors());
	}

	// sql
	public function test009(){
		$p = $this->db->pessoa;

		$p[] = array('nome:sql' => '1+2' );
		$e = $p[1];
		$this->assertEquals('3',$e['nome']);

		$p[1] = array('nome:sql(1+?)' => '5' );
		$e = $p[1];
		$this->assertEquals('6',$e['nome']);

		$p[1] = array('nome:sql(?+?)' => array(5,5) );
		$e = $p[1];
		$this->assertEquals('10',$e['nome']);

		$err = false;
		try{
			$p[1] = array('nome:sql' => '-a-' );
			$err = true;
		}catch(Exception $e ){
			$err = true;
		}
		$this->assertTrue($err);
	}
}
