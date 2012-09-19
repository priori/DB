<?php
		// em caso de inserssões multiplas o bixo pega
		// entries tem os valores de cada nova entrada
		// names os nomes das colunas que serão utilizadas
		$entries = array(); // valores de cada nova entrada
		$entries[] = array();
		$names = array(); // nomes das col utilizadas

		// se houver relações has many ou for utilizada outro tipo
		// de funcionalidade que necessite de transações
		$need_transaction = false;

		// varias entradas, mas cada uma será inserida separadamente
		$fazer_por_partes = false; // uma insersao multipla

		$b = false; // precisa de virgula?
		$entries_count = 0;
		$has_more_entries = true;
		while( $has_more_entries ){
			if( !$multiple_insertions ){
				$has_more_entries = false;
				$entry =& $e;
			}else{
				// mais complicado
				// if default value
				// guarda default value
				// continue
				// else
				// set entry
			}

			foreach( $entry as $name => $attr ){

				if( !isset($attr['has_many']) ){ // is not has many
					if( isset($names[$name]) ){
						continue;
					}
					if( $b )
						$q[] = ', ';
					else
						$b = true;
					$q[] = $this->a;
					$q[] = $this->db->_escape($name);
					$q[] = $this->b;

					$entries[$entries_count][$name] = $attr; // tinha um & antes
					$names[$name] = true;

				}else{

				}
			}
			$entries_count++;
		}
		$q[] = ') VALUES ';

		if( !$multiple_insertions ){
			return $this->_values( $q, $entries, $names, $need_transaction,
					$default_values );
		}
		return $r0;

	private function _values( &$q, &$entries, &$names, &$need_transaction,
			&$default_values ){
		$b2 = false;
		foreach( $entries as $entry ){
			if( $b2 )
				$q[] = ', ';
			$q[] = '(';
			$b = false;
			foreach( $names as $name => $aux ){
				if( $b ){
					$q[] = ', ';
				}
				$attr;
				if( isset($entry[$name]) ){
					$attr =& $entry[$name];
					if( isset($attr['content']) ){ // ponteiro
						$v = $attr['content'];
					}else{
						$this->db->fire_error('Chave sem valor!');
						return;
					}
					$this->sql_value( $q, $v, $attr, $attr );
				}else{
					$q[] = 'DEFAULT';
				}
				$b = true;
			}
			$q[] = ')';
			$b2 = true;
		}
		return $this->db->_query( implode('',$q) );
	}

