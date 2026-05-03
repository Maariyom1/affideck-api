<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function sign(Request $request)
    {
        $data = $request->validate(['filename' => 'required|string', 'contentType' => 'required|string']);
        $signed = url('/uploads/mock/'.$data['filename']);
        return response()->json(['data' => ['signed_url' => $signed]]);
    }

    public function store(Request $request)
    {
        $file = $request->file('file');
        if (!$file) {
            return response()->json(['message' => 'file required'], 422);
        }
        $path = $file->store('uploads');
        $upload = Upload::create(['user_id' => $request->user()->id, 'filename' => $file->getClientOriginalName(), 'path' => $path, 'mime' => $file->getClientMimeType(), 'size' => $file->getSize()]);
        return response()->json(['data' => $upload], 201);
    }

    public function destroy(Request $request, $id)
    {
        $upload = Upload::where('user_id', $request->user()->id)->findOrFail($id);
        if ($upload->path) { Storage::delete($upload->path); }
        $upload->delete();
        return response()->json(null, 204);
    }
}
