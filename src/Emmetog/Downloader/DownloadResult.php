<?php

namespace Emmetog\Downloader;

/**
 * The result passed to the Parser and Scraper classes. This object allows us to
 * experiment with different ways of writing parsers.
 *
 * @author Emmet O'Grady <emmet@studentcentral.ie>
 */
class DownloadResult
{

    /**
     * The filename where the file is downloaded to.
     *
     * @var string
     */
    protected $filepath;

    /**
     * The info array from the curl result.
     *
     * @var array
     */
    protected $curlInfo;

    /**
     * The options used in the request for the URL.
     *
     * @var array
     */
    protected $curlOptions;

    public function __construct($filepath, array $curlInfo, array $curlOptions)
    {
        $this->filepath = $filepath;
        $this->curlInfo = $curlInfo;
        $this->curlOptions = $curlOptions;
    }

    public function getRawContents()
    {
        return file_get_contents($this->filepath);
    }

    public function getUrl()
    {
        return $this->curlInfo['url'];
    }

    public function getStatusCode()
    {
        return $this->curlInfo['status_code'];
    }

    public function getContentType()
    {
        return $this->curlInfo['content_type'];
    }
    
    /**
     * Gets the filepath to where the downloaded resource is saved locally.
     * 
     * @return string
     */
    public function getFilepath()
    {
        return $this->filepath;
    }
    
    /**
     * Gets the curl_info array from the result of the download.
     * 
     * @return array
     */
    public function getCurlInfo()
    {
        return $this->curlInfo;
    }
    
    /**
     * Gets the curl options used to download the resource.
     * 
     * @return array
     */
    public function getCurlOptions()
    {
        return $this->curlOptions;
    }

    /**
     * Gets a Crawler object.
     * 
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function getCrawler()
    {
        if (!class_exists('Symfony\Component\DomCrawler\Crawler'))
        {
            trigger_error('The Symfony\Component\DomCrawler\Crawler object is not installed');
            return null;
        }

        $crawler = new \Symfony\Component\DomCrawler\Crawler(null, $this->getUrl());
        $crawler->addContent($this->getRawContents(), $this->getContentType());

        return $crawler;
    }

}

?>
