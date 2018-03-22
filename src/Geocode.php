<?php

namespace KamranAhmed\Geocode;

/**
 * A wrapper around Google's Geocode API that parses the address,
 * to get different details regarding the address
 *
 * @author  Kamran Ahmed <kamranahmed.se@gmail.com>
 * @license http://www.opensource.org/licenses/MIT
 * @version v2.0
 */
class Geocode
{
    /**
     * API URL through which the address will be obtained.
     */
    private $serviceUrl = "://maps.googleapis.com/maps/api/geocode/json?";

    /**
     * Array containing the query results
     */
    private $serviceResults;

    /**
     * Constructor
     *
     * @param string $key Google Maps Geocoding API key
     */
    public function __construct($key = '')
    {
        $this->serviceUrl = (!empty($key))
            ? 'https' . $this->serviceUrl . "key={$key}"
            : 'http' . $this->serviceUrl;
    }

    /**
     * Returns the private $serviceUrl
     *
     * @return string The service URL
     */
    public function getServiceUrl()
    {
        return $this->serviceUrl;
    }
	
	/**
	 * Viewport Biasing key: `bounds`
	 *      format: [ [top_left_lat, top_left_lng], [bottom_right_lat, bottom_right_lng] ]
	 *      https://developers.google.com/maps/documentation/geocoding/intro#Viewports
	 * Region Biasing key: `region`
	 *      https://developers.google.com/maps/documentation/geocoding/intro#RegionCodes
	 * Component Filtering key: `components`
	 *      https://developers.google.com/maps/documentation/geocoding/intro#ComponentFiltering
	 *
	 * @param array $options
	 * @throws \Exception
	 * @return string urlencoded options
	 */
	protected function buildQueryOptions($options) {
		$q = '';
		if(isset($options['bounds'])) {
			if(count($options['bounds']) !== 2
				|| count($options['bounds'][0]) !== 2
				|| count($options['bounds'][1]) !== 2) {
				
				throw new \Exception("Viewport format is invalid");
			}
			$q .= "&bounds=" . join(',', $options['bounds'][0]) . "|" . join(',', $options['bounds'][1]);
		}
		
		if(isset($options['region'])) {
			$q .= "&region=" . urlencode($options['region']);
		}
		
		if(isset($options['components'])) {
			$components = [];
			foreach ($options['components'] as $key => $component) {
				$components[] = $key . ":" . urlencode($component);
			}
			$q .= "&components=" . join('|', $components);
		}
		
		return $q;
	}
	
	/**
	 * Sends request to the passed Google Geocode API URL and fetches the address details and returns them
	 *
	 * @param $address
	 * @param array $options
	 * @param string $sensor
	 *
	 * @return   bool|object false if no data is returned by URL and the detail otherwise
	 * @throws \Exception
	 * @internal param string $url Google geocode API URL containing the address or latitude/longitude
	 */
    public function get($address, $options = [], $sensor = 'false')
    {
        if (empty($address)) {
            throw new \Exception("Address is required in order to process");
        }

        $url = $this->getServiceUrl()
	        . "&address=" . urlencode($address)
	        . $this->buildQueryOptions($options)
            . "&sensor=" . urlencode($sensor);
        $ch  = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $serviceResults = json_decode(curl_exec($ch));
        if ($serviceResults && $serviceResults->status === 'OK') {
            $this->serviceResults = $serviceResults;

            return new Location($address, $this->serviceResults);
        }

        return new Location($address, new \stdClass);
    }
}
