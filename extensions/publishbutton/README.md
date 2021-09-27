# Publish Button

A button that toggles an entry's published-state and allows authors to preview unpublished entries on the live site.


## Installation

- Upload the `/publishbutton` folder to your Symphony `/extensions` folder.
- Enable it by selecting 'Publish Button', choose 'Enable' from the with-selected menu, then click 'Apply'.


## Usage

When this field is added to a section, a new button will appear on its entries pages. This **Publish Button** will allow an author to toggle the published-state of an entry between "_Published_" and "_Unpublished_". Behind the scenes the button acts like a normal checkbox and translates these states to simple `yes` and `no` values which then can be used to filter your entries in data-sources.

When the user is logged in to Symphony, the value used for filtering and XML output will always be `yes`. This allows the author to create/edit an entry (like an article) and preview it on the live site without the public seeing it.


## Compatibility

**Publish button**  plays nicely together with the [Ajax Checkbox][2] extension _(Version 1.5.0 and up)_ which adds the possibility to easily toggle the published-state directly in the section's index/table view.


[1]: https://www.getsymphony.com
[2]: https://github.com/DeuxHuitHuit/ajax_checkbox