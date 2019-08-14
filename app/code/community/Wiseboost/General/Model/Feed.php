<?php
/**
 * Wiseboost
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Wiseboost
 * @package     Wiseboost_General
 * @copyright   Copyright (c) 2013 Wiseboost. (http://www.wiseboost.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Wiseboost_General_Model_Feed extends Mage_AdminNotification_Model_Feed
{
    const XML_USE_HTTPS_PATH			= 'wiseboostgeneral/feed/use_https';
    const XML_FEED_URL_PATH     		= 'wiseboostgeneral/feed/url';
    const XML_FREQUENCY_PATH    		= 'wiseboostgeneral/feed/frequency'; 
	const XML_FREQUENCY_ENABLE    		= 'wiseboostgeneral/feed/enable';
    const XML_LAST_UPDATE_PATH  		= 'wiseboostgeneral/feed/last_update';

    /**
     * Retrieve feed url
     *
     * @return string
     */
    public function getFeedUrl()
    {
        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = (Mage::getStoreConfigFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://')
                . Mage::getStoreConfig(self::XML_FEED_URL_PATH);
        }
        return $this->_feedUrl;
    }
	
    /**
     * Check feed for modification
     *
     * @return Mage_AdminNotification_Model_Feed
     */
    public function checkUpdate()
    {

        if (($this->getFrequency() + $this->getLastUpdate()) > time()) {
            return $this;
        }

        $feedData = array();

        $feedXml = $this->getFeedData();

        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
				if ($this->isValidItem($item)) {
					$feedData[] = array(
						'severity'      => (int)$item->severity ? (int)$item->severity : 3,
						'date_added'    => $this->getDate((string)$item->pubDate),
						'title'         => (string)$item->title,
						'description'   => (string)$item->description,
						'url'           => (string)$item->link,
					);
				}
            }

            if ($feedData) {
                Mage::getModel('adminnotification/inbox')->parse(array_reverse($feedData));
            }

        }
        $this->setLastUpdate();

        return $this;
    }
	
    /**
     * Retrieve Update Frequency
     *
     * @return int
     */
    public function getFrequency()
    {
        return Mage::getStoreConfig(self::XML_FREQUENCY_PATH) * 3600;
    }
	
    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return Mage::app()->loadCache('wiseboostgeneral_notifications_lastcheck');
    }
	
    /**
     * Set last update time (now)
     *
     * @return Mage_AdminNotification_Model_Feed
     */
    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), 'wiseboostgeneral_notifications_lastcheck');
        return $this;
    }
	
	public static function check(){
		if (!Mage::getStoreConfig(self::XML_FREQUENCY_ENABLE)) { 
			return;
		}
		
		return Mage::getModel('wiseboostgeneral/feed')->checkUpdate();
	}
	
	/* Valid the feed item */
	private function isValidItem($item) {
		$bValid = false;
		
		if ( (strtotime($this->getDate((string)$item->pubDate))) >= 
			(strtotime("7 May 2013")) ) {
			$bValid = true;
		}
		
		return $bValid;
	}
}
