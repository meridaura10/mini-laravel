@extends('layouts.app')

@section('content')
        @include('components.products.product-list', compact('products'))
@endsection