// frontend/src/lib/api.ts
// Single fetch wrapper for all backend calls.
// Automatically attaches the Bearer token and handles 401 globally.

import { getToken, clearAuth } from './auth';

const BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api';

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

interface ApiOptions {
  method?:  HttpMethod;
  body?:    Record<string, unknown> | FormData;
  isForm?:  boolean; // set true when sending multipart/form-data (image uploads)
}

interface ApiResponse<T> {
  data:   T | null;
  error:  string | null;
  status: number;
}

export async function apiRequest<T>(
  endpoint: string,
  options: ApiOptions = {}
): Promise<ApiResponse<T>> {
  const { method = 'GET', body, isForm = false } = options;

  const headers: Record<string, string> = {
    Accept: 'application/json',
  };

  const token = getToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  // For regular JSON requests, set Content-Type.
  // For FormData, let the browser set it (includes boundary).
  if (!isForm && body) {
    headers['Content-Type'] = 'application/json';
  }

  const config: RequestInit = {
    method,
    headers,
  };

  if (body) {
    config.body = isForm
      ? (body as FormData)
      : JSON.stringify(body);
  }

  try {
    const response = await fetch(`${BASE_URL}${endpoint}`, config);

    // Token expired or invalid — clear session and redirect to login
    if (response.status === 401) {
      clearAuth();
      window.location.href = '/login';
      return { data: null, error: 'Unauthenticated.', status: 401 };
    }

    // No content (e.g. DELETE success)
    if (response.status === 204) {
      return { data: null, error: null, status: 204 };
    }

    const json = await response.json();

    if (!response.ok) {
      // Laravel validation errors come back as { message, errors: { field: [msg] } }
      const errors = json?.errors as Record<string, string[]> | undefined;
      const message =
        json?.message ??
        (errors ? Object.values(errors)[0]?.[0] : undefined) ??
        'An error occurred.';
      return { data: null, error: message, status: response.status };
    }

    return { data: json as T, error: null, status: response.status };

  } catch {
    return { data: null, error: 'Network error. Please try again.', status: 0 };
  }
}