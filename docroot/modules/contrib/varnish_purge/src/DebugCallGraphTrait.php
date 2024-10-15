<?php

namespace Drupal\varnish_purger;

use Drupal\purge\Logger\PurgeLoggerAwareTrait;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait DebugCallGraphTrait {
  use PurgeLoggerAwareTrait;

  /**
   * Supporting variable for ::debug(), which keeps a call graph in it.
   *
   * @var string[]
   */
  protected $debug = [];

  /**
   * Generate a short and readable class name.
   *
   * @param string|object $class
   *   Fully namespaced class or an instantiated object.
   *
   * @return string
   */
  protected function getClassName($class) {
    if (is_object($class)) {
      $class = get_class($class);
    }
    if ($pos = strrpos($class, '\\')) {
      $class = substr($class, $pos + 1);
    }
    return $class;
  }

  /**
   * Log the caller graph using $this->logger()->debug() messages.
   *
   * @param string $caller
   *   Name of the PHP method that is calling ::debug().
   */
  protected function debug($caller) {
    /** @var \Drupal\purge\Logger\LoggerChannelPartInterface $logger */
    $logger = $this->logger();
    if (!$logger || !$logger->isDebuggingEnabled()) {
      return;
    }

    // Generate a caller name used both in logging and call counting.
    $caller = str_replace(
      $this->getClassName(__CLASS__),
      '',
      $this->getClassName($caller)
    );

    // Define a simple closure to print with prefixed indentation.
    $log = function ($output) use ($logger) {
      $space = str_repeat('  ', count($this->debug));
      $logger->debug($space . $output);
    };

    if (!in_array($caller, $this->debug)) {
      $this->debug[] = $caller;
      $log("--> $caller():");
    }
    else {
      unset($this->debug[array_search($caller, $this->debug)]);
      $log("      (finished)");
    }
  }

  /**
   * Extract debug information from a request.
   *
   * @param \Psr\Http\Message\RequestInterface $r
   *   The HTTP request object.
   *
   * @return string[]
   */
  protected function debugInfoForRequest(RequestInterface $r) {
    $info = [];
    $info['req http'] = $r->getProtocolVersion();
    $info['req uri'] = $r->getUri()->__toString();
    $info['req method'] = $r->getMethod();
    $info['req headers'] = [];
    foreach ($r->getHeaders() as $h => $v) {
      $info['req headers'][] = $h . ': ' . $r->getHeaderLine($h);
    }
    return $info;
  }

  /**
   * Extract debug information from a response.
   *
   * @param \Psr\Http\Message\ResponseInterface $r
   *   The HTTP response object.
   * @param \GuzzleHttp\Exception\RequestException $e
   *   Optional exception in case of failures.
   *
   * @return string[]
   */
  protected function debugInfoForResponse(ResponseInterface $r, RequestException $e = NULL) {
    $info = [];
    $info['rsp http'] = $r->getProtocolVersion();
    $info['rsp status'] = $r->getStatusCode();
    $info['rsp reason'] = $r->getReasonPhrase();
    if (!is_null($e)) {
      $info['rsp summary'] = json_encode($e->getResponseBodySummary($r));
    }
    $info['rsp headers'] = [];
    foreach ($r->getHeaders() as $h => $v) {
      $info['rsp headers'][] = $h . ': ' . $r->getHeaderLine($h);
    }
    return $info;
  }

  /**
   * Render debugging information as table to $this->logger()->debug().
   *
   * @param mixed[] $table
   *   Associative array with each key being the row title. Each value can be
   *   a string, or when it is a array itself, the row will be repeated.
   * @param int $left
   *   Amount of characters that the left size of the table can be long.
   */
  protected function logDebugTable(array $table, $left = 15) {
    $longest_key = max(array_map('strlen', array_keys($table)));
    $logger = $this->logger();
    if ($longest_key > $left) {
      $left = $longest_key;
    }
    foreach ($table as $title => $value) {
      $spacing = str_repeat(' ', $left - strlen($title));
      $title = strtoupper($title) . $spacing . ' | ';
      if (is_array($value)) {
        foreach ($value as $repeated_value) {
          $logger->debug($title . $repeated_value);
        }
      }
      else {
        $logger->debug($title . $value);
      }
    }
  }

  /**
   * Write an error to the log for a failed request.
   *
   * @param string $caller
   *   Name of the PHP method that executed the request.
   * @param \Exception $e
   *   The exception thrown by Guzzle.
   */
  protected function logFailedRequest($caller, \Exception $e) {
    $msg = "::@caller() -> @class:";
    $vars = [
      '@caller' => $caller,
      '@class' => $this->getClassName($e),
      '@msg' => $e->getMessage(),
    ];

    // Add request information when this is present in the exception.
    if ($e instanceof ConnectException) {
      $vars['@msg'] = str_replace(
        '(see http://curl.haxx.se/libcurl/c/libcurl-errors.html)',
        '', $e->getMessage());
      $vars['@msg'] .= '; This is allowed to happen accidentally when load'
        . ' balancers are slow. However, if all cache invalidations fail, your'
        . ' queue may stall and you should investigate with your hosting'
        . ' provider!';
    }
    elseif ($e instanceof RequestException) {
      $req = $e->getRequest();
      $msg .= " HTTP @status; @method @uri;";
      $vars['@uri'] = $req->getUri();
      $vars['@method'] = $req->getMethod();
      $vars['@status'] = $e->hasResponse() ? $e->getResponse()
        ->getStatusCode() : '???';
    }

    // Log the normal message to the emergency output stream.
    /** @var \Drupal\purge\Logger\LoggerChannelPartInterface $logger */
    $logger = $this->logger();
    $logger->emergency("$msg @msg", $vars);

    // In debugging mode, follow with quite some more data.
    if ($logger->isDebuggingEnabled()) {
      $table = ['exception' => get_class($e)];
      if ($e instanceof RequestException) {
        $table = array_merge($table, $this->debugInfoForRequest($e->getRequest()));
        $table['rsp'] = ($has_rsp = $e->hasResponse()) ? 'YES' : 'No response';
        if ($has_rsp && ($rsp = $e->getResponse())) {
          $table = array_merge($table, $this->debugInfoForResponse($rsp, $e));
        }
      }
      $this->logDebugTable($table);
    }
  }
}
