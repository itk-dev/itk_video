<?php

namespace Drupal\itk_video;

/**
 * Supported video providers.
 */
enum SupportedVideoProvider: string {
  case VIDEO_TOOL = 'video_tool';
  case VIMEO = 'vimeo';

  /**
   * Get config for supported video providers.
   *
   * @return array[]
   *   A list of configurations for supported video providers.
   */
  public static function getConfig(): array {
    return [
      self::VIDEO_TOOL->value => [
        'label' => 'Video Tool',
        'host' => 'media.videotool.dk',
    // Use custom code to create iframe.
        'type' => 'custom',
    // Cookies that require acceptance from user. CookieInformation syntax.
        'requiredCookies' => 'cookie_cat_statistic',
      ],
      self::VIMEO->value => [
        'label' => 'Vimeo',
        'host' => 'vimeo.com',
      // Use oembed endpoint when defining iframe.
        'type' => 'oembed',
      // Cookies that require acceptance from user. CookieInformation syntax.
        'requiredCookies' => 'cookie_cat_statistic cookie_cat_marketing',
      ],
    ];
  }

  /**
   * Get provider Urls.
   *
   * @return array
   *   A list of provider urls.
   */
  public static function getProviderHosts(): array {
    $providerHosts = [];
    $providers = SupportedVideoProvider::getConfig();
    foreach ($providers as $config) {
      $providerHosts[] = $config['host'];
    }

    return $providerHosts;
  }

}
