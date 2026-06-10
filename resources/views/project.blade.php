@extends('layouts.app')

@section('title', 'Project')
 
@section('content')
    Title: {{ $project['title'] }}
    description: {{ $project['description'] }}
    tech: @php echo implode(', ', $project['tech']); @endphp
    github: {{ $project['github'] }}
@endsection