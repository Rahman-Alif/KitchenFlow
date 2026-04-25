// frontend/src/lib/services/orders.ts

import { apiRequest } from '@/lib/api';

// ─── Types ────────────────────────────────────────────────

export type OrderStatus = 'pending' | 'preparing' | 'ready' | 'served' | 'canceled';

export interface OrderItem {
  id:         number;
  name:       string;
  quantity:   number;
  unit_price: string;
  subtotal?:  string;
}

export interface OrderTransaction {
  tendered_amount: string;
  change_returned: string;
  recorded_by:     string;
  recorded_at:     string;
}

export interface Transaction {
  id:              number;
  tendered_amount: string;
  change_returned: string;
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

export interface Order {
  id:           number;
  status:       OrderStatus;
  total_amount: string;
  notes:        string | null;
  placed_by:    { id: number; name: string };
  items:        OrderItem[];
  created_at:   string;
  transaction?: Transaction | null;
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

export interface KitchenMenuItem {
  id:                  number;
  name:                string;
  description:         string | null;
  image_path:          string | null;
  category:            { id: number; name: string };
  price:               string;
  stock_quantity:      number;
  low_stock_threshold: number;
  is_available:        boolean;
  is_low_stock:        boolean;
  needs_restock:       boolean;
  requested_restock_quantity: number | null;
}

export interface UserMenuItem {
  id:          number;
  name:        string;
  description: string | null;
  image_path:  string | null;
  price:       string;
  category:    { id: number; name: string };
}

export interface MenuCategory {
  category_id:   number;
  category_name: string;
  items:         UserMenuItem[];
}

export interface CartItem {
  menu_item_id: number;
  name:         string;
  price:        string;
  quantity:     number;
}

// ─── Admin — Orders ───────────────────────────────────────

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

// ─── Kitchen Staff — Menu & Orders ────────────────────────

export async function getOrderQueue() {
  return apiRequest<{ data: Order[] }>('/kitchen/orders');
}

export async function getOrderDetail(id: number) {
  return apiRequest<{ data: Order }>(`/kitchen/orders/${id}`);
}

export async function updateOrderStatus(id: number, status: string) {
  return apiRequest<{ data: Order }>(`/kitchen/orders/${id}/status`, {
    method: 'PATCH',
    body:   { status },
  });
}

export async function recordTransaction(id: number, tendered_amount: number) {
  return apiRequest<{ data: Transaction }>(`/kitchen/orders/${id}/transaction`, {
    method: 'POST',
    body:   { tendered_amount },
  });
}

export async function getKitchenMenu() {
  return apiRequest<{ data: KitchenMenuItem[] }>('/kitchen/menu');
}

export async function updateAvailability(id: number, is_available: boolean) {
  return apiRequest<{ data: KitchenMenuItem }>(`/kitchen/menu/${id}/availability`, {
    method: 'PATCH',
    body:   { is_available },
  });
}

export async function requestRestock(id: number, quantity?: number) {
  return apiRequest<{ data: KitchenMenuItem }>(`/kitchen/menu/${id}/request-restock`, {
    method: 'POST',
    body: quantity ? { quantity } : undefined,
  });
}

// ─── User — Menu & Orders ─────────────────────────────────

export async function getUserMenu() {
  return apiRequest<{ data: MenuCategory[] }>('/user/menu');
}

export async function placeOrder(items: { menu_item_id: number; quantity: number }[], notes?: string) {
  return apiRequest<{ data: Order }>('/user/orders', {
    method: 'POST',
    body:   { items, notes },
  });
}

export async function getUserOrder(id: number) {
  return apiRequest<{ data: Order }>(`/user/orders/${id}`);
}

export async function getUserOrders() {
  return apiRequest<{ data: Order[] }>('/user/orders');
}

export async function updateUserOrder(
  orderId: number,
  items: { order_item_id: number; quantity: number }[],
  notes?: string
) {
  return apiRequest<{ data: Order }>(`/user/orders/${orderId}`, {
    method: 'PATCH',
    body:   { items, notes },
  });
}

export async function cancelUserOrder(orderId: number) {
  return apiRequest<{ data: Order }>(`/user/orders/${orderId}/cancel`, {
    method: 'PATCH',
  });
}
