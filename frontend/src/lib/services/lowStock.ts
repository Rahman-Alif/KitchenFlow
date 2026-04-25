// frontend/src/lib/services/lowStock.ts

import { apiRequest } from '@/lib/api';

export interface LowStockItem {
  id:              number;
  name:            string;
  category:        string;
  stock_quantity:  number;
  low_stock_threshold: number;
}

interface LowStockApiItem {
  id?:                  number;
  name?:                string;
  category?:            { name?: string };
  stock_quantity?:      number;
  low_stock_threshold?: number;
}

interface LowStockApiResponse {
  data: LowStockApiItem[];
}

export async function getLowStockItems(): Promise<{
  data: LowStockItem[] | null;
  error: string | null;
}> {
  const response = await apiRequest<LowStockApiResponse>('/admin/menu-items/low-stock');

  if (response.error || !response.data) {
    return { data: null, error: response.error ?? 'Failed to load low stock data.' };
  }

  const items = response.data.data.map((item) => ({
    id:                  item.id ?? 0,
    name:                item.name ?? 'Unknown',
    category:            item.category?.name ?? '—',
    stock_quantity:      Number(item.stock_quantity ?? 0),
    low_stock_threshold: Number(item.low_stock_threshold ?? 0),
  }));

  return { data: items, error: null };
}

export async function restockItem(id: number, quantity: number): Promise<{
  error: string | null;
}> {
  const { error } = await apiRequest(`/admin/menu-items/${id}/restock`, {
    method: 'PATCH',
    body:   { quantity },
  });

  return { error: error ?? null };
}