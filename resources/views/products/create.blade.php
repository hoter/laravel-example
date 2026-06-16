@extends('layouts.app')

@section('content')
<form method="POST" action="/products">
    @csrf
    <div><input type="text" name="name" /></div>
    <div><input type="text" name="sku" /></div>
    <div><input type="text" name="description" /></div>
    <div><input type="text" name="price" /></div>
    <div><input type="text" name="old_price" /></div>
    <div><input type="text" name="stock" /></div>
    <input type="submit" />
</form>
@endsection
