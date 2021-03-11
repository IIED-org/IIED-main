<?php

namespace Drupal\ckwordcount\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "wordcount" plugin.
 *
 * @CKEditorPlugin(
 *   id = "wordcount",
 *   label = @Translation("Word Count & Character Count")
 * )
 */
class Wordcount extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['notification'];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return file_exists(DRUPAL_ROOT . '/libraries/wordcount/plugin.js') ? 'libraries/wordcount/plugin.js' : 'libraries/ckeditor-wordcount-plugin/wordcount/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $settings = $editor->getSettings();
    return isset($settings['plugins']['wordcount']) ? $settings['plugins']['wordcount']['enable'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();

    return [
      'wordcount' => [
        'showRemaining' => !empty($settings['plugins']['wordcount']['show_remaining']) ? $settings['plugins']['wordcount']['show_remaining'] : FALSE,
        'showParagraphs' => !empty($settings['plugins']['wordcount']['show_paragraphs']) ? $settings['plugins']['wordcount']['show_paragraphs'] : FALSE,
        'showWordCount' => !empty($settings['plugins']['wordcount']['show_word_count']) ? $settings['plugins']['wordcount']['show_word_count'] : FALSE,
        'showCharCount' => !empty($settings['plugins']['wordcount']['show_char_count']) ? $settings['plugins']['wordcount']['show_char_count'] : FALSE,
        'countBytesAsChars' => !empty($settings['plugins']['wordcount']['count_bytes']) ? $settings['plugins']['wordcount']['count_bytes'] : FALSE,
        'countSpacesAsChars' => !empty($settings['plugins']['wordcount']['count_spaces']) ? $settings['plugins']['wordcount']['count_spaces'] : FALSE,
        'countHTML' => !empty($settings['plugins']['wordcount']['count_html']) ? $settings['plugins']['wordcount']['count_html'] : FALSE,
        'countLineBreaks' => !empty($settings['plugins']['wordcount']['count_line_breaks']) ? $settings['plugins']['wordcount']['count_line_breaks'] : FALSE,
        'maxWordCount' => !empty($settings['plugins']['wordcount']['max_words']) ? $settings['plugins']['wordcount']['max_words'] : -1,
        'maxCharCount' => !empty($settings['plugins']['wordcount']['max_chars']) ? $settings['plugins']['wordcount']['max_chars'] : -1,
        'hardLimit' => isset($settings['plugins']['wordcount']['hard_limit']) ? $settings['plugins']['wordcount']['hard_limit'] : TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the counter'),
      '#default_value' => !empty($settings['plugins']['wordcount']['enable']) ? $settings['plugins']['wordcount']['enable'] : FALSE,
    ];

    $form['show_remaining'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show remaining'),
      '#default_value' => !empty($settings['plugins']['wordcount']['show_remaining']) ? $settings['plugins']['wordcount']['show_remaining'] : FALSE,
    ];

    $form['show_paragraphs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the paragraphs count'),
      '#default_value' => !empty($settings['plugins']['wordcount']['show_paragraphs']) ? $settings['plugins']['wordcount']['show_paragraphs'] : FALSE,
    ];

    $form['show_word_count'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the word count'),
      '#default_value' => !empty($settings['plugins']['wordcount']['show_word_count']) ? $settings['plugins']['wordcount']['show_word_count'] : FALSE,
    ];

    $form['show_char_count'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the character count'),
      '#default_value' => !empty($settings['plugins']['wordcount']['show_char_count']) ? $settings['plugins']['wordcount']['show_char_count'] : FALSE,
    ];

    $form['count_bytes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Count bytes'),
      '#default_value' => !empty($settings['plugins']['wordcount']['count_bytes']) ? $settings['plugins']['wordcount']['count_bytes'] : FALSE,
    ];

    $form['count_spaces'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Count spaces as characters'),
      '#default_value' => !empty($settings['plugins']['wordcount']['count_spaces']) ? $settings['plugins']['wordcount']['count_spaces'] : FALSE,
    ];

    $form['count_html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Count HTML as characters'),
      '#default_value' => !empty($settings['plugins']['wordcount']['count_html']) ? $settings['plugins']['wordcount']['count_html'] : FALSE,
    ];

    $form['count_line_breaks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Count line breaks'),
      '#default_value' => !empty($settings['plugins']['wordcount']['count_line_breaks']) ? $settings['plugins']['wordcount']['count_line_breaks'] : FALSE,
    ];

    $form['max_words'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum word limit'),
      '#description' => $this->t('Enter a maximum word limit. Leave this set to -1 for unlimited.'),
      '#default_value' => !empty($settings['plugins']['wordcount']['max_words']) ? $settings['plugins']['wordcount']['max_words'] : -1,
    ];

    $form['max_chars'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum character limit'),
      '#description' => $this->t('Enter a maximum character limit. Leave this set to -1 for unlimited.'),
      '#default_value' => !empty($settings['plugins']['wordcount']['max_chars']) ? $settings['plugins']['wordcount']['max_chars'] : -1,
    ];

    $form['hard_limit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Lock editor when limit is reached'),
      '#default_value' => isset($settings['plugins']['wordcount']['hard_limit']) ? $settings['plugins']['wordcount']['hard_limit'] : TRUE,
    ];

    $form['max_words']['#element_validate'][] = [$this, 'isNumeric'];
    $form['max_chars']['#element_validate'][] = [$this, 'isNumeric'];
    return $form;
  }

  /**
   * Validation function for the settings form.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function isNumeric(array $element, FormStateInterface $form_state) {
    if (!is_numeric($element['#value'])) {
      $form_state->setError($element, 'Value must be a number.');
    }
  }

}
