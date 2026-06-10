@extends('layouts.app')

@section('title', 'Contact')

@section('content')
    <form method="POST" action="/contacts">
        @csrf
    </form>
@endsection