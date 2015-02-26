<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 1/6/15
 * Time: 18:01
 */

class Chanel extends ModelBase{
    private static $instance;

    public static function getInstance()
    {
        if(self::$instance==null) {
            self::$instance=new Chanel();
        }
        return self::$instance;
    }
    public function __construct()
    {
        parent::__construct('chanel'); // TODO: Change the autogenerated stub
    }
    public function isValid($id)
    {
        $sql="select count(*) as count from chanel where id=?";
        $result=DBConnection::read()->select($sql,array($id));

        if($result[0]->count>0) {
            return true;
        } else {
            return false;
        }
    }
    public function get($id,$since,$limit) {
        $chanel=$this->getOneObjectByField(array('id'=>$id));
        if(!$chanel) {
            return array();
        }
        $movies=Movie::getInstance()->getByChanelId($id,$since,$limit);
        return $this->composeResponse($chanel,$movies);
    }

    public function composeResponse($chanel,$movies) {
        // is_followed is bool type
        if(!is_array($movies)) {
            $movies=array($movies);
        }

        $chanel=(array)$chanel;

        $chanel['movies']=array();

        foreach ($movies as $movie) {
            $chanel['movies'][]=Movie::getInstance()->composeResponse($movie);
        }
        $chanel= (object)$chanel;

        $chanel->id=intval($chanel->id);
        $chanel->created_at=intval(strtotime($chanel->created_at));
        $chanel->updated_at=intval(strtotime($chanel->updated_at));
        $chanel->order=intval($chanel->order);

        return $chanel;
    }

    protected function createCacheObject($id, $params)
    {
        $new_object=array(
            'id'=>$id,
            'created_at'=>$this->getParamValue('created_at',$params,time()),
            'updated_at'=>$this->getParamValue('updated_at',$params,time()),
            'name'=>$this->getParamValue('name',$params,'')
        );
        return (object)$new_object;
    }

    public function insertMovies($chanel_id,$matchs)
    {
        $movie_ids=array_keys($matchs);

        $movies=Movie::getInstance()->getObjectsByFields(array('id'=>$movie_ids,'chanel_id'=>array($chanel_id)));

        $existed_ids=array();
        foreach ($movies as $movie) {
            $existed_ids[]=$movie->id;
        }

        $not_exist_ids=array_diff($movie_ids,$existed_ids);

        if(count($not_exist_ids)==0) {
            return;
        }
        $movie_inputs=array();

        foreach ($not_exist_ids as $movie_id) {
            $match=$matchs[$movie_id];

            $movie_inputs[]=array(
                'id'=>$match->id,
                'created_at'=>array('now()'),
                'title'=>$match->title,
                'url'=>$match->url,
                'chanel_id'=>$chanel_id,
                'match_url'=>$match->match_url,
                'thumb'=>$match->thumb
                );
        }
        Movie::getInstance()->inserts(array('id','created_at','title','url','chanel_id','match_url','thumb'),$movie_inputs);

        if(count($existed_ids)>0) {
            $urls=array();
            foreach($existed_ids as $id) {
                $match=$matchs[$id];

                $urls[]=$match->url;
            }
            Movie::getInstance()->updates(array('ids'=>$existed_ids,'urls'=>$urls));
        }
    }


} 