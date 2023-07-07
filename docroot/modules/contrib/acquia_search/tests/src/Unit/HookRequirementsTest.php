<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_search\Plugin\search_api\backend\AcquiaSearchSolrBackend;
use Drupal\acquia_search\Plugin\SolrConnector\SearchApiSolrAcquiaConnector;
use Drupal\acquia_search\PreferredCoreService;
use Drupal\acquia_search\PreferredCoreServiceFactory;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\ServerInterface;

/**
 * @group acquia_search
 */
final class HookRequirementsTest extends AcquiaSearchTestCase {

  /**
   * @dataProvider requirementsData
   */
  public function testRequirements(bool $is_read_only, array $servers, bool $preferred_core, array $expected): void {
    require_once $this->root . '/core/includes/install.inc';
    require_once __DIR__ . '/../../../acquia_search.install';

    $this->createMockContainer(function () use ($is_read_only, $servers, $preferred_core) {
      $acquia_search_settings = $this->createMock(Config::class);
      $acquia_search_settings
        ->method('get')
        ->willReturnMap([
          ['read_only', $is_read_only],
          ['override_search_core', ''],
        ]);

      $module_handler = $this->createMock(ModuleHandlerInterface::class);
      $module_handler->expects($this->once())
        ->method('moduleExists')
        ->willReturn('acquia_connector');

      $entity_type_repository = $this->createMock(EntityTypeRepositoryInterface::class);
      $entity_type_repository
        ->method('getEntityTypeFromClass')
        ->with(Server::class)
        ->willReturn('search_api_server');

      $server_storage = $this->createMock(EntityStorageInterface::class);
      $server_storage->method('loadMultiple')->willReturn($servers);

      $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
      $entity_type_manager
        ->method('getStorage')
        ->willReturnMap([
          ['search_api_server', $server_storage],
        ]);

      // @todo This needs to be expanded
      $config_factory = $this->createMock(ConfigFactoryInterface::class);
      $config_factory
        ->method('get')
        ->willReturnMap([
          ['acquia_search.settings', $acquia_search_settings],
          ['acquia_search_solr.settings', $this->createMock(Config::class)],
        ]);

      $preferred_core_factory = $this->createMock(PreferredCoreServiceFactory::class);
      $acquia_search_preferred_core = $this->createMock(PreferredCoreService::class);
      $acquia_search_preferred_core
        ->method('isPreferredCoreAvailable')
        ->willReturn($preferred_core);
      $preferred_core_factory->method('get')
        ->willReturn($acquia_search_preferred_core);

      return [
        'entity_type.repository' => $entity_type_repository,
        'entity_type.manager' => $entity_type_manager,
        'config.factory' => $config_factory,
        'module_handler' => $module_handler,
        'acquia_search.preferred_core_factory' => $preferred_core_factory,
        'string_translation' => new TranslationManager(new LanguageDefault(['id' => 'en'])),
        'state' => $this->createMock(StateInterface::class),
        'extension.list.module' => $this->createMock(ExtensionList::class),
        'renderer' => $this->createMock(RendererInterface::class),
      ];
    });

    // @todo need to test isPreferredCoreAvailable is TRUE.
    $requirements = acquia_search_requirements('runtime');
    self::assertEquals(
      $expected,
      $requirements
    );
  }

