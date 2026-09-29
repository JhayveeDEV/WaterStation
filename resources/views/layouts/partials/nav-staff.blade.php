<x-nav-link :href="route('manage.dashboard')" :active="request()->routeIs('manage.dashboard')" icon="dashboard">Dashboard</x-nav-link>
<x-nav-link :href="route('manage.orders.board')" :active="request()->routeIs('manage.orders.board')" icon="board">Order board</x-nav-link>
<x-nav-link :href="route('manage.orders.history')" :active="request()->routeIs('manage.orders.history')" icon="history">Order history</x-nav-link>
<x-nav-link :href="route('manage.inventory.index')" :active="request()->routeIs('manage.inventory.*')" icon="inventory">Inventory</x-nav-link>
@can('view reports')
	<x-nav-link :href="route('manage.reports.sales')" :active="request()->routeIs('manage.reports.*')" icon="reports">Reports</x-nav-link>
@endcan
