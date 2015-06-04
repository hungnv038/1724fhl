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
    public function createYoutubeChanelCron() {
        Log::info("Crons Youtube Chanel run");
        $youtubeChanel= new YoutubeChanel();
        $chanels=$youtubeChanel->getObjectsByField(array('active'=>1));
        foreach ($chanels as $chanel) {
            BackgroundProcess::getInstance()->throwProcess("/crons/youtube/chanels/".$chanel->id);
        }
    }
    public function createYoutubeVideoCron($chanel_id) {
        $youtubeChanel=new YoutubeChanel();
        $chanel=$youtubeChanel->getOneObjectByField(array('id'=>$chanel_id));

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

            $videoHelper->upload($file_name,$title,'sport');
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

    public function getYoutubeChanelVideos($chanel_id) {
        $chanel=YoutubeChanel::getInstance()->getOneObjectByField(array('id'=>$chanel_id));
        if($chanel==null) {
            return;
        }
        $youtubeHelper=new YoutubeVideoHelper();
        $result=$youtubeHelper->getChanelVideos($chanel->playlist_id);

        $videoItems=$result->items;
        $videos=array();
        foreach ($videoItems as $item) {
            $title=$item->snippet->title;
            $id=$item->snippet->resourceId->videoId;
            $videos[$id]=array('id'=>$id,'title'=>$title);
        }

        // check video is existing or not
        $video_ids=array_keys($videos);
        $exists=Video::getInstance()->getObjectsByFields(array('id'=>$video_ids));
        if(count($exists)==count($video_ids)){
            // all got videos are existing
            return;
        } else {
            // get existed video
            $exist_ids=array();
            foreach ($exists as $item) {
                $exist_ids[]=$item->id;
            }
        }
        // insert new video
        $new_ids=array_diff($video_ids,$exist_ids);
        $new_videos=array();
        foreach ($new_ids as $id) {
            $video=$videos[$id];
            $new_videos[]=array(
                'id'=>$id,
                'title'=>$video['title'],
                'description'=>$video['title'],
                'type'=>Constants::VIDEO_YOUTUBE,
                'created_at'=>array('now()'),
                'chanel'=>$chanel->dailychanel
            );
        }

        // insert
        Video::getInstance()->inserts(array('id','title','description','type','created_at'),$new_videos);


    }

    public function makeDownloadVideoCron() {
        $videoDao=new Video();
        $waitings=$videoDao->getObjectsByField(array('status'=>'waiting','current_step'=>'added'));

        foreach ($waitings as $video) {
            BackgroundProcess::getInstance()->throwProcess('/crons/manual/video/download',array('video_id'=>$video->id));
        }
    }

    public function makeUploadVideoCron() {
        $videoDao=new Video();
        $waitings=$videoDao->getObjectsByField(array('status'=>'waiting','current_step'=>'downloaded'));

        foreach ($waitings as $video) {
            BackgroundProcess::getInstance()->throwProcess('/crons/manual/video/upload',array('video_id'=>$video->id));
        }
    }
}
