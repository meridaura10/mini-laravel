@extends('layouts.client.app')

@section('content')
    <div class="my-6">
        @include('components.client.brands.popular.popular-brands-list', compact('brands'))
        @include('components.client.products.popular.popular-products-list', compact('brands'))
    </div>
@endsection