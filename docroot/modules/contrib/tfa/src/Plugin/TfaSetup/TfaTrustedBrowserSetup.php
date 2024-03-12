<?php

namespace Drupal\tfa\Plugin\TfaSetup;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\tfa\Plugin\TfaLogin\TfaTrustedBrowser;
use Drupal\tfa\Plugin\TfaSetupInterface;

/**
 * TFA Trusted Browser Setup Plugin.
 *
 * @TfaSetup(
 *   id = "tfa_trusted_browser_setup",
 *   label = @Translation("TFA Trusted Browser Setup"),
 *   description = @Translation("TFA Trusted Browser Setup Plugin"),
 *   setupMessages = {
 *    "saved" = @Translation("Browser saved."),
 *    "skipped" = @Translation("Browser not saved.")
 *   }
 * )
 */
class TfaTrustedBrowserSetup extends TfaTrustedBrowser implements TfaSetupInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getSetupForm(array $form, FormStateInterface $form_state) {
    $existing = $this->getTrustedBrowsers();
    $form['info'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t("Trusted browsers are a method for
      simplifying login by avoiding verification code entry for a set amount of
      time, @time days from marking a browser as trusted. After @time days, to
      log in you'll need to enter a verification code with your username and
      password during which you can again mark the browser as trusted.",
      ['@time' => $this->expiration]) . '</p>',
    ];
    // Present option to trust this browser if it's not currently trusted.
    if (isset($_COOKIE[$this->cookieName]) && $this->trustedBrowser($_COOKIE[$this->cookieName]) !== FALSE) {
      $current_trusted = $_COOKIE[$this->cookieName];
    }
    else {
      $current_trusted = FALSE;
      $form['trust'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Trust this browser?'),
        '#default_value' => empty($existing) ? 1 : 0,
      ];
      // Optional field to name this browser.
      $form['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name this browser'),
        '#maxlength' => 255,
        '#description' => $this->t('Optionally, name the browser on your browser (e.g.
        "home firefox" or "office desktop windows"). Your current browser user
        agent is %browser', ['%browser' => $_SERVER['HTTP_USER_AGENT']]),
        '#default_value' => $this->getAgent(),
        '#states' => [
          'visible' => [
            ':input[name="trust"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
    if (!empty($existing)) {
      $form['existing'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Existing browsers'),
        '#description' => $this->t('Leave checked to keep these browsers in your trusted log in list.'),
        '#tree' => TRUE,
      ];

      foreach ($existing as $browser_id => $browser) {
        $date_formatter = \Drupal::service('date.formatter');
        $vars = [
          '@set' => $date_formatter->format($browser['created']),
        ];

        if (isset($browser['last_used'])) {
          $vars['@time'] = $date_formatter->format($browser['last_used']);
        }

        if ($current_trusted == $browser_id) {
          $name = '<strong>' . $this->t('@name (current browser)', ['@name' => $browser['name']]) . '</strong>';
        }
        else {
          $name = Html::escape($browser['name']);
        }

        if (empty($browser['last_used'])) {
          $message = $this->t('Marked trusted @set', $vars);
        }
        else {
          $message = $this->t('Marked trusted @set, last used for log in @time', $vars);
        }
        $form['existing']['trusted_browser_' . $browser_id] = [
          '#type' => 'checkbox',
          '#title' => $name,
          '#description' => $message,
          '#default_value' => 1,
        ];
      }
    }
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSetupForm(array $form, FormStateInterface $form_state) {
    // Do nothing, no validation required.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitSetupForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['existing'])) {
      $count = 0;
      foreach ($values['existing'] as $element => $value) {
        $id = str_replace('trusted_browser_', '', $element);
        if (!$value) {
          $this->deleteTrusted($id);
          $count++;
        }
      }
      if ($count) {
        \Drupal::logger('tfa')->notice('Removed @num TFA trusted browsers during trusted browser setup', ['@num' => $count]);
      }
    }
    if (!empty($values['trust']) && $values['trust']) {
      $name = '';
      if (!empty($values['name'])) {
        $name = $values['name'];
      }
      elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
        $name = $this->getAgent();
      }
      $this->setTrusted($this->generateBrowserId(), $name);
    }
    return TRUE;
  }

  /**
   * Get list of trusted browsers.
   *
   * @return array
   *   List of current trusted browsers.
   */
  public function getTrustedBrowsers() {
    return $this->getUserData('tfa', 'tfa_trusted_browser', $this->uid, $this->userData) ?: [];
  }

  /**
   * Delete a trusted browser by its ID.
   *
   * @param int $id
   *   ID of the browser to delete.
   *
   * @return bool
   *   TRUE if successful otherwise FALSE.
   */
  public function deleteTrustedId($id) {
    return $this->deleteTrusted($id);
  }

  /**
   * Delete all trusted browsers.
   *
   * @return bool
   *   TRUE if successful otherwise FALSE.
   */
  public function deleteTrustedBrowsers() {
    return $this->deleteTrusted();
  }

  /**
   * {@inheritdoc}
   */
  public function getOverview(array $params) {
    $trusted_browsers = [];
    foreach ($this->getTrustedBrowsers() as $device) {
      $date_formatter = \Drupal::service('date.formatter');
      $vars = [
        '@set' => $date_formatter->format($device['created']),
        '@browser' => $device['name'],
      ];
      if (empty($device['last_used'])) {
        $message = $this->t('@browser, set @set', $vars);
      }
      else {
        $vars['@time'] = $date_formatter->format($device['last_used']);
        $message = $this->t('@browser, set @set, last used @time', $vars);
      }
      $trusted_browsers[] = $message;
    }
    $output = [
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Trusted browsers'),
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Browsers that will not require a verification code during login.'),
      ],
    ];
    $output['list'] = [
      '#theme' => 'item_list',
      '#items' => $trusted_browsers,
      '#empty' => $this->t('No trusted browsers found.'),
    ];

    $output['link'] = [
      '#theme' => 'links',
      '#links' => [
        'admin' => [
          'title' => $this->t('Configure trusted browsers'),
          'url' => Url::fromRoute('tfa.validation.setup', [
            'user' => $params['account']->id(),
            'method' => $params['plugin_id'],
          ]),
        ],
      ],
    ];

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelpLinks() {
    return ($this->pluginDefinition['helpLinks']) ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSetupMessages() {
    return ($this->pluginDefinition['setupMessages']) ?: '';
  }

}
