Po Parser
=========

Po Parser is a personal project to fulfill a need I got: parse po files and edit its content using PHP.

[![Build Status](https://travis-ci.org/raulferras/PHP-po-parser.png?branch=master)](https://travis-ci.org/raulferras/PHP-po-parser)
[![Code Coverage](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/badges/coverage.png?s=a19ece2a8543b085ab1a5db319ded3bc4530b567)](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/badges/quality-score.png?s=6aaf3c31ce15cebd1d4bed718cd41fd2d921fd31)](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/)
[![Latest Stable Version](https://poser.pugx.org/leaphly/cart-bundle/version.png)](https://packagist.org/packages/sepia/po-parser)

Methods
=======
## read( $file_path )
This method parses a `po` file and returns an array with its entries.  

### Parameters
`$file_path`: String. po filepath.

### Returns
An `Array` of `entries`.  
Each `entry` has the following keys:

- `msgid`: String Array. Entry identifier.
- `msgstr`: String Array. Translated string.
- `reference`: String Array. Source code filepaths where this message is found.
- `msgctxt`: String. Disambiguating context.
- `tcomment`: String Array. Translator comments.
- `ccomment`: String Array. Source code comments linked to the message.
- `obsolete`: Bool (1/0). Marks the entry as obsolete.
- `fuzzy`: Bool (1/0). Marks the entry as a fuzzy translation.

### Throws
This method throws `Exception` if file cannot be opened and parse error or a logic error occurs.


## headers()
Called after `read()` method, returns the headers of the file, if present.

## set_headers(array)
Called before `write()` method, set new headers.

### Returns 
An `Array` of strings containing all headers present in the file.

## write( $file_path )
This method writes a `po` file from the internal `$entries` property.  

### Throws
This method throws `Exception` if output file cannot be opened to write.


## update_entry( $msgid, $msgstr = null, $tcomment = array(), $ccomment = array() )
This method updates an entry parsed previously with `read` method.

### Parameters
`$msgid`: Entry identifier.  
`$msgstr`: String. Optional. Translation to be stored.
`$tcomment`: Array. Optional. Translator comments. 
`$ccomment`: Array. Optional. Developer comments.

When updating an entry that makes use of a **Disambiguating Context**, use &lt;context>!&lt;msgid> as the first parameter.
Example:

    // Edit the message "Welcome user!"
    $poparser->update_entry( "Welcome user!", "Bienvenido usuario!" );
    
    // Edit the message "N" in the Dissambiguating Context "North"
    $poparser->update_entry( "N!Norte" );

    // Edit the message "N" in the Dissambiguating Context "No"
    $poparser->update_entry( "N!No" );



Usage
=====
## Reading Po Content

    $poparser = new Sepia\PoParser();
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

    $poparser = new Sepia\PoParser();
    $poparser->read( 'my-pofile.po' );
    // Entries are stored in `$pofile` object, so you can modify them.
    
    // Use `update_entry( msgid, msgstr )` to change the messages you want.
    $poparser->update_entry( 'Write your email', 'Escribe tu email' );
    $poparser->write( 'my-pofile.po' );


Todo
====
* Improve interface to edit entries.


Changelog
=========

###v3.0.4

* Fix updating multiline plural messages (thanks @newage)
* `update_entry` method now allows update translator comments and developer comments (thanks @newage)
* Added travis and scrutinizer files (thanks @newage)

###v3.0.3

* Project renamed to sepia/po-parser in composer.json

###v3.0.2

* Changes to follow PSR-0

###v3.0.1

* Po Header detection was too strict by expecting “Plural-Forms” to be present


###v3.0
Version 3.0 changes:

* Library namespaced.
* Adds composer support.
* Includes some simple unit tests to better avoid regressions or detect errors.
* Fixes an error when no header is found.

###v2.1
Version 2.1 has the following changes:

* fixes errors when saving msgid_plurals (thanks @felixgilles).
* Now it handles entries using msgctxt correctly by not merging them into a single entry.
* Headers of some file headers were being ignored because of a too strict check.
* A new method is introduced to read file headers: `headers()`.

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