  public function requirementsData() {
    yield 'no read only, no servers' => [
      FALSE,
      [],
      FALSE,
      [
        'acquia_search_ssl' => [
          'title' => new TranslatableMarkup('Acquia Search Solr'),
          'value' => 'Security',
          'severity' => 0,
          'description' => new TranslatableMarkup('The Acquia Search module is using SSL to protect the privacy of your content.'),
        ],
      ],
    ];
    yield 'read only, no servers' => [
      TRUE,
      [],
      FALSE,
      [
        'acquia_search_ssl' => [
          'title' => new TranslatableMarkup('Acquia Search Solr'),
          'value' => 'Security',
          'severity' => 0,
          'description' => new TranslatableMarkup('The Acquia Search module is using SSL to protect the privacy of your content.'),
        ],
        'acquia_search_read_only' => [
          'title' => new TranslatableMarkup('Acquia Search Solr'),
          'value' => new TranslatableMarkup('Read-only warning'),
          'severity' => 1,
          'description' => [
            '#markup' => 'The read-only mode is set in the configuration of the Acquia Search Solr module.',
          ],
        ],
      ],
    ];

    $standard_server = $this->createMock(ServerInterface::class);
    $standard_server->method('id')->willReturn('standard');
    $standard_server->expects($this->once())
      ->method('getBackendConfig')
      ->willReturn([
        'connector' => 'standard',
      ]);
    $standard_server->expects($this->never())
      ->method('getBackend');

    $acquia_server = $this->createMock(ServerInterface::class);
    $acquia_server->method('id')->willReturn('acquia_server');
    $acquia_server
      ->method('getBackendConfig')
      ->willReturn([
        'connector' => 'solr_acquia_connector',
      ]);
    $acquia_server
      ->method('getBackend')
      ->willReturnCallback(function () use ($acquia_server) {
        $connector = $this->createMock(SearchApiSolrAcquiaConnector::class);
        $connector
          ->method('getConfiguration')
          ->willReturn([]);
        $backend = $this->createMock(AcquiaSearchSolrBackend::class);
        $backend
          ->method('getSolrConnector')
          ->willReturn($connector);
        $backend->method('isPreferredCoreAvailable')->willReturn(TRUE);
        $backend->method('getServer')->willReturn($acquia_server);
        return $backend;
      });
    yield 'no read only, no acquia servers' => [
      FALSE,
      ['standard' => $standard_server],
      FALSE,
      [
        'acquia_search_ssl' => [
          'title' => new TranslatableMarkup('Acquia Search Solr'),
          'value' => 'Security',
          'severity' => 0,
          'description' => new TranslatableMarkup('The Acquia Search module is using SSL to protect the privacy of your content.'),
        ],
      ],
    ];
    yield 'no read only, acquia servers' => [
      FALSE,
      ['acquia' => $acquia_server],
      FALSE,
      [
        'acquia_search_ssl' => [
          'title' => new TranslatableMarkup('Acquia Search Solr'),
          'value' => 'Security',
          'severity' => 0,
          'description' => new TranslatableMarkup('The Acquia Search module is using SSL to protect the privacy of your content.'),
        ],
        'acquia_search_status_acquia' => [
          'title' => new TranslatableMarkup('Acquia Search connection status'),
          'severity' => 0,
          'description' => [
            '#markup' => new TranslatableMarkup('Connection managed by Acquia Search Solr module. @list', [
              '@list' => NULL,
            ]),
          ],
        ],
      ],
    ];
    yield 'read only, acquia servers' => [
      TRUE,
      ['acquia' => $acquia_server],
      FALSE,
      [
        'acquia_search_ssl' => [
          'title' => new TranslatableMarkup('Acquia Search Solr'),
          'value' => 'Security',
          'severity' => 0,
          'description' => new TranslatableMarkup('The Acquia Search module is using SSL to protect the privacy of your content.'),
        ],
        'acquia_search_read_only' => [
          'title' => new TranslatableMarkup('Acquia Search Solr'),
          'value' => new TranslatableMarkup('Read-only warning'),
          'severity' => 1,
          'description' => [
            '#markup' => 'The read-only mode is set in the configuration of the Acquia Search Solr module.',
          ],
        ],
        'acquia_search_status_acquia' => [
          'title' => new TranslatableMarkup('Acquia Search connection status'),
          'severity' => 0,
          'description' => [
            '#markup' => new TranslatableMarkup('Connection managed by Acquia Search Solr module. @list', [
              '@list' => NULL,
            ]),
          ],
        ],
      ],
    ];
    yield 'read only, acquia servers, preferred core' => [
      TRUE,
      ['acquia' => $acquia_server],
      TRUE,
      [
        'acquia_search_ssl' => [
          'title' => new TranslatableMarkup('Acquia Search Solr'),
          'value' => 'Security',
          'severity' => 0,
          'description' => new TranslatableMarkup('The Acquia Search module is using SSL to protect the privacy of your content.'),
        ],
        'acquia_search_read_only' => [
          'title' => new TranslatableMarkup('Acquia Search Solr'),
          'value' => new TranslatableMarkup('Read-only warning'),
          'severity' => 1,
          'description' => [
            '#markup' => 'The read-only mode is set in the configuration of the Acquia Search Solr module.',
          ],
        ],
        'acquia_search_status_acquia' => [
          'title' => new TranslatableMarkup('Acquia Search connection status'),
          'severity' => 0,
          'description' => [
            '#markup' => new TranslatableMarkup('Connection managed by Acquia Search Solr module. @list', [
              '@list' => NULL,
            ]),
          ],
        ],
      ],
    ];
  }

