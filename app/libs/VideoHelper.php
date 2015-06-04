<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 4/16/15
 * Time: 11:52
 */

class VideoHelper {
    private function curlGet($URL) {
        $ch = curl_init();
        $timeout = 3;
        curl_setopt( $ch , CURLOPT_URL , $URL );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , 1 );
        curl_setopt( $ch , CURLOPT_CONNECTTIMEOUT , $timeout );
        /* if you want to force to ipv6, uncomment the following line */
        //curl_setopt( $ch , CURLOPT_IPRESOLVE , 'CURLOPT_IPRESOLVE_V6');
        $tmp = curl_exec( $ch );
        curl_close( $ch );
        return $tmp;
    }
    /*
     * function to use cUrl to get the headers of the file
     */
    private function get_location($url) {
        $my_ch = curl_init();
        curl_setopt($my_ch, CURLOPT_URL,$url);
        curl_setopt($my_ch, CURLOPT_HEADER,         true);
        curl_setopt($my_ch, CURLOPT_NOBODY,         true);
        curl_setopt($my_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($my_ch, CURLOPT_TIMEOUT,        10);
        $r = curl_exec($my_ch);
        foreach(explode("\n", $r) as $header) {
            if(strpos($header, 'Location: ') === 0) {
                return trim(substr($header,10));
            }
        }
        return '';
    }
    private function get_size($url) {
        $my_ch = curl_init();
        curl_setopt($my_ch, CURLOPT_URL,$url);
        curl_setopt($my_ch, CURLOPT_HEADER,         true);
        curl_setopt($my_ch, CURLOPT_NOBODY,         true);
        curl_setopt($my_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($my_ch, CURLOPT_TIMEOUT,        10);
        $r = curl_exec($my_ch);
        foreach(explode("\n", $r) as $header) {
            if(strpos($header, 'Content-Length:') === 0) {
                return trim(substr($header,16));
            }
        }
        return '';
    }
    private function get_description($url) {
        $fullpage = $this->curlGet($url);
        $dom = new DOMDocument();
        @$dom->loadHTML($fullpage);
        $xpath = new DOMXPath($dom);
        $tags = $xpath->query('//div[@class="info-description-body"]');
        $my_description='';
        foreach ($tags as $tag) {
            $my_description .= (trim($tag->nodeValue));
        }

        return utf8_decode($my_description);
    }
    public function downloadYoutubeVideo($video_id) {
        $my_video_info = 'http://www.youtube.com/get_video_info?&video_id='.$video_id."&el=embedded&ps=default&eurl=&hl=en_US"; //video details fix *1
        $my_video_info = $this->curlGet($my_video_info);

        $thumbnail_url = $title = $url_encoded_fmt_stream_map = $type = $url = '';

        parse_str($my_video_info);

        if(isset($url_encoded_fmt_stream_map)) {
            /* Now get the url_encoded_fmt_stream_map, and explode on comma */
            $my_formats_array = explode(',',$url_encoded_fmt_stream_map);

        } else {
            throw new APIException("Can not get video content from Youtube");
        }
        if (count($my_formats_array) == 0) {
            throw new APIException("No valid Youtube video format");
        }
        /* create an array of available download formats */
        $avail_formats[] = '';
        $i = 0;
        $ipbits = $ip = $itag = $sig = $quality = '';
        $expire = time();
        foreach($my_formats_array as $format) {
            parse_str($format);
            $avail_formats[$i]['itag'] = $itag;
            $avail_formats[$i]['quality'] = $quality;
            $type = explode(';',$type);
            $avail_formats[$i]['type'] = $type[0];
            $avail_formats[$i]['url'] = urldecode($url) . '&signature=' . $sig;
            parse_str(urldecode($url));
            $avail_formats[$i]['expires'] = date("G:i:s T", $expire);
            $avail_formats[$i]['ipbits'] = $ipbits;
            $avail_formats[$i]['ip'] = $ip;
            $i++;
        }

        $format=$this->getMp4Url($avail_formats);
        if($format==null) {
            throw new APIException("Do not exist Mp4 format on youtube file");
        } else {
            $url=$format['url'];

            return $this->downloadVideo($url);
        }
    }
    public function downloadVideo($video_url) {
        // folder to save downloaded files to. must end with slash
        set_time_limit (1 * 60 * 60);

        $destination_folder = Constants::SYS_DOWNLOAD_FOLDER;

        $file_name=time().'_'.rand(0,10000).'.mp4';

        $newfname = $destination_folder.'/'.$file_name;

        $file = fopen ($video_url, "rb");
        if ($file) {
            $newf = fopen ($newfname, "wb");

            if ($newf)
                while(!feof($file)) {
                    fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
                }
        }

        if ($file) {
            fclose($file);
        }

        if ($newf) {
            fclose($newf);
        }
        return $file_name;

    }
    private function getMp4Url($avaiable_formats) {
        $first=null;
        $hd720=null;
        $mp4=null;
        foreach ($avaiable_formats as $format) {
            if($format['type']=='video/mp4' ) {
                $first = $format;
                if ($format['quality'] == 'medium') {
                    $mp4=$format;
                }
                if($format['quality']=="hd720") {
                    $hd720=$format;
                }
            }
        }
        if($hd720!=null)
        {
            return $hd720;
        } else if($mp4!=null) {
            return $mp4;
        } else {
            return $first;
        }
    }
    public function upload($file_name,$title,$chanel) {

        $videoPath=Constants::SYS_DOWNLOAD_FOLDER."/".$file_name;

        $scopes = array(
            'manage_videos'
        );
        $api=new Dailymotion();
        $api->setGrantType(Dailymotion::GRANT_TYPE_PASSWORD,
            Config::get('dailymotion.api'),
            Config::get('dailymotion.secret'),
            $scopes,
            array(
                'username' => Config::get('dailymotion.user'), // don't forget to sanitize this,
                'password' => Config::get('dailymotion.password'), // never use POST variables this way
            )
        );

        $url = $api->uploadFile($videoPath);

        // delete file
        unlink($videoPath);

        $description=$title;

        $api->post(
           '/me/videos',
            array('url' => $url, 'title' => $title,'description'=>$description,'published'=>true,'private'=>false,'channel'=>$chanel)
        );
    }

}