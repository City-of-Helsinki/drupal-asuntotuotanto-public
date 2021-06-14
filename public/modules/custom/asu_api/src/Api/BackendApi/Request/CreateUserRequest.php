<?php

namespace Drupal\asu_api\Api\BackendApi\Request;

use Drupal\asu_api\Api\Request;
use Drupal\user\UserInterface;

/**
 * Create user request.
 */
class CreateUserRequest extends Request {

  protected const METHOD = 'POST';

  protected const PATH = 'api/v1/create_user';

  private UserInterface $user;

  /**
   * Construct.
   */
  public function __construct(UserInterface $user) {
    $this->user = $user;
  }

  /**
   * Data to array.
   */
  public function toArray(): array {
    return [
      'uuid' => $this->user->uuid(),
      'username' => $this->user->getEmail(),
      'first_name' => $this->user->field_first_name->value,
      'last_name' => $this->user->field_last_name->value,
      'date_of_birth' => $this->user->field_date_of_birth->value,
      'city' => $this->user->field_city->value,
      'postal_code' => $this->user->field_postal_code->value,
      'address' => $this->user->field_address->value,
    ];
  }

}