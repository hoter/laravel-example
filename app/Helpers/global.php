<?php

use Illuminate\Validation\Rule;

/**
 * Generate an asset path for the application.
 *
 * @param  string $path
 * @param  bool $secure
 * @return string
 */
function assets($path, $secure = null)
{
    return app('url')->asset("assets/$path", $secure);
}

/**
 * Generate an asset path for the application.
 *
 * @param  string $path
 * @param  bool $secure
 * @return string
 */
function resource($path, $secure = null)
{
    return app('url')->asset("resources/$path", $secure);
}

/**
 * Returns the keys from any config array for use in validation
 *
 * @param $path
 * @return \Illuminate\Validation\Rules\In
 */
function rules ($path)
{
    return Rule::in(array_keys(config($path)));
}
