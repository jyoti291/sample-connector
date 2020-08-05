<?php

namespace sample_connector;

use Http\Client\HttpClient;
use Http\Message\UriFactory;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface ClientInterface.
 *
 * Describes the public methods of  API client.
 */
interface ClientInterface extends HttpClient
{
    /**
     * Returns the URI factory used by the Client.
     */
    public function getUriFactory(): UriFactory;

    /**
     * Returns the version of the API client.
     *
     * @return string
     */
    public function getClientVersion(): string;

    /**
     * Returns the user agent that the API client sends.
     *
     * @return string|null
     */
    public function getUserAgent(): ?string;

    /**
     * Returns the endpoint that the client currently communicates with.
     *
     * @return string
     */
    public function getEndpoint(): string;

    /**
     * Sends a GET request.
     *
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @param array $headers
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function get($uri, array $headers = []): ResponseInterface;

    /**
     * Sends a HEAD request.
     *
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @param array $headers
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function head($uri, array $headers = []): ResponseInterface;

    /**
     * Sends a POST request.
     *
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @param \Psr\Http\Message\StreamInterface|resource|string|null $body
     * @param array $headers
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function post($uri, $body = null, array $headers = []): ResponseInterface;

    /**
     * Sends a PUT request.
     *
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @param \Psr\Http\Message\StreamInterface|resource|string|null $body
     * @param array $headers
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function put($uri, $body = null, array $headers = []): ResponseInterface;

    /**
     * Sends a DELETE request.
     *
     * @param \Psr\Http\Message\UriInterface|string $uri
     * @param \Psr\Http\Message\StreamInterface|resource|string|null $body
     * @param array $headers
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function delete($uri, $body = null, array $headers = []): ResponseInterface;
}
