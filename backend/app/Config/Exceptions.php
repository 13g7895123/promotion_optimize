<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Debug\ExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;

/**
 * Setup how the exception handler works.
 */
class Exceptions extends BaseConfig
{
    /**
     * The class to use as the Exception Handler.
     */
    public string $handler = ExceptionHandler::class;

    /**
     * An array of the exception types that should NOT be logged.
     * By default, exceptions that are not considered critical issues
     * are ignored.
     */
    public array $ignoreCodes = [404];

    /**
     * Should the Exception Handler log exceptions?
     */
    public bool $log = true;

    /**
     * List of sensitive data to hide from backtraces.
     * Used by the default exception handler.
     */
    public array $sensitiveDataInTrace = [
        'password',
        'passwd',
        'pass',
        'token',
        'key',
        'secret',
        'authorization'
    ];

    /**
     * Specify the context to show in stack trace previews.
     */
    public int $traceContext = 5;

    /**
     * Use flat array in error template instead of objects
     */
    public bool $logDeprecations = true;

    /**
     * Set this to the log level for deprecation logging.
     */
    public string $deprecationLogLevel = 'error';

    /**
     * Path to the error views directory.
     */
    public string $errorViewPath = '';
}