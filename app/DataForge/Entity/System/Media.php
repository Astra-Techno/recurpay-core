<?php

namespace App\DataForge\Entity\System;

use DataForge\Entity;

class Media extends Entity
{
    function init($params = null)
    {
        if (!is_array($params))
            $params = ['id' => $params, 'type' => 'A'];
        return \Sql('System\MediaType:getMedia', $params)->assoc();
    }
    function delete()
    {
        // Do access validation here.
        // Delete from media tables.
        $tmp = ['id' => $this->id, 'status' => 0, 'deleted_by' => user()->id, 'deleted_at' => DataForge::Date()];
        $table = 'media';
        if ($this->type == 'T')
            $table = 'media_temp';
        if (\Table::save($tmp, $table, 'id'))
            return true;
        return false;
    }
}
