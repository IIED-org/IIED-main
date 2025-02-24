<?php

namespace Drupal\clamav\EventSubscriber;

use Drupal\clamav\Scanner;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Validation\FileValidationEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Subscribes to the file validation event to add ClamAV scanning.
 */
class FileValidationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The ClamAV scanner service.
   *
   * @var \Drupal\clamav\Scanner
   */
  protected $clamav;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a FileValidationSubscriber.
   *
   * @param \Drupal\clamav\Scanner $clamav
   *   The ClamAV scanner service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(Scanner $clamav, LoggerInterface $logger) {
    $this->clamav = $clamav;
    $this->logger = $logger;
  }

  /**
   * Adds ClamAV scanning to file validation.
   *
   * @param \Drupal\file\Validation\FileValidationEvent $event
   *   The file validation event.
   */
  public function onFileValidate(FileValidationEvent $event) {
    $file = $event->file;
    $violations = $event->violations;

    // Check if the file needs to be scanned.
    if (empty($file->clamav_attemptScan)) {
      return;
    }

    if ($this->clamav->isEnabled() && $this->clamav->isScannable($file)) {
      $result = $this->clamav->scan($file);

      switch ($result) {
        case Scanner::FILE_IS_INFECTED:
          $message = $this->t('A virus has been detected in the file. The file will be deleted.');
          $violation = new ConstraintViolation($message, $message, [], $file, '', $file);
          $violations->add($violation);
          break;

        case Scanner::FILE_IS_UNCHECKED:
          if (!$this->clamav->allowUncheckedFiles()) {
            $message = $this->t('The anti-virus scanner could not check the file, so the file cannot be uploaded. Contact the site administrator if this problem persists.');
            $violation = new ConstraintViolation($message, $message, [], $file, '', $file);
            $violations->add($violation);
          }
          break;
      }
    }
    elseif ($this->clamav->isVerboseModeEnabled()) {
      $this->logger->info('Uploaded file %filename was not checked and was uploaded without scanning.', [
        '%filename' => $file->getFilename(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      FileValidationEvent::class => 'onFileValidate',
    ];
  }

}
