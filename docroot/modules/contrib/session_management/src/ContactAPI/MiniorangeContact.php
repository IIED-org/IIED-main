<?php

namespace Drupal\session_management\ContactAPI;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;

/**
 * Class to handle the miniOrange support related queries.
 */
class MiniorangeContact implements MiniorangeContactInterface {

  /**
   * Email of customer sending the query.
   */
  protected string $customerEmail;

  public const SUPPORT_EMAIL = '<a href="mailto:drupalsupport@xecurify.com" target="_blank">drupalsupport@xecurify.com</a>';

  /**
   * Email to send query.
   */
  protected string $ccEmail = 'drupalsupport@xecurify.com';

  /**
   * Name to send the email.
   */
  protected string $supportName = 'drupalsupport';

  /**
   * Name to send email from.
   */
  protected string $fromName = 'miniOrange';

  /**
   * Customer number/key.
   */
  protected string $customerKey = '16555';

  /**
   * API key for the customer.
   */
  protected string $apiKey = 'fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq';

  /**
   * Content to send in email body.
   */
  protected string $content;

  /**
   * Http client to send the query.
   */
  protected ClientInterface $httpClient;

  public function __construct($customerEmail = NULL) {
    $this->customerEmail = $customerEmail ?? \Drupal::currentUser()->getEmail();
    $this->httpClient = \Drupal::httpClient();
  }

  /**
   * {@inheritdoc}
   */
  public function notify(string $subject, string $content): mixed {

    $fields = [
      'customerKey' => $this->customerKey,
      'sendEmail' => TRUE,
      'email' => [
        'customerKey' => $this->customerKey,
        'fromEmail' => $this->customerEmail,
        'fromName' => $this->getFromName(),
        'toEmail' => $this->ccEmail,
        'toName'  => $this->supportName,
        'subject' => $subject,
        'content' => $content,
      ],
    ];
    $currentTimeInMillis = $this->getTimestamp();
    $header = [
      'Content-Type' => 'application/json',
      'Customer-Key' => $this->customerKey,
      'Timestamp' => $currentTimeInMillis,
      'Authorization' => hash("sha512", $this->customerKey . $currentTimeInMillis . $this->apiKey),
    ];

    try {
      $response = $this->httpClient->request('POST', self::NOTIFY, [
        'body' => Json::encode($fields),
        'allow_redirects' => TRUE,
        'http_errors' => FALSE,
        'decode_content'  => TRUE,
        'verify' => FALSE,
        'headers' => $header,
      ]);

      return Json::decode($response->getBody()->getContents());
    }
    catch (\Exception $e) {
      return FALSE;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function contact($query) {

    $fields = [
      'company' => $this->getBaseUrl(),
      'email' => $this->customerEmail,
      'phone' => '',
      'ccEmail' => $this->ccEmail,
      'query' => $query,
    ];

    $header = [
      'Content-Type' => 'application/json',
      'charset' => 'UTF-8',
      'Authorization' => 'Basic',
    ];

    try {
      $response = $this->httpClient
        ->request('POST', self::CONTACT_US, [
          'body' => Json::encode($fields),
          'allow_redirects' => TRUE,
          'http_errors' => FALSE,
          'decode_content'  => TRUE,
          'verify' => FALSE,
          'headers' => $header,
        ]);

      return [
        'code' => $response->getStatusCode(),
        'message' => $response->getBody()->getContents(),
      ];
    }
    catch (\Exception $e) {
      return FALSE;
    }

  }

  /**
   * Return the timestamp in millisecond.
   */
  private function getTimestamp(): mixed {

    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'allow_redirects' => TRUE,
      'http_errors' => FALSE,
      'decode_content'  => TRUE,
      'verify' => FALSE,
      'body' => Json::encode([]),
    ];

    try {
      $response = $this->httpClient->request('POST', self::GET_TIMESTAMP, $options);

      $content = $response->getBody()->getContents();
      $status_code = $response->getStatusCode();

      if (empty($content)) {
        $currentTimeInMillis = round(microtime(TRUE) * 1000);
        return number_format($currentTimeInMillis, 0, '', '');
      }

      return $content;
    }
    catch (GuzzleException $e) {
      return FALSE;
    }

    return '';
  }

  /**
   * Set the customer email.
   */
  public function setCustomerEmail(string $customerEmail): void {
    $this->customerEmail = $customerEmail;
  }

  /**
   * Return the base url of the site.
   */
  public function getBaseUrl(): string {
    $request = \Drupal::request();
    return $request->getSchemeAndHttpHost() . $request->getBasePath();
  }

  /**
   * Return FromName value.
   */
  public function getFromName(): string {
    return $this->fromName;
  }

  /**
   * Set the FromName value.
   */
  public function setFromName(string $fromName): void {
    $this->fromName = $fromName;
  }

}
