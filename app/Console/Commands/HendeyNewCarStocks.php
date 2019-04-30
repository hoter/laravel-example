<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\Stocks\SftpService;

class HendeyNewCarStocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:hendy-new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import hendy new car form sftp';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //Tread from sftp
        //pass this new path to importStocks

        try {
            $config = config('services.hendy_sftp.new_car');

            $service = new SftpService($config);
            $sftp = $service->receiveFile();
            $isDuplicate = $service->checkDuplicateFile($sftp,'hendy');
            if(!$isDuplicate){
                $className = config('constants.feeds')['hendy']['class'];
                $importStock = $className::importStocks($sftp, 'hendy');
                if($importStock){
                    $service->updateUploadHistory($sftp,'hendy');
                    File::Delete($sftp) ;
                }

                $this->info('Stock feed has been successfully imported.');
            }
            else{
                $this->error('Duplicate file. This stock feed file already uploaded.');
            }

        } catch (\Exception $e) {
            $this->error('Unable to import stock feed. '. $e->getMessage());
        }
    }
}
