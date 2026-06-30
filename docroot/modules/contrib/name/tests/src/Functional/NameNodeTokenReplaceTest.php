<?php

namespace Drupal\Tests\name\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests text replacements in content to check node name token replacement.
 *
 * @group name
 */
class NameNodeTokenReplaceTest extends NameTestBase {

  use NameTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'filter', 'token'];

  /**
   * The name formatter.
   *
   * @var \Drupal\name\NameFormatterInterface
   */
  protected $formatter;

  /**
   * The interface language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $interfaceLanguage;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->interfaceLanguage = \Drupal::languageManager()->getCurrentLanguage();
    $this->tokenService = \Drupal::token();

    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();

    // Create body field storage if it doesn't exist.
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    if (!$field_storage) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => 'body',
        'entity_type' => 'node',
        'type' => 'text_with_summary',
      ]);
      $field_storage->save();
    }

    // Create body field configuration for the article bundle.
    $field = FieldConfig::loadByName('node', 'article', 'body');
    if (!$field) {
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => 'article',
        'label' => 'Body',
        'settings' => [
          'display_summary' => TRUE,
          'allowed_formats' => [],
        ],
      ]);
      $field->save();
    }

    $this->createNameField('field_name', 'node', 'article');
    $this->createNameField('field_multi', 'node', 'article', ['cardinality' => 2]);
    $this->createNameField('field_realname', 'user', 'user');
  }

  /**
   * Creates a node, then tests the tokens generated from it.
   */
  public function testNodeTokenReplacement() {
    $this->formatter = \Drupal::service('name.formatter');

    \Drupal::configFactory()
      ->getEditable('name.settings')
      ->set('user_preferred', 'field_realname')
      ->save();

    // Create a user and a node with populated name fields.
    $account = $this->createUser();
    $account->set('field_realname', [
      'title' => 'UUtt',
      'given' => 'UUgg',
      'middle' => 'UUmm UUnn',
      'family' => 'UUff',
      'generational' => 'Jr.',
      'credentials' => 'UUCreds, UUMoreCreds',
    ])->save();

    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'article',
      'tnid' => 0,
      'uid' => $account->id(),
      'title' => '<blink>Blinking Text</blink>',
      'body' => [
        [
          'value' => 'Regular NODE body for the test.',
          'summary' => 'Fancy NODE summary.',
          'format' => 'plain_text',
        ],
      ],
      'field_name' => [
        [
          'title' => 'Ttt',
          'given' => 'Ggg',
          'middle' => 'Mmm Nnnn',
          'family' => 'Fff',
          'generational' => 'Sr.',
          'credentials' => 'Creds, MoreCreds',
        ],
      ],
      'field_multi' => [
        [
          'title' => '',
          'given' => 'Alpha',
          'middle' => '',
          'family' => 'One',
          'generational' => '',
          'credentials' => '',
        ],
        [
          'title' => '',
          'given' => 'Beta',
          'middle' => '',
          'family' => 'Two',
          'generational' => '',
          'credentials' => '',
        ],
      ],
    ]);
    $node->save();

    /** @var \Drupal\name\Plugin\Field\FieldType\NameItem $item */
    $item = $node->get('field_name')->get(0);
    $components = $item->filteredArray();

    // Generate and test tokens.
    $tests = [];
    $tests['[node:field_name]'] = $this->formatter->format($components);
    $tests['[node:field_name:title]'] = $components['title'];
    $tests['[node:field_name:given]'] = $components['given'];
    $tests['[node:field_name:middle]'] = $components['middle'];
    $tests['[node:field_name:family]'] = $components['family'];
    $tests['[node:field_name:generational]'] = $components['generational'];
    $tests['[node:field_name:credentials]'] = $components['credentials'];
    $tests['[node:field_name:formatted:given]'] = $this->formatter->format($components, 'given');
    $tests['[node:formatted_field_name:given]'] = $this->formatter->format($components, 'given');
    $tests['[node:field_name:formatted:nonexistent_format__xyz]'] = $this->formatter->format($components, 'nonexistent_format__xyz');

    /** @var \Drupal\name\Plugin\Field\FieldType\NameItem $multi_item */
    $multi_item = $node->get('field_multi')->get(1);
    $multi_components = $multi_item->filteredArray();
    $tests['[node:field_multi:1:formatted:given]'] = $this->formatter->format($multi_components, 'given');
    $tests['[node:field_multi:9:formatted:given]'] = '';
    /** @var \Drupal\name\Plugin\Field\FieldType\NameItem $item */
    $item = $account->get('field_realname')->get(0);
    $components = $item->filteredArray();

    $tests['[node:author:name]'] = $account->getAccountName();
    $tests['[node:author:account-name]'] = $account->getAccountName();
    $tests['[node:author:display-name]'] = $account->getDisplayName();
    $tests['[node:author:field_realname]'] = $this->formatter->format($components);
    $tests['[node:author:field_realname:family]'] = $components['family'];
    $tests['[node:author:field_realname:formatted:given]'] = $this->formatter->format($components, 'given');

    // @todo consider current user tests, "[current-user:display-name]".
    foreach ($tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $this->tokenService->replace($input, ['node' => $node], ['langcode' => $this->interfaceLanguage->getId()], $bubbleable_metadata);
      $this->assertEquals($output, (string) $expected, new FormattableMarkup('Node token %token replaced with %expected, got %actual.', [
        '%token' => $input,
        '%expected' => $expected,
        '%actual' => $output,
      ]));
      // @todo caching tests.
      // @see NodeTokenReplaceTest.
      // $this->assertEquals($bubbleable_metadata, $metadata_tests[$input]);
    }

    $user_tests['[user:field_realname:formatted:given]'] = $this->formatter->format($components, 'given');
    $user_tests['[user:formatted_field_realname:given]'] = $this->formatter->format($components, 'given');
    foreach ($user_tests as $input => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $this->tokenService->replace($input, ['user' => $account], ['langcode' => $this->interfaceLanguage->getId()], $bubbleable_metadata);
      $this->assertEquals($output, (string) $expected, new FormattableMarkup('User token %token replaced with %expected, got %actual.', [
        '%token' => $input,
        '%expected' => $expected,
        '%actual' => $output,
      ]));
    }
  }

}
