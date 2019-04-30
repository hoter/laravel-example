<?php

namespace App\Http\Controllers\Api\v2;

use App\Repositories\StockRepo;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SitemapController extends ApiController
{
    /**
     * Escapes string to be used in sitemap url.
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
     * Returns list of content page urls with modification date.
     *
     * @param string $host
     * @return array
     */
    protected static function getContentPages($host)
    {
        $urls = [];
        $pages = 1;

        for ($page = 1; $page <= $pages; $page++) {
            $path = static::getContentUrl($host)
                . "/wp-json/wp/v2/pages?fields=link,modified&per_page=100&page=$page";

            try {
                $client = new Client();
                $res = $client->get($path);
                $data = json_decode($res->getBody());

                $data = array_map(function ($page) {
                    $url = $page->link;
                    $url = preg_replace('/^.*?\/\/.*?\//', '', $url);
                    $url = preg_replace('/\/$/', '', $url);

                    return [
                        'url' => $url,
                        'modified' => $page->modified . 'Z',
                    ];
                }, $data);

                // Filter pages used on site by first url part.
                $data = array_filter($data, function ($page) {
                    $pageStartUrls = [
                        'legal/',
                        'account/',
                        'business/',
                        'partners/',
                        'help/',
                    ];

                    return array_first($pageStartUrls, function ($url) use ($page) {
                        return substr($page['url'], 0, strlen($url)) === $url;
                    }) !== null;
                });

                array_walk($data, function ($page) use (&$urls) {
                    $urls[$page['url']] = $page['modified'];
                });

                $totalPagesHeaders = $res->getHeader('x-wp-totalpages');
                $totalPagesHeader = reset($totalPagesHeaders);

                if ($totalPagesHeader !== false) {
                    $pages = (int) $totalPagesHeader;
                }
            } catch (\Exception $error) {
            }
        }

        return $urls;
    }

    /**
     * Returns list of blog posts urls with modification date.
     *
     * @param string $host
     * @return array
     */
    protected static function getBlogPosts($host)
    {
        $urls = [];
        $pages = 1;

        for ($page = 1; $page <= $pages; $page++) {
            $path = static::getContentUrl($host)
                . "/wp-json/wp/v2/posts?fields=slug,modified&per_page=100&page=$page";

            try {
                $client = new Client();
                $res = $client->get($path);
                $data = json_decode($res->getBody());

                $data = array_map(function ($page) {
                    $slug = $page->slug;

                    return [
                        'url' => "blog/$slug",
                        'modified' => $page->modified . 'Z',
                    ];
                }, $data);

                array_walk($data, function ($post) use (&$urls) {
                    $urls[$post['url']] = $post['modified'];
                });

                $totalPagesHeaders = $res->getHeader('x-wp-totalpages');
                $totalPagesHeader = reset($totalPagesHeaders);

                if ($totalPagesHeader !== false) {
                    $pages = (int) $totalPagesHeader;
                }
            } catch (\Exception $error) {
            }
        }

        return $urls;
    }

    /**
     * Returns list of cars/make models pages urls for specified make.
     *
     * @param string $make
     * @return array
     */
    protected static function getCarModelsPages($make)
    {
        $models = StockRepo::getModels($make);
        return array_map(function ($curr) use ($make) {
            $make = static::escapeUrl($make);
            $model = static::escapeUrl($curr);
            return "cars/makes/$make/$model";
        }, $models);
    }

    /**
     * Returns list of cars/make pages urls.
     *
     * @return array
     */
    protected static function getCarMakesPages()
    {
        $makes = StockRepo::getMakes();
        return array_flatten(array_map(function ($curr) {
            $make = static::escapeUrl($curr);
            return ["cars/makes/$make"] + static::getCarModelsPages($curr);
        }, $makes));
    }

    /**
     * Returns list of cars/view pages urls.
     *
     * @return array
     */
    protected static function getCarsPages()
    {
        $query = DB::table('stocks');
        $cars = $query
            ->distinct()
            ->orderBy('id', 'ASC')
            ->get(['id', 'make', 'model', 'derivative'])
            ->toArray();

        return array_map(function ($car) {
            $make = $car->make;
            $model = $car->model;
            $derivative = $car->derivative;
            $id = $car->id;

            $slug = "$make-$model-$derivative-$id";
            $slug = static::escapeUrl($slug);
            return "cars/view/$slug";
        }, $cars);
    }

    /**
     * Returns canonical url base.
     *
     * @return string
     */
    protected static function getBaseUrl()
    {
        return env('CANONICAL_URL_BASE');
    }

    /**
     * Creates sitemap index sitemap-tag.
     *
     * @param string $url
     * @return string
     */
    protected static function createSitemapIndexUrl($url)
    {
        $baseUrl = static::getBaseUrl();

        return <<<END
  <sitemap>
    <loc>$baseUrl$url</loc>
  </sitemap>
END;
    }

    /**
     * Creates sitemap url-tag.
     *
     * @param string $url
     * @param string|null $modified
     * @return string
     */
    protected static function createSitemapUrl($url, $modified = null)
    {
        $baseUrl = static::getBaseUrl();

        if ($modified !== null) {
            return <<<END
  <url>
    <loc>$baseUrl$url</loc>
    <lastmod>$modified</lastmod>
  </url>
END;
        } else {
            return <<<END
  <url>
    <loc>$baseUrl$url</loc>
  </url>
END;
        }
    }

    /**
     * Creates sitemap index.
     *
     * @param array $urls
     * @return string
     */
    protected static function createSitemapIndex($urls)
    {
        $data = implode("\n", array_map(function ($url) {
            return static::createSitemapIndexUrl($url);
        }, $urls));

        return <<<END
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
$data
</sitemapindex>
END;
    }

    /**
     * Checks is array sequential.
     *
     * @param array $arr
     * @return bool
     */
    protected static function isSequential($arr)
    {
        if (empty($arr)) {
            return true;
        }

        return isset($arr[0]);
    }

    /**
     * Creates sitemap.
     *
     * @param array $urls
     * @return string
     */
    protected static function createSitemap($urls)
    {
        if (static::isSequential($urls)) {
            $urlTags = array_map(function ($url) {
                return static::createSitemapUrl($url);
            }, $urls);
        } else {
            $urlTags = array_map(function ($url, $modified) {
                return static::createSitemapUrl($url, $modified);
            }, array_keys($urls), array_values($urls));
        }

        $data = implode("\n", $urlTags);

        return <<<END
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
$data
</urlset>
END;
    }

    /**
     * Creates XML response from string.
     *
     * @param string $content
     * @return Response
     */
    protected static function createXmlResponse($content)
    {
        return response($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Creates sitemap index XML response from url list.
     *
     * @param array $urls
     * @return Response
     */
    protected static function createSitemapIndexResponse($urls)
    {
        $content = static::createSitemapIndex($urls);
        return static::createXmlResponse($content);
    }

    /**
     * Creates sitemap XML response from url list.
     *
     * @param array $urls
     * @return Response
     */
    protected static function createSitemapResponse($urls)
    {
        $content = static::createSitemap($urls);
        return static::createXmlResponse($content);
    }

    /**
     * Returns sitemap index.
     *
     * @return Response
     */
    public function index()
    {
        $urls = [
            'sitemap-static.xml',
            'sitemap-blog-posts.xml',
            'sitemap-car-makes.xml',
            'sitemap-cars.xml',
        ];

        return static::createSitemapIndexResponse($urls);
    }

    /**
     * Returns sitemap for static pages.
     *
     * @param Request $request
     * @return Response
     */
    public function staticPages(Request $request)
    {
        $urls = [
            '' => null,
            'contact' => null,
            'site-map' => null,
            'blog' => null,
            'cars' => null,
            'cars/browse' => null,
            'cars/new' => null,
            'cars/used' => null,
        ];

        $host = $request->getHost();
        $urls += static::getContentPages($host);
        return static::createSitemapResponse($urls);
    }

    /**
     * Returns sitemap for blog posts.
     *
     * @param Request $request
     * @return Response
     */
    public function blogPosts(Request $request)
    {
        $host = $request->getHost();
        $urls = static::getBlogPosts($host);
        return static::createSitemapResponse($urls);
    }

    /**
     * Returns sitemap for cars/make pages.
     *
     * @return Response
     */
    public function carMakes()
    {
        $urls = static::getCarMakesPages();
        return static::createSitemapResponse($urls);
    }

    /**
     * Returns sitemap for cars/view pages.
     *
     * @return Response
     */
    public function cars()
    {
        $urls = static::getCarsPages();
        return static::createSitemapResponse($urls);
    }
}
