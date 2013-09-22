# PoParser
PoParser is PO parser for PHP.

## Usage
### Read Po Content
```php
    $parser = new PoParser\Parser();
    $parser->read('my-pofile.po');
    $entries = $parser->getEntriesAsArrays();
    // Now $entries contains every string information in your pofile

    echo '<ul>';
    foreach ($entries as $entry) {
       echo '<li>'.
       '<b>msgid:</b> '.$entry['msgid'].'<br>'.         // Message ID
       '<b>msgstr:</b> '.$entry['msgstr'].'<br>'.       // Translation
       '<b>reference:</b> '.$entry['reference'].'<br>'. // Reference
       '<b>msgctxt:</b> ' . $entry['msgctxt'].'<br>'.   // Message Context
       '<b>tcomment:</b> ' . $entry['tcomment'].'<br>'. // Translator comment
	   '<b>ccomment:</b> ' . $entry['ccomment'].'<br>'. // Code Comment
	   '<b>obsolete?:</b> '.(string)$entry['obsolete'].'<br>'. // Is obsolete?
		'<b>fuzzy?:</b> ' .(string)$entry['fuzzy'].     // Is fuzzy?
		'</li>';
	}
	echo '</ul>';
```

### Modify Content
```php
    $parser = new PoParser\Parser();
    $parser->read('my-pofile.po');
    // Entries are stored in array, so you can modify them.

    // Use updateEntry method to change messages you want.
    $parser->updateEntry('Write your email', 'Escribe tu email');
    $parser->write('my-pofile.po');
```

## Todo
* Improve interface to edit entries.
* Discover what's the meaning of "#@ " line.

## License
This library is released under [MIT](http://www.tldrlegal.com/license/mit-license) license.