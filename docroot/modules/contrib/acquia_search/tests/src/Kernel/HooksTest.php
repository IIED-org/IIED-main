<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Kernel;

use Drupal\Core\Url;
use Drupal\search_api\Entity\Server;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests hooks defined in the .module file.
 *
 * @group acquia_search
 */
final class HooksTest extends AcquiaSearchTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'acquia_search_defaults',
  ];

  /**
   * Tests implementation of hook_entity_operation_alter().
   */
  public function testEntityOperationsAlter(): void {
    Server::create([
      'id' => 'sample_server',
      'name' => 'Sample server',
      'description' => 'WebTest server description',
      'backend' => 'search_api_solr',
      'backend_config' => [
        'connector' => 'standard',
      ],
    ])->save();
    $this->container->get('current_user')
      ->setAccount($this->createUser(['administer search_api']));
    $request = Request::create(
      Url::fromRoute('search_api.overview')->toString()
    );
    $this->doRequest($request);

    $expected = [
      'sample_server' => TRUE,
      'acquia_search_server' => FALSE,
      'acquia_search_index' => FALSE,
    ];
    foreach ($expected as $entity_id => $has_delete) {
      // Verify row actually exists before asserting dropbutton links.
      $row = $this->xpath('//tr[@title=:title]', [':title' => "ID: $entity_id"]);
      self::assertNotFalse($row);
      self::assertCount(1, $row);
      $links = $this->xpath(
        '//tr[@title=:title]//a[normalize-space(text())=:label]',
        [
          ':title' => "ID: $entity_id",
          ':label' => 'Delete',
        ]
      );
      self::assertCount($has_delete ? 1 : 0, $links);
    }
  }

}
