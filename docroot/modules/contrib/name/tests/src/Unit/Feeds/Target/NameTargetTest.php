<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Feeds name target.
 *
 * @group name
 * @covers \Drupal\name\Feeds\Target\NameTarget
 */
final class NameTargetTest extends UnitTestCase {

  /**
   * @covers \Drupal\name\Feeds\Target\NameTarget::prepareTarget
   */
  public function testPrepareTargetAddsCoreNameProperties(): void {
    if (!class_exists('\Drupal\feeds\Plugin\Type\Target\FieldTargetBase')) {
      $this->markTestSkipped('Feeds is not available in this test environment.');
    }

    $method = new \ReflectionMethod(
      '\Drupal\name\Feeds\Target\NameTarget',
      'prepareTarget',
    );
    $method->setAccessible(TRUE);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('field_name_test');
    $field_definition->method('getLabel')->willReturn('Name test');
    $field_definition->method('getDescription')->willReturn('Name target test');
    $field_definition->method('isRequired')->willReturn(FALSE);
    $field_definition->method('getType')->willReturn('name');

    $target_definition = $method->invoke(NULL, $field_definition);
    $this->assertInstanceOf('\Drupal\feeds\FieldTargetDefinition', $target_definition);

    $definitions = $this->readDefinitionArrays($target_definition);
    foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $key) {
      $this->assertTrue(
        $this->arrayContainsKeyValue($definitions, $key),
        sprintf('Expected property "%s" to be registered.', $key),
      );
    }
  }

  /**
   * Confirms plugin annotation metadata is present.
   *
   * @coversNothing
   */
  public function testPluginAnnotationDeclaresExpectedIdAndFieldType(): void {
    if (!class_exists('\Drupal\feeds\Plugin\Type\Target\FieldTargetBase')) {
      $this->markTestSkipped('Feeds is not available in this test environment.');
    }

    $reflection = new \ReflectionClass('\Drupal\name\Feeds\Target\NameTarget');
    $doc = $reflection->getDocComment();

    $this->assertIsString($doc);
    $this->assertStringContainsString('id = "name"', $doc);
    $this->assertStringContainsString('"name"', $doc);
  }

  /**
   * Reads all array-like definition data from an object via reflection.
   *
   * @param object $object
   *   Object to inspect.
   *
   * @return array<string, mixed>
   *   Flattened arrays from object properties.
   */
  private function readDefinitionArrays(object $object): array {
    $values = [];
    $reflection = new \ReflectionObject($object);
    do {
      foreach ($reflection->getProperties() as $property) {
        $property->setAccessible(TRUE);
        $value = $property->getValue($object);
        if (is_array($value)) {
          $values[$property->getName()] = $value;
        }
      }
      $reflection = $reflection->getParentClass();
    } while ($reflection instanceof \ReflectionClass);

    return $values;
  }

  /**
   * Determines whether any nested array key or value matches a target.
   *
   * @param array<mixed> $haystack
   *   Array tree to search.
   * @param string $needle
   *   Key/value to find.
   *
   * @return bool
   *   TRUE when found.
   */
  private function arrayContainsKeyValue(array $haystack, string $needle): bool {
    foreach ($haystack as $key => $value) {
      if ((string) $key === $needle || (is_string($value) && $value === $needle)) {
        return TRUE;
      }
      if (is_array($value) && $this->arrayContainsKeyValue($value, $needle)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
