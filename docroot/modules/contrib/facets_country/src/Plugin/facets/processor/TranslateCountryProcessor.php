<?php

declare(strict_types = 1);

namespace Drupal\facets_country\Plugin\facets\processor;

use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Class TranslateCountryProcessor.
 *
 * @FacetsProcessor(
 *   id = "translate_country",
 *   label = @Translation("facets_country.label.facets_country"),
 *   description = @Translation("facets_country.description.facets_country"),
 *   stages = {
 *     "build" = 5
 *   }
 * )
 */
class TranslateCountryProcessor extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * TranslateCountryProcessor constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   The country manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CountryManagerInterface $country_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->countryManager = $country_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('country_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $dataDefinition */
    $dataDefinition = $facet->getDataDefinition();

    $property = NULL;
    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $definition */
    foreach ($dataDefinition->getPropertyDefinitions() as $key => $definition) {
    //   if ($dataDefinition->getDataType() === 'field_item::country') {
        $property = $key;
        break;
    //  }
    }

    if ($property === NULL) {
      throw new InvalidProcessorException((string) $this->t('facets_country.message.invalid_processor'));
    }

    $countryList = $this->countryManager->getList();
    foreach ($results as $index => $result) {
      $code = $result->getRawValue();
      $results[$index]->setDisplayValue($countryList[$code] ?? $code);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    /** @var \Drupal\Core\TypedData\DataDefinitionInterface $dataDefinition */
    $dataDefinition = $facet->getDataDefinition();

    //return $dataDefinition->getDataType() === 'field_item:country';
    return TRUE;
   }

}
