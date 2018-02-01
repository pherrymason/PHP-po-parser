v5.0
* Backwards incompatible version! Check [v5 Documentation]() and [Migration guide]() for more information.
* Refactored to avoid usage issues like [this](https://github.com/raulferras/PHP-po-parser/issues/67), [this](https://github.com/raulferras/PHP-po-parser/issues/62), [this](https://github.com/raulferras/PHP-po-parser/issues/52), [this](https://github.com/raulferras/PHP-po-parser/issues/50)
* New feature: All entry properties can now be edited.
* New feature: Output files wraps long strings.
* New feature: Compatible with older disambiguating contexts formats (_:...\n): 
    The disambiguating context has been embedded at the beginning of the msgid, surrounded by _⁠: ...\n. In a contemporary Gnome program, the same message would look something like this:


* Fix: Obsolete entries were ignoring `msgctxt` properties.
* Fix: Obsolete entries does not output `msgstr` properties.
* Fixes some corner cases reported.
* More tests!
* Main deprecations (check [Migration guide]() for more information.):
  - Namespace changed to `Sepia\PoParser`
  - `PoParser` renamed to `Parser`
  - `parser` method does not return an array anymore, instead a `Catalog` object.
  - No need for options anymore.
  
  
    * `Parser::parse` method now returns a `Catalog` object containing a collection of entries. 
    * Transparent handling of multiline entries. Parser no longer saves `msgid` and `msgstr` as arrays in memory. 
      It was stupid to do so as the multiline feature found in po files is only
      a cosmetic thing to help translator read the file more easily.
      As a consequence, the option `multiline-glue` has been removed. 
    * Better handling of contexts. `Catalog::getEntry` offers an optional `context`
      argument to retrieve a specific context.
      As a consequence, the option `context-glue` has been removed.
    * PoParser methods removed:
      * getEntries
      * entries
      * getHeaders
      * setHeaders
      * setEntry
      * setEntryPlural
      * setEntryContext
      * writeFile
      * compile
    * PoParser Namespace changed to Sepia\PoParser
    * PoParser class renamed to Parser

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