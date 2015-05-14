<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 1/2/15
 * Time: 22:30
 */

class ChanelControllers extends BaseController {
    public function get($id) {
        try {
            $device_id=Device::getInstance()->authentication();
            if(!Chanel::getInstance()->isValid($id)) {
                throw new APIException("CHANEL ID INVALID",APIException::ERRORCODE_INVALID_INPUT);
            }
            $limit=InputHelper::getInput('limit',false,10);
            $since=InputHelper::getInput('since',false,time());
            $response=Chanel::getInstance()->get($id,$since,$limit);

            return ResponseBuilder::success($response);

        }catch (Exception $e) {
            return ResponseBuilder::error($e);
        }
    }
    public function getList() {
        try {
            $device_id=Device::getInstance()->authentication();
            $chanels=Chanel::getInstance()->getObjectsByField(array());

            $response=array();

            foreach ($chanels as $chanel) {
                $chanel_obj=Chanel::getInstance()->composeResponse($chanel,array());

                $response[]=$chanel_obj;
            }

            return ResponseBuilder::success(array('id'=>$device_id,'chanels'=>$response));

        } catch(Exception $e) {
            return ResponseBuilder::error($e);
        }
    }
} 