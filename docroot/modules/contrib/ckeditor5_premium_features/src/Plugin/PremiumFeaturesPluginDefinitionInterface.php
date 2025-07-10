<?php

/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

declare(strict_types=1);

namespace Drupal\ckeditor5_premium_features\Plugin;

/**
 * Interface describing Premium feature plugin definition.
 */
interface PremiumFeaturesPluginDefinitionInterface {

  /**
   * Gets the featured plugin id.
   *
   * @return string
   *   The CKEditor plugin name.
   */
  public function getFeaturedPluginId(): string;

}
