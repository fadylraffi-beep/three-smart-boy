@extends('layouts.master')

@section('content')
    <form method="POST" action="{{ route('edit.form', $blog->id) }}">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control" id="title" name="title" value="{{ $blog->title }}">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <input type="text" class="form-control" id="description" name="description" value="{{ $blog->description }}">
        </div>
        <button type="submit" class="btn btn-outline-primary">Submit</button>
    </form>
@endsection


