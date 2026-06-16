@extends('layouts.app')

@section('content')

  @foreach ($products as $product)
    <p>{{ $product->name }}: {{ $product->sku }}, price: {{ $product->price }}</p>
  @endforeach

  <p>Average price: {{ $average }}</p>

@endsection
