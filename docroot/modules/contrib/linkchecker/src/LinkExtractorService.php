<?php

namespace Drupal\linkchecker;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\linkchecker\Entity\LinkCheckerLink;
use Drupal\linkchecker\Plugin\LinkExtractorManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LinkExtractor.
 */
class LinkExtractorService {

  /**
   * The extractor manager.
   *
   * @var \Drupal\linkchecker\Plugin\LinkExtractorManager
   */
  protected $extractorManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Linkchecker settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $linkcheckerSetting;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Position in the urls array to avoid erase merge.
   *
   * @var int
   */
  protected $pos;

  /**
   * Constructs a new LinkExtractor object.
   */
  public function __construct(LinkExtractorManager $extractorManager, EntityTypeManagerInterface $entityTypeManager, ConfigFactory $configFactory, RequestStack $requestStack, Connection $dbConnection, TimeInterface $time) {
    $this->extractorManager = $extractorManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->linkcheckerSetting = $configFactory->get('linkchecker.settings');
    $this->request = $requestStack->getCurrentRequest();
    $this->database = $dbConnection;
    $this->time = $time;
  }

  /**
   * Extracts links from entity fields.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Entity from which to extract.
   *
   * @return \Drupal\linkchecker\LinkCheckerLinkInterface[]
   *   Array of extracted links.
   */
  public function extractFromEntity(FieldableEntityInterface $entity) {
    $links = [];
    $this->pos = 0;
    foreach ($entity->getFieldDefinitions() as $fieldDefinition) {
      if ($entity instanceof TranslatableInterface && $fieldDefinition->isTranslatable()) {
        foreach ($entity->getTranslationLanguages() as $language) {
          $translation = $entity->getTranslation($language->getId());
          $links += $this->extractFromField($translation->get($fieldDefinition->getName()));
        }
      }
      else {
        $links += $this->extractFromField($entity->get($fieldDefinition->getName()));
      }
    }

    return $links;
  }

  /**
   * Extracts links from field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $fieldItemList
   *   The field from which to extract.
   *
   * @return \Drupal\linkchecker\LinkCheckerLinkInterface[]
   *   Array of extracted links.
   */
  public function extractFromField(FieldItemListInterface $fieldItemList) {
    $urls = [];

    $entity = $fieldItemList->getEntity();
    $entityBundle = $fieldItemList->getEntity()->bundle();
    $fieldConfig = $fieldItemList->getFieldDefinition()
      ->getConfig($entityBundle);

    $scan = $fieldConfig->getThirdPartySetting('linkchecker', 'scan', FALSE);

    if ($scan) {
      try {
        $baseContentUrl = $entity
          ->toUrl()
          ->setAbsolute()
          ->toString();
      }
      catch (\Exception $e) {
        $baseContentUrl = NULL;
      }

      $extractorName = $fieldConfig->getThirdPartySetting('linkchecker', 'extractor', NULL);
      /** @var \Drupal\linkchecker\Plugin\LinkExtractorInterface $extractor */
      $extractor = $this->extractorManager->createInstance($extractorName);
      $urls = $extractor->extract($fieldItemList->getValue());

      // Remove empty values.
      $urls = array_filter($urls);
      // Remove duplicate urls.
      $urls = array_unique($urls);

      $urls = $this->getLinks($urls, $baseContentUrl);
    }

    $linkCheckerLinks = [];
    foreach ($urls as $link) {
      $linkCheckerLinks[$this->pos] = LinkCheckerLink::create([
        'url' => $link,
        'entity_id' => [
          'target_id' => $entity->id(),
          'target_type' => $entity->getEntityTypeId(),
        ],
        'entity_field' => $fieldItemList->getFieldDefinition()->getName(),
        'entity_langcode' => $fieldItemList->getLangcode(),
      ]);
      $this->pos++;
    }

    return $linkCheckerLinks;
  }

