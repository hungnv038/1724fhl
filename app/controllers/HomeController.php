<?php

class HomeController extends BaseController {

    public function getAddNewVideoView() {
        return View::make('home.add_video');
    }
    /// home tool
    public function postNewVideo() {
        try {
            $type=InputHelper::getInput('type',true);
            if($type<=2) {
                $video_link=InputHelper::getInput('video_id',true);
                $title=InputHelper::getInput('title',true);
                $description=InputHelper::getInput('description',false,'');
                $chanel_id=InputHelper::getInput('chanel_id',true);
                $video_id=strval(time());

                Video::getInstance()->insert(
                    array(
                        'id'=>$video_id,
                        'title'=>$title,
                        'description'=>$description,
                        'type'=>Constants::VIDEO_LINK,
                        'chanel'=>$chanel_id,
                        'link'=>$video_link,
                        'created_at'=>array('now()')));

            } else {
                $chanel_name=InputHelper::getInput('chanel_name',true);
                $daily_chanel=InputHelper::getInput('chanel_id',true);

                $youtubeHelper=new YoutubeVideoHelper();
                $playlist_id=$youtubeHelper->getPlaylistId($chanel_name);
                YoutubeChanel::getInstance()->insert(array(
                    'chanel_name'=>$chanel_name,
                    'playlist_id'=>$playlist_id,
                    'dailychanel'=>$daily_chanel,
                    'created_at'=>array('now()')
                ));
            }
            return ResponseBuilder::success(array('html'=>'Success','error'=>0));
        } catch(Exception $e) {
            return ResponseBuilder::success(array('html'=>$e->getMessage(),'error'=>1));
        }
    }
    public function upload() {
        try {
            $video_id=InputHelper::getInput("video_id",true);

            $video=Video::getInstance()->getOneObjectByField(array('id'=>$video_id));
            if($video==null) {
                return;
            }
            if($video->status!='waiting' || $video->current_step!='downloaded') {
                return;
            }
            if($video->file_name==null || $video->file_name="") {
                Video::getInstance()->update(array('current_step'=>'added'),array('id'=>$video_id));
                return;
            }

            Video::getInstance()->update(array('status'=>'processing'),array('id'=>$video_id));

            try {
                $video_helper=new VideoHelper();
                $video_helper->upload($video->file_name,$video->title,$video->chanel);
            } catch(Exception $e) {
                Video::getInstance()->update(array('status'=>'waiting'),array('id'=>$video_id));
                throw $e;
            }

            Video::getInstance()->update(array('status'=>'waiting','current_step'=>'uploaded'),array('id'=>$video_id));

        } catch(Exception $e) {
            return ResponseBuilder::error($e);
        }

    }
    public function download()
    {
        try {
            $video_id=InputHelper::getInput("video_id",true);
            $video=Video::getInstance()->getOneObjectByField(array('id'=>$video_id));
            if($video==null) {
                return;
            }
            if($video->status!='waiting' || $video->current_step!='added') {
                return;
            }

            Video::getInstance()->update(array('status'=>'processing'),array('id'=>$video_id));

            try {
                $video_helper = new VideoHelper();
                $filename = '';
                if ($video->type == Constants::VIDEO_YOUTUBE) {
                    $filename = $video_helper->downloadYoutubeVideo($video->id);
                } elseif ($video->type == Constants::VIDEO_LINK) {
                    $filename = $video_helper->downloadVideo($video->link);
                } else {
                    throw new APIException("video type not support in downloader");
                }
            } catch(Exception $e) {
                //rollback
                Video::getInstance()->update(array('status'=>'waiting'),array('id'=>$video_id));
                throw $e;
            }

            Video::getInstance()->update(array('status'=>'waiting','current_step'=>'downloaded','file_name'=>$filename),array('id'=>$video_id));
        } catch (Exception $e) {
            return ResponseBuilder::error($e);
        }

    }

}
