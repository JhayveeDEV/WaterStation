@can('view shop')
	<x-nav-link :href="route('customer.shop')" :active="request()->routeIs('customer.shop')" icon="shop">Shop</x-nav-link>
@endcan
@can('place orders')
	<x-nav-link :href="route('customer.cart')" :active="request()->routeIs('customer.cart')" icon="cart">Cart</x-nav-link>
@endcan
@can('manage own orders')
	<x-nav-link :href="route('customer.orders.index')" :active="request()->routeIs('customer.orders.*')" icon="orders">My orders</x-nav-link>
	<x-nav-link :href="route('customer.recurring.index')" :active="request()->routeIs('customer.recurring.*')" icon="history">Repeat orders</x-nav-link>
	<x-nav-link :href="route('customer.addresses.index')" :active="request()->routeIs('customer.addresses.*')" icon="pin">Addresses</x-nav-link>
@endcan
