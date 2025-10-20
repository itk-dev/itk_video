<?php

namespace Drupal\itk_video\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Plugin implementation of the 'itk_video_field' field type.
 *
 * @FieldType(
 *   id = "itk_video_field",
 *   label = @Translation("ITK Video field"),
 *   module = "itk_video",
 *   description = @Translation("An entity field containing a video URI."),
 *   default_formatter = "itk_video_formatter",
 *   default_widget = "itk_video_widget",
 * )
 */
class Video extends LinkItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'title' => DRUPAL_REQUIRED,
      'link_type' => LinkItemInterface::LINK_EXTERNAL,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    return $element;
  }

}
