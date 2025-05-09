<?php

namespace App\DataForge\Sql\System;

use DataForge\Sql;

class MediaType extends Sql
{
    public function default(&$data)
    {
        $query = Query('MediaType');
        $query->select('list', "mt.*");
        $query->select('item', "mt.*");
        $query->select('total', 'COUNT(mt.id) AS total');
        $query->from('FROM media_type AS mt');
        $query->filterAnyOneRequired(
            'IdOrName&SectionMust',
            [
                'mt.id={id}',
                'mt.name={name} AND mt.section={section}'
            ]
        );
        $query->group('mt.id');
        return $query;
    }
    public function getMediaTable($type)
    {
        $select_type = ", 'A' AS `type`";
        $table = 'media';
        if (!empty($type) && $type == 'T') {
            $select_type = ", 'T' AS `type`";
            $table = 'media_temp';
        }
        return ['table' => $table, 'select' => $select_type];
    }
    public function getMedia(&$data)
    {
        $query = Query('Media');
        $tmp = @self::getMediaTable($data['type']);
        $query->select('list', 'm.*' . $tmp['select']);
        $query->select('item', 'm.*' . $tmp['select']);
        $query->select('media', 'm.id AS media_id, m.media_type_id, m.name, m.path, (m.size * 1024) AS size, m.user_id, m.user_name AS author, m.created_at AS created');
        $query->select('total', 'COUNT(m.id) AS total');
        $query->from('FROM ' . $tmp['table'] . ' AS m');

        $query->filter('status=1');
        $query->filterAnyOneRequired(
            'IdOrTokenIdOrMediaType&RecordIdMust',
            [
                'm.id={id}',
                'm.token_id={token_id}',
                'm.media_type_id={media_type_id} AND m.record_id={record_id}'
            ]
        );
        $query->group('m.id');
        return $query;
    }
}
