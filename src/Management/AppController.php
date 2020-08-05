<?php

namespace sample_connector\Management\Controller;

use Psr\Http\Message\UriInterface;

/**
 * Class AppController.
 */
class AppController extends PaginatedEntityController implements AppControllerInterface
{
    use EntityListingControllerTrait;
    use PaginationHelperTrait {
        listEntities as private traitListEntities;
    }

    protected const ID_GETTER = 'getAppId';

    /**
     * AppController constructor.
     *
     */
    public function __construct(
        string $organization,
        ClientInterface $client,
        ?EntitySerializerInterface $entitySerializer = null,
        ?OrganizationControllerInterface $organizationController = null
    ) {
        $entitySerializer = $entitySerializer ?? new AppEntitySerializer();
        parent::__construct($organization, $client, $entitySerializer, $organizationController);
    }

    /**
     * @inheritdoc
     */
    public function loadApp(string $appId): AppInterface
    {
        $response = $this->client->get($this->getEntityEndpointUri($appId));

        return $this->entitySerializer->denormalize(
            // Pass it as an object, because if serializer would have been used here (just as other places) it would
            // pass an object to the denormalizer and not an array.
            (object) $this->responseToArray($response),
            $this->getEntityClass()
        );
    }

    /**
     * @inheritdoc
     */
    public function listAppIds(PagerInterface $pager = null): array
    {
        return $this->listEntityIds($pager, []);
    }

    /**
     * @inheritdoc
     */
    public function listApps(bool $includeCredentials = true, PagerInterface $pager = null): array
    {
        $queryParams = [
            'includeCred' => $includeCredentials ? 'true' : 'false',
        ];

        return $this->listEntities($pager, $queryParams);
    }

    /**
     * @inheritdoc
     */
    public function listAppIdsByStatus(string $status, PagerInterface $pager = null): array
    {
        $queryParams = [
            'status' => $status,
        ];

        return $this->listEntityIds($pager, $queryParams);
    }

    /**
     * @inheritdoc
     */
    public function listAppsByStatus(
        string $status,
        bool $includeCredentials = true,
        PagerInterface $pager = null
    ): array {
        $queryParams = [
            'status' => $status,
            'includeCred' => $includeCredentials ? 'true' : 'false',
        ];

        return $this->listEntities($pager, $queryParams);
    }

    /**
     * @inheritdoc
     */
    public function listAppIdsByType(string $appType, PagerInterface $pager = null): array
    {
        $queryParams = [
            'apptype' => $appType,
        ];

        return $this->listEntityIds($pager, $queryParams);
    }

    /**
     * @inheritdoc
     */
    public function listAppIdsByFamily(string $appFamily, PagerInterface $pager = null): array
    {
        $queryParams = [
            'appfamily' => $appFamily,
        ];

        return $this->listEntityIds($pager, $queryParams);
    }

    /**
     * @inheritdoc
     */
    protected function getBaseEndpointUri(): UriInterface
    {
        return $this->client->getUriFactory()->createUri("/organizations/{$this->organization}/apps");
    }

    /**
     * @inheritdoc
     */
    protected function getEntityClass(): string
    {
        // It could be either a developer- or a company app. The AppDenormalizer can automatically decide which one
        // should be used.
        return AppInterface::class;
    }

    /**
     * @inheritdoc
     */
    protected function listEntities(
        PagerInterface $pager = null,
        array $query_params = [],
        string $idGetter = null
    ): array {
        $idGetter = $idGetter ?? static::ID_GETTER;

        return $this->traitListEntities($pager, $query_params, $idGetter);
    }
}
