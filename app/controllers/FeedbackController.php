<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 3/2/15
 * Time: 14:21
 */

class FeedbackController extends BaseController {
    public function feedback() {
        try {
            $device_id=Device::getInstance()->authentication();

            $feedback=InputHelper::getInput('content',true);

            if(isset($feedback) && !empty($feedback)) {
                Feedback::getInstance()->insert(
                    array(
                        'created_at'=>array('now()'),
                        'content'=>$feedback
                    ));
            }
        } catch(Exception $e) {
            return ResponseBuilder::error($e);
        }
    }
}