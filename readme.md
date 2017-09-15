Po Parser
=========

[![Latest Stable Version](https://poser.pugx.org/sepia/po-parser/v/stable)](https://packagist.org/packages/sepia/po-parser) 
[![Total Downloads](https://poser.pugx.org/sepia/po-parser/downloads)](https://packagist.org/packages/sepia/po-parser) 
[![License](https://poser.pugx.org/sepia/po-parser/license)](https://packagist.org/packages/sepia/po-parser) 
[![Build Status](https://travis-ci.org/raulferras/PHP-po-parser.png?branch=master)](https://travis-ci.org/raulferras/PHP-po-parser) 
[![Code Coverage](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/badges/coverage.png?s=a19ece2a8543b085ab1a5db319ded3bc4530b567)](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/) 
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/badges/quality-score.png?s=6aaf3c31ce15cebd1d4bed718cd41fd2d921fd31)](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/) 
[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/raulferras/PHP-po-parser?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)


PoParser is a personal project to fulfill a need I got: parse Gettext Portable files (*.po files) and edit its content using PHP.  

PoParser will allow you to read PO Data from any source (files and strings built-in), update it and store back to a file (or get the compiled string).

It supports following parsing features:

- header section.
- msgid, both single and multiline.
- msgstr, both single and multiline.
- msgctxt (Message context).
- msgid_plural (plurals forms).
- #, keys (flags).
- # keys (translator comments).
- #. keys (Comments extracted from source code).
- #: keys (references).
- #| keys (previously untranslated), both single and multiline.
- #~ keys (old entries), both single and multiline.

Usage
=====

    // Parse a po file
    $fileHandler = new Sepia\FileHandler('es.po');
    
    $poParser = new Sepia\PoParser($fileHandler);
    $entries  = $poParser->parse();
    // $entries contains every entry in es.po file.

    // Update entries
    $msgid = 'Press this button to save';
    $entries[$msgid]['msgstr'] = 'Pulsa este botón para guardar';
    $poParser->setEntry($msgid, $entries[$msgid]);
    // You can also change translator comments, code comments, flags...



Changelog
=========
v5.0 (WIP)
* Classes are now fluid.
* Namespaces reorganized.
* Removed `fuzzy` index in favour of `flags`.
* Display line number on parsing errors instead of line content.
* Adds compatibility with `#~|` entries.
* `parseString()` and `parseFile()` converted to factory methods.
* Removed method `updateEntry()` in favour of `setEntry()`.

v4.2.2
* More PHPDocs fixes
* Strict comparisons used where safe.
* Fix example for `writeFile`.
* Support for EOL line formatting.

v4.2.1
* Support multiline for plural entries (thanks @Ben-Ho)

v4.2.0
* Add function to add plural and context to existing entry (thanks @Ben-Ho)
* Add ability to change msg id of entry (thanks @wildex)


v4.1.1
* Fixes with multi-flags entries (thanks @gnouet)

v4.1
* Constructor now accepts options to define separator used in multiline msgid entries.
* New method `getOptions()`.

v4.0

* new methods parseString() and parseFile() replace the old parse()`
* new method writeFile() replaces the old write().
* new method compile() which takes all parsed entries and coverts back to a PO formatted string.

[See whole changelog](https://github.com/raulferras/PHP-po-parser/wiki/Changelog)


Documentation
=============
[See v4 documentation](https://github.com/raulferras/PHP-po-parser/wiki/Documentation-4.0)


Testing
=======
Tests are done using PHPUnit.
To execute tests, from command line type: 

```
php vendor/bin/phpunit
```


Install via composer
====================
Edit your composer.json file to include the following:

    {
        "require": {
            "sepia/po-parser": "dev-master"
        }
    }
