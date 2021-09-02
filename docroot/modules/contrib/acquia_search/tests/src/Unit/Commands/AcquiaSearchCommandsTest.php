<?php

namespace Drupal\Tests\acquia_search\Unit\Commands {

  use Drupal\acquia_search\Commands\AcquiaSearchCommands;
  use Drupal\acquia_search\Helper\Storage;
  use Drupal\Component\Datetime\Time;
  use Drupal\Component\Serialization\Json;
  use Drupal\Component\Uuid\Php;
  use Drupal\Core\Cache\CacheBackendInterface;
  use Drupal\Core\Config\Config;
  use Drupal\Core\Config\ConfigFactoryInterface;
  use Drupal\Core\Config\ImmutableConfig;
  use Drupal\Core\DependencyInjection\ContainerBuilder;
  use Drupal\Core\Extension\ModuleHandlerInterface;
  use Drupal\Core\State\State;
  use Drupal\Tests\UnitTestCase;
  use GuzzleHttp\Client;
  use Prophecy\Argument;
  use Psr\Http\Message\ResponseInterface;
  use Psr\Http\Message\StreamInterface;
  use Symfony\Component\Console\Output\OutputInterface;

  /**
   * @coversDefaultClass \Drupal\acquia_search\Commands\AcquiaSearchCommands
   * @group Acquia Search Solr
   */
  class AcquiaSearchCommandsTest extends UnitTestCase {

    /**
     * OutputInterface.
     *
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    protected $output;

    /**
     * Command.
     *
     * @var \Drupal\acquia_search\Commands\AcquiaSearchCommands
     */
    protected $command;

    /**
     * CacheBackendInterface.
     *
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    protected $cache;

    /**
     * GuzzleHttp Client.
     *
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    protected $guzzleClient;

    /**
     * {@inheritdoc}
     */
    protected function setUp() {
      parent::setUp();

      $state = $this->prophesize(State::class);
      $state->set(Argument::type('string'), Argument::type('string'))
        ->will(function ($arguments) use ($state) {
          $state->get($arguments[0], Argument::any())
            ->willReturn($arguments[1]);
        });

      $config_factory = $this->prophesize(ConfigFactoryInterface::class);
      $config = $this->prophesize(ImmutableConfig::class);
      $config->get('override_search_core')->willReturn(NULL);
      $config->get('read_only')->willReturn(FALSE);
      $config->get('api_host')->willReturn('https://example.com');
      $config_editable = $this->prophesize(Config::class);
      $config_editable->set('api_host', 'https://example.com')
        ->willReturn($config_editable->reveal());
      $config_editable->save()->willReturn($config_editable);
      $config_factory->get('acquia_search.settings')
        ->willReturn($config->reveal());
      $config_factory->getEditable('acquia_search.settings')
        ->willReturn($config_editable->reveal());

      $config_factory->get('acquia_search.settings')
        ->willReturn($config->reveal());

      $json = json_encode([
        [
          'key' => 'ABC-12345.prod.default',
          'secret_key' => 'secret_key',
          'product_policies' => ['salt' => 'salt'],
          'host' => 'example.com',
        ],
        [
          'key' => 'ABC-12345.dev.drupal8',
          'secret_key' => 'secret_key',
          'product_policies' => ['salt' => 'salt'],
          'host' => 'example.com',
        ],
      ]);
      $stream = $this->prophesize(StreamInterface::class);
      $stream->getSize()->willReturn(1000);
      $stream->read(1000)->willReturn($json);

      $response = $this->prophesize(ResponseInterface::class);
      $response->getStatusCode()->willReturn(200);
      $response->getBody()->willReturn($stream);

      $this->guzzleClient = $this->prophesize(Client::class);
      $this->guzzleClient->get(Argument::type('string'), Argument::any())
        ->willReturn($response);
      $this->guzzleClient->get(Argument::any(), Argument::any())->willReturn();

      $this->cache = $this->prophesize(CacheBackendInterface::class);

      // \Drupal::time().
      $time = $this->prophesize(Time::class);
      $time->getRequestTime()->willReturn(1234567000000);

      $module_handler = $this->prophesize(ModuleHandlerInterface::class);

      $container = new ContainerBuilder();
      $container->set('cache.default', $this->cache->reveal());
      $container->set('config.factory', $config_factory->reveal());
      $container->set('datetime.time', $time->reveal());
      $container->set('http_client', $this->guzzleClient->reveal());
      $container->set('module_handler', $module_handler->reveal());
      $container->set('site.path', 'sites/default');
      $container->set('state', $state->reveal());
      \Drupal::setContainer($container);

      $storage = new Storage();
      $storage->setApiHost('https://example.com');
      $storage->setApiKey('XXXXXXXXXXyyyyyyyyyyXXXXXXXXXXyyyyyyyyyy');
      $storage->setIdentifier('ABC-12345');
      $uuid = new Php();
      $storage->setUuid($uuid->generate());

      $this->command = new AcquiaSearchCommands($this->cache->reveal());
      $this->output = $this->prophesize(OutputInterface::class);
      $this->command->setOutput($this->output->reveal());

    }

    /**
     * @covers ::searchSolrCoresList
     */
    public function testSearchSolrCoresList() {

      $available_cores = ['ABC-12345.prod.default', 'ABC-12345.dev.drupal8'];

      $this->output->writeln(print_r($available_cores, TRUE))
        ->shouldBeCalledTimes(3);
      $this->output->writeln(Json::encode($available_cores))
        ->shouldBeCalledOnce();
      $this->output->writeln(var_export($available_cores, TRUE))
        ->shouldBeCalledTimes(2);
      $this->output->reveal();

      $this->command->searchSolrCoresList();
      $this->command->searchSolrCoresList(['format' => 'print_r']);
      $this->command->searchSolrCoresList(['format' => 'non-existing']);

      $this->command->searchSolrCoresList(['format' => 'json']);

      $this->command->searchSolrCoresList(['format' => 'var_dump']);
      $this->command->searchSolrCoresList(['format' => 'var_export']);

    }

