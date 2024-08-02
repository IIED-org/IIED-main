<?php

declare(strict_types=1);

namespace Drupal\gin_lb\HookHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\gin_lb\Service\ContextValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hook implementation.
 */
class PageAttachments implements ContainerInjectionInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The context validator.
   *
   * @var \Drupal\gin_lb\Service\ContextValidatorInterface
   */
  protected ContextValidatorInterface $contextValidator;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\gin_lb\Service\ContextValidatorInterface $contextValidator
   *   The context validator.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ContextValidatorInterface $contextValidator,
  ) {
    $this->configFactory = $configFactory;
    $this->contextValidator = $contextValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    // @phpstan-ignore-next-line
    return new static(
      $container->get('config.factory'),
      $container->get('gin_lb.context_validator')
    );
  }

  /**
   * Hook implementation.
   *
   * @param array &$attachments
   *   An array that you can add attachments to.
   */
  public function attachments(array &$attachments): void {
    if (!$this->contextValidator->isLayoutBuilderRoute()) {
      return;
    }

    $attachments['#attached']['library'][] = 'gin_lb/gin_lb_init';
    $attachments['#attached']['library'][] = 'gin_lb/offcanvas';
    $attachments['#attached']['library'][] = 'gin_lb/preview';
    $attachments['#attached']['library'][] = 'gin_lb/toolbar';
    $attachments['#attached']['library'][] = 'gin/gin_ckeditor';
    $attachments['#attached']['library'][] = 'claro/claro.jquery.ui';
    $attachments['#attached']['library'][] = 'gin_lb/gin_lb';
    $attachments['#attached']['library'][] = 'claro/global-styling';
    if (\Drupal::VERSION >= '10.0.0') {
      $attachments['#attached']['library'][] = 'gin_lb/gin_lb_10';
    }

    $config = $this->configFactory->get('gin_lb.settings');
    if ($config->get('toastify_loading') === 'cdn') {
      $attachments['#attached']['library'][] = 'gin_lb/toastify_cdn';
      $attachments['#attached']['library'][] = 'gin_lb/gin_lb_toastify';
    }
    elseif ($config->get('toastify_loading') === 'composer') {
      $attachments['#attached']['library'][] = 'gin_lb/toastify_composer';
      $attachments['#attached']['library'][] = 'gin_lb/gin_lb_toastify';
    }
  }

}
