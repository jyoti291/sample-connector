<?php

namespace sample_connector\Management\Controller;

/**
 * Common implementation for company- and developer app credential controllers.
 */
abstract class AppCredentialController extends EntityController
{
    use AttributesAwareEntityControllerTrait;
    use StatusAwareEntityControllerTrait;

    /** @var string App name. */
    protected $appName;

    /**
     * AppCredentialController constructor.
     *
     * @param string $organization
     * @param string $appName
     */
    public function __construct(string $organization, string $appName, ClientInterface $client, ?EntitySerializerInterface $entitySerializer = null)
    {
        $this->appName = $appName;
        $entitySerializer = $entitySerializer ?? new AppCredentialSerializer();
        parent::__construct($organization, $client, $entitySerializer);
    }

    /**
     * @inheritdoc
     */
    public function create(string $consumerKey, string $consumerSecret): AppCredentialInterface
    {
        $response = $this->client->post(
            // Just to spare some extra lines of code.
            $this->getEntityEndpointUri('create'),
            (string) json_encode((object) ['consumerKey' => $consumerKey, 'consumerSecret' => $consumerSecret])
        );

        return $this->entitySerializer->deserialize(
            (string) $response->getBody(),
            $this->getEntityClass(),
            'json'
        );
    }

    /**
     * @inheritdoc
     */
    public function generate(
        array $apiProducts,
        AttributesProperty $appAttributes,
        string $callbackUrl,
        array $scopes = [],
        string $keyExpiresIn = '-1'
    ): AppCredentialInterface {
        $response = $this->client->post(
            $this->getBaseEndpointUri(),
            (string) json_encode((object) [
                'apiProducts' => $apiProducts,
                'attributes' => $this->entitySerializer->normalize($appAttributes),
                'callbackUrl' => $callbackUrl,
                'keyExpiresIn' => $keyExpiresIn,
                'scopes' => $scopes,
            ])
        );
        // It returns a complete developer app entity, but we only returns the newly created credential for the
        // sake of consistency.
        $responseArray = $this->responseToArray($response);
        $credentialArray = reset($responseArray['credentials']);

        return $this->entitySerializer->denormalize(
            $credentialArray,
            $this->getEntityClass()
        );
    }

    /**
     * @inheritdoc
     */
    public function addProducts(string $consumerKey, array $apiProducts): AppCredentialInterface
    {
        $response = $this->client->post(
            $this->getEntityEndpointUri($consumerKey),
            (string) json_encode((object) ['apiProducts' => $apiProducts])
        );

        return $this->entitySerializer->deserialize(
            (string) $response->getBody(),
            $this->getEntityClass(),
            'json'
        );
    }

    /**
     * @inheritdoc
     */
    public function setApiProductStatus(string $consumerKey, string $apiProduct, string $status): void
    {
        $apiProduct = rawurlencode($apiProduct);
        $uri = $this->getBaseEndpointUri()
            ->withPath("{$this->getBaseEndpointUri()}/keys/{$consumerKey}/apiproducts/{$apiProduct}")
            ->withQuery(http_build_query(['action' => $status]));
        $this->client->post($uri, null, ['Content-Type' => 'application/octet-stream']);
    }

    /**
     * @inheritdoc
     */
    public function deleteApiProduct(string $consumerKey, string $apiProduct): AppCredentialInterface
    {
        $apiProduct = rawurlencode($apiProduct);
        $uri = $this->getBaseEndpointUri()->withPath("{$this->getBaseEndpointUri()}/keys/{$consumerKey}/apiproducts/{$apiProduct}");
        $response = $this->client->delete($uri);

        return $this->entitySerializer->deserialize(
            (string) $response->getBody(),
            $this->getEntityClass(),
            'json'
        );
    }

    /**
     * @inheritdoc
     */
    public function overrideScopes(string $consumerKey, array $scopes): AppCredentialInterface
    {
        $response = $this->client->put(
            $this->getEntityEndpointUri($consumerKey),
            (string) json_encode((object) ['scopes' => $scopes])
        );

        return $this->entitySerializer->deserialize(
            (string) $response->getBody(),
            $this->getEntityClass(),
            'json'
        );
    }

    /**
     * @inheritdoc
     */
    public function load(string $entityId): AppCredentialInterface
    {
        $response = $this->client->get($this->getEntityEndpointUri($entityId));

        return $this->entitySerializer->deserialize(
        (string) $response->getBody(),
        $this->getEntityClass(),
        'json'
      );
    }

    /**
     * @inheritdoc
     */
    public function delete(string $entityId): AppCredentialInterface
    {
        $response = $this->client->delete($this->getEntityEndpointUri($entityId));

        return $this->entitySerializer->deserialize(
        (string) $response->getBody(),
        $this->getEntityClass(),
        'json'
      );
    }

    /**
     * @inheritdoc
     */
    protected function getEntityClass(): string
    {
        return AppCredential::class;
    }
}