  /**
   * Filters URL that do not need to check.
   *
   * @param array $urls
   *   Array of URLs.
   * @param string $baseContentUrl
   *   Base URL for internal, not absolute urls.
   *
   * @return array
   *   List of links.
   */
  public function getLinks(array $urls, $baseContentUrl = NULL) {
    // What type of links should be checked?
    $checkLinksType = $this->linkcheckerSetting->get('check_links_types');
    if (isset($this->request)) {
      $httpProtocol = $this->request->getScheme() . '://';
      $baseUrl = $this->request->getSchemeAndHttpHost();
    }
    else {
      $httpProtocol = $this->linkcheckerSetting->get('default_url_scheme');
      $baseUrl = $httpProtocol . $this->linkcheckerSetting->get('base_path');
    }

    if (empty($baseContentUrl)) {
      $baseContentUrl = $baseUrl;
    }

    $links = [];
    foreach ($urls as $url) {
      // Decode HTML links into plain text links.
      // DOMDocument->loadHTML does not provide the RAW url from code. All html
      // entities are already decoded.
      // @todo: Try to find a way to get the raw value.
      $urlDecoded = $url;

      // Prefix protocol relative urls with a protocol to allow link checking.
      if (preg_match('!^//!', $urlDecoded)) {
        $urlDecoded = $httpProtocol . ':' . $urlDecoded;
      }

      // FIXME: #1149596 HACK - Encode spaces in URLs, so validation equals TRUE
      // and link gets added.
      $urlEncoded = str_replace(' ', '%20', $urlDecoded);

      // Full qualified URLs.
      if ($checkLinksType != LinkCheckerLinkInterface::TYPE_INTERNAL && UrlHelper::isValid($urlEncoded, TRUE)) {
        // Add to Array and change HTML links into plain text links.
        $links[$urlDecoded] = $url;
      }
      // Skip mailto:, javascript:, etc.
      elseif (preg_match('/^\w[\w.+]*:/', $urlDecoded)) {
        continue;
      }
      elseif ($checkLinksType != LinkCheckerLinkInterface::TYPE_EXTERNAL && UrlHelper::isValid($urlEncoded, FALSE)) {
        $absoluteContentPath = $this->getAbsoluteContentPath($baseContentUrl);
        // Absolute local URLs need to start with [/].
        if (preg_match('!^/!', $urlDecoded)) {
          // Add to Array and change HTML encoded links into plain text links.
          $links[$baseUrl . $urlDecoded] = $baseUrl . $url;
        }
        // Anchors and URL parameters like "#foo" and "?foo=bar".
        elseif (!empty($baseContentUrl) && preg_match('!^[?#]!', $urlDecoded)) {
          // Add to Array and change HTML encoded links into plain text links.
          $links[$baseContentUrl . $baseContentUrl] = $baseContentUrl . $url;
        }
        // Relative URLs like "./foo/bar" and "../foo/bar".
        elseif (!empty($absoluteContentPath) && preg_match('!^\.{1,2}/!', $urlDecoded)) {
          // Build the URI without hostname before the URI is normalized and
          // dot-segments will be removed. The hostname is added back after the
          // normalization has completed to prevent hostname removal by the
          // regex. This logic intentionally does not implement all the rules
          // defined in RFC 3986, section 5.2.4 to show broken links and
          // over-dot-segmented URIs; e.g., https://example.com/../../foo/bar.
          // For more information, see https://drupal.org/node/832388.
          $path = substr_replace($absoluteContentPath . $urlDecoded, '', 0, strlen($baseUrl));

          // Remove './' segments where possible.
          $path = str_replace('/./', '/', $path);

          // Remove '../' segments where possible. Loop until all segments are
          // removed. Taken over from _drupal_build_css_path() in common.inc.
          $last = '';
          while ($path != $last) {
            $last = $path;
            $path = preg_replace('`(^|/)(?!\.\./)([^/]+)/\.\./`', '$1', $path);
          }

          // Glue the hostname and path to full-qualified URI.
          $links[$baseUrl . $path] = $baseUrl . $path;
        }
        // Relative URLs like "test.png".
        elseif (!empty($absoluteContentPath) && preg_match('!^[^/]!', $urlDecoded)) {
          $links[$absoluteContentPath . $url] = $absoluteContentPath . $url;
        }
        else {
          // @todo Are there more special cases the module need to handle?
        }
      }
    }

    return array_filter($links, function ($url) {
      return !$this->isUrlBlacklisted($url);
    });
  }

  /**
   * Checks if link was not removed from content.
   *
   * If link becomes blacklisted this method will return false.
   *
   * @param \Drupal\linkchecker\LinkCheckerLinkInterface $link
   *   Link to check.
   *
   * @return bool
   *   TRUE if link exists in a content.
   */
  public function isLinkExists(LinkCheckerLinkInterface $link) {
    $entity = $link->getParentEntity();

    // If entity was removed.
    if (!isset($entity)) {
      return FALSE;
    }

    if ($entity instanceof TranslatableInterface) {
      if ($entity->hasTranslation($link->getParentEntityLangcode())) {
        $entity = $entity->getTranslation($link->getParentEntityLangcode());
      }
      // If translation with link was removed - FALSE.
      else {
        return FALSE;
      }
    }

    // If field was removed - FALSE.
    if (!$entity->hasField($link->getParentEntityFieldName())) {
      return FALSE;
    }

    $links = $this->extractFromField($entity->get($link->getParentEntityFieldName()));

    foreach ($links as $extractedLink) {
      if (LinkCheckerLink::generateHash($extractedLink->getUrl()) == $link->getHash()) {
        return TRUE;
      }
    }

    // Link was removed from content.
    return FALSE;
  }

