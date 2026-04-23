'use client';

import { useEffect, useState } from 'react';
import { getKitchenMenu, updateAvailability, KitchenMenuItem } from '@/lib/services/orders';

export default function KitchenMenuView() {
  const [items, setItems]       = useState<KitchenMenuItem[]>([]);
  const [loading, setLoading]   = useState(true);
  const [error, setError]       = useState('');
  const [toggling, setToggling] = useState<number | null>(null);

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
              <div className="space-y-2">
                {categoryItems.map((item) => (
                  <div
                    key={item.id}
                    className={`bg-zinc-900 border rounded-xl overflow-hidden flex transition ${
                      item.is_available ? 'border-zinc-800' : 'border-zinc-800 opacity-60'
                    }`}
                  >
                    {/* Image */}
                    {(item as any).image_path && (
                      <div className="w-24 h-24 shrink-0">
                        <img
                          src={(item as any).image_path}
                          alt={item.name}
                          className="w-full h-full object-cover"
                        />
                      </div>
                    )}

                    {/* Details */}
                    <div className="flex-1 p-4 flex items-center justify-between">
                      <div className="flex-1 mr-4">
                        <div className="flex items-center gap-2 flex-wrap">
                          <p className="text-white font-medium">{item.name}</p>
                          {item.is_low_stock && item.is_available && (
                            <span className="px-2 py-0.5 bg-orange-500/10 border border-orange-500/20 text-orange-400 text-xs rounded-full">
                              Low Stock
                            </span>
                          )}
                          {!item.is_available && (
                            <span className="px-2 py-0.5 bg-red-500/10 border border-red-500/20 text-red-400 text-xs rounded-full">
                              Unavailable
                            </span>
                          )}
                        </div>
                        {(item as any).description && (
                          <p className="text-zinc-500 text-xs mt-0.5 line-clamp-2">
                            {(item as any).description}
                          </p>
                        )}
                        <div className="flex items-center gap-4 mt-1">
                          <p className="text-zinc-400 text-sm">৳{item.price}</p>
                          <p className="text-zinc-500 text-sm">Stock: {item.stock_quantity}</p>
                        </div>
                      </div>

                      {/* Toggle */}
                      <button
                        onClick={() => handleToggle(item)}
                        disabled={toggling === item.id}
                        className={`relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none shrink-0 ${
                          item.is_available ? 'bg-orange-500' : 'bg-zinc-700'
                        } ${toggling === item.id ? 'opacity-50' : ''}`}
                      >
                        <span
                          className={`absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 ${
                            item.is_available ? 'translate-x-7' : 'translate-x-1'
                          }`}
                        />
                      </button>
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
