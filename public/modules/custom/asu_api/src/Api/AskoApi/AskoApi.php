<?php

namespace Drupal\asu_api\Api\AskoApi;

use Drupal\asu_api\Api\AskoApi\Request\AskoApplicationRequest;
use Drupal\asu_application\Entity\Application;
use Drupal\user\Entity\User;

/**
 * Integration to As-Ko.
 */
class AskoApi {

  /**
   * Asko hitas email address.
   *
   * @var string
   */
  private string $hitasEmailAddress;

  /**
   * Asko haso email address.
   *
   * @var string
   */
  private string $hasoEmailAddress;

  /**
   * Constructor.
   */
  public function __construct(string $hasoAdressVariable, string $hitasEmailAddress) {
    if ($hitas = getenv($hitasEmailAddress) && $haso = getenv($hasoAdressVariable)) {
      $this->hitasEmailAddress = $hitas;
      $this->hasoEmailAddress = $haso;
    }
    else {
      throw new \InvalidArgumentException('As-Ko address is not set');
    }
  }

  /**
   * Get asko email address.
   *
   * @param string $type
   *  Hitas or haso email address.
   * @return string
   */
  public function getEmailAddress(string $type): string {
    if (in_array($type, ['hitas', 'haso'])) {
      return $type == 'hitas' ? $this->hitasEmailAddress : $this->hasoEmailAddress;
    }
    throw new \Exception('Tried to fetch email for undefined application type.');
  }

  /**
   * Get email address.
   *
   * @return string
   */
  public function getHitasEmailAddress(): string {
    return $this->hasoEmailAddress;
  }

  /**
   * Get asko request.
   *
   * @param \Drupal\user\Entity\User $user
   *   User entity.
   * @param \Drupal\asu_application\Entity\Application $application
   *   Application entity.
   * @param string $projectName
   *   Name of the project.
   *
   * @return Drupal\asu_api\Api\AskoApi\Request\AskoApplicationRequest
   */
  public function getAskoApplicationRequest(User $user, Application $application, string $projectName): AskoApplicationRequest {
    return new AskoApplicationRequest($user, $application, $projectName);
  }

}
