# Taxonomy Import

## Contents of this file

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers

## Introduction

This module can be used to create taxonomy terms by importing data from CSV or
XML file to a specified Vocabulary.

## Requirements

This module requires the following:

* A CSV or XML file of Taxonomy terms to import.
* The .csv file can have three columns,eg:- name, parent, description.
* The first column is taken as Name, second column is taken as Parent of the
  Taxonomy Term created and the third column is taken as Description of the
  Taxonomy term created. The first row will be the headers.
* The .xml file can have three tags, ei: `<name>`, `<parent>`, `<description>`.
* Refer the example given in the module folder XML_Test.xml, CSV_Test.csv.

## Installation

Install as usual.  See
[Extending Drupal](https://www.drupal.org/docs/8/extending-drupal-8/) for
further information.

## Configuration

After successfully installing the module taxonomy_import, you can create a
vocabulary and save terms to it provided via the file import.

Install the module taxonomy_import.

Go to Configuration and select Taxonomy Import from Content Authoring.

It will redirect you to Taxonomy Import Form, with two fields: Vocabulary name
and Import file.

For a Taxonomy term, the two main fields are its Name and Relations.  Before
selecting the file, you need to consider that this module create taxonomy terms
with a name, a parent and a description. So these data should come first in
your file.

Give values to the fields. The file Imported should be a CSV or XML.

Click on Import which redirects you to
`admin/structure/taxonomy/manage/<vocabulary name>/overview page`.

If .csv file contains three colums, the first column value is set as Name,
second column value is set as Parent and third column value is set as
Description. For creating hierarchy based taxonomy, the values in the second
column should come in the first column at the beginning of the file.

Similarly .xml file can have three tags with values from which first value will
be saved to Name, second value will be saved to Parent and third value will be
saved to Description fields of taxonomy respectively.

## Maintainers

### Original

* Sajini Antony
* Neethu P S
* Aswathy Ajish
* Colan Schwartz
* Ilcho Vuchkov

### Current

* Colan Schwartz ([colan](https://www.drupal.org/u/colan))
* Ilcho Vuchkov ([vuil](https://www.drupal.org/u/vuil))
