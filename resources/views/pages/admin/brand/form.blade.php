@extends('layouts.admin.app')

@section('content')
    @component('components.admin.ui.container-wrap')
        <form method="POST" action="{{$brand->id ? route('admin.brand.update', $brand->id) : route('admin.brand.store')}}">
            <div class="mb-6">
                <h2 class="font-bold text-2xl">
                    Форма створення бренду
                </h2>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                    Title
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       id="title" name="title" type="text" placeholder="Enter brand title"
                       value="{{ old('title', $brand->id ? $brand->title : '') }}">
                @error('title')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                        type="submit">
                    {{ $brand->id ? 'Update' : 'Create' }}
                </button>
            </div>
        </form>
    @endcomponent
@endsection
