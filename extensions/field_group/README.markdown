# Field Group

A Symphony CMS extension to group fields in a row.

## Purpose

Sometimes there are groups of fields that would look nicer in a row, either aesthetically or for a better UI, instead of stacked. This solves that.

## Installation

- Upload the `/field_group` folder to your Symphony `/extensions` folder.
- Enable it by selecting "Field Group", choose Enable from the with-selected menu, then click Apply.

## Usage

There are 2 fields, Group Start and Group End. All fields within those will be grouped into a parent wrapper. If there is no Group End field, it will pull out all following fields (including other Groups) so use wisely.

As this groups using Javascript, fields with a different placement will not be included. Make sure each field within a group has the same placement.


### Todo

- Add columns option, to allow for a maximum column length within rows.
