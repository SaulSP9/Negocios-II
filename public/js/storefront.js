document.addEventListener('alpine:init', () => {
            Alpine.data('app', () => ({
                currentRoute: 'inicio',
                searchQuery: '',
                toast: { show: false, message: '' },
                mobileMenuOpen: false,
                landingEmail: '', newsletterEmail: '',
                contactForm: { name: '', email: '', message: '' },
                
                get isAnyModalOpen() {
                    return this.cartOpen || this.checkoutOpen || this.productDetailOpen || 
                           this.orderSuccessOpen || this.invoiceModalOpen || this.editModalOpen || 
                           this.mobileMenuOpen || this.productEditorOpen;
                },
                
                allProducts: [],
                selectedCategory: 'All', selectedProduct: {}, productDetailOpen: false,
                
                get filteredProducts() {
                    let p = this.allProducts.filter(p => p.active);
                    if (this.searchQuery.trim()) {
                        const q = this.searchQuery.toLowerCase().trim();
                        p = p.filter(x => (x.name || '').toLowerCase().includes(q) || (x.category || '').toLowerCase().includes(q) || (x.color || '').toLowerCase().includes(q));
                    } else {
                        if (this.selectedCategory === 'Promociones') p = p.filter(x => x.badge && x.badge.includes('PROMO'));
                        else if (this.selectedCategory !== 'All') p = p.filter(x => x.category === this.selectedCategory);
                    }
                    return p;
                },
                
                cart: [], cartOpen: false, discountCode: '', discountApplied: false,
                
                checkoutOpen: false, checkoutStep: 1, paymentMethod: 'tarjeta', checkoutForm: { address: '', city: '', zip: '' },
                isCheckingOut: false, orderSuccessOpen: false, orderNumber: '', lastPurchaseSummary: [],

                get cartTotalItems() { return this.cart.reduce((acc, item) => acc + (Number(item.quantity) || 1), 0); },
                get cartCount() { return this.cart.length; },
                get cartTotalSinDescuento() { return this.cart.reduce((t, item) => t + ((Number(item.price) || 0) * (Number(item.quantity) || 1)), 0); },
                get cartTotal() { return this.discountApplied ? this.cartTotalSinDescuento * 0.9 : this.cartTotalSinDescuento; },
                
                userOrders: [],
                invoiceModalOpen: false, selectedOrder: null,

                isLoggedIn: false, isAdmin: false, loginRole: 'user', isLoggingIn: false,
                user: { name: '', email: '' }, registroForm: { name: '', email: '', password: '' }, loginForm: { email: '', password: '' },

                userProducts: [],
                publishForm: { name: '', price: '', description: '', image: '' },
                editModalOpen: false, editingIndex: -1, editForm: { name: '', price: '', description: '', image: '' },
                
                newComment: '', comments: [],

                auction: {timerSeconds:0, product:{name:'',image:'',currentBid:0},offers:[],newOffer:''},
                adminTab: 'pedidos', adminStats:{sales:'$0',orders:0,users:0},
                adminOrdersList: [], adminInvoicesList: [], adminUsersList: [],
                checkoutKey: '', isSaving: false, ready:false,
                productEditorOpen:false, productEditingId:null,
                productForm:{name:'',color:'',category:'Hoodie',price:'',originalPrice:'',stock:0,image:'',badge:'',description:'',active:true},
                get isStaff() {return ['admin','usuario'].includes(this.user.role);},
                get formattedTimer() {
                    const h = Math.floor(this.auction.timerSeconds / 3600).toString().padStart(2, '0');
                    const m = Math.floor((this.auction.timerSeconds % 3600) / 60).toString().padStart(2, '0');
                    const s = (this.auction.timerSeconds % 60).toString().padStart(2, '0');
                    return `${h}:${m}:${s}`;
                },

                async init() {
                    try {
                        const savedCart=JSON.parse(localStorage.getItem('hfstudios_cart')||'[]');
                        this.cart=Array.isArray(savedCart)?savedCart.filter(i=>Number.isInteger(i.id)&&Number.isInteger(i.quantity)&&i.quantity>0&&i.quantity<=99).map(i=>({...i,cartItemId:i.id})):[];
                    } catch {this.cart=[];}
                    localStorage.removeItem('hfstudios_user');
                    this.$watch('cart',value=>{try{localStorage.setItem('hfstudios_cart',JSON.stringify(value));}catch{}});
                    try {
                        const session=await HF.request('/sesion'); this.setSession(session.user);
                        await this.refreshPublic(); if(this.isLoggedIn) await this.refreshPrivate();
                        if(new URLSearchParams(location.search).get('vista')==='login') this.currentRoute='login';
                    } catch(e) {this.showToast(e.message);} finally {this.ready=true;}
                    this.timer=setInterval(()=>{if(this.auction.timerSeconds>0)this.auction.timerSeconds--;},1000);
                },
                destroy(){clearInterval(this.timer);},
                setSession(user){this.user=user||{name:'',email:''};this.isLoggedIn=!!user;this.isAdmin=user?.role==='admin';},
                async refreshPublic(){
                    [this.allProducts,this.comments]=await Promise.all([HF.request('/tienda/productos'),HF.request('/tienda/comentarios')]);
                    const auction=await HF.request('/tienda/subasta');this.auction={...auction,newOffer:this.auction.newOffer||''};
                    this.cart=this.cart.filter(i=>this.allProducts.some(p=>p.id===i.id&&p.active)).map(i=>({...i,...this.allProducts.find(p=>p.id===i.id),quantity:i.quantity,cartItemId:i.id}));
                },
                async refreshPrivate(){
                    this.userOrders=await HF.request('/tienda/pedidos');this.userProducts=await HF.request('/tienda/publicaciones');
                    if(this.isAdmin){this.adminOrdersList=this.userOrders;this.adminStats=await HF.request('/tienda/estadisticas');this.adminInvoicesList=this.userOrders.filter(o=>o.status!=='Cancelado').map(o=>({...o,orderId:o.id}));}
                },
                navigateTo(route) {
                    if(route==='admin'&&!this.isAdmin){this.showToast('Acceso solo para administradores.');return;}
                    if(['perfil','mis-publicaciones','publicar','subasta'].includes(route)&&!this.isLoggedIn){this.currentRoute='login';this.showToast('Inicia sesión para continuar.');return;}
                    if(route==='crm'||route==='scm'){if(this.isStaff)location.href='/'+route;return;}
                    this.currentRoute=route;window.scrollTo({top:0,behavior:'smooth'});
                },
                showToast(message) { this.toast.message = message; this.toast.show = true; setTimeout(() => { this.toast.show = false; }, 3500); },
                async subscribeNewsletter(){try{await HF.request('/tienda/suscripciones','POST',{email:this.newsletterEmail});this.newsletterEmail='';this.showToast('Suscripción guardada.');}catch(e){this.showToast(e.message);}},
                async handleLandingSubmit(){try{await HF.request('/tienda/suscripciones','POST',{email:this.landingEmail});this.showToast('Suscripción guardada.');this.landingEmail='';this.navigateTo('catalogo');}catch(e){this.showToast(e.message);}},
                viewProductDetail(product) { this.selectedProduct = product; this.productDetailOpen = true; },
                
                addToCart(product) {
                    const item=this.cart.find(i=>i.id===product.id);
                    if(!product.active||product.stock<1||(item&&item.quantity>=Math.min(product.stock,99))){this.showToast('Existencias insuficientes.');return;}
                    if(item)item.quantity++;else this.cart.push({...product,cartItemId:product.id,quantity:1});
                    this.showToast('Agregado a la bolsa');this.cartOpen=true;
                },
                increaseQty(index){const i=this.cart[index];if(i.quantity<Math.min(i.stock,99))i.quantity++;else this.showToast('Límite de existencias.');},
                decreaseQty(index){if(this.cart[index].quantity>1)this.cart[index].quantity--;else this.removeFromCart(index);},
                removeFromCart(index){this.cart.splice(index,1);},
                applyDiscount(){if(this.discountCode.trim().toUpperCase()!=='HF10'){this.showToast('Código inválido.');return;}this.discountApplied=true;this.discountCode='';this.showToast('Descuento HF10 aplicado.');},
                handleCheckout(){if(!this.isLoggedIn){this.cartOpen=false;this.navigateTo('login');this.showToast('Inicia sesión para registrar tu pedido.');return;}if(!this.cart.length)return;this.checkoutKey=crypto.randomUUID();this.cartOpen=false;this.checkoutOpen=true;this.checkoutStep=1;},
                async processFinalPayment(){
                    if(this.isCheckingOut)return;this.isCheckingOut=true;
                    try{
                        const order=await HF.request('/tienda/pedidos','POST',{items:this.cart.map(i=>({id:i.id,quantity:i.quantity})),...this.checkoutForm,discount_code:this.discountApplied?'HF10':null,checkout_key:this.checkoutKey});
                        this.orderNumber=order.id;this.lastPurchaseSummary=order.items;this.cart=[];this.discountApplied=false;this.checkoutOpen=false;this.orderSuccessOpen=true;
                        await this.refreshPublic();await this.refreshPrivate();
                    }catch(e){this.showToast(e.message);}finally{this.isCheckingOut=false;}
                },
                openInvoice(order) { this.selectedOrder = order; this.invoiceModalOpen = true; },
                
                downloadInvoice(order=this.selectedOrder) {
                    if(!order)return;
                    try {
                        const {jsPDF}=window.jspdf;const doc=new jsPDF();
                        doc.setFont('courier','bold');doc.setFontSize(24);doc.text('HFSTUDIOS',105,20,{align:'center'});
                        doc.setFont('courier','normal');doc.setFontSize(10);doc.text('Comprobante de pedido · Sin valor fiscal',105,28,{align:'center'});
                        doc.setFontSize(11);doc.text('Cliente: '+(order.user||this.user.name),20,42);
                        doc.setFontSize(9);doc.text('Folio: '+order.id,20,51);doc.text('Fecha: '+order.date,20,59);doc.text('Estado: '+order.status,20,67);
                        doc.autoTable({startY:76,head:[['CANT.','PRODUCTO','PRECIO MXN','TOTAL MXN']],body:order.items.map(i=>[i.quantity,i.name,Number(i.price).toFixed(2),(i.price*i.quantity).toFixed(2)]),theme:'striped',styles:{font:'courier'},headStyles:{fillColor:[20,20,20]},margin:{left:20,right:20}});
                        let y=doc.lastAutoTable.finalY+16;if(y>260){doc.addPage();y=25;}
                        doc.setFontSize(10);if(order.discount)doc.text('Descuento: $'+Number(order.discount).toFixed(2)+' MXN',20,y-7);
                        doc.setFont('courier','bold');doc.setFontSize(14);doc.text('Total del pedido:',20,y);doc.text('$'+Number(order.total).toFixed(2)+' MXN',190,y,{align:'right'});
                        doc.setFont('courier','normal');doc.setFontSize(9);doc.text('Este comprobante no acredita un cobro ni sustituye una factura fiscal.',20,y+12);
                        doc.save('HFSTUDIOS_'+order.id+'.pdf');this.showToast('Comprobante descargado.');
                    } catch(e){this.showToast('No se pudo generar el comprobante: '+e.message);}
                },

                async handlePublishSubmit(){if(this.isSaving)return;this.isSaving=true;try{await HF.request('/tienda/publicaciones','POST',this.publishForm);this.publishForm={name:'',price:'',description:'',image:''};await this.refreshPrivate();this.showToast('Publicación guardada.');this.navigateTo('mis-publicaciones');}catch(e){this.showToast(e.message);}finally{this.isSaving=false;}},
                async deleteUserProduct(index){if(!confirm('¿Eliminar publicación?'))return;try{await HF.request('/tienda/publicaciones/'+this.userProducts[index].id,'DELETE');await this.refreshPrivate();this.showToast('Publicación eliminada.');}catch(e){this.showToast(e.message);}},
                openEditModal(product,index){this.editingIndex=index;this.editForm={...product};this.editModalOpen=true;},
                async saveEdit(){if(this.isSaving)return;this.isSaving=true;try{await HF.request('/tienda/publicaciones/'+this.editForm.id,'PUT',this.editForm);await this.refreshPrivate();this.editModalOpen=false;this.showToast('Publicación actualizada.');}catch(e){this.showToast(e.message);}finally{this.isSaving=false;}},
                async handleLogin(){
                    if(this.isLoggingIn)return;this.isLoggingIn=true;
                    try{const result=await HF.request('/login','POST',this.loginForm);this.setSession(result.user);this.loginForm={email:'',password:''};await this.refreshPublic();await this.refreshPrivate();this.showToast('Sesión iniciada.');if(this.isStaff)location.href='/crm';else this.navigateTo('perfil');}
                    catch(e){this.showToast(e.message);}finally{this.isLoggingIn=false;}
                },
                async handleLogout(){try{await HF.request('/logout','POST',{});this.setSession(null);this.userOrders=[];this.userProducts=[];this.adminOrdersList=[];this.adminInvoicesList=[];this.adminStats={sales:'$0',orders:0,users:0};this.navigateTo('inicio');await this.refreshPublic();this.showToast('Sesión finalizada.');}catch(e){this.showToast(e.message);}},
                async handleRegistro(){if(this.isSaving)return;this.isSaving=true;try{const result=await HF.request('/registro','POST',this.registroForm);this.setSession(result.user);this.registroForm={name:'',email:'',password:''};await this.refreshPrivate();this.navigateTo('perfil');this.showToast('Cuenta creada.');}catch(e){this.showToast(e.message);}finally{this.isSaving=false;}},
                async handleContactSubmit(){try{await HF.request('/tienda/contacto','POST',this.contactForm);this.contactForm={name:'',email:'',message:''};this.showToast('Mensaje guardado para atención del equipo.');}catch(e){this.showToast(e.message);}},
                async addComment(){try{this.comments=await HF.request('/tienda/comentarios','POST',{text:this.newComment});this.newComment='';this.showToast('Reseña guardada.');}catch(e){this.showToast(e.message);}},
                async handleOfferSubmit(){try{const result=await HF.request('/tienda/subastas/'+this.auction.id+'/ofertas','POST',{amount:this.auction.newOffer});this.auction={...result,newOffer:''};this.showToast('Oferta registrada.');}catch(e){this.showToast(e.message);await this.refreshPublic().catch(()=>{});}},
                async changeOrderStatus(order,status){try{await HF.request('/tienda/pedidos/'+order.database_id+'/estado','PUT',{status});await this.refreshPrivate();await this.refreshPublic();this.showToast('Estado actualizado.');}catch(e){this.showToast(e.message);}},
                openProductEditor(product=null){this.productEditingId=product?.id||null;this.productForm=product?{...product}:{name:'',color:'',category:'Hoodie',price:'',originalPrice:'',stock:0,image:'',badge:'',description:'',active:true};this.productEditorOpen=true;},
                async saveProduct(){if(this.isSaving)return;this.isSaving=true;try{const body={...this.productForm,stock:Number(this.productForm.stock),originalPrice:this.productForm.originalPrice||null};await HF.request('/tienda/productos'+(this.productEditingId?'/'+this.productEditingId:''),this.productEditingId?'PUT':'POST',body);await this.refreshPublic();this.productEditorOpen=false;this.showToast('Producto guardado.');}catch(e){this.showToast(e.message);}finally{this.isSaving=false;}},
                async toggleProduct(product){try{await HF.request('/tienda/productos/'+product.id+'/visibilidad','PATCH',{});await this.refreshPublic();this.showToast('Visibilidad actualizada.');}catch(e){this.showToast(e.message);}}
            }));
        });
