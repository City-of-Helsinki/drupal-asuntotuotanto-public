<?php

namespace Drupal\asu_rest\Plugin\rest\resource;

use Drupal\asu_rest\Applications;
use Drupal\asu_rest\UserDto;
use Drupal\asu_api\Api\DrupalApi\Request\FilterRequest;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a resource to get user applications.
 *
 * @RestResource(
 *   id = "asu_initialize",
 *   label = @Translation("Initialize"),
 *   uri_paths = {
 *     "canonical" = "/initialize",
 *     "https://www.drupal.org/link-relations/create" = "/initialize"
 *   }
 * )
 */
final class Initialize extends ResourceBase {

  use StringTranslationTrait;

  /**
   * Responds to GET requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The HTTP response object.
   */
  public function get(Request $request) {
    $response = [];

    $response['filters'] = $this->getFilters();
    $response['static_content'] = $this->getStaticContent();
    $response['apartment_application_status'] = $this->getApartmentApplicationStatus();

    /** @var \Drupal\user\Entity\User $user */
    if ($user = User::load(\Drupal::currentUser()->id())) {
      $response['user'] = $this->getUser($user);
      $response['user']['applications'] = $this->getUserApplications($user);
    }

    return new ModifiedResourceResponse($response, 200);
  }

  /**
   * Get user data for logged in user.
   *
   * @param \Drupal\user\Entity\User $user
   *   User object.
   *
   * @return array
   *   Array of user data.
   */
  private function getUser(User $user): array {
    $userData = UserDto::createFromUser($user);
    return $userData->toArray();
  }

  /**
   * Get application apartments sent by the user.
   *
   * @param \Drupal\user\Entity\User $user
   *   User object.
   *
   * @return array
   *   Array of applications by user.
   */
  private function getUserApplications(User $user) {
    return Applications::applicationsByUser($user->id())
      ->getApartmentApplicationsByProject();
  }

  /**
   * Get application count as enum for apartments.
   *
   * @return array
   *   Array of application statuses by apartment.
   */
  private function getApartmentApplicationStatus(): array {
    return Applications::create()
      ->getApartmentApplicationStatuses();
  }

  /**
   * Get the static content.
   */
  private function getStaticContent(): array {
    $config = \Drupal::config('asu_rest.static_content');
    return $config->get('static_content');
  }

  /**
   * Get the filters.
   */
  private function getFilters(): array {
    $languageCode = \Drupal::languageManager()
      ->getCurrentLanguage()
      ->getId();

    try {
      /** @var \Drupal\asu_api\Api\DrupalApi\DrupalApi $drupalApi */
      $drupalApi = \Drupal::service('asu_api.drupalapi');
      return $drupalApi
        ->getFiltersService()
        ->getFilters(FilterRequest::create($languageCode))
        ->getContent();
    }
    catch (\Exception $e) {
      // @todo error: connection failed, add logging.
      return [];
    }
  }

}