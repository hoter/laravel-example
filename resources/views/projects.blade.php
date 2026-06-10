@extends('layouts.app')

@section('title', 'Projects')
 
@section('content')
    <ul>
    @forelse ($projects as $project)
        <li>{{ $loop->iteration }}: {{ $project['title'] }}</li>
    @empty
        <li>No projects</li>
    @endforelse
    </ul>
@endsection