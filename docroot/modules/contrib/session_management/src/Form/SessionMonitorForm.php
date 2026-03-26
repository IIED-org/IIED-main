<?php

namespace Drupal\session_management\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\session_management\Browser;
use Drupal\session_management\SessionMonitorInterface;
use Drupal\session_management\Utilities;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class to show the user active sessions list.
 */
class SessionMonitorForm extends FormBase
{

  /**
   * Session monitor service.
   *
   * @var \Drupal\session_management\SessionMonitorInterface
   */
  private SessionMonitorInterface $sessionMonitor;

  /**
   * The Data formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private DateFormatterInterface $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'session-monitor-form';
  }

  public function __construct(ConfigFactoryInterface $configFactory, SessionMonitorInterface $sessionMonitor, DateFormatterInterface $dateFormatter)
  {
    $this->configFactory = $configFactory->getEditable('session_management.settings');
    $this->sessionMonitor = $sessionMonitor;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new self(
      $container->get('config.factory'),
      $container->get('mo_session_monitor'),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL)
  {
    $form['libraries'] = [
      '#attached' => [
        'library' => [
          "session_management/session_management.mo_session",
        ],
      ],
    ];

    $premium_tag = Utilities::getPremiumBadge();

    $queryDelete = \Drupal::request()->get('delete');
    if ($queryDelete) {
      $this->messenger()->addWarning($this->t("You do not have permission to delete the session. Please contact your administrator for access."));
    }

    $url = Url::fromRoute('session_management.session_manage', ['user' => $user->id()]);
    $url->setOption('query', ['delete' => "session"]);
    $url = $url->toString();

    $header = $this->getSessionTableHeader();
    $rows = [];

    $sessions = $this->sessionMonitor->getSessions($user);

    $form['#title'] = $this->t('Your Sessions');

    // @todo handle the Masquerade Session.
    foreach ($sessions as $i => $session) {
      $style = $this->sessionMonitor->isCurrentActiveSession($session['sid']) ? 'font-weight:600;background-color:#e1f8ff;text-align:center;' : 'text-align:center;';
      $hostname = $this->sessionMonitor->isCurrentActiveSession($session['sid']) ? '(Current Session)' : '';

      $userAgent = $this->sessionMonitor->getStoredSessionData($session['session'])['_sf2_attributes']['mo_browser'] ?? "";

      $rows[$session['sid']] = [
        [
          'data' => $session['hostname'] . $hostname,
          'style' => $style,
        ],
        [
          'data' => Markup::create($this->getBrowserName($userAgent)),
          'style' => $style,
        ],
        [
          'data' => Markup::create($this->getDeviceName($userAgent)),
          'style' => $style,
        ],
        [
          'data' => $this->formatDateTime($session['timestamp']),
          'style' => $style,
        ],
        [
          'data' => $user->hasRole('administrator') ? t("Delete $premium_tag") : $this->t("<a  href='$url' title='This is a premium feature. Please contact your administrator for access.'>Delete</a>"),
          'style' => $style,
        ],
      ];
    }

    $form['info'] = [
      '#markup' => $this->t('Hi @user, your all current active sessions are listed below.', ['@user' => $user->getDisplayName()]),
    ];

    $form['user_session_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#prefix' => '<br><br>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Return user Session Table header.
   */
  public function getSessionTableHeader(): array
  {

    $style = 'text-align: center';
    return [
      [
        'data' => $this->t('Hostname'),
        'style' => $style,
        'title' => $this->t('The IP address that last used this session.'),
      ],
      [
        'data' => $this->t('Browser'),
        'style' => $style,
        'title' => $this->t('The Browser Name the session is present on.'),
      ],
      [
        'data' => $this->t('Device'),
        'style' => $style,
        'title' => $this->t('The Device Name the session is present on.'),
      ],
      [
        'data' => $this->t('Last Activity At'),
        'style' => $style,
        'title' => $this->t('The time when this session last requested.'),
      ],
      [
        'data' => $this->t('Operation'),
        'style' => $style,
        'title' => $this->t('Delete the session if not recognise by you.'),
      ],
    ];
  }

  /**
   * Return user Session Date and time in configured format.
   */
  public function formatDateTime($timestamp): string
  {
    $day_time_format = $this->configFactory->get('date_time_format');
    return $day_time_format === 'time_passed' ? $this->dateFormatter->formatTimeDiffSince($timestamp) . ' ago' : $this->dateFormatter->format($timestamp, 'custom', $day_time_format);
  }

  /**
   * Return user Browser name.
   */
  public function getBrowserName($userAgent): string
  {
    return empty($userAgent) ? 'Unknown' : (new Browser($userAgent))->getBrowser();
  }

  /**
   * Return user Device name.
   */
  public function getDeviceName($userAgent): string
  {
    return empty($userAgent) ? 'Unknown' : (new Browser($userAgent))->getPlatform();
  }
}
