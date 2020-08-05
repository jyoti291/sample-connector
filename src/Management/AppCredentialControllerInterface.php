<?php

namespace sample_connector\Management\Controller;

/**
 * Describes common operations for company- and developer app credentials.
 */
interface AppCredentialControllerInterface extends
    AttributesAwareEntityControllerInterface,
    EntityControllerInterface,
    StatusAwareEntityControllerInterface
{
    /**
     * String that should be sent to the API to change the status of a credential to approved.
     */
    public const STATUS_APPROVE = 'approve';

    /**
     * String that should be sent to the API to change the status of a credential to revoked.
     */
    public const STATUS_REVOKE = 'revoke';

    /**
     * Creates a new consumer key and secret for an app.
     *
     * @param string $consumerKey
     * @param string $consumerSecret
     *
     */
    public function create(string $consumerKey, string $consumerSecret): AppCredentialInterface;

    /**
     * Generates a new key pair for an app.
     *
     *
     * @param string[] $apiProducts
     *   API Product names.
     * @param string $callbackUrl
     *   Current callback url of the app. (If you don't include it then the existing callback url gets deleted.)
     * @param string[] $scopes
     *   List of OAuth scopes (from API products).
     * @param string $keyExpiresIn
     *   In milliseconds. A value of -1 means the key/secret pair never expire.
     *
     */
    public function generate(
        array $apiProducts,
        AttributesProperty $appAttributes,
        string $callbackUrl,
        array $scopes = [],
        string $keyExpiresIn = '-1'
    ): AppCredentialInterface;

    /**
     * Adds API products to a consumer key.
     *
     *
     * Modifying attributes of a consumer key is intentionally separated because attributes can not just be added but
     * existing ones can be removed if they are missing from the payload.
     *
     * @param string $consumerKey
     *   The consumer key to modify.
     * @param string[] $apiProducts
     *   API Product names.
     *
     */
    public function addProducts(string $consumerKey, array $apiProducts): AppCredentialInterface;

    /**
     * Approve or revoke specific key of a developer app.
     *
     * @param string $consumerKey
     * @param string $status
     */
    public function setStatus(string $consumerKey, string $status): void;

    /**
     * Approve or revoke API product for an API key.
     *
     * @param string $consumerKey
     * @param string $apiProduct
     * @param string $status
     */
    public function setApiProductStatus(string $consumerKey, string $apiProduct, string $status): void;

    /**
     * Delete key for an developer app.
     *
     * @param string $consumerKey
     *
     */
    public function delete(string $consumerKey): AppCredentialInterface;

    /**
     * Remove API product for a consumer key for an developer app.
     *
     * @param string $consumerKey
     * @param string $apiProduct
     *
     */
    public function deleteApiProduct(string $consumerKey, string $apiProduct): AppCredentialInterface;

    /**
     * Get key details for a developer app.
     *
     * @param string $consumerKey
     *
     */
    public function load(string $consumerKey): AppCredentialInterface;

    /**
     * Modify (override) scopes of a customer key.
     *
     * It is called override, because previous scopes can be removed if those are not included in the
     * passed $scopes variable.
     *
     *
     * @param string $consumerKey
     *   The consumer key to modify.
     * @param string[] $scopes
     *
     */
    public function overrideScopes(string $consumerKey, array $scopes): AppCredentialInterface;
}
