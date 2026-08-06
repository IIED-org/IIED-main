<?php

namespace Drupal\search_api_solr\Plugin\search_api\parse_mode;

<<<<<<< HEAD
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiParseMode;
=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
use Drupal\search_api\Plugin\search_api\parse_mode\Terms;

/**
 * Represents a parse mode that parses the sentence into a sloppy search.
<<<<<<< HEAD
 */
#[SearchApiParseMode(
  id: 'sloppy_terms',
  label: new TranslatableMarkup('Multiple words with sloppiness'),
  description: new TranslatableMarkup('The query is interpreted as multiple keywords separated by spaces. Keywords containing spaces may be ""quoted"" and interpreted as a single phrase. Solr will also show results where the words are not directly positioned next to each other. The scoring will be lower the further away the words are from each other. Quoted keywords must still be separated by spaces.'),
)]
=======
 *
 * @SearchApiParseMode(
 *   id = "sloppy_terms",
 *   label = @Translation("Multiple words with sloppiness"),
 *   description = @Translation("The query is interpreted as multiple keywords separated by spaces. Keywords containing spaces may be ""quoted"" and interpreted as a single phrase. Solr will also show results where the words are not directly positioned next to each other. The scoring will be lower the further away the words are from eachother. Quoted keywords must still be separated by spaces."),
 * )
 */
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
class SloppyTerms extends Terms {

}
