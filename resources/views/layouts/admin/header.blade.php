<header class="bg-[#192130] h-[48px] text-white shadow-md  py-3 px-3">
    <div class="flex justify-between h-full items-center">
        <h3>
            <a href="{{ route('admin.home') }}">
                Адмін панель
            </a>
        </h3>
        <h3 >
            <a href="{{ route('home') }}">сайт</a>
        </h3>
        <h3>
            {{ auth()->user()->name }}
        </h3>
    </div>
</header>
