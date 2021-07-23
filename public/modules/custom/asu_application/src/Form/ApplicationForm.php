<?php

namespace Drupal\asu_application\Form;

use Drupal\asu_api\Api\ElasticSearchApi\Request\ProjectApartmentsRequest;
use Drupal\asu_application\Entity\Application;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\asu_application\Event\ApplicationEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form for Application.
 */
class ApplicationForm extends ContentEntityForm {

  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $parameters = \Drupal::routeMatch()->getParameters();

    // If user already has an application for this project.
    if ($project_id = $parameters->get('project_id')) {
      $applications = \Drupal::entityTypeManager()
        ->getStorage('asu_application')
        ->loadByProperties([
          'uid' => \Drupal::currentUser()->id(),
          'project_id' => $project_id,
        ]);
      if (!empty($applications)) {
        $url = reset($applications)->toUrl()->toString();
        (new RedirectResponse($url.'/edit'))->send();
        return $form;
      }
    }

    // Pre-create the application if user comes to the form for the first time.
    if($this->entity->isNew()){
      $project_id = $parameters->get('project_id');
      $user = User::load(\Drupal::currentUser()->id());
      /** @var \Drupal\asu_application\Entity\ApplicationType $application */
      $application = $parameters->get('application_type');
      $this->entity->save();
      $url = $this->entity->toUrl()->toString();
      (new RedirectResponse($url.'/edit'))->send();
      return $form;
    }

    // Application is created.
    if ($this->isFormAccessable()) {
      // @todo Add message & redirect.
      // $this->messenger()->addMessage($this->t('You are trying to fill an application which is not active.'));
      die('you should not be here');
    }

    $project_id = $this->entity->get('project_id')->value;
    $user = $this->entity->getOwner();
    $application_type_id = $this->entity->bundle();
    $form['project_id'] = $project_id;

    try {
      $project_data = $this->getApartments($project_id);
    }
    catch (\Exception $e) {
      // @todo Message & redirect, cannot fetch apartments.
    }

    if (!$this->isCorrectApplicationFormForProject($application_type_id, $project_data['ownership_type'])) {
      // @todo Redirect to correct form.
    }

    $startDate = $project_data['application_start_date'];
    $endDate = $project_data['application_end_date'];

    if (!$this->isFormActive($startDate, $endDate)) {
      // @todo Add redirect to proper place, outside of application time.
      $this->messenger()->addMessage($this->t('You are trying to fill an application which is not active.'));
    }

    $projectName = $project_data['project_name'];
    $apartments = $project_data['apartments'];
    // Set the apartments as a value to the form array.
    $form['apartment_values'] = $apartments;
    $form['project_name'] = $projectName;

    $form = parent::buildForm($form, $form_state);

    $form['#title'] = $this->t('Application for') . ' ' . $projectName;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if($form_state->getTriggeringElement()['#type'] == 'select'){
      parent::save($form, $form_state);
      return $this->entity;
    }
    \Drupal::logger('asu_application')->notice('ajax saving');

