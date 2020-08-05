<?php

namespace sample_connector\Management\Controller;

/**
 * Interface AppControllerInterface.
 */
interface AppControllerInterface extends PaginatedEntityControllerInterface, EntityControllerInterface
{
    /**
     * Type of a developer app.
     */
    public const APP_TYPE_DEVELOPER = 'developer';

    /**
     * Type of a company app.
     */
    public const APP_TYPE_COMPANY = 'company';

    /**
     * String that should be sent to the API to change the status of a
     * credential to approved.
     */
    public const STATUS_APPROVE = 'approve';

    /**
     * String that should be sent to the API to change the status of a
     * credential to revoked.
     */
    public const STATUS_REVOKE = 'revoke';

    /**
     * Loads a developer or a company app from Edge based on its UUID.
     *
     * @param string $appId
     *   UUID of an app (appId).
     */
    public function loadApp(string $appId): AppInterface;

    /**
     * Returns list of app ids from Edge.
     *
     * @return string[]
     *   An array of developer- and company app ids.
     */
    public function listAppIds(PagerInterface $pager = null): array;

    /**
     * Returns list of app entities from Edge. The returned number of entities can be limited.
     *
     * @param bool $includeCredentials
     *   Whether to include consumer key and secret for each app in the response or not.
     */
    public function listApps(bool $includeCredentials = false, PagerInterface $pager = null): array;

    /**
     * Returns a list of app ids filtered by status from Edge.
     *
     * @param string $status
     *   App status. (Recommended way is to use App entity constants.)
     *
     * @return string[]
     *   An array of developer- and company app ids.
     */
    public function listAppIdsByStatus(string $status, PagerInterface $pager = null): array;

    /**
     * Returns a list of app entities filtered by status from Edge.
     *
     * @param string $status
     *   App status. (Recommended way is to use App entity constants.)
     * @param bool $includeCredentials
     *   Whether to include app credentials in the response or not.
     */
    public function listAppsByStatus(
        string $status,
        bool $includeCredentials = true,
        PagerInterface $pager = null
    ): array;

    /**
     * Returns a list of app ids filtered by app type from Edge.
     *
     * @param string $appType
     *   Either "developer" or "company".
     *
     * @return string[]
     *   An array of developer- and company app ids.
     */
    public function listAppIdsByType(string $appType, PagerInterface $pager = null): array;

    /**
     * Returns a list of app ids filtered by app family from Edge.
     *
     * @param string $appFamily
     *   App family, example: default.
     *
     * @return string[]
     *   An array of developer- and company app ids.
     */
    public function listAppIdsByFamily(string $appFamily, PagerInterface $pager = null): array;
}
