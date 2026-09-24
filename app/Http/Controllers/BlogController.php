<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        return view("create");
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:30',
            'description' => 'required|max:255',
        ]);

        Blog::create($validated);

        return redirect()->route('home');
    }
}
