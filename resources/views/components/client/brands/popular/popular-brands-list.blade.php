<div class="brands-container">
    <ul class="brands-list">
        @foreach($brands as $brand)
           @include('components.client.brands.popular.popular-brands-list-item', compact('brand'))
        @endforeach
    </ul>
</div>
