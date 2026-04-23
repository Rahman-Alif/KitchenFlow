// frontend/src/lib/auth.ts
// Manages the auth session.
// Writes to both localStorage (for client-side reads) and
// cookies (so the server-side middleware can read auth state).

const TOKEN_KEY = 'kitchenflow_token';
const USER_KEY  = 'kitchenflow_user';

export type UserRole = 'admin' | 'kitchen_staff' | 'user';

export interface AuthUser {
  id:    number;
  name:  string;
  email: string;
  role:  UserRole;
}

// ── Write ─────────────────────────────────────────────────────

export function saveAuth(token: string, user: AuthUser): void {
  // localStorage — for client components
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify(user));

  // Cookies — for middleware (server edge cannot read localStorage)
  const expires = new Date();
  expires.setDate(expires.getDate() + 7); // 7 day expiry

  document.cookie = `kf_token=${token}; path=/; expires=${expires.toUTCString()}; SameSite=Lax`;
  document.cookie = `kf_user=${encodeURIComponent(JSON.stringify(user))}; path=/; expires=${expires.toUTCString()}; SameSite=Lax`;
}

// ── Read ──────────────────────────────────────────────────────

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY);
}

export function getUser(): AuthUser | null {
  const raw = localStorage.getItem(USER_KEY);
  if (!raw) return null;
  try {
    return JSON.parse(raw) as AuthUser;
  } catch {
    return null;
  }
}

export function isAuthenticated(): boolean {
  return getToken() !== null && getUser() !== null;
}

// ── Clear ─────────────────────────────────────────────────────

export function clearAuth(): void {
  // Clear localStorage
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);

  // Clear cookies — set expiry in the past to delete them
  document.cookie = 'kf_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax';
  document.cookie = 'kf_user=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax';
}

// ── Role helpers ──────────────────────────────────────────────

export function getHomeRoute(role: UserRole): string {
  switch (role) {
    case 'admin':         return '/dashboard';
    case 'kitchen_staff': return '/orders';
    case 'user':          return '/menu';
  }
}