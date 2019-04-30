<?php

namespace App\Services\Images;

use GuzzleHttp\Client;

/**
 * Cap Image Service class
 *
 * Set name, viewport, size, fetch and display images from Cap
 *
 * @see
 *
 *    - App/Services/Images/ImageService.php
 *    - config/constants/images.php
 *    - config/filesystem.php
 *
 * @usage
 *
 *    // create service
 *    $service = CAPImageService::create();
 *
 *    // initialize derivative, viewpoint, and thumbnail, then load (and save)
 *    $service
 *        ->init($derivativeId, 3, 'thumb')
 *        ->load();
 *
 *    // initialize and fetch full size images (images are skipped if they exist)
 *    $service
 *        ->setSize('full')
 *        ->setName(1)->load()
 *        ->setName(2)->load()
 *        ->setName(3)->load()
 *        ->setName(4)->load()
 *        ->setName(5)->load()
 *        ->setName(6)->load();
 *
 *    // dump values (these can be used for updating models)
 *    dump($service->values);
 *
 *    // view 3/4 viewpoint <img> in page
 *    $service->setName(3)->show();
 */
class CapImageService extends ImageService
{
    // -----------------------------------------------------------------------------------------------------------------
    // properties
    // -----------------------------------------------------------------------------------------------------------------

    protected $api;

    protected $username;

    protected $password;

    protected $database;

    protected $viewpoint;


    // -----------------------------------------------------------------------------------------------------------------
    // instantiation
    // -----------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();

        // settings
        $this->provider = 'cap';

        // config
        $config         = config('services.cap');
        $this->api      = $config['image_api'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->database = $config['database'];
    }


    // -----------------------------------------------------------------------------------------------------------------
    // protected methods
    // -----------------------------------------------------------------------------------------------------------------

    protected function fetch()
    {
        $inputs = [
            'CAPID'     => $this->id,
            'VIEWPOINT' => $this->name,     // viewpoints 1 - 6 (uses name)
            'WIDTH'     => $this->size[0],
            'HEIGHT'    => $this->size[1],
            'SUBID'     => $this->username,
            'PWD'       => $this->password,
            'DB'        => $this->database,
        ];

        // api
        $client   = new Client();
        $response = $client->request('POST', $this->api, ['form_params' => $inputs]);
        $data     = $response->getBody()->getContents();

        // handle errors
        if (strstr($data, 'Invalid login') !== false) {
            throw new \Exception($data);
        }

        // only save if we have some data
        if (strlen($data) > 100)
        {
            $this->save($data);
        };

        // return
        return $this;
    }
}