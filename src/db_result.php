<?php 


class DB_Result implements arrayaccess, Countable, Iterator{

	private $db_r;
	private $r;
	private $mode;
	public function DB_Result( &$r, &$db_r, $mode ){
		$this->mode = $mode;
		$this->db_r =& $db_r;
		$this->r =& $r;
	}
	public function fetch(){
		if( $this->mode === DB::MYSQLI ){
			$r = $this->r->fetch_assoc();
		}elseif( $this->mode === DB::MYSQL ){
			$r = mysql_fetch_assoc($this->r);
		}elseif( $this->mode === DB::POSTGRESQL ){
			$r = pg_fetch_assoc( $this->r );
		}
		if( $r ){
			$this->count++;
		}else{
			$this->count = -1;
		}
		return $r;
	}
	public function count(){
		if( $this->mode === DB::MYSQLI ){
			return $this->r->num_rows;
		}elseif( $this->mode === DB::MYSQL ){
			return mysql_num_rows($this->r);
		}elseif( $this->mode === DB::POSTGRESQL ){
			return pg_num_rows($this->r);
		}
	}
	public function data_seek($c){
		if( $this->mode === DB::MYSQLI ){
			$this->r->data_seek($c);
		}elseif( $this->mode === DB::MYSQL ){
			mysql_data_seek($this->r,$c);
		}elseif( $this->mode === DB::POSTGRESQL ){
			pg_result_seek($this->r,$c);
		}
	}
	
	public function __toString(){
		$r = array();
		$b = false;
		$r[] = '<table>';
		foreach( $this as $v ){
			if( !$b ){
				$r[] = '<thead>';
				foreach( $v as $c => $val ){
					$r[] = '<th>';
					$r[] = htmlspecialchars( $c );
					$r[] = '</th>';
				}
				$r[] = '</thead><tbody>';
				$b = true;
			}
			$r[] = '<tr>';
			foreach( $v as $c => $val ){
				$r[] = '<td>';
				$r[] = htmlspecialchars( $val );
				$r[] = '</td>';
			}
			$r[] = '</tr>';
		}
		$r[] = '</tbody></table>';
		return implode('',$r);
	}
	
	private $current;
	private $count = 0;
	function rewind(){
		$this->data_seek(0);
		$this->count = 0;
		$this->current = $this->fetch();
	}
	function next() {
		$this->count++;
		$this->current = $this->fetch();
	}
	function current() {
		return $this->current;
	}
	function key() {
		return $this->count;
	}
	function valid() {
		return is_array($this->current);
	}
	public function offsetSet($k,$val){
		$this->fire_error('Não permite mudança.');
	}
	public function offsetUnset($k){
		$this->fire_error('Não permite mudança.');
	}
	public function offsetGet($k){
		if( $k.'' === ''.((int)$k) and $k >= 0 and $this->count() > $k ){
			$this->data_seek($k);
			$r = $this->fetch();
			$this->data_seek($this->count);
			return $r;
		}
		$this->fire_error('Não há valor para esta chave.');
	}
	public function offsetExists($k){
		if( $k.'' === ''.((int)$k) )
			return $k >= 0 and $this->count() > $k;
	}
}

// ArrayAccess
// offsetExists
// offsetSet
// offsetGet
// offsetUnset



