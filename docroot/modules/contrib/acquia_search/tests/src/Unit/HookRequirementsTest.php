<?php

declare(strict_types=1);

namespace Drupal\Tests\acquia_search\Unit;

use Drupal\acquia_search\Plugin\SolrConnector\SearchApiSolrAcquiaConnector;
use Drupal\acquia_search\PreferredCoreService;
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
use Drupal\search_api_solr\SolrBackendInterface;

final class HookRequirementsTest extends AcquiaSearchTestCase {

  /**
   * @dataProvider requirementsData
   */
  public function testRequirements(bool $is_read_only, array $servers, bool $preferred_core, array $expected): void {
    require_once $this->root . '/core/includes/install.inc';
    require_once __DIR__ . '/../../../acquia_search.install';

    $this->createMockContainer(function () use ($is_read_only, $servers, $preferred_core) {
      $acquia_search_settings = $this->createMock(Config::class);
      $acquia_search_settings->expects($this->once())
        ->method('get')
        ->with('read_only')
        ->willReturn($is_read_only);

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

      $config_factory = $this->createMock(ConfigFactoryInterface::class);
      $config_factory
        ->method('get')
        ->willReturnMap([
          ['acquia_search.settings', $acquia_search_settings],
        ]);

      $acquia_search_preferred_core = $this->createMock(PreferredCoreService::class);
      $acquia_search_preferred_core
        ->method('isPreferredCoreAvailable')
        ->willReturn($preferred_core);

      return [
        'entity_type.repository' => $entity_type_repository,
        'entity_type.manager' => $entity_type_manager,
        'config.factory' => $config_factory,
        'module_handler' => $this->createMock(ModuleHandlerInterface::class),
        'acquia_search.preferred_core' => $acquia_search_preferred_core,
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
        'acquia_search_read_only' => [
          'title' => new TranslatableMarkup('Acquia Search Solr'),
          'value' => new TranslatableMarkup('No preferred search core'),
          'severity' => 2,
          'description' => [
            '#markup' => 'Could not find a Solr core corresponding to your website and environment. Your subscription contains no cores. To fix this problem, please read <a href="https://docs.acquia.com/acquia-search/multiple-cores/">our documentation</a>.',
          ],
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
          'value' => new TranslatableMarkup('No preferred search core'),
          'severity' => 2,
          'description' => [
            '#markup' => 'Could not find a Solr core corresponding to your website and environment. Your subscription contains no cores. To fix this problem, please read <a href="https://docs.acquia.com/acquia-search/multiple-cores/">our documentation</a>.',
          ],
        ],
      ],
    ];

    $standard_server = $this->createMock(ServerInterface::class);
    $standard_server->expects($this->once())
      ->method('getBackendConfig')
      ->willReturn([
        'connector' => 'standard',
      ]);
    $standard_server->expects($this->never())
      ->method('getBackend');

    $acquia_server = $this->createMock(ServerInterface::class);
    $acquia_server
      ->method('getBackendConfig')
      ->willReturn([
        'connector' => 'solr_acquia_connector',
      ]);
    $acquia_server
      ->method('getBackend')
      ->willReturnCallback(function () {
        $connector = $this->createMock(SearchApiSolrAcquiaConnector::class);
        $connector
          ->method('getConfiguration')
          ->willReturn([]);
        $backend = $this->createMock(SolrBackendInterface::class);
        $backend
          ->method('getSolrConnector')
          ->willReturn($connector);
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
        'acquia_search_read_only' => [
          'title' => new TranslatableMarkup('Acquia Search Solr'),
          'value' => new TranslatableMarkup('No preferred search core'),
          'severity' => 2,
          'description' => [
            '#markup' => 'Could not find a Solr core corresponding to your website and environment. Your subscription contains no cores. To fix this problem, please read <a href="https://docs.acquia.com/acquia-search/multiple-cores/">our documentation</a>.',
          ],
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
        'acquia_search_read_only' => [
          'title' => new TranslatableMarkup('Acquia Search Solr'),
          'value' => new TranslatableMarkup('No preferred search core'),
          'severity' => 2,
          'description' => [
            '#markup' => 'Could not find a Solr core corresponding to your website and environment. Your subscription contains no cores. To fix this problem, please read <a href="https://docs.acquia.com/acquia-search/multiple-cores/">our documentation</a>.',
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
          'value' => new TranslatableMarkup('No preferred search core'),
          'severity' => 2,
          'description' => [
            '#markup' => 'Could not find a Solr core corresponding to your website and environment. Your subscription contains no cores. To fix this problem, please read <a href="https://docs.acquia.com/acquia-search/multiple-cores/">our documentation</a>.',
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

}
