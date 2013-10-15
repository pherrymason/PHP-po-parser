<?php


/**
*	Class to parse .po file and extract its strings.
*/
class PoParser
{
	protected $entries = array();
	protected $headers = array();


	/**
	*	Reads and parses strings of a .po file.
	*
	*	@return Array. List of entries found in .po file.
	*/
	public function read( $file_path )
	{
		if( empty($file_path) )
		{
			throw new Exception('PoParser: Input File not defined.');
		}
		elseif( file_exists($file_path)===false )
		{
			throw new Exception('PoParser: Input File does not exists: "' . htmlspecialchars($file_path) . '"' );
		}
		elseif( is_readable($file_path)===false )
		{
			throw new Exception('PoParser: File is not readable: "' . htmlspecialchars($file_path) . '"' );
		}


		$handle   = fopen( $file_path, 'r' );
		$headers  = array();
		$hash     = array();
		$fuzzy    = false;
		$tcomment = $ccomment = $reference = null;
		$entry    = array();
		$just_new_entry    = false;		// A new entry has been just inserted.
		$first_line        = true;
		$last_obsolete_key = null;	// Used to remember last key in a multiline obsolete entry.

		while( !feof($handle) )
		{
			$line = trim( fgets($handle) );

			if( $line==='' )
			{
				if( $just_new_entry )
				{
					// Two consecutive blank lines
					continue;
				}

				if( $first_line )
				{
					$first_line = false;
					if( self::is_header( $entry ) )
					{
						array_shift( $entry['msgstr'] );
						$headers = $entry['msgstr'];
					}
					else
					{
						$hash[] = $entry;
					}
				}
				else
				{
					// A new entry is found!
					$hash[] = $entry;
				}

				$entry  = array();
				$state  = null;
				$just_new_entry    = true;
				$last_obsolete_key = null;
				continue;
			}

			$just_new_entry = false;
			$split = preg_split( '/\s/ ', $line, 2 );
			$key   = $split[0];
			$data  = isset($split[1])? $split[1]:null;

			switch( $key )
			{
				// Flagged translation
				case '#,':
							$entry['fuzzy'] = in_array('fuzzy', preg_split('/,\s*/', $data) );
							$entry['flags'] = $data;
							break;

				// # Translator comments
				case '#':
							$entry['tcomment'] = !isset($entry['tcomment'])? array():$entry['tcomment'];
							$entry['tcomment'][] = $data;
							break;

				// #. Comments extracted from source code
				case '#.':
							$entry['ccomment'] = !isset($entry['ccomment'])? array():$entry['ccomment'];
							$entry['ccomment'][] = $data;
							break;

				// Reference
				case '#:':
							$entry['reference'][] = addslashes($data);
							break;

				// #| Previous untranslated string
				case '#|':
							// Start a new entry
							break;

				// #~ Old entry
				case '#~':
					$entry['obsolete'] = true;

					$tmpParts = explode(' ', $data);
					$tmpKey   = $tmpParts[0];

					if( $tmpKey!='msgid' && $tmpKey!='msgstr' )
					{
						$tmpKey = $last_obsolete_key;
						$str = $data;
					}
					else
					{
						$str = implode( ' ', array_slice($tmpParts,1) );
					}
	
					switch( $tmpKey )
					{
						case 'msgid':
									$entry['msgid'][] = $str;
									$last_obsolete_key  = $tmpKey;
									break;

						case 'msgstr':
									if( $str=="\"\"" )
									{
										$entry['msgstr'][] = trim( $str, '"' );
									}
									else
									{
										$entry['msgstr'][] = $str;
									}
									$last_obsolete_key   = $tmpKey;
									break;

						default:	break;
					}

					continue;
					break;

				// context
				case 'msgctxt' :
				// untranslated-string
				case 'msgid' :
				// untranslated-string-plural
				case 'msgid_plural' :
							$state = $key;
							$entry[$state][] = $data;
							break;
				// translated-string
				case 'msgstr' :
							$state = 'msgstr';
							$entry[$state][] = $data;
							break;

				default:
							if( strpos($key, 'msgstr[') !== false )
							{
								// translated-string-case-n
								$state = 'msgstr';
								$entry[$state][] = $data;
							}
							else
							{
								// continued lines
								switch( $state )
								{
									case 'msgctxt':
									case 'msgid':
									case 'msgid_plural':
										if( is_string($entry[$state]) )
										{
											// Convert it to array
											$entry[$state] = array( $entry[$state] );
										}
										$entry[$state][] = $line;
										break;

									case 'msgstr':
										// Special fix where msgid is ""
										if( $entry['msgid']=="\"\"" )
										{
											$entry['msgstr'][] = trim( $line, '"' );
										}
										else
										{
											$entry['msgstr'][] = $line;
										}
										break;

									default:
										throw new Exception('PoParser: Parse error! Unknown key "'.$key.'" on line '.$line);
								}
							}
							break;
			}
		}
		fclose($handle);

		// add final entry
		if( $state == 'msgstr' )
		{
			$hash[] = $entry;
		}


		// - Cleanup header data
		$this->headers = array();
		foreach( $headers AS $header )
		{
			$this->headers[] = "\"" . preg_replace( "/\\n/", "\\n", $this->clean( $header ) ) . "\"";
		}

		// - Cleanup data, 
		// - merge multiline entries
		// - Reindex hash for ksort
		$temp = $hash;
		$this->entries = array();
		foreach( $temp AS $entry )
		{
			foreach( $entry AS &$v )
			{
				$or = $v;
				$v = $this->clean( $v );
				if( $v === false )
				{
					// parse error
					throw new Exception('PoParser: Parse error! poparser::clean returned false on "'.htmlspecialchars($or).'"');
				}
			}

			if( isset($entry['msgid']) && isset($entry['msgstr']) )
			{
				$id = is_array( $entry['msgid'] )? implode('', $entry['msgid']):$entry['msgid'];
				$this->entries[$id] = $entry;
			}
		}

		return $this->entries;
	}




