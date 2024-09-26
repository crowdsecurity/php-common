<?php

declare(strict_types=1);

namespace CrowdSec\Common\Client\RequestHandler;

use CrowdSec\Common\Client\ClientException;
use CrowdSec\Common\Client\HttpMessage\AppSecRequest;
use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Client\HttpMessage\Response;
use CrowdSec\Common\Client\TimeoutException;
use CrowdSec\Common\Constants;

/**
 * File_get_contents request handler.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class FileGetContents extends AbstractRequestHandler
{
    /**
     * @throws ClientException
     * @throws TimeoutException
     */
    public function handle(Request $request): Response
    {
        $config = $this->createContextConfig($request);
        $context = stream_context_create($config);

        $method = $request->getMethod();
        $parameters = $request->getParams();
        $url = $request->getUri();

        if ('GET' === strtoupper($method) && !empty($parameters)) {
            $url .= strpos($url, '?') ? '&' : '?';
            $url .= http_build_query($parameters);
        }

        $lastError = null;
        // Set a custom error handler to catch warnings
        set_error_handler(function ($errno, $errstr) use (&$lastError) {
            $lastError = $errstr;

            return true;
        });
        $fullResponse = $this->exec($url, $context);
        // Restore the previous error handler
        restore_error_handler();
        $responseBody = $fullResponse['response'] ?? false;
        // Check for errors
        if (false === $responseBody) {
            // Use error_get_last() to check if it was a timeout error
            if ($lastError && false !== strpos($lastError, 'timed out')) {
                throw new TimeoutException('file_get_contents call timeout: ' . $lastError, 500);
            }
            throw new ClientException('Unexpected file_get_contents call failure: ' . $lastError, 500);
        }
        $responseHeaders = (isset($fullResponse['header'])) ? $fullResponse['header'] : [];
        $parts = !empty($responseHeaders) ? explode(' ', $responseHeaders[0]) : [];
        $status = $this->getResponseHttpCode($parts);

        return new Response($responseBody, $status);
    }

    /**
     * Convert a key-value array of headers to the official HTTP header string.
     */
    protected function convertHeadersToString(array $headers): string
    {
        $builtHeaderString = '';
        foreach ($headers as $key => $value) {
            $builtHeaderString .= "$key: $value\r\n";
        }

        return $builtHeaderString;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function exec(string $url, $context): array
    {
        return [
            'response' => file_get_contents($url, false, $context),
            'header' => $http_response_header ?? [],
        ];
    }

    /**
     * @param string[] $parts
     *
     * @psalm-param list<string> $parts
     */
    protected function getResponseHttpCode(array $parts): int
    {
        $status = 0;
        if (\count($parts) > 1) {
            $status = (int) $parts[1];
        }

        return $status;
    }

    /**
     * Retrieve configuration for the stream content.
     *
     * @return array|array[]
     *
     * @throws ClientException
     */
    private function createContextConfig(Request $request): array
    {
        $headers = $request->getValidatedHeaders();
        $isAppSec = $request instanceof AppSecRequest;
        $rawBody = '';
        if ($isAppSec) {
            /** @var AppSecRequest $request */
            $rawBody = $request->getRawBody();
        }
        $header = $this->convertHeadersToString($headers);
        $method = $request->getMethod();
        $config = [
            'http' => [
                'method' => $method,
                'header' => $header,
                'ignore_errors' => true,
            ],
        ];
        $config['http'] += $this->handleTimeout($request);
        $config += $this->handleSSL($request);

        if ('POST' === strtoupper($method)) {
            $config['http']['content'] =
                $isAppSec ? $rawBody : json_encode($request->getParams());
        }

        return $config;
    }

    private function handleSSL(Request $request): array
    {
        $result = ['ssl' => ['verify_peer' => false]];
        if ($request instanceof AppSecRequest) {
            /**
             * AppSec does not currently support TLS authentication.
             *
             * @see https://github.com/crowdsecurity/crowdsec/issues/3172
             */
            return $result;
        }
        $authType = $this->getConfig('auth_type');
        if ($authType && Constants::AUTH_TLS === $authType) {
            $verifyPeer = $this->getConfig('tls_verify_peer') ?? true;
            $result['ssl'] = [
                'verify_peer' => $verifyPeer,
                'local_cert' => $this->getConfig('tls_cert_path') ?? '',
                'local_pk' => $this->getConfig('tls_key_path') ?? '',
            ];
            if ($verifyPeer) {
                $result['ssl']['cafile'] = $this->getConfig('tls_ca_cert_path') ?? '';
            }
        }

        return $result;
    }

    private function handleTimeout(Request $request): array
    {
        $timeout = $this->getTimeout($request);
        // Negative value will result in an unlimited timeout

        return ['timeout' => $timeout];
    }
}
