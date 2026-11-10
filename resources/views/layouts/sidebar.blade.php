<aside class="app-sidebar">
    <div class="pt-3">
        <div class="text-uppercase text-white-50 small px-3 mb-2">
            Navigation
        </div>

        <a href="{{ route('dashboard') }}"
           class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            Dashboard
        </a>

        <a href="{{ route('product.index') }}"
           class="sidebar-link {{ request()->routeIs('product.*') ? 'active' : '' }}">
            Product
        </a>

        <a href="{{ route('menu.index') }}"
           class="sidebar-link {{ request()->routeIs('menu.*') ? 'active' : '' }}">
            Menu Resto
        </a>

        <a href="{{ route('order.index') }}"
            class="sidebar-link {{ request()->routeIs('order.*') ? 'active' : '' }}">
            Order
        </a>

    </div>
</aside>