	/**
	*	Updates an entry.
	*
	*	@param $original. String. Original string to translate.
	*	@param $translation. String. Translated string
	*/
	public function update_entry( $original, $translation )
	{
		$this->entries[ $original ]['fuzzy'] = false;
		$this->entries[ $original ]['msgstr'] = array($translation);

		if( isset( $this->entries[$original]['flags']) )
		{
			$flags = $this->entries[ $original ]['flags'];
			$this->entries[ $original ]['flags'] = str_replace('fuzzy', '', $flags );
		}
	}



	/**
	*	Write entries to a po file.
	*
	*	@example
	*		$pofile = new PoParser();
	*		$pofile->read('ca.po');
	*		
	*		// Modify an antry
	*		$pofile->update_entry( $msgid, $msgstr );
	*		// Save Changes back into `ca.po`
	*		$pofile->write('ca.po');
	*/
	public function write( $file_path )
	{
		$handle = @fopen($file_path, "wb");
		if( $handle!==false )
		{
			if( count($this->headers)>0 )
			{
				fwrite( $handle, "msgid \"\"\n");
				fwrite( $handle, "msgstr \"\"\n");
				foreach( $this->headers AS $header )
				{
					fwrite( $handle, $header."\n" );
				}
				fwrite( $handle, "\n" );
			}


			$entries_count = count($this->entries);
			$counter = 0;
			foreach( $this->entries AS $entry )
			{
				$isObsolete = isset($entry['obsolete']) && $entry['obsolete'];
            	$isPlural   = isset($entry['msgid_plural']);

				if( isset($entry['tcomment']) )
				{
					foreach( $entry['tcomment'] AS $comment )
					{
						fwrite( $handle, "# ". $comment . "\n" );
					}
				}

				if( isset($entry['ccomment']) )
				{
					foreach( $entry['ccomment'] AS $comment )
					{
						fwrite( $handle, '#. '.$comment . "\n" );
					}
				}

				if( isset($entry['reference']) )
				{
					foreach( $entry['reference'] AS $ref )
					{
						fwrite( $handle, '#: '.$ref . "\n" );
					}
				}

				if( isset($entry['flags'] ) && !empty($entry['flags']) )
				{
					fwrite( $handle, "#, ".$entry['flags']."\n" );
				}
				
				if( isset($entry['@']) )
				{
					fwrite( $handle, "#@ ".$entry['@']."\n" );
				}

				if( isset($entry['msgctxt']) )
				{
					fwrite( $handle, 'msgctxt '.$entry['msgctxt'] . "\n" );
				}

				if( $isObsolete )
				{
					fwrite($handle, "#~ ");
				}

				if( isset($entry['msgid']) )
				{
					// Special clean for msgid
					if( is_string($entry['msgid']) )
					{
						$msgid = explode("\n", $entry['msgid']);
					}
					elseif( is_array($entry['msgid']) )
					{
						$msgid = $entry['msgid'];
					}
					
					fwrite( $handle, 'msgid ');
					foreach( $msgid AS $i=>$id )
					{
						if( $i>0 && $isObsolete)
						{
							fwrite($handle, "#~ ");
						}
						fwrite( $handle, $this->clean_export($id). "\n");
					}
				}
				
				if( isset($entry['msgid_plural']) )
				{
					fwrite( $handle, 'msgid_plural '.$entry['msgid_plural'] . "\n" );
				}

				if( isset($entry['msgstr']) )
				{
					if( $isPlural )
					{
						foreach( $entry['msgstr'] AS $i=>$t )
						{
							if( $i==0 )
							{
								fwrite( $handle, 'msgstr '.$this->clean_export($entry['msgstr'][0]). "\n");
							}
							else
							{
								if( $isObsolete )
								{
									fwrite($handle, "#~ ");
								}

								fwrite( $handle, "msgstr[$i] " . $this->clean_export($t) . "\n");
							}
						}
					}
					else
					{
						foreach( $entry['msgstr'] AS $i=>$t )
						{
							if( $i==0 )
							{
								if( $isObsolete )
								{
									fwrite($handle, "#~ ");
								}

								fwrite( $handle, 'msgstr ' . $this->clean_export($t) . "\n" );
							}
							else
							{
								if( $isObsolete )
								{
									fwrite($handle, "#~ ");
								}

								fwrite( $handle, $this->clean_export($t). "\n" );
							}
						}
					}
				}

				$counter++;
				// Avoid inserting an extra newline at end of file
				if( $counter<$entries_count )
				{
					fwrite( $handle, "\n" );
				}
			}

			fclose( $handle );
		}
		else
		{
			throw new Exception('PoParser: Could not write into file "'.htmlspecialchars($file_path).'"');
		}
	}



