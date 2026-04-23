import { apiRequest } from '@/lib/api';

export type UserRole = 'admin' | 'kitchen_staff' | 'user';

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  is_active: boolean;
  created_at: string;
}

interface CollectionResponse<T> {
  data: T[];
}

interface SingleResponse<T> {
  data: T;
}

interface BulkResponse {
  message: string;
  created: number;
  errors: string[];
}

export async function getUsers(): Promise<{ data: AdminUser[] | null; error: string | null }> {
  const { data, error } = await apiRequest<CollectionResponse<AdminUser>>('/admin/users');
  if (error || !data) return { data: null, error: error ?? 'Failed to fetch users.' };
  return { data: data.data ?? [], error: null };
}

export async function createUser(payload: {
  name: string;
  email: string;
  role: UserRole;
}): Promise<{ data: AdminUser | null; error: string | null }> {
  const { data, error } = await apiRequest<SingleResponse<AdminUser>>('/admin/users', {
    method: 'POST',
    body: payload,
  });
  if (error || !data) return { data: null, error: error ?? 'Failed to create user.' };
  return { data: data.data, error: null };
}

export async function updateUser(
  userId: number,
  payload: Partial<Pick<AdminUser, 'name' | 'email' | 'role'>>
): Promise<{ data: AdminUser | null; error: string | null }> {
  const { data, error } = await apiRequest<SingleResponse<AdminUser>>(`/admin/users/${userId}`, {
    method: 'PUT',
    body: payload,
  });
  if (error || !data) return { data: null, error: error ?? 'Failed to update user.' };
  return { data: data.data, error: null };
}

export async function activateUser(userId: number): Promise<{ error: string | null }> {
  const { error } = await apiRequest(`/admin/users/${userId}/activate`, { method: 'PATCH' });
  return { error };
}

export async function deactivateUser(userId: number): Promise<{ error: string | null }> {
  const { error } = await apiRequest(`/admin/users/${userId}/deactivate`, { method: 'PATCH' });
  return { error };
}

export async function deleteUser(userId: number): Promise<{ error: string | null }> {
  const { error } = await apiRequest(`/admin/users/${userId}`, { method: 'DELETE' });
  return { error };
}

export async function bulkUploadUsers(file: File): Promise<{
  data: BulkResponse | null;
  error: string | null;
}> {
  const formData = new FormData();
  formData.append('file', file);

  const { data, error } = await apiRequest<BulkResponse>('/admin/users/bulk', {
    method: 'POST',
    body: formData,
    isForm: true,
  });

  if (error || !data) return { data: null, error: error ?? 'Bulk upload failed.' };
  return { data, error: null };
}
