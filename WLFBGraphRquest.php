<?php

namespace App\Models\FBGraphApi;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

use Facebook\Facebook as Facebook;
use Facebook\Exceptions\FacebookResponseException as FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException as FacebookSDKException;

use Session;

class WLFBGraphRquest
{
    /*
    *   Package Info
    *   author : Wei-Lun Hsu
    *   author-email : gxh0181160809rp@gmail.com
    *   github : https://github.com/weilun-shrimp/WLFBGraphRequest.git
    *   dev_for : 
    *           For those who get started use or learnning fb graph api php developer,
    *       apply instinctive code method in codding, reduce code rows and words.
    *           For example, you don't need to set a function for request,
    *       only need codding straight and instinct code when you want to request,
    *       similar to laravel "Eloquent".
    *
    *       So, just enjoy it, I will open source in github.
    */

    public $app_id;
    public $app_secret;
    public $defult_graphVersion;

    //for graph
    public $prepoint;
    public $endpoint;
    public $urlpara;
    public $access_token;
    public $method;

    public $para; //for post,delete

    public $fields;
    public $batch;
    public $include_headers;



    //result
    public $is_suc; // If Request Http Status Code first word is 2 or '2'
    public $res_code; // Request Http Status Code
    public $decode_body; // If fb callback suc
    public $error_msg; // If fb callback error
    public $response; //result for fb callback object


    public function __construct()
    {
        $this->app_id = env('FB_APP_ID');
        $this->app_secret = env('FB_APP_SECRET');
        $this->defult_graphVersion = env('FB_DEFAULT_GRAPH_VERSION');

        $this->method = 'get';
        $this->para = [];
        $this->include_headers = 'false';
        $this->urlpara = '';
    }

    public function init_fb_request()
    {
        return new Facebook([
            'app_id' => $this->app_id, // Replace {app-id} with your app id
            'app_secret' => $this->app_secret,
            'default_graph_version' => $this->defult_graphVersion,
        ]);
    }

