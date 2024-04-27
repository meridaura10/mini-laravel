<div>
    <h2 class="text-3xl font-bold text-center mt-8 mb-6">Популярні продукти</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach ($products as $product)
           @include('components.client.products.popular.popular-products-list-item', compact('product'))
        @endforeach
    </div>
</div>
