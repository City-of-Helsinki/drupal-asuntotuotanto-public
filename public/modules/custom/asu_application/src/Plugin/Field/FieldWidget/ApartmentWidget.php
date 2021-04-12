<?php

namespace Drupal\asu_application\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the apartment field widget.
 *
 * @FieldWidget(
 *   id = "asu_apartment_widget",
 *   label = @Translation("Asu apartment - Widget"),
 *   description = @Translation("Asu apartment - Widget"),
 *   field_types = {
 *     "asu_apartment"
 *   },
 * )
 */
class ApartmentWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['id'] = [
      '#type' => 'select',
      '#cardinality' => -1,
      '#title' => t('Apartment'),
      '#required' => FALSE,
      '#options' => $this->getApartments(),
      '#default_value' => isset($items->getValue()[$delta]['id']) ? $items->getValue()[$delta]['id'] : 0,
    ];

    return $element;
  }

  /**
   * @inheritDoc
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    $values = parent::massageFormValues($values, $form, $form_state);
    foreach($values as $key => $value){
      $values[$key]['information'] = $form['apartment']['widget'][$key]['id']['#options']['apartments'][$value['id']];
    }
    return $values;
  }

  /**
   *
   */
  private function getApartments() {
    // Get apartments.
    /** @var \GuzzleHttp\Client $client */
    // $client = Drupal::httpClient();
    // $client->post();
    $apartments = [
      '0' => $this->t('Select'),
      '2' => 'Kaakelikuja 22 A1',
      '3' => 'Kaakelikuja 22 A2',
      '4' => 'Kaakelikuja 22 A3',
      '5' => 'Kaakelikuja 22 A4',
      '6' => 'Kaakelikuja 22 A5',
      '7' => 'Kaakelikuja 22 A6',
      '8' => 'Kaakelikuja 22 B1',
      '9' => 'Kaakelikuja 22 B2',
      '10' => 'Kaakelikuja 22 B3',
      '11' => 'Kaakelikuja 22 B4',
      '12' => 'Kaakelikuja 22 B5',
      '13' => 'Kaakelikuja 22 B6',
    ];

    $data = [
      'project' => 'Kaakelikuja 22',
      'apartments' => $apartments,
    ];

    return $data;
  }

}
