v5.1.7 (2019-06-03)
* Fix not fully flagging obsolete entries (thanks @Stefaminator).
* Fix long lines wrapping not taking into account multi-byte characters. (thanks @Stefaminator).

v5.1.6 (2018-07-20)
* Fix wrong initialization of plural strings.

v5.1.5 (2018-04-17)
* Fix #79 Prevent splitting multibyte characters across lines (thanks @chrisminett)

v5.1.4 (2018-04-05)
* Fix #86 by making parseFile and parseString both return a Catalog

v5.1.3 (2018-03-28)
* Fix #83, double escaped special characters.
* Add support for `msgid_plural` in `EntryFactory::createFromArray`

v5.1.2 (2018-02-26)
* Fix PoCompiler by adding missing quotes and \n in headers. 

v5.1.1 (2018-02-10)
* Header::setHeaders() to allow modifying PO headers.

v5.1 (2018-02-04)
* Parser::parser() optionally accepts a Catalog interface implementation. 
* Parser refactor for easier maintenance.
* Fix parsing comments without space between `#` and text.
* Fix parsing multiline Headers. 
* Improve Compiler by creating an empty `msgstr[n]` for every plural form defined.
* Improve parsing headers by offering a base for more granular interpretation.

v5.0 (2018-02-02)
* Backwards incompatible version! Check [v5 Documentation]() and [Migration guide]() for more information.
* Refactored to avoid usage issues like [this](https://github.com/raulferras/PHP-po-parser/issues/67), [this](https://github.com/raulferras/PHP-po-parser/issues/62), [this](https://github.com/raulferras/PHP-po-parser/issues/52), [this](https://github.com/raulferras/PHP-po-parser/issues/50)
* PSR-4
* New feature: All entry properties of an entry can now be edited.
* New feature: Output files wraps long strings.
* Fixes parsing previous strings wrapped in multiple lines.
* Fix: Obsolete entries were ignoring `msgctxt` properties.
* Fix: Obsolete entries does not output `msgstr` properties.
* Fixes some corner cases reported.
* More tests!
* Main deprecations (check [Migration guide]() for more information.):
  - Namespace changed to `Sepia\PoParser`
  - `PoParser` renamed to `Parser`
  - `parser` method does not return an array anymore, instead a `CatalogArray` object.
  - No need for options anymore.

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