    /**
     * @covers ::searchSolrResetCoresCache
     */
    public function testSearchSolrResetCoresCache() {

      $this->output->writeln('Cache is empty for ABCD-12345')
        ->shouldBeCalledOnce();
      $this->output->writeln('Cache is empty for ABCDE-123456')
        ->shouldBeCalledOnce();
      $this->output->writeln('Cache cleared for WXYZ-12345')
        ->shouldBeCalledOnce();
      $this->output->writeln('Cache cleared for WXYZ-123456')
        ->shouldBeCalledOnce();
      $this->output->reveal();

      $this->cache->get('acquia_search.indexes.ABCD-12345')
        ->willReturn()
        ->shouldBeCalledOnce();
      $this->cache->get('acquia_search.indexes.ABCDE-123456')
        ->willReturn()
        ->shouldBeCalledOnce();
      $this->cache->get('acquia_search.indexes.WXYZ-12345')
        ->willReturn(TRUE)
        ->shouldBeCalledOnce();
      $this->cache->delete('acquia_search.indexes.WXYZ-12345')
        ->shouldBeCalledOnce();
      $this->cache->get('acquia_search.indexes.WXYZ-123456')
        ->willReturn(TRUE)
        ->shouldBeCalledOnce();
      $this->cache->delete('acquia_search.indexes.WXYZ-123456')
        ->shouldBeCalledOnce();
      $this->cache->reveal();

      try {
        $this->command->searchSolrResetCoresCache();
        $this->fail('No exception');
      }
      catch (\Exception $exception) {
        $this->assertEquals('Provide a valid Acquia subscription identifier', $exception->getMessage());
      }

      try {
        $this->command->searchSolrResetCoresCache(['id' => 'ABC-12345']);
        $this->fail('No exception');
      }
      catch (\Exception $exception) {
        $this->assertEquals('Provide a valid Acquia subscription identifier', $exception->getMessage());
      }

      try {
        $this->command->searchSolrResetCoresCache(['id' => 'ABCD-12345']);
      }
      catch (\Exception $exception) {
        $this->fail('Unexpected exception: ' . $exception->getMessage());
      }

      try {
        $this->command->searchSolrResetCoresCache(['id' => 'ABCDE-123456']);
      }
      catch (\Exception $exception) {
        $this->fail('Unexpected exception: ' . $exception->getMessage());
      }

      try {
        $this->command->searchSolrResetCoresCache(['id' => 'ABCDEF-123456']);
        $this->fail('No exception');
      }
      catch (\Exception $exception) {
        $this->assertEquals('Provide a valid Acquia subscription identifier', $exception->getMessage());
      }

      try {
        $this->command->searchSolrResetCoresCache(['id' => 'ABCD-1234567']);
        $this->fail('No exception');
      }
      catch (\Exception $exception) {
        $this->assertEquals('Provide a valid Acquia subscription identifier', $exception->getMessage());
      }

      $this->command->searchSolrResetCoresCache(['id' => 'WXYZ-12345']);
      $this->command->searchSolrResetCoresCache(['id' => 'WXYZ-123456']);

    }

    /**
     * @covers ::searchSolrCoresPossible
     */
    public function testSearchSolrCoresPossible() {

      try {
        $this->command->searchSolrCoresPossible();
        $this->fail('No exception');
      }
      catch (\Exception $exception) {
        $this->assertEquals('No possible cores', $exception->getMessage());
      }

      $_ENV['AH_SITE_ENVIRONMENT'] = 'dev';

      $possible_cores = ['ABC-12345.dev.default'];

      $this->output->writeln(print_r($possible_cores, TRUE))
        ->shouldBeCalledTimes(3);
      $this->output->writeln(Json::encode($possible_cores))
        ->shouldBeCalledOnce();
      $this->output->writeln(var_export($possible_cores, TRUE))
        ->shouldBeCalledTimes(2);
      $this->output->reveal();

      $this->command->searchSolrCoresPossible();
      $this->command->searchSolrCoresPossible(['format' => 'print_r']);
      $this->command->searchSolrCoresPossible(['format' => 'non-existing']);

      $this->command->searchSolrCoresPossible(['format' => 'json']);

      $this->command->searchSolrCoresPossible(['format' => 'var_dump']);
      $this->command->searchSolrCoresPossible(['format' => 'var_export']);

      unset($_ENV['AH_SITE_ENVIRONMENT']);

    }

    /**
     * @covers ::searchSolrCoresPreferred
     */
    public function testSearchSolrCoresPreferred() {

      try {
        $this->command->searchSolrCoresPreferred();
        $this->fail('No exception');
      }
      catch (\Exception $exception) {
        $this->assertEquals('No preferred search core available', $exception->getMessage());
      }

      $_ENV['AH_SITE_ENVIRONMENT'] = 'prod';
      $this->output->writeln('ABC-12345.prod.default')->shouldBeCalledTimes(1);

      $this->command->searchSolrCoresPreferred();

      unset($_ENV['AH_SITE_ENVIRONMENT']);

    }

  }

}

namespace {

  if (!function_exists('dt')) {

    /**
     * Helper function to test Helper\Storage::getVersion.
     *
     * Rudimentary translation system, akin to Drupal's t() function.
     *
     * @param string $message
     *   String to process, possibly with replacement item.
     * @param array $replace
     *   An associative array of replacement items.
     *
     * @return string
     *   The processed string.
     */
    function dt($message, array $replace) {
      return strtr($message, $replace);
    }

  }

}
