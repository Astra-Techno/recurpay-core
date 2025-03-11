<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EntityController extends Controller
{
	public function fetch(Request $request, $name, $key = '')
	{
        $entity = call_user_func_array([\Factory(), 'get'.$name], ($key ? ['id' => $key] : []));
        if (!$entity) {
        	return response([
				'message' => \Factory()::getError()
			], 403);
        }

        $base = (int) request('base');
        $attribGroup = request('group');
        $attribs = request('attribs');

        if ($attribGroup)
            return response($entity->toGroupArray($attribGroup, $attribs, $base));

		return response($entity->toArray($attribs, $base));
	}
}
