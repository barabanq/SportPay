import './bootstrap';
import { createApp } from 'vue';

import NavbarApp from './components/NavbarApp.vue';
import MarketplacePage from './components/MarketplacePage.vue';
import LotShowPage from './components/LotShowPage.vue';
import OrderShowPage from './components/OrderShowPage.vue';
import GlobalChatPage from './components/GlobalChatPage.vue';
import WalletPage from './components/WalletPage.vue';
import ArbitrationPage from './components/ArbitrationPage.vue';

// Монтируем шапку отдельно, чтобы не затрагивать Blade-скрипты внутри @yield('content')
const navEl = document.getElementById('sportpay-navbar-mount');
if (navEl) {
    const navApp = createApp({});
    navApp.component('navbar-app', NavbarApp);
    navApp.mount('#sportpay-navbar-mount');
}

// Монтируем страничные Vue-компоненты, если на странице есть #sportpay-page-mount
const pageEl = document.getElementById('sportpay-page-mount');
if (pageEl) {
    const pageApp = createApp({});
    pageApp.component('marketplace-page', MarketplacePage);
    pageApp.component('lot-show-page', LotShowPage);
    pageApp.component('order-show-page', OrderShowPage);
    pageApp.component('global-chat-page', GlobalChatPage);
    pageApp.component('wallet-page', WalletPage);
    pageApp.component('arbitration-page', ArbitrationPage);
    pageApp.mount('#sportpay-page-mount');
}
