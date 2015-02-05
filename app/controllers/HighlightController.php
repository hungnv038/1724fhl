<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 2/5/15
 * Time: 10:17
 */

class HighlightController extends BaseController {
    public function getFHL($chanel_id) {
        $chanel=Chanel::getInstance()->getOneObjectByField(array('id'=>$chanel_id));
        if($chanel==null) {
            return;
        }
        $url=$chanel->url;
        $html=new Htmldom($url);

        $result=$html->find(".post-outer");

        $matchs=array();

        foreach($result as $item) {

            $link=$item->children[0]->children[1]->children[0];

            $link=$link->attr["href"];

            $match=new stdClass();

            $match_title=$item->children[0]->children[1]->children[0]->nodes[0]->text();

            $match->title=$match_title;

            $this->getMatchInfo($link,$match);

            $matchs[$match->id]=$match;
        }

        Chanel::getInstance()->insertMovies($chanel_id,$matchs);
    }
    private function getMatchInfo($match_link,&$match_info) {
        $match=new Htmldom($match_link);
        $post=$match->find(".post-body");

        $post=$post[0]->find("script");

        $post=$post[0]->attr["data-config"];

        $content=file_get_contents($post);

        $content=json_decode($content);

        $content=$content->content;

        $f4m=$content->media->f4m;

        $xml=file_get_contents($f4m);

        $xml=simplexml_load_string($xml);

        $match_info->id=strval($xml->id);

        $baseUrl=$xml->baseURL;

        $xml=$xml->media['url'];

        $match_info->url= $baseUrl."/".$xml;
    }
}