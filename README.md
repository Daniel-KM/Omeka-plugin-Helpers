Helpers (plugin for Omeka)
==========================

[Helpers] is a plugin for [Omeka] that adds some useful functions for plugin and
theme builders.

- Find the previous record from the metadata
- Find the next record from the metadata


Installation
------------

Uncompress files and rename plugin folder "Helpers".

Then install it like any other Omeka plugin and follow the config instructions.


Usage
-----

The config provides some options to set the default values.

Other functions are available in the file `libraries/Helpers/helpers.php` and
the files in `views/helpers`.

* Link to previous record / Link to next record

The previous and next records can be set with a metadata, specified in the
plugin. It will be used with standard functions `link_to_previous_item_show()`
and `link_to_next_item_show()` and standard methods `$item->previous()` and
`$item->next()`.

For more flexibility, you can use the view helpers `$this->getNextItem()` and
`$this->getNextItem()`.


Update for version 2.12
-----------------------

The functions `link_to_previous()` and `link_to_next()` have been removed since
version 2.12 due to a possible incompatibility with Geolocation (see [issues/1]).
So replace them with the view helpers above, or include the file `libraries/Helpers/helpers.php`
inside the file `custom.php` of your theme, or inside a specific plugin:

```php
include_once '../../plugins/Helpers/libraries/Helpers/helpers.php';
```


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [plugin issues] page on GitHub.


License
-------

This plugin is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

In consideration of access to the source code and the rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors only have limited liability.

In this respect, the risks associated with loading, using, modifying and/or
developing or reproducing the software by the user are brought to the user’s
attention, given its Free Software status, which may make it complicated to use,
with the result that its use is reserved for developers and experienced
professionals having in-depth computer knowledge. Users are therefore encouraged
to load and test the suitability of the software as regards their requirements
in conditions enabling the security of their systems and/or data to be ensured
and, more generally, to use and operate it in the same conditions of security.
This Agreement may be freely reproduced and published, provided it is not
altered, and that no provisions are either added or removed herefrom.


Contact
-------

Current maintainers:

* Daniel Berthereau (see [Daniel-KM] on GitHub)


Copyright
---------

* Copyright Daniel Berthereau, 2012-2018


[Helpers]: https://github.com/Daniel-KM/Omeka-plugin-Helpers
[Omeka]: https://omeka.org/classic
[issues/1]: https://github.com/Daniel-KM/Omeka-plugin-Helpers/issues/1
[plugin issues]: https://github.com/Daniel-KM/Omeka-plugin-Helpers/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
