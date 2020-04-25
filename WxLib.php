<?php

class WxLib
{
    private $config;
    private $cache;
    function __construct()
    {
        $this->config = require('config.php');
        $this->cache = new Cache('wx_data.json');
    }
    function show_config()
    {
        var_dump($this->config);
    }
    function get_access_key()
    {
        $start_at = time();
        $access_token = $this->cache->get('access_token');
        $expire_at = $this->cache->get('token_expire_at');
        if ($expire_at > $start_at) {
            return $access_token;
        }
        $app_id = $this->config['app_id'];
        $app_secret = $this->config['app_secret'];
        $acc_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$app_id}&secret={$app_secret}";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $acc_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $acc_data = curl_exec($curl);
        $acc_data = json_decode($acc_data, true);
        $access_token = $acc_data['access_token'];
        $expire_at = $start_at + $acc_data['expires_in'] - 20;
        $this->cache->put('access_token', $access_token);
        $this->cache->put('token_expire_at', $expire_at);
        return $access_token;
    }
    function base_post($url, $post_data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        //curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($post_data));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($curl);
        $data = json_decode($data, true);
        curl_close($curl);
        return $data;
    }
    function base_get($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $resp_data = curl_exec($curl);
        $resp_data = json_decode($resp_data, true);
        curl_close($curl);
        return $resp_data;
    }
    function send_template_message($template_id, $data, $to_user, $return_url = "")
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $this->get_access_key();
        $post_data['touser'] = $to_user;
        if (!empty($return_url)) {
            $post_data['url'] = $return_url;
        }
        $post_data['template_id'] = $template_id;
        foreach ($data as $key => $value) {
            $post_data['data'][$key] = [
                'value' => $value,
                'color' => '#173177'
            ];
        }
        return $this->base_post($url, $post_data);
    }
    function get_permanent_media_list($type, $from = 0, $count = 20)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=" . $this->get_access_key();
        $post_data['type'] = $type;
        $post_data['offset'] = $from;
        $post_data['count'] = $count;
        return $this->base_post($url, json_encode($post_data, JSON_UNESCAPED_UNICODE));
    }
    function get_permanent_media_counts()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=" . $this->get_access_key();
        return $this->base_get($url);
    }
    function add_permanent_material($file, $type = "image")
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=" . $this->get_access_key() . "&type={$type}";
        $post_data['media'] = new CURLFile($file);
        return $this->base_post($url, $post_data);
    }
    function add_news($title, $content, $thumb, $source_url, $author = "EnderCaster", $show_cover_pic = 1, $digest = "")
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_news?access_token=" . $this->get_access_key();
        $single_article_data = [
            'title' => $title,
            'content' => $content,
            "thumb_media_id" => $thumb,
            "content_source_url" => $source_url,
            "author" => $author,
            'show_cover_pic' => $show_cover_pic
        ];
        if ($digest) {
            $single_article_data['digest'] = $digest;
        }
        $post_data['articles'][] = $single_article_data;
        return $this->base_post($url, json_encode($post_data, JSON_UNESCAPED_UNICODE));
    }
    function push_to_preview($news_media_id, $to_user)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=" . $this->get_access_key();
        $post_data = [
            'touser' => $to_user,
            'mpnews' => [
                'media_id' => $news_media_id
            ],
            'msgtype' => "mpnews"
        ];
        return $this->base_post($url, json_encode($post_data, JSON_UNESCAPED_UNICODE));
    }
    function get_user_tag($open_id)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/tags/getidlist?access_token=" . $this->get_access_key();
        $post_data = [
            'openid' => $open_id
        ];
        return $this->base_post($url, json_encode($post_data, JSON_UNESCAPED_UNICODE));
    }
    function create_tag($tag_name)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/tags/create?access_token=" . $this->get_access_key();
        $post_data['tag'] = [
            "name" => $tag_name
        ];
        return $this->base_post($url, json_encode($post_data, JSON_UNESCAPED_UNICODE));
    }
    function get_tag_list()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/tags/get?access_token=" . $this->get_access_key();
        return $this->base_get($url);
    }
    function add_tag_to_users($open_id_list, $tag_id)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging?access_token=" . $this->get_access_key();
        $post_data = [
            'openid_list' => $open_id_list,
            'tagid' => $tag_id
        ];
        return $this->base_post($url, json_encode($post_data, JSON_UNESCAPED_UNICODE));
    }
    function get_interface_summary()
    {
        $url = 'https://api.weixin.qq.com/datacube/getinterfacesummary?access_token=' . $this->get_access_key();
        $post_data = [
            'begin_date' => '2020-04-06',
            'end_date' => '2020-04-07'
        ];
        return $this->base_post($url, json_encode($post_data));
    }
    function get_interface_summary_hour()
    {
        $url = 'https://api.weixin.qq.com/datacube/getinterfacesummaryhour?access_token=' . $this->get_access_key();
        $post_data = [
            'begin_date' => '2020-04-07',
            'end_date' => '2020-04-07'
        ];
        return $this->base_post($url, json_encode($post_data));
    }
    /**
     * fuck,这号没有这个权限
     */
    function push_to_all($mp_news_id, $tag_id)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=" . $this->get_access_key();
        $post_data = [
            'filter' => [
                'is_to_all' => false,
                'tag_id' => $tag_id
            ],
            'mpnews' => [
                "media_id" => $mp_news_id
            ],
            "msgtype" => "mpnews",
            "send_ignore_reprint" => 0
        ];
        return $this->base_post($url, json_encode($post_data, JSON_UNESCAPED_UNICODE));
    }
}
