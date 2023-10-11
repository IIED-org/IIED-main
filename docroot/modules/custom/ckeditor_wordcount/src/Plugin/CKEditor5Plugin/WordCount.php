<?php
namespace Drupal\ckeditor_wordcount\Plugin\CKEditor5Plugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;

/**
 * Defines the "WordCount" plugin.
 *
 * @CKEditor5Plugin(
 *   id = "ckeditor_wordcount_default",
 *   ckeditor5 = @CKEditor5AspectsOfCKEditor5Plugin(
 *     plugins = { "wordcount.WordCount", "wordcount.DrupalWordCount"},
 *   ),
 *   drupal = @DrupalAspectsOfCKEditor5Plugin(
 *     label = @Translation("Word count"),
 *     library = "ckeditor_wordcount/editor",
 *     elements = false,
 *   )
 * )
 */

class WordCount extends CKEditor5PluginDefault {



}
