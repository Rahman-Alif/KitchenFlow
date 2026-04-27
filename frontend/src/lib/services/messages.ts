// frontend/src/lib/services/messages.ts
import { apiRequest } from '@/lib/api';

export type MessagePriority = 'high' | 'medium' | 'low';
export type MessageTag = 'item_requirement' | 'customer_inquiry' | 'staff_duty' | 'incident' | 'other';

export interface UserSnippet {
  id: number;
  name: string;
}

export interface Message {
  id: number;
  sender_id: number;
  receiver_id: number;
  title: string;
  content: string;
  tag: MessageTag;
  priority: MessagePriority;
  is_read: boolean;
  created_at: string;
  sender: UserSnippet;
  receiver: UserSnippet;
}

export interface PaginatedMessages {
  data: Message[];
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

export interface MessageFilters {
  priority?: string;
  tag?: string;
  page?: number;
}

export async function getMessages(filters: MessageFilters = {}) {
  const params = new URLSearchParams();
  if (filters.priority) params.set('priority', filters.priority);
  if (filters.tag) params.set('tag', filters.tag);
  if (filters.page) params.set('page', String(filters.page));

  const query = params.toString();
  return apiRequest<PaginatedMessages>(`/admin/messages${query ? `?${query}` : ''}`);
}

export async function sendMessage(data: { receiver_id: number; title: string; content: string; tag: string; priority: string }) {
  return apiRequest<{ message: string; data: Message }>('/admin/messages', {
    method: 'POST',
    body: data,
  });
}

export async function markMessageRead(id: number) {
  return apiRequest<{ message: string; data: Message }>(`/admin/messages/${id}/read`, {
    method: 'PATCH',
  });
}

export async function deleteMessage(id: number) {
  return apiRequest<{ message: string }>(`/admin/messages/${id}`, {
    method: 'DELETE',
  });
}

