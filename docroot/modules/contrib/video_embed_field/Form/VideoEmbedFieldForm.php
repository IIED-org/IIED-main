<?php

namespace Drupal\video_embed_media\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\media_library\Form\AddFormBase;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\media_library\OpenerResolverInterface;
use Drupal\video_embed_field\ProviderManager;
use Drupal\video_embed_media\Plugin\media\Source\VideoEmbedFieldInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a form to create media entities from Video Embed fields.
 */
class VideoEmbedFieldForm extends AddFormBase {

  /**
   * The video provider manager.
   *
   * @var \Drupal\video_embed_field\ProviderManager
   */
  protected $providerManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new OEmbedForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\media_library\MediaLibraryUiBuilder $library_ui_builder
   *   The media library UI builder.
   * @param \Drupal\video_embed_field\ProviderManager $provider_manager
   *   The video provider plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\media_library\OpenerResolverInterface|null $opener_resolver
   *   The opener resolver.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MediaLibraryUiBuilder $library_ui_builder, ProviderManager $provider_manager, RendererInterface $renderer, OpenerResolverInterface $opener_resolver = NULL) {
    parent::__construct($entity_type_manager, $library_ui_builder, $opener_resolver);
    $this->providerManager = $provider_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('media_library.ui_builder'),
      $container->get('video_embed_field.provider_manager'),
      $container->get('renderer'),
      $container->get('media_library.opener_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return $this->getBaseFormId() . '_video_embed';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMediaType(FormStateInterface $form_state) {
    if ($this->mediaType) {
      return $this->mediaType;
    }

    $media_type = parent::getMediaType($form_state);
    if (!$media_type->getSource() instanceof VideoEmbedFieldInterface) {
      throw new \InvalidArgumentException('Can only add media types which use an Video Embed source plugin.');
    }
    return $media_type;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    $media_type = $this->getMediaType($form_state);
    $data_definition = $media_type->getSource()->getSourceFieldDefinition($media_type)
      ->getItemDefinition();

    // Add a container to group the input elements for styling purposes.
    $form['container'] = [
      '#type' => 'container',
    ];

    $form['container']['video_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add @type URL', [
        '@type' => $this->getMediaType($form_state)->label(),
      ]),
      '#maxlength' => $data_definition->getSetting('max_length'),
      '#allowed_providers' => $data_definition->getSetting('allowed_providers'),
      '#theme' => 'input__video',
      '#required' => TRUE,
    ];

    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#validate' => ['::validateUrl'],
      '#submit' => ['::addButtonSubmit'],
      // @todo Move validation in https://www.drupal.org/node/2988215
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        // Add a fixed URL to post the form since AJAX forms are automatically
        // posted to <current> instead of $form['#action'].
        // @todo Remove when https://www.drupal.org/project/drupal/issues/2504115
        //   is fixed.
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
              FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
            ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Validates the oEmbed URL.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateUrl(array &$form, FormStateInterface $form_state) {
    $provider = $this->getProvider($form_state->getValue('video_url'));
    // Display an error if no provider can be loaded for this video.
    if (!$provider) {
      $form_state->setErrorByName('video_url', $this->t('Could not find a video provider to handle the given URL.'));
    }
  }

  /**
   * Submit handler for the add button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state) {
    $this->processInputValues([$form_state->getValue('video_url')], $form, $form_state);
  }

  /**
   * Get a provider from some input.
   *
   * @param string $input
   *   The input string.
   *
   * @return bool|\Drupal\video_embed_field\ProviderPluginInterface
   *   A video provider or FALSE on failure.
   */
  protected function getProvider(string $input) {
    return $this->providerManager->loadProviderFromInput($input);
  }

}
