<?php

namespace Drupal\itk_video;

/**
 * Supported video providers.
 */
enum SupportedVideoProviders: string
{
  case VIDEO_TOOL = 'video_tool';
  case VIMEO = 'vimeo';

  public static function getConfig(): array
  {
    return [
      'video_tool' => [
        'label' => 'Video Tool',
        'url' => 'media.videotool.dk', // Name of provider used in preg_match when analyzing iframe src.
        'type' => 'custom', // Use custom code to create iframe.
        'requiredCookies' => 'cookie_cat_statistic', // Cookies that require acceptance from user. CookieInformation syntax.
      ],
      'vimeo' => [
        'label' => 'Vimeo',
        'url' => 'vimeo.com', // Name of provider used in preg_match when analyzing iframe src.
        'type' => 'Oembed', // Use oembed endpoint when defining iframe.
        'requiredCookies' => 'cookie_cat_statistic cookie_cat_marketing', // Cookies that require acceptance from user. CookieInformation syntax.
      ],
    ];
  }

  public static function getAllValues(): array
  {
    return array_column(SupportedVideoProviders::cases(), 'value');
  }

  public static function getProviderUrls(): array
  {
    $providerUrls = [];
    $providers = SupportedVideoProviders::getConfig();
    foreach ($providers as $config) {
      $providerUrls[] = $config['url'];
    }

    return $providerUrls;
  }

  public function getProviderLabel() : string
  {
    return SupportedVideoProviders::getConfig()[$this->value]['label'];
  }


}
