<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TaskController extends Controller
{
	public function action(Request $request, $name, $method = 'default')
	{
		$data = \Task($name.':'.$method, $request);
		\Table::dispatchBulkChanges();

		return response($data);
	}
}
