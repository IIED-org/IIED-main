<?php

namespace Drupal\search_api_solr\Solarium\Autocomplete;

use Solarium\Component\ComponentAwareQueryInterface;
use Solarium\Component\ComponentAwareQueryTrait;
use Solarium\Component\QueryTraits\SpellcheckTrait;
use Solarium\Component\QueryTraits\SuggesterTrait;
use Solarium\Component\QueryTraits\TermsTrait;
use Solarium\Component\Spellcheck;
use Solarium\Component\Suggester;
use Solarium\Component\Terms;
use Solarium\Core\Query\AbstractQuery;
use Solarium\Core\Query\RequestBuilderInterface;
use Solarium\Core\Query\ResponseParserInterface;

/**
 * Autocomplete query.
 */
class Query extends AbstractQuery implements ComponentAwareQueryInterface {

  use ComponentAwareQueryTrait;
  use SpellcheckTrait;
  use SuggesterTrait;
  use TermsTrait;

  /**
   * Default options.
   *
<<<<<<< HEAD
   * @var array<string, mixed>
   */
  protected array $options = [
=======
   * @var array
   */
  protected $options = [
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
    'handler' => 'autocomplete',
    'resultclass' => Result::class,
  ];

  /**
   * Constructs a Query object.
   */
<<<<<<< HEAD
  public function __construct(?array $options = NULL) {
=======
  public function __construct($options = NULL) {
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
    $this->componentTypes = [
      ComponentAwareQueryInterface::COMPONENT_SPELLCHECK => Spellcheck::class,
      ComponentAwareQueryInterface::COMPONENT_SUGGESTER => Suggester::class,
      ComponentAwareQueryInterface::COMPONENT_TERMS => Terms::class,
    ];

    parent::__construct($options);
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return 'autocomplete';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestBuilder(): RequestBuilderInterface {
    return new RequestBuilder();
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseParser(): ResponseParserInterface {
    return new ResponseParser();
  }

}
