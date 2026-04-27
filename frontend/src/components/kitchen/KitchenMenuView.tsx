'use client';

import { useEffect, useState } from 'react';
import { useAutoAnimate } from '@formkit/auto-animate/react';
import { getKitchenMenu, updateAvailability, requestRestock, KitchenMenuItem } from '@/lib/services/orders';

export default function KitchenMenuView() {
  const [items, setItems] = useState<KitchenMenuItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [toggling, setToggling] = useState<number | null>(null);
  const [restocking, setRestocking] = useState<number | null>(null);
  const [menuRef] = useAutoAnimate<HTMLDivElement>();

  useEffect(() => {
    fetchMenu();
    
    // Auto refresh every 5 seconds
    const interval = setInterval(() => {
      fetchMenu();
    }, 5000);
    
    return () => clearInterval(interval);
  }, []);

  async function fetchMenu() {
    const { data, error } = await getKitchenMenu();
    if (error) {
      setError(error);
    } else {
      setItems(data?.data ?? []);
    }
    setLoading(false);
  }

  async function handleToggle(item: KitchenMenuItem) {
    setToggling(item.id);
    const { data, error } = await updateAvailability(item.id, !item.is_available);
    if (error) {
      setError(error);
    } else if (data?.data) {
      setItems((prev) =>
        prev.map((i) => (i.id === item.id ? data.data : i))
      );
    }
    setToggling(null);
  }

  async function handleRestock(item: KitchenMenuItem) {
    const qtyStr = window.prompt(`How many units of ${item.name} do you need?`, '50');
    if (qtyStr === null) return; // User cancelled
    
    const qty = parseInt(qtyStr, 10);
    if (isNaN(qty) || qty <= 0) {
      alert('Please enter a valid positive quantity.');
      return;
    }

    setRestocking(item.id);
    const { data, error } = await requestRestock(item.id, qty);
    if (error) {
      setError(error);
    } else if (data?.data) {
      setItems((prev) =>
        prev.map((i) => (i.id === item.id ? data.data : i))
      );
    }
    setRestocking(null);
  }

  const grouped = items.reduce<Record<string, KitchenMenuItem[]>>((acc, item) => {
    const cat = item.category.name;
    if (!acc[cat]) acc[cat] = [];
    acc[cat].push(item);
    return acc;
  }, {});

  return (
    <div className="max-w-4xl mx-auto">

      {/* ── Staff Hero ── */}
      <div className="relative mb-8 rounded-3xl overflow-hidden bg-white border border-slate-200 shadow-sm">

        {/* Decorative glow — emerald for menu management */}
        <div className="absolute -top-16 -right-16 w-64 h-64 bg-emerald-500/8 rounded-full blur-3xl pointer-events-none" />
        <div className="absolute -bottom-10 -left-10 w-48 h-48 bg-emerald-600/5 rounded-full blur-2xl pointer-events-none" />

        <div className="relative px-8 py-6 flex items-center justify-between gap-6 flex-wrap">

          {/* Left: badge + title */}
          <div>
            <div className="inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-3 py-1 mb-3">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
              <span className="text-emerald-400 text-xs font-semibold uppercase tracking-widest">Kitchen Staff · Menu Control</span>
            </div>
            <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight leading-tight">
              Menu <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-emerald-600">Availability</span>
            </h1>
            <p className="text-slate-500 text-sm mt-1">Enable or disable items and request restocks instantly.</p>
          </div>

          {/* Right: stats + refresh */}
          <div className="flex items-center gap-5">
            <div className="flex items-center gap-6">
              <div className="text-center">
                <p className="text-2xl font-bold text-emerald-500">
                  {items.filter(i => i.is_available).length}
                </p>
                <p className="text-slate-500 text-xs mt-0.5">Available</p>
              </div>
              <div className="w-px h-10 bg-slate-200" />
              <div className="text-center">
                <p className="text-2xl font-bold text-red-500">
                  {items.filter(i => !i.is_available).length}
                </p>
                <p className="text-slate-500 text-xs mt-0.5">Unavailable</p>
              </div>
              <div className="w-px h-10 bg-slate-200" />
              <div className="text-center">
                <p className="text-2xl font-bold text-orange-500">
                  {items.filter(i => i.is_low_stock).length}
                </p>
                <p className="text-slate-500 text-xs mt-0.5">Low Stock</p>
              </div>
            </div>
            <button
              onClick={fetchMenu}
              className="flex items-center gap-2 px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-200 text-sm font-semibold rounded-xl transition"
            >
              ↻ Refresh
            </button>
          </div>

        </div>
      </div>

      {error && (
        <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
          {error}
        </div>
      )}

      {loading ? (
        <div className="space-y-8">
          {[1, 2].map(group => (
            <div key={group}>
              <div className="h-4 w-28 bg-slate-200 rounded-full animate-pulse mb-4"></div>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {[1, 2, 3].map(i => (
                  <div key={i} className="bg-white rounded-2xl overflow-hidden border border-slate-200 animate-pulse shadow-sm">
                    <div className="w-full h-40 bg-slate-100"></div>
                    <div className="p-5">
                      <div className="h-5 w-32 bg-slate-200 rounded-md mb-3"></div>
                      <div className="h-3 w-full bg-slate-100 rounded-md mb-2"></div>
                      <div className="h-3 w-2/3 bg-slate-100 rounded-md mb-4"></div>
                      <div className="h-10 w-full bg-slate-200 rounded-xl"></div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div ref={menuRef} className="space-y-8">
          {Object.entries(grouped).map(([category, categoryItems]) => (
            <div key={category}>
              <h3 className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
                {category}
              </h3>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {categoryItems.map((item) => (
                  <div
                    key={item.id}
                    className={`bg-white border rounded-2xl overflow-hidden flex flex-col transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-200/50 shadow-sm ${item.is_available ? 'border-slate-200 hover:border-slate-300' : 'border-slate-200 opacity-60 grayscale-[0.5]'
                      }`}
                  >
                    {/* Image */}
                    {item.image_url && (
                      <div className="w-full h-40 shrink-0 relative overflow-hidden">
                        <img
                          src={item.image_url}
                          alt={item.name}
                          className="w-full h-full object-cover transition-transform duration-500 hover:scale-105"
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent" />
                        <div className="absolute bottom-3 left-3 right-3 flex justify-between items-end">
                          <p className="text-white font-bold text-lg drop-shadow-md">৳{item.price}</p>
                          <p className="text-slate-100 text-sm font-medium bg-black/40 px-2 py-1 rounded-md backdrop-blur-sm">Stock: {item.stock_quantity}</p>
                        </div>
                      </div>
                    )}

                    {/* Details */}
                    <div className="flex-1 p-5 flex flex-col justify-between">
                      <div className="mb-4">
                        <div className="flex items-start justify-between gap-2 mb-2">
                          <p className="text-slate-900 font-semibold text-lg leading-tight">{item.name}</p>
                          <div className="flex flex-col gap-1 shrink-0 items-end">
                            {item.is_low_stock && item.is_available && (
                              <span className="px-2 py-0.5 bg-orange-50 text-orange-600 border border-orange-200 text-[10px] font-bold uppercase tracking-wide rounded-full">
                                Low Stock
                              </span>
                            )}
                            {!item.is_available && (
                              <span className="px-2 py-0.5 bg-red-50 text-red-600 border border-red-200 text-[10px] font-bold uppercase tracking-wide rounded-full">
                                Unavailable
                              </span>
                            )}
                          </div>
                        </div>
                        {item.description && (
                          <p className="text-slate-500 text-sm line-clamp-2">
                            {item.description}
                          </p>
                        )}
                        {/* Fallback for no image */}
                        {!item.image_url && (
                           <div className="flex items-center justify-between mt-3">
                             <p className="text-slate-900 font-bold text-lg">৳{item.price}</p>
                             <p className="text-slate-500 text-sm">Stock: {item.stock_quantity}</p>
                           </div>
                        )}
                      </div>

                      {/* Actions */}
                      <div className="flex flex-col gap-2 mt-4">
                        <button
                          onClick={() => handleToggle(item)}
                          disabled={toggling === item.id}
                          className={`w-full py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-200 active:scale-95 flex items-center justify-center gap-2 ${item.is_available
                            ? 'bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200'
                            : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white border border-emerald-200'
                            } ${toggling === item.id ? 'opacity-50 cursor-wait' : ''}`}
                        >
                          {toggling === item.id ? (
                            <>
                              <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                              </svg>
                              Updating...
                            </>
                          ) : (item.is_available ? 'Disable Item' : 'Activate Item')}
                        </button>

                        {item.stock_quantity === 0 && (
                          <button
                            onClick={() => handleRestock(item)}
                            disabled={item.needs_restock || restocking === item.id}
                            className={`w-full py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-200 flex items-center justify-center gap-2 ${
                              item.needs_restock
                                ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed'
                                : 'bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white border border-blue-200 active:scale-95'
                            } ${restocking === item.id ? 'opacity-50 cursor-wait' : ''}`}
                          >
                            {restocking === item.id ? (
                              <>
                                <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none"></circle>
                                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Requesting...
                              </>
                            ) : item.needs_restock ? (
                              <>
                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                                Restock Requested {item.requested_restock_quantity ? `(${item.requested_restock_quantity})` : ''}
                              </>
                            ) : (
                              'Request Restock'
                            )}
                          </button>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
