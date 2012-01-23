<?php
/**
 * Make curl requests
 */
class Curl
{
  private $url;
  private $port;

  /**
   * Curl to $url on $port
   */
  public function __construct($host_url, $port = 80)
  {
    $this->url = $host_url;
    $this->port = $port;
  }

  /**
   * @param $path relative path from base url
   * @param $get_params An associative array of get parameters
   * @param $post_params The data to send in your post
   *
   * @return Remote response body as string
   */
  public function post($path, array $get_params, array $post_params)
  {
    return Curl_Helper::post(
            $this->url . $path, $get_params, $post_params, $this->port);
  }
}
