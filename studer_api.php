<?php
/* Modified by Madhu Avasarala 10/06/2019
* ver 1.7 added change active status of VPA
* ver 1.6 added params to getcurl
* ver 1.5 added prod_cosnt as variable and not a constant
* ver 1.4 make the site settings generic instead of hset, etc.
* ver 1.3 add Moodle and WP compatibility and get settings appropriately
*         all data returned as objects instead of arrays in json_decode
*/

// if directly called die. Use standard WP and Moodle practices
if (!defined( "ABSPATH" ) && !defined( "MOODLE_INTERNAL" ) )
    {
    	die( 'No script kiddies please!' );
    }

// class definition begins
class studer_api
{
    const VERBOSE          = true;

    public function __construct()
    {
        $this->verbose      = self::VERBOSE;


			// we are in wordpress environment, don't care about $site_name since get_option is site dependendent
            // ensure key and sercret set correctly no check is made wether set or not
            // Make sure these work for Virtual Account API
			$api_phash		= md5($this->getoption("studer_settings", "studer_password"));
			$api_uhash		= hash('sha256', $this->getoption("studer_settings", "studer_email"));
      error_log( "This is the uhash . $api_uhash");
      error_log( "This is the phash . $api_phash");

		  $api_baseUrl          = $this->getoption("studer_settings", "studer_api_baseurl");
      error_log( "This is the Base URL . $api_baseUrl");
      //$api_installation_id  = 6076;

      // add these as properties of object
      $this->api_uhash		        = $api_uhash;
		  $this->api_phash	          = $api_phash;
		  $this->api_baseUrl	        = $api_baseUrl;


      $api_installation_id  = $this->get_installation_id();
      error_log( "This is the installation ID extracted . $api_installation_id");

      $this->installation_id      = $api_installation_id;
    }       // end construct function

  	/**
  	*  @param optionGroup is the group for the settings
  	*  @param optionField is the serring field within the group
  	*  returns the value of the setting specified by the field in the settings group
  	*/
  	public function getoption($optionGroup, $optionField)
  	{
  		return get_option( $optionGroup)[$optionField];
  	}

    public function get_installation_id()
    {
      $uhash    = $this->api_uhash;
      $phash    = $this->api_phash;
      $baseurl  = $this->api_baseUrl;

      $headers =
      [
       "UHASH: $uhash",
       "PHASH: $phash"
      ];

      $endpoint = $this->baseUrl . "/api/v1/installation/installations";

      $curlResponse   = $this->getCurl ($endpoint, $headers);

      error_log( "This is the response while querying for your Studer installations" . print_r($curlResponse, true) );

      if ($curlResponse[0]->id)
          {
              return $curlResponse[0]->id; // returns parameter value
          }
      else
          {
              if ($this->verbose)
              {
                  error_log( "This is the error message while querying for your Studer Installations" . print_r($curlResponse, true) );
              }
              return null;
          }
    }

    /**
    * read value of given parameter using the Studer API
    *
    */
    public function get_parameter_value()
    {
      $uhash    = $this->api_uhash;
      $phash    = $this->api_phash;
      $baseurl  = $this->api_baseUrl;
      $paramId  = $this->paramId;
      $device           = $this->device;
      $paramPart        = $this->paramPart;
      $installation_id  = $this->installation_id;

      $headers =
      [
       "UHASH: $uhash",
       "PHASH: $phash"
      ];

      $params     = array
                          (
                              "device"    => $device,
                              "paramId"   => $paramId,
                              "paramPart" => $paramPart,
                          );

      $endpoint = $this->baseUrl . "/api/v1/installation/parameter/" . $installation_id;

      $curlResponse   = $this->getCurl ($endpoint, $headers, $params);
      error_log( "This is the response while querying for your Studer parameter" . print_r($curlResponse, true) );

      if ($curlResponse->status == "OK")
          {
              return $curlResponse->floatValue; // returns parameter value
          }
      else
          {
              if ($this->verbose)
              {
                  error_log( "This is the response while querying for your Studer parameter" . print_r($curlResponse, true) );
              }
              return null;
          }
    }





    protected function postCurl ($endpoint, $headers, $params = []) {
      $postFields = json_encode($params);
      array_push($headers,
         'Content-Type: application/json',
         'Content-Length: ' . strlen($postFields));


      $endpoint = $endpoint."?";
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $endpoint);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

      $returnData = curl_exec($ch);
      curl_close($ch);
      if ($returnData != "") {
        return json_decode($returnData, false);     // returns object not array
      }
      return NULL;
    }

    /**
    *  @param endpoint is the full path url of endpoint, not including any parameters
    *  @param headers is the array conatining a single item, the bearer token
    *  @param params is the optional array containing the get parameters
    */
    protected function getCurl ($endpoint, $headers, $params = [])
    {
        // check if anything exists in $params. If so make a query string out of it
       if ($params)
        {
           if ( count($params) )
           {
               $endpoint = $endpoint . '?' . http_build_query($params);
           }
        }
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $endpoint);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); // verifies the authenticity of the peer's certificate
       curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // verify the certificate's name against host
       $returnData = curl_exec($ch);
       curl_close($ch);
       if ($returnData != "")
       {
        return json_decode($returnData, false);     // returns object not array
       }
       return NULL;
    }
}
