<?php

namespace kong_connector;

use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Http\Client\Common\Plugin\HistoryPlugin;
use Http\Client\Common\Plugin\RetryPlugin;
use Http\Client\Exception;
use Http\Client\HttpClient;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Discovery\UriFactoryDiscovery;
use Http\Message\Authentication;
use Http\Message\UriFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class Client.
 *
 * Default API client KONG Implemetation.
 */
class Client implements ClientInterface
{
    public const CONFIG_USER_AGENT_PREFIX = 'user_agent_prefix';

    public const CONFIG_URI_FACTORY = 'uri_factory';

    public const CONFIG_REQUEST_FACTORY = 'request_factory';

    public const CONFIG_ERROR_FORMATTER = 'error_formatter';

    public const CONFIG_RETRY_PLUGIN_CONFIG = 'retry_plugin_config';

    /** @var \Http\Message\UriFactory */
    private $uriFactory;

    /** @var string|null */
    private $userAgentPrefix;

    /**
     * endpoint.
     *
     * @var string
     */
    private $endpoint;

    /** @var \Http\Message\Authentication */
    private $authentication;

    /**
     * Http client builder.
     */
    private $httpClientBuilder;

    /** @var bool */
    private $httpClientNeedsBuild = true;

    /**
     * @var \Http\Message\RequestFactory
     */
    private $requestFactory;

    /**
     * @var \Http\Message\Formatter|null
     */
    private $errorFormatter;

    /** @var array|null */
    private $retryPluginConfig;

    /**
     * Client constructor.
     *
     * @param \Http\Message\Authentication $authentication
     *   Authentication plugin.
     * @param string|null $endpoint
     *   The  API endpoint, including API version.
     */
    public function __construct(
        Authentication $authentication,
        string $endpoint = null,
        array $options = []
    ) {
        $this->authentication = $authentication;
        $this->endpoint = $endpoint ?: self::DEFAULT_ENDPOINT;
        $this->resolveConfiguration($options);
    }

    /**
     * @inheritdoc
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @inheritdoc
     */
    public function getUserAgent(): string
    {
        if (null !== $this->userAgentPrefix) {
            return sprintf("{$this->userAgentPrefix} ({$this->getClientVersion()})");
        }

        return $this->getClientVersion();
    }

    /**
     * @inheritdoc
     */
    public function getClientVersion(): string
    {
        return sprintf('Client %s', self::VERSION);
    }

    /**
     * @inheritdoc
     */
    public function get($uri, array $headers = []): ResponseInterface
    {
        return $this->send('GET', $uri, $headers, null);
    }

    /**
     * @inheritdoc
     */
    public function head($uri, array $headers = []): ResponseInterface
    {
        return $this->send('HEAD', $uri, $headers, null);
    }

    /**
     * @inheritdoc
     */
    public function post($uri, $body = null, array $headers = []): ResponseInterface
    {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        return $this->send('POST', $uri, $headers, $body);
    }

    /**
     * @inheritdoc
     */
    public function put($uri, $body = null, array $headers = []): ResponseInterface
    {
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        return $this->send('PUT', $uri, $headers, $body);
    }

    /**
     * @inheritdoc
     */
    public function delete($uri, $body = null, array $headers = []): ResponseInterface
    {
        return $this->send('DELETE', $uri, $headers, $body);
    }

