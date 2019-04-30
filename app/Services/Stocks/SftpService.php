<?php

namespace App\Services\Stocks;


use Illuminate\Support\Facades\Storage;

class SftpService
{
    /**
     * The SFTP connection configuration.
     */
    protected   $config;
    private   $connection;
    private     $remoteDirectory;
    private     $extension;

    /**
     * SFtpService constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config           = $config;
        $this->connection       = $this->connect();
        $this->extension        = $this->config['fileExtension'];
        $this->remoteDirectory  = $this->config['directory'];
    }


    /**
     * Establish SFTP connection
     *
     * @param $config
     * @return connection
     * @throws \Exception
     */
    public function connect()
    {
        $config = $this->config;
        $connectionId = null;
        if(!isset($config['port']))
            $config['port'] = 22;

        if (!($this->connection = ssh2_connect($config['host'], $config['port']))) {
            throw new \Exception('Cannot connect to server');
        }
        if ($this->connection) {
            if(ssh2_auth_password($this->connection, $config['username'], $config['password'])){
                //Initialize SFTP subsystem
                $connectionId = ssh2_sftp($this->connection);
            }else{
                throw new \Exception('Unable to authenticate on server!');

            }
        }
        return $connectionId;
    }

    /**
     * Get All files from directory
     * @return array
     */

    public function scanFilesystem() {

        $stream = "ssh2.sftp://{$this->connection}/./{$this->remoteDirectory}";
        $tempArray = array();
        $handle = opendir($stream);
        // List all the files
        while (false !== ($file = readdir($handle))) {
            if (substr("$file", 0, 1) != "."){
                $tempArray[] = $file;
            }
        }
        closedir($handle);
        return $tempArray;
    }

    /**
     * Get extension specific file from directory files
     * @param $extension
     * @return mixed|null
     */
    public function searchInFileSystem($extension){

        $fileArray = $this->scanFilesystem();
        $file = null;
        if (!empty($fileArray)) {
            foreach ($fileArray as $item){
                if($this->isValidName($item)){
                    if($this->getFileExtension($item) == $extension){
                        $file = $item;
                        break;
                    }
                }
            }
        }
        return $file;
    }

    /**
     * Check file name is valid or not
     * @param $string
     * @return bool
     */
    public function isValidName($string) {
        return preg_match('/^([-\.\w]+)$/', $string) > 0;
    }

    /**
     * Get extension from file name
     * @param $string
     * @return bool|string
     */
    public function getFileExtension($string) {
        return substr(strrchr($string,'.'),1);
    }

    /**
     * Download file from remote server to local directory
     * @return mixed|null
     * @throws \Exception
     */
    public function receiveFile()
    {
        $getRemote_file = $this->searchInFileSystem($this->extension);
        if (!$remote = @fopen("ssh2.sftp://{$this->connection}/{$this->remoteDirectory}{$getRemote_file}", 'r'))
        {
            throw new \Exception("Unable to write to local file: $getRemote_file");
        }

        $url="ssh2.sftp://{$this->connection}/{$this->remoteDirectory}{$getRemote_file}";
        $contents = file_get_contents($url);
        $name = substr($url, strrpos($url, '/') + 1);

        if (Storage::put($name, $contents) === FALSE)
        {
            throw new \Exception("Unable to write to local file: $getRemote_file");
        }

        @fclose($remote);

        $filePath = Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix().$getRemote_file;

        return $filePath;

    }
    /**
     * Disconnect active connection.
     *
     * @param  config
     * @return void
     */
    public function disconnect() {
        ssh2_exec($this->extension, 'exit');
    }

    /**
     * Check file already uploaded or not
     * @param $file
     * @return int
     */

    public function checkDuplicateFile($file,$type){
        $md5File = md5_file($file);
        $filePath = 'stocks/'.$type.'.json';
        if(!Storage::exists($filePath)){
            return false;
        }
        $getFile = Storage::get($filePath);
        $tempArray=json_decode($getFile);
        $filter = array_filter($tempArray, function($md5) use($md5File) {
            return $md5 == $md5File;
        });
        return $filter ? true :false;
    }

    /**
     * @param $file
     * @param $type
     */
    public function updateUploadHistory($file,$type){
        $md5File = md5_file($file);
        $filePath = 'stocks/'.$type.'.json';
        $getFile = Storage::exists($filePath);
        if($getFile){
            $getFile = Storage::get($filePath);
            $tempArray=json_decode($getFile);
            array_push($tempArray, $md5File);
            Storage::put($filePath,json_encode($tempArray));
        }
        else{
            $tempArray = array($md5File);
            $tempArrayEncode=json_encode($tempArray);
            Storage::put($filePath,$tempArrayEncode);
        }

    }
}