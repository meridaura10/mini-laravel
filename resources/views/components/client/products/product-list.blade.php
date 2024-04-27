<div>
    @foreach($products as $product)
        @include('components.client.products.product-list-item', compact('product'))
    @endforeach

    <div>
        {{ $products->links() }}
    </div>
</div>