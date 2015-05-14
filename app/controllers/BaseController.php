<?php

class BaseController extends Controller {

	public function __construct() {

		//parent::__construct();

		$input = Input::json()->all();
		if ( count($input) == 0 ) {
			$input = Input::all();
		}

		InputHelper::setInputArray( $input );

        Log::info("Request Input:".json_encode(InputHelper::getAllInput()));
	}

}
