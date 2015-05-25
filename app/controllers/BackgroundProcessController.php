<?php

class BackgroundProcessController extends BaseController {
    const NUMBER_RECORD = 30;
    public function process( $processId ) {
        $bg = new BackgroundProcess();
        try {
            //$runTime['start_time'] = microtime(true);
            $bg->process($processId);
            //Log::info("Run process: $processId", $runTime);
        } catch( Exception $e ) {
            return ResponseBuilder::error($e);
        }
        return;
    }

    public function cron() {
        try {
            // Run batch process
            //$runTime['start_time'] = microtime(true);
            $processBatch = BackgroundProcess::getBatchProcess(self::NUMBER_RECORD);
            foreach($processBatch as $process) {
                $this->process($process->id);
            }
        } catch (Exception $e ) {
            return ResponseBuilder::error($e);
        }
        return;
    }
    public function createChanelsCron() {
        Log::info("Crons/Chanels run");
        $chanels=Chanel::getInstance()->getObjectsByField(array());
        foreach ($chanels as $key => $chanel) {
            BackgroundProcess::getInstance()->throwProcess("crons/chanels/".$chanel->id,array());
        }
    }
    public function createMoviesCron($chanel_id) {
        $chanel=Chanel::getInstance()->getOneObjectByField(array('id'=>$chanel_id));
        if($chanel==null) {
            return;
        }
        $url=$chanel->url;
        $html=new Htmldom($url);

        $result=$html->find(".post-outer");

        $commands=array();
        $params=array();

        foreach($result as $item) {
            $thumb=$item->children[0]->children[0]->children[0];
            $thumb=$thumb->attr["src"];

            $link=$item->children[0]->children[1]->children[0];

            $link=$link->attr["href"];

            $match_title=$item->children[0]->children[1]->children[0]->nodes[0]->text();
            $commands[$link]="crons/chanels/movie";

            $params[$link]=array('chanel_id'=>$chanel_id,'link'=>$link,'title'=>$match_title,'thumb'=>$thumb);
        }
        $need_to_updates=Movie::getInstance()->getObjectsByFields(
            array(
                'match_url'=>array_keys($params),
                'is_updated'=>array(1)));

        foreach ($need_to_updates as $match) {
            unset($commands[$match->match_url]);
            unset($params[$match->match_url]);
        }

        if(count($commands)>0) {
            BackgroundProcess::getInstance()->throwMultipleProcesses(array('command'=>array_values($commands),'parameter'=>array_values($params)));
        }
    }

    public function loadMovieInfo() {
        $chanel_id=InputHelper::getInput('chanel_id',true);
        $link=InputHelper::getInput('link',true);
        $title=InputHelper::getInput('title',true);
        $thumb=InputHelper::getInput('thumb',true);

        $match=new stdClass();
        $match->title=$title;
        $match->match_url=$link;
        $match->thumb=$thumb;

        $this->getMatchInfo($link,$match);

        if(!ChanelMovie::getInstance()->isExistingRecord($chanel_id,$link)) {
            ChanelMovie::getInstance()->insert(array('chanel_id'=>$chanel_id,'match_url'=>$link,'created_at'=>array('now()')));
        }

        Chanel::getInstance()->insertMovie($match);
    }

    private function getMatchInfo($match_link,&$match_info) {
        $match=new Htmldom($match_link);
        $post=$match->find(".post-body");

        $post=$post[0]->find("script");

        $post=$post[0]->attr["data-config"];

        if(!filter_var($post,FILTER_VALIDATE_URL)) {
            $post="http:".$post;
        }
        if(!filter_var($post,FILTER_VALIDATE_URL)) {
            throw new APIException("URL ".$post." is invalid");
        }

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
    public function uploadVideo() {
        try {
            $url=InputHelper::getInput("url",true);
            $file_name=InputHelper::getInput("file_name",true);
            $title=InputHelper::getInput("title",true);

            $videoHelper=new VideoHelper();

            $videoHelper->upload($file_name,$title,$url);
        } catch(Exception $e) {
            return ResponseBuilder::error($e);
        }

    }
    public function downloadVideo() {
        try {
            $link=InputHelper::getInput("url",true);
            $title=InputHelper::getInput("title",true);
            $videoHelper=new VideoHelper();
            $file_name=$videoHelper->downloadVideo($link);

            BackgroundProcess::throwProcess("/crons/video/upload",array('url'=>$link,'file_name'=>$file_name,'title'=>$title));
        } catch(Exception $e) {
            return ResponseBuilder::error($e);
        }

    }
}
