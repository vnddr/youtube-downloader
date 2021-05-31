<?php

namespace YouTube;

class Client
{
    // Default HTTP headers
    protected $headers = array();

    // Default cURL options
    protected $options = array();

    public function __construct()
    {
        // do nothing
    }

    protected function buildUrl($uri, $params = array())
    {
        if ($params) {
            return $uri . '?' . http_build_query($params);
        }

        return $uri;
    }

    protected function getCombinedHeaders($headers)
    {
        $headers = $this->headers + $headers;

        array_walk($headers, function (&$item, $key) {
            $item = "{$key}: {$item}";
        });

        return array_values($headers);
    }

    public function request($method, $uri, $params = array(), $headers = array(), $curl_options = array(), $proxy = array())
    {
        $ch = curl_init();

        if ($method == 'GET') {
            curl_setopt($ch, CURLOPT_URL, $this->buildUrl($uri, $params));
        } else {
            curl_setopt($ch, CURLOPT_URL, $uri);

            $post_data = is_array($params) ? http_build_query($params) : $params;

            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getCombinedHeaders($headers));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);


        // proxy settings
        if (isset($proxy)) {
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL , 1);
            curl_setopt($ch, CURLOPT_PROXY, $proxy['addr']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['port']);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['credentials']);
            if (isset($proxy['useragent'])) {
                curl_setopt($ch, CURLOPT_USERAGENT, $proxy['useragent']);
            }
            if (isset($proxy['cookiefile'])) {
                $cookiefileNew = join(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), $proxy['cookiefile']]);
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefileNew);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefileNew);
            }
        }

        // last chance to override
        curl_setopt_array($ch, is_array($curl_options) ? ($curl_options + $this->options) : $this->options);

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);

        $response = new Response();
        $response->status = $info ? $info['http_code'] : 0;
        $response->body = $result;
        $response->error = curl_error($ch);
        $response->info = new CurlInfo($info);

        curl_close($ch);

        return $response;
    }

    public function get($uri, $params = array(), $headers = array(), $proxy = array())
    {
        return $this->request('GET', $uri, $params, $headers, [], $proxy);
    }

    public function post($uri, $params = array(), $headers = array())
    {
        return $this->request('POST', $uri, $params, $headers);
    }
}
