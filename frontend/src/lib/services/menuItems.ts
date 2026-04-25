import { apiRequest } from '@/lib/api';

export interface MenuItemCategory {
  id: number;
  name: string;
}

export interface AdminMenuItem {
  id: number;
  category: MenuItemCategory;
  name: string;
  description: string | null;
  image_url: string | null;
  price: string | number;
  stock_quantity: number;
  low_stock_threshold: number;
  is_available: boolean;
  created_at: string;
}

interface CollectionResponse<T> {
  data: T[];
}

interface SingleResponse<T> {
  data: T;
}

export interface MenuItemPayload {
  category_id: number;
  name: string;
  description: string;
  price: number;
  stock_quantity: number;
  low_stock_threshold: number;
  is_available: boolean;
  image?: File | null;
}

function toFormData(payload: MenuItemPayload, methodSpoof?: 'PUT'): FormData {
  const form = new FormData();
  form.append('category_id', String(payload.category_id));
  form.append('name', payload.name);
  form.append('description', payload.description);
  form.append('price', String(payload.price));
  form.append('stock_quantity', String(payload.stock_quantity));
  form.append('low_stock_threshold', String(payload.low_stock_threshold));
  form.append('is_available', payload.is_available ? '1' : '0');

  if (payload.image) {
    form.append('image', payload.image);
  }

  if (methodSpoof) {
    form.append('_method', methodSpoof);
  }

  return form;
}

export async function getMenuItems(): Promise<{ data: AdminMenuItem[] | null; error: string | null }> {
  const { data, error } = await apiRequest<CollectionResponse<AdminMenuItem>>('/admin/menu-items');
  if (error || !data) return { data: null, error: error ?? 'Failed to fetch menu items.' };
  return { data: data.data ?? [], error: null };
}

export async function updateMenuItemAvailability(
  id: number,
  isAvailable: boolean
): Promise<{ error: string | null }> {
  const { error } = await apiRequest(`/admin/menu-items/${id}/availability`, {
    method: 'PATCH',
    body:   { is_available: isAvailable },
  });
  return { error: error ?? null };
}

export async function getMenuItem(menuItemId: number): Promise<{
  data: AdminMenuItem | null;
  error: string | null;
}> {
  const { data, error } = await apiRequest<SingleResponse<AdminMenuItem>>(`/admin/menu-items/${menuItemId}`);
  if (error || !data) return { data: null, error: error ?? 'Failed to fetch menu item.' };
  return { data: data.data, error: null };
}

export async function createMenuItem(payload: MenuItemPayload): Promise<{
  data: AdminMenuItem | null;
  error: string | null;
}> {
  const form = toFormData(payload);
  const { data, error } = await apiRequest<SingleResponse<AdminMenuItem>>('/admin/menu-items', {
    method: 'POST',
    body: form,
    isForm: true,
  });
  if (error || !data) return { data: null, error: error ?? 'Failed to create menu item.' };
  return { data: data.data, error: null };
}

export async function updateMenuItem(menuItemId: number, payload: MenuItemPayload): Promise<{
  data: AdminMenuItem | null;
  error: string | null;
}> {
  const form = toFormData(payload, 'PUT');
  const { data, error } = await apiRequest<SingleResponse<AdminMenuItem>>(`/admin/menu-items/${menuItemId}`, {
    method: 'POST',
    body: form,
    isForm: true,
  });
  if (error || !data) return { data: null, error: error ?? 'Failed to update menu item.' };
  return { data: data.data, error: null };
}

export async function restockMenuItem(menuItemId: number, quantity: number): Promise<{ error: string | null }> {
  const { error } = await apiRequest(`/admin/menu-items/${menuItemId}/restock`, {
    method: 'PATCH',
    body: { quantity },
  });
  return { error };
}

export async function deleteMenuItem(menuItemId: number): Promise<{ error: string | null }> {
  const { error } = await apiRequest(`/admin/menu-items/${menuItemId}`, { method: 'DELETE' });
  return { error };
}
