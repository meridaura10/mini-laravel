<header class="h-14 shadow-xl border-b border-red-300 bg-blue-400">
    <div class="my-container h-full">
        <div class="flex justify-between items-center font-bold h-full">
            <div class="text-3xl">
                <a href="">
                    Toy store
                </a>
            </div>
            <div class="w-1/2">
                <input class="w-full border border-red-300 rounded-2xl outline-0 focus:border-red-500   px-4 py-1.5"
                       placeholder="search..." type="text">
            </div>
            <div>
                <a href="{{route('admin.home')}}">admin panel</a>
            </div>
            <div>
                @guest
                    <a href="{{ route('login') }}">
                        <button>
                            login
                        </button>
                    </a>
                @else
                    <div class="flex items-center gap-2">
                        <div>
                            {{ auth()->user()->email }}
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            <button class="bg-red-400 py-1 px-2 rounded-md hover:bg-red-500 hover:text-white transition-colors"
                                    type="submit">
                                logout
                            </button>
                        </form>
                    </div>
                @endguest
            </div>
        </div>
    </div>
</header>