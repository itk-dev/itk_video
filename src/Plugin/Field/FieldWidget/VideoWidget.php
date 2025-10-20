<?php

namespace Drupal\itk_video\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\itk_video\SupportedVideoProvider;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin implementation of the 'itk_video_widget' widget.
 *
 * @FieldWidget(
 *   id = "itk_video_widget",
 *   label = @Translation("Video embed field"),
 *   module = "itk_video",
 *   field_types = {
 *     "itk_video_field",
 *   }
 * )
 */
final class VideoWidget extends LinkWidget {

  /**
   * Constructs a VideoWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    protected ConfigFactoryInterface $configFactory,
    protected AccountInterface $account,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create($container, array $configuration, $plugin_id, $plugin_definition): VideoWidget {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // The contents of this method are in large parts copied from the parent
    // class.
    $config = $this->configFactory->get('itk_video.settings')->get('providers_status');
    $enabledProviders = [];
    foreach ($config as $provider => $enabled) {
      if ($enabled) {
        $enabledProviders[$provider] = SupportedVideoProvider::getConfig()[$provider]['label'];
      }
    }

    /** @var \Drupal\link\LinkItemInterface $item */
    $item = $items[$delta];

    $display_uri = NULL;
    if (!$item->isEmpty()) {
      try {
        // The current field value could have been entered by a different user.
        // However, if it is inaccessible to the current user, do not display it
        // to them.
        if ($this->account->hasPermission('link to any page') || $item->getUrl()->access()) {
          $display_uri = static::getUriAsDisplayableString($item->getUrl()->getUri());
        }
      }
      catch (\InvalidArgumentException $e) {
        // If $item->uri is invalid, show value as is, so the user can see what
        // to edit.
        // @todo Add logging here in https://www.drupal.org/project/drupal/issues/3348020
        $display_uri = $item->getUrl()->getUri();
      }
    }
    $element['uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Video URL'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      '#default_value' => $display_uri,
      '#element_validate' => [[static::class, 'validateUriElement']],
      '#maxlength' => 2048,
      '#required' => $element['#required'],
      '#link_type' => $this->getFieldSetting('link_type'),
    ];

    $providersText = $enabledProviders ? '<div>' . $this->t('Supported providers: @providers', ['@providers' => implode(', ', $enabledProviders)]) . '</div>' : '';
    $element['uri']['#description'] = $element['#description'] . $providersText;

    // Make uri required on the front-end when title filled-in.
    if (!$this->isDefaultValueWidget($form_state) && $this->getFieldSetting('title') !== DRUPAL_DISABLED && !$element['uri']['#required']) {
      $parents = $element['#field_parents'];
      $parents[] = $this->fieldDefinition->getName();
      $selector = $root = array_shift($parents);
      if ($parents) {
        $selector = $root . '[' . implode('][', $parents) . ']';
      }

      $element['uri']['#states']['required'] = [
        ':input[name="' . $selector . '[' . $delta . '][title]"]' => ['filled' => TRUE],
      ];
    }

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video title'),
      '#placeholder' => $this->getSetting('placeholder_title'),
      '#default_value' => $items[$delta]->title ?? NULL,
      '#maxlength' => 255,
      '#required' => !$this->isDefaultValueWidget($form_state) && $element['#required'],
    ];
    // Post-process the title field to make it conditionally required if URL is
    // non-empty. Omit the validation on the field edit form, since the field
    // settings cannot be saved otherwise.
    //
    // Validate that title field is filled out (regardless of uri) when it is a
    // required field.
    if (!$this->isDefaultValueWidget($form_state)) {
      $element['#element_validate'][] = [static::class, 'validateTitleElement'];
      $element['#element_validate'][] = [static::class, 'validateTitleNoLink'];

      if (!$element['title']['#required']) {
        // Make title required on the front-end when URI filled-in.
        $parents = $element['#field_parents'];
        $parents[] = $this->fieldDefinition->getName();
        $selector = $root = array_shift($parents);
        if ($parents) {
          $selector = $root . '[' . implode('][', $parents) . ']';
        }

        $element['title']['#states']['required'] = [
          ':input[name="' . $selector . '[' . $delta . '][uri]"]' => ['filled' => TRUE],
        ];
      }
    }

    // Exposing the attributes array in the widget is left for alternate and
    // more advanced field widgets.
    $element['attributes'] = [
      '#type' => 'value',
      '#tree' => TRUE,
      '#value' => !empty($items[$delta]->options['attributes']) ? $items[$delta]->options['attributes'] : [],
      '#attributes' => ['class' => ['link-field-widget-attributes']],
    ];

    // If cardinality is 1, ensure a proper label is output for the field.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += [
        '#type' => 'fieldset',
      ];
    }

    return $element;
  }

}
