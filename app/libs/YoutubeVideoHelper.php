<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 3/11/15
 * Time: 17:17
 */

class YoutubeVideoHelper {
    public function getPlaylistId($chanel_name) {
        $client=new Google_Client();
        $client->setDeveloperKey(Constants::DEVELOPER_KEY);
        $youtube=new Google_Service_YouTube($client);
        $result=$youtube->channels->listChannels('contentDetails',array('forUsername'=>$chanel_name));
        if($result) {
            return $result->getItems()[0]->contentDetails->relatedPlaylists->uploads;
        } else {
            return "";
        }
    }
    public function getChanelVideos($playlist_id) {
        $client=new Google_Client();
        $client->setDeveloperKey(Constants::DEVELOPER_KEY);
        $youtube=new Google_Service_YouTube($client);
        $result=$youtube->playlistItems->listPlaylistItems("snippet",array('playlistId'=>$playlist_id,'maxResults'=>10));

        return $result;
    }
}