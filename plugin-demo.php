<?php
session_start();

if (!isset($_SESSION['imgur_state'])) {
    $_SESSION['imgur_state']  = sha1(microtime());
}

$state =  $_SESSION['imgur_state'];
$authUrl = getAuthUrl($client_id,null,$state);
if (!isset($_SESSION['access_token'])) {
    getAuth($client_id,$state,$authUrl,$client_secret);
}

$token = $_SESSION['access_token'];

$images = getImages($token);
if (isset($images['data']['error'])) {
    if ($images['data']['error'] == 'The access token provided is invalid.') {
        getAuth($client_id,$state,$authUrl,$client_secret);
        $images = getImages($token);
    }
}

//$images = getImages($token);
//print_r();

foreach($images['data'] as $key => $val) {
    $image = getImage($val);
}



class ImgurAPI {

    private $env;
    private $clientId;// = '61cf321962377a9';
    private $clientSecret;// = ''
    const AUTHORIZE_URL = 'https://api.imgur.com/oauth2/authorize';

    public function __construct()
    {
        $this->env = $env = require_once __DIR__.'./.env.php';
        $this->clientId = $env['clientId'];
        $this->clientSecret = $env['clientSecret'];
    }


    public function getAuthUrl($clientId,$responseType = null,&$state) {
        if (!isset($responseType)) {
            $responseType = 'code';
            //$responseType = 'pin';
        }
        //if (!isset($token)) {
        //    $state = bin2hex(openssl_random_pseudo_bytes($clientId));
        //}
        $authUrl = "https://api.imgur.com/oauth2/authorize?client_id={$clientId}&response_type={$responseType}&state={$state}";

        return $authUrl;

    }

    public function getTokenJson($client_id,$client_secret,$code,$grant_type = null)
    {
        if (!isset($grant_type)) {
            $grant_type = urlencode('authorization_code');
        }
        if ($grant_type == 'authorization_code') {
            $codeKey = 'code';
        }
        else {
            $codeKey = $grant_type;
        }
        $tokenUrl = "https://api.imgur.com/oauth2/token";//?client_id={$client_id}&client_secret={$client_secret}&grant_type=authorization_code&code={$code}";
        $fields = array(
            'client_id' => urlencode($client_id),
            'client_secret' => urlencode($client_secret),
            'grant_type' => $grant_type,
            $codeKey => urlencode($code)
        );
        $fields_string = '';
        foreach($fields as $key=>$value) {
            $fields_string .= $key . "=" . $value . "&";
        }
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $tokenUrl);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        $json = json_decode($data, true);
        curl_close($ch);
        return $json;
    }

    public function getImages($token)
    {
        $url = 'https://api.imgur.com/3/account/me/image';
        $url = 'https://api.imgur.com/3/account/me';
        $url = 'https://api.imgur.com/3/account/me/images/ids';//{page}
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.urlencode($token)));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $json = json_decode($data, true);
        curl_close($ch);
        return $json;
    }


    public function getAuth($client_id,$state,$authUrl,$client_secret)
    {
        if (!isset($_SESSION['code']) || !isset($_SESSION['access_token'])) {
            if (!isset($_GET['code']) || !isset($_GET['state']) || !($_GET['state'] = $state)) {
                header('Location: ' . $authUrl, true, 303);
                exit();
            }
            $code = $_SESSION['code'] = $_GET['code'];
            $tokenjson = getTokenJson($client_id,$client_secret,$code);
        }
        else {
            if (isset($_SESSION['refresh_token'])) {
                $tokenjson = getTokenJson($client_id,$client_secret,$_SESSION['refresh_token'],'refresh_token');
            }
        }
        if (isset($tokenjson['access_token'])) {
            $_SESSION['access_token'] = $tokenjson['access_token'];
            if (isset($tokenjson['refresh_token'])) {
                $_SESSION['refresh_token'] = $tokenjson['refresh_token'];
            }

        }
        else {
            echo print_r($tokenjson);
        }
    }

    public function getImage()
    {
        https://api.imgur.com/3/account/{username}/image/{id}
    }
}