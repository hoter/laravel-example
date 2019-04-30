<?php

namespace App\Http\Controllers\Sketchpad;

use App\Models\Stock;
use App\Models\StockImage;
use App\Services\Images\CapImageService;
use App\Services\Images\FeedImageService;

class ImagesController
{

    /**
     * @group Cap
     *
     * @field select $size options:thumb,full
     * @field select $view options:side=1,front=2,three-quarter=3,rear=4,rear three-quarter=5,interior=6
     */
    public function getImage($stockId = 1, $size = 'thumb', $view = 3)
    {
        $stock = Stock::find($stockId);
        alert($stock->make . ' ' . $stock->derivative);

        $service = CapImageService::create($stock->derivative_id, $view, $size)
            ->load()
            ->show();

        p('Image properties:');
        ls($service->values);

        p('Stock properties:');
        ls($stock->toArray());
    }

    public function getAllImages($stockId = 1)
    {
        // variables
        $stock        = Stock::find($stockId);
        $derivativeId = $stock->derivative_id;
        alert($stock->make . ' ' . $stock->derivative);

        // create service
        $service = CapImageService::create();

        // initialize, fetch and save viewpoints
        $service
            ->init($derivativeId, 3, 'thumb')
            ->load(true)
            ->setSize('full');

        // dump values
        dump($service->values);

        // load, save and show full size images
        $service
            ->setName(3)->load()->show()
            ->setName(1)->load()->show()
            ->setName(2)->load()->show()
            ->setName(4)->load()->show()
            ->setName(5)->load()->show()
            ->setName(6)->load()->show();
    }

    /**
     * Note that the example code below is using fake list of URLs (as the feed code needs updating) but will save to the right folders
     *
     * @group Feed
     *
     * @field select    $size options:thumb,full
     * @param int    $stockId
     * @param int    $imageId
     * @param string $size
     * @param bool   $force
     */
    public function testFeedService($stockId = 1, $imageId = 0, $size = 'thumb', $force = false)
    {
        // example images as we don't have stocks ids lined up yet
        $images = [
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_1.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_2.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_3.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_4.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_5.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_6.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_7.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_8.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_9.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_10.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_11.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_12.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_13.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV14984291_14.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_1.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_2.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_3.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_4.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_5.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_6.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_7.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_8.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_9.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_10.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_11.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_12.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_13.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_14.jpg',
            'http://images.autoexposure.co.uk/AETA14709/AETV22240082_15.jpg',
        ];

        // load the service
        $service = app(FeedImageService::class);

        // grab a url (should be pulled from the feed)
        $url = $images[$imageId];

        // load the image
        $service
            ->init($stockId, $imageId, $size)
            ->loadUrl($url, $force); // force is ONLY needed if you want to overwrite an existing image

        // for this example, show the image
        $service
            ->show();

        // dump details
        dump($service);
    }

    /**
     * @group Database
     *
     * @field select $car_type options:Any=,New=new,Used=used
     */
    public function addStockThumbnails($refresh = false, $car_type = '', $run = false)
    {
        $service = CapImageService::create(0, 3, 'thumb');

        $paths   = [];
        $columns = ['id', 'derivative_id', 'derivative'];
        $stocks  = $car_type
            ? Stock::where('car_type', $car_type)->get($columns)
            : Stock::get($columns);

        if ($run)
        {
            alert('Getting stock images...');
            $stocks
                ->each(function ($stock) use (&$paths, $service, $refresh) {
                    // variables
                    $id   = $stock->derivative_id;
                    $path = array_get($paths, $id);

                    // load image
                    if (!$path)
                    {
                        p('Loading stock image ' . $id);
                        $path = $service->setId($id)->load($refresh)->path;
                        $service->show();
                        $paths[$id] = $path;
                    }

                    // update database
                    $stock->thumb_url = url($path);
                    $stock->save();
                });
        }

        alert(count($stocks) . ' rows:');
        tb($stocks, 'index');
    }

    public function addStockImages($refresh = false, $run = false)
    {
        $service = CapImageService::create();

        $ids = \DB::table('stocks')
            ->distinct()
            //->limit(5)
            ->pluck('derivative_id');

        if ($run)
        {
            alert('Getting stock images...');
            collect($ids)
                ->each(function ($id) use (&$paths, $service, $refresh) {

                    // load images
                    $imageIds = [3, 1, 2, 4, 5, 6];
                    foreach ($imageIds as $imageId)
                    {
                        // load and set path
                        $path = $service
                            ->setId($id)
                            ->setName($imageId)
                            ->load()
                            ->path;

                        // model
                        $model = new StockImage([
                            'path'       => $path,
                            'size'       => 'full',
                            'type'       => 'derivative',
                            'related_id' => $id,
                            'width'      => $service->size[0],
                            'height'     => $service->size[1],
                        ]);

                        // output
                        $model->save();
                        dump($model->toArray());
                    }

                });
        }

    }

}

