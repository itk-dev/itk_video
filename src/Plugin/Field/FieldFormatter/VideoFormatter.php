<?php

namespace Drupal\itk_video\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\itk_video\Plugin\Field\FieldType\Video;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\itk_video\SupportedVideoProviders;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'itk_video_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "itk_video_formatter",
 *   module = "itk_video",
 *   label = @Translation("Video formatter"),
 *   field_types = {
 *     "itk_video_field"
 *   }
 * )
 */
class VideoFormatter extends LinkFormatter {

  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected $urlResolver,
    protected $httpClient,
    protected $pathValidator,
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $pathValidator);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): VideoFormatter {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('media.oembed.url_resolver'),
      $container->get('http_client'),
      $container->get('path.validator'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements['#attached']['library'][] = 'itk_video/video';

    foreach ($items as $delta => $item) {

      $this->createVideo($item);

      if (!empty($item->getUrl()->toString())) {
        $markup =
          '<div class="itk-video itk-video-responsive">' . $this->createVideo($item) . '</div>';

        $elements[$delta] = [
          '#type' => 'inline_template',
          '#template' => $markup,
        ];
      }
    }

    return $elements;
  }

  /**
   * Render a video from an embed url or iframe.
   *
   * @param \Drupal\itk_video\Plugin\Field\FieldType\Video $value
   *   The video field type.
   *
   * @return string|null
   *   The rendered html.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   An exception if guzzle fails.
   */
  private function createVideo(Video $value): ?string {
    $settings = $this->configFactory->get('itk_video.settings');

    // Set string.
    $url = $value->getUrl()->toString();

    $videoArray = $this->createVideoFromUrl($url, $settings);

    if ($settings->get('respect_cookie_information')) {
      $videoArray = $this->applyCookieConsent($videoArray, $value);
    }

    return $videoArray['iframe'] ?? NULL;
  }

  /**
   * Create an array containing video information.
   *
   * @param string $text
   *   The text input to create video from.
   * @param array $settings
   *   The settings for the field.
   *
   * @return array
   *   The resulting video array.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   Exception if oembed fails.
   */
  private function createVideoFromUrl(string $text, array $settings): array {
    $video = [];
    if (filter_var($text, FILTER_VALIDATE_URL)) {
      $supportedProviders = SupportedVideoProviders::getConfig();
      $providersStatus = $settings->get('providers_status');

      $url = parse_url($text);
      if (in_array($url['host'], SupportedVideoProviders::getProviderUrls())) {
        $video['host'] = $url['host'];

        $providerKey = $this->getProviderIdFromHost($supportedProviders, $video['host']);

        if (!empty($providerKey && $providersStatus[$providerKey])) {
          // Use oembed to create iframe if possible.
          if ('Oembed' === $supportedProviders[$providerKey]['type']) {
            try {
              $url = $this->urlResolver->getResourceUrl($text);
              $request = $this->httpClient->request('GET', $url);
              $status = $request->getStatusCode();
              if (200 == $status) {
                $video['oembed'] = Json::decode($request->getBody()->getContents());
                $video['iframe'] = $video['oembed']['html'];
              }
            }
            catch (\Exception $e) {
              if ('No matching provider found.' === $e->getMessage()) {
                $this->messenger->addWarning($this->t('Could not build video.'));
                $video = [];
              }
            }
          }
          // If oembed is not an option create iframe from a url.
          elseif ('custom' === $supportedProviders[$providerKey]['type']) {
            $video['custom']['src'] = $text;
            $video['iframe'] = '<iframe allow="fullscreen" src="' . $text . '"></iframe>';
          }
        }
      }
    }

    return $video;
  }

  /**
   * Change iframe to support cookie consent.
   *
   * @param array $videoArray
   *   The video array.
   * @param \Drupal\itk_video\Plugin\Field\FieldType\Video $fieldValue
   *   The video array.
   *
   * @return array
   *   The modified video array.
   */
  private function applyCookieConsent(array $videoArray, Video $fieldValue): array {
    $supportedProviders = SupportedVideoProviders::getConfig();
    if (in_array($videoArray['host'], SupportedVideoProviders::getProviderUrls())) {
      $providerKey = $this->getProviderIdFromHost($supportedProviders, $videoArray['host']);
      $requiredCookies = $supportedProviders[$providerKey]['requiredCookies'];
      if (!empty($requiredCookies) && isset($videoArray['iframe'])) {
        $videoArray['iframe'] = str_replace(' src="', ' src="" data-category-consent="' . $requiredCookies . '" data-consent-src="', $videoArray['iframe']);
        $blockedText = $this->t('<strong>Accept cookies</strong> to view this video:');
        $blockedText .= '<br>';
        $blockedText .= '"' . $fieldValue->title . '"' ?? '';
        $videoArray['iframe'] = $videoArray['iframe'] . '<div class="itk-blocked-text"> ' . $blockedText . '</div>';
      }
    }

    return $videoArray;
  }

  /**
   * Get provider id from supplied host.
   *
   * @param array $supportedProviders
   *   A list of all supported providers.
   * @param string $host
   *   The host to get the provider id from.
   *
   * @return string|null
   *   A provider id or null if not found.
   */
  private function getProviderIdFromHost(array $supportedProviders, string $host): ?string {
    foreach ($supportedProviders as $key => $value) {
      if ($value['url'] === $host) {
        return $key;
      }
    }

    return NULL;
  }

}
