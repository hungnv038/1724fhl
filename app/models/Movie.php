<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 1/6/15
 * Time: 21:30
 */

class Movie extends ModelBase {

    private static $instance;

    public static function getInstance()
    {
        if(self::$instance==null) {
            self::$instance=new Movie();
        }
        return self::$instance;
    }
    public function __construct()
    {
        parent::__construct('movie'); // TODO: Change the autogenerated stub
    }

    protected function createCacheObject($id, $params)
    {
        $new_object=array(
            'id'        =>$this->getParamValue('id',$params,''),
            'created_at'=>$this->getParamValue('created_at',$params,time()),
            'updated_at'=>$this->getParamValue('updated_at',$params,time()),
            'title'=>$this->getParamValue('title',$params,''),
            'length'=>$this->getParamValue('length',$params,0),
            'chanel_id'=>$this->getParamValue('chanel_id',$params,-1),
            'number_view'=>0,
            'number_like'=>0
        );
        return (object)$new_object;
    }
    // update movie counter value
    public function updateCount($id,$action,$step) {
        $movie=$this->getOneObjectByField(array('id'=>$id));
        if($movie) {
            if($action==Constants::MOVIE_VIEW) {
                $movie->number_view+=$step;

                $this->addToCache($movie->id,$movie);
            } elseif($action==Constants::MOVIE_LIKE) {
                $movie->number_like+=$step;

                $this->addToCache($movie->id,$movie);
            }
        }
    }
    public function composeResponse($movie) {
        // input is object, not array
        $movie->created_at=intval($movie->created_at);
        $movie->updated_at=intval($movie->updated_at);
        $movie->chanel_id=intval($movie->chanel_id);
        unset($movie->match_url);
        return $movie;
    }
    public function getByChanelId($chanel_id,$since,$limit) {
        if($chanel_id==17) {
            $last_chanel=" and to_days(now())- to_days(created_at)<=2";
        } else {
            $last_chanel="";
        }

        $sql="select id,chanel_id,title,url,match_url,thumb,
              unix_timestamp(movie.created_at) as created_at,
              unix_timestamp(movie.updated_at) as updated_at from movie
              where chanel_id=? and unix_timestamp(movie.created_at) < ? {$last_chanel}
              order by movie.created_at DESC
              limit 0, ?";
        $result=DBConnection::read()->select($sql,array($chanel_id,$since,$limit));

        return $result;
    }

    public function updates($field_values)
    {
        if(array_key_exists('match_urls',$field_values)) {
            $match_urls=$field_values['match_urls'];
            if(!is_array($match_urls)) $match_urls=array($match_urls);
        } else {
            return -1;
        }
        if(array_key_exists('urls',$field_values)) {
            $urls=$field_values['urls'];
            if(!is_array($urls)) $urls=array($urls);
        } else {
            return -1;
        }

        $sql="UPDATE movie SET url= CASE ";
        $index=0;
        foreach ($match_urls as $id) {
            $sql.=" WHEN match_url='".$id."'  THEN '".$urls[$index]."' ";
            $index++;
        }
        $sql.=" END
                WHERE match_url IN ('".implode("','",$match_urls)."')";

        DBConnection::write()->update($sql);
    }


} 