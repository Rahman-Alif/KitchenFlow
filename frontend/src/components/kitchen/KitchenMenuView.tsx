'use client';

import { useEffect, useState } from 'react';
import { getKitchenMenu, updateAvailability, requestRestock, KitchenMenuItem } from '@/lib/services/orders';

export default function KitchenMenuView() {
  const [items, setItems] = useState<KitchenMenuItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [toggling, setToggling] = useState<number | null>(null);
  const [restocking, setRestocking] = useState<number | null>(null);

  useEffect(() => {
    fetchMenu();
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

      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-2xl font-bold text-white">Menu</h2>
          <p className="text-zinc-400 text-sm mt-1">Toggle item availability</p>
        </div>
        <button
          onClick={fetchMenu}
          className="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm rounded-lg transition"
        >
          Refresh
        </button>
      </div>

      {error && (
        <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg px-4 py-3 mb-6">
          {error}
        </div>
      )}

      {loading ? (
        <div className="text-zinc-500 text-sm">Loading menu...</div>
      ) : (
        <div className="space-y-8">
          {Object.entries(grouped).map(([category, categoryItems]) => (
            <div key={category}>
              <h3 className="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">
                {category}
              </h3>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {categoryItems.map((item) => (
                  <div
                    key={item.id}
                    className={`bg-zinc-900/50 backdrop-blur-md border rounded-2xl overflow-hidden flex flex-col transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-black/50 ${item.is_available ? 'border-zinc-800 hover:border-zinc-700' : 'border-zinc-800 opacity-60 grayscale-[0.5]'
                      }`}
                  >
                    {/* Image */}
                    {(item as any).image_path && (
                      <div className="w-full h-40 shrink-0 relative overflow-hidden">
                        <img
                          src={(item as any).image_path}
                          alt={item.name}
                          className="w-full h-full object-cover transition-transform duration-500 hover:scale-105"
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-zinc-900/80 to-transparent" />
                        <div className="absolute bottom-3 left-3 right-3 flex justify-between items-end">
                          <p className="text-zinc-100 font-bold text-lg drop-shadow-md">৳{item.price}</p>
                          <p className="text-zinc-300 text-sm font-medium bg-black/40 px-2 py-1 rounded-md backdrop-blur-sm">Stock: {item.stock_quantity}</p>
                        </div>
                      </div>
                    )}

                    {/* Details */}
                    <div className="flex-1 p-5 flex flex-col justify-between">
                      <div className="mb-4">
                        <div className="flex items-start justify-between gap-2 mb-2">
                          <p className="text-white font-semibold text-lg leading-tight">{item.name}</p>
                          <div className="flex flex-col gap-1 shrink-0 items-end">
                            {item.is_low_stock && item.is_available && (
                              <span className="px-2 py-0.5 bg-orange-500/20 text-orange-400 text-[10px] font-bold uppercase tracking-wide rounded-full">
                                Low Stock
                              </span>
                            )}
                            {!item.is_available && (
                              <span className="px-2 py-0.5 bg-red-500/20 text-red-400 text-[10px] font-bold uppercase tracking-wide rounded-full">
                                Unavailable
                              </span>
                            )}
                          </div>
                        </div>
                        {(item as any).description && (
                          <p className="text-zinc-400 text-sm line-clamp-2">
                            {(item as any).description}
                          </p>
                        )}
                        {/* Fallback for no image */}
                        {!(item as any).image_path && (
                           <div className="flex items-center justify-between mt-3">
                             <p className="text-zinc-100 font-bold text-lg">৳{item.price}</p>
                             <p className="text-zinc-400 text-sm">Stock: {item.stock_quantity}</p>
                           </div>
                        )}
                      </div>

                      {/* Actions */}
                      <div className="flex flex-col gap-2 mt-4">
                        <button
                          onClick={() => handleToggle(item)}
                          disabled={toggling === item.id}
                          className={`w-full py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-200 active:scale-95 flex items-center justify-center gap-2 ${item.is_available
                            ? 'bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white border border-red-500/20'
                            : 'bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500 hover:text-white border border-emerald-500/20'
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
                                ? 'bg-zinc-800 text-zinc-500 border border-zinc-700 cursor-not-allowed'
                                : 'bg-blue-500/10 text-blue-500 hover:bg-blue-500 hover:text-white border border-blue-500/20 active:scale-95'
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
