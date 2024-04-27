@extends('layouts.auth')

@section('content')
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div>
            <a href="{{route('home')}}">
                @include('components.assets.logo')
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <form method="POST" action="{{ route('login') }}">
                <div>
                    @if($errors->any())
                        <div class="bg-red-100 mb-2 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                             role="alert">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <div>
                    <label for="email" class="block font-medium text-sm text-gray-700">Email</label>
                    <input type="email" name="email" id="email"
                           class="bg-gray-100 border border-gray-300 outline-0 py-1 px-2 focus:border-gray-500  mt-1 block w-full rounded-md  shadow-sm"/>
                </div>

                <div class="mt-4">
                    <label for="password" class="block font-medium text-sm text-gray-700">Password</label>
                    <input type="password" name="password" id="password"
                           class="bg-gray-100 border border-gray-300 outline-0 py-1 px-2 focus:border-gray-500  mt-1 block w-full rounded-md  shadow-sm"/>
                </div>

                <div class="flex items-center justify-between mt-4">
                    <label for="remember_me" class="flex items-center">
                        <input id="remember_me" type="checkbox"
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                               name="remember" value="1">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>

                    <div class="text-sm">
                        <a href="" class="font-medium text-indigo-600 hover:text-indigo-500">Forgot your password?</a>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Log in
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
