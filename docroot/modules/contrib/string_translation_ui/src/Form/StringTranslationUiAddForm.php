<?php

namespace Drupal\string_translation_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\locale\SourceString;
use Drupal\locale\StringDatabaseStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Configure locale settings for this site.
 *
 * @internal
 */
class StringTranslationUiAddForm extends ConfigFormBase {

  /**
   * Context Name Default.
   */
  const CONTEXT_NAME_DEFAULT = 'stringTranslationUi';

  /**
   * String Database Storage.
   *
   * @var \Drupal\locale\StringDatabaseStorage
   */
  protected $stringDatabaseStorage;

  /**
   * Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Form object.
   */
  public function __construct(StringDatabaseStorage $stringDatabaseStorage, ModuleHandlerInterface $module_handler) {
    $this->stringDatabaseStorage = $stringDatabaseStorage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('locale.storage'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'string_translation_ui_add_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['string_translation_ui.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('string_translation_ui.settings');

    $form['strings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Strings to be added'),
      '#required' => TRUE,
      '#description' => $this->t('Use one string per line.'),
    ];

    $contextsUsed = [self::CONTEXT_NAME_DEFAULT];
    if (!empty($config->get('contexts_used'))) {
      $contextsUsed = Json::decode($config->get('contexts_used'));
      sort($contextsUsed);
    }

    $form['context'] = [
      '#type' => 'select',
      '#title' => $this->t('Context'),
      '#options' => array_combine($contextsUsed, $contextsUsed) + ['_other_' => $this->t('- Other -')],
    ];

    $form['context_other'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Please specify'),
      '#description' => $this->t('Please use only lowercase letters, numbers, and underscores.'),
      '#states' => [
        'visible' => [
          ':input[name="context"]' => ['value' => '_other_'],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);

    // Get the context.
    $context = $form_state->getValue('context');
    $otherContext = $form_state->getValue('context_other');

    // Validate special chars.
    if ($context == '_other_' && !preg_match('/^[a-z0-9_]+$/', $otherContext)) {
      $form_state->setErrorByName('context_other', $this->t('The context must contain only lowercase letters, numbers, and underscores.'));
    }

    // Get the strings.
    $strings = $form_state->getValue('strings');
    $stringsToTranslate = $this->getStringsToTranslate($strings);

    // Check if in some line there is only a number.
    foreach ($stringsToTranslate as $string) {
      if (ctype_digit($string)) {
        $form_state->setErrorByName('strings', $this->t("This string: @current_string is invalid. We can't translate numbers", ['@current_string' => $string]));
        return FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $stringsToTranslate = $this->getStringsToTranslate($form_state->getValue('strings'));
    $context = $values['context'] === '_other_' ? $values['context_other'] : $values['context'];

    foreach ($stringsToTranslate as $stringToTranslate) {

      $stringToTranslate = trim($stringToTranslate);

      $string = new SourceString();
      $string->setString($stringToTranslate);
      $string->context = $context;
      $string->setStorage($this->stringDatabaseStorage);
      $string->save();
    }

    $config = $this->config('string_translation_ui.settings');

    $contextsUsed = [];

    if (!empty($config->get('contexts_used'))) {
      $contextsUsed = Json::decode($config->get('contexts_used'));
    }

    if (array_search($context, $contextsUsed) === FALSE) {
      array_push($contextsUsed, $context);
      sort($contextsUsed);
    }

    $contextsUsed = Json::encode($contextsUsed);

    $config->set('contexts_used', $contextsUsed);

    $config->save();

    $this->messenger()->addMessage($this->t('Strings have been added'));

    if (!empty($this->moduleHandler->moduleExists('strings_i18n_json_export'))) {

      // We need to use ::service because this project is optional and for this
      // reason, we can't use dependency injection @codingStandardsIgnoreLine
      \Drupal::service('strings_i18n_json_export.json')->exportAllJsonFiles();
    }
  }

  /**
   * Get an array with strings to translate.
   */
  public function getStringsToTranslate($strings) {

    if (empty($strings)) {
      return FALSE;
    }

    $stringsToTranslate = explode(PHP_EOL, $strings);

    foreach ($stringsToTranslate as $key => $value) {
      $stringsToTranslate[$key] = trim($value);
    }

    return $stringsToTranslate;
  }

}
