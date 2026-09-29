<x-nav-link :href="route('rider.dashboard')" :active="request()->routeIs('rider.dashboard')" icon="dashboard">Dashboard</x-nav-link>
@can('view deliveries')
	<x-nav-link :href="route('rider.deliveries.index')" :active="request()->routeIs('rider.deliveries.*')" icon="truck">My deliveries</x-nav-link>
@endcan
