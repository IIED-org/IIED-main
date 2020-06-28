<?php

namespace Drupal\devel\Plugin\Devel\Dumper;

use Drupal\devel\DevelDumperBase;
use Kint\Parser\BlacklistPlugin;
use Kint\Renderer\RichRenderer;
use Psr\Container\ContainerInterface;

/**
 * Provides a Kint dumper plugin.
 *
 * @DevelDumper(
 *   id = "kint",
 *   label = @Translation("Kint"),
 *   description = @Translation("Wrapper for <a href='https://github.com/kint-php/kint'>Kint</a> debugging tool."),
 * )
 */
class Kint extends DevelDumperBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configure();
  }

  /**
   * Configures kint with more sane values.
   */
  protected function configure() {
    // Remove resource-hungry plugins.
    \Kint::$plugins = array_diff(\Kint::$plugins, [
      'Kint\\Parser\\ClassMethodsPlugin',
      'Kint\\Parser\\ClassStaticsPlugin',
      'Kint\\Parser\\IteratorPlugin',
    ]);
    \Kint::$aliases = $this->getInternalFunctions();

    RichRenderer::$folder = FALSE;
    BlacklistPlugin::$shallow_blacklist[] = ContainerInterface::class;
  }

  /**
   * {@inheritdoc}
   */
  public function export($input, $name = NULL) {
    ob_start();
    if ($name == '__ARGS__') {
      call_user_func_array(['Kint', 'dump'], $input);
      $name = NULL;
    }
    elseif ($name !== NULL) {
      // In order to get the correct access path information returned from Kint
      // we have to give a second parameter here. This is due to a fault in
      // Kint::getSingleCall which returns no info when the number of arguments
      // passed to Kint::dump does not match the number in the original call
      // that invoked the export (such as dsm). However, this second parameter
      // is just treated as the next variable to dump, it is not used as the
      // label. So we give a dummy value that we can remove below.
      // @see https://gitlab.com/drupalspoons/devel/-/issues/252
      \Kint::dump($input, '---remove-this---');
    }
    else {
      \Kint::dump($input);
    }
    $dump = ob_get_clean();
    if ($name) {
      // Kint no longer treats an additional parameter as a custom title, but we
      // can add in the required $name to the dump output. Providing that a
      // variable starting with $ was passed to the original call, we can find
      // the place where this starts and add in our custom $name.
      $dump = str_replace('<dfn>$', '<dfn>' . $name . ': $', $dump);

      // Remove the output for the second dummy parameter. $1 will be the greedy
      // match of everything before <dl><dt> related to the section to remove.
      $pattern = '/(.*)(<dl><dt>)(.*)("---remove-this---"<\/dt><\/dl>)/';
      $dump = preg_replace($pattern, '$1', $dump, 1);
    }

    return $this->setSafeMarkup($dump);
  }

  /**
   * {@inheritdoc}
   */
  public static function checkRequirements() {
    return class_exists('Kint', TRUE);
  }

}
