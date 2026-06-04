<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Service\AdditionalComponentService;

/**
 * @coversDefaultClass \Drupal\name\Service\AdditionalComponentService
 *
 * @group name
 */
class AdditionalComponentServiceTest extends UnitTestCase {

  /**
   * @covers ::getAdditionalComponent
   */
  public function testEmptyKeyValueReturnsEmptyString(): void {
    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $items = $this->createMock(FieldItemListInterface::class);
    $this->assertSame('', $service->getAdditionalComponent($items, '', ', '));
    $this->assertSame('', $service->getAdditionalComponent($items, 0, ', '));
  }

  /**
   * @covers ::getAdditionalComponent
   * @covers ::resolveSelf
   */
  public function testSelfReturnsParentLabel(): void {
    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->expects($this->once())
      ->method('label')
      ->willReturn('Node title');

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $this->assertSame('Node title', $service->getAdditionalComponent($items, '_self', ''));
  }

  /**
   * @covers ::getAdditionalComponent
   * @covers ::resolveSelf
   */
  public function testSelfWithoutLabelReturnsEmpty(): void {
    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->method('label')->willReturn('');

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $this->assertSame('', $service->getAdditionalComponent($items, '_self', ''));
  }

  /**
   * @covers ::getAdditionalComponent
   */
  public function testNonFieldableParentReturnsEmpty(): void {
    $parent = $this->createMock(EntityInterface::class);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $this->assertSame('', $service->getAdditionalComponent($items, 'field_ref', ''));
  }

  /**
   * @covers ::getAdditionalComponent
   * @covers ::resolveSelfProperty
   */
  public function testSelfPropertyReturnsFieldValue(): void {
    $fieldItem = new \stdClass();
    $fieldItem->value = 'Nickname';

    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->expects($this->once())
      ->method('get')
      ->with('field_nick')
      ->willReturn($fieldItem);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $this->assertSame(
      'Nickname',
      $service->getAdditionalComponent($items, '_self_property_field_nick', ''),
    );
  }

  /**
   * @covers ::getAdditionalComponent
   * @covers ::resolveSelfProperty
   */
  public function testSelfPropertyEmptyValueReturnsEmpty(): void {
    $fieldItem = new \stdClass();
    $fieldItem->value = '';

    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->method('get')->with('field_nick')->willReturn($fieldItem);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $this->assertSame('', $service->getAdditionalComponent($items, '_self_property_field_nick', ''));
  }

  /**
   * @covers ::getAdditionalComponent
   * @covers ::resolveSelfProperty
   */
  public function testSelfPropertyInvalidArgumentReturnsEmpty(): void {
    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->expects($this->once())
      ->method('get')
      ->with('missing')
      ->willThrowException(new \InvalidArgumentException());

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $this->assertSame('', $service->getAdditionalComponent($items, '_self_property_missing', ''));
  }

  /**
   * @covers ::collectEntityReferenceLabels
   * @covers ::getAdditionalComponent
   * @covers ::resolveField
   */
  public function testEntityReferenceFieldJoinsLabels(): void {
    $ref1 = $this->createMock(EntityInterface::class);
    $ref1->method('access')->with('view')->willReturn(TRUE);
    $ref1->method('label')->willReturn('One');

    $ref2 = $this->createMock(EntityInterface::class);
    $ref2->method('access')->with('view')->willReturn(TRUE);
    $ref2->method('label')->willReturn('Two');

    $erItem1 = new \stdClass();
    $erItem1->entity = $ref1;
    $erItem2 = new \stdClass();
    $erItem2->entity = $ref2;

    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('entity_reference');

    $targetList = $this->getMockBuilder(FieldItemList::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['isEmpty', 'access', 'getFieldDefinition', 'getIterator'])
      ->getMock();
    $targetList->method('isEmpty')->willReturn(FALSE);
    $targetList->method('access')->with('view')->willReturn(TRUE);
    $targetList->method('getFieldDefinition')->willReturn($fieldDef);
    $targetList->method('getIterator')->willReturn(new \ArrayIterator([$erItem1, $erItem2]));

    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->method('hasField')->with('field_refs')->willReturn(TRUE);
    $parent->method('get')->with('field_refs')->willReturn($targetList);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    // Separator is trim()med; leading/trailing spaces are removed.
    $this->assertSame('One|Two', $service->getAdditionalComponent($items, 'field_refs', ' | '));
  }

  /**
   * @covers ::getAdditionalComponent
   * @covers ::resolveField
   */
  public function testEntityReferenceNoViewAccessReturnsEmpty(): void {
    $targetList = $this->createMock(FieldItemListInterface::class);
    $targetList->method('isEmpty')->willReturn(FALSE);
    $targetList->method('access')->with('view')->willReturn(FALSE);

    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->method('hasField')->with('field_refs')->willReturn(TRUE);
    $parent->method('get')->with('field_refs')->willReturn($targetList);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $this->assertSame('', $service->getAdditionalComponent($items, 'field_refs', ''));
  }

