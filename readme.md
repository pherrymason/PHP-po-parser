Po Parser
=========
[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/raulferras/PHP-po-parser?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Build Status](https://travis-ci.org/raulferras/PHP-po-parser.png?branch=master)](https://travis-ci.org/raulferras/PHP-po-parser)
[![Code Coverage](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/badges/coverage.png?s=a19ece2a8543b085ab1a5db319ded3bc4530b567)](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/badges/quality-score.png?s=6aaf3c31ce15cebd1d4bed718cd41fd2d921fd31)](https://scrutinizer-ci.com/g/raulferras/PHP-po-parser/)
[![Latest Stable Version](https://poser.pugx.org/leaphly/cart-bundle/version.png)](https://packagist.org/packages/sepia/po-parser)

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
    $poParser = new Sepia\PoParser();
    $entries  = $poParser->parseFile( 'es.po' );
    // $entries contains every entry in es.po file.

    // Update entries
    $msgid = 'Press this button to save';
    $msgstr= 'Pulsa este botón para guardar';
    $poParser->updateEntry($msgid, $msgstr);
    // You can also change translator comments, code comments, flags...



Changelog
=========
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
