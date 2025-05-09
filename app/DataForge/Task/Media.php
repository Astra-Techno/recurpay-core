<?php

namespace App\DataForge\Task;

use DataForge\Task;
use Illuminate\Http\UploadedFile;

class Media extends Task
{
    private function validation($request)
    {
        $validatedData = $request->validate([
            'mediaTypeId' => 'required',
            'tokenId' => 'required',
            'section' => 'required',
            'sectionType' => 'required',
        ]);
        $mediaType = \DataForge::getSystemMediaType($request->get('mediaTypeId'));
        if (!$mediaType)
            return $this->raiseError('Invlaid MediaType to upload!');
        if ($mediaType->section != $request->get('section') || $mediaType->name != $request->get('sectionType'))
            return $this->raiseError('MediaType conflict, upload failed!');
        return $mediaType;
    }
    public function upload($request)
    {
        $mediaType = $this->validation($request);
        return $mediaType->upload($request);
    }
    public function delete($request)
    {
        $mediaType = $this->validation($request);

        $validatedData = $request->validate([
            'media_id' => 'required',
            'type' => 'required'
        ]);
        $media = \DataForge::getSystemMedia(['id' => (int) $request->get('media_id'), 'type' => $request->get('type')]);
        if (!$media)
            return $this->raiseError('Invalid input to load Media!');    // echo DataForge::getError();
        // Do internal validation to avoid request violation.
        if (
            $request->get('mediaTypeId') != $media->media_type_id && $request->get('tokenId') != $media->token_id &&
            $request->get('name') != $media->name && $request->get('type') != $media->type
        ) {
            return $this->raiseError('Invalid access to media internal process!');
        }
        return $media->delete();
    }
}
