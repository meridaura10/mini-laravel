@extends('layouts.admin.app')

@section('content')
    @component('components.admin.ui.container-wrap')
        <div>
            <h2 class="text-2xl font-semibold mb-4">Brands</h2>
            <div class="overflow-x-auto">
                <table class="table-auto w-full border-collapse border border-gray-300">
                    <thead>
                    <tr>
                        <th class="border border-gray-300 px-4 py-2">ID</th>
                        <th class="border border-gray-300 px-4 py-2">Title</th>
                        <th class="border border-gray-300 px-4 py-2">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($brands as $brand)
                        <tr>
                            <td class="border border-gray-300 px-4 py-2">{{ $brand->id }}</td>
                            <td class="border border-gray-300 px-4 py-2">{{ $brand->title }}</td>
                            <td class="border border-gray-300 px-4 py-2">
                                <a href="{{ route('admin.brand.edit', $brand->id) }}" class="text-blue-500 hover:text-blue-700 mr-2">Edit</a>
                                <form  action="{{ route('admin.brand.destroy', $brand->id) }}" method="POST" class="inline">
                                    <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endcomponent
@endsection
