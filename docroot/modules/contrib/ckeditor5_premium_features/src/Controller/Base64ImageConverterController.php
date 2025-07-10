<?php

/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

declare(strict_types=1);

namespace Drupal\ckeditor5_premium_features\Controller;

use Drupal\ckeditor5_premium_features\CKeditorPremiumLoggerChannelTrait;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller exposing an endpoint for changing images urls into base64.
 */
class Base64ImageConverterController extends ControllerBase {

  use CKeditorPremiumLoggerChannelTrait;

  const CONVERTER_TYPE_PRIVATE = 'private';
  const CONVERTER_TYPE_ALL = 'all';

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   File system.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   Stream wrapper.
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   Image factory.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected FileSystemInterface $fileSystem,
    protected StreamWrapperManagerInterface $streamWrapperManager,
    protected ImageFactory $imageFactory,
  ) {
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('file_system'),
      $container->get('stream_wrapper_manager'),
      $container->get('image.factory'),
    );
  }

  /**
   * API endpoint for converting images into base64 in HTML document.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response.
   */
  public function convertImages(Request $request) {
    $args = $request->request;
    $content = $args->get('document');
    $type = $args->get('filesType');

    if (!$content || !$type) {
      return new AjaxResponse(NULL, 400);
    }
    $document = Json::decode($content);
    if (!$document) {
      return new AjaxResponse(NULL, 400);
    }

    $dom = new \DOMDocument();
    // Ignore warnings about wrong html tags.
    @$dom->loadHTML($document);

    $images = $dom->getElementsByTagName('img');
    foreach ($images as $img) {
      try {
        $imageSrc = $img->getAttribute('src');

        $urlArr = parse_url($imageSrc);
        if (empty($urlArr['path'])) {
          continue;
        }
        if (!empty($urlArr['host']) && $urlArr['host'] !== $request->getHost()) {
          continue;
        }
        $imageSrc = $urlArr['path'];

        if (str_starts_with($imageSrc, '/system/files/')) {
          $scheme = 'private';
          $imageSrc = preg_replace('|^\/system\/files\/|', '', $imageSrc);
          $uri = $this->streamWrapperManager->normalizeUri($scheme . '://' . $imageSrc);
        }
        elseif ($type === self::CONVERTER_TYPE_ALL) {
          $uri = \Drupal::root() . $imageSrc;
        }
        else {
          continue;
        }

        $image = $this->imageFactory->get($uri);
        if (!$image->isValid()) {
          continue;
        }

        $mimeType = $image->getMimeType();
        $path = $this->fileSystem->realpath($uri);

        if (!$path) {
          continue;
        }
        $imageData = file_get_contents($path);
        if ($imageData) {
          $base64Image = base64_encode($imageData);
          $img->setAttribute('src', "data:$mimeType;base64,$base64Image");
        }
      }
      catch (\Exception $e) {
        $this->logException('Exception occurred during convert image to base64.', $e);
        continue;
      }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    $responseDocument = new \DOMDocument();
    foreach ($body->childNodes as $child) {
      $responseDocument->appendChild($responseDocument->importNode($child, TRUE));
    }
    $processedHtml = $responseDocument->saveHTML();
    return new AjaxResponse(['document' => $processedHtml]);
  }

}
