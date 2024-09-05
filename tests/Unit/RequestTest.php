<?php

declare(strict_types=1);

namespace CrowdSec\Common\Tests\Unit;

/**
 * Test for request.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */

use CrowdSec\Common\Client\HttpMessage\AppSecRequest;
use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Tests\Constants as TestConstants;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CrowdSec\Common\Client\HttpMessage\Request::getParams
 * @covers \CrowdSec\Common\Client\HttpMessage\Request::getMethod
 * @covers \CrowdSec\Common\Client\HttpMessage\Request::getUri
 * @covers \CrowdSec\Common\Client\HttpMessage\Request::__construct
 * @covers \CrowdSec\Common\Client\HttpMessage\AppSecRequest::__construct
 * @covers \CrowdSec\Common\Client\HttpMessage\AppSecRequest::getRawBody
 * @covers \CrowdSec\Common\Client\HttpMessage\AbstractMessage::getHeaders
 */
final class RequestTest extends TestCase
{
    public function testConstructor()
    {
        $request = new Request(
            'test-uri',
            'POST',
            ['test' => 'test', 'User-Agent' => TestConstants::USER_AGENT_SUFFIX],
            ['foo' => 'bar']
        );

        $headers = $request->getHeaders();
        $params = $request->getParams();
        $method = $request->getMethod();
        $uri = $request->getUri();

        $this->assertEquals(
            'POST',
            $method,
            'Request method should be set'
        );

        $this->assertEquals(
            'test-uri',
            $uri,
            'Request URI should be set'
        );

        $this->assertEquals(
            ['foo' => 'bar'],
            $params,
            'Request params should be set'
        );

        $this->assertEquals(
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => TestConstants::USER_AGENT_SUFFIX,
                'test' => 'test',
            ],
            $headers,
            'Request headers should be set'
        );

        $request = new AppSecRequest(
            'test-uri',
            'POST',
            ['test' => 'test'],
            'this is raw body'
        );

        $headers = $request->getHeaders();
        $params = $request->getParams();
        $method = $request->getMethod();
        $uri = $request->getUri();
        $rawBody = $request->getRawBody();

        $this->assertEquals(
            'POST',
            $method,
            'AppSecRequest method should be set'
        );

        $this->assertEquals(
            'test-uri',
            $uri,
            'AppSecRequest URI should be set'
        );

        $this->assertEquals(
            [],
            $params,
            'AppSecRequest params should be empty'
        );

        $this->assertEquals(
            'this is raw body',
            $rawBody,
            'AppSecRequest rawBody should be set'
        );

        $this->assertEquals(
            [
                'test' => 'test',
            ],
            $headers,
            'AppSecRequest headers should be set without Content-Type and Accept'
        );
    }
}
