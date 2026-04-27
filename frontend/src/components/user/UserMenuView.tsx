'use client';

import { useEffect, useState, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import { useAutoAnimate } from '@formkit/auto-animate/react';
import { toast } from 'sonner';
import {
  getUserMenu,
  placeOrder,
  MenuCategory,
  CartItem,
  UserMenuItem,
} from '@/lib/services/orders';

type SortOption = 'default' | 'price_asc' | 'price_desc';

export default function UserMenuView() {
  const router = useRouter();
  const [categories, setCategories] = useState<MenuCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [cart, setCart] = useState<CartItem[]>([]);
  const [notes, setNotes] = useState('');
  const [placing, setPlacing] = useState(false);
  const [orderError, setOrderError] = useState('');
  const [search, setSearch] = useState('');
  const [sort, setSort] = useState<SortOption>('default');
  const [activeCategory, setActiveCategory] = useState<string>('All');
  const [flyingItems, setFlyingItems] = useState<{ id: number, startX: string, startY: string, endX: string, endY: string, image: string | null }[]>([]);
  
  const [menuContainerRef] = useAutoAnimate<HTMLDivElement>();
  const [cartRef] = useAutoAnimate<HTMLDivElement>();

  useEffect(() => {
    fetchMenu();
  }, []);

  async function fetchMenu() {
    const { data, error } = await getUserMenu();
    if (error) {
      setError(error);
    } else {
      setCategories(data?.data ?? []);
    }
    setLoading(false);
  }

  function addToCart(item: { id: number; name: string; price: string, image_path?: string | null }, e?: React.MouseEvent) {
    if (e) {
      const cartEl = document.getElementById('cart-container');
      if (cartEl) {
        const cartRect = cartEl.getBoundingClientRect();
        // Fallback for smaller screens to fly to the middle if cart isn't strictly right side
        const endX = cartRect.left + cartRect.width / 2 + 'px';
        const endY = cartRect.top + cartRect.height / 2 + 'px';
        const startX = e.clientX + 'px';
        const startY = e.clientY + 'px';
        const flyingId = Date.now() + Math.random();
        setFlyingItems(prev => [...prev, { id: flyingId, startX, startY, endX, endY, image: item.image_path || null }]);
        setTimeout(() => {
          setFlyingItems(prev => prev.filter(f => f.id !== flyingId));
        }, 800);
      }
    }

    setCart((prev) => {
      const existing = prev.find((c) => c.menu_item_id === item.id);
      if (existing) {
        return prev.map((c) =>
          c.menu_item_id === item.id
            ? { ...c, quantity: c.quantity + 1 }
            : c
        );
      }
      return [...prev, {
        menu_item_id: item.id,
        name: item.name,
        price: item.price,
        quantity: 1,
      }];
    });
    toast.success(`${item.name} added to cart`, { duration: 2000 });
  }

  function removeFromCart(menu_item_id: number) {
    setCart((prev) => {
      const existing = prev.find((c) => c.menu_item_id === menu_item_id);
      if (existing && existing.quantity > 1) {
        return prev.map((c) =>
          c.menu_item_id === menu_item_id
            ? { ...c, quantity: c.quantity - 1 }
            : c
        );
      }
      return prev.filter((c) => c.menu_item_id !== menu_item_id);
    });
  }

  function getQuantity(menu_item_id: number): number {
    return cart.find((c) => c.menu_item_id === menu_item_id)?.quantity ?? 0;
  }

  const cartTotal = cart.reduce(
    (sum, item) => sum + parseFloat(item.price) * item.quantity,
    0
  );

  async function handlePlaceOrder() {
    if (cart.length === 0) return;
    setOrderError('');
    setPlacing(true);

    const items = cart.map((c) => ({
      menu_item_id: c.menu_item_id,
      quantity: c.quantity,
    }));

    const { data, error } = await placeOrder(items, notes || undefined);

    if (error) {
      setOrderError(error);
      toast.error('Failed to place order');
      setPlacing(false);
      return;
    }

    const orderId = data?.data?.id;
    toast.success('Order placed successfully!');
    router.push(`/orders/${orderId}/confirmation`);
  }

  // ─── Search + Sort + Filter logic ──────────────────────────────
  const filteredCategories = useMemo(() => {
    let result = categories;

    // 1. Filter by Category
    if (activeCategory !== 'All') {
      result = result.filter(cat => cat.category_name === activeCategory);
    }

    // 2. Search and Sort require flattening
    if (search.trim() || sort !== 'default') {
      let allItems: (UserMenuItem & { category_name: string })[] = [];
      result.forEach((cat) => {
        cat.items.forEach((item) => {
          allItems.push({ ...item, category_name: cat.category_name });
        });
      });

      // Search
      if (search.trim()) {
        allItems = allItems.filter((item) =>
          item.name.toLowerCase().includes(search.toLowerCase())
        );
      }

      // Sort
      if (sort === 'price_asc') {
        allItems.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
      } else if (sort === 'price_desc') {
        allItems.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
      }

      if (allItems.length === 0) return [];

      let title = activeCategory !== 'All' ? activeCategory : 'All Items';
      if (search.trim()) {
        title = `Results for "${search}"`;
      } else if (sort === 'price_asc') {
        title = `${title} (Price: Low to High)`;
      } else if (sort === 'price_desc') {
        title = `${title} (Price: High to Low)`;
      }

      return [{
        category_id: 0,
        category_name: title,
        items: allItems,
      }];
    }

    return result;
  }, [categories, search, sort, activeCategory]);

  return (
    <div className="max-w-7xl mx-auto">
      <div className="flex gap-6">

        {/* Menu */}
        <div className="flex-1">

          {/* ── Hero Section ── */}
          <div className="relative mb-8 rounded-3xl overflow-hidden bg-white/70 backdrop-blur-md border border-slate-200 shadow-sm">

            {/* Decorative background glow */}
            <div className="absolute -top-16 -right-16 w-64 h-64 bg-orange-500/10 rounded-full blur-3xl pointer-events-none" />
            <div className="absolute -bottom-10 -left-10 w-48 h-48 bg-orange-600/5 rounded-full blur-2xl pointer-events-none" />

            <div className="relative px-8 pt-10 pb-8">

              {/* Badge */}
              <div className="inline-flex items-center gap-2 bg-orange-500/10 border border-orange-500/20 rounded-full px-3 py-1 mb-5">
                <span className="w-1.5 h-1.5 rounded-full bg-orange-400 animate-pulse" />
                <span className="text-orange-400 text-xs font-semibold uppercase tracking-widest">Betopia Kitchen · Live Menu</span>
              </div>

              <div className="flex items-end justify-between gap-6 flex-wrap">
                {/* Text */}
                <div>
                  <h1 className="text-4xl font-extrabold text-slate-900 tracking-tight leading-tight mb-2">
                    What are you
                    <span className="block text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-orange-600">
                      craving today?
                    </span>
                  </h1>
                  <p className="text-slate-500 text-sm max-w-sm leading-relaxed">
                    Browse our fresh menu, add items to your order, and we'll have it ready for you in no time.
                  </p>
                </div>

                {/* Stats */}
                <div className="flex items-center gap-6 shrink-0">
                  <div className="text-center">
                    <p className="text-2xl font-bold text-slate-900">{categories.reduce((s, c) => s + c.items.length, 0)}</p>
                    <p className="text-slate-500 text-xs mt-0.5">Menu Items</p>
                  </div>
                  <div className="w-px h-10 bg-slate-200" />
                  <div className="text-center">
                    <p className="text-2xl font-bold text-slate-900">{categories.length}</p>
                    <p className="text-slate-500 text-xs mt-0.5">Categories</p>
                  </div>
                  <div className="w-px h-10 bg-slate-200" />
                  <div className="text-center">
                    <p className="text-2xl font-bold text-orange-500">{cart.length}</p>
                    <p className="text-slate-500 text-xs mt-0.5">In Cart</p>
                  </div>
                </div>
              </div>

              {/* Integrated Search Bar */}
              <div className="mt-6 relative max-w-lg">
                <span className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</span>
                  <input
                    type="text"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search dishes, drinks, desserts..."
                    className="w-full bg-white/50 backdrop-blur-sm border border-slate-200 text-slate-900 placeholder-slate-400 rounded-2xl pl-10 pr-10 py-3 text-sm focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500/50 focus:bg-white transition-all shadow-sm"
                  />
                {search && (
                  <button
                    onClick={() => setSearch('')}
                    className="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition text-xs"
                  >
                    ✕
                  </button>
                )}
              </div>

            </div>
          </div>

          {/* Category Filter Pills */}
          <div className="flex gap-2 mb-6 overflow-x-auto pb-2 scrollbar-none">
            {['All', ...categories.map(c => c.category_name)].map((cat) => (
              <button
                key={cat}
                onClick={() => setActiveCategory(cat)}
                className={`whitespace-nowrap px-5 py-2 rounded-full text-sm font-semibold border transition-all ${
                  activeCategory === cat
                    ? 'bg-orange-500 border-orange-500 text-white shadow-md shadow-orange-500/20'
                    : 'bg-white border-slate-200 text-slate-600 hover:border-orange-200 hover:text-orange-600 hover:bg-orange-50 shadow-sm'
                }`}
              >
                {cat}
              </button>
            ))}
          </div>

          {/* Sort + Clear toolbar */}
          <div className="flex items-center gap-3 mb-6">
            <select
              value={sort}
              onChange={(e) => setSort(e.target.value as SortOption)}
              className="bg-white/70 backdrop-blur-md border border-slate-200 text-slate-700 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-orange-500 transition cursor-pointer shadow-sm"
            >
              <option value="default">Sort by price</option>
              <option value="price_asc">Price: Low → High</option>
              <option value="price_desc">Price: High → Low</option>
            </select>

            {(search || sort !== 'default' || activeCategory !== 'All') && (
              <button
                onClick={() => { setSearch(''); setSort('default'); setActiveCategory('All'); }}
                className="flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 hover:text-slate-900 text-sm rounded-xl border border-slate-200 transition shadow-sm"
              >
                ✕ Clear filters
              </button>
            )}

            {(search || activeCategory !== 'All') && (
              <p className="text-slate-500 text-sm ml-auto">
                {search && <span>Results for <span className="text-slate-900 font-medium">"{search}"</span></span>}
                {!search && activeCategory !== 'All' && <span>Showing <span className="text-slate-900 font-medium">{activeCategory}</span></span>}
              </p>
            )}
          </div>

          {error && (
            <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
              {error}
            </div>
          )}

          {loading ? (
            <div className="space-y-12">
              {[1, 2].map((group) => (
                <div key={group}>
                  <div className="flex justify-center mb-6">
                    <div className="h-6 w-32 bg-slate-200 rounded-full animate-pulse"></div>
                  </div>
                  <div className="grid grid-cols-2 md:grid-cols-3 gap-6">
                    {[1, 2, 3].map(item => (
                      <div key={item} className="flex flex-col items-center">
                         <div className="w-full aspect-square rounded-lg bg-slate-200 animate-pulse mb-3"></div>
                         <div className="h-4 w-24 bg-slate-200 rounded-md animate-pulse mb-2"></div>
                         <div className="h-3 w-32 bg-slate-200 rounded-md animate-pulse mb-3"></div>
                         <div className="h-5 w-16 bg-slate-200 rounded-md animate-pulse mb-3"></div>
                         <div className="h-8 w-28 bg-slate-200 rounded-full animate-pulse"></div>
                      </div>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          ) : filteredCategories.length === 0 ? (
            <div className="text-center py-20 bg-white/70 backdrop-blur-md border border-slate-200 rounded-3xl shadow-sm">
              <p className="text-slate-500 text-lg">No items found</p>
              <p className="text-slate-400 text-sm mt-1">Try a different search term</p>
              <button
                onClick={() => { setSearch(''); setSort('default'); setActiveCategory('All'); }}
                className="mt-4 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm rounded-lg transition"
              >
                Clear search
              </button>
            </div>
          ) : (
            <div ref={menuContainerRef}>
              {filteredCategories.map((cat) => (
                <div key={cat.category_id} className="mb-12">

                  {/* Category header */}
                  <div className="text-center mb-6">
                    <p className="text-slate-300 text-lg">···</p>
                    <h2 className="text-xl font-bold text-slate-900 uppercase tracking-widest mt-1">
                      {cat.category_name}
                    </h2>
                    <p className="text-slate-300 text-lg">···</p>
                  </div>

                  {/* Items grid */}
                  <div className="grid grid-cols-2 md:grid-cols-3 gap-6">
                    {cat.items.map((item) => {
                      const qty = getQuantity(item.id);
                      return (
                        <div key={item.id} className="flex flex-col items-center text-center">

                          <div className="group relative w-full aspect-square rounded-2xl overflow-hidden mb-3 bg-white border border-slate-200 shadow-sm transition-all duration-300 hover:shadow-lg hover:border-orange-300 p-1">
                            <div className="w-full h-full rounded-[14px] overflow-hidden">
                              {item.image_path ? (
                                <img
                                  src={item.image_path}
                                  alt={item.name}
                                  className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                />
                              ) : (
                                <div className="w-full h-full flex items-center justify-center text-slate-400 text-sm">
                                  No image
                                </div>
                              )}
                            </div>
                          </div>

                          {/* Name */}
                          <p className="text-slate-900 font-semibold text-sm leading-tight mb-1">
                            {item.name}
                          </p>

                          {/* Description */}
                          {item.description && (
                            <p className="text-slate-500 text-xs leading-relaxed mb-2 line-clamp-2 px-1">
                              {item.description}
                            </p>
                          )}

                          {/* Price */}
                          <p className="text-orange-500 font-bold text-base mb-3">
                            ৳{item.price}
                          </p>

                          {/* Add / Quantity control */}
                          {qty === 0 ? (
                            <button
                              onClick={(e) => addToCart(item, e)}
                              className="px-5 py-1.5 border border-orange-500 text-orange-400 hover:bg-orange-500 hover:text-white text-xs font-semibold rounded-full transition"
                            >
                              Add to Order
                            </button>
                          ) : (
                            <div className="flex items-center gap-3">
                              <button
                                onClick={() => removeFromCart(item.id)}
                                className="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition border border-slate-200"
                              >
                                −
                              </button>
                              <span className="text-slate-900 font-semibold text-sm w-4 text-center">
                                {qty}
                              </span>
                              <button
                                onClick={(e) => addToCart(item, e)}
                                className="w-7 h-7 rounded-full bg-orange-500 hover:bg-orange-600 text-white flex items-center justify-center transition shadow-sm"
                              >
                                +
                              </button>
                            </div>
                          )}
                        </div>
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Cart — right side */}
        <div className="w-80 shrink-0 hidden md:block">
          <div id="cart-container" className="bg-white/70 backdrop-blur-md border border-slate-200 rounded-2xl p-5 sticky top-24 shadow-sm">
            <h3 className="text-slate-900 font-bold text-lg mb-4">Your Order</h3>

            {cart.length === 0 ? (
              <div className="flex flex-col items-center justify-center py-10 text-center">
                <svg className="w-20 h-20 text-slate-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <p className="text-slate-500 text-sm font-medium">Your cart is hungry!</p>
                <p className="text-slate-400 text-xs mt-1">Browse the menu to add some items.</p>
              </div>
            ) : (
              <>
                <div ref={cartRef} className="space-y-3 mb-4">
                  {cart.map((item) => (
                    <div key={item.menu_item_id} className="flex justify-between items-center">
                      <div>
                        <p className="text-slate-900 text-sm font-medium">{item.name}</p>
                        <p className="text-slate-500 text-xs">
                          ৳{item.price} x {item.quantity}
                        </p>
                      </div>
                      <div className="flex items-center gap-2">
                        <button
                          onClick={() => removeFromCart(item.menu_item_id)}
                          className="w-6 h-6 rounded-full bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-600 text-xs flex items-center justify-center transition"
                        >
                          −
                        </button>
                        <span className="text-slate-900 text-sm font-semibold w-4 text-center">
                          {item.quantity}
                        </span>
                        <button
                          onClick={(e) => addToCart({ id: item.menu_item_id, name: item.name, price: item.price }, e)}
                          className="w-6 h-6 rounded-full bg-orange-500 hover:bg-orange-600 text-white text-xs flex items-center justify-center transition shadow-sm"
                        >
                          +
                        </button>
                      </div>
                    </div>
                  ))}
                </div>

                <div className="border-t border-slate-200 pt-4 mb-4">
                  <div className="flex justify-between">
                    <span className="text-slate-500 text-sm">Total</span>
                    <span className="text-slate-900 font-bold">৳{cartTotal.toFixed(2)}</span>
                  </div>
                </div>

                <div className="mb-4">
                  <label className="block text-sm font-medium text-slate-700 mb-1.5">
                    Notes (optional)
                  </label>
                  <textarea
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                    placeholder="e.g. Less spicy please"
                    rows={2}
                    className="w-full bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition resize-none"
                  />
                </div>

                {orderError && (
                  <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-3 py-2 mb-4">
                    {orderError}
                  </div>
                )}

                <button
                  onClick={handlePlaceOrder}
                  disabled={placing}
                  className="w-full bg-orange-500 hover:bg-orange-600 disabled:bg-orange-500/50 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition"
                >
                  {placing ? 'Placing order...' : 'Place Order'}
                </button>
              </>
            )}
          </div>
        </div>

      </div>

      {/* Flying Items overlay */}
      {flyingItems.map(item => (
        <div
          key={item.id}
          className="animate-fly w-16 h-16 rounded-full overflow-hidden shadow-xl border-2 border-white bg-white flex items-center justify-center"
          style={{ '--startX': item.startX, '--startY': item.startY, '--endX': item.endX, '--endY': item.endY } as React.CSSProperties}
        >
          {item.image ? (
            <img src={item.image} alt="flying" className="w-full h-full object-cover" />
          ) : (
            <div className="w-full h-full bg-orange-100 flex items-center justify-center text-orange-500 text-xl font-bold">😋</div>
          )}
        </div>
      ))}
    </div>
  );
}