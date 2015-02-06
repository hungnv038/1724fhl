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
        $movie->created_at=intval(strtotime($movie->created_at));
        $movie->updated_at=intval(strtotime($movie->updated_at));
        $movie->chanel_id=intval($movie->chanel_id);
        unset($movie->match_url);
        return $movie;
    }
    public function getByChanelId($chanel_id,$since,$limit) {
        $sql="select * from movie
              where chanel_id=? and unix_timestamp(movie.created_at) < ?
              order by movie.created_at DESC
              limit 0, ?";
        $result=DBConnection::read()->select($sql,array($chanel_id,$since,$limit));

        return $result;
    }

} 