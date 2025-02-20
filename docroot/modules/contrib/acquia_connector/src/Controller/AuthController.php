<?php

declare(strict_types=1);

namespace Drupal\acquia_connector\Controller;

use Drupal\acquia_connector\AuthService;
use Drupal\acquia_connector\Form\ApiKeyCredentialForm;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Authorization controller for Acquia Cloud OAuth.
 */
final class AuthController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private RendererInterface $renderer;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private RequestStack $requestStack;

  /**
   * The auth service.
   *
   * @var \Drupal\acquia_connector\AuthService
   */
  private AuthService $authService;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private MessengerInterface $messenger;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * Module List for getting the module's path.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private ModuleExtensionList $moduleList;

  /**
   * Form builder to return API credentials form.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  private FormBuilder $formBuilder;

  /**
   * Construct a new AuthController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\acquia_connector\AuthService $auth_service
   *   The auth service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   Module Extension List.
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder service.
   */
  public function __construct(RendererInterface $renderer, RequestStack $request_stack, AuthService $auth_service, MessengerInterface $messenger, LoggerInterface $logger, ModuleExtensionList $module_list, FormBuilder $form_builder) {
    $this->renderer = $renderer;
    $this->requestStack = $request_stack;
    $this->authService = $auth_service;
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->moduleList = $module_list;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = new self(
      $container->get('renderer'),
      $container->get('request_stack'),
      $container->get('acquia_connector.auth_service'),
      $container->get('messenger'),
      $container->get('acquia_connector.logger_channel'),
      $container->get('extension.list.module'),
      $container->get('form_builder')
    );
    $instance->setStringTranslation($container->get('string_translation'));
    return $instance;
  }

  /**
   * The setup landing page.
   *
   * @return array
   *   The build array.
   */
  public function setup(): array {
    // Redirect to API Creds form if client id is invalid.
    if (!$this->authService->authorizeClientId()) {
      return $this->formBuilder->getForm(ApiKeyCredentialForm::class);
    }

    return [
      '#theme' => 'acquia_connector_banner',
      '#attached' => [
        'library' => [
          'acquia_connector/acquia_connector.form',
        ],
      ],
      '#attributes' => [
        'path' => $this->moduleList->getPath('acquia_connector'),
      ],
      'actions' => [
        '#type' => 'actions',
        '#weight' => 0,
        'continue' => [
          '#type' => 'link',
          '#title' => $this->t('Authenticate with Acquia Cloud'),
          '#url' => Url::fromRoute('acquia_connector.auth.begin'),
          '#cache' => [
            'max-age' => 0,
          ],
          '#attributes' => [
            'class' => ['button', 'button--primary'],
            'id' => 'acquia-connector-oauth',
          ],
        ],
        'manual' => [
          '#type' => 'link',
          '#title' => $this->t('Configure manually'),
          '#url' => Url::fromRoute('acquia_connector.setup_manual'),
          '#attributes' => [
            'class' => ['button'],
          ],
        ],
      ],
      'signup' => [
        '#markup' => $this->t('Need a subscription? <a href=":url">Get one</a>.', [
          ':url' => Url::fromUri('https://www.acquia.com/acquia-cloud-free')->getUri(),
        ]),
      ],
    ];
  }

  /**
   * Begins the API authorization process.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   The redirect response.
   */
  public function begin(): TrustedRedirectResponse {
    $context = new RenderContext();
    $response = $this->renderer->executeInRenderContext($context, function (): TrustedRedirectResponse {
      $url = $this->authService->getAuthUrl();
      $generated = $url->toString(TRUE);
      $response = new TrustedRedirectResponse($generated->getGeneratedUrl());
      $response
        ->getCacheableMetadata()
        ->setCacheMaxAge(0);
      $response->addCacheableDependency($generated);
      return $response;
    });
    assert($response instanceof TrustedRedirectResponse);
    if (!$context->isEmpty()) {
      $response->addCacheableDependency($context->pop());
    }
    return $response;
  }

  /**
   * Finalizes the OAuth authorization process when the user returns.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function return(): RedirectResponse {
    $request = $this->requestStack->getCurrentRequest();
    assert($request !== NULL);
    $code = $request->query->get('code', '');
    $state = $request->query->get('state', '');

    try {
      $this->authService->finalize($code, $state);
      return new RedirectResponse(
        Url::fromRoute('acquia_connector.setup_configure')->toString()
      );
    }
    catch (\Throwable $e) {
      $this->messenger->addError($this->t('We could not retrieve account data, please re-authorize with your Acquia Cloud account. For more information check <a target="_blank" href=":url">this link</a>.', [
        ':url' => Url::fromUri('https://docs.acquia.com/cloud-platform/known-issues/#unable-to-log-in-through-acquia-connector')->getUri(),
      ]));
      $this->logger->error('Unable to finalize OAuth handshake with Acquia Cloud: @error', [
        '@error' => trim($e->getMessage()),
      ]);
    }
    return new RedirectResponse(
      Url::fromRoute('acquia_connector.setup_oauth')->toString()
    );
  }

}
