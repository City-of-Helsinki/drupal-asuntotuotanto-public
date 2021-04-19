<?php

namespace Drupal\asu_api\Api\ElasticSearchApi\Response;

use Psr\Http\Message\ResponseInterface;

/**
 * Response for application request.
 */
class ApartmentResponse {

  /**
   * Aparments array.
   *
   * @var array
   */
  private array $apartments;

  /**
   * Start time ISO string.
   *
   * @var string|mixed
   */
  private string $startTime;

  /**
   * End time ISO string.
   *
   * @var string|mixed
   */
  private string $endTime;

  /**
   * Project name (address).
   *
   * @var string|mixed
   */
  private string $projectName;

  /**
   * Apartment ownership type (HITAS/HASO).
   *
   * @var string|mixed
   */
  private string $ownershipType;

  /**
   * ApartmentResponse constructor.
   *
   * @param array $content
   *   Content from http request.
   *
   * @throws \Exception
   */
  public function __construct(array $content) {
    if (empty($content)) {
      throw new \Exception('No apartments found.');
    }
    $this->apartments = $content;
    $this->projectName = $content[0]['_source']['project_street_address'];
    $this->startTime = $content[0]['_source']['project_application_start_time'];
    $this->endTime = $content[0]['_source']['project_application_end_time'];
    $this->ownershipType = $content[0]['_source']['project_ownership_type'];
  }

  /**
   * Get array of apartments.
   *
   * @return array
   *   Apartments.
   */
  public function getApartments(): array {
    return $this->apartments;
  }

  /**
   * Get application start time in ISO format.
   *
   * @return string
   *   Start time.
   */
  public function getStartTime(): string {
    return $this->startTime;
  }

  /**
   * Get application end time in ISO format.
   *
   * @return string
   *   End time.
   */
  public function getEndTime(): string {
    return $this->endTime;
  }

  /**
   * Get ownership type. Haso or Hitas.
   *
   * @return string
   *   Ownership type.
   */
  public function getOwnershipType(): string {
    return $this->ownershipType;
  }

  /**
   * Get the address of the project.
   *
   * @return string
   *   Address.
   */
  public function getProjectName(): string {
    return $this->projectName;
  }

  /**
   * Create an ApplicationResponse from http response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   HttpResponse.
   *
   * @return ApartmentResponse
   *   Apartment response.
   *
   * @throws \Exception
   *    Apartments not found.
   */
  public static function createFromHttpResponse(ResponseInterface $response): ApartmentResponse {
    $responseContent = json_decode($response->getBody()->getContents(), TRUE);
    $content = $responseContent['hits']['hits'];
    if (empty($content)) {
      throw new \Exception('No apartments found.');
    }
    return new self($content);
  }

}
