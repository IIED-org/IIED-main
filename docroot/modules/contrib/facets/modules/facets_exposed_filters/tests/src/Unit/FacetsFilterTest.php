<?php

declare(strict_types=1);

namespace Drupal\Tests\facets_exposed_filters\Unit;

use Drupal\facets\FacetInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter;
use Drupal\Tests\UnitTestCase;

/**
 * Tests exposed-filter value handling for facet results.
 *
 * @group facets
 */
final class FacetsFilterTest extends UnitTestCase {

  /**
   * Tests that missing results use the encoded missing-filter syntax.
   */
  public function testMissingResultUsesEncodedExposedValue(): void {
    $filter = (new \ReflectionClass(FacetsFilter::class))->newInstanceWithoutConstructor();

    $facet = $this->createMock(FacetInterface::class);
    $result = $this->createMock(ResultInterface::class);
    $url_processor = new class() {

      /**
       * Returns the query-string delimiter.
       */
      public function getDelimiter(): string {
        return ',';
      }

    };
    $url_processor_handler = new class($url_processor) {

      /**
       * Creates the handler stub.
       *
       * @param object $processor
       *   The URL processor stub.
       */
      public function __construct(
        private readonly object $processor,
      ) {}

      /**
       * Returns the URL processor.
       */
      public function getProcessor(): object {
        return $this->processor;
      }

    };

    $facet
      ->method('getProcessors')
      ->willReturn(['url_processor_handler' => $url_processor_handler]);
    $result
      ->method('isMissing')
      ->willReturn(TRUE);
    $result
      ->method('getMissingFilters')
      ->willReturn(['apple', 'pear']);
    $result
      ->method('getRawValue')
      ->willReturn('!');

    $value = $this->invokePrivateMethod($filter, 'getExposedOptionValue', [$facet, $result]);

    self::assertSame('!(apple,pear)', $value);
  }

  /**
   * Tests that an active missing token is preserved for checkbox matching.
   */
  public function testActiveMissingResultKeepsSubmittedToken(): void {
    $filter = (new \ReflectionClass(FacetsFilter::class))->newInstanceWithoutConstructor();

    $facet = $this->createMock(FacetInterface::class);
    $result = $this->createMock(ResultInterface::class);

    $facet
      ->method('getActiveItems')
      ->willReturn(['!(apple,pear)']);
    $facet
      ->method('getProcessors')
      ->willReturn([]);
    $result
      ->method('isMissing')
      ->willReturn(TRUE);
    $result
      ->method('getMissingFilters')
      ->willReturn(['apple', 'pear']);
    $result
      ->method('getRawValue')
      ->willReturn('!');

    $value = $this->invokePrivateMethod($filter, 'getExposedOptionValue', [$facet, $result]);

    self::assertSame('!(apple,pear)', $value);
  }

  /**
   * Invokes a private method on the subject.
   *
   * @param object $subject
   *   The subject instance.
   * @param string $method_name
   *   The private method name.
   * @param array<int, mixed> $arguments
   *   Method arguments.
   *
   * @return mixed
   *   The method result.
   */
  private function invokePrivateMethod(object $subject, string $method_name, array $arguments = []): mixed {
    $reflection = new \ReflectionObject($subject);
    $method = $reflection->getMethod($method_name);
    $method->setAccessible(TRUE);

    return $method->invokeArgs($subject, $arguments);
  }

}
