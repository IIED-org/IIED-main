<?php

namespace Drupal\iied_schema_organization_researchproject\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_organization_funder' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_organization_funder",
 *   label = @Translation("funder"),
 *   description = @Translation("A person or organization that supports (sponsors) something through some kind of financial contribution."),
 *   name = "funders",
 *   group = "schema_organization",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaOrganizationResearchProjectFunder extends SchemaNameBase {

}
