@extends('layouts.app')

@section('content')

  @foreach ($products as $product)
    <p>{{ $product->name }}: {{ $product->sku }}</p>
  @endforeach

@endsection
