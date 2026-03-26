<?php

namespace Drupal\session_management\ContactAPI;

/**
 * An interface for sending support queries.
 */
interface MiniorangeContactInterface {

  public const CONTACT_US = 'https://login.xecurify.com/moas/rest/customer/contact-us';
  public const NOTIFY = 'https://login.xecurify.com/moas/api/notify/send';
  public const GET_TIMESTAMP = 'https://login.xecurify.com/moas/rest/mobile/get-timestamp';

  /**
   * Notify the drupalsupport about the user query.
   *
   * @param string $subject
   *   the Subject line for the email.
   * @param string $content
   *   the body content of the Email. HTML allowed.
   *
   * @return mixed
   *   {"txId":"random_id","responseType":"NOTIFICATION",
   *   "status":"SUCCESS", "message":"Notification was sent successfully."}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function notify(string $subject, string $content);

  /**
   * Contact to the drupalsupport.
   */
  public function contact(string $query);

}
