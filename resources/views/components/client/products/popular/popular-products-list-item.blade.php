<div class="rounded-lg shadow-md overflow-hidden product">
    <img src="{{ $product->image->url }}" alt="{{ $product->title }}" class="w-full h-48 object-cover">
    <div class="p-4">
        <h3 class="text-xl font-semibold mb-2">{{ $product->title }}</h3>
        <p class="text-gray-600">{{ $product->description ?? '' }}</p>
        <div class="flex justify-between items-center mt-4">
            <span class="text-gray-700 font-semibold">{{ $product->price }} грн</span>
            <button class="cursor-pointer bg-blue-500 hover:bg-blue-600 text-white font-semibold px-4 py-2 rounded focus:outline-none focus:ring-2 focus:ring-blue-400">Купити</button>
        </div>
    </div>
</div>