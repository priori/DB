<?php

include 'include.php';


class Test extends PHPUnit_Framework_TestCase {


	protected function setUp() {
		$this->db = new DB;
		$this->db->select_db('test');
		$this->db->error_mode = DB::THROW_ERROR;
	}

	protected function tearDown(){ }

	function test001() {
		$db = $this->db;
		$db->query('SELECT * FROM pessoa');
		$r = $db->pessoa->get_where(array('id:in'=>array('asdf','10','50'),'or',
			'casa_id:in:sql'=>0));

		$this->assertTrue( !!$r );
	}
}
 


