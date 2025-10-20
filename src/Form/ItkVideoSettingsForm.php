<?php

namespace Drupal\itk_video\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\itk_video\SupportedVideoProvider;

/**
 * Configuration form for ITK Video settings.
 */
class ItkVideoSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['itk_video.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'itk_video_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('itk_video.settings');

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General Settings'),
      '#collapsible' => FALSE,
    ];

    $form['general']['respect_cookie_information'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Whether to respect cookie information'),
      '#description' => $this->t('If enabled the video will respect boundaries provided by cookie information https://cookieinformation.com/, And the users cookie consent.'),
      '#default_value' => $config->get('respect_cookie_information') ?? TRUE,
    ];

    $form['providers'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled Video Providers'),
      '#collapsible' => FALSE,
    ];

    // Get available providers from the service.
    $supportedProviders = SupportedVideoProvider::getConfig();
    $availableProviders = [];
    foreach ($supportedProviders as $provider => $providerConfig) {
      $availableProviders[$provider] = $providerConfig['label'];
    }
    $enabledProviders = $config->get('providers_status') ?? [];

    $form['providers']['providers_status'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable providers'),
      '#options' => $availableProviders,
      '#default_value' => array_keys(array_filter($enabledProviders)),
      '#description' => $this->t('Select the video providers that should be available for use.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('itk_video.settings');

    $config->set('respect_cookie_information', $form_state->getValue('respect_cookie_information'));

    $providers = $form_state->getValue('providers_status');
    $providersStatus = array_map(function ($enabled) {
      return (bool) $enabled;
    }, $providers);

    $config->set('providers_status', $providersStatus);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
