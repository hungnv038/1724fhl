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

        foreach($result as $item) {
            $thumb=$item->children[0]->children[0]->children[0];
            $thumb=$thumb->attr["src"];

            $link=$item->children[0]->children[1]->children[0];

            $link=$link->attr["href"];

            $match_title=$item->children[0]->children[1]->children[0]->nodes[0]->text();

            BackgroundProcess::getInstance()->throwProcess("crons/chanels/movie",
                array('chanel_id'=>$chanel_id,'link'=>$link,'title'=>$match_title,'thumb'=>$thumb));
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

        Chanel::getInstance()->insertMovies($chanel_id,array($match->match_url=>$match));
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
    
     /**
     * Create all process
     **/
    public function cronCreateProcess() {
        $command = 'users/me/friend-crawl';
        try {
            $startDate = date("Y-m-d H:i:s");
            // Get all users and service
            $serverAddr = Input::server("SERVER_ADDR");
            $users = User::getAllUserServiceApp();
            $arrValues = array(); 
            if(isset($users)) {
                foreach($users as $user) {
                    // Parser Parameters
                    $parameter['access_token'] = $user->access_token;
                    $parameter['serviceapp'] = $user->service;
                    //Build url
                    $param_query = http_build_query($parameter);
                    $url = $command . "?" . $param_query;
                    $arrValues[] = "('waiting','{$serverAddr}','{$url}','slow',adddate(now(), interval 30 minute),now())";
                }
                if(count($arrValues)) {
                    $strValues = implode(',', $arrValues);
                    // Insert data to process table
                    DBConnection::write()->insert("INSERT INTO process (status, ip, process, priority, scheduled_at, created_at) VALUES {$strValues}");
                }
            }
            //Log::info("Create All Process", array('Start at:' => $startDate, 'Finish at: ' => date("Y-m-d H:i:s"), 'count: ' => count($arrValues)));
        } catch (Exception $e ) {
            return ResponseBuilder::error($e);
        }
        return;
    }
}
