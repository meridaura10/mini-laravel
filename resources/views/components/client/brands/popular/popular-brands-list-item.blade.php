<li class="brand">
    <a href="{{ route('brand.show', $brand->id) }}" class="brand-link">
        <div class="brand-image">
            <img src="{{ $brand->image->url }}" alt="{{ $brand->title }}" class="brand-img">
        </div>
        <div class="brand-details">
            <h4 class="brand-title">{{ $brand->title }}</h4>
        </div>
    </a>
</li>