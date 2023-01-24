<?php

declare(strict_types=1);

namespace CrowdSec\Common\Client\RequestHandler;

use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Client\HttpMessage\Response;

/**
 * Request handler abstraction.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractRequestHandler
{
    /**
     * @var array
     */
    private $configs;

    public function __construct(array $configs = [])
    {
        $this->configs = $configs;
    }

    /**
     * Retrieve a config value by name.
     *
     * @return mixed
     */
    public function getConfig(string $name)
    {
        return (isset($this->configs[$name])) ? $this->configs[$name] : null;
    }

    /**
     * Performs an HTTP request and returns a response.
     */
    abstract public function handle(Request $request): Response;
}
