<?php

namespace Emmetog\Downloader;

use Emmetog\Downloader\DownloadResult;

/**
 * The Downloader class simply downloads something given an array of Curl paramerers 
 */
class Downloader
{

    /**
     * Downloads a url
     * 
     * @param type $url
     * @param type $extraOptions 
     */
    public function download($url, $extraOptions = array())
    {
        if (!is_array($extraOptions))
        {
            info('Expecting the $extraCurlOptions param to be an array, a ' . gettype($extraCurlOptions) . ' was given');
        }
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

        $curlHandle = curl_init();

        $defaultCurlOptions = array(
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_MAXCONNECTS => 3,
            CURLOPT_USERAGENT => '',
        );

        $curlOptions = array_replace($defaultCurlOptions, $extraOptions);

        $sourceFilepath = '/tmp/scraper/downloaded_file';
        $fileHandle = fopen($sourceFilepath, 'w');

        $mandatoryCurlOptions = array(
            CURLOPT_VERBOSE => false,
            CURLOPT_FILE => $fileHandle,
        );

        $curlOptions = array_replace($curlOptions, $mandatoryCurlOptions);

        $urlInfo['scheme'] = strtolower($urlInfo['scheme']);

        foreach ($curlOptions as $opt => $value)
        {
            $success = curl_setopt($curlHandle, $opt, $value);
            if (!$success)
            {
                throw new DowloaderInvalidCurlOptException('Error while setting the curlopt ' . $opt . ' with value ' . $value);
            }
        }

        curl_setopt($curlHandle, CURLOPT_URL, $url);

        $curlResult = curl_exec($curlHandle);
        if (!$curlResult)
        {
            throw new DowloaderDownloadErrorException('Unexpected cURL error while downloading');
        }

        $curlInfo = curl_getinfo($curlHandle);

        curl_close($curlHandle);
        fclose($fileHandle);

        $returnResult = new DownloadResult($sourceFilepath, $curlInfo, $curlOptions);

        return $returnResult;
    }

}

class DownloaderException extends \Exception
{
    
}

class DowloaderDownloadErrorException extends DownloaderException
{
    
}

class DowloaderInvalidCurlOptException extends DownloaderException
{
    
}

?>
