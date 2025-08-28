<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShareController extends Controller
{
    public function uploadImage(Request $request)
    {
        $request->validate(['image' => 'required|string']);

        // Decode the base64 image data
        $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->image));
        $imageName = Str::random(10) . '.png';

        // Store the image in a public disk
        Storage::disk('public')->put('share/' . $imageName, $image);
        $url = Storage::disk('public')->url('share/' . $imageName);

        return response()->json(['url' => $url]);
    }
}
