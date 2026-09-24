@extends('layouts.master')

@section('content')
    <h1>Hello Guys</h1>
    <p>Welcome to the blog!</p>

    <div class="d-flex flex-row-reverse">
        <a href="{{ route('show.create') }}" class="btn btn-outline-primary mb-2">Add</a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-stretch">
        @foreach ($blogs as $blog)
            <div class="card m-4" style="width: 18rem;">
                <div class="card-body d-flex flex-column h-100">
                    <h5 class="card-title">{{ $blog->title }}</h5>
                    <p class="card-text">{{ $blog->description }}</p>
                    <div class="row align-items-end mt-auto">
                        <a href="{{ route('show.edit', $blog->id) }}" class="col card-link btn btn-outline-warning">Edit</a>
                        <form action="{{ route('delete.form', $blog->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                            @csrf
                            @method('delete')
                            <button class="col card-link btn btn-outline-danger">Delete</button>

                        </form>
                        
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
