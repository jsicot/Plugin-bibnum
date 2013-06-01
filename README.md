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

Furthermore, in order to append the viewer on the page, the items/show.php file
in your theme should be updated:

* Admin item view
Replace line 42 in admin theme (default is admin/themes/default/items/show.php):

```
    echo display_files_for_item(array('imageSize' => 'fullsize'));
```

by:

```
    if (function_exists(live_book_append_to_item)) :
        echo live_book_admin_append_to_item($item);
    else :
        echo display_files_for_item(array('imageSize' => 'fullsize'));
    endif;
```

* Public item view

The plugin need some similar changes in your theme. For example, if you had:

```
    <?php
        echo custom_show_item_metadata();
        echo plugin_append_to_items_show();
    ?>
```

you can change it with:

```
    <?php if (function_exists(live_book_append_to_item)) : ?>
        <div id="tabs">
            <ul>
                <li><a href="#view"><?php echo __('View');?></a></li>
                <li><a href="#notice"><?php echo __('Record');?></a></li>
                <?php if ($tableOfContent = live_book_tableOfContent()) : ?>
                <li><a href="#index"><?php echo __('Index');?></a></li>
                <?php endif; ?>
                <?php if ($notes = '') : ?>
                <li><a href="#notes"><?php echo __('Notes');?></a></li>
                <?php endif; ?>
                <?php if ($searchContent = live_book_searchContent()) : ?>
                <li><a href="#search_content"><?php echo __('Search');?></a></li>
                <?php endif; ?>
            </ul>
            <!-- view -->
            <?php echo live_book_append_to_item(); ?>
            <!-- notice -->
            <div id="notice">
            <?php
                // The following function prints all the the metadata associated
                // with an item: Dublin Core, extra element sets, etc. See
                // http://omeka.org/codex or the examples on items/browse for
                // information on how to print only select metadata fields.
                echo custom_show_item_metadata();
                echo plugin_append_to_items_show();
            ?>
            </div>
            <?php if ($tableOfContent) : ?>
            <!-- index -->
            <?php echo $tableOfContent;?>
            <?php endif; ?>
            <?php if ($notes) : ?>
            <!-- notes -->
            <div id="notes">
                <?php echo $notes;?>
            </div>
            <?php endif; ?>
            <!-- search_content -->
            <?php if ($searchContent) : ?>
            <div id="search_content">
                <?php echo $searchContent;?>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
```

So the page displays a toolbar and, for the public page, from two to five tabs:
image, metadata, table of content, description and search/result.


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