  /**
   * Tests deprecated override requirements check.
   *
   * @param string $acquia_search_core
   *   The deprecated `acquia_search.settings` override.
   * @param string $acquia_search_solr_core
   *   The deprecated `acquia_search_solr.settings` override.
   * @param bool $is_deprecated
   *   If the overrides should be considered deprecated.
   *
   * @dataProvider deprecatedCoreOverrideValues
   */
  public function testDeprecatedCoreOverride(string $acquia_search_core, string $acquia_search_solr_core, bool $is_deprecated): void {
    require_once $this->root . '/core/includes/install.inc';
    require_once __DIR__ . '/../../../acquia_search.install';

    $this->createMockContainer(function () use ($acquia_search_core, $acquia_search_solr_core) {
      $acquia_search_settings = $this->createMock(Config::class);
      $acquia_search_settings
        ->method('get')
        ->willReturnMap([
          ['read_only', FALSE],
          ['override_search_core', $acquia_search_core],
        ]);
      $acquia_search_solr_settings = $this->createMock(Config::class);
      $acquia_search_solr_settings
        ->method('get')
        ->with('override_search_core')
        ->willReturn($acquia_search_solr_core);
      $config_factory = $this->createMock(ConfigFactoryInterface::class);
      $config_factory
        ->method('get')
        ->willReturnMap([
          ['acquia_search.settings', $acquia_search_settings],
          ['acquia_search_solr.settings', $acquia_search_solr_settings],
        ]);

      $entity_type_repository = $this->createMock(EntityTypeRepositoryInterface::class);
      $entity_type_repository
        ->method('getEntityTypeFromClass')
        ->with(Server::class)
        ->willReturn('search_api_server');

      $server_storage = $this->createMock(EntityStorageInterface::class);
      $server_storage->method('loadMultiple')->willReturn([]);

      $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
      $entity_type_manager
        ->method('getStorage')
        ->willReturnMap([
          ['search_api_server', $server_storage],
        ]);

      $module_handler = $this->createMock(ModuleHandlerInterface::class);
      $module_handler->expects($this->once())
        ->method('moduleExists')
        ->willReturn('acquia_connector');

      $acquia_search_preferred_core = $this->createMock(PreferredCoreService::class);
      $acquia_search_preferred_core
        ->method('isPreferredCoreAvailable')
        ->willReturn('FOO');

      return [
        'config.factory' => $config_factory,
        'module_handler' => $module_handler,
        'entity_type.repository' => $entity_type_repository,
        'entity_type.manager' => $entity_type_manager,
        'acquia_search.preferred_core_factory' => $acquia_search_preferred_core,
      ];
    });

    $requirements = acquia_search_requirements('runtime');
    self::assertEquals($is_deprecated, isset($requirements['acquia_search_deprecated_config']));
  }

  /**
   * Data for testing deprecated core overrides.
   *
   * @return \Generator
   *   The test data.
   */
  public static function deprecatedCoreOverrideValues() {
    yield 'no deprecated' => [
      '',
      '',
      FALSE,
    ];
    yield 'acquia_search.settings: deprecated' => [
      'OVERRIDE',
      '',
      TRUE,
    ];
    yield 'acquia_search_solr.settings: deprecated' => [
      '',
      'OVERRIDE',
      TRUE,
    ];
    yield 'both deprecated' => [
      'OVERRIDE',
      'OVERRIDE',
      TRUE,
    ];
  }

}
