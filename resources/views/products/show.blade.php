@extends('layouts.app')

@section('content')
  {{ $product->name }} views: {{ $product->views }}
@endsection
