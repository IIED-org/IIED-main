<?php

namespace Drupal\acquia_purge\Plugin\Purge\Purger;

use Drupal\purge\Logger\LoggerChannelPartInterface;
use Drupal\purge\Logger\PurgeLoggerAwareTrait;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides a centralized debugger for Acquia purger plugins.
 */
class Debugger implements DebuggerInterface {
  use PurgeLoggerAwareTrait;

  /**
   * Int: the assumed maximum buffer length for debugg output.
   */
  const OUTPUT_BUFLEN = 80;

  /**
   * Int: the length of indentation padding.
   */
  const OUTPUT_INDENTLEN = 2;

  /**
   * Supporting variable for ::callerAdd() and ::callerRemove.
   *
   * @var string[]
   */
  protected $callGraph = [];

  /**
   * Boolean indicating whether debugging is enabled in the passed logger.
   *
   * @var bool
   */
  protected $enabled = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(LoggerChannelPartInterface $logger) {
    $this->setLogger($logger);
    // Only enable the debugger when running in CLI context (e.g. Drush).
    if (php_sapi_name() == 'cli') {
      $this->enabled = $logger->isDebuggingEnabled();
    }
    $this->callerAdd(__CLASS__);
  }

  /**
   * {@inheritdoc}
   */
  public function callerAdd($caller) {
    if (!$this->enabled) {
      return;
    }
    // Normalize the caller name and add it to the call graph.
    $caller = $this->extractClassName($caller);
    $this->write('<' . $caller . '>');
    $this->callGraph[] = $caller;
  }

  /**
   * {@inheritdoc}
   */
  public function callerRemove($caller) {
    if (!$this->enabled) {
      return;
    }
    // Normalize the caller name and remove it from the call graph.
    $caller = $this->extractClassName($caller);
    if (is_int($key = array_search($caller, $this->callGraph))) {
      unset($this->callGraph[$key]);
    }
    $this->write('</' . $caller . '>');
  }

