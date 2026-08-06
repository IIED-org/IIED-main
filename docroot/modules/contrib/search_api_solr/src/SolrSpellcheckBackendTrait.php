<?php

namespace Drupal\search_api_solr;

<<<<<<< HEAD
use Drupal\search_api_solr\Solarium\Autocomplete\Result as AutocompleteResult;
use Solarium\Component\ComponentAwareQueryInterface;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\Select\Result\Result as SelectResult;
=======
use Solarium\Component\ComponentAwareQueryInterface;
use Solarium\Core\Query\Result\ResultInterface;
>>>>>>> parent of 3b9f439507 (remove gitignored directories)

/**
 * Provides spellcheck related methods used by search backends and plugins.
 */
trait SolrSpellcheckBackendTrait {

  /**
<<<<<<< HEAD
   * Get the spellcheck suggestions from the given query result.
   *
   * @param \Solarium\Core\Query\Result\ResultInterface $result
   *   A query result.
=======
   * Get the spellcheck suggestions from the autocomplete query result.
   *
   * @param \Solarium\Core\Query\Result\ResultInterface $result
   *   An autocomplete query result.
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
   *
   * @return array
   *   An array of suggestions.
   */
  protected function extractSpellCheckSuggestions(ResultInterface $result) {
    $suggestions = [];
<<<<<<< HEAD
    if (
      ($result instanceof AutocompleteResult || $result instanceof SelectResult)
      && ($spellcheck_results = $result->getComponent(ComponentAwareQueryInterface::COMPONENT_SPELLCHECK))
    ) {
      /** @var \Solarium\Component\Result\Spellcheck\Suggestion $correction */
      foreach ($spellcheck_results as $correction) {
        $keys = [];
        foreach ($correction->getWords() as $word) {
          $keys[] = $word['word'];
        }
        if ($keys) {
          $suggestions[$correction->getOriginalTerm()] = $keys;
=======
    if ($spellcheck_results = $result->getComponent(ComponentAwareQueryInterface::COMPONENT_SPELLCHECK)) {
      foreach ($spellcheck_results as $term_result) {
        $keys = [];
        /** @var \Solarium\Component\Result\Spellcheck\Suggestion $term_result */
        foreach ($term_result->getWords() as $correction) {
          $keys[] = $correction['word'];
        }
        if ($keys) {
          $suggestions[$term_result->getOriginalTerm()] = $keys;
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
        }
      }
    }
    return $suggestions;
  }

}
