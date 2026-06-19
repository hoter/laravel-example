@extends('layouts.app')

@section('title', 'Post')

@section('content')
    <p>Author: <a href="{{ url('/user/' . $post['author']['id']) }}">{{ $post['author']['name'] }}</a></p>
    <p>Category: {{ $post['category']['name'] ?? 'Test' }}</p>
    <p>Tags:</p>
    <ul>
    @foreach($post['tags'] as $tag)
      <li>{{ $tag['name'] }}</li>
    @endforeach
    </ul>
    <p>Comments:</p>
    <ul>
    @foreach($post['comments'] as $comment)
      <li>{{ $comment['author']['name'] }}: {{ $comment['content'] }}</li>
    @endforeach
    </ul>
    <p>Likes: {{ count($post['likes']) }}</p>
@endsection
