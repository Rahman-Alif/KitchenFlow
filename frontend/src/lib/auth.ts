export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'kitchen_staff' | 'user';
}

export function getToken(): string | null {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem('token');
}

export function getUser(): AuthUser | null {
  if (typeof window === 'undefined') return null;
  const user = localStorage.getItem('user');
  return user ? JSON.parse(user) : null;
}

export function setAuth(token: string, user: AuthUser): void {
  localStorage.setItem('token', token);
  localStorage.setItem('user', JSON.stringify(user));
}

export function clearAuth(): void {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
}

export function isAuthenticated(): boolean {
  return !!getToken();
}

export function setAuthCookies(token: string, role: string): void {
  document.cookie = `token=${token}; path=/; max-age=86400`;
  document.cookie = `role=${role}; path=/; max-age=86400`;
}

export function clearAuthCookies(): void {
  document.cookie = 'token=; path=/; max-age=0';
  document.cookie = 'role=; path=/; max-age=0';
}