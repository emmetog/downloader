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
     * @param string $url The url to download
     * @param array $extraOptions An array of extra curl options.
     * @param string $destinationFilepath The filepath to save the resource to.
     * 
     * @throws DownloaderInvalidArgumentException When given arguments are invalid.
     * @throws DowloaderInvalidCurlOptException When a specified CURL_OPT option is invalid.
     * @throws DowloaderDownloadErrorException When an error happens while downloading.
     * 
     * @return DownloadResult The DownloadResult object.
     */
    public function download($url, $extraOptions = array(), $destinationFilepath)
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

        if (!is_dir(dirname($destinationFilepath)))
        {
            throw new DownloaderInvalidArgumentException('Destination directory does not exist');
        }
        if (is_file($destinationFilepath) && !is_writable($destinationFilepath))
        {
            throw new DownloaderInvalidArgumentException('Destination filepath is not writable');
        }
        if (!touch($destinationFilepath))
        {
            throw new DownloaderInvalidArgumentException('Destination filepath could not be created');
        }


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

        $fileHandle = fopen($destinationFilepath, 'w');

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

        $returnResult = new DownloadResult($destinationFilepath, $curlInfo, $curlOptions);

        return $returnResult;
    }

}

class DownloaderException extends \Exception
{
    
}

class DownloaderInvalidArgumentException extends \InvalidArgumentException
{
    
}

class DowloaderDownloadErrorException extends DownloaderException
{
    
}

class DowloaderInvalidCurlOptException extends DownloaderException
{
    
}

?>
