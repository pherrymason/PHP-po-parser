PoParser
=======
PoParser is a personal project to fulfill a need I got: parse Gettext Portable files (*.po files) and edit its content using PHP.  

PoParser requires PHP >= 5.4, but may work in 5.3 too.  
[Changelog](changelog.md)

[![Latest Stable Version](https://poser.pugx.org/sepia/po-parser/v/stable)](https://packagist.org/packages/sepia/po-parser) 
[![Total Downloads](https://poser.pugx.org/sepia/po-parser/downloads)](https://packagist.org/packages/sepia/po-parser) 
[![License](https://poser.pugx.org/sepia/po-parser/license)](https://packagist.org/packages/sepia/po-parser) 
[![Build Status](https://travis-ci.org/raulferras/PHP-po-parser.png?branch=master)](https://travis-ci.org/raulferras/PHP-po-parser) 
[![Code Coverage](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/badges/coverage.png?s=a19ece2a8543b085ab1a5db319ded3bc4530b567)](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/) 
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/badges/quality-score.png?s=6aaf3c31ce15cebd1d4bed718cd41fd2d921fd31)](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/) 

[![Gitter](https://badges.gitter.im/raulferras/PHP-po-parser.svg)](https://gitter.im/raulferras/PHP-po-parser?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)


Features
========
It supports following parsing features:

- header section.
- msgid, both single and multiline.
- msgstr, both single and multiline.
- msgctxt (Message context).
- msgid_plural (plurals forms).
- #, keys (flags).
- <span># keys (translator comments).</span>
- #. keys (Comments extracted from source code).
- #: keys (references).
- #| keys (previous strings), both single and multiline.
- #~ keys (old entries), both single and multiline.

Installation
============

```
composer require sepia/po-parser
```

Usage
=====
```
<?php 
// Parse a po file
$fileHandler = new Sepia\PoParser\SourceHandler\FileSystem('es.po');

$poParser = new Sepia\PoParser\Parser($fileHandler);
$catalog  = $poParser->parse();

// Get an entry
$entry = $catalog->getEntry('welcome.user');

// Update entry
$entry = new Entry('welcome.user', 'Welcome User!');
$catalog->setEntry($entry);

// You can also modify other entry attributes as translator comments, code comments, flags...
$entry->setTranslatorComments(array('This is shown whenever a new user registers in the website'));
$entry->setFlags(array('fuzzy', 'php-code'));
```

## Save Changes back to a file
Use `PoCompiler` together with `FileSystem` to save a catalog back to a file:
 
```
$fileHandler = new Sepia\PoParser\SourceHandler\FileSystem('en.po');
$compiler = new Sepia\PoParser\PoCompiler();
$fileHandler->save($compiler->compile($catalog));
```

Documentation
=============
- [v5 Documentation](https://github.com/raulferras/PHP-po-parser/wiki/Documentation-5.0)
- [Migration guide from v4 to v5](https://github.com/raulferras/PHP-po-parser/wiki/Migration-v4-to-v5)
- [v4 documentation](https://github.com/raulferras/PHP-po-parser/wiki/Documentation-4.0)


Testing
=======
Tests are done using PHPUnit.
To execute tests, from command line type: 

```
php vendor/bin/phpunit
```


TODO
====
* Add compatibility with older disambiguating contexts formats. 