  /**
   * @covers ::getAdditionalComponent
   * @covers ::resolveField
   */
  public function testFieldNotFoundReturnsEmpty(): void {
    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->method('hasField')->with('field_missing')->willReturn(FALSE);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $this->assertSame('', $service->getAdditionalComponent($items, 'field_missing', ''));
  }

  /**
   * @covers ::collectEntityReferenceLabels
   * @covers ::getAdditionalComponent
   * @covers ::resolveField
   */
  public function testEntityReferenceItemWithNoEntitySkipped(): void {
    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('entity_reference');

    $targetList = $this->getMockBuilder(FieldItemList::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['isEmpty', 'access', 'getFieldDefinition', 'getIterator'])
      ->getMock();
    $targetList->method('isEmpty')->willReturn(FALSE);
    $targetList->method('access')->with('view')->willReturn(TRUE);
    $targetList->method('getFieldDefinition')->willReturn($fieldDef);
    $targetList->method('getIterator')->willReturn(new \ArrayIterator([new \stdClass()]));

    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->method('hasField')->with('field_refs')->willReturn(TRUE);
    $parent->method('get')->with('field_refs')->willReturn($targetList);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $this->assertSame('', $service->getAdditionalComponent($items, 'field_refs', ''));
  }

  /**
   * @covers ::collectEntityReferenceLabels
   * @covers ::getAdditionalComponent
   * @covers ::resolveField
   */
  public function testEntityReferenceItemAccessDeniedSkipped(): void {
    $ref = $this->createMock(EntityInterface::class);
    $ref->method('access')->with('view')->willReturn(FALSE);
    $ref->expects($this->never())->method('label');

    $erItem = new \stdClass();
    $erItem->entity = $ref;

    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('entity_reference');

    $targetList = $this->getMockBuilder(FieldItemList::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['isEmpty', 'access', 'getFieldDefinition', 'getIterator'])
      ->getMock();
    $targetList->method('isEmpty')->willReturn(FALSE);
    $targetList->method('access')->with('view')->willReturn(TRUE);
    $targetList->method('getFieldDefinition')->willReturn($fieldDef);
    $targetList->method('getIterator')->willReturn(new \ArrayIterator([$erItem]));

    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->method('hasField')->with('field_refs')->willReturn(TRUE);
    $parent->method('get')->with('field_refs')->willReturn($targetList);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $this->assertSame('', $service->getAdditionalComponent($items, 'field_refs', ''));
  }

  /**
   * @covers ::collectEntityReferenceLabels
   * @covers ::getAdditionalComponent
   * @covers ::resolveField
   */
  public function testEntityReferenceItemNullLabelSkipped(): void {
    $ref = $this->createMock(EntityInterface::class);
    $ref->method('access')->with('view')->willReturn(TRUE);
    $ref->method('label')->willReturn(NULL);

    $erItem = new \stdClass();
    $erItem->entity = $ref;

    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('entity_reference');

    $targetList = $this->getMockBuilder(FieldItemList::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['isEmpty', 'access', 'getFieldDefinition', 'getIterator'])
      ->getMock();
    $targetList->method('isEmpty')->willReturn(FALSE);
    $targetList->method('access')->with('view')->willReturn(TRUE);
    $targetList->method('getFieldDefinition')->willReturn($fieldDef);
    $targetList->method('getIterator')->willReturn(new \ArrayIterator([$erItem]));

    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->method('hasField')->with('field_refs')->willReturn(TRUE);
    $parent->method('get')->with('field_refs')->willReturn($targetList);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $this->assertSame('', $service->getAdditionalComponent($items, 'field_refs', ''));
  }

  /**
   * @covers ::collectRenderedValues
   * @covers ::getAdditionalComponent
   * @covers ::resolveField
   */
  public function testNonEntityReferenceUsesViewBuilderAndRenderer(): void {
    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('text');

    $scalarItem = new \stdClass();

    $targetList = $this->getMockBuilder(FieldItemList::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['isEmpty', 'access', 'getFieldDefinition', 'getIterator'])
      ->getMock();
    $targetList->method('isEmpty')->willReturn(FALSE);
    $targetList->method('access')->with('view')->willReturn(TRUE);
    $targetList->method('getFieldDefinition')->willReturn($fieldDef);
    $targetList->method('getIterator')->willReturn(new \ArrayIterator([$scalarItem]));

    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->method('hasField')->with('field_body')->willReturn(TRUE);
    $parent->method('get')->with('field_body')->willReturn($targetList);
    $parent->method('getEntityTypeId')->willReturn('node');

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $viewBuilder = new class() {

      /**
       * Returns a minimal render array for the field item.
       *
       * @return array<string, string>
       *   Render array with markup.
       */
      public function viewFieldItem(object $item, array $options): array {
        return ['#markup' => '<p>Hello &amp; Co</p>'];
      }

    };

    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->expects($this->once())
      ->method('getViewBuilder')
      ->with('node')
      ->willReturn($viewBuilder);

    $renderer = $this->createMock(RendererInterface::class);
    $renderer->expects($this->once())
      ->method('render')
      ->willReturn('<p>Hello &amp; Co</p>');

    $service = new AdditionalComponentService($etm, $renderer);
    $this->assertSame('Hello & Co', $service->getAdditionalComponent($items, 'field_body', ' '));
  }

