<?php

namespace Emmetog\Downloader;

/**
 * The MultiDownloader class simply downloads something given an array of Curl paramerers
 * 
 * Might need small tweaks to get it working again, this one is an oldie.
 */
class MultiDownloader
{

    /**
     * @var QueueInterface
     */
    private $preQueue;

    /**
     * @var QueueInterface
     */
    private $postQueue;

    /**
     * @var array An array of the active http downloads
     */
    private $activeHttpDownloads = array();

    /**
     * @var integer The total number of downloads that can be run in parallel
     */
    private $downloadSlots = 10;

    /**
     * @var Resource The multi curl handle
     */
    private $curlMultiHandle;

    public function __construct()
    {
        $this->curlMultiHandle = curl_multi_init();

        echo 'Total download slots: ' . $this->downloadSlots . PHP_EOL;
    }

    /**
     * Sets a job to download. If a slot is available then downloading is started immediately, otherwise job is added to queue
     */
    public function startMultiDownload($url, $extraOptions = array())
    {
        $this->addToDownloadSlot($url, $extraOptions);

        return;
    }

    /**
     * Kicks off a download
     *
     * @param array $workload
     */
    private function addToDownloadSlot($url, $extraOptions = array())
    {
        $originalUrl = $url;


//	if (!is_array($extraCurlOptions)) {
//	    critical('Expecting the $extraCurlOptions param to be an array, a ' . gettype($extraCurlOptions) . ' was given');
//	}
        // check the protocol
        $urlInfo = parse_url($url);

        if (!isset($urlInfo['scheme']))
        {
            $url = 'http://' . $url;
            $urlInfo = parse_url($url);
        }

        if (!isset($urlInfo['path']))
        {
            $url = $url . '/';
            $urlInfo = parse_url($url);
        }

        $shortUrl = $urlInfo['host'] . $urlInfo['path'];

//	echo ('Adding url ' . $url . ' to download slot now' . PHP_EOL);

        $curlHandle = curl_init();

        $connectionTimeout = 30; // 30 seconds

        $downloadTimeout = 60; // 60 seconds

        $defaultCurlOptions = array(
//            CURLOPT_VERBOSE => true,
        );

//	$curlOptions = array_replace($defaultCurlOptions, $extraCurlOptions);

        $mandatoryCurlOptions = array(
            CURLOPT_CONNECTTIMEOUT => $connectionTimeout,
            CURLOPT_TIMEOUT => $downloadTimeout,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_NOBODY => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_MAXCONNECTS => 5,
        );

//        curl_setopt($curlHandle, CURLOPT_VERBOSE, true);



        $urlInfo['scheme'] = strtolower($urlInfo['scheme']);
//        switch ($urlInfo['scheme']) {
//            case 'http' :
//                debug('Protocol is HTTP');
//                break;
//            case 'https' :
//                debug('Protocol is HTTPS');
//                break;
//            case 'ftp' :
//                debug('Protocol is FTP');
//                break;
//            default :
//                debug('The url did not specify the protocol, assuming HTTP');
//                break;
//        }

        $curlOptions = array_replace(array(), $mandatoryCurlOptions);

        foreach ($curlOptions as $opt => $value)
        {
            $success = curl_setopt($curlHandle, $opt, $value);
            if (!$success)
            {
                critical('Error while setting the curlopt ' . $opt . ' with value ' . $value);
            }
        }

        curl_setopt($curlHandle, CURLOPT_URL, $url);

//        echo "output filepath is ". $outputFilepath.PHP_EOL;
//	$file = @fopen($outputFilepath, "w");
//	if (!is_resource($file)) {
//	    fatal('Error opening the $outputFilepath ' . $outputFilepath . ', download not started');
//	    return;
//	}
//	curl_setopt($curlHandle, CURLOPT_FILE, $file);
//        if (isset($post_fields)) {
//            curl_setopt($curlHandle, CURLOPT_POST, TRUE);
//            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $post_fields);
//        }

        $result = curl_multi_add_handle($this->curlMultiHandle, $curlHandle);

        $ch_array_key = (int) $curlHandle;

//	$this->activeHttpDownloads[$ch_array_key] = array(
//	    'url' => $originalUrl,
////	    'options' => $extraCurlOptions,
////	    'filepath' => $outputFilepath,
////	    'filehandle' => $file,
//	);
        $this->activeHttpDownloads[$ch_array_key] = $extraOptions;

//	echo 'A download job (#' . $ch_array_key . ') has been added to a free download slot (' . $this->getFreeSlots() . ' free now)' . PHP_EOL;
    }

