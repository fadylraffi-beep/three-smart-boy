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

    public function show_edit($id)
    {
        $blog = Blog::find($id);

        return view('edit', compact('blog'));
    }

    public function edit_form(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required|max:30',
            'description' => 'required|max:255',
        ]); 
        
        $blog = Blog::find($id);

        $blog->update($validated);

        return redirect()->route('home');
    } 

    public function delete_form($id)
    {
        $blog = Blog::find($id);

        $blog->delete();

        return redirect()->route('home');
    }

}
