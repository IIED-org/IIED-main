<?php

namespace Drupal\iied_migrate_d7\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drush\Drush;

/**
 * Perform custom value transformations.
 *
 * @MigrateProcessPlugin(
 *   id = "rewrite_inline_image_styles"
 * )
 *
 * To do custom value transformations use the following:
 *
 * @code
 * field_text:
 *   plugin: rewrite_inline_image_styles
 *   source: text
 * @endcode
 *
 */
class RewriteInlineImageStyles extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // We want to match any paths like:
    // /sites/default/files/styles/float_right/public/images/2018/05/24/2018-05_ina_porras_0.jpg
    // replacing them with a path like:
    // /sites/default/files/styles/new_style/public/images/2018/05/24/2018-05_ina_porras_0.jpg
    // Note: the new destination files have moved to /yyyy/mm/dd/filename
    // - we'll have to look up the path in the database!

    $new_style = $this->configuration['new_style'];
    if (!strlen($new_style)) {
      $new_style = 'scale_md';
    }

    $pattern_to_match = '/\/sites\/default\/files\/styles\/.*?\/public\/(.*?.(?:png|jpeg|jpg|gif|bmp))/';
    // $pattern_to_replace = "sites/default/files/styles/$new_style/public/";

    preg_match_all($pattern_to_match, $value, $matches);

    if (is_array($matches[0])) {
      foreach ($matches[0] as $key => $original_path) {
        $file_name = urldecode($matches[1][$key]);
        $db = \Drupal\Core\Database\Database::getConnection();
        $query = $db->select('file_managed', 'fm');
        $query->fields('fm', array('fid', 'uri'));
        $query->condition('uri', "%$file_name%", 'LIKE');
        $file_managed = $query->execute()->fetchAll();

        $files = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['filename' => $file_name]);

        $fid = $file_managed[0]->fid;

        if ($fid) {
          $file_object = File::load($fid);
          //$file_url = $file_object->createFileUrl();

          // Get origin image URI.
          $image_uri = $file_object->getFileUri();
          // Load image style.
          $style = ImageStyle::load($new_style);

          // Get URI.
          $uri = $style->buildUri($image_uri);

          $file_url_generator = \Drupal::service('file_url_generator');
          $file_url = $file_url_generator->generateString($uri);
          // Replace the original path with the new file url.
          $value = str_replace($original_path, $file_url, $value);
        } else {
          // We can't find a reference to this file in Drupal's managed files.
          $node_id = $row->getSourceProperty(('nid'));
          $message = "\nRewrite inline image styles: Cannot find reference to $file_name from node $node_id. \n";
          Drush::output()->writeln($message);
        }
      }
    }

    // Add rewrites for pattersn like src="/files/filename.ext" > src="/sites/default/files/filename.ext"
    $webroot_files_pattern = '/\/files\/(.*?.(?:png|jpeg|jpg|gif|bmp|pdf))/';
    preg_match_all($webroot_files_pattern, $value, $webroot_filesmatches);
    if (is_array($webroot_filesmatches[0])) {
      foreach ($webroot_filesmatches[0] as $key => $original_path) {
        $webroot_file_new_path = '/sites/default' . $original_path;
        $value = str_replace($original_path, $webroot_file_new_path, $value);
      }
    }

    return $value;

  }

}