    /**
     * Checks for complete downloads and returns the result (blocks until a result is found)
     *
     * This function should be wrapped in a while in order to get all the finished downloads (this function returns them one at a time)
     *
     * @return mixed A result array or false if all downloads have finished
     */
    public function getFinishedMultiDownload()
    {

//        echo "getting complete download\n";

        $info = false;

//        echo count($this->activeHttpDownloads);

        while ($this->getFreeSlots() < $this->downloadSlots)
        { // Stay in this loop until we have a sucessful download in $info or we're finished downloading one
            curl_multi_exec($this->curlMultiHandle, $running);


//            echo "doing loop, active download count is ".count($this->activeHttpDownloads).", \$running is $running\n";
            // Block until something happens
            $select = curl_multi_select($this->curlMultiHandle);

//            echo "finished waiting, \$select is $select\n";

            if ($select < 0)
            {
                continue;
            }




            // Since something's happening (maybe we recieved a chunk of file), give curl a chance to process it
            do
            {
                $status = curl_multi_exec($this->curlMultiHandle, $running);
            }
            while ($status == CURLM_CALL_MULTI_PERFORM);

            $info = curl_multi_info_read($this->curlMultiHandle);




            if (!is_array($info))
            {
                continue;
            }



            /**
             * If we are passing this stage then we have a complete download in $info
             */
//            var_dump($info); die;
            // Now grab the information about the completed requests
            $ch = $info['handle'];





            $chInfo = curl_getinfo($ch);
            curl_multi_remove_handle($this->curlMultiHandle, $ch);
            curl_close($ch);


            $curlHandleKey = (int) $ch;
            $current = $this->activeHttpDownloads[$curlHandleKey];
            unset($this->activeHttpDownloads[$curlHandleKey]);

            // close the output filepointer
//            die;
            // Now fill any open slots. This MUST be done before closing the curl handle of the individual download because the next download will
            // fill the newly freed resource handle if it is closed first.
            // now check whether any slots are available
//	    if (count($this->activeHttpDownloads) < $this->downloadSlots) {
//		debug('Slots available: ' . ($this->downloadSlots - count($this->activeHttpDownloads)));
//
//		// Check the queue and move items from the queue into the dowloading slots
//		$slotsAvailable = $this->getFreeSlots();
//		for ($i = 1; $i <= $slotsAvailable; $i++) {
//		    $nextJob = $this->preQueue->getNextInQueue();
//		    if (!$nextJob) {
//			break;
//		    }
//		    $this->addToDownloadSlot($nextJob['u'], $nextJob['f'], $nextJob['o']);
//		}
//	    }
            // Error handling / statistics gathering here...
//	    if ($error_no != 0) {
//		unlink($outputFilepath);
//		throw new DowloaderHttpDownloadErrorException($error_str);
//	    }
//	    unset($error_no, $error_str);
//
//	    if ($chInfo['http_code'] != '200') {
//		throw new DowloaderHttpDownloadErrorException('The remote server responded with code ' . $chInfo['http_code'] . ' trying to download the url "' . $chInfo['url'] . '"');
//	    }
//	    unset($chInfo);
//	    unset($chInfo, $current['filehandle']);
            // Instead of putting the finished job in the postqueue we will just return it straight away to save memory
            return array('result' => $chInfo, 'options' => $current);
        }


        // if we pass this point then we are not downloading anything else, we must have finished all downloads
        return false;
    }

    /**
     * Returns the number of slots that are free
     *
     * @return integer
     */
    public function getFreeSlots()
    {
        return $this->downloadSlots - count($this->activeHttpDownloads);
    }

    /**
     * @return boolean True if all downloads have finished, false if not
     */
    public function isFinished()
    {
//        debug('Checking if the downloads have finished now');
        $count = count($this->activeHttpDownloads);
//        if ($count === 0) {
//            debug('Downloads have finished');
//        }
//        echo 'isFinished count is '.count($this->activeHttpDownloads).PHP_EOL;
        if ($count < 1)
        {
            return true;
        }
        return false;
    }

}

class DownloaderHttpException extends \Exception
{
    
}

class DowloaderHttpDownloadErrorException extends DownloaderHttpException
{
    
}

?>
