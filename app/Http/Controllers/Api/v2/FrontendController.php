<?php

namespace App\Http\Controllers\Api\v2;

use App\Repositories\StockRepo;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class FrontendController extends ApiController
{
    /**
     * Escapes string to be used in url.
     *
     * @param string $url
     * @return string
     */
    protected static function escapeUrl($url)
    {
        $url = mb_strtolower($url);
        $url = preg_replace('/\s/', '-', $url);
        return preg_replace('/[^a-zA-Z0-9\-_~.]/', '', $url);
    }

    /**
     * Gets content from url.
     *
     * @param string $url
     * @return string
     */
    protected static function download($url)
    {
        $client = new Client();
        $res = $client->get($url);
        return $res->getBody();
    }

    /**
     * Returns content server url.
     *
     * @param string $host
     * @return string
     */
    protected static function getContentUrl($host)
    {
        $uatHost = env('HOST_UAT');

        return $host === $uatHost
            ? env('CONTENT_URL_UAT')
            : env('CONTENT_URL');
    }

    /**
     * Checks is specified page exists on a content server.
     *
     * @param string $host
     * @param string $slug
     * @return bool
     */
    protected static function findContentPage($host, $slug)
    {
        $url = static::getContentUrl($host)
            . "/wp-json/wp/v2/pages?slug=$slug&fields=id";

        try {
            $content = static::download($url);
            $data = json_decode($content);
            return !empty($data);
        } catch (\Exception $error) {
            return false;
        }
    }

    /**
     * Checks is specified post exists on a content server.
     *
     * @param string $host
     * @param string $slug
     * @return bool
     */
    protected static function findContentPost($host, $slug)
    {
        $url = static::getContentUrl($host)
            . "/wp-json/wp/v2/posts?slug=$slug&fields=id";

        try {
            $content = static::download($url);
            $data = json_decode($content);
            return !empty($data);
        } catch (\Exception $error) {
            return false;
        }
    }

    /**
     * Checks is car with specified id exists.
     *
     * @param string $slug
     * @return bool
     */
    protected static function findCar($slug)
    {
        $slugParts = explode('-', $slug);
        $id = end($slugParts);

        if ($id === false) {
            return false;
        }

        $stock = StockRepo::find($id);
        return !empty($stock);
    }

    /**
     * Checks is car make exists.
     *
     * @param string $make
     * @return bool
     */
    protected static function findMake($make)
    {
        $makes = StockRepo::getMakes();
        $makeFound = array_first($makes, function ($curr) use ($make) {
            $curr = static::escapeUrl($curr);
            return $curr === $make;
        });
        return $makeFound !== null;
    }

    /**
     * Checks is car model exists.
     *
     * @param string $make
     * @param string $model
     * @return bool
     */
    protected static function findModel($make, $model)
    {
        $models = StockRepo::getModels($make);
        $modelFound = array_first($models, function ($curr) use ($model) {
            $curr = static::escapeUrl($curr);
            return $curr === $model;
        });
        return $modelFound !== null;
    }

    /**
     * Creates function that returns frontend index.html with specified status.
     *
     * @param string $path
     * @param int $status
     *
     * @return \Closure
     */
    protected static function createResponse($path, $status)
    {
        return function () use ($path, $status) {
            $url = env('PRERENDER_URL') . $path;

            $content = '';

            try {
                $content = static::download($url);
            } catch (\Exception $error) {
            }

            return response($content, $status);
        };
    }

    /**
     * Converts vue-router like path string to regexp pattern string.
     *
     * @param string $path
     *
     * @return string
     */
    protected static function pathToRegexp($path)
    {
        $parts = explode('/', $path);
        $parts = array_map(function ($part) {
            if (!empty($part) && $part[0] === ':') {
                return '([^\/]+)';
            } else {
                return preg_quote($part);
            }
        }, $parts);
        return '/^' . implode('\/', $parts) . '$/';
    }

    /**
     * Check is page exists.
     *
     * @param Request $request
     * @return Response
     */
    public function get(Request $request)
    {
        $host = $request->getHost();

        // Remove controller url part.
        $url = str_replace('api/v2/frontend/get', '', $request->path());

        // Response callbacks.
        $ok = static::createResponse($url, 200);
        $notFound = static::createResponse($url, 404);

        $contentPage = function ($args) use ($host, $ok, $notFound) {
            return static::findContentPage($host, $args[1])
                ? $ok() : $notFound();
        };

        $contentPost = function ($args) use ($host, $ok, $notFound) {
            return static::findContentPost($host, $args[1])
                ? $ok() : $notFound();
        };

        $car = function ($args) use ($ok, $notFound) {
            return static::findCar($args[1]) ? $ok() : $notFound();
        };

        $make = function ($args) use ($ok, $notFound) {
            return static::findMake($args[1]) ? $ok() : $notFound();
        };

        $model = function ($args) use ($ok, $notFound) {
            return static::findModel($args[1], $args[2]) ? $ok() : $notFound();
        };

        // Map route path to response callback.
        $routes = [
            '/' => $ok,
            '/contact' => $ok,
            '/site-map' => $ok,
            '/legal/:page' => $contentPage,
            '/account/:page' => $contentPage,
            '/business/about-us' => $ok,
            '/business/:page' => $contentPage,
            '/partners/lenders/:page' => $contentPage,
            '/partners/:page' => $contentPage,
            '/help/process/how-it-works' => $ok,
            '/help/process/:page' => $contentPage,
            '/help/products/:page' => $contentPage,
            '/help/:page*' => $contentPage,

            '/blog' => $ok,
            '/blog/:slug' => $contentPost,

            '/cars' => $ok,
            '/cars/browse' => $ok,
            '/cars/new' => $ok,
            '/cars/used' => $ok,
            '/cars/view/:slug' => $car,
            '/cars/makes/:make' => $make,
            '/cars/makes/:make/:slug' => $model,

            '/application/personal' => $ok,
            '/application/finances' => $ok,

            // Requires auth. Always return ok.
            '/application/:id/offers' => $ok,
            '/application/:id/summary' => $ok,
            '/application/:id/security' => $ok,
            '/application/:id/deposit' => $ok,
            '/application/:id/contract' => $ok,
            '/application/:id/delivery' => $ok,

            // Static
            '/index.html' => $ok,
        ];

        // Test url with each route.
        foreach (array_keys($routes) as $route) {
            $pattern = static::pathToRegexp($route);
            $matches = [];

            if (preg_match($pattern, $url, $matches)) {
                return $routes[$route]($matches);
            }
        }

        return $notFound();
    }
}
