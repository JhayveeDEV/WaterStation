<x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="dashboard">Dashboard</x-nav-link>
<x-nav-link :href="route('manage.orders.board')" :active="request()->routeIs('manage.orders.board')" icon="board">Order board</x-nav-link>
<x-nav-link :href="route('manage.orders.history')" :active="request()->routeIs('manage.orders.history')" icon="history">Order history</x-nav-link>
<x-nav-link :href="route('manage.inventory.index')" :active="request()->routeIs('manage.inventory.*')" icon="inventory">Inventory</x-nav-link>

<p class="nav-section">Admin</p>
<x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" icon="users">Users</x-nav-link>
<x-nav-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')" icon="roles">Roles &amp; permissions</x-nav-link>
<x-nav-link :href="route('admin.refunds.index')" :active="request()->routeIs('admin.refunds.*')" icon="refunds">Refunds</x-nav-link>
<x-nav-link :href="route('admin.areas.index')" :active="request()->routeIs('admin.areas.*')" icon="pin">Delivery areas</x-nav-link>
<x-nav-link :href="route('admin.reports.sales')" :active="request()->routeIs('admin.reports.*')" icon="reports">Reports</x-nav-link>
<x-nav-link :href="route('admin.activity.index')" :active="request()->routeIs('admin.activity.*')" icon="activity">Activity log</x-nav-link>
