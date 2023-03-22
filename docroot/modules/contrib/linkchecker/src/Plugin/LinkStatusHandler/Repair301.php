<?php

namespace Drupal\linkchecker\Plugin\LinkStatusHandler;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\linkchecker\LinkCheckerLinkInterface;
use Drupal\linkchecker\Plugin\LinkStatusHandlerBase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Repairs 301 links.
 *
 * @LinkStatusHandler(
 *   id = "repair_301",
 *   label = @Translation("Repair on 301"),
 *   status_codes = {
 *     301,
 *   }
 * )
 */
class Repair301 extends LinkStatusHandlerBase {

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Repair301 constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueueFactory $queueFactory, EntityTypeManagerInterface $entityTypeManager, AccountSwitcherInterface $accountSwitcher, ImmutableConfig $linkcheckerSetting, RequestStack $requestStack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $queueFactory, $entityTypeManager, $accountSwitcher, $linkcheckerSetting);
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('queue'),
      $container->get('entity_type.manager'),
      $container->get('account_switcher'),
      $container->get('config.factory')->get('linkchecker.settings'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getItems(LinkCheckerLinkInterface $link, ResponseInterface $response) {
    // A HTTP status code of 301 tells us an existing link have changed to
    // a new link. The remote site owner was so kind to provide us the new
    // link and if we trust this change we are able to replace the old link
    // with the new one without any hand work.
    $autoRepair301 = $this->linkcheckerSetting->get('error.action_status_code_301');
    $redirectUrl = $response->getHeaderLine('Location');

    if ($autoRepair301
      && $autoRepair301 <= $link->getFailCount()
      && UrlHelper::isValid($redirectUrl, TRUE)) {
      return parent::getItems($link, $response);
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doHandle(LinkCheckerLinkInterface $link, ResponseInterface $response, FieldableEntityInterface $entity) {
    $autoRepair301 = $this->linkcheckerSetting->get('error.action_status_code_301');
    $redirectUrl = $response->getHeaderLine('Location');

    if ($autoRepair301
      && $autoRepair301 <= $link->getFailCount()
      && UrlHelper::isValid($redirectUrl, TRUE)
      && $link->isExists()) {
      $values = $entity->get($link->getParentEntityFieldName())->getValue();

      foreach ($values as $key => $value) {
        if (isset($value['value'])) {
          $values[$key]['value'] = $this->linkReplace($value['value'], $link->getUrl(), $redirectUrl);
        }
      }

      $entity->set($link->getParentEntityFieldName(), $values);
      $entity->save();
    }
  }

  /**
   * Replaces old link with new link in text.
   *
   * @param string $value
   *   The text with a link inside.
   * @param string $oldLinkAbsolute
   *   The old link to search for in strings.
   * @param string $newLinkAbsolute
   *   The old link should be overwritten with this new link.
   *
   * @return string
   *   Text with new links.
   */
  protected function linkReplace($value, $oldLinkAbsolute, $newLinkAbsolute) {
    // Don't do any string replacement if one of the values is empty.
    if (empty($value) || empty($oldLinkAbsolute) || empty($newLinkAbsolute)) {
      return $value;
    }

    if (isset($this->request)) {
      $baseUrl = $this->request->getSchemeAndHttpHost();
    }
    else {
      $httpProtocol = $this->linkcheckerSetting->get('default_url_scheme');
      $baseUrl = $httpProtocol . $this->linkcheckerSetting->get('base_path');
    }

    // Remove protocols and hostname from local URLs.
    $baseRoot = mb_strtolower($baseUrl);
    $oldLink = str_replace($baseRoot, '', $oldLinkAbsolute);
    $newLink = str_replace($baseRoot, '', $newLinkAbsolute);

    // Build variables with all URLs and run check_url() only once.
    $oldHtmlLinkAbsolute = UrlHelper::filterBadProtocol($oldLinkAbsolute);
    $newHtmlLinkAbsolute = UrlHelper::filterBadProtocol($newLinkAbsolute);
    $oldHtmlLink = UrlHelper::filterBadProtocol($oldLink);
    $newHtmlLink = UrlHelper::filterBadProtocol($newLink);

    // Replace links in link fields and text and Links weblink fields.
    if (in_array($value, [
      $oldHtmlLinkAbsolute,
      $oldHtmlLink,
      $oldLinkAbsolute,
      $oldLink,
    ])) {
      // Keep old and new links in the same encoding and format and short or
      // fully qualified.
      $value = str_replace($oldHtmlLinkAbsolute, $newHtmlLinkAbsolute, $value);
      $value = str_replace($oldHtmlLink, $newHtmlLink, $value);
      $value = str_replace($oldLinkAbsolute, $newLinkAbsolute, $value);
      $value = str_replace($oldLink, $newLink, $value);
    }
    else {
      // Create an array of links with HTML decoded and encoded URLs.
      $oldLinks = [
        $oldHtmlLinkAbsolute,
        $oldHtmlLink,
        $oldLink,
      ];

      // Remove duplicate URLs from array if URLs do not have URL parameters.
      // If more than one URL parameter exists - one URL in the array will have
      // an unencoded ampersand "&" and a second URL will have an HTML encoded
      // ampersand "&amp;".
      $oldLinks = array_unique($oldLinks);

      // Load HTML code into DOM.
      $htmlDom = Html::load($value);

      // Finds all hyperlinks in the content.
      if ($this->linkcheckerSetting->get('extract.from_a') == TRUE) {
        $links = $htmlDom->getElementsByTagName('a');
        foreach ($links as $link) {
          if (in_array($link->getAttribute('href'), $oldLinks)) {
            $link->setAttribute('href', $newHtmlLink);
          }
          // Replace link text, if same like the URL. If a link text contains
          // other child tags like <img> it will be skipped.
          if (in_array($link->nodeValue, $oldLinks)) {
            $link->nodeValue = $newHtmlLink;
          }
        }

        $links = $htmlDom->getElementsByTagName('area');
        foreach ($links as $link) {
          if (in_array($link->getAttribute('href'), $oldLinks)) {
            $link->setAttribute('href', $newHtmlLink);
          }
        }
      }

      // Finds all audio links in the content.
      if ($this->linkcheckerSetting->get('extract.from_a') == TRUE) {
        $audios = $htmlDom->getElementsByTagName('audio');
        foreach ($audios as $audio) {
          if (in_array($audio->getAttribute('src'), $oldLinks)) {
            $audio->setAttribute('src', $newHtmlLink);
          }

          // Finds source tags with links in the audio tag.
          $sources = $audio->getElementsByTagName('source');
          foreach ($sources as $source) {
            if (in_array($source->getAttribute('src'), $oldLinks)) {
              $source->setAttribute('src', $newHtmlLink);
            }
          }
          // Finds track tags with links in the audio tag.
          $tracks = $audio->getElementsByTagName('track');
          foreach ($tracks as $track) {
            if (in_array($track->getAttribute('src'), $oldLinks)) {
              $track->setAttribute('src', $newHtmlLink);
            }
          }
        }
      }

      // Finds embed tags with links in the content.
      if ($this->linkcheckerSetting->get('extract.from_embed') == TRUE) {
        $embeds = $htmlDom->getElementsByTagName('embed');
        foreach ($embeds as $embed) {
          if (in_array($embed->getAttribute('src'), $oldLinks)) {
            $embed->setAttribute('src', $newHtmlLink);
          }
          if (in_array($embed->getAttribute('pluginurl'), $oldLinks)) {
            $embed->setAttribute('pluginurl', $newHtmlLink);
          }
          if (in_array($embed->getAttribute('pluginspage'), $oldLinks)) {
            $embed->setAttribute('pluginspage', $newHtmlLink);
          }
        }
      }

      // Finds iframe tags with links in the content.
      if ($this->linkcheckerSetting->get('extract.from_iframe') == TRUE) {
        $iframes = $htmlDom->getElementsByTagName('iframe');
        foreach ($iframes as $iframe) {
          if (in_array($iframe->getAttribute('src'), $oldLinks)) {
            $iframe->setAttribute('src', $newHtmlLink);
          }
        }
      }

      // Finds img tags with links in the content.
      if ($this->linkcheckerSetting->get('extract.from_img') == TRUE) {
        $imgs = $htmlDom->getElementsByTagName('img');
        foreach ($imgs as $img) {
          if (in_array($img->getAttribute('src'), $oldLinks)) {
            $img->setAttribute('src', $newHtmlLink);
          }
          if (in_array($img->getAttribute('longdesc'), $oldLinks)) {
            $img->setAttribute('longdesc', $newHtmlLink);
          }
        }
      }

      // Finds object/param tags with links in the content.
      if ($this->linkcheckerSetting->get('extract.from_object') == TRUE) {
        $objects = $htmlDom->getElementsByTagName('object');
        foreach ($objects as $object) {
          if (in_array($object->getAttribute('data'), $oldLinks)) {
            $object->setAttribute('data', $newHtmlLink);
          }
          if (in_array($object->getAttribute('codebase'), $oldLinks)) {
            $object->setAttribute('codebase', $newHtmlLink);
          }

          // Finds param tags with links in the object tag.
          $params = $object->getElementsByTagName('param');
          foreach ($params as $param) {
            // @todo
            // - Try to replace links in unknown "flashvars" values
            //   (e.g., file=http://, data=http://).
            $names = ['archive', 'filename', 'href', 'movie', 'src', 'url'];
            if ($param->hasAttribute('name') && in_array($param->getAttribute('name'), $names)) {
              if (in_array($param->getAttribute('value'), $oldLinks)) {
                $param->setAttribute('value', $newHtmlLink);
              }
            }

            $srcs = ['movie'];
            if ($param->hasAttribute('src') && in_array($param->getAttribute('src'), $srcs)) {
              if (in_array($param->getAttribute('value'), $oldLinks)) {
                $param->setAttribute('value', $newHtmlLink);
              }
            }
          }
        }
      }

      // Finds video tags with links in the content.
      if ($this->linkcheckerSetting->get('extract.from_video') == TRUE) {
        $videos = $htmlDom->getElementsByTagName('video');
        foreach ($videos as $video) {
          if (in_array($video->getAttribute('poster'), $oldLinks)) {
            $video->setAttribute('poster', $newHtmlLink);
          }
          if (in_array($video->getAttribute('src'), $oldLinks)) {
            $video->setAttribute('src', $newHtmlLink);
          }

          // Finds source tags with links in the video tag.
          $sources = $video->getElementsByTagName('source');
          foreach ($sources as $source) {
            if (in_array($source->getAttribute('src'), $oldLinks)) {
              $source->setAttribute('src', $newHtmlLink);
            }
          }
          // Finds track tags with links in the audio tag.
          $tracks = $video->getElementsByTagName('track');
          foreach ($tracks as $track) {
            if (in_array($track->getAttribute('src'), $oldLinks)) {
              $track->setAttribute('src', $newHtmlLink);
            }
          }
        }
      }

      $value = Html::serialize($htmlDom);
    }

    return $value;
  }

}
