<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\field\Entity\FieldConfig;
use Drupal\name\Hook\UserHooks;
use Drupal\name\Service\AdditionalComponentInterface;
use Drupal\name\Service\NameFormatterInterface;
use Drupal\name\Service\UserRealnamePreloadInterface;

/**
 * @coversDefaultClass \Drupal\name\Hook\UserHooks
 *
 * @group name
 */
final class UserHooksTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    drupal_static_reset('name_user_realname_cache');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    drupal_static_reset('name_user_realname_cache');
    parent::tearDown();
  }

  /**
   * @covers ::userFormatNameAlter
   */
  public function testUserFormatNameAlterReturnsEarlyForAnonymous(): void {
    $preload = $this->createMock(UserRealnamePreloadInterface::class);
    $preload->expects($this->never())->method('preload');

    $formatter = $this->createMock(NameFormatterInterface::class);
    $formatter->expects($this->never())->method('format');

    $additional = $this->createMock(AdditionalComponentInterface::class);
    $additional->expects($this->never())->method('getAdditionalComponent');

    $hooks = new UserHooks(
      $this->createMock(ConfigFactoryInterface::class),
      $formatter,
      $additional,
      $preload,
    );

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAnonymous')->willReturn(TRUE);
    $account->expects($this->never())->method('id');

    $name = 'Anonymous Original';
    $hooks->userFormatNameAlter($name, $account);

    $this->assertSame('Anonymous Original', $name);
  }

  /**
   * @covers ::userLoad
   */
  public function testUserLoadCopiesCachedRealnameAndContinues(): void {
    $field = $this->createMock(FieldConfig::class);
    $field->expects($this->never())->method('getName');
    $field->expects($this->never())->method('getSetting');

    $additional = $this->createMock(AdditionalComponentInterface::class);
    $additional->expects($this->never())->method('getAdditionalComponent');

    $formatter = $this->createMock(NameFormatterInterface::class);
    $formatter->expects($this->never())->method('format');

    $hooks = new UserHooks(
      $this->createMock(ConfigFactoryInterface::class),
      $formatter,
      $additional,
      $this->createMock(UserRealnamePreloadInterface::class),
    );
    $this->seedPreferredField($hooks, $field);

    $cache = &drupal_static('name_user_realname_cache', []);
    $cache[42] = 'Cached Name';

    $account = new class() {

      /**
       * The account UID exposed via id().
       */
      public int $uid = 42;

      /**
       * The `realname` property the hook is expected to populate.
       */
      public ?string $realname = NULL;

      /**
       * Returns the account UID.
       */
      public function id(): int {
        return $this->uid;
      }

      /**
       * Fails the test if the field check is evaluated.
       */
      public function hasField(string $name): bool {
        throw new \LogicException('Cached branch must not evaluate hasField().');
      }

      /**
       * Fails the test if the field accessor is evaluated.
       */
      public function get(string $name): FieldItemListInterface {
        throw new \LogicException('Cached branch must not evaluate get().');
      }

    };

    $users = [42 => $account];
    $hooks->userLoad($users);

    $this->assertSame('Cached Name', $account->realname);
  }

  /**
   * @covers ::userLoad
   */
  public function testUserLoadAppliesAdditionalComponentsBeforeFormatting(): void {
    $field = $this->createMock(FieldConfig::class);
    $field->method('getName')->willReturn('field_name');
    $field->method('getSetting')->willReturnMap([
      ['preferred_field_reference', 'field_preferred'],
      ['preferred_field_reference_separator', ', '],
      ['alternative_field_reference', 'field_alt'],
      ['alternative_field_reference_separator', ' / '],
      ['override_format', 'default'],
    ]);

    $additional = $this->createMock(AdditionalComponentInterface::class);
    $additional->expects($this->exactly(2))
      ->method('getAdditionalComponent')
      ->willReturnCallback(
        static function (FieldItemListInterface $items, $key_value, $sep_value): string {
          return match ($key_value) {
            'field_preferred' => 'PreferredValue',
            'field_alt'       => 'AlternativeValue',
            default           => '',
          };
        },
      );

    $name_item = $this->createMock(FieldItemInterface::class);
    $name_item->method('getValue')->willReturn([
      'given'  => 'Base',
      'family' => 'Name',
    ]);

    $list = $this->createMock(FieldItemListInterface::class);
    $list->method('isEmpty')->willReturn(FALSE);
    $list->method('get')->with(0)->willReturn($name_item);

    $formatter = $this->createMock(NameFormatterInterface::class);
    $formatter->expects($this->once())
      ->method('format')
      ->with(
        [
          'given'       => 'Base',
          'family'      => 'Name',
          'preferred'   => 'PreferredValue',
          'alternative' => 'AlternativeValue',
        ],
        'default',
      )
      ->willReturn('Preferred Name');

    $hooks = new UserHooks(
      $this->createMock(ConfigFactoryInterface::class),
      $formatter,
      $additional,
      $this->createMock(UserRealnamePreloadInterface::class),
    );
    $this->seedPreferredField($hooks, $field);

    $account = new class($list) {

      /**
       * The `realname` property the hook is expected to populate.
       */
      public ?string $realname = NULL;

      /**
       * The account UID exposed via id().
       */
      public int $uid = 7;

      /**
       * Captures the mocked name field list returned by get().
       */
      public function __construct(private readonly FieldItemListInterface $list) {}

      /**
       * Returns the account UID.
       */
      public function id(): int {
        return $this->uid;
      }

      /**
       * Reports whether the account exposes the configured preferred field.
       */
      public function hasField(string $name): bool {
        return $name === 'field_name';
      }

      /**
       * Returns the mocked name field item list for any field name.
       */
      public function get(string $name): FieldItemListInterface {
        return $this->list;
      }

    };

    $users = [7 => $account];
    $hooks->userLoad($users);

    $this->assertSame('Preferred Name', (string) $account->realname);

    $cache = &drupal_static('name_user_realname_cache', []);
    $this->assertArrayHasKey(7, $cache);
  }

  /**
   * Seeds the cached preferred field bypassing FieldConfig::loadByName().
   */
  private function seedPreferredField(UserHooks $hooks, FieldConfig $field): void {
    $reflection = new \ReflectionClass(UserHooks::class);
    $reflection->getProperty('preferredField')->setValue($hooks, $field);
    $reflection->getProperty('preferredFieldResolved')->setValue($hooks, TRUE);
  }

}
