## CONTENTS OF THIS FILE

 - Introduction
 - Requirements
 - Installation
 - Configuration

## INTRODUCTION

 - This module provides the ability to create content entities after submitting webforms,
  and do mappings between the fields of the created content and webform
  submission values.

## REQUIREMENTS

This module requires the following modules:

 - [Webform](https://www.drupal.org/project/webform)

## INSTALLATION

 - Install as you would normally install a contributed Drupal module.

## CONFIGURATION

 - Configure Webform Content Creator entities:

   1. Enable Webform Content Creator module;
   2. Go to Webform Content Creator configuration page;
      (/admin/config/webform_content_creator)
   3. Click on "Add configuration";
   4. Give a title to Webform Content Creator entity and choose a Webform and a
      Content Type, in order to have mappings between Webform submission values
      and content field values, and then click on "Save";
   5. In the configuration page (/admin/config/webform_content_creator), click
      on "Manage fields" on the entity you have just created;
   6. In the "Title" input, you can give a title to the content that is created
      after submitting the Webform (tokens may be used);
   7. After that, you have the possibility to choose the Content entity fields used in
      the mapping;
   8. When choosing a Content field (checkbox on the left side of each field name)
      , a Webform field can be choosen to match with this Content field
      (optionally, you can provide a custom text instead, using available
      tokens).

 - Configure the user permissions in Administration » People » Permissions:

   - Access Webform content creator configurations

     Users with this permission will see the administration menu at the top of
     each page.

## MAINTAINERS

- João Marques (joaomarques736) - <https://www.drupal.org/u/joaomarques736>
- Ricardo Tenreiro de Sá (ricardotenreiro) - <https://www.drupal.org/u/ricardotenreiro>
- Nuno Ramos (-nrzr-) - <https://www.drupal.org/u/nrzr>
- Débora Antunes (dgaspara) - <https://www.drupal.org/u/dgaspara>
- Miguel Ferreira (Miguelpamferreira) - <https://www.drupal.org/u/miguelpamferreira>
- Nelson Alves (nsalves) - <https://www.drupal.org/u/nsalves>
- sara_asb - <https://www.drupal.org/u/sara_asb>
