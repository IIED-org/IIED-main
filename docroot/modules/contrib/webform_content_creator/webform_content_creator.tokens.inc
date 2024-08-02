<?php

/**
 * @file
 * Builds placeholder replacement tokens for webforms and submissions.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\Plugin\WebformElement\WebformComputedBase;
use Drupal\webform\Plugin\WebformElement\WebformMarkupBase;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\Utility\WebformHtmlHelper;
use Drupal\webform\Utility\WebformLogicHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Implements hook_token_info().
 */
function webform_content_creator_token_info() {
  $types = [];
  $tokens = [];

  /****************************************************************************/
  // Webform submission.
  /****************************************************************************/

  $webform_submission = [];
  $webform_submission['unmapped_values'] = [
    'name' => t('Unmapped submission values'),
    'description' => Markup::create(t('Webform values from submitted data for all values not mapped.')),
  ];

  $tokens['webform_submission'] = $webform_submission;

  return ['types' => $types, 'tokens' => $tokens];
}

/**
 * Implements hook_tokens().
 */
function webform_content_creator_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  // Set URL options to generate absolute translated URLs.
  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  $replacements = [];
  if ($type === 'webform_submission' && !empty($data['webform_submission']) && !empty($data['webform_content_creator'])) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
 
    // Adding webform submission, webform, source entity to bubbleable meta.
    // This reduces code duplication and easier to track.
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $data['webform_submission'];
    $bubbleable_metadata->addCacheableDependency($webform_submission);

    $webform = $webform_submission->getWebform();
    $bubbleable_metadata->addCacheableDependency($webform);

    $source_entity = $webform_submission->getSourceEntity(TRUE);
    if ($source_entity) {
      $bubbleable_metadata->addCacheableDependency($source_entity);
    }

    $mapping = $data['webform_content_creator'];
    $bubbleable_metadata->addCacheableDependency($mapping);

    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $webform_submission->getOwner() ?: User::load(0);
    $bubbleable_metadata->addCacheableDependency($account);

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'unmapped_values':
          $replacements[$original] = _webform_content_creator_token_get_submission_values(['html' => TRUE], $webform_submission, $mapping);
          break;
      }
    }
  }

  return $replacements;
}

/**
 * Get webform submission values.
 *
 * @param array $options
 *   An array of token options.
 * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
 *   A webform submission.
 * @param array $mapping
 *   A webform content creator field mapping.
 *
 * @return \Drupal\Component\Render\MarkupInterface|string
 *   Webform submission values.
 */
function _webform_content_creator_token_get_submission_values(array $options, WebformSubmissionInterface $webform_submission, $mapping) {
  $submission_format = (!empty($options['html'])) ? 'html' : 'text';
  /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
  $elements = $webform_submission->getWebform()->getElementsInitializedAndFlattened();

  foreach ($mapping as $map) {
    if (!empty($map['webform_field'])) {
      unset($elements[$map['webform_field']]);
    }
  }

  $token_value = $view_builder->buildElements($elements, $webform_submission, $options, $submission_format);

  // Note, tokens can't include CSS and JS libraries since they can be
  // included in an email.
  $value = \Drupal::service('renderer')->renderPlain($token_value);

  return $value;
}
