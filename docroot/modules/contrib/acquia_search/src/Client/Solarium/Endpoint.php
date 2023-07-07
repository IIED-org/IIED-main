<?php

namespace Drupal\acquia_search\Client\Solarium;

use Drupal\search_api_solr\SolrConnectorInterface;
use Solarium\Core\Client\Endpoint as SolariumEndpoint;

/**
 * Custom Endpoint class for Solarium.
 *
 * This class takes custom Acquia Configuration for v3 endpoints.
 *
 * URL Pattern for SOLR 7+ QUERIES:
 *  "$SCHEME://$HOST:$PORT/$PATH/$CORE"
 *
 * @package Drupal\acquia_search
 */
class Endpoint extends SolariumEndpoint {

  const DEFAULT_NAME = 'search_api_solr';

  /**
   * Default name for Endpoint.
   *
   * @var string
   */
  protected $schema;

  /**
   * Options for putting together the endpoint urls.
   *
   * @var array
   */
  protected $options = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $options) {
    $timeout_config = [
      // Search API Solr maps all other timeouts, except this one.
      SolrConnectorInterface::QUERY_TIMEOUT => $options['timeout'],
    ];
    $options = array_merge(
      $timeout_config,
      [
        'collection' => NULL,
        'leader' => FALSE,
      ],
      $options
    );

    parent::__construct($options);
  }

  /**
   * Fetch the Core Path, without the base uri.
   *
   * @return string
   *   Get the path and the core only w/o endpoint basepath.
   */
  public function getCorePath(): string {
    return vsprintf(
        '/%s/%s/',
        [
          $this->getPath(),
          $this->getCore(),
        ]
      );
  }

  /**
   * Get the V1 base url for all requests.
   *
   * @return string
   *   Get the base URI for the Endpoint plus path and the core vars.
   *
   * @throws \Solarium\Exception\UnexpectedValueException
   */
  public function getCoreBaseUri(): string {
    return $this->getBaseUri();
  }

  /**
   * Base URI for Endpoint.
   *
   * @return string
   *   Base URL with scheme and port.
   */
  public function getBaseUri(): string {
    return vsprintf(
      '%s://%s:%d/%s/%s/',
      [
        $this->getScheme(),
        $this->getHost(),
        $this->getPort(),
        $this->getPath(),
        $this->getCore(),
      ]
    );
  }

  /**
   * Get the base url for all V1 API requests.
   *
   * @return string
   *   Base v1 URI for the endpoint.
   *
   * @throws \Solarium\Exception\UnexpectedValueException
   */
  public function getV1BaseUri(): string {
    return vsprintf(
      '%s://%s:%d/%s/',
      [
        $this->getScheme(),
        $this->getHost(),
        $this->getPort(),
        $this->getPath(),
      ]
    );
  }

  /**
   * Get the base url for all V2 API requests.
   *
   * @return string
   *   V2 base URI for the endpoint.
   *
   * @throws \Solarium\Exception\UnexpectedValueException
   */
  public function getV2BaseUri(): string {
    return $this->getBaseUri() . '/api/';
  }

  /**
   * Get the server uri, required for non core/collection specific requests.
   *
   * @return string
   *   Base URI for the endpoint.
   */
  public function getServerUri(): string {
    return $this->getBaseUri();
  }

  /**
   * Get the name of this endpoint.
   *
   * @return string|null
   *   Always use the default name.
   */
  public function getKey(): ?string {
    return self::DEFAULT_NAME;
  }

}