  /**
   * {@inheritdoc}
   */
  public function enabled() {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function extractClassName($caller) {
    if (is_object($caller)) {
      $caller = get_class($caller);
    }
    // Strip out a couple of common class paths to make output more readable.
    $caller = str_replace('Drupal\acquia_purge\\', '', $caller);
    $caller = str_replace('Plugin\Purge\Purger\\', '', $caller);
    return $caller;
  }

  /**
   * {@inheritdoc}
   */
  public function extractRequestInfo(RequestInterface $request, $body_title = FALSE) {
    $info = [];
    $info['REQ'] = sprintf(
      "%s %s HTTP/%s",
      $request->getMethod(),
      $request->getRequestTarget(),
      $request->getProtocolVersion()
    );
    foreach ($request->getHeaders() as $header => $value) {
      $info['REQ ' . $header] = implode(',', $value);
    }
    if ($body = $request->getBody()->getContents()) {
      if ($body_title) {
        $info['REQ BODY'] = $body;
      }
      else {
        $info[] = '';
        $info[] = $body;
      }
    }
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function extractResponseInfo(ResponseInterface $response, $body_title = FALSE) {
    $info = [];
    $info['RSP'] = sprintf(
      "HTTP/%s %d %s",
      $response->getProtocolVersion(),
      $response->getStatusCode(),
      $response->getReasonPhrase()
    );
    foreach ($response->getHeaders() as $header => $value) {
      $info['RSP ' . $header] = implode(',', $value);
    }

    if ($body = (string) $response->getBody()) {
      $content_type = $response->getHeaderLine('content-type');
      if (strpos($content_type, 'application/json') !== FALSE) {
        $body = json_decode($body);
        $body = json_encode($body, JSON_PRETTY_PRINT);
      }
      elseif (strpos($content_type, 'text/html') !== FALSE) {
        if (count($lines = explode("\n", $body)) >= 4) {
          $lines = array_slice($lines, 0, 4);
          $body = implode("\n", $lines) . "\n...";
        }
      }
      if ($body_title) {
        $info['RSP BODY'] = $body;
      }
      else {
        $info[] = '';
        $info[] = $body;
      }
    }
    return $info;
  }

  /**
   * Calculate the indentation depth in number of characters.
   *
   * @return int
   *   The number of characters to be used for indentation.
   */
  protected function indentationDepth() {
    return count($this->callGraph) * self::OUTPUT_INDENTLEN;
  }

  /**
   * {@inheritdoc}
   */
  public function logFailedRequest(\Exception $exception) {
    $debug = [];
    $vars = ['@excmsg' => $exception->getMessage()];

    // ConnectException's are frequent, rewrite its message somewhat.
    if ($exception instanceof ConnectException) {
      $vars['@excmsg'] = str_replace('cURL error 6: ', '', $vars['@excmsg']);
      $vars['@excmsg'] = str_replace(
        '(see http://curl.haxx.se/libcurl/c/libcurl-errors.html)',
        '(this is allowed to happen incidentally when servers are slow, not structurally)',
        $vars['@excmsg']
      );
    }

    // Enrich debugging data with the request and response, if available.
    if ($exception instanceof RequestException) {
      $req = $exception->getRequest();
      foreach ($this->extractRequestInfo($req, TRUE) as $key => $value) {
        $debug[$key] = $value;
      }
      if ($exception->hasResponse() && ($rsp = $exception->getResponse())) {
        foreach ($this->extractResponseInfo($rsp, TRUE) as $key => $value) {
          $debug[$key] = $value;
        }
      }
    }

    // Add exception and backtrace data.
    $debug = array_merge(['EXC' => $this->extractClassName($exception)], $debug);
    $debug['BACKTRACE'] = [];
    $path_strip = getcwd() . DIRECTORY_SEPARATOR;
    foreach (explode("\n", $exception->getTraceAsString()) as $i => $frame) {
      if (in_array($i, [0, 1, 2])) {
        $debug['BACKTRACE'][$i] = str_replace($path_strip, '', $frame);
      }
    }

    // Log the normal message to the emergency output stream.
    $vars['%debug'] = json_encode($debug, JSON_PRETTY_PRINT);
    $this->logger()->error("@excmsg\n\n%debug", $vars);
  }

  /**
   * Write a line to the logger's debug output stream.
   *
   * Content will be prefixed depending on its depth in the call graph. Output
   * will also be right-side padded up to $padlen, to improve readability when
   * executed using 'drush -d', which adds timestamps at the end of each line.
   *
   * {@inheritdoc}
   */
  public function write($line) {
    if (!$this->enabled) {
      throw new \LogicException("Cannot call ::write().");
    }
    $depth = $this->indentationDepth();
    $prefix = str_repeat(' ', $depth);
    $suffix = '';
    if (($suffixlen = (self::OUTPUT_BUFLEN - $depth - strlen($line))) > 0) {
      $suffix = str_repeat(' ', $suffixlen);
    }
    $this->logger()->debug($prefix . $line . $suffix);
  }

  /**
   * {@inheritdoc}
   */
  public function writeSeparator($separator = '-') {
    if (!$this->enabled) {
      throw new \LogicException("Cannot call ::writeSeparator().");
    }
    $depth = $this->indentationDepth();
    $this->write(str_repeat($separator, self::OUTPUT_BUFLEN - $depth));
  }

  /**
   * {@inheritdoc}
   */
  public function writeTable(array $table, $title = NULL) {
    if (!$this->enabled) {
      throw new \LogicException("Cannot call ::writeTable().");
    }
    if ($title) {
      $this->writeTitle($title, TRUE, FALSE);
    }
    $this->writeSeparator('-');
    $longest_key = max(array_map('strlen', array_keys($table)));
    foreach ($table as $key => $value) {
      $spacing = '';
      // Determine how the left-side of the table looks like.
      $left = '| ';
      if (!is_int($key)) {
        $spacing = str_repeat(' ', $longest_key - strlen($key));
        $left = '| ' . $key . $spacing . ' | ';
      }
      // Treat all values as potential multiline and render accordingly.
      if (!is_string($value)) {
        $value = json_encode($value);
      }
      foreach (explode("\n", $value) as $line) {
        $this->write($left . $line);
        // Render empty columns on the left after rendering the key once.
        if (!is_int($key)) {
          $left = '| ' . str_repeat(' ', strlen($key)) . $spacing . ' | ';
        }
      }
    }
    $this->writeSeparator('-');
  }

  /**
   * {@inheritdoc}
   */
  public function writeTitle($title, $top = TRUE, $bottom = TRUE) {
    if (!$this->enabled) {
      throw new \LogicException("Cannot call ::writeTitle().");
    }
    if ($top) {
      $this->writeSeparator('-');
    }
    $workspace = self::OUTPUT_BUFLEN - $this->indentationDepth();
    $left = intval(floor($workspace / 2) - ceil(strlen($title) / 2) - 1);
    $right = $workspace - $left - strlen($title) - 2;
    $padleft = str_repeat(' ', $left);
    $padright = str_repeat(' ', $right);
    $this->write('|' . $padleft . $title . $padright . '|');
    if ($bottom) {
      $this->writeSeparator('-');
    }
  }

}