	/**
	*	Prepares a string to be outputed into a file.
	*	
	*	@param $string. The string to be converted.
	*/
	protected function clean_export( $string )
	{
		$quote = '"';
		$slash = '\\';
		$newline = "\n";

		$replaces = array(
			"$slash" 	=> "$slash$slash",
			"$quote"	=> "$slash$quote",
			"\t" 		=> '\t',
		);

		$string = str_replace( array_keys($replaces), array_values($replaces), $string );

		$po = $quote.implode( "${slash}n$quote$newline$quote", explode($newline, $string) ).$quote;

		// remove empty strings
		return str_replace( "$newline$quote$quote", '', $po );
	}





	/**
	*	Undos `clean_export` actions on a string.
	*
	*	@param $input
	*	@return string|array.
	*/
	protected function clean($x)
	{
		if( is_array($x) ) {
			foreach( $x as $k => $v )
			{
				$x[$k] = $this->clean($v);
			}
		}
		else
		{
			// Remove double quotes from start and end of string
			if( $x=='' )
				return '';

			if( $x[0]=='"' )
				$x = substr( $x, 1, -1 );

			$x = stripcslashes( $x );
		}

		return $x;
	}


	/**
	*	Checks if entry is a header by 
	*/
	static protected function is_header( $entry )
	{
 		$header_keys = array(
			'Project-Id-Version:'	=> false,
			'Report-Msgid-Bugs-To:'	=> false,
			'POT-Creation-Date:'	=> false,
			'PO-Revision-Date:'		=> false,
			'Last-Translator:'		=> false,
			'Language-Team:'		=> false,
			'MIME-Version:'			=> false,
			'Content-Type:'			=> false,
			'Content-Transfer-Encoding:' => false,
			'Plural-Forms:'			=> false
		);
		$count = count($header_keys);
		$keys = array_keys($header_keys);

 		$header_items = 0;
 		foreach( $entry['msgstr'] AS $str )
 		{
 			$tokens = explode(':', $str);
 			$tokens[0] = trim( $tokens[0], "\"" ) . ':';

 			if( in_array($tokens[0], $keys) )
 			{
 				$header_items++;
 				unset( $header_keys[ $tokens[0] ] );
 				$keys = array_keys($header_keys);
 			}
 		}

 		return ($header_items==$count)? true:false;
	}
}