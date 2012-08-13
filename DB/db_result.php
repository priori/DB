<?php 


class DB_Result implements Countable, Iterator{

	private $db_r;
	private $r;
	private $mode;
	public function DB_Result( &$r, &$db_r, $mode ){
		$this->mode = $mode;
		$this->db_r =& $db_r;
		$this->r =& $r;
	}
	public function num_rows(){
		if( $this->mode === DB::MYSQLI ){
			return $this->r->num_rows;
		}elseif( $this->mode === DB::MYSQL ){
			return mysql_num_rows($this->r);
		}elseif( $this->mode === DB::POSTGRESQL ){
			return pg_num_rows($this->r);
		}
	}
	public function fetch(){
		if( $this->mode === DB::MYSQLI ){
			return $this->r->fetch_assoc();
		}elseif( $this->mode === DB::MYSQL ){
			return mysql_fetch_assoc($this->r);
		}elseif( $this->mode === DB::POSTGRESQL ){
			return pg_fetch_assoc( $this->r );
		}
	}
	public function count(){
		return $this->num_rows();
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
	function rewind(){
		$this->data_seek(0);
		$this->current = $this->fetch();
	}
	function next() {
		$this->current = $this->fetch();
	}
	function current() {
		return $this->current;
	}
	function key() {
		return 0;
	}
	function valid() {
		return is_array($this->current);
	}
}

// ArrayAccess
// offsetExists
// offsetSet
// offsetGet
// offsetUnset