    public function get_graph($url_node, $type)
    {   //未整合方法--以前的
        $method = $this->method;
        try {
            // Returns a `FacebookFacebookResponse` object
            $response = $this->init_fb_request()->$method();
        } catch (FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        if ($type == 'edge') {
            return $response->getGraphEdge();
        } elseif ($type == 'node') {
            return $response->getGraphNode();
        }
    }

    public function request()
    {
        $method = $this->method;
        try {
            // Returns a `FacebookFacebookResponse` object
            if ($method == 'get') {
                $this->endpoint = $this->build_endpoint($this->prepoint, $this->fields, $this->urlpara);
                $response = $this->init_fb_request()->$method(
                    $this->endpoint,
                    $this->access_token,
                );
            } else { //post, delete
                /*
                *   In fb graph api php sdk, fields should in para, not in endpoint.
                *   But actually, if you insert in endpoint, it should be ok too.
                *   Because fb graph api is url structure, so your url ok, it should ok.
                */
                $this->endpoint = $this->build_endpoint($this->prepoint, null, $this->urlpara);
                $this->para = $this->build_para($this->fields, $this->batch, $this->include_headers);
                $response = $this->init_fb_request()->$method(
                    $this->endpoint,
                    $this->para,
                    $this->access_token,
                );
            }
        } catch (FacebookResponseException $e) {
            $response = $e;
        } catch (FacebookSDKException $e) {
            $response = $e;
            // echo 'Facebook SDK returned an error: ' . $e->getMessage();
            // exit;
        }

        if (substr($response->getHttpStatusCode(), 0, 1) == 2 || substr($response->getHttpStatusCode(), 0, 1) == '2') {
            $this->is_suc = true;
            $this->res_code = $response->getHttpStatusCode();
            $this->decode_body = $response->getDecodedBody();
            $this->response = $response;
        } else {
            $this->is_suc = false;
            $this->res_code = $response->getHttpStatusCode();
            $this->error_msg = $response->getMessage();
            $this->response = $response;
        }
        return $this;
    }

    public static function get($prepoint)
    {
        $self = new self();
        $self = $self->prepoint($prepoint);
        return $self;
    }

    public static function post($prepoint = null)
    {
        $self = new self();
        $self->method = __FUNCTION__;
        $self = $self->prepoint($prepoint);
        return $self;
    }

    public static function delete($prepoint)
    {
        $self = new self();
        $self->method = __FUNCTION__;
        $self = $self->prepoint($prepoint);
        return $self;
    }

    public static function multiple($prepoints, $mul_prepoints = [], $defult_fields = null, $defult_url_para = null, $defult_method = 'GET', $include_headers = 'false')
    { //prepoints can't allow $keys
        $self = self::post($prepoints);
        $self->batch = $self->build_batch($mul_prepoints, $defult_fields, $defult_url_para, $defult_method);
        return $self;
    }

    public function prepoint($prepoint)
    {
        $this->prepoint = '/';
        if (is_array($prepoint))
            $this->prepoint .= implode('/', $prepoint);
        else
            $this->prepoint .= $prepoint;
        return $this;
    }

    public function build_endpoint($prepoint = '', $fields = null, $urlpara = null, $batch = null)
    {
        $result = '';
        if ($fields) $result .= '&fields=' . $fields;
        if ($urlpara) $result .= '&' . $urlpara;
        if ($batch) $result .= '&batch=' . $batch;
        $result = substr($result, 1);
        $prepoint .= '?' . $result;
        return $prepoint;
    }

    public function access_token($token)
    {
        $this->access_token = $token;
        return $this;
    }

    public function fields($fields = [])
    {
        $this->fields = $this->build_fields_node($fields);
        return $this;
    }

    public function build_fields_node($fields = [])
    {
        $result = '';
        if (is_array($fields)) {
            foreach ($fields as $key => $value) {
                if (!is_string($key)) {
                    $result .= $this->build_fields_node($value);
                } else {
                    $result .= $key . '{' . $this->build_fields_node($value) . '}';
                }
                $result .= ',';
            }
        } else {
            $result .= $fields . ',';
        }
        $result = substr($result, 0, -1);
        return $result != '' ? $result : null;
    }

    public function urlpara($paras = [], $value = null)
    {
        $this->urlpara = $this->build_urlpara($paras, $value);
        return $this;
    }

    public function build_urlpara($paras = [], $value = null)
    {
        $result = '';
        if (is_array($paras) && !empty($paras)) {
            foreach ($paras as $key => $para_value) {
                $result .= '&' . $key . '=' . $para_value;
            }
        } else {
            $result .= !empty($paras) ? '&' . $paras . '=' . $value : '';
        }
        $result = substr($result, 1);
        return $result != '' ? $result : null;
    }

    public function build_para($fields, $batch, $include_headers = 'false')
    { // only for method: post, delete
        $result = [];
        if ($fields) $result['fields'] =  $fields;
        if ($batch) $result['batch'] =  $batch;
        $result['include_headers'] =  $include_headers;
        return $result;
    }

    public function build_batch($prepoints = [], $defult_fields = [], $defult_urlpara = [], $defult_method = 'GET')
    {
        $defult_fields = $this->build_fields_node($defult_fields);
        $defult_urlpara = $this->build_urlpara($defult_urlpara);
        $result = '[';
        if (is_array($prepoints)) {
            foreach ($prepoints as $value) {
                if (is_array($value)) {
                    /*
                    *   If array have inner array, it should at least possess 1 value by key in "prepoint" .
                    *   Key in "fields" or "urlpara" value will apply build thenself function.
                    *   If without possess Key in "fields" or "urlpara" value, inherit defult.
                    */
                    $pre_fields = isset($value['fields']) ? $this->build_fields_node($value['fields']) : $defult_fields;
                    $pre_urlpara = isset($value['urlpara']) ? $this->build_urlpara($value['urlpara']) : $defult_urlpara;
                    $pre_point = $this->build_endpoint($value['prepoint'], $pre_fields, $pre_urlpara);
                    $pre_method = isset($value['method']) ? $value['method'] : $defult_method;
                    $result .= $this->build_batch_inner_json($pre_method, $pre_point);
                } else {
                    $pre_point = $this->build_endpoint($value, $defult_fields);
                    $result .= $this->build_batch_inner_json($defult_method, $pre_point);
                }
            }
        } else {
            $pre_point = $this->build_endpoint($prepoints, $defult_fields, $defult_urlpara);
            $result .= $this->build_batch_inner_json($defult_method, $pre_point);
        }
        $result = substr($result, 0, -1) . ']'; //remove latest comma(,) from build_batch_inner_json()
        return $result;
    }

    public function build_batch_inner_json($method, $relative_url)
    {
        $result = '{';
        $result .= "\"method\":\"" . $method . "\",";
        $result .= "\"relative_url\":\"" . $relative_url . "\"";
        $result .= '},';
        return $result;
    }
}