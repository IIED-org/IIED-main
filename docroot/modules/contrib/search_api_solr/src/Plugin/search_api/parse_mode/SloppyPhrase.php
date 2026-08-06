<?php

namespace Drupal\search_api_solr\Plugin\search_api\parse_mode;

<<<<<<< HEAD
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiParseMode;
=======
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
use Drupal\search_api\Plugin\search_api\parse_mode\Phrase;

/**
 * Represents a parse mode.
<<<<<<< HEAD
 */
#[SearchApiParseMode(
  id: 'sloppy_phrase',
  label: new TranslatableMarkup('Phrase search with sloppiness'),
  description: new TranslatableMarkup('The query is interpreted as a single phrase. Solr will also show results where the words are not directly positioned next to each other. The scoring will be lower the further away the words are from each other'),
)]
=======
 *
 * A parse mode that parses the sentence into a sloppy search for the sentence.
 *
 * @SearchApiParseMode(
 *   id = "sloppy_phrase",
 *   label = @Translation("Phrase search with sloppiness"),
 *   description = @Translation("The query is interpreted as a single phrase. Solr will also show results where the words are not directly positioned next to each other. The scoring will be lower the further away the words are from eachother"),
 * )
 */
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
class SloppyPhrase extends Phrase {

}
