// frontend/src/middleware.ts
// Intercepts every page request before it loads.
// Enforces authentication and role-based access.

import { NextRequest, NextResponse } from 'next/server';

// ── Route definitions ─────────────────────────────────────────

const PUBLIC_ROUTES = [
  '/login',
  '/reset-password/request',
  '/reset-password/confirm',
];

const ROLE_ROUTES: Record<string, string[]> = {
  admin:         ['/dashboard', '/users', '/categories', '/menu'],
  kitchen_staff: ['/orders', '/menu'],
  user:          ['/menu', '/orders'],
};

const ROLE_HOME: Record<string, string> = {
  admin:         '/dashboard',
  kitchen_staff: '/orders',
  user:          '/menu',
};

// ── Helpers ───────────────────────────────────────────────────

function isPublicRoute(pathname: string): boolean {
  return PUBLIC_ROUTES.some((route) => pathname.startsWith(route));
}

function isAllowedForRole(pathname: string, role: string): boolean {
  const allowed = ROLE_ROUTES[role] ?? [];
  return allowed.some((route) => pathname.startsWith(route));
}

// ── Middleware ────────────────────────────────────────────────

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Read token and user from cookies.
  // (We will sync localStorage → cookie on login so middleware can read them.
  //  Middleware runs on the server edge — it cannot access localStorage directly.)
  const token = request.cookies.get('kf_token')?.value ?? null;
  const userRaw = request.cookies.get('kf_user')?.value ?? null;

  const isLoggedIn = !!token && !!userRaw;

  // ── Unauthenticated user ──────────────────────────────────

  if (!isLoggedIn) {
    // Allow public routes through
    if (isPublicRoute(pathname)) return NextResponse.next();
    // Redirect everything else to login
    return NextResponse.redirect(new URL('/login', request.url));
  }

  // ── Authenticated user ────────────────────────────────────

  let role: string | null = null;
  try {
    const user = JSON.parse(decodeURIComponent(userRaw!));
    role = user?.role ?? null;
  } catch {
    // Corrupted cookie — clear and redirect to login
    const response = NextResponse.redirect(new URL('/login', request.url));
    response.cookies.delete('kf_token');
    response.cookies.delete('kf_user');
    return response;
  }

  // Logged-in user trying to access public routes → send to their home
  if (isPublicRoute(pathname)) {
    return NextResponse.redirect(new URL(ROLE_HOME[role!] ?? '/login', request.url));
  }

  // Root path → redirect to role home
  if (pathname === '/') {
    return NextResponse.redirect(new URL(ROLE_HOME[role!] ?? '/login', request.url));
  }

  // Role check — wrong role for this route → send to their own home
  if (role && !isAllowedForRole(pathname, role)) {
    return NextResponse.redirect(new URL(ROLE_HOME[role] ?? '/login', request.url));
  }

  return NextResponse.next();
}

// ── Matcher ───────────────────────────────────────────────────
// Run middleware on all routes except Next.js internals and static files.

export const config = {
  matcher: [
    '/((?!_next/static|_next/image|favicon.ico|.*\\.(?:svg|png|jpg|jpeg|gif|webp)$).*)',
  ],
};