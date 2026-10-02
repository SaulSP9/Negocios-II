<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HFSTUDIOS - Acceso Exclusivo</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">



    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Helvetica Neue', sans-serif; }
        [x-cloak] { display: none !important; }
        html { scroll-behavior: smooth; }
        @keyframes marquee { 0% { transform: translateX(0%); } 100% { transform: translateX(-50%); } }
        .marquee-content { animation: marquee 20s linear infinite; }
        .product-card:hover .hover-overlay { opacity: 1; transform: translateY(0); }
        .hover-overlay { opacity: 0; transform: translateY(20px); transition: all 0.3s ease; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { animation: spin 1s linear infinite; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #888; border-radius: 3px; }
        .grayscale-map { filter: grayscale(100%); }
    </style>
</head>
<body class="bg-white antialiased" x-data="app">

    <div x-show="toast.show" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-x-full" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-full" class="fixed top-6 right-6 z-[90] bg-black text-white px-6 py-4 shadow-2xl max-w-sm" x-cloak>
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <p class="font-semibold text-sm" x-text="toast.message"></p>
        </div>
    </div>

    <div x-show="productDetailOpen" x-cloak class="fixed inset-0 z-[85] overflow-y-auto">
        <div @click="productDetailOpen = false" x-show="productDetailOpen" x-transition.opacity class="fixed inset-0 bg-black/80"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="productDetailOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-6xl w-full grid md:grid-cols-2 gap-0 shadow-2xl">
                <button @click="productDetailOpen = false" class="absolute top-4 right-4 z-10 w-10 h-10 flex items-center justify-center bg-white/90 hover:bg-white rounded-full text-black font-bold text-2xl shadow-lg">×</button>
                <div class="bg-[#F4F4F4] flex items-center justify-center p-8 min-h-[500px]">
                    <img :src="selectedProduct.image" :alt="selectedProduct.name" class="w-full h-auto object-cover max-h-[600px]">
                </div>
                <div class="p-8 md:p-12 flex flex-col justify-center">
                    <span x-show="selectedProduct.badge" :class="selectedProduct.badge && selectedProduct.badge.includes('PROMO') ? 'bg-black text-white' : 'bg-white text-black'" class="inline-block text-[9px] font-bold uppercase tracking-wider px-2 py-1 mb-4 w-fit"><span x-text="selectedProduct.badge"></span></span>
                    <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tight mb-3" x-text="selectedProduct.name"></h2>
                    <p class="text-sm text-gray-500 uppercase tracking-wider mb-4" x-text="selectedProduct.color"></p>
                    <div class="flex items-end gap-3 mb-6">
                        <p class="text-4xl font-black text-red-600" x-text="'$' + selectedProduct.price + ' MXN'"></p>
                        <template x-if="selectedProduct.originalPrice">
                            <p class="text-lg font-bold text-gray-400 line-through mb-1" x-text="'$' + selectedProduct.originalPrice + ' MXN'"></p>
                        </template>
                    </div>
                    <p class="text-sm text-gray-700 leading-relaxed mb-8" x-text="selectedProduct.description"></p>
                    <div class="space-y-4">
                        <button @click="addToCart(selectedProduct); productDetailOpen = false" class="w-full bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Agregar al Carrito</button>
                        <button @click="productDetailOpen = false" class="w-full border-2 border-black text-black py-4 text-sm font-bold uppercase tracking-widest hover:bg-black hover:text-white transition">Regresar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="orderSuccessOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div @click="orderSuccessOpen = false; navigateTo('perfil')" x-show="orderSuccessOpen" x-transition.opacity class="absolute inset-0 bg-black/70"></div>
        <div x-show="orderSuccessOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-md w-full p-12 text-center shadow-2xl">
            <div class="w-24 h-24 mx-auto mb-6 bg-green-500 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h2 class="text-3xl font-black uppercase tracking-tight mb-3">Pedido registrado</h2>
            <p class="text-gray-600 mb-2">Tu pedido está pendiente de pago y revisión por el equipo.</p>
            <p class="text-sm font-mono bg-gray-100 inline-block px-4 py-2 mb-8" x-text="orderNumber"></p>
            <button @click="orderSuccessOpen = false; navigateTo('perfil')" class="w-full bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Ver en Mi Perfil</button>
        </div>
    </div>

    <div x-show="invoiceModalOpen" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4">
        <div @click="invoiceModalOpen = false" x-show="invoiceModalOpen" x-transition.opacity class="absolute inset-0 bg-black/70"></div>
        <div x-show="invoiceModalOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-md w-full p-12 shadow-2xl">
            <button @click="invoiceModalOpen = false" class="absolute top-4 right-4 text-2xl font-bold hover:text-gray-500 transition">×</button>
            <div class="text-center mb-8 border-b-2 border-black pb-4">
                <h2 class="text-3xl font-black tracking-tighter">HFSTUDIOS</h2>
                <p class="text-[10px] uppercase tracking-widest text-gray-500 mt-1">Comprobante de pedido · Sin valor fiscal</p>
            </div>
            <template x-if="selectedOrder">
                <div>
                    <div class="flex justify-between mb-6 text-xs bg-gray-50 p-4 border border-gray-200">
                        <div>
                            <p class="font-bold uppercase text-gray-400 mb-1">Cliente:</p>
                            <p class="font-semibold uppercase" x-text="selectedOrder?.user || user.name || 'Invitado'"></p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold uppercase text-gray-400 mb-1">Detalles:</p>
                            <p class="font-mono">Folio: <span x-text="selectedOrder.id"></span></p>
                            <p x-text="selectedOrder.date"></p>
                        </div>
                    </div>
                    <div class="mb-6 max-h-48 overflow-y-auto custom-scroll pr-2">
                        <p class="text-[10px] font-bold uppercase text-gray-400 mb-2 border-b border-gray-200 pb-1">Productos</p>
                        <template x-for="item in selectedOrder.items" :key="item.name">
                            <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                                <span class="uppercase font-medium text-gray-800 text-xs" x-text="(item.quantity || 1) + 'x ' + item.name"></span>
                                <span class="font-mono font-bold text-xs" x-text="'$' + ((item.price * (item.quantity || 1)).toLocaleString())"></span>
                            </div>
                        </template>
                    </div>
                    <div class="border-t-2 border-black pt-4 mb-8 flex justify-between items-center text-xl font-black uppercase">
                        <span>Total del pedido</span>
                        <span class="text-red-600 font-mono" x-text="'$' + (selectedOrder.total || 0).toLocaleString() + ' MXN'"></span>
                    </div>
                    <button @click="downloadInvoice()" class="w-full border-2 border-black text-black py-4 text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition">
                        Descargar PDF
                    </button>
                </div>
            </template>
        </div>
    </div>

    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-[120] flex items-center justify-center p-4">
        <div @click="editModalOpen = false" x-show="editModalOpen" x-transition.opacity class="absolute inset-0 bg-black/70"></div>
        <div x-show="editModalOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-lg w-full p-10 shadow-2xl">
            <button @click="editModalOpen = false" class="absolute top-4 right-4 text-2xl font-bold hover:text-gray-500 transition">×</button>
            <h2 class="text-3xl font-black uppercase tracking-tight mb-8 text-center">Editar Publicación</h2>
            <form @submit.prevent="saveEdit()" novalidate class="space-y-6">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Nombre del artículo</label>
                    <input type="text" x-model="editForm.name" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Precio (MXN)</label>
                    <input type="number" x-model="editForm.price" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">URL de la Foto</label>
                    <input type="text" x-model="editForm.image" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Descripción</label>
                    <textarea x-model="editForm.description" rows="3" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm resize-none"></textarea>
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" @click="editModalOpen = false" class="w-1/3 border-2 border-black text-black py-4 text-sm font-bold uppercase tracking-widest hover:bg-black hover:text-white transition">Cancelar</button>
                    <button type="submit" class="w-2/3 bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="currentRoute !== 'inicio' && currentRoute !== 'admin'" class="bg-[#F0EBE0] overflow-hidden relative h-6">
        <div class="flex whitespace-nowrap marquee-content">
            <span class="inline-block text-[10px] font-bold uppercase tracking-[0.2em] py-1.5 px-8">VENTA SS26: 20% DE DESCUENTO EN TODA LA TIENDA | © HF STUDIOS, 2026</span>
            <span class="inline-block text-[10px] font-bold uppercase tracking-[0.2em] py-1.5 px-8">VENTA SS26: 20% DE DESCUENTO EN TODA LA TIENDA | © HF STUDIOS, 2026</span>
            <span class="inline-block text-[10px] font-bold uppercase tracking-[0.2em] py-1.5 px-8">VENTA SS26: 20% DE DESCUENTO EN TODA LA TIENDA | © HF STUDIOS, 2026</span>
        </div>
    </div>

    <nav x-show="currentRoute !== 'inicio' && currentRoute !== 'admin'" class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm">
        <div class="max-w-screen-2xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">

                <div class="hidden lg:flex items-center gap-6">
                    <button @click="navigateTo('catalogo')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Catálogo</button>
                    <button @click="navigateTo('campana')" class="text-xs font-bold uppercase tracking-wider text-blue-600 hover:opacity-60 transition">Campaña</button>
                    <button @click="navigateTo('promociones-front')" class="text-xs font-bold uppercase tracking-wider text-red-600 hover:opacity-60 transition">Promos</button>
                    <button @click="navigateTo('comunidad')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Comunidad</button>
                </div>

                <div class="absolute left-1/2 transform -translate-x-1/2">
                    <button @click="navigateTo('catalogo')" class="text-2xl md:text-3xl font-black tracking-tighter">HFSTUDIOS</button>
                </div>

                <div class="hidden lg:flex items-center gap-5">
                    <button @click="navigateTo('nosotros')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Nosotros</button>
                    <button x-show="!isLoggedIn" @click="navigateTo('login')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Ingresar</button>

                    <template x-if="isLoggedIn && !isAdmin">
                        <div class="flex items-center gap-5">
                            <button @click="navigateTo('subasta')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition"><i class="fas fa-gavel mr-1"></i> Subastas</button>
                            <button @click="navigateTo('mis-publicaciones')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Publicar</button>
                            <button @click="navigateTo('perfil')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition">Perfil</button>
                        </div>
                    </template>

                    <button x-show="isLoggedIn && isStaff" @click="navigateTo('crm')" class="text-xs font-bold">CRM</button>
                    <a x-show="isLoggedIn && isStaff" href="/scm" class="font-bold">SCM</a>
                    <button x-show="isLoggedIn && isAdmin" @click="navigateTo('admin')" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition bg-black text-white px-2 py-1">Admin</button>

                    <button @click="cartOpen = true" class="text-xs font-bold uppercase tracking-wider hover:opacity-60 transition relative">
                        <i class="fas fa-shopping-cart mr-2"></i>Cart (<span x-text="cartTotalItems"></span>)
                        <span x-show="cartTotalItems > 0" class="absolute -top-1 -right-1 w-2 h-2 bg-black rounded-full animate-pulse"></span>
                    </button>
                </div>

                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden text-sm font-bold"><span x-show="!mobileMenuOpen">MENÚ</span><span x-show="mobileMenuOpen">CERRAR</span></button>
            </div>
        </div>
    </nav>

    <div x-show="mobileMenuOpen" x-transition.opacity class="fixed inset-0 bg-black z-40 lg:hidden overflow-y-auto" x-cloak>
        <div class="flex flex-col items-center justify-center min-h-full py-12 space-y-6 text-white">
            <button @click="navigateTo('catalogo'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Catálogo</button>
            <button @click="navigateTo('campana'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider text-blue-400">Campaña</button>
            <button @click="navigateTo('promociones-front'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider text-red-500">Promos</button>
            <button @click="navigateTo('comunidad'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Comunidad</button>
            <button @click="navigateTo('nosotros'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Nosotros</button>

            <button x-show="!isLoggedIn" @click="navigateTo('login'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider border-t border-gray-800 pt-6 mt-2">Iniciar sesión</button>

            <template x-if="isLoggedIn && !isAdmin">
                <div class="flex flex-col items-center space-y-6 border-t border-gray-800 pt-6 w-full">
                    <button @click="navigateTo('subasta'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Subastas</button>
                    <button @click="navigateTo('mis-publicaciones'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Mis Publicaciones</button>
                    <button @click="navigateTo('perfil'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider">Perfil</button>
                </div>
            </template>

            <button x-show="isLoggedIn && isStaff" @click="navigateTo('crm')" class="text-xl font-bold">CRM</button>
                    <a x-show="isLoggedIn && isStaff" href="/scm" class="font-bold">SCM</a>
            <button x-show="isLoggedIn && isAdmin" @click="navigateTo('admin'); mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider border-t border-gray-800 pt-6">Admin Panel</button>
            <button @click="cartOpen = true; mobileMenuOpen = false" class="text-2xl font-bold uppercase tracking-wider border-t border-gray-800 pt-6">Carrito (<span x-text="cartTotalItems"></span>)</button>
        </div>
    </div>

    <div x-show="cartOpen" class="fixed inset-0 z-[60]" x-cloak>
        <div @click="cartOpen = false" x-show="cartOpen" x-transition.opacity class="absolute inset-0 bg-black/70"></div>
        <div x-show="cartOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 class="text-2xl font-black uppercase tracking-tight">Tu Carrito</h2>
                <button @click="cartOpen = false" class="text-3xl font-bold hover:opacity-60">×</button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 custom-scroll">
                <template x-if="cart.length === 0">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-24 w-24 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                        <p class="text-gray-500 text-sm uppercase tracking-wider mb-4 font-semibold">Tu carrito está vacío</p>
                        <button @click="cartOpen = false" class="text-xs font-bold uppercase tracking-wider underline hover:no-underline">Continuar Comprando</button>
                    </div>
                </template>
                <div class="space-y-6">
                    <template x-for="(item, index) in cart" :key="item.cartItemId || item.id">
                        <div class="flex gap-4 pb-6 border-b border-gray-200">
                            <img :src="item.image" class="w-24 h-32 object-cover bg-gray-100 flex-shrink-0">
                            <div class="flex-1 min-w-0 flex flex-col">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-sm font-bold uppercase tracking-wide mb-1 truncate" x-text="item.name"></h3>
                                        <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-2" x-text="item.color"></p>
                                    </div>
                                    <button @click="removeFromCart(index)" class="text-xs text-gray-400 hover:text-red-600 font-semibold transition self-start"><i class="fas fa-trash"></i></button>
                                </div>
                                <div class="mt-auto flex items-center justify-between">
                                    <div class="flex items-center border border-gray-200 rounded-sm">
                                        <button @click="decreaseQty(index)" class="px-3 py-1 text-gray-500 hover:bg-gray-100 transition">-</button>
                                        <span class="px-2 py-1 text-xs font-bold font-mono text-center min-w-[2rem]" x-text="item.quantity || 1"></span>
                                        <button @click="increaseQty(index)" class="px-3 py-1 text-gray-500 hover:bg-gray-100 transition">+</button>
                                    </div>
                                    <p class="text-base font-bold text-red-600" x-text="'$' + ((item.price || 0) * (item.quantity || 1)).toLocaleString() + ' MXN'"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="border-t border-gray-200 p-6 space-y-4 bg-gray-50" x-show="cart.length > 0">
                <div class="flex gap-2 mb-4">
                    <input type="text" x-model="discountCode" placeholder="Código de descuento" class="flex-1 px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-black uppercase bg-white">
                    <button @click="applyDiscount()" class="bg-gray-800 text-white px-4 py-2 text-xs font-bold uppercase tracking-wider hover:bg-black transition">Aplicar</button>
                </div>

                <div class="flex items-center justify-between text-lg font-bold">
                    <span class="uppercase tracking-wider">Subtotal</span>
                    <div class="text-right">
                        <template x-if="discountApplied">
                            <span class="text-xs text-gray-400 line-through block" x-text="'$' + cartTotalSinDescuento.toLocaleString() + ' MXN'"></span>
                        </template>
                        <span class="text-red-600" x-text="'$' + cartTotal.toLocaleString() + ' MXN'"></span>
                    </div>
                </div>
                <template x-if="discountApplied">
                    <p class="text-[10px] uppercase font-bold text-green-600 tracking-wider text-right">Descuento aplicado con éxito</p>
                </template>

                <button @click="handleCheckout()" class="w-full bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Finalizar Compra</button>
            </div>
        </div>
    </div>

    <div x-show="checkoutOpen" x-cloak class="fixed inset-0 z-[80] overflow-y-auto">
        <div @click="checkoutOpen = false" x-show="checkoutOpen" x-transition.opacity class="absolute inset-0 bg-black/80"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="checkoutOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative bg-white max-w-2xl w-full p-8 md:p-12 shadow-2xl overflow-hidden">
                <button @click="checkoutOpen = false" class="absolute top-4 right-4 text-2xl font-bold text-gray-400 hover:text-black transition">×</button>

                <div class="flex justify-between mb-12 relative px-4 md:px-12">
                    <div class="absolute top-1/2 left-0 w-full h-0.5 bg-gray-200 -translate-y-1/2 z-0"></div>
                    <div class="absolute top-1/2 left-0 h-0.5 bg-black -translate-y-1/2 z-0 transition-all duration-500" :style="'width: ' + ((checkoutStep - 1) * 50) + '%'"></div>

                    <template x-for="step in [1, 2, 3]">
                        <div class="relative z-10 flex flex-col items-center bg-white px-2">
                            <div :class="checkoutStep >= step ? 'bg-black text-white border-black' : 'bg-white text-gray-300 border-gray-200'" class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-colors">
                                <span x-show="checkoutStep <= step" x-text="step"></span>
                                <i x-show="checkoutStep > step" class="fas fa-check text-[10px]"></i>
                            </div>
                            <span class="text-[9px] font-bold uppercase mt-2 tracking-widest absolute -bottom-6 w-max text-center" :class="checkoutStep >= step ? 'text-black' : 'text-gray-400'" x-text="step === 1 ? 'Dirección' : step === 2 ? 'Pago' : 'Confirma'"></span>
                        </div>
                    </template>
                </div>

                <div x-show="checkoutStep === 1">
                    <h3 class="text-xl font-black uppercase mb-6 tracking-tight">Datos de Envío</h3>
                    <form @submit.prevent="checkoutStep = 2" class="space-y-4">
                        <input type="text" required x-model="checkoutForm.address" placeholder="Calle y número" class="w-full border-2 border-gray-200 px-4 py-3 text-sm focus:border-black outline-none bg-gray-50 focus:bg-white transition-colors">
                        <div class="grid grid-cols-2 gap-4">
                            <input type="text" required x-model="checkoutForm.city" placeholder="Ciudad" class="border-2 border-gray-200 px-4 py-3 text-sm focus:border-black outline-none bg-gray-50 focus:bg-white transition-colors">
                            <input type="text" required pattern="[0-9]{5}" maxlength="5" x-model="checkoutForm.zip" placeholder="C.P." class="border-2 border-gray-200 px-4 py-3 text-sm focus:border-black outline-none bg-gray-50 focus:bg-white transition-colors font-mono">
                        </div>
                        <button type="submit" class="w-full bg-black text-white py-4 mt-8 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">Siguiente</button>
                    </form>
                </div>

                <div x-show="checkoutStep === 2" style="display:none;">
                    <h3 class="text-xl font-black uppercase mb-6 tracking-tight">Registro del pedido</h3>
                    <div class="bg-gray-50 border border-gray-200 p-6"><h4 class="font-bold mb-3">Pedido pendiente de pago</h4><p class="text-sm">Al confirmar, se guardará el pedido y se reservarán las existencias. El equipo coordinará el pago y envío. No se cobra ninguna tarjeta desde esta pantalla.</p></div>

                    <div class="grid grid-cols-2 gap-4 mt-8">
                        <button @click="checkoutStep = 1" class="border-2 border-black text-black py-4 text-xs font-bold uppercase tracking-widest hover:bg-gray-50 transition">Atrás</button>
                        <button @click="checkoutStep = 3" class="bg-black text-white py-4 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">Revisar</button>
                    </div>
                </div>

                <div x-show="checkoutStep === 3" style="display:none;">
                    <h3 class="text-xl font-black uppercase mb-6 tracking-tight">Resumen Final</h3>
                    <div class="space-y-4 mb-8">
                        <div class="border border-gray-200 bg-gray-50 p-4">
                            <div class="flex justify-between items-center mb-2">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Enviar a:</p>
                                <button @click="checkoutStep = 1" class="text-xs text-blue-500 hover:underline font-bold">Editar</button>
                            </div>
                            <p class="text-sm font-bold uppercase" x-text="checkoutForm.address || 'PENDIENTE'"></p>
                            <p class="text-xs text-gray-500 uppercase mt-1" x-text="(checkoutForm.city || 'Ciudad') + ', CP: ' + (checkoutForm.zip || '00000')"></p>
                        </div>
                        <div class="border border-gray-200 bg-gray-50 p-4">
                            <div class="flex justify-between items-center mb-2">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Pago:</p>
                                <button @click="checkoutStep = 2" class="text-xs text-blue-500 hover:underline font-bold">Editar</button>
                            </div>
                            <p class="text-sm font-bold uppercase" x-text="'Pendiente de pago'"></p>
                        </div>
                        <div class="border border-gray-200 p-4">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 border-b border-gray-100 pb-2">Artículos (<span x-text="cartTotalItems"></span>)</p>
                            <div class="max-h-32 overflow-y-auto custom-scroll pr-2 space-y-2 mb-4">
                                <template x-for="item in cart" :key="item.cartItemId || item.id">
                                    <div class="flex justify-between text-xs">
                                        <span class="truncate pr-2 font-medium uppercase" x-text="(item.quantity || 1) + 'x ' + item.name"></span>
                                        <span class="font-mono font-bold" x-text="'$' + ((item.price || 0) * (item.quantity || 1)).toLocaleString()"></span>
                                    </div>
                                </template>
                            </div>
                            <div class="flex justify-between items-center text-lg font-black uppercase border-t border-black pt-3">
                                <span>Total a pagar</span>
                                <span class="text-red-600" x-text="'$' + cartTotal.toLocaleString() + ' MXN'"></span>
                            </div>
                        </div>
                    </div>
                    <button :disabled="isCheckingOut" @click="processFinalPayment()" class="w-full bg-black text-white py-5 text-sm font-black uppercase tracking-widest hover:bg-gray-800 transition flex justify-center items-center">
                        <span x-show="!isCheckingOut">Registrar pedido</span>
                        <div x-show="isCheckingOut" class="flex gap-2 items-center">
                            <svg class="w-5 h-5 spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>Procesando...</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="currentRoute === 'inicio'" x-cloak>
        <section class="relative h-screen overflow-hidden">
            <div class="absolute inset-0">
                <img src="https://images.unsplash.com/photo-1550639525-c97d455acf70?q=80&w=2070&auto=format&fit=crop" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/80"></div>
            </div>
            <div class="relative z-10 flex items-center justify-between px-6 md:px-12 py-6">
                <h1 class="text-2xl md:text-3xl font-black tracking-tighter text-white">HFSTUDIOS</h1>
                <button @click="navigateTo('login')" class="bg-white hover:bg-gray-200 text-black px-6 py-2 text-sm font-bold uppercase tracking-wider transition">Iniciar sesión</button>
            </div>
            <div class="relative z-10 flex flex-col items-center justify-center h-full px-6 pb-32">
                <div class="max-w-3xl text-center">
                    <h2 class="text-4xl md:text-6xl lg:text-7xl font-black uppercase tracking-tight text-white mb-6 leading-tight">ACCESO EXCLUSIVO<br>AL DROP SS26.</h2>
                    <p class="text-lg md:text-xl text-gray-300 mb-12 font-medium">Todo el catálogo con 20% de descuento por tiempo limitado.</p>
                    <form @submit.prevent="handleLandingSubmit()" class="flex flex-col sm:flex-row gap-4 max-w-2xl mx-auto">
                        <input type="email" required x-model="landingEmail" placeholder="Tu Email" class="flex-1 px-6 py-4 text-lg bg-white/90 border-2 border-white/20 focus:border-white focus:outline-none text-black">
                        <button type="submit" class="bg-black hover:bg-gray-800 text-white border-2 border-white px-8 py-4 text-lg font-black uppercase tracking-widest transition whitespace-nowrap">Entrar a la tienda <i class="fas fa-chevron-right ml-2"></i></button>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'promociones-front'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-4xl mx-auto text-center mb-16">
                <h1 class="text-5xl md:text-6xl font-black uppercase tracking-tight mb-4">Promociones</h1>
                <p class="text-lg text-gray-600 uppercase tracking-wider">Aprovecha nuestras ofertas activas de temporada.</p>
            </div>
            <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                <div class="bg-black text-white p-10 flex flex-col items-center text-center justify-center shadow-xl">
                    <h2 class="text-6xl font-black uppercase mb-2">10% OFF</h2>
                    <p class="text-sm tracking-widest uppercase mb-8 text-gray-400">En toda tu compra</p>
                    <div class="bg-white/10 px-6 py-3 border border-white/30 mb-4">
                        <p class="font-mono text-2xl font-bold tracking-widest">HF10</p>
                    </div>
                    <p class="text-xs text-gray-400">Ingresa este código visual en el carrito.</p>
                </div>
                <div class="bg-[#F0EBE0] text-black p-10 flex flex-col items-center text-center justify-center shadow-xl">
                    <h2 class="text-6xl font-black uppercase mb-2">CAMISETAS</h2>
                    <p class="text-sm tracking-widest uppercase mb-8 text-gray-600">Explora los modelos disponibles</p>
                    <button @click="navigateTo('catalogo'); selectedCategory = 'T-Shirt'" class="bg-black text-white px-8 py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">
                        Ver Colección
                    </button>
                </div>
            </div>
            <div class="max-w-md mx-auto mt-20 bg-gray-50 p-8 border border-gray-200">
                <h3 class="text-center font-bold uppercase tracking-wider mb-4">Prueba tu código aquí</h3>
                <form @submit.prevent="applyDiscount()" class="flex gap-2">
                    <input type="text" x-model="discountCode" placeholder="Código de descuento" class="flex-1 px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none uppercase">
                    <button type="submit" class="bg-black text-white px-6 py-3 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Aplicar</button>
                </form>
            </div>
        </section>
    </div>

  <div x-show="currentRoute === 'campana'" x-cloak>
        <section class="relative h-[80vh] bg-[#111] overflow-hidden flex items-center">
            <div class="absolute inset-0 w-full h-full opacity-50">
                <img src="https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?q=80&w=2000&auto=format&fit=crop" class="w-full h-full object-cover">
            </div>
            <div class="relative z-10 max-w-screen-2xl mx-auto px-6 w-full flex flex-col md:flex-row items-center gap-12">
                <div class="text-white flex-1">
                    <p class="text-red-500 font-bold uppercase tracking-widest mb-4">Nueva Colección - Producto Destacado</p>
                    <h1 class="text-6xl md:text-8xl font-black uppercase tracking-tighter leading-none mb-6">HOODIE<br>GRÁFICO<br>HF</h1>
                    <p class="text-lg text-gray-300 mb-8 max-w-md">Descubre el hoodie gráfico de la colección HF: serigrafía de alta densidad y acabado lavado.</p>
                    <button @click="navigateTo('catalogo')" class="bg-white text-black px-10 py-4 text-sm font-black uppercase tracking-widest hover:bg-gray-200 transition">
                        Comprar Ahora
                    </button>
                </div>
                <div class="hidden md:block flex-1">
                    <img src="https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=800&auto=format&fit=crop" class="w-2/3 ml-auto shadow-2xl rotate-3 hover:rotate-0 transition duration-500">
                </div>
            </div>
        </section>
        <section class="py-20 bg-gray-50">
            <div class="max-w-screen-2xl mx-auto px-6">
                <h2 class="text-3xl font-black uppercase tracking-tight text-center mb-12">Lo que dice nuestra comunidad</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-white p-8 border border-gray-200 shadow-sm">
                        <div class="flex text-yellow-400 mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="text-gray-700 mb-6 italic">"La calidad de los materiales es brutal. Superó por completo mis expectativas. El envío fue rápido."</p>
                        <p class="font-bold uppercase text-xs tracking-wider">- Roberto M.</p>
                    </div>
                    <div class="bg-white p-8 border border-gray-200 shadow-sm">
                        <div class="flex text-yellow-400 mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                        <p class="text-gray-700 mb-6 italic">"Increíble el fit de las camisetas oversized. Ya me pedí tres colores diferentes."</p>
                        <p class="font-bold uppercase text-xs tracking-wider">- Ana S.</p>
                    </div>
                    <div class="bg-white p-8 border border-gray-200 shadow-sm">
                        <div class="flex text-yellow-400 mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i></div>
                        <p class="text-gray-700 mb-6 italic">"Me encanta el diseño minimalista de la marca. Una estética de lujo a precio accesible."</p>
                        <p class="font-bold uppercase text-xs tracking-wider">- Diego L.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'comunidad'" x-cloak>
        <section class="max-w-screen-xl mx-auto px-6 py-20">
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight mb-4">Comunidad HF</h1>
                <p class="text-gray-600 uppercase tracking-wider text-sm">Únete a la conversación. Deja tu comentario sobre nuestros drops.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-12">
                <div class="md:col-span-1">
                    <div class="bg-gray-50 p-8 border border-gray-200 sticky top-32">
                        <h3 class="font-black uppercase tracking-tight text-xl mb-6">Deja un comentario</h3>
                        <form @submit.prevent="addComment()" class="space-y-4">
                            <textarea x-model="newComment" rows="4" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm resize-none" placeholder="¿Qué opinas de la nueva colección?"></textarea>
                            <button type="submit" class="w-full bg-black text-white py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">Publicar</button>
                        </form>
                    </div>
                </div>
                <div class="md:col-span-2 space-y-6">
                    <template x-for="comment in comments" :key="comment.id || comment.name">
                        <div class="bg-white p-6 border-b border-gray-200 flex gap-4">
                            <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center shrink-0">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <div>
                                <p class="font-bold text-sm mb-1" x-text="'@' + (comment.name || 'Usuario')"></p>
                                <p class="text-gray-700 text-sm leading-relaxed" x-text="comment.text || 'Sin texto'"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'catalogo'" x-cloak>
        <div class="bg-black text-white p-4 text-center"><p class="text-xs md:text-sm font-black uppercase tracking-[0.2em] animate-pulse">🔥 TODA LA TIENDA TIENE 20% DE DESCUENTO APLICADO 🔥</p></div>
        <section class="max-w-screen-2xl mx-auto px-6 py-12">
            <div class="mb-12">
                <h2 class="text-3xl md:text-4xl font-black uppercase tracking-tight mb-2">Catálogo</h2>
                <div class="mb-8 relative">
                    <input type="text" x-model="searchQuery" @input="selectedCategory = 'All'" placeholder="Buscar productos (ej. Hoodie, Negro)..." class="w-full px-6 py-4 text-sm border-2 border-gray-200 focus:border-black focus:outline-none bg-white">
                    <i class="fas fa-search absolute right-6 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button @click="selectedCategory = 'All'; searchQuery = ''" :class="selectedCategory === 'All' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">Todos</button>
                    <button @click="selectedCategory = 'Promociones'; searchQuery = ''" :class="selectedCategory === 'Promociones' ? 'bg-red-600 text-white border-red-600' : 'bg-white text-red-600 border border-red-600'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition"><i class="fas fa-tags"></i> Promociones</button>
                    <button @click="selectedCategory = 'Hoodie'; searchQuery = ''" :class="selectedCategory === 'Hoodie' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">Hoodies</button>
                    <button @click="selectedCategory = 'T-Shirt'; searchQuery = ''" :class="selectedCategory === 'T-Shirt' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">T-Shirts</button>
                    <button @click="selectedCategory = 'Pants'; searchQuery = ''" :class="selectedCategory === 'Pants' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">Pants</button>
                    <button @click="selectedCategory = 'Accessory'; searchQuery = ''" :class="selectedCategory === 'Accessory' ? 'bg-black text-white' : 'bg-white text-black border border-gray-300'" class="px-6 py-2 text-xs font-bold uppercase tracking-wider transition">Accesorios</button>
                </div>
            </div>

            <template x-if="filteredProducts.length === 0">
                <div class="text-center py-20">
                    <svg class="mx-auto h-24 w-24 text-gray-300 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <h3 class="text-2xl font-black uppercase tracking-tight mb-2">No se encontraron resultados</h3>
                    <button @click="searchQuery = ''; selectedCategory = 'All'" class="inline-block bg-black text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition mt-4">Ver todos los productos</button>
                </div>
            </template>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 gap-y-10" x-show="filteredProducts.length > 0">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div class="product-card group cursor-pointer" @click="viewProductDetail(product)">
                        <div class="relative bg-[#F4F4F4] overflow-hidden mb-4">
                            <span x-show="product.badge" :class="product.badge && product.badge.includes('PROMO') ? 'bg-red-600 text-white' : 'bg-black text-white'" class="absolute top-3 left-3 text-[9px] font-bold uppercase tracking-wider px-2 py-1 z-10 shadow-md" x-text="product.badge"></span>
                            <img :src="product.image" class="w-full aspect-[3/4] object-cover group-hover:scale-105 transition duration-500">
                        </div>
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wide mb-1 truncate" x-text="product.name"></h3>
                            <p class="text-[10px] text-gray-500 uppercase tracking-wider mb-2" x-text="product.color"></p>
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-black text-red-600" x-text="'$' + (product.price || 0).toLocaleString() + ' MXN'"></p>
                                <template x-if="product.originalPrice"><p class="text-[10px] font-bold text-gray-400 line-through" x-text="'$' + product.originalPrice.toLocaleString()"></p></template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'mis-publicaciones'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-5xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                    <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight">Mis Publicaciones</h1>
                    <button @click="navigateTo('publicar')" class="bg-black text-white px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">+ Vender Producto</button>
                </div>
                <div class="bg-white border border-gray-200 overflow-hidden shadow-sm">
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                                <tr><th class="px-6 py-4">Producto</th><th class="px-6 py-4">Precio</th><th class="px-6 py-4 text-right">Acciones</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-if="userProducts.length === 0">
                                    <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500 font-medium">No has publicado ningún producto aún.</td></tr>
                                </template>
                                <template x-for="(prod, index) in userProducts" :key="prod.id || index">
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <div class="w-16 h-16 bg-gray-100 border border-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0"><img :src="prod.image" class="w-full h-full object-cover"></div>
                                                <div><p class="text-sm font-bold uppercase" x-text="prod.name || 'Sin Título'"></p><p class="text-xs text-gray-500 truncate max-w-[200px]" x-text="prod.description || '...'"></p></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4"><p class="text-sm font-bold" x-text="'$' + (prod.price || 0).toLocaleString()"></p></td>
                                        <td class="px-6 py-4 text-right space-x-3">
                                            <button @click="openEditModal(prod, index)" class="text-xs font-bold text-blue-600 hover:underline uppercase tracking-wider">Editar</button>
                                            <button @click="deleteUserProduct(index)" class="text-xs font-bold text-red-600 hover:underline uppercase tracking-wider">Eliminar</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="md:hidden divide-y divide-gray-100">
                        <template x-if="userProducts.length === 0">
                            <div class="py-10 text-center text-gray-500 text-sm">No hay publicaciones</div>
                        </template>
                        <template x-for="(prod, index) in userProducts" :key="'mob-'+(prod.id || index)">
                            <div class="p-4 flex gap-4">
                                <div class="w-20 h-24 bg-gray-100 border border-gray-200 flex-shrink-0"><img :src="prod.image" class="w-full h-full object-cover"></div>
                                <div class="flex-1 flex flex-col">
                                    <p class="text-sm font-bold uppercase tracking-tight" x-text="prod.name || 'Prod'"></p>
                                    <p class="font-mono font-bold text-xs mt-1" x-text="'$' + (prod.price || 0).toLocaleString()"></p>
                                    <div class="mt-auto flex justify-between border-t border-gray-100 pt-2">
                                        <button @click="openEditModal(prod, index)" class="text-[10px] font-bold text-blue-600 uppercase">Editar</button>
                                        <button @click="deleteUserProduct(index)" class="text-[10px] font-bold text-red-500 uppercase">Eliminar</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'publicar'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-2xl mx-auto bg-white p-10 shadow-2xl border border-gray-100 relative">
                <button @click="navigateTo('mis-publicaciones')" class="absolute top-6 left-6 text-gray-400 hover:text-black transition"><i class="fas fa-arrow-left text-xl"></i></button>
                <div class="text-center mb-8 border-b border-gray-100 pb-4 mt-2">
                    <h1 class="text-3xl font-black uppercase tracking-tight">Publica tu producto</h1>
                </div>
                <form @submit.prevent="handlePublishSubmit()" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Nombre del artículo</label>
                        <input type="text" required x-model="publishForm.name" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm" placeholder="Ej. Jordan 1 Retro High">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Precio (MXN)</label>
                        <input type="number" required x-model="publishForm.price" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm" placeholder="2500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">URL de la Foto</label>
                        <input type="text" required x-model="publishForm.image" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm" placeholder="https://ejemplo.com/foto.jpg">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Descripción</label>
                        <textarea x-model="publishForm.description" rows="4" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm resize-none" placeholder="Condición, talla, detalles..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-black text-white py-4 mt-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Publicar</button>
                </form>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'subasta'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-5xl mx-auto">
                <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight mb-2">Subasta en Vivo</h1>
                <p class="text-sm text-gray-600 uppercase tracking-wider mb-8">Comunidad HF</p>
                <div class="grid md:grid-cols-2 gap-10">
                    <div class="bg-[#F4F4F4] p-8 border border-gray-200 relative">
                        <span class="absolute top-4 left-4 bg-red-600 text-white text-[10px] font-bold uppercase tracking-widest px-3 py-1 animate-pulse">En vivo</span>
                        <img :src="auction.product.image" class="w-full h-auto object-cover max-h-[400px]">
                    </div>
                    <div class="flex flex-col justify-between">
                        <div>
                            <h2 class="text-2xl font-black uppercase tracking-tight mb-2" x-text="auction.product.name"></h2>
                            <div class="flex items-center gap-3 mb-6 bg-black text-white w-fit px-4 py-2"><i class="far fa-clock"></i><span class="font-mono text-lg font-bold tracking-widest" x-text="formattedTimer"></span></div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Oferta Actual</p>
                            <p class="text-4xl font-black text-green-600 mb-8" x-text="'$' + (auction.product.currentBid || 0).toLocaleString()"></p>
                            <form @submit.prevent="handleOfferSubmit()" class="mb-8 bg-gray-50 p-6 border border-gray-200">
                                <label class="block text-xs font-bold uppercase tracking-wider mb-3">Hacer Oferta</label>
                                <div class="flex gap-2">
                                    <span class="bg-gray-200 flex items-center px-4 font-bold text-gray-600">$</span>
                                    <input type="number" required min="0.01" step="0.01" x-model="auction.newOffer" class="flex-1 px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none font-bold" placeholder="Monto">
                                    <button type="submit" class="bg-black text-white px-6 font-bold uppercase tracking-widest hover:bg-gray-800 transition">Ofertar</button>
                                </div>
                                <p class="text-[10px] text-gray-500 mt-2">* La oferta debe superar el importe actual y la subasta debe estar abierta.</p>
                            </form>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider mb-3 border-b pb-2">Historial de Ofertas</h3>
                            <ul class="space-y-2 max-h-40 overflow-y-auto custom-scroll">
                                <template x-for="offer in auction.offers" :key="offer.amount || offer.user">
                                    <li class="flex justify-between items-center text-sm p-2 bg-gray-50"><span class="font-semibold text-gray-700" x-text="offer.user || 'Anónimo'"></span><span class="font-black" x-text="'$' + (offer.amount || 0).toLocaleString()"></span></li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'admin' && isAdmin" x-cloak class="min-h-screen bg-gray-50 flex flex-col md:flex-row">
        <aside class="w-full md:w-64 bg-white border-r border-gray-200 flex flex-col shrink-0">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-black uppercase tracking-tighter">HF. ADMIN</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mt-1" x-text="'HOLA, ' + (user.name || 'ADMIN')"></p>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <button @click="adminTab = 'pedidos'" :class="adminTab === 'pedidos' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-100'" class="w-full text-left px-4 py-3 text-xs font-bold uppercase tracking-widest transition rounded-sm"><i class="fas fa-box w-5"></i> Pedidos</button>
                <button @click="adminTab = 'productos'" :class="adminTab === 'productos' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-100'" class="w-full text-left px-4 py-3 text-xs font-bold uppercase tracking-widest transition rounded-sm"><i class="fas fa-tags w-5"></i> Productos</button>
                <a href="/scm" class="block px-4 py-3 text-xs font-bold uppercase tracking-widest">Inventario SCM</a>
                <button @click="adminTab = 'clientes'" :class="adminTab === 'clientes' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-100'" class="w-full text-left px-4 py-3 text-xs font-bold uppercase tracking-widest transition rounded-sm"><i class="fas fa-users w-5"></i> Clientes</button>
                <button @click="adminTab = 'facturas'" :class="adminTab === 'facturas' ? 'bg-black text-white' : 'text-gray-600 hover:bg-gray-100'" class="w-full text-left px-4 py-3 text-xs font-bold uppercase tracking-widest transition rounded-sm"><i class="fas fa-file-invoice w-5"></i> Comprobantes</button>
            </nav>
            <div class="p-4 border-t border-gray-200 space-y-2">
                <button @click="navigateTo('catalogo')" class="w-full bg-white border border-gray-300 text-black px-4 py-3 text-xs font-bold uppercase tracking-widest hover:bg-gray-100 transition text-center">Ver Tienda</button>
                <button @click="handleLogout()" class="w-full bg-red-600 text-white px-4 py-3 text-xs font-bold uppercase tracking-widest hover:bg-red-700 transition text-center">Cerrar Sesión</button>
            </div>
        </aside>

        <main class="flex-1 p-6 md:p-10 overflow-y-auto">
            <div class="mb-8 flex justify-between items-end border-b border-gray-200 pb-4">
                <h1 class="text-3xl md:text-4xl font-black uppercase tracking-tight" x-text="adminTab === 'facturas' ? 'Comprobantes' : adminTab"></h1>
                <button x-show="adminTab === 'productos'" @click="openProductEditor()" class="bg-black text-white px-6 py-3 text-xs font-bold">+ Nuevo producto</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" x-show="adminTab === 'pedidos' || adminTab === 'clientes' || adminTab === 'facturas'">
                <div class="bg-white p-6 border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Valor de pedidos</p>
                    <p class="text-3xl font-black text-black" x-text="adminStats.sales"></p>
                </div>
                <div class="bg-white p-6 border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Pedidos Activos</p>
                    <p class="text-3xl font-black text-black" x-text="adminStats.orders"></p>
                </div>
                <div class="bg-white p-6 border border-gray-200 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Clientes CRM</p>
                    <p class="text-3xl font-black text-black" x-text="adminStats.users"></p>
                </div>
            </div>

            <div x-show="adminTab === 'pedidos'" class="bg-white border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">ID Pedido</th>
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Fecha</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="pedido in adminOrdersList" :key="pedido.id">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-mono text-sm font-bold" x-text="pedido.id"></td>
                                    <td class="px-6 py-4 text-sm uppercase font-semibold" x-text="pedido.user"></td>
                                    <td class="px-6 py-4 text-xs text-gray-500 font-mono" x-text="pedido.date"></td>
                                    <td class="px-6 py-4">
                                        <select x-show="pedido.status === 'Pendiente' || pedido.status === 'Enviado'" @change="changeOrderStatus(pedido, $event.target.value); $event.target.value = ''" class="text-xs border p-2"><option value="">Cambiar estado</option><option x-show="pedido.status === 'Pendiente'" value="Enviado">Enviado</option><option x-show="pedido.status === 'Enviado'" value="Entregado">Entregado</option><option x-show="pedido.status === 'Pendiente'" value="Cancelado">Cancelado</option></select>
                                        <span :class="pedido.status === 'Entregado' ? 'bg-green-100 text-green-700' : (pedido.status === 'Enviado' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')" class="px-2 py-1 text-[9px] font-bold uppercase tracking-widest rounded-sm" x-text="pedido.status"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono font-bold text-sm" x-text="'$' + pedido.total.toLocaleString()"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="adminTab === 'productos'" class="bg-white border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">Producto</th>
                                <th class="px-6 py-4">Categoría</th>
                                <th class="px-6 py-4">Precio</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="prod in allProducts" :key="prod.id">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <img :src="prod.image" class="w-12 h-16 object-cover bg-gray-100 border border-gray-200">
                                            <span class="text-sm font-bold uppercase" x-text="prod.name"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 uppercase tracking-widest font-bold" x-text="prod.category"></td>
                                    <td class="px-6 py-4 font-mono font-bold text-sm text-red-600" x-text="'$' + prod.price.toLocaleString()"></td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <button @click="openProductEditor(prod)" class="text-[10px] font-bold text-blue-600 uppercase tracking-wider hover:underline">Editar</button>
                                        <button @click="toggleProduct(prod)" class="text-[10px] font-bold text-gray-500 uppercase tracking-wider" x-text="prod.active ? 'Ocultar' : 'Mostrar'"></button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="adminTab === 'clientes'" class="bg-white p-8 border border-gray-200"><h2 class="text-2xl font-black mb-4">Gestión CRM</h2><p class="mb-6">Clientes, interacciones, etapas, evaluaciones y métricas centralizadas.</p><a href="/crm" class="bg-black text-white px-6 py-3 inline-block">Abrir CRM</a></div>

            <div x-show="adminTab === 'facturas'" class="bg-white border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">Folio del pedido</th>
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Pedido Relacionado</th>
                                <th class="px-6 py-4">Fecha de registro</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4 text-right">Monto</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="factura in adminInvoicesList" :key="factura.id">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-mono text-sm font-bold" x-text="factura.id"></td>
                                    <td class="px-6 py-4 text-sm uppercase font-semibold" x-text="factura.user"></td>
                                    <td class="px-6 py-4 text-xs text-gray-500 font-mono" x-text="factura.orderId"></td>
                                    <td class="px-6 py-4 text-xs text-gray-500 font-mono" x-text="factura.date"></td>
                                    <td class="px-6 py-4">
                                        <span :class="factura.status === 'Pagada' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" class="px-2 py-1 text-[9px] font-bold uppercase tracking-widest rounded-sm" x-text="factura.status"></span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono font-bold text-sm" x-text="'$' + factura.total.toLocaleString()"></td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <button @click="downloadInvoice(factura)" class="text-[10px] font-bold text-blue-600 uppercase tracking-wider hover:underline"><i class="fas fa-file-pdf mr-1"></i> Descargar PDF</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <div x-show="currentRoute === 'perfil'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-5xl mx-auto">
                <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tight mb-8 border-b border-gray-100 pb-4">Mi Perfil</h1>
                <div class="grid lg:grid-cols-3 gap-8">
                    <div class="bg-gray-50 p-8 border border-gray-100 lg:col-span-1 h-fit">
                        <div class="w-16 h-16 bg-black text-white rounded-full flex items-center justify-center text-2xl font-black mb-6">
                            <span x-text="(user.name || 'U').charAt(0).toUpperCase()"></span>
                        </div>
                        <h2 class="text-xl font-black uppercase tracking-tight mb-6">Mis Datos</h2>
                        <div class="space-y-4">
                            <div><p class="text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Nombre</p><p class="text-base font-semibold uppercase" x-text="user.name || 'Invitado'"></p></div>
                            <div><p class="text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Email</p><p class="text-base font-semibold" x-text="user.email || 'N/A'"></p></div>
                        </div>
                        <button @click="handleLogout()" class="mt-8 w-full border-2 border-black text-black py-3 text-xs font-bold uppercase tracking-widest hover:bg-black hover:text-white transition">Cerrar Sesión</button>
                    </div>

                    <div class="bg-white p-8 border border-gray-100 lg:col-span-2">
                        <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                            <h2 class="text-xl font-black uppercase tracking-tight">Mis Compras</h2>
                            <span class="text-[10px] font-bold bg-gray-100 text-gray-600 px-2 py-1 uppercase tracking-widest" x-text="userOrders.length + ' Órdenes'"></span>
                        </div>
                        <div class="space-y-4 max-h-[500px] overflow-y-auto custom-scroll pr-2">
                            <template x-for="order in userOrders" :key="order.id">
                                <div class="bg-gray-50 p-5 border border-gray-200 flex flex-col md:flex-row md:justify-between md:items-center hover:border-black transition gap-4">
                                    <div>
                                        <p class="font-bold font-mono text-sm mb-1" x-text="order.id"></p>
                                        <div class="flex items-center gap-2 text-[10px] text-gray-500 uppercase tracking-widest font-bold">
                                            <span x-text="order.date"></span><span>•</span>
                                            <span :class="order.status === 'Entregado' ? 'text-green-600' : 'text-blue-600'" x-text="order.status || 'Procesando'"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between md:justify-end gap-6 w-full md:w-auto mt-2 md:mt-0 border-t md:border-t-0 border-gray-200 pt-3 md:pt-0">
                                        <p class="font-black font-mono text-lg" x-text="'$' + (order.total || 0).toLocaleString()"></p>
                                        <button @click="openInvoice(order)" class="text-[10px] font-bold uppercase tracking-widest text-black border border-black px-4 py-2 hover:bg-black hover:text-white transition whitespace-nowrap">
                                            Ver Ticket
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="userOrders.length === 0">
                                <div class="text-center py-16 bg-gray-50 border border-gray-100">
                                    <i class="fas fa-shopping-bag text-4xl text-gray-200 mb-4"></i>
                                    <p class="text-gray-500 text-xs uppercase tracking-widest font-bold">Aún no tienes pedidos.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'registro'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-md mx-auto bg-white p-10 shadow-2xl border border-gray-100">
                <h1 class="text-3xl font-black uppercase tracking-tight mb-8 text-center">Crear Cuenta</h1>
                <form @submit.prevent="handleRegistro()" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Nombre</label>
                        <input type="text" required x-model="registroForm.name" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Email</label>
                        <input type="email" required x-model="registroForm.email" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Contraseña</label>
                        <input type="password" required minlength="12" maxlength="128" x-model="registroForm.password" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm">
                    </div>
                    <button type="submit" class="w-full bg-black text-white py-4 mt-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Registrarme</button>
                </form>
                <div class="mt-6 text-center border-t border-gray-100 pt-6">
                    <p class="text-[10px] uppercase font-bold text-gray-500 tracking-widest">¿Ya tienes cuenta? <button @click="navigateTo('login')" class="text-black font-black hover:underline ml-1">Inicia sesión</button></p>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'login'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-md mx-auto bg-white p-10 shadow-2xl border border-gray-100">
                <h1 class="text-3xl font-black uppercase tracking-tight mb-8 text-center">Iniciar Sesión</h1>
                <p class="text-xs text-gray-500 mb-6">Accede con tu cuenta. Los permisos se asignan por el equipo.</p>
                <form @submit.prevent="handleLogin()" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Email</label>
                        <input type="email" required x-model="loginForm.email" :disabled="isLoggingIn" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm disabled:opacity-50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Contraseña</label>
                        <input type="password" required x-model="loginForm.password" :disabled="isLoggingIn" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm disabled:opacity-50">
                    </div>
                    <button type="submit" :disabled="isLoggingIn" class="w-full bg-black text-white py-4 mt-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition disabled:opacity-70 flex items-center justify-center gap-2">
                        <span x-show="!isLoggingIn">Entrar</span>
                        <div x-show="isLoggingIn" class="flex gap-2 items-center"><svg class="w-4 h-4 spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Cargando...</span></div>
                    </button>
                </form>
                <div class="mt-6 text-center border-t border-gray-100 pt-6">
                    <p class="text-[10px] uppercase font-bold text-gray-500 tracking-widest">¿No tienes cuenta? <button @click="navigateTo('registro')" class="text-black font-black hover:underline ml-1">Regístrate</button></p>
                </div>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'contacto'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-20">
            <div class="max-w-2xl mx-auto">
                <h1 class="text-5xl md:text-6xl font-black uppercase tracking-tight mb-8">Contacto</h1>
                <form @submit.prevent="handleContactSubmit()" class="space-y-6 mb-12">
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Nombre</label><input type="text" required x-model="contactForm.name" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Email</label><input type="email" required x-model="contactForm.email" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-2 text-gray-600">Mensaje</label><textarea x-model="contactForm.message" rows="5" class="w-full px-4 py-3 border-2 border-gray-200 focus:border-black focus:outline-none text-sm resize-none"></textarea></div>
                    <button type="submit" class="w-full bg-black text-white py-4 text-sm font-bold uppercase tracking-widest hover:bg-gray-800 transition">Enviar Mensaje</button>
                </form>
            </div>
        </section>
    </div>

    <div x-show="currentRoute === 'nosotros'" x-cloak>
        <section class="max-w-screen-2xl mx-auto px-6 py-24">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-5xl md:text-7xl font-black uppercase tracking-tighter mb-10">Nosotros</h1>
                <div class="space-y-6 text-gray-700 leading-relaxed mb-12 font-medium">
                    <p class="text-xl font-bold border-l-4 border-black pl-4 py-2 text-black">Fundada en 2026, HFSTUDIOS representa la intersección del lujo y la cultura urbana.</p>
                    <h2 class="text-2xl font-black uppercase tracking-tighter mt-12 mb-4 border-b border-gray-100 pb-2 text-black">Misión</h2>
                    <p>Ofrecemos streetwear de alta calidad diseñado para individuos que se niegan a mezclarse con la multitud. Cada pieza es una declaración construida con atención obsesiva a los detalles.</p>
                    <h2 class="text-2xl font-black uppercase tracking-tighter mt-12 mb-4 border-b border-gray-100 pb-2 text-black">Horarios</h2>
                    <div class="bg-gray-50 border border-gray-200 p-6 shadow-sm w-fit">
                        <p class="text-xs font-bold uppercase tracking-widest text-black">Lunes a Sábado <span class="text-gray-500 font-medium ml-4">10:00 - 20:00</span></p>
                    </div>
                </div>
                <div class="mt-16">
                    <h2 class="text-2xl font-black uppercase tracking-tighter mb-6 text-black">Ubicación</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-4">Aguascalientes, México</p>
                    <div class="aspect-video bg-gray-100 border border-gray-200 flex items-center justify-center shadow-sm opacity-60">
                        <div class="text-center"><i class="fas fa-map-marker-alt text-4xl mb-3 text-black"></i><p class="text-[10px] font-bold uppercase tracking-widest">Mapa Interactivo</p></div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer x-show="currentRoute !== 'inicio' && currentRoute !== 'admin'" class="bg-black text-white py-16 mt-auto">
        <div class="max-w-screen-2xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div>
                    <h3 class="text-3xl font-black mb-4 tracking-tighter">HFSTUDIOS</h3>
                    <p class="text-xs text-gray-400 leading-relaxed font-medium">Streetwear premium para quienes se atreven a ser diferentes. Aguascalientes, MX.</p>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-widest mb-6 text-gray-500">Tienda</h4>
                    <ul class="space-y-3 text-xs font-bold uppercase tracking-wider text-gray-300">
                        <li><button @click="navigateTo('catalogo')" class="hover:text-white transition">Catálogo</button></li>
                        <li><button @click="navigateTo('campana')" class="hover:text-white transition">Campaña</button></li>
                        <li><button @click="navigateTo('promociones-front')" class="hover:text-white transition">Promociones</button></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-widest mb-6 text-gray-500">Comunidad</h4>
                    <ul class="space-y-3 text-xs font-bold uppercase tracking-wider text-gray-300">
                        <li><button @click="navigateTo('comunidad')" class="hover:text-white transition">Reseñas</button></li>
                        <li><button @click="navigateTo('nosotros')" class="hover:text-white transition">Nosotros</button></li>
                        <li><button @click="navigateTo('subasta')" class="hover:text-white transition text-red-500">Subastas (C2C)</button></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold uppercase tracking-widest mb-6 text-gray-500">Newsletter</h4>
                    <form @submit.prevent="subscribeNewsletter()" class="flex shadow-md border border-white/20">
                        <input type="email" required x-model="newsletterEmail" placeholder="TU EMAIL" class="flex-1 bg-white/5 px-4 py-3 text-[10px] font-bold uppercase tracking-widest text-white focus:outline-none focus:bg-white/10 transition">
                        <button type="submit" class="bg-white text-black px-6 py-3 text-sm font-black hover:bg-gray-200 transition">→</button>
                    </form>
                </div>
            </div>
            <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">© 2026 HF Studios. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>


    <div x-show="productEditorOpen" x-cloak class="fixed inset-0 z-[130] overflow-y-auto bg-black/70 p-4">
        <form @submit.prevent="saveProduct()" class="bg-white max-w-xl mx-auto my-8 p-8 space-y-4">
            <div class="flex justify-between"><h2 class="font-black text-xl">Producto de catálogo</h2><button type="button" @click="productEditorOpen=false">Cerrar</button></div>
            <label class="block">Nombre<input required x-model="productForm.name" maxlength="120" class="w-full border p-2"></label>
            <label class="block">Color<input x-model="productForm.color" class="w-full border p-2"></label>
            <label class="block">Categoría<select x-model="productForm.category" class="w-full border p-2"><option>Hoodie</option><option>T-Shirt</option><option>Pants</option><option>Accessory</option></select></label>
            <div class="grid grid-cols-2 gap-4"><label>Precio MXN<input required type="number" min="0.01" step="0.01" x-model="productForm.price" class="w-full border p-2"></label><label>Precio original<input type="number" min="0.01" step="0.01" x-model="productForm.originalPrice" class="w-full border p-2"></label></div>
            <label class="block">Existencias<input required type="number" min="0" step="1" x-model="productForm.stock" class="w-full border p-2"></label>
            <label class="block">URL de imagen<input required type="url" x-model="productForm.image" class="w-full border p-2"></label>
            <label class="block">Etiqueta<input x-model="productForm.badge" class="w-full border p-2"></label>
            <label class="block">Descripción<textarea required x-model="productForm.description" class="w-full border p-2"></textarea></label>
            <label><input type="checkbox" x-model="productForm.active"> Visible en catálogo</label>
            <button :disabled="isSaving" class="w-full bg-black text-white p-3">Guardar producto</button>
        </form>
    </div>

    <script src="/js/http.js"></script>
    <script src="/js/storefront.js"></script>
</body>
</html>
