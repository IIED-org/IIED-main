<?php

namespace Drupal\iied_schema_webpage_fields\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_web_page_PrimaryImageOfPage' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_web_page_primaryimageofpage",
 *   label = @Translation("primaryImageOfPage"),
 *   description = @Translation("Primary image of the page."),
 *   name = "primaryimageofpage",
 *   group = "schema_web_page",
*   weight = 2,
*   type = "image",
*   secure = FALSE,
*   multiple = FALSE,
*   property_type = "image_object",
*   tree_parent = {
*     "ImageObject",
*   },
*   tree_depth = 0,
 * )
 */
class SchemaWebPagePrimaryImageOfPage extends SchemaNameBase {

}
