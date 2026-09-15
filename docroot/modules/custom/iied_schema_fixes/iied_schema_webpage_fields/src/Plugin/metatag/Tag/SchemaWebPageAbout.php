<?php

namespace Drupal\iied_schema_webpage_fields\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_web_page_about' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_web_page_about",
 *   label = @Translation("about"),
 *   description = @Translation("What this page is about."),
 *   name = "about",
 *   group = "schema_web_page",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = 0,
 * )
 */
class SchemaWebPageAbout extends SchemaNameBase {

}
