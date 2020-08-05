<?php

namespace Drupal\kong_connector\Entity;

use Apigee\Edge\Api\Management\Entity\DeveloperInterface as EdgeDeveloperInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines an interface for develper entity objects.
 */
interface DeveloperInterface extends EdgeDeveloperInterface, EdgeEntityInterface, EntityOwnerInterface {

  /**
   * Resets the original email.
   */
  public function resetOriginalEmail() : void;

  /**
   * Set status of the developer.
   *
   * @param string $status
   *   Status of the entity.
   */
  public function setStatus(string $status): void;

  /**
   * Returns the original (unmodified) email address of a developer.
   *
   * @return null|string
   *   The original (unmodified) email address of a developer.
   */
  public function getOriginalEmail(): ?string;

}
