@extends('layouts.client.app')

@section('content')
    <div class="mt-6">
        <div>
            <h1 class="text-2xl font-bold">
                {{ $brand->title }}
            </h1>
        </div>
        <div>
            @include('components.client.products.product-list', compact('products'))
        </div>
    </div>
@endsection