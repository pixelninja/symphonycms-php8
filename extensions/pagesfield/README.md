# Page Select Box Field

> A field that features a select box filled with Symphony's pages

## Installation

1. Upload the `pagesfield` folder in this archive to your Symphony `/extensions` folder.

2. Enable it by selecting the "Field: Page Select Box", choose Enable from the with-selected menu, then click Apply.

3. You can now add the "Page Select Box" field to your sections.

For more information, see <http://getsymphony.com/learn/tasks/view/install-an-extension/>

## Sorting

The field sorts the pages according to the sortorder determined in 'Blueprints » Pages'. If the sorting isn't correct, it's probably because of the sortorder. To set the sortorder of your pages, edit your `config.php`, set `symphony/pages_table_nest_children` to `yes`, and sort the pages manually in your pages screen. When done, you can set `symphony/pages_table_nest_children` back to no (or keep it on yes, if that is your prefered way of working).
