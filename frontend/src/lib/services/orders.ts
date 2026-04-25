// frontend/src/lib/services/orders.ts

import { apiRequest } from '@/lib/api';

export type OrderStatus = 'pending' | 'preparing' | 'ready' | 'served' | 'canceled';

export interface OrderItem {
  id:         number;
  name:       string;
  quantity:   number;
  unit_price: string;
  subtotal:   string;
}

export interface OrderTransaction {
  tendered_amount: string;
  change_returned: string;
  recorded_by:     string;
  recorded_at:     string;
}

export interface AdminOrder {
  id:           number;
  status:       OrderStatus;
  total_amount: string;
  notes:        string | null;
  created_at:   string;
  placed_by:    { id: number; name: string };
  items:        OrderItem[];
  transaction:  OrderTransaction | null;
}

export interface OrdersMeta {
  current_page: number;
  last_page:    number;
  total:        number;
  per_page:     number;
}

interface OrdersApiResponse {
  data: AdminOrder[];
  meta: OrdersMeta;
}

export interface OrderFilters {
  date_from?: string;
  date_to?:   string;
  status?:    string;
  search?:    string;
  item?:      string;
  page?:      number;
}

export async function getOrders(filters: OrderFilters = {}): Promise<{
  data:  AdminOrder[] | null;
  meta:  OrdersMeta  | null;
  error: string | null;
}> {
  const params = new URLSearchParams();
  if (filters.date_from) params.set('date_from', filters.date_from);
  if (filters.date_to)   params.set('date_to',   filters.date_to);
  if (filters.status)    params.set('status',     filters.status);
  if (filters.search)    params.set('search',     filters.search);
  if (filters.item)      params.set('item',       filters.item);
  if (filters.page)      params.set('page',       String(filters.page));

  const query = params.toString();
  const response = await apiRequest<OrdersApiResponse>(
    `/admin/orders${query ? `?${query}` : ''}`
  );

  if (response.error || !response.data) {
    return { data: null, meta: null, error: response.error ?? 'Failed to load orders.' };
  }

  return { data: response.data.data, meta: response.data.meta, error: null };
}