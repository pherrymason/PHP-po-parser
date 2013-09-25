Po Parser
=========

Po Parser is a personal project to fulfill a need I got: parse po files and edit its content using PHP.

Methods
=======
## read( $file_path )
This function parses a `po` file and returns an array with its entries.  

### Parameters
`$file_path`: String. po filepath.

### Returns
An `Array` of `entries`.  
Each `entry` has the following keys:

- `msgid`: String Array. Entry identifier.
- `msgstr`: String Array. Translated string.
- `reference`: String Array. Source code filepaths where this message is found.
- `msgctxt`: String. Message context.
- `tcomment`: String Array. Translator comments.
- `ccomment`: String Array. Source code comments linked to the message.
- `obsolete`: Bool (1/0). Marks the entry as obsolete.
- `fuzzy`: Bool (1/0). Marks the entry as a fuzzy translation.

### Throws
This function throws `Exception` if file cannot be opened and parse error or a logic error occurs.



## write( $file_path )
This function writes a `po` file from the internal `$entries` property.  

### Throws
This function throws `Exception` if output file cannot be opened to write.


## update_entry( $msgid, $msgstr )
This functions updates an entry parsed previously with `read` method.

### Parameters
`$msgid`: Entry identifier.  
`$msgstr`: Translation to be stored.




Usage
=====
## Reading Po Content

    $poparser = new PoParser();
    entries = $poparser->read( 'my-pofile.po' );
    // Now $entries contains every string information in your pofile
    
    echo '<ul>';
    foreach( $entries AS $entry )
    {
       echo '<li>'.
       '<b>msgid:</b> '.implode('<br>',$entry['msgid']).'<br>'.         // Message ID
       '<b>msgstr:</b> '.implode('<br>',$entry['msgstr']).'<br>'.       // Translation
       '<b>reference:</b> '.implode('<br>',$entry['reference']).'<br>'. // Reference
       '<b>msgctxt:</b> ' . $entry['msgctxt'].'<br>'.   // Message Context
       '<b>tcomment:</b> ' . implode("<br>",$entry['tcomment']).'<br>'. // Translator comment
	   '<b>ccomment:</b> ' . implode("<br>",$entry['ccomment']).'<br>'. // Code Comment
	   '<b>obsolete?:</b> '.(string)$entry['obsolete'].'<br>'. // Is obsolete?
		'<b>fuzzy?:</b> ' .(string)$entry['fuzzy'].     // Is fuzzy?
		'</li>';
	}
	echo '</ul>';
	
	
## Modify Content

    $poparser = new PoParser();
    $poparser->read( 'my-pofile.po' );
    // Entries are stored in `$pofile` object, so you can modify them.
    
    // Use `update_entry( msgid, msgstr )` to change the messages you want.
    $poparser->update_entry( 'Write your email', 'Escribe tu email' );
    $poparser->write( 'my-pofile.po' );


Todo
====
* Improve interface to edit entries.
* <strike>Discover what's the meaning of the line "#@ ".</strike> It was just a comment `# @`.


Changelog
=========

###v2.0
Version 2.0 introduces a lot of bug fixes, mainly related to multiline entries. I also decide to change class name to something more semantic (`PoParser`) as I felt old name was not well suited.  
Check `read` documentation to look for changes in data returned.

* Class name changed to `PoParser`.
* Improve reading of multiline entries.
* Fix ending quotes being removed on multiline `msgid` and `msgstr`.
* Possible bug with `msg_id_plural`.
* `read` method throws `Exception` if an error occurs.
* Translator and source code **Multiline comments**  are properly parsed.


###v1.0
* First version.