    $entity = &$this->entity;
    $message_params = [
      '%entity_label' => $entity->id(),
      '%content_entity_label' => $entity->getEntityType()->getLabel()->render(),
      '%bundle_label' => $entity->bundle->entity->label(),
    ];

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $project_name = $form['project_name'];
        #$event = new ApplicationEvent($entity->id(), $project_name);
        /** @var \Symfony\Component\EventDispatcher\EventDispatcher $event_dispatcher */
        #$event_dispatcher = \Drupal::service('event_dispatcher');
        #$event_dispatcher->dispatch($event, ApplicationEvent::EVENT_NAME);
        $this->messenger()->addStatus($this->t('Created the %bundle_label - %content_entity_label entity:  %entity_label.', $message_params));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %bundle_label - %content_entity_label entity:  %entity_label.', $message_params));
    }

    $content_entity_id = $entity->getEntityType()->id();
    $form_state->setRedirect("entity.{$content_entity_id}.canonical", [$content_entity_id => $entity->id()]);

  }

  /**
   * Check if the form be edited or filled.
   *
   * @return bool
   *   Is the form accessible.
   */
  private function isFormAccessable(): bool {
    return !$this->entity->isNew() && !$this->entity->getProjectId();
  }

  /**
   * The form should only be active between designated application time.
   *
   * @param string $startTime
   *   Start time as ISO string.
   * @param string $endTime
   *   End time as ISO string.
   *
   * @return bool
   *   Is current moment between the project's application time.
   */
  private function isFormActive(string $startTime, string $endTime): bool {
    $start = strtotime($startTime);
    $end = strtotime($endTime);
    $date = new \DateTime();
    $now = $date->getTimestamp();
    return $now < $end && $now > $start;
  }

  /**
   * Check that user is filling the correct form.
   *
   * @param string $formType
   *   Form type.
   * @param string $ownershipType
   *   Ownership type.
   *
   * @return bool
   *   Does the apartment ownershiptype match the form's type.
   */
  private function isCorrectApplicationFormForProject($formType, $ownershipType) {
    return $formType == $ownershipType;
  }

  /**
   * Get project apartments.
   *
   * @return array
   *   Array of project information & apartments.
   */
  private function getApartments($projectId): ?array {
    /** @var \Drupal\asu_api\Api\ElasticSearchApi\ElasticSearchApi $elastic */
    $elastic = \Drupal::service('asu_api.elasticapi');
    $request = new ProjectApartmentsRequest($projectId);
    $apartmentResponse = $elastic->getApartmentService()
      ->getProjectApartments($request);

    $projectName = $apartmentResponse->getProjectName();

    $apartments = [];
    foreach ($apartmentResponse->getApartments() as $apartment) {
      $data = $apartment['_source'];

      $living_area_size_m2 = number_format($data['living_area'], 1, ',', '');
      $debt_free_sales_price = number_format($data['debt_free_sales_price'] / 100, 0, ',', ' ');
      $sales_price = number_format($data['sales_price'] / 100, 0, ',', ' ');

      $select_text = "{$data['apartment_number']} | {$data['apartment_structure']} | {$data['floor']} / {$data['floor_max']} | {$living_area_size_m2} m2 | {$sales_price} € | {$debt_free_sales_price} €";

      $apartments[$data['nid']] = $select_text;
    }
    ksort($apartments, SORT_NUMERIC);

    return [
      'project_name' => $projectName,
      'ownership_type' => $apartmentResponse->getOwnershipType(),
      'application_start_date' => $apartmentResponse->getStartTime(),
      'application_end_date' => $apartmentResponse->getEndTime(),
      'apartments' => $apartments,
    ];
  }

  /**
   * Ajax callback function to presave the form.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function saveApplicationCallback(array &$form, FormStateInterface $form_state) {
    /** @var Application $entity */
    $entity = $form_state->getFormObject()->entity;
    $values = $form_state->getUserInput();

    // Save apartment values to database.
    $this->updateApartments($form, $entity, $values['apartment']);

    // Update "has_children" value
    $entity->set('has_children', $values['has_children']['value'] ?? 0);

    $entity->save();
  }

  /**
   * Ajax callback function to remove apartment from list.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function removeApartmentCallback(array &$form, FormStateInterface $form_state) {

    // Remove unwanted element from the form.
    $newApartments = [];
    foreach ($form_state->getUserInput()['apartment'] as $key => $value) {
      if ($value['id'] != 0) {
        $newApartments[] = $value;
      } else {
        unset($form['apartment']['widget'][$key]);
      }
    }

    // Sort by weight.
    uasort($newApartments, function ($item, $compare)  {
      return $item['_weight'] >= $compare['_weight'];
    });

    // Save apartments to database.
    $entity = $form_state->getFormObject()->entity;
    $this->updateApartments($form, $entity, $newApartments);

    // Return updated form.
    $form_state->setRebuild(TRUE);
    return $form['apartment'];
  }

  /**
   * Update entity.
   *
   * @param $form
   * @param $entity
   * @param $apartmentValues
   */
  private function updateApartments(array $form, Application $entity, array $apartmentValues) {
    $apartments = [];
    foreach ($apartmentValues as $key => $value) {
      if($value['id'] == 0) {
        continue;
      }
      $apartments[] = [
        'id' => $value['id'],
        'information' => $form['apartment_values'][$value['id']]
      ];
    }
    $entity->apartment->setValue($apartments);
  }

}
