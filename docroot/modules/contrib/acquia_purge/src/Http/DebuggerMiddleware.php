<?php

namespace Drupal\acquia_purge\Http;

use Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerAwareInterface;
use Drupal\acquia_purge\Plugin\Purge\Purger\DebuggerAwareTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP middleware which logs requests and responses for debugging purposes.
 */
class DebuggerMiddleware implements DebuggerAwareInterface {
  use DebuggerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function __invoke() {
    return function (callable $handler) {
      return function ($req, array $options) use ($handler) {

        // Don't interfere on requests not made by the acquia_purge module, or
        // set the debgger object (::setDebugger will do input type validation).
        if (!isset($options['acquia_purge_debugger'])) {
          return $handler($req, $options);
        }
        else {
          $this->setDebugger($options['acquia_purge_debugger'], FALSE);
        }

        // Render a visual separation between middleware invocations.
        $this->debugger()->writeSeparator('█');

        // Guzzle Request Options.
        $info = [];
        foreach ($options as $key => $value) {
          if (is_scalar($value)) {
            $info[$key] = $value;
          }
        }
        $this->debugger()->writeTable($info, 'Guzzle Request Options');

        // Cache Tags Mapping.
        if (isset($options['acquia_purge_tags'])) {
          $this->debugger()->writeTable(
            $options['acquia_purge_tags']->getTagsMap(),
            'Cache Tags Mapping'
          );
        }

        // Request table.
        $info = $this->debugger()->extractRequestInfo($req);
        $this->debugger()->writeTable($info);

        // Return a handler which writes out a response table.
        return $handler($req, $options)->then(
          function (ResponseInterface $rsp) {
            $info = $this->debugger()->extractResponseInfo($rsp);
            $this->debugger()->writeTable($info);
            return $rsp;
          }
        );
      };
    };
  }

}