  /**
   * @covers ::collectRenderedValues
   * @covers ::getAdditionalComponent
   * @covers ::resolveField
   */
  public function testRenderExceptionReturnsEmpty(): void {
    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('text');

    $targetList = $this->getMockBuilder(FieldItemList::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['isEmpty', 'access', 'getFieldDefinition', 'getIterator'])
      ->getMock();
    $targetList->method('isEmpty')->willReturn(FALSE);
    $targetList->method('access')->with('view')->willReturn(TRUE);
    $targetList->method('getFieldDefinition')->willReturn($fieldDef);
    $targetList->method('getIterator')->willReturn(new \ArrayIterator([new \stdClass()]));

    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->method('hasField')->willReturn(TRUE);
    $parent->method('get')->willReturn($targetList);
    $parent->method('getEntityTypeId')->willReturn('node');

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $viewBuilder = new class() {

      /**
       * Returns a minimal render array for the field item.
       *
       * @return array<string, string>
       *   Render array with markup.
       */
      public function viewFieldItem(object $item, array $options): array {
        return ['#markup' => 'x'];
      }

    };

    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->method('getViewBuilder')->willReturn($viewBuilder);

    $renderer = $this->createMock(RendererInterface::class);
    $renderer->method('render')->willThrowException(new \RuntimeException('boom'));

    $service = new AdditionalComponentService($etm, $renderer);
    $this->assertSame('', $service->getAdditionalComponent($items, 'field_x', ''));
  }

  /**
   * @covers ::collectRenderedValues
   * @covers ::getAdditionalComponent
   * @covers ::resolveField
   */
  public function testRenderedValueEmptyAfterStrippingTagsSkipped(): void {
    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('text');

    $targetList = $this->getMockBuilder(FieldItemList::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['isEmpty', 'access', 'getFieldDefinition', 'getIterator'])
      ->getMock();
    $targetList->method('isEmpty')->willReturn(FALSE);
    $targetList->method('access')->with('view')->willReturn(TRUE);
    $targetList->method('getFieldDefinition')->willReturn($fieldDef);
    $targetList->method('getIterator')->willReturn(new \ArrayIterator([new \stdClass()]));

    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->method('hasField')->with('field_body')->willReturn(TRUE);
    $parent->method('get')->with('field_body')->willReturn($targetList);
    $parent->method('getEntityTypeId')->willReturn('node');

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $viewBuilder = new class() {

      /**
       * Returns a minimal render array for the field item.
       *
       * @return array<string, string>
       *   Render array with markup.
       */
      public function viewFieldItem(object $item, array $options): array {
        return ['#markup' => '<span></span>'];
      }

    };

    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->expects($this->once())
      ->method('getViewBuilder')
      ->with('node')
      ->willReturn($viewBuilder);

    $renderer = $this->createMock(RendererInterface::class);
    $renderer->expects($this->once())
      ->method('render')
      ->willReturn('<span></span>');

    $service = new AdditionalComponentService($etm, $renderer);
    $this->assertSame('', $service->getAdditionalComponent($items, 'field_body', ' '));
  }

  /**
   * @covers ::collectEntityReferenceLabels
   * @covers ::getAdditionalComponent
   * @covers ::resolveField
   */
  public function testSeparatorStripsTagsAndDecodesEntities(): void {
    $ref = $this->createMock(EntityInterface::class);
    $ref->method('access')->with('view')->willReturn(TRUE);
    $ref->method('label')->willReturn('A');

    $erItem = new \stdClass();
    $erItem->entity = $ref;

    $fieldDef = $this->createMock(FieldDefinitionInterface::class);
    $fieldDef->method('getType')->willReturn('entity_reference');

    $targetList = $this->getMockBuilder(FieldItemList::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['isEmpty', 'access', 'getFieldDefinition', 'getIterator'])
      ->getMock();
    $targetList->method('isEmpty')->willReturn(FALSE);
    $targetList->method('access')->with('view')->willReturn(TRUE);
    $targetList->method('getFieldDefinition')->willReturn($fieldDef);
    $targetList->method('getIterator')->willReturn(new \ArrayIterator([$erItem]));

    $parent = $this->createMock(ContentEntityInterface::class);
    $parent->method('hasField')->willReturn(TRUE);
    $parent->method('get')->willReturn($targetList);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getEntity')->willReturn($parent);

    $service = new AdditionalComponentService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(RendererInterface::class),
    );
    $this->assertSame('A', $service->getAdditionalComponent($items, 'field_r', '<b> &amp; </b>'));
  }

}