    /**
     * @inheritdoc
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->getHttpClient()->sendRequest($request);
    }

    /**
     * Sets default for supported configuration options.
     *
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *   Option resolver.
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        // We set object properties' _default_ values to null to ensure we do not create unnecessary objects.
        $resolver->setDefaults([
            static::CONFIG_USER_AGENT_PREFIX => null,
            static::CONFIG_URI_FACTORY => null,
            static::CONFIG_REQUEST_FACTORY => null,
            static::CONFIG_ERROR_FORMATTER => null,
            static::CONFIG_RETRY_PLUGIN_CONFIG => null,
        ]);
        $resolver->setAllowedTypes(static::CONFIG_USER_AGENT_PREFIX, ['null', 'string']);
        $resolver->setAllowedTypes(static::CONFIG_URI_FACTORY, ['null', '\Http\Message\UriFactory']);
        $resolver->setAllowedTypes(static::CONFIG_REQUEST_FACTORY, ['null', '\Http\Message\RequestFactory']);
        $resolver->setAllowedTypes(static::CONFIG_ERROR_FORMATTER, ['null', '\Http\Message\Formatter']);
        $resolver->setAllowedTypes(static::CONFIG_RETRY_PLUGIN_CONFIG, ['null', 'array']);
    }

    /**
     * Returns default HTTP headers sent by the underlying HTTP client.
     *
     * @return array
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'User-Agent' => $this->getUserAgent(),
            'Accept' => 'application/json; charset=utf-8',
        ];
    }

    /**
     * Returns default plugins used by the underlying HTTP client.
     *
     * Call order of default plugins for sending a request (only those plugins listed that actually does something):
     * Request -> PluginClient -> BaseUriPlugin -> HeaderDefaultsPlugin -> HttpClient
     *
     * Call order of default plugins for processing a response (only those plugins listed that actually does something):
     * HttpClient -> ResponseHandlerPlugin -> RetryOauthAuthenticationPlugin -> HistoryPlugin -> Response
     *
     * @return \Http\Client\Common\Plugin[]
     */
    protected function getDefaultPlugins(): array
    {
        // Alters requests, adds base path and authentication.
        $firstPlugins = [
            new AddHostPlugin($this->getBaseUri(), ['replace' => true]),
            new AddPathPlugin($this->getBaseUri()),
            new HeaderDefaultsPlugin($this->getDefaultHeaders()),
        ];

        if ($this->authentication) {
            $firstPlugins[] = new AuthenticationPlugin($this->authentication);
        }

        // Acts based on response data.
        // (Retry plugin should be added here if it will be used.)
        $middlePlugins = [
            new HistoryPlugin(),
        ];

        if (null !== $this->retryPluginConfig) {
            if (!isset($this->retryPluginConfig['exception_decider'])) {
                $this->retryPluginConfig['exception_decider'] = function (RequestInterface $request, Exception $e) {
                    // When Oauth authentication is in use retry decider should ignore
                    // OauthAuthenticationException.
                    if (!$e instanceof OauthAuthenticationException) {
                        // Do not retry API calls that failed with
                        // client error.
                        if ($e instanceof ApiResponseException && $e->getResponse()->getStatusCode() >= 400 && $e->getResponse()->getStatusCode() < 500) {
                            return false;
                        }

                        return true;
                    }

                    return false;
                };
            }
            $middlePlugins[] = new RetryPlugin($this->retryPluginConfig);
        }

        if ($this->authentication instanceof AbstractOauth) {
            $middlePlugins[] = new RetryOauthAuthenticationPlugin($this->authentication);
        }

        // Alters, analyzes responses.
        $finalPlugins = [
            new ResponseHandlerPlugin($this->errorFormatter),
        ];

        return array_merge($firstPlugins, $middlePlugins, $finalPlugins);
    }

    /**
     * @inheritdoc
     */
    protected function getHttpClient(): HttpClient
    {
        if ($this->httpClientNeedsBuild) {
            foreach ($this->getDefaultPlugins() as $plugin) {
                $this->httpClientBuilder->addPlugin($plugin);
            }
            $this->httpClientNeedsBuild = false;
        }

        return $this->httpClientBuilder->getHttpClient();
    }

    /**
     * Resolve configuration options.
     *
     * @param array $options
     *   Array of configuration options.
     */
    private function resolveConfiguration(array $options = []): void
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);
        $this->userAgentPrefix = $options[static::CONFIG_USER_AGENT_PREFIX];
        $this->uriFactory = $options[static::CONFIG_URI_FACTORY] ?: UriFactoryDiscovery::find();
        $this->requestFactory = $options[static::CONFIG_REQUEST_FACTORY] ?: MessageFactoryDiscovery::find();
        $this->errorFormatter = $options[static::CONFIG_ERROR_FORMATTER];
        $this->retryPluginConfig = $options[static::CONFIG_RETRY_PLUGIN_CONFIG];
    }

    /**
     * @inheritdoc
     *
     * @throws \Http\Client\Exception
     */
    private function send($method, $uri, array $headers = [], $body = null): ResponseInterface
    {
        return $this->sendRequest($this->requestFactory->createRequest($method, $uri, $headers, $body));
    }

    /**
     * Returns endpoint as an URI.
     *
     * @return \Psr\Http\Message\UriInterface
     */
    private function getBaseUri(): UriInterface
    {
        return $this->uriFactory->createUri($this->getEndpoint());
    }
}
