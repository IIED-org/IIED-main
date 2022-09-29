<?php

namespace Drupal\Tests\media_revisions_ui\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests media revisions UI.
 *
 * @group media_revisions_ui
 */
class MediaRevisionsUiTest extends MediaRevisionsTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'media',
    'media_test_source',
    'media_revisions_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User to test media revisions tab.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Media type entity.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $mediaType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->mediaType = $this->createMediaType('test');
  }

  /**
   * Tests reverting a revision.
   */
  public function testRevert() {
    $media = Media::create([
      'bundle' => $this->mediaType->id(),
      'name' => 'Test media',
    ]);
    $media->save();
    $user = $this->drupalCreateUser([
      'administer media',
      'view all media revisions',
    ]);
    $this->createMediaRevision($media);
    $this->assertRevisionsListStatusCode($user, $media, 200);
    $this->clickLink('Revert');
    $this->assertSession()->statusCodeEquals(200);
    $this->getSession()->getPage()->pressButton('Revert');
    $this->assertSession()->pageTextContains('Media Test media has been reverted');
  }

  /**
   * Logs in a user, visits media revisions list page and asserts status code.
   *
   * @param \Drupal\user\Entity\User $user
   *   User to log in.
   * @param \Drupal\Core\Entity\EntityInterface $media
   *   Media from which to load revisions list.
   * @param int $expectedStatusCode
   *   Expected status code when visiting revisions list.
   */
  protected function assertRevisionsListStatusCode(User $user, EntityInterface $media, int $expectedStatusCode) {
    $this->drupalLogin($user);
    $this->drupalGet("/media/{$media->id()}/revisions");
    $this->assertSession()->statusCodeEquals($expectedStatusCode);
  }

}
