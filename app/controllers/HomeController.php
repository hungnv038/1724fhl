<?php

class HomeController extends BaseController {

    public function getAddNewVideoView() {
        return View::make('home.add_video');
    }
    /// home tool
    public function postNewVideo() {
        try {
            $type=InputHelper::getInput('type',true);
            $video_id=InputHelper::getInput('video_id',true);
            $title=InputHelper::getInput('title',true);
            $description=InputHelper::getInput('description',false,'');
            $chanel_id=InputHelper::getInput('chanel_id',true);

            BackgroundProcess::throwProcess(
                '/crons/manual/video/download',
                array('type'=>$type,
                    'video_id'=>$video_id,
                    'title'=>$title,
                    'description'=>$description,
                    'chanel_id'=>$chanel_id));
            return ResponseBuilder::success(array('html'=>'Success','error'=>0));
        } catch(Exception $e) {
            return ResponseBuilder::success(array('html'=>$e->getMessage(),'error'=>1));
        }
    }
    public function upload() {
        try {
            $file_name=InputHelper::getInput('file_name',true);
            $title=InputHelper::getInput('title',true);
            $chanel_id=InputHelper::getInput('chanel_id',true);

            $video_helper=new VideoHelper();
            $video_helper->upload($file_name,$title,$chanel_id);
        } catch(Exception $e) {
            return ResponseBuilder::error($e);
        }

    }
    public function download()
    {
        try {
            $type = InputHelper::getInput('type', true);
            $video_id = InputHelper::getInput('video_id', true);
            $title = InputHelper::getInput('title', true);
            $description = InputHelper::getInput('description', false, '');
            $chanel_id = InputHelper::getInput('chanel_id', true);

            $video_helper = new VideoHelper();
            $filename = '';
            if ($type == Constants::VIDEO_YOUTUBE) {
                $filename = $video_helper->downloadYoutubeVideo($video_id);
            } elseif ($type == Constants::VIDEO_LINK) {
                $filename = $video_helper->downloadVideo($video_id);
            } else {
                throw new APIException("video type not support in downloader");
            }

            // create cron tab to upload video to youtube
            BackgroundProcess::getInstance()->throwProcess(
                '/crons/manual/video/upload',
                array('file_name' => $filename,
                    'title' => $title,
                    'description' => $description,
                    'chanel_id' => $chanel_id));
        } catch (Exception $e) {
            return ResponseBuilder::error($e);
        }

    }

}
