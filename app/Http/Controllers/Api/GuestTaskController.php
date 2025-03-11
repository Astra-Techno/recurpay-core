<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GuestTaskController extends Controller
{
	public function action(Request $request, $name, $method = 'default')
	{
		$data = \Task($name.':Guest'.$method, $request);
		return response($data);
	}
}
