import { apiRequest } from '@/lib/api';

export interface AdminCategory {
  id: number;
  name: string;
  created_at: string;
}

interface CollectionResponse<T> {
  data: T[];
}

interface SingleResponse<T> {
  data: T;
}

export async function getCategories(): Promise<{
  data: AdminCategory[] | null;
  error: string | null;
}> {
  const { data, error } = await apiRequest<CollectionResponse<AdminCategory>>('/admin/categories');
  if (error || !data) return { data: null, error: error ?? 'Failed to fetch categories.' };
  return { data: data.data ?? [], error: null };
}

export async function createCategory(name: string): Promise<{
  data: AdminCategory | null;
  error: string | null;
}> {
  const { data, error } = await apiRequest<SingleResponse<AdminCategory>>('/admin/categories', {
    method: 'POST',
    body: { name },
  });
  if (error || !data) return { data: null, error: error ?? 'Failed to create category.' };
  return { data: data.data, error: null };
}

export async function deleteCategory(categoryId: number): Promise<{ error: string | null }> {
  const { error } = await apiRequest(`/admin/categories/${categoryId}`, {
    method: 'DELETE',
  });
  return { error };
}
