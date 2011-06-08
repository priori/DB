<?php 

	class ErrorList{
		
		private $a = array();
		private $fields = array();
		
		public function ErrorList( $a=false, $b=false ){
			if( is_array($a) && is_array($b) ){
				$this->a = $a;
				$this->fields = $b;
			}
		}
		public function _add( $field, $type, $msg ){
			$se = new SingleError( $field, $type, $msg );
			$this->a[] = $se;
			if( !isset($this->fields[$field]) )$this->fields[$field] = array();
			$this->fields[$field][] = $se;
		}
		public function errors(){
			return $this->messages( func_get_args() );
		}
		public function messages(){
			$args = func_get_args();
			if( is_array($args[0]) )
				$args =& $args[0];
			if( !count($args) ){
				return $this;
			}
			if( count($args) == 1 ){
				return new ErrorList( $this->fields[$args[0]], array($args[0]=>$this->fields[$args[0]]) );
			}
			$a = array();
			$b = array();
			foreach( $args as $v ){
				if( isset($this->fields[$v]) ){
					foreach( $this->fields[$v] as $v2 ){
						$a[] = $v2;
					}
					$b[$v] = $this->fields[$v];
				}
			}
			return new ErrorList( $a, $b );
		}
		public function __toString(){
			$r = array('<ul>');
			if( isset($this->fields) )
			foreach( $this->fields as $v ){
				foreach( $v as $err ){
					$r[] = '<li>';
					$r[] = htmlspecialchars(''.$err);
					$r[] = '</li>';
				}
			}
			$r[] = '</ul>';
			return implode('',$r);
		}
	}
	
	
	
	class SingleError{
		private $msg;
		private $type;
		private $field;
		public function SingleError( $field, $type, $msg ){
			$this->field = $field;
			$this->type = $type;
			$this->msg = $msg;
		}
		public function __toString(){
			return $this->msg;
		}
		public function __get( $arg ){
			if( $arg == 'msg' )
				return $this->msg;
			if( $arg == 'type' ){
				return $this->type;
			}
			if( $arg == 'field' ){
				return $this->field;
			}
		}
	}