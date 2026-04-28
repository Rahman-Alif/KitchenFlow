import { getToken, clearAuth } from './auth';

const BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api';

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

interface ApiOptions {
  method?:  HttpMethod;
  body?:    Record<string, unknown> | FormData;
  isForm?:  boolean;
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

  if (!isForm && body) {
    headers['Content-Type'] = 'application/json';
  }

  const config: RequestInit = { method, headers };

  if (body) {
    config.body = isForm ? (body as FormData) : JSON.stringify(body);
  }

  try {
    const response = await fetch(`${BASE_URL}${endpoint}`, config);

    if (response.status === 401) {
      clearAuth();
      window.location.href = '/login';
      return { data: null, error: 'Unauthenticated.', status: 401 };
    }

    if (response.status === 204) {
      return { data: null, error: null, status: 204 };
    }

    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      return { 
        data: null, 
        error: `Server error (${response.status}). The server did not return a valid JSON response.`, 
        status: response.status 
      };
    }

    const json = await response.json();

    if (!response.ok) {
      const errors = json?.errors as Record<string, string[]> | undefined;
      const message =
        json?.message ??
        (errors ? Object.values(errors)[0]?.[0] : undefined) ??
        'An error occurred.';
      return { data: null, error: message, status: response.status };
    }

    return { data: json as T, error: null, status: response.status };

  } catch (error) {
    console.error('API Request failed:', error);
    return { data: null, error: 'Network error. Please check your connection and ensure the server is running.', status: 0 };
  }
}