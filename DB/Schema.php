<?php


class Schema{

	private $db;
	private $n;
	private $a;
	private $m;
	public function __construct($db,$n,$a,$m){
		$this->db = $db;
		$this->n = $n;
		$this->a = $a;
		$this->m = $m;
	}



	private $models = array();
	public function __get($n){
		if( !isset($this->models[$n]) )
			$this->models[$n] = new Model($this->db,$n,$this->a,$this->m,$this->n);
		return $this->models[$n];
	}

}

