<?php

namespace Config;

/**
 * Paths
 *
 * This file contains all the path configurations required by CodeIgniter
 * to locate the various system directories.
 */
class Paths
{
    /**
     * The path to the directory containing the system directory.
     */
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    /**
     * The path to the directory containing the application directory.
     */
    public string $appDirectory = __DIR__ . '/..';

    /**
     * The directory name of the directory containing the writable directory.
     */
    public string $writableDirectory = __DIR__ . '/../../writable';

    /**
     * The directory name of the directory containing the test files.
     */
    public string $testsDirectory = __DIR__ . '/../../tests';

    /**
     * The directory name of the directory containing the views directory.
     */
    public string $viewDirectory = __DIR__ . '/../Views';
}