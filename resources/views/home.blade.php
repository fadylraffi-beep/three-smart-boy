@extends('layouts.master')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h2 class="mb-1 fw-bold">Blog Dashboard</h2>
            <p class="text-muted mb-0">Manage and publish your latest updates.</p>
        </div>
        <a href="{{ route('show.create') }}" class="btn btn-primary shadow-sm px-4">
            + Add New Post
        </a>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        
        @forelse ($blogs as $blog)
            <div class="col">
                <div class="card h-100 shadow-sm border-0 bg-white">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold text-dark">{{ $blog->title }}</h5>

                        <p class="card-text text-muted mb-4">
                            {{ Str::limit($blog->description, 100) }}
                        </p>
                        
                        <div class="d-flex gap-2 mt-auto border-top pt-3">
                            <a href="{{ route('show.edit', $blog->id) }}" class="btn btn-info w-50">
                                Edit
                            </a>
                            
                            <form action="{{ route('delete.form', $blog->id) }}" method="POST" class="w-50 m-0" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn btn-danger w-100">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
        @empty
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-light rounded-3 border">
                    <h5 class="text-muted">No posts available</h5>
                    <p class="text-muted mb-3">You haven't created any blog posts yet.</p>
                    <a href="{{ route('show.create') }}" class="btn btn-outline-primary">Write your first post</a>
                </div>
            </div>
        @endforelse

    </div>
@endsection