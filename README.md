Live Book (plugin for Omeka)
============================


Summary
-------

This plugin for [Omeka] is a digital reader that allows to manages scanned books
or images with Omeka:

* zoom and flip of pages (need some extra javascript to run),
* extraction and displaying of table of contents from PDF (need a pdf tool),
* full text search from content (KWIC) (if text is in the database or in xml).

This is an upgrade of [bibnum], used at [Université Rennes 2].


Installation
------------

Uncompress files and rename plugin folder "LiveBook".

Then install it like any other Omeka plugin and follow the config instructions.

The plugin is published as it, but you can adapt libraries/live_book_custom.php
to comply with your metadata.

Furthermore, in order to append the viewer on the page, the `items/show.php` file
in your theme should be updated:

### Admin item view (just the item files viewer)
- Add this line just before `echo head(...)`, line 10 of `admin/themes/default/items/show.php`:

```
    fire_plugin_hook('admin_theme_header', array('view' => $this));
```

- Add this line just after `echo item_image_gallery(...)` lines 17-19:

```
    fire_plugin_hook('live_book_item_image', array('view' => $this));
```

### Public item view (all tabs):
- Add this line just before `echo head(...)` in the `items/show.php` file:

```
    fire_plugin_hook('public_theme_header', array('view' => $this));
```

- Replace:

```
    <div id="item-metadata">
        <?php echo all_element_texts('item'); ?>
    </div>
    <h3><?php echo __('Files'); ?></h3>
    <div id="item-images">
         <?php echo files_for_item(); ?>
    </div>
```

by:

```
    <?php fire_plugin_hook('live_book_tabs', array('view' => $this, 'tabs' => array(
        'item-image', 'item-metadata', 'file-metadata', 'table-of-content', 'notes', 'search-content',
    ))); ?>
```

So the page displays a toolbar and ordered tabs for image, item metadata, table
of content, full notes, file metadata and search/result inside the content of
the current item.

Finally, you should adapt the css, currently adapted only for a set of images
with same size.


Warning
-------

Use it at your own risk.

It's always recommended to backup your database so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [Live Book issues] page on GitHub.


License
-------

This plugin is published under the [GNU/GPL] licence.

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.


Contact
-------

Current maintainers:

* Daniel Berthereau (see [Daniel-KM])
* Julien Sicot (see [jsicot], [bibnum] v0.5)

First version has been built by Julien Sicot for [Université Rennes 2].
It has been updated for [École des Ponts ParisTech].
The upgrade for Omeka 2.0 has been built for [Mines ParisTech].


Copyright
---------

* Copyright Daniel Berthereau, 2013
* Copyright Julien Sicot (Université de Rennes 2), 2010-2013 (bibnum v0.5)


[Omeka]: https://omeka.org "Omeka.org"
[bibnum]: https://github.com/jsicot/Plugin-bibnum
[Live Book issues]: https://github.com/Daniel-KM/LiveBook/Issues "GitHub LiveBook"
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html "GNU/GPL v3"
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
[jsicot]: https://github.com/jsicot "Julien Sicot"
[Université Rennes 2]: http://bibnum.univ-rennes2.fr
[École des Ponts ParisTech]: http://bibliotheque.enpc.fr "École des Ponts ParisTech / ENPC"
[Mines ParisTech]: http://bib.mines-paristech.fr "Mines ParisTech / ENSMP"
