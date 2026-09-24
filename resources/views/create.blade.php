@extends('layouts.master')

@section('content')
    <form method="POST" action="{{route("submit.form")}}">
        @csrf
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control" id="title" name="title">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <input type="text" class="form-control" id="description" name="description">
        </div>
        <button type="submit" class="btn btn-outline-primary">Submit</button>
    </form>
@endsection
