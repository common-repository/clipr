<?php

// don't forget to prefix function name so it can't be override by over modules

// Curl request
if (!function_exists('clipr_curl')) {
    function clipr_curl($service, $url, $postData = [], $env = "prod") {


        if ($service == "landing") {
            $url = $env == "staging" ? "https://m.aws.clipr.co/fr".$url : "https://m.clipr.co/fr".$url;
        } else {
            $url = $env == "staging" ? "https://app.aws.clipr.co/fr".$url : "https://app.clipr.co/fr".$url;
        }


        // Arguments of WP Http Api
        $args = [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0'
            ],
            "redirection" => 8,
        ];

        // Add post data
        if (!empty($postData)) {
            $args['body'] = $postData;
        }

        // Send request and get response
        $response = wp_remote_post( $url, $args );

        // Is response fine ?
        if ($response['response']['code'] == 200) {

            // Return response body data
            return json_decode($response['body'],true);
        }

        return ['success'=>false];
    }
}


// Easy debug
if (!function_exists('clipr_csl')) {
    function clipr_csl($data)
    {
        if (is_array($data) || is_object($data)) {
            echo(json_encode($data));
        } else {
            echo($data);
        }
    }
}