  /**
   * Helper function to save links.
   *
   * Saves link if it not a duplicate.
   *
   * @param \Drupal\linkchecker\LinkCheckerLinkInterface[] $links
   *   Array of links to save.
   *
   * @todo should we move this method to entity storage?
   */
  public function saveLinkMultiple(array $links) {
    foreach ($links as $link) {
      $this->saveLink($link);
    }
  }

  /**
   * Helper function to save link.
   *
   * Saves link if it not a duplicate.
   *
   * @param \Drupal\linkchecker\LinkCheckerLinkInterface $link
   *   Link to save.
   *
   * @todo should we move this method to entity storage?
   */
  public function saveLink(LinkCheckerLinkInterface $link) {
    $storage = $this->entityTypeManager->getStorage($link->getEntityTypeId());

    $query = $storage->getQuery();
    $query->accessCheck()
      ->condition('urlhash', LinkCheckerLink::generateHash($link->getUrl()))
      ->condition('entity_id.target_id', $link->getParentEntity()->id())
      ->condition('entity_id.target_type', $link->getParentEntity()
        ->getEntityTypeId())
      ->condition('entity_field', $link->getParentEntityFieldName())
      ->condition('entity_langcode', $link->getParentEntityLangcode());
    $ids = $query->execute();

    if (empty($ids)) {
      $link->save();
    }
  }

  /**
   * Adds or updates extract index for given entity.
   *
   * This should be run after saving extracted links from given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   */
  public function updateEntityExtractIndex(FieldableEntityInterface $entity) {
    // We can`t use Connection::upsert() here cause primary key consist of two
    // columns, entity_id and entity_type.
    $isExistsQuery = $this->database->select('linkchecker_index', 'i');
    $isExistsQuery->fields('i');
    $isExistsQuery->condition('entity_id', $entity->id());
    $isExistsQuery->condition('entity_type', $entity->getEntityTypeId());
    $isExistsQuery->range(0, 1);
    $isExists = $isExistsQuery->execute()->fetchField();

    if (empty($isExists)) {
      $this->database
        ->insert('linkchecker_index')
        ->fields([
          'entity_id' => $entity->id(),
          'entity_type' => $entity->getEntityTypeId(),
          'last_extracted_time' => $this->time->getCurrentTime(),
        ])
        ->execute();
    }
    else {
      $this->database->update('linkchecker_index')
        ->fields(['last_extracted_time' => $this->time->getCurrentTime()])
        ->condition('entity_id', $entity->id())
        ->condition('entity_type', $entity->getEntityTypeId())
        ->execute();
    }
  }

  /**
   * Verifies against blacklists, if the link status should be checked or not.
   *
   * @param string $url
   *   The URL to check.
   *
   * @return bool
   *   TRUE if URL should be checked.
   */
  protected function isUrlBlacklisted($url) {
    if (mb_strlen($url) > 2048) {
      // The URL is too long for Drupal to save/process. So ignore it.
      return TRUE;
    }

    // Is url in domain blacklist?
    $urls = $this->linkcheckerSetting->get('check.disable_link_check_for_urls');
    if (!empty($urls) && preg_match('/' . implode('|', array_map(function ($links) {return preg_quote($links, '/');}, preg_split('/(\r\n?|\n)/', $urls))) . '/', $url)) {
      return TRUE;
    }

    // Protocol whitelist check (without curl, only http/https is supported).
    if (!preg_match('/^(https?):\/\//i', $url)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get the path of an URL.
   *
   * @param string $url
   *   The http/https URL to parse.
   *
   * @return string
   *   Full qualified URL with absolute path of the URL.
   */
  protected function getAbsoluteContentPath($url) {
    // Parse the URL and make sure we can handle the schema.
    $uri = @parse_url($url);

    if ($uri == FALSE) {
      return NULL;
    }

    if (!isset($uri['scheme'])) {
      return NULL;
    }

    // Break if the schema is not supported.
    if (!in_array($uri['scheme'], ['http', 'https'])) {
      return NULL;
    }

    $scheme = isset($uri['scheme']) ? $uri['scheme'] . '://' : '';
    $user = isset($uri['user']) ? $uri['user'] . ($uri['pass'] ? ':' . $uri['pass'] : '') . '@' : '';
    $port = isset($uri['port']) ? $uri['port'] : 80;
    $host = $uri['host'] . ($port != 80 ? ':' . $port : '');
    $path = isset($uri['path']) ? $uri['path'] : '/';

    // Glue the URL variables.
    $absoluteUrl = $scheme . $user . $host . $path;

    // Find the last slash and remove all after the last slash to get the path.
    $lastSlash = strrpos($absoluteUrl, '/');
    $absoluteContentPath = mb_substr($absoluteUrl, 0, $lastSlash + 1);

    return $absoluteContentPath;
  }

}
