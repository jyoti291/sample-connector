<?php

namespace Drupal\kong_connector\Entity;

use Apigee\Edge\Api\Management\Entity\AppInterface as EdgeAppInterface;

/**
 * Defines an interface for App entity objects.
 */
interface AppInterface extends EdgeAppInterface {

  /**
   * Returns the id of the app owner from the app entity.
   *
   * Return value could be either the developer id or the company name.
   *
   * @return string
   *   Id of the app owner, or null if the app is new.
   */
  public function getAppOwner(): ?string;

  /**
   * Sets the app owner's property value on an app.
   *
   * @param string $owner
   *   The owner of the app. Developer id (uuid) or team (company) name.
   */
  public function setAppOwner(string $owner): void;

}
