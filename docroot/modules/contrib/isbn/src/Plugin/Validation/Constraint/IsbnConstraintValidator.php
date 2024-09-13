<?php

namespace Drupal\isbn\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Utility\Error;
use Drupal\isbn\IsbnToolsServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates if strings are valid ISBN numbers.
 */
class IsbnConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use LoggerChannelTrait;

  /**
   * The ISBN Tools service.
   *
   * @var \Drupal\isbn\IsbnToolsServiceInterface
   */
  protected $isbnTools;

  /**
   * Constructs a IsbnConstraintValidator object.
   *
   * @param \Drupal\isbn\IsbnToolsServiceInterface $isbn_tools
   *   The ISBN Tools service.
   */
  public function __construct(IsbnToolsServiceInterface $isbn_tools) {
    $this->isbnTools = $isbn_tools;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('isbn.isbn_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    try {
      if (gettype($value) == 'string') {
        if (!$this->isbnTools->isValidIsbn($value)) {
          $this->context->addViolation(t('"%isbn" isn\'t a valid ISBN number.', ['%isbn' => $value]));
        }
      }
    }
    catch (\RuntimeException $e) {
      // Available since Drupal 10.1.0.
      if (method_exists(Error::class, 'logException')) {
        Error::logException($this->getLogger('isbn'), $e);
      }
      else {
        // @phpstan-ignore-next-line
        watchdog_exception('isbn', $e);
      }
      $this->context->addViolation(t('An error occurred while trying to validate the ISBN number. Refer to the logs for more info.'));
    }
  }

}
