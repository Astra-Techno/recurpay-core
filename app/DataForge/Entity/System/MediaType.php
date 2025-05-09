<?php

namespace App\DataForge\Entity\System;

use Illuminate\Support\Facades\Auth;
use DataForge\Entity;

class MediaType extends Entity
{
    function init($params = null)
    {
        if (empty($params))
            $params = ['name' => request('media_type'), 'section' => request('media_section')];
        else if (!is_array($params))
            $params = ['id' => $params];
        $data = \Sql('System\MediaType', $params)->assoc();
        if (!empty($params['record_id']))
            $data['record_id'] = $params['record_id'];
        return $data;
    }
    function getFormats()
    {
        return str_replace(" ", '', $this->allowed_formats);
    }
    function getKB()
    {
        return $this->max_upload_size * 1024;
    }
    function getTempFolderPath()
    {
        return  str_replace(" ", "", "uploads/" . now()->utc()->format('Y-m-d') . "/" . $this->path);
    }
    function getFiles()
    {
        if (empty($this->record_id))
            $this->record_id = (int) request('record_id');
        $files = \Sql('System\MediaType:getMedia', ['media_type_id' => $this->id, 'record_id' => $this->record_id, 'select_type' => 'media'])->assocList();
        if (!$files && $this->syncEntityMedia())
            $files = \Sql('System\MediaType:getMedia', ['media_type_id' => $this->id, 'record_id' => $this->record_id, 'select_type' => 'media'])->assocList();
        return $files;
    }
    function cleanFileName($filename)
    {
        // Remove any null bytes
        $filename = str_replace("\0", '', $filename);
        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);
        // Remove any characters that are not alphanumeric, underscores, hyphens, or dots
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $filename);
        // Ensure that there is only one dot (.) before the file extension
        $filenameParts = explode('.', $filename);
        $extension = array_pop($filenameParts); // Get the file extension
        $filename = implode('_', $filenameParts); // Rejoin the name part with underscores
        // Return the cleaned file name with the extension
        return $filename . '.' . $extension;
    }
    function upload($request)
    {
        $customMessages = [
            'file.required' => 'Please upload a valid file.',
            'file.file' => 'The uploaded file must be a valid file.',
            'file.mimes' => 'Only ' . $this->formats . ' files are allowed.',
            'file.max' => 'The file size should not exceed ' . $this->max_upload_size . 'MB.',
        ];

        $user = Auth::user();

        $validatedData = $request->validate([
            'file' => 'required|file|mimes:' . $this->formats . '|max:' . $this->KB,
        ], $customMessages);
        $file = $request->file('file');
        // Generate a unique file name or use the original one
        $fileName = uniqid() . '_' . $this->cleanFileName($file->getClientOriginalName());
        $fileSize = $file->getSize() / 1024;
        // Move the file to the custom directory
        $file->move(storage_path("app/public/" . $this->tempFolderPath), $fileName);
        // The path to save in the database or further processing
        $filePath = $this->tempFolderPath . '/' . $fileName;
        $input = [
            'media_type_id' => $this->id,
            'token_id' => $request->get('tokenId'),
            'record_id' => $request->get('recordId'),
            'path' => $filePath,
            'name' => $file->getClientOriginalName(),
            'size' => $fileSize,
            'format' => $file->getClientOriginalExtension(),
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => 1
        ];
        $media = $this->TableSave($input, 'media', 'media_type_id&token_id&name');
        return ['media_id' => $media['id'], 'path' => $media['path'], 'type' => 'T'];
    }
    protected function copyMedia($tempTokenId, $record_id)
    {
        $medias = \Sql('System\MediaType:getMedia', ['type' => 'T', 'token_id' => $tempTokenId])->assocList();
        foreach ($medias as $media) {
            $temp_id = $media['id'];
            $media['id'] = null;
            if (empty($media['record_id']))
                $media['record_id'] = $record_id;
            // Save record into media table.
            if (!$this->TableSave($media, 'media', 'id')) {
                $this->setError('Failed to save media!');
                return false;
            }
            // Soft delete copied media temp record.
            $this->TableSave(['id' =>  $temp_id, 'status' => 0], 'media_temp', 'id');
        }
        return true;
    }
    protected function syncEntityMedia()
    {
        $flag = false;
        if (!$this->linked_entity || !$this->linked_field)
            return $flag;
        $params = ['id' => $this->record_id, 'select_type' => 'media'];
        $entity    = call_user_func_array([\DataForge, 'get' . $this->linked_entity], [$params]);
        if (!$entity)
            return $flag;
        $medias = $entity->{$this->linked_field};
        if (empty($medias))
            return $flag;
        foreach ($medias as $media) {
            $valid = $this->validate(['name' => 'required', 'path' => 'required'], $media);

            if (isset($media['id']))
                unset($media['id']);

            $media['status']         =    1;
            $media['record_id']        =    $this->record_id;
            $media['media_type_id']    =    $this->id;

            // Save record into media table.
            if (!$this->TableSave($media, 'media', 'id')) {
                $this->setError('Failed to save media!');
                return $flag;
            }
            $flag = true;
        }
        return $flag;
    }
}
